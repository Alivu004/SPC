import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
// import AuthenticatedLayout from '@/Layouts/Embedded/AuthenticatedLayout';
import { BlockStack, Page } from '@shopify/polaris';
import RuleForm from '@/Components/Rules/RuleForm';

// ─── Static Fallback Data (used when no backend prop is provided) ──────────────

const SAMPLE_RULE = {
    id:                1,
    name:              'Slow Moving Products',
    description:       'Identifies products that have high inventory but low recent sales activity.',
    product_status_id: 2,
    match_type:        'all',
    priority:          10,
    is_active:         true,
    conditions: [
        { field_key: 'total_inventory', operator: 'greater_than', value: '50',     value_type: '' },
        { field_key: 'shopify_status',  operator: 'equals',       value: 'active', value_type: '' },
    ],
};

// ─── Edit Rule Page ───────────────────────────────────────────────────────────

export default function Edit({ rule: serverRule, product_statuses, allowed_field_keys, allowed_operators }) {
    const rule       = serverRule ?? SAMPLE_RULE;
    const { errors } = usePage().props;
    const query      = usePage().props?.ziggy?.query ?? {};

    const [loading, setLoading] = useState(false);

    const handleSubmit = (data) => {
        setLoading(true);
        router.put(route('rules.update', { rule: rule.id, ...query }), data, {
            onSuccess: () => setLoading(false),
            onError:   () => setLoading(false),
        });
    };

    const goToIndex = () => router.get(route('rules.index', query));

    return (
        <>
            <Page
                title={`Edit Rule: ${rule.name}`}
                subtitle="Update the conditions, status assignment, or configuration of this rule."
                backAction={{
                    content:  'Rules',
                    onAction: goToIndex,
                }}
            >
                <BlockStack gap="500">
                    <RuleForm
                        initialValues={rule}
                        productStatuses={product_statuses}
                        fieldKeyOptions={allowed_field_keys}
                        operatorOptions={allowed_operators}
                        serverErrors={errors}
                        onSubmit={handleSubmit}
                        loading={loading}
                        onCancel={goToIndex}
                    />
                </BlockStack>
            </Page>
        </>
    );
}
