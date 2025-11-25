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
        <template v-for="item in alwaysVisibleLinks" :key="item.label">
          <q-btn
            :color="item.color"
            :icon="item.icon"
            :label="item.label"
            flat
            :to="item.url"
            v-if="!item.external"
          />

          <q-btn
            v-else
            :color="item.color"
            :icon="item.icon"
            :label="item.label"
            flat
            @click="openExternal(item.url)"
          ></q-btn>
        </template>
      </div>
    </q-banner>
  </div>
</template>

<script setup>
// Props or imports based on your original
import { useStore } from "src/stores/store";
import { computed } from "vue";
const store = useStore();

const auditionLink = computed(() => {
  const audition = {
    label: "Audition",
    url: "/audition",
    open: store.home.currentShow.audition,
  };
  return audition.open ? audition : null;
});

const classLink = computed(() => {
  const cls = {
    label: "Classes",
    url: "/classes",
    enrolling: store.courses.length > 0,
  };
  return cls.enrolling ? cls : null;
});

const alwaysVisibleLinks = [
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
    url:
      "https://actseats.us20.list-manage.com/subscribe?u=0022128dcf342c7a58eb81790&id=dff3d650cc",
    external: true,
    icon: "mail",
    color: "deep-orange",
  },
];

const displayActionButtons = computed(() => {
  return auditionLink.value || classLink.value || alwaysVisibleLinks.length;
});

const openExternal = (url) => {
  window.open(url);
};
</script>

<style scoped>
.q-banner {
  border-radius: 12px;
}
</style>
