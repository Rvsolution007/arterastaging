<?php

namespace App\Services;

use Illuminate\Support\Str;

class BusinessAiPromptCompiler
{
    public function compile(array $purpose, array $style, array $brief, array $products, array $business, ?string $instruction, string $size, array $headerFooter = []): string
    {
        $facts = collect($brief)
            ->filter(fn ($value, $key) => is_string($key) && is_scalar($value) && trim((string) $value) !== '')
            ->map(fn ($value, $key) => Str::headline($key) . ': ' . Str::limit(Str::squish((string) $value), 180))
            ->values()
            ->all();
        $productNames = collect($products)->pluck('title')->filter()->implode(', ');
        $safeInstruction = Str::limit(Str::squish(strip_tags((string) $instruction)), 500);

        return implode("\n\n", array_filter([
            'TASK: Create one polished, premium business marketing artwork for a mobile post. Canvas: exactly ' . $size . '.',
            'BUSINESS PURPOSE: ' . ($purpose['prompt'] ?? 'Create a professional business post.'),
            filled($purpose['product_prompt'] ?? null) ? 'UNIVERSAL PRODUCT / SERVICE RULE: ' . $purpose['product_prompt'] : null,
            'SELECTED STYLE: ' . ($style['name'] ?? 'Modern professional') . '. ' . ($style['description'] ?? ''),
            'SELECTED THEME PROMPT: ' . ($style['prompt_text'] ?? $style['description'] ?? 'Use a polished, professional business design direction.'),
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
}
