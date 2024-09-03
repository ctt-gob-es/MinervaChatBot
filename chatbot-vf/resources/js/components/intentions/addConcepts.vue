<template>
  <Loader :loading="loading" />
  <v-card elevation="16">
    <v-card-title class="title-datatable">
      {{ title }}
      <v-btn prepend-icon="mdi mdi-arrow-left" variant="tonal" class="btn-log" @click="$emit('backTo', true)">
        Volver
      </v-btn>
    </v-card-title>
    <v-card-subtitle>
      <span>{{ conceptsSelect.name }}</span>
    </v-card-subtitle>
    <v-card-text>
      <div class="d-flex mx-3" md12>
        <v-text-field v-model="conceptsSelect.name" label="Nombre del Contexto" disabled placeholder="Nombre del Contexto"
          variant="outlined" class="mr-2" :error-messages="errorMessages.name" @input="validateDataConcept" />
      </div>
      <div class="d-flex" md12>
        <template v-for="lang in languages">
          <v-col :cols="calculateColumns(languages.length)">
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
      <div class="d-flex" md12>
        <template v-for="lang in languages">
          <v-col :cols="calculateColumns(languages.length)">
            <label v-if="lang === 'castellano'" class="mr-2">
              <strong>Respuesta (Castellano)</strong>
              &nbsp;
              <img src="../../../images/Castellano.png" alt="" width="30" />
            </label>
            <label v-else-if="lang === 'ingles'" class="mr-2">
              <strong>Respuesta (Inglés)</strong>
              &nbsp;
              <img src="../../../images/Ingles.png" alt="" width="30" />
            </label>
            <label v-else-if="lang === 'valenciano'" class="mr-2">
              <strong>Respuesta (Valenciano)</strong>
              &nbsp;
              <img src="../../../images/Valenciano.png" alt="" width="30" />
            </label>
            <v-textarea v-model="textareaserror[lang].value" variant="outlined" rows="3" hide-details>
            </v-textarea>
          </v-col>
        </template>
      </div>
      <div>
        <Datatable v-if="tableRender" ref="datatable" class="tabla-m mt-2" title="Listas" :headers="headersList"
          :items="lists" :initSelection="conceptsSelect.lists" :showSearch="true" :enableSelect="true"
          :selectStrategy="'single'" :button_reload="true" @click-reload="getLists(chatbot_id)"
          @changeSelection="changeSelection($event)">
        </Datatable>
      </div>

    </v-card-text>
    <v-card-actions>
      <v-spacer />
      <v-btn v-if="conceptUpdate" class="btn-search ml-3" :disabled="saveBtn" @click="updateConcept(conceptsSelect.id)">
        Guardar
      </v-btn>
      <v-btn v-else class="btn-search ml-3" :disabled="saveBtn" @click="createConcept()">
        Guardar
      </v-btn>
      <v-btn variant="tonal" class="black-close" text @click="$emit('backTo', true)">
        Cancelar
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, onMounted, reactive } from "vue";
import Loader from "../utilities/Loader.vue";
import Datatable from "../utilities/Datatable.vue";
import axios from "axios";
import Swall from "sweetalert2";

const props = defineProps({
  dataChat: { type: Object, default: null },
  lists: { type: Array, default: [] },
  conceptId: { type: Number, default: null },
  editConcept: { type: Object, default: null }
});
const chatbot_id = ref(null);
const title = ref(null);
const languages = ref(props.dataChat.settings
  .filter((setting) => setting.name_setting !== "idioma_principal")
  .map((setting) => setting.name_setting));

const textareas = ref(languages.value.reduce((acc, lang) => {
  acc[lang] = { value: '' };
  return acc;
}, {}));

const textareaserror = ref(languages.value.reduce((acc, lang) => {
  acc[lang] = { value: '' };
  return acc;
}, {}));

const emit = defineEmits(["backTo", "refresh-synonyms"]);
const errorMessages = reactive({
  name: null,
  question: null
});
const tableRender = ref(false);
const loading = ref(false);
const saveBtn = ref(false);
const conceptUpdate = ref(false);
const lists = ref([]);

const conceptsSelect = ref({
  id: "",
  key: "",
  name: "",
  question: "",
  lists: [],
});

const headersList = ref([
  { title: "Listas", align: "start", sortable: true, key: "name", },
]);

onMounted(async () => {
  if (props.editConcept) {
    const editedConcept = { ...props.editConcept }; // Clonar el concepto editado
    editedConcept.lists.forEach(list => {
      delete list.pivot;
      delete list.terms;
    });
    title.value = 'Editar Contexto'
    conceptsSelect.value = editedConcept;
    conceptsSelect.value.concept_languages.forEach(conceptLang => {
      if (textareas.value.hasOwnProperty(conceptLang.language)) {
        textareas.value[conceptLang.language].value = conceptLang.question;
      }
    });
    conceptsSelect.value.concept_errors.forEach(conceptError => {
      if (textareaserror.value.hasOwnProperty(conceptError.language)) {
        textareaserror.value[conceptError.language].value = conceptError.answer;
      }
    });

    conceptUpdate.value = true;
  } else {
    title.value = 'Agregar Contexto'
    conceptUpdate.value = false;
  }
  await loadData();
  tableRender.value = true
});

const getLists = async (chatbotId) => {
  axios
    .get(`/getLists?chatbot_id=${chatbotId}`)
    .then((response) => {
      response.data.data.forEach(list => {
        delete list.terms
      })
      lists.value = response.data.data
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
    })
}

const loadData = async () => {
  chatbot_id.value = props.dataChat.id;
  await getLists(chatbot_id.value);
};

const validateDataConcept = () => {
  if (conceptsSelect.value.name.trim() === "") {
    errorMessages.name = "Este campo es obligatorio.";
    saveBtn.value = true
    return false;
  } else if (/\s/.test(conceptsSelect.value.name)) {
    errorMessages.name = "El nombre no puede contener espacios en blanco, debes reemplazar por _";
    saveBtn.value = true
    return false;
  } else if (/[A-Z]/.test(conceptsSelect.value.name)) {
    errorMessages.name = "El nombre no puede contener letras mayúsculas.";
    saveBtn.value = true
    return false;
  } else {
    errorMessages.name = "";
    saveBtn.value = false
  }
}
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
  const isEmptyError = Object.keys(textareaserror.value).some(key => textareaserror.value[key].value === '');
  if (isEmptyError) {
    Swall.fire({
      title: "Atención!",
      text: "Al menos una pregunta está vacía!",
      icon: "warning",
    });
    return false;
  }
  if (!Object.keys(conceptsSelect.value.lists).length) {
    Swall.fire({
      title: "Atención!",
      text: "Debes seleccionar como mínimo una lista.",
      icon: "warning",
    });
    return false;
  }
  return true;
};
const createConcept = async () => {
  if (!validateData()) {
    return;
  }
  try {
    let requestObject = {
      concept: conceptsSelect.value,
      question: JSON.stringify(textareas.value),
      error: JSON.stringify(textareaserror.value),
      chatbot_id: props.dataChat.id
    }
    await axios.post("createConcepts", requestObject)
      .then((response) => {
        if (response?.data?.success) {
          Swall.fire({
            title: "Correcto!",
            text: "Datos guardados",
            icon: "success",
          });
          emit('backTo', true)
        } else {
          Swall.fire({
            title: "Atención",
            html: `Ha ocurrido un error al guardar el parámetro <b>${conceptsSelect.value.name}</b>`,
            icon: "warning",
          });
        }
      })
      .catch((error) => {
        console.error(error);
        Swall.fire({
          title: "Atención!",
          text: error.response.data.message,
          icon: "warning",
        });
      })
      .finally(() => {
        loadData()
      });
  } catch (error) {
    console.error(error);
  }
}

const updateConcept = async (id) => {
  if (!validateData()) {
    return;
  }
  try {
    let requestObject = {
      concept: conceptsSelect.value,
      question: JSON.stringify(textareas.value),
      error: JSON.stringify(textareaserror.value),
      chatbot_id: props.dataChat.id
    }
    await axios.post("updateConcepts/" + id, requestObject)
      .then((response) => {
        if (response?.data?.success) {
          Swall.fire({
            title: "Correcto!",
            text: "Datos guardados",
            icon: "success",
          });
          emit('backTo', true)
        } else {
          Swall.fire({
            title: "Atención",
            html: `Ha ocurrido un error al guardar el parámetro <b>${conceptsSelect.value.name}</b>`,
            icon: "warning",
          });
        }
      })
  } catch (error) {
    console.error(error);
    Swall.fire({
      title: "Atención!",
      text: error.response.data.message,
      icon: "warning",
    });
  }

}

const changeSelection = (e) => {
  conceptsSelect.value.lists = e
  if (e.length != 0) {

    conceptsSelect.value.name = e[0].name
  } else {
    conceptsSelect.value.name = ''
  }
}

const calculateColumns = (totalLanguages) => {
  const totalColumns = 12;
  return Math.floor(totalColumns / totalLanguages);
};

</script>

<style scoped>
.title-datatable {
  display: flex !important;
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
