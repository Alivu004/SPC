<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Products\Product;
use App\Models\ProductClassification;
use App\Models\ProductClassificationHistory;
use App\Models\ClassificationRule;
use App\Repositories\Order\OrderRepositoryInterface;

class DashboardController extends Controller
{
    protected $OrderRepository;

    public function __construct(OrderRepositoryInterface $OrderRepository)
    {
        $this->OrderRepository = $OrderRepository;
    }

    public function index()
    {
        $user   = auth()->user();
        $userId = $user->id;

        // ── Product counts ────────────────────────────────────────────────────
        $totalProducts  = Product::where('user_id', $userId)->count();
        $classifiedCount = Product::where('user_id', $userId)
            ->whereHas('classification', fn ($q) => $q->whereNotNull('product_status_id'))
            ->count();
        $unclassifiedCount = $totalProducts - $classifiedCount;

        $statusCounts = $this->getStatusCounts($userId);

        $activeCount       = $statusCounts['Active'] ?? 0;
        $slowCount         = $statusCounts['Slow'] ?? 0;
        $inactiveCount     = $statusCounts['Inactive'] ?? 0;
        $needsAttnCount    = $statusCounts['Needs Attention'] ?? 0;
        $overstockedCount  = $statusCounts['Overstocked'] ?? 0;

        // ── Status distribution ───────────────────────────────────────────────
        $statusDistribution = $this->buildStatusDistribution($statusCounts, $unclassifiedCount, $totalProducts);

        // ── Attention products (top 10) ───────────────────────────────────────
        $attentionProducts = $this->getAttentionProducts($userId);

        // ── Recent activity (latest 10) ───────────────────────────────────────
        $recentActivity = $this->getRecentActivity($userId);

        // ── Rules performance ─────────────────────────────────────────────────
        $rulesPerformance = $this->getRulesPerformance($userId);

        // ── Recommendations ───────────────────────────────────────────────────
        $recommendations = $this->buildRecommendations(
            $needsAttnCount, $slowCount, $unclassifiedCount, $overstockedCount,
            $totalProducts, $rulesPerformance
        );

        return $this->render('Dashboard', [
            'summary' => [
                'total_products'          => $totalProducts,
                'classified_products'     => $classifiedCount,
                'unclassified_products'   => $unclassifiedCount,
                'active_products'         => $activeCount,
                'slow_products'           => $slowCount,
                'inactive_products'       => $inactiveCount,
                'needs_attention_products'=> $needsAttnCount,
                'overstocked_products'    => $overstockedCount,
            ],
            'status_distribution' => $statusDistribution,
            'attention_products'  => $attentionProducts,
            'recent_activity'     => $recentActivity,
            'rules_performance'   => $rulesPerformance,
            'recommendations'     => $recommendations,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Return [ 'StatusName' => count ] from product_classifications for this user.
     */
    private function getStatusCounts(int $userId): array
    {
        $rows = ProductClassification::where('user_id', $userId)
            ->whereNotNull('status_name')
            ->selectRaw('status_name, COUNT(*) as cnt')
            ->groupBy('status_name')
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row->status_name] = (int) $row->cnt;
        }
        return $counts;
    }

    /**
     * Build the status distribution array with percentages.
     */
    private function buildStatusDistribution(array $statusCounts, int $unclassified, int $total): array
    {
        $colorMap = [
            'Active'          => '#008060',
            'Slow'            => '#FFA500',
            'Inactive'        => '#D72C0D',
            'Needs Attention' => '#8C5E00',
            'Overstocked'     => '#2C6ECB',
        ];

        $distribution = [];
        foreach ($statusCounts as $name => $count) {
            $distribution[] = [
                'status_name'  => $name,
                'status_color' => $colorMap[$name] ?? '#637381',
                'count'        => $count,
                'percentage'   => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }

        if ($unclassified > 0) {
            $distribution[] = [
                'status_name'  => 'Unclassified',
                'status_color' => '#637381',
                'count'        => $unclassified,
                'percentage'   => $total > 0 ? round(($unclassified / $total) * 100, 1) : 0,
            ];
        }

        usort($distribution, fn ($a, $b) => $b['count'] <=> $a['count']);
        return $distribution;
    }

    /**
     * Top 10 products needing attention (Needs Attention / Slow / Inactive / Overstocked / Unclassified).
     */
    private function getAttentionProducts(int $userId): array
    {
        $attentionStatuses = ['Needs Attention', 'Slow', 'Inactive', 'Overstocked'];

        // Products with an attention-worthy classification
        $classified = Product::where('products.user_id', $userId)
            ->whereHas('classification', fn ($q) => $q->whereIn('status_name', $attentionStatuses))
            ->with(['classification', 'classification.classificationRule'])
            ->orderByDesc('updated_at')
            ->take(8)
            ->get();

        // Products with no classification at all
        $unclassified = Product::where('user_id', $userId)
            ->doesntHave('classification')
            ->orderByDesc('updated_at')
            ->take(10 - $classified->count())
            ->get();

        $merged = $classified->concat($unclassified)->take(10);

        return $merged->map(fn ($p) => [
            'id'                   => $p->id,
            'title'                => $p->title ?? '(no title)',
            'image_url'            => $p->image_url,
            'vendor'               => $p->vendor,
            'product_type'         => $p->product_type,
            'total_inventory'      => $p->total_inventory ?? 0,
            'classification_status'=> $p->classification?->status_name ?? 'Unclassified',
            'status_color'         => $p->classification?->status_color ?? '#637381',
            'short_reason'         => $this->getShortReason($p->classification),
            'classified_at'        => $p->classification?->classified_at?->toISOString(),
        ])->values()->toArray();
    }

    /**
     * Business-friendly short reason for a classification.
     */
    private function getShortReason($classification): string
    {
        if (!$classification) {
            return 'No rule matched yet';
        }
        if ($classification->classificationRule?->name) {
            return 'Matches "' . $classification->classificationRule->name . '" rule';
        }
        $exp = $classification->explanation;
        if (is_array($exp) && count($exp) > 0) {
            $first = $exp[0];
            $field = ucwords(str_replace('_', ' ', $first['field'] ?? ''));
            return $field ? "Condition: {$field}" : 'Classified based on current rules';
        }
        return 'Classified based on current rules';
    }

    /**
     * Latest 10 classification history records for the user.
     */
    private function getRecentActivity(int $userId): array
    {
        $histories = ProductClassificationHistory::where('user_id', $userId)
            ->with(['product', 'previousStatus', 'newStatus', 'classificationRule'])
            ->orderByDesc('classified_at')
            ->take(10)
            ->get();

        return $histories->map(fn ($h) => [
            'product_title'   => $h->product?->title ?? '(deleted product)',
            'previous_status' => $h->previousStatus?->name ?? '—',
            'new_status'      => $h->newStatus?->name ?? '—',
            'matched_rule'    => $h->classificationRule?->name ?? '—',
            'triggered_by'    => $h->triggered_by ?? 'system',
            'classified_at'   => $h->classified_at?->toISOString() ?? $h->created_at?->toISOString(),
        ])->values()->toArray();
    }

    /**
     * Rule performance: group product_classifications by classification_rule_id.
     */
    private function getRulesPerformance(int $userId): array
    {
        $rules = ClassificationRule::where('user_id', $userId)
            ->with(['productStatus', 'productClassifications' => fn ($q) => $q->where('user_id', $userId)])
            ->withCount(['productClassifications as matched_products_count' => fn ($q) => $q->where('user_id', $userId)])
            ->orderByDesc('matched_products_count')
            ->get();

        return $rules->map(fn ($r) => [
            'rule_name'             => $r->name,
            'assigned_status'       => $r->productStatus?->name ?? '—',
            'matched_products_count'=> (int) $r->matched_products_count,
            'is_active'             => (bool) $r->is_active,
        ])->values()->toArray();
    }

    /**
     * Generate text recommendations from dashboard counts.
     */
    private function buildRecommendations(
        int $needsAttn, int $slow, int $unclassified, int $overstocked,
        int $total, array $rulesPerformance
    ): array {
        if ($total === 0) {
            return ['No products synced yet. Sync products from Shopify to begin classification.'];
        }

        $recs = [];

        if ($needsAttn > 0) {
            $recs[] = "{$needsAttn} " . ($needsAttn === 1 ? 'product needs' : 'products need') . " attention. Review missing content, images, or product setup.";
        }
        if ($slow > 0) {
            $recs[] = "{$slow} " . ($slow === 1 ? 'product is' : 'products are') . " slow. Consider discounts, promotion, or visibility improvements.";
        }
        if ($overstocked > 0) {
            $recs[] = "{$overstocked} " . ($overstocked === 1 ? 'product is' : 'products are') . " overstocked. Consider clearance sales or bundle offers.";
        }
        if ($unclassified > 0) {
            $recs[] = "{$unclassified} " . ($unclassified === 1 ? 'product is' : 'products are') . " unclassified. Create or adjust rules to improve classification coverage.";
        }

        $hasNoRules = empty(array_filter($rulesPerformance, fn ($r) => $r['is_active']));
        if ($hasNoRules && $total > 0) {
            $recs[] = 'No active rules found. Create classification rules to automatically categorize your products.';
        }

        if (empty($recs)) {
            $recs[] = 'Your product catalog looks healthy based on current rules.';
        }

        return $recs;
    }

    // ── Legacy order search (kept for compatibility) ──────────────────────────

    public function orderSeacrhfilter(Request $request)
    {
        $filters = $request->all();
        $filters['relation'] = [
            'orderCustomer',
            'OrderFulfillments',
            'OrderLineItems',
            'OrderShippingAddress',
        ];

        $filters['financial_status']    = $request->financial_status;
        $filters['fulfillment_status']  = $request->fulfillment_status;

        return $this->OrderRepository->SearchFilter($filters);
    }
}
