<?php

namespace App\Services;

use App\Models\AiSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Plans only the editable overlay for an already-requested one-image poster.
 * It never asks the image API for a background, product cut-out or icon.
 * That is the core V1 cost rule: one provider image result per generation.
 */
class AiEditableLayerPlanningService
{
    public function plan(object $generation): array
    {
        $model = trim((string) config('ai_editable_v1.planner_model'));
        $apiKey = trim((string) AiSetting::getAiSetting('chatgpt_api_key'));
        if ($model === '' || $apiKey === '') {
            // A Business Post must not lose editability merely because the
            // optional semantic planner is temporarily unconfigured. The
            // deterministic plan still creates editable, zero-image-cost
            // overlays from the submitted brief.
            return $this->fallbackPlan($generation, $this->canvasSize($generation->size_value));
        }

        $canvas = $this->canvasSize($generation->size_value);
        $payload = [
            'model' => $model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => 'You plan editable overlay layers for a professional mobile business poster. The visual artwork is one locked bitmap, already generated separately. Return only the requested JSON. Keep copy concise, place it in safe zones, and never create product bitmap layers.',
                    ]],
                ],
                [
                    'role' => 'user',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => json_encode([
                            'poster_request' => $generation->final_prompt,
                            // The user approved this copy before the artwork
                            // was queued. It is a normal editable overlay
                            // source, not a frame/render-version payload.
                            'approved_content_preview' => (array) ($generation->content_preview ?? []),
                            'canvas' => $canvas,
                            'business' => [
                                'name' => data_get($generation->business_snapshot, 'name'),
                                'phones' => data_get($generation->business_snapshot, 'phones', []),
                                'emails' => data_get($generation->business_snapshot, 'emails', []),
                                'websites' => data_get($generation->business_snapshot, 'websites', []),
                                'addresses' => data_get($generation->business_snapshot, 'addresses', []),
                                'has_logo' => filled(data_get($generation->business_snapshot, 'logo_path')),
                            ],
                            'products' => collect((array) $generation->product_snapshot)
                                ->map(fn ($product) => [
                                    'name' => data_get($product, 'title'),
                                    'description' => Str::limit((string) data_get($product, 'description'), 180),
                                ])
                                ->values()
                                ->all(),
                        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    ]],
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'ai_editable_v1_overlay_plan',
                    'strict' => true,
                    'schema' => $this->schema(),
                ],
            ],
        ];

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(90)
                ->post('https://api.openai.com/v1/responses', $payload);
        } catch (ConnectionException) {
            return $this->fallbackPlan($generation, $canvas);
        }

        if (!$response->successful()) {
            return $this->fallbackPlan($generation, $canvas);
        }

        $decoded = json_decode($this->outputText((array) $response->json()), true);
        if (!is_array($decoded)) {
            return $this->fallbackPlan($generation, $canvas);
        }

        return $this->normalise($decoded, $canvas);
    }

    private function schema(): array
    {
        $bounds = [
            'x' => ['type' => 'number'], 'y' => ['type' => 'number'],
            'width' => ['type' => 'number'], 'height' => ['type' => 'number'],
        ];
        $text = [
            'type' => 'object', 'additionalProperties' => false,
            'required' => ['name', 'text', 'role', 'font_token', 'x', 'y', 'width', 'height', 'color'],
            'properties' => array_merge([
                'name' => ['type' => 'string'], 'text' => ['type' => 'string'],
                'role' => ['type' => 'string', 'enum' => ['heading', 'subheading', 'body', 'cta', 'contact']],
                'font_token' => ['type' => 'string', 'enum' => ['display', 'serif', 'sans', 'devanagari']],
                'color' => ['type' => 'string'],
            ], $bounds),
        ];
        $shape = [
            'type' => 'object', 'additionalProperties' => false,
            'required' => ['name', 'kind', 'color', 'radius', 'x', 'y', 'width', 'height'],
            'properties' => array_merge([
                'name' => ['type' => 'string'],
                'kind' => ['type' => 'string', 'enum' => ['rectangle', 'pill', 'circle']],
                'color' => ['type' => 'string'], 'radius' => ['type' => 'number'],
            ], $bounds),
        ];
        $icon = [
            'type' => 'object', 'additionalProperties' => false,
            'required' => ['name', 'icon_name', 'color', 'x', 'y', 'width', 'height'],
            'properties' => array_merge([
                'name' => ['type' => 'string'],
                'icon_name' => ['type' => 'string', 'enum' => ['phone', 'whatsapp', 'email', 'website', 'location', 'star', 'arrow']],
                'color' => ['type' => 'string'],
            ], $bounds),
        ];

        return [
            'type' => 'object', 'additionalProperties' => false,
            'required' => ['palette', 'text_layers', 'shapes', 'icons'],
            'properties' => [
                'palette' => ['type' => 'array', 'minItems' => 2, 'maxItems' => 4, 'items' => ['type' => 'string']],
                'text_layers' => ['type' => 'array', 'maxItems' => 6, 'items' => $text],
                'shapes' => ['type' => 'array', 'maxItems' => 5, 'items' => $shape],
                'icons' => ['type' => 'array', 'maxItems' => 4, 'items' => $icon],
            ],
        ];
    }

    private function outputText(array $payload): string
    {
        if (is_string($payload['output_text'] ?? null) && $payload['output_text'] !== '') {
            return $payload['output_text'];
        }
        foreach ((array) ($payload['output'] ?? []) as $item) {
            foreach ((array) ($item['content'] ?? []) as $content) {
                if (is_string($content['text'] ?? null) && $content['text'] !== '') {
                    return $content['text'];
                }
            }
        }
        throw new RuntimeException('AI Editable V1 returned no layer plan.');
    }

    private function normalise(array $plan, array $canvas): array
    {
        $palette = array_values(array_filter(array_map(fn ($color) => $this->color($color), (array) ($plan['palette'] ?? []))));
        if (count($palette) < 2) {
            $palette = ['#4F46E5', '#EC4899'];
        }

        $normaliseBounds = function (array $item) use ($canvas): array {
            $item['x'] = $this->coordinate($item['x'] ?? 0, $canvas['width']);
            $item['y'] = $this->coordinate($item['y'] ?? 0, $canvas['height']);
            $item['width'] = $this->dimension($item['width'] ?? $canvas['width'] / 2, $canvas['width']);
            $item['height'] = $this->dimension($item['height'] ?? $canvas['height'] / 4, $canvas['height']);
            return $item;
        };
        $texts = array_map($normaliseBounds, array_slice((array) ($plan['text_layers'] ?? []), 0, 6));
        $shapes = array_map($normaliseBounds, array_slice((array) ($plan['shapes'] ?? []), 0, 5));
        $icons = array_map($normaliseBounds, array_slice((array) ($plan['icons'] ?? []), 0, 4));
        foreach ([$texts, $shapes, $icons] as $items) {
            foreach ($items as $item) {
                // Bounds are now normalised; detailed field defaults are applied by the assembler.
            }
        }

        return compact('canvas', 'palette', 'texts', 'shapes', 'icons');
    }

    private function fallbackPlan(object $generation, array $canvas): array
    {
        $approvedPreview = (array) ($generation->content_preview ?? []);
        $brief = collect((array) ($generation->brief ?? []))
            ->filter(fn ($value) => is_scalar($value) && trim((string) $value) !== '')
            ->map(fn ($value) => Str::limit(Str::squish((string) $value), 120))
            ->values();
        $title = trim((string) ($approvedPreview['headline'] ?? '')) ?: ($brief->first() ?: (string) ($generation->purpose_title ?? 'Your Business Post'));
        $support = trim((string) ($approvedPreview['content'] ?? '')) ?: ($brief->skip(1)->first() ?: trim((string) data_get($generation->business_snapshot, 'name')));
        $cta = trim((string) ($approvedPreview['cta'] ?? ''));
        $phone = collect((array) data_get($generation->business_snapshot, 'phones', []))->first();
        $width = $canvas['width'];
        $height = $canvas['height'];

        $texts = [
            ['name' => 'Headline', 'text' => $title, 'role' => 'heading', 'font_token' => 'display', 'color' => '#FFFFFF', 'x' => $width * .07, 'y' => $height * .12, 'width' => $width * .58, 'height' => $height * .22],
        ];
        if ($support !== '') {
            $texts[] = ['name' => 'Supporting text', 'text' => $support, 'role' => 'subheading', 'font_token' => 'sans', 'color' => '#FFFFFF', 'x' => $width * .07, 'y' => $height * .36, 'width' => $width * .52, 'height' => $height * .10];
        }
        if ($cta !== '') {
            $texts[] = ['name' => 'Call to action', 'text' => $cta, 'role' => 'cta', 'font_token' => 'sans', 'color' => '#FFFFFF', 'x' => $width * .16, 'y' => $height * .755, 'width' => $width * .42, 'height' => $height * .05];
        }
        if (is_string($phone) && trim($phone) !== '') {
            $texts[] = ['name' => 'Contact', 'text' => trim($phone), 'role' => 'contact', 'font_token' => 'sans', 'color' => '#FFFFFF', 'x' => $width * .16, 'y' => $height * .78, 'width' => $width * .42, 'height' => $height * .06];
        }
        return [
            'canvas' => $canvas,
            'palette' => ['#4F46E5', '#1E293B'],
            'texts' => $texts,
            'shapes' => [['name' => 'CTA background', 'kind' => 'pill', 'color' => '#4F46E5', 'radius' => 30, 'x' => $width * .06, 'y' => $height * .74, 'width' => $width * .56, 'height' => $height * .12]],
            'icons' => $phone ? [['name' => 'Phone icon', 'icon_name' => 'phone', 'color' => '#FFFFFF', 'x' => $width * .08, 'y' => $height * .775, 'width' => $width * .06, 'height' => $height * .05]] : [],
        ];
    }

    private function canvasSize(string $size): array
    {
        if (preg_match('/^(\d+)x(\d+)$/', $size, $match)) {
            return ['width' => (int) $match[1], 'height' => (int) $match[2]];
        }
        return ['width' => 1024, 'height' => 1024];
    }

    private function coordinate(mixed $value, int $maximum): float
    {
        $number = is_numeric($value) ? (float) $value : 0;
        return max(-$maximum, min($maximum * 2, $number));
    }

    private function dimension(mixed $value, int $maximum): float
    {
        $number = is_numeric($value) ? (float) $value : $maximum / 2;
        return max(1, min($maximum * 2, $number));
    }

    private function color(mixed $value): ?string
    {
        $color = trim((string) $value);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtoupper($color) : null;
    }
}
