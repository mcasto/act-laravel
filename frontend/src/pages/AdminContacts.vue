<template>
  <div>
    <q-pagination
      v-model="page"
      color="black"
      :max="max"
      :max-pages="6"
      :boundary-numbers="false"
      v-if="max > 1"
    />

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

const max = Math.ceil(store.admin.contacts.length / perPage);

const paged = computed(() => {
  const start = (page.value - 1) * perPage;
  const end = start + perPage;

  return [...store.admin.contacts].slice(start, end);
});
</script>
