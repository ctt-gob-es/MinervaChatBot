<template>
  <div ref="el">
    <div class="title-box">Inicio</div>
    <div class="box">
      <select-language :items="languages" :defaultLanguage="languageSelected" @click-language="changeLanguage"></select-language>
      <v-textarea v-for="(language, i) in languages" :key="i" v-show="languageSelected == dataMessage[i].language" v-model="dataMessage[i].message" label="Mensaje" variant="outlined" class="ma-2" rows="4" hide-details @change="updateData"></v-textarea>
    </div>
  </div>
</template>

<script setup>
import { ref, getCurrentInstance, onBeforeMount, nextTick, watch } from 'vue';
import { emptyObject } from '../../composables/methodsBuilder.js'
import useEventsBus from '../../../eventBus.js';
import selectLanguage from '../selectLanguage.vue';

const { bus } = useEventsBus()

const typeNode = 'start';
const el = ref(null);
const nodeId = ref(0);
const dataNode = ref({});
const dataMessage = ref([]);
const languageSelected = ref('');

let df = null;
df = getCurrentInstance().appContext.config.globalProperties.$df.value;


const props = defineProps({
  idChat: {type: String, defautl: () => ''},
  languages: {type: Array, default: () => []},
  defaultLanguage: {type: String, default: () => ''}
})

languageSelected.value = props.defaultLanguage;

dataMessage.value = props.languages.map((item) => ({
  language: item.name_setting,
  message: '',
}))


onBeforeMount(async () => {
  await nextTick()
  nodeId.value = el.value.parentElement.parentElement.id.slice(5)
  dataNode.value = df.getNodeFromId(nodeId.value)

  await getDataNode()
  updateData()
})


const getDataNode = () => {
  if (!emptyObject(dataNode.value?.data) && 'info' in dataNode.value.data){
    if('messages' in dataNode.value.data.info){
      dataMessage.value.forEach(element => {
        let object = dataNode.value.data.info.messages.find((field) => {
          return element.language == field.language
        })
        if (object) {
          element.message = object.message;
          element.language = object.language;
        }
      });
    }
  }
}

watch(() => bus.value.get('Start'), async (val) => {
})

const updateData = () => {
  let data = {
    type: typeNode,
    id_chatbot: props.idChat != '' ? props.idChat : undefined,
    info: {
      messages: dataMessage.value,
    }
  }

  df.updateNodeDataFromId(nodeId.value, data);
  dataNode.value = df.getNodeFromId(nodeId.value)
}

const changeLanguage = (language) => {
  languageSelected.value = language;
}
</script>

<style>
</style>
