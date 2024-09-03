<template>
  <button class="sc-user-input--button-icon-wrapper">
    <v-icon :color="color" :icon="tooltip"></v-icon>
  </button>
</template>

<script>
import IconBase from './utilities/IconBase.vue'

export default {
  components: {
    IconBase
  },
  props: {
    color: {
      type: String,
      required: true
    },
    tooltip: {
      type: String,
      default: ''
    }
  }
}
</script>

<style scoped>
.sc-user-input--button-icon-wrapper {
  background: none;
  border: none;
  padding: 0px;
  margin: 0px;
  outline: none;
  cursor: pointer;
}
</style>
