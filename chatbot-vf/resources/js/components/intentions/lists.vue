<template>
  <div>
    <Loader :loading="loading" />
    <DateRange @submit="loadData()" @updateDate="setDate" @clearData="clearData" :initialFilter="false"
      v-if="!addVisible" />
    <Datatable ref="datatable" class="tabla-m" :title="'Listas'" :button_add="$can('lists_add')" :headers="headers"
      :items="lists" @click-add="openAdd()" :titleAdd="'Agregar lista'" :button_reload="true" @click-reload="loadData()" :showSearch="true"
      v-if="!addVisible">
      <template v-slot:[`item.option`]="{ item }">
        <v-container>
          <v-row align="center" justify="center">
            <v-col cols="auto" class="pa-2" v-if="$can('lists_information')">
              <v-tooltip location="top" text="Información">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click.stop="showInfo(item)">
                    <v-icon color="#a1a5b7"> mdi mdi-eye</v-icon>
                  </v-btn>
                </template>
              </v-tooltip>
            </v-col>
            <v-col cols="auto" class="pa-2" v-if="$can('lists_edit')">
              <v-tooltip location="top" text="Editar">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click.stop="openEdit(item)">
                    <v-icon color="#a1a5b7"> mdi mdi-file-document-edit</v-icon>
                  </v-btn>
                </template>
              </v-tooltip>
            </v-col>
            <v-col cols="auto" class="pa-2" v-if="$can('lists_delete')">
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
    <addLists :dataChat="dataChat" v-else @backTo="backTo" :listId="listToEdit" :editList="editList" />

    <v-dialog width="900" v-model="dialogInfoList">
      <v-card title="Información de lista">
        <v-card-text>
          <v-expansion-panels v-model="panel">
            <v-expansion-panel title="Información general">
              <v-expansion-panel-text>
                <div class="d-flex justify-space-between">
                  <p>
                    <span class="bold">Nombre de lista: </span>{{ detailList.name }}
                  </p>
                </div>
                <div class="d-flex justify-space-between">
                  <p>
                    <span class="bold">Fecha de creación: </span>
                    {{ detailList.created_at }}
                  </p>
                </div>
              </v-expansion-panel-text>
            </v-expansion-panel>
            <v-expansion-panel title="Términos">
              <v-expansion-panel-text>
                <b>{{ detailList.terms[0].term }}</b>
                <p class="">
                  <span></span>
                </p>
                <template v-for="lang in languages">
                  <div v-for="terms in detailList.terms" :key="terms.id">
                    <v-row v-if="lang === 'castellano' && terms.language === 'castellano'">
                      <v-col>
                        <span class="mdi mdi-invoice-list-outline"></span> <b>Castellano: </b>
                        {{ terms.lang_term }}
                      </v-col>
                    </v-row>
                    <v-row v-if="lang === 'ingles' && terms.language === 'ingles'">
                      <v-col>
                        <span class="mdi mdi-invoice-list-outline"></span> <b>Inglés: </b>
                        {{ terms.lang_term }}
                      </v-col>
                    </v-row>
                    <v-row v-if="lang === 'valenciano' && terms.language === 'valenciano'">
                      <v-col>
                        <span class="mdi mdi-invoice-list-outline"></span> <b>Valenciano:</b>
                        {{ terms.lang_term }}
                      </v-col>
                    </v-row>
                  </div>
                </template>
              </v-expansion-panel-text>
            </v-expansion-panel>
            <v-expansion-panel title="Sinónimos">
              <v-expansion-panel-text>
                <template v-for="lang in languages">
                  <div class="mb-2" v-for="terms in detailList.terms" :key="terms.id">
                    <v-row v-if="lang === 'castellano' && terms.language === 'castellano'">
                      <v-col>
                        <div>
                          <p>
                            <span class="mdi mdi-list-box-outline"></span>
                            Sinónimos de <span class="black-letter">
                              {{ terms.lang_term }} ({{ lang }}):
                            </span>
                          </p>
                          <div class="d-flex ml-3">
                            <div class="d-flex justify-space-between" v-for="synonym in terms.synonyms"
                              :key="synonym.id">
                              <span class="black-letter mr-3"
                                v-if="lang === 'castellano' && synonym.language === 'castellano'">
                                <span class="mdi mdi-alpha-s-box-outline"> -</span>
                                {{ synonym.synonym }}
                              </span>
                            </div>
                          </div>
                        </div>
                      </v-col>
                    </v-row>
                    <v-row v-if="lang === 'ingles' && terms.language === 'ingles'">
                      <v-col>
                        <div>
                          <p class="">
                            <span class="mdi mdi-list-box-outline"></span>
                            Sinónimos de <span class="black-letter">
                              {{ terms.lang_term }} ({{ lang }})
                              :
                            </span>
                          </p>
                          <div class="d-flex ml-3">
                            <div class="d-flex justify-space-between" v-for="synonym in terms.synonyms"
                              :key="synonym.id">
                              <span class="black-letter mr-3" v-if="lang === 'ingles' && synonym.language === 'ingles'">
                                <span class="mdi mdi-alpha-s-box-outline"> -</span>
                                {{ synonym.synonym }}
                              </span>
                            </div>
                          </div>
                        </div>
                      </v-col>
                    </v-row>
                    <v-row v-if="lang === 'valenciano' && terms.language === 'valenciano'">
                      <v-col>
                        <div>
                          <p class="">
                            <span class="mdi mdi-list-box-outline"></span>
                            Sinónimos de <span class="black-letter">
                              {{ terms.lang_term }} ({{ lang }}):
                            </span>
                          </p>
                          <div class="d-flex ml-3">
                            <div class="d-flex justify-space-between" v-for="synonym in terms.synonyms"
                              :key="synonym.id">
                              <span class="black-letter mr-3"
                                v-if="lang === 'valenciano' && synonym.language === 'valenciano'">
                                <span class="mdi mdi-alpha-s-box-outline"> -</span>
                                {{ synonym.synonym }}
                              </span>
                            </div>
                          </div>
                        </div>
                      </v-col>
                    </v-row>
                  </div>
                </template>
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
import addLists from "./addLists.vue";
import Datatable from "../utilities/Datatable.vue";
import DateRange from "../utilities/DateRange.vue";
import Loader from "../utilities/Loader.vue";
import axios from "axios";
import Swall from "sweetalert2";

const props = defineProps({
  dataChat: { type: Object, default: null },
});
const languages = ref(props.dataChat.settings
  .filter((setting) => setting.name_setting !== "idioma_principal")
  .map((setting) => setting.name_setting));
const dateFrom = ref(null);
const dateTo = ref(null);
const addVisible = ref(false);
const panel = ref(false);
const dialogInfoList = ref(false);
const listToEdit = ref(null);
const lists = ref([]);

const editList = ref({
  id: "",
  key: "",
  name: "",
  terms: [
    {
      id: "",
      list_id: "",
      term: "",
      synonyms: []
    }
  ],
});

const detailList = ref({
  id: "",
  key: "",
  name: "",
  terms: [
    {
      id: "",
      list_id: "",
      term: "",
      synonyms: []
    }
  ],
});

const chatbot_id = ref(null);
const loading = ref(false);

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
  await getLists(chatbot_id.value);
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

const getLists = async (chatbotId) => {
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
    .get(`/getLists?chatbot_id=${chatbotId}`, {
      params: {
        ...(from && to && { from, to }),
      }
    })
    .then((response) => {
      lists.value = response.data.data.map((list) => ({
        ...list,
        created_at: formatDateTime(list.created_at),
      }));
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
      loading.value = false;
    });
};

const deleteList = async (id) => {
  loading.value = true;
  await axios.delete("deleteLists/" + id)
    .then((response) => {
      Swall.fire({
        title: "Correcto!",
        text: response.data.message,
        icon: "success",
      });
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
      loadData()
    });
}

const showInfo = (item) => {
  dialogInfoList.value = true;
  detailList.value = cloneDeep(item)
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
      deleteList(id);
    }
  });
};

const closeShowInfo = () => {
  dialogInfoList.value = false
  detailList.value = {
    id: "",
    key: "",
    name: "",
    terms: [
      {
        id: "",
        list_id: "",
        term: "",
        synonyms: []
      }
    ],
  };
}

const openAdd = () => {
  editList.value = null;
  addVisible.value = true;
};

const openEdit = (item) => {
  editList.value = cloneDeep(item)
  addVisible.value = true;
};

const backTo = async () => {
  addVisible.value = false;
  listToEdit.value = null;
  await loadData();
};

</script>

<style scoped>
.black-close {
  background: rgb(103, 100, 100) !important;
  color: white !important;
}

.black-letter {
  font-weight: 900 !important;
}
</style>
