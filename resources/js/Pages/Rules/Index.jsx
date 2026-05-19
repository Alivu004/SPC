import { useState, useCallback } from 'react';
import AuthenticatedLayout from '@/Layouts/Embedded/AuthenticatedLayout';
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

const SAMPLE_RULES = [
    {
        id:              1,
        name:            'Slow Moving Products',
        assignedStatus:  'slow',
        matchType:       'ALL',
        priority:        10,
        conditionsCount: 2,
        isActive:        true,
    },
    {
        id:              2,
        name:            'Missing Product Content',
        assignedStatus:  'needs_attention',
        matchType:       'ANY',
        priority:        8,
        conditionsCount: 3,
        isActive:        true,
    },
    {
        id:              3,
        name:            'High Inventory Products',
        assignedStatus:  'overstocked',
        matchType:       'ALL',
        priority:        6,
        conditionsCount: 1,
        isActive:        false,
    },
    {
        id:              4,
        name:            'Recently Added Products',
        assignedStatus:  'active',
        matchType:       'ALL',
        priority:        4,
        conditionsCount: 2,
        isActive:        true,
    },
];

const STATUS_LABEL = {
    active:          'Active',
    slow:            'Slow',
    inactive:        'Inactive',
    needs_attention: 'Needs Attention',
    overstocked:     'Overstocked',
};

// ─── Main Page ────────────────────────────────────────────────────────────────

export default function Index({ rules: serverRules }) {
    const [rules, setRules] = useState(serverRules ?? SAMPLE_RULES);
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

        // When backend is connected, replace this block with:
        // router.delete(`/rules/${ruleToDelete.id}`, {
        //     onSuccess: () => { closeDeleteModal(); setDeleting(false); },
        //     onError:   () => setDeleting(false),
        // });

        // ── Static demo: remove from local state ──
        setTimeout(() => {
            setRules((prev) => prev.filter((r) => r.id !== ruleToDelete.id));
            setDeleting(false);
            closeDeleteModal();
        }, 600);
    }, [ruleToDelete, closeDeleteModal]);

    // ── Toggle active status ──────────────────────────────────────────────────

    const handleToggle = useCallback((rule) => {
        // When backend is connected, replace with:
        // router.post(`/rules/${rule.id}/toggle-status`, {}, {
        //     preserveState: true,
        // });

        // ── Static demo: flip local state ──
        setRules((prev) =>
            prev.map((r) =>
                r.id === rule.id ? { ...r, isActive: !r.isActive } : r,
            ),
        );
    }, []);

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
                <Badge tone={STATUS_TONE[rule.assignedStatus] ?? 'info'}>
                    {STATUS_LABEL[rule.assignedStatus] ?? rule.assignedStatus}
                </Badge>
            </IndexTable.Cell>

            {/* Match Type */}
            <IndexTable.Cell>
                <Badge tone={rule.matchType === 'ALL' ? 'info' : 'attention'}>
                    {rule.matchType}
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
                    {rule.conditionsCount}{' '}
                    {rule.conditionsCount === 1 ? 'condition' : 'conditions'}
                </Text>
            </IndexTable.Cell>

            {/* Active Status */}
            <IndexTable.Cell>
                <Badge tone={rule.isActive ? 'success' : 'critical'}>
                    {rule.isActive ? 'Active' : 'Inactive'}
                </Badge>
            </IndexTable.Cell>

            {/* Actions */}
            <IndexTable.Cell>
                <InlineStack gap="200" blockAlign="center">
                    <Button
                        size="slim"
                        icon={EditIcon}
                        onClick={() => router.get(route('rules.edit', { id: rule.id, ...query }))}
                        accessibilityLabel={`Edit ${rule.name}`}
                    >
                        Edit
                    </Button>
                    <Button
                        size="slim"
                        variant={rule.isActive ? 'secondary' : 'primary'}
                        tone={rule.isActive ? 'critical' : undefined}
                        onClick={() => handleToggle(rule)}
                        accessibilityLabel={
                            rule.isActive
                                ? `Disable ${rule.name}`
                                : `Enable ${rule.name}`
                        }
                    >
                        {rule.isActive ? 'Disable' : 'Enable'}
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
        <AuthenticatedLayout>
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
        </AuthenticatedLayout>
    );
}
