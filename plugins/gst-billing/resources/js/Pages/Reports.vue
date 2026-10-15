<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import { Head, Link } from '@inertiajs/vue3';
import { BarChart3, FileSpreadsheet } from 'lucide-vue-next';
</script>

<template>
    <Head title="GSTR Reports & Summary" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-2 text-xs">
                <Link href="/" class="text-text-tertiary hover:text-text-primary">Dashboard</Link>
                <span class="text-text-tertiary">/</span>
                <span class="text-text-tertiary">GST Invoicing</span>
                <span class="text-text-tertiary">/</span>
                <span class="font-medium text-text-primary">Reports</span>
            </div>
        </template>

        <PageHeader 
            title="GSTR-1 Filing Summaries" 
            description="Aggregated B2B, B2C, and HSN-wise tax breakdowns ready for monthly or quarterly return preparation."
        />

        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="rounded-lg border border-border-subtle bg-surface-overlay p-6 shadow-xs">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-soft text-brand">
                    <FileSpreadsheet :size="20" />
                </div>
                <h4 class="mt-4 text-sm font-semibold text-text-primary">B2B Invoices (Table 4)</h4>
                <p class="mt-1 text-xs text-text-tertiary">Registered customer invoices with valid GSTINs and state-wise split.</p>
                <span class="mt-4 inline-block text-xs font-medium text-text-secondary bg-surface-canvas px-2.5 py-1 rounded">Auto-aggregated</span>
            </div>

            <div class="rounded-lg border border-border-subtle bg-surface-overlay p-6 shadow-xs">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                    <BarChart3 :size="20" />
                </div>
                <h4 class="mt-4 text-sm font-semibold text-text-primary">B2C Retail (Table 7 & 5)</h4>
                <p class="mt-1 text-xs text-text-tertiary">Unregistered retail customer transactions summarized by rate and POS.</p>
                <span class="mt-4 inline-block text-xs font-medium text-text-secondary bg-surface-canvas px-2.5 py-1 rounded">Auto-aggregated</span>
            </div>

            <div class="rounded-lg border border-border-subtle bg-surface-overlay p-6 shadow-xs">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600">
                    <FileSpreadsheet :size="20" />
                </div>
                <h4 class="mt-4 text-sm font-semibold text-text-primary">HSN Summary (Table 12)</h4>
                <p class="mt-1 text-xs text-text-tertiary">Total quantity, taxable value, and tax rates bucketed per HSN/SAC code.</p>
                <span class="mt-4 inline-block text-xs font-medium text-text-secondary bg-surface-canvas px-2.5 py-1 rounded">Auto-aggregated</span>
            </div>
        </div>
    </AppLayout>
</template>
