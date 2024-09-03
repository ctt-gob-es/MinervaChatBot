import { createRouter, createWebHistory } from "vue-router"

import Dashboard from "./components/dashboard/dashboard.vue";
import Conversations from "./components/conversations/index.vue";
import Builder from "./components/builder/builder.vue";
import ChatbotSettings from "./components/settings-chatbot/Index.vue";
import Users from "./components/users/index.vue";
import Customers from "./components/customers/index.vue";
import Settings from "./components/settings/index.vue";
import Roles from "./components/roles/index.vue";
import Chatbots from "./components/chatbots/index.vue";
import SupervisedTraining from "./components/supervised-training/index.vue";
import SupervisedManual from "./components/supervised-manual/index.vue";
import ChabotCustomer from "./components/chatCustomer/Index.vue";
import Profile from "./components/account/profile.vue";
import EditConfiguration from "./components/settings/editConfiguration.vue";
import ScriptTester from "./components/scriptTester/index.vue";



const routes = [
    {
        path: '/',
        component: Dashboard
    },
    {
        path: '/conversations',
        name: 'conversations',
        component: Conversations
    },
    {
        path: '/builder',
        name: 'builder',
        component: Builder
    },
    {
        path: '/chatbotSettings',
        name: 'chatbotSettings',
        component: ChatbotSettings
    },
    {
        path: '/users',
        name: 'users',
        component: Users
    },
    {
        path: '/customers',
        name: 'customers',
        component: Customers
    },
    {
        path: '/settings',
        name: 'settings',
        component: Settings
    },
    {
        path: '/roles',
        name: 'roles',
        component: Roles
    },
    {
        path: '/chatbots/:idCustomer?',
        name: 'chatbots',
        component: Chatbots
    },
    {
        path: '/supervised_training',
        name: 'supervised_training',
        component: SupervisedTraining
    },
    {
        path: '/supervised_manual',
        name: 'supervised_manual',
        component: SupervisedManual
    },
    {
        path: '/chatbot-customer/:chatbotId/:lang?',
        name: 'chatbot-customer',
        component: ChabotCustomer,
    },
    {
        path: '/profile/:idUser',
        name: 'profile',
        component: Profile
    },
    {
        path: '/editConfiguration',
        name: 'editConfiguration',
        component: EditConfiguration
    },
    {
        path: '/scriptTester',
        name: 'scriptTester',
        component: ScriptTester
    }

];
const router = createRouter({
    history: createWebHistory(),
    routes
})
export default router;
