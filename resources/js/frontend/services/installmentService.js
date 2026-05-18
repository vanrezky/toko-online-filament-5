import axios from "axios";

let csrfInitialized = false;

const initCSRF = async () => {
    if (csrfInitialized) return;
    await axios.get("/sanctum/csrf-cookie", { withCredentials: true });
    csrfInitialized = true;
};

export const installmentService = {
    async calculate(amount) {
        await initCSRF();
        const response = await axios.post('/api/installment/calculate', { amount });
        return response.data; // Returns { success: true, data: { principal_amount, plans } }
    },

    async simulate(amount, planId) {
        await initCSRF();
        const response = await axios.post('/api/installment/simulate', { amount, plan_id: planId });
        return response.data.data; // Returns the simulation data
    },

    async getPlans() {
        const response = await axios.get('/api/installment/plans');
        return response.data.data; // Returns the plans array
    },

    async getCreditLimit() {
        const response = await axios.get('/api/customer/credit-limit');
        return response.data.data; // Returns the credit limit data
    },

    async getSchedule(uuid) {
        const response = await axios.get(`/installments/${uuid}/schedule`);
        return response.data; // Returns the schedule response
    },
};