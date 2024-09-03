<template>
  <div class="sc-decision-row mb-2" :style="{background: colors.messageList.bg}">
    <button
      v-for="(decision, idx) in actions"
      :key="idx"
      class="sc-decision-element"
      :style="{
        color: colors.sentMessage.text,
        background: colors.sentMessage.bg,
        fontSize: fontSize + 'px'
      }"
      @click="$emit('sendDecision', decision)"
    >
      {{ decision.text }}
    </button>
  </div>
</template>

<script>
export default {
  emits: ["sendDecision"],
  props: {
    actions: {
      type: Array,
      default: () => []
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
    return {}
  }
}
</script>

<style>
.text-decision{
  font-weight: 600;
  margin-left: 12px;
  margin-bottom: 3px;
}
.sc-decision-row {
  text-align: center;
  background: inherit;
}

.sc-decision-element {
  margin: 3px;
  padding: 5px 10px 5px 10px;
  border: 1px solid;
  border-radius: 8px;
  font-size: 14px;
  background: inherit;
  cursor: pointer;
}
</style>
