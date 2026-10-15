<script setup>
import { usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Building2, Check, X } from 'lucide-vue-next';
import axios from 'axios';

const page = usePage();
const customer = page.props.customer;

const showModal = ref(false);
const loading = ref(false);
const saving = ref(false);
const savedSuccess = ref(false);

const form = ref({
    gstin: '',
    trade_name: '',
    state_code: '24',
    registration_type: 'Regular',
});

const stateCodes = ref({});

onMounted(async () => {
    if (!customer?.id) return;
    try {
        const res = await axios.get(`/p/gst-billing/customers/${customer.id}/gst`);
        if (res.data) {
            form.value.gstin = res.data.profile?.gstin || '';
            form.value.trade_name = res.data.profile?.trade_name || '';
            form.value.state_code = res.data.profile?.state_code || '24';
            form.value.registration_type = res.data.profile?.registration_type || 'Regular';
            stateCodes.value = res.data.stateCodes || {};
        }
    } catch (e) {
        // Fallback default
    }
});

const openModal = () => {
    showModal.value = true;
};

const saveProfile = async () => {
    if (!customer?.id) return;
    saving.value = true;
    try {
        await axios.post(`/p/gst-billing/customers/${customer.id}/gst`, form.value);
        savedSuccess.value = true;
        setTimeout(() => {
            savedSuccess.value = false;
            showModal.value = false;
        }, 1500);
    } catch (e) {
        alert('Failed to save GST profile: ' + (e.response?.data?.message || e.message));
    } finally {
        saving.value = false;
    }
};

const regTypes = ['Regular', 'Composition', 'Unregistered', 'SEZ'];
</script>

<template>
    <div v-if="customer" class="inline-flex items-center">
        <button
            type="button"
            @click="openModal"
            class="inline-flex items-center gap-1.5 rounded-md border border-border-subtle bg-surface-canvas px-3 py-1.5 text-xs font-medium text-text-primary hover:bg-surface-overlay transition-colors"
        >
            <Building2 :size="14" class="text-brand" />
            <span>GST Profile</span>
        </button>

        <!-- Modal Dialog -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-lg border border-border-subtle bg-surface-overlay p-6 shadow-xl">
                <div class="flex items-center justify-between border-b border-border-subtle pb-3">
                    <div class="flex items-center gap-2">
                        <Building2 :size="18" class="text-brand" />
                        <h3 class="text-sm font-semibold text-text-primary">Customer GST Profile</h3>
                    </div>
                    <button @click="showModal = false" class="text-text-tertiary hover:text-text-primary">
                        <X :size="18" />
                    </button>
                </div>

                <div v-if="savedSuccess" class="mt-3 flex items-center gap-1.5 text-xs font-medium text-emerald-600 bg-emerald-500/10 p-2.5 rounded">
                    <Check :size="15" /> GST profile saved successfully!
                </div>

                <div class="mt-4 space-y-3.5 text-xs">
                    <div>
                        <label class="block font-medium text-text-secondary mb-1">Buyer GSTIN (15 Digits)</label>
                        <input
                            v-model="form.gstin"
                            type="text"
                            maxlength="15"
                            placeholder="e.g. 24AAAAA0000A1Z5"
                            class="h-8 w-full rounded border border-border-subtle bg-surface-canvas px-2.5 text-xs text-text-primary font-mono uppercase focus:border-brand focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="block font-medium text-text-secondary mb-1">Trade / Company Name</label>
                        <input
                            v-model="form.trade_name"
                            type="text"
                            placeholder="e.g. Acme Enterprises"
                            class="h-8 w-full rounded border border-border-subtle bg-surface-canvas px-2.5 text-xs text-text-primary focus:border-brand focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="block font-medium text-text-secondary mb-1">State Code (Place of Supply) *</label>
                        <select
                            v-model="form.state_code"
                            class="h-8 w-full rounded border border-border-subtle bg-surface-canvas px-2.5 text-xs text-text-primary focus:border-brand focus:outline-none"
                        >
                            <option v-for="(name, code) in stateCodes" :key="code" :value="code">
                                [{{ code }}] {{ name }}
                            </option>
                        </select>
                        <p class="mt-1 text-[11px] text-text-tertiary">Used to determine whether order charges CGST+SGST or IGST.</p>
                    </div>

                    <div>
                        <label class="block font-medium text-text-secondary mb-1">Registration Type</label>
                        <select
                            v-model="form.registration_type"
                            class="h-8 w-full rounded border border-border-subtle bg-surface-canvas px-2.5 text-xs text-text-primary focus:border-brand focus:outline-none"
                        >
                            <option v-for="type in regTypes" :key="type" :value="type">
                                {{ type }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button
                        type="button"
                        @click="showModal = false"
                        class="rounded border border-border-subtle px-3 py-1.5 text-xs text-text-secondary hover:bg-surface-canvas"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="saveProfile"
                        :disabled="saving"
                        class="rounded bg-brand px-4 py-1.5 text-xs font-medium text-white hover:bg-brand/90 disabled:opacity-50"
                    >
                        {{ saving ? 'Saving...' : 'Save GST Profile' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
