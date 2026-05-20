<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // References products.id (local product row)
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();
            $table->foreignId('product_status_id')
                  ->nullable()
                  ->constrained('product_statuses')
                  ->nullOnDelete();
            $table->foreignId('classification_rule_id')
                  ->nullable()
                  ->constrained('classification_rules')
                  ->nullOnDelete();
            $table->string('status_name')->nullable();
            $table->string('status_color')->nullable();
            $table->json('explanation')->nullable();
            $table->timestamp('classified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'product_id'], 'pc_user_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_classifications');
    }
};
