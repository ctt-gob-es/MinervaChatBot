<template>
  <div class="mt-7">
    <v-card>
      <v-card-title class="title-datatable-section">
        <div class="title-datatable">
          <span class="title-vuely">
            Editar Perfil
          </span>
          <div class="d-flex">
            <slot name="buttons-header"></slot>
            <v-spacer></v-spacer>
            <v-btn v-if="$can('change_password')" prepend-icon="mdi mdi-lock-check-outline"
              class="m-3 btn-change-pass" @click="openDialog()">Cambiar Contraseña</v-btn>
          </div>
        </div>
      </v-card-title>
      <div class="px-5 py-5 d-flex justify-center">
        <div class="col-5">
          <v-row class="d-flex justify-center">
            <div class="d-flex col-12 w-100">
              <div class="col-12 edit-profile-card w-100 mb-5 position-relative photo-container">
                <label for="fileInput" class="d-flex flex-column align-center mb-5 profile__img-wrapper">
                  <v-img :src="selectedImage ? selectedImage : img_users" style="cursor: pointer; border-radius: 50%;"
                    width="150" height="150" cover></v-img>
                  <input id="fileInput" type="file" class="position-absolute top-0 start-0 h-100 w-100 opacity-0"
                    @change="onFileSelected" />
                </label>
                <div class="text-center mb-4 edit-profile-card__btn d-flex col-12 justify-center gap-3">
                  <v-tooltip location="start" text="Subir">
                    <template v-slot:activator="{ props }">
                      <v-btn elevation="0" class="btn-search" v-bind="props" @click="triggerFileInput">
                        <span class="mdi mdi-upload big-icon"></span>
                      </v-btn>
                    </template>
                  </v-tooltip>
                  <v-tooltip location="end" text="Eliminar">
                    <template v-slot:activator="{ props }">
                      <v-btn elevation="0" class="btn-search" v-bind="props" color="red" @click="clearFileInput">
                        <span class="mdi mdi-delete big-icon"></span>
                      </v-btn>
                    </template>
                  </v-tooltip>
                </div>
              </div>
            </div>
            <v-text-field class="col-12" label="Nombre" variant="solo-filled" v-model="selectedUser.name"
              :error-messages="errorNameMessages" @update:modelValue="clearError">
            </v-text-field>
            <v-text-field class="col-12" label="Correo" variant="solo-filled" v-model="selectedUser.email"
              :error-messages="errorMailMessages" @update:modelValue="clearError"></v-text-field>
          </v-row>
          <v-card-actions>
            <v-spacer />
            <v-btn class="btn-search ml-3" :disabled="!isValid" @click="updateUser(selectedUser.id)">
              Guardar
              <span v-if="loading" class='spinner-border spinner-border-sm ml-1'></span>
            </v-btn>
            <v-btn @click="returnPage()" class="black-close" variant="tonal">
              Cancelar
            </v-btn>
          </v-card-actions>
        </div>
      </div>
    </v-card>
    <v-dialog width="500" v-model="dialogPassword">
      <change-password :userId="idUser" @password-changed="handlePasswordChanged"></change-password>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from "vue";
import axios from "axios";
import { useRoute } from "vue-router";
import img_users from "../../../images/user.svg";
import Swal from "sweetalert2";
import ChangePassword from "./changePassword.vue";

const idUser = ref(null);
const route = useRoute();
const loading = ref(false);
const selectedImage = ref(null);
const dialogPassword = ref(false);
const errorNameMessages = ref(null);
const errorMailMessages = ref(null);
const selectedUser = reactive({
  id: "",
  name: "",
  email: "",
  photo: "",
});

const openDialog = () => {
  dialogPassword.value = true;
}

onMounted(async () => {
  await loadData();

});

const handlePasswordChanged = () => {
  dialogPassword.value = false;
}

const loadData = async () => {
  idUser.value = route.params.idUser
  await getUser(idUser.value);
};

const triggerFileInput = () => {
  // Simular el clic en el input de archivo
  document.getElementById('fileInput').click();
}

const clearFileInput = () => {
  selectedUser.photo = null
  selectedImage.value = null
}

const onFileSelected = (event) => {
  const file = event.target.files[0];
  if (file) {
    selectedUser.photo = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      selectedImage.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const updateUser = async (idUser) => {
  if (isValid.value) {
    loading.value = true
    let request = selectedUser
    const config = {
      headers: {
        "content-type": "multipart/form-data",
      },
    };
    axios
      .post("/updateUser/" + idUser, request, config)
      .then((response) => {
        if (response.data.success) {
          setTimeout(() => {
            loading.value = false
            window.history.back();
          }, 2000);
        } else {
          Swal.fire({
            title: "Error",
            text: "Hubo un error al guardar el usuario",
            icon: "error",
          });
        }
      })
      .catch((error) => {
        console.error(error);
      })
      .finally(() => {
      });
  }
}

const returnPage = () => {
  window.history.back();
}

const getUser = async (idUser) => {
  axios
    .get("/getUser/" + idUser)
    .then((response) => {
      selectedUser.id = response.data.data.id
      selectedUser.name = response.data.data.name
      selectedUser.photo = response.data.data.photo
      selectedUser.email = response.data.data.email

      if (response.data.data.photo) {
        selectedImage.value = '/support/userProfile/' + response.data.data.photo
      } else {
        selectedImage.value = null
      }
      isValid.value = false
      clearError()
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
    });
};

const isValid = computed(() => {
  return validateData();
});

const clearError = () => {
  if (isValid.value) {
    errorNameMessages.value = null;
    errorMailMessages.value = null;
  }
};

const validateData = () => {
  const emailRegex = /\S+@\S+\.\S+/;
  if (!selectedUser.name || !selectedUser.email) {
    if (!selectedUser.name) {
      errorNameMessages.value = "Este campo es obligatorio";
    }
    if (!selectedUser.email) {
      errorMailMessages.value = "Este campo es obligatorio";
    }
    if (selectedUser.name) {
      errorNameMessages.value = null;
    }
    if (selectedUser.email) {
      errorMailMessages.value = null;
      if (!emailRegex.test(selectedUser.email)) {
        errorMailMessages.value = "Este campo debe ser un correo electrónico válido";
      }
    }
    return false;
  }

  if (selectedUser.name) {
    errorNameMessages.value = null;
  }

  if (!emailRegex.test(selectedUser.email)) {
    errorMailMessages.value = "Este campo debe ser un correo electrónico válido";
    return false;
  }
  return true;
};

</script>

<style scoped>

.title-vuely {
  font-size: 22px !important;
  color: #ffff !important;
  align-items: center;
  display: flex;
  padding-left: 12px;
}

.black-close {
  background: rgb(103, 100, 100) !important;
  color: white !important;
}

.title-datatable {
  border-bottom: 1px solid rgba(253, 253, 253, 0.2) !important;
  padding: 0rem 0.2rem !important;
  background-color: var(--primary-color) !important;
  margin-bottom: 15px;
  display: flex;
  justify-content: space-between;
}

.title-datatable-section {
  display: flex;
  flex-direction: column;
  align-items: inherit;
  padding: 0 !important;
}

.big-icon {
  font-size: 20px;
}

.photo-container {
  border-radius: 2%;
  background-color: rgb(250, 250, 250) !important;
  box-shadow: 0px 1px 2px rgb(136, 136, 136);
}
.btn-change-pass {
  background-color: #fff;
}
</style>
