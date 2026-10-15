<script setup>
import { usePage, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import { Tag, Check, AlertCircle } from 'lucide-vue-next';
import axios from 'axios';

const page = usePage();
const product = page.props.product;

const hsn = ref('');
const rate = ref('18');
const isEditing = ref(false);
const isSaving = ref(false);
const savedSuccess = ref(false);

const rates = [
    { label: 'Exempt / 0%', value: '0' },
    { label: '5% (Essential)', value: '5' },
    { label: '12% (Standard I)', value: '12' },
    { label: '18% (Standard II)', value: '18' },
    { label: '28% (Luxury / De-merit)', value: '28' },
];

onMounted(async () => {
    if (!product?.id) return;
    try {
        const res = await axios.get(`/p/gst-billing/products/${product.id}/gst`);
        if (res.data) {
            hsn.value = res.data.hsn_sac_code || '';
            rate.value = res.data.gst_rate ? String(Number(res.data.gst_rate)) : '18';
        }
    } catch (e) {
        // Fallback default
    }
});

const saveGst = async () => {
    if (!product?.id) return;
    isSaving.value = true;
    try {
        await axios.post(`/p/gst-billing/products/${product.id}/gst`, {
            hsn_sac_code: hsn.value,
            gst_rate: Number(rate.value),
        });
        savedSuccess.value = true;
        isEditing.value = false;
        setTimeout(() => {
            savedSuccess.value = false;
        }, 2500);
    } catch (err) {
        alert('Failed to save GST: ' + (err.response?.data?.message || err.message));
    } finally {
        isSaving.value = false;
    }
};
</script>

<template>
    <div v-if="product" class="rounded-lg border border-border-subtle bg-surface-overlay p-4 shadow-xs">
        <div class="flex items-center justify-between border-b border-border-subtle pb-3">
            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-brand-soft text-brand">
                    <Tag :size="15" />
                </span>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-text-primary">Indian GST Details</h3>
            </div>
            <button
                v-if="!isEditing"
                type="button"
                @click="isEditing = true"
                class="text-xs font-medium text-brand hover:underline"
            >
                Edit
            </button>
        </div>

        <div v-if="savedSuccess" class="mt-2 text-xs text-emerald-600 flex items-center gap-1 font-medium">
            <Check :size="14" /> Saved successfully
        </div>

        <!-- Read-only View -->
        <div v-if="!isEditing" class="mt-3 space-y-2 text-xs">
            <div class="flex justify-between items-center py-1">
                <span class="text-text-tertiary">HSN/SAC Code:</span>
                <span class="font-mono font-medium text-text-primary">{{ hsn || 'Not set' }}</span>
            </div>
            <div class="flex justify-between items-center py-1 border-t border-border-subtle/40">
                <span class="text-text-tertiary">GST Rate:</span>
                <span class="font-semibold text-brand px-2 py-0.5 rounded bg-brand/10">{{ rate }}%</span>
            </div>
        </div>

        <!-- Edit Form -->
        <div v-else class="mt-3 space-y-3">
            <div>
                <label class="block text-[11px] font-medium text-text-secondary mb-1">HSN / SAC Code</label>
                <input
                    v-model="hsn"
                    type="text"
                    placeholder="e.g. 8471"
                    class="h-8 w-full rounded border border-border-subtle bg-surface-canvas px-2 text-xs text-text-primary font-mono focus:border-brand focus:outline-none"
                />
            </div>

            <div>
                <label class="block text-[11px] font-medium text-text-secondary mb-1">GST Tax Slab</label>
                <select
                    v-model="rate"
                    class="h-8 w-full rounded border border-border-subtle bg-surface-canvas px-2 text-xs text-text-primary focus:border-brand focus:outline-none"
                >
                    <option v-for="r in rates" :key="r.value" :value="r.value">
                        {{ r.label }}
                    </option>
                </select>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <button
                    type="button"
                    :disabled="isSaving"
                    @click="saveGst"
                    class="flex-1 rounded bg-brand py-1.5 text-center text-xs font-medium text-white hover:bg-brand/90 disabled:opacity-50"
                >
                    {{ isSaving ? 'Saving...' : 'Save GST' }}
                </button>
                <button
                    type="button"
                    @click="isEditing = false"
                    class="rounded border border-border-subtle py-1.5 px-3 text-xs text-text-secondary hover:bg-surface-canvas"
                >
                    Cancel
                </button>
            </div>
        </div>
    </div>
</template>
