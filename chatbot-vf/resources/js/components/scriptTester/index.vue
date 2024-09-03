<template>
  <div>

    <div class="d-flex nevegation-bread pr-10 pb-3 mt-2">
      <div>
        <v-icon :color="global.color" class="mr-1" icon="mdi-menu-right"></v-icon><a :style="'color:' + global.color"
        @click="closePanel" class="navegation-init">CHATBOTS</a>
      </div>
      <div>
        <v-icon :color="global.color" class="mr-1" icon="mdi-menu-right"></v-icon>
        <a class='navegation-selected'>PRUEBA DE SCRIPT</a>
      </div>
      <v-spacer></v-spacer>
      <v-chip class="chip-chatbot" prev-icon >
        <p class="title-selected-chatbot px-5 mb-0">
          <span class="mdi mdi-robot-outline" style="font-size: 20px"></span>
          Chatbot: {{ chatbotName }}
        </p>
      </v-chip>

    </div >

    <div>
      <v-card>
        <v-card-title class="title-datatable-section">
          <div class="title-datatable">
            <span class="title-vuely pr-2">
              Prueba de script
            </span>
          </div>
        </v-card-title>
        <v-card-text>
          <div>
            <h3>Script
              <span class="script-span" @click="copyScript(scriptTag)">{{scriptTag}}</span>
              <v-icon class="icon-copy" v-if="copy" icon="mdi mdi-clipboard-check-multiple"></v-icon>
              <span class="text-copy" v-if="copy">copiado</span>
            </h3>
          </div>
          <div class="mt-5">
            <p>En esta interfaz puedes hacer uso del script que generaste para el chatbot <span class="chatbot-name">{{chatbotName}}</span>.</p>
            <p>Asegurate de tener el chatbot <strong>activo</strong>, de lo contrario no iniciará la ventana de chat.</p>
            <p>Mira en la parte inferior el botón que abre la ventana de interacción con el chatbot.</p>
            <!-- <img class="img-example" src="../../../../public/images/example-img.png" alt="chat-example"> -->
          </div>
        </v-card-text>
      </v-card>
    </div>
  </div>
</template>

<script setup>

import { onMounted, ref} from 'vue';
import { useGlobalStore } from '../store/global';
import { useRouter } from "vue-router";
import { copyTextToClipboard } from "../composables/methods.js";
const global = useGlobalStore();
const chatbotName = ref(null)
const router = useRouter();
const scriptTag = ref(null)
const host = ref(null);
const type = ref(null)
const chatbotId = ref (null);
const customerId = ref(null);
const loading = ref(false);
const copy = ref(false)

const closePanel = () => {
  let path = "/chatbots/" + customerId.value;

  router.push({ path: path});
};

onMounted(async () => {
    chatbotName.value = localStorage.getItem('chatbotName');
    host.value = localStorage.getItem('src');
    type.value = localStorage.getItem('type');
    chatbotId.value = localStorage.getItem('chatbotId');
    customerId.value = localStorage.getItem('customerId');
    scriptTag.value = localStorage.getItem('scriptTag');

    const scriptElement = document.createElement('script');
    scriptElement.type = type.value;
    scriptElement.src = host.value + '/chatbot';
    scriptElement.setAttribute('chatbot-id', chatbotId.value);
    localStorage.setItem('testScript', true);
    document.body.appendChild(scriptElement);

});

const copyScript = (script) => {
  copy.value = true
  copyTextToClipboard(script)
}

</script>

<style>
.navegation-init {
  cursor: pointer !important;
}

.navegation-selected {
  color: #797979 !important;
}

.title-selected-chatbot {
  font-weight: 700;
  color: var(--primary-color);
}

.title-datatable-section {
  display: flex;
  flex-direction: column;
  align-items: inherit;
  padding: 0 !important;
  width: 100%;
}

.script-span{
  font-size: 15px;
  background-color: #d3d1d1;
  padding: 5px;
  border: 1px solid black;
  border-radius: 3px;
}

.script-span:hover{
  cursor: pointer;
}

.icon-copy{
  font-size: 20px!important;
  margin-left: 5px;
}
.text-copy{
  font-size: 15px!important;
  margin-left: 3px;
}

.chatbot-name{
  font-weight: bold;
}

.img-example{
  box-shadow: 1px 1px 1px rgb(100, 100, 100);
}
</style>


