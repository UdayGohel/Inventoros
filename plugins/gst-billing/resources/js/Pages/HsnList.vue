<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Button from '@/Components/ui/Button.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Tags, Check, Save, Plus } from 'lucide-vue-next';
import axios from 'axios';
import { formatMoney } from '@/lib/money';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
});

const rates = [
    { label: '0% (Exempt)', value: 0 },
    { label: '5% (Essential)', value: 5 },
    { label: '12% (Standard I)', value: 12 },
    { label: '18% (Standard II)', value: 18 },
    { label: '28% (Luxury)', value: 28 },
];

const items = ref(
    props.products.map(p => ({
        id: p.id,
        name: p.name,
        sku: p.sku,
        price: p.price,
        currency: p.currency,
        hsn: p.hsn_sac_code || '',
        rate: p.gst_rate !== null ? Number(p.gst_rate) : 18,
        saved: false,
        saving: false,
    }))
);

const isSavingAll = ref(false);
const saveAllNotice = ref(false);

const saveSingle = async (item) => {
    item.saving = true;
    try {
        await axios.post(`/p/gst-billing/products/${item.id}/gst`, {
            hsn_sac_code: item.hsn,
            gst_rate: item.rate,
        });
        item.saved = true;
        setTimeout(() => {
            item.saved = false;
        }, 2000);
    } catch (e) {
        alert('Failed to save rate: ' + (e.response?.data?.message || e.message));
    } finally {
        item.saving = false;
    }
};

const thClass = 'px-4 py-2.5 text-left text-xs font-medium tracking-tight text-text-secondary';
const cellInput = 'h-9 rounded-md border border-border-subtle bg-surface-canvas px-3 text-sm text-text-primary placeholder:text-text-tertiary ds-focus-ring';

const saveAll = async () => {
    isSavingAll.value = true;
    try {
        const payload = items.value.map(i => ({
            product_id: i.id,
            hsn_sac_code: i.hsn,
            gst_rate: i.rate,
        }));
        await axios.post('/p/gst-billing/products/bulk-gst', { rates: payload });
        saveAllNotice.value = true;
        setTimeout(() => {
            saveAllNotice.value = false;
        }, 3000);
    } catch (e) {
        alert('Failed to save rates: ' + (e.response?.data?.message || e.message));
    } finally {
        isSavingAll.value = false;
    }
};
</script>

<template>
    <Head title="HSN Codes & Tax Slabs" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-2 text-xs">
                <Link href="/" class="text-text-tertiary hover:text-text-primary">Dashboard</Link>
                <span class="text-text-tertiary">/</span>
                <span class="text-text-tertiary">GST Invoicing</span>
                <span class="text-text-tertiary">/</span>
                <span class="font-medium text-text-primary">HSN & Tax Slabs</span>
            </div>
        </template>

        <PageHeader
            title="HSN / SAC Codes & GST Slabs"
            description="Assign 4-digit or 8-digit HSN codes and applicable tax slabs to each product in your catalog."
        >
            <template #actions>
                <Button size="sm" :disabled="isSavingAll || items.length === 0" @click="saveAll">
                    <Save :size="14" />
                    {{ isSavingAll ? 'Saving all...' : 'Save All Changes' }}
                </Button>
            </template>
        </PageHeader>

        <div v-if="saveAllNotice" class="gst-saved mt-4 flex items-center gap-2 rounded-lg p-3 text-sm">
            <Check :size="18" />
            <span>All product GST rates and HSN codes saved successfully!</span>
        </div>

        <div class="mt-6 w-full overflow-x-auto rounded-lg border border-border-subtle bg-surface-raised">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border-subtle">
                        <th :class="thClass">Product</th>
                        <th :class="thClass">SKU</th>
                        <th :class="[thClass, 'text-right']">Base Price</th>
                        <th :class="thClass">HSN / SAC Code</th>
                        <th :class="thClass">GST Tax Slab</th>
                        <th :class="[thClass, 'text-right']">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="items.length === 0">
                        <td colspan="6" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <Tags :size="22" class="text-text-tertiary" />
                                <p class="text-sm font-medium text-text-primary">No products in catalog</p>
                                <p class="text-sm text-text-tertiary">Add products first to assign HSN codes.</p>
                                <Button size="sm" as="Link" href="/products/create">
                                    <Plus :size="14" />
                                    Create First Product
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr
                        v-for="item in items"
                        :key="item.id"
                        class="border-b border-border-subtle transition-colors last:border-b-0 hover:bg-surface-overlay"
                    >
                        <td class="px-4 py-3">
                            <Link :href="`/products/${item.id}`" class="font-medium text-text-primary hover:text-brand hover:underline">
                                {{ item.name }}
                            </Link>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-text-secondary">{{ item.sku || '-' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-text-secondary">{{ formatMoney(item.price, item.currency) }}</td>
                        <td class="px-4 py-3">
                            <input
                                v-model="item.hsn"
                                type="text"
                                placeholder="e.g. 8471"
                                :aria-label="`HSN / SAC code for ${item.name}`"
                                :class="cellInput"
                                class="w-32 font-mono"
                            />
                        </td>
                        <td class="px-4 py-3">
                            <select v-model="item.rate" :aria-label="`GST tax slab for ${item.name}`" :class="cellInput" class="pr-9">
                                <option v-for="r in rates" :key="r.value" :value="r.value">{{ r.label }}</option>
                            </select>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end">
                                <Button variant="secondary" size="sm" :disabled="item.saving" @click="saveSingle(item)">
                                    <template v-if="item.saving">Saving...</template>
                                    <span v-else-if="item.saved" class="flex items-center gap-1 text-status-success">
                                        <Check :size="13" /> Saved
                                    </span>
                                    <template v-else>Save</template>
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>

<style scoped>
/* The app's Tailwind build does not scan plugins, so classes core never uses live here. */
.gst-saved {
    border: 1px solid hsl(var(--status-success) / 0.3);
    background: hsl(var(--status-success) / 0.1);
    color: hsl(var(--status-success));
}
</style>
