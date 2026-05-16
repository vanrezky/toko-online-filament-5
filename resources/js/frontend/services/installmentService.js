import { api } from './api'

export const installmentService = {
    simulate: (amount, planId) => api.post('/api/installment/simulate', { amount, plan_id: planId }),
    getPlans: () => api.get('/api/installment/plans'),
    getCreditLimit: () => api.get('/api/customer/credit-limit'),
    getSchedule: (uuid) => api.get(`/installments/${uuid}/schedule`),
}