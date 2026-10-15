<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Button from '@/Components/ui/Button.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Tags, Check, Save } from 'lucide-vue-next';
import axios from 'axios';

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

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <PageHeader 
                title="HSN / SAC Codes & GST Slabs" 
                description="Assign 4-digit or 8-digit HSN codes and applicable tax slabs to each product in your catalog."
            />
            <div class="flex items-center gap-2">
                <Button 
                    @click="saveAll" 
                    :disabled="isSavingAll || items.length === 0"
                    class="flex items-center gap-1.5"
                >
                    <Save :size="15" />
                    <span>{{ isSavingAll ? 'Saving all...' : 'Save All Changes' }}</span>
                </Button>
            </div>
        </div>

        <div v-if="saveAllNotice" class="mt-4 flex items-center gap-2 rounded-lg border border-emerald-500/20 bg-emerald-500/10 p-3 text-sm text-emerald-600">
            <Check :size="18" />
            <span>All product GST rates and HSN codes saved successfully!</span>
        </div>

        <div class="mt-6 rounded-lg border border-border-subtle bg-surface-overlay shadow-xs overflow-hidden">
            <div v-if="items.length === 0" class="py-16 text-center">
                <Tags :size="40" class="mx-auto text-text-tertiary/60" />
                <h3 class="mt-3 text-sm font-semibold text-text-primary">No products in catalog</h3>
                <p class="mt-1 text-xs text-text-tertiary">Add products first to assign HSN codes.</p>
                <div class="mt-4">
                    <Link href="/products/create" class="inline-flex rounded-md bg-brand px-3 py-1.5 text-xs font-medium text-white">
                        Create First Product
                    </Link>
                </div>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border-subtle bg-surface-canvas text-xs uppercase text-text-tertiary">
                        <tr>
                            <th class="px-5 py-3">Product Name</th>
                            <th class="px-5 py-3">SKU</th>
                            <th class="px-5 py-3">Base Price</th>
                            <th class="px-5 py-3">HSN / SAC Code</th>
                            <th class="px-5 py-3">GST Tax Slab</th>
                            <th class="px-5 py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle">
                        <tr v-for="item in items" :key="item.id" class="hover:bg-surface-canvas/50">
                            <td class="px-5 py-3.5 font-medium text-text-primary">
                                <Link :href="`/products/${item.id}`" class="hover:underline hover:text-brand">
                                    {{ item.name }}
                                </Link>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-xs text-text-secondary">
                                {{ item.sku || '—' }}
                            </td>
                            <td class="px-5 py-3.5 text-text-secondary">
                                ₹{{ Number(item.price).toFixed(2) }}
                            </td>
                            <td class="px-5 py-3.5">
                                <input
                                    v-model="item.hsn"
                                    type="text"
                                    placeholder="e.g. 8471"
                                    class="h-8 w-32 rounded border border-border-subtle bg-surface-canvas px-2.5 text-xs text-text-primary font-mono focus:border-brand focus:outline-none"
                                />
                            </td>
                            <td class="px-5 py-3.5">
                                <select
                                    v-model="item.rate"
                                    class="h-8 rounded border border-border-subtle bg-surface-canvas px-2.5 text-xs text-text-primary focus:border-brand focus:outline-none"
                                >
                                    <option v-for="r in rates" :key="r.value" :value="r.value">
                                        {{ r.label }}
                                    </option>
                                </select>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <button
                                    type="button"
                                    @click="saveSingle(item)"
                                    :disabled="item.saving"
                                    class="rounded bg-surface-canvas px-2.5 py-1 text-xs font-medium text-text-secondary border border-border-subtle hover:bg-brand hover:text-white hover:border-brand transition-colors disabled:opacity-50"
                                >
                                    <span v-if="item.saving">Saving...</span>
                                    <span v-else-if="item.saved" class="text-emerald-600 font-bold flex items-center gap-1">
                                        <Check :size="13" /> Saved
                                    </span>
                                    <span v-else>Save</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
