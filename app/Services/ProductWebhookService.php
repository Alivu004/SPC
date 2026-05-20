<?php

namespace App\Services;

use App\Models\User;
use App\Models\Products\Product;

class ProductWebhookService
{
    /**
     * Create or update a Product record from a webhook payload.
     * Uses user_id + shopify_product_id as the unique key to prevent duplicates.
     */
    public function upsertProductFromWebhook(User $user, array $payload): Product
    {
        $data = $this->mapPayloadToProductData($payload);
        $data['user_id'] = $user->id;

        return Product::updateOrCreate(
            [
                'user_id'            => $user->id,
                'shopify_product_id' => $data['shopify_product_id'],
            ],
            $data
        );
    }

    /**
     * Map a raw Shopify REST webhook payload to the products column set.
     */
    public function mapPayloadToProductData(array $payload): array
    {
        $variants   = $payload['variants'] ?? [];
        $priceRange = $this->calculatePriceRange($variants);

        return [
            'shopify_product_id'  => (string) ($payload['id'] ?? ''),
            'admin_graphql_api_id' => $payload['admin_graphql_api_id'] ?? null,
            'title'               => $payload['title'] ?? null,
            'handle'              => $payload['handle'] ?? null,
            'body_html'           => $payload['body_html'] ?? null,
            'vendor'              => $payload['vendor'] ?? null,
            'product_type'        => $payload['product_type'] ?? null,
            'shopify_status'      => $payload['status'] ?? null,
            'tags'                => $payload['tags'] ?? null,
            'published_at'        => $payload['published_at'] ?? null,
            'created_at_shopify'  => $payload['created_at'] ?? null,
            'updated_at_shopify'  => $payload['updated_at'] ?? null,
            'total_inventory'     => $this->calculateTotalInventory($variants),
            'variants_count'      => count($variants),
            'min_price'           => $priceRange['min'],
            'max_price'           => $priceRange['max'],
            'image_url'           => $this->extractFeaturedImage($payload),
            'has_image'           => $this->extractFeaturedImage($payload) !== null,
            'has_description'     => $this->hasDescription($payload['body_html'] ?? null),
            'last_synced_at'      => now(),
            'raw_payload'         => $payload,
        ];
    }

    /**
     * Sum inventory_quantity across all variants.
     */
    public function calculateTotalInventory(array $variants): int
    {
        $total = 0;
        foreach ($variants as $variant) {
            $total += (int) ($variant['inventory_quantity'] ?? 0);
        }
        return $total;
    }

    /**
     * Find minimum and maximum price across all variants.
     * Variant prices come from Shopify as decimal strings (e.g. "19.99").
     * Returns array with keys 'min' and 'max' (float|null each).
     */
    public function calculatePriceRange(array $variants): array
    {
        if (empty($variants)) {
            return ['min' => null, 'max' => null];
        }

        $prices = array_filter(
            array_map(fn($v) => isset($v['price']) ? (float) $v['price'] : null, $variants),
            fn($p) => $p !== null
        );

        if (empty($prices)) {
            return ['min' => null, 'max' => null];
        }

        return ['min' => min($prices), 'max' => max($prices)];
    }

    /**
     * Extract the featured image URL from the payload.
     * Checks payload.image.src first, then payload.images[0].src.
     */
    public function extractFeaturedImage(array $payload): ?string
    {
        if (!empty($payload['image']['src'])) {
            return $payload['image']['src'];
        }

        if (!empty($payload['images'][0]['src'])) {
            return $payload['images'][0]['src'];
        }

        return null;
    }

    /**
     * Return true if body_html contains visible text after stripping tags.
     */
    public function hasDescription(?string $bodyHtml): bool
    {
        if ($bodyHtml === null || $bodyHtml === '') {
            return false;
        }

        return trim(strip_tags($bodyHtml)) !== '';
    }
}
