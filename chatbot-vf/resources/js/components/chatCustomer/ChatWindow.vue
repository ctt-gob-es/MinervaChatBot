<template>
  <div ref="windowChat" class="sc-chat-window" :class="{opened: isOpen, closed: !isOpen}">
    <div v-if="loadingConversation" class="loader_component">
      <v-progress-circular
        :size="100"
        :width="7"
        :color="colors.messageList.bg"
        indeterminate
      ></v-progress-circular>
    </div>
    <Header v-if="showHeader" :title="title" :colors="colors" @close="$emit('close')" @changeFontSize="$emit('changeFontSize')" @maximizeChat="$emit('maximizeChat')" @minimizeChat="$emit('minimizeChat')" @userList="handleUserListToggle" :windowIsMaximized="windowIsMaximized" @downloadConversation="$emit('downloadConversation')">
    </Header>
    <UserList v-if="showUserList" :colors="colors" :participants="participants" />
    <MessageList
      v-if="!showUserList"
      :messages="messages"
      :fontSize="fontSize"
      :participants="participants"
      :show-typing-indicator="showTypingIndicator"
      :colors="colors"
      :always-scroll-to-bottom="alwaysScrollToBottom"
      :show-confirmation-deletion="showConfirmationDeletion"
      :confirmation-deletion-message="confirmationDeletionMessage"
      :message-styling="messageStyling"
      @scrollToTop="$emit('scrollToTop')"
      @remove="$emit('remove', $event)"
    >
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
    </MessageList>
    <UserInput
      v-if="!showUserList"
      :show-emoji="showEmoji"
      :show-emoji-in-text="showEmojiInText"
      :on-submit="onUserInputSubmit"
      :suggestions="getSuggestions()"
      :message="getMessageSuggestions()"
      :actions="getActions()"
      :show-file="showFile"
      :placeholder="placeholder"
      :colors="colors"
      :font-size="fontSize"
      @onType="$emit('onType', $event)"
      @edit="$emit('edit', $event)"
    />
  </div>
</template>

<script>
import Header from './Header.vue'
import MessageList from './MessageList.vue'
import UserInput from './UserInput.vue'
import UserList from './UserList.vue'

export default {
  components: {
    Header,
    MessageList,
    UserInput,
    UserList
  },
  props: {
    showEmoji: {
      type: Boolean,
      default: false
    },
    showEmojiInText: {
      type: Boolean,
      default: false
    },
    showFile: {
      type: Boolean,
      default: false
    },
    loadingConversation: {
      type: Boolean,
      default: false
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
      required: true
    },
    fontSize: {
      type: String,
      required: true
    },
    onUserInputSubmit: {
      type: Function,
      required: true
    },
    messageList: {
      type: Array,
      default: () => []
    },
    isOpen: {
      type: Boolean,
      default: () => false
    },
    placeholder: {
      type: String,
      required: true
    },
    showTypingIndicator: {
      type: String,
      required: true
    },
    colors: {
      type: Object,
      required: true
    },
    alwaysScrollToBottom: {
      type: Boolean,
      required: true
    },
    messageStyling: {
      type: Boolean,
      required: true
    },
    showConfirmationDeletion: {
      type: Boolean,
      required: true
    },
    windowIsMaximized: {
      type: Boolean,
      required: true
    },
    confirmationDeletionMessage: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      showUserList: false,
    }
  },
  computed: {
    messages() {
      let messages = this.messageList

      return messages
    }
  },
  methods: {
    handleUserListToggle(showUserList) {
      this.showUserList = showUserList
    },
    getSuggestions() {
      return this.messages.length > 0 ? this.messages[this.messages.length - 1].suggestions : []
    },
    getMessageSuggestions() {
      let message = null;
      let suggestions = this.messages.length > 0 ? this.messages[this.messages.length - 1].suggestions : []
      if(suggestions && suggestions.length > 0){
        message = this.messages[this.messages.length - 1];
      }
      return message
    },
    getActions() {
      return this.messages.length > 0 ? this.messages[this.messages.length - 1].actions : []
    },
  }
}
</script>

<style scoped>
.sc-chat-window {
  width: 100%;
  height: 100%;
  position: fixed;
  right: 5px;
  bottom: 5px;
  box-sizing: border-box;
  box-shadow: 0px 7px 40px 2px rgba(148, 149, 150, 0.1);
  background: white;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border-radius: 10px;
  font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
  animation: fadeIn;
  animation-duration: 0.3s;
  animation-timing-function: ease-in-out;
}

.sc-chat-window.closed {
  opacity: 0;
  display: none;
  bottom: 90px;
}

@keyframes fadeIn {
  0% {
    display: none;
    opacity: 0;
  }

  100% {
    display: flex;
    opacity: 1;
  }
}

.sc-message--me {
  text-align: right;
}
.sc-message--them {
  text-align: left;
}

@media (max-width: 450px) {
  .sc-chat-window {
    width: 100%;
    height: 100%;
    max-height: 100%;
    right: 0px;
    bottom: 0px;
    border-radius: 0px;
  }
  .sc-chat-window {
    transition: 0.1s ease-in-out;
  }
  .sc-chat-window.closed {
    bottom: 0px;
  }
}
.loader_component {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  margin: auto !important;
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 9999;
}
</style>
