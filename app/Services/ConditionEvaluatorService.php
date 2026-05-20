<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Products\Product;
use App\Models\ClassificationCondition;

class ConditionEvaluatorService
{
    private const NUMERIC_FIELDS = ['total_inventory', 'variants_count', 'min_price', 'max_price'];
    private const BOOLEAN_FIELDS = ['has_image', 'has_description'];
    private const DATE_FIELDS    = ['published_at', 'created_at_shopify', 'updated_at_shopify'];

    /**
     * Evaluate a single classification condition against a product.
     * Returns array with keys: matched, field_key, operator, expected_value, actual_value, message.
     */
    public function evaluate(Product $product, ClassificationCondition $condition): array
    {
        $fieldKey      = $condition->field_key;
        $operator      = $condition->operator;
        $expectedValue = $condition->value;

        // Guard: field must be in the allowed classification fields
        $allowedFields = config('product-classifier.allowed_field_keys', []);
        if (!in_array($fieldKey, $allowedFields)) {
            return $this->result(
                false, $fieldKey, $operator, $expectedValue, null,
                "Field '{$fieldKey}' is not a supported classification field."
            );
        }

        // Get actual value through Eloquent (casts applied automatically)
        $actualValue = $product->$fieldKey;

        if (in_array($fieldKey, self::BOOLEAN_FIELDS)) {
            return $this->evaluateBoolean($fieldKey, $actualValue, $operator, $expectedValue);
        }

        if (in_array($fieldKey, self::DATE_FIELDS)) {
            return $this->evaluateDate($fieldKey, $actualValue, $operator, $expectedValue);
        }

        if (in_array($fieldKey, self::NUMERIC_FIELDS)) {
            return $this->evaluateNumeric($fieldKey, $actualValue, $operator, $expectedValue);
        }

        return $this->evaluateString($fieldKey, $actualValue, $operator, $expectedValue);
    }

    // -------------------------------------------------------------------------
    // Boolean fields: has_image, has_description
    // Supported operators: equals, not_equals, is_empty, is_not_empty
    // Expected values: true/false/1/0/yes/no
    // -------------------------------------------------------------------------

    private function evaluateBoolean(string $fieldKey, $actualValue, string $operator, ?string $expectedValue): array
    {
        if ($operator === 'is_empty') {
            $matched = $actualValue === null;
            return $this->result(
                $matched, $fieldKey, $operator, null, $this->boolDisplay($actualValue),
                "{$fieldKey} is " . ($matched ? 'empty (null)' : 'not empty')
            );
        }

        if ($operator === 'is_not_empty') {
            $matched = $actualValue !== null;
            return $this->result(
                $matched, $fieldKey, $operator, null, $this->boolDisplay($actualValue),
                "{$fieldKey} is " . ($matched ? 'not empty' : 'empty (null)')
            );
        }

        $actualBool   = $this->toBool($actualValue);
        $expectedBool = $this->toBool($expectedValue);
        $displayActual   = $this->boolDisplay($actualBool);
        $displayExpected = $this->boolDisplay($expectedBool);

        $matched = match ($operator) {
            'equals'     => $actualBool === $expectedBool,
            'not_equals' => $actualBool !== $expectedBool,
            default      => false,
        };

        return $this->result(
            $matched, $fieldKey, $operator, $displayExpected, $displayActual,
            "{$fieldKey} is {$displayActual}, expected {$operator} {$displayExpected}"
        );
    }

    // -------------------------------------------------------------------------
    // Date fields: published_at, created_at_shopify, updated_at_shopify
    // Supported operators: older_than_days, within_last_days, is_empty, is_not_empty
    // -------------------------------------------------------------------------

    private function evaluateDate(string $fieldKey, $actualValue, string $operator, ?string $expectedValue): array
    {
        if ($operator === 'is_empty') {
            $matched = $actualValue === null;
            return $this->result(
                $matched, $fieldKey, $operator, null, (string) $actualValue,
                "{$fieldKey} is " . ($matched ? 'empty (null)' : 'not empty')
            );
        }

        if ($operator === 'is_not_empty') {
            $matched = $actualValue !== null;
            return $this->result(
                $matched, $fieldKey, $operator, null, (string) $actualValue,
                "{$fieldKey} is " . ($matched ? 'not empty' : 'empty (null)')
            );
        }

        if ($actualValue === null) {
            return $this->result(
                false, $fieldKey, $operator, $expectedValue, null,
                "{$fieldKey} is null — cannot evaluate date condition."
            );
        }

        try {
            $date = $actualValue instanceof Carbon
                ? $actualValue
                : Carbon::parse($actualValue);

            $days    = (int) $expectedValue;
            $diffDays = (int) $date->diffInDays(now());

            $matched = match ($operator) {
                'older_than_days'  => $diffDays > $days,
                'within_last_days' => $diffDays <= $days,
                default            => false,
            };

            $displayActual = $date->toDateTimeString();
            $message = "{$fieldKey} ({$displayActual}) is {$diffDays} days old — "
                . ($matched ? 'matches' : 'does not match') . " {$operator} {$days}";

            return $this->result($matched, $fieldKey, $operator, (string) $days, $displayActual, $message);
        } catch (\Throwable $e) {
            return $this->result(
                false, $fieldKey, $operator, $expectedValue, (string) $actualValue,
                "Failed to parse date for {$fieldKey}: " . $e->getMessage()
            );
        }
    }

    // -------------------------------------------------------------------------
    // Numeric fields: total_inventory, variants_count, min_price, max_price
    // Supported operators: equals, not_equals, greater_than, less_than,
    //   greater_than_or_equal, less_than_or_equal, is_empty, is_not_empty
    // -------------------------------------------------------------------------

    private function evaluateNumeric(string $fieldKey, $actualValue, string $operator, ?string $expectedValue): array
    {
        if ($operator === 'is_empty') {
            $matched = $actualValue === null;
            return $this->result(
                $matched, $fieldKey, $operator, null, $actualValue,
                "{$fieldKey} is " . ($matched ? 'empty (null)' : 'not empty')
            );
        }

        if ($operator === 'is_not_empty') {
            $matched = $actualValue !== null;
            return $this->result(
                $matched, $fieldKey, $operator, null, $actualValue,
                "{$fieldKey} is " . ($matched ? 'not empty' : 'empty (null)')
            );
        }

        if ($actualValue === null) {
            return $this->result(
                false, $fieldKey, $operator, $expectedValue, null,
                "{$fieldKey} is null — cannot evaluate numeric condition."
            );
        }

        $numActual   = (float) $actualValue;
        $numExpected = (float) $expectedValue;

        $matched = match ($operator) {
            'equals'                => $numActual == $numExpected,
            'not_equals'            => $numActual != $numExpected,
            'greater_than'          => $numActual > $numExpected,
            'less_than'             => $numActual < $numExpected,
            'greater_than_or_equal' => $numActual >= $numExpected,
            'less_than_or_equal'    => $numActual <= $numExpected,
            default                 => false,
        };

        $message = "{$fieldKey} {$numActual} {$operator} {$numExpected}";
        return $this->result($matched, $fieldKey, $operator, (string) $numExpected, $numActual, $message);
    }

    // -------------------------------------------------------------------------
    // String fields: title, vendor, product_type, shopify_status, tags
    // Supported operators: equals, not_equals, contains, does_not_contain,
    //   is_empty, is_not_empty
    // Comparisons are case-insensitive.
    // -------------------------------------------------------------------------

    private function evaluateString(string $fieldKey, $actualValue, string $operator, ?string $expectedValue): array
    {
        $strActual   = (string) ($actualValue ?? '');
        $strExpected = (string) ($expectedValue ?? '');

        if ($operator === 'is_empty') {
            $matched = trim($strActual) === '';
            return $this->result(
                $matched, $fieldKey, $operator, null, $strActual,
                "{$fieldKey} is " . ($matched ? 'empty' : 'not empty')
            );
        }

        if ($operator === 'is_not_empty') {
            $matched = trim($strActual) !== '';
            return $this->result(
                $matched, $fieldKey, $operator, null, $strActual,
                "{$fieldKey} is " . ($matched ? 'not empty' : 'empty')
            );
        }

        $lowerActual   = strtolower($strActual);
        $lowerExpected = strtolower($strExpected);

        $matched = match ($operator) {
            'equals'           => $lowerActual === $lowerExpected,
            'not_equals'       => $lowerActual !== $lowerExpected,
            'contains'         => str_contains($lowerActual, $lowerExpected),
            'does_not_contain' => !str_contains($lowerActual, $lowerExpected),
            default            => false,
        };

        $message = "{$fieldKey} '{$strActual}' {$operator} '{$strExpected}'";
        return $this->result($matched, $fieldKey, $operator, $strExpected, $strActual, $message);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Normalise a value to bool.
     * Accepts: true/false, 1/0, "true"/"false", "yes"/"no", "1"/"0", "on"/"off".
     */
    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return $value !== 0 && $value !== 0.0;
        }
        return in_array(strtolower((string) $value), ['true', '1', 'yes', 'on'], true);
    }

    private function boolDisplay($value): string
    {
        return $this->toBool($value) ? 'true' : 'false';
    }

    private function result(
        bool $matched,
        string $fieldKey,
        string $operator,
        $expectedValue,
        $actualValue,
        string $message
    ): array {
        return [
            'matched'        => $matched,
            'field_key'      => $fieldKey,
            'operator'       => $operator,
            'expected_value' => $expectedValue,
            'actual_value'   => $actualValue,
            'message'        => $message,
        ];
    }
}
