<?php

namespace App\Services;

use App\Models\User;
use App\Models\ProductStatus;
use App\Models\Products\Product;
use App\Models\ProductClassification;
use App\Models\ProductClassificationHistory;

class ProductClassificationService
{
    public function __construct(
        private readonly ProductWebhookService $webhookService,
        private readonly RuleEvaluatorService  $ruleEvaluator
    ) {}

    /**
     * Entry point called from the product webhook job.
     * Upserts the ShopifyProduct record then runs classification rules.
     */
    public function classifyProductFromWebhook(User $user, array $payload): ProductClassification
    {
        $product = $this->webhookService->upsertProductFromWebhook($user, $payload);
        return $this->classifyProduct($product, 'product_webhook');
    }

    /**
     * Evaluate all active rules for a product and persist the result.
     * Matches the first rule by priority (ASC). Uses the user's default status
     * when no rule matches, or marks the product as Unclassified.
     * Upserts product_classifications and appends history only on status change.
     */
    public function classifyProduct(
        Product $product,
        string $triggeredBy = 'product_webhook'
    ): ProductClassification {
        $ruleResult = $this->ruleEvaluator->getMatchingRule($product);

        [$status, $ruleId, $explanation] = $this->resolveStatusAndExplanation($product, $ruleResult);

        // Read the current classification before we overwrite it
        $existing = ProductClassification::where('user_id', $product->user_id)
            ->where('product_id', $product->id)
            ->first();

        $previousStatusId = $existing?->product_status_id;
        $newStatusId      = $status?->id;

        $classification = ProductClassification::updateOrCreate(
            [
                'user_id'    => $product->user_id,
                'product_id' => $product->id,
            ],
            [
                'product_status_id'      => $newStatusId,
                'classification_rule_id' => $ruleId,
                'status_name'            => $status?->name ?? 'Unclassified',
                'status_color'           => $status?->color,
                'explanation'            => $explanation,
                'classified_at'          => now(),
            ]
        );

        // Record history only when the assigned status actually changed (or first-time classification)
        $statusChanged = $existing === null || $previousStatusId !== $newStatusId;

        if ($statusChanged) {
            ProductClassificationHistory::create([
                'user_id'    => $product->user_id,
                'product_id' => $product->id,
                'previous_status_id'     => $previousStatusId,
                'new_status_id'          => $newStatusId,
                'classification_rule_id' => $ruleId,
                'explanation'            => $explanation,
                'triggered_by'           => $triggeredBy,
                'classified_at'          => now(),
            ]);
        }

        return $classification;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Determine the ProductStatus, rule ID, and explanation JSON to persist.
     * Returns a 3-element array: [ProductStatus|null, int|null (ruleId), array (explanation)].
     */
    private function resolveStatusAndExplanation(Product $product, ?array $ruleResult): array
    {
        if ($ruleResult !== null) {
            /** @var \App\Models\ClassificationRule $rule */
            $rule   = $ruleResult['rule'];
            $status = $rule->productStatus;
            $ruleId = $rule->id;

            $explanation = [
                'matched'           => true,
                'matched_rule'      => $rule->name,
                'match_type'        => $ruleResult['match_type'],
                'assigned_status'   => $status?->name,
                'condition_results' => $ruleResult['condition_results'],
            ];

            return [$status, $ruleId, $explanation];
        }

        // No rule matched — try to find the user's default status
        $status = ProductStatus::active()
            ->forUser($product->user_id)
            ->where('is_default', true)
            ->orderBy('priority')
            ->first();

        $explanation = [
            'matched'         => false,
            'matched_rule'    => null,
            'assigned_status' => $status?->name ?? 'Unclassified',
            'reason'          => $status
                ? 'No rules matched. Default status assigned.'
                : 'No rules matched and no default status is configured.',
        ];

        return [$status, null, $explanation];
    }
}
