<?php

namespace App\Models;

use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductClassificationHistory extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',           // FK → products.id
        'previous_status_id',
        'new_status_id',
        'classification_rule_id',
        'explanation',
        'triggered_by',
        'classified_at',
    ];

    protected $casts = [
        'explanation'   => 'array',
        'classified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function previousStatus(): BelongsTo
    {
        return $this->belongsTo(ProductStatus::class, 'previous_status_id');
    }

    public function newStatus(): BelongsTo
    {
        return $this->belongsTo(ProductStatus::class, 'new_status_id');
    }

    public function classificationRule(): BelongsTo
    {
        return $this->belongsTo(ClassificationRule::class);
    }
}
