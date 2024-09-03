<template>
  <Loader :loading="loading" />
  <DateRange class="mt-7"  @submit="getMetrics(idCustomer, idChatbot)" @updateDate="setDate" @clearData="clearData" :filter_dashboard="true" :customerOptions="Customers" :chatbotOptions="chatbotsPerCustomer"
  :initialCustomerId="initialCustomerId" :initialChatbotId="initialChatbotId"
  @updateChatbots="getChatbotsPerCustomer"
  @chatbotSelected="chatbotModel"
  :initialFilter="true"
  />
  <v-row>
    <v-col cols="12" xs="12" sm="6" md="3" lg="3">
      <v-card class="d-flex flex-column" prepend-icon="mdi mdi-account-group-outline">
        <template v-slot:title>Ciudadanos </template>
        <v-card-text class="truncate-text" style="text-align: center;"> {{ nCiudadanos }} </v-card-text>
      </v-card>
    </v-col>
    <v-col cols="12" xs="12" sm="6" md="3" lg="3">
      <v-card prepend-icon="mdi mdi-timer-star-outline">
        <template v-slot:title> Tiempo medio</template>
        <v-card-text class="truncate-text"> {{ tiempoMedio }} (hh:mm:ss) por conversacion</v-card-text>
      </v-card>
    </v-col>
    <v-col cols="12" xs="12" sm="6" md="3" lg="3">
      <v-card prepend-icon="mdi mdi-check-circle-outline">
        <template v-slot:title> Tasa de éxito </template>
        <v-card-text class="truncate-text"> {{tasaExito}}% de preguntas libres resueltas</v-card-text>
      </v-card>
    </v-col>
    <v-col cols="12" xs="12" sm="6" md="3" lg="3">
      <v-card prepend-icon="mdi mdi-alert-circle-outline">
        <template v-slot:title> Tasa de abandono </template>
        <v-card-text class="truncate-text"> {{ tasaAbandono }}% de conversaciones abandonadas</v-card-text>
      </v-card>
    </v-col>

  </v-row>

  <div class="mt-3 d-flex align-center justify-center flex-wrap col-12">
    <div class="mt-5 col-12 col-xl-12">
      <h2 style="text-align: start;">Intenciones</h2>
      <v-card class="col-12">
        <div class="p-2" id="chartIntencionesMasUsadas"
        style="width: 100%; height: 450px"
        />
      </v-card>
    </div>

    <div class="mt-5 col-12 col-xl-12">
      <h2 style="text-align: start;">Detalle de intenciones</h2>
      <v-card class="col-12">
        <div id="DetailResponseResults"
        style="width: 100%; height: 450px"
        />
      </v-card>
    </div>
    <h2 class="mt-8 col-12" style="text-align: start;">Respuesta de usuarios</h2>
  </div>

  <v-row class="py-5 px-7">
    <v-col cols="12" xs="12" sm="6" md="3" lg="3">
      <v-card prepend-icon="mdi mdi-thumb-up-outline">
        <template v-slot:title> Positivas </template>
        <v-card-text class="truncate-text"> {{ respPositivas }} </v-card-text>
      </v-card>
    </v-col>
    <v-col cols="12" xs="12" sm="6" md="3" lg="3">
      <v-card prepend-icon="mdi mdi-thumb-down-outline">
        <template v-slot:title> Negativas </template>
        <v-card-text class="truncate-text"> {{ respNegativas }} </v-card-text>
      </v-card>
    </v-col>
    <v-col cols="12" xs="12" sm="6" md="3" lg="3">
      <v-card prepend-icon="mdi mdi-message-reply-outline">
        <template v-slot:title> Sin valoración </template>
        <v-card-text class="truncate-text"> {{ respSinValoracion }} </v-card-text>
      </v-card>
    </v-col>
    <v-col cols="12" xs="12" sm="6" md="3" lg="3">
      <v-card prepend-icon="mdi mdi-message-reply-outline">
        <template v-slot:title> Sin categoria </template>
        <v-card-text class="truncate-text"> {{ respSinCategoria }} </v-card-text>
      </v-card>
    </v-col>
  </v-row>

  <div class="d-flex align-center justify-center flex-wrap col-12 ">
    <div class="mt-5 col-12 col-xl-12">
      <v-card class="col-12">
        <div class="p-2" id="chartRespuestas"
        style="width: 100%; height: 450px"
        />
      </v-card>
    </div>

    <div class="mt-5 col-12 col-xl-12 mb-10">
      <h2 style="text-align: start;">Detalle de respuestas</h2>
      <v-card class="col-12">
        <div class="p-2" id="chartResponseByDay"
        style="width: 100%; height: 450px"
        />
      </v-card>
    </div>
  </div>

</template>

<script setup>
import { ref, onMounted } from "vue";
import * as am4core from "@amcharts/amcharts4/core";
import * as am4charts from "@amcharts/amcharts4/charts";
import axios from "axios";
import am4themes_frozen from "@amcharts/amcharts4/themes/frozen";
import { useGlobalStore } from "../store/global";
import Swall from "sweetalert2";
import moment from "moment";
import Loader from "../utilities/Loader.vue";
import DateRange from "../utilities/DateRange.vue";
import { useRouter } from "vue-router";
const router = useRouter()
const global = useGlobalStore();
const idCustomer = ref(null);
const idChatbot = ref(null);
const Customers = ref([]);
const chatbotsPerCustomer = ref([]);
const loading = ref(false);
const dateFrom = ref(null);
const dateTo = ref(null);
const nCiudadanos = ref(null);
const tiempoMedio = ref(null);
const tasaExito = ref(null);
const tasaAbandono = ref(null);
const initialCustomerId = ref(null);
const initialChatbotId = ref(null)
const totalIntenciones = ref(null)
const intencionesMasUsadas = ref([]);
const chatsAbandonados = ref(null)
const intencionesPorDia = ref([])
const intentionsEmpty = ref(null)
const respNegativas = ref(null)
const respPositivas = ref(null)
const respSinCategoria = ref(null)
const respSinValoracion = ref(null)

onMounted(async () => {
  let initialGet = true
  await loadData(initialGet);
});

const loadData = async (initialGet = false) => {
  await getRedirection();
  loading.value = true;
  await getCustomers()
  await getChatbotsPerCustomer(idCustomer.value);
  await getMetrics(idCustomer.value, idChatbot.value, initialGet);
  await loadCharts();
};

const getRedirection = async () => {
  const response = await axios.get(`/getInitialRedirectPath`);
  if (response.data != 'permission'){
    router.push({ path: response.data});
  }
}

const setDate = (from, to) => {
  if (from) dateFrom.value = from;
  if (to) dateTo.value = to;
};

const clearData = async () => {
  dateFrom.value = null;
  dateTo.value = null;
  idChatbot.value = null;
  initialChatbotId.value = null
  await getCustomers()
  await getChatbotsPerCustomer(idCustomer.value);
};

const getMetrics = async (idCustomer, idChatbot, initialGet = false) => {
  if(initialGet){
    if(!idChatbot){
      return;
    }
  }

  if (!dateFrom.value || !dateTo.value || !idCustomer || !idChatbot) {
    Swall.fire({
      title: "Atención!",
      text: "Por favor completa todos los campos de la busqueda",
      icon: "warning",
    });
    return;
  }

  let from, to;
  loading.value = true;
  if (dateFrom.value && dateTo.value) {
    from = moment(dateFrom.value, "YYYY-MM-DD").format("YYYY-MM-DD");
    to = moment(dateTo.value, "YYYY-MM-DD").format("YYYY-MM-DD");

    const response = await axios.get(`/getMetrics`, {
      params: {
        ...(from && to && { from, to }),
        idCustomer: idCustomer,
        idChatbot: idChatbot
      },
    });
    loading.value = false
    intencionesPorDia.value = response.data.intencionesPorDia
    intencionesMasUsadas.value = response.data.intencionesMasUsadas
    nCiudadanos.value = response.data.totalCiudadanos
    tiempoMedio.value = response.data.tiempoConversacion
    tasaExito.value = response.data.tasaExito.toFixed(2),
    tasaAbandono.value = response.data.tasaAbandono.toFixed(2),
    chatsAbandonados.value = response.data.chatsAbandonados,
    totalIntenciones.value = response.data.totalIntentions,
    respNegativas.value = response.data.Respuesta_negativa,
    respPositivas.value = response.data.Respuesta_positiva,
    respSinCategoria.value = response.data.Respuesta_sin_categoria,
    respSinValoracion.value = response.data.Respuesta_sin_valoracion

    loadCharts()
  } else {
    Swall.fire({
      title: "Atención!",
      text: "Por favor selecciona un rango de fechas",
      icon: "warning",
    });
  }
}

const getCustomers = async () => {
  const response = await axios.get(`/getCustomers`);
  initialCustomerId.value = response.data[0].id
  idCustomer.value = response.data[0].id
  Customers.value = response.data;
  idChatbot.value = null
}

const chatbotModel = async (idChat) => {
  idChatbot.value = idChat
}

const getChatbotsPerCustomer = async(idClient) => {
    idCustomer.value = idClient
    chatbotsPerCustomer.value = []
    const response = await axios.get(`/getChatbotsPerCustomer/${idClient}`)
    if(response.data.length > 0){
      initialChatbotId.value = response.data[0].id
      chatbotsPerCustomer.value = response.data;
      idChatbot.value = response.data[0].id
    }else{
      idChatbot.value = null
      initialChatbotId.value = null
    }
}

const loadCharts = async () => {
  am4core.options.autoDispose = true;
  am4core.ready(function () {
    am4core.useTheme(am4themes_frozen);

    chartRespuestas(am4core);
    chartIntencionesMasUsadas(am4core);
    DetailResponseResults(am4core);
    createResponseChartByDay(intencionesPorDia.value)

    loading.value = false;
  });
};

const chartIntencionesMasUsadas = (am4core) => {
  let chart = am4core.create("chartIntencionesMasUsadas", am4charts.PieChart3D);
  chart.logo.disabled = true;
  chart.innerRadius = am4core.percent(40);
  chart.paddingRight = 20;

  intentionsEmpty.value = false

  let pieSeries = chart.series.push(new am4charts.PieSeries3D());
  pieSeries.dataFields.value = "percentage";
  pieSeries.dataFields.category = "intentionName";
  pieSeries.dataFields.count = "count";

  pieSeries.slices.template.strokeWidth = 2;
  pieSeries.slices.template.strokeOpacity = 1;

  let totalPercentage = 0;
  let totalInts = totalIntenciones.value;
  for (let key in intencionesMasUsadas.value) {
    totalInts -= intencionesMasUsadas.value[key].count;
    totalPercentage += intencionesMasUsadas.value[key].percentage;
  }
  const otrosPercentage = 100 - totalPercentage;

  const pieChartData = Object.values(intencionesMasUsadas.value).map((item) => ({
    intentionName: item.intentionName,
    percentage: item.percentage.toFixed(2),
    count: item.count
  }));

  if (intencionesMasUsadas.value.length === 0) {
    pieChartData.push({ intentionName: "Sin intenciones detectadas", percentage: 100, count: 0 });
  } else {
    if (otrosPercentage > 0) {

    pieChartData.push({ intentionName: "Otras intenciones", percentage: otrosPercentage.toFixed(2), count: totalInts });
    }
  }

  chart.data = pieChartData;
  let from = 'top'
  const colorPalette = generateColorPalette(global.color, pieChartData.length, from);

  pieSeries.slices.template.adapter.add("fill", function(fill, target) {
  return colorPalette[target.dataItem.index % colorPalette.length];
  });

  pieSeries.labels.template.disabled = true;

  chart.legend = new am4charts.Legend();
  chart.legend.position = "bot";
  chart.legend.width = "100%";

  var legendLabels = chart.legend.labels.template;
  legendLabels.text = "{category} ({count})";

  chart.legend.scrollable = true;
  chart.legend.maxHeight = 65;

};

const generateColorPalette = (baseColor, count, from) => {
    const palette = [];
    for (let i = 0; i < count; i++) {
        let color = null
      if(from == 'top'){
          color = am4core.color(baseColor).brighten(i * 0.05);
        } else if (from == 'few'){
          color = am4core.color(baseColor).brighten(i * 0.45);
        }
        palette.push(color);
    }
    return palette;
}

const DetailResponseResults = () => {
  let chart = am4core.create("DetailResponseResults", am4charts.XYChart);
  chart.logo.disabled = true;
  chart.legend = new am4charts.Legend();
  chart.scrollbarX = new am4core.Scrollbar();

  let xAxis = chart.xAxes.push(new am4charts.CategoryAxis());
  xAxis.dataFields.category = "date";
  xAxis.renderer.cellStartLocation = 0.1;
  xAxis.renderer.cellEndLocation = 0.9;
  xAxis.renderer.grid.template.location = 0;

  let yAxis = chart.yAxes.push(new am4charts.ValueAxis());
  yAxis.min = 0;

  let data = [];

  let transformedData = {};

  intencionesPorDia.value.forEach((item) => {
      const date = item.date;
      const intentions = item.topIntentionsPerDay;

      const dateFormatted = moment(date).format("YYYY-MM-DD");
      transformedData[dateFormatted] = [];

      Object.keys(intentions).forEach((intentionName) => {
          transformedData[dateFormatted][intentionName] = {
              intencion: intentions[intentionName].intentionName,
              repeticiones: intentions[intentionName].count
          };
      });
  });

  Object.keys(transformedData).forEach(date => {
    if(transformedData[date].length != 0){
      transformedData[date].forEach(intent => {
        let dataItem = { date: date, intencion: intent.intencion, repeticiones: intent.repeticiones };
        data.push(dataItem);
      });
    } else {
      let dataItem = {date: date, intencion: 'Sin intenciones', repeticiones: 0}
      data.push(dataItem);
    }
  });

  let uniqueIntenciones = [...new Set(data.map(item => item.intencion))];

  let from = 'top'
  const colorPalette = generateColorPalette(global.color, uniqueIntenciones.length, from);

  uniqueIntenciones.forEach((intencion, index) => {
    let color = colorPalette[index];
    let series = chart.series.push(new am4charts.ColumnSeries());
    series.dataFields.valueY = "repeticiones";
    series.dataFields.categoryX = "date";
    series.name = intencion;
    series.columns.template.tooltipText = "{name}: {valueY}";
    series.columns.template.fill = color;
    series.columns.template.stroke = am4core.color(color.rgb).brighten(-0.5);
    series.data = data.filter(item => item.intencion === intencion);
  });

  chart.data = data;

};

const chartRespuestas = (am4core) => {
  let chart = am4core.create("chartRespuestas", am4charts.PieChart3D);
  chart.logo.disabled = true;
  chart.innerRadius = am4core.percent(40);
  chart.paddingRight = 20;

  const totalRespuestas = respNegativas.value + respPositivas.value + respSinCategoria.value + respSinValoracion.value;

  let pieSeries = chart.series.push(new am4charts.PieSeries3D());
  pieSeries.dataFields.value = "value";
  pieSeries.dataFields.category = "category";
  pieSeries.dataFields.count = "count";
  pieSeries.slices.template.strokeWidth = 2;
  pieSeries.slices.template.strokeOpacity = 1;

  let pieChartData = []
  if (totalRespuestas === 0) {
    pieChartData = [
      { category: "Sin Respuesta", value: 100, count: 0 },
    ];
  } else {
    pieChartData = [
      { category: "Respuestas Negativas", value: respNegativas.value, count: respNegativas.value },
      { category: "Respuestas Positivas", value: respPositivas.value, count: respPositivas.value },
      { category: "Respuestas Sin Categoría", value: respSinCategoria.value, count: respSinCategoria.value },
      { category: "Respuestas Sin Valoración", value: respSinValoracion.value, count: respSinValoracion.value }
    ];
  }

  chart.data = pieChartData;
  let from = 'few'
  const colorPalette = generateColorPalette(global.color, pieChartData.length, from);

  pieSeries.slices.template.adapter.add("fill", function(fill, target) {
    return colorPalette[target.dataItem.index % colorPalette.length];
  });

  pieSeries.labels.template.disabled = true;

  chart.legend = new am4charts.Legend();
  chart.legend.position = "down";
  chart.legend.width = "100%";

  var legendLabels = chart.legend.labels.template;
  legendLabels.text = "{category} ({count})";

};

const createResponseChartByDay = () => {
  const data = intencionesPorDia.value;
  let from = 'few'
  const colorPalette = generateColorPalette(global.color, 4, from);

  let chart = am4core.create("chartResponseByDay", am4charts.XYChart);

  var categoryAxis = chart.xAxes.push(new am4charts.CategoryAxis());
  categoryAxis.dataFields.category = "date";
  categoryAxis.renderer.minGridDistance = 60;
  categoryAxis.title.text = "Fecha";

  chart.scrollbarX = new am4core.Scrollbar();
  chart.scrollbarX.parent = chart.bottomAxesContainer;
  chart.scrollbarX.orientation = "horizontal";
  chart.scrollbarX.align = "center";
  chart.scrollbarX.width = am4core.percent(85);

  var valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
  valueAxis.title.text = "Respuestas";
  valueAxis.min = 0;

  ['Respuesta_negativa', 'Respuesta_positiva', 'Respuesta_sin_categoria', 'Respuesta_sin_valoracion'].forEach((respuesta, index)=> {
    var series = chart.series.push(new am4charts.ColumnSeries());
    series.dataFields.valueY = respuesta;
    series.dataFields.categoryX = "date";
    series.name = respuesta;
    series.tooltipText = "{name}: [bold]{valueY}[/]";
    series.columns.template.strokeWidth = 0;
    series.columns.template.strokeOpacity = 1;
    series.columns.template.width = am4core.percent(50);
    series.columns.template.column.cornerRadiusTopLeft = 5;
    series.columns.template.column.cornerRadiusTopRight = 5;

    series.columns.template.fill = colorPalette[index % colorPalette.length];
  });

  chart.data = data;

  chart.cursor = new am4charts.XYCursor();

  chart.legend = new am4charts.Legend();
  chart.legend.position = "bottom";
  chart.legend.scrollable = true;
  chart.legend.itemContainers.template.clickable = false;
  chart.legend.itemContainers.template.focusable = false;
}

</script>

<style scoped>
#chartIntentsDetailBar,
#chartIntents,
#DetailResponseResults,
#ResultAnswers {
  width: 100%;
  height: 700px;
}
.cards-dashboard{
  width: 100%;
}
.truncate-text {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
</style>
