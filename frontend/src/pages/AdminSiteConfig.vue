<template>
  <div class="q-mt-md">
    <q-form @submit.prevent="store.updateSiteConfig">
      <div class="row q-gutter-y-md">
        <div class="col-6 q-px-md">
          <q-input
            type="number"
            v-model.number="store.config.ticket_price"
            label="Default Ticket Price"
            dense
            outlined
          ></q-input>
        </div>
        <div class="col-6 q-px-md">
          <q-input
            type="number"
            v-model.number="store.config.sold_out_target"
            label="Default Sold Out Target"
            dense
            outlined
          ></q-input>
        </div>
        <div class="col-6 q-px-md">
          <q-input
            type="string"
            v-model.number="store.config.ticket_email"
            label="Email for Ticket Orders"
            dense
            outlined
          ></q-input>
        </div>
        <div class="col-6 q-px-md">
          <q-input
            type="string"
            v-model.number="store.config.ticket_email"
            label="Email for Contacts"
            dense
            outlined
          ></q-input>
        </div>
      </div>

      <fieldset class="q-ma-md">
        <legend>Payment Options</legend>
        <div class="text-h6" v-for="option of store.buttons" :key="option.key">
          {{ option.label }}
          <q-editor v-model="option.popupText"></q-editor>
        </div>
      </fieldset>

      <div class="flex justify-end q-mt-md q-mr-md">
        <q-btn type="submit" label="Update" color="primary"></q-btn>
      </div>
    </q-form>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import { ref } from "vue";

const store = useStore();
</script>
