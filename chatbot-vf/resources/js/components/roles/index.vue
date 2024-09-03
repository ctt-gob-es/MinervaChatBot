<template>
  <div id="main-wrapper" class="mini-sidebar">
    <Loader :loading="loading" />
    <v-overlay class="d-flex flex-column text-center" :value="loading" absolute>
      <v-progress-circular color="#FFFFFF" indeterminate :size="128" :width="12"></v-progress-circular>
    </v-overlay>
    <Datatable class="tabla-m mt-2" :title="'Roles y permisos'" :headers="headers" :items="dataRoles"
      :button_add="$can('add_roles')" :titleAdd="'Agregar rol'" :button_reload="true" @click-reload="loadData()" :showSearch="true"
      @click-add="title = 'Nuevo rol', dialogRol = true, clearData()">
      <template v-slot:[`item.permissions`]="{ item }" class="col-6">
        <div>
          <span v-for="(permission, index) in item.permissions.split(', ')" :key="index"
            :style="getPermissionStyles(permission)">
            {{ permission }}
          </span>
        </div>
      </template>
      <template v-slot:[`item.actions`]="{ item }">
        <v-container v-if="item.name != 'Api' && item.name != 'SuperAdmin'">
          <v-row>
            <v-col v-if="$can('edit_roles')">
              <v-tooltip text="Tooltip" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props"
                    @click="getDataRoleId(item.id), roleName=item.name, title = 'Editar rol', dialogRol = true">
                    <v-icon color="#a1a5b7">mdi mdi-file-document-edit</v-icon></v-btn>
                </template>
                <span>Editar rol</span>
              </v-tooltip>
            </v-col>
            <v-col v-if="$can('delete_roles')">
              <v-tooltip text="Tooltip" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn icon size="small" v-bind="props" @click="confirmDeletion(item.id)">
                    <v-icon color="#a1a5b7">mdi mdi-trash-can</v-icon>
                  </v-btn>
                </template>
                <span>Eliminar rol</span>
              </v-tooltip>
            </v-col>
          </v-row>
        </v-container>
      </template>
    </Datatable>

    <v-dialog max-width="1000" v-model="dialogRol">
      <v-card :title="title">
        <v-card-text>
          <div class="form-group col-md-12">
            <label for="name">Nombre:*</label>
            <input class="form-control login-group__input" id="role_name" required="" placeholder="Nombre" name="name"
              type="text" v-model="roleName" />
            <label for="permissions" class="login-group__sub-title mt-3">Permisos:*</label>
            <br />
            <div class="row px-3">
              <v-expansion-panels>
                <template v-if="permissionsData.length">
                  <template
                    v-for="module in ['manage_dashboard', 'manage_chatbots', 'ajust_chatbots', 'manage_knowledge', 'manage_intention', 'manage_thematic', 'manage_concepts', 'manage_lists', 'manage_clients', 'manage_training', 'manage_training_manual', 'manage_conversations', 'manage_settings', 'manage_users', 'manage_roles']">
                    <v-expansion-panel v-if="hasPermissions(module)" :key="module">
                      <v-expansion-panel-title>
                        <v-row no-gutters>
                          <v-col class="d-flex justify-start" cols="4">{{ getModuleName(module) }}</v-col>
                        </v-row>
                      </v-expansion-panel-title>
                      <v-expansion-panel-text>
                        <v-row justify="start" no-gutters>
                          <div v-for="permission in getPermissionsForModule(module)" :key="permission.id"
                            class="custom-control custom-checkbox mb-2">
                            <input :id="'permission-' + permission.id"
                              class="custom-control-input not-checkbox role-permission" type="checkbox"
                              :value="permission.name" v-model="selectedPermissions" />
                            <label :for="'permission-' + permission.id" class="custom-control-label">{{
      permission.name_es }}</label>
                          </div>
                        </v-row>
                      </v-expansion-panel-text>
                    </v-expansion-panel>
                  </template>
                </template>
              </v-expansion-panels>
            </div>
          </div>
        </v-card-text>
        <v-card-actions class="pt-0 mr-6">
          <v-spacer></v-spacer>
          <v-btn variant="elevated" :color="global.color" @click="saveRol()" v-if="roleName!=='SuperAdmin'"
            data-loading-text="<span class='spinner-border spinner-border-sm'></span> Procesando ..."
            :loading="loading">
            Guardar
          </v-btn>
          <v-btn variant="tonal" class="black-close" @click="clearData(), dialogRol = false">
            Cancelar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import Datatable from "../utilities/Datatable.vue";
import axios from "axios";
import Swal from "sweetalert2";
import Loader from "../utilities/Loader.vue";
import { useGlobalStore } from "../store/global";
const global = useGlobalStore();

const dataRoles = ref([]);
const dataUpdate = ref(null);
const permissionsData = ref([]);
const dialogRol = ref(false);
const title = ref(null);
const roleName = ref("");
const selectedPermissions = ref([]);
const creatingRole = ref(false);
const loading = ref(false);
const roleId = ref(null);
const headers = ref([
  { title: "#", align: "start", sortable: true, key: "number" },
  { title: "Nombre", align: "start", sortable: true, key: "name" },
  { title: "Opciones", align: "center", sortable: false, key: "actions", width: "200px" },
]);

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
      deleteRole(id);
    }
  });
};
const deleteRole = async (id) => {
  loading.value = true;
  axios
    .delete("/deleteRole/" + id)
    .then((response) => {
      Swal.fire({
        title: "Correcto!",
        text: response.data.message,
        icon: "success",
      });
      loadData();
      loading.value = false;
      loadData();
    })
    .catch((error) => {
      Swal.fire({
        title: "Atención!",
        text: error.response.data.message,
        icon: "warning",
      });
      loading.value = false;
    });
};
const getDataPermission = async () => {
  axios
    .get("/getPermissionData")
    .then((response) => {
      permissionsData.value = response.data;
    })
    .catch((error) => {
      console.error(error);
    });
};
const hasPermissions = (module) => {
  return permissionsData.value.some(permission => permission.module === module);
};

const getPermissionsForModule = (module) => {
  return permissionsData.value.filter(permission => permission.module === module);
};

const getModuleName = (module) => {
  const moduleNames = {
    'manage_dashboard': 'Métricas',
    'manage_chatbots': 'Chatbots',
    'ajust_chatbots': 'Ajustes Chatbots',
    'manage_knowledge': 'Base de conocimiento',
    'manage_intention': 'Intenciones',
    'manage_thematic': 'Tématicas',
    'manage_concepts': 'Contextos',
    'manage_lists': 'Listas',
    'manage_clients': 'Clientes',
    'manage_training': 'Entrenamiento supervisado',
    'manage_training_manual': 'Entrenamiento manual',
    'manage_conversations': 'Conversaciones',
    'manage_settings': 'Configuración',
    'manage_users': 'Usuarios',
    'manage_roles': 'Roles',
  };
  return moduleNames[module] || module;
};
const loadData = async () => {
  loading.value = true;
  await getData();
  await getDataPermission();
};
const getData = async () => {
  try {
    const response = await axios.get("/getRoleData");
    dataRoles.value = [];
    if (response && response.data) {
      const roles = Object.entries(response.data);
      let counter = 1; // Inicializamos un contador

      roles.forEach(([roleId, role]) => {
        if (role && role.permissions) {
          const obj = {
            number: counter++, // Agregamos el número y luego incrementamos el contador
            id: role.id,
            name: role.name,
            permissions: Array.isArray(role.permissions)
              ? role.permissions
                .map((permission) => permission.name_es)
                .join(", ")
              : role.permissions.toString(),
            actions: "",
          };
          dataRoles.value.push(obj);
        } else {
          console.error(
            `Error: Role or permissions property is undefined for role with ID ${roleId}.`
          );
        }
      });

    } else {
      console.error("Error: Response is not valid or is undefined.");
    }
    loading.value = false;
  } catch (error) {
    console.error(error);
  }
};

const getDataRoleId = (item) => {
  loading.value = true;
  axios
    .get("/getRoleId/" + item)
    .then((response) => {
      dataUpdate.value = response.data[0];
      roleId.value = dataUpdate.value.id;
      roleName.value = dataUpdate.value.name;
      selectedPermissions.value = dataUpdate.value.permissions.map(
        (permission) => permission.name
      );
      loading.value = false;
    })
    .catch((error) => {
      console.error(error);
      loading.value = false;
    });
};

const getPermissionStyles = (permission) => {
  return {
    backgroundColor: "#f5f8fa",
    borderRadius: "50px",
    padding: "0.25rem 0.5rem",
    color: "var(--primary-color)",
  };
};

const clearData = () => {
  roleName.value = "";
  selectedPermissions.value = [];
  title.value = null;
  roleId.value = null;
};

const saveRol = async () => {
  loading.value = true;
  if (roleName.value === '') {
    Swal.fire({
      title: "Atención!",
      text: "Debes digitar el rol",
      icon: "warning",
    });
    loading.value = false;
    return;
  }
  if (selectedPermissions.value.length === 0) {
    Swal.fire({
      title: "Atención!",
      text: "Debes seleccionar al menos un permiso",
      icon: "warning",
    });
    loading.value = false;
    return;
  }

  if (title.value === 'Editar rol') {
    await updateRole();
  } else {
    await createRole();
  }
};

const createRole = () => {
  loading.value = true;
  const formData = {
    name: roleName.value,
    permissions: selectedPermissions.value,
  };
  axios
    .post("/createRole", formData)
    .then((response) => {
      Swal.fire({
        title: "Correcto!",
        text: response.data.message,
        icon: "success",
      });
      selectedPermissions.value = [];
      roleName.value = "";
      clearData();
      getData();
      dialogRol.value = false;
    })
    .catch((error) => {
      Swal.fire({
        title: "Atención!",
        text: error.response.data.message,
        icon: "warning",
      });
    })
    .finally(() => {
      loading.value = false;
    });
};
const updateRole = async () => {
  creatingRole.value = true;
  loading.value = true;
  const formData = {
    id: roleId.value,
    name: roleName.value,
    permissions: selectedPermissions.value,
  };
  axios
    .post("/updateRole", formData)
    .then((response) => {

      Swal.fire({
        title: "Correcto!",
        text: response.data.message,
        icon: "success",
      });
      selectedPermissions.value = [];
      roleName.value = "";
      clearData();
      getData();
      dialogRol.value = false;
    })
    .catch((error) => {
      Swal.fire({
        title: "Atención!",
        text: error.response.data.message,
        icon: "warning",
      });
    }).finally(() => {
      loading.value = false;
    });
};
onMounted(async () => {
  await loadData();
});
</script>
