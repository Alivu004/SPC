<?php

namespace Database\Seeders;

use App\Models\ProductStatus;
use Illuminate\Database\Seeder;

class ProductStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = config('product-classifier.default_statuses');

        foreach ($statuses as $status) {
            ProductStatus::firstOrCreate(
                ['slug' => $status['slug'], 'user_id' => null],
                array_merge($status, ['user_id' => null, 'is_active' => true]),
            );
        }
    }
}
