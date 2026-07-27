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
    public function __construct(private FestivalAiBrandComposer $brandComposer)
    {
    }

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

        $references = $this->referenceImages((array) $generation->product_snapshot);
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
            throw new RuntimeException($this->providerFailureMessage(
                $response->status(),
                $providerCode,
                $providerMessage
            ));
        }

        $responsePayload = (array) $response->json();
        $base64 = data_get($responsePayload, 'data.0.b64_json');
        if (!is_string($base64) || $base64 === '') {
            throw new RuntimeException('The image provider returned an invalid image result.');
        }

        $binary = base64_decode($base64, true);
        if ($binary === false) {
            throw new RuntimeException('The image provider returned unreadable image data.');
        }

        // The provider receives the admin's header/footer visual prompts.
        // This pass places the exact current business logo/details deterministically
        // and never depends on frame/editor rendering or AI-generated text.
        $binary = $this->brandComposer->compose(
            $binary,
            (array) $generation->business_snapshot,
            (array) $generation->brand_chrome_snapshot
        );

        $fileName = Str::uuid() . '.png';
        $relativePath = 'festival_ai/' . $fileName;

        if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') {
            Storage::disk('spaces')->put('uploads/' . $relativePath, $binary, 'public');
        } else {
            $directory = public_path('uploads/festival_ai');
            File::ensureDirectoryExists($directory);
            file_put_contents($directory . DIRECTORY_SEPARATOR . $fileName, $binary);
        }

        return [
            'path' => $relativePath,
            'usage' => (array) data_get($responsePayload, 'usage', []),
        ];
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

    private function imageContents(string $image): ?string
    {
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            $response = Http::timeout(20)->get($image);
            return $response->successful() ? $response->body() : null;
        }

        $relativePath = ltrim(str_replace('uploads/', '', $image), '/\\');
        $localPaths = [
            public_path('uploads/' . $relativePath),
            base_path('uploads/' . $relativePath),
        ];

        foreach ($localPaths as $path) {
            if (is_file($path)) {
                return file_get_contents($path) ?: null;
            }
        }

        if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') {
            $remotePath = 'uploads/' . $relativePath;
            if (Storage::disk('spaces')->exists($remotePath)) {
                return Storage::disk('spaces')->get($remotePath);
            }
        }

        return null;
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
