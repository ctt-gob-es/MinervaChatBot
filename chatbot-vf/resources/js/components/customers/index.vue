<template>
  <div style="padding-bottom: 50px">
    <Loader :loading="loading" />
    <DateRange class="mt-7" @submit="loadData" @updateDate="setDate" @clearData="clearData" :initialFilter="false" />
    <Datatable ref="datatable" class="tabla-m" :title="'Clientes'" :button_add="$can('clients_add')"
      :titleAdd="'Agregar cliente'" :button_reload="true" :headers="headersClients" :items="cityCouncils"
      @click-reload="loadData()" :showSearch="true" @click-add="dialogOpen(), (titleModal = 'Nuevo cliente')">
      <template v-slot:[`item.option`]="{ item }">
        <v-container>
          <v-row align="center" justify="center">
            <v-col cols="auto" v-if="roleUser === 'SuperAdmin'">
              <v-tooltip text="Tooltip" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="getAdminClient(item.id)">
                    <v-icon color="#a1a5b7">mdi mdi-account-cog</v-icon>
                  </v-btn>
                </template>
                <span>Usuarios</span>
              </v-tooltip>
            </v-col>
            <v-col cols="auto" v-if="$can('clients_chatbot')">
              <v-tooltip text="Tooltip" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" :href="`/chatbots/${item.id}`" class="no-underline">
                    <v-icon color="#a1a5b7">mdi mdi-robot</v-icon>
                  </v-btn>
                </template>
                <span>Chatbots</span>
              </v-tooltip>
            </v-col>
            <v-col cols="auto" v-if="$can('clients_edit')">
              <v-tooltip text="Tooltip" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="
                    dialogOpenEdit(item.id), (titleModal = 'Editar cliente')
                    ">
                    <v-icon color="#a1a5b7">mdi mdi-file-document-edit</v-icon></v-btn>
                </template>
                <span>Editar</span>
              </v-tooltip>
            </v-col>
            <v-col cols="auto">
              <v-tooltip text="Tooltip" location="top" v-if="$can('clients_delete')">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="showDelete(item.id)">
                    <v-icon color="#a1a5b7">mdi mdi-trash-can</v-icon>
                  </v-btn>
                </template>
                <span>Eliminar</span>
              </v-tooltip>
            </v-col>
            <v-col cols="auto" v-if="$can('clients_ajusts')">
              <v-tooltip text="Tooltip" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="openSetting(item)">
                    <v-icon color="#a1a5b7">mdi mdi-cog</v-icon>
                  </v-btn>
                </template>
                <span>Ajustes</span>
              </v-tooltip>
            </v-col>
          </v-row>
        </v-container>
      </template>
    </Datatable>

    <v-dialog width="900" v-model="dialogVisible">
      <v-card :title="titleModal">
        <v-card-text class="pb-0">
          <form method="dialog">
            <v-text-field v-model="initialState.name" label="Nombre" required variant="outlined"
              :error-messages="errorMessages.name"></v-text-field>
            <v-textarea v-model="initialState.information" label="Información" variant="outlined"></v-textarea>
            <Datatable v-if="dtClients" ref="datatable" class="tabla-m" :title="'Usuarios'" :headers="headers_admin"
              :items="admin" :showSearch="true" @changeSelection="changeSelection" :initSelection="selectAdmin"
              :enableSelect="true" return-object>
            </Datatable>
            <v-text-field v-else v-model="initialState.admin" label="Administrador" required variant="outlined"
              :error-messages="errorMessages.manage_id" disabled></v-text-field>
          </form>
        </v-card-text>
        <v-card-actions class="mt-2 mr-4">
          <v-spacer></v-spacer>
          <v-btn variant="elevated" @click="saveData" :loading="loadingSave" :color="global.color">
            Guardar
          </v-btn>
          <v-btn variant="tonal" @click="dialogClose" class="black-close">
            Cancelar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <!--Modal admin-->
    <v-dialog width="1200" v-model="dialogAdmin">
      <v-card>
        <v-card-text>
          <Datatable ref="datatable" class="tabla-m" :title="'Usuarios'" :headers="headers_adminCli"
            :items="dataAdminClient" :showSearch="true">
          </Datatable>
        </v-card-text>
        <v-card-actions class="mr-4">
          <v-spacer></v-spacer>
          <v-btn variant="tonal" text="Cancelar" class="black-close" @click="dialogAdmin = false"></v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!--Modal ajustes-->
    <v-dialog width="400" v-model="openDialogSetting">
      <v-card title="Ajustes del cliente" :subtitle="titleName">
        <v-card-text class="pb-0">
          <form class="p-3 pb-0">
            <div class="my-2">
              <label for="">Logo</label>
              <label for="fileInput" class="position-relative col-12">
                <v-img :src="selectedImage ? selectedImage : img_banner" class="m-auto"
                  style="cursor: pointer; object-fit: cover" width="100" height="100"></v-img>
                <input id="fileInput" type="file" class="position-absolute top-0 start-0 h-100 w-100 opacity-0"
                  @change="onFileSelected" />
              </label>
            </div>
            <v-textarea label="Pie de página" variant="outlined" v-model="initialStateSetting.pie_de_página" counter
              rows="2"></v-textarea>
            <div class="my-2">
              <label for="">Color</label>
              <v-color-picker v-model="initialStateSetting.color" :swatches="swatches" class="ma-2"
                show-swatches></v-color-picker>
            </div>
          </form>
        </v-card-text>
        <v-card-actions class="pt-0 mr-4">
          <v-spacer></v-spacer>
          <v-btn variant="elevated" :color="global.color" @click="saveDataSetting">Guardar</v-btn>
          <v-btn variant="tonal" class="black-close" @click="closeSetting">Cancelar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed } from "vue";
import moment from "moment";
import { useRouter } from "vue-router";
import { formatDateTime } from "@/helpers";
import Datatable from "../utilities/Datatable.vue";
import DateRange from "../utilities/DateRange.vue";
import Loader from "../utilities/Loader.vue";
import axios from "axios";
import Swal from "sweetalert2";
import img_banner from "../../../../public/images/upload.png";
import { useGlobalStore } from "../store/global";
const global = useGlobalStore();

const selectedImage = ref(null);
const dateFrom = ref(null);
const dateTo = ref(null);
const id_city = ref(null);
const loading = ref(false);
const openDialogSetting = ref(false);
const roleUser = ref(null);
const dtClients = ref(false);
const admin = ref([]);
const dataAdminClient = ref([]);
const selectAdmin = ref([]);
const loadingSave = ref(false);
const dialogVisible = ref(false);
const dialogAdmin = ref(false);
const cityCouncils = ref([]);
const dataAdmin = ref([]);
const titleName = ref("");

const swatches = [
  ["#FF0000", "#AA0000", "#550000"],
  ["#FFFF00", "#AAAA00", "#555500"],
  ["#00FF00", "#00AA00", "#005500"],
  ["#00FFFF", "#00AAAA", "#005555"],
  ["#0000FF", "#0000AA", "#000055"],
];

const errorMessages = reactive({
  name: null,
  manage_id: null,
});
const headers_admin = ref([
  { title: "", align: "start", sortable: true, width: "50px" },
  { title: "Nombre", align: "start", sortable: true, key: "name" },
  { title: "Rol", align: "start", sortable: true, key: "rol" },
]);
const titleModal = ref("Nuevo cliente");
const router = useRouter();

const headers = ref([
  { title: "Nombre", align: "start", sortable: true, key: "name" },
  { title: "Información", align: "start", sortable: true, key: "information" },
  { title: "Administrador", align: "start", sortable: true, key: "admin_name" },
  {
    title: "Creador / último modificador",
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
  { title: "Opciones", align: "center", sortable: true, key: "option" },
]);

const headersClients = computed(() => {
  return roleUser.value === "SuperAdmin"
    ? headers.value.filter((i) => i.title !== "Administrador")
    : headers.value;
});
const headers_adminCli = ref([
  { title: "#", align: "start", sortable: true, key: "number" },
  { title: "Nombre", align: "start", sortable: true, key: "name" },
  { title: "Rol", align: "start", sortable: true, key: "rol" },
]);
const initialState = reactive({
  id: null,
  name: "",
  information: null,
  admin: null,
});

const initialStateSetting = reactive({
  city_council_id: null,
  logo: null,
  color: null,
  pie_de_página: null,
});

const clearData = async () => {
  dateFrom.value = null;
  dateTo.value = null;
  await loadData();
};

const openSetting = async (item) => {
  titleName.value = item.name;
  initialStateSetting.city_council_id = item.id;
  loading.value = true;
  openDialogSetting.value = true;
  try {
    const response = await axios.get(
      `/editCityCouncilSetting/${initialStateSetting.city_council_id}`
    );
    if (
      response &&
      response.data &&
      response.data.data &&
      response.data.data.length > 0
    ) {
      response.data.data.forEach((setting) => {
        switch (setting.setting.name) {
          case "logo":
            if (setting.value != null) {
              selectedImage.value =
                "/support/logoCityCouncilSetting/" + setting.value;
            } else {
              selectedImage.value = null;
            }
            break;
          case "color":
            initialStateSetting.color = setting.value;
            break;
          case "pie_de_página":
            initialStateSetting.pie_de_página = setting.value;
            break;
          default:
            break;
        }
      });
    }
  } catch (error) {
    console.error(error);
  }
  loading.value = false;
};

const onFileSelected = (event) => {
  const file = event.target.files[0];
  if (file) {
    initialStateSetting.logo = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      selectedImage.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};
const closeSetting = () => {
  openDialogSetting.value = false;
  clearFieldsSetting();
};
const changeSelection = (e) => {
  selectAdmin.value = [];
  e.forEach((item) => {
    const id = item.id;
    selectAdmin.value.push({ id });
  });
};
const dialogOpen = async () => {
  dialogClose();
  loading.value = true;
  selectAdmin.value = [];
  await getDataAdmin();
  dialogVisible.value = true;
};
const dialogOpenEdit = async (id) => {
  loading.value = true;
  await axios
    .get(`/editCityCouncils/${id}`)
    .then((response) => {
      initialState.id = response.data.data.id;
      initialState.name = response.data.data.name;
      initialState.information = response.data.data.information;
      getDataAdmin();
      if (roleUser.value !== "SuperAdmin") {
        selectAdmin.value = [];
        const id = response.data.manage[0].id;
        selectAdmin.value.push({ id });
        initialState.admin = response.data.manage[0].name;
      } else {
        dtClients.value = true;
        selectAdmin.value = response.data.manage;
      }
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
      loading.value = false;
      dialogVisible.value = true;
    });
};
const dialogClose = async () => {
  dialogVisible.value = false;
  await loadData();
};
const clearFields = () => {
  initialState.id = null;
  initialState.name = "";
  initialState.information = "";
  initialState.id = null;
};
const clearFieldsSetting = () => {
  initialStateSetting.city_council_id = null;
  initialStateSetting.logo = null;
  initialStateSetting.color = null;
  initialStateSetting.pie_de_página = null;
  selectedImage.value = null;
  titleName.value = "";
};
onMounted(async () => {
  await loadData();
  id_city.value = localStorage.getItem("id_city");
});

const setDate = (from, to) => {
  if (from) dateFrom.value = from;
  if (to) dateTo.value = to;
};

const getDataAdmin = async () => {
  try {
    const response = await axios.get("/getDataAdmin");
    dataAdmin.value = response.data.users;
    if (response.data.userRole !== "SuperAdmin") {
      selectAdmin.value = [];
      const id = response.data.users[0].id;
      selectAdmin.value.push({ id });
    } else {
      dtClients.value = true;
      admin.value = response.data.users;
    }
  } catch (error) {
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const loadData = async () => {
  loading.value = true;
  await getCityCouncils();
  clearFields();
};

const getCityCouncils = async () => {
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
    const response = await axios.get("/getAllCityCouncils", {
      params: {
        ...(from && to && { from, to }),
      },
    });
    roleUser.value = response.data.role;
    cityCouncils.value = response.data.data.map((intention) => ({
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
const getAdminClient = async ($id) => {
  dialogAdmin.value = true;
  try {
    const response = await axios.get("/getAdminClient/" + $id);
    const data = response.data.map((item, index) => ({
      ...item,
      number: index + 1,
    }));
    dataAdminClient.value = data;
  } catch (error) {
    console.error(error);
  } finally {
    loading.value = false;
  }
};
const validateData = () => {
  if (initialState.name.trim() === "") {
    errorMessages.name = "Este campo es obligatorio.";
    return false;
  } else {
    errorMessages.name = "";
  }
  if (!Object.keys(selectAdmin.value).length) {
    Swal.fire({
      title: "Atención!",
      text: "Debes seleccionar como mínimo un administrador.",
      icon: "warning",
    });
    return false;
  }
  return true;
};
const saveData = () => {
  if (titleModal.value === "Nuevo cliente") {
    setCustomer();
  } else {
    updateCustomer();
  }
};
const setCustomer = async () => {
  if (!validateData()) {
    return;
  }
  loadingSave.value = true;
  const formData = {
    initialState: initialState,
    admin: JSON.stringify(selectAdmin.value),
  };
  await axios
    .post("/saveCityCouncils", formData)
    .then((response) => {
      Swal.fire({
        title: "Correcto!",
        text: response.data.message,
        icon: "success",
      });
      dialogClose();
    })
    .catch((error) => {
      console.error(error);
      Swal.fire({
        title: "Atención!",
        text: error.response.data.message,
        icon: "warning",
      });
    })
    .finally(() => {
      loadingSave.value = false;
    });
};
const updateCustomer = async () => {
  if (!validateData()) {
    return;
  }
  const formData = {
    initialState: initialState,
    admin: JSON.stringify(selectAdmin.value),
  };
  loadingSave.value = true;
  await axios
    .put(`/updateCityCouncils/${initialState.id}`, formData)
    .then((response) => {
      Swal.fire({
        title: "Correcto!",
        text: response.data.message,
        icon: "success",
      });
      dialogClose();
    })
    .catch((error) => {
      Swal.fire({
        title: "Atención!",
        text: error.response.data.message,
        icon: "warning",
      });
      console.error(error);
    })
    .finally(() => {
      loadingSave.value = false;
    });
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
      deleteCityCou(id);
    }
  });
};

const deleteCityCou = async (id) => {
  loading.value = true;
  await axios
    .delete(`/deleteCityCouncils/${id}`)
    .then((response) => {
      dialogClose();
      Swal.fire({
        title: "Excelente!",
        text: "Cambios realizados!",
        icon: "success",
      });
      loading.value = false;
      loadData();
    })
    .catch((error) => {
      loading.value = false;
      Swal.fire({
        title: "Error",
        text: error.response.data.message,
        icon: "error",
      });
      console.error(error);
    });
};

const saveDataSetting = async () => {
  const config = {
    headers: {
      "content-type": "multipart/form-data",
    },
  };
  try {
    const response = await axios.post(
      "saveOrUpdateSettings",
      initialStateSetting,
      config
    );
    if (response.data.success) {
      Swal.fire({
        title: "Excelente",
        text: "Cambios realizados!",
        icon: "success",
      });
      loadData();
      closeSetting();
      clearFieldsSetting();
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
};
</script>

<style scoped>
.logo {
  display: block;
  margin: 0 auto 2rem;
}

@media (min-width: 1024px) {
  #sample {
    display: flex;
    flex-direction: column;
    place-items: center;
    width: 1000px;
  }
}

.no-underline {
  text-decoration: none !important;
  /* Anula el subrayado */
}
</style>
