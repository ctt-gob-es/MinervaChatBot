<template>
  <div class="d-flex">
    <v-spacer></v-spacer>
    <div v-for="(language, i) in languageList" :key="i" class="language-select mr-2" @click="changeLanguage(language.language)">
      <img :src="language.src" alt="" width="30" :class="defaultLanguage == language.language ? 'selected': ''"/>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import castellanoImage from '../../../images/Castellano.png';
import inglesImage from '../../../images/Ingles.png';
import valencianoImage from '../../../images/Valenciano.png';

const emit = defineEmits(["click-language"]);

const props = defineProps({
  items: {type: Array, default: () => []},
  defaultLanguage: {type: String, default: () => ''}
})

const languageList = ref([
  {language: 'castellano', src: castellanoImage},
  {language: 'ingles', src: inglesImage},
  {language: 'valenciano', src: valencianoImage},
]);

onMounted(async () => {
  await filterList()
})

const filterList = () => {
  languageList.value = languageList.value.filter(language => {
    return props.items.some(item => item.name_setting === language.language);
  });
  languageList.value.sort((a, b) => {
    if (a.language === props.defaultLanguage) return -1;
    if (b.language === props.defaultLanguage) return 1;
    return 0;
  });
}

const changeLanguage = (language) => {
  emit('click-language', language);
}

</script>

<style>
.language-select{
  cursor: pointer;
}
.selected{
  padding: 2px;
  border: var(--dfNodeSelectedBorderSize) solid var(--primary-color);
}
</style>
