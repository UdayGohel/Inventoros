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

const fieldLabel = 'mb-1 block text-sm font-medium text-text-secondary';
const fieldInput = 'h-9 w-full rounded-md border border-border-subtle bg-surface-canvas px-3 text-sm text-text-primary placeholder:text-text-tertiary ds-focus-ring';
const fieldTextarea = 'w-full rounded-md border border-border-subtle bg-surface-canvas px-3 py-2 text-sm text-text-primary placeholder:text-text-tertiary ds-focus-ring';
const fieldHelp = 'mt-1 text-xs text-text-tertiary';
const fieldError = 'mt-1 text-xs text-status-danger';
// A bordered option card for a radio or checkbox, highlighted when chosen.
const optionCard = (selected) => [
    'flex cursor-pointer items-start gap-2 rounded-lg border bg-surface-canvas p-3 text-sm transition-colors',
    selected ? 'border-brand' : 'border-border-subtle hover:border-border-strong',
];
const optionInput = 'mt-0.5 shrink-0 border-border-subtle bg-surface-canvas text-brand ds-focus-ring';
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

        <div v-if="savedNotice" class="gst-saved mt-4 flex items-center gap-2 rounded-lg p-3 text-sm">
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
                        <label for="gst_gstin" :class="fieldLabel">Seller GSTIN (15 Digits) *</label>
                        <input
                            id="gst_gstin"
                            v-model="form.gstin"
                            type="text"
                            maxlength="15"
                            placeholder="e.g. 24AAAAA0000A1Z5"
                            :class="fieldInput"
                            class="gst-uppercase font-mono"
                        />
                        <p v-if="form.errors.gstin" :class="fieldError">{{ form.errors.gstin }}</p>
                    </div>

                    <div>
                        <label for="gst_state_code" :class="fieldLabel">Registered State (Place of Supply origin) *</label>
                        <select id="gst_state_code" v-model="form.state_code" :class="fieldInput">
                            <option v-for="(name, code) in stateCodes" :key="code" :value="code">
                                [{{ code }}] {{ name }}
                            </option>
                        </select>
                        <p :class="fieldHelp">Used to determine CGST+SGST (Intra-state) vs IGST (Inter-state).</p>
                    </div>

                    <div>
                        <label for="gst_legal_name" :class="fieldLabel">Legal Business Name *</label>
                        <input
                            id="gst_legal_name"
                            v-model="form.legal_name"
                            type="text"
                            placeholder="e.g. Reliance Retail Ltd."
                            :class="fieldInput"
                        />
                    </div>

                    <div>
                        <label for="gst_trade_name" :class="fieldLabel">Trade Name (Brand / Operating Name)</label>
                        <input
                            id="gst_trade_name"
                            v-model="form.trade_name"
                            type="text"
                            placeholder="e.g. JioMart"
                            :class="fieldInput"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label for="gst_registered_address" :class="fieldLabel">Full Registered Address *</label>
                        <textarea
                            id="gst_registered_address"
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
                        <label for="gst_bank_name" :class="fieldLabel">Bank Name</label>
                        <input
                            id="gst_bank_name"
                            v-model="form.bank_name"
                            type="text"
                            placeholder="e.g. HDFC Bank"
                            :class="fieldInput"
                        />
                    </div>

                    <div>
                        <label for="gst_bank_account_no" :class="fieldLabel">Bank Account Number</label>
                        <input
                            id="gst_bank_account_no"
                            v-model="form.bank_account_no"
                            type="text"
                            placeholder="e.g. 50200012345678"
                            :class="fieldInput"
                        />
                    </div>

                    <div>
                        <label for="gst_bank_ifsc" :class="fieldLabel">IFSC Code</label>
                        <input
                            id="gst_bank_ifsc"
                            v-model="form.bank_ifsc"
                            type="text"
                            placeholder="e.g. HDFC0001234"
                            :class="fieldInput"
                            class="gst-uppercase font-mono"
                        />
                    </div>

                    <div>
                        <label for="gst_bank_branch" :class="fieldLabel">Branch Name</label>
                        <input
                            id="gst_bank_branch"
                            v-model="form.bank_branch"
                            type="text"
                            placeholder="e.g. MG Road Branch"
                            :class="fieldInput"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label for="gst_upi_id" :class="fieldLabel">UPI ID (VPA for QR Code on Invoice)</label>
                        <input
                            id="gst_upi_id"
                            v-model="form.upi_id"
                            type="text"
                            placeholder="e.g. yourbusiness@okaxis"
                            :class="fieldInput"
                        />
                        <p :class="fieldHelp">Customers can scan the UPI QR code on the PDF invoice to pay directly.</p>
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

                <div class="mt-5 grid grid-cols-1 gap-4">
                    <div>
                        <label for="gst_invoice_prefix" :class="fieldLabel">Fiscal Invoice Prefix *</label>
                        <input
                            id="gst_invoice_prefix"
                            v-model="form.invoice_prefix"
                            type="text"
                            maxlength="8"
                            placeholder="INV"
                            :class="fieldInput"
                            class="max-w-xs font-mono uppercase"
                        />
                        <p :class="fieldHelp">
                            Example invoice number: <span class="font-mono font-medium text-text-primary">{{ form.invoice_prefix || 'INV' }}/26-27/0001</span>
                        </p>
                        <p v-if="form.errors.invoice_prefix" :class="fieldError">{{ form.errors.invoice_prefix }}</p>
                    </div>

                    <!-- Print Layout / Matrix Grouping Toggle -->
                    <fieldset>
                        <legend :class="fieldLabel">Invoice Print Layout & Variant Grouping</legend>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <label :class="optionCard(form.invoice_item_grouping === 'matrix')">
                                <input v-model="form.invoice_item_grouping" type="radio" value="matrix" :class="optionInput" class="rounded-full" />
                                <span>
                                    <span class="block font-medium text-text-primary">
                                        Group by Product & Size Matrix
                                        <span class="ml-1 text-xs font-medium text-brand">1-page compact</span>
                                    </span>
                                    <span class="mt-0.5 block text-xs text-text-tertiary">
                                        Recommended for footwear and garments. Condenses multi-size orders (e.g. 15 sizes of 3 styles) into 3 rows with a size curve like <code class="font-mono">Sizes: [6: 1, 7: 1, 8: 1]</code>, so bills fit on one A4 page.
                                    </span>
                                </span>
                            </label>

                            <label :class="optionCard(form.invoice_item_grouping === 'itemized')">
                                <input v-model="form.invoice_item_grouping" type="radio" value="itemized" :class="optionInput" class="rounded-full" />
                                <span>
                                    <span class="block font-medium text-text-primary">Itemized Lines (1 Row per Size/Variant)</span>
                                    <span class="mt-0.5 block text-xs text-text-tertiary">
                                        Prints each variant and SKU as its own line item on the invoice.
                                    </span>
                                </span>
                            </label>
                        </div>
                    </fieldset>

                    <label :class="optionCard(form.is_composition_scheme)">
                        <input v-model="form.is_composition_scheme" type="checkbox" :class="optionInput" class="rounded" />
                        <span>
                            <span class="block font-medium text-text-primary">Composition Scheme Taxpayer</span>
                            <span class="mt-0.5 block text-xs text-text-tertiary">
                                If enabled, invoices are issued as a <strong class="font-medium text-text-secondary">"Bill of Supply"</strong> with the statutory declaration <em>"Composition taxable person, not eligible to collect tax on supplies"</em> instead of charging GST.
                            </span>
                        </span>
                    </label>

                    <div>
                        <label for="gst_terms_conditions" :class="fieldLabel">Default Terms & Conditions (Printed on PDF)</label>
                        <textarea
                            id="gst_terms_conditions"
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

<style scoped>
/* The app's Tailwind build does not scan plugins, so classes core never uses live here. */
.gst-uppercase {
    text-transform: uppercase;
}
.gst-uppercase::placeholder {
    text-transform: none;
}
.gst-saved {
    border: 1px solid hsl(var(--status-success) / 0.3);
    background: hsl(var(--status-success) / 0.1);
    color: hsl(var(--status-success));
}
</style>
