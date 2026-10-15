<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Button from '@/Components/ui/Button.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    Building2, 
    Landmark, 
    FileText, 
    QrCode, 
    CheckCircle2, 
    AlertCircle 
} from 'lucide-vue-next';

const props = defineProps({
    settings: Object,
    stateCodes: Object,
});

const form = useForm({
    gstin: props.settings?.gstin || '',
    legal_name: props.settings?.legal_name || '',
    trade_name: props.settings?.trade_name || '',
    state_code: props.settings?.state_code || '24',
    registered_address: props.settings?.registered_address || '',
    bank_name: props.settings?.bank_name || '',
    bank_account_no: props.settings?.bank_account_no || '',
    bank_ifsc: props.settings?.bank_ifsc || '',
    bank_branch: props.settings?.bank_branch || '',
    upi_id: props.settings?.upi_id || '',
    invoice_prefix: props.settings?.invoice_prefix || 'INV',
    invoice_item_grouping: props.settings?.invoice_item_grouping || 'matrix',
    is_composition_scheme: Boolean(props.settings?.is_composition_scheme),
    terms_conditions: props.settings?.terms_conditions || '1. Goods once sold will not be taken back.\n2. Interest @ 18% p.a. will be charged if payment is not made within the due date.\n3. Subject to local jurisdiction only.',
});

const savedNotice = ref(false);

const submit = () => {
    form.post('/p/gst-billing/settings', {
        preserveScroll: true,
        onSuccess: () => {
            savedNotice.value = true;
            setTimeout(() => {
                savedNotice.value = false;
            }, 3000);
        },
    });
};

const fieldLabel = 'mb-1.5 block text-xs font-medium text-text-secondary';
const fieldInput = 'h-9 w-full rounded-md border border-border-subtle bg-surface-canvas px-3 text-sm text-text-primary placeholder:text-text-tertiary focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand';
const fieldTextarea = 'w-full rounded-md border border-border-subtle bg-surface-canvas p-3 text-sm text-text-primary placeholder:text-text-tertiary focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand';
</script>

<template>
    <Head title="GST Invoicing Settings" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-2 text-xs">
                <Link href="/" class="text-text-tertiary hover:text-text-primary">Dashboard</Link>
                <span class="text-text-tertiary">/</span>
                <span class="text-text-tertiary">GST Invoicing</span>
                <span class="text-text-tertiary">/</span>
                <span class="font-medium text-text-primary">Settings</span>
            </div>
        </template>

        <PageHeader 
            title="Indian GST Invoicing Settings" 
            description="Configure your GSTIN, registered state, bank details, and invoice preferences to generate Rule 46 compliant tax invoices."
        />

        <div v-if="savedNotice" class="mt-4 flex items-center gap-2 rounded-lg border border-emerald-500/20 bg-emerald-500/10 p-3 text-sm text-emerald-600">
            <CheckCircle2 :size="18" />
            <span>GST settings have been successfully updated!</span>
        </div>

        <form @submit.prevent="submit" class="mt-6 space-y-6 max-w-5xl pb-16">
            <!-- Business GST Identification -->
            <div class="rounded-lg border border-border-subtle bg-surface-overlay p-6 shadow-xs">
                <div class="flex items-center gap-3 border-b border-border-subtle pb-4">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-soft text-brand">
                        <Building2 :size="18" />
                    </span>
                    <div>
                        <h3 class="text-sm font-semibold text-text-primary">GST Identification & Place of Supply</h3>
                        <p class="text-xs text-text-tertiary">Details used as Seller Information on your Tax Invoices.</p>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label :class="fieldLabel">Seller GSTIN (15 Digits) *</label>
                        <input
                            v-model="form.gstin"
                            type="text"
                            maxlength="15"
                            placeholder="e.g. 24AAAAA0000A1Z5"
                            :class="fieldInput"
                            class="uppercase font-mono"
                        />
                        <p v-if="form.errors.gstin" class="mt-1 text-xs text-status-danger">{{ form.errors.gstin }}</p>
                    </div>

                    <div>
                        <label :class="fieldLabel">Registered State (Place of Supply origin) *</label>
                        <select v-model="form.state_code" :class="fieldInput">
                            <option v-for="(name, code) in stateCodes" :key="code" :value="code">
                                [{{ code }}] {{ name }}
                            </option>
                        </select>
                        <p class="mt-1 text-[11px] text-text-tertiary">Used to determine CGST+SGST (Intra-state) vs IGST (Inter-state).</p>
                    </div>

                    <div>
                        <label :class="fieldLabel">Legal Business Name *</label>
                        <input
                            v-model="form.legal_name"
                            type="text"
                            placeholder="e.g. Reliance Retail Ltd."
                            :class="fieldInput"
                        />
                    </div>

                    <div>
                        <label :class="fieldLabel">Trade Name (Brand / Operating Name)</label>
                        <input
                            v-model="form.trade_name"
                            type="text"
                            placeholder="e.g. JioMart"
                            :class="fieldInput"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label :class="fieldLabel">Full Registered Address *</label>
                        <textarea
                            v-model="form.registered_address"
                            rows="2"
                            placeholder="Enter complete office address as per GST registration..."
                            :class="fieldTextarea"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Bank & Payment Details -->
            <div class="rounded-lg border border-border-subtle bg-surface-overlay p-6 shadow-xs">
                <div class="flex items-center gap-3 border-b border-border-subtle pb-4">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-soft text-brand">
                        <Landmark :size="18" />
                    </span>
                    <div>
                        <h3 class="text-sm font-semibold text-text-primary">Bank Account & Payment QR Code</h3>
                        <p class="text-xs text-text-tertiary">These details will print directly on customer invoices for NEFT/RTGS and UPI payments.</p>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label :class="fieldLabel">Bank Name</label>
                        <input
                            v-model="form.bank_name"
                            type="text"
                            placeholder="e.g. HDFC Bank"
                            :class="fieldInput"
                        />
                    </div>

                    <div>
                        <label :class="fieldLabel">Bank Account Number</label>
                        <input
                            v-model="form.bank_account_no"
                            type="text"
                            placeholder="e.g. 50200012345678"
                            :class="fieldInput"
                        />
                    </div>

                    <div>
                        <label :class="fieldLabel">IFSC Code</label>
                        <input
                            v-model="form.bank_ifsc"
                            type="text"
                            placeholder="e.g. HDFC0001234"
                            :class="fieldInput"
                            class="uppercase font-mono"
                        />
                    </div>

                    <div>
                        <label :class="fieldLabel">Branch Name</label>
                        <input
                            v-model="form.bank_branch"
                            type="text"
                            placeholder="e.g. MG Road Branch"
                            :class="fieldInput"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label :class="fieldLabel">UPI ID (VPA for QR Code on Invoice)</label>
                        <input
                            v-model="form.upi_id"
                            type="text"
                            placeholder="e.g. yourbusiness@okaxis"
                            :class="fieldInput"
                        />
                        <p class="mt-1 text-[11px] text-text-tertiary">Customers can scan the UPI QR code on the PDF invoice to pay directly.</p>
                    </div>
                </div>
            </div>

            <!-- Invoicing Preferences -->
            <div class="rounded-lg border border-border-subtle bg-surface-overlay p-6 shadow-xs">
                <div class="flex items-center gap-3 border-b border-border-subtle pb-4">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-soft text-brand">
                        <FileText :size="18" />
                    </span>
                    <div>
                        <h3 class="text-sm font-semibold text-text-primary">Invoice Numbering & Tax Scheme</h3>
                        <p class="text-xs text-text-tertiary">Configure the fiscal series and regulatory scheme.</p>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-5">
                    <div>
                        <label :class="fieldLabel">Fiscal Invoice Prefix *</label>
                        <div class="flex items-center gap-3">
                            <input
                                v-model="form.invoice_prefix"
                                type="text"
                                maxlength="8"
                                placeholder="INV"
                                :class="fieldInput"
                                class="max-w-xs font-mono uppercase"
                            />
                            <span class="text-xs text-text-tertiary">
                                Example generated number: <strong class="text-text-primary font-mono">{{ form.invoice_prefix || 'INV' }}/26-27/0001</strong>
                            </span>
                        </div>
                    </div>

                    <!-- Print Layout / Matrix Grouping Toggle -->
                    <div>
                        <label :class="fieldLabel">Invoice Print Layout & Variant Grouping</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-1.5">
                            <label :class="[
                                'flex items-start gap-3 p-3.5 rounded-lg border cursor-pointer transition-all',
                                form.invoice_item_grouping === 'matrix' 
                                    ? 'border-brand bg-brand-soft/30 text-text-primary' 
                                    : 'border-border-subtle bg-surface-canvas text-text-secondary hover:border-border-strong'
                            ]">
                                <input
                                    v-model="form.invoice_item_grouping"
                                    type="radio"
                                    value="matrix"
                                    class="mt-0.5 text-brand focus:ring-brand"
                                />
                                <div>
                                    <div class="text-xs font-semibold text-text-primary">
                                        Group by Product & Size Matrix <span class="text-[10px] text-brand uppercase font-bold">(1-Page Compact)</span>
                                    </div>
                                    <p class="text-[11px] text-text-tertiary mt-1 leading-relaxed">
                                        Recommended for footwear & garments. Condenses multi-size orders (e.g. 15 sizes of 3 styles) into 3 clean rows with a size curve like <code>Sizes: [6: 1, 7: 1, 8: 1]</code> so bills fit on a single A4 page.
                                    </p>
                                </div>
                            </label>

                            <label :class="[
                                'flex items-start gap-3 p-3.5 rounded-lg border cursor-pointer transition-all',
                                form.invoice_item_grouping === 'itemized' 
                                    ? 'border-brand bg-brand-soft/30 text-text-primary' 
                                    : 'border-border-subtle bg-surface-canvas text-text-secondary hover:border-border-strong'
                            ]">
                                <input
                                    v-model="form.invoice_item_grouping"
                                    type="radio"
                                    value="itemized"
                                    class="mt-0.5 text-brand focus:ring-brand"
                                />
                                <div>
                                    <div class="text-xs font-semibold text-text-primary">
                                        Itemized Lines (1 Row per Size/Variant)
                                    </div>
                                    <p class="text-[11px] text-text-tertiary mt-1 leading-relaxed">
                                        Prints each individual variant and SKU as its own separate line item on the invoice.
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-lg border border-border-subtle bg-surface-canvas p-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input
                                v-model="form.is_composition_scheme"
                                type="checkbox"
                                class="mt-1 h-4 w-4 rounded border-border-subtle text-brand focus:ring-brand"
                            />
                            <div>
                                <span class="text-sm font-medium text-text-primary">Composition Scheme Taxpayer</span>
                                <p class="text-xs text-text-tertiary mt-0.5">
                                    If enabled, invoices will be issued as a <strong>"Bill of Supply"</strong> with the statutory declaration: <em>"Composition taxable person, not eligible to collect tax on supplies"</em> instead of charging GST.
                                </p>
                            </div>
                        </label>
                    </div>

                    <div>
                        <label :class="fieldLabel">Default Terms & Conditions (Printed on PDF)</label>
                        <textarea
                            v-model="form.terms_conditions"
                            rows="3"
                            :class="fieldTextarea"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Submit action -->
            <div class="flex justify-end gap-3">
                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="min-w-32"
                >
                    <span v-if="form.processing">Saving...</span>
                    <span v-else>Save GST Settings</span>
                </Button>
            </div>
        </form>
    </AppLayout>
</template>
