<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Approved Custom Post Type configuration for one business category and,
 * optionally, one subcategory. It never stores a user's private business
 * facts; those are resolved only for the authenticated user at request time.
 */
class BusinessAiPurposeScope extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_ai_purpose_id',
        'business_category_id',
        'business_sub_category_id',
        'brief_fields',
        'brief_fields_source_scope_id',
        'general_data',
        'content_instruction',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'brief_fields' => 'array',
        'general_data' => 'array',
        'status' => 'boolean',
    ];

    public function purpose()
    {
        return $this->belongsTo(BusinessAiPurpose::class, 'business_ai_purpose_id');
    }

    public function category()
    {
        return $this->belongsTo(BusinessCategory::class, 'business_category_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(BusinessSubCategory::class, 'business_sub_category_id');
    }

    /**
     * The scope whose Brief fields this row currently uses.  A target keeps
     * its own `brief_fields` JSON untouched as a backup; the pointer gives us
     * a live link instead of copying fields to every selected subcategory.
     */
    public function briefFieldsSource()
    {
        return $this->belongsTo(self::class, 'brief_fields_source_scope_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany */
    public function briefFieldsTargets()
    {
        return $this->hasMany(self::class, 'brief_fields_source_scope_id');
    }

    public function styles()
    {
        return $this->belongsToMany(BusinessAiStyle::class, 'business_ai_purpose_scope_styles')
            ->withTimestamps()
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Brief fields are owned by this concrete category/subcategory scope.
     * Parent Type fields remain in the database only as legacy data and must
     * not appear as a fallback in the current scoped Custom Post flow.
     */
    public function resolvedBriefFields(): array
    {
        return $this->resolveBriefFields([], 0);
    }

    /**
     * Resolves a live Brief-field link without ever overwriting the target's
     * original JSON backup.  The controller prevents invalid links, but this
     * defensive resolver also refuses cross-Type links and falls back safely
     * if an old/manual database row creates a loop.
     *
     * @param array<int, bool> $visitedScopeIds
     */
    private function resolveBriefFields(array $visitedScopeIds, int $depth): array
    {
        $ownFields = (array) ($this->brief_fields ?? []);
        $scopeId = (int) ($this->getKey() ?? 0);
        $sourceId = (int) ($this->brief_fields_source_scope_id ?? 0);

        // Eight levels is deliberately far beyond the normal one-level UI
        // flow. It protects mobile/API reads if a bad historic row exists.
        if ($sourceId <= 0 || $sourceId === $scopeId || $depth >= 8) {
            return $ownFields;
        }
        if ($scopeId > 0 && isset($visitedScopeIds[$scopeId])) {
            return $ownFields;
        }
        $visitedScopeIds[$scopeId] = true;

        $source = $this->relationLoaded('briefFieldsSource')
            ? $this->getRelation('briefFieldsSource')
            : $this->briefFieldsSource()->first();

        if (!$source || (int) $source->business_ai_purpose_id !== (int) $this->business_ai_purpose_id) {
            return $ownFields;
        }

        return $source->resolveBriefFields($visitedScopeIds, $depth + 1);
    }

    /** Normalise old textarea/list/object data into a safe, compact list. */
    public function resolvedGeneralData(): array
    {
        return collect((array) $this->general_data)
            ->map(function ($item) {
                if (is_string($item)) {
                    $text = trim($item);
                    return $text === '' ? null : ['text' => $text];
                }

                if (is_array($item)) {
                    $text = trim((string) ($item['text'] ?? $item['point'] ?? $item['title'] ?? ''));
                    if ($text === '') {
                        return null;
                    }

                    return array_filter([
                        'text' => $text,
                        'source' => filled($item['source'] ?? null) ? trim((string) $item['source']) : null,
                        'notes' => filled($item['notes'] ?? null) ? trim((string) $item['notes']) : null,
                    ], static fn ($value) => $value !== null);
                }

                return null;
            })
            ->filter()
            ->take(30)
            ->values()
            ->all();
    }
}
