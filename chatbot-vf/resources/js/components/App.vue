<template>
  <div class="div-container">
    <router-view />
  </div>
</template>

<script setup>
import { useGlobalStore } from './store/global'


const props = defineProps({
  color: { type: String, default: () => ''},
  roleUser: { type: String, default: () => null},
});

const global = useGlobalStore();
global.changeColor(props.color);
global.setRoleUser(props.roleUser);

let idCustomer = localStorage.getItem("id_city");
if(idCustomer) global.setIdCustomer(idCustomer);

</script>
<style>
.div-container{
  padding-bottom: 40px;
}
</style>
