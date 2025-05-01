<template>
  <div v-if="displayActionButtons" class="q-mt-xl">
    <q-banner class="bg-grey-2 text-primary q-pa-md rounded-borders" dense>
      <div class="text-h6 text-center q-mb-md">
        Get Involved
      </div>

      <div class="row items-center justify-center q-gutter-md">
        <q-btn
          v-if="auditionLink"
          color="primary"
          icon="theater_comedy"
          :label="auditionLink.label"
          flat
          :to="auditionLink.url"
        />
        <q-btn
          v-if="classLink"
          color="primary"
          icon="school"
          :label="classLink.label"
          flat
          :to="classLink.url"
        />
        <q-btn
          v-for="item in alwaysVisibleLinks"
          :key="item.label"
          :color="item.color"
          :icon="item.icon"
          :label="item.label"
          flat
          :to="item.url"
        />
      </div>
    </q-banner>
  </div>
</template>

<script setup>
// Props or imports based on your original
import { computed } from "vue";

const auditionLink = computed(() => {
  // logic from your original code
  const audition = {
    label: "Audition",
    url: "/audition",
    open: true, // Replace this with your real logic
  };
  return audition.open ? audition : null;
});

const classLink = computed(() => {
  // logic from your original code
  const cls = {
    label: "Classes",
    url: "/classes",
    enrolling: true, // Replace this with your real logic
  };
  return cls.enrolling ? cls : null;
});

const alwaysVisibleLinks = [
  {
    label: "Donate",
    url: "/donate",
    icon: "favorite",
    color: "positive",
  },
  {
    label: "Volunteer",
    url: "/volunteer",
    icon: "volunteer_activism",
    color: "accent",
  },
  {
    label: "Find Us",
    url: "/find-us",
    icon: "map",
    color: "secondary",
  },
  {
    label: "Join Mailing List",
    url: "/join-mailing-list",
    icon: "mail",
    color: "deep-orange",
  },
];

const displayActionButtons = computed(() => {
  return auditionLink.value || classLink.value || alwaysVisibleLinks.length;
});
</script>

<style scoped>
.q-banner {
  border-radius: 12px;
}
</style>
