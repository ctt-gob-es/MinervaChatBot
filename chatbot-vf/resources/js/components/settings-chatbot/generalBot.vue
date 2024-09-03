<template>
  <div>
    <Loader :loading="loading" />
    <Datatable ref="datatable" class="tabla-m" :button_export="false" :title="'General'" :headers="headers"
      :items="ajustes" :showSearch="true" :button_reload="true" @click-reload="loadData">
      <template v-slot:[`item.value`]="{ item }">
        <div v-if="item.type == 'texto' || item.type == 'Texto'">
          <div v-if="item.key == 'titulo'">
            <span v-if="item.languages.length == 0">
              {{ item.value }}
             </span>
             <div class="d-flex flex-column"v-else>

                 <div v-for="langValue in item.languages" :key="langValue.value">
                   <span v-if="languagesOptions.some(langOpt=>langOpt.value == langValue.language)">
                     <img v-if="langValue.language == 'castellano'" src="../../../images/Castellano.png" alt="" width="30" />
                     <img v-if="langValue.language == 'ingles'" src="../../../images/Ingles.png" alt="" width="30" />
                     <img v-if="langValue.language == 'valenciano'" src="../../../images/Valenciano.png" alt="" width="30" />
                     {{ langValue.value }}
                   </span>
                 </div>
             </div>
            </div>

          <div v-else-if="item.key === 'logo' || item.key === 'icono_bot' || item.key === 'icono_agente'">
            <img class="image-logo py-2" v-if="item.value && item.deleted_at_switch" :src="item.value" />
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="70" height="70" viewBox="0 0 24 24"
              style="fill: rgba(0, 0, 0, 1); msfilter: ">
              <path
                d="M12 2A10.13 10.13 0 0 0 2 12a10 10 0 0 0 4 7.92V20h.1a9.7 9.7 0 0 0 11.8 0h.1v-.08A10 10 0 0 0 22 12 10.13 10.13 0 0 0 12 2zM8.07 18.93A3 3 0 0 1 11 16.57h2a3 3 0 0 1 2.93 2.36 7.75 7.75 0 0 1-7.86 0zm9.54-1.29A5 5 0 0 0 13 14.57h-2a5 5 0 0 0-4.61 3.07A8 8 0 0 1 4 12a8.1 8.1 0 0 1 8-8 8.1 8.1 0 0 1 8 8 8 8 0 0 1-2.39 5.64z">
              </path>
              <path
                d="M12 6a3.91 3.91 0 0 0-4 4 3.91 3.91 0 0 0 4 4 3.91 3.91 0 0 0 4-4 3.91 3.91 0 0 0-4-4zm0 6a1.91 1.91 0 0 1-2-2 1.91 1.91 0 0 1 2-2 1.91 1.91 0 0 1 2 2 1.91 1.91 0 0 1-2 2z">
              </path>
            </svg>
          </div>
          <div v-else-if="item.key == 'color'">
            <div class="d-flex" v-if="item.value && item.deleted_at_switch">
              <v-sheet class="my-auto pa-0" :color="item.value" elevation="1" height="12" width="12"></v-sheet>
              <p class="ml-1 mb-0 text-value-color">{{ item.value }}</p>
            </div>
          </div>
          <div v-else>
            <span v-if="item.deleted_at_switch"> {{ item.value }} </span>
          </div>
        </div>
        <span v-if="item.type == 'predeterminado'"> {{ item.value }} </span>
        <span v-if="item.type == 'mensaje'">
          <span v-if="item.languages.length == 0">
           {{ item.value }}
          </span>
          <div class="d-flex flex-column"v-else>

              <div v-for="langValue in item.languages" :key="langValue.value">
                <span v-if="languagesOptions.some(langOpt=>langOpt.value == langValue.language)">
                  <img v-if="langValue.language == 'castellano'" src="../../../images/Castellano.png" alt="" width="30" />
                  <img v-if="langValue.language == 'ingles'" src="../../../images/Ingles.png" alt="" width="30" />
                  <img v-if="langValue.language == 'valenciano'" src="../../../images/Valenciano.png" alt="" width="30" />
                  {{ langValue.value }}
                </span>
              </div>
          </div>
        </span>
        <span v-if="(item.deleted_at_switch && item.type == 'numero') ||
        item.type == 'Numero'
        ">{{ item.value }}</span>
          <span v-if="(item.deleted_at_switch && item.type == 'porcentaje') ||
        item.type == 'Porcentaje'
        ">{{ item.value }}</span>
          <span v-if="(item.deleted_at_switch && item.type == 'activacion') ||
        item.type == 'Activacion' ||
        (item.deleted_at_switch && item.type == 'idioma') ||
        item.type == 'Idioma'
        ">
          <span v-if="item.value == '0'" class="mdi mdi-close-circle red">
            Inactivo
            <v-tooltip v-if="item.name == 'Módulo agente'" text="Panel de horas y festivos oculto">
              <template v-slot:activator="{ props }">
                <v-icon v-bind="props" class="mdi mdi-information info-icon"></v-icon>
              </template>
            </v-tooltip>
          </span>
          <span v-else class="mdi mdi-check-circle" style="color: green">
            Activo
            <v-tooltip v-if="item.name == 'Módulo agente'" text="Panel de horas y festivos disponible">
              <template v-slot:activator="{ props }">
                <v-icon v-bind="props" class="mdi mdi-information info-icon"></v-icon>
              </template>
            </v-tooltip>
          </span>
        </span>
      </template>
      <template v-slot:item.option="{ item }">
        <v-container>
          <v-row class="no-wrap-row" align="center" justify="center">
            <v-col v-if="item.deleted_at_switch && $can('chatbots_settings_info')" cols="auto">
              <v-tooltip text="Tooltip">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="showInfo(item)">
                    <v-icon color="#a1a5b7">mdi mdi-eye</v-icon>
                  </v-btn>
                </template>
                <span>Ver información</span>
              </v-tooltip>
            </v-col>
            <v-col v-if="item.deleted_at_switch && $can('chatbots_settings_edit')" cols="auto">
              <v-tooltip text="Tooltip">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="dialogOpen(item)">
                    <v-icon color="#a1a5b7">mdi mdi-file-document-edit</v-icon></v-btn>
                </template>
                <span>Editar Configuración</span>
              </v-tooltip>
            </v-col>
          </v-row>
        </v-container>
      </template>
    </Datatable>

    <v-dialog v-model="dialogAjustes" max-width="650">
      <v-form ref="form" v-model="validAjustes" lazy-validation>
        <v-card>
          <v-card-title>
            <span class="headline">Ajustes de Chatbot</span>
          </v-card-title>
          <v-card-subtitle>
            <span>{{ ajuste_select.value.name }}</span>
          </v-card-subtitle>
          <v-card-text>

            <div class="d-flex" md12 v-if="ajuste_select.value.type == 'texto' || ajuste_select.value.type == 'Texto'">
              <div class="col-12" v-if="ajuste_select.value.key == 'logo' || ajuste_select.value.key === 'icono_bot' || ajuste_select.value.key === 'icono_agente'">
                <upload-image-component :value="ajuste_select.value.value"
                  @upload-image="uploadImage"></upload-image-component>
                <p v-if="errorImage" style="color: red">{{ errorImage }}</p>
              </div>
              <div class="col-12" v-else-if="ajuste_select.value.key == 'color'">
                <Chrome v-model="ajuste_select.value.value" style="width: 325px" :disableAlpha="true" class="mx-auto"
                  @update:model-value="updateValue" />
              </div>
              <div class="col-12" v-else-if="ajuste_select.value.key == 'titulo'">
                <v-text-field v-model="ajuste_select.value.value" label="Predeterminado" placeholder="Valor predeterminado"></v-text-field>

                <div class="col-12 d-flex w-100 justify-space-evenly gap-2">

                  <div class="col-6"v-for="lang in languagesOptions" :key="lang.value">
                    <div v-if="settingExists(lang.value)">
                      <div class="d-flex justify-space-evenly mb-2" >

                        <strong>{{ lang.value }}</strong>
                        <img v-if="lang.value == 'castellano'" src="../../../images/Castellano.png" alt="" width="30" />
                        <img v-if="lang.value == 'ingles'" src="../../../images/Ingles.png" alt="" width="30" />
                        <img v-if="lang.value == 'valenciano'" src="../../../images/Valenciano.png" alt="" width="30" />
                      </div>
                      <v-text-field variant="outlined" label="Nombre" v-model="ajuste_select.value.languages.find(language => language.language == lang.value).value"></v-text-field>
                    </div>

                  </div>

                </div>
              </div>
              <v-text-field v-else v-model="ajuste_select.value.value" label="Valor" placeholder="Valor"
                variant="outlined" />
            </div>

            <div class="d-flex w-100 flex-column justify-space-evenly gap-2" md12 v-if="ajuste_select.value.type == 'mensaje' || ajuste_select.value.type == 'Mensaje'">

              <v-text-field v-model="ajuste_select.value.value" label="Predeterminado" placeholder="Valor predeterminado"></v-text-field>

              <div class="d-flex w-100 justify-space-evenly gap-2">

                <div v-for="lang in languagesOptions" :key="lang.value">
                  <div v-if="settingExists(lang.value)">
                    <div class="d-flex justify-space-evenly mb-2" >

                    <strong>{{ lang.value }}</strong>
                    <img v-if="lang.value == 'castellano'" src="../../../images/Castellano.png" alt="" width="30" />
                    <img v-if="lang.value == 'ingles'" src="../../../images/Ingles.png" alt="" width="30" />
                    <img v-if="lang.value == 'valenciano'" src="../../../images/Valenciano.png" alt="" width="30" />
                  </div>
                  <v-textarea variant="outlined" hide-details rows="6" v-model="ajuste_select.value.languages.find(language => language.language == lang.value).value"></v-textarea>
                </div>

                </div>

              </div>
            </div>

            <div class="d-flex" md12 v-if="ajuste_select.value.type == 'predeterminado' ||
      ajuste_select.value.type == 'Predeterminado'
      ">
              <v-autocomplete v-model="ajuste_select.value.value" label="Idioma principal del Chat"
                :items="languagesOptions" item-title="name" item-value="value"></v-autocomplete>
            </div>

            <div class="d-flex justify-center col-12" md12 v-if="ajuste_select.value.type == 'activacion' ||
      ajuste_select.value.type == 'idioma'
      ">
              <v-switch class="col-6" :color="ajuste_select.value.value === '1' ? 'success' : 'error'
      " v-model="ajuste_select.value.value" true-value="1" false-value="0">
                <template v-slot:label="{ on }">
                  <span v-if="ajuste_select.value.value === '1'" v-on="on">Activo</span>
                  <span v-else v-on="on">Inactivo</span>
                </template>
              </v-switch>
            </div>

            <div class="d-flex col-12 justify-center" md12 v-if="ajuste_select.value.type == 'numero'">
              <input class="input__number" type="number" v-model="ajuste_select.value.value" />
            </div>

            <div class="d-flex porcentaje col-12 justify-center" md12 v-if="ajuste_select.value.type == 'porcentaje' ||
      ajuste_select.value.type == 'Porcentaje'
      ">
            <v-slider
              v-model="ajuste_select.value.value"
              @input="handleInput"
              :max="60" :min="1" :step="1"     thumb-label="always"
            ></v-slider>
            </div>
          </v-card-text>
          <v-card-actions class="mr-4">
            <v-spacer />
            <v-btn v-if="ajuste_select.value.type == 'mensaje' || ajuste_select.value.key == 'titulo'" class="btn-search" :disabled="ajuste_select.value.languages.some(language => language.value == '' && languagesOptions.some(langOpt => langOpt.value == language.language)) || nuevoLenguaje.texto == ''"
              @click="updateChatbotSetting(ajuste_select.value.id)">
              Guardar
            </v-btn>
            <v-btn v-else-if="ajuste_select.value.key == 'logo'" class="btn-search"
              @click="updateChatbotSetting(ajuste_select.value.id)">
              Guardar
            </v-btn>
            <v-btn v-else class="btn-search" :disabled="!ajuste_select.value.value"
            @click="updateChatbotSetting(ajuste_select.value.id)">
            Guardar
          </v-btn>
            <v-btn variant="tonal" class="black-close" @click="closeDialog">
              Cancelar
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-form>
    </v-dialog>

    <v-dialog v-model="showDescription" max-width="450">
      <v-card :title="ajuste_select.value.name">
        <v-card-text class="pb-0">
          <form class="pb-0" method="dialog">

            <v-card-text>
              <span v-if="ajuste_select.value.description">{{ ajuste_select.value.description }}</span>
              <span v-else>No hay descripcion de este festivo</span>
            </v-card-text>

            <v-card-text>
              <div class="d-flex" v-if="ajuste_select.value.key == 'color' || ajuste_select.value.key == 'Color'">
                <span>Valor actual: </span>
                <v-sheet class="my-auto ml-2 pa-0" :color="ajuste_select.value.value" elevation="1" height="12"
                  width="12"></v-sheet>
                <p class="ml-1 mb-0 text-value-color">{{ ajuste_select.value.value }}</p>
              </div>

              <img v-else-if="ajuste_select.value.key == 'logo' || ajuste_select.value.key == 'Logo'"
                class="image-logo py-2" :src="ajuste_select.value.value" />

              <span v-else-if="(ajuste_select.value.type == 'activacion') ||
              ajuste_select.value.type == 'Activacion' ||
              (ajuste_select.value.type == 'idioma') ||
              ajuste_select.value.type == 'Idioma'
              ">
                <span v-if="ajuste_select.value.value == '0'" class="mdi mdi-close-circle red">
                  Inactivo</span>
                <span v-else class="mdi mdi-check-circle" style="color: green">
                  Activo</span>
              </span>
              <span v-else-if="ajuste_select.value.type == 'porcentaje'">
                {{ ajuste_select.value.value }} %
             </span>
              <div v-else>
                <span>Valor actual: </span>
                <div v-if="ajuste_select.value.languages.length > 0">
                  <div class="my-5"v-for="langVal in ajuste_select.value.languages" :key="langVal.language">
                    <img v-if="langVal.language === 'castellano'" src="../../../images/Castellano.png" alt="" width="30"
                    style="margin-right: 5px;" />
                    <img v-if="langVal.language === 'ingles'" src="../../../images/Ingles.png" alt="" width="30"
                    style="margin-right: 5px;" />
                    <img v-if="langVal.language === 'valenciano'" src="../../../images/Valenciano.png" alt="" width="30"
                    style="margin-right: 5px;" />
                    <span>- "{{langVal.value}}"</span>
                  </div>
                </div>
                <span v-else>
                   {{ ajuste_select.value.value }}
                </span>
              </div>

            </v-card-text>

            <v-card-actions>
              <v-spacer />
              <v-btn variant="tonal" class="black-close" @click="closeDialog">
                Cancelar
              </v-btn>
            </v-card-actions>

          </form>
        </v-card-text>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import Datatable from "../utilities/Datatable.vue";
import Loader from "../utilities/Loader.vue";
import axios from "axios";
import { ref, onMounted, reactive } from "vue";
import { formatDateTime } from "@/helpers";
import { cloneDeep } from "lodash";
import Swall from "sweetalert2";
import { Chrome, create } from "@ckpack/vue-color";
import uploadImageComponent from "../settings/uploadImage.vue";

const props = defineProps({
  settings: { type: Object },
  loadingSave: { type: Boolean, default: () => false },
  languages: { type: Array, default: () => [] },
});

const loading = ref(false);
const ajustes = ref([]);
const languagesOptions = ref([]);
const errorImage = ref(null);
const dialogAjustes = ref(false);
const showDescription = ref(false);
const validAjustes = ref(false);
const item_update = ref(false);
const nuevoLenguaje = ref({ texto: null, idioma: null });
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

const ajuste_select = reactive({
  key: "",
  id: null,
  name: "",
  type: null,
  default_id: null,
  value: null,
  description: "",
  language:[]
});

const headers = ref([
  {
    title: "Nombre",
    align: "start",
    sortable: true,
    key: "name",
  },
  {
    title: "Valor",
    align: "start",
    sortable: true,
    key: "value",
  },
  {
    title: "",
    align: "end",
    sortable: true,
    key: "option",
  },
]);

onMounted(async () => {
  await loadData();
});

const closeDialog = () => {
  dialogAjustes.value = false;
  showDescription.value = false;
};

const dialogOpen = (item) => {

  if (item) {
    item_update.value = true;

    ajuste_select.value = cloneDeep(item);
    dialogAjustes.value = true;
  } else {
    item_update.value = true;
    ajuste_select.value = {
      key: "",
      id: null,
      type: null,
      name: null,
      value: null,
      description: null,
      default_id: null,
    };
    dialogAjustes.value = true;
  }
   if(item.type == 'mensaje' || item.key == 'titulo'){
     languagesOptions.value.forEach(langOpt => {
     let langExist = ajuste_select.value.languages.some(lang => lang.language == langOpt.value)
     if(langExist){

     } else {
       ajuste_select.value.languages.push({'chatbot_setting_id': ajuste_select.value.id,'language': langOpt.value, 'value': ''})
     }
     })
   }
};

const loadData = async () => {
  loading.value = true;
  await getOneChatbotSettings(props.settings.id);
};

const getOneChatbotSettings = async (id) => {
  axios
    .get("/getOneChatbotSettings/" + id)
    .then((response) => {
      ajustes.value = response.data.map((ajuste) => ({
        ...ajuste,
        created_at: formatDateTime(ajuste.created_at),
        updated_at: formatDateTime(ajuste.updated_at),
        name: capitalizeWords(ajuste.default_table.name.replace(/_/g, " ")),
        type: ajuste.default_table.type,
        description: ajuste.default_table.description,
        deleted_at_switch: ajuste.deleted_at == null,
        key: ajuste.default_table.name,
        value:
          ajuste.default_table.type == "predeterminado"
            ? capitalizeWords(ajuste.value)
            : ajuste.value,
      }));
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {

      ajustes.value.forEach((ajuste) => {
        let object = settingsJson.value.find((item) => {
          return ajuste.name.toLowerCase() == item.parameter_value.replace(/_/g, " ");
        });
        if (object) {
          ajuste.name = object.parameter_name;
          ajuste.key = object.parameter_value;
        }
      })

      languagesOptions.value = ajustes.value
        .filter(
          (item) =>
            item.type === "idioma" &&
            item.value == 1 &&
            item.deleted_at_switch == true
        )
        .map((item) => ({
          name: item.name,
          value: removeAccents(item.name.toLowerCase()),
        }));
      loading.value = false;
    });
};

const updateChatbotSetting = async (id) => {
  loading.value = true;
  let settingRequest = {};
  settingRequest = ajuste_select.value;
  let checkModule = false;
  if (ajuste_select.value.key == 'modulo_agente') {
    checkModule = true;
  }

  await axios
    .post("/updateChatbotSetting/" + id, settingRequest)
    .then((data) => {
      if (checkModule == true) {
        if (data.data.module_agent) {
          updateShowAgentsModule(data.data.module_agent)
        } else {
          updateShowAgentsModule(data.data.module_agent)
        }
      } else {
        let modulo_agente = ajustes.value.find(ajuste => ajuste.key == 'modulo_agente')
          if(modulo_agente.value == 0){
            updateShowAgentsModule(false)
          } else {
            updateShowAgentsModule(true)
          }
      }
      if (data.data.success) {
        Swall.fire({
          title: "Datos guardados ",
          icon: "success",
          text: data.data.message,
        });
      } else {
        Swall.fire({
          title: "Atención",
          text: data.data.message,
          icon: "warning",
        });
      }
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
      dialogAjustes.value = false;
      loadData();
      loading.value = false;
    });
};

const emit = defineEmits(["updateShowAgentsModule"]);

const updateShowAgentsModule = (value) => {
  emit("updateShowAgentsModule", value);
};

const showInfo = (item) => {
  if (item) {
    ajuste_select.value = cloneDeep(item);
    showDescription.value = true;
  } else {
  }
};
const uploadImage = (image) => {
  if (typeof image !== "boolean") {
    errorImage.value = null;
    if (!image) return (ajuste_select.value.value = null);
    ajuste_select.value.value = image;
  } else {
    errorImage.value = "La imagen excede el tamaño máximo permitido de 5 MB";
  }
};

const settingExists = (language) => {
  return ajuste_select.value.languages.some(lang => lang.language == language)
}


const updateValue = async (event) => {
  ajuste_select.value.value = event.hex;
};

const handleInput = () => {
  if (ajuste_select.value.type == "porcentaje") {
    if (ajuste_select.value.value < 0) {
      ajuste_select.value.value = 0;
    } else if (ajuste_select.value.value > 100) {
      ajuste_select.value.value = 100;
    }
  }
};

const capitalizeWords = (str) => {
  return str.replace(/\b\w/g, (char) => char.toUpperCase());
};

function removeAccents(word) {
  return word.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
}
</script>

<style>
.no-wrap-row {
  flex-wrap: nowrap !important;
}

.black-close {
  background: rgb(103, 100, 100) !important;
  color: white !important;
}

.info-icon {
  font-size: 15px !important;
  color: rgb(96, 99, 104);
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
