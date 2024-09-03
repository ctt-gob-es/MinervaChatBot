<template>
  <v-tabs v-model="tabs" :color="global.color" fixed-tabs>
    <v-tab value="general">General</v-tab>
    <v-tab v-if="showAgentsModule && $can('manage_settings_hours')" value="hours">Horas</v-tab>
    <v-tab v-if="showAgentsModule && $can('manage_settings_holidays')" value="holidays">Festivos</v-tab>
  </v-tabs>

  <v-card-text>
    <v-window v-model="tabs">
      <v-window-item value="general">
        <generalBot :settings="props.settings" @updateShowAgentsModule="handleUpdateShowAgentsModule" />
      </v-window-item>
      <v-window-item v-if="showAgentsModule" value="hours">
        <schedule :settings="props.settings" />
      </v-window-item>
      <v-window-item v-if="showAgentsModule" value="holidays">
        <holidays :settings="props.settings" />
      </v-window-item>
    </v-window>
  </v-card-text>
</template>

<script setup>
import { onMounted, ref } from "vue";

import generalBot from "./generalBot.vue";
import schedule from "./schedule.vue";
import holidays from "./holidays.vue";
import axios from 'axios';
import { useGlobalStore } from "../store/global";
const global = useGlobalStore();
const showAgentsModule = ref(false);

const emit = defineEmits(["back-to", "save-settings-chat", 'show-message', 'updateShowAgentsModule']);

const props = defineProps({
  settings: { type: Object },
  loadingSave: { type: Boolean, default: () => false },
  languages: { type: Array, default: () => [] },
  settingsChatbot: { type: Array, default: () => [] },
});

const handleUpdateShowAgentsModule = (value) => {
  showAgentsModule.value = value;
};

const tabs = ref("general");

const getModuleAgent = async (props) => {
  try {
    await axios
      .get("/getModuleAgent/" + props)
      .then((response) => {
        if (response.data.success) {
          showAgentsModule.value = true
        } else {
          showAgentsModule.value = false
        }
      })
      .catch((error) => {
        console.error(error);

      })
      .finally(() => {


      });
  } catch (error) {
    console.error(error);
  }
};

const updateShowAgentsModule = (value) => {
  emit("updateShowAgentsModule", value);
};

onMounted(() => {
  getModuleAgent(props.settings.id);
});
</script>

<style scoped>
.v-tab {
  max-width: 760px !important;
}
</style>
