<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(amount);
};

const props = defineProps({
    installment: Object,
    schedule: Array
})

const progressPercentage = computed(() => {
    if (!props.installment) return 0
    return Math.round((props.installment.paid_installments / props.installment.tenor) * 100)
})

const getStatusColor = (status) => {
    const colors = {
        unpaid: 'bg-yellow-100 text-yellow-800',
        partial: 'bg-blue-100 text-blue-800',
        paid: 'bg-green-100 text-green-800',
        overdue: 'bg-red-100 text-red-800',
        cancelled: 'bg-gray-100 text-gray-800'
    }
    return colors[status] || colors.unpaid
}

const getStatusLabel = (status) => {
    const labels = {
        unpaid: 'Belum Bayar',
        partial: 'Sebagian',
        paid: 'Lunas',
        overdue: 'Terlambat',
        cancelled: 'Dibatalkan'
    }
    return labels[status] || 'Belum Bayar'
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
            <Link href="/installments" class="text-primary hover:underline text-sm mb-4 inline-block">
                &larr; Kembali ke Daftar Cicilan
            </Link>

            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h1 class="text-xl font-bold mb-4">Detail Cicilan</h1>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Kode Cicilan</p>
                        <p class="font-mono text-sm">{{ installment?.code || installment?.uuid }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <span :class="['px-2 py-1 rounded-full text-xs font-medium',
                            installment?.status === 'active' ? 'bg-yellow-100 text-yellow-800' :
                            installment?.status === 'completed' ? 'bg-green-100 text-green-800' :
                            installment?.status === 'cancelled' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800']">
                            {{ installment?.status === 'active' ? 'Aktif' :
                               installment?.status === 'completed' ? 'Lunas' :
                               installment?.status === 'cancelled' ? 'Dibatalkan' : 'Terlambat' }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Harga Pokok</p>
                        <p class="font-semibold">{{ formatCurrency(installment?.principal_amount) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Fee Cicilan</p>
                        <p class="font-semibold">{{ formatCurrency(installment?.fee_amount) }}</p>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm text-gray-500">Progress Pembayaran</span>
                        <span class="text-sm font-medium">{{ installment?.paid_installments }} / {{ installment?.tenor }} bulan</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" :style="{ width: progressPercentage + '%' }"></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-6">
                    <div>
                        <p class="text-sm text-gray-500">Total Cicilan</p>
                        <p class="font-bold text-lg">{{ formatCurrency(installment?.total_amount) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Angsuran/bulan</p>
                        <p class="font-semibold">{{ formatCurrency(installment?.monthly_amount) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Dibayar</p>
                        <p class="font-semibold text-green-600">{{ formatCurrency(installment?.paid_amount) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Sisa</p>
                        <p class="font-semibold text-red-600">{{ formatCurrency(installment?.total_amount - installment?.paid_amount) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Jadwal Pembayaran</h2>
                </div>

                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bulan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jatuh Tempo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nominal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="payment in schedule" :key="payment.installment_number">
                            <td class="px-6 py-4 text-sm">{{ payment.installment_number }} / {{ installment?.tenor }}</td>
                            <td class="px-6 py-4 text-sm">{{ formatDate(payment.due_date) }}</td>
                            <td class="px-6 py-4 text-sm font-medium">{{ formatCurrency(payment.amount) }}</td>
                            <td class="px-6 py-4">
                                <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusColor(payment.status)]">
                                    {{ getStatusLabel(payment.status) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
