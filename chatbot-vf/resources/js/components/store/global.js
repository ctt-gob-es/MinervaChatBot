import { defineStore } from 'pinia';

export const useGlobalStore = defineStore('global', {
    state: () => ({
        color: '#32799',
        idCustomer: null,
        roleUser: null,
    }),
    actions: {
        changeColor(color) {
            this.color = color;
        },
        setIdCustomer(id) {
            this.idCustomer = id;
        },
        setRoleUser(role) {
            this.roleUser = role;
        },
    },
    getters: {
        getColor() {
            return this.color;
        },
    },
});
