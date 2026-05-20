<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Rename existing 'status' column to 'shopify_status' to align with classifier field keys
            $table->renameColumn('status', 'shopify_status');

            // Additional product identity fields
            $table->string('admin_graphql_api_id')->nullable()->after('shopify_product_id');

            // Computed fields for classification rules
            $table->unsignedInteger('total_inventory')->default(0)->after('tags');
            $table->unsignedInteger('variants_count')->default(0)->after('total_inventory');
            $table->decimal('min_price', 12, 2)->nullable()->after('variants_count');
            $table->decimal('max_price', 12, 2)->nullable()->after('min_price');
            $table->text('image_url')->nullable()->after('max_price');
            $table->boolean('has_image')->default(false)->after('image_url');
            $table->boolean('has_description')->default(false)->after('has_image');

            // Date fields for date-based conditions
            $table->timestamp('published_at')->nullable()->after('has_description');
            $table->timestamp('created_at_shopify')->nullable()->after('published_at');
            $table->timestamp('updated_at_shopify')->nullable()->after('created_at_shopify');
            $table->timestamp('last_synced_at')->nullable()->after('updated_at_shopify');

            // Full webhook payload for debugging / future use
            $table->json('raw_payload')->nullable()->after('last_synced_at');

            // Enforce one product per user (prevent duplicates on repeated webhooks)
            $table->unique(['user_id', 'shopify_product_id'], 'products_user_shopify_product_unique');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_user_shopify_product_unique');
            $table->dropColumn([
                'admin_graphql_api_id',
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
            ]);
            $table->renameColumn('shopify_status', 'status');
        });
    }
};
