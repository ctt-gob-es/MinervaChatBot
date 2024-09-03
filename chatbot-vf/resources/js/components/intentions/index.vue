
<template>
  <v-tabs v-model="tabs" :color="global.color"  fixed-tabs>
    <v-tab value="subjects" v-if="$can('manage_thematic')">Temáticas</v-tab>
    <v-tab value="intentions" v-if="$can('manage_intention')">Intenciones</v-tab>
    <v-tab value="concepts" v-if="$can('manage_concepts')">Contextos</v-tab>
    <v-tab value="lists" v-if="$can('manage_lists')">Listas</v-tab>
  </v-tabs>

  <v-card-text class="p-0 py-2">
    <v-window v-model="tabs" >
      <v-window-item value="subjects">
        <subjectsTab :dataChat="dataChat"/>
      </v-window-item>
      <v-window-item value="intentions">
        <intentionsTab :dataChat="dataChat" />
      </v-window-item>
      <v-window-item value="concepts">
        <conceptsTab :dataChat="dataChat" />
      </v-window-item>
      <v-window-item value="lists">
        <listsTab :dataChat="dataChat" />
      </v-window-item>
    </v-window>
  </v-card-text>
</template>

<script setup>
import { ref } from "vue";

import intentionsTab from "./intentions.vue";
import subjectsTab from "./subjects.vue";
import conceptsTab from "./concepts.vue";
import listsTab from "./lists.vue";
import { useGlobalStore } from "../store/global";
const global = useGlobalStore();

const tabs = ref("subjects");

const props = defineProps({
  dataChat: { type: Object, default: null },
});
</script>

<style scoped>
.v-tab {
  max-width: 760px !important;
}
</style>

