<template>
  <div>
    <q-toolbar>
      <q-toolbar-title>
        Comp Tickets for {{ store.admin.show.name }}
      </q-toolbar-title>
    </q-toolbar>

    <q-separator></q-separator>

    <div class="row">
      <div class="col-6 q-mt-sm">
        <q-card flat>
          <q-form
            ref="formRef"
            @submit.prevent="onSubmit"
            class="column q-gutter-y-sm"
          >
            <q-input
              dense
              outlined
              stack-label
              type="text"
              label="Name"
              :rules="[(val) => !!val || 'Name is required']"
              v-model="form.name"
              @blur="form.pickup_name = form.name"
            ></q-input>

            <q-input
              dense
              outlined
              stack-label
              type="email"
              label="Email"
              :rules="[(val) => !!val || 'Email is required']"
              v-model="form.email"
            ></q-input>

            <q-card-actions class="justify-end">
              <q-btn
                type="submit"
                label="Send"
                color="primary"
                :loading="loading"
              ></q-btn>
            </q-card-actions>
          </q-form>
        </q-card>
      </div>
      <div class="col q-ml-sm q-mt-sm">
        <q-table
          :columns="columns"
          :rows="store.admin.compList"
          :pagination="{ rowsPerPage: 0 }"
          :hide-bottom="store.admin.compList.length > 0"
        ></q-table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { format, parseISO } from "date-fns";
import { Notify } from "quasar";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";

const store = useStore();

const loading = ref(false);

const columns = [
  {
    label: "Name",
    name: "name",
    field: "name",
  },
  {
    label: "Sent",
    name: "sent_at",
    field: (row) => format(parseISO(row.sent_at), "PP"),
  },
  {
    label: "Redeemed",
    name: "redeemed_at",
    field: (row) =>
      row.redeemed_at ? format(parseISO(row.redeemed_at), "PP") : "N/A",
  },
];

const form = ref({
  name: null,
  email: null,
});

const formRef = ref(null);

const performances = computed(() => {
  return store.admin.show.performances.map((performance) => {
    return {
      label: `${performance.formatted_date} ${performance.formatted_time}`,
      value: performance.id,
    };
  });
});

const onSubmit = async () => {
  loading.value = true;
  store.admin.comp = form.value;
  const response = await store.saveCompConfig();
  loading.value = false;

  if (response.status === "success") {
    Notify.create({
      color: "positive",
      message: "Comp Ticket Notification Sent",
    });

    form.value = { name: null, email: null };
    formRef.value.reset();

    store.admin.compList = response.list;
  }
};
</script>
