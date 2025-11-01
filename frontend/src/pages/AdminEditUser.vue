<template>
  <div>
    <q-btn @click="store.updateUser" label="Save"></q-btn>
    <div class="row">
      <div class="col">
        <pre>
      {{ store.admin.editUser }}
    </pre
        >
      </div>
      <div class="col">
        <pre>
      {{ store.admin.permissionLevels }}
    </pre
        >
      </div>
    </div>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";

const store = useStore();
</script>
