<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Products\Product;
use App\Models\ProductClassification;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Product::where('user_id', $user->id)
            ->with(['classification', 'classification.classificationRule']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('title', 'like', "%{$s}%");
        }

        if ($request->filled('shopify_status') && $request->shopify_status !== 'all') {
            $query->where('shopify_status', $request->shopify_status);
        }

        if ($request->filled('classification_status') && $request->classification_status !== 'all') {
            if ($request->classification_status === 'unclassified') {
                $query->doesntHave('classification');
            } else {
                $query->whereHas('classification', fn ($q) =>
                    $q->where('status_name', $request->classification_status)
                );
            }
        }

        if ($request->filled('vendor') && $request->vendor !== 'all') {
            $query->where('vendor', $request->vendor);
        }

        $paginated = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();

        $total          = Product::where('user_id', $user->id)->count();
        $classified     = Product::where('user_id', $user->id)->has('classification')->count();
        $unclassified   = $total - $classified;
        $needsAttention = Product::where('user_id', $user->id)
            ->whereHas('classification', fn ($q) => $q->where('status_name', 'Needs Attention'))
            ->count();

        $vendors = Product::where('user_id', $user->id)
            ->whereNotNull('vendor')->where('vendor', '!=', '')
            ->distinct()->orderBy('vendor')->pluck('vendor')->values();

        $classificationStatuses = ProductClassification::whereHas(
            'product', fn ($q) => $q->where('user_id', $user->id)
        )->whereNotNull('status_name')
         ->distinct()->orderBy('status_name')->pluck('status_name')->values();

        return $this->render('Products', [
            'products' => [
                'data' => $paginated->map(fn ($p) => $this->formatProduct($p))->values(),
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'total'        => $paginated->total(),
                    'per_page'     => $paginated->perPage(),
                ],
            ],
            'summary' => [
                'total'           => $total,
                'classified'      => $classified,
                'unclassified'    => $unclassified,
                'needs_attention' => $needsAttention,
            ],
            'vendors'                => $vendors,
            'classificationStatuses' => $classificationStatuses,
            'filters' => [
                'search'                => $request->search ?? '',
                'shopify_status'        => $request->shopify_status ?? 'all',
                'classification_status' => $request->classification_status ?? 'all',
                'vendor'                => $request->vendor ?? 'all',
            ],
        ]);
    }

    private function formatProduct(Product $product): array
    {
        $c = $product->classification;

        return [
            'id'                 => $product->id,
            'shopify_product_id' => $product->shopify_product_id,
            'title'              => $product->title ?? '(no title)',
            'handle'             => $product->handle,
            'vendor'             => $product->vendor,
            'product_type'       => $product->product_type,
            'shopify_status'     => $product->shopify_status,
            'total_inventory'    => $product->total_inventory ?? 0,
            'min_price'          => $product->min_price,
            'max_price'          => $product->max_price,
            'image_url'          => $product->image_url,
            'has_image'          => (bool) $product->has_image,
            'has_description'    => (bool) $product->has_description,
            'last_synced_at'     => $product->last_synced_at?->toISOString(),
            'classification'     => $c ? [
                'status_name'   => $c->status_name,
                'status_color'  => $c->status_color,
                'rule_name'     => $c->classificationRule?->name,
                'match_type'    => $c->classificationRule?->match_type,
                'classified_at' => $c->classified_at?->toISOString(),
                'explanation'   => $c->explanation ?? [],
            ] : null,
        ];
    }

}
