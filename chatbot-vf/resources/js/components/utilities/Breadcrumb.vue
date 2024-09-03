<template>
  <v-breadcrumbs :items="items_Breadcrumb">
    <template v-slot:divider>
      <v-icon class="custom-icon">mdi mdi-menu-right</v-icon>
    </template>
  </v-breadcrumbs>
</template>

<script setup>
const props = defineProps({
  items_Breadcrumb: { type: Array, default: () => [] },
});
</script>

<style scoped>
.v-breadcrumbs {
  font-size: 14px;
  text-transform: uppercase;
  color: var(--primary-color) !important;
  line-height: 0;
  padding: 0px 0 5px 0px !important;
}

.custom-icon {
  font-size: 30px;
  color: var(--primary-color) !important;
}

ul {
  padding: 0 0 0 0 !important;
  margin: 0 10px !important;
}
</style>
