<template>
  <div>
    <div>
      <q-card bordered class="q-mx-md">
        <q-form @submit.prevent="onSubmit">
          <q-card-section class="q-gutter-y-sm">
            <q-input
              type="email"
              label="Email"
              stack-label
              dense
              outlined
              v-model="form.email"
              :rules="(v) => !!v || 'Required'"
            ></q-input>
            <div class="text-subtitle2">Your Message</div>
            <q-editor v-model="form.body"></q-editor>
          </q-card-section>

          <q-card-actions class="flex justify-end">
            <q-btn type="submit" label="Send" color="primary"></q-btn>
          </q-card-actions>
        </q-form>
      </q-card>
    </div>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import { ref } from "vue";

const store = useStore();

const form = ref({
  email: store.patron?.email || null,
  body: "",
});

const onSubmit = async () => {
  await store.newContact({
    name: form.value.email,
    email: form.value.email,
    subject: "Message Us - Other Questions",
    body: form.value.body,
  });

  form.value.body = "";
};
</script>
