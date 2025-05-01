<template>
  <div>
    <div class="text-h6 q-pl-sm">
      Dashboard
    </div>
    <div class="text-subtitle1 q-ml-xl">
      <div class="text-bold">
        If current show has active ticket sales:
      </div>
      <div class="q-ml-xl">
        Summary of ticket sales for current show
      </div>
      <div class="text-bold">
        Else:
      </div>
      <div class="q-ml-xl">
        Default text
      </div>
    </div>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";

const store = useStore();
</script>
