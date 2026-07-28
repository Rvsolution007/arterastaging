<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Models\BusinessAiGeneration;
use App\Models\StorageSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/** One Custom Post request always results in one image-provider call. */
class BusinessAiImageService
{
    public function generate(BusinessAiGeneration $generation): array
    {
        if ($generation->provider !== 'openai') {
            throw new RuntimeException('The selected image provider is not available for Custom Post AI yet.');
        }
        $apiKey = trim((string) AiSetting::getAiSetting('chatgpt_api_key'));
        if ($apiKey === '') {
            throw new RuntimeException('Custom Post AI is not configured by the administrator.');
        }

        $references = $this->referenceImages((array) $generation->product_snapshot);
        $expected = count((array) $generation->product_snapshot);
        if (count($references) !== $expected) {
            throw new RuntimeException('One or more selected product photos could not be read. Please upload them again; your credit was restored.');
        }

        $payload = [
            'model' => $generation->provider_model_id,
            'prompt' => $generation->final_prompt,
            'quality' => $generation->quality,
            'size' => $generation->size_value,
            'n' => 1,
        ];
        $endpoint = $references === [] ? '/v1/images/generations' : '/v1/images/edits';
        $generation->update(['request_diagnostics' => array_merge((array) $generation->request_diagnostics, [
            'endpoint' => $endpoint, 'attached_reference_count' => count($references),
            'image_generation_count' => 1, 'branding_render_mode' => 'editable_overlay_manifest',
        ])]);

        $request = Http::withToken($apiKey)->acceptJson()->timeout(150);
        try {
            if ($references === []) {
                $response = $request->asJson()->post('https://api.openai.com/v1/images/generations', $payload);
            } else {
                foreach ($references as $reference) {
                    $request = $request->attach('image[]', $reference['contents'], $reference['filename']);
                }
                $response = $request->post('https://api.openai.com/v1/images/edits', $payload);
            }
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Custom Post AI could not connect to the image service.', 0, $exception);
        }
        if (!$response->successful()) {
            $message = trim((string) data_get($response->json(), 'error.message'));
            throw new RuntimeException('Custom Post AI image generation failed' . ($message !== '' ? ': ' . $message : '.'));
        }

        $binary = base64_decode((string) data_get($response->json(), 'data.0.b64_json'), true);
        if ($binary === false || $binary === '') {
            throw new RuntimeException('The image provider returned an unreadable image result.');
        }
        $relativePath = 'business_ai/' . Str::uuid() . '.png';
        if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') {
            if (!Storage::disk('spaces')->put('uploads/' . $relativePath, $binary, 'public')) {
                throw new RuntimeException('Custom Post AI could not save the image. Your credit was restored.');
            }
        } else {
            $directory = public_path('uploads/business_ai');
            File::ensureDirectoryExists($directory);
            if (file_put_contents($directory . DIRECTORY_SEPARATOR . basename($relativePath), $binary) === false) {
                throw new RuntimeException('Custom Post AI could not save the image. Your credit was restored.');
            }
        }
        return ['path' => $relativePath, 'usage' => (array) data_get($response->json(), 'usage', [])];
    }

    private function referenceImages(array $products): array
    {
        return collect($products)->map(function ($product) {
            $path = is_array($product) ? trim((string) ($product['image'] ?? '')) : '';
            $contents = $path !== '' ? $this->imageContents($path) : null;
            return $contents === null ? null : ['contents' => $contents, 'filename' => basename(parse_url($path, PHP_URL_PATH) ?: $path) ?: 'product.png'];
        })->filter()->values()->all();
    }

    private function imageContents(string $image): ?string
    {
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            try {
                $response = Http::timeout(20)->get($image);
                return $response->successful() ? $this->validImage($response->body()) : null;
            } catch (\Throwable) { return null; }
        }
        $relative = ltrim((string) preg_replace('#^/?uploads/#i', '', str_replace('\\', '/', $image)), '/');
        if (preg_match('#(^|/)\.{1,2}(/|$)#', $relative)) return null;
        foreach ([public_path('uploads'), base_path('uploads')] as $root) {
            $rootPath = realpath($root);
            $candidate = realpath($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
            if ($rootPath && $candidate && str_starts_with(strtolower($candidate), strtolower(rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) && is_file($candidate)) {
                $contents = file_get_contents($candidate);
                return $contents === false ? null : $this->validImage($contents);
            }
        }
        if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean' && Storage::disk('spaces')->exists('uploads/' . $relative)) {
            return $this->validImage(Storage::disk('spaces')->get('uploads/' . $relative));
        }
        return null;
    }

    private function validImage(string $contents): ?string
    {
        return $contents !== '' && strlen($contents) <= 12 * 1024 * 1024 && (!function_exists('getimagesizefromstring') || @getimagesizefromstring($contents) !== false) ? $contents : null;
    }
}
