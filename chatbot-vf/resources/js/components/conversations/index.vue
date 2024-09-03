<template>
  <div>
    <div style="padding-bottom: 50px">
      <Loader :loading="loading" />
      <DateRange class="mt-7" @submit="loadData" @updateDate="setDate" @clearData="clearData" :initialFilter="false"
        :stateOptions="stateOpt" :filter_conversation="true" @stateSelected="stateSelect" />
      <Datatable ref="datatable" class="tabla-m" :title="'Conversaciones'" :button_reload="true" :headers="headers"
        :items="conversation" @click-reload="loadData()" :showSearch="true">
        <template v-slot:buttons-header>
          <v-autocomplete style="min-width: 140px" v-model="idChatbot" density="compact" variant="solo-filled"
            label="Chatbots" :items="chatbots" item-title="name" item-value="id" auto-select-first
            @update:modelValue="getConversation" hide-details></v-autocomplete>
        </template>
        <template v-slot:[`item.option`]="{ item }">
          <v-container>
            <v-row>
              <v-col v-if="$can('detail_conversations')">
                <v-tooltip text="Tooltip" location="top">
                  <template v-slot:activator="{ props }">
                    <v-btn icon size="small" v-bind="props" @click="dialog = true, getConversationDetail(item.id)">
                      <v-icon color="#a1a5b7">mdi mdi-eye</v-icon>
                    </v-btn>
                  </template>
                  <span>Ver conversación</span>
                </v-tooltip>
              </v-col>
            </v-row>
          </v-container>
        </template>
      </Datatable>
      <v-dialog v-model="dialog" max-width="1000">
        <v-card>
          <v-card-text>
            <Datatable ref="datatable" class="tabla-m" :title="'Conversación'" :button_reload="false"
              :headers="headers_conversation" :items="conversation_detail" @click-reload="loadData()" :showSearch="false">
              <template v-slot:[`item.type_user`]="{ item }">
                <template v-if="item.type_user === 'bot'">
                  <v-btn prepend-icon="mdi-robot" variant="text">
                    Bot
                  </v-btn>
                </template>
                <template v-if="item.type_user === 'agente'">
                  <v-btn prepend-icon="mdi-face-agent" variant="text">
                    Agente
                  </v-btn>
                </template>
                <template v-if="item.type_user === 'ciudadano'">
                  <v-btn prepend-icon="mdi-account-badge" variant="text">
                    Ciudadano
                  </v-btn>
                </template>
              </template>
            </Datatable>
          </v-card-text>
          <v-card-actions class="mr-4">
            <v-spacer></v-spacer>
            <v-btn variant="tonal" class="black-close" @click="dialog = false">
              CERRAR
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";
import Datatable from "../utilities/Datatable.vue";
import DateRange from "../utilities/DateRange.vue";
import Loader from "../utilities/Loader.vue";
import { useGlobalStore } from "../store/global";
import { formatDateTime } from "@/helpers";
import Swal from "sweetalert2";
import moment from "moment";
import { setIdentifier } from "@/helpers";

const global = useGlobalStore();
const loading = ref(false);
const dialog = ref(false);
const dateFrom = ref(null);
const dateTo = ref(null);
const idChatbot = ref(null);
const chatbots = ref([]);
const conversation_detail = ref([]);
const conversation = ref([]);
const stateOpt = ref([]);
const stateSlt = ref(null);
const title = 'conversation';

const headers = ref([
  { title: "Identificador", align: "start", sortable: true, key: "id", },
  { title: "Fecha de creación", align: "start", sortable: true, key: "created_at", },
  { title: "Fecha primer mensaje", align: "start", sortable: true, key: "message_init_date", },
  { title: "Fecha último mensaje", align: "start", sortable: true, key: "message_finish_date", },
  { title: "Duración de la conversación (H:M:S)", align: "center", sortable: true, key: "time_conversation", },
  { title: "Mensajes totales", align: "start", sortable: true, key: "total_message", },
  { title: "Agente", align: "start", sortable: true, key: "agent", },
  { title: "Estado", align: "start", sortable: true, key: "state", },
  { title: "Acción", align: "center", sortable: true, key: "option", },
]);
const headers_conversation = ref([
  { title: "Fecha de creación", align: "start", sortable: true, key: "created_at", },
  { title: "Tipo usuario", align: "start", sortable: true, key: "type_user", },
  { title: "Mensaje", align: "start", sortable: true, key: "message", },
]);

onMounted(async () => {
  await loadData();
});

const loadData = async () => {
  getChatbots();
  await getConversationStatus();
};

const setDate = (from, to) => {
  if (from) dateFrom.value = from;
  if (to) dateTo.value = to;
};

const clearData = async () => {
  dateFrom.value = null;
  dateTo.value = null;
  stateSlt.value = null;
  await loadData();
};

const getChatbots = async () => {
  loading.value = true;
  let idCustomerSelected = global.idCustomer;
  let url = idCustomerSelected
    ? `/getChatbots/${idCustomerSelected}`
    : "/getChatbots/";
  chatbots.value = [];
  axios
    .get(url)
    .then((response) => {
      chatbots.value = response.data.data.map((chat) => {
        return {
          id: chat.id,
          name: chat.name
        };
      });
      if (chatbots.value.length > 0) {
        let chatbotStorage = localStorage.getItem(`select_${title}`);

        if(chatbotStorage && chatbotStorage != null){
          let foundChatbot = chatbots.value.find(chatbot => chatbot.id === chatbotStorage);

          if (foundChatbot) {
              idChatbot.value = chatbotStorage;
          } else {
              idChatbot.value = chatbots.value[0].id;
          }
        } else  {
          idChatbot.value = chatbots.value[0].id;
        }

        getConversation()
      }

    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
      loading.value = false;
    });
};
const getConversation = async () => {
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
    .get('/getConversation?&chatbot_id=' + idChatbot.value + '&from=' + from + '&to=' + to + '&state=' + stateSlt.value)
    .then((response) => {
      conversation.value = response.data.map((conv) => ({
        ...conv,
        created_at: formatDateTime(conv.created_at),
        message_finish_date: formatDateTime(conv.message_finish_date),
        message_init_date: formatDateTime(conv.message_init_date),
        agent: conv.agent === 0 ? 'N/A' : 'Agente',
        option: conv.id
      }));
      loading.value = false;
    })
    .catch((error) => {
      console.error(error);
      loading.value = false;
    });
};
const stateSelect = (item) => {
  stateSlt.value = item;
};
const getConversationDetail = (item) => {
  loading.value = true;
  axios
    .get("/getConversationDetail/" + item)
    .then((response) => {
      conversation_detail.value = response.data.map((detail) => ({
        ...detail,
        created_at: formatDateTime(detail.created_at),
      }));
      loading.value = false;
    })
    .catch((error) => {
      console.error(error);
      loading.value = false;
    });
};
const getConversationStatus = () => {
  loading.value = true;
  axios
    .get("/getConversationStatus")
    .then((response) => {
      stateOpt.value = response.data.map((state) => {
        return {
          id: state.id,
          name: state.name,
        };
      });
    })
    .catch((error) => {
      console.error(error);
      loading.value = false;
    });
};

watch(idChatbot, (newValue)=> {
  setIdentifier(title, newValue)
})
</script>
