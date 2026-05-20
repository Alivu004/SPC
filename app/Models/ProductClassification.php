<?php

namespace App\Models;

use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductClassification extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',           // FK → products.id
        'product_status_id',
        'classification_rule_id',
        'status_name',
        'status_color',
        'explanation',
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

    public function productStatus(): BelongsTo
    {
        return $this->belongsTo(ProductStatus::class);
    }

    public function classificationRule(): BelongsTo
    {
        return $this->belongsTo(ClassificationRule::class);
    }
}
