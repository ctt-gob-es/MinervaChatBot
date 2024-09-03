<template>
  <div>
    <Loader :loading="loading" />
    <datatable class="tabla-m" :title="'Predeterminados'" :headers="headers" :items="settings"
      @click-reload="getSettings"
       :showSearch="true">
      <template v-slot:[`item.value`]="{ item }">
        <div v-if="item.type == 'texto' || item.type == 'Texto'">
          <div v-if="item.key === 'logo' || item.key === 'icono_bot' || item.key === 'icono_agente'">
            <img class="image-logo py-2" v-if="item.value" :src="item.value" />
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="70" height="70" viewBox="0 0 24 24"
              style="fill: rgba(0, 0, 0, 1); transform: ; msfilter: ">
              <path
                d="M12 2A10.13 10.13 0 0 0 2 12a10 10 0 0 0 4 7.92V20h.1a9.7 9.7 0 0 0 11.8 0h.1v-.08A10 10 0 0 0 22 12 10.13 10.13 0 0 0 12 2zM8.07 18.93A3 3 0 0 1 11 16.57h2a3 3 0 0 1 2.93 2.36 7.75 7.75 0 0 1-7.86 0zm9.54-1.29A5 5 0 0 0 13 14.57h-2a5 5 0 0 0-4.61 3.07A8 8 0 0 1 4 12a8.1 8.1 0 0 1 8-8 8.1 8.1 0 0 1 8 8 8 8 0 0 1-2.39 5.64z">
              </path>
              <path
                d="M12 6a3.91 3.91 0 0 0-4 4 3.91 3.91 0 0 0 4 4 3.91 3.91 0 0 0 4-4 3.91 3.91 0 0 0-4-4zm0 6a1.91 1.91 0 0 1-2-2 1.91 1.91 0 0 1 2-2 1.91 1.91 0 0 1 2 2 1.91 1.91 0 0 1-2 2z">
              </path>
            </svg>
          </div>
          <div v-else-if="item.key == 'color'">
            <div class="d-flex">
              <v-sheet class="my-auto pa-0" :color="item.value" elevation="1" height="12" width="12"></v-sheet>
              <p class="ml-1 mb-0 text-value-color">{{ item.value }}</p>
            </div>
          </div>
          <span v-else> {{ item.value }} </span>
        </div>
        <span v-if="item.type == 'predeterminado'"> {{ item.value }} </span>
        <span v-if="item.type == 'numero' || item.type == 'Numero'">{{
          item.value
        }}</span>
        <span v-if="item.type == 'mensaje'">{{ item.value }}</span>
        <span v-if="item.type == 'porcentaje' || item.type == 'Porcentaje'">{{ item.value }} </span>
        <span v-if="(item.type == 'activacion') ||
          item.type == 'Activacion' ||
          (item.type == 'idioma') ||
          item.type == 'Idioma'
          ">
          <span v-if="item.value == '0'" class="mdi mdi-close-circle red">
            Inactivo</span>
          <span v-else class="mdi mdi-check-circle" style="color: green">
            Activo</span>
        </span>
      </template>
      <template v-slot:[`item.actions`]="{ item }">
        <v-tooltip location="top" text="Editar" v-if="$can('default_chatbot_edit')">
          <template v-slot:activator="{ props }">
            <v-btn icon size="small" v-bind="props" @click.stop="dialogOpen(item)">
              <v-icon color="#a1a5b7"> mdi mdi-file-document-edit</v-icon>
            </v-btn>
          </template>
        </v-tooltip>
      </template>
    </datatable>

    <v-dialog v-model="dialogSettings" max-width="700">
      <v-form ref="form" v-model="validSettings" lazy-validation style="height: 100%">
        <v-card>
          <v-card-title>
            <span class="headline">Ajuste</span>
          </v-card-title>
          <v-card-subtitle>
            <span>{{ settingSelect.name }}</span>
          </v-card-subtitle>
          <v-card-text>

            <div class="d-flex flex-column" md12 v-if="newDefault">
              <span v-if="!settingSelect.type" class="mb-2">Por favor seleccione el tipo de configuración</span>
              <v-select v-model="settingSelect.type" :items="defaultTypes" label="Selecciona un tipo"
                variant="outlined"></v-select>
            </div>

            <div v-if="!newDefault || settingSelect.type">
              <div class="d-flex" md12 v-if="newDefault == true">
                <v-text-field v-model="settingSelect.name" label="Nombre" placeholder="Nombre" variant="outlined" />
              </div>

              <div class="d-flex" md12 v-if="settingSelect.type == 'texto' ||
                settingSelect.type == 'Texto'
                ">
                <div class="col-12" v-if="settingSelect.key == 'logo' || settingSelect.key === 'icono_bot' || settingSelect.key === 'icono_agente'">
                  <upload-image-component :value="settingSelect.value"
                    @upload-image="uploadImage"></upload-image-component>
                  <p v-if="errorImage" style="color: red">{{ errorImage }}</p>
                </div>
                <div class="col-12" v-else-if="settingSelect.key == 'color'" md12>
                  <Chrome v-model="settingSelect.value" style="width: 325px" :disableAlpha="true" class="mx-auto"
                    @update:model-value="updateValue" />
                </div>
                <v-text-field v-else v-model="settingSelect.value" label="Valor" placeholder="Valor" variant="outlined" />
              </div>
              <div class="d-flex w-100 flex-column justify-space-evenly gap-2" md12
                v-if="settingSelect.type == 'mensaje' || settingSelect.type == 'Mensaje'">
                <v-text-field v-model="settingSelect.value" label="Predeterminado"
                  placeholder="Valor predeterminado"></v-text-field>
              </div>
              <div class="d-flex" md12 v-if="settingSelect.type == 'predeterminado' ||
                settingSelect.type == 'Predeterminado'
                ">
                <v-autocomplete v-model="settingSelect.value" label="Idioma principal del Chat" :items="languagesOptions"
                  item-title="name" item-value="value"></v-autocomplete>
              </div>
              <div class="d-flex" md12 v-if="settingSelect.type == 'activacion' ||
                settingSelect.type == 'Activacion' ||
                settingSelect.type == 'idioma' ||
                settingSelect.type == 'Idioma'
                ">
                <v-switch :color="settingSelect.value === '1' ? 'success' : 'error'" v-model="settingSelect.value"
                  true-value="1" false-value="0">
                  <template v-slot:label="{ on }">
                    <span v-if="settingSelect.value === '1'" v-on="on">Activo</span>
                    <span v-else v-on="on">Inactivo</span>
                  </template>
                </v-switch>
              </div>
              <div class="d-flex" md12 v-if="settingSelect.type == 'numero' ||
                settingSelect.type == 'Numero'
                ">
                <v-text-field  v-model="settingSelect.value" type="number" />
              </div>
              <div class="d-flex porcentaje" md12 v-if="settingSelect.type == 'porcentaje' ||
                settingSelect.type == 'Porcentaje'
                ">
                    <v-slider
                  v-model="settingSelect.value"
                  @input="handleInput"
                  :max="60" :min="1" :step="1"     thumb-label="always"
                ></v-slider>
              </div>
            </div>
          </v-card-text>
          <v-card-actions class="mr-4">
            <v-spacer />
            <v-btn v-if="settingSelect.key == 'logo' || settingSelect.key === 'icono_bot' || settingSelect.key === 'icono_agente' " class="btn-search" @click="updateSettings()">
              Guardar
            </v-btn>
            <v-btn v-else class="btn-search" :disabled="!settingSelect.value || errorImage" @click="updateSettings()">
              Guardar
            </v-btn>
            <v-btn variant="tonal" class="black-close" text @click="closeDialog()">
              Cancelar
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

const props = defineProps({
  messages: { type: Object, required: false, default: null },
});

const settingSelect = ref({
  id: "",
  key: "",
  name: "",
  value: "",
  type: "",
});
const newSetting = ref(false);
const languagesOptions = ref([]);
const errorImage = ref(null);
const itemUpdate = ref(false);
const dialogSettings = ref(false);
const newDefault = ref(false);
const validSettings = ref(false);
const settings = ref([]);
const defaultTypes = ref([]);
const loading = ref(true);
const settingsJson = ref([
  {
    parameter_name: "Logo",
    parameter_value: "logo",
  },
  {
    parameter_name: "Icono Bot",
    parameter_value: "icono_bot",
  },
  {
    parameter_name: "Icono Agente",
    parameter_value: "icono_agente",
  },
  {
    parameter_name: "Color",
    parameter_value: "color",
  },
  {
    parameter_name: "Título",
    parameter_value: "titulo",
  },
  {
    parameter_name: "Inglés",
    parameter_value: "ingles",
  },
  {
    parameter_name: "Nivel precisión",
    parameter_value: "porcentaje_exito",
  },
  {
    parameter_name: "Módulo agente",
    parameter_value: "modulo_agente",
  }

]);
const headers = ref([
  { title: "Nombre", align: "start", sortable: true, key: "name" },
  { title: "Valor", align: "start", sortable: true, key: "value" },
  { title: "Opciones", align: "center", sortable: false, key: "actions" },
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
      .get("/getDefaults")
      .then((response) => {
        let data = [];
        if (
          response?.data?.success &&
          response?.data?.data &&
          response?.data?.data?.length > 0
        ) {
          data = response.data.data;
        }
        settings.value = data;
        defaultTypes.value = [];
        settings.value.forEach((setting) => {
          setting.name = setting.name.replace(/_/g, " ");
          setting.name = capitalizeWords(setting.name);
          if (!defaultTypes.value.includes(capitalizeWords(setting.type)) && setting.type !== 'predeterminado') {
            defaultTypes.value.push(capitalizeWords(setting.type));
          }
          if (setting.type == 'predeterminado') {
            setting.value = capitalizeWords(setting.value)
          }
          let object = settingsJson.value.find((item) => {
            return setting.name.toLowerCase() == item.parameter_value.replace(/_/g, " ");
          });
          if (object) {
            setting.name = object.parameter_name;
            setting.key = object.parameter_value;
          }
        });
        loading.value = false;
      })
      .catch((error) => {
        console.error(error);
        loading.value = false;
      })
      .finally(() => {
        languagesOptions.value = settings.value
          .filter(
            (item) =>
              item.type === "idioma" &&
              item.value == 1
          )
          .map((item) => ({
            name: item.name,
            value: item.name.toLowerCase(),
          }));
        loading.value = false;

      });
  } catch (error) {
    console.error(error);
  }
};

const updateSettings = async () => {
  if (settingSelect.value.id == "" || settingSelect.value.id == null) {
    try {
      let data;
      let settingRequest = {};
      await axios
        .post("storeDefault", settingSelect.value)
        .then((response) => {
          if (response?.data?.success) {
            Swall.fire({
              title: "Datos guardados ",
              icon: "success",
            });
          } else {
            Swall.fire({
              title: "Atención",
              html: `Ha ocurrido un error al guardar el parámetro <b>${settingSelect.value.name}</b>`,
              icon: "warning",
            });
          }
          dialogSettings.value = false;
          getSettings();
        })
        .catch((error) => {
          console.error(error);
        })
        .finally(() => {
          loading.value = false;

        });
    } catch (error) {
      console.error(error);
    }
  } else {
    try {
      let data;
      loading.value = true;
      await axios
        .put("updateDefault/" + settingSelect.value.id, settingSelect.value)
        .then((response) => {
          if (response?.data?.success) {
            Swall.fire({
              title: "Datos guardados ",
              icon: "success",
            });
          } else {
            Swall.fire({
              title: "Atención",
              text: response.data.message,
              icon: "warning",
            });
          }

          dialogSettings.value = false;
          getSettings();
        })
        .catch((error) => {
          console.error(error);
        })
        .finally(() => {
          loading.value = false;
        });
    } catch (error) {
      console.error(error);
    }
  }
};

const closeDialog = () => {
  dialogSettings.value = false;
  newDefault.value = false;
};

const dialogOpen = (item, newSetting = false) => {
  if (item) {
    itemUpdate.value = true;
    settingSelect.value = cloneDeep(item);
  } else {
    settingSelect.value = {
      id: "",
      key: "",
      name: "",
      value: "",
      type: "",
    };
    newDefault.value = true;
  }
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

const handleInput = () => {
  // Verificar si el valor está fuera del rango permitido
  if (settingSelect.value.type == "porcentage") {
    if (settingSelect.value.value < 0) {
      settingSelect.value.value = 0;
    } else if (settingSelect.value.value > 100) {
      settingSelect.value.value = 100;
    }
  }
};

const capitalizeWords = (str) => {
  return str.charAt(0).toUpperCase() + str.slice(1);
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

#app .v-dialog__content {
  position: absolute !important;
}

.text-value-color {
  padding: 0;
  margin: 0;
  font-weight: 700;
}

.image-logo {
  max-width: 230px;
  max-height: 90px;
}

.input__number {
  background-color: rgb(243, 243, 243);
  display: flex;
  flex-direction: row;
  padding: 5px;
  justify-content: center;
  text-align: center;
  border-radius: 15px;
  border-bottom: 1px solid rgb(131, 131, 131);
}

.porcentaje {
  display: flex;
  flex-direction: row;
  align-items: center;
}
</style>
