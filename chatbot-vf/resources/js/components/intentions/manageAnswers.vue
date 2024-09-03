<template>
  <Loader :loading="loading" />
  <v-card elevation="16">
    <v-card-title class="title-datatable">
      {{ title }}
      <v-btn prepend-icon="mdi mdi-arrow-left" variant="tonal" class="btn-log" @click="$emit('backTo', true)">
        Volver
      </v-btn>
    </v-card-title>
    <v-card-text>
      <Datatable ref="datatable" class="tabla-m" :title="'Combinaciones'" :button_reload="true" :headers="headersCombinations" :items="combinations" @click-reload="loadData()" :showSearch="true" :showHeader="true" :itemsPerPage="25" hideHeaderTable>
        <template v-slot:buttons-header>
          <v-btn align="center" class="btn-log btn-datatable btn-reload" title="Generar combinaciones" @click="confirmCombinations()" v-if="$can('generate_combinations')">
            <v-icon class="btn-icon-data" size="large" color="#212529">mdi-transit-connection-variant</v-icon>
            <span class="btn-txt ml-1">Generar combinaciones</span>
          </v-btn>
        </template>
        <template v-slot:[`item.response`]="{ item }">
          <v-switch v-model="item.response" color="success" :false-value="0" :true-value="1" class="d-flex justify-content-center"
            hide-details></v-switch>
        </template>
      </Datatable>
    </v-card-text>
    <v-card-actions class="pt-0 mr-4">
      <v-spacer></v-spacer>
      <v-btn variant="elevated" :color="global.color" @click="storeCombinations()" :loading="loadingStore">
        Guardar
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, onMounted } from "vue";
import Loader from "../utilities/Loader.vue";
import Datatable from "../utilities/Datatable.vue";
import axios from "axios";
import Swal from "sweetalert2";
import { useGlobalStore } from "../store/global";
const global = useGlobalStore();

const props = defineProps({
  dataChat: { type: Object, default: null },
  intentionId: { type: Number, default: null },
});
const emit = defineEmits(["backTo"]);

const title = ref('Gestionar respuestas');
const loading = ref(false);
const headersCombinations = ref([]);
const combinations = ref([]);
const loadingStore = ref(false);

onMounted(async () => {
  loadData();
});

const loadData = async () => {
  loading.value = true;
  await getCombinations();
  loading.value = false;
};

const confirmCombinations = async () => {
  Swal.fire({
    title: "¿Estás seguro?",
    text: "El generar las combinaciones hará que tengas que gestionar nuevamente cada una de las respuestas.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Generar",
  }).then(async (result) => {
    if (result.isConfirmed && props.intentionId) {
      await generateCombinations();
    }
  });
}

const generateCombinations = async () => {
  await axios.get(`/create-combinations/${props.intentionId}/${props.dataChat.id}`)
    .then((response) => {
      if(response?.data?.success){
        Swal.fire({ title: "Excelente", text: "Combinaciones generadas correctamente!", icon: "success"});
      }else{
        Swal.fire({ title: "Atención!", text: "Ocurrió un problema al momento de generar las combinaciones.", icon: "warning"});
      }
    })
    .catch((error) => {
      console.error(error);
      if (error?.response?.data?.message == "not flow.") {
        Swal.fire({
          title: "Atención",
          text: "Para habilitar el chat asegurese de tener un flujo creado.",
          icon: "warning",
        });
        loadData();
      }
      loading.value = false;
    })
    .finally(async () => {
      await loadData();
    })
}

const getCombinations = () => {
  if(!props.intentionId){
    Swal.fire({ title: "Atención!", text: "No fue posible obtener el identificador de la intención", icon: "warning"});
    return;
  }
  axios.get(`/combinations/${props.intentionId}`).then((response) => {
    if(response?.data?.data?.headers){
      headersCombinations.value = [];
      headersCombinations.value = response?.data?.data?.headers
      headersCombinations.value.push({ title: "Response", align: "center", sortable: true, key: "response"},)
    }
    if(response?.data?.data?.items){
      combinations.value = response?.data?.data?.items
    }
  })
}

const storeCombinations = async() => {
  if(!props.intentionId){
    Swal.fire({ title: "Atención!", text: "No fue posible obtener el identificador de la intención", icon: "warning"});
    return;
  }
  let data = {
    intention_id: props.intentionId,
    items: combinations.value,
  }
  loadingStore.value = true;
  await axios.post('/storeCombinations', data)
    .then((response) => {
      if(response?.data?.success){
        Swal.fire({ title: "Excelente", text: "Cambios realizados correctamente!", icon: "success"});
      }else{
        Swal.fire({ title: "Atención!", text: "Ocurrió un problema al momento de guardar combinaciones.", icon: "warning"});
      }
    })
    .catch((error) => {
      console.error(error)
      Swal.fire({ title: "Atención!", text: "Ocurrió un problema al momento de guardar combinaciones.", icon: "warning"});
    })
    .finally(async () => {
      loadingStore.value = false;
      await loadData();
    })
}

</script>
<style scoped>
</style>
