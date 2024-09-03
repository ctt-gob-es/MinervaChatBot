<template>
  <div>
    <Suggestions :suggestions="suggestions" :message="message" :colors="colors" @sendSuggestion="_submitSuggestion" :font-size="fontSize"/>
    <Decisions :actions="actions" :colors="colors" @sendDecision="_submitDecision" :font-size="fontSize"/>
    <div
      v-if="file"
      class="file-container"
      :style="{
        backgroundColor: colors.userInput.text,
        color: colors.userInput.bg
      }"
    >
      <span class="icon-file-message"
        ><img :src="icons.file.img" :alt="icons.file.name" height="15"
      /></span>
      {{ file.name }}
      <span class="delete-file-message" @click="cancelFile()"
        ><img
          :src="icons.closeSvg.img"
          :alt="icons.closeSvg.name"
          height="10"
          title="Remove the file"
      /></span>
    </div>
    <form
      v-if="!showInput"
      class="sc-user-input"
      :class="{active: inputActive}"
      :style="{background: colors.userInput.bg}"
    >
      <div
        ref="userInput"
        role="button"
        tabIndex="0"
        contentEditable="true"
        :placeholder="placeholder"
        class="sc-user-input--text"
        :style="{color: colors.userInput.text}"
        @focus="setInputActive(true)"
        @blur="setInputActive(false)"
        @keydown="handleKey"
        @input="handleInput"
        @focusUserInput="focusUserInput()"
      ></div>
      <div class="sc-user-input--buttons">
        <div class="sc-user-input--button"></div>
        <div v-if="showEmoji && !isEditing" class="sc-user-input--button">
          <EmojiIcon :on-emoji-picked="_handleEmojiPicked" :color="colors.userInput.text" />
        </div>
        <div v-if="showFile && !isEditing" class="sc-user-input--button">
          <FileIcons :on-change="_handleFileSubmit" :color="colors.userInput.text" />
        </div>
        <div v-if="isEditing" class="sc-user-input--button">
          <UserInputButton
            :color="colors.userInput.text"
            tooltip="cancel"
            @click.native.prevent="_editFinish"
          >
            <IconCross />
          </UserInputButton>
        </div>
        <div class="sc-user-input--button">
          <UserInputButton
            v-if="isEditing"
            :color="colors.userInput.text"
            tooltip="Edit"
            @click.native.prevent="_editText"
          >
            <IconOk />
          </UserInputButton>
          <UserInputButton
            v-else
            :color="colors.userInput.text"
            tooltip="mdi-send-variant"
            @click.native.prevent="_submitText"
          >
            <IconSend />
          </UserInputButton>
        </div>
        <!-- <div class="sc-user-input--button">
          <v-icon @click.stop="toggle ? endSpeechRecognition() : startSpeechRecognition()" icon :color="!toggle ? colors.userInput.text : speaking ? 'red lighten-2' : 'red darken-4'" :class="{ pulse: toggle }">{{ toggle ? 'mdi-microphone-off' : 'mdi-microphone' }}</v-icon>

        </div> -->
      </div>
    </form>
  </div>
</template>

<script>
import EmojiIcon from './icons/EmojiIcon.vue'
import FileIcons from './icons/FileIcons.vue'
import UserInputButton from './UserInputButton.vue'
import Suggestions from './Suggestions.vue'
import Decisions from './Decisions.vue'
import FileIcon from '../assets/file.svg'
import CloseIconSvg from '../assets/close.svg'
import store from '../store/chat-customer.js'
import IconCross from './utilities/icons/IconCross.vue'
import IconOk from './utilities/icons/IconOk.vue'
import IconSend from './utilities/icons/IconSend.vue'
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
const recognition = SpeechRecognition ? new SpeechRecognition() : false;

export default {
  components: {
    EmojiIcon,
    FileIcons,
    UserInputButton,
    Suggestions,
    Decisions,
    IconCross,
    IconOk,
    IconSend
  },
  props: {
    icons: {
      type: Object,
      default: function () {
        return {
          file: {
            img: FileIcon,
            name: 'default'
          },
          closeSvg: {
            img: CloseIconSvg,
            name: 'default'
          }
        }
      }
    },
    showEmoji: {
      type: Boolean,
      default: () => false
    },
    showEmojiInText: {
      type: Boolean,
      default: () => false
    },
    suggestions: {
      type: Array,
      default: () => []
    },
    message: {
      type: Object,
      default: () => null
    },
    actions: {
      type: Array,
      default: () => []
    },
    showFile: {
      type: Boolean,
      default: () => false
    },
    onSubmit: {
      type: Function,
      required: true
    },
    placeholder: {
      type: String,
      default: 'Write something...'
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
      file: null,
      inputActive: false,
      prevSelectionRange: null,
      lang: "es_Es",
      error: false,
      speaking: false,
      toggle: false,
      runtimeTranscription: "",
      sentences: [],
      text: "",
      showInputCache: null,
    }
  },
  computed: {
    showInput(){
      if (this.showInputCache !== null) {
        return this.showInputCache;
      }
      this.showInputCache = this.suggestions.length > 0 || this.actions.length > 0;
      return this.showInputCache;
    },
    editMessageId() {
      return this.isEditing && store.state.editMessage.id
    },
    isEditing() {
      return store.state.editMessage && store.state.editMessage.id
    },
    hasText() {
      return this.text.length > 0 ? true : false;
    }
  },
  watch: {
    editMessageId(m) {
      if (store.state.editMessage != null && store.state.editMessage != undefined) {
        this.$refs.userInput.focus()
        this.$refs.userInput.textContent = store.state.editMessage.data.text
      } else {
        this.$refs.userInput.textContent = ''
      }
    },
    suggestions: function() {
      this.showInputCache = null;
    },
    actions: function() {
      this.showInputCache = null;
    }
  },
  mounted() {
    // this.$event.$on('focusUserInput', () => {
    //   if (this.$refs.userInput) {
    //     this.focusUserInput()
    //   }
    // })
    this.checkCompatibility();

    document.addEventListener('selectionchange', () => {
      const selection = document.getSelection()
      if (
        !selection ||
        !selection.anchorNode ||
        (selection.anchorNode != this.$refs.userInput &&
          selection.anchorNode.parentNode != this.$refs.userInput)
      ) {
        return
      }
      if (selection.rangeCount) {
        this.prevSelectionRange = selection.getRangeAt(0).cloneRange()
      } else {
        this.prevSelectionRange = null
      }
    })
  },
  methods: {
    cancelFile() {
      this.file = null
    },
    setInputActive(onoff) {
      this.inputActive = onoff
    },
    handleKey(event) {
      if (event.keyCode === 13 && !event.shiftKey) {
        if (!this.isEditing) {
          this._submitText(event)
        } else {
          this._editText(event)
        }
        this._editFinish()
        event.preventDefault()
      } else if (event.keyCode === 27) {
        this._editFinish()
        event.preventDefault()
      }
    },
    handleInput(event) {
      this.$emit('onType', event.target.textContent)
    },
    focusUserInput() {
      this.$nextTick(() => {
        this.$refs.userInput.focus()
      })
    },
    _submitSuggestion(suggestion) {
      this.onSubmit({author: 'me', type: 'text', data: {text: suggestion}, isSuggestion: true})
    },
    _submitDecision(decision) {
      let data = {
        author: 'me',
        type: 'text',
        data: {
          text: decision.text
        },
        isDecision: true,
        ...decision
      }
      this.onSubmit(data)
    },
    _checkSubmitSuccess(success) {
      if (Promise !== undefined) {
        Promise.resolve(success).then(
          function (wasSuccessful) {
            if (wasSuccessful === undefined || wasSuccessful) {
              this.file = null
              this.$refs.userInput.innerHTML = ''
            }
          }.bind(this)
        )
      } else {
        this.file = null
        this.$refs.userInput.innerHTML = ''
      }
    },
    _submitText(event) {
      const text = this.$refs.userInput.textContent
      const file = this.file
      if (file) {
        this._submitTextWhenFile(event, text, file)
      } else {
        if (text && text.length > 0) {
          this._checkSubmitSuccess(
            this.onSubmit({
              author: 'me',
              type: 'text',
              data: {text}
            })
          )
        }
      }
    },
    _submitTextWhenFile(event, text, file) {
      if (text && text.length > 0) {
        this._checkSubmitSuccess(
          this.onSubmit({
            author: 'me',
            type: 'file',
            data: {text, file}
          })
        )
      } else {
        this._checkSubmitSuccess(
          this.onSubmit({
            author: 'me',
            type: 'file',
            data: {file}
          })
        )
      }
    },
    _editText(event) {
      const text = this.$refs.userInput.textContent
      if (text && text.length) {
        this.$emit('edit', {
          author: 'me',
          type: 'text',
          id: store.state.editMessage.id,
          data: {text}
        })
        this._editFinish()
      }
    },
    _handleEmojiPicked(emoji) {
      if (this.showEmojiInText) {
        this._addToTextEmoji(emoji)
      } else {
        this._submitEmoji(emoji)
      }
    },
    _submitEmoji(emoji) {
      this._checkSubmitSuccess(
        this.onSubmit({
          author: 'me',
          type: 'emoji',
          data: {emoji}
        })
      )
    },
    _addToTextEmoji(emoji) {
      let range = this.prevSelectionRange
      if (!range) {
        if (!this.$refs.userInput.firstChild) {
          this.$refs.userInput.append(document.createTextNode(''))
        }

        range = document.createRange()
        range.setStart(this.$refs.userInput.firstChild, this.$refs.userInput.textContent.length)
        range.collapse(true)
      }
      let selection = window.getSelection()
      selection.removeAllRanges()
      selection.addRange(range)

      let textNode = document.createTextNode(emoji)
      range.deleteContents()
      range.insertNode(textNode)
      range.collapse(false)
      this.$refs.userInput.focus()
    },
    _handleFileSubmit(file) {
      this.file = file
    },
    _editFinish() {
      store.setState('editMessage', null)
    },
    cleanReset() {
      this.error = false;
      this.speaking = false;
      this.toggle = false;
      this.runtimeTranscription = "";
      this.sentences = [];
      this.text = "";
    },
    speechEnd({ sentences, text }) {
      this.sentences = sentences;
      this.text = text;
      this.$refs.userInput.textContent = this.text
    },
    checkCompatibility() {
      if (!recognition) {
        console.error("Speech Recognition is not available on this browser. Please use Chrome or Firefox")
      }
    },

    endSpeechRecognition() {
      recognition.stop();
      this.toggle = false;
      this.speaking = false;

      if (this.text.length >= 1) {
        setTimeout(() => {
          this.speechEnd({
            sentences: this.sentences,
            text: `${this.text}. ${this.sentences.join(". ")}`
          });
        }, 500);
      } else {
        setTimeout(() => {
          this.speechEnd({
            sentences: this.sentences,
            text: `${this.sentences.join(". ")}`
          });
        }, 500);
      }
    },

    startSpeechRecognition() {
      this.toggle = true;

      recognition.lang = this.lang;
      recognition.continuous = true;
      recognition.interimResults = true;
      recognition.maxAlternatives = 1;

      recognition.onspeechstart = () => {
        this.speaking = true;
      };

      recognition.onspeechend = () => {
        this.speaking = false;
      };

      recognition.onresult = (event) => {
        if (typeof event.results === "undefined") {
          recognition.stop();
          return;
        }

        if (this.text.length >= 1) {
          this.sentences = [];
        }

        for (var i = event.resultIndex; i < event.results.length; ++i) {
          if (event.results[i].isFinal) {
            let finalSentence = event.results[i][0].transcript;

            this.runtimeTranscription = event.results[i][0].transcript;

            this.sentences.push(this.capitalizeFirstLetter(finalSentence));
          } else {
            this.runtimeTranscription = event.results[i][0].transcript;
          }
        }
      };

      recognition.start();
    },
    capitalizeFirstLetter(string) {
      return string.charAt(0).toUpperCase() + string.slice(1);
    }
  },
  beforeDestroy() {
    recognition.abort();
  }
}
</script>

<style>
.sc-user-input {
  min-height: 55px;
  margin: 0px;
  position: relative;
  bottom: 0;
  display: flex;
  background-color: #f4f7f9;
  border-bottom-left-radius: 10px;
  border-bottom-right-radius: 10px;
  transition: background-color 0.2s ease, box-shadow 0.2s ease;
}

.sc-user-input--text {
  width: 70%;
  resize: none;
  border: none;
  outline: none;
  border-bottom-left-radius: 10px;
  box-sizing: border-box;
  padding: 18px;
  font-size: 15px;
  font-weight: 400;
  line-height: 1.33;
  white-space: pre-wrap;
  word-wrap: break-word;
  color: #565867;
  -webkit-font-smoothing: antialiased;
  max-height: 200px;
  overflow: scroll;
  bottom: 0;
  overflow-x: hidden;
  overflow-y: auto;
}

.sc-user-input--text:empty:before {
  content: attr(placeholder);
  display: block; /* For Firefox */
  /* color: rgba(86, 88, 103, 0.3); */
  filter: contrast(15%);
  outline: none;
  cursor: text;
}

.sc-user-input--buttons {
  width: 100px;
  position: absolute;
  right: 30px;
  height: 100%;
  display: flex;
  justify-content: flex-end;
}

.sc-user-input--button:first-of-type {
  width: 40px;
}

.sc-user-input--button {
  width: 30px;
  height: 55px;
  margin-left: 2px;
  margin-right: 2px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.sc-user-input.active {
  box-shadow: none;
  background-color: white;
  box-shadow: 0px -5px 20px 0px rgba(150, 165, 190, 0.2);
}

.sc-user-input--button label {
  position: relative;
  height: 24px;
  padding-left: 3px;
  cursor: pointer;
}

.sc-user-input--button label:hover path {
  fill: rgba(86, 88, 103, 1);
}

.sc-user-input--button input {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  z-index: 99999;
  height: 100%;
  opacity: 0;
  cursor: pointer;
  overflow: hidden;
}

.file-container {
  background-color: #f4f7f9;
  border-top-left-radius: 10px;
  padding: 5px 20px;
  color: #565867;
}

.delete-file-message {
  font-style: normal;
  float: right;
  cursor: pointer;
  color: #c8cad0;
}

.delete-file-message:hover {
  color: #5d5e6d;
}

.icon-file-message {
  margin-right: 5px;
}
@keyframes pulse {
  from {
    transform: scale3d(1, 1, 1);
  }

  50% {
    transform: scale3d(1.35, 1.35, 1.35);
  }

  to {
    transform: scale3d(1, 1, 1);
  }
}

.pulse {
  animation-name: pulse;
  animation-fill-mode: both;
  animation-duration: 1s;
  animation-delay: 1s;
  animation-iteration-count: infinite;
}

</style>
