<template>
  <div class="row">
    <div class="col-12 col-md-6">
      <div class="text-center">
        <div class="text-h6">
          Audition Notice
        </div>
        <div>
          {{ show.name }}
        </div>
        <div>by {{ show.writer }}</div>
        <div>Directed by {{ show.director }}</div>
        <q-img
          :src="`/api/storage/images/${show.poster}`"
          style="max-width: 35vw;"
        ></q-img>
      </div>
    </div>
    <div class="col-12 col-md-6 q-pa-sm column q-gutter-y-sm">
      <audition-roles :roles="audition.roles"></audition-roles>
      <audition-sessions :sessions="audition.sessions"></audition-sessions>
    </div>
  </div>

  <div class="q-px-xl">
    <audition-form :roles="audition.roles" :show="show"></audition-form>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import { computed, onBeforeMount, onMounted, ref } from "vue";
import AuditionForm from "src/components/AuditionForm.vue";
import AuditionRoles from "src/components/AuditionRoles.vue";
import AuditionSessions from "src/components/AuditionSessions.vue";

const store = useStore();

const audition = computed(() => {
  return store.audition;
});

const show = computed(() => {
  return audition.value.show;
});
</script>
