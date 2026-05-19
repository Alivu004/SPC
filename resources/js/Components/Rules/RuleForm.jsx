import { useState, useCallback } from 'react';
import { router } from '@inertiajs/react';
import {
    Badge,
    Banner,
    BlockStack,
    Box,
    Button,
    Card,
    Checkbox,
    Divider,
    FormLayout,
    InlineGrid,
    InlineStack,
    Select,
    Text,
    TextField,
} from '@shopify/polaris';
import { PlusIcon, XSmallIcon } from '@shopify/polaris-icons';

// ─── Constants ────────────────────────────────────────────────────────────────

const PRODUCT_FIELD_OPTIONS = [
    { label: 'Title', value: 'title' },
    { label: 'Vendor', value: 'vendor' },
    { label: 'Product Type', value: 'product_type' },
    { label: 'Shopify Status', value: 'shopify_status' },
    { label: 'Tags', value: 'tags' },
    { label: 'Total Inventory', value: 'total_inventory' },
    { label: 'Variants Count', value: 'variants_count' },
    { label: 'Min Price', value: 'min_price' },
    { label: 'Max Price', value: 'max_price' },
    { label: 'Has Image', value: 'has_image' },
    { label: 'Has Description', value: 'has_description' },
    { label: 'Published At', value: 'published_at' },
    { label: 'Created At (Shopify)', value: 'created_at_shopify' },
    { label: 'Updated At (Shopify)', value: 'updated_at_shopify' },
];

const OPERATOR_OPTIONS = [
    { label: 'Equals', value: 'equals' },
    { label: 'Not Equals', value: 'not_equals' },
    { label: 'Contains', value: 'contains' },
    { label: 'Does Not Contain', value: 'does_not_contain' },
    { label: 'Greater Than', value: 'greater_than' },
    { label: 'Less Than', value: 'less_than' },
    { label: 'Greater Than or Equal', value: 'greater_than_or_equal' },
    { label: 'Less Than or Equal', value: 'less_than_or_equal' },
    { label: 'Is Empty', value: 'is_empty' },
    { label: 'Is Not Empty', value: 'is_not_empty' },
    { label: 'Older Than (days)', value: 'older_than_days' },
    { label: 'Within Last (days)', value: 'within_last_days' },
];

const ASSIGN_STATUS_OPTIONS = [
    { label: 'Active', value: 'active' },
    { label: 'Slow', value: 'slow' },
    { label: 'Inactive', value: 'inactive' },
    { label: 'Needs Attention', value: 'needs_attention' },
    { label: 'Overstocked', value: 'overstocked' },
];

const MATCH_TYPE_OPTIONS = [
    { label: 'ALL — Every condition must match', value: 'ALL' },
    { label: 'ANY — At least one condition must match', value: 'ANY' },
];

export const STATUS_TONE = {
    active:          'success',
    slow:            'warning',
    inactive:        'critical',
    needs_attention: 'attention',
    overstocked:     'info',
};

const DEFAULT_CONDITION = { field: 'title', operator: 'equals', value: '' };

// ─── Condition Row ────────────────────────────────────────────────────────────

function ConditionRow({ condition, index, onUpdate, onRemove, canRemove }) {
    return (
        <Box background="bg-surface-secondary" padding="300" borderRadius="200">
            <InlineGrid columns={['1fr', '1fr', '1.2fr', 'auto']} gap="200" alignItems="end">
                <Select
                    label="Field"
                    options={PRODUCT_FIELD_OPTIONS}
                    value={condition.field}
                    onChange={(value) => onUpdate(index, 'field', value)}
                />
                <Select
                    label="Operator"
                    options={OPERATOR_OPTIONS}
                    value={condition.operator}
                    onChange={(value) => onUpdate(index, 'operator', value)}
                />
                <TextField
                    label="Value"
                    placeholder="Enter value..."
                    value={condition.value}
                    onChange={(value) => onUpdate(index, 'value', value)}
                    autoComplete="off"
                />
                <Box paddingBlockStart="500">
                    <Button
                        icon={XSmallIcon}
                        tone="critical"
                        variant="tertiary"
                        disabled={!canRemove}
                        onClick={() => onRemove(index)}
                        accessibilityLabel="Remove condition"
                    />
                </Box>
            </InlineGrid>
        </Box>
    );
}

// ─── Rule Preview Card ────────────────────────────────────────────────────────

function RulePreview({ ruleName, matchType, conditions, assignedStatus }) {
    const statusLabel =
        ASSIGN_STATUS_OPTIONS.find((o) => o.value === assignedStatus)?.label ?? assignedStatus;
    const tone = STATUS_TONE[assignedStatus] ?? 'info';

    return (
        <BlockStack gap="300">
            <Text variant="headingMd" as="h3">
                Rule Preview
            </Text>

            {ruleName ? (
                <Text variant="bodySm" tone="subdued" fontWeight="semibold">
                    {ruleName}
                </Text>
            ) : (
                <Text variant="bodySm" tone="subdued">
                    Fill in the rule name to see a preview.
                </Text>
            )}

            <Divider />

            <Text variant="bodySm" tone="subdued">
                If <strong>{matchType}</strong> of these conditions match:
            </Text>

            <Box background="bg-surface-secondary" padding="200" borderRadius="100">
                <BlockStack gap="100">
                    {conditions.map((cond, i) => {
                        const fieldLabel =
                            PRODUCT_FIELD_OPTIONS.find((o) => o.value === cond.field)?.label ??
                            cond.field;
                        const opLabel =
                            OPERATOR_OPTIONS.find((o) => o.value === cond.operator)?.label ??
                            cond.operator;

                        return (
                            <Text key={i} variant="bodySm">
                                <span style={{ color: 'var(--p-color-text-subdued)' }}>→ </span>
                                <strong>{fieldLabel}</strong>{' '}
                                <em style={{ color: 'var(--p-color-text-secondary)' }}>
                                    {opLabel}
                                </em>{' '}
                                {cond.value ? (
                                    <strong>{cond.value}</strong>
                                ) : (
                                    <span style={{ color: 'var(--p-color-text-disabled)' }}>
                                        ...
                                    </span>
                                )}
                            </Text>
                        );
                    })}
                </BlockStack>
            </Box>

            <Divider />

            <InlineStack gap="200" blockAlign="center">
                <Text variant="bodySm">Then assign status:</Text>
                <Badge tone={tone}>{statusLabel}</Badge>
            </InlineStack>
        </BlockStack>
    );
}

// ─── Main RuleForm Component ──────────────────────────────────────────────────

export default function RuleForm({
    initialValues = {},
    onSubmit,
    loading = false,
    onCancel,
}) {
    const [name, setName]                   = useState(initialValues.name        ?? '');
    const [description, setDescription]     = useState(initialValues.description ?? '');
    const [assignedStatus, setAssignedStatus] = useState(initialValues.assignedStatus ?? 'active');
    const [matchType, setMatchType]         = useState(initialValues.matchType   ?? 'ALL');
    const [priority, setPriority]           = useState(String(initialValues.priority ?? '1'));
    const [isActive, setIsActive]           = useState(initialValues.isActive    ?? true);
    const [conditions, setConditions]       = useState(
        initialValues.conditions?.length
            ? initialValues.conditions
            : [{ ...DEFAULT_CONDITION }],
    );
    const [errors, setErrors] = useState({});

    // ── Condition handlers ────────────────────────────────────────────────────

    const addCondition = useCallback(() => {
        setConditions((prev) => [...prev, { ...DEFAULT_CONDITION }]);
    }, []);

    const removeCondition = useCallback((index) => {
        setConditions((prev) => prev.filter((_, i) => i !== index));
    }, []);

    const updateCondition = useCallback((index, key, value) => {
        setConditions((prev) => {
            const next = [...prev];
            next[index] = { ...next[index], [key]: value };
            return next;
        });
    }, []);

    // ── Validation & submit ───────────────────────────────────────────────────

    const validate = () => {
        const errs = {};
        if (!name.trim()) {
            errs.name = 'Rule name is required.';
        }
        if (!priority || isNaN(Number(priority)) || Number(priority) < 1) {
            errs.priority = 'Priority must be a positive number.';
        }
        setErrors(errs);
        return Object.keys(errs).length === 0;
    };

    const handleSubmit = () => {
        if (!validate()) return;
        onSubmit?.({
            name,
            description,
            assignedStatus,
            matchType,
            priority: Number(priority),
            isActive,
            conditions,
        });
    };

    const handleCancel = onCancel ?? (() => router.visit('/rules'));

    // ── Render ────────────────────────────────────────────────────────────────

    return (
        <BlockStack gap="500">
            <InlineGrid columns={{ xs: 1, md: '2fr 1fr' }} gap="500">

                {/* ── Left column ── */}
                <BlockStack gap="500">

                    {/* Basic Information Card */}
                    <Card>
                        <BlockStack gap="400">
                            <Text variant="headingMd" as="h2">
                                Rule Information
                            </Text>
                            <Divider />
                            <FormLayout>
                                <TextField
                                    label="Rule Name"
                                    value={name}
                                    onChange={setName}
                                    autoComplete="off"
                                    error={errors.name}
                                    placeholder="e.g. Slow Moving Products"
                                />
                                <TextField
                                    label="Description"
                                    value={description}
                                    onChange={setDescription}
                                    multiline={3}
                                    autoComplete="off"
                                    placeholder="Describe what this rule does and when it should apply..."
                                />
                                <FormLayout.Group>
                                    <Select
                                        label="Assign Status"
                                        options={ASSIGN_STATUS_OPTIONS}
                                        value={assignedStatus}
                                        onChange={setAssignedStatus}
                                        helpText="Status applied to matching products"
                                    />
                                    <Select
                                        label="Match Type"
                                        options={MATCH_TYPE_OPTIONS}
                                        value={matchType}
                                        onChange={setMatchType}
                                        helpText="How conditions are evaluated"
                                    />
                                </FormLayout.Group>
                                <TextField
                                    label="Priority"
                                    type="number"
                                    value={priority}
                                    onChange={setPriority}
                                    autoComplete="off"
                                    error={errors.priority}
                                    helpText="Higher number = evaluated first when multiple rules match"
                                    min="1"
                                />
                                <Checkbox
                                    label="Rule is active"
                                    helpText="Inactive rules are skipped during product classification"
                                    checked={isActive}
                                    onChange={setIsActive}
                                />
                            </FormLayout>
                        </BlockStack>
                    </Card>

                    {/* Condition Builder Card */}
                    <Card>
                        <BlockStack gap="400">
                            <InlineStack align="space-between" blockAlign="center">
                                <Text variant="headingMd" as="h2">
                                    Conditions
                                </Text>
                                <Badge tone={matchType === 'ALL' ? 'info' : 'attention'}>
                                    {matchType}
                                </Badge>
                            </InlineStack>

                            <Text tone="subdued" variant="bodySm">
                                Products must satisfy these conditions for this rule to apply.
                                Use the <strong>Add Condition</strong> button to add more rows.
                            </Text>

                            <Divider />

                            <BlockStack gap="200">
                                {conditions.map((condition, index) => (
                                    <ConditionRow
                                        key={index}
                                        condition={condition}
                                        index={index}
                                        onUpdate={updateCondition}
                                        onRemove={removeCondition}
                                        canRemove={conditions.length > 1}
                                    />
                                ))}
                            </BlockStack>

                            <div>
                                <Button icon={PlusIcon} variant="plain" onClick={addCondition}>
                                    Add Condition
                                </Button>
                            </div>
                        </BlockStack>
                    </Card>

                </BlockStack>

                {/* ── Right column ── */}
                <BlockStack gap="500">

                    {/* Rule Preview Card */}
                    <Card>
                        <RulePreview
                            ruleName={name}
                            matchType={matchType}
                            conditions={conditions}
                            assignedStatus={assignedStatus}
                        />
                    </Card>

                    {/* Help / Explanation Card */}
                    <Card>
                        <BlockStack gap="300">
                            <Text variant="headingMd" as="h3">
                                How Rules Work
                            </Text>
                            <Divider />
                            <BlockStack gap="200">
                                <Text variant="bodySm">
                                    <strong>ALL</strong> — every condition must match for the
                                    status to be assigned.
                                </Text>
                                <Text variant="bodySm">
                                    <strong>ANY</strong> — at least one condition must match for
                                    the status to be assigned.
                                </Text>
                            </BlockStack>
                            <Divider />
                            <Text variant="bodySm" tone="subdued">
                                Rules run in priority order. The highest-priority matching rule
                                determines the product's status. Lower-priority rules are skipped
                                once a match is found.
                            </Text>
                            <Banner tone="info">
                                <Text variant="bodySm">
                                    Tip: Use broad rules at lower priority and specific rules at
                                    higher priority for best results.
                                </Text>
                            </Banner>
                        </BlockStack>
                    </Card>

                </BlockStack>
            </InlineGrid>

            {/* Action Buttons */}
            <InlineStack align="end" gap="300">
                <Button onClick={handleCancel}>Cancel</Button>
                <Button variant="primary" loading={loading} onClick={handleSubmit}>
                    Save Rule
                </Button>
            </InlineStack>
        </BlockStack>
    );
}
