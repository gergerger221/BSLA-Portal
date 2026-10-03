<template>
  <div class="p-8 text-center text-slate-500 text-sm">
    Redirecting to Registrar Portal...
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
onMounted(() => {
  router.replace('/registrar');
});
</script>
