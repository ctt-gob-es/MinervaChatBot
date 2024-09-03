<template>
  <v-app>
    <Loader :loading="loading" />
    <v-card class="outlined-0">
      <div class="px-2 mb-2">
        <p class="pl-5 my-5 text-schedule">Configuración del horario</p>

        <div v-for="(day, i) in normalDaysSchedule" :key="i">
          <v-divider v-if="day.name === 'Sábado'" :thickness="4" class="mb-5 mt-6 border-opacity-25"></v-divider>

          <div class="hours-row" wrap>
            <div v-if="activeSpecialSchedule" class="mr-2 d-flex flex-row mt-4">
              <v-chip class="chip-hours" label>
                <v-switch hide-details class="switch-name" v-model="day.active"></v-switch>
                <span class="pl-1">{{ day.name }}</span>
              </v-chip>
            </div>
            <div v-else class="mr-2 d-flex flex-row mt-4">
              <v-chip :color="day.color" class="chip-hours" label>
                <v-switch hide-details :color="day.active ? 'info' : 'error'" class="" v-model="day.active"></v-switch>
                <span class="pl-1">{{ day.name }}</span>
              </v-chip>
            </div>

            <div v-for="(timeSlot, j) in day.timeSlots" :key="j" class="d-flex mt-4">
              <div class="slots-div" wrap>
                <v-text-field v-model="timeSlot.startTime" variant="outlined" hide-details
                  :label="'Inicio ' + timeSlot.name" class="pr-3 hours-input" type="time" />
                <v-text-field v-model="timeSlot.endTime" variant="outlined" hide-details :label="'Fin ' + timeSlot.name"
                  class="pl-3 hours-input" type="time" max="23:59" />
              </div>
            </div>
          </div>
        </div>

        <div>
          <div class="d-flex mb-5 mt-5">
            <p class="pl-5 text-schedule mb-0 mr-2 especial-span">Horario especial</p>
            <v-switch class="pl-1 pt-0 mt-0" :color="activeSpecialSchedule ? 'info' : 'error'" hide-details
              v-model="activeSpecialSchedule"></v-switch>
          </div>


          <div v-if="activeSpecialSchedule">
            <div v-for="(day, i) in specialDaysSchedule" :key="i">

              <v-divider v-if="day.name === 'Sábado'" :thickness="4" class="mb-5 mt-6 border-opacity-25"></v-divider>
              <div class="hours-row" wrap>
                <div class="mr-2 d-flex flex-row mt-4">
                  <v-chip :color="day.color" class="chip-hours" label>
                    <v-switch hide-details :color="day.active ? 'info' : 'error'" class=""
                      v-model="day.active"></v-switch>
                    <span class="pl-1">{{ day.name }}</span>
                  </v-chip>
                </div>
                <div v-for="(timeSlot, j) in day.timeSlots" :key="j" class="d-flex mt-4">
                  <div class="slots-div" wrap>
                    <v-text-field v-model="timeSlot.startTime" dense variant="outlined" hide-details
                      :label="'Inicio ' + timeSlot.name" class="pr-3 hours-input" type="time" />
                    <v-text-field v-model="timeSlot.endTime" dense variant="outlined" hide-details
                      :label="'Fin ' + timeSlot.name" class="pl-3 hours-input" type="time" max="23:59" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn variant="elevated" :color="global.color" class="ma-2" dense @click="saveSettings()">Guardar</v-btn>
      </v-card-actions>
    </v-card>
  </v-app>
</template>

<script setup>
import axios from 'axios';
import { ref, onMounted, watch } from 'vue';
import Loader from "../utilities/Loader.vue";
import Swall from "sweetalert2";
import { useGlobalStore } from "../store/global";
const global = useGlobalStore();

const loading = ref(false);
const activeSpecialSchedule = ref(false);
const normalDaysSchedule = ref([]);
const specialDaysSchedule = ref([]);


const props = defineProps({
  settings: { type: Object },
  loadingSave: { type: Boolean, default: () => false },
  languages: { type: Array, default: () => [] },
});


const saveSettings = async () => {
  loading.value = true;

  const request = {
    activeSpecial: activeSpecialSchedule.value,
    normal: normalDaysSchedule.value,
    special: specialDaysSchedule.value
  }

  await axios.post("/updateBotSchedule/" + props.settings.id, request)
    .then((data) => {
      if (data.data.success) {
        Swall.fire({
          title: "Datos guardados correctamente",
          icon: "success",
          text: data.data.message,
        });
      } else {
        Swall.fire({
          title: "Atención",
          text: data.data.message,
          icon: "warning",
        });
      }
    })
    .catch((error) => {

      console.error(error);
    })
    .finally(() => {
      loadBotSchedule(props.settings.id);
      loading.value = false;
    });
};

onMounted(() => {
  loadBotSchedule(props.settings.id)
});

watch(activeSpecialSchedule, (newVal) => {
  if (newVal && specialDaysSchedule.value.length > 0) {
    specialDaysSchedule.value[0].active = true;
  }
});

const loadBotSchedule = async (id) => {
  loading.value = true;

  axios.get("/getBotSchedule/" + id)
    .then((response) => {

      const dayNameMapping = {
        lunes: 'Lunes',
        martes: 'Martes',
        miercoles: 'Miércoles',
        jueves: 'Jueves',
        viernes: 'Viernes',
        sabado: 'Sábado',
        domingo: 'Domingo'
      };

      const days = {
        Lunes: { name: 'Lunes', value: 'lunes', active: false, timeSlots: [], color: '#3ec951' },
        Martes: { name: 'Martes', value: 'martes', active: false, timeSlots: [], color: '#3ec951' },
        Miércoles: { name: 'Miércoles', value: 'miercoles', active: false, timeSlots: [], color: '#3ec951' },
        Jueves: { name: 'Jueves', value: 'jueves', active: false, timeSlots: [], color: '#3ec951' },
        Viernes: { name: 'Viernes', value: 'viernes', active: false, timeSlots: [], color: '#3ec951' },
        Sábado: { name: 'Sábado', value: 'sabado', active: false, timeSlots: [], color: '#ff9800' },
        Domingo: { name: 'Domingo', value: 'domingo', active: false, timeSlots: [], color: '#f34336' }
      };

      const specialDays = {
        Lunes: { name: 'Lunes', value: 'lunes', active: false, timeSlots: [], color: '#3ec951' },
        Martes: { name: 'Martes', value: 'martes', active: false, timeSlots: [], color: '#3ec951' },
        Miércoles: { name: 'Miércoles', value: 'miercoles', active: false, timeSlots: [], color: '#3ec951' },
        Jueves: { name: 'Jueves', value: 'jueves', active: false, timeSlots: [], color: '#3ec951' },
        Viernes: { name: 'Viernes', value: 'viernes', active: false, timeSlots: [], color: '#3ec951' },
        Sábado: { name: 'Sábado', value: 'sabado', active: false, timeSlots: [], color: '#ff9800' },
        Domingo: { name: 'Domingo', value: 'domingo', active: false, timeSlots: [], color: '#f34336' }
      };

      const timeSlotMapping = {};
      const specialTimeSlotMapping = {};

      //desde aca se cargan las normalDays

      const normalSchedules = response.data.normalSchedule

      normalSchedules.forEach(item => {
        const dayName = dayNameMapping[item.day_time_slot.day.day];
        const active = item.schedule.active === "1";
        const timeSlot = {
          id: item.id,
          name: item.day_time_slot.time_slot.name,
          value: item.day_time_slot.time_slot.name,
          startTime: item.day_time_slot.start_time,
          endTime: item.day_time_slot.end_time
        };
        timeSlotMapping[timeSlot.value] = timeSlot;
        days[dayName].timeSlots.push(timeSlot);
        days[dayName].active = active;
      })

      const daysArray = Object.values(days);

      normalDaysSchedule.value = daysArray;

      const specialSchedules = response.data.specialSchedule

      specialSchedules.forEach(item => {
        const specialDayName = dayNameMapping[item.day_time_slot.day.day];
        const specialActive = item.schedule.active === "1";
        const specialTimeSlot = {
          id: item.id,
          name: item.day_time_slot.time_slot.name,
          value: item.day_time_slot.time_slot.name,
          startTime: item.day_time_slot.start_time,
          endTime: item.day_time_slot.end_time
        };
        specialTimeSlotMapping[specialTimeSlot.value] = specialTimeSlot;
        specialDays[specialDayName].timeSlots.push(specialTimeSlot);
        specialDays[specialDayName].active = specialActive;
      })

      const specialDaysArray = Object.values(specialDays);

      const hasActiveSpecialDay = specialDaysArray.find(day => day.active === true) !== undefined;

      activeSpecialSchedule.value = hasActiveSpecialDay

      specialDaysSchedule.value = specialDaysArray;

    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
      loading.value = false;
    });
}

</script>
<style scoped>
.text-schedule {
  font-weight: 600;
  font-size: 16px;
}

.hours-row {
  display: flex;
  flex-direction: row;
  justify-content: space-evenly;
  align-items: center;
}

.especial-span {
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
}

.hours-input {
  min-width: 15vw;
}

.chip-hours {
  min-width: 130px;
  height: 50px !important;
  display: flex;
  flex-direction: row;
  justify-content: start;
  align-items: center;
}


.switch-name>div>.v-selection-control {
  justify-content: flex-end !important;

}

.slots-div {
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
}
</style>
