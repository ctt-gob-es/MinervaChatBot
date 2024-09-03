<template>
  <div>
    <beautiful-chat
      v-if="activateRender"
      :participants="participants"
      :titleImageUrl="titleImageUrl"
      :title="title"
      :fontSize="fontSize"
      :onMessageWasSent="onMessageWasSent"
      :messageList="messageList"
      :newMessagesCount="newMessagesCount"
      :isOpen="isChatOpen"
      :close="closeChat"
      :open="openChat"
      :changeFontSize="setFontSize"
      :maximizeChat="maximizeChat"
      :minimizeChat="minimizeChat"
      :windowIsMaximized="windowIsMaximized"
      :showEmoji="false"
      :showFile="false"
      :showEdition="true"
      :showDeletion="true"
      :deletionConfirmation="true"
      :showTypingIndicator="showTypingIndicator"
      :showLauncher="true"
      :showCloseButton="true"
      :showFontSizeButton="true"
      :showDownloadButton="true"
      :colors="colors"
      :alwaysScrollToBottom="alwaysScrollToBottom"
      :disableUserListToggle="false"
      :messageStyling="messageStyling"
      :loading-conversation="loadingConversation"
      :icons="icons"
      :placeholder="placeholder"
      @onType="handleOnType"
      @edit="editMessage"
      @downloadConversation="downloadConversation" />

  </div>
</template>
<script>
import beautifulChat from './Launcher.vue';
import axios from 'axios'
import Echo from 'laravel-echo'
import Pusher from "pusher-js";

export default {
  name: 'app',
  components: {
    beautifulChat
  },
  data() {
    return {
      testChat: false,
      chatbotId: null,
      language: null,
      conversationId: null,
      nodePosition: null,
      initConversation: false,
      loadingConversation: false,
      settings: [],
      participants: [
        {
          id: 'bot',
          name: 'Bot',
          imageUrl: 'https://cdn-icons-png.flaticon.com/512/4711/4711987.png'
        },
        {
          id: 'agente',
          name: 'Agente',
          imageUrl: 'https://cdn-icons-png.flaticon.com/512/3937/3937056.png'
        },
        {
          id: 'user2',
          name: 'Customer',
          imageUrl: 'https://avatars3.githubusercontent.com/u/37018832?s=200&v=4'
        }
      ],
      title: '',
      titleImageUrl: 'https://avatars3.githubusercontent.com/u/37018832?s=200&v=4',
      messageList: [
        // { type: 'text', author: `me`, data: { text: `Mensaje cliente` } },
        // { type: 'system', author: `me`, data: { text: `Mensaje system` } },
        // { type: 'text', author: `user1`, data: { text: `Mensaje bot`, meta: '06-16-2019 12:43' } },
        // { type: 'text', author: `user1`, data: { text: `MENSAJE DE EJEMPLO REPETIDO MUCHISIMAS VECES, MENSAJE DE EJEMPLO REPETIDO MUCHISIMAS VECES, MENSAJE DE EJEMPLO REPETIDO MUCHISIMAS VECES, MENSAJE DE EJEMPLO REPETIDO MUCHISIMAS VECES, MENSAJE DE EJEMPLO REPETIDO MUCHISIMAS VECES, MENSAJE DE EJEMPLO REPETIDO MUCHISIMAS VECES, MENSAJE DE EJEMPLO REPETIDO MUCHISIMAS VECES, MENSAJE DE EJEMPLO REPETIDO MUCHISIMAS VECES, MENSAJE DE EJEMPLO REPETIDO MUCHISIMAS VECES` } },
        // { type: 'text_buttons', author: `user1`, data: { text: `Mensaje botones`, meta: '06-16-2019 12:43' }, buttons: [{text: 'Boton 1'}, {text: 'Boton 2'}, {text: 'Boton 3'}]},
        // { type: 'text', author: `BOT`, data: { text: `Mensaje seleccion` }, suggestions: ['some quick reply', 'another one', 'another one', 'another one', 'another one'] },
      ],
      newMessagesCount: 0,
      isChatOpen: false,
      showTypingIndicator: '',
      colors: {
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
          bg: '#eaeaea',
          text: '#222222'
        },
        userInput: {
          bg: '#f4f7f9',
          text: '#565867'
        }
      },
      alwaysScrollToBottom: true,
      messageStyling: true,
      fontSize: null,
      fontSizeMessageArray: ['14', '18', '22', '26'],
      currentFontSize: 0,
      windowIsMaximized: false,
      activateRender: false,
      placeholder: 'Escriba un mensaje...',
      icons: {
        open: null,
        close: null,
      },
      translations: {
        'question_new': {
          'castellano': 'Esta no es la primera vez que estás aquí ¿Que te gustaría hacer?',
          'ingles': 'This is not your first time here, what would you like to do?',
          'valenciano': 'Esta no és la primera vegada que eres ací Que t\'agradaria fer?',
        },
        'new_conversation': {
          'castellano': 'Nueva conversación',
          'ingles': 'New conversation',
          'valenciano': 'Nova conversa',
        },
        'continue': {
          'castellano': 'Continuar',
          'ingles': 'Continue',
          'valenciano': 'Continuar',
        },
        'restart_conversation': {
          'castellano': 'Si deseas reiniciar la conversación, pulsa el siguiente botón:',
          'ingles': 'If you want to restart the conversation, press the following button:',
          'valenciano': 'Si desitges reiniciar la conversa, polsa el següent botó:',
        },
        'starting_over': {
          'castellano': 'Comenzar de nuevo',
          'ingles': 'Starting over',
          'valenciano': 'Començar de nou',
        },
        'write_message': {
          'castellano': 'Escriba un mensaje...',
          'ingles': 'Write a message...',
          'valenciano': 'Escriga un missatge...',
        },
        'restart_conversation_inactivity': {
          'castellano': 'Su conversación ha sido finalizada por inactividad. Si deseas iniciar una nueva pulsa el siguiente botón.',
          'ingles': 'Your conversation has been terminated due to inactivity. If you wish to start a new one click the following button.',
          'valenciano': 'La seua conversa ha sigut finalitzada per inactivitat. Si desitges iniciar una nova polsa el següent botó.',
        },
      }
    }
  },
  async created(){
    await this.initializeWebsocket();
    await this.getLocalStorage();
  },
  async mounted(){
    await this.loadChatData();
  },
  methods: {
    initializeWebsocket(){
      window.Pusher = Pusher

      window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        wsHost: window.location.hostname,
        wsPort: import.meta.env.VITE_WEBSOCKET_PORT,
        wssPort: import.meta.env.VITE_WEBSOCKET_PORT,
        forceTLS: import.meta.env.VITE_FORCE_TLS === 'true',
        disableStats: true,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
      })
    },
      async getLocalStorage(){
      let localFontSize = localStorage.getItem("chatFontSize");
      let indexLocalFontSize = this.fontSizeMessageArray.findIndex((fontSize) => fontSize === localFontSize)
      this.currentFontSize = indexLocalFontSize !== -1 ? indexLocalFontSize : this.currentFontSize;

      await this.setFontSize();
    },
    async setFontSize(){
      this.fontSize = this.fontSizeMessageArray[this.currentFontSize];
      this.currentFontSize = (this.currentFontSize + 1) % this.fontSizeMessageArray.length;
      localStorage.setItem("chatFontSize", this.fontSize);
    },
    async loadChatData(){
      this.chatbotId = this.$route.params.chatbotId;
      let testScript = localStorage.getItem('testScript')
      this.testChat = testScript

      await this.getLanguage()

      let urlTest = '';
      if (testScript && testScript == 'true') {
      urlTest = '?testScript=true';
      }
      await axios.get("/getOneChatbotCustomerSettings/" + this.chatbotId + urlTest)
        .then((response) => {
          this.settings = response.data.map((setting) => ({
            ...setting,
            type: setting.default_table.type,
            description: setting.default_table.description,
            deleted_at_switch: setting.deleted_at == null,
            key: setting.default_table.name,
          }));
        })
        .catch((error) => {
          console.error(error);
        })
        .finally(async () => {
          await this.getSettings();
          if (this.settings.length > 0) this.activateRender = true;
          // languagesOptions.value = ajustes.value
          //   .filter(
          //     (item) =>
          //       item.type === "idioma" &&
          //       item.value == 1 &&
          //       item.deleted_at_switch == true
          //   )
          //   .map((item) => ({
          //     name: item.name,
          //     value: item.name.toLowerCase(),
          //   }));
          // loading.value = false;

          // console.log(languagesOptions.value);
        });
    },
    async createConversation(){
      //Limpio todas las variables almacenadas de conversacion
      this.cleanLocalStorageConversation();

      // Si no tengo el lenguage intento recuperarlo
      if(!this.language){
        this.getLanguage();
      }

      let data = {
        chatbot_id: this.chatbotId
      }
      this.loadingConversation = true;
      await axios.post("api/createConversation", data)
        .then(async (response) => {
          this.conversationId = response.data
          localStorage.setItem("conversationId-"+this.chatbotId, this.conversationId);
          await this.listenEventsMessage(this.conversationId);
          this.sendConversation();
        })
        .catch((error) => {
          console.error(error);
        })
        .finally(async () => {
          this.loadingConversation = false;
        });
    },
    async getHistoryConversation(){
      let data = {
        conversation_id: this.conversationId,
        lang: this.language,
        chatbot_id: this.chatbotId,
      }
      this.loadingConversation = true;
      await axios.post("api/conversationHistory", data)
        .then(async (response) => {
          if(response?.data && Array.isArray(response.data)){
            response.data.forEach(element => {
              if(element?.message?.author == 'ciudadano') element.message.author = 'me';
              this.processMessage(element)
            });
          }
        })
        .catch(async (error) => {
          console.error(error);
          //En caso de que haya un error obteniendo el historial de la conversación, se crea una nueva
          this.createConversation();
        })
        .finally(async () => {
          this.loadingConversation = false;
        });
    },
    sendMessage (text) {
      if (text.length > 0) {
        this.newMessagesCount = this.isChatOpen ? this.newMessagesCount : this.newMessagesCount + 1
        this.onMessageWasSent({ author: 'support', type: 'text', data: { text } })
      }
    },
    onMessageWasSent (message) {
      this.messageList = [ ...this.messageList, message ]

      if(message?.isDecision){
        this.validateDecision(message)
          .then(validate => {
            if (validate) return;

            this.sendConversation(message);
          })
          .catch(error => {
            console.error('Error validating decision:', error);
          });
      }else{
        this.sendConversation(message);
      }
    },
    async validateDecision(message = null){
      let response = false;

      if(message?.action == 'continue'){
        this.messageList = [];
        response = true;
        await this.getHistoryConversation();
        this.listenEventsMessage(this.conversationId);
      }
      if(message?.action == 'new'){
        this.messageList = [];
        if(this.conversationId){
          await this.closeConversation(this.conversationId);
        }
        this.createConversation();
        response = true;
      }
      if(message?.action == 'end_conversation'){
        this.messageList = [];
        this.createConversation();
        response = true;
      }
      if(message?.action == 'free_question'){
        response = true;
      }

      return response;

    },
    async validateConversationStatus(conversationId){
      let validate = false;
      await axios.post('api/validateConversationStatus', {conversation_id: conversationId})
        .then((response) => {
          validate = response;
        })
        .catch((error) => {
          console.log(error)
        })

      return validate;
    },
    async openChat () {
      if(this.inIframe()){
        window.parent.postMessage('openChat', '*');
        if(this.windowIsMaximized) window.parent.postMessage('maximizeChat', '*');
      }
      this.isChatOpen = true
      this.newMessagesCount = 0

      if(!this.initConversation){

        let conversationId = localStorage.getItem("conversationId-"+this.chatbotId);
        if(conversationId && this.language){
          this.conversationId = conversationId;

          this.validateConversationStatus(this.conversationId)
            .then(async (response) => {
              if (response){
                if(response.data.agent){
                  let messageAgent = 'Se ha perdido la conexión con el agente. Se reinicia la conversación.'
                  if(this.language == 'ingles'){
                    messageAgent = 'Connection with the agent has been lost. The conversation restarts.'
                  } else if(this.language == 'castellano') {
                    messageAgent = 'Se ha perdido la conexión con el agente. Se reinicia la conversación.'
                  } else if(this.language == 'valenciano') {
                    messageAgent = 'S ha perdut la connexió amb l agent. Es reinicia la conversa.'
                  } else {

                  }

                  this.messageList.push({ type: 'text', author: `bot`, data: { text: messageAgent}})
                  await this.createConversation();
                } else {
                  this.messageList.push({ type: 'text', author: `BOT`, data: { text: this.translations['question_new'][this.language], continueConversation: false }, actions: [{text: this.translations['new_conversation'][this.language], action: 'new'}, {text: this.translations['continue'][this.language], action: 'continue'}] });
                }
              }else{
                await this.createConversation();
              }
            })

        }else{
          await this.createConversation();
        }
        this.initConversation = true;
      }
    },
    closeChat () {
      if(this.inIframe()){
        window.parent.postMessage('closeChat', '*');
        // let iframeParent = window.parent.document.getElementById("chatbot-container");
        // iframeParent.style.height = "88px";
        // iframeParent.style.width = "100px";
      }
      this.isChatOpen = false
    },
    maximizeChat () {
      if(this.inIframe()){
        this.windowIsMaximized = true;
        window.parent.postMessage('maximizeChat', '*');
      }
    },
    minimizeChat () {
      if(this.inIframe()){
        this.windowIsMaximized = false;
        window.parent.postMessage('minimizeChat', '*');
      }
    },
    handleScrollToTop () {
      // called when the user scrolls message list to top
      // leverage pagination for loading another page of messages
    },
    handleOnType () {
      console.log('Emit typing event')
    },
    editMessage(message){
      const m = this.messageList.find(m=>m.id === message.id);
      m.isEdited = true;
      m.data.text = message.data.text;
    },
    getSettings(){
      this.settings.forEach(element => {
        if(element?.key == 'logo'){
          this.titleImageUrl = element.value ?? null
          if(element.value == ''){
            this.icons.open = null
          }else{
            this.icons.open  = {};
            this.icons.open.img = element.value
            this.icons.open.name = 'customImage'

          }
        }
        if (element?.key === 'icono_bot') {
          this.participants = this.participants.map(participant => {
            if (participant.id === 'bot') {
              return {
                ...participant,
                imageUrl: element.value
              };
            }
            return participant;
          });
        }

        if (element?.key === 'icono_agente') {
          this.participants = this.participants.map(participant => {
            if (participant.id === 'agente') {
              return {
                ...participant,
                imageUrl: element.value
              };
            }
            return participant;
          });
        }
        if(element?.key == 'color'){
          this.colors.header.bg = this.colors.launcher.bg = this.colors.sentMessage.bg  = element.value
        }
        if (element?.key == 'titulo') {
            this.title = element.value;
            if (element?.languages && Array.isArray(element.languages)) {
                const matchingLanguage = element.languages.find(lang => lang?.language === this.language);
                if (matchingLanguage && matchingLanguage.value) {
                    this.title = matchingLanguage.value;
                }
            }
        }

        if(element?.key == 'mensaje_reiniciar'){
           // se agrega el valor por defecto si existe
          if(element?.value){
            Object.keys(this.translations['question_new']).forEach(language => {
              this.translations['question_new'][language] = element.value;
            });
          }
          // se agrega el lenguaje del mensaje en caso de existir
          if(element?.languages && Array.isArray(element.languages)){
            element.languages.forEach(element => {
              if((element?.language == 'castellano' || element?.language == 'ingles' || element?.language == 'valenciano') && element?.value){
                this.translations['question_new'][element.language] = element.value
              }
            });
          }
        }
        if(element?.key == 'mensaje_comenzar_nuevamente'){
          // se agrega el valor por defecto si existe
          if(element?.value){
            Object.keys(this.translations['restart_conversation']).forEach(language => {
              this.translations['restart_conversation'][language] = element.value;
            });
          }
          // se agrega el lenguaje del mensaje en caso de existir
          if(element?.languages && Array.isArray(element.languages)){
            element.languages.forEach(element => {
              if((element?.language == 'castellano' || element?.language == 'ingles' || element?.language == 'valenciano') && element?.value){
                this.translations['restart_conversation'][element.language] = element.value
              }
            });
          }
        }
        if(element?.key == 'mensaje_comenzar_nuevamente_inactividad'){
          // se agrega el valor por defecto si existe
          if(element?.value){
            Object.keys(this.translations['restart_conversation_inactivity']).forEach(language => {
              this.translations['restart_conversation_inactivity'][language] = element.value;
            });
          }
          // se agrega el lenguaje del mensaje en caso de existir
          if(element?.languages && Array.isArray(element.languages)){
            element.languages.forEach(element => {
              if((element?.language == 'castellano' || element?.language == 'ingles' || element?.language == 'valenciano') && element?.value){
                this.translations['restart_conversation_inactivity'][element.language] = element.value
              }
            });
          }
        }
      });

      if(this.language){
        this.placeholder = this.translations['write_message'][this.language];
      }
    },
    inIframe () {
      try {
        return window.self !== window.top;
      } catch (e) {
        return true;
      }
    },
    listenEventsMessage(id){
      window.Echo.channel('conversation-' + id)
        .listen('EventConversation', (e) => {
          this.processMessage(e)
        });
    },
    async sendConversation(message = null){
      let data = this.prepareData(message);
      if (this.testChat == 'true') {
        data.testScript = true;
      }
      await axios.post("api/conversation", data)
        .then(async (response) => {

        })
        .catch((error) => {
          console.error(error);
        })
        .finally(async () => {
        });
    },
    processMessage(response){
      if(response?.node){
        this.nodePosition = response.node
        localStorage.setItem("nodePosition-"+this.chatbotId, this.nodePosition);
      } else {

      }
      if(response?.message) this.receivedMessage(response.message)

      if(response?.end){
        this.messageList.push({ type: 'text', author: `bot`, data: { text: this.translations['restart_conversation'][this.language], continueConversation: false }, actions: [{text: this.translations['starting_over'][this.language], action: 'end_conversation'}] });
      }

      if(response?.inactivity){
        this.cleanLocalStorageConversation();
        this.messageList.push({ type: 'text', author: `bot`, data: { text: this.translations['restart_conversation_inactivity'][this.language], continueConversation: false }, actions: [{text: this.translations['starting_over'][this.language], action: 'end_conversation'}] });
      }
    },
    receivedMessage(e){
      this.messageList.push(e)
    },
    updateTitle(language){
        this.settings.forEach(element => {
        if (element?.key == 'titulo') {
            this.title = element.value;
            if (element?.languages && Array.isArray(element.languages)) {
                const matchingLanguage = element.languages.find(lang => lang?.language === language);
                if (matchingLanguage && matchingLanguage.value) {
                    this.title = matchingLanguage.value;
                }
            }
        }
      });
    },
    prepareData(message){
      let data = {
        chatbot_id: this.chatbotId,
        conversation_id: this.conversationId,
        node: this.nodePosition,
        type_user: 'ciudadano',
      }

      if (message?.isSuggestion) {
        let text = this.getSuggestion(message?.data?.text);
        data.selection = ['yes', 'no'].includes(text.text) ? text.text : undefined;

        if(text.type == 'language'){
          let language = this.mappingLanguage(text.text)
          this.setLanguage(language);
          //actualizar header title
          this.updateTitle(language);
        }

      }
      if (message?.data?.text) {
        data.message = message.data.text;
      }

      if(message?.isDecision){
        if(message?.action && message.action == 'faq'){
          data.type_faq = {type: message.action, intention_id: message.intention_id}
          data.selection = 'yes'
        }
      }

      //Cuando lo obtengo de un tipo de mensaje decision/action y es free_question
      if(this.messageList.length > 2 && this.messageList[this.messageList.length - 2]?.action == 'free_question' && message?.author == 'me'){
        data.type_faq = {type: 'free_question', question: message?.data?.text}
        data.selection = 'no';
      }

      //Cuando la ia me devuelve una sola pregunta y es free_question
      if(this.messageList.length > 2 && this.messageList[this.messageList.length - 2]?.data?.action == 'free_question' && this.messageList[this.messageList.length - 2]?.author == 'bot'){
        data.type_faq = {type: 'free_question', question: message?.data?.text}
        data.selection = 'no';
      }

      data.lang = this.language ?? null;

      return data;

    },
    getSuggestion(text){
      let message = {
        type: 'selection',
        text: '',
      }
      if(text == 'Si' || text == 'Sí' || text == 'Yes' || text == 'Estoy de acuerdo' || text == 'I agree' || text == 'Estic d\'acord'){
        message.text = 'yes';
      }else if(text == 'No' || text == 'No estoy de acuerdo' || text == 'I disagree' || text == 'No estic d\'acord'){
        message.text = 'no';
      }else{
        message.type = 'language';
        message.text = text;
      }
      return message
    },
    getLanguage(){

      let conversationId = localStorage.getItem("conversationId-"+this.chatbotId)
      let languageStorage = localStorage.getItem("languageConversation-"+this.chatbotId)

      //obtener lenguaje de la conversacion en curso
      if(conversationId && languageStorage) {
        this.language = languageStorage
      } else if (languageStorage){
        this.language = languageStorage
      }

      //obtener lenguaje del parametro
      if(!this.language && this.$route.params.lang) this.setLanguage(this.mappingLanguageNavigator(this.$route.params.lang))

      //obtener lenguaje del navegador
      if(!this.language){
        let langNavigator = navigator.language || navigator.userLanguage;
        let foundLanguage = null;

        if (langNavigator) {
          const languages = ['es', 'en', 'va'];
          foundLanguage = languages.find(language => langNavigator.startsWith(language));
        }

        if (!foundLanguage) {
          langNavigator = null;
        }
        let selectedLanguage = this.mappingLanguageNavigator(langNavigator ? langNavigator.substring(0, 2) : null)
        this.setLanguage(selectedLanguage);
      }
    },
    setLanguage(language){
      this.language = language
      if(language){
        this.placeholder = this.translations['write_message'][this.language];
        localStorage.setItem('languageConversation-'+this.chatbotId, language)
      }
    },
    cleanLocalStorageConversation(){
      localStorage.removeItem("conversationId-"+this.chatbotId);
      //localStorage.removeItem("languageConversation-"+this.chatbotId);
      this.language = null;
      this.conversationId = null;
      this.nodePosition = null;
    },
    mappingLanguageNavigator(language){
      let lang = null;
      switch (language) {
        case 'es':
          lang = 'castellano';
          break;
        case 'en':
          lang = 'ingles';
          break;
        case 'va':
          lang = 'valenciano';
          break;

        default:
          lang = null;
          break;
      }
      return lang;
    },
    mappingLanguage(language){
      let lang = null;
      switch (language) {
        case 'Castellano':
          lang = 'castellano';
          break;
        case 'Spanish':
          lang = 'castellano';
          break;
        case 'Castellà':
          lang = 'castellano';
          break;
        case 'Inglés':
          lang = 'ingles';
          break;
        case 'English':
          lang = 'ingles';
          break;
        case 'Anglés':
          lang = 'ingles';
          break;
        case 'Valenciano':
          lang = 'valenciano';
          break;
        case 'Valencian':
          lang = 'valenciano';
          break;
        case 'Valencià':
          lang = 'valenciano';
          break;
        default:
          lang = this.language;
          break;
      }
      return lang;
    },
    downloadConversation(){
      const currentDate = new Date();
      const formattedDate = currentDate.toISOString().slice(0, 10);

      let messageText = '';
      this.messageList.forEach(element => {
        if(element?.data?.text == '' && element?.actions && element.actions.length > 0){
          messageText += `${element.author}: `;
          element.actions.forEach(value => {
            messageText += `${value.text} \n`;
          });
        }else{
          messageText += `${element.author}: ${element.data.text}\n`;
        }
      });

      const blob = new Blob([messageText], { type: 'text/plain' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `${this.title}_${formattedDate}.txt`;

      document.body.appendChild(link);
      link.click();

      setTimeout(() => {
        URL.revokeObjectURL(url);
        document.body.removeChild(link);
      }, 0);
    },
    async closeConversation(id){
      let data = {
        conversation_id: id
      }
      await axios.post('api/closeConversationAbandonment', data)
        .catch(async (error) => {
          console.error(error);
        })

    }
  }
}
</script>
