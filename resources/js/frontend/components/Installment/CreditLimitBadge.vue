<script setup>
import { computed } from 'vue'

const props = defineProps({
    installment: Object,
    size: {
        type: String,
        default: 'md'
    }
})

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'text-xs px-2 py-1',
        md: 'text-sm px-3 py-1.5',
        lg: 'text-base px-4 py-2'
    }
    return sizes[props.size] || sizes.md
})

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value)
}
</script>

<template>
    <div :class="['bg-gradient-to-r from-primary/10 to-primary/5 rounded-lg border border-primary/20 p-4', sizeClasses]">
        <div class="flex items-center gap-2 mb-2">
            <span class="text-primary">💳</span>
            <span class="font-medium text-primary">Limit Kredit</span>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(installment?.remaining_credit_limit || 0) }}</p>
        <p class="text-xs text-gray-500 mt-1">
            dari {{ formatCurrency(installment?.credit_limit || 0) }}
        </p>
        <div class="mt-2 pt-2 border-t border-primary/20">
            <p class="text-xs text-gray-600">
                Terpakai: <span class="font-medium">{{ formatCurrency(installment?.outstanding_balance || 0) }}</span>
            </p>
        </div>
    </div>
</template>