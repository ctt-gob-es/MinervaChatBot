<template>
  <div>
    <div style="padding-bottom: 50px">
      <Loader :loading="loading" />
      <!-- <DateRange
        class="mt-7"
        @submit="loadData"
        @updateDate="setDate"
        @clearData="clearData"
        :initialFilter="false"
      /> -->
      <Datatable
        ref="datatable"
        class="tabla-m mt-7"
        :title="'Ajustes generales cliente'"
        :button_reload="true"
        :headers="headers"
        :items="dataSettings"
        @click-reload="loadData()"
        :showSearch="true"
        :button_add="$can('add_settings')"
        :titleAdd="'Agregar ajuste personalizado'"
        @click-add="dialogOpen(), selectedImage = null"
        :view_default="$can('system_settings') || $can('default_chatbot_settings')"
      >
        <template v-slot:[`item.option`]="{ item }">
          <v-container>
            <v-row align="center" justify="center">
              <v-col cols="auto" v-if="$can('edit_settings')">
                <v-tooltip text="Tooltip" location="top">
                  <template v-slot:activator="{ props }">
                    <v-btn icon size="small" v-bind="props" @click="editarVisible(item)">
                      <v-icon color="#a1a5b7">mdi mdi-file-document-edit</v-icon></v-btn>
                  </template>
                  <span>Editar</span>
                </v-tooltip>
              </v-col>
              <v-col cols="auto" v-if="$can('delete_settings')">
                <v-tooltip text="Tooltip" location="top">
                  <template v-slot:activator="{ props }">
                    <v-btn icon size="small" v-bind="props" @click="showDelete(item.city_council_id)">
                      <v-icon color="#a1a5b7">mdi mdi-trash-can</v-icon>
                    </v-btn>
                  </template>
                  <span>Eliminar</span>
                </v-tooltip>
              </v-col>
            </v-row>
          </v-container>
        </template>
      </Datatable>
      <v-dialog width="400" v-model="dialogVisible">
        <v-card title="Ajustes personalizados de cliente" :subtitle="titleName">
          <v-card-text class="pb-0">
            <form class="pb-0">
              <v-autocomplete v-if="!editData" density="compact" variant="outlined" auto-select-first label="Cliente"
                :items="customers" item-title="name" item-value="id" v-model="initialState.city_council_id"
                :error-messages="errorMessages"></v-autocomplete>
              <v-divider :thickness="5" color="success" inset></v-divider>
              <div class="my-2">
                <label for="">Logo</label>
                <label for="fileInput" class="position-relative col-12">
                  <v-img :src="selectedImage ? selectedImage : img_banner" class="m-auto cursor-pointer" width="100"
                    height="100"></v-img>
                  <input id="fileInput" type="file" class="top-0 start-0 h-100 w-100 opacity-0"
                    @change="onFileSelected" />
                </label>
              </div>
              <v-textarea label="Pie de página" variant="outlined" v-model="initialState.pie_de_página" counter
                rows="2"></v-textarea>
              <div>
                <label for="">Color</label>
                <v-color-picker v-model="initialState.color" :swatches="swatches" class="ma-2"
                  show-swatches></v-color-picker>
              </div>
            </form>
          </v-card-text>
          <v-card-actions class="pt-0 mr-4">
            <v-spacer></v-spacer>
            <v-btn variant="elevated" :color="global.color" @click="saveData">Guardar</v-btn>
            <v-btn variant="tonal" class="black-close" @click="dialogClose">Cancelar</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from "vue";
import moment from "moment";
import axios from "axios";
import { formatDateTime } from "@/helpers";
import img_banner from "../../../../public/images/upload.png";
import Datatable from "../utilities/Datatable.vue";
import DateRange from "../utilities/DateRange.vue";
import Loader from "../utilities/Loader.vue";
import Swal from "sweetalert2";
import { useGlobalStore } from "../store/global";
const global = useGlobalStore();

const selectedImage = ref(null);
const loading = ref(false);
const dateFrom = ref(null);
const dateTo = ref(null);
const id_city = ref(null);
const dataSettings = ref([]);
const customers = ref([]);
const dialogVisible = ref(false);
const titleName = ref("");
const editData = ref(false);

const errorMessages = ref("");

const initialState = reactive({
  city_council_id: null,
  logo: null,
  color: null,
  pie_de_página: null,
});

const swatches = [
  ["#FF0000", "#AA0000", "#550000"],
  ["#FFFF00", "#AAAA00", "#555500"],
  ["#00FF00", "#00AA00", "#005500"],
  ["#00FFFF", "#00AAAA", "#005555"],
  ["#0000FF", "#0000AA", "#000055"],
];

const headers = ref([
  {
    title: "Ciudad",
    align: "start",
    sortable: true,
    key: "city_council.name",
  },
  {
    title: "Fecha de creación",
    align: "center",
    sortable: true,
    key: "created_at",
  },
  {
    title: "Fecha de modificación",
    align: "center",
    sortable: true,
    key: "updated_at",
  },
  {
    title: "Opciones",
    align: "center",
    sortable: true,
    key: "option",
  },
]);

onMounted(async () => {
  id_city.value = localStorage.getItem("id_city");
  await loadData();
});

const editarVisible = async (item) => {
  titleName.value = item.city_council.name;
  const id = item.city_council.id;
  loading.value = true;
  editData.value = true;
  dialogVisible.value = true;
  try {
    const response = await axios.get(`/editCityCouncilSetting/${id}`);
    initialState.city_council_id = response.data.data[0].city_council_id;
    response.data.data.forEach((setting) => {
      switch (setting.setting.name) {
        case "logo":
          initialState.logo = setting.value;
          if (setting.value != null) {
            selectedImage.value =
              "/support/logoCityCouncilSetting/" + setting.value;
          } else {
            selectedImage.value = null;
          }

          break;
        case "color":
          initialState.color = setting.value;
          break;
        case "pie_de_página":
          initialState.pie_de_página = setting.value;
          break;
        default:
          break;
      }
    });
  } catch (error) {
    console.error(error);
  }
  loading.value = false;
};

const dialogOpen = async () => {
  editData.value = false;
  dialogVisible.value = true;
  clearFields();
};
const dialogClose = async () => {
  dialogVisible.value = false;
  clearFields();
};

const loadData = async () => {
  loading.value = true;
  await getSettings();
  await getCustomers();
};

const onFileSelected = (event) => {
  const file = event.target.files[0];
  if (file) {
    initialState.logo = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      selectedImage.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const getCustomers = async () => {
  await axios
    .get("/getAllCity")
    .then((response) => {
      customers.value = response.data.data;
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => { });
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
      deleteSetting(id);
    }
  });
};

const deleteSetting = async (id) => {
  loading.value = true;
  await axios
    .delete(`/deleteSettings/${id}`)
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
      console.error(error);
    });
};

const setDate = (from, to) => {
  if (from) dateFrom.value = from;
  if (to) dateTo.value = to;
};

const clearData = async () => {
  dateFrom.value = null;
  dateTo.value = null;
  await loadData();
};

const getSettings = async () => {
  let from = null;
  let to = null;
  if (dateFrom.value !== null) {
    if (dateTo.value === null) {
      Swal.fire({
        title: "Atención!",
        text: "Debes seleccionar la fecha (Hasta)",
        icon: "warning",
      });
      loading.value = false;
      return;
    }
  }
  if (dateFrom.value && dateTo.value) {
    from = moment(dateFrom.value, "YYYY-MM-DD").format("YYYY-MM-DD");
    to = moment(dateTo.value, "YYYY-MM-DD").format("YYYY-MM-DD");
  }

  try {
    const response = await axios.get("/getSettingsDta", {
      params: {
        ...(from && to && { from, to }),
        id_city: id_city.value,
      },
    });
    dataSettings.value = response.data.data.map((intention) => ({
      ...intention,
      created_at: formatDateTime(intention.created_at),
      updated_at: formatDateTime(intention.updated_at),
    }));
  } catch (error) {
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const clearFields = async () => {
  initialState.city_council_id = null;
  initialState.logo = null;
  initialState.color = null;
  initialState.pie_de_página = null;
  titleName.value = "";
};

const saveData = async () => {
  const config = {
    headers: {
      "content-type": "multipart/form-data",
    },
  };
  if (!editData.value) {
    if (!initialState.city_council_id) {
      Swal.fire({
        title: "Atención!",
        text: "Por favor seleccione un cliente",
        icon: "warning",
      });
      errorMessages.value = "Cliente es requerido";
      return;
    } else {
      errorMessages.value = "";
    }
    try {
      const response = await axios.post("saveSettings", initialState, config);
      if (response.data.success) {
        Swal.fire({
          title: "Excelente",
          text: "Cambios realizados!",
          icon: "success",
        });
        dialogClose();
        loadData();
        loading.value = false;
      } else {
        Swal.fire({
          title: "Atención!",
          text: "No se puede realizar el cambio",
          icon: "warning",
        });
      }
    } catch (error) {
      console.error(error);
      loading.value = false;
    }
  } else {
    await axios
      .post(`/updateSettingsCityCouncil`, initialState, config)
      .then((response) => {
        if (response.data.success) {
          Swal.fire({
            title: "Excelente",
            text: "Cambios realizados!",
            icon: "success",
          });
          dialogClose();
          loadData();
          loading.value = false;
        } else {
          Swal.fire({
            title: "Atención!",
            text: "No se puede realizar el cambio",
            icon: "warning",
          });
        }
      })
      .catch((error) => {
        console.error(error);
        loading.value = false;
      });
  }
};
</script>

<style scoped></style>
