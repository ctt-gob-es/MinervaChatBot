<template>
  <v-card title="Nueva contraseña">
    <v-card-text class="pb-0">
      <form class="pb-0" method="dialog">
        <v-text-field class="mb-2"
          v-model="newPassword.pass1"
          label="Nueva Contraseña"
          variant="outlined"
          required
          :append-icon="showPassword1 ? 'mdi-eye' : 'mdi-eye-off'"
          :type="showPassword1 ? 'text' : 'password'"
          @click:append="showPassword1 = !showPassword1"
          :error-messages="errorMessages.password1"
          hint="Al menos 8 caracteres. Usa al menos 1 minúscula, 1 mayúscula y 1 número"
          autocomplete="off"
          ></v-text-field>
        <v-text-field
        class="mb-2"
        v-model="newPassword.pass2"
          label="Confirmar Contraseña"
          variant="outlined"
          required
          :append-icon="showPassword2 ? 'mdi-eye' : 'mdi-eye-off'"
          :type="showPassword2 ? 'text' : 'password'"
          @click:append="showPassword2 = !showPassword2"
          :error-messages="errorMessages.confirmPassword"
          hint="Al menos 8 caracteres"
          autocomplete="off"
          ></v-text-field>
      </form>
    </v-card-text>
    <v-card-actions class="pt-0 mr-4">
      <v-spacer></v-spacer>
      <v-btn
        variant="elevated"
        :color="global.color"
        :disabled="!newPassword.pass1 || !newPassword.pass2"
        @click="savePassword(props.userId)"

        >
        <span v-if="loading" class='spinner-border spinner-border-sm'></span>
        Guardar
        </v-btn
      >
      <v-btn class="btn-search black-close" variant="tonal" @click="dialogClose"> Cancelar </v-btn>
    </v-card-actions>
  </v-card>

</template>

<script setup>
import { ref, reactive } from "vue";
import axios from "axios";
import { useGlobalStore } from "../store/global";
import Swall from "sweetalert2";
const global = useGlobalStore();
const showPassword1 = ref(false);
const showPassword2 = ref(false);
const loading = ref(false);
const props = defineProps({
  userId: { type: Object },
});
const newPassword = reactive({
  pass1: "",
  pass2: "",
});
const errorMessages = reactive({
  password1: null,
  confirmPassword: null,
});

const validateFields = () => {
  if (newPassword.pass1.length < 8) {
    errorMessages.password1 = "La contraseña debe tener al menos 8 caracteres.";
    return false;
  } else {
    errorMessages.password1 = ""
  }
  if (newPassword.pass2.length < 8) {
    errorMessages.confirmPassword = "La contraseña debe tener al menos 8 caracteres.";
    return false;
  } else {
    errorMessages.confirmPassword = ""
  }
  if(!/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/.test(newPassword.pass1)){
    errorMessages.password1 = "La contraseña debe contener al menos un número, una letra minúscula y una letra mayúscula.";
    return false;
  } else {
    errorMessages.password1 = ""
  }
  if (newPassword.pass1 != newPassword.pass2) {
    errorMessages.confirmPassword = "Las contraseñas deben coincidir.";
    return false;
  } else {
    errorMessages.confirmPassword = ""
  }
  return true;
}

const savePassword = (id) => {
  if (!validateFields()) {
    return;
  }

  loading.value = true

  errorMessages.password1 = null
  errorMessages.confirmPassword = null

  let request = newPassword;

  axios.post('newPassword/'+id, request)
  .then((response) => {
    if(response.data.success){

        Swall.fire({
        title: "Contraseña guardada",
        icon: "success",
      });
    loading.value = false
    dialogClose()
    } else {
        Swall.fire({
        title: "Atención",
        icon: "warning",
      });
    }
    loading.value = false
  })
}

const emit = defineEmits(['password-changed']);

const dialogClose = () => {
  emit('password-changed');
}
</script>

<style scoped>
.black-close{
  background: rgb(103, 100, 100) !important;
  color: white !important;
}
</style>
