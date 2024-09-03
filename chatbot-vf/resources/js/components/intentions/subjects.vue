<template>
  <div style="padding-bottom: 50px">
    <Loader :loading="loading" />
    <DateRange @submit="loadData()" @updateDate="setDate" @clearData="clearDate" :initialFilter="false" />
    <Datatable ref="datatable" class="tabla-m" :title="'Temáticas'" :button_add="$can('thematic_add')" :headers="headers"
      :items="subjects" @click-add="dialogOpen()" :titleAdd="'Agregar tematica'" :button_reload="true"
      @click-reload="loadData()" :showSearch="true" @changeSelection="changeSelection" :initSelection="selectThematics"
      :enableSelect="stateSelect" return-object>
      <template v-slot:slot-buttons v-if="$can('thematic_import')">
        <div>
          <v-btn @click="clickFileImport" class="btn-log btn-datatable" title="Importar">
            <v-icon class="btn-icon-data" color="#212529" size="large">
              mdi mdi-file-import
            </v-icon>
            <span class="btn-txt ml-1">Importar</span>
          </v-btn>
        </div>
      </template>
      <template v-slot:header-opt v-if="selectThematics.length > 0">
        <v-row>
          <div class="d-flex mb-4 ml-5">
            <v-col v-if="$can('discard_response_manual')">
              <v-tooltip text="Tooltip" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="exportIntentionSelect()">
                    <v-icon color="#a1a5b7">mdi-export-variant</v-icon>
                  </v-btn>
                </template>
                <span>Exportar intenciones</span>
              </v-tooltip>
            </v-col>
          </div>
        </v-row>
      </template>
      <template v-slot:[`item.option`]="{ item }">
        <v-container>
          <v-row align="center" justify="center">
            <v-col cols="auto" v-if="$can('thematic_edit') && item.name !== 'Mensajes chatbots'">
              <v-tooltip text="Tooltip" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="dialogOpenEdit(item.id)">
                    <v-icon color="#a1a5b7">mdi mdi-file-document-edit</v-icon></v-btn>
                </template>
                <span>Editar </span>
              </v-tooltip>
            </v-col>
            <v-col cols="auto" v-if="$can('thematic_delete') && item.name !== 'Mensajes chatbots'">
              <v-tooltip text="Tooltip" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="showDelete(item.id)">
                    <v-icon color="#a1a5b7">mdi mdi-trash-can</v-icon>
                  </v-btn>
                </template>
                <span>Eliminar </span>
              </v-tooltip>
            </v-col>
            <v-col cols="auto" v-if="$can('thematic_export')">
              <v-tooltip text="Tooltip" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="exportIntentions(item.id, item.name)">
                    <v-icon color="#a1a5b7">mdi-export-variant</v-icon>
                  </v-btn>
                </template>
                <span>Exportar intenciones</span>
              </v-tooltip>
            </v-col>
          </v-row>
        </v-container>
      </template>
    </Datatable>

    <v-dialog width="900" v-model="dialogVisible">
      <v-card :title="title">
        <v-card-text class="pb-0">
          <form method="dialog">
            <v-text-field v-model="initialState.name" label="Nombre" required variant="outlined"
              :error-messages="errorMessages" @input="clearError"></v-text-field>
          </form>
        </v-card-text>
        <v-card-actions class="pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="elevated" class="btn_color" @click="saveData" :loading="loadingSave">
            Guardar
          </v-btn>
          <v-btn variant="tonal" @click="dialogClose" :disabled="btnCancell">
            Cancelar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <input @change="changeFile($event)" ref="importFile" :key="fileInputKey" type="file" class="d-none" accept=".json">
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from "vue";
import moment from "moment";
import { formatDateTime } from "@/helpers";
import Datatable from "../utilities/Datatable.vue";
import DateRange from "../utilities/DateRange.vue";
import Loader from "../utilities/Loader.vue";
import axios from "axios";
import Swal from "sweetalert2";

const title = ref(null);
const dateFrom = ref(null);
const dateTo = ref(null);
const loading = ref(false);
const loadingSave = ref(false);
const dialogVisible = ref(false);
const btnCancell = ref(false);
const subjects = ref([]);
const errorMessages = ref(null);
const chatbotId = ref(null);
const importFile = ref(null);
const fileInputKey = ref(0);
const selectThematics = ref([]);
const stateSelect = ref(true);
const props = defineProps({
  dataChat: { type: Object, default: null },
})

const headers = ref([
  {
    title: "Nombre",
    align: "start",
    sortable: true,
    key: "name",
  },
  {
    title: "Creador",
    align: "start",
    sortable: true,
    key: "creator_name",
  },
  {
    title: "Fecha creación",
    align: "center",
    sortable: true,
    key: "created_at",
  },

  {
    title: "Opciones",
    align: "center",
    sortable: true,
    key: "option",
  },
]);

const initialState = reactive({
  id: null,
  name: "",
});

onMounted(async () => {
  await loadData();
});

const clearData = () => {
  initialState.id = null;
  initialState.name = "";
};

const clearDate = async () => {
  dateFrom.value = null;
  dateTo.value = null;
  await loadData();
};

const dialogOpen = () => {
  title.value = 'Nueva Temática';
  initialState.name = "";
  dialogVisible.value = true;
};
const dialogClose = async () => {
  dialogVisible.value = false;
  await loadData();
  clearData();
};
const dialogOpenEdit = async (id) => {
  title.value = 'Editar Temática'
  loading.value = true;
  dialogVisible.value = true;
  await axios
    .get(`/editSubjects/${id}`)
    .then((response) => {
      initialState.id = response.data.data.id;
      initialState.name = response.data.data.name;
    })
    .catch((error) => {
      console.error(error);
    });
  loading.value = false;
};
const datatable = ref([]);

const changeSelection = (e) => {
  selectThematics.value = [];
  e.forEach((item) => {
    const id = item.id;
    const name = item.name;
    selectThematics.value.push({ id, name });
  });
};

const loadData = async () => {
  loading.value = true;
  chatbotId.value = props.dataChat.id;
  await getSubjects();
};

const setDate = (from, to) => {
  if (from) dateFrom.value = from;
  if (to) dateTo.value = to;
};

const getSubjects = async () => {
  loading.value = true;
  let from = null;
  let to = null;

  if (dateFrom.value && dateTo.value) {
    from = moment(dateFrom.value, "YYYY-MM-DD").format("YYYY-MM-DD");
    to = moment(dateTo.value, "YYYY-MM-DD").format("YYYY-MM-DD");
  }

  axios
    .get(`/getAllSubjects?chatbot_id=${chatbotId.value}`, {
      params: {
        ...(from && to && { from, to }),
      }
    })
    .then((response) => {
      subjects.value = response.data.data.map((intention) => ({
        ...intention,
        created_at: formatDateTime(intention.created_at),
        updated_at: formatDateTime(intention.updated_at),
      }));
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
      loading.value = false;
    });
};

const validateData = () => {
  if (!initialState.name.trim()) {
    errorMessages.value = "Este campo es obligatorio";
    return false;
  }
  return true;
};
const clearError = () => {
  errorMessages.value = null;
};
const saveData = async () => {
  if (!validateData()) {
    return;
  }
  loading.value = true;
  loadingSave.value = true;
  btnCancell.value = true;
  if (initialState.id === null) {
    await axios
      .post("/saveSubjects", {
        name: initialState.name,
        chatbot_id: chatbotId.value,
      })
      .then((response) => {
        dialogClose();
        Swal.fire({
          title: "Excelente",
          text: "Cambios realizados!",
          icon: "success",
        });
      })
      .catch((error) => {
        console.error(error);
        if (error.response.status === 422) {
          Swal.fire({
            title: "Error",
            text: "No se ha podido agregar la Temática porque este nombre ya existe.",
            icon: "error",
          });
        } else {
          Swal.fire({
            title: "Error",
            text: "Ocurrió un error al intentar agregar el la Temática.",
            icon: "error",
          });
        }
        dialogClose();
        loadingSave.value = false;
        btnCancell.value = false;
        clearData();
      })
      .finally(() => {
        loading.value = false;
        loadingSave.value = false;
        btnCancell.value = false;
        clearData();
      });
  } else {
    loading.value = true;
    await axios
      .put(`/updateSubjects/${initialState.id}`, {
        name: initialState.name,
        chatbot_id: chatbotId.value,
      })
      .then((response) => {
        dialogClose();
        Swal.fire({
          title: "Excelente",
          text: "¡Cambios realizados!",
          icon: "success",
        });
      })
      .catch((error) => {
        console.error(error);
        if (error.response.status === 422) {
          Swal.fire({
            title: "Error",
            text: "No se ha podido editar la Temática porque este nombre ya existe.",
            icon: "error",
          });
        } else {
          Swal.fire({
            title: "Error",
            text: "Ocurrió un error al intentar editar el la Temática.",
            icon: "error",
          });
        }
        dialogClose();
        loadingSave.value = false;
        btnCancell.value = false;
        clearData();
      })
      .finally(() => {
        loading.value = false;
        loadingSave.value = false;
        btnCancell.value = false;
        clearData();
      });
  }

};

const showDelete = (id) => {
  Swal.fire({
    title: "¿Estás seguro?",
    text: "No podrás revertirlo.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "¡Sí, bórralo!!",
  }).then((result) => {
    if (result.isConfirmed) {
      deleteSubjects(id);
    }
  });
};

const deleteSubjects = async (id) => {
  loading.value = true;
  await axios
    .delete(`/deleteSubjects/${id}`)
    .then((response) => {
      dialogClose();
      Swal.fire({
        title: "Excelente",
        text: "Cambios realizados!",
        icon: "success",
      });
      loading.value = false;
      loadData();
    })
    .catch((error) => {
      if (error.response.status === 422) {
        Swal.fire({
          title: "Error",
          text: "No se puede eliminar la temática porque tiene intenciones asociadas.",
          icon: "error",
        });
      } else {
        Swal.fire({
          title: "Error",
          text: "Ocurrió un error al intentar eliminar el sujeto.",
          icon: "error",
        });
      }
      loading.value = false;
    });
};

const exportIntentions = async (id, name) => {
  loading.value = true;
  await axios
    .get(`/exportIntentions/${id}`)
    .then((response) => {
      loading.value = false;
      if (response.data.success) {
        if (response.data.data?.length == 0) {
          Swal.fire({
            title: "Atención",
            text: "Ningún dato por exportar.",
            icon: "info",
          });
          return
        }
        downloadFile(response.data.data, name)
      }
    })
    .catch((error) => {
      console.error(error);
      Swal.fire({
        title: "Error",
        text: "Ocurrió un error al intentar exportar las intenciones.",
        icon: "error",
      });
      loading.value = false;
    });
};

const downloadFile = (data, name) => {
  if (data[0].subject.name !== 'Mensajes chatbots') {
    let currentDate = new Date();
    let formattedDate = `${currentDate.getFullYear()}-${(
      currentDate.getMonth() + 1
    )
      .toString()
      .padStart(2, "0")}-${currentDate
        .getDate()
        .toString()
        .padStart(2, "0")}`;
    let formattedTime = `${currentDate
      .getHours()
      .toString()
      .padStart(2, "0")}-${currentDate
        .getMinutes()
        .toString()
        .padStart(2, "0")}-${currentDate
          .getSeconds()
          .toString()
          .padStart(2, "0")}`;
    let fileName = `${data[0].subject.name}_${formattedDate}_${formattedTime}.json`;
    let jsonData = JSON.stringify(data, null, 4);
    let blob = new Blob([jsonData], { type: "application/json" });
    let link = document.createElement("a");
    link.href = window.URL.createObjectURL(blob);
    link.download = fileName;
    link.click();
    window.URL.revokeObjectURL(link.href);
  }
}

const exportIntentionSelect = async () => {
  loading.value = true;
  const formData = {
    data: selectThematics.value,
  };
  await axios
    .post('/exportIntentionSelect', formData)
    .then((response) => {
      loading.value = false;
      if (response.data.success) {
        if (response.data.data?.length == 0) {
          Swal.fire({
            title: "Atención",
            text: "Ningún dato por exportar.",
            icon: "info",
          });
          return
        }
        let data = response.data.data;
        for (let key in data) {
          if (data[key].length > 0) {
            downloadFile(data[key])
          }
        }
        selectThematics.value = [];
        datatable.value.clearSelection()
      }
    })
    .catch((error) => {
      console.error(error);
      Swal.fire({
        title: "Error",
        text: "Ocurrió un error al intentar exportar las intenciones.",
        icon: "error",
      });
      loading.value = false;
    });
};

const clickFileImport = async () => {
  importFile.value.click()
};

const changeFile = (e) => {
  let file = e.target.files[0];
  let reader = new FileReader();
  reader.onload = async () => {
    let jsonData = reader.result;

    if (isValidJson(jsonData)) {
      let data = { chatbot_id: chatbotId.value, data: JSON.parse(jsonData) }
      await importIntention(data);
    } else {
      Swal.fire({ title: "Error", text: "Ingrese un archivo valido.", icon: "error" });
    }

  };

  reader.readAsText(file);
  fileInputKey.value++;
}

const isValidJson = (jsonString) => {
  try {
    let jsonObject = JSON.parse(jsonString);
    return jsonObject.every(object => (
      object.hasOwnProperty('questions') &&
      object.hasOwnProperty('answers') &&
      object.hasOwnProperty('subject')
    ));
  } catch (error) {
    return false;
  }
};

const importIntention = async (data) => {
  loading.value = true;
  await axios
    .post(`/importIntentions`, data)
    .then((response) => {
      loading.value = false;
      loadData();

      Swal.fire({ title: "Excelente", text: "Intenciones cargadas correctamente!", icon: "success" });
    })
    .catch((error) => {
      console.error(error);
      if (error?.response?.data?.message == "some intentions were not imported") {
        Swal.fire({ title: "Atención", text: "Algunas intenciones no fueron importadas.", icon: "info" });
      } else {
        Swal.fire({ title: "Error", text: "Ocurrió un error al intentar importar las intenciones.", icon: "error" });
      }
      loading.value = false;
    });
}
</script>

<style scoped>
.btn_color {
  background-color: var(--primary-color) !important;
  color: #fff !important;
}

.btn-log {
  background-color: #fff !important;
  color: #444 !important;
  border: 1px solid #f5f8fa !important;
  margin-left: 1em;
}

.btn-datatable {
  height: 32px !important;
  min-width: 40px !important;

}

@media screen and (min-width: 1200px) {
  .btn-txt {
    display: flex;
  }
}


@media screen and (max-width: 1200px) and (min-width: 1075px) {
  .btn-datatable {
    height: 30px !important;
    width: 40px !important;
  }

  .btn-icon-data {
    font-size: 18px;
  }

  .btn-txt {
    display: none;
  }
}


@media screen and (max-width: 1075px) and (min-width: 580px) {
  .btn-datatable {
    height: 30px !important;
    width: 40px !important;
  }

  .btn-icon-data {
    font-size: 18px;
  }

  .btn-txt {
    display: none;
  }
}

@media screen and (max-width: 580px) {
  .btn-datatable {
    height: 30px !important;
    width: 45px !important;
  }

  .btn-icon-data {
    font-size: 18px;
  }

  .btn-txt {
    display: none;
  }
}
</style>
