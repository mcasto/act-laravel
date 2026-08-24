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
        placeholder="Search messages..."
        class="q-ml-auto"
        style="min-width: 240px"
        @update:model-value="page = 1"
      />
    </div>

    <div class="row q-mt-md">
      <div class="col-12 col-md-6" v-for="submission of paged">
        <admin-message-us-card :submission="submission"></admin-message-us-card>
      </div>
    </div>

    <div
      v-if="store.admin.message_us_submissions.length === 0"
      class="text-center q-mt-md text-grey"
    >
      No messages yet.
    </div>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";
import AdminMessageUsCard from "src/components/AdminMessageUsCard.vue";

const store = useStore();

const page = ref(1);
const perPage = 4;
const search = ref("");

const filtered = computed(() => {
  const q = search.value?.toLowerCase().trim();
  if (!q) return store.admin.message_us_submissions;
  return store.admin.message_us_submissions.filter((s) =>
    [s.email, s.body].some((f) => f?.toLowerCase().includes(q)),
  );
});

const max = computed(() => Math.ceil(filtered.value.length / perPage));

const paged = computed(() => {
  const start = (page.value - 1) * perPage;
  const end = start + perPage;
  return filtered.value.slice(start, end);
});
</script>
