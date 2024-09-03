<template>
  <div>
    <Datatable
      ref="datatable"
      class="tabla-m"
      :button_reload="true"
      :title="'Histórico chatbots'"
      :headers="headers"
      :items="logs"
      @click-reload="getLogs"
    >
      <template v-slot:slot-buttons>
        <v-btn @click="importFileClick" class="btn-log btn-datatable"
        title="Archivo"
        >
          <v-icon class="btn-icon-data" color="#212529" size="large">
            mdi mdi-file-import
          </v-icon>
          <span class="btn-txt ml-1">Archivo</span>
        </v-btn>
      </template>
      <template v-slot:[`item.actions`]="{ item }">
        <v-container>
          <v-row align="center" justify="center">
            <v-col cols="auto" class="pa-2">
              <v-tooltip text="Importar" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="importFlow(item.flow)">
                    <v-icon color="#a1a5b7">mdi-download-circle</v-icon>
                  </v-btn>
                </template>
              </v-tooltip>
            </v-col>
          </v-row>
        </v-container>
      </template>
    </Datatable>
    <input @change="changeFile" ref="importFile" type="file" class="d-none" accept=".json">
  </div>
</template>

<script setup>
import { ref } from "vue";
import Datatable from "../utilities/Datatable.vue";
import Swal from "sweetalert2";

const props = defineProps({
  logs: {type: Array, default: () => []},
})

const emit = defineEmits(["import", "get-logs"]);

const importFile = ref(null);
const headers = ref([
  { title: "Usuario", align: "start", sortable: true, key: "user_name",},
  { title: "Fecha creación", align: "start", sortable: true, key: "created_at",},
  { title: "", align: "end", sortable: false, key: "actions",},
]);

const importFlow = (flow) => {
  emit('import', flow)
  emit('get-logs')
}

const getLogs = () => {
  emit('get-logs')
}

const importFileClick = () => {
  importFile.value.click()
}

const changeFile = (e) => {
  let file = e.target.files[0];
  let reader = new FileReader();
  reader.onload = () => {
    let jsonData = reader.result;

    if (isValidJson(jsonData)) {
      emit('import', jsonData)
      emit('get-logs')
    }else{
      Swal.fire({
        title: "Formato incorrecto",
        text: "Ingresa un diagrama valido.",
        icon: "warning",
        confirmButtonText: "Aceptar",
      });
    }
  };

  reader.readAsText(file);
}

const isValidJson = (jsonString) => {
  try {
    let jsonObject = JSON.parse(jsonString);
    return jsonObject.hasOwnProperty('drawflow') && jsonObject.drawflow.hasOwnProperty('Home') && jsonObject.drawflow.Home.hasOwnProperty('data');
  } catch (error) {
    return false;
  }
};

</script>
<style>
.btn-log {
  background-color: #fff !important;
  color: #444 !important;
  border: 1px solid #f5f8fa !important;
  margin-left: 1em;
}
.swal2-container {
  z-index: 2401!important;
}

.btn-datatable{
  height: 32px!important;
  min-width: 40px!important;

}

@media screen and (min-width: 1200px) {
  .btn-txt{
    display: flex;
  }
}


@media screen and (max-width: 1200px) and (min-width: 1075px) {
    .btn-datatable{
      height: 30px!important;
      width: 40px!important;
    }
    .btn-icon-data{
      font-size: 18px;
    }
    .btn-txt{
      display: none;
    }
  }


@media screen and (max-width: 1075px) and (min-width: 580px) {
  .btn-datatable{
    height: 30px!important;
    width: 40px!important;
  }
  .btn-icon-data{
    font-size: 18px;
  }
  .btn-txt{
    display: none;
  }
}

@media screen and (max-width: 580px) {
  .btn-datatable{
    height: 30px!important;
    width: 45px!important;
  }
  .btn-icon-data{
    font-size: 18px;
  }
  .btn-txt{
    display: none;
  }
}
</style>
