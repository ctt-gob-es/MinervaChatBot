<template>
  <div>
    <Loader :loading="loading" />
    <DateRange @submit="loadData()" @updateDate="setDate" @clearData="clearData" :initialFilter="false"
      v-if="!addVisible && viewRange" />
    <Datatable ref="datatable" class="tabla-m" :title="'Contextos'" :button_add="$can('concepts_add')"
      :headers="headers" :items="concepts" @click-add="openAdd()" :titleAdd="'Agregar contexto'" :button_reload="true" @click-reload="loadData()"
      :showSearch="true" @changeSelection="emit('changeSelection', $event)" :enableSelect="selectionTable"
      :height="heightTable" :initSelection="initSelection" v-if="!addVisible">
      <template v-slot:[`item.option`]="{ item }">
        <v-container>
          <v-row align="center" justify="center">
            <v-col cols="auto" class="pa-2" v-if="$can('concepts_view')">
              <v-tooltip location="top" text="Ver">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click.stop="showInfo(item)">
                    <v-icon color="#a1a5b7"> mdi mdi-eye</v-icon>
                  </v-btn>
                </template>
              </v-tooltip>
            </v-col>
            <v-col cols="auto" class="pa-2" v-if="$can('concepts_edit')">
              <v-tooltip location="top" text="Editar">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click.stop="openEdit(item)">
                    <v-icon color="#a1a5b7"> mdi mdi-file-document-edit</v-icon>
                  </v-btn>
                </template>
              </v-tooltip>
            </v-col>
            <v-col cols="auto" class="pa-2" v-if="$can('concepts_delete')">
              <v-tooltip text="Eliminar" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn @click="showDelete(item.id)" icon size="small" v-bind="props">
                    <v-icon color="#a1a5b7">mdi-trash-can</v-icon>
                  </v-btn>
                </template>
              </v-tooltip>
            </v-col>
          </v-row>
        </v-container>
      </template>
    </Datatable>
    <addConcepts :dataChat="dataChat" v-else @backTo="backTo" :editConcept="editConcept" />
    <v-dialog width="900" v-model="dialogInfoConcept">
      <v-card title="Información del contexto">
        <v-card-text>
          <v-expansion-panels v-model="panel">
            <v-expansion-panel title="Información general">
              <v-expansion-panel-text>
                <div class="d-flex justify-space-between">
                  <p>
                    <span class="bold">Contexto: </span>{{ detailConcept.name }}
                  </p>
                </div>
                <div class="d-flex justify-space-between">
                  <p>
                    <span class="bold">Fecha de creación: </span>
                    {{ detailConcept.created_at }}
                  </p>
                </div>
              </v-expansion-panel-text>
            </v-expansion-panel>
            <v-expansion-panel title="Pregunta">
              <v-expansion-panel-text>
                <v-row>
                  <v-col>
                    <template v-for="lang in languages">
                      <div v-for="question in detailConcept.concept_languages">
                        <p v-if="lang === 'castellano' && question.language === 'castellano'">
                          <span class="mdi mdi-comment-question"></span>
                          Pregunta en <b>Castellano</b>: {{ question.question }}
                        </p>
                        <p v-if="lang === 'ingles' && question.language === 'ingles'">
                          <span class="mdi mdi-comment-question"></span>
                          Pregunta en <b>Inglés</b>: {{ question.question }}
                        </p>
                        <p v-if="lang === 'valenciano' && question.language === 'valenciano'">
                          <span class="mdi mdi-comment-question"></span>
                          Pregunta en <b>Valenciano</b>: {{ question.question }}
                        </p>
                    </div>
                    </template>
                  </v-col>
                </v-row>
              </v-expansion-panel-text>
            </v-expansion-panel>
            <v-expansion-panel title="Listas">
              <v-expansion-panel-text>
                <div v-for="list in detailConcept.lists" :key="list.id">
                  <v-row>
                    <v-col>
                      <div>
                        <p class="">
                          <span class="mdi mdi-list-box"></span>
                          {{ list.name }}
                        </p>
                      </div>
                    </v-col>
                  </v-row>
                </div>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="tonal" text="Cancelar" class="black-close" @click="closeShowInfo()"></v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import moment from "moment";
import { formatDateTime } from "@/helpers";
import { cloneDeep } from "lodash";
import Datatable from "../utilities/Datatable.vue";
import Loader from "../utilities/Loader.vue";
import axios from "axios";
import Swall from "sweetalert2";
import addConcepts from "./addConcepts.vue";
import DateRange from "../utilities/DateRange.vue";

const props = defineProps({
  dataChat: { type: Object, default: null },
  heightTable: { type: String, default: null },
  selectionTable: { type: Boolean, default: false },
  viewRange: { type: Boolean, default: true },
  initSelection: { type: Array, default: [] },
});
const languages = ref(props.dataChat.settings
  .filter((setting) => setting.name_setting !== "idioma_principal")
  .map((setting) => setting.name_setting));
const emit = defineEmits(["changeSelection"])

const dateFrom = ref(null);
const dateTo = ref(null);
const addVisible = ref(false);
const chatbot_id = ref(null);
const concepts = ref([]);
const dialogConcepts = ref(false);
const dialogInfoConcept = ref(false);
const loading = ref(false);
const panel = ref(false);

const editConcept = ref({
  id: "",
  key: "",
  name: "",
  question: "",
  errors: "",
  lists: [],
});

const detailConcept = ref({
  id: "",
  key: "",
  name: "",
  question: "",
  lists: [],
});

const headers = ref([
  {
    title: "Nombre",
    align: "start",
    sortable: true,
    key: "name",
  },
  {
    title: "Fecha de creación",
    align: "start",
    sortable: true,
    key: "created_at",
    width: 170,
  },
  {
    title: "Opciones",
    align: "center",
    sortable: true,
    key: "option",
  },
]);

onMounted(async () => {
  await loadData();
});

const loadData = async () => {
  loading.value = true;
  chatbot_id.value = props.dataChat.id;
  await getConcepts(chatbot_id.value);
};

const clearData = async () => {
  dateFrom.value = null;
  dateTo.value = null;
  await loadData();
};

const setDate = (from, to) => {
  if (from) dateFrom.value = from;
  if (to) dateTo.value = to;
};

const getConcepts = async (chatbotId) => {
  loading.value = true;

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
    .get(`/getConcepts?chatbot_id=${chatbotId}`, {
      params: {
        ...(from && to && { from, to }),
      }
    })
    .then((response) => {
      concepts.value = response.data.data.map((concept) => ({
        ...concept,
        created_at: formatDateTime(concept.created_at),
        updated_at: formatDateTime(concept.updated_at),
      }));
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
      loading.value = false;
    });
};

const deleteConcept = async (id) => {
  loading.value = true;
  try {
    await axios.delete("deleteConcepts/" + id)
      .then((response) => {
        Swall.fire({
          title: "Correcto!",
          text: response.data.message,
          icon: "success",
        });
        dialogConcepts.value = false;
      })
      .catch((error) => {
        Swall.fire({
          title: "Atención!",
          text: error.response.data.message,
          icon: "warning",
        });
        loading.value = false;
      })
      .finally(() => {
        loading.value = false;
        loadData()
      });
  } catch (error) {
    console.error(error);
  }
}
const showInfo = (item) => {
  dialogInfoConcept.value = true;
  detailConcept.value = cloneDeep(item)
}

const showDelete = (id) => {
  Swall.fire({
    title: "¿Estás seguro?",
    text: "No podrás revertirlo.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "¡Sí, bórralo!!",
  }).then((result) => {
    if (result.isConfirmed) {
      deleteConcept(id);
    }
  });
};

const closeShowInfo = () => {
  dialogInfoConcept.value = false
  detailConcept.value = {
    id: "",
    key: "",
    name: "",
    question: "",
    lists: []
  };
}

const openAdd = () => {
  editConcept.value = null;
  addVisible.value = true;
};

const openEdit = (item) => {
  editConcept.value = cloneDeep(item)
  addVisible.value = true;
};

const backTo = async () => {
  addVisible.value = false;
  await loadData();
};

</script>

