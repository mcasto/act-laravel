<template>
  <div>
    <div class="flex items-center justify-between">
      <q-pagination
        v-model="page"
        color="black"
        :max="max"
        :max-pages="6"
        v-if="max > 1"
      />
      <q-input
        v-model="search"
        dense
        outlined
        clearable
        placeholder="Search contacts..."
        class="q-ml-auto"
        style="min-width: 240px"
        @update:model-value="page = 1"
      />
    </div>

    <div class="row q-mt-md">
      <div class="col-12 col-md-6" v-for="contact of paged">
        <admin-contact-card :contact="contact"></admin-contact-card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { format, parseISO } from "date-fns";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";
import AdminContactMessageDialog from "src/components/AdminContactMessageDialog.vue";
import AdminContactCard from "src/components/AdminContactCard.vue";

const store = useStore();

const showDialog = ref({
  visible: false,
  message: "",
});

const page = ref(1);
const perPage = 4;
const search = ref("");

const filtered = computed(() => {
  const q = search.value?.toLowerCase().trim();
  if (!q) return store.admin.contacts;
  return store.admin.contacts.filter((c) =>
    [c.name, c.email, c.phone, c.subject].some((f) =>
      f?.toLowerCase().includes(q)
    )
  );
});

const max = computed(() => Math.ceil(filtered.value.length / perPage));

const paged = computed(() => {
  const start = (page.value - 1) * perPage;
  const end = start + perPage;
  return filtered.value.slice(start, end);
});
</script>
