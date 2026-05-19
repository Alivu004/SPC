import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/Embedded/AuthenticatedLayout';
import { BlockStack, Page } from '@shopify/polaris';
import RuleForm from '@/Components/Rules/RuleForm';

// ─── Static Fallback Data (used when no backend prop is provided) ──────────────

const SAMPLE_RULE = {
    id:             1,
    name:           'Slow Moving Products',
    description:    'Identifies products that have high inventory but low recent sales activity.',
    assignedStatus: 'slow',
    matchType:      'ALL',
    priority:       10,
    isActive:       true,
    conditions: [
        { field: 'total_inventory', operator: 'greater_than', value: '50' },
        { field: 'shopify_status',  operator: 'equals',       value: 'active' },
    ],
};

// ─── Edit Rule Page ───────────────────────────────────────────────────────────

export default function Edit({ rule: serverRule }) {
    const rule  = serverRule ?? SAMPLE_RULE;
    const query = usePage().props?.ziggy?.query ?? {};

    const [loading, setLoading] = useState(false);

    const handleSubmit = (data) => {
        setLoading(true);

        router.put(route('rules.update', { id: rule.id, ...query }), data, {
            onSuccess: () => setLoading(false),
            onError:   () => setLoading(false),
        });
    };

    const goToIndex = () => router.get(route('rules.index', query));

    return (
        <AuthenticatedLayout>
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
                        onSubmit={handleSubmit}
                        loading={loading}
                        onCancel={goToIndex}
                    />
                </BlockStack>
            </Page>
        </AuthenticatedLayout>
    );
}
