<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassificationRule extends Model
{
    protected $fillable = [
        'user_id',
        'product_status_id',
        'name',
        'description',
        'match_type',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority'  => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function productStatus(): BelongsTo
    {
        return $this->belongsTo(ProductStatus::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(ClassificationCondition::class)->orderBy('sort_order');
    }

    public function productClassifications(): HasMany
    {
        return $this->hasMany(ProductClassification::class);
    }
}
