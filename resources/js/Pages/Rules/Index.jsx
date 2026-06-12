import { useState, useCallback } from 'react';
// import AuthenticatedLayout from '@/Layouts/Embedded/AuthenticatedLayout';
import {
    Badge,
    BlockStack,
    Button,
    Card,
    EmptyState,
    IndexTable,
    InlineStack,
    Modal,
    Page,
    Text,
} from '@shopify/polaris';
import { EditIcon, DeleteIcon } from '@shopify/polaris-icons';
import { STATUS_TONE } from '@/Components/Rules/RuleForm';
import { Head, router, usePage } from '@inertiajs/react';

// ─── Static Sample Data ───────────────────────────────────────────────────────

// ─── Main Page ────────────────────────────────────────────────────────────────

export default function Index({ rules = [] }) {
    const query = usePage().props?.ziggy?.query ?? {};

    // ── Delete modal state ────────────────────────────────────────────────────
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [ruleToDelete, setRuleToDelete]       = useState(null);
    const [deleting, setDeleting]               = useState(false);

    const openDeleteModal = useCallback((rule) => {
        setRuleToDelete(rule);
        setDeleteModalOpen(true);
    }, []);

    const closeDeleteModal = useCallback(() => {
        setDeleteModalOpen(false);
        setRuleToDelete(null);
    }, []);

    const handleConfirmDelete = useCallback(() => {
        if (!ruleToDelete) return;
        setDeleting(true);
        router.delete(route('rules.destroy', { rule: ruleToDelete.id, ...query }), {
            onSuccess: () => { setDeleting(false); closeDeleteModal(); },
            onError:   () => setDeleting(false),
        });
    }, [ruleToDelete, closeDeleteModal, query]);

    // ── Toggle active status ──────────────────────────────────────────────────

    const handleToggle = useCallback((rule) => {
        router.post(route('rules.toggle-status', { rule: rule.id, ...query }), {}, {
            preserveScroll: true,
        });
    }, [query]);

    // ── Table headings ────────────────────────────────────────────────────────

    const headings = [
        { title: 'Rule Name' },
        { title: 'Assigned Status' },
        { title: 'Match Type' },
        { title: 'Priority' },
        { title: 'Conditions' },
        { title: 'Status' },
        { title: 'Actions' },
    ];

    // ── Empty state ───────────────────────────────────────────────────────────

    const emptyStateMarkup = (
        <EmptyState
            heading="No rules created yet"
            action={{
                content:  'Create Rule',
                onAction: () => router.get(route('rules.create', query)),
            }}
        >
            <p>
                Rules let you automatically classify products based on conditions you define.
                Create your first rule to get started.
            </p>
        </EmptyState>
    );

    // ── Rows ──────────────────────────────────────────────────────────────────

    const rowMarkup = rules.map((rule, index) => (
        <IndexTable.Row id={String(rule.id)} key={rule.id} position={index}>

            {/* Rule Name */}
            <IndexTable.Cell>
                <Text variant="bodyMd" fontWeight="semibold" as="span">
                    {rule.name}
                </Text>
            </IndexTable.Cell>

            {/* Assigned Status */}
            <IndexTable.Cell>
                <Badge tone={STATUS_TONE[rule.product_status?.slug] ?? 'info'}>
                    {rule.product_status?.name ?? '—'}
                </Badge>
            </IndexTable.Cell>

            {/* Match Type */}
            <IndexTable.Cell>
                <Badge tone={rule.match_type === 'all' ? 'info' : 'attention'}>
                    {rule.match_type?.toUpperCase()}
                </Badge>
            </IndexTable.Cell>

            {/* Priority */}
            <IndexTable.Cell>
                <Text variant="bodyMd" as="span">
                    {rule.priority}
                </Text>
            </IndexTable.Cell>

            {/* Conditions Count */}
            <IndexTable.Cell>
                <Text variant="bodyMd" as="span">
                    {rule.conditions_count}{' '}
                    {rule.conditions_count === 1 ? 'condition' : 'conditions'}
                </Text>
            </IndexTable.Cell>

            {/* Active Status */}
            <IndexTable.Cell>
                <Badge tone={rule.is_active ? 'success' : 'critical'}>
                    {rule.is_active ? 'Active' : 'Inactive'}
                </Badge>
            </IndexTable.Cell>

            {/* Actions */}
            <IndexTable.Cell>
                <InlineStack gap="200" blockAlign="center">
                    <Button
                        size="slim"
                        icon={EditIcon}
                        onClick={() => router.get(route('rules.edit', { rule: rule.id, ...query }))}
                        accessibilityLabel={`Edit ${rule.name}`}
                    >
                        Edit
                    </Button>
                    <Button
                        size="slim"
                        variant={rule.is_active ? 'secondary' : 'primary'}
                        tone={rule.is_active ? 'critical' : undefined}
                        onClick={() => handleToggle(rule)}
                        accessibilityLabel={
                            rule.is_active
                                ? `Disable ${rule.name}`
                                : `Enable ${rule.name}`
                        }
                    >
                        {rule.is_active ? 'Disable' : 'Enable'}
                    </Button>
                    <Button
                        size="slim"
                        icon={DeleteIcon}
                        variant="plain"
                        tone="critical"
                        onClick={() => openDeleteModal(rule)}
                        accessibilityLabel={`Delete ${rule.name}`}
                    >
                        Delete
                    </Button>
                </InlineStack>
            </IndexTable.Cell>

        </IndexTable.Row>
    ));

    // ── Render ────────────────────────────────────────────────────────────────

    return (
        <>
            <Page
                title="Rules"
                subtitle="Define conditions to automatically classify products and assign statuses."
                primaryAction={
                    <Button
                        variant="primary"
                        onClick={() => router.get(route('rules.create', query))}

                    >
                        Create Rule
                    </Button>
                }
            >
                <BlockStack gap="500">
                    <Card padding="0">
                        <IndexTable
                            resourceName={{ singular: 'rule', plural: 'rules' }}
                            itemCount={rules.length}
                            headings={headings}
                            selectable={false}
                            emptyState={emptyStateMarkup}
                        >
                            {rowMarkup}
                        </IndexTable>
                    </Card>
                </BlockStack>
            </Page>

            {/* ── Delete Confirmation Modal ── */}
            <Modal
                open={deleteModalOpen}
                onClose={closeDeleteModal}
                title="Delete rule"
                primaryAction={{
                    content:     'Delete',
                    destructive: true,
                    loading:     deleting,
                    onAction:    handleConfirmDelete,
                }}
                secondaryActions={[
                    {
                        content:  'Cancel',
                        onAction: closeDeleteModal,
                    },
                ]}
            >
                <Modal.Section>
                    <BlockStack gap="200">
                        <Text variant="bodyMd" as="p">
                            Are you sure you want to delete{' '}
                            <strong>"{ruleToDelete?.name}"</strong>?
                        </Text>
                        <Text variant="bodyMd" tone="critical" as="p">
                            This action cannot be undone.
                        </Text>
                    </BlockStack>
                </Modal.Section>
            </Modal>
        </>
    );
}
