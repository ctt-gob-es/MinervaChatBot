<template>
  <div style="padding-bottom: 50px">
    <Loader :loading="loading" />
    <DateRange @submit="loadData()" @updateDate="setDate" @clearData="clearData" :initialFilter="false"
      v-if="addVisible == 'index'" />
    <Datatable ref="datatable" class="tabla-m" :title="'Intenciones'" :button_add="$can('knowledge_add')"
      :button_reload="true" :button_import_int="true" :button_export_int="false" :button_template_int="true" :headers="headers" :items="intentions"
      @click-reload="loadData()" :showSearch="true" @click-add="openAdd()" @click-import-intention="dialog_import = true" @click-export-intention="exportIntention()"
      :titleAdd="'Agregar intención'" v-if="addVisible == 'index'">
      <template v-slot:item.option="{ item }">
        <v-container>
          <v-row align="center" justify="center">
            <v-col cols="auto" v-if="$can('knowledge_information')">
              <v-tooltip text="Tooltip" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="dialogOpen(item.id)">
                    <v-icon color="#a1a5b7">mdi mdi-eye</v-icon>
                  </v-btn>
                </template>
                <span>Información</span>
              </v-tooltip>
            </v-col>
            <v-col cols="auto" v-if="$can('knowledge_edit')">
              <v-tooltip text="Tooltip" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="editIntention(item.id)">
                    <v-icon color="#a1a5b7">mdi mdi-file-document-edit</v-icon>
                  </v-btn>
                </template>
                <span>Editar</span>
              </v-tooltip>
            </v-col>
            <v-col cols="auto" v-if="item?.has_concepts && $can('manage_responses')">
              <v-tooltip text="Tooltip" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="openManageAnswers(item.id)"
                    :color="item.training === 1 ? 'red-darken-3' : undefined">
                    <v-icon :color="item.training === 1 ? '#FFFFFF' : '#a1a5b7'">mdi-transit-connection-variant</v-icon>
                  </v-btn>
                </template>
                <span>Gestionar respuestas</span>
              </v-tooltip>
            </v-col>
            <v-col cols="auto" v-if="$can('intentions_history')">
              <v-tooltip text="Histórico" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn @click="dialogOpenLog(item.id)" icon size="small" v-bind="props">
                    <v-icon color="#a1a5b7">mdi mdi-clipboard-text-clock-outline</v-icon>
                  </v-btn>
                </template>
              </v-tooltip>
            </v-col>
            <v-col cols="auto"
              v-if="$can('knowledge_delete') && item.name !== 'cancelar' && item.name !== 'desvio_agente' && item.name !== 'no_le_he_entendido'">
              <v-tooltip text="Tooltip" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="showDelete(item.id)">
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

    <addIntentions :dataChat="dataChat" :editQuestion="editQuestion" v-else-if="addVisible == 'manageIntention'" @backTo="backTo"
      :intentionId="intentionToEdit" />
    <manageAnswers :dataChat="dataChat" v-else-if="addVisible == 'manageAnswers'" @backTo="backTo"
      :intentionId="intentionToManage" />

    <v-dialog width="900" v-model="dialogVisible">
      <v-card :title="detailIntention.name" subtitle="Intención">
        <v-card-text>
          <v-expansion-panels v-model="panel">
            <v-expansion-panel title="Información general">
              <v-expansion-panel-text>
                <div class="d-flex justify-space-between">
                  <p>
                    <span class="bold">Nombre: </span>{{ detailIntention.name }}
                  </p>
                  <p>
                    <span class="bold">Temática: </span>{{ detailIntention.subject_name }}
                  </p>
                </div>
                <div class="d-flex justify-start" v-if="detailIntention.information !== null &&
                  detailIntention.information !== ''
                  ">
                  <p>
                    <span class="bold">Descripcion: </span>{{ detailIntention.information }}
                  </p>
                </div>
                <div class="d-flex justify-space-between">
                  <p>
                    <span class="bold">Fecha de creación: </span>
                    {{ formatDateTime(detailIntention.created_at) }}
                  </p>
                  <p>
                    <span class="bold">Metodo de creacion: </span>
                    {{ detailIntention.creation_method }}
                  </p>
                </div>
                <div class="d-flex justify-start">
                  <p>
                    <span class="bold">Creador: </span>
                    {{ detailIntention.creator }}
                  </p>
                </div>
                <div class="d-flex justify-space-between">
                  <div v-for="intentionLang in detailIntention.intention_language" :key="intentionLang.id">
                    <div v-for="lang in ['castellano', 'ingles', 'valenciano']">
                      <p v-if="intentionLang.language == lang">Nombre en <strong>{{ lang }}</strong>:
                        {{intentionLang.name}}</p>
                    </div>
                  </div>
                </div>
              </v-expansion-panel-text>
            </v-expansion-panel>
            <v-expansion-panel title="Preguntas">
              <v-expansion-panel-text>
                <div v-for="question in detailIntention.questions" :key="question.id">
                  <v-row>
                    <template v-for="lang in ['castellano', 'ingles', 'valenciano']">
                      <v-col v-if="question.question.find((q) => q.language === lang)
                        " :key="lang" cols="12" md="4">
                        <div>
                          <p class="">
                            <span class="mdi mdi-comment-question"></span>
                            Pregunta ({{ lang }}):
                          </p>

                          <p class="">
                            <span v-html="question.question.find(
                              (q) => q.language === lang
                            ).question
                              "></span>
                          </p>
                        </div>
                      </v-col>
                    </template>
                  </v-row>
                </div>
              </v-expansion-panel-text>
            </v-expansion-panel>

            <v-expansion-panel title="Respuestas">
              <v-expansion-panel-text>
                <div class="d-flex justify-space-between" v-for="answer in detailIntention.answers" :key="answer.id">
                  <v-row>
                    <template v-for="lang in ['castellano', 'ingles', 'valenciano']">
                      <v-col v-if="answer.answer.find((q) => q.language === lang)" :key="lang" cols="12" md="4">
                        <div>
                          <p class="">
                            <span class="mdi mdi-comment-text"></span>
                            Respuesta ({{ lang }}):
                          </p>
                          <p class="">
                            <span v-html="answer.answer.find((q) => q.language === lang)
                              .answer
                              "></span>
                          </p>
                        </div>
                      </v-col>
                    </template>
                  </v-row>
                </div>
              </v-expansion-panel-text>
            </v-expansion-panel>
            <v-expansion-panel title="Contextos" v-if="detailIntention.concepts.length > 0">
              <v-expansion-panel-text>
                <div class="d-flex justify-space-between" v-for="concept in detailIntention.concepts" :key="concept.id">
                  <div class="mb-4">
                    <p class="mb-1 font-weight-bold">{{ concept.name }}:</p>
                    <div class="d-flex justify-content-evenly">
                      <p v-for="conceptLang in concept.concept_language" class="mb-0 col-4">
                        <span class="mdi mdi-comment-question mr-1"></span>
                        <span class="bold">Pregunta ({{ conceptLang.lang }}): </span>
                        {{ conceptLang.question }}
                      </p>
                    </div>
                  </div>
                </div>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="tonal" text="Cancelar" class="black-close" @click="closeAdd()"></v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog width="1200" v-model="dialogIntentionLogs">
      <v-card>
        <v-card-text>
          <intention-logs :logs="intentionLogs"></intention-logs>
        </v-card-text>
        <v-card-actions class="mr-4">
          <v-spacer></v-spacer>
          <v-btn variant="tonal" class="black-close" text="Cancelar" @click="dialogIntentionLogs = false"></v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="dialog_import" max-width="800">
      <v-card prepend-icon="mdi mdi-file-document-plus" title="Importar intenciones">
        <v-card-text>
          <v-file-input v-on:change="fileChange"
            accept="*.xlsx, *.xls, application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.oasis.opendocument.spreadsheet, text/csv"
            label="Xlsx">
          </v-file-input>
        </v-card-text>
        <v-card-actions class="mr-4">
          <v-spacer></v-spacer>
          <v-btn variant="elevated" :color="global.color" @click="setImportXlsx">
            Importar
          </v-btn>
          <v-btn variant="tonal" class="black-close" @click="dialog_import = false">
            Cancelar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from "vue";
import moment from "moment";
import { formatDateTime } from "@/helpers";
import Loader from "../utilities/Loader.vue";
import Datatable from "../utilities/Datatable.vue";
import DateRange from "../utilities/DateRange.vue";
import addIntentions from "./addIntentions.vue";
import manageAnswers from "./manageAnswers.vue";
import axios from "axios";
import Swal from "sweetalert2";
import IntentionLogs from "./intentionLogs.vue";
import { useGlobalStore } from "../store/global";
const global = useGlobalStore();
const dateFrom = ref(null);
const dateTo = ref(null);
const chatbot_id = ref(null);
const loading = ref(false);
const addVisible = ref('index');
const dialogVisible = ref(false);
const intentionToEdit = ref(null);
const intentionToManage = ref(null);
const panel = ref(0);
const intentions = ref([]);
const dialogIntentionLogs = ref(false);
const intentionLogs = ref([]);
const dialog_import = ref(false);
const template_xlsx = ref(null);
const editQuestion = ref(null);
const detailIntention = reactive({
  name: "",
  information: "",
  subject_name: "",
  creation_method: "",
  creator: "",
  created_at: "",
  questions: "",
  answers: "",
  concepts: [],
  intention_language: []
});
const headers = ref([
  {
    title: "Intención",
    align: "start",
    sortable: true,
    key: "name",
    width: "150px",
  },
  {
    title: "Información",
    align: "start",
    sortable: true,
    key: "information",
    width: "350px",
  },
  {
    title: "Temática",
    align: "start",
    sortable: true,
    key: "subject_name",
  },
  {
    title: "Fecha creación",
    align: "center",
    sortable: true,
    key: "created_at",
    width: "150px",
  },
  {
    title: "Fecha modificación",
    align: "center",
    sortable: true,
    key: "updated_at",
    width: "150px",
  },
  {
    title: "Método creación",
    align: "center",
    sortable: true,
    key: "creation_method",
    width: "150px",
  },
  {
    title: "Creador",
    align: "start",
    sortable: true,
    key: "creator",
    width: "150px",
  },
  {
    title: "Opciones",
    align: "center",
    sortable: true,
    key: "option",
  },
]);

const props = defineProps({
  dataChat: { type: Object, default: null },
  editQuestion: { type: Object, default: null },
});

onMounted(async () => {
  await loadData();
});

const loadData = async () => {
  loading.value = true;
  editQuestion.value = props.editQuestion
  chatbot_id.value = props.dataChat.id;
  await getIntentions(chatbot_id.value);
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

const getIntentions = async (chatbotId) => {
  loading.value = true;
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
  axios
    .get(`/getIntentions?chatbot_id=${chatbotId}`, {
      params: {
        ...(from && to && { from, to }),
      },
    })
    .then((response) => {
      intentions.value = response.data.data.map((intention) => ({
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

const dialogOpen = (intention_id) => {
  loading.value = true;
  axios
    .get(`/getDetailIntention?intention_id=${intention_id}`)
    .then((response) => {
      detailIntention.name = response.data.data.name;
      detailIntention.information = response.data.data.information;
      detailIntention.subject_name = response.data.data.subject_name;
      detailIntention.creation_method = response.data.data.creation_method;
      detailIntention.creator = response.data.data.creator;
      detailIntention.created_at = response.data.data.created_at;
      detailIntention.questions = response.data.data.questions;
      detailIntention.answers = response.data.data.answers;
      detailIntention.concepts = response.data.data.concepts;
      detailIntention.intention_language = response.data.data.intention_language;
      dialogVisible.value = true;
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
      loading.value = false;
    });
};

const dialogOpenLog = async (intentionId) => {
  await axios
    .get(`getHistoryIntentions/${intentionId}`)
    .then((response) => {

      if (response?.data?.data?.modifications) {
        intentionLogs.value = response.data.data.modifications.map((log) => ({
          ...log,
          user_name: log?.user?.name,
          created_at: formatDateTime(log.created_at),
        }));
      }
      dialogIntentionLogs.value = true;
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {

    });

};

const openAdd = () => {
  addVisible.value = 'manageIntention';
};

const openManageAnswers = (id) => {
  intentionToManage.value = id;
  addVisible.value = 'manageAnswers';
};

const editIntention = (id) => {
  intentionToEdit.value = id;
  addVisible.value = 'manageIntention';
};

const closeAdd = () => {
  dialogVisible.value = false;
  panel.value = 0;
};

const backTo = async () => {
  addVisible.value = 'index';
  intentionToEdit.value = null;
  intentionToManage.value = null;
  await loadData();
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
      deleteIntentions(id);
    }
  });
};

const deleteIntentions = async (id) => {
  loading.value = true;
  await axios
    .delete(`/deleteIntentions/${id}`)
    .then((response) => {
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
      if (error.response.status === 422) {
        Swal.fire({
          title: "Error",
          text: "No se puede eliminar la intención porque tiene intenciones asociadas.",
          icon: "error",
        });
      } else {
        Swal.fire({
          title: "Error",
          text: "Ocurrió un error al intentar eliminar la intención.",
          icon: "error",
        });
      }
      loading.value = false;
    });
};
const fileChange = async (event) => {
  template_xlsx.value = event.target.files[0];
};
const setImportXlsx = () => {
  if (template_xlsx.value === null) {
    Swal.fire({
      title: "Atención!",
      text: "Debes seleccionar un archivo xlsx",
      icon: "warning",
    });
    return;
  }
  const config = {
    headers: {
      "Content-Type": "multipart/form-data",
    },
  };

  const formData = new FormData();
  formData.append("chatbot_id", chatbot_id.value);
  formData.append("xlsx_import", template_xlsx.value);
  loading.value = true;
  axios
    .post("/importIntentionsXlsx", formData, config)
    .then((response) => {
      if (response.data.message === 'ok') {
        Swal.fire({
          title: "Correcto!",
          text: 'Intenciones importadas.',
          icon: "success",
        });
        getIntentions(chatbot_id.value);
      } else {
        Swal.fire({
          title: "Atención!",
          text: response.data.message,
          icon: "warning",
        });
      }
      dialog_import.value = false;
      template_xlsx.value === null;
      loading.value = false;
    })
    .catch((error) => {
      loading.value = false;
    });
};
const exportIntention = async () => {
  const formData = {
    chatbot_id: chatbot_id.value
  };
  window.open('/exportIntentionsXlsx/'+ btoa(JSON.stringify(formData)));
};
</script>

<style>
.black-close {
  background: rgb(103, 100, 100) !important;
  color: white !important;
}

.swal2-container {
  z-index: 3000 !important;
}
</style>
