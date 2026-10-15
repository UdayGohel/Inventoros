<script setup>
import { usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Landmark, FileText, CheckCircle2 } from 'lucide-vue-next';
import axios from 'axios';

const page = usePage();
const order = page.props.order;

const loading = ref(true);
const breakdown = ref(null);

onMounted(async () => {
    if (!order?.id) return;
    try {
        const res = await axios.get(`/p/gst-billing/orders/${order.id}/breakdown`);
        breakdown.value = res.data;
    } catch (e) {
        console.error('Failed to load GST breakdown', e);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div v-if="order" class="rounded-lg border border-border-subtle bg-surface-overlay p-4 shadow-xs">
        <div class="flex items-center justify-between border-b border-border-subtle pb-3">
            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-brand-soft text-brand">
                    <Landmark :size="15" />
                </span>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-text-primary">Indian GST Tax Breakdown</h3>
            </div>
            <span v-if="breakdown" class="text-[10px] font-mono font-medium text-text-tertiary">
                {{ breakdown.invoice_number }}
            </span>
        </div>

        <div v-if="loading" class="py-4 text-center text-xs text-text-tertiary">
            Calculating GST breakdown...
        </div>

        <div v-else-if="breakdown" class="mt-3 space-y-2 text-xs">
            <div class="flex justify-between items-center py-1">
                <span class="text-text-tertiary">Place of Supply (POS):</span>
                <span class="font-medium text-text-primary">
                    [{{ breakdown.place_of_supply }}] {{ breakdown.pos_state_name }}
                </span>
            </div>

            <div class="flex justify-between items-center py-1">
                <span class="text-text-tertiary">Tax Type:</span>
                <span 
                    :class="breakdown.tax_type === 'INTRA_STATE' ? 'bg-blue-500/10 text-blue-600' : 'bg-purple-500/10 text-purple-600'" 
                    class="px-2 py-0.5 rounded text-[11px] font-semibold"
                >
                    {{ breakdown.tax_type === 'INTRA_STATE' ? 'Intra-state (CGST + SGST)' : 'Inter-state (IGST)' }}
                </span>
            </div>

            <div class="flex justify-between items-center py-1 border-t border-border-subtle/50">
                <span class="text-text-tertiary">Taxable Subtotal:</span>
                <span class="font-mono text-text-primary">₹{{ Number(breakdown.taxable_value).toFixed(2) }}</span>
            </div>

            <template v-if="breakdown.tax_type === 'INTRA_STATE'">
                <div class="flex justify-between items-center py-0.5">
                    <span class="text-text-tertiary">CGST:</span>
                    <span class="font-mono text-brand">₹{{ Number(breakdown.cgst_amount).toFixed(2) }}</span>
                </div>
                <div class="flex justify-between items-center py-0.5">
                    <span class="text-text-tertiary">SGST:</span>
                    <span class="font-mono text-brand">₹{{ Number(breakdown.sgst_amount).toFixed(2) }}</span>
                </div>
            </template>
            <template v-else>
                <div class="flex justify-between items-center py-0.5">
                    <span class="text-text-tertiary">IGST:</span>
                    <span class="font-mono text-brand">₹{{ Number(breakdown.igst_amount).toFixed(2) }}</span>
                </div>
            </template>

            <div class="flex justify-between items-center py-1 border-t border-border-subtle font-semibold">
                <span class="text-text-primary">Total Tax:</span>
                <span class="font-mono text-brand">₹{{ Number(breakdown.total_tax).toFixed(2) }}</span>
            </div>

            <div class="pt-2">
                <a
                    :href="`/p/gst-billing/orders/${order.id}/invoice`"
                    target="_blank"
                    class="block text-center rounded border border-border-subtle py-1.5 text-xs font-medium text-brand hover:bg-brand-soft transition-colors"
                >
                    View Official Rule 46 PDF &rarr;
                </a>
            </div>
        </div>
    </div>
</template>
