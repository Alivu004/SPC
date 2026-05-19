<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Allowed Product Field Keys
    |--------------------------------------------------------------------------
    | Fields that can be used as condition targets when building classification
    | rules. These values are passed to the frontend dropdown.
    */
    'allowed_field_keys' => [
        'title',
        'vendor',
        'product_type',
        'shopify_status',
        'tags',
        'total_inventory',
        'variants_count',
        'min_price',
        'max_price',
        'has_image',
        'has_description',
        'published_at',
        'created_at_shopify',
        'updated_at_shopify',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Operators
    |--------------------------------------------------------------------------
    */
    'allowed_operators' => [
        'equals',
        'not_equals',
        'contains',
        'does_not_contain',
        'greater_than',
        'less_than',
        'greater_than_or_equal',
        'less_than_or_equal',
        'is_empty',
        'is_not_empty',
        'older_than_days',
        'within_last_days',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Statuses (seeded on install)
    |--------------------------------------------------------------------------
    */
    'default_statuses' => [
        ['name' => 'Active',          'slug' => 'active',          'color' => '#28a745', 'priority' => 1,  'is_default' => true],
        ['name' => 'Slow',            'slug' => 'slow',            'color' => '#ffc107', 'priority' => 2,  'is_default' => true],
        ['name' => 'Inactive',        'slug' => 'inactive',        'color' => '#dc3545', 'priority' => 3,  'is_default' => true],
        ['name' => 'Needs Attention', 'slug' => 'needs_attention', 'color' => '#fd7e14', 'priority' => 4,  'is_default' => true],
        ['name' => 'Overstocked',     'slug' => 'overstocked',     'color' => '#17a2b8', 'priority' => 5,  'is_default' => true],
    ],

];
