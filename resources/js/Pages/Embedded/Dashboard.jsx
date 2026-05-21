import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/Embedded/AuthenticatedLayout';
import {
    Badge,
    BlockStack,
    Button,
    Card,
    DataTable,
    EmptyState,
    InlineGrid,
    InlineStack,
    Page,
    ProgressBar,
    Text,
    Thumbnail,
} from '@shopify/polaris';

// ── Helpers ───────────────────────────────────────────────────────────────────

const STATUS_TONE = {
    Active: 'success',
    Slow: 'warning',
    Inactive: 'critical',
    'Needs Attention': 'attention',
    Overstocked: 'info',
    Unclassified: 'subdued',
};

function statusBadge(name) {
    return <Badge tone={STATUS_TONE[name] ?? 'subdued'}>{name ?? 'Unclassified'}</Badge>;
}

function fmtDate(isoStr) {
    if (!isoStr) return '—';
    return new Date(isoStr).toLocaleDateString(undefined, {
        year: 'numeric', month: 'short', day: 'numeric',
    });
}

function fmtDateTime(isoStr) {
    if (!isoStr) return '—';
    return new Date(isoStr).toLocaleString(undefined, {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

// ── Summary Card ──────────────────────────────────────────────────────────────

function SummaryCard({ label, count, helper, tone }) {
    return (
        <Card>
            <BlockStack gap="100">
                <Text variant="headingSm" as="h3" tone="subdued">{label}</Text>
                <Text variant="heading2xl" as="p" tone={tone}>{count}</Text>
                {helper && (
                    <Text variant="bodySm" as="p" tone="subdued">{helper}</Text>
                )}
            </BlockStack>
        </Card>
    );
}

// ── Main Component ────────────────────────────────────────────────────────────

export default function Dashboard({
    summary = {},
    status_distribution = [],
    attention_products = [],
    recent_activity = [],
    rules_performance = [],
    recommendations = [],
}) {
    const s = summary;
    const noProducts       = (s.total_products ?? 0) === 0;
    const noClassifications = (s.classified_products ?? 0) === 0 && (s.total_products ?? 0) > 0;
    const noRules          = rules_performance.length === 0;

    return (
        <AuthenticatedLayout>
            <Page
                title="Smart Product Status Dashboard"
                subtitle="Monitor product states, identify issues, and understand classification performance."
                primaryAction={
                    <Button variant="primary" url="/products">
                        View Products
                    </Button>
                }
                secondaryActions={[
                    { content: 'Manage Rules', url: '/rules' },
                ]}
            >
                <BlockStack gap="600">

                    {/* ── Empty States ─────────────────────────────────────── */}
                    {noProducts && (
                        <Card>
                            <EmptyState
                                heading="No products synced yet"
                                image="https://cdn.shopify.com/s/files/1/0262/4071/2726/files/emptystate-files.png"
                            >
                                <Text as="p">
                                    Sync products from Shopify to begin classification.
                                </Text>
                            </EmptyState>
                        </Card>
                    )}

                    {noClassifications && (
                        <Card>
                            <BlockStack gap="200">
                                <Text variant="headingMd" as="h2">Classification Not Started</Text>
                                <Text as="p" tone="subdued">
                                    Products are synced but not classified yet. Review your rules or trigger classification.
                                </Text>
                                <InlineStack gap="200">
                                    <Button url="/rules">Manage Rules</Button>
                                </InlineStack>
                            </BlockStack>
                        </Card>
                    )}

                    {!noProducts && noRules && (
                        <Card>
                            <BlockStack gap="200">
                                <Text variant="headingMd" as="h2">No Rules Created</Text>
                                <Text as="p" tone="subdued">
                                    No rules found. Create classification rules to automatically categorize your products.
                                </Text>
                                <InlineStack gap="200">
                                    <Button variant="primary" url="/rules/create">Create First Rule</Button>
                                </InlineStack>
                            </BlockStack>
                        </Card>
                    )}

                    {/* ── 1. Summary Cards ──────────────────────────────────── */}
                    <BlockStack gap="300">
                        <Text variant="headingMd" as="h2">Overview</Text>
                        <InlineGrid columns={{ xs: 2, sm: 2, md: 4 }} gap="400">
                            <SummaryCard
                                label="Total Products"
                                count={s.total_products ?? 0}
                                helper="Synced from Shopify"
                            />
                            <SummaryCard
                                label="Classified Products"
                                count={s.classified_products ?? 0}
                                helper="Products with an assigned status"
                                tone="success"
                            />
                            <SummaryCard
                                label="Unclassified"
                                count={s.unclassified_products ?? 0}
                                helper="No status assigned yet"
                                tone={s.unclassified_products > 0 ? 'caution' : undefined}
                            />
                            <SummaryCard
                                label="Needs Attention"
                                count={s.needs_attention_products ?? 0}
                                helper="Products requiring merchant action"
                                tone={s.needs_attention_products > 0 ? 'critical' : undefined}
                            />
                        </InlineGrid>

                        <InlineGrid columns={{ xs: 2, sm: 2, md: 4 }} gap="400">
                            <SummaryCard
                                label="Active Products"
                                count={s.active_products ?? 0}
                                helper="Healthy, well-performing products"
                                tone="success"
                            />
                            <SummaryCard
                                label="Slow Products"
                                count={s.slow_products ?? 0}
                                helper="Low sales velocity"
                                tone={s.slow_products > 0 ? 'caution' : undefined}
                            />
                            <SummaryCard
                                label="Inactive Products"
                                count={s.inactive_products ?? 0}
                                helper="Not selling or out of stock"
                                tone={s.inactive_products > 0 ? 'critical' : undefined}
                            />
                            <SummaryCard
                                label="Overstocked"
                                count={s.overstocked_products ?? 0}
                                helper="High inventory levels"
                                tone={s.overstocked_products > 0 ? 'caution' : undefined}
                            />
                        </InlineGrid>
                    </BlockStack>

                    {/* ── 2. Status Distribution ────────────────────────────── */}
                    {status_distribution.length > 0 && (
                        <Card>
                            <BlockStack gap="400">
                                <Text variant="headingMd" as="h2">Product Status Distribution</Text>
                                <BlockStack gap="300">
                                    {status_distribution.map((row) => (
                                        <BlockStack gap="100" key={row.status_name}>
                                            <InlineStack align="space-between">
                                                <InlineStack gap="200" blockAlign="center">
                                                    {statusBadge(row.status_name)}
                                                    <Text variant="bodyMd" as="span">
                                                        {row.count} {row.count === 1 ? 'product' : 'products'}
                                                    </Text>
                                                </InlineStack>
                                                <Text variant="bodySm" as="span" tone="subdued">
                                                    {row.percentage}%
                                                </Text>
                                            </InlineStack>
                                            <ProgressBar
                                                progress={row.percentage}
                                                size="small"
                                                tone={
                                                    row.status_name === 'Active' ? 'success' :
                                                    row.status_name === 'Inactive' ? 'critical' :
                                                    row.status_name === 'Needs Attention' ? 'critical' :
                                                    'highlight'
                                                }
                                            />
                                        </BlockStack>
                                    ))}
                                </BlockStack>
                            </BlockStack>
                        </Card>
                    )}

                    {/* ── 3. Products Needing Action ────────────────────────── */}
                    <Card>
                        <BlockStack gap="400">
                            <InlineStack align="space-between" blockAlign="center">
                                <Text variant="headingMd" as="h2">Products Needing Action</Text>
                                <Button size="slim" url="/products">View All Products</Button>
                            </InlineStack>

                            {attention_products.length === 0 ? (
                                <Text as="p" tone="subdued">
                                    No products currently require action. Your catalog looks good!
                                </Text>
                            ) : (
                                <DataTable
                                    columnContentTypes={['text', 'text', 'text', 'numeric', 'text', 'text']}
                                    headings={['Product', 'Status', 'Why this matters', 'Inventory', 'Last Classified', 'Action']}
                                    rows={attention_products.map((p) => [
                                        // Product column
                                        <InlineStack gap="300" blockAlign="center" key={p.id}>
                                            <Thumbnail
                                                source={p.image_url || 'https://cdn.shopify.com/s/files/1/0533/2089/files/placeholder-images-product-1_small.png'}
                                                alt={p.title}
                                                size="small"
                                            />
                                            <BlockStack gap="0">
                                                <Text variant="bodyMd" as="span" fontWeight="semibold">
                                                    {p.title}
                                                </Text>
                                                {p.vendor && (
                                                    <Text variant="bodySm" as="span" tone="subdued">
                                                        {p.vendor}
                                                    </Text>
                                                )}
                                            </BlockStack>
                                        </InlineStack>,
                                        // Status
                                        statusBadge(p.classification_status),
                                        // Reason
                                        <Text variant="bodySm" as="span" key={`r-${p.id}`}>{p.short_reason}</Text>,
                                        // Inventory
                                        p.total_inventory,
                                        // Last classified
                                        fmtDate(p.classified_at),
                                        // Action
                                        <Button
                                            key={`a-${p.id}`}
                                            size="micro"
                                            url={`/products?search=${encodeURIComponent(p.title)}`}
                                        >
                                            View
                                        </Button>,
                                    ])}
                                />
                            )}
                        </BlockStack>
                    </Card>

                    {/* ── 4. Recent Classification Activity ────────────────── */}
                    <Card>
                        <BlockStack gap="400">
                            <Text variant="headingMd" as="h2">Recent Classification Changes</Text>

                            {recent_activity.length === 0 ? (
                                <Text as="p" tone="subdued">
                                    No recent classification activity yet.
                                </Text>
                            ) : (
                                <DataTable
                                    columnContentTypes={['text', 'text', 'text', 'text', 'text', 'text']}
                                    headings={['Product', 'Previous Status', 'New Status', 'Matched Rule', 'Triggered By', 'Classified At']}
                                    rows={recent_activity.map((h, i) => [
                                        <Text variant="bodyMd" as="span" key={`pt-${i}`}>{h.product_title}</Text>,
                                        statusBadge(h.previous_status !== '—' ? h.previous_status : null),
                                        statusBadge(h.new_status !== '—' ? h.new_status : null),
                                        <Text variant="bodySm" as="span" key={`mr-${i}`}>{h.matched_rule}</Text>,
                                        <Text variant="bodySm" as="span" key={`tb-${i}`} tone="subdued">{h.triggered_by}</Text>,
                                        <Text variant="bodySm" as="span" key={`ca-${i}`} tone="subdued">{fmtDateTime(h.classified_at)}</Text>,
                                    ])}
                                />
                            )}
                        </BlockStack>
                    </Card>

                    {/* ── 5. Rule Performance ───────────────────────────────── */}
                    <Card>
                        <BlockStack gap="400">
                            <InlineStack align="space-between" blockAlign="center">
                                <Text variant="headingMd" as="h2">Rule Performance</Text>
                                <Button size="slim" url="/rules">Manage Rules</Button>
                            </InlineStack>

                            {rules_performance.length === 0 ? (
                                <BlockStack gap="200">
                                    <Text as="p" tone="subdued">
                                        No rules created yet.
                                    </Text>
                                    <InlineStack>
                                        <Button variant="primary" url="/rules/create">Create First Rule</Button>
                                    </InlineStack>
                                </BlockStack>
                            ) : (
                                <DataTable
                                    columnContentTypes={['text', 'text', 'numeric', 'text']}
                                    headings={['Rule Name', 'Assigned Status', 'Matched Products', 'Rule Status']}
                                    rows={rules_performance.map((r, i) => [
                                        <Text variant="bodyMd" as="span" key={`rn-${i}`} fontWeight="semibold">{r.rule_name}</Text>,
                                        statusBadge(r.assigned_status !== '—' ? r.assigned_status : null),
                                        r.matched_products_count,
                                        <Badge key={`rs-${i}`} tone={r.is_active ? 'success' : 'subdued'}>
                                            {r.is_active ? 'Active' : 'Inactive'}
                                        </Badge>,
                                    ])}
                                />
                            )}
                        </BlockStack>
                    </Card>

                    {/* ── 6. Recommended Next Steps ─────────────────────────── */}
                    {recommendations.length > 0 && (
                        <Card>
                            <BlockStack gap="300">
                                <Text variant="headingMd" as="h2">Recommended Next Steps</Text>
                                <BlockStack gap="200">
                                    {recommendations.map((rec, i) => (
                                        <InlineStack key={i} gap="200" blockAlign="start" wrap={false}>
                                            <Text variant="bodyMd" as="span" tone="subdued">•</Text>
                                            <Text variant="bodyMd" as="p">{rec}</Text>
                                        </InlineStack>
                                    ))}
                                </BlockStack>
                            </BlockStack>
                        </Card>
                    )}

                </BlockStack>
            </Page>
        </AuthenticatedLayout>
    );
}
