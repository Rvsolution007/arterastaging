<?php

namespace App\Models\Traits;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::creating(function ($model) {
            if (auth()->check() && empty($model->company_id)) {
                $model->company_id = auth()->user()->company_id;
            }
        });
    }

    public function scopeForCompany($query, ?int $companyId = null)
    {
        $companyId = $companyId ?? auth()->user()?->company_id;
        if ($companyId) {
            return $query->where('company_id', $companyId);
        }
        return $query;
    }
}
