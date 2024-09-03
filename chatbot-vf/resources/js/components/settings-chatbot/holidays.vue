<template>
  <div>
    <DateRange @submit="loadData" @updateDate="setDate" @clearData="clearData" :initialFilter="false" />
    <Loader :loading="loading" />
    <Datatable ref="datatable" class="tabla-m" :title="'Festivos'" :button_add="$can('add_holiday')" :button_reload="true"
    :titleAdd="'Agregar festivo'" :showSearch="true" :headers="headers" :items="festivos" @click-add="dialogOpen()" @click-reload="loadData">
      <template v-slot:[`item.description`]="{ item }">
        <span v-if="item.languages.length == 0">{{item.description}}</span>
        <div v-else>
          <div v-for="langDes in item.languages" :key="langDes.id">
            <span v-if="activeLanguages.some(langOpt=>langOpt == langDes.language)">
              <img v-if="langDes.language == 'castellano'" src="../../../images/Castellano.png" alt="" width="30" />
              <img v-if="langDes.language == 'ingles'" src="../../../images/Ingles.png" alt="" width="30" />
              <img v-if="langDes.language == 'valenciano'" src="../../../images/Valenciano.png" alt="" width="30" />
              {{ langDes.message }}
            </span>
          </div>
        </div>
      </template>
      <template v-slot:item.option="{ item }">
        <v-container>
          <v-row class="no-wrap-row" align="center" justify="center">
            <v-col cols="auto" v-if="$can('see_holiday_information')">
              <v-tooltip text="Tooltip">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="showInfo(item)">
                    <v-icon color="#a1a5b7">mdi mdi-eye</v-icon>
                  </v-btn>
                </template>
                <span>Ver información</span>
              </v-tooltip>
            </v-col>
            <v-col cols="auto" v-if="$can('edit_holiday')">
              <v-tooltip text="Tooltip">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="dialogOpen(item)">
                    <v-icon color="#a1a5b7">mdi mdi-file-document-edit</v-icon></v-btn>
                </template>
                <span>Editar Festivo</span>
              </v-tooltip>
            </v-col>
            <v-col cols="auto">
              <v-tooltip text="Tooltip" v-if="$can('delete_holiday')">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="showDelete(item.id)">
                    <v-icon color="#a1a5b7">mdi mdi-trash-can</v-icon></v-btn>
                </template>
                <span>Eliminar Festivo</span>
              </v-tooltip>
            </v-col>
          </v-row>
        </v-container>
      </template>
    </Datatable>

    <v-dialog v-model="showDescription" max-width="350">
      <v-card :title="festivo_select.value.name">

        <v-card-text class="pb-0">
          <form class="p-3 pb-0" method="dialog">

            <v-card-text>
              <span v-if="festivo_select.value.description">{{ festivo_select.value.description }}</span>
              <span v-else>No hay descripción de este festivo</span>
            </v-card-text>

            <v-card-text>
              <span>Fecha: {{ festivo_select.value.day }}</span>
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

    <v-dialog v-model="dialogEdit" max-width="550">
      <v-card :title="modalTitle">
        <v-card-text class="pb-0">
          <form method="dialog">
            <v-text-field density="compact" v-model="festivo_select.value.name" label="Nombre de festivo" required
              variant="outlined">
            </v-text-field>
            <v-text-field density="compact" v-model="festivo_select.value.description" label="Descripción" required
              variant="outlined"></v-text-field>

            <div class="d-flex w-100 justify-space-evenly gap-2">
              <div v-for="lang in activeLanguages" :key="lang">
                <div v-if="settingExists(lang)">
                  <div class="d-flex justify-space-evenly mb-2" >
                    <strong>{{ lang }}</strong>
                    <img v-if="lang == 'castellano'" src="../../../images/Castellano.png" alt="" width="30" />
                    <img v-if="lang == 'ingles'" src="../../../images/Ingles.png" alt="" width="30" />
                    <img v-if="lang == 'valenciano'" src="../../../images/Valenciano.png" alt="" width="30" />
                  </div>
                  <v-textarea variant="outlined" hide-details rows="6" v-model="festivo_select.value.languages.find(language => language.language == lang).message"></v-textarea>
                </div>
              </div>
            </div>

            <div class="div-input-date">
              <input class="input-date" type="date" v-model="festivo_select.value.day" placeholder="Dia" />
            </div>
          </form>
        </v-card-text>

        <v-card-actions class="pt-0 mr-4">
          <v-spacer></v-spacer>
          <v-btn variant="elevated" :color="global.color" :disabled="!festivo_select.value.day ||
      !festivo_select.value.name || !festivo_select.value.description || festivo_select.value.languages.some(language => language.message == '' && activeLanguages.some(langOpt => langOpt == language.language))" :loading="loadingSave" @click="updateChatbotHoliday(settings.id)">
            Guardar
          </v-btn>
          <v-btn variant="tonal" class="black-close" @click="closeDialog()">
            Cancelar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="dialogCrear" max-width="450">
      <v-card title="Nuevo Festivo">
        <v-card-text class="pb-0">
          <form class="p-3 pb-0" method="dialog">
            <v-text-field density="compact" v-model="festivo_select.value.name" label="Nombre de festivo" required
              variant="outlined">
            </v-text-field>
            <v-text-field density="compact" v-model="festivo_select.value.description" label="Descripción" required
              variant="outlined"></v-text-field>

            <div class="d-flex w-100 justify-space-evenly gap-2">
              <div v-for="lang in activeLanguages" :key="lang">
                <div v-if="settingExists(lang)">
                  <div class="d-flex justify-space-evenly mb-2" >
                    <strong>{{ lang }}</strong>
                    <img v-if="lang == 'castellano'" src="../../../images/Castellano.png" alt="" width="30" />
                    <img v-if="lang == 'ingles'" src="../../../images/Ingles.png" alt="" width="30" />
                    <img v-if="lang == 'valenciano'" src="../../../images/Valenciano.png" alt="" width="30" />
                  </div>
                  <v-textarea variant="outlined" hide-details rows="6" v-model="festivo_select.value.languages.find(language => language.language == lang).message"></v-textarea>
                </div>
              </div>
            </div>

            <div class="div-input-date">
              <input class="input-date" type="date" v-model="festivo_select.value.day" placeholder="Dia" />
            </div>
          </form>
        </v-card-text>


        <v-card-actions class="pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="elevated" :color="global.color" :disabled="!festivo_select.value.day ||
      !festivo_select.value.name || !festivo_select.value.description || festivo_select.value.languages.some(language => language.message == '' && activeLanguages.some(langOpt => langOpt == language.language))" :loading="loadingSave" @click="createChatbotHoliday(settings.id)">
            Guardar
          </v-btn>
          <v-btn variant="tonal" class="black-close" @click="closeDialog()">
            Cancelar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>

import Datatable from "../utilities/Datatable.vue";
import axios from 'axios';
import { ref, onMounted, watch, reactive, computed } from 'vue';
import Loader from "../utilities/Loader.vue";
import Swall from "sweetalert2";
import moment from "moment";
import { cloneDeep } from "lodash";
import { useGlobalStore } from "../store/global";
import DateRange from "../utilities/DateRange.vue";

const global = useGlobalStore();

const props = defineProps({
  settings: { type: Object },
  loadingSave: { type: Boolean, default: () => false },
  languages: { type: Array, default: () => [] },
});
const holiday_id = ref(null);
const dateFrom = ref(null);
const dateTo = ref(null);
const loading = ref(false);
const festivos = ref([]);
const dialogEdit = ref(false);
const dialogCrear = ref(false);
const newHoliday = ref(false);
const showDescription = ref(false);
const activeLanguages = ref([]);
const modalTitle = ref('Nuevo')

const festivo_select = reactive({
  key: "",
  id: null,
  name: null,
  day: null,
  description: null
});

const headers = ref([
  {
    title: "Nombre",
    align: "start",
    sortable: true,
    key: "name",
  },
  {
    title: "Día",
    align: "start",
    sortable: true,
    key: "day",
  },
  {
    title: "Descripción",
    align: "start",
    sortable: true,
    key: "description",
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
  dialogEdit.value = false;
  dialogCrear.value = false;
  newHoliday.value = false;
  showDescription.value = false;
};

const dialogOpen = (item) => {
  if (item) {
    holiday_id.value = item.id;
    modalTitle.value = 'Editar Festivo'
    newHoliday.value = false;
    dialogEdit.value = true;
    dialogCrear.value = false;
    festivo_select.value = cloneDeep(item);
  } else {
    festivo_select.value = {
      key: "",
      id: null,
      name: null,
      day: null,
      description: null,
      languages:[]
    }
    modalTitle.value = 'Nuevo Festivo'
    dialogCrear.value = true;
    dialogEdit.value = false;
    newHoliday.value = true;
  }

  activeLanguages.value.forEach(langOpt => {
     let langExist = festivo_select.value.languages.some(lang => lang.language == langOpt)
     if(langExist){

     } else {
      festivo_select.value.languages.push({'holiday_id': festivo_select.value.id,'language': langOpt, 'message': ''})
     }
     })
};

const loadData = async () => {
  loading.value = true;
  activeLanguages.value = props.settings.languages;
  await getChatbotHolidays(props.settings.id);
};

const getChatbotHolidays = async (id) => {
  let from = null;
  let to = null;
  if (dateFrom.value !== null) {
    if (dateTo.value === null) {
      Swall.fire({
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

  axios
    .get("/getChatbotHolidays/" + id, {
      params: {
        ...(from && to && { from, to }),
      },
    })
    .then((response) => {
      festivos.value = response.data
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {

      loading.value = false;

    });
};

const createChatbotHoliday = async (id) => {
  loading.value = true;
  let settingRequest = {};
  settingRequest = festivo_select.value;
  await axios
    .post("/createChatbotHoliday/" + id, settingRequest)
    .then((data) => {

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
      dialogEdit.value = false;
      dialogCrear.value = false;
      loadData();
      loading.value = false;
    });
}

const updateChatbotHoliday = async (id) => {
  loading.value = true;
  let settingRequest = {};
  settingRequest = festivo_select.value;

  await axios
    .post("/updateChatbotHoliday/" + holiday_id.value, settingRequest)
    .then((data) => {

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
      dialogEdit.value = false;
      dialogCrear.value = false;
      loadData();
      loading.value = false;
    });
}

const deleteChatbotHolidays = async (item) => {
  loading.value = true;
  axios.delete("/deleteChatbotHoliday/" + item)
    .then((data) => {
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
      loadData();
      loading.value = false;
    });
}

const setDate = (from, to) => {
  if (from) dateFrom.value = from;
  if (to) dateTo.value = to;
};

const clearData = async () => {
  dateFrom.value = null;
  dateTo.value = null;
  await loadData();
};


const showDelete = (id) => {
  Swall.fire({
    title: "¿Estás seguro?",
    text: "No podrás revertirlo.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Aceptar",
  }).then((result) => {
    if (result.isConfirmed) {
      deleteChatbotHolidays(id);
    }
  });
};

const settingExists = (language) => {
  return festivo_select.value.languages.some(lang => lang.language == language)
}

const showInfo = (item) => {

  if (item) {
    festivo_select.value = cloneDeep(item);
    showDescription.value = true;
  } else {
  }
};

</script>
<style scoped>
.div-input-date {
  display: flex;
  flex-direction: row;
  justify-content: start;
  align-items: center;
  margin-bottom: 10px;
}

.input-date {
  padding: 8px;
  border: 1px solid rgb(175, 175, 175);
  border-radius: 3px;
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
</style>
