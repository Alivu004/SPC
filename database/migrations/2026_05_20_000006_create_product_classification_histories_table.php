<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_classification_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // References products.id (local product row)
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();
            $table->foreignId('previous_status_id')
                  ->nullable()
                  ->constrained('product_statuses')
                  ->nullOnDelete();
            $table->foreignId('new_status_id')
                  ->nullable()
                  ->constrained('product_statuses')
                  ->nullOnDelete();
            $table->foreignId('classification_rule_id')
                  ->nullable()
                  ->constrained('classification_rules')
                  ->nullOnDelete();
            $table->json('explanation')->nullable();
            $table->string('triggered_by')->nullable();
            $table->timestamp('classified_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'product_id'], 'pch_user_product_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_classification_histories');
    }
};
