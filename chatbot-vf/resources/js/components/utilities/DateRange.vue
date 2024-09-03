<template>
  <div class="pb-2">
    <v-expansion-panels>
      <v-expansion-panel>
        <v-expansion-panel-title class="bar_color">Búsqueda</v-expansion-panel-title>
        <v-expansion-panel-text>
          <v-row class="elemento">
            <v-col cols="4" class="elemento_col">
              <v-menu :close-on-content-click="false" transition="scale-transition" offset-y max-width="200px"
                min-width="200px">
                <template v-slot:activator="{ props }">
                  <v-text-field v-bind="props" v-model="date_desde_formatted" label="Desde (Fecha de creación)"
                    append-inner-icon="mdi-calendar" variant="solo" readonly
                    @input="date_desde = formatDate(date_desde_formatted)" />
                </template>
                <v-date-picker hide-header v-model="date_desde" no-title scrollable :max="to"></v-date-picker>
              </v-menu>
            </v-col>
            <v-col cols="4" class="elemento_col">
              <v-menu :close-on-content-click="false" transition="scale-transition" offset-y max-width="200px"
                min-width="200px">
                <template v-slot:activator="{ props }">
                  <v-text-field v-bind="props" v-model="date_hasta_formatted" label="Hasta (Fecha de creación)"
                    append-inner-icon="mdi-calendar" variant="solo" readonly
                    @input="date_hasta = formatDate(date_hasta_formatted)" />
                </template>
                <v-date-picker hide-header v-model="date_hasta" no-title scrollable :min="from"></v-date-picker>
              </v-menu>
            </v-col>

            <v-col style="
                justify-content: end;
                display: inline-flex;
                align-items: flex-start;
              ">
              <v-row style="justify-content: center; align-items: center" class="elemeto_botones">
                <v-col cols="auto">
                  <v-btn prepend-icon="mdi mdi-magnify" class="btn_color" @click="$emit('submit')">
                    BUSCAR
                  </v-btn>
                </v-col>
                <v-col cols="auto">
                  <v-btn prepend-icon="mdi mdi-broom" class="btn_clean" @click="clearDataRange">
                    LIMPIAR
                  </v-btn>
                </v-col>
              </v-row>
            </v-col>
          </v-row>
          <v-row v-if="filter_conversation" class="elemeto_botones">
            <v-col cols="4">
              <v-select label="Estado" :items="stateOptions" v-model="state" @update:modelValue="stateChanged" variant="solo" item-title="name" item-value="id"></v-select>
            </v-col>
          </v-row>

          <v-row v-if="filter_dashboard"  class="elemeto_dashboard">
            <v-col cols="4">
              <v-menu :close-on-content-click="false" transition="scale-transition" offset-y max-width="200px"
                min-width="200px">
                <template v-slot:activator="{ props }">
                  <v-select label="Clientes" @update:modelValue="customerChanged" :items="customerOptions"
                    item-title="name" item-value="id" v-model="customerId" variant="solo"></v-select>
                </template>

              </v-menu>
            </v-col>
            <v-col cols="4">
              <v-menu :close-on-content-click="false" transition="scale-transition" offset-y max-width="200px"
                min-width="200px">
                <template v-slot:activator="{ props }">
                  <v-select label="Chatbots" :items="chatbotOptions" v-model="chatbotId"
                    @update:modelValue="chatbotChanged" variant="solo" item-title="name" item-value="id"></v-select>
                </template>
              </v-menu>
            </v-col>
          </v-row>

        </v-expansion-panel-text>
      </v-expansion-panel>
    </v-expansion-panels>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";

const props = defineProps({
  initialFilter: { type: Boolean, default: () => false },
  filter_conversation: { type: Boolean, default: () => false },
  filter_dashboard: { type: Boolean, default: () => false },
  customerOptions: { type: Array, default: () => [] },
  stateOptions: { type: Array, default: () => [] },
  chatbotOptions: { type: Array, default: () => [] },
  initialCustomerId: { type: Number, default: () => null },
  initialChatbotId: { type: String, default: () => null },
});
const emit = defineEmits(["submit", "updateDate", "clearData", "updateChatbots"]);
const from = ref(null);
const to = ref(null);
const state = ref(null);
const result = ref(null);
const date_hasta = ref(null);
const date_desde = ref(null);
const date_desde_formatted = ref(null);
const date_hasta_formatted = ref(null);
const chatbotId = ref(props.initialChatbotId);
const customerId = ref(props.initialCustomerId);

onMounted(() => {
  setInitDate();
});

watch(date_hasta, (val) => {
  if (date_hasta.value != null) {
    to.value = date_hasta.value;
    date_hasta_formatted.value = formatDate(date_hasta.value);
    updateDate();
  }
});

watch(date_desde, (val) => {
  if (date_desde.value != null) {
    from.value = date_desde.value;
    date_desde_formatted.value = formatDate(date_desde.value);
    updateDate();
  }
});

watch(() => props.initialCustomerId, (newValue) => {
  if (newValue !== null) {
    customerId.value = newValue
  }
});

watch(() => props.initialChatbotId, (newValue) => {
  if (newValue !== null) {
    chatbotId.value = newValue
  }
});

const updateDate = () => {
  emit("updateDate", from.value, to.value);
};

const customerChanged = (value) => {
  // Emitir evento con el ID del cliente seleccionado
  chatbotId.value = null
  emit("updateChatbots", value);
};

const chatbotChanged = (value) => {
  emit("chatbotSelected", value)
}
const stateChanged = (value) => {
  emit("stateSelected", value)
}

const clearDataRange = () => {
  setInitDate();
  from.value = null;
  to.value = null;
  date_hasta.value = null;
  date_desde.value = null;
  date_desde_formatted.value = null;
  date_hasta_formatted.value = null;
  state.value = null;
  result.value = null;
  chatbotId.value = null;
  emit("clearData");
};

const setInitDate = () => {
  const timeZone = "Europe/Madrid";
  const hoy = new Date().toLocaleDateString("en-US", { timeZone });
  const [hoy_fecha] = hoy.split(",");
  if (props.initialFilter) {
    date_desde.value = new Date(hoy_fecha);
    date_hasta.value = new Date(hoy_fecha);

    date_desde_formatted.value = formatDate(date_desde.value);
    date_hasta_formatted.value = formatDate(date_hasta.value);

    from.value = date_desde.value;
    to.value = date_hasta.value;
    updateDate();
  }
};

const formatDate = (date) => {
  if (!date || isNaN(new Date(date).getTime())) return null;

  if (!(date instanceof Date)) {
    date = new Date(date);
  }
  const options = { timeZone: "Europe/Madrid" };
  return date.toLocaleDateString("es-ES", options);
};
</script>

<style scoped>
.bar_color {
  padding: 1rem !important;
  background-color: var(--primary-color) !important;
  display: flex;
  justify-content: space-between;
  font-size: 18px !important;
  font-weight: 600 !important;
  color: #ffff !important;
}

.btn_color {
  background-color: var(--primary-color) !important;
  color: #ffff !important;
}

.btn_clean {
  background-color: rgb(103, 100, 100);
  color: #ffff !important;
}

/* Estilos para dispositivos con un ancho de pantalla menor o igual a 375px */
@media only screen and (max-width: 375px) {
  .elemento {
    flex-direction: column !important;
  }
  .elemento .elemento_col{
    max-width: 100% !important;
  }
  .elemeto_botones{
    display: flex !important;
    flex-wrap: nowrap !important;
  }
  .elemeto_botones .v-col-4 {
    width: 100% !important;
    max-width: 100% !important;
    flex: 0 0 100% !important;
  }
  .elemeto_dashboard .v-col-4 {
    width: 100% !important;
    max-width: 100% !important;
    flex: 0 0 100% !important;
  }
  .btn_color, .btn_clean{
    width: 110px;
  }
}


</style>
