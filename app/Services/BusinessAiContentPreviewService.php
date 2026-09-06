<?php

namespace App\Services;

use App\Models\BusinessAiPurpose;
use App\Models\BusinessAiPurposeScope;
use App\Models\BusinessAiStyle;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Creates the reviewable, editable copy before the image generation request.
 * It may use the configured content AI, but always has a deterministic
 * fallback so previewing never reserves an image credit or queues artwork.
 */
class BusinessAiContentPreviewService
{
    public function generate(
        User $user,
        BusinessAiPurpose $purpose,
        BusinessAiPurposeScope $scope,
        array $brief,
        array $businessSnapshot,
        ?BusinessAiStyle $style = null,
        ?string $userInstruction = null,
        ?string $language = null
    ): array {
        $context = $this->context($purpose, $scope, $brief, $businessSnapshot, $style, $userInstruction, $language);
        $aiPreview = $this->fromConfiguredContentAi($user, $context);

        return $this->normalise($aiPreview ?: $this->fallback($context), $context);
    }

    /** Allows a user-edited preview to remain the exact approved source. */
    public function normaliseSubmitted(array $submitted, array $fallbackPreview): array
    {
        $headline = $this->clean($submitted['headline'] ?? null, 80) ?: (string) ($fallbackPreview['headline'] ?? '');
        $content = $this->clean($submitted['content'] ?? null, 360) ?: (string) ($fallbackPreview['content'] ?? '');
        $cta = $this->clean($submitted['cta'] ?? null, 100) ?: (string) ($fallbackPreview['cta'] ?? '');
        $lines = collect((array) ($submitted['content_lines'] ?? []))
            ->map(fn ($line) => $this->clean($line, 140))
            ->filter()
            ->take(4)
            ->values()
            ->all();

        return [
            'headline' => $headline,
            'content' => $content,
            'cta' => $cta,
            'content_lines' => $lines !== [] ? $lines : (array) ($fallbackPreview['content_lines'] ?? []),
            'source_summary' => (array) ($fallbackPreview['source_summary'] ?? []),
        ];
    }

    private function context(
        BusinessAiPurpose $purpose,
        BusinessAiPurposeScope $scope,
        array $brief,
        array $businessSnapshot,
        ?BusinessAiStyle $style,
        ?string $userInstruction,
        ?string $language
    ): array {
        $generalData = $scope->resolvedGeneralData();
        return [
            'purpose' => ['key' => $purpose->key, 'title' => $purpose->title],
            'scope' => [
                'category' => optional($scope->category)->name,
                'subcategory' => optional($scope->subCategory)->name,
                'general_data' => $generalData,
                'content_instruction' => $this->clean($scope->content_instruction, 3000),
            ],
            'brief' => $this->cleanBrief($brief),
            'business' => $businessSnapshot,
            'style' => $style ? ['key' => $style->key, 'name' => $style->name] : null,
            'user_instruction' => $this->clean($userInstruction, 1000),
            'language' => $this->clean($language, 80) ?: 'English',
        ];
    }

    private function fromConfiguredContentAi(User $user, array $context): ?array
    {
        try {
            $service = new VertexAIService($user->id);
            if (!$service->isConfigured()) {
                return null;
            }

            $system = implode("\n", [
                'You write a short, reviewable social-media post plan for an Indian business.',
                'Use only the supplied brief, business facts, and approved general data.',
                'Never invent services, doctor credentials, offers, prices, results, diagnoses, cures, guarantees, or medical claims.',
                'For healthcare, use educational/process language only and avoid promises or before/after claims.',
                'Return valid JSON only with exactly: headline, content, cta, content_lines.',
                'headline: maximum 80 characters; content: maximum 360 characters; cta: maximum 100 characters; content_lines: 1 to 4 short strings.',
            ]);
            $response = $service->generateContent($system, [[
                'role' => 'user',
                'text' => json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]]);
            $decoded = $this->decodeJson((string) ($response['text'] ?? ''));

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function fallback(array $context): array
    {
        $brief = (array) ($context['brief'] ?? []);
        $generalPoints = collect((array) data_get($context, 'scope.general_data', []))
            ->map(fn ($item) => is_array($item) ? ($item['text'] ?? null) : $item)
            ->filter()
            ->map(fn ($value) => Str::squish((string) $value))
            ->take(3)
            ->values()
            ->all();

        $headline = $this->first($brief, ['headline', 'post_title', 'title', 'service_name', 'treatment_name', 'main_message'])
            ?: (string) data_get($context, 'purpose.title', 'Your Business Post');
        $service = $this->first($brief, ['service', 'service_name', 'eye_service', 'skin_service', 'treatment_name']);
        $businessName = trim((string) data_get($context, 'business.name'));
        $lines = array_values(array_filter(array_merge(
            $service ? [$service] : [],
            $generalPoints
        )));
        $content = implode('. ', $lines);
        if ($businessName !== '') {
            $content = trim($businessName . ($content !== '' ? ': ' . $content : ''));
        }
        if ($content === '') {
            $content = 'Review the details below and personalise this post before generating it.';
        }

        return [
            'headline' => $headline,
            'content' => $content,
            'cta' => $this->first($brief, ['cta', 'appointment_cta', 'call_to_action']) ?: 'Contact us today',
            'content_lines' => $lines !== [] ? $lines : [$content],
        ];
    }

    private function normalise(array $candidate, array $context): array
    {
        $fallback = $this->fallback($context);
        $headline = $this->clean($candidate['headline'] ?? null, 80) ?: $fallback['headline'];
        $content = $this->clean($candidate['content'] ?? null, 360) ?: $fallback['content'];
        $cta = $this->clean($candidate['cta'] ?? null, 100) ?: $fallback['cta'];
        $lines = collect((array) ($candidate['content_lines'] ?? []))
            ->map(fn ($line) => $this->clean($line, 140))
            ->filter()
            ->take(4)
            ->values()
            ->all();

        return [
            'headline' => $headline,
            'content' => $content,
            'cta' => $cta,
            'content_lines' => $lines !== [] ? $lines : $fallback['content_lines'],
            'source_summary' => [
                'uses_user_brief' => (array) ($context['brief'] ?? []),
                'uses_my_business' => $this->publicBusinessSummary((array) ($context['business'] ?? [])),
                'uses_general_data' => (array) data_get($context, 'scope.general_data', []),
                'content_instruction' => data_get($context, 'scope.content_instruction'),
            ],
        ];
    }

    private function cleanBrief(array $brief): array
    {
        return collect($brief)
            ->filter(fn ($value, $key) => is_string($key) && is_scalar($value))
            ->map(fn ($value) => $this->clean($value, 300))
            ->filter()
            ->all();
    }

    private function publicBusinessSummary(array $business): array
    {
        return array_filter([
            'name' => $business['name'] ?? null,
            'phones' => array_values((array) ($business['phones'] ?? [])),
            'emails' => array_values((array) ($business['emails'] ?? [])),
            'websites' => array_values((array) ($business['websites'] ?? [])),
            'addresses' => array_values((array) ($business['addresses'] ?? [])),
            'services' => array_values((array) ($business['services'] ?? [])),
            'catalog_items' => array_values((array) ($business['catalog_items'] ?? [])),
            'old_confirmed_content' => collect((array) ($business['old_confirmed_content'] ?? []))
                ->map(fn ($item) => is_array($item) ? array_filter([
                    'headline' => $item['headline'] ?? null,
                    'content' => $item['content'] ?? null,
                    'cta' => $item['cta'] ?? null,
                ]) : null)
                ->filter()
                ->take(6)
                ->values()
                ->all(),
        ], static fn ($value) => $value !== null && $value !== []);
    }

    private function first(array $values, array $keys): ?string
    {
        foreach ($keys as $key) {
            if ($value = $this->clean($values[$key] ?? null, 200)) {
                return $value;
            }
        }
        foreach ($values as $value) {
            if ($value = $this->clean($value, 200)) {
                return $value;
            }
        }
        return null;
    }

    private function clean(mixed $value, int $limit): ?string
    {
        $text = trim(strip_tags((string) $value));
        return $text === '' ? null : Str::limit(Str::squish($text), $limit, '');
    }

    private function decodeJson(string $text): ?array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text;
        $decoded = json_decode($text, true);
        return is_array($decoded) ? $decoded : null;
    }
}
