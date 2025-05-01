<template>
  <q-dialog v-model="model.visible" full-width>
    <q-card>
      <q-form @submit.prevent="$emit('enroll')">
        <q-card-section>
          <div class="row">
            <div class="col-12 col-sm-6 q-pa-xs">
              <q-input
                type="text"
                label="First Name"
                v-model="model.first_name"
                stack-label
                dense
                outlined
                required
              ></q-input>
            </div>
            <div class="col-12 col-sm-6 q-pa-xs">
              <q-input
                type="text"
                label="Last Name"
                v-model="model.last_name"
                stack-label
                dense
                outlined
                required
              ></q-input>
            </div>
            <div class="col-12 col-sm-6 q-pa-xs">
              <q-input
                type="email"
                label="Email"
                v-model="model.email"
                stack-label
                dense
                outlined
                required
                :rules="[(v) => isValidEmail(v) || 'Invalid Email']"
              ></q-input>
            </div>
            <div class="col-12 col-sm-6 q-pa-xs">
              <q-input
                type="tel"
                label="Phone / Whatsapp"
                v-model="model.phone"
                stack-label
                dense
                outlined
                required
              ></q-input>
            </div>
            <div class="col-12 q-pa-xs">
              <div class="text-subtitle2">Specific Questions?</div>
              <q-editor v-model="model.questions"></q-editor>
            </div>
          </div>
        </q-card-section>
        <q-card-actions class="justify-end q-mr-md">
          <q-btn label="Cancel" color="negative" v-close-popup></q-btn>
          <q-btn label="Continue" color="positive" type="submit"></q-btn>
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { isValidEmail } from "@shelf/is-valid-email-address";

const model = defineModel();
const emits = defineEmits(["enroll"]);
</script>
