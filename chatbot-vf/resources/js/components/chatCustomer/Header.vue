<template>
  <div class="sc-header" :style="{background: colors.header.bg, color: colors.header.text}">
    <img v-if="titleImageUrl" class="sc-header--img" :src="titleImageUrl" alt=""/>
    <div class="sc-header--title"><slot>{{ title }}</slot></div>

    <div class="sc-header--actions d-flex ml-auto">
      <div v-if="showDownloadButton" class="buttons-actions mr-1" @click="$emit('downloadConversation')">
        <v-icon icon="mdi-file-download"></v-icon>
      </div>
      <div v-if="!windowIsMaximized" class="buttons-actions mr-1" @click="$emit('maximizeChat')">
        <v-icon icon="mdi-arrow-expand"></v-icon>
      </div>
      <div v-else class="buttons-actions" @click="$emit('minimizeChat')">
        <v-icon icon="mdi-arrow-collapse"></v-icon>
      </div>
      <div v-if="showFontSizeButton" class="buttons-actions" @click="$emit('changeFontSize')">
        <v-icon icon="mdi-format-font"></v-icon>
      </div>
      <div v-if="showCloseButton" class="buttons-actions ml-1" @click="$emit('close')">
        <v-icon icon="mdi-close"></v-icon>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState } from '../store/chat-customer'
import CloseIcon from '../assets/close-icon-big.png'

export default {
  props: {
    icons: {
      type: Object,
      default: function () {
        return {
          close: {
            img: CloseIcon,
            name: 'default'
          }
        }
      }
    },
    title: {
      type: String,
      required: true
    },
    colors: {
      type: Object,
      required: true
    },
    windowIsMaximized: {
      type: Boolean,
      required: true
    },
  },
  data() {
    return {
      inUserList: false,
      maximize: false,
    }
  },
  computed: {
    ...mapState(['disableUserListToggle', 'titleImageUrl', 'showCloseButton', 'showFontSizeButton', 'showDownloadButton'])
  },
  methods: {
  }
}
</script>

<style scoped>
.sc-header {
  min-height: 75px;
  border-top-left-radius: 9px;
  border-top-right-radius: 9px;
  padding: 10px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
  position: relative;
  box-sizing: border-box;
  display: flex;
}

.sc-header--img {
  border-radius: 50%;
  align-self: center;
  padding: 10px;
  max-height: 60px;
  max-width: 60px;
}

.sc-header--title {
  align-self: center;
  padding: 10px;
  flex: 1;
  user-select: none;
  font-size: 19px;
  font-weight: 600;
  max-width: 230px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.sc-header--title.enabled {
  cursor: pointer;
  border-radius: 5px;
}

.sc-header--title.enabled:hover {
  box-shadow: 0px 2px 5px rgba(0.2, 0.2, 0.5, 0.1);
}

.sc-header--actions .buttons-actions {
  width: 33px;
  align-self: center;
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  align-content: center;
  height: 33px;
  box-sizing: border-box;
  cursor: pointer;
  border-radius: 5px;
}

.sc-header--actions .buttons-actions:hover {
  box-shadow: 0px 2px 5px rgba(0.2, 0.2, 0.5, 0.1);
}

.sc-header--action-button img {
  width: 100%;
  height: 100%;
  padding: 13px;
  box-sizing: border-box;
}

@media (max-width: 450px) {
  .sc-header {
    border-radius: 0px;
  }
}
</style>
