<?php

namespace App\Models\Products;

use App\Models\User;
use App\Models\ProductClassification;
use App\Models\ProductClassificationHistory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public $fillable = [
        'user_id',
        'shopify_product_id',
        'admin_graphql_api_id',
        'title',
        'handle',
        'body_html',
        'tags',
        'vendor',
        'product_type',
        'shopify_status',
        'total_inventory',
        'variants_count',
        'min_price',
        'max_price',
        'image_url',
        'has_image',
        'has_description',
        'published_at',
        'created_at_shopify',
        'updated_at_shopify',
        'last_synced_at',
        'raw_payload',
    ];

    protected $casts = [
        'has_image'          => 'boolean',
        'has_description'    => 'boolean',
        'total_inventory'    => 'integer',
        'variants_count'     => 'integer',
        'min_price'          => 'decimal:2',
        'max_price'          => 'decimal:2',
        'published_at'       => 'datetime',
        'created_at_shopify' => 'datetime',
        'updated_at_shopify' => 'datetime',
        'last_synced_at'     => 'datetime',
        'raw_payload'        => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function productVarients()
    {
        return $this->hasMany(ProductVarient::class);
    }

    public function productMedias()
    {
        return $this->hasMany(ProductMedia::class);
    }

    public function classification()
    {
        return $this->hasOne(ProductClassification::class, 'product_id');
    }

    public function classificationHistories()
    {
        return $this->hasMany(ProductClassificationHistory::class, 'product_id');
    }
}
