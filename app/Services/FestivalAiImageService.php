<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Models\FestivalAiGeneration;
use App\Models\StorageSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class FestivalAiImageService
{
    public function generate(FestivalAiGeneration $generation): array
    {
        if ($generation->provider !== 'openai') {
            throw new RuntimeException('The selected image provider is not available for Festival AI yet.');
        }

        $apiKey = trim((string) AiSetting::getAiSetting('chatgpt_api_key'));
        if ($apiKey === '') {
            throw new RuntimeException('Artera AI image generation is not configured by the administrator.');
        }

        $payload = [
            'model' => $generation->provider_model_id,
            'prompt' => $generation->final_prompt,
            'quality' => $generation->quality,
            'size' => $generation->size_value,
            'n' => 1,
        ];

        $expectedProductReferenceCount = collect((array) $generation->product_snapshot)
            ->filter(fn ($product) => is_array($product))
            ->count();
        $references = $this->referenceImages((array) $generation->product_snapshot);
        $brandLogoReference = $this->brandLogoReference(
            (array) $generation->business_snapshot,
            (array) $generation->brand_chrome_snapshot
        );
        $brandLogoExpected = $this->brandLogoExpected(
            (array) $generation->business_snapshot,
            (array) $generation->brand_chrome_snapshot
        );
        if ($brandLogoReference !== null) {
            $references[] = $brandLogoReference;
        }
        $expectedReferenceCount = $expectedProductReferenceCount + ($brandLogoExpected ? 1 : 0);

        if (count($references) !== $expectedReferenceCount) {
            $this->updateDiagnostics($generation, [
                'expected_reference_count' => $expectedReferenceCount,
                'attached_reference_count' => count($references),
                'expected_product_reference_count' => $expectedProductReferenceCount,
                'expected_brand_logo_reference' => $brandLogoExpected,
                'reference_validation' => 'failed',
            ]);

            throw new RuntimeException(
                'Artera AI could not read every selected product image or the current business logo. '
                . 'Please upload it again in My Business, then try again; your quota was restored.'
            );
        }

        $endpoint = empty($references) ? '/v1/images/generations' : '/v1/images/edits';
        $generation->update(['actual_reference_count' => count($references)]);
        $this->updateDiagnostics($generation, [
            'endpoint' => $endpoint,
            'expected_reference_count' => $expectedReferenceCount,
            'attached_reference_count' => count($references),
            'expected_product_reference_count' => $expectedProductReferenceCount,
            'expected_brand_logo_reference' => $brandLogoExpected,
            'reference_validation' => 'passed',
        ]);

        $request = Http::withToken($apiKey)->acceptJson()->timeout(150);

        try {
            if (empty($references)) {
                $response = $request->asJson()->post('https://api.openai.com/v1/images/generations', $payload);
            } else {
                foreach ($references as $reference) {
                    $request = $request->attach('image[]', $reference['contents'], $reference['filename']);
                }
                $response = $request->post('https://api.openai.com/v1/images/edits', $payload);
            }
        } catch (ConnectionException $exception) {
            Log::warning('Festival AI provider could not be reached.', [
                'generation_id' => $generation->id,
                'provider' => $generation->provider,
            ]);

            throw new RuntimeException('Artera AI could not connect to the image service. Check this server’s internet or firewall connection, then try again.');
        }

        if (!$response->successful()) {
            $providerMessage = trim((string) data_get($response->json(), 'error.message'));
            $providerCode = trim((string) data_get($response->json(), 'error.code'));
            Log::warning('Festival AI provider request failed.', [
                'generation_id' => $generation->id,
                'status' => $response->status(),
                'provider' => $generation->provider,
                'model' => $generation->provider_model_id,
                'provider_code' => $providerCode ?: null,
            ]);
            $this->updateDiagnostics($generation, [
                'provider_status_code' => $response->status(),
                'provider_error_code' => $providerCode ?: null,
            ]);
            throw new RuntimeException($this->providerFailureMessage(
                $response->status(),
                $providerCode,
                $providerMessage
            ));
        }

        $responsePayload = (array) $response->json();
        $this->updateDiagnostics($generation, [
            'provider_request_id' => $response->header('x-request-id'),
            'provider_status_code' => $response->status(),
            'provider_response_created' => data_get($responsePayload, 'created'),
        ]);
        $base64 = data_get($responsePayload, 'data.0.b64_json');
        if (!is_string($base64) || $base64 === '') {
            throw new RuntimeException('The image provider returned an invalid image result.');
        }

        $binary = base64_decode($base64, true);
        if ($binary === false) {
            throw new RuntimeException('The image provider returned unreadable image data.');
        }

        // Brand/header/footer are deliberately generated by the provider as part
        // of one poster. Never draw an extra logo, name or contact strip over it.
        $this->updateDiagnostics($generation, [
            'branding_render_mode' => 'provider_prompt_and_reference_image',
            'branding_overlay_requested' => false,
            'branding_overlay_applied' => false,
            'branding_overlay_result' => 'disabled_by_provider_render_mode',
        ]);

        $fileName = Str::uuid() . '.png';
        $relativePath = 'festival_ai/' . $fileName;

        try {
            if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') {
                $stored = Storage::disk('spaces')->put('uploads/' . $relativePath, $binary, 'public');
                if (!$stored) {
                    throw new RuntimeException('Artera AI could not save the generated image. Your quota was restored.');
                }
            } else {
                $directory = public_path('uploads/festival_ai');
                File::ensureDirectoryExists($directory);
                $stored = file_put_contents($directory . DIRECTORY_SEPARATOR . $fileName, $binary);
                if ($stored === false) {
                    throw new RuntimeException('Artera AI could not save the generated image. Your quota was restored.');
                }
            }
        } catch (\Throwable $exception) {
            if ($exception instanceof RuntimeException
                && str_starts_with($exception->getMessage(), 'Artera AI could not save')) {
                throw $exception;
            }
            throw new RuntimeException(
                'Artera AI could not save the generated image. Your quota was restored.',
                0,
                $exception
            );
        }

        return [
            'path' => $relativePath,
            'usage' => (array) data_get($responsePayload, 'usage', []),
        ];
    }

    private function updateDiagnostics(FestivalAiGeneration $generation, array $updates): void
    {
        $diagnostics = array_merge((array) $generation->request_diagnostics, array_filter(
            $updates,
            static fn ($value) => $value !== null
        ));
        $generation->update(['request_diagnostics' => $diagnostics]);
        $generation->setAttribute('request_diagnostics', $diagnostics);
    }

    private function referenceImages(array $products): array
    {
        $references = [];

        foreach ($products as $product) {
            $image = is_array($product) ? ($product['image'] ?? null) : null;
            if (!is_string($image) || trim($image) === '') {
                continue;
            }

            $contents = $this->imageContents($image);
            if ($contents === null) {
                continue;
            }

            $references[] = [
                'contents' => $contents,
                'filename' => basename(parse_url($image, PHP_URL_PATH) ?: $image) ?: 'product.png',
            ];
        }

        return $references;
    }

    private function brandLogoExpected(array $business, array $brandChrome): bool
    {
        return (bool) ($brandChrome['overlay_enabled'] ?? true)
            && is_string($business['logo_path'] ?? null)
            && trim((string) $business['logo_path']) !== '';
    }

    private function brandLogoReference(array $business, array $brandChrome): ?array
    {
        if (!$this->brandLogoExpected($business, $brandChrome)) {
            return null;
        }

        $image = trim((string) $business['logo_path']);
        $contents = $this->imageContents($image);
        if ($contents === null) {
            return null;
        }

        return [
            'contents' => $contents,
            'filename' => 'business-logo-' . (basename(parse_url($image, PHP_URL_PATH) ?: $image) ?: 'logo.png'),
        ];
    }

    private function imageContents(string $image): ?string
    {
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            try {
                $response = Http::timeout(20)->get($image);
                if (!$response->successful()) {
                    return null;
                }

                return $this->validImageContents($response->body());
            } catch (\Throwable) {
                return null;
            }
        }

        // Strip only an actual leading uploads/ segment. The previous global
        // replacement corrupted valid paths such as festival_ai_uploads/x.jpg.
        $normalised = str_replace('\\', '/', trim($image));
        $relativePath = preg_replace('#^/?uploads/#i', '', $normalised) ?? $normalised;
        $relativePath = ltrim($relativePath, '/');
        if (preg_match('#(^|/)\.{1,2}(/|$)#', $relativePath)) {
            return null;
        }
        foreach ([public_path('uploads'), base_path('uploads')] as $root) {
            $rootPath = realpath($root);
            $candidate = realpath($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
            if (
                $rootPath !== false
                && $candidate !== false
                && str_starts_with(
                    strtolower($candidate),
                    strtolower(rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
                )
                && is_file($candidate)
            ) {
                $contents = file_get_contents($candidate);
                return $contents === false ? null : $this->validImageContents($contents);
            }
        }

        if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') {
            $remotePath = 'uploads/' . $relativePath;
            if (Storage::disk('spaces')->exists($remotePath)) {
                return $this->validImageContents(Storage::disk('spaces')->get($remotePath));
            }
        }

        return null;
    }

    private function validImageContents(string $contents): ?string
    {
        if ($contents === '' || strlen($contents) > 12 * 1024 * 1024) {
            return null;
        }

        if (function_exists('getimagesizefromstring') && @getimagesizefromstring($contents) === false) {
            return null;
        }

        return $contents;
    }

    private function providerFailureMessage(int $status, string $code, string $message): string
    {
        $normalised = strtolower($code . ' ' . $message);
        $detail = $message !== '' ? $message : null;

        if ($status === 401 || str_contains($normalised, 'invalid_api_key')) {
            return 'Artera AI connection was rejected' . ($detail ? ': ' . $detail : '. Please update it in AI credentials.');
        }

        if ($status === 402 || str_contains($normalised, 'insufficient_quota') || str_contains($normalised, 'billing') || str_contains($normalised, 'exceeded your current quota')) {
            return 'Artera AI billing or credits are unavailable' . ($detail ? ': ' . $detail : '. Add API billing/credits, then try again.');
        }

        if ($status === 429) {
            return 'Artera AI rate limit reached' . ($detail ? ': ' . $detail : '. Please try again shortly.');
        }

        return 'Artera AI image generation failed' . ($detail ? ': ' . $detail : '.');
    }
}
