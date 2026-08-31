<template>
  <div>
    <q-form @submit.prevent="submit">
      <div class="row q-gutter-y-sm items-center">
        <div class="col-6 q-px-xs">
          <q-input
            outlined
            dense
            type="text"
            label="Name"
            v-model="props.show.name"
            :disable="isReadOnly"
            required
          ></q-input>
        </div>
        <div class="col-6 q-px-xs">
          <q-input
            outlined
            dense
            type="text"
            label="Writer"
            v-model="props.show.writer"
            :disable="isReadOnly"
            required
          ></q-input>
        </div>

        <div class="col-6 q-px-xs">
          <q-input
            outlined
            dense
            type="text"
            label="Tagline"
            v-model="props.show.tagline"
            :disable="isReadOnly"
            required
          ></q-input>
        </div>
        <div class="col-6 q-px-xs">
          <q-input
            outlined
            dense
            type="text"
            label="Director"
            v-model="props.show.director"
            :disable="isReadOnly"
            required
          ></q-input>
        </div>

        <div class="col-6 q-px-xs">
          <q-input
            type="number"
            v-model.number="store.admin.show.ticket_price"
            label="Ticket Price"
            dense
            outlined
            :disable="isReadOnly"
          ></q-input>
        </div>
        <div class="col-6 q-px-xs text-right">
          <div class="q-my-md cursor-pointer">
            Ticket Sales Start:
            {{ format(parseISO(props.show.ticket_sales_start), "PP") }}
            <q-btn :icon="matCalendarMonth" flat round :disable="isReadOnly"></q-btn>
            <q-popup-edit
              v-if="!isReadOnly"
              v-model="props.show.ticket_sales_start"
              v-slot="scope"
              buttons
            >
              <q-date mask="YYYY-MM-DD" v-model="scope.value"></q-date>
            </q-popup-edit>
          </div>
        </div>
        <div class="col-12">
          <div class="text-caption">
            Info
          </div>
          <q-editor v-model="props.show.info" :disable="isReadOnly"></q-editor>
        </div>
        <div class="col-12 text-right">
          <q-btn
            type="submit"
            :label="btnLabel"
            color="positive"
            class="q-mr-md"
            :disable="isReadOnly"
          ></q-btn>
        </div>
      </div>
    </q-form>
  </div>
</template>

<script setup>
import { matCalendarMonth } from "@quasar/extras/material-icons";
import { format, parseISO } from "date-fns";
import getPermissionLevel from "src/assets/get-permission-level";
import { useStore } from "src/stores/store";
import { computed } from "vue";

const props = defineProps(["show"]);

const store = useStore();

const isReadOnly = computed(
  () => getPermissionLevel(store.admin.user, "shows") === "read-only",
);

const btnLabel = computed(() => {
  return store.router.currentRoute.value.params.id ? "udpate" : "create";
});

const cancel = () => {
  // pull show info from database again
  store.editShow(store.admin.show.id);
};

const submit = async () => {
  if (btnLabel.value == "create") {
    await store.createShow();
    return;
  }

  // update show in database
  await store.updateShow();
};
</script>
