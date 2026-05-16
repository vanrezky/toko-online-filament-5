<script setup>
import { ref, computed, watch } from 'vue'
import { installmentService } from '@/frontend/services/installmentService'

const props = defineProps({
    amount: {
        type: Number,
        required: true
    },
    selectedPlanId: {
        type: Number,
        default: null
    }
})

const emit = defineEmits(['update:selectedPlanId', 'update:simulationResult'])

const plans = ref([])
const loading = ref(false)
const selectedPlan = ref(null)
const simulationResult = ref(null)

const fetchPlans = async () => {
    loading.value = true
    try {
        const response = await installmentService.getPlans()
        plans.value = response.data.data
    } finally {
        loading.value = false
    }
}

const selectPlan = async (planId) => {
    emit('update:selectedPlanId', planId)
    selectedPlan.value = plans.value.find(p => p.id === planId)

    if (selectedPlan.value && props.amount) {
        loading.value = true
        try {
            const result = await installmentService.simulate(props.amount, planId)
            simulationResult.value = result.data.data
            emit('update:simulationResult', simulationResult.value)
        } finally {
            loading.value = false
        }
    }
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value)
}

watch(() => props.amount, async (newAmount) => {
    if (selectedPlan.value && newAmount) {
        await selectPlan(selectedPlan.value.id)
    }
})

fetchPlans()
</script>

<template>
    <div class="bg-white rounded-lg border p-4">
        <h3 class="font-semibold text-gray-900 mb-3">Simulasi Cicilan</h3>

        <div v-if="loading && plans.length === 0" class="text-center py-4">
            <div class="h-6 w-6 mx-auto border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
        </div>

        <div v-else class="space-y-3">
            <div class="text-sm text-gray-600 mb-2">
                Harga Produk: <span class="font-semibold text-gray-900">{{ formatCurrency(amount) }}</span>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <button v-for="plan in plans" :key="plan.id"
                    @click="selectPlan(plan.id)"
                    :class="[
                        'p-3 rounded-lg border text-left transition',
                        selectedPlan?.id === plan.id
                            ? 'border-primary bg-primary/5'
                            : 'border-gray-200 hover:border-gray-300'
                    ]">
                    <p class="font-medium">{{ plan.tenor }} bulan</p>
                    <p class="text-xs text-gray-500">{{ plan.fee_percentage }}% fee</p>
                </button>
            </div>

            <div v-if="simulationResult" class="mt-4 p-3 bg-gray-50 rounded-lg text-sm">
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Fee Cicilan ({{ simulationResult.fee_percentage }}%):</span>
                        <span class="font-medium">{{ formatCurrency(simulationResult.fee_amount) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Cicilan:</span>
                        <span class="font-bold">{{ formatCurrency(simulationResult.total_amount) }}</span>
                    </div>
                    <div class="flex justify-between border-t pt-2">
                        <span class="text-gray-600">Angsuran/bulan:</span>
                        <span class="font-bold text-primary">{{ formatCurrency(simulationResult.monthly_amount) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>