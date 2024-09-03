<template>
    <Loader :loading="loading" />
    <v-tabs v-model="tab" :color="global.color" fixed-tabs>
      <v-tab value="intentions" v-if="$can('question_intentions')">CON INTENCIÓN</v-tab>
      <v-tab value="no_intentions" v-if="$can('question_no_intentions')">SIN INTENCIÓN</v-tab>
    </v-tabs>
    <v-card-text class="p-0 py-2">
      <v-window v-model="tab">
        <v-window-item value="intentions">
          <intentions/>
        </v-window-item>
        <v-window-item value="no_intentions">
          <noIntentions/>
        </v-window-item>
      </v-window>
    </v-card-text>
  </template>
  <script setup>
  import Loader from "../utilities/Loader.vue";
  import intentions from "./intentions.vue";
  import noIntentions from "./no_intentions.vue";
  import { ref } from "vue";
  import { useGlobalStore } from "../store/global";
  const global = useGlobalStore();
  const tab = ref("negative");
  </script>
  <style scoped>
  .v-tab {
    max-width: 850px !important;
  }
  </style>
