<template>
  <Loader :loading="loading" />
  <v-card elevation="16">
    <v-card-title class="title-datatable">
      {{ title }}
      <v-btn prepend-icon="mdi mdi-arrow-left" variant="tonal" class="btn-log" @click="$emit('backTo', true)">
        Volver
      </v-btn>
    </v-card-title>
    <v-card-text class="p-0">
      <v-stepper :items="itemStepers" color="#444" v-model="activeStep" hide-actions editable>
        <template v-slot:[`item.1`]>
          <div class="mt-2">
            <v-select v-model="initialState.subjects" :items="itemsSubjects" item-title="name" item-value="id"
              variant="outlined" label="Temáticas" required persistent-hint :disabled="flagIntention"></v-select>
            <v-text-field v-model="initialState.name" label="Nombre" :error-messages="errorMessages.name" required
              variant="outlined"
              :disabled="initialState.name === 'cancelar' || initialState.name === 'desvio_agente' || initialState.name === 'no_le_he_entendido'"
              @input="validateDataIntention"></v-text-field>
            <div class="row mx-auto mb-2">
              <template v-for="lang in languages">
                <v-col :cols="calculateColumns(languages.length)" class="m-0 p-0 pr-2 py-3">
                  <label v-if="lang === 'castellano'" class="mr-2">
                    <strong>Intención (Castellano)</strong>
                    &nbsp;
                    <img src="../../../images/Castellano.png" alt="" width="30" />
                  </label>
                  <label v-else-if="lang === 'ingles'" class="mr-2">
                    <strong>Intención (Inglés)</strong>
                    &nbsp;
                    <img src="../../../images/Ingles.png" alt="" width="30" />
                  </label>
                  <label v-else-if="lang === 'valenciano'" class="mr-2">
                    <strong>Intención (Valenciano)</strong>
                    &nbsp;
                    <img src="../../../images/Valenciano.png" alt="" width="30" />
                  </label>
                  <v-textarea v-model="textareas[lang].value" variant="outlined" rows="3" hide-details>
                  </v-textarea>
                </v-col>
              </template>
            </div>
            <v-textarea label="Información" required variant="outlined" v-model="initialState.information">
            </v-textarea>
          </div>
        </template>
        <template v-slot:[`item.2`]>
          <template
            v-if="conceptAll.length > 0 && !['no_le_he_entendido', 'cancelar', 'desvio_agente'].includes(initialState.name)">
            <Concepts :dataChat="dataChat" @changeSelection="changeSelection" :selectionTable="true"
              :initSelection="initialState.concepts" :viewRange="false"></Concepts>
          </template>
          <template v-else>
            <div row v-for="(questionSet, setIndex) in initialState.questions" :key="setIndex">
              <div class="py-1 px-2 d-flex justify-end align-center">
                <v-btn title="Eliminar" icon size="small" color="#B71C1C" @click="removeItem(setIndex)">
                  <v-icon color="#fff">mdi mdi-trash-can</v-icon>
                </v-btn>
              </div>
              <v-row>
                <template v-for="(language, languageIndex) in questionSet.question" :key="languageIndex">
                  <v-col :cols="calculateColumns(questionSet.question.length)">
                    <label v-if="language.name_language === 'castellano'">
                      <strong>Pregunta (Castellano)</strong>
                    </label>
                    <label v-else-if="language.name_language === 'ingles'">
                      <strong>Pregunta (Inglés)</strong>
                    </label>
                    <label v-else-if="language.name_language === 'valenciano'">
                      <strong>Pregunta (Valenciano)</strong>
                    </label>
                    &nbsp;
                    <img v-if="language.name_language === 'castellano'" src="../../../images/Castellano.png" alt=""
                      width="30" />
                    <img v-else-if="language.name_language === 'ingles'" src="../../../images/Ingles.png" alt=""
                      width="30" />
                    <img v-else-if="language.name_language === 'valenciano'" src="../../../images/Valenciano.png" alt=""
                      width="30" />
                    <v-tooltip text="Agregar contexto" location="top">
                      <template v-slot:activator="{ props }">
                        <v-btn v-if="initialState.concepts.length > 0" class="ml-3" :color="global.color"
                          density="comfortable" icon="mdi-plus" size="small" v-bind="props"
                          @click="openDialogAddConceptQuestion(setIndex, languageIndex)">
                        </v-btn>
                      </template>
                    </v-tooltip>
                    <v-textarea v-model="initialState.questions[setIndex]['question'][languageIndex].text"
                      variant="outlined" rows="1" hide-details>
                    </v-textarea>
                  </v-col>
                </template>
              </v-row>
            </div>
            <v-btn v-if="initialState.name!=='no_le_he_entendido'" title="AGREGAR" icon size="large" color="#4CAF50" class="btn-flotante" @click="addItem">
              <v-icon color="#fff">mdi mdi-forum-plus</v-icon>
            </v-btn>
          </template>
        </template>
        <template v-slot:[`item.3`]>
          <template
            v-if="conceptAll.length > 0 && !['no_le_he_entendido', 'cancelar', 'desvio_agente'].includes(initialState.name)">
            <div row v-for="(questionSet, setIndex) in initialState.questions" :key="setIndex">
              <div class="py-1 px-2 d-flex justify-end align-center">
                <v-btn title="Eliminar" icon size="small" color="#B71C1C" @click="removeItem(setIndex)">
                  <v-icon color="#fff">mdi mdi-trash-can</v-icon>
                </v-btn>
              </div>
              <v-row>
                <template v-for="(language, languageIndex) in questionSet.question" :key="languageIndex">
                  <v-col :cols="calculateColumns(questionSet.question.length)">
                    <label v-if="language.name_language === 'castellano'">
                      <strong>Pregunta (Castellano)</strong>
                    </label>
                    <label v-else-if="language.name_language === 'ingles'">
                      <strong>Pregunta (Inglés)</strong>
                    </label>
                    <label v-else-if="language.name_language === 'valenciano'">
                      <strong>Pregunta (Valenciano)</strong>
                    </label>
                    &nbsp;
                    <img v-if="language.name_language === 'castellano'" src="../../../images/Castellano.png" alt=""
                      width="30" />
                    <img v-else-if="language.name_language === 'ingles'" src="../../../images/Ingles.png" alt=""
                      width="30" />
                    <img v-else-if="language.name_language === 'valenciano'" src="../../../images/Valenciano.png" alt=""
                      width="30" />
                    <v-tooltip text="Agregar contexto" location="top">
                      <template v-slot:activator="{ props }">
                        <v-btn v-if="initialState.concepts.length > 0" class="ml-3" :color="global.color"
                          density="comfortable" icon="mdi-plus" size="small" v-bind="props"
                          @click="openDialogAddConceptQuestion(setIndex, languageIndex)">
                        </v-btn>
                      </template>
                    </v-tooltip>
                    <v-textarea v-model="initialState.questions[setIndex]['question'][languageIndex].text"
                      variant="outlined" rows="1" hide-details>
                    </v-textarea>
                  </v-col>
                </template>
              </v-row>
            </div>
            <v-btn title="AGREGAR" icon size="large" color="#4CAF50" class="btn-flotante" @click="addItem">
              <v-icon color="#fff">mdi mdi-forum-plus</v-icon>
            </v-btn>
          </template>
          <template v-else>
            <div row v-for="(answerSet, setIndex) in initialState.answers" :key="setIndex">
              <div v-if="initialState.concepts.length == 0" class="py-1 d-flex justify-end align-center">
                <v-btn title="Eliminar" icon size="small" color="#B71C1C" @click="removeItemAnswers(setIndex)">
                  <v-icon color="#fff">mdi mdi-trash-can</v-icon>
                </v-btn>
              </div>
              <div v-else class="py-1 d-flex justify-start align-center">
                <v-chip v-if="answerSet.type == CORRECT" size="small" class="my-3" variant="flat" color="green">
                  Respuesta correcta
                </v-chip>
                <v-chip v-if="answerSet.type == INCORRECT" size="small" class="my-3" variant="flat" color="red">
                  Respuesta incorrecta
                </v-chip>
              </div>
              <v-row>
                <template v-for="(language, languageIndex) in answerSet.answer" :key="languageIndex">
                  <v-col :cols="calculateColumns(answerSet.answer.length)">
                    <label v-if="language.name_language === 'castellano'"><strong>Respuesta (Castellano)</strong></label>
                    <label v-else-if="language.name_language === 'ingles'"><strong>Respuesta (Inglés)</strong></label>
                    <label v-else-if="language.name_language === 'valenciano'"><strong>Respuesta
                        (Valenciano)</strong></label>

                    &nbsp;
                    <img v-if="language.name_language === 'castellano'" src="../../../images/Castellano.png" alt=""
                      width="30" />
                    <img v-else-if="language.name_language === 'ingles'" src="../../../images/Ingles.png" alt=""
                      width="30" />
                    <img v-else-if="language.name_language === 'valenciano'" src="../../../images/Valenciano.png" alt=""
                      width="30" />
                    <v-tooltip text="Agregar contexto" location="top">
                      <template v-slot:activator="{ props }">
                        <v-btn v-if="initialState.concepts.length > 0" class="ml-3" :color="global.color"
                          density="comfortable" icon="mdi-plus" size="small" v-bind="props"
                          @click="openDialogAddConceptAnswer(setIndex, languageIndex)">
                        </v-btn>
                      </template>
                    </v-tooltip>
                    <v-textarea variant="outlined" rows="1" hide-details
                      v-model="initialState.answers[setIndex]['answer'][languageIndex].text">
                    </v-textarea>
                  </v-col>
                </template>
              </v-row>
            </div>
            <v-btn v-if="initialState.concepts.length == 0" title="AGREGAR" icon size="large" color="#4CAF50"
              class="btn-flotante" @click="addItemAnswers">
              <v-icon color="#fff">mdi mdi-forum-plus</v-icon>
            </v-btn>
          </template>
        </template>
        <template v-slot:[`item.4`]>
          <div row v-for="(answerSet, setIndex) in initialState.answers" :key="setIndex">
            <div v-if="initialState.concepts.length == 0" class="py-1 d-flex justify-end align-center">
              <v-btn title="Eliminar" icon size="small" color="#B71C1C" @click="removeItemAnswers(setIndex)">
                <v-icon color="#fff">mdi mdi-trash-can</v-icon>
              </v-btn>
            </div>
            <div v-else class="py-1 d-flex justify-start align-center">
              <v-chip v-if="answerSet.type == CORRECT" size="small" class="my-3" variant="flat" color="green">
                Respuesta correcta
              </v-chip>
              <v-chip v-if="answerSet.type == INCORRECT" size="small" class="my-3" variant="flat" color="red">
                Respuesta incorrecta
              </v-chip>
            </div>
            <v-row>
              <template v-for="(language, languageIndex) in answerSet.answer" :key="languageIndex">
                <v-col :cols="calculateColumns(answerSet.answer.length)">
                  <label v-if="language.name_language === 'castellano'"><strong>Respuesta (Castellano)</strong></label>
                  <label v-else-if="language.name_language === 'ingles'"><strong>Respuesta (Inglés)</strong></label>
                  <label v-else-if="language.name_language === 'valenciano'"><strong>Respuesta
                      (Valenciano)</strong></label>

                  &nbsp;
                  <img v-if="language.name_language === 'castellano'" src="../../../images/Castellano.png" alt=""
                    width="30" />
                  <img v-else-if="language.name_language === 'ingles'" src="../../../images/Ingles.png" alt=""
                    width="30" />
                  <img v-else-if="language.name_language === 'valenciano'" src="../../../images/Valenciano.png" alt=""
                    width="30" />
                  <v-tooltip text="Agregar contexto" location="top">
                    <template v-slot:activator="{ props }">
                      <v-btn v-if="initialState.concepts.length > 0" class="ml-3" :color="global.color"
                        density="comfortable" icon="mdi-plus" size="small" v-bind="props"
                        @click="openDialogAddConceptAnswer(setIndex, languageIndex)">
                      </v-btn>
                    </template>
                  </v-tooltip>
                  <v-textarea variant="outlined" rows="1" hide-details
                    v-model="initialState.answers[setIndex]['answer'][languageIndex].text">
                  </v-textarea>
                </v-col>
              </template>
            </v-row>
          </div>
          <v-btn v-if="initialState.concepts.length == 0" title="AGREGAR" icon size="large" color="#4CAF50"
            class="btn-flotante" @click="addItemAnswers">
            <v-icon color="#fff">mdi mdi-forum-plus</v-icon>
          </v-btn>
        </template>
        <template v-slot:default="{ prev, next }">
          <v-stepper-actions :next-text="nameNext" prev-text="&#x25C0; Anterior" :disabled="disabled" @click:prev="prev"
            @click:next="activeStep === lengthItems ? addIntent() : next()" :color="global.color"></v-stepper-actions>
        </template>
      </v-stepper>
    </v-card-text>

    <v-dialog width="400" v-model="dialogAddConceptQuestion">
      <v-card title="Agregar contexto">
        <v-card-text>
          <v-form ref="formAddConcept">
            <v-autocomplete v-model="wordAddConcept.concept" label="Contexto" :rules="[v => !!v || 'Agregue un contexto']"
              variant="outlined" :items="initialState.concepts" item-title="name"></v-autocomplete>
            <v-autocomplete v-model="wordAddConcept.term" label="Termino" :rules="[v => !!v || 'Agregue un termino']"
              variant="outlined" :items="conceptSelected" item-title="term"></v-autocomplete>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="elevated" :color="global.color" text="Agregar" @click="addConceptToQuestion"
            class="mr-3"></v-btn>
          <v-btn variant="tonal" class="black-close" text="Cancelar" @click="dialogAddConceptQuestion = false"></v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog width="400" v-model="dialogAddConceptAnswer">
      <v-card title="Agregar contexto">
        <v-card-text>
          <v-form ref="formAddConceptAnswer">
            <v-autocomplete v-model="wordAddConceptAnswer.concept" label="contexto"
              :rules="[v => !!v || 'Agregue un contexto']" variant="outlined" :items="initialState.concepts"
              item-title="name"></v-autocomplete>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="elevated" :color="global.color" text="Agregar" @click="addConceptToAnswer" class="mr-3"></v-btn>
          <v-btn variant="tonal" class="black-close" text="Cancelar" @click="dialogAddConceptAnswer = false"></v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

  </v-card>
</template>

<script setup>
import { reactive, ref, onMounted, computed } from "vue";
import { formatDateTime } from "@/helpers";
import Loader from "../utilities/Loader.vue";
import Concepts from "./concepts.vue";
import axios from "axios";
import Swal from "sweetalert2";
import { useGlobalStore } from "../store/global";
const global = useGlobalStore();
const props = defineProps({
  dataChat: { type: Object, default: null },
  intentionId: { type: Number, default: null },
  editQuestion:  { type: Object, default: null },
});
const errorMessages = reactive({
  name: null,
  question: null
});
const emit = defineEmits(["backTo"]);
const title = ref('Agregar Intención');
const MULTIPLE = 0;
const CORRECT = 1;
const INCORRECT = 2;
const chatbot_id = ref(null);
const formAddConcept = ref(null);
const formAddConceptAnswer = ref(null);
const loading = ref(false);
const dialogAddConceptQuestion = ref(false);
const dialogAddConceptAnswer = ref(false);
const activeStep = ref(1);
const itemCount = ref(1);
const languages = ref([]);
const editQuestion = ref(null);
const flagIntention = ref(false);
const languagesCt = ref(props.dataChat.settings
  .filter((setting) => setting.name_setting !== "idioma_principal")
  .map((setting) => setting.name_setting));
const textareas = ref(languagesCt.value.reduce((acc, lang) => {
  acc[lang] = { value: '' };
  return acc;
}, {}));
const initialState = reactive({
  subjects: null,
  name: "",
  information: "",
  questions: [],
  answers: [],
  concepts: [],
});
const wordAddConcept = reactive({
  indexQuestion: null,
  indexLanguage: null,
  term: '',
  concept: null,
});
const wordAddConceptAnswer = reactive({
  indexAnswer: null,
  indexLanguage: null,
  concept: null,
});
const conceptAll = ref([]);
const dateFrom = ref(null);
const dateTo = ref(null);
const itemsSubjects = ref([]);

const itemStepersFilter = ['Datos intención', 'Contextos', 'Preguntas', 'Respuestas'];
const lengthItems = computed(() => {
  if (conceptAll.value.length > 0 && !['cancelar', 'no_le_he_entendido', 'desvio_agente'].includes(initialState.name)) {
    return 4;
  } else {
    return 3;
  }
});

const itemStepers = computed(() => {
  return lengthItems.value == 4 ? itemStepersFilter : itemStepersFilter.filter(i => i != 'Contextos');
})


const itemsManagementIntentions = ref([]);
const headersManagementIntentions = ref([]);
const groupByManagementIntentions = ref([]);

onMounted(async () => {
  await loadData();

  if (props.intentionId) getIntention(props.intentionId)
});

const loadData = async () => {
  loading.value = true;
  chatbot_id.value = props.dataChat.id;
  await getConcepts(props.dataChat.id);
  await getAllSubjects();
  languages.value = props.dataChat.settings
    .filter((setting) => setting.name_setting !== "idioma_principal")
    .map((setting) => setting.name_setting);

  if(props.editQuestion != null){
    let newQuestions = []

    languages.value.forEach(language => {
    const question = props.editQuestion[language];
    if (question) {
      if(question.value != ''){

        newQuestions.push({
          name_language: language,
          text: question.value
        });
      } else {
        newQuestions.push({
          name_language: language,
          text: ''
        });
      }
    }
  });
  initialState.questions.push({ question: newQuestions })
  }

  let dataInicialLanguagesQuestions = languages.value.map((item) => ({
    name_language: item,
    text: "",
  }));
  let dataInicialLanguagesAnswers = languages.value.map((item) => ({
    name_language: item,
    text: "",
  }));
  initialState.questions.push({ question: dataInicialLanguagesQuestions });
  initialState.answers.push({ answer: dataInicialLanguagesAnswers, type: MULTIPLE });

};

const getAllSubjects = async () => {
  try {
    const response = await axios.get(`/getAllSubjects?chatbot_id=${chatbot_id.value}`);


    if (!props.intentionId) {
      const response = await axios.get(`/getAllSubjects?chatbot_id=${chatbot_id.value}`);
      itemsSubjects.value = response.data.data
        .filter(subject => subject.name !== "Mensajes chatbots") // Filtrar los sujetos que no tengan el nombre "Mensajes Chatbot"
        .map((subject) => {
          return {
            id: subject.id,
            name: subject.name,
          };
        });
    } else {
      itemsSubjects.value = response.data.data.map((subject) => {
        return {
          id: subject.id,
          name: subject.name,
        };
      });
    }
  } catch (error) {
    console.error(error);
  } finally {
    loading.value = false;
  }
};


const addItemAnswers = () => {
  itemCount.value++;
  let dataInicialLanguages = languages.value.map((item) => ({
    name_language: item,
    text: "",
  }));
  initialState.answers.push({ answer: dataInicialLanguages, type: MULTIPLE });
};
const removeItemAnswers = (setIndex) => {
  if (initialState.answers.length > 1) {
    initialState.answers.splice(setIndex, 1);
  } else {
    Swal.fire({
      title: "Atención!",
      text: "No se puede eliminar, debe haber mínimo una respuesta.",
      icon: "warning",
    });
  }
};
const addItem = () => {
  itemCount.value++;
  let dataInicialLanguages = languages.value.map((item) => ({
    name_language: item,
    text: "",
  }));
  initialState.questions.push({ question: dataInicialLanguages });
};
const removeItem = async (setIndex) => {
  if (initialState.questions.length > 1) {
    initialState.questions.splice(setIndex, 1);
  } else {
    Swal.fire({
      title: "Atención!",
      text: "No se puede eliminar, debe haber mínimo una pregunta.",
      icon: "warning",
    });
  }
};
const validateDataIntention = () => {
  if (initialState.name.trim() === "") {
    errorMessages.name = "Este campo es obligatorio.";
    return false;
  } else if (/\s/.test(initialState.name)) {
    errorMessages.name = "El nombre no puede contener espacios en blanco, debes reemplazar por _";
    return false;
  } else if (/[A-Z]/.test(initialState.name)) {
    errorMessages.name = "El nombre no puede contener letras mayúsculas.";
    return false;
  } else {
    errorMessages.name = "";
  }
}
const validateFields = () => {
  // Validación de Temática
  if (initialState.subjects === null) {
    Swal.fire({
      title: "Atención!",
      text: "Debes seleccionar una Temática.",
      icon: "warning",
    });
    return false;
  }
  //Validación de lenguajes de intenciones
  const isEmpty = Object.keys(textareas.value).some(key => textareas.value[key].value === '');
  if (isEmpty) {
    Swal.fire({
      title: "Atención!",
      text: "Al menos una intención está vacía!",
      icon: "warning",
    });
    return false;
  }
  // Validación de Nombre
  if (!initialState.name.trim()) {
    Swal.fire({
      title: "Atención!",
      text: "El campo de nombre no puede estar vacío.",
      icon: "warning",
    });
    return false;
  }

  // Validación de Preguntas
  for (let i = 0; i < initialState.questions.length; i++) {
    const questionSet = initialState.questions[i]['question'];
    for (let j = 0; j < questionSet.length; j++) {
      const question = questionSet[j];
      if (!question.text.trim()) {
        Swal.fire({
          title: "Atención!",
          text: "Debes llenar todas las preguntas.",
          icon: "warning",
        });
        return false;
      }
    }
  }

  // Validación de Respuestas
  if (initialState.concepts.length > 0 && initialState.answers.length < 2) {
    Swal.fire({
      title: "Atención!",
      text: "Debes tener por lo menos una repsuesta correcta y una incorrecta.",
      icon: "warning",
    });
    return false;
  }
  for (let i = 0; i < initialState.answers.length; i++) {
    const answerSet = initialState.answers[i]['answer'];
    for (let j = 0; j < answerSet.length; j++) {
      const answer = answerSet[j];
      if (!answer.text.trim()) {
        Swal.fire({
          title: "Atención!",
          text: "Debes llenar todas las respuestas.",
          icon: "warning",
        });
        return false;
      }
    }
  }

  return true;
};

const addIntent = async () => {
  if (!validateFields()) {
    return;
  }

  let postData = {
    subjects_id: initialState.subjects,
    name: initialState.name,
    information: initialState.information,
    chatbot_id: chatbot_id.value,
    questions: initialState.questions,
    answers: initialState.answers,
    concepts: initialState.concepts,
    intention_language: JSON.stringify(textareas.value)
  }

  if (props.intentionId) postData.id = props.intentionId;

  loading.value = true;
  await axios
    .post("/saveIntentions", postData)
    .then((response) => {
      if (response.data.message === 'exist') {
        Swal.fire({
          title: "Atención!",
          text: "Ya existe una intención con nombre: " + initialState.name,
          icon: "warning",
        });
      } else {
        Swal.fire({
          title: "Excelente",
          text: "Cambios realizados!",
          icon: "success",
        });
        emit("backTo", true);
      }
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
      loading.value = false;
    });
};

const nameNext = computed(() => {
  return activeStep.value === lengthItems.value ? "Guardar" : "Siguiente \u25B6";
});
const disabled = computed(() => {
  if (activeStep.value === 1) {
    return "prev";
  }
  if (activeStep.value === lengthItems.value) {
    return false;
  }
});

const conceptSelected = computed(() => {
  const lists = initialState.concepts.find(element => element.name === wordAddConcept.concept)?.lists;

  return lists ? lists.flatMap(item => item.terms) : [];
});

const calculateColumns = (totalLanguages) => {
  const totalColumns = 12;
  return Math.floor(totalColumns / totalLanguages);
};

const changeSelection = async (e) => {
  initialState.concepts = e;
  await validateAnswers();
  await mappingTerms();
}

const getIntention = (id) => {
  title.value = 'Editar Intención';
  loading.value = true;

  axios
    .get(`/getDetailIntention?intention_id=${id}`)
    .then((response) => {
      mappingDataIntention(response.data.data)
      const filteredSubjects = itemsSubjects.value.filter(subject => subject.id === initialState.subjects);

      if (filteredSubjects.length > 0) {
        const name = filteredSubjects[0].name;
        if (name === 'Mensajes chatbots') {
          flagIntention.value = true;
        } else {
          let newarra = itemsSubjects.value.filter(subject => subject.name != "Mensajes chatbots");
          itemsSubjects.value = newarra;
          flagIntention.value = false;
        }
      }
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
      loading.value = false;
    });
}

const mappingDataIntention = (intention) => {
  initialState.name = intention?.name;
  initialState.information = intention?.information;

  intention.questions.forEach(element => {
    element.question = languages.value.map(item => {
      const matchedItem = element.question.find(q => q.language === item);
      return {
        name_language: item,
        id: matchedItem ? matchedItem.id : null,
        text: matchedItem ? matchedItem.question : ""
      };
    });
  });

  intention.answers.forEach(element => {
    element.answer = languages.value.map(item => {
      const matchedItem = element.answer.find(a => a.language === item);
      return {
        name_language: item,
        id: matchedItem ? matchedItem.id : null,
        text: matchedItem ? matchedItem.answer : ""
      };
    });
  });

  intention.intention_language.forEach(intentionLang => {
    if (textareas.value.hasOwnProperty(intentionLang.language)) {
      textareas.value[intentionLang.language].value = intentionLang.name;
    }
  });

  initialState.concepts = intention.concepts.map((concept) => ({
    ...concept,
    created_at: formatDateTime(concept.created_at),
    updated_at: formatDateTime(concept.updated_at),
  }));
  initialState.questions = intention.questions;
  initialState.answers = intention.answers;
  initialState.subjects = intention.subject_id;
  if (initialState.concepts.length > 0) mappingTerms();
}

const validateAnswers = () => {

  if (initialState.concepts.length > 0) {

    while (initialState.answers.length > 2) {
      initialState.answers.pop();
    }
    let dataInicialLanguagesAnswers = languages.value.map((item) => ({
      name_language: item,
      text: "",
    }));

    while (initialState.answers.length < 2) {
      initialState.answers.push({ answer: dataInicialLanguagesAnswers });
    }

    initialState.answers.forEach((element, i) => {
      if (i == 0) element.type = CORRECT;
      if (i == 1) element.type = INCORRECT;
    });
  }

  if (initialState.concepts.length < 1) {
    initialState.answers.forEach((element, i) => {
      element.type = MULTIPLE;
    });
  }


}

const openDialogAddConceptQuestion = (indexQuestion, indexLanguage) => {
  wordAddConcept.indexQuestion = indexQuestion
  wordAddConcept.indexLanguage = indexLanguage
  dialogAddConceptQuestion.value = true;
}

const openDialogAddConceptAnswer = (indexAnswer, indexLanguage) => {
  wordAddConceptAnswer.indexAnswer = indexAnswer
  wordAddConceptAnswer.indexLanguage = indexLanguage
  dialogAddConceptAnswer.value = true;
}

const addConceptToQuestion = async () => {
  const { valid } = await formAddConcept.value.validate()
  if (!valid) return;

  dialogAddConceptQuestion.value = false;
  initialState.questions[wordAddConcept.indexQuestion]['question'][wordAddConcept.indexLanguage].text += `[${wordAddConcept.term.trim()}](${wordAddConcept.concept})`
  formAddConcept.value.reset()
  wordAddConcept.indexQuestion = null;
  wordAddConcept.indexLanguage = null;
}

const addConceptToAnswer = async () => {
  const { valid } = await formAddConceptAnswer.value.validate()
  if (!valid) return;

  dialogAddConceptAnswer.value = false;
  initialState.answers[wordAddConceptAnswer.indexAnswer]['answer'][wordAddConceptAnswer.indexLanguage].text += `{${wordAddConceptAnswer.concept}}`
  formAddConceptAnswer.value.reset()
  wordAddConceptAnswer.indexAnswer = null;
  wordAddConceptAnswer.indexLanguage = null;
}

const mappingTerms = () => {
  const arrayTermsConcept = initialState.concepts.map((concept) => ({
    name: concept.name,
    terms: concept.lists.flatMap((list) => list.terms.map((term) => term.term)),
  }));

  const arrayTermsConceptHeaders = arrayTermsConcept.map((_, index) => ({
    title: '',
    key: `term${index + 1}`,
  }));

  itemsManagementIntentions.value = generateCombinations(arrayTermsConcept);
  headersManagementIntentions.value = [...arrayTermsConceptHeaders, { title: "Respuesta", align: "center", sortable: false, key: "answer" }];
  groupByManagementIntentions.value = [];

  if (headersManagementIntentions.value.length > 2) {
    groupByManagementIntentions.value = [{ key: headersManagementIntentions.value[0].key }];
    headersManagementIntentions.value.shift();
  }
};

const generateCombinations = (conceptArray, currentIndex = 0, currentCombination = {}) => {
  if (currentIndex === conceptArray.length) return [currentCombination];

  const currentConcept = conceptArray[currentIndex];
  return currentConcept.terms.flatMap((term) => generateCombinations(conceptArray, currentIndex + 1, { ...currentCombination, [`term${currentIndex + 1}`]: term, answer: false }));
};
const getConcepts = async (chatbot_id) => {
  loading.value = true;

  let from = null;
  let to = null;
  if (dateFrom.value && dateTo.value) {
    from = moment(dateFrom.value, "YYYY-MM-DD").format("YYYY-MM-DD");
    to = moment(dateTo.value, "YYYY-MM-DD").format("YYYY-MM-DD");
  }
  axios
    .get(`/getConcepts?chatbot_id=${chatbot_id}`, {
      params: {
        ...(from && to && { from, to }),
      }
    })
    .then((response) => {
      conceptAll.value = response.data.data.map((concept) => ({
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
</script>


<style scoped>
.btn-flotante {
  position: fixed;
  bottom: 80px;
  right: 50px;
  transition: all 300ms ease 0ms;
  box-shadow: 0px 8px 15px rgba(0, 0, 0, 0.1);
  z-index: 99;
}

.btn-flotante:hover {
  background-color: #2c2fa5;
  /* Color de fondo al pasar el cursor */
  box-shadow: 0px 15px 20px rgba(0, 0, 0, 0.3);
  transform: translateY(-7px);
}

@media only screen and (max-width: 375px) {
  .btn-flotante {
    font-size: 14px;
    padding: 12px 20px;
    bottom: 20px;
    right: 20px;
  }
}

.title-datatable {
  border-bottom: 1px solid rgba(253, 253, 253, 0.2) !important;
  padding: 0.6rem 0.9rem !important;
  background-color: var(--primary-color) !important;
  margin-bottom: 15px;
  display: flex;
  justify-content: space-between;
  color: #fff;
  font-size: 22px !important;
  font-weight: 600 !important;
  color: #ffff !important;
}

.btn-log {
  background-color: #fff !important;
  color: #444 !important;
  border: 1px solid #f5f8fa !important;
  margin-left: 1em;
}
</style>

