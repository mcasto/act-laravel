<template>
  <q-dialog v-model="model.visible">
    <q-card>
      <q-toolbar class="bg-primary text-white">
        <q-toolbar-title>
          New User
        </q-toolbar-title>
      </q-toolbar>
      <q-card-section>
        <q-form @submit.prevent="$emit('create', model)">
          <div class="column q-gutter-y-sm">
            <q-input
              type="text"
              label="Name"
              v-model="model.user.name"
              dense
              outlined
              autocomplete="username"
              required
            ></q-input>
            <q-input
              type="email"
              label="Email"
              v-model="model.user.email"
              dense
              outlined
              autocomplete="username"
              required
            ></q-input>
            <q-input
              :type="showPass ? 'text' : 'password'"
              label="Password"
              autocomplete="new-password"
              v-model="model.user.password"
              dense
              outlined
              required
            >
              <template #append>
                <q-btn
                  flat
                  round
                  :icon="showPass ? 'visibility_off' : 'visibility'"
                  @click="showPass = !showPass"
                ></q-btn>
              </template>
            </q-input>
            <q-input
              :type="showPass ? 'text' : 'password'"
              label="Confirm Password"
              autocomplete="new-password"
              v-model="confirmPassword"
              dense
              outlined
              :rules="[rules.confirmPassword]"
            >
            </q-input>
          </div>
          <div class="flex justify-between">
            <q-btn
              type="button"
              label="Cancel"
              color="negative"
              @click="model.visible = false"
            ></q-btn>
            <q-btn type="submit" label="Create" color="positive"></q-btn>
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref } from "vue";

const model = defineModel();
const emits = defineEmits(["create"]);

const confirmPassword = ref(null);
const showPass = ref(false);

const rules = {
  confirmPassword: (v) =>
    v == model.value.user.password || "Must match password",
};
</script>
