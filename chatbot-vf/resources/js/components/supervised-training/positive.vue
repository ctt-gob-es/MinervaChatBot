<template>
    <Loader :loading="loading" />
    <DateRange @submit="loadData" @updateDate="setDate" @clearData="clearData" :initialFilter="false" />
    <Datatable ref="datatableSupervised" class="tabla-m" :title="'Entrenamiento supervisado'" :button_reload="true"
        :headers="headersTraining" :items="supervisedTraining" @click-reload="loadData()"
        @changeSelection="changeSelection" :initSelection="selectConvertations" :showSearch="true"
        :enableSelect="stateSelect" return-object>
        <template v-slot:buttons-header>
            <v-autocomplete style="min-width: 140px" v-model="idChatbot" density="compact" variant="solo-filled"
                label="Chatbots" :items="chatbots" item-title="name" item-value="id" auto-select-first
                @update:modelValue="getSupervisedTraining" hide-details></v-autocomplete>
        </template>
        <template v-slot:header-opt v-if="selectConvertations.length > 0">
            <v-row>
                <div class="d-flex mb-4 ml-5">
                    <v-col v-if="$can('discard_response')">
                        <v-tooltip text="Tooltip" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn icon size="small" v-bind="props" @click="confirmDescart(selectConvertations, 1)">
                                    <v-icon color="red-darken-3">mdi mdi-head-remove-outline</v-icon>
                                </v-btn>
                            </template>
                            <span>Descartar respuesta</span>
                        </v-tooltip>
                    </v-col>
                </div>
            </v-row>
        </template>
        <template v-slot:[`item.id`]="{ item }">
            <v-container>
                <v-row align="center" justify="center">
                    <v-col cols="auto" class="pa-2"
                        v-if="$can('validate_positive_response') && selectConvertations.length === 0">
                        <v-tooltip text="Tooltip" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn icon size="small" v-bind="props"
                                    @click="dialog = true, descart_id = item.id, titleModal = 'positiva', thematic = item.subjects_id, intention = item.intention_id, langQuestion = item.language, question = item.question, getIntentions()">
                                    <v-icon color="green-darken-3">mdi mdi-thumb-up</v-icon>
                                </v-btn>
                            </template>
                            <span>Validar positiva la respuesta</span>
                        </v-tooltip>
                    </v-col>
                    <v-col cols="auto" class="pa-2"
                        v-if="$can('validate_negative_response') && selectConvertations.length === 0">
                        <v-tooltip text="Tooltip" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn icon size="small" v-bind="props"
                                    @click="dialog = true, descart_id = item.id, titleModal = 'negativa', thematic = item.subjects_id, intention = item.intention_id, langQuestion = item.language, question = item.question, getIntentions(), getSubject()"
                                    :readonly="selectConvertations.length > 0">
                                    <v-icon color="red-darken-3">mdi mdi-thumb-down</v-icon>
                                </v-btn>
                            </template>
                            <span>Validar negativa la respuesta</span>
                        </v-tooltip>
                    </v-col>
                    <v-col cols="auto" class="pa-2" v-if="$can('discard_response') && selectConvertations.length === 0">
                        <v-tooltip text="Tooltip" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn icon size="small" v-bind="props" @click="confirmDescart(item.id, 2)">
                                    <v-icon color="red-darken-3">mdi mdi-head-remove-outline</v-icon>
                                </v-btn>
                            </template>
                            <span>Descartar respuesta</span>
                        </v-tooltip>
                    </v-col>
                </v-row>
            </v-container>
        </template>
    </Datatable>
    <v-dialog v-model="dialog" max-width="900">
        <v-card prepend-icon="mdi-robot-confused" :title="'Validar como ' + titleModal + ' la respuesta del bot'">
            <v-list-item class="px-8" height="88" v-if="titleModal === 'negativa'">
                <template v-slot:append>
                    <v-btn variant="elevated" :color="global.color" @click="openIntention(textareas)">
                        Intención
                    </v-btn>
                </template>
            </v-list-item>
            <v-card-text>
                <v-col cols="12" sm="12" v-if="titleModal === 'negativa'">
                    <v-select :items="thematics" v-model="thematic" label="Temática" @update:modelValue="getIntentions"
                        item-title="name" item-value="id" required></v-select>
                </v-col>
                <v-col cols="12" sm="12">
                    <v-select :items="intentions" v-model="intention" label="Intención" item-title="name"
                        :readonly="titleModal === 'positiva'" item-value="id" required></v-select>
                </v-col>
                <div class="d-flex" md12>
                    <template v-for="lang in langChat">
                        <v-col :cols="calculateColumns(langChat.length)">
                            <label v-if="lang === 'castellano'" class="mr-2">
                                <strong>Pregunta (Castellano)</strong>
                                &nbsp;
                                <img src="../../../images/Castellano.png" alt="" width="30" />
                            </label>
                            <label v-else-if="lang === 'ingles'" class="mr-2">
                                <strong>Pregunta (Inglés)</strong>
                                &nbsp;
                                <img src="../../../images/Ingles.png" alt="" width="30" />
                            </label>
                            <label v-else-if="lang === 'valenciano'" class="mr-2">
                                <strong>Pregunta (Valenciano)</strong>
                                &nbsp;
                                <img src="../../../images/Valenciano.png" alt="" width="30" />
                            </label>
                            <v-textarea v-model="textareas[lang].value" variant="outlined" rows="3" hide-details>
                            </v-textarea>
                        </v-col>
                    </template>
                </div>
            </v-card-text>
            <v-card-actions class="mr-4">
                <v-spacer></v-spacer>
                <v-btn variant="elevated" :color="global.color" @click="setRating">
                    Guardar
                </v-btn>
                <v-btn variant="tonal" class="black-close" @click="dialog = false">
                    Cancelar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <v-dialog v-model="dialog_intention" max-width="1600">
        <v-card>
            <v-card-text>
                <intentionsTab :dataChat="dataChat" :editQuestion="editQuestion"/>
            </v-card-text>
            <v-card-actions class="mr-4">
                <v-spacer></v-spacer>
                <v-btn variant="tonal" class="black-close"
                    @click="dialog_intention = false, getIntentions(), getSubject()">
                    Cancelar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { ref, onMounted, computed, watch } from "vue";
import moment from "moment";
import axios from "axios";
import Datatable from "../utilities/Datatable.vue";
import DateRange from "../utilities/DateRange.vue";
import Loader from "../utilities/Loader.vue";
import { useGlobalStore } from "../store/global";
import Swal from "sweetalert2";
import { formatDateTime } from "@/helpers";
import Swall from "sweetalert2";
import intentionsTab from "../intentions/intentions.vue";
import { setIdentifier } from "@/helpers";
const title = 'positive';
const datatableSupervised = ref([]);
const global = useGlobalStore();
const intentions = ref([]);
const thematics = ref([]);
const thematic = ref([]);
const langQuestion = ref(null);
const question = ref(null);
const titleModal = ref(null);
const loading = ref(false);
const stateSelect = ref(true);
const idChatbot = ref(null);
const intention = ref(null);
const chatbots = ref([]);
const editQuestion = ref();
const dateFrom = ref(null);
const dateTo = ref(null);
const tab = ref(null);
const dialog = ref(false);
const dialog_intention = ref(false);
const dataChat = ref(null);
const descart_id = ref(null);
const supervisedTraining = ref([
]);
const headers = ref([
    { title: "Fecha creación", align: "start", sortable: true, key: "created_at" },
    { title: "Pregunta ciudadano", align: "start", sortable: true, key: "question" },
    { title: "Intención", align: "start", sortable: true, key: "intention" },
    { title: "Respuesta Bot", align: "start", sortable: true, key: "answer" },
    { title: "Validación manual", align: "center", sortable: true, key: "id" },
]);
const headersTraining = computed(() => {
    return tab.value === 4
        ? headers.value.filter((i) => i.title !== "Validación manual")
        : headers.value;
});
let langChat = null;
let textareas = null;
const selectConvertations = ref([]);
const selectConvertationsId = ref([]);
const changeSelection = (e) => {
    selectConvertations.value = [];
    e.forEach((item) => {
        const id = item.id;
        selectConvertations.value.push({ id });
    });
};
onMounted(async () => {
    await loadData();
    await getChatbots();
});

const loadData = async () => {
    getSupervisedTraining();
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

const getSupervisedTraining = async () => {
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
        .get('/resourceSupervisedTraining?Q=0&chatbot_id=' + idChatbot.value + '&from=' + from + '&to=' + to)
        .then((response) => {
            supervisedTraining.value = response.data
                .filter(st => st.state === 'Positiva' && st.intention_id != null)
                .map((st) => ({
                    ...st,
                    intention_id: st.intention_id,
                    subjects_id: st.subjects_id,
                    language: st.language,
                    created_at: formatDateTime(st.created_at),
                    option: st.id
                }));
            if (idChatbot.value !== null) {
                chatbots.value.map((chat) => {
                    if (idChatbot.value === chat.id) {
                        langChat = chat.languages;
                        textareas = ref(chat.languages.reduce((acc, lang) => {
                            acc[lang] = { value: '' };
                            return acc;
                        }, {}));
                        const chatToAdd = chatbots.value.find(chat => chat.id === idChatbot.value);
                        if (chatToAdd) {
                            dataChat.value = chatToAdd;
                        }
                    }
                });
            }
            loading.value = false;
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        });
};
const calculateColumns = (totalLanguages) => {
    const totalColumns = 12;
    return Math.floor(totalColumns / totalLanguages);
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

              getSupervisedTraining()
            }
        })
        .catch((error) => {
            console.error(error);
        })
        .finally(() => {
            loading.value = false;
        });
};
const getSubject = async () => {
    loading.value = true;
    axios
        .get('/resourceSupervisedTraining?Q=1&chatbot_id=' + idChatbot.value)
        .then((response) => {
            let filter = response.data.filter(thematic => thematic.name != 'Mensajes chatbots')
            thematics.value = filter;
            loading.value = false;
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        });
};
const getIntentions = async () => {
    loading.value = true;
    axios
        .get('/resourceSupervisedTraining?Q=2&chatbot_id=' + idChatbot.value)
        .then((response) => {
            intentions.value = response.data
                .filter(st => {
                    return st.subjects_id === thematic.value;
                })
                .map((st) => ({
                    ...st,
                }));
            if (!intentions.value.some(int => int.id === intention.value)) {
                intention.value = null;
            }
            if (textareas.value.hasOwnProperty(langQuestion.value)) {
                textareas.value[langQuestion.value].value = question.value;
            }
            loading.value = false;
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        });
};
const validateData = () => {
    const isEmpty = Object.keys(textareas.value).some(key => textareas.value[key].value === '');
    if (isEmpty) {
        Swall.fire({
            title: "Atención!",
            text: "Al menos una pregunta está vacía!",
            icon: "warning",
        });
        return false;
    }
    return true;
};
const setRating = async () => {
    if (!validateData()) {
        return;
    }
    try {
        loading.value = true;
        let requestObject = {
            id: descart_id.value,
            intention_id: intention.value,
            question: JSON.stringify(textareas.value),
            chatbot_id: idChatbot.value
        }
        await axios.post("setRating", requestObject)
            .then((response) => {
                Swall.fire({
                    title: "Correcto!",
                    text: "Datos guardados exitosamente.",
                    icon: "success",
                });
                dialog.value = false
            })
            .catch((error) => {
                console.error(error);
            })
            .finally(() => {
                loadData()
                loading.value = false;
            });
    } catch (error) {
        console.error(error);
    }
}
const confirmDescart = (data, tp) => {
    if (tp === 2) {
        selectConvertationsId.value.push({ id: data })
    }
    Swal.fire({
        title: "¿Estás seguro?",
        text: "¡No podrás revertir esto!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "¡Sí, bórralo!",
    }).then((result) => {
        if (result.isConfirmed) {
            if (tp === 2) {
                descartRating(selectConvertationsId.value);
            } else {
                descartRating(data);
            }
        } else {
            selectConvertationsId.value = [];
        }
    });
};
const descartRating = async (data) => {
    loading.value = true;
    const formData = {
        data: data,
    };
    axios
        .post("/descartRating", formData)
        .then((response) => {
            Swal.fire({
                title: "Excelente",
                text: "Cambios realizados!",
                icon: "success",
            });
            loading.value = false;
            selectConvertations.value = [];
            selectConvertationsId.value = [];
            datatableSupervised.value.clearSelection()
            getSupervisedTraining();
        })
        .catch((error) => {
            console.log(error)
            loading.value = false;
        });
};

const openIntention = async (item) => {
  editQuestion.value = item
  dialog_intention.value = true
}

watch(idChatbot, (newValue)=> {
  setIdentifier(title, newValue)
})
</script>
<style scoped>
.v-tab {
    max-width: 760px !important;
}
</style>
