import { ref } from 'vue'
import { api } from '../services/api'

export function useInstallment() {
    const loading = ref(false)
    const plans = ref([])
    const creditLimit = ref(null)

    const getPlans = async () => {
        loading.value = true
        try {
            const response = await api.get('/api/installment/plans')
            plans.value = response.data.data
            return plans.value
        } finally {
            loading.value = false
        }
    }

    const simulate = async (amount, planId) => {
        loading.value = true
        try {
            const response = await api.post('/api/installment/simulate', { amount, plan_id: planId })
            return response.data.data
        } finally {
            loading.value = false
        }
    }

    const getCreditLimit = async () => {
        loading.value = true
        try {
            const response = await api.get('/api/customer/credit-limit')
            creditLimit.value = response.data.data
            return creditLimit.value
        } finally {
            loading.value = false
        }
    }

    const getSchedule = async (uuid) => {
        loading.value = true
        try {
            const response = await api.get(`/installments/${uuid}/schedule`)
            return response.data
        } finally {
            loading.value = false
        }
    }

    return {
        loading,
        plans,
        creditLimit,
        getPlans,
        simulate,
        getCreditLimit,
        getSchedule,
    }
}