import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/Embedded/AuthenticatedLayout';
import { BlockStack, Page } from '@shopify/polaris';
import RuleForm from '@/Components/Rules/RuleForm';

// ─── Create Rule Page ─────────────────────────────────────────────────────────

export default function Create({ product_statuses, allowed_field_keys, allowed_operators }) {
    const { errors } = usePage().props;
    const query      = usePage().props?.ziggy?.query ?? {};
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (data) => {
        console.log('Submitting new rule with data 12:', data);
        setLoading(true);

        try {
            const response = await fetch(route('rules.store', query), {
                method: 'POST',

                body: JSON.stringify(data),
            });

            if (!response.ok) throw new Error('Request failed');

        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const goToIndex = () => router.get(route('rules.index', query));

    return (
        <>
            <Page
                title="Create Rule"
                subtitle="Set up conditions to automatically classify products and assign a status."
                backAction={{
                    content:  'Rules',
                    onAction: goToIndex,
                }}
            >
                <BlockStack gap="500">
                    <RuleForm
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
