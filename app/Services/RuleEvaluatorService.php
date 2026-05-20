<?php

namespace App\Services;

use App\Models\Products\Product;
use App\Models\ClassificationRule;

class RuleEvaluatorService
{
    public function __construct(
        private readonly ConditionEvaluatorService $conditionEvaluator
    ) {}

    /**
     * Find the first matching active rule for a product.
     * Rules are sorted by priority ASC (1 = highest priority).
     * Returns null when no rule matches.
     */
    public function getMatchingRule(Product $product): ?array
    {
        $rules = ClassificationRule::with('conditions')
            ->where('user_id', $product->user_id)
            ->where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get();

        foreach ($rules as $rule) {
            $result = $this->evaluateRule($product, $rule);
            if ($result['matched']) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Evaluate all conditions of a rule against a product.
     * match_type all = every condition must pass (AND logic).
     * match_type any = at least one condition must pass (OR logic).
     * A rule with no conditions never matches.
     */
    public function evaluateRule(Product $product, ClassificationRule $rule): array
    {
        $conditions = $rule->conditions;
        $conditionResults = [];

        foreach ($conditions as $condition) {
            $conditionResults[] = $this->conditionEvaluator->evaluate($product, $condition);
        }

        if (empty($conditionResults)) {
            // A rule with no conditions never matches
            return [
                'matched'           => false,
                'rule'              => $rule,
                'match_type'        => $rule->match_type,
                'condition_results' => $conditionResults,
            ];
        }

        $matched = match ($rule->match_type) {
            'all'   => collect($conditionResults)->every(fn($r) => $r['matched']),
            'any'   => collect($conditionResults)->contains(fn($r) => $r['matched']),
            default => false,
        };

        return [
            'matched'           => $matched,
            'rule'              => $rule,
            'match_type'        => $rule->match_type,
            'condition_results' => $conditionResults,
        ];
    }
}
