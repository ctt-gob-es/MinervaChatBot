<template>
  <div>
    <Loader :loading="loading" />
    <datatable class="tabla-m" :title="'Ajustes predeterminados del sistema'" :headers="headers" :items="settings"
      @click-reload="getSettings" :showSearch="true">
      <template v-slot:[`item.actions`]="{ item }">
        <v-tooltip location="top" text="Editar" v-if="$can('edit_default_settings')">
          <template v-slot:activator="{ props }">
            <v-btn icon size="small" v-bind="props" @click.stop="dialogOpen(item)">
              <v-icon color="#a1a5b7"> mdi mdi-file-document-edit</v-icon>
            </v-btn>
          </template>
        </v-tooltip>
      </template>
      <template v-slot:[`item.value`]="{ item }">
        <div v-if="item.key === 'logo'">
          <img class="image-logo py-2" v-if="item.value" :src="item.value" />
        </div>
        <div v-else-if="item.key == 'imagen_login'">
          <img class="image_banner_login py-2" v-if="item.value !== null" :src="item.value" />
        </div>
        <div v-else-if="item.key == 'color'">
          <div class="d-flex">
            <v-sheet class="my-auto pa-0" :color="item.value" elevation="1" height="12" width="12"></v-sheet>
            <p class="ml-1 mb-0 text-value-color">{{ item.value }}</p>
          </div>
        </div>
        <div v-else-if="item.key == 'recuperar_clave'">
          <span v-if="item.value == '0'" class="mdi mdi-close-circle red">
            Inactivo
            <v-tooltip text="Para poder hacer uso de ésta función debe configurar el envío de correos">
              <template v-slot:activator="{ props }">
                <v-icon v-bind="props" class="mdi mdi-information info-icon"></v-icon>
              </template>
            </v-tooltip>
          </span>
          <span v-else class="mdi mdi-check-circle" style="color: green">
            Activo
            <v-tooltip text="Para poder hacer uso de ésta función debe configurar el envío de correos">
              <template v-slot:activator="{ props }">
                <v-icon v-bind="props" class="mdi mdi-information info-icon"></v-icon>
              </template>
            </v-tooltip>
          </span>
        </div>
        <div v-else>
          {{ item.value }}
        </div>

      </template>
    </datatable>
    <v-dialog v-model="dialogSettings" max-width="650">
      <v-form ref="form" v-model="validSettings" lazy-validation style="height: 100%">
        <v-card>
          <v-card-title>
            <span class="headline">Ajuste</span>
          </v-card-title>
          <v-card-subtitle>
            <span>{{ settingSelect.name }}</span>
          </v-card-subtitle>
          <v-card-text>
            <div v-if="settingSelect.key == 'logo' || settingSelect.key == 'imagen_login'">
              <upload-image-component :value="settingSelect.value" @upload-image="uploadImage"></upload-image-component>
              <p v-if="errorImage" style="color: red">{{ errorImage }}</p>
            </div>
            <div class="d-flex" v-else-if="settingSelect.key == 'color'" md12>
              <Chrome v-model="settingSelect.value" style="width: 325px" :disableAlpha="true" class="mx-auto"
                @update:model-value="updateValue" />
            </div>
            <div class="d-flex justify-center col-12" md12 v-else-if="settingSelect.key == 'recuperar_clave'">
                <v-switch class="col-6" :color="settingSelect.value === '1' ? 'success' : 'error'"
                  v-model="settingSelect.value" true-value="1" false-value="0">
                  <template v-slot:label="{ on }">
                    <span v-if="settingSelect.value === '1'" v-on="on">Activo</span>
                    <span v-else v-on="on">Inactivo</span>
                  </template>
                </v-switch>
            </div>
            <div class="d-flex" md12 v-else>
              <v-text-field v-model="settingSelect.value" label="Valor" placeholder="Valor" variant="outlined" />
            </div>
          </v-card-text>
          <v-card-actions class="mr-4">
            <v-spacer />
            <v-btn v-if="settingSelect.key == 'logo'" class="btn-search" @click="updateSettings()">
              Guardar
            </v-btn>
            <v-btn v-else text="guardar" class="btn-search" :disabled="!settingSelect.value || errorImage"
              @click="updateSettings()">
            </v-btn>
            <v-btn class="black-close" text="cancelar" @click="closeDialog()">
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-form>
    </v-dialog>
  </div>
</template>

<script setup>
import axios from "axios";
import { ref, onMounted } from "vue";
import Datatable from "../utilities/Datatable.vue";
import Swall from "sweetalert2";
import { cloneDeep } from "lodash";
import { Chrome } from "@ckpack/vue-color";
import uploadImageComponent from "./uploadImage.vue";
import Loader from "../utilities/Loader.vue";

const settingSelect = ref({
  key: "",
  name: "",
  value: "",
});
const errorImage = ref(null);
const itemUpdate = ref(false);
const dialogSettings = ref(false);
const validSettings = ref(false);
const settings = ref([]);
const loading = ref(true);
const headers = ref([
  { title: "Nombre", align: "start", sortable: true, key: "name" },
  { title: "Valor", align: "start", sortable: true, key: "value" },
  { title: "Opciones", align: "center", sortable: false, key: "actions" },
]);
const settingsJson = ref([
  {
    parameter_name: "Logo",
    parameter_value: "logo",
  },
  {
    parameter_name: "Color",
    parameter_value: "color",
  },
  {
    parameter_name: "Pie de página",
    parameter_value: "pie_de_página",
  },
  {
    parameter_name: "Título del login",
    parameter_value: "titulo_login",
  },
  {
    parameter_name: "Subtítulo del login",
    parameter_value: "subtitulo_login",
  },
  {
    parameter_name: "Imagen del login",
    parameter_value: "imagen_login",
  },
  {
    parameter_name: "Recuperar clave",
    parameter_value: "recuperar_clave",
  },
]);

const updateValue = async (event) => {
  settingSelect.value.value = event.hex;
};

const loadData = async () => {
  await getSettings();
};

const getSettings = async () => {
  try {
    loading.value = true;
    await axios
      .get("/getSettings")
      .then((response) => {
        let data = [];
        if (
          response?.data?.success &&
          response?.data?.data &&
          response?.data?.data?.length > 0
        ) {
          response.data.data.forEach((element) => {
            let object = settingsJson.value.find((names) => {
              return element.name == names.parameter_value;
            });
            if (object) {
              element.name = object.parameter_name;
              element.key = object.parameter_value;
            }
          });
          data = response.data.data;
        }
        settings.value = data;
        loading.value = false;
      })
      .catch((error) => {
        console.error(error);
        loading.value = false;
      });
  } catch (error) {
    console.error(error);
  }
};

const updateSettings = async () => {
  try {
    let data;
    let settingRequest = {};
    settingRequest[settingSelect.value.key] = settingSelect.value.value;
    loading.value = true;
    await axios
      .put("updateSettings", settingSelect.value)
      .then((response) => {
        if (response?.data?.success) {
          Swall.fire({
            title: "Correcto!",
            icon: "success",
            text: "Ajuste actualizado!",
          });
        } else {
          Swall.fire({
            title: "Atención",
            html: `Ha ocurrido un error al guardar el parámetro <b>${settingSelect.value.name}</b>`,
            icon: "warning",
          });
        }
        getSettings();
        dialogSettings.value = false;
        loading.value = false;
      })
      .catch((error) => {
        console.error(error);
        loading.value = false;
      });
  } catch (error) {
    console.error(error);
  }
};

const closeDialog = () => {
  dialogSettings.value = false;
};

const dialogOpen = (item) => {
  itemUpdate.value = true;
  settingSelect.value = cloneDeep(item);
  dialogSettings.value = true;
};

const uploadImage = (image) => {
  if (typeof image !== "boolean") {
    errorImage.value = null;
    if (!image) return (settingSelect.value.value = null);
    settingSelect.value.value = image;
  } else {
    errorImage.value = "La imagen excede el tamaño máximo permitido de 5 MB";
  }
};

onMounted(() => {
  loadData();
});
</script>
<style>
.btn-search {
  background: var(--primary-color) !important;
  color: white !important;
}

.black-close {
  background: rgb(103, 100, 100) !important;
  color: white !important;
}

.v-dialog {
  box-shadow: 1px 3px 9px 1px rgb(0 0 0 / 20%);
}

.image-logo {
  max-width: 230px;
  max-height: 90px;
}

.image_banner_login {
  width: 300px;
  max-width: 300px;
  max-height: auto;
  object-fit: cover;
}
</style>
