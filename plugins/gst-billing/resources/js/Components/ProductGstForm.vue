<script setup>
import { Link } from '@inertiajs/vue3';
import { Tag, ExternalLink } from 'lucide-vue-next';
</script>

<template>
    <div class="mt-6 rounded-lg border border-border-subtle bg-surface-overlay p-5 shadow-xs">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-soft text-brand">
                    <Tag :size="18" />
                </span>
                <div>
                    <h3 class="text-sm font-semibold text-text-primary">Indian GST & HSN Codes</h3>
                    <p class="text-xs text-text-tertiary">
                        Set HSN codes and GST slabs (0%, 5%, 12%, 18%, 28%) for this item or manage your entire catalog in bulk.
                    </p>
                </div>
            </div>
            <Link
                href="/plg/gst-billing/hsn"
                target="_blank"
                class="inline-flex items-center gap-1 text-xs font-medium text-brand hover:underline"
            >
                Open HSN & Tax Slabs Manager
                <ExternalLink :size="13" />
            </Link>
        </div>
    </div>
</template>
