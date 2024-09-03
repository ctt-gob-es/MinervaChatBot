<template>
  <div>
    <v-card :elevation="elevation">
      <v-card-title v-if="showHeader" class="title-datatable-section">
        <div class="title-datatable">
          <span class="title-vuely pr-2">
            {{ title }}
          </span>

          <div class="d-flex flex-wrap justify-center align-items-center gap-1 principal_botones_table">
            <v-text-field v-if="showSearch" v-model="search" prepend-inner-icon="mdi-magnify" density="compact"
              label="Buscar" single-line flat hide-details variant="solo-filled" class="showSearch mr-3"></v-text-field>

            <div class="botones_table">
              <slot name="buttons-header"></slot>
              <slot name="slot-buttons"></slot>
              <v-btn class="btn-log btn-datatable" v-if="view_default" href="editConfiguration"
                title="Ajustes predeterminados">
                <v-icon class="btn-icon-data" color="#212529" size="large">mdi mdi-file-find</v-icon>
                <span class="btn-txt ml-1">Predeterminados</span>
              </v-btn>
              <v-btn align="center" class="btn-log btn-datatable btn-reload"
                v-if="button_template_int && $can('download_template_intentions')" title="Plantilla"
                href="/support/template_csv/IMPORT_INTENTIONS.xlsx">
                <v-icon class="btn-icon-data" size="large" color="#212529">mdi mdi-file-excel</v-icon>
                <span class="btn-txt ml-1">PLANTILLA</span>
              </v-btn>
              <v-btn align="center" class="btn-log btn-datatable btn-reload"
                v-if="button_import_int && $can('import_template_intentions')" title="Importar"
                @click="$emit('click-import-intention')">
                <v-icon class="btn-icon-data" size="large" color="#212529">mdi mdi-file-import</v-icon>
                <span class="btn-txt ml-1">IMPORTAR</span>
              </v-btn>
              <v-btn class="btn-log btn-datatable" v-if="button_add" :title="titleAdd" @click="$emit('click-add')">
                <v-icon class="btn-icon-data" color="#212529" size="large">mdi mdi-plus-circle</v-icon>
                <span class="btn-txt ml-1">Agregar</span>
              </v-btn>
              <v-btn align="center" class="btn-log btn-datatable btn-reload" v-if="button_reload"
                @click="$emit('click-reload')" title="Recargar">
                <v-icon class="btn-icon-data" size="large" color="#212529">mdi mdi-reload</v-icon>
                <span class="btn-txt ml-1">Recargar</span>
              </v-btn>
              <v-btn align="center" class="btn-log btn-datatable btn-reload"
                v-if="button_export && $can('download_template')" title="Plantilla"
                href="/support/template_csv/ENTRENAMIENTO_MANUAL.xlsx">
                <v-icon class="btn-icon-data" size="large" color="#212529">mdi mdi-file-excel</v-icon>
                <span class="btn-txt ml-1">PLANTILLA</span>
              </v-btn>
              <v-btn align="center" class="btn-log btn-datatable btn-reload"
                v-if="button_import && $can('import_template')" title="Importar" @click="$emit('click-import')">
                <v-icon class="btn-icon-data" size="large" color="#212529">mdi mdi-file-import</v-icon>
                <span class="btn-txt ml-1">IMPORTAR</span>
              </v-btn>

              <v-btn v-if="addBtnRole" prepend-icon="mdi mdi-plus-circle" class="btn-log btn-datatable"
                @click="$emit('showCreateDtRole', true)">
                Nuevo rol
              </v-btn>
            </div>
          </div>
        </div>
      </v-card-title>
      <slot name="header-opt"></slot>
      <v-data-table :search="search" v-model="itemsSelect" return-object loading-text="Cargando ..."
        :no-data-text="'Listado vacío'" :headers="headers" :items-per-page="itemsPerPage" :items="items"
        @current-items="getFiltered" :show-select="enableSelect" @update:modelValue="changeSelection" :height="height" :select-strategy="selectStrategy"
        :item-value="(item) => item" :group-by="groupBy" :value-comparator="(a, b) => a.id == b.id">
        <template v-for="(_, slot) of $slots" v-slot:[slot]="scope">
          <slot :name="slot" v-bind="scope" />
        </template>
        <template v-slot:headers v-if="hideHeaderTable"></template>
        <template v-slot:bottom v-if="hideFooter"></template>
      </v-data-table>
    </v-card>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, onBeforeUnmount, watch } from "vue";

const props = defineProps({
  title: { type: String, default: () => null },
  height: { type: String, default: () => null },
  button_add: { type: Boolean, default: () => false },
  view_default: { type: Boolean, default: () => false },
  button_reload: { type: Boolean, default: () => false },
  button_export: { type: Boolean, default: () => false },
  button_import: { type: Boolean, default: () => false },
  button_import_int: { type: Boolean, default: () => false },
  button_export_int: { type: Boolean, default: () => false },
  button_template_int: { type: Boolean, default: () => false },
  enableSelect: { type: Boolean, default: () => false },
  selectStrategy: { type: String, default: () => 'page' },
  headers: { type: Array, default: () => [] },
  items: { type: Array, default: () => [] },
  addBtnRole: { type: Boolean, default: () => false },
  elevation: { type: Number, default: () => 5 },
  showSearch: { type: Boolean, default: () => false },
  showHeader: { type: Boolean, default: () => true },
  hideFooter: { type: Boolean, default: () => false },
  hideHeaderTable: { type: Boolean, default: () => false },
  initSelection: { type: Array, default: () => [] },
  itemsPerPage: { type: Number, default: () => 10 },
  groupBy: { type: Array, default: () => [] },
  titleAdd: { type: String, default: () => null },
});

const emit = defineEmits(["click-add", "click-reload", "click-import", "click-import-intention", "click-export-intention", "showCreateDtRole", "changeSelection"]);

const headerprops = ref({
  "sort-icon": "mdi-menu-down"
});

let search = ref("");
let filteredItems = ref([]);
let itemsSelect = ref([]);
itemsSelect.value = props.initSelection;

const getFiltered = (e) => {
  filteredItems.value = null;
  filteredItems.value = e;
};

const clearSelection = () => {
  itemsSelect.value = [];
}

const changeSelection = (value) => {
  emit('changeSelection', value)
}

// Función para obtener un identificador único para cada instancia del v-data-table
const getUniqueIdentifier = () => {
  return `search_${props.title}`;
};

// Recuperar el estado del filtro al montar el componente
onMounted(() => {
  const uniqueIdentifier = getUniqueIdentifier();
  search.value = localStorage.getItem(uniqueIdentifier) || "";
});

watch(search, (newValue) => {
  const uniqueIdentifier = getUniqueIdentifier();
  localStorage.setItem(uniqueIdentifier, newValue);
});

defineExpose({
  clearSelection,
})
</script>

<style scoped>
.v-data-table :deep(tr:hover) {
  background: #eeeeee;
  cursor: pointer !important;
}

.v-data-table :deep(th) {
  border: none !important;
  vertical-align: middle !important;
  color: #a1a5b7 !important;
  font-size: 0.85rem;
  font-weight: 900 !important;
}

.v-data-table tr {
  border-bottom: 1px dashed #f3f3f3 !important;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
    "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji",
    "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji" !important;
}

.v-data-table td {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
    "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji",
    "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji" !important;
  font-size: 0.875rem;
  border-bottom: none !important;
}

.v-data-table {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
    "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji",
    "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji" !important;
  font-size: 0.875rem;
}

.title-datatable-section {
  display: flex;
  flex-direction: column;
  align-items: inherit;
  padding: 0 !important;
  width: 100%;
}

.title-datatable {
  border-bottom: 1px solid rgba(253, 253, 253, 0.2) !important;
  padding: 0.6rem 0.9rem !important;
  background-color: var(--primary-color) !important;
  display: flex;
  justify-content: space-between;
  width: 100%;
  flex-wrap: wrap !important;
}

.text-search {
  height: calc(1.5em + 0.75rem + 12px) !important;
  border-radius: 10px !important;
  border: 1px solid #f5f8fa !important;
  background-color: #f5f8fa !important;
  min-width: 230px !important;
}

.theme--light.v-text-field>.v-input__control>.v-input__slot:before {
  border: 0 !important;
}

.text-search .v-label {
  left: 15px !important;
  top: 2px !important;
}

.title-vuely {
  font-size: 22px !important;
  font-weight: 600 !important;
  color: #ffff !important;
}

.theme--light.v-data-table .v-data-table-header th.sortable.active .v-data-table-header__icon {
  color: #009ef7 !important;
}

th.sortable .v-icon:hover {
  color: #009ef7 !important;
}

.btn-log {
  background-color: #fff !important;
  color: #444 !important;
  border: 1px solid #f5f8fa !important;
  margin-left: 1em;
  text-decoration: none;
}

.showSearch {
  width: 300px;
}

.btn-datatable {
  height: 32px !important;
  min-width: 40px !important;

}

.principal_botones_table {
  justify-content: start !important;
}

.botones_table {
  display: flex;
  justify-content: start !important;
  align-items: center;
}

@media screen and (min-width: 1200px) {
  .btn-txt {
    display: flex;
  }
}


@media screen and (max-width: 1200px) and (min-width: 1075px) {
  .showSearch {
    width: 200px;
  }

  .btn-datatable {
    height: 30px !important;
    width: 40px !important;
  }

  .btn-icon-data {
    font-size: 18px;
  }

  .btn-txt {
    display: none;
  }
}


@media screen and (max-width: 1075px) and (min-width: 580px) {
  .showSearch {
    width: 139px;
  }

  .btn-datatable {
    height: 30px !important;
    width: 40px !important;
  }

  .btn-icon-data {
    font-size: 18px;
  }

  .btn-txt {
    display: none;
  }
}

@media screen and (max-width: 580px) {
  .showSearch {
    width: 180px;
  }

  .btn-datatable {
    height: 30px !important;
    width: 45px !important;
  }

  .btn-icon-data {
    font-size: 18px;
  }

  .btn-txt {
    display: none;
  }
}</style>
