<template>
  <v-app>
    <!-- <div class="toolbar py-1 pl-2" density="compact" color="#FFFFFF">
      <v-btn @click="backTo" class="mr-3" icon="mdi-arrow-left" size="small" v-bind="props"></v-btn>
      <v-btn @click="save" class="button-clear" size="small" color="success" :loading="loadingSave">Guardar</v-btn>
    </div> -->
    <v-layout class="layout-responsive" style="border: 1px solid #5050503d;">
      <v-navigation-drawer permanent class="pr-2 v-navigation-drawer" location="right">
        <v-row class="ma-1">
          <v-col cols="3" md="4" sm="2" v-for="n in listNodes" :key="n" class="pa-1">
            <v-card  width="100%" height="85" draggable="true" :data-node="n.item" @dragstart="drag($event)" class="drag-drawflow ma-1 text-center card-nodes">
              <v-card-text class="pa-1">
                <div class="icon-node">
                  <v-icon v-if="n.icon" class="my-2" size="large">{{ n.icon }}</v-icon>
                </div>
                <v-spacer></v-spacer>
                <p class="nodes-text">{{ n.displayName }}</p>
              </v-card-text>
            </v-card>
          </v-col>

        </v-row>
      </v-navigation-drawer>
      <v-main class="mx-2 v-main">
        <div id="drawflow" >
          <div class="bar-actions">
            <v-btn @click="save" class="button-clear mr-2" size="small" color="success" :loading="loadingSave" v-if="$can('chatbots_build_save')">Guardar</v-btn>
            <v-btn color="red" class="button-clear mr-2" @click="cleardf" size="small">Limpiar</v-btn>
            <v-btn color="muted" class="button-clear mr-2" @click="openDialogLog" size="small">Importar</v-btn>
            <v-btn color="#2d4a77" class="button-clear mr-2" @click="exportdf" size="small">Exportar</v-btn>
            <v-btn class="button-export" @click="generateScript" size="small">Script</v-btn>
          </div>
          <div class="bar-zoom">
            <div class="d-flex">
              <v-btn density="comfortable" class="mr-2 button-zoom" @click="zoomIn" icon="mdi-magnify-plus-outline" size="large"></v-btn>
              <v-btn density="comfortable" class="mr-2 button-zoom" @click="zoomReset" icon="mdi-reload" size="large"></v-btn>
              <v-btn density="comfortable" class="mr-2 button-zoom" @click="zoomOut" icon="mdi-magnify-minus-outline" size="large"></v-btn>
            </div>
          </div>
          <div id="drawflow-canvas" @drop="drop($event)" @dragover="allowDrop($event)"></div>
        </div>
      </v-main>
    </v-layout>

    <v-dialog width="800" v-model="dialogLog">
      <v-card>
        <v-card-text>
          <chatbot-logs @get-logs="getLogs" @import="importdf" :logs="logs"></chatbot-logs>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="outlined" text="Cancelar" class="black-close" @click="dialogLog = false"></v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-app>
</template>

<script setup>

import { onMounted, shallowRef, h, getCurrentInstance, render, readonly, ref, computed, nextTick } from 'vue'
import { emptyObject } from '../composables/methodsBuilder.js'
import { formatDateTime } from "@/helpers";
import { copyTextToClipboard } from "../composables/methods.js";
import Swal from "sweetalert2";
import Drawflow from 'drawflow';
import Start from './nodes/start.vue';
import Language from './nodes/language.vue';
import PrivacyPolicy from './nodes/privacyPolicy.vue';
import FAQ from './nodes/faq.vue';
import ValidateResponse from './nodes/validateResponse.vue';
import NewInquiry from './nodes/newInquiry.vue';
import Message from './nodes/message.vue';
import ChatbotLogs from "./logs.vue"
import End from './nodes/end.vue';
import axios from "axios";
import useEventsBus from '../../eventBus.js';

// css
import '../styles/builder.css'

const listNodes = readonly([
  { name: 'Start', item: 'Start', input:0, output:1, displayName: 'Inicio', icon: 'mdi-play-circle'},
  { name: 'Language', item: 'Language', input:1, output: 1, displayName: 'Idioma', icon: 'mdi-web-box'},
  { name: 'PrivacyPolicy', item: 'PrivacyPolicy', input: 1, output:2, displayName: 'Política de privacidad', icon: 'mdi-treasure-chest'},
  { name: 'FAQ', item: 'FAQ', input:1, output: 0, displayName: 'Pregunta ciudadana', icon: 'mdi-frequently-asked-questions'},
  { name: 'Message', item: 'Message', input:1, output:1, displayName: 'Mensaje', icon: 'mdi-message-bulleted'},
  { name: 'ValidateResponse', item: 'ValidateResponse', input:1, output:2, displayName: 'Consulta resuelta', icon: 'mdi-check-network'},
  { name: 'NewInquiry', item: 'NewInquiry', input:1, output:2, displayName: 'Nueva consulta', icon: 'mdi-invoice-text-plus-outline'},
  { name: 'End', item: 'End', input:1, output:0, displayName: 'Despedida', icon: 'mdi-hand-wave'},
])

const emit = defineEmits(["back-to", "save-build-chat", 'show-message']);
const { emitBus }=useEventsBus()

const props = defineProps({
  chat: { type: Object},
  loadingSave: { type: Boolean, default: () => false},
});

const editor = shallowRef({})
const languages = ref([]);
const defaultLanguage = ref([]);
const intentions = ref([]);
const logs = ref([]);
const dialogLog = ref(false);
const internalInstance = getCurrentInstance()
internalInstance.appContext.app._context.config.globalProperties.$df = editor;

const Vue = { version: 3, h, render };
const exportValue  = ref(null);

onMounted(async () => {
  await getLanguages();
  await getIntentions();
  getLogs();

  if(props.chat){
    createDrawflow()
  }
});

const flowImport = computed(() => {
  return props?.chat?.information !== null && props.chat?.information !== '' ? props.chat.information : '{"drawflow":{"Home":{"data":{}}}}';
});

const createDrawflow = async() => {

  let elements = document.getElementsByClassName('drag-drawflow');
  for (let i = 0; i < elements.length; i++) {
    elements[i].addEventListener('touchend', drop, {passive: true});
    elements[i].addEventListener('touchmove', positionMobile, {passive: true});
    elements[i].addEventListener('touchstart', drag, {passive: true} );
  }

  const id = document.getElementById('drawflow-canvas');
  editor.value = new Drawflow(id, Vue, internalInstance.appContext.app._context);

  editor.value.registerNode('Start', Start, {idChat: props.chat.id, languages: languages.value, defaultLanguage: defaultLanguage.value}, {});
  editor.value.registerNode('Language', Language, { languages: languages.value, defaultLanguage: defaultLanguage.value }, {});
  editor.value.registerNode('PrivacyPolicy', PrivacyPolicy, { languages: languages.value, defaultLanguage: defaultLanguage.value }, {});
  editor.value.registerNode('FAQ', FAQ, { intentions: intentions.value, languages: languages.value, defaultLanguage: defaultLanguage.value }, {});
  editor.value.registerNode('ValidateResponse', ValidateResponse, { languages: languages.value, defaultLanguage: defaultLanguage.value }, {});
  editor.value.registerNode('NewInquiry', NewInquiry, { languages: languages.value, defaultLanguage: defaultLanguage.value }, {});
  editor.value.registerNode('Message', Message, { languages: languages.value, defaultLanguage: defaultLanguage.value }, {});
  editor.value.registerNode('End', End, { languages: languages.value, defaultLanguage: defaultLanguage.value }, {});

  editor.value.draggable_inputs = false;
  editor.value.zoom_min = 0.2000000000000006;
  editor.value.start();

  await importDrawflow();
  setZoomInit();
  setFunctionInit();
  listenEvents();
}

const importDrawflow  = () => {
  let flow = JSON.parse(flowImport.value);
  editor.value.import(flow)
}

const setFunctionInit = () => {
  editor.value.removeNodeId = function(id, removedCreate = false) {
    let startExist = editor.value.getNodeFromId(id.slice(5))
    let countNodes = Object.keys(editor.value.drawflow.drawflow.Home.data).length;

    let confirmDelete = (countNodes > 1 && startExist.name == 'Start' && !removedCreate) ? false : true;
    if(confirmDelete){
      this.removeConnectionNodeId(id);
      var moduleName = this.getModuleFromNodeId(id.slice(5))
      if(this.module === moduleName) {
        document.getElementById(id).remove();
      }
      delete this.drawflow.drawflow[moduleName].data[id.slice(5)];
      this.dispatch('nodeRemoved', id.slice(5));
    }else{
      emit('show-message', 'No es posible eliminar el inicio.', 3000)
    }
  }
}

const zoomIn = () => {
  editor.value.zoom_in();
}
const zoomReset = () => {
  editor.value.zoom_reset();
}
const zoomOut = () => {
  editor.value.zoom_out();
}
const exportdf = () => {
  let exportValue = editor.value.export();

  let from = 'export';
  if(!validateConnections(exportValue, from)){
    return;
  }

  let currentDate = new Date();
  let formattedDate = `${currentDate.getFullYear()}-${(currentDate.getMonth() + 1).toString().padStart(2, '0')}-${currentDate.getDate().toString().padStart(2, '0')}`;
  let formattedTime = `${currentDate.getHours().toString().padStart(2, '0')}-${currentDate.getMinutes().toString().padStart(2, '0')}-${currentDate.getSeconds().toString().padStart(2, '0')}`;
  let fileName = `${props.chat.name}_${formattedDate}_${formattedTime}.json`;
  let jsonData = JSON.stringify(exportValue, null, 4);

  let blob = new Blob([jsonData], { type: 'application/json' });

  let link = document.createElement('a');
  link.href = window.URL.createObjectURL(blob);
  link.download = fileName;

  link.click();

  window.URL.revokeObjectURL(link.href);
}
const cleardf = () => {
  editor.value.clear();
}
const importdf = (flow) => {
  editor.value.import(JSON.parse(flow));
  dialogLog.value = false;
}
const drag = (ev) => {
  if (ev.type === "touchstart") {
    mobile_item_selec = ev.target.closest(".drag-drawflow").getAttribute('data-node');
  } else {
    ev.dataTransfer.setData("node", ev.target.getAttribute('data-node'));
  }
}
const drop = (ev) => {
  if (ev.type === "touchend") {
    var parentdrawflow = document.elementFromPoint( mobile_last_move.touches[0].clientX, mobile_last_move.touches[0].clientY).closest("#drawflow");
    if(parentdrawflow != null) {
      addNodeToDrawFlow(mobile_item_selec, mobile_last_move.touches[0].clientX, mobile_last_move.touches[0].clientY);
    }
    mobile_item_selec = '';
  } else {
    ev.preventDefault();
    var data = ev.dataTransfer.getData("node");
    addNodeToDrawFlow(data, ev.clientX, ev.clientY);
  }

}
const allowDrop = (ev) => {
  ev.preventDefault();
}

let mobile_item_selec = '';
let mobile_last_move = null;

function positionMobile(ev) {
  mobile_last_move = ev;
}
function addNodeToDrawFlow(name, pos_x, pos_y) {
  if(!name) return;

  pos_x = pos_x * ( editor.value.precanvas.clientWidth / (editor.value.precanvas.clientWidth * editor.value.zoom)) - (editor.value.precanvas.getBoundingClientRect().x * ( editor.value.precanvas.clientWidth / (editor.value.precanvas.clientWidth * editor.value.zoom)));
  pos_y = pos_y * ( editor.value.precanvas.clientHeight / (editor.value.precanvas.clientHeight * editor.value.zoom)) - (editor.value.precanvas.getBoundingClientRect().y * ( editor.value.precanvas.clientHeight / (editor.value.precanvas.clientHeight * editor.value.zoom)));

  const nodeSelected = listNodes.find(ele => ele.item == name);
  editor.value.addNode(name, nodeSelected.input,  nodeSelected.output, pos_x, pos_y, name, {}, name, 'vue');

}

const setZoomInit = () => {
  editor.value.zoom_out();
  editor.value.zoom_out();
}

const listenEvents = () => {
  editor.value.on("connectionCreated", function(info) {
    removeConnectionInDoubleOutput(info)
  });

  editor.value.on("nodeCreated", function(id) {
    removeNodes(id)
  });

  editor.value.on("clickEnd", (e) =>  {
    disabledDragNodes(e);
  })
}

const removeConnectionInDoubleOutput = (info) => {
  let nodeInfo = editor.value.getNodeFromId(info.output_id);
  if(nodeInfo.outputs[info.output_class].connections.length > 1) {
    const removeConnectionInfo = nodeInfo.outputs[info.output_class].connections[0];
    editor.value.removeSingleConnection(info.output_id, removeConnectionInfo.node, info.output_class, removeConnectionInfo.output);
  }
}

const removeNodes = async (id) => {
  await nextTick()
  let startExist = editor.value.getNodesFromName('Start')
  let languagesExist = editor.value.getNodesFromName('Language')
  let countNodes = Object.keys(editor.value.drawflow).length;

  if(countNodes > 0 && startExist.length == 0){
    editor.value.removeNodeId('node-' + id, true)
    emit('show-message', 'Es necesario agregar un inicio.', 3000)
  }

  if(startExist.length > 1){
    editor.value.removeNodeId('node-' + id, true)
    emit('show-message', 'Solo es posible tener un inicio.', 3000)
  }

  if(languagesExist.length > 1){
    editor.value.removeNodeId('node-' + id, true)
    emit('show-message', 'Solo es posible tener un componente de lenguajes.', 3000)
  }
}

const disabledDragNodes = (e) => {
  if(e.target.classList[0] !== "title-box") {
    editor.value.drag = false;
  }
}

const save = () => {
  let flow = editor.value.export();
  let from = 'save';
  if(!validateConnections(flow, from)){
    return;
  }
  let endExist = editor.value.getNodesFromName('End')
  if(endExist.length == 0){
    emit('show-message', 'Es necesario agregar una Despedida.', 3000)
    return;
  }

  emit('save-build-chat', props.chat.id, JSON.stringify(flow))
}

const backTo = () => {
  emit('back-to')
}

const getLanguages = () => {
  languages.value = props.chat.settings.filter((setting, i) => {
    if(setting.name_setting == 'idioma_principal'){
      defaultLanguage.value = setting.value;
      return false;
    }
    return true;
  })
};

const getIntentions = async () => {
  let chatbotId = props.chat?.id;
  await axios
    .get(`/getIntentionsBuilder?chatbot_id=${chatbotId}`)
    .then((response) => {
      if(response?.data?.data && Array.isArray(response.data.data)){
        intentions.value = response.data.data;
      }
    })
    .catch((error) => {
      console.error(error);
    })
};

const getLogs = async () => {
  let data = { chatbot_id: props.chat?.id };
  await axios
    .post('getLogBuilder', data)
    .then((response) => {
      logs.value = response.data.data.map((log) => ({
        ...log,
        user_name: log?.user?.name,
        created_at: formatDateTime(log.created_at),
      }));
    })
    .catch((error) => {
      console.error(error);
    })
};

const openDialogLog = () => {
  getLogs();
  dialogLog.value = true;
}

const validateConnections = (flow, from) => {
  const data = flow?.drawflow?.Home?.data;
  let isValid = true;

  if (emptyObject(data)) {
    if (from == 'save'){

      emit('show-message', `No hay datos para guardar.`, 3000)
    } else if(from == 'export'){
      emit('show-message', `No hay datos para exportar.`, 3000)
    }
    return false;
  }
  const nodesConsider = [];

  Object.keys(data).forEach((key, i) => {
    const node = data[key];

    if (i === 0 && node.name === 'Start') nodesConsider.push(node.id);

    const nodeOutputs = node.outputs;
    // const nodeConsider = nodesConsider.includes(node.id);
    // if (!nodeConsider) return;

    if (!emptyObject(nodeOutputs)) {
      Object.keys(nodeOutputs).forEach((keyOutput, i) => {
        const output = nodeOutputs[keyOutput];
        if (output.connections.length < 1) {
          emitError(node, i, keyOutput);
          isValid = false;
          return;
        } else {
          // validar si alguno de los mensajes esta vacio
          if(node.data?.info?.messages && node.data.info.messages.length > 0){
            node.data.info.messages.forEach(element => {
              if(element?.message == ''){
                emitError(node, null, null, 'Por favor agregar un texto en el mensaje.');
                isValid = false;
                return;

              }
            });
          }
          //validar si alguna intención esta vacia
          if(node.data?.info?.intentions && node.data.info.intentions.length > 0){
            node.data.info.intentions.forEach(element => {
              if(element?.message == '' || !element?.id){
                emitError(node, null, null, 'Por favor seleccionar una intención.');
                isValid = false;
                return;
              }
            });
          }
          emitBus(node.name, 'good');
        }

        output.connections.forEach(connection => {
          nodesConsider.push(parseInt(connection.node));
        });
      });
    }

    if (emptyObject(nodeOutputs) && node.name !== 'End') {
      emitError(node);
      isValid = false;
      return;
    }

    if(node.name === 'End'){
      if(node.data?.info?.messages && node.data.info.messages.length > 0){
        node.data.info.messages.forEach(element => {
          if(element?.message == ''){
            emitError(node, null, null, 'Por favor agregar un texto en el mensaje.');
            isValid = false;
            return;
          }
        });
      }
    }
  });

  return isValid;
}

const emitError = (node, i, keyOutput = null, customErrorMessage = null) => {
  let errorMessage;

  if (customErrorMessage) {
    errorMessage = customErrorMessage;
  } else {
    errorMessage = i !== undefined ? `Por favor agregar una salida al nodo en la posición ${parseInt(i) + 1}` : 'Por favor agregar una salida al nodo.';
  }

  emit('show-message', errorMessage, 3000);
  focusNode(node.id, true, true);

  const nodeElement = document.getElementById(`node-${node.id}`);
  nodeElement.classList.add('errorNode');

  const outputsContainer = nodeElement.querySelector('.outputs');
  const outputNameElement = outputsContainer && keyOutput ? outputsContainer.querySelector(`.output.${keyOutput}`) : null;

  if (outputNameElement) {
    outputNameElement.classList.add('errorOutput');
  }

  setTimeout(() => {
    nodeElement.classList.remove('errorNode');
    if (outputNameElement) outputNameElement.classList.remove('errorOutput');
  }, 3000);
}


const generateScript = () => {
  let chatbotId = props?.chat?.id;
  if(!chatbotId){
    Swal.fire({
      title: "Error",
      text: "Por favor seleccione un chatbot",
      icon: "warning",
    });
  }
  let host = window.location.origin
  let script = `<script type="text/javascript" src="${host}/chatbot" chatbot-id="${chatbotId}"><\/script>`;
  localStorage.setItem('scriptTag', script);
  localStorage.setItem('chatbotName', props.chat.name);
  localStorage.setItem('type', 'text/javascript');
  localStorage.setItem('src', host);
  localStorage.setItem('chatbotId', props?.chat?.id);
  localStorage.setItem('customerId', props?.chat?.city_councils_id);

  copyTextToClipboard(script)

  Swal.fire({
    title: "Script copiado",
    text: "¿Quieres probar el script generado?",
    icon: "success",
    showCancelButton: true,
    confirmButtonText: "Probar",
    cancelButtonText: "No",
  }).then(async (result) => {
    if (result.value) {
      window.location.href = '/scriptTester'
    };
  });
}

const focusNode = (nodeId, animated, focused) => {
  const node = document.getElementById(`node-${nodeId}`)
  const args = {
    node_x: editor.value.drawflow.drawflow.Home.data[nodeId].pos_x,
    node_y: editor.value.drawflow.drawflow.Home.data[nodeId].pos_y,
    node_w: node.clientWidth,
    node_h: node.clientHeight,
    canvas_w: editor.value.precanvas.clientWidth,
    canvas_h: editor.value.precanvas.clientHeight
  }
  const pos_x = -args.node_x + (args.canvas_w / 2) - (args.node_w / 2)
  const pos_y = -args.node_y + (args.canvas_h / 2) - (args.node_h / 2)
  const zoom = editor.value.zoom
  setTranslate(pos_x, pos_y, zoom)

  if (animated) {
    const millisecondsStart = 50
    const millisecondsAnimate = 500
    node.style.transition = `all ${millisecondsAnimate / 1000}s ease 0s`
    window.setTimeout(() => { node.style.transform = 'scale(1.1)' }, millisecondsStart)
    window.setTimeout(() => { node.style.transform = 'scale(1.0)' }, millisecondsStart + millisecondsAnimate)
    window.setTimeout(() => {
      node.style.transition = '';
      node.style.transform = '';
    }, millisecondsStart + millisecondsAnimate * 2)
  }

  if (focused) {
    var evt = new window.MouseEvent("mousedown", { view: window, bubbles: true, cancelable: false });
    var evt2 = new window.MouseEvent("mouseup", { view: window, bubbles: true, cancelable: false });
    document.querySelector(`#node-${nodeId} .drawflow_content_node`).dispatchEvent(evt);
    document.querySelector(`#node-${nodeId} .drawflow_content_node`).dispatchEvent(evt2);
  }
}

const setTranslate = (x, y, zoom) => {
  const $this = editor.value
  $this.canvas_x = x;
  $this.canvas_y = y;
  let storedZoom = zoom;
  $this.zoom = 1;
  $this.precanvas.style.transform = "translate(" + $this.canvas_x + "px, " +
  $this.canvas_y + "px) scale(" + $this.zoom + ")";
  $this.zoom = storedZoom;
  $this.zoom_last_value = 1;
  $this.zoom_refresh();
}
const showError = (message) => {
  Swal.fire({
    title: "Error",
    text: message,
    icon: "warning",
  });
}

defineExpose({
  showError,
})


</script>

<style>
.bar-actions{
  position: absolute!important;
  top: 20px;
  right: 275px;
  z-index: 5;
  display: flex;
  gap: 10px;
}

.button-export {
  font-size: 1.5rem;
  color: white!important;
  font-weight: bold!important;
  background: var(--primary-color)!important;
}

.button-clear {
  font-size: 1.5rem;
  font-weight: bold!important;
}

.bar-zoom {
  position: absolute;
  bottom: 7px;
  right: 172px;
  z-index: 5;
  width: 250px;
  display: flex;
  justify-content: space-between;
}

.button-zoom {
  color: white!important;
  font-weight: bold;
  background: var(--primary-color)!important;
}
.nodes-text{
  font-size: 12px;
  font-weight: 700;
  color: #292929;
}
.icon-node{
  background: #f5f8fa;
}
.toolbar{
  border: 1px solid #5050503d;
  border-bottom: none;
}

@media (max-width: 1089px) {
  .bar-actions {
    position: static;
    flex-direction: column;
    align-items: flex-start;
  }

  .bar-actions .v-btn {
    width: 100%;
    text-align: center;
  }
}

@media (max-width: 958px){

  .v-navigation-drawer {
    width: 100%!important;
    position: relative!important;
    margin-bottom: 15px!important;
    transform: translateX(0%)!important;
  }

  .layout-responsive{
    display: flex!important;
    flex-direction: column-reverse!important;
  }

  .v-main{
    padding-right: 0px!important;
  }

  #drawflow-canvas{
    top: 0px!important;
    height: calc(100vh - 280px)
  }

  .bar-actions{
    right: 120px!important;
  }

  .bar-zoom {
    position: absolute;
    bottom: 233px;
    right: -80px;
    z-index: 5;
    width: 250px;
    display: flex;
    justify-content: space-between;
  }
}

@media (max-width: 908px){
  .bar-actions{
    right: 120px!important;
  }
}

@media (max-width: 958px){
  .bar-actions{
    right:0px!important;
  }

  .bar-actions .v-btn {
    width: 80%;
    text-align: center;
  }
}


</style>
