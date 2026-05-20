import { useState, useRef } from 'react';
import { router, usePage } from '@inertiajs/react';
import {
    Page, Card, Box, BlockStack, InlineStack, InlineGrid, Text, Badge, Button,
    IndexTable, Thumbnail, EmptyState, Pagination, Select, TextField, Modal,
} from '@shopify/polaris';
import AuthenticatedLayout from '@/Layouts/Embedded/AuthenticatedLayout';

// ─── Constants ────────────────────────────────────────────────────────────────

const RECOMMENDED_ACTIONS = {
    Active: 'Product looks healthy based on the current rules.',
    Slow: 'Consider running a discount, improving product visibility, or promoting this product.',
    Inactive: 'Review whether this product should remain in the catalog or be reactivated.',
    'Needs Attention': 'Review missing product content such as image, description, vendor, or product type.',
    Overstocked: 'Consider a clearance sale, bundle offer, or stock reduction strategy.',
    Unclassified: 'No rule matched this product. Create or adjust rules to classify it properly.',
};

const STATUS_TONES = {
    Active: 'success',
    Slow: 'warning',
    Inactive: 'critical',
    'Needs Attention': 'attention',
    Overstocked: 'info',
};

const SHOPIFY_STATUS_TONES = {
    active: 'success',
    draft: 'attention',
    archived: 'critical',
};

const TABLE_HEADINGS = [
    { title: 'Product' },
    { title: 'Vendor' },
    { title: 'Type' },
    { title: 'Inventory' },
    { title: 'Price Range' },
    { title: 'Shopify Status' },
    { title: 'Classification' },
    { title: 'Reason / Remark' },
    { title: 'Last Classified' },
    { title: 'Actions' },
];

// ─── Helpers ──────────────────────────────────────────────────────────────────

function getClassificationStatus(product) {
    return product.classification?.status_name ?? 'Unclassified';
}

function getStatusTone(statusName) {
    return STATUS_TONES[statusName] ?? 'subdued';
}

function getShortRemark(product) {
    if (!product.classification) return 'No rule matched yet';
    if (product.classification.rule_name) return product.classification.rule_name;
    const exp = product.classification.explanation;
    if (Array.isArray(exp) && exp.length > 0) {
        const first = exp[0];
        return `${fmtField(first.field)} ${fmtOp(first.operator)} ${first.expected}`;
    }
    return '—';
}

function getBusinessExplanation(product) {
    if (!product.classification) return 'This product has not been classified yet.';
    const { rule_name, match_type } = product.classification;
    if (rule_name) {
        const mt = match_type === 'all' ? 'all conditions' : 'at least one condition';
        return `Matched rule "${rule_name}" (${mt} met).`;
    }
    return 'Classification data is available but the rule name is unknown.';
}

function getRecommendedAction(statusName) {
    return RECOMMENDED_ACTIONS[statusName] ?? 'Review this product manually.';
}

function formatPriceRange(min, max) {
    if (min == null && max == null) return '—';
    const fmt = (v) => `$${parseFloat(v).toFixed(2)}`;
    if (min === max || max == null) return fmt(min);
    return `${fmt(min)} – ${fmt(max)}`;
}

function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString();
}

function fmtField(key) {
    if (!key) return '';
    return key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function fmtOp(op) {
    const MAP = {
        equals: '=',
        not_equals: '≠',
        contains: 'contains',
        not_contains: 'not contains',
        greater_than: '>',
        less_than: '<',
        greater_than_or_equal: '≥',
        less_than_or_equal: '≤',
        is_empty: 'is empty',
        is_not_empty: 'is not empty',
    };
    return MAP[op] ?? op;
}

// ─── Sub-components ───────────────────────────────────────────────────────────

function SummaryCard({ title, value, tone }) {
    return (
        <Card>
            <BlockStack gap="100">
                <Text as="p" variant="bodySm" tone="subdued">{title}</Text>
                <Text as="p" variant="headingLg" tone={tone}>{value}</Text>
            </BlockStack>
        </Card>
    );
}

function LabelValue({ label, value }) {
    return (
        <InlineStack gap="200" wrap={false}>
            <Text as="span" variant="bodySm" tone="subdued" fontWeight="semibold">{label}:</Text>
            <Text as="span" variant="bodySm">{value ?? '—'}</Text>
        </InlineStack>
    );
}

function ConditionRow({ cond }) {
    const passed = cond.passed ?? cond.result === 'passed';
    return (
        <Box padding="200" background={passed ? 'bg-surface-success' : 'bg-surface-critical'} borderRadius="200">
            <InlineStack gap="300" align="space-between" wrap={false}>
                <Badge tone={passed ? 'success' : 'critical'}>{passed ? 'Passed' : 'Failed'}</Badge>
                <Text variant="bodySm">{fmtField(cond.field)}</Text>
                <Text variant="bodySm" tone="subdued">{fmtOp(cond.operator)}</Text>
                <Text variant="bodySm">{String(cond.expected ?? '—')}</Text>
                <Text variant="bodySm" tone="subdued">→ actual: {String(cond.actual ?? '—')}</Text>
            </InlineStack>
        </Box>
    );
}

function ProductDetailModal({ product, onClose }) {
    if (!product) return null;
    const status = getClassificationStatus(product);
    const exp = product.classification?.explanation;
    const conditions = Array.isArray(exp) ? exp : [];

    return (
        <Modal
            open
            onClose={onClose}
            title={product.title}
            large
            secondaryActions={[{ content: 'Close', onAction: onClose }]}
        >
            <Modal.Section>
                {/* 1 · Product Summary */}
                <BlockStack gap="400">
                    <Text as="h3" variant="headingMd">Product Summary</Text>
                    <InlineStack gap="400" wrap={false}>
                        {product.image_url && (
                            <Thumbnail source={product.image_url} alt={product.title} size="large" />
                        )}
                        <BlockStack gap="150">
                            <LabelValue label="Vendor" value={product.vendor} />
                            <LabelValue label="Type" value={product.product_type} />
                            <LabelValue label="Inventory" value={product.total_inventory} />
                            <LabelValue label="Price" value={formatPriceRange(product.min_price, product.max_price)} />
                            <LabelValue label="Has Image" value={product.has_image ? 'Yes' : 'No'} />
                            <LabelValue label="Has Description" value={product.has_description ? 'Yes' : 'No'} />
                            <LabelValue label="Shopify Status" value={product.shopify_status} />
                            <LabelValue label="Last Synced" value={formatDate(product.last_synced_at)} />
                        </BlockStack>
                    </InlineStack>
                </BlockStack>
            </Modal.Section>

            <Modal.Section>
                {/* 2 · Classification Status */}
                <BlockStack gap="300">
                    <Text as="h3" variant="headingMd">Classification</Text>
                    <InlineStack gap="200" align="start">
                        <Text variant="bodySm">Status:</Text>
                        <Badge tone={getStatusTone(status)}>{status}</Badge>
                    </InlineStack>
                    {product.classification && (
                        <>
                            <LabelValue label="Rule" value={product.classification.rule_name} />
                            <LabelValue label="Match Type" value={product.classification.match_type} />
                            <LabelValue label="Classified At" value={formatDate(product.classification.classified_at)} />
                        </>
                    )}
                </BlockStack>
            </Modal.Section>

            <Modal.Section>
                {/* 3 · Business Explanation */}
                <BlockStack gap="300">
                    <Text as="h3" variant="headingMd">Business Explanation</Text>
                    <Text variant="bodyMd">{getBusinessExplanation(product)}</Text>
                </BlockStack>
            </Modal.Section>

            {conditions.length > 0 && (
                <Modal.Section>
                    {/* 4 · Condition Results */}
                    <BlockStack gap="300">
                        <Text as="h3" variant="headingMd">Condition Results</Text>
                        <BlockStack gap="200">
                            {conditions.map((cond, i) => (
                                <ConditionRow key={i} cond={cond} />
                            ))}
                        </BlockStack>
                    </BlockStack>
                </Modal.Section>
            )}

            <Modal.Section>
                {/* 5 · Recommended Action */}
                <BlockStack gap="300">
                    <Text as="h3" variant="headingMd">Recommended Action</Text>
                    <Text variant="bodyMd">{getRecommendedAction(status)}</Text>
                </BlockStack>
            </Modal.Section>
        </Modal>
    );
}

// ─── Main Component ───────────────────────────────────────────────────────────

export default function Products({ products, summary, vendors, classificationStatuses, filters }) {
    const query = usePage().props?.ziggy?.query ?? {};

    const [search, setSearch]     = useState(filters.search ?? '');
    const [shopifySt, setShopifySt] = useState(filters.shopify_status ?? 'all');
    const [classSt, setClassSt]   = useState(filters.classification_status ?? 'all');
    const [vendor, setVendor]     = useState(filters.vendor ?? 'all');
    const [modalProd, setModalProd] = useState(null);
    const searchTimer = useRef(null);

    function go(overrides = {}) {
        const params = {
            search:                search,
            shopify_status:        shopifySt,
            classification_status: classSt,
            vendor:                vendor,
            ...overrides,
        };
        // strip empty / default values to keep URL clean
        Object.keys(params).forEach((k) => {
            if (params[k] === '' || params[k] === 'all') delete params[k];
        });
        router.get(route('products.index', query), params, { preserveState: true, replace: true });
    }

    function handleSearch(val) {
        setSearch(val);
        clearTimeout(searchTimer.current);
        searchTimer.current = setTimeout(() => go({ search: val }), 500);
    }

    const vendorOptions = [
        { label: 'All Vendors', value: 'all' },
        ...(vendors ?? []).map((v) => ({ label: v, value: v })),
    ];

    const classOptions = [
        { label: 'All Statuses', value: 'all' },
        { label: 'Unclassified', value: 'unclassified' },
        ...(classificationStatuses ?? []).map((s) => ({ label: s, value: s })),
    ];

    const shopifyOptions = [
        { label: 'All', value: 'all' },
        { label: 'Active', value: 'active' },
        { label: 'Draft', value: 'draft' },
        { label: 'Archived', value: 'archived' },
    ];

    const rows = products.data ?? [];
    const meta = products.meta ?? {};

    const tableRows = rows.map((product, index) => {
        const status = getClassificationStatus(product);
        return (
            <IndexTable.Row id={String(product.id)} key={product.id} position={index}>
                {/* Product */}
                <IndexTable.Cell>
                    <InlineStack gap="200" wrap={false} blockAlign="center">
                        {product.image_url
                            ? <Thumbnail source={product.image_url} alt={product.title} size="small" />
                            : <Box width="40px" minHeight="40px" background="bg-surface-secondary" borderRadius="100" />
                        }
                        <BlockStack gap="0">
                            <Text variant="bodyMd" fontWeight="semibold">{product.title}</Text>
                            {product.handle && (
                                <Text variant="bodySm" tone="subdued">{product.handle}</Text>
                            )}
                        </BlockStack>
                    </InlineStack>
                </IndexTable.Cell>
                {/* Vendor */}
                <IndexTable.Cell>
                    <Text variant="bodySm">{product.vendor ?? '—'}</Text>
                </IndexTable.Cell>
                {/* Type */}
                <IndexTable.Cell>
                    <Text variant="bodySm">{product.product_type ?? '—'}</Text>
                </IndexTable.Cell>
                {/* Inventory */}
                <IndexTable.Cell>
                    <Text variant="bodySm">{product.total_inventory}</Text>
                </IndexTable.Cell>
                {/* Price Range */}
                <IndexTable.Cell>
                    <Text variant="bodySm">{formatPriceRange(product.min_price, product.max_price)}</Text>
                </IndexTable.Cell>
                {/* Shopify Status */}
                <IndexTable.Cell>
                    <Badge tone={SHOPIFY_STATUS_TONES[product.shopify_status] ?? 'subdued'}>
                        {product.shopify_status ?? '—'}
                    </Badge>
                </IndexTable.Cell>
                {/* Classification */}
                <IndexTable.Cell>
                    <Badge tone={getStatusTone(status)}>{status}</Badge>
                </IndexTable.Cell>
                {/* Reason / Remark */}
                <IndexTable.Cell>
                    <Text variant="bodySm">{getShortRemark(product)}</Text>
                </IndexTable.Cell>
                {/* Last Classified */}
                <IndexTable.Cell>
                    <Text variant="bodySm">{formatDate(product.classification?.classified_at)}</Text>
                </IndexTable.Cell>
                {/* Actions */}
                <IndexTable.Cell>
                    <Button size="slim" onClick={() => setModalProd(product)}>View Details</Button>
                </IndexTable.Cell>
            </IndexTable.Row>
        );
    });

    return (
        <AuthenticatedLayout>
            <Page title="Products" subtitle={`${summary.total ?? 0} products synced`}>
                <BlockStack gap="500">

                    {/* Summary Cards */}
                    <InlineGrid columns={4} gap="400">
                        <SummaryCard title="Total Products"  value={summary.total ?? 0} />
                        <SummaryCard title="Classified"      value={summary.classified ?? 0}     tone="success" />
                        <SummaryCard title="Unclassified"    value={summary.unclassified ?? 0}   tone="subdued" />
                        <SummaryCard title="Needs Attention" value={summary.needs_attention ?? 0} tone="critical" />
                    </InlineGrid>

                    {/* Filters */}
                    <Card>
                        <InlineGrid columns={4} gap="300">
                            <TextField
                                label="Search"
                                value={search}
                                onChange={handleSearch}
                                placeholder="Search by title…"
                                clearButton
                                onClearButtonClick={() => handleSearch('')}
                                autoComplete="off"
                            />
                            <Select
                                label="Shopify Status"
                                options={shopifyOptions}
                                value={shopifySt}
                                onChange={(val) => { setShopifySt(val); go({ shopify_status: val }); }}
                            />
                            <Select
                                label="Classification"
                                options={classOptions}
                                value={classSt}
                                onChange={(val) => { setClassSt(val); go({ classification_status: val }); }}
                            />
                            <Select
                                label="Vendor"
                                options={vendorOptions}
                                value={vendor}
                                onChange={(val) => { setVendor(val); go({ vendor: val }); }}
                            />
                        </InlineGrid>
                    </Card>

                    {/* Table */}
                    <Card padding="0">
                        <IndexTable
                            resourceName={{ singular: 'product', plural: 'products' }}
                            itemCount={rows.length}
                            headings={TABLE_HEADINGS}
                            selectable={false}
                            emptyState={
                                <EmptyState
                                    heading="No products found"
                                    image="https://cdn.shopify.com/s/files/1/0262/4071/2726/files/emptystate-files.png"
                                >
                                    <p>Try adjusting your filters or sync products from Shopify.</p>
                                </EmptyState>
                            }
                        >
                            {tableRows}
                        </IndexTable>
                    </Card>

                    {/* Pagination */}
                    {meta.last_page > 1 && (
                        <Box paddingBlockEnd="400">
                            <InlineStack align="center">
                                <Pagination
                                    hasPrevious={meta.current_page > 1}
                                    onPrevious={() => go({ page: meta.current_page - 1 })}
                                    hasNext={meta.current_page < meta.last_page}
                                    onNext={() => go({ page: meta.current_page + 1 })}
                                    label={`Page ${meta.current_page} of ${meta.last_page}`}
                                />
                            </InlineStack>
                        </Box>
                    )}
                </BlockStack>
            </Page>

            {/* Detail Modal */}
            <ProductDetailModal product={modalProd} onClose={() => setModalProd(null)} />
        </AuthenticatedLayout>
    );
}
