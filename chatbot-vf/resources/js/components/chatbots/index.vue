<template>
  <div>
    <div class="d-flex nevegation-bread pr-10 pb-3 mt-4">
      <div v-if="panel != 'init'">
        <v-icon :color="global.color" class="mr-1" icon="mdi-menu-right"></v-icon><a :style="'color:' + global.color"
          @click="closePanel" class="navegation-init">CHATBOTS</a>
      </div>
      <div v-for="(item, i) in itemsBreadcrum" :key="i">
        <div v-if="panel == item.key">
          <v-icon :color="global.color" class="mr-1" icon="mdi-menu-right"></v-icon><a
            :class="panel == item.key ? 'navegation-selected' : ''">{{
              item.text
            }}</a>
        </div>
      </div>
      <v-spacer></v-spacer>
      <v-chip class="chip-chatbot" v-if="idChatSelected" prev-icon>
        <p class="title-selected-chatbot px-5 mb-0">
          <span class="mdi mdi-robot-outline" style="font-size: 20px"></span>
          Chatbot: {{ idChatSelected?.name }}
        </p>
      </v-chip>
    </div>
    <div v-if="panel == 'init'">
      <DateRange @submit="loadData" @updateDate="setDate" @clearData="clearData" :initialFilter="false" />
      <Loader :loading="loading" />
      <Datatable ref="datatable" class="tabla-m" :title="'Chatbots'" :button_add="$can('chatbots_add')"
        :titleAdd="'Agregar chatbot'" :button_reload="true" :headers="headers" :items="chatbots" @click-reload="loadData"
        @click-add="dialogOpen" :showSearch="true">
        <template v-slot:[`item.actions`]="{ item }">
          <v-container>
            <v-row align="center" justify="center">
              <v-col cols="auto" class="pa-2" v-if="$can('chatbots_build')">
                <v-tooltip v-if="item.buildAlert" text="Vuelve a construir" location="top">
                  <template v-slot:activator="{ props }">
                    <v-btn icon size="small" v-bind="props" @click="openBuilder(item)">
                      <v-icon color="red">mdi-robot-industrial</v-icon>
                    </v-btn>
                  </template>
                </v-tooltip>
                <v-tooltip v-else text="Construir" location="top">
                  <template v-slot:activator="{ props }">
                    <v-btn icon size="small" v-bind="props" @click="openBuilder(item)">
                      <v-icon color="#a1a5b7">mdi-robot-industrial</v-icon>
                    </v-btn>
                  </template>
                </v-tooltip>

              </v-col>
              <v-col cols="auto" class="pa-2" v-if="$can('manage_knowledge')">
                <v-tooltip text="Conocimiento" location="top">
                  <template v-slot:activator="{ props }">
                    <v-btn icon size="small" v-bind="props" @click="openIntentions(item)">
                      <v-icon color="#a1a5b7">mdi mdi-brain</v-icon>
                    </v-btn>
                  </template>
                </v-tooltip>
              </v-col>
              <v-col cols="auto" class="pa-2" v-if="$can('chatbots_settings')">
                <v-tooltip text="Ajustes" location="top">
                  <template v-slot:activator="{ props }">
                    <v-btn icon size="small" v-bind="props" @click="openSettings(item)">
                      <v-icon color="#a1a5b7">mdi-cog</v-icon>
                    </v-btn>
                  </template>
                </v-tooltip>
              </v-col>
              <v-col cols="auto" class="pa-2">
                <v-menu>
                  <template v-slot:activator="{ props: menu }">
                    <v-tooltip text="Opciones" location="top">
                      <template v-slot:activator="{ props: tooltip }">
                        <v-btn icon size="small" v-bind="mergeProps(menu, tooltip)">
                          <v-icon color="#a1a5b7">mdi-menu</v-icon>
                        </v-btn>
                      </template>
                    </v-tooltip>
                  </template>
                  <v-list>
                    <v-list-item v-if="$can('chatbots_edit')" @click="dialogEdit(item)">
                      <v-icon color="#a1a5b7">mdi-file-document-edit</v-icon> <span style="color: #6d7182">Editar</span>
                    </v-list-item>
                    <v-list-item
                      v-if="$can('training') && item.st_training !== 'entrenando' && item.st_training !== 'fallo'"
                      @click="trainingChatbot(item)">
                      <v-icon color="#a1a5b7">mdi-book-refresh-outline</v-icon> <span style="color: #6d7182">Entrenamiento
                      </span>
                    </v-list-item>
                    <v-list-item v-if="item.st_training === 'entrenando'">
                      <v-icon class="color_sw">mdi-cog-sync</v-icon> <span class="color_sw">Entrenando... </span>
                    </v-list-item>
                    <v-list-item v-if="item.st_training === 'fallo'" @click="trainingChatbot(item)">
                      <v-icon color="red-darken-3">mdi-sync-alert</v-icon> <span style="color: #c62828">Falló... </span>
                    </v-list-item>
                    <v-list-item v-if="$can('test')" @click="generateScript(item)">
                      <v-icon color="#a1a5b7">mdi-gesture-double-tap</v-icon> <span style="color: #6d7182">Probar</span>
                    </v-list-item>
                    <v-list-item v-if="$can('recover_chatbot')" @click="recoverChatbot(item.id, item.name)">
                      <v-icon color="#a1a5b7">mdi-robot-confused</v-icon> <span style="color: #6d7182">Estado</span>
                    </v-list-item>
                    <v-list-item v-if="$can('chatbots_history')" @click="dialogOpenLog(item.id)">
                      <v-icon color="#a1a5b7">mdi-clipboard-text-clock-outline</v-icon> <span
                        style="color: #6d7182">Histórico</span>
                    </v-list-item>
                    <v-list-item v-if="$can('chatbots_delete')" @click="showDelete(item.id)">
                      <v-icon color="#a1a5b7">mdi-trash-can</v-icon> <span style="color: #6d7182">Eliminar</span>
                    </v-list-item>
                  </v-list>
                </v-menu>
              </v-col>
            </v-row>
          </v-container>
        </template>
        <template v-slot:[`item.status`]="{ item }">
          <v-switch v-model="item.status" color="success" :true-value="1" :false-value="0"
            class="d-flex justify-content-center" hide-details @change="updateStateChatbot(item)"></v-switch>
        </template>
        <template v-slot:[`item.languages`]="{ item }">
          <span v-for="(language, index) in item.languages" :key="index">
            <div style="display: block; margin-bottom: 5px;">
              <img v-if="language === 'castellano'" src="../../../images/Castellano.png" alt="" width="30"
                style="margin-right: 5px;" />
              <img v-else-if="language === 'ingles'" src="../../../images/Ingles.png" alt="" width="30"
                style="margin-right: 5px;" />
              <img v-else-if="language === 'valenciano'" src="../../../images/Valenciano.png" alt="" width="30"
                style="margin-right: 5px;" />
              <span style="text-transform: capitalize;">{{ language }}</span>
            </div>
          </span>
        </template>
        <template v-slot:[`item.name`]="{ item }">
          <span>{{ item.name }}
            <v-tooltip text="Alerta" location="top" v-if="item.messageAlert.length > 0">
              <template v-slot:activator="{ props }">
                <v-btn icon size="small" v-bind="props" @click="openMessage(item.messageAlert)" style="border: none !important;">
                  <v-icon color="#ce0a0a">mdi mdi-alert info-icon</v-icon>
                </v-btn>
              </template>
            </v-tooltip>
          </span>
        </template>
        <template v-slot:buttons-header>
          <v-autocomplete v-if="global.roleUser == 'SuperAdmin'" style="min-width: 140px" v-model="idCustomer"
            density="compact" variant="solo-filled" label="Cliente" :items="customers" item-title="name" item-value="id"
            @update:modelValue="getChatbots()" auto-select-first hide-details></v-autocomplete>
        </template>
      </Datatable>
    </div>
    <div v-else-if="panel == 'builder'">
      <builder ref="builder" :chat="idChatSelected" :loadingSave="loadingSaveFlow" @back-to="closePanel"
        @save-build-chat="saveBuildChat" @show-message="openSnackbar"></builder>
    </div>
    <div v-else-if="panel == 'settings'">
      <settings :settings="idChatSelected" :settingsChatbot="chatbotSettings" :loadingSave="loadingSaveFlow"
        @back-to="closePanel" @save-settings-chat="saveSettingsChat" @show-message="openSnackbar"></settings>
    </div>
    <div v-else-if="panel == 'conocimiento'">
      <Conocimiento :dataChat="idChatSelected" />
    </div>
  </div>

  <v-dialog width="600" v-model="dialogCreate">
    <v-card :title="titleDialog">
      <v-card-text class="pb-0">
        <form method="dialog">
          <v-autocomplete v-if="!dataEdit" v-model="initialChatbot.customer_id" density="compact" variant="outlined"
            auto-select-first label="Cliente" :items="customersFilter" item-title="name" item-value="id"
            :readonly="global.roleUser !== 'SuperAdmin'" :error-messages="errorMessages"></v-autocomplete>
          <v-text-field density="compact" v-model="initialChatbot.name" label="Nombre del Chatbot" required
            variant="outlined" :error-messages="errorMessages"></v-text-field>
        </form>
      </v-card-text>
      <v-card-actions class="pt-0 mr-4">
        <v-spacer></v-spacer>
        <v-btn variant="elevated" :color="global.color" @click="saveChatbot" :loading="loadingSave">Guardar</v-btn>
        <v-btn variant="tonal" class="black-close" @click="dialogClose">
          Cancelar
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <v-dialog width="1200" v-model="dialogLogs">
    <v-card>
      <v-card-text>
        <chatbot-logs :logs="chatbotLogs"></chatbot-logs>
      </v-card-text>
      <v-card-actions class="mr-4">
        <v-spacer></v-spacer>
        <v-btn variant="tonal" class="black-close" text="Cancelar" @click="dialogLogs = false"></v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <v-dialog width="1000" v-model="dialogMessage">
    <v-card>
      <v-card-text>
        <Datatable ref="datatable" class="tabla-m" :title="'Atención'" :button_add="false"
        :titleAdd="'A'" :button_reload="false" :headers="headersMessage" :items="messageAlert" @click-reload="loadData"
        @click-add="dialogOpen" :showSearch="false">
      </Datatable>
      </v-card-text>
      <v-card-actions class="mr-4">
        <v-spacer></v-spacer>
        <v-btn variant="tonal" class="black-close" text="Cancelar" @click="dialogMessage = false"></v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <v-snackbar v-model="snackbar.value" :timeout="snackbar.timeout" location="top">
    {{ snackbar.message }}
    <template v-slot:actions>
      <v-btn class="btn-primary-color" variant="text" @click="snackbar.value = false">Aceptar</v-btn>
    </template>
  </v-snackbar>

  <v-dialog width="600" v-model="dialogRecovery">
    <v-card>
      <v-card-text class="pb-0">
        <Datatable ref="datatable" class="tabla-m" :title="'Estados Chatbot - ' + chatName" :headers="headersRecovery"
          :items="recoveryBot" :showSearch="false" :button_reload="false">
          <template v-slot:header-opt>
            <v-row align="end" justify="end" class="px-16">
              <div class="d-flex mb-4 ml-5">
                <v-col>
                  <v-tooltip text="Tooltip" location="top">
                    <template v-slot:activator="{ props }">
                      <v-btn icon size="small" v-bind="props" @click="activeChatbots('active')">
                        <v-icon color="green-darken-3">mdi mdi-robot</v-icon>
                      </v-btn>
                    </template>
                    <span>Activar chatbots</span>
                  </v-tooltip>
                  <v-tooltip text="Tooltip" location="top">
                    <template v-slot:activator="{ props }">
                      <v-btn icon size="small" class="ml-3" v-bind="props" @click="activeChatbots('inactive')">
                        <v-icon color="red-darken-3">mdi mdi-robot-off</v-icon>
                      </v-btn>
                    </template>
                    <span>Desactivar chatbots</span>
                  </v-tooltip>
                </v-col>
              </div>
            </v-row>
          </template>
          <template v-slot:[`item.state`]="{ item, index }">
            <v-container>
              <v-row align="center" justify="center">
                <v-col cols="auto">
                  <v-tooltip text="Tooltip">
                    <template v-slot:activator="{ props }">
                      <div class="switch">
                        <label v-if="item.state == 'active'">
                          <v-chip color="green">
                            Activo
                          </v-chip>
                        </label>
                        <label v-else>
                          <v-chip color="red">
                            Inactivo
                          </v-chip>
                        </label>
                      </div>
                    </template>
                  </v-tooltip>
                </v-col>
              </v-row>
            </v-container>
          </template>
        </Datatable>
      </v-card-text>
      <v-card-actions class="pt-0 mr-4">
        <v-spacer></v-spacer>
        <v-btn variant="tonal" class="black-close" @click="dialogRecovery = false">
          Cancelar
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, onMounted, reactive, computed, watch } from "vue";
import { useRouter, useRoute } from "vue-router";
import { formatDateTime } from "@/helpers";
import { mergeProps } from 'vue'
import Datatable from "../utilities/Datatable.vue";
import DateRange from "../utilities/DateRange.vue";
import Loader from "../utilities/Loader.vue";
import axios from "axios";
import Swal from "sweetalert2";
import Builder from "../builder/builder.vue";
import Settings from "../settings-chatbot/Index.vue";
import ChatbotLogs from "./chatbotLogs.vue";
import Conocimiento from "../intentions/index.vue";
import { useGlobalStore } from "../store/global";
import moment from "moment";
import { copyTextToClipboard } from "../composables/methods.js";
const global = useGlobalStore();

const loading = ref(false);
const chatbots = ref([]);
const messageAlert = ref([]);
const recoveryBot = ref([]);
const chatbotId = ref(null);
const chatName = ref(null);
const builder = ref(null);

const headers = ref([
  { title: "Nombre", align: "start", sortable: true, key: "name" },
  { title: "Cliente", align: "start", sortable: true, key: "customer" },
  { title: "Creador", align: "start", sortable: true, key: "creator" },
  { title: "Idiomas", align: "start", sortable: true, key: "languages" },
  {
    title: "Fecha creación",
    align: "center",
    sortable: true,
    key: "created_at",
  },
  { title: "Activo", align: "center", sortable: true, key: "status" },
  { title: "Opciones", align: "center", sortable: false, key: "actions" },
]);
const headersRecovery = ref([
  { title: "Idioma", align: "start", sortable: true, key: "language" },
  { title: "Estado", align: "center", sortable: false, key: "state" },
]);
const headersMessage = ref([
  { title: "Mensaje", align: "start", sortable: true, key: "message" },
]);
const itemsBreadcrum = ref([
  { key: "builder", text: "CONSTRUCTOR" },
  { key: "conocimiento", text: "CONOCIMIENTO" },
  { key: "settings", text: "AJUSTES" },
]);

const titleDialog = ref("");
const idCustomer = ref(null);
const router = useRouter();
const route = useRoute();

idCustomer.value =
  route.params.idCustomer.trim() != ""
    ? parseInt(route.params.idCustomer)
    : null;
if (
  global.roleUser == "SuperAdmin" &&
  idCustomer.value == null &&
  global.idCustomer
)
  idCustomer.value = parseInt(global.idCustomer);

const idChatSelected = ref(null);
const dialogCreate = ref(false);
const initialChatbot = reactive({
  id: null,
  name: "",
  customer_id: null,
});
const clearFields = () => {
  initialChatbot.name = "";
  initialChatbot.customer_id = null;
  initialChatbot.id = null;
};
initialChatbot.customer_id =
  idCustomer.value != null ? parseInt(idCustomer.value) : null;

const snackbar = ref({
  value: false,
  message: "",
  timeout: 2000,
});

watch(idCustomer, (newVal) => {
  if (!newVal) {
    router.replace("/chatbots");
  } else {
    router.replace("/chatbots/" + newVal);
  }
});

const errorMessages = ref(null);
const loadingSave = ref(false);
const dataEdit = ref(false);
const loadingSaveFlow = ref(false);
const dialogLogs = ref(false);
const dialogMessage = ref(false);
const dialogRecovery = ref(false);
const chatbotLogs = ref([]);
const chatbotSettings = ref([]);
const panel = ref("init");
const customers = ref([]);
const dateFrom = ref(null);
const dateTo = ref(null);
onMounted(async () => {
  await loadData();
});

const loadData = async () => {
  await getCustomers();
  await getChatbots();
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
const getChatbots = async () => {
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
  initialChatbot.customer_id = parseInt(global.idCustomer);
  loading.value = true;
  let idCustomerSelected =
    global.roleUser == "SuperAdmin" ? idCustomer.value : global.idCustomer;
  let url = idCustomerSelected
    ? `/getChatbots/${idCustomerSelected}`
    : "/getChatbots/";

  let params = {};
  if (from && to) {
    params = { from, to };
  }

  chatbots.value = [];
  axios
    .get(url, { params })
    .then((response) => {
      chatbots.value = response.data.data.map((chat) => {
        const languages = chat.settings
          .filter((setting) => setting.type_setting === "idioma")
          .map((setting) => setting.default_table.name);
        return {
          ...chat,
          created_at: formatDateTime(chat.created_at),
          updated_at: formatDateTime(chat.updated_at),
          status: chat.active,
          customer: chat.city_council?.name,
          creator: chat.creator?.name,
          languages: languages,
        };
      });
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
      loading.value = false;
    });
};

const getCustomers = async () => {
  customers.value = [];
  customers.value.push({ id: null, name: "Todos" });
  axios
    .get("/getAllCityCouncils")
    .then((response) => {
      response.data.data.forEach((element) => {
        customers.value.push(element);
      });
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => { });
};

const dialogOpen = () => {
  dialogCreate.value = true;
  initialChatbot.name = "";
  titleDialog.value = "Nuevo ChatBot";
  dataEdit.value = false;
};
const dialogEdit = async (item) => {
  titleDialog.value = "Editar ChatBot";
  dataEdit.value = true;
  await axios
    .get(`/getIdChatbot/${item.id}`)
    .then((response) => {
      initialChatbot.id = response.data.data.id;
      initialChatbot.name = response.data.data.name;
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
      loading.value = false;
      dialogCreate.value = true;
    });
};

const dialogOpenLog = async (chatbotId) => {
  await axios
    .get(`getHistoryChatbots/${chatbotId}`)
    .then((response) => {
      if (response?.data?.data?.modifications) {
        chatbotLogs.value = response.data.data.modifications.map((log) => ({
          ...log,
          user_name: log?.user?.name,
          created_at: formatDateTime(log.created_at),
        }));
        dialogLogs.value = true;
      }
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
    });

};

const dialogClose = async () => {
  clearFields();
  dialogCreate.value = false;
};

const customersFilter = computed(() => {
  return customers.value.filter((i) => i.id != null);
});

const validateData = () => {
  if (!initialChatbot.name.trim() || !initialChatbot.customer_id) {
    errorMessages.value = "Este campo es obligatorio";
    return false;
  }
  return true;
};
const validateDataEdit = () => {
  if (!initialChatbot.name.trim()) {
    errorMessages.value = "Este campo es obligatorio";
    return false;
  }
  return true;
};

const saveChatbot = async () => {
  if (initialChatbot.id == null) {
    if (!validateData()) {
      return;
    }
    loading.value = true;
    loadingSave.value = true;
    await axios
      .post("/saveChatbot", initialChatbot)
      .then((response) => {
        if (response.data.success) {
          Swal.fire({
            title: "Excelente",
            text: "Cambios realizados!",
            icon: "success",
          });
          return;
        }
        Swal.fire({
          title: "Error",
          text: "Ocurrió un error inesperado.",
          icon: "error",
        });
      })
      .catch((error) => {
        console.error(error);
        if (error?.response?.data?.message == "chatbot name already exist") {
          Swal.fire({
            title: "Error",
            text: "El nombre del chatbot ya esta en uso.",
            icon: "error",
          });
          return;
        }
      })
      .finally(() => {
        dialogClose();
        clearFields();
        loadingSave.value = false;
        loadData();
        loading.value = false;
      });
  } else {
    if (!validateDataEdit()) {
      return;
    }
    loadingSave.value = true;
    await axios
      .post("/setEditChatbot", initialChatbot)
      .then((response) => {
        if (response.data.success) {
          Swal.fire({
            title: "Excelente",
            text: "Cambios realizados!",
            icon: "success",
          });
          return;
        }
      })
      .catch((error) => {
        console.error(error);
        if (error.response.status === 400) {
          Swal.fire({
            title: "Error",
            text: "El nombre del chatbot ya está en uso.",
            icon: "error",
          });
        } else {
          Swal.fire({
            title: "Error",
            text: "Ocurrió un error inesperado.",
            icon: "error",
          });
        }
      })
      .finally(() => {
        dialogClose();
        clearFields();
        loadingSave.value = false;
        loadData();
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
      deleteChatbot(id);
    }
  });
};

const deleteChatbot = async (id) => {
  loading.value = true;
  await axios
    .delete(`/deleteChatbot/${id}`)
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

const updateStateChatbot = (param) => {
  loading.value = true;
  const formData = {
    id: param.id,
    active: param.active,
  };
  axios
    .post("/updateStateChatbot", formData)
    .then((response) => {
      changeStatusModel(param.id, response.data.data.status);
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
      if (error?.response?.data?.message == "not flow.") {
        Swal.fire({
          title: "Atención",
          text: "Para habilitar el chat asegurese de tener un flujo creado.",
          icon: "warning",
        });
        loadData();
      }
      loading.value = false;
    });
};

const changeStatusModel = (id, status) => {
  let chatbot = chatbots.value.findIndex((element) => element.id == id);
  if (chatbot !== -1) chatbots.value[chatbot].active = status;
};

const openBuilder = (item) => {
  idChatSelected.value = item;
  panel.value = "builder";
};

const openSettings = (item) => {
  chatbotSettings.value = item?.settings.map((setting) => ({
    ...setting,
    created_at: formatDateTime(setting.created_at),
  }));
  idChatSelected.value = item;
  panel.value = "settings";
};

const openIntentions = (item) => {
  idChatSelected.value = item;
  panel.value = "conocimiento";
};

const closePanel = () => {
  loadData();
  idChatSelected.value = null;
  panel.value = "init";
};

const openSnackbar = (message, timeout = 2000) => {
  snackbar.value = {
    message: message,
    value: true,
    timeout: timeout,
  };
};

const saveBuildChat = async (idChat, flow) => {
  let data = { information: flow };
  loadingSaveFlow.value = true;
  await axios
    .post("/updateChatbot/" + idChat, data)
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
      if (builder.value && typeof builder.value.showError === 'function') {
        builder.value.showError('Ocurrio un error al momento de almacenar el chatbot')
      }
    })
    .finally(() => {
      loadingSaveFlow.value = false;
      loadData();
    });
};

const saveSettingsChat = async (idChat, flow) => {
  let data = { information: flow };
  loadingSaveFlow.value = true;
  await axios
    .post("/updateChatbotSetting/" + idChat, data)
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
    })
    .finally(() => {
      loadingSaveFlow.value = false;
      loadData();
    });
};

const trainingChatbot = async (chatbot) => {
  Swal.fire({
    title: "¿Estás seguro?",
    text: "Este proceso podría tardar varios minutos.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Entrenar",
  }).then(async (result) => {
    if (result.isConfirmed) {
      loading.value = true;

      const foundChatbot = chatbots.value.find(c => c.id === chatbot.id);

      if (foundChatbot) {
          foundChatbot.st_training = 'entrenando';
      }
      let data = {
        chatbot_id: chatbot.id
      };
      await axios
        .post("/trainingChatbot", data)
        .then((response) => {
          loading.value = false;
          Swal.fire({ title: "Excelente", text: "El Chatbot ha iniciado el entrenamiento...En unos minutos estará disponible!", icon: "success" });
          getChatbots();
        })
        .catch((error) => {
          loading.value = false;
          Swal.fire({ title: "Atención!", text: "Ocurrió un problema en el entrenamiento.", icon: "warning" });
          console.error(error);
        })
        .finally(() => {
        });
    }
  });
};

const generateScript = (chatbot) => {
  if (!chatbot.id) {
    Swal.fire({
      title: "Error",
      text: "Por favor seleccione un chatbot",
      icon: "warning",
    });
  }
  let host = window.location.origin
  let script = `<script type="text/javascript" src="${host}/chatbot" chatbot-id="${chatbot.id}"><\/script>`;
  localStorage.setItem('scriptTag', script);
  localStorage.setItem('chatbotName', chatbot.name);
  localStorage.setItem('type', 'text/javascript');
  localStorage.setItem('src', host);
  localStorage.setItem('chatbotId', chatbot.id);
  localStorage.setItem('customerId', chatbot.city_councils_id);

  copyTextToClipboard(script)

  Swal.fire({
    title: "Script copiado",
    text: "¿Quieres probar el script generado?",
    icon: "success",
    showCancelButton: true,
    confirmButtonText: "Probar",
    cancelButtonText: "No",
  }).then(async (result) => {
    if (result.value) {
      window.location.href = '/scriptTester'
    };
  });
}
const recoverChatbot = async (id, name) => {
  loading.value = true;
  chatbotId.value = id;
  chatName.value = name;
  dialogRecovery.value = true;
  try {
    const response = await axios.get(`/recoverChatbot/${id}`);
    const data = response.data;
    recoveryBot.value = [];
    for (const [language, state] of Object.entries(data)) {
      if (language !== "success") {
        if (typeof state === 'object') {
          for (const [subLanguage, subState] of Object.entries(state)) {
            recoveryBot.value.push({ language: subLanguage, state: subState });
          }
        } else {
          recoveryBot.value.push({ language, state });
        }
      }
    }
    loading.value = false;
  } catch (error) {
    console.error(error);
    loading.value = false;
  }
};
const activeChatbots = async (state) => {
  loading.value = true;
  try {
    const response = await axios.get(`/stateChatbot/${chatbotId.value}/${state}`);
    recoverChatbot(chatbotId.value, chatName.value)
    loading.value = false;
  } catch (error) {
    console.error(error);
    loading.value = false;
  }
};
const openMessage = async (message) => {
  dialogMessage.value = true;
  messageAlert.value = message.map((msg, index) => ({
    message: msg,
    key: `message${index}`
  }));
};

</script>

<style scoped>
.color_sw {
  color: var(--primary-color) !important;
}

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

.navegation-selected {
  color: #797979 !important;
}

.navegation-init {
  cursor: pointer !important;
}

.btn-primary-color {
  background: var(--primary-color);
}

.black-close {
  background: rgb(103, 100, 100) !important;
  color: white !important;
}

.title-selected-chatbot {
  font-weight: 700;
  color: var(--primary-color);
}

.info-icon {
  color: rgb(206, 10, 10);
  font-size: 25px !important;
  text-align: center;
}

@media (max-width: 650px) {
  .nevegation-bread {
    flex-wrap: wrap !important
  }

}
</style>
