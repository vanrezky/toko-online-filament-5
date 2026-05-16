<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import { installmentService } from '@/frontend/services/installmentService'
import { formatCurrency } from '@/frontend/utils/formatCurrency'

const installments = ref([])
const loading = ref(true)

onMounted(async () => {
    try {
        const response = await installmentService.getPlans()
        // Get customer's installments from the page data
        installments.value = props.installments
    } finally {
        loading.value = false
    }
})

const props = defineProps({
    installments: {
        type: Array,
        default: () => []
    }
})

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-green-100 text-green-800',
        overdue: 'bg-red-100 text-red-800',
        defaulted: 'bg-gray-100 text-gray-800'
    }
    return colors[status] || colors.active
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    })
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="mx-auto max-w-4xl px-4">
            <h1 class="mb-6 text-2xl font-bold text-gray-900">Cicilan Aktif</h1>

            <div v-if="loading" class="text-center py-12">
                <div class="h-8 w-8 mx-auto border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else-if="installments.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
                <p class="text-gray-500">Belum ada cicilan aktif</p>
                <Link href="/products" class="mt-4 inline-block text-primary hover:underline">
                    Mulai Belanja
                </Link>
            </div>

            <div v-else class="space-y-4">
                <div v-for="installment in installments" :key="installment.uuid"
                    class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm text-gray-500">Pesanan #{{ installment.transaction?.uuid }}</p>
                            <p class="text-lg font-semibold">{{ formatCurrency(installment.total_amount) }}</p>
                        </div>
                        <span :class="['px-3 py-1 rounded-full text-xs font-medium', getStatusColor(installment.status)]">
                            {{ installment.status === 'active' ? 'Aktif' :
                               installment.status === 'completed' ? 'Lunas' :
                               installment.status === 'overdue' ? 'Terlambat' : 'Wanprestasi' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Angsuran/bulan</p>
                            <p class="font-medium">{{ formatCurrency(installment.monthly_amount) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Tenor</p>
                            <p class="font-medium">{{ installment.tenor }} bulan</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Sudah Dibayar</p>
                            <p class="font-medium">{{ installment.paid_installments }} dari {{ installment.tenor }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Mulai</p>
                            <p class="font-medium">{{ formatDate(installment.start_date) }}</p>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t">
                        <Link :href="`/installments/${installment.uuid}`"
                            class="text-primary hover:underline text-sm">
                            Lihat Jadwal Cicilan &rarr;
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>