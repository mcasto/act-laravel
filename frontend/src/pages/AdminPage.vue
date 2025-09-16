<template>
  <div>
    <q-splitter :model-value="20">
      <template #before>
        <admin-nav></admin-nav>
      </template>
      <template #after>
        <div class="q-pa-md">
          <router-view />
        </div>
      </template>
    </q-splitter>
  </div>
</template>

<script setup>
import AdminNav from "src/components/AdminNav.vue";
</script>
