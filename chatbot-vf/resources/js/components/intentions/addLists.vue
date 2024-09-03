<template>
  <Loader :loading="loading" />
  <v-card>
    <v-card-title class="title-datatable">
      {{ title }}
      <v-btn prepend-icon="mdi mdi-arrow-left" variant="tonal" class="btn-log" @click="$emit('backTo', true)">
        Volver
      </v-btn>
    </v-card-title>
    <v-card-subtitle>
      <span>{{ list_name }}</span>
    </v-card-subtitle>
    <v-card-text>
      <div class="d-flex mx-3 mb-2" md12>
        <v-text-field v-model="list_name" label="Nombre de Lista" placeholder="Nombre de Lista" variant="outlined" :error-messages="errorMessages.name" @input="validateDataList"/>
      </div>
      <Datatable ref="datatable" class="tabla-m" :title="'Términos'" :button_add="true" :titleAdd="'Agregar termino'"
        :headers="headers" :items="listItems" @click-add="dialogOpen" :button_reload="false"
        :showSearch="false">
        <template v-slot:[`item.id`]="{ item }">
          <v-container>
            <v-row align="center" justify="center">
              <v-col cols="auto">
                <v-tooltip text="Eliminar" location="top">
                  <template v-slot:activator="{ on }">
                    <v-btn icon size="small" v-on="on" @click="editTerm(item)">
                      <v-icon color="#a1a5b7">mdi-file-document-edit</v-icon>
                    </v-btn>
                  </template>
                  <span>Editar</span>
                </v-tooltip>
              </v-col>
              <v-col cols="auto">
                <v-tooltip text="Eliminar" location="top">
                  <template v-slot:activator="{ on }">
                    <v-btn icon size="small" v-on="on" @click="deleteTerm(item)">
                      <v-icon color="#a1a5b7">mdi mdi-trash-can</v-icon>
                    </v-btn>
                  </template>
                  <span>Eliminar</span>
                </v-tooltip>
              </v-col>
            </v-row>
          </v-container>
        </template>
        <template v-slot:[`item.lang`]="{ item }">
          <div class="d-flex align-center">
            <div>
              <span v-for="(value, lang) in item.lang" :key="lang">
                <img v-if="lang === 'castellano'" src="../../../images/Castellano.png" alt="Castellano" width="20">
                <img v-else-if="lang === 'ingles'" src="../../../images/Ingles.png" alt="Inglés" width="20">
                <img v-else-if="lang === 'valenciano'" src="../../../images/Valenciano.png" alt="Valenciano" width="20">
                {{ value.value }}
                <br>
              </span>
            </div>
          </div>
        </template>
        <template v-slot:[`item.synonyms`]="{ item }">
          <div class="d-flex align-center">
            <div>
              <span v-for="(value, synonyms) in item.synonyms" :key="lang">
                <img v-if="synonyms === 'castellano'" src="../../../images/Castellano.png" alt="Castellano" width="20">
                <img v-else-if="synonyms === 'ingles'" src="../../../images/Ingles.png" alt="Inglés" width="20">
                <img v-else-if="synonyms === 'valenciano'" src="../../../images/Valenciano.png" alt="Valenciano"
                  width="20">
                {{ value.value }}
                <br>
              </span>
            </div>
          </div>
        </template>
      </Datatable>
      <v-dialog max-width="1000" persistent v-model="dialogTerms">
        <v-card :title="titleTerm">
          <v-card-text>
            <v-text-field label="Nombre" placeholder="Nombre" class="mx-3" v-model="term_general"
              variant="outlined"></v-text-field>
            <div class="d-flex" v-for="lang in languages">
              <v-col cols="6">
                <label v-if="lang === 'castellano'">
                  <strong>(Castellano)</strong>
                  &nbsp;
                  <img src="../../../images/Castellano.png" alt="" width="30" />
                </label>
                <label v-else-if="lang === 'ingles'">
                  <strong>(Inglés)</strong>
                  &nbsp;
                  <img src="../../../images/Ingles.png" alt="" width="30" />
                </label>
                <label v-else-if="lang === 'valenciano'">
                  <strong>(Valenciano)</strong>
                  &nbsp;
                  <img src="../../../images/Valenciano.png" alt="" width="30" />
                </label>
                <v-text-field v-model="term[lang].value" variant="outlined"></v-text-field>
              </v-col>
              <v-col cols="6">
                <label v-if="lang === 'castellano'">
                  <strong>Sinónimos (Castellano)</strong>
                  &nbsp;
                  <img src="../../../images/Castellano.png" alt="" width="30" />
                </label>
                <label v-else-if="lang === 'ingles'">
                  <strong>Sinónimos (Inglés)</strong>
                  &nbsp;
                  <img src="../../../images/Ingles.png" alt="" width="30" />
                </label>
                <label v-else-if="lang === 'valenciano'">
                  <strong>Sinónimos (Valenciano)</strong>
                  &nbsp;
                  <img src="../../../images/Valenciano.png" alt="" width="30" />
                </label>
                <v-combobox v-model="synonym[lang].value" item-value="id" item-title="synonym" clearable chips multiple
                  return-object></v-combobox>
              </v-col>
            </div>
          </v-card-text>
          <v-card-actions class="pt-0">
            <v-spacer></v-spacer>
            <v-btn variant="elevated" :color="global.color" @click="addTerm">
              Guardar
            </v-btn>
            <v-btn variant="tonal" @click="dialogTerms = false">
              Cancelar
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-card-text>
    <v-card-actions>
      <v-spacer />
      <v-btn v-if="ListUpdate" class="btn-search ml-3" :disabled="listItems.length === 0 || list_name === '' || saveBtn"
        @click="updateList()">
        Guardar
      </v-btn>
      <v-btn v-else class="btn-search ml-3" :disabled="listItems.length === 0 || list_name === '' || saveBtn"
        @click="createList()">
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
import axios from "axios";
import Swall from "sweetalert2";
import { useGlobalStore } from "../store/global";
import Datatable from "../utilities/Datatable.vue";
const global = useGlobalStore();

const props = defineProps({
  dataChat: { type: Object, default: null },
  listId: { type: Number, default: null },
  editList: { type: Object, default: null }
});
const headers = ref([
  { title: "Nombre", align: "start", sortable: true, key: "term", },
  { title: "Lenguaje términos", align: "start", sortable: true, key: "lang", },
  { title: "Sinónimos", align: "start", sortable: true, key: "synonyms", },
  { title: "Opciones", align: "center", sortable: true, key: "id", },
]);
const term_general = ref('')
const titleTerm = ref('')
const deleteItem = ref('')
const saveBtn = ref(false)

const languages = ref(props.dataChat.settings
  .filter((setting) => setting.name_setting !== "idioma_principal")
  .map((setting) => setting.name_setting));

const term = ref(languages.value.reduce((acc, lang) => {
  acc[lang] = { value: '' };
  return acc;
}, {}));

const synonym = ref(languages.value.reduce((acc, lang) => {
  acc[lang] = {};
  return acc;
}, {}));

const listItems = ref([]);
const errorMessages = reactive({
  name: null,
});
const dialogTerms = ref(false)
const list_id = ref('');
const list_name = ref('');
const title = ref('');
const loading = ref(false);
const chatbot_id = ref(null);
const ListUpdate = ref(false);
const emit = defineEmits(["backTo", "refresh-synonyms"]);

const dialogOpen = () => {
  dialogTerms.value = true
  titleTerm.value = 'Agregar término',
  clearDataTerm()
}

const editTerm = (item) => {
  titleTerm.value = 'Editar término'
  dialogTerms.value = true
  term_general.value = item.term;
  if (typeof item.lang === 'object' && !Array.isArray(item.lang)) {
    Object.keys(item.lang).forEach((lang) => {
      if (term.value.hasOwnProperty(lang)) {
        term.value[lang].value = item.lang[lang].value;
      }
    });
  }
  if (typeof item.synonyms === 'object' && !Array.isArray(item.synonyms)) {
    Object.keys(item.synonyms).forEach((lang) => {
      if (synonym.value.hasOwnProperty(lang)) { // Verificar si el idioma existe en synonym.value
        synonym.value[lang].value = item.synonyms[lang].value;
      }
    });
  }
  deleteItem.value = item;

};

const deleteTerm = (itemToDelete) => {
  const index = listItems.value.indexOf(itemToDelete);
  if (index !== -1) {
    listItems.value.splice(index, 1);
  }
};
const addTerm = () => {
  if (!validateData()) {
    return;
  }
  if(titleTerm.value === 'Editar término'){
    deleteTerm(deleteItem.value);
  }
  const termAlreadyExists = listItems.value.some(item => item.term === term_general.value);
  if (termAlreadyExists) {
    Swall.fire({
      title: "Atención!",
      text: "Ya existe un término '" + term_general.value + "'",
      icon: "warning",
    });
    return;
  }

  const keysTerms = Object.keys(term.value);
  let breakTerms = false
  keysTerms.forEach(key => {
  const termLangAlreadyExists = listItems.value.some(item => item.lang[key].value == term.value[key].value);
  if (termLangAlreadyExists) {
        Swall.fire({
          title: "Atención!",
          text: "Ya existe un término en "+key+ " igual a '" + term.value[key].value+ "'.",
          icon: "warning",
        });
        breakTerms = true
        return;
      }

  })
  if(breakTerms){
    return
  }

  const keysSynonym = Object.keys(synonym.value);
  let breakSynonym = false
  keysSynonym.forEach(key => {
  const synonymAlreadyExists = listItems.value.some(item => {
    return item.synonyms[key].value.some(synon => {
      if(synon != "N/A"){
        if (synonym.value[key].value.includes(synon)) {
          return true;
        } else {
          return false;
        }
      } else {
        return false;
      }
    });
  });

  if (synonymAlreadyExists) {
        Swall.fire({
          title: "Atención!",
          text: "Ya existe un sinónimo en "+key+ " igual a '" + synonym.value[key].value+ "'.",
          icon: "warning",
        });
        breakSynonym = true
        return;
      }

  })
  if(breakSynonym){
    return
  }

  const clonedTerm = JSON.parse(JSON.stringify(term.value));
  const clonedSynonym = JSON.parse(JSON.stringify(synonym.value));
  listItems.value.push({ term: term_general.value, lang: clonedTerm, synonyms: clonedSynonym });
  clearDataTerm();
};
const clearDataTerm = () => {
  term_general.value = '';
  for (let lang in term.value) {
    term.value[lang].value = '';
  }
  for (let lang in synonym.value) {
    synonym.value[lang] = {};
  }
};

const validateDataList = () => {
  if (list_name.value.trim() === "") {
    errorMessages.name = "Este campo es obligatorio.";
    saveBtn.value = true
    return false;
  } else if (/\s/.test(list_name.value)) {
    errorMessages.name = "El nombre no puede contener espacios en blanco, debes reemplazar por _";
    saveBtn.value = true
    return false;
  } else if (/[A-Z]/.test(list_name.value)) {
    errorMessages.name = "El nombre no puede contener letras mayúsculas.";
    saveBtn.value = true
    return false;
  } else {
    errorMessages.name = "";
    saveBtn.value = false
  }
}

const validateData = () => {
  if (term_general.value.trim() === "") {
    Swall.fire({
      title: "Atención!",
      text: "El campo nombre es obligatorio.",
      icon: "warning",
    });
    return false;
  }
  for (const lang in synonym.value) {
    if (Object.keys(synonym.value[lang]).length === 0) {
      synonym.value[lang].value = ['N/A'];
    }
    if (synonym.value.hasOwnProperty(lang) && synonym.value[lang].value.length === 0) {
      synonym.value[lang].value = ['N/A'];
    }
  }
  for (const lang in synonym.value) {
    if (synonym.value.hasOwnProperty(lang) && synonym.value[lang].value.length === 0) {
      synonym.value[lang].value = ['N/A'];
    }
  }
  const isEmpty = Object.values(term.value).some(lang => lang.value === '');
  if (isEmpty) {
    Swall.fire({
      title: "Atención!",
      text: "Todos los campos según el idioma deben ser diligenciados.",
      icon: "warning",
    });
    return false;
  }
  return true;
};
onMounted(async () => {
  await loadData();
  if (props.editList) {
    list_name.value = props.editList.name;
    list_id.value = props.editList.terms[0].list_id;
    listItems.value = [];
    let e = props.editList.terms;
    const uniqueTerms = new Set();
    e.forEach((item) => {
      uniqueTerms.add(item.term);
    });
    uniqueTerms.forEach((term) => {
      const filteredItems = e.filter((item) => item.term === term);
      const termObject = {
        term,
        lang: {},
        synonyms: {}
      };
      const uniqueSynonyms = new Set();
      let langExample = {};
      filteredItems.forEach((item) => {
        langExample[item.language] = { value: item.lang_term };
        item.synonyms.forEach((synonymObj) => {
          const lang = synonymObj.language;
          const synonymValue = synonymObj.synonym;
          if (!uniqueSynonyms.has(synonymValue)) {
            uniqueSynonyms.add(synonymValue);
            if (!termObject.synonyms[lang]) {
              termObject.synonyms[lang] = { value: [] };
            }
            termObject.synonyms[lang].value.push(synonymValue);
          }
        });
      });

      termObject.lang = langExample;

      listItems.value.push(termObject);

      listItems.value.forEach(item => {
          languages.value.forEach(language => {
            if(item.lang[language]){
            } else {
              item.lang[language] = {
                value : ''
              }
            }

            if(item.synonyms[language]){
            } else {
              item.synonyms[language] = {
                value : []
              }
              item.synonyms[language].value.push("N/A")
            }
        })
      })
    });
    loading.value = false
    ListUpdate.value = true
    title.value = 'Editar lista'
  } else {
    ListUpdate.value = false
    title.value = 'Agregar lista'
    loading.value = false
  }
});

const loadData = async () => {
  loading.value = true;
  chatbot_id.value = props.dataChat.id;
  loading.value = false;
};

const createList = async () => {
  try {
    loading.value = true;
    const formData = {
      name_list: list_name.value,
      list: JSON.stringify(listItems.value),
      chatbot_id: props.dataChat.id
    }

    await axios.post("createLists", formData)
      .then((response) => {
        Swall.fire({
          title: "Excelente",
          text: response.data.message,
          icon: "success",
        });
        emit('backTo', true)
      })
      .catch((error) => {
        Swall.fire({
          title: "Atención!",
          text: error.response.data.message,
          icon: "warning",
        });
      })
      .finally(() => {
        loadData()
        loading.value = false;
      });
  } catch (error) {
    console.error(error);
  }
}

const updateList = async () => {
  loading.value = true;
  try {
    const formData = {
      name_list: list_name.value,
      list: JSON.stringify(listItems.value),
      chatbot_id: props.dataChat.id
    }
    await axios.post("updateLists/" + list_id.value, formData)
      .then((response) => {
        Swall.fire({
          title: "Excelente",
          text: response.data.message,
          icon: "success",
        });
        emit('backTo', true)
      })
      .catch((error) => {
        loading.value = false;
        Swall.fire({
          title: "Atención!",
          text: error.response.data.message,
          icon: "warning",
        });
      })
      .finally(() => {
        loading.value = false;
        loadData()
      });
  } catch (error) {
    console.error(error);
  }
}

</script>

<style>
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
