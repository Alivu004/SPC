import { useCallback, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/Embedded/AuthenticatedLayout';
import {
    Badge,
    BlockStack,
    Box,
    Card,
    Icon,
    InlineGrid,
    InlineStack,
    Modal,
    Page,
    Pagination,
    ResourceItem,
    ResourceList,
    Select,
    Text,
    TextField,
} from '@shopify/polaris';
import { CheckIcon, XSmallIcon } from '@shopify/polaris-icons';

// ─── Constants ────────────────────────────────────────────────────────────────

const STATUS_TONE = {
    active: 'success',
    slow_mover: 'warning',
    inactive: 'critical',
};

const STATUS_OPTIONS = [
    { label: 'All', value: 'all' },
    { label: 'Active', value: 'active' },
    { label: 'Slow Mover', value: 'slow_mover' },
    { label: 'Inactive', value: 'inactive' },
];

const SORT_OPTIONS = [
    { label: 'Score (High → Low)', value: 'score_desc' },
    { label: 'Score (Low → High)', value: 'score_asc' },
    { label: 'Name (A – Z)', value: 'name_asc' },
    { label: 'Name (Z – A)', value: 'name_desc' },
    { label: 'Status', value: 'status' },
];

function statusLabel(raw) {
    return raw ? raw.replace(/_/g, ' ') : 'Unknown';
}

// ─── Main Page ────────────────────────────────────────────────────────────────

export default function Products({ products, filters = {}, sort = 'score_desc' }) {
    const { data = [], meta = {} } = products ?? {};

    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');
    const [sortBy, setSortBy] = useState(sort);
    const [selectedProduct, setSelectedProduct] = useState(null);

    const searchTimer = useRef(null);

    // ── Routing helpers ──────────────────────────────────────────────────────

    const go = (overrides = {}) => {
        const params = {
            search:  overrides.search  !== undefined ? overrides.search  : search,
            status:  overrides.status  !== undefined ? overrides.status  : status,
            sort:    overrides.sort    !== undefined ? overrides.sort    : sortBy,
            page:    overrides.page    !== undefined ? overrides.page    : 1,
        };

        // Omit "all" from the query string
        if (params.status === 'all') delete params.status;
        // Omit empty search
        if (!params.search) delete params.search;

        router.get(window.location.pathname, params, {
            preserveState: true,
            replace: true,
        });
    };

    // ── Filter handlers ──────────────────────────────────────────────────────

    const handleSearchChange = useCallback((value) => {
        setSearch(value);
        if (searchTimer.current) clearTimeout(searchTimer.current);
        searchTimer.current = setTimeout(() => {
            go({ search: value, page: 1 });
        }, 400);
    }, [status, sortBy]); // eslint-disable-line react-hooks/exhaustive-deps

    const handleSearchClear = useCallback(() => {
        setSearch('');
        if (searchTimer.current) clearTimeout(searchTimer.current);
        go({ search: '', page: 1 });
    }, [status, sortBy]); // eslint-disable-line react-hooks/exhaustive-deps

    const handleStatusChange = useCallback((value) => {
        setStatus(value);
        go({ status: value, page: 1 });
    }, [search, sortBy]); // eslint-disable-line react-hooks/exhaustive-deps

    const handleSortChange = useCallback((value) => {
        setSortBy(value);
        go({ sort: value, page: 1 });
    }, [search, status]); // eslint-disable-line react-hooks/exhaustive-deps

    // ── Pagination ───────────────────────────────────────────────────────────

    const currentPage = meta.current_page ?? 1;
    const lastPage    = meta.last_page    ?? 1;

    const handlePrevious = () => go({ page: currentPage - 1 });
    const handleNext     = () => go({ page: currentPage + 1 });

    // ── Detail panel ─────────────────────────────────────────────────────────

    const openDetail  = useCallback((product) => setSelectedProduct(product), []);
    const closeDetail = useCallback(() => setSelectedProduct(null), []);

    // ── Render ───────────────────────────────────────────────────────────────

    return (
        <AuthenticatedLayout>
            <Page title="Products">
                <BlockStack gap="400">

                    {/* Filter Bar */}
                    <Card>
                        <InlineStack gap="300" wrap={false} blockAlign="end">
                            <Box minWidth="220px" flexGrow="2">
                                <TextField
                                    label="Search"
                                    labelHidden
                                    placeholder="Search products…"
                                    value={search}
                                    onChange={handleSearchChange}
                                    onClearButtonClick={handleSearchClear}
                                    clearButton
                                    autoComplete="off"
                                />
                            </Box>
                            <Box minWidth="160px">
                                <Select
                                    label="Status"
                                    options={STATUS_OPTIONS}
                                    value={status}
                                    onChange={handleStatusChange}
                                />
                            </Box>
                            <Box minWidth="190px">
                                <Select
                                    label="Sort by"
                                    options={SORT_OPTIONS}
                                    value={sortBy}
                                    onChange={handleSortChange}
                                />
                            </Box>
                        </InlineStack>
                    </Card>

                    {/* Product List */}
                    <Card padding="0">
                        <ResourceList
                            resourceName={{ singular: 'product', plural: 'products' }}
                            items={data}
                            emptyState={
                                <Box paddingBlock="1200" paddingInline="600">
                                    <BlockStack gap="200" inlineAlign="center">
                                        <Text variant="bodyMd" tone="subdued" as="p" alignment="center">
                                            No products found matching your filters.
                                        </Text>
                                    </BlockStack>
                                </Box>
                            }
                            renderItem={(product) => {
                                const { id, title, status: pStatus, score } = product;
                                return (
                                    <ResourceItem
                                        id={String(id)}
                                        name={title}
                                        accessibilityLabel={`View details for ${title}`}
                                        onClick={() => openDetail(product)}
                                    >
                                        <InlineStack align="space-between" blockAlign="center">
                                            <Text variant="bodyMd" fontWeight="semibold" as="span">
                                                {title}
                                            </Text>
                                            <InlineStack gap="400" blockAlign="center">
                                                <Text variant="bodySm" tone="subdued" as="span">
                                                    Score: <Text as="span" fontWeight="semibold">{score ?? '—'}</Text>
                                                </Text>
                                                <Badge tone={STATUS_TONE[pStatus] ?? 'attention'}>
                                                    {statusLabel(pStatus)}
                                                </Badge>
                                            </InlineStack>
                                        </InlineStack>
                                    </ResourceItem>
                                );
                            }}
                        />
                    </Card>

                    {/* Pagination */}
                    {lastPage > 1 && (
                        <InlineStack align="center">
                            <Pagination
                                hasPrevious={currentPage > 1}
                                onPrevious={handlePrevious}
                                hasNext={currentPage < lastPage}
                                onNext={handleNext}
                                label={`Page ${currentPage} of ${lastPage}`}
                            />
                        </InlineStack>
                    )}

                </BlockStack>
            </Page>

            {/* Detail Panel */}
            {selectedProduct && (
                <ProductDetailModal product={selectedProduct} onClose={closeDetail} />
            )}
        </AuthenticatedLayout>
    );
}

// ─── Detail Modal ─────────────────────────────────────────────────────────────

function ProductDetailModal({ product, onClose }) {
    const {
        title,
        status,
        score,
        signals       = {},
        rules         = [],
        statusHistory = [],
    } = product;

    return (
        <Modal
            open
            onClose={onClose}
            title={title}
            secondaryActions={[{ content: 'Close', onAction: onClose }]}
            size="medium"
        >
            {/* ── Status & Score ── */}
            <Modal.Section>
                <InlineStack gap="400" blockAlign="center">
                    <Badge tone={STATUS_TONE[status] ?? 'attention'}>
                        {statusLabel(status)}
                    </Badge>
                    <Text variant="bodySm" tone="subdued" as="span">
                        Classification score:{' '}
                        <Text as="span" fontWeight="semibold">
                            {score ?? '—'}
                        </Text>
                    </Text>
                </InlineStack>
            </Modal.Section>

            {/* ── Signal Values ── */}
            <Modal.Section>
                <BlockStack gap="300">
                    <Text variant="headingSm" as="h3">Signals</Text>
                    <InlineGrid columns={{ xs: 1, sm: 3 }} gap="400">
                        <SignalCard label="Sales Rate"      value={signals.salesRate} />
                        <SignalCard label="Stock Days Left" value={signals.stockDays} />
                        <SignalCard label="Views"           value={signals.views}     />
                    </InlineGrid>
                </BlockStack>
            </Modal.Section>

            {/* ── Rules Checklist ── */}
            {rules.length > 0 && (
                <Modal.Section>
                    <BlockStack gap="300">
                        <Text variant="headingSm" as="h3">Rule Evaluation</Text>
                        <BlockStack gap="150">
                            {rules.map((rule, i) => (
                                <InlineStack key={i} gap="200" blockAlign="center">
                                    <Box width="20px">
                                        <Icon
                                            source={rule.matched ? CheckIcon : XSmallIcon}
                                            tone={rule.matched ? 'success' : 'critical'}
                                        />
                                    </Box>
                                    <Text
                                        variant="bodySm"
                                        as="span"
                                        tone={rule.matched ? undefined : 'subdued'}
                                    >
                                        {rule.name}
                                    </Text>
                                </InlineStack>
                            ))}
                        </BlockStack>
                    </BlockStack>
                </Modal.Section>
            )}

            {/* ── Status History ── */}
            {statusHistory.length > 0 && (
                <Modal.Section>
                    <BlockStack gap="300">
                        <Text variant="headingSm" as="h3">Status History</Text>
                        <BlockStack gap="200">
                            {statusHistory.map((entry, i) => (
                                <InlineStack key={i} gap="300" blockAlign="center">
                                    <Badge tone={STATUS_TONE[entry.status] ?? 'attention'}>
                                        {statusLabel(entry.status)}
                                    </Badge>
                                    <Text variant="bodySm" tone="subdued" as="span">
                                        {entry.changedAt
                                            ? new Date(entry.changedAt).toLocaleDateString(undefined, {
                                                year: 'numeric',
                                                month: 'short',
                                                day: 'numeric',
                                            })
                                            : ''}
                                    </Text>
                                    {i === 0 && (
                                        <Text variant="bodySm" tone="success" fontWeight="semibold" as="span">
                                            Current
                                        </Text>
                                    )}
                                </InlineStack>
                            ))}
                        </BlockStack>
                    </BlockStack>
                </Modal.Section>
            )}
        </Modal>
    );
}

// ─── Signal Value Card ────────────────────────────────────────────────────────

function SignalCard({ label, value }) {
    return (
        <Box
            background="bg-surface-secondary"
            borderRadius="200"
            padding="300"
        >
            <BlockStack gap="100">
                <Text variant="bodySm" tone="subdued" as="p">
                    {label}
                </Text>
                <Text variant="headingMd" as="p">
                    {value ?? '—'}
                </Text>
            </BlockStack>
        </Box>
    );
}
