<template>
  <div class="sc-suggestions-row mb-2" :style="{background: colors.messageList.bg}">
    <button
      v-for="(suggestion, idx) in suggestions"
      :key="idx"
      class="sc-suggestions-element"
      :style="{
        color: colors.sentMessage.text,
        background: colors.sentMessage.bg,
        fontSize: fontSize + 'px'
      }"
      @click="$emit('sendSuggestion', suggestion)"
    >
      <div v-if="message && message?.typeNode == 'language'">
        <div v-if="showImageLanguage(message, suggestion)">
          <img :src="languageList[showImageLanguage(message, suggestion)]" alt="" width="30"/>
        </div>
        <div v-else>{{ suggestion }}</div>
      </div>
      <div v-else> {{ suggestion }}</div>
    </button>
  </div>
</template>

<script>
import { mappingLanguage } from '../composables/methods.js'
import castellanoImage from '../../../images/Castellano.png';
import inglesImage from '../../../images/Ingles.png';
import valencianoImage from '../../../images/Valenciano.png';

export default {
  emits: ["sendSuggestion"],
  props: {
    suggestions: {
      type: Array,
      default: () => []
    },
    message: {
      type: Object,
      default: () => null
    },
    colors: {
      type: Object,
      required: true
    },
    fontSize: {
      type: String,
      required: true
    },
  },
  data() {
    return {
      languageList: {
        'castellano': castellanoImage,
        'ingles': inglesImage,
        'valenciano': valencianoImage,
      }
    }
  },
  methods: {
    showImageLanguage(message, suggestion){
      let response = null;
      if(message && message?.typeNode == 'language'){
        let language = mappingLanguage(suggestion)
        if (language) response = language
      }
      return response;
    }
  },
}
</script>

<style>
.text-suggestions{
  font-weight: 600;
  margin-left: 12px;
  margin-bottom: 3px;
}
.sc-suggestions-row {
  text-align: center;
  background: inherit;
}

.sc-suggestions-element {
  margin: 3px;
  padding: 5px 10px 5px 10px;
  border: 1px solid;
  border-radius: 8px;
  font-size: 14px;
  background: inherit;
  cursor: pointer;
}
</style>
