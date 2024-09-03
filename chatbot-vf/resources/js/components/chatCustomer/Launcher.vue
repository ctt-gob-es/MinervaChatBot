<template>
  <div>
    <div
      v-if="showLauncher && !isOpen"
      class="sc-launcher"
      :class="{opened: isOpen}"
      :style="{backgroundColor: colors.launcher.bg}"
      @click.prevent="isOpen ? close() : openAndFocus()"
    >
      <div v-if="newMessagesCount > 0 && !isOpen" class="sc-new-messsages-count">
        {{ newMessagesCount }}
      </div>
      <img v-if="isOpen" class="sc-closed-icon" :src="iconClose.img" :alt="iconClose.name" />
      <img v-else class="sc-open-icon" :src="iconOpen.img" :alt="iconOpen.name" />
    </div>
    <ChatWindow
      :loading-conversation="loadingConversation"
      :message-list="messageList"
      :on-user-input-submit="onMessageWasSent"
      :participants="participants"
      :title="chatWindowTitle"
      :fontSize="fontSize"
      :is-open="isOpen"
      :show-emoji="showEmoji"
      :show-emoji-in-text="showEmojiInText"
      :show-file="showFile"
      :show-confirmation-deletion="showConfirmationDeletion"
      :confirmation-deletion-message="confirmationDeletionMessage"
      :show-header="showHeader"
      :placeholder="placeholder"
      :show-typing-indicator="showTypingIndicator"
      :colors="colors"
      :windowIsMaximized="windowIsMaximized"
      :always-scroll-to-bottom="alwaysScrollToBottom"
      :message-styling="messageStyling"
      @close="close"
      @maximizeChat="maximizeChat"
      @minimizeChat="minimizeChat"
      @changeFontSize="changeFontSize"
      @downloadConversation="$emit('downloadConversation')"
      @scrollToTop="$emit('scrollToTop')"
      @onType="$emit('onType', $event)"
      @edit="$emit('edit', $event)"
      @remove="$emit('remove', $event)"
    >
      <template v-slot:header>
        <slot name="header"> </slot>
      </template>
      <template v-slot:user-avatar="scopedProps">
        <slot name="user-avatar" :user="scopedProps.user" :message="scopedProps.message"> </slot>
      </template>
      <template v-slot:text-message-body="scopedProps">
        <slot
          name="text-message-body"
          :message="scopedProps.message"
          :messageText="scopedProps.messageText"
          :messageColors="scopedProps.messageColors"
          :me="scopedProps.me"
        >
        </slot>
      </template>
      <template v-slot:system-message-body="scopedProps">
        <slot name="system-message-body" :message="scopedProps.message"> </slot>
      </template>
      <template v-slot:text-message-toolbox="scopedProps">
        <slot name="text-message-toolbox" :message="scopedProps.message" :me="scopedProps.me">
        </slot>
      </template>
    </ChatWindow>
  </div>
</template>

<script>
import store from '../store/chat-customer.js'
import ChatWindow from './ChatWindow.vue'

import CloseIcon from '../assets/close-icon.png'
import OpenIcon from '../assets/logo-no-bg.svg'

export default {
  components: {
    ChatWindow
  },
  props: {
    icons: {
      type: Object,
      default: function () {
        return {
          open: null,
          close: null,
        }
      }
    },
    showEmoji: {
      type: Boolean,
      default: false
    },
    autoFocus: {
      type: Boolean,
      default: true
    },
    showEmojiInText: {
      type: Boolean,
      default: false
    },
    loadingConversation: {
      type: Boolean,
      default: false
    },
    showEdition: {
      type: Boolean,
      default: false
    },
    showDeletion: {
      type: Boolean,
      default: false
    },
    showConfirmationDeletion: {
      type: Boolean,
      default: false
    },
    confirmationDeletionMessage: {
      type: String,
      default: 'Do you really want to delete the message?'
    },
    isOpen: {
      type: Boolean,
      required: true
    },
    windowIsMaximized: {
      type: Boolean,
      required: true
    },
    open: {
      type: Function,
      required: true
    },
    close: {
      type: Function,
      required: true
    },
    changeFontSize: {
      type: Function,
      required: true
    },
    maximizeChat: {
      type: Function,
      required: true
    },
    minimizeChat: {
      type: Function,
      required: true
    },
    showFile: {
      type: Boolean,
      default: false
    },
    showLauncher: {
      type: Boolean,
      default: true
    },
    showCloseButton: {
      type: Boolean,
      default: true
    },
    showFontSizeButton: {
      type: Boolean,
      default: true
    },
    showDownloadButton: {
      type: Boolean,
      default: true
    },
    showHeader: {
      type: Boolean,
      default: true
    },
    participants: {
      type: Array,
      required: true
    },
    title: {
      type: String,
      default: () => ''
    },
    fontSize: {
      type: String,
      default: () => '13'
    },
    titleImageUrl: {
      type: String,
      default: () => ''
    },
    onMessageWasSent: {
      type: Function,
      required: true
    },
    messageList: {
      type: Array,
      default: () => []
    },
    newMessagesCount: {
      type: Number,
      default: () => 0
    },
    placeholder: {
      type: String,
      default: 'Write a message...'
    },
    showTypingIndicator: {
      type: String,
      default: () => ''
    },
    colors: {
      type: Object,
      validator: (c) =>
        'header' in c &&
        'bg' in c.header &&
        'text' in c.header &&
        'launcher' in c &&
        'bg' in c.launcher &&
        'messageList' in c &&
        'bg' in c.messageList &&
        'sentMessage' in c &&
        'bg' in c.sentMessage &&
        'text' in c.sentMessage &&
        'receivedMessage' in c &&
        'bg' in c.receivedMessage &&
        'text' in c.receivedMessage &&
        'userInput' in c &&
        'bg' in c.userInput &&
        'text' in c.userInput,
      default: function () {
        return {
          header: {
            bg: '#4e8cff',
            text: '#ffffff'
          },
          launcher: {
            bg: '#4e8cff'
          },
          messageList: {
            bg: '#ffffff'
          },
          sentMessage: {
            bg: '#4e8cff',
            text: '#ffffff'
          },
          receivedMessage: {
            bg: '#f4f7f9',
            text: '#ffffff'
          },
          userInput: {
            bg: '#f4f7f9',
            text: '#565867'
          }
        }
      }
    },
    alwaysScrollToBottom: {
      type: Boolean,
      default: () => false
    },
    messageStyling: {
      type: Boolean,
      default: () => false
    },
    disableUserListToggle: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    chatWindowTitle() {
      if (this.title !== '') return this.title

      return 'Chatbot';
    },
    iconOpen(){
      return this.$props?.icons?.open !== null ? this.$props.icons.open : { img: OpenIcon, name: 'default'}
    },
    iconClose(){
      return this.$props?.icons?.close !== null ? this.$props.icons.close : { img: CloseIcon, name: 'default'}
    },
  },
  watch: {
    $props: {
      deep: true,
      immediate: true,
      handler(props) {
        for (const prop in props) {
          store.setState(prop, props[prop])
        }
      }
    }
  },
  methods: {
    openAndFocus() {
      this.open()
      // if (this.autoFocus) {
      //   this.$event.$emit('focusUserInput')
      // }
    }
  }
}
</script>

<style scoped>
.sc-launcher {
  width: 60px;
  height: 60px;
  background-position: center;
  background-repeat: no-repeat;
  position: fixed;
  right: 5px;
  bottom: 5px;
  border-radius: 50%;
  /* box-shadow: none; */
  /* transition: box-shadow 0.2s ease-in-out; */
  cursor: pointer;
  z-index: 10000;
}

.sc-launcher:before {
  content: '';
  position: relative;
  display: block;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  transition: box-shadow 0.2s ease-in-out;
}

.sc-launcher .sc-open-icon,
.sc-launcher .sc-closed-icon {
  width: 60px;
  height: 60px;
  position: fixed;
  right: 5px;
  bottom: 5px;
  transition: opacity 100ms ease-in-out, transform 100ms ease-in-out;
}

.sc-launcher .sc-closed-icon {
  transition: opacity 100ms ease-in-out, transform 100ms ease-in-out;
  width: 60px;
  height: 60px;
}

.sc-launcher .sc-open-icon {
  padding: 17px;
  box-sizing: border-box;
  opacity: 1;
}

.sc-launcher.opened .sc-open-icon {
  transform: rotate(-90deg);
  opacity: 1;
}

.sc-launcher.opened .sc-closed-icon {
  transform: rotate(-90deg);
  opacity: 1;
}

.sc-launcher.opened:before {
  box-shadow: 0px 0px 400px 250px rgba(148, 149, 150, 0.2);
}

/* .sc-launcher:hover {
  box-shadow: 0 0px 27px 1.5px rgba(0, 0, 0, 0.2);
} */

.sc-new-messsages-count {
  position: absolute;
  top: -3px;
  left: 41px;
  display: flex;
  justify-content: center;
  flex-direction: column;
  border-radius: 50%;
  width: 22px;
  height: 22px;
  background: #ff4646;
  color: white;
  text-align: center;
  margin: auto;
  font-size: 12px;
  font-weight: 500;
}
</style>
