<template>
  <div class="q-mt-md">
    <div class="text-h6">
      CONTACT DIRECTOR TO RESERVE YOUR AUDITION TIME SLOT
    </div>
    <div class="text-subtitle1">
      <span class="text-bold">Director: </span>{{ show.director }}
    </div>

    <q-form @submit.prevent="auditionContact">
      <div class="row q-gutter-y-sm">
        <div class="col-12 col-md-6 q-px-sm">
          <q-input
            type="text"
            label="Name"
            stack-label
            dense
            outlined
            v-model="contact.name"
            required
          ></q-input>
        </div>
        <div class="col-12 col-md-6 q-px-sm">
          <q-select
            label="Role"
            stack-label
            dense
            outlined
            v-model="contact.role"
            :options="roles"
            option-label="name"
            error-message="Required"
            :error="roleError"
            @update:model-value="roleError = false"
          ></q-select>
        </div>
        <div class="col-12 col-md-6 q-px-sm">
          <q-input
            type="email"
            label="Email"
            stack-label
            dense
            outlined
            v-model="contact.email"
            required
            :rules="[(v) => isValidEmail(v) || 'Valid email required']"
          ></q-input>
        </div>
        <div class="col-12 col-md-6 q-px-sm">
          <q-input
            type="tel"
            label="Phone"
            stack-label
            dense
            outlined
            v-model="contact.phone"
            required
          ></q-input>
        </div>

        <div class="col-3 offset-9 q-pr-sm text-right">
          <q-btn
            type="submit"
            label="Contact Director"
            color="positive"
          ></q-btn>
        </div>
      </div>
    </q-form>
  </div>
</template>

<script setup>
import { isValidEmail } from "@shelf/is-valid-email-address";
import { cloneDeep } from "lodash-es";
import { Loading, Notify } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { ref } from "vue";

const props = defineProps(["roles", "show"]);

const store = useStore();

const contact = ref({
  name: null,
  email: null,
  phone: null,
  role: null,
});

const roleError = ref(false);

const auditionContact = async () => {
  Loading.show();

  if (!!!contact.value.role) {
    roleError.value = true;
    return;
  }

  const payload = cloneDeep(contact.value);

  const response = await callApi({
    path: "/audition-contact",
    payload,
    method: "post",
  });

  Notify.create({
    type: "positive",
    message: "Director contacted and will reach out to you soon",
  });

  Loading.hide();
};
</script>
