<template>
  <Loader :loading="loading" />
  <v-tabs v-model="tab" :color="global.color" fixed-tabs>
    <v-tab value="negative" v-if="$can('answer_negative')">NEGATIVA</v-tab>
    <v-tab value="no_valuation" v-if="$can('answer_no_valuation')">SIN VALORACIÓN</v-tab>
    <v-tab value="positive" v-if="$can('answer_positive')">POSITIVA</v-tab>
    <v-tab value="uncategorized" v-if="$can('answer_uncategorized')">SIN CATEGORÍA</v-tab>
  </v-tabs>
  <v-card-text class="p-0 py-2">
    <v-window v-model="tab">
      <v-window-item value="negative">
        <negative/>
      </v-window-item>
      <v-window-item value="no_valuation">
        <noValuation/>
      </v-window-item>
      <v-window-item value="positive">
        <positive/>
      </v-window-item>
      <v-window-item value="uncategorized">
        <uncategorized/>
      </v-window-item>
    </v-window>
  </v-card-text>

</template>

<script setup>
import Loader from "../utilities/Loader.vue";
import negative from "./negative.vue";
import noValuation from "./noValuation.vue";
import positive from "./positive.vue";
import uncategorized from "./uncategorized.vue";
import { ref } from "vue";
import { useGlobalStore } from "../store/global";
const global = useGlobalStore();
const tab = ref("negative");
</script>
<style scoped>
.v-tab {
  max-width: 760px !important;
}
</style>
