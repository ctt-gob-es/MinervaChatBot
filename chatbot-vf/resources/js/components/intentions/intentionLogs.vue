<template>
  <Datatable
    ref="datatable"
    class="tabla-m"
    :title="'Histórico intención'"
    :headers="headers"
    :items="logs"
    :showSearch="true"
  >
  </Datatable>
</template>
<script setup>
import { ref } from "vue";
import Datatable from "../utilities/Datatable.vue";

const props = defineProps({
  logs: {type: Array, default: () => []}
})

const headers = ref([
  { title: "Acción", align: "start", sortable: true, key: "action",},
  { title: "Usuario", align: "start", sortable: true, key: "user_name",},
  { title: "Fecha de modificación", align: "start", sortable: true, key: "created_at",},
]);
const logs = ref([]);

logs.value = props.logs;
</script>
