import axios from "axios";

const apiClient = axios.create({
    baseURL: "/",
    headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
    },
    withCredentials: true,
});

let csrfInitialized = false;

const initCSRF = async () => {
    if (csrfInitialized) return;
    await axios.get("/sanctum/csrf-cookie", { withCredentials: true });
    csrfInitialized = true;
};

export const newsletterService = {
    async subscribe(email) {
        await initCSRF();
        const response = await apiClient.post("/newsletter/subscribe", { email });
        return response.data;
    },

    async unsubscribe(token) {
        const response = await apiClient.get(`/newsletter/unsubscribe/${token}`);
        return response.data;
    },

    async sendTest(email) {
        await initCSRF();
        const response = await apiClient.post("/newsletter/send-test", { email });
        return response.data;
    },
};

export default newsletterService;