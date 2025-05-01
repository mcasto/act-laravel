<template>
  <div>
    <q-form
      @submit.prevent="store.newContact({ name, email, phone, subject, body })"
      ref="formRef"
    >
      <div class="row">
        <div class="col-12 col-md-6 column q-gutter-y-sm q-pa-sm">
          <q-input
            type="text"
            label="Name"
            dense
            outlined
            required
            v-model="name"
            name="name"
          ></q-input>
          <q-input
            type="email"
            label="Email"
            dense
            outlined
            required
            v-model="email"
            name="email"
          ></q-input>
          <q-input
            type="tel"
            label="Phone"
            dense
            outlined
            required
            v-model="phone"
            name="phone"
          ></q-input>
          <q-input
            type="text"
            label="Subject"
            dense
            outlined
            v-model="subject"
            name="subject"
          ></q-input>
        </div>
        <div class="col-12 col-md-6 q-pa-sm">
          <q-input
            type="textarea"
            label="Message"
            dense
            outlined
            v-model="body"
            name="body"
            rows="9"
          ></q-input>
        </div>
      </div>

      <div class="flex justify-end q-px-sm">
        <q-btn label="Send" color="positive" type="submit"></q-btn>
      </div>
    </q-form>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import { ref } from "vue";

const store = useStore();
("");

const name = ref(null);
const email = ref(null);
const phone = ref(null);
const subject = ref(null);
const body = ref("");

const formRef = ref(null);
</script>
