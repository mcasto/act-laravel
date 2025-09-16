<template>
  <div>
    <!--  -->
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";

const store = useStore();

console.log({ volunteers: store.admin.volunteers });
</script>
