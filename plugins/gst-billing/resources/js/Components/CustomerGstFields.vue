<script setup>
import { computed, watch } from 'vue';

// Rendered in the customer form's Business Details grid (customers.create /
// customers.edit). The fields live under form.plugin_data['gst-billing'] and
// are submitted, validated and saved together with the customer.
const props = defineProps({
    form: { type: Object, default: null },
    profile: { type: Object, default: null },
    stateCodes: { type: Object, default: () => ({}) },
});

const SLUG = 'gst-billing';

if (props.form) {
    props.form.plugin_data[SLUG] = {
        registration_type: props.profile?.registration_type || 'Unregistered',
        gstin: props.profile?.gstin || '',
        state_code: props.profile?.state_code || '',
        trade_name: props.profile?.trade_name || '',
    };
}

const gst = computed(() => props.form?.plugin_data[SLUG]);
const error = (field) => props.form?.errors[`plugin_data.${SLUG}.${field}`];
const registered = computed(() => gst.value && gst.value.registration_type !== 'Unregistered');

// GSTINs are upper case, and their first two digits are the state code.
watch(() => gst.value?.gstin, (value) => {
    if (!value) return;
    const upper = value.toUpperCase();
    if (upper !== value) gst.value.gstin = upper;
    const code = upper.slice(0, 2);
    if (!gst.value.state_code && props.stateCodes[code]) gst.value.state_code = code;
});

const registrationTypes = ['Unregistered', 'Regular', 'Composition', 'SEZ'];

const fieldLabel = 'mb-1 block text-sm font-medium text-text-secondary';
const fieldInput = 'h-9 w-full rounded-md border border-border-subtle bg-surface-canvas px-3 text-sm text-text-primary placeholder:text-text-tertiary ds-focus-ring';
const fieldHelp = 'mt-1 text-xs text-text-tertiary';
const fieldError = 'mt-1 text-xs text-status-danger';
</script>

<template>
    <template v-if="gst">
        <div class="border-t border-border-subtle pt-4 md:col-span-2">
            <h4 class="text-sm font-semibold text-text-primary">GST (India)</h4>
            <p :class="fieldHelp">Printed on tax invoices. The customer's state decides CGST + SGST (same state as you) or IGST.</p>
        </div>

        <div>
            <label for="gst_registration_type" :class="fieldLabel">GST registration type</label>
            <select id="gst_registration_type" v-model="gst.registration_type" :class="fieldInput">
                <option v-for="type in registrationTypes" :key="type" :value="type">
                    {{ type === 'Unregistered' ? 'Unregistered (B2C, no GSTIN)' : type }}
                </option>
            </select>
            <p v-if="error('registration_type')" :class="fieldError">{{ error('registration_type') }}</p>
        </div>

        <div>
            <label for="gst_state_code" :class="fieldLabel">GST state (place of supply)</label>
            <select id="gst_state_code" v-model="gst.state_code" :class="fieldInput">
                <option value="">Same as your business state</option>
                <option v-for="(name, code) in stateCodes" :key="code" :value="code">[{{ code }}] {{ name }}</option>
            </select>
            <p v-if="error('state_code')" :class="fieldError">{{ error('state_code') }}</p>
        </div>

        <div>
            <label for="gst_gstin" :class="fieldLabel">
                GSTIN <span v-if="registered" class="text-status-danger">*</span>
            </label>
            <input
                id="gst_gstin"
                v-model.trim="gst.gstin"
                type="text"
                maxlength="15"
                placeholder="e.g. 24AAAAA0000A1Z5"
                autocomplete="off"
                :class="fieldInput"
                class="font-mono"
            />
            <p v-if="error('gstin')" :class="fieldError">{{ error('gstin') }}</p>
            <p v-else :class="fieldHelp">15 characters. Required for Regular, Composition and SEZ.</p>
        </div>

        <div>
            <label for="gst_trade_name" :class="fieldLabel">GST trade name</label>
            <input id="gst_trade_name" v-model="gst.trade_name" type="text" placeholder="As on the GST certificate" :class="fieldInput" />
            <p v-if="error('trade_name')" :class="fieldError">{{ error('trade_name') }}</p>
        </div>
    </template>
</template>
