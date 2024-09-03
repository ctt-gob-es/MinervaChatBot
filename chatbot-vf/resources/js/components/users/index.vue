<template>
  <div style="padding-bottom: 50px">
    <DateRange class="mt-7" @submit="loadData" @updateDate="setDate" @clearData="clearData" :initialFilter="false" />
    <Loader :loading="loading" />
    <Datatable ref="datatable" class="tabla-m" :title="'Usuarios'" :headers="headers" :items="users"
      @click-reload="getUsers()" :showSearch="true" @click-add="
      getClients(), (optionUser = 'Nuevo usuario'), (dialogVisible = true), clearFields()
      " :button_add="$can('users_add')" :button_reload="true" :titleAdd="'Agregar usuario'">
      <template v-slot:[`item.photo`]="{ item }">
        <div class="mx-auto my-auto">
          <v-img height="50" width="50" :src="item.photo ? '/support/userProfile/' + item.photo : img_users"></v-img>
        </div>
      </template>

      <template v-slot:[`item.status`]="{ item, index }">
        <v-container>
          <v-row align="center" justify="center">
            <v-col cols="auto">
              <v-tooltip text="Tooltip">
                <template v-slot:activator="{ props }">
                  <v-switch v-if="item.deleted_at === 'activo'" v-model="checkboxes[index]" @change="updateState(item)"
                    color="success" label="Activo" :value="true" hide-details></v-switch>
                  <v-switch v-else v-model="checkboxes[index]" @change="updateState(item)" color="success"
                    label="Inactivo" value="false" hide-details></v-switch>
                </template>
              </v-tooltip>
            </v-col>
          </v-row>
        </v-container>
      </template>

      <template v-slot:[`item.options`]="{ item }">
        <v-container>
          <v-row align="center" justify="center">
            <v-col cols="auto" v-if="$can('users_history')">
              <v-tooltip text="Tooltip" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="dialogHistoryOpen(item.id)">
                    <v-icon color="#a1a5b7">mdi mdi-clipboard-text-clock-outline</v-icon></v-btn>
                </template>
                <span>Historial </span>
              </v-tooltip>
            </v-col>
            <v-col cols="auto" v-if="$can('users_edit')">
              <v-tooltip text="Tooltip" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="
      getUsersId(item.id),
      getClientUser(item.id), roleName = item.rol,
      (optionUser = 'Editar usuario')
      ">
                    <v-icon color="#a1a5b7">mdi mdi-file-document-edit</v-icon></v-btn>
                </template>
                <span>Editar </span>
              </v-tooltip>
            </v-col>
            <v-col cols="auto" v-if="$can('users_delete')">
              <v-tooltip text="Tooltip" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="confirmDeletion(item.id)">
                    <v-icon color="#a1a5b7">mdi mdi-trash-can</v-icon>
                  </v-btn>
                </template>
                <span>Eliminar </span>
              </v-tooltip>
            </v-col>
          </v-row>
        </v-container>
      </template>
    </Datatable>
    <v-dialog width="900" v-model="dialogVisible" v-if="dialogVisible">
      <v-card :title="optionUser">
        <v-card-text>
          <div>
            <div class="d-flex justify-content-between">
              <label for="fileInput" class="col-2 cursor-pointer">
                <v-img :src="selectedImage ? selectedImage : img_users" class="mr-3 cursor-pointer" width="150"></v-img>
                <input id="fileInput" type="file" class="top-0 start-0 h-100 w-100 opacity-0"
                  @change="onFileSelected" />
              </label>
              <div class="col-10">
                <v-row>
                  <v-text-field class="col-12" label="Nombre completo" required variant="outlined"
                    v-model="newUser.name" :error-messages="errorMessages.name" autocomplete="off"></v-text-field>
                </v-row>
                <v-row>
                  <v-text-field class="col-6" label="Correo electrónico" required variant="outlined"
                    v-model="newUser.email" :error-messages="errorMessages.email" autocomplete="off"></v-text-field>
                  <v-select class="col-6" variant="outlined" label="Rol" :items="rolesData" item-title="name"
                    item-value="id" v-model="newUser.role" :error-messages="errorMessages.role"
                    autocomplete="off"></v-select>
                </v-row>
              </div>
            </div>
            <v-row class="mt-1"
              v-if="optionUser === 'Nuevo usuario' || global.roleUser === 'SuperAdmin' || global.roleUser === 'Administrador'">
              <v-text-field class="col-6" label="Contraseña" required variant="outlined" v-model="newUser.password"
                :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'" :type="showPassword ? 'text' : 'password'"
                @click:append="showPassword = !showPassword" :error-messages="errorMessages.password"
                hint="Al menos 8 caracteres. Usa al menos 1 minúscula, 1 mayúscula y 1 número."
                autocomplete="off"></v-text-field>
              <v-text-field class="col-6" label="Confirmar contraseña" required variant="outlined"
                v-model="newUser.confirmPassword" :append-icon="showConfirmPassword ? 'mdi-eye' : 'mdi-eye-off'"
                :type="showConfirmPassword ? 'text' : 'password'"
                @click:append="showConfirmPassword = !showConfirmPassword"
                :error-messages="errorMessages.confirmPassword" autocomplete="off"
                hint="Al menos 8 caracteres"></v-text-field>
            </v-row>
            <Datatable v-if="newUser.role != apiRoleId" ref="datatable" class="tabla-m mt-3" :title="'Clientes'" :headers="headers_client"
              :items="clients" :showSearch="true" @changeSelection="changeSelection" :initSelection="selectClients"
              :enableSelect="true" return-object>
            </Datatable>
          </div>
        </v-card-text>
        <v-card-actions class="pt-0 mr-4">
          <v-spacer></v-spacer>
          <v-btn variant="elevated" :color="global.color" @click="saveUser()">
            Guardar
          </v-btn>
          <v-btn variant="tonal" class="black-close" @click="dialogClose">
            Cancelar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog width="1200" v-model="dialogHistory">
      <v-card>
        <v-card-text>
          <Datatable ref="datatable" class="tabla-m" :title="'Histórico Inicio Sesión'" :headers="headers_history"
            :items="historyData" :showSearch="true">
            <template v-slot:[`item.session_data`]="{ item }">
              <span><b>Nombre: </b> {{ JSON.parse(item.session_data).name }}</span><br />
              <span><b> Rol:</b> {{ JSON.parse(item.session_data).rol }}</span><br />
            </template>
          </Datatable>
        </v-card-text>
        <v-card-actions class="mr-4">
          <v-spacer></v-spacer>
          <v-btn variant="tonal" text="Cancelar" class="black-close" @click="dialogHistoryClose()"></v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from "vue";
import Datatable from "../utilities/Datatable.vue";
import DateRange from "../utilities/DateRange.vue";
import Loader from "../utilities/Loader.vue";
import img_users from "../../../images/user.svg";
import { formatDateTime } from "@/helpers";
import moment from "moment";
import axios from "axios";
import Swal from "sweetalert2";
import { useGlobalStore } from "../store/global";
const global = useGlobalStore();

const dateFrom = ref(null);
const dateTo = ref(null);
const users = ref([]);
const clients = ref([]);
const historyData = ref([]);
const rolesData = ref(null);
const roleName = ref(null);
const checkboxes = ref([]);
const loading = ref(true);
const apiRoleId = ref(null);
const dialogVisible = ref(false);
const dialogHistory = ref(false);
const selectedImage = ref(null);
const newUser = reactive({
  id: null,
  name: "",
  email: "",
  role: "",
  password: "",
  photo: "",
  confirmPassword: "",
});
const errorMessages = reactive({
  name: null,
  email: null,
  role: null,
  password: null,
  confirmPassword: null,
});
const selectClients = ref([]);
const showPassword = ref(false);
const showConfirmPassword = ref(false);
const validarCorreoElectronico = (email) => {
  // Expresión regular para validar el formato del correo electrónico
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  return emailRegex.test(email);
};

const headers = ref([
  { title: "", align: "center", sortable: true, key: "photo", width: "50px" },
  { title: "Usuario", align: "start", sortable: true, key: "name" },
  { title: "Email", align: "start", sortable: true, key: "email" },
  { title: "Rol", align: "start", sortable: true, key: "rol" },
  { title: "Estado", align: "center", sortable: false, key: "status" },
  {
    title: "Fecha creación",
    align: "center",
    sortable: false,
    key: "created_at",
  },
  { title: "Opciones", align: "center", sortable: false, key: "options" },
]);

const headers_history = ref([
  {
    title: "Datos de sesión",
    align: "start",
    sortable: true,
    key: "session_data",
  },
  { title: "Dirección IP", align: "center", sortable: true, key: "ip" },
  { title: "Navegador", align: "center", sortable: false, key: "browser" },
  { title: "Plataforma", align: "center", sortable: false, key: "platform" },
  {
    title: "Fecha y hora",
    align: "center",
    sortable: false,
    key: "created_at",
  },
]);
const optionUser = ref("Nuevo usuario");
const headers_client = ref([
  { title: "", align: "start", sortable: true, key: "photo", width: "50px" },
  { title: "Nombre", align: "start", sortable: true, key: "name" },
  { title: "Información", align: "start", sortable: true, key: "information" },
]);
const changeSelection = (e) => {
  selectClients.value = [];
  e.forEach((item) => {
    const id = item.id;
    selectClients.value.push({ id });
  });
};

const validateFields = () => {
  if (newUser.name.trim() === "") {
    errorMessages.name = "Este campo es obligatorio.";
    return false;
  } else {
    errorMessages.name = "";
  }
  if (newUser.email.trim() === "") {
    errorMessages.email = "Este campo es obligatorio.";
    return false;
  } else if (!validarCorreoElectronico(newUser.email.trim())) {
    errorMessages.email = "Por favor, ingresa un correo electrónico válido.";
    return false;
  } else {
    errorMessages.email = "";
  }

  if (
    typeof newUser.role === "string" &&
    (newUser.role.trim() === "" || newUser.role.trim() === '""')
  ) {
    errorMessages.role = "Este campo es obligatorio.";
    return false;
  } else {
    errorMessages.role = "";
  }
  if (newUser.password.trim() === "") {
    errorMessages.password = "Este campo es obligatorio.";
    return false;
  } else if (newUser.password.length < 8) {
    errorMessages.password = "La contraseña debe tener al menos 8 caracteres.";
    return false;
  } else if (!/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/.test(newUser.password)) {
    errorMessages.password = "La contraseña debe contener al menos un número, una letra minúscula y una letra mayúscula.";
    return false;
  } else {
    errorMessages.password = "";
  }

  if (newUser.confirmPassword.trim() === "") {
    errorMessages.confirmPassword = "Este campo es obligatorio.";
    return false;
  } else if (newUser.password !== newUser.confirmPassword) {
    errorMessages.confirmPassword = "Las contraseñas no coinciden.";
    return false;
  } else {
    errorMessages.confirmPassword = "";
  }
  if(newUser.role != apiRoleId.value){
    if (!Object.keys(selectClients.value).length) {
      Swal.fire({
        title: "Atención!",
        text: "Debes seleccionar como mínimo un cliente.",
        icon: "warning",
      });
      return false;
    }
  }

  return true;
};
const validateFieldsEdit = () => {
  if (newUser.name.trim() === "") {
    errorMessages.name = "Este campo es obligatorio.";
    return false;
  } else {
    errorMessages.name = "";
  }
  if (newUser.email.trim() === "") {
    errorMessages.email = "Este campo es obligatorio.";
    return false;
  } else if (!validarCorreoElectronico(newUser.email.trim())) {
    errorMessages.email = "Por favor, ingresa un correo electrónico válido.";
    return false;
  } else {
    errorMessages.email = "";
  }
  if (newUser.password.trim() !== "") {
    if (newUser.password.length < 8) {
      errorMessages.password = "La contraseña debe tener al menos 8 caracteres.";
      return false;
    } else if (!/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/.test(newUser.password)) {
      errorMessages.password = "La contraseña debe contener al menos un número, una letra minúscula y una letra mayúscula.";
      return false;
    } else {
      errorMessages.password = "";
    }
    if (newUser.confirmPassword.trim() === "") {
      errorMessages.confirmPassword = "Este campo es obligatorio.";
      return false;
    } else if (newUser.password !== newUser.confirmPassword) {
      errorMessages.confirmPassword = "Las contraseñas no coinciden.";
      return false;
    } else {
      errorMessages.confirmPassword = "";
    }
  }
  if (
    typeof newUser.role === "string" &&
    (newUser.role.trim() === "" || newUser.role.trim() === '""')
  ) {
    errorMessages.role = "Este campo es obligatorio.";
    return false;
  } else {
    errorMessages.role = "";
  }
  if(newUser.role != apiRoleId.value){
    if (!Object.keys(selectClients.value).length) {
      Swal.fire({
        title: "Atención!",
        text: "Debes seleccionar como mínimo un cliente.",
        icon: "warning",
      });
      return false;
    }
  }
  return true;
};
const setDate = (from, to) => {
  if (from) dateFrom.value = from;
  if (to) dateTo.value = to;
};
const clearData = async () => {
  dateFrom.value = null;
  dateTo.value = null;
  await loadData();
};
const clearFields = () => {
  newUser.name = "";
  newUser.email = "";
  newUser.role = "";
  newUser.password = "";
  newUser.photo = "";
  newUser.confirmPassword = "";
  selectedImage.value = null;
  selectClients.value = [];
};

const dialogClose = async () => {
  dialogVisible.value = false;
  clearFields();
  await loadData();
};
const dialogHistoryOpen = async (id) => {
  await getAccessHistory(id);
  dialogHistory.value = true;
};
const dialogHistoryClose = async () => {
  dialogHistory.value = false;
};

const confirmDeletion = (id) => {
  Swal.fire({
    title: "¿Estás seguro?",
    text: "¡No podrás revertir esto!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "¡Sí, bórralo!",
  }).then((result) => {
    if (result.isConfirmed) {
      deleteUser(id);
    }
  });
};
const deleteUser = async (id) => {
  loading.value = true;
  axios
    .delete("/deleteUser/" + id)
    .then((response) => {
      Swal.fire({
        title: "Excelente",
        text: "Cambios realizados!",
        icon: "success",
      });
      getUsers();
      loading.value = false;
    })
    .catch((error) => {
      if (error.response.status === 400) {
        Swal.fire({
          title: "Error",
          text: error.response.data.error,
          icon: "error",
        });
      } else {
        console.error(error);
      }
      loading.value = false;
    });
};

const getUsers = async () => {
  loading.value = true;
  try {
    let from = null;
    let to = null;
    if (dateFrom.value !== null) {
      if (dateTo.value === null) {
        Swal.fire({
          title: "Atención!",
          text: "Debes seleccionar la fecha (Hasta)",
          icon: "warning",
        });
        loading.value = false;
        return;
      }
    }
    if (dateFrom.value && dateTo.value) {
      from = moment(dateFrom.value, "YYYY-MM-DD").format("YYYY-MM-DD");
      to = moment(dateTo.value, "YYYY-MM-DD").format("YYYY-MM-DD");
    }
    const response = await axios.get("/getUsers", {
      params: {
        ...(from && to && { from, to }),
      },
    });
    const data = response.data.data;
    const checkboxes_aux = [];
    for (const i in data) {
      const status = data[i].deleted_at === null ? "activo" : "inactivo";
      data[i].deleted_at = status;
      data[i].created_at = formatDateTime(data[i].created_at);
      checkboxes_aux.push(status == "activo" ? true : false);
    }
    users.value = data

    checkboxes.value = checkboxes_aux;
  } catch (error) {
    console.error(error);
  }
  loading.value = false;
};

function parseDate(dateString) {
    let [day, month, yearTime] = dateString.split('/');
    let [year, time] = yearTime.split(', ');
    return new Date(`${month}/${day}/${year} ${time}`);
}

const getRoles = async () => {
  try {
    const { data } = await axios.get("/getRoles");

    const apiRole = data.find(role => role.name === "Api");
    if (apiRole) {
      apiRoleId.value = apiRole.id;
    } else {
      console.error("El rol 'Api' no fue encontrado.");
    }

    rolesData.value = data;
  } catch (error) {
    console.error(error);
  }
  loading.value = false;
};

const updateState = (param) => {
  loading.value = true;
  const formData = {
    id: param.id,
    state: param.deleted_at,
  };
  axios
    .post("/updateState", formData)
    .then((response) => {
      Swal.fire({
        title: "Correcto!",
        text: response.data.message,
        icon: "success",
      });
      getUsers();
      loading.value = false;
    })
    .catch((error) => {
      console.error(error);
      loading.value = false;
    });
};
const loadData = async () => {
  loading.value = true;
  await getRoles();
  await getUsers();
};

onMounted(async () => {
  await loadData();
});

const onFileSelected = (event) => {
  const file = event.target.files[0];
  if (file) {
    newUser.photo = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      selectedImage.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const getUsersId = async (id) => {
  loading.value = true;
  selectedImage.value = null;
  await axios
    .get("/getUsersId/" + id)
    .then((response) => {
      newUser.id = response.data.data.user.id;
      newUser.name = response.data.data.user.name;
      newUser.email = response.data.data.user.email;
      newUser.photo = response.data.data.user.photo;
      newUser.role = response.data.data.roles.id;
      clients.value = response.data.data.clients;
      if (response.data.data.user.photo != null) {
        selectedImage.value =
          "/support/userProfile/" + response.data.data.user.photo;
      }
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
      loading.value = false;
    });
};
const getClients = async () => {
  loading.value = true;
  await axios
    .get("/getClients")
    .then((response) => {
      clients.value = response.data;
      loading.value = false;
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
      loading.value = false;
    });
};
const getClientUser = async (id) => {
  loading.value = true;
  await axios
    .get("/getClientUser/" + id)
    .then((response) => {
      selectClients.value = response.data;
      loading.value = false;
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
      dialogVisible.value = true;
      loading.value = false;
    });
};
const saveUser = () => {
  const config = {
    headers: {
      "content-type": "multipart/form-data",
    },
  };
  if (optionUser.value === "Nuevo usuario") {
    if (!validateFields()) {
      return;
    }
    const formData = new FormData();
    formData.append("name", newUser.name);
    formData.append("email", newUser.email);
    formData.append("password", newUser.password);
    formData.append("photo", newUser.photo);
    formData.append("role", newUser.role);
    formData.append("clients", JSON.stringify(selectClients.value));
    loading.value = true;
    axios
      .post("/saveUser", formData, config)
      .then((response) => {
        Swal.fire({
          title: "Correcto!",
          text: response.data.message,
          icon: "success",
        });
        dialogClose();
        clearFields();
        loadData();
        loading.value = false;
      })
      .catch((error) => {
        Swal.fire({
          title: "Atención!",
          text: error.response.data.message,
          icon: "warning",
        });
        loading.value = false;
      });
  } else {
    if (!validateFieldsEdit()) {
      return;
    }
    const formData = new FormData();
    formData.append("id", newUser.id);
    formData.append("name", newUser.name);
    formData.append("email", newUser.email);
    formData.append("password", newUser.password);
    formData.append("photo", newUser.photo);
    formData.append("role", newUser.role);
    formData.append("clients", JSON.stringify(selectClients.value));
    axios
      .post(`/updateUserData`, formData, config)
      .then((response) => {
        Swal.fire({
          title: "Correcto!",
          text: response.data.message,
          icon: "success",
        });
        dialogClose();
        clearFields();
        loadData();
        loading.value = false;
      })
      .catch((error) => {
        Swal.fire({
          title: "Atención!",
          text: error.response.data.message,
          icon: "warning",
        });
        loading.value = false;
      });
  }
};

const getAccessHistory = async (id) => {
  loading.value = true;
  await axios
    .get("/getAccessHistory/" + id)
    .then((response) => {
      historyData.value = response.data.data.map((intention) => ({
        ...intention,
        created_at: formatDateTime(intention.created_at),
      }));
    })
    .catch((error) => {
      console.error(error);
    })
    .finally(() => {
      loading.value = false;
    });
};
</script>

<style scoped>
.black-close {
  background: rgb(103, 100, 100) !important;
  color: white !important;
}

.swal2-container {
  z-index: 2500;
}
</style>
