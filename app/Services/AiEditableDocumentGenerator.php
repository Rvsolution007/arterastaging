<?php

namespace App\Services;

use App\Models\AiEditableDocument;
use App\Models\BusinessAiGeneration;
use App\Models\FestivalAiGeneration;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Assembles the hybrid V1 document. The AI image is one locked artwork layer;
 * every editable item is regular manifest data. This class must never call an
 * image-generation service because one generation is one image credit.
 */
class AiEditableDocumentGenerator
{
    public function __construct(
        private AiEditableLayerPlanningService $planner,
        private AiEditableDocumentService $documents
    ) {
    }

    public function generate(FestivalAiGeneration|BusinessAiGeneration $generation): AiEditableDocument
    {
        if (blank($generation->generated_image_path)) {
            throw new RuntimeException('The source AI artwork is unavailable.');
        }

        $plan = $this->planner->plan($generation);
        $canvas = $plan['canvas'];
        $layers = [[
            'id' => 'ai_artwork',
            'name' => 'AI artwork (locked)',
            'type' => 'bitmap',
            'z_index' => 0,
            'opacity' => 1,
            'blend_mode' => 'normal',
            'visible' => true,
            'locked' => true,
            'asset' => ['path' => $generation->generated_image_path, 'fit' => 'cover'],
            'transform' => $this->bounds(0, 0, $canvas['width'], $canvas['height']),
        ]];

        $textOnly = $generation instanceof BusinessAiGeneration;

        // New Business/Custom posts keep every non-text visual inside the
        // generated artwork. Existing Festival V1 documents intentionally
        // retain their old overlay behaviour.
        if (!$textOnly) {
            foreach ((array) $plan['shapes'] as $index => $shape) {
                $layers[] = [
                    'id' => 'shape_' . ($index + 1),
                    'name' => Str::limit(trim((string) ($shape['name'] ?? 'Shape')) ?: 'Shape', 120),
                    'type' => 'shape',
                    'z_index' => 10 + $index,
                    'opacity' => 1,
                    'blend_mode' => 'normal',
                    'visible' => true,
                    'locked' => false,
                    'style' => [
                        'kind' => in_array($shape['kind'] ?? null, ['rectangle', 'pill', 'circle'], true) ? $shape['kind'] : 'rectangle',
                        'color' => $this->color($shape['color'] ?? null, $plan['palette'][0]),
                        'radius' => max(0, min((float) ($shape['radius'] ?? 16), 999)),
                    ],
                    'transform' => $this->bounds($shape['x'], $shape['y'], $shape['width'], $shape['height']),
                ];
            }
        }

        $textZ = 30;
        foreach ((array) $plan['texts'] as $index => $text) {
            $content = Str::limit(trim((string) ($text['text'] ?? '')), 240);
            if ($content === '') {
                continue;
            }
            $role = (string) ($text['role'] ?? 'body');
            $layers[] = [
                'id' => 'text_' . ($index + 1),
                'name' => Str::limit(trim((string) ($text['name'] ?? 'Text layer')) ?: 'Text layer', 120),
                'type' => 'text',
                'text' => $content,
                'z_index' => $textZ++,
                'opacity' => 1,
                'blend_mode' => 'normal',
                'visible' => true,
                'locked' => false,
                'style' => $this->textStyle($role, $this->color($text['color'] ?? null, '#FFFFFF'), (string) ($text['font_token'] ?? 'sans')),
                'transform' => $this->bounds($text['x'], $text['y'], $text['width'], $text['height']),
            ];
        }

        if (!$textOnly) {
            foreach ((array) $plan['icons'] as $index => $icon) {
                $layers[] = [
                    'id' => 'icon_' . ($index + 1),
                    'name' => Str::limit(trim((string) ($icon['name'] ?? 'Icon')) ?: 'Icon', 120),
                    'type' => 'icon',
                    'z_index' => 45 + $index,
                    'opacity' => 1,
                    'blend_mode' => 'normal',
                    'visible' => true,
                    'locked' => false,
                    'style' => [
                        'icon_name' => in_array($icon['icon_name'] ?? null, ['phone', 'whatsapp', 'email', 'website', 'location', 'star', 'arrow'], true)
                            ? $icon['icon_name'] : 'star',
                        'color' => $this->color($icon['color'] ?? null, '#FFFFFF'),
                    ],
                    'transform' => $this->bounds($icon['x'], $icon['y'], $icon['width'], $icon['height']),
                ];
            }

            $logoPath = trim((string) data_get($generation->business_snapshot, 'logo_path'));
            if ($logoPath !== '') {
                $layers[] = [
                    'id' => 'business_logo',
                    'name' => 'Business logo',
                    'type' => 'bitmap',
                    'z_index' => 60,
                    'opacity' => 1,
                    'blend_mode' => 'normal',
                    'visible' => true,
                    'locked' => false,
                    'asset' => ['path' => $logoPath, 'fit' => 'contain'],
                    'transform' => $this->bounds($canvas['width'] * .06, $canvas['height'] * .05, $canvas['width'] * .22, $canvas['height'] * .10),
                ];
            }
        }

        $manifest = [
            'document_contract' => $textOnly
                ? config('ai_editable_v1.business_custom_contract')
                : config('ai_editable_v1.contract'),
            'schema_version' => $textOnly ? 2 : (int) config('ai_editable_v1.schema_version'),
            'canvas' => $canvas,
            'layers' => $layers,
            'extensions' => [
                'ai_editable_v1' => [
                    'mode' => $textOnly ? 'single_artwork_text_only_overlay' : 'single_artwork_editable_overlay',
                    'source_generation_id' => $generation->id,
                    'image_generation_count' => 1,
                    'editable_layer_types' => $textOnly ? ['text'] : ['text', 'shape', 'icon', 'bitmap'],
                ],
            ],
        ];

        return $generation instanceof BusinessAiGeneration
            ? $this->documents->createForBusiness($generation->user, $manifest, $generation)
            : $this->documents->create($generation->user, $manifest, $generation);
    }

    private function bounds(float|int $x, float|int $y, float|int $width, float|int $height): array
    {
        return ['x' => (float) $x, 'y' => (float) $y, 'width' => (float) $width, 'height' => (float) $height, 'rotation' => 0];
    }

    private function textStyle(string $role, string $color, string $fontToken): array
    {
        $fontToken = in_array($fontToken, ['display', 'serif', 'sans', 'devanagari'], true) ? $fontToken : 'sans';
        return match ($role) {
            'heading' => ['color' => $color, 'font_token' => $fontToken, 'font_size' => 76, 'font_weight' => 800, 'line_height' => 1.0, 'shadow' => ['color' => '#000000', 'opacity' => .30, 'x' => 0, 'y' => 4, 'blur' => 8]],
            'subheading' => ['color' => $color, 'font_token' => $fontToken, 'font_size' => 42, 'font_weight' => 700, 'line_height' => 1.05],
            'cta' => ['color' => $color, 'font_token' => $fontToken, 'font_size' => 38, 'font_weight' => 700, 'line_height' => 1.0],
            'contact' => ['color' => $color, 'font_token' => $fontToken, 'font_size' => 28, 'font_weight' => 600, 'line_height' => 1.15],
            default => ['color' => $color, 'font_token' => $fontToken, 'font_size' => 32, 'font_weight' => 500, 'line_height' => 1.15],
        };
    }

    private function color(mixed $value, string $fallback): string
    {
        $color = trim((string) $value);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtoupper($color) : $fallback;
    }
}
