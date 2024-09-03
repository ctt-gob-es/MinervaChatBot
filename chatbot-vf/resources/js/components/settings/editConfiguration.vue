<template>
  <Breadcrumb :items_Breadcrumb="items_Breadcrumb" />
  <v-tabs v-model="tabs" :color="global.color" fixed-tabs>
    <v-tab value="ajustes" v-if="$can('system_settings')">Ajustes predeterminados del sistema</v-tab>
    <v-tab value="predeterminados" v-if="$can('default_chatbot_settings')">Ajustes Predeterminados de Chatbot</v-tab>
  </v-tabs>

  <v-card-text>
    <v-window v-model="tabs">
      <v-window-item value="ajustes">
        <appSettings  />
      </v-window-item>
      <v-window-item value="predeterminados">
        <defaults  />
      </v-window-item>
    </v-window>
  </v-card-text>
</template>

  <script setup>
import { ref } from "vue";
import Breadcrumb from "../utilities/Breadcrumb.vue";
import appSettings from "./appSettings.vue";
import defaults from "./defaults.vue";
import { useGlobalStore } from "../store/global";
const global = useGlobalStore();
const items_Breadcrumb = ref([
  {
    title: "Ajustes",
    disabled: false,
    href: "settings",
  },
  {
    title: "Ajustes predeterminados",
    disabled: true,
    href: "breadcrumbs_link_2",
  },
]);


const tabs = ref("ajustes");
</script>

<style scoped>
.v-tab {
  max-width: 760px !important;
}
</style>
