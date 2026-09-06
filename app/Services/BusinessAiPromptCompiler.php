<?php

namespace App\Services;

use Illuminate\Support\Str;

class BusinessAiPromptCompiler
{
    public function compile(
        array $purpose,
        array $style,
        array $brief,
        array $products,
        array $business,
        ?string $instruction,
        string $size,
        array $headerFooter = [],
        array $scope = [],
        array $contentPreview = []
    ): string
    {
        $facts = collect($brief)
            ->filter(fn ($value, $key) => is_string($key) && is_scalar($value) && trim((string) $value) !== '')
            ->map(fn ($value, $key) => Str::headline($key) . ': ' . Str::limit(Str::squish((string) $value), 180))
            ->values()
            ->all();
        $productNames = collect($products)->pluck('title')->filter()->implode(', ');
        $safeInstruction = Str::limit(Str::squish(strip_tags((string) $instruction)), 500);
        $businessFacts = $this->businessFacts($business);
        $generalPoints = $this->generalPoints($scope);
        $scopeInstruction = Str::limit(Str::squish(strip_tags((string) data_get($scope, 'content_instruction', ''))), 3000);
        $preview = $this->contentPreview($contentPreview);

        return implode("\n\n", array_filter([
            'TASK: Create one polished, premium business marketing artwork for a mobile post. Canvas: exactly ' . $size . '.',
            'BUSINESS PURPOSE: ' . ($purpose['prompt'] ?? 'Create a professional business post.'),
            filled($purpose['product_prompt'] ?? null) ? 'UNIVERSAL PRODUCT / SERVICE RULE: ' . $purpose['product_prompt'] : null,
            'SELECTED STYLE: ' . ($style['name'] ?? 'Modern professional') . '. ' . ($style['description'] ?? ''),
            'SELECTED THEME PROMPT: ' . ($style['prompt_text'] ?? $style['description'] ?? 'Use a polished, professional business design direction.'),
            $this->paletteInstruction($style),
            $businessFacts !== '' ? "MY BUSINESS FACTS (use only these supplied facts):\n" . $businessFacts : null,
            $generalPoints !== '' ? "APPROVED CATEGORY / SUBCATEGORY GENERAL CONTENT:\n" . $generalPoints : null,
            $scopeInstruction !== '' ? 'CATEGORY CONTENT INSTRUCTION: ' . $scopeInstruction : null,
            $preview !== '' ? "APPROVED EDITABLE POST PLAN (use its topic and intent; do not render its text in the artwork):\n" . $preview : null,
            !empty($headerFooter['enabled']) ? 'HEADER/FOOTER STYLE: ' . implode(' ', array_filter([
                $headerFooter['header_prompt'] ?? null,
                $headerFooter['footer_prompt'] ?? null,
                'Reserve those zones for editable app layers; do not render readable business text into the artwork.',
            ])) : null,
            $facts !== [] ? "POST BRIEF (use facts accurately):\n" . implode("\n", $facts) : null,
            $productNames !== '' ? 'ATTACHED PRODUCT REFERENCES: ' . $productNames . '. Keep every reference recognisable, use all selected products together in one cohesive composition, never duplicate any product.' : null,
            $safeInstruction !== '' ? 'VISUAL PREFERENCE: ' . $safeInstruction : null,
            'EDITABLE LAYER RULE (highest priority): generate artwork, product photography, lighting, texture and safe zones only. Do not render any headline, readable letters, digits, business logo, contact detail, CTA, button, icon, badge, watermark, border or decorative text. Leave a calm, high-contrast text-safe area of roughly 35% of the canvas. Preserve only text physically printed on an attached product.',
            'QUALITY: professional advertising composition, accurate product geometry, deliberate whitespace, no distorted products, no watermarks, no unintended logos.',
        ]));
    }

    private function businessFacts(array $business): string
    {
        $facts = [];
        foreach ([
            'Business name' => data_get($business, 'name'),
            'Category' => data_get($business, 'category.name'),
            'Services' => collect((array) data_get($business, 'services', []))->filter()->implode(', '),
            'Catalogue items' => collect((array) data_get($business, 'catalog_items', []))->filter()->take(8)->implode(', '),
            'Phone' => collect((array) data_get($business, 'phones', []))->filter()->take(2)->implode(', '),
            'Website' => collect((array) data_get($business, 'websites', []))->filter()->take(1)->implode(', '),
            'Address' => collect((array) data_get($business, 'addresses', []))->filter()->take(1)->implode(', '),
        ] as $label => $value) {
            $text = Str::limit(Str::squish((string) $value), 300);
            if ($text !== '') {
                $facts[] = $label . ': ' . $text;
            }
        }

        return implode("\n", $facts);
    }

    private function generalPoints(array $scope): string
    {
        return collect((array) data_get($scope, 'general_data', []))
            ->map(fn ($point) => is_array($point) ? ($point['text'] ?? $point['point'] ?? null) : $point)
            ->filter(fn ($point) => is_scalar($point) && trim((string) $point) !== '')
            ->map(fn ($point) => '- ' . Str::limit(Str::squish((string) $point), 240))
            ->take(12)
            ->implode("\n");
    }

    private function contentPreview(array $preview): string
    {
        return collect([
            'Headline' => $preview['headline'] ?? null,
            'Content' => $preview['content'] ?? null,
            'CTA' => $preview['cta'] ?? null,
        ])
            ->filter(fn ($value) => is_scalar($value) && trim((string) $value) !== '')
            ->map(fn ($value, $label) => $label . ': ' . Str::limit(Str::squish((string) $value), 380))
            ->implode("\n");
    }

    private function paletteInstruction(array $style): ?string
    {
        $colors = collect((array) ($style['effective_colors'] ?? $style['colors'] ?? []))
            ->map(fn ($color) => strtoupper(trim((string) $color)))
            ->filter(fn ($color) => preg_match('/^#[A-F0-9]{6}$/', $color) === 1)
            ->take(2)
            ->values()
            ->all();

        if (count($colors) < 2) {
            return null;
        }

        $source = ($style['palette_mode'] ?? 'style_colors') === 'business_theme'
            ? 'active business theme'
            : 'selected Custom Post Style';

        return "DESIGN PALETTE ({$source}): primary {$colors[0]}, secondary {$colors[1]}. Give these two colours visual priority while preserving contrast and readable text-safe space.";
    }
}
