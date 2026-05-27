<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    installments: {
        type: Array,
        default: () => []
    },
    monthlyBills: {
        type: Object,
        default: () => ({ next_month: null, upcoming: [] })
    }
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(amount)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    })
}

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-green-100 text-green-800',
        overdue: 'bg-red-100 text-red-800',
        defaulted: 'bg-gray-100 text-gray-800',
        pending: 'bg-yellow-100 text-yellow-800',
        submitted: 'bg-blue-100 text-blue-800',
        failed: 'bg-red-100 text-red-800',
        unpaid: 'bg-yellow-100 text-yellow-800',
        partial: 'bg-blue-100 text-blue-800',
        cancelled: 'bg-gray-100 text-gray-800',
    }

    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
    const labels = {
        active: 'Aktif',
        completed: 'Lunas',
        overdue: 'Terlambat',
        defaulted: 'Wanprestasi',
        pending: 'Menunggu Diproses',
        submitted: 'Diajukan ke Keuangan',
        failed: 'Gagal Dipotong',
        unpaid: 'Belum Bayar',
        partial: 'Sebagian',
        paid: 'Lunas',
        cancelled: 'Dibatalkan',
    }

    return labels[status] || status
}

const monthlySections = computed(() => {
    const sections = []

    if (props.monthlyBills?.next_month) {
        sections.push({
            title: 'Tagihan Bulan Depan',
            month: props.monthlyBills.next_month.month_label,
            items: props.monthlyBills.next_month.items,
        })
    }

    ;(props.monthlyBills?.upcoming || []).forEach((group) => {
        sections.push({
            title: 'Tagihan Bulan Berikutnya',
            month: group.month_label,
            items: group.items,
        })
    })

    return sections
})
</script>

<template>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4">
            <h1 class="text-2xl font-bold text-gray-900">Cicilan & Tagihan Bulanan</h1>

            <div v-if="monthlySections.length" class="space-y-4">
                <div v-for="(section, index) in monthlySections" :key="`${section.month}-${index}`" class="rounded-lg bg-white p-5 shadow">
                    <p class="text-sm font-semibold text-gray-600">{{ section.title }} - {{ section.month }}</p>
                    <ul class="mt-3 space-y-2">
                        <li v-for="(item, itemIndex) in section.items" :key="`${item.reference}-${itemIndex}`" class="flex items-center justify-between rounded-md bg-gray-50 px-3 py-2">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ itemIndex + 1 }}. {{ item.description }}</p>
                                <p class="text-xs text-gray-500">Ref: {{ item.reference }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-900">{{ formatCurrency(item.amount) }}</p>
                                <span :class="['mt-1 inline-flex rounded-full px-2 py-0.5 text-xs', getStatusColor(item.status)]">{{ getStatusLabel(item.status) }}</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div v-if="installments.length === 0" class="rounded-lg bg-white py-12 text-center shadow">
                <p class="text-gray-500">Belum ada cicilan aktif</p>
                <Link href="/products" class="mt-4 inline-block text-primary hover:underline">Mulai Belanja</Link>
            </div>

            <div v-else class="space-y-4">
                <div v-for="installment in installments" :key="installment.uuid" class="rounded-lg bg-white p-6 shadow">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Cicilan #{{ installment.code }}</p>
                            <p class="text-sm text-gray-500">Pesanan #{{ installment.transaction?.code }}</p>
                            <p class="text-lg font-semibold">{{ formatCurrency(installment.total_amount) }}</p>
                        </div>
                        <span :class="['rounded-full px-3 py-1 text-xs font-medium', getStatusColor(installment.status)]">
                            {{ getStatusLabel(installment.status) }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
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

                    <div class="mt-4 border-t pt-4">
                        <Link :href="`/installments/${installment.uuid}`" class="text-sm text-primary hover:underline">Lihat Jadwal Cicilan -></Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
