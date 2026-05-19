<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classification_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classification_rule_id')
                  ->constrained('classification_rules')
                  ->cascadeOnDelete();
            $table->string('field_key');
            $table->string('operator');
            $table->text('value')->nullable();
            $table->string('value_type')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['classification_rule_id', 'sort_order'], 'cc_rule_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classification_conditions');
    }
};
