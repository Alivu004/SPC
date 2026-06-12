import { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/Embedded/AuthenticatedLayout';
import {
    Badge,
    Banner,
    BlockStack,
    Button,
    Card,
    InlineGrid,
    InlineStack,
    Page,
    ResourceItem,
    ResourceList,
    Text,
} from '@shopify/polaris';

const STATUS_TONE = {
    active: 'success',
    draft: 'attention',
    archived: 'critical',
};

export default function Dashboard({
    activeProducts = 0,
    slowMovers = 0,
    inactiveProducts = 0,
    totalSynced = 0,
    lastSyncedAt = null,
    needsAttention = [],
}) {
    const [syncing, setSyncing] = useState(false);

    const handleSync = () => {
        setSyncing(true);
        router.post('/sync', {}, {
            onFinish: () => setSyncing(false),
        });
    };

    const formattedLastSync = lastSyncedAt
        ? new Date(lastSyncedAt).toLocaleString()
        : 'Never';

    return (
        <>
            <Page
                title="Dashboard"
                primaryAction={
                    <Button
                        variant="primary"
                        loading={syncing}
                        onClick={handleSync}
                    >
                        Sync Now
                    </Button>
                }
            >
                <BlockStack gap="500">

                    {/* Stat Cards */}
                    <InlineGrid columns={{ xs: 1, sm: 2, md: 4 }} gap="400">
                        <Card>
                            <BlockStack gap="200">
                                <Text variant="headingSm" as="h3" tone="subdued">
                                    Active Products
                                </Text>
                                <Text variant="headingXl" as="p">
                                    {activeProducts}
                                </Text>
                            </BlockStack>
                        </Card>

                        <Card>
                            <BlockStack gap="200">
                                <Text variant="headingSm" as="h3" tone="subdued">
                                    Slow Movers
                                </Text>
                                <Text variant="headingXl" as="p">
                                    {slowMovers}
                                </Text>
                            </BlockStack>
                        </Card>

                        <Card>
                            <BlockStack gap="200">
                                <Text variant="headingSm" as="h3" tone="subdued">
                                    Inactive Products
                                </Text>
                                <Text variant="headingXl" as="p">
                                    {inactiveProducts}
                                </Text>
                            </BlockStack>
                        </Card>

                        <Card>
                            <BlockStack gap="200">
                                <Text variant="headingSm" as="h3" tone="subdued">
                                    Total Synced
                                </Text>
                                <Text variant="headingXl" as="p">
                                    {totalSynced}
                                </Text>
                            </BlockStack>
                        </Card>
                    </InlineGrid>

                    {/* Sync Status Banner */}
                    <Banner tone="info">
                        <Text as="p">
                            Last synced: <strong>{formattedLastSync}</strong>
                        </Text>
                    </Banner>

                    {/* Needs Attention */}
                    <Card>
                        <BlockStack gap="300">
                            <Text variant="headingMd" as="h2">
                                Needs Attention
                            </Text>
                            {needsAttention.length === 0 ? (
                                <Text as="p" tone="subdued">
                                    All products are active. Nothing needs attention.
                                </Text>
                            ) : (
                                <ResourceList
                                    resourceName={{ singular: 'product', plural: 'products' }}
                                    items={needsAttention}
                                    renderItem={(product) => {
                                        const { id, title, status } = product;
                                        return (
                                            <ResourceItem
                                                id={String(id)}
                                                name={title}
                                                accessibilityLabel={`View details for ${title}`}
                                            >
                                                <InlineStack align="space-between" blockAlign="center">
                                                    <Text variant="bodyMd" as="span" fontWeight="semibold">
                                                        {title}
                                                    </Text>
                                                    <Badge tone={STATUS_TONE[status] ?? 'attention'}>
                                                        {status}
                                                    </Badge>
                                                </InlineStack>
                                            </ResourceItem>
                                        );
                                    }}
                                />
                            )}
                        </BlockStack>
                    </Card>

                </BlockStack>
            </Page>
        </>
    );
}
