<template>
  <div>
    <div v-if="currentImage" class="text-center">
      <img :src="currentImage" class="image-logo py-2" />
    </div>
    <v-file-input class="mt-3" show-size variant="outlined" dense label="Seleccione imagen" accept="image/*"
      @update:model-value="selectImage"></v-file-input>
    <div v-if="progress">
      <div>
        <v-progress-linear v-model="progress" color="light-blue" height="25" reactive>
          <strong>{{ progress }} %</strong>
        </v-progress-linear>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  value: { type: [Object, String] }
});

const emit = defineEmits([
  "upload-image",
]);

const currentImage = ref('')
const progress = ref(0)

currentImage.value = props.value

watch(() => props.value, async (newVal) => {
  currentImage.value = newVal
})

const selectImage = (image) => {
  if (!image || image.length == 0) {
    currentImage.value = undefined;
    emit("upload-image", null);
    return
  }
  image = image[0];

  let maxSizeInBytes = 5000000; // Tamaño máximo permitido en bytes (5 MB)
  if (image.size > maxSizeInBytes) {
    // Asignar el mensaje de error si la imagen excede el límite de tamaño
    currentImage.value = undefined;
    emit("upload-image", true);
    return;
  } else {
    progress.value = 0;
    let reader = new FileReader();
    reader.onloadend = () => {
      let base64String = reader.result;
      emit("upload-image", base64String);
    };
    reader.readAsDataURL(image);
  }
}
</script>
<style scoped>
.image-logo {
  max-width: 270px;
  max-height: 120px;
}</style>
