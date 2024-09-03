<template>
  <div ref="el" class="node-faqs">
    <div class="title-box"><i class=""></i> Pregunta ciudadana</div>
    <div class="box ">
      <div class="d-flex justify-center mb-3">
        <v-btn class="mr-2" color="success" prepend-icon="mdi-plus" @click="addIntention">FAQ</v-btn>
        <v-btn v-if="!freeQuestionActive" class="ml-auto" color="red" prepend-icon="mdi-plus" @click="addFreeQuestion">Pregunta libre</v-btn>
      </div>
      <div class="d-flex align-center mb-2" v-for="(intention, index) in intentions" :key="index">
        <v-autocomplete v-model="intentions[index]" :items="props.intentions" item-title="name" item-value="id" class="mx-2 mr-2" variant="outlined" density="compact" label="Intención" hide-details @update:modelValue="updateData" return-object></v-autocomplete>
        <v-icon color="red" icon="mdi-close-circle" @click="deleteIntention(index)"></v-icon>
      </div>
      <div v-if="freeQuestionActive">
        <select-language :items="languages" :defaultLanguage="languageSelected" @click-language="changeLanguage"></select-language>
        <div class="d-flex align-center">
          <v-textarea v-for="(language, i) in languages" :key="i" v-show="languageSelected == freeQuestions[i].language" v-model="freeQuestions[i].message" label="Mensaje" variant="outlined" class="ma-2" rows="3" hide-details @change="updateData"></v-textarea>
          <v-icon color="red" icon="mdi-close-circle" @click="deleteFreeQuestion(index)"></v-icon>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, getCurrentInstance, onBeforeMount, nextTick, watch } from 'vue';
import { emptyObject } from '../../composables/methodsBuilder.js';
import selectLanguage from '../selectLanguage.vue';
import useEventsBus from '../../../eventBus.js';

const { bus } = useEventsBus()

const typeNode = 'faq';
const el = ref(null);
const nodeId = ref(0);
const dataNode = ref({});
const freeQuestions = ref([]);
const freeQuestionActive = ref(false);
const intentions = ref([]);
const languageSelected = ref('');

let df = null;
df = getCurrentInstance().appContext.config.globalProperties.$df.value;

const props = defineProps({
  languages: {type: Array, default: () => []},
  intentions: {type: Array, default: () => []},
  defaultLanguage: {type: String, default: () => ''}
})

languageSelected.value = props.defaultLanguage;

freeQuestions.value = props.languages.map((item) => ({
  language: item.name_setting,
  message: '',
}))

onBeforeMount(async () => {
  await nextTick()
  nodeId.value = el.value.parentElement.parentElement.id.slice(5)
  dataNode.value = df.getNodeFromId(nodeId.value)

  await getDataNode();
  updateData();
})

watch(()=>bus.value.get('FAQ'), (val) => {
})

const getDataNode = () => {
  if (!emptyObject(dataNode.value?.data) && 'info' in dataNode.value.data){
    if('messages' in dataNode.value.data.info){
      freeQuestions.value.forEach(element => {
        let object = dataNode.value.data.info.messages.find((field) => {
          return element.language == field.language
        })
        if (object) {
          element.message = object.message;
          element.language = object.language;
        }
      });
      freeQuestionActive.value = true;
    }
    if('intentions' in dataNode.value.data.info){
      intentions.value = dataNode.value.data.info.intentions;
    }
  }
}

const addIntention = () => {
  if(intentions.value.length == 0){
    df.addNodeOutput(nodeId.value);
    dataNode.value = df.getNodeFromId(nodeId.value)
  }
  intentions.value.push({name: '', id: null});
  updateData();
}

const addFreeQuestion = () => {
  if(freeQuestionActive.value) return
  df.addNodeOutput(nodeId.value);
  dataNode.value = df.getNodeFromId(nodeId.value)
  freeQuestionActive.value = true
  updateData();
}

const deleteIntention = (index) => {
  intentions.value.splice(index, 1);
  if(intentions.value.length == 0) removeOutput()
  updateData();
}

const deleteFreeQuestion = (index) => {
  freeQuestionActive.value = false
  if(!freeQuestionActive.value) removeOutput()
  updateData();
}

const updateData = async () => {
  await nextTick()

  let data = {
    type: typeNode,
    info: {
      intentions: intentions.value.length > 0 ? intentions.value : undefined,
      messages: freeQuestionActive.value ? freeQuestions.value : undefined,
      buttons: []
    }
  }
  if(intentions.value.length > 0) data.info.buttons.push({button: 'yes'})
  if(freeQuestionActive.value) data.info.buttons.push({button: 'no'})

  if (df && typeof df.updateNodeDataFromId === 'function'){
    df.updateNodeDataFromId(nodeId.value, data);
  }
  df.updateConnectionNodes(el.value.parentElement.parentElement.id);
}

const removeOutput = () => {
  if(dataNode.value?.outputs && !emptyObject(dataNode.value?.outputs)){
    let lastOutputProperty = Object.keys(dataNode.value?.outputs).reduce((lastProperty, key) => {
      return key.includes('output') ? key : lastProperty;
    }, null);

    df.removeNodeOutput(nodeId.value, lastOutputProperty);
    dataNode.value = df.getNodeFromId(nodeId.value)
  }
}

const changeLanguage = (language) => {
  languageSelected.value = language;
}

</script>
<style>
.node-faqs{
  min-width: 320px;
}
</style>
