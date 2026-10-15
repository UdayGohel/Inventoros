<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import { Head, Link } from '@inertiajs/vue3';
import { FileText, Download } from 'lucide-vue-next';

defineProps({
    invoices: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="GST Tax Invoices" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-2 text-xs">
                <Link href="/" class="text-text-tertiary hover:text-text-primary">Dashboard</Link>
                <span class="text-text-tertiary">/</span>
                <span class="text-text-tertiary">GST Invoicing</span>
                <span class="text-text-tertiary">/</span>
                <span class="font-medium text-text-primary">Invoices</span>
            </div>
        </template>

        <PageHeader 
            title="GST Tax Invoices" 
            description="View, print, and audit all Rule 46 Tax Invoices generated for orders."
        />

        <div class="mt-6 rounded-lg border border-border-subtle bg-surface-overlay shadow-xs">
            <div v-if="invoices.length === 0" class="py-16 text-center">
                <FileText :size="40" class="mx-auto text-text-tertiary/60" />
                <h3 class="mt-3 text-sm font-semibold text-text-primary">No GST Invoices issued yet</h3>
                <p class="mt-1 text-xs text-text-tertiary max-w-sm mx-auto">
                    When you view any completed order, click <strong>"Download GST Invoice"</strong> to generate your first fiscal invoice.
                </p>
                <div class="mt-4">
                    <Link href="/orders" class="inline-flex items-center gap-1.5 text-xs font-medium text-brand hover:underline">
                        Go to Orders &rarr;
                    </Link>
                </div>
            </div>

            <table v-else class="w-full text-left text-sm">
                <thead class="border-b border-border-subtle bg-surface-canvas text-xs uppercase text-text-tertiary">
                    <tr>
                        <th class="px-4 py-3">Invoice #</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Place of Supply</th>
                        <th class="px-4 py-3 text-right">Taxable Amt</th>
                        <th class="px-4 py-3 text-right">GST Total</th>
                        <th class="px-4 py-3 text-right">Grand Total</th>
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle">
                    <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-surface-canvas/50">
                        <td class="px-4 py-3 font-mono font-medium text-text-primary">{{ inv.invoice_number }}</td>
                        <td class="px-4 py-3 text-text-secondary">{{ inv.invoice_date }}</td>
                        <td class="px-4 py-3 text-text-secondary">State Code [{{ inv.place_of_supply }}]</td>
                        <td class="px-4 py-3 text-right font-mono">₹{{ Number(inv.taxable_value).toFixed(2) }}</td>
                        <td class="px-4 py-3 text-right font-mono text-brand">₹{{ (Number(inv.cgst_amount) + Number(inv.sgst_amount) + Number(inv.igst_amount)).toFixed(2) }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-text-primary">₹{{ Number(inv.total_amount).toFixed(2) }}</td>
                        <td class="px-4 py-3 text-center">
                            <a :href="`/p/gst-billing/orders/${inv.order_id}/invoice`" target="_blank" class="inline-flex items-center gap-1 text-xs text-brand hover:underline font-medium">
                                <Download :size="13" /> PDF
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
