<script setup>
// Rendered in the Business Details list of the customer page (customers.show).
defineProps({
    profile: { type: Object, default: null },
    stateName: { type: String, default: null },
});
</script>

<template>
    <div class="border-t border-border-subtle pt-3">
        <dt class="text-xs text-text-tertiary">GSTIN</dt>
        <dd class="mt-1 font-mono text-sm text-text-primary">{{ profile?.gstin || '-' }}</dd>
    </div>
    <div>
        <dt class="text-xs text-text-tertiary">GST registration type</dt>
        <dd class="mt-1 text-sm text-text-primary">{{ profile?.registration_type || 'Unregistered' }}</dd>
    </div>
    <div>
        <dt class="text-xs text-text-tertiary">GST state (place of supply)</dt>
        <dd class="mt-1 text-sm text-text-primary">
            {{ profile?.state_code ? `[${profile.state_code}] ${stateName || ''}` : 'Same as your business state' }}
        </dd>
    </div>
    <div v-if="profile?.trade_name">
        <dt class="text-xs text-text-tertiary">GST trade name</dt>
        <dd class="mt-1 text-sm text-text-primary">{{ profile.trade_name }}</dd>
    </div>
</template>
