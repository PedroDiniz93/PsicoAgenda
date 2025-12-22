import { defineStore } from 'pinia';
import axios from 'axios';

export const useAlertsStore = defineStore('alerts', {
    state: () => ({
        inactivePatients: [],
        thresholdDays: 15,
        loadingInactivePatients: false,
        inactivePatientsError: '',
        hasUnreadInactivePatients: false,
    }),
    getters: {
        inactiveCount: (state) => state.inactivePatients.length,
    },
    actions: {
        async fetchInactivePatients(params = {}) {
            this.loadingInactivePatients = true;
            this.inactivePatientsError = '';

            try {
                const { data } = await axios.get('/api/patients/inactivity-alerts', { params });
                this.inactivePatients = Array.isArray(data?.patients) ? data.patients : [];
                this.hasUnreadInactivePatients = Boolean(data?.has_unread);

                const threshold = Number(data?.threshold_days ?? params.days ?? this.thresholdDays ?? 15);
                this.thresholdDays = Number.isNaN(threshold) ? 15 : threshold;
            } catch (error) {
                this.inactivePatientsError =
                    error?.response?.data?.message ?? 'Não foi possível carregar os pacientes em alerta.';
            } finally {
                this.loadingInactivePatients = false;
            }
        },
        reset() {
            this.inactivePatients = [];
            this.inactivePatientsError = '';
            this.loadingInactivePatients = false;
            this.hasUnreadInactivePatients = false;
        },
        async acknowledgeInactivePatients() {
            if (!this.hasUnreadInactivePatients) return;

            try {
                await axios.post('/api/patients/inactivity-alerts/acknowledge');
                this.hasUnreadInactivePatients = false;
            } catch (error) {
                // keep the unread flag so the UI can try again
                throw error;
            }
        },
    },
});
