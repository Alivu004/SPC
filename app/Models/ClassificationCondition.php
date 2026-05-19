<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassificationCondition extends Model
{
    protected $fillable = [
        'user_id',
        'classification_rule_id',
        'field_key',
        'operator',
        'value',
        'value_type',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classificationRule(): BelongsTo
    {
        return $this->belongsTo(ClassificationRule::class);
    }
}
