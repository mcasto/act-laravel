<template>
  <div>
    <q-list separator dense>
      <q-item
        v-for="route of routes"
        :key="route.path"
        :to="route.path"
        exact
        active-class="text-white bg-blue-grey-6"
      >
        <q-item-section>
          <q-item-label>
            {{ route.name }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </q-list>
  </div>
</template>

<script setup>
const props = defineProps(["routes"]);
</script>
