<script setup>
import { usePage } from '@inertiajs/vue3';
import { FileText, Download } from 'lucide-vue-next';

const page = usePage();
const order = page.props.order;
</script>

<template>
    <div v-if="order" class="inline-flex items-center">
        <a
            :href="`/p/gst-billing/orders/${order.id}/invoice`"
            target="_blank"
            class="inline-flex items-center gap-1.5 rounded-md bg-brand px-3 py-1.5 text-xs font-medium text-white shadow-xs hover:bg-brand/90 transition-colors"
        >
            <Download :size="14" />
            <span>Download GST Invoice</span>
        </a>
    </div>
</template>
