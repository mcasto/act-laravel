<template>
  <div class="q-ma-md">
    <div class="text-subtitle2">
      Fill out this form to contact our volunteer coordinator.
    </div>
    <q-form @submit.prevent="volunteerContact">
      <q-card bordered flat>
        <div class="q-pa-md">
          <div class="row q-gutter-y-xs">
            <q-input
              type="text"
              label="Name"
              v-model="volunteerForm.name"
              stack-label
              dense
              outlined
              class="col-12 col-sm-4 q-px-sm"
              required
            ></q-input
            ><q-input
              type="email"
              label="Email"
              v-model="volunteerForm.email"
              stack-label
              dense
              outlined
              class="col-12 col-sm-4 q-px-sm"
              required
              :rules="[(v) => isValidEmail(v) || 'Invalid Email']"
            ></q-input
            ><q-input
              type="tel"
              label="Phone / Whatsapp"
              v-model="volunteerForm.phone"
              stack-label
              dense
              outlined
              class="col-12 col-sm-4 q-px-sm"
              required
            ></q-input>

            <div class="text-subtitle2 q-mt-lg">
              What interest, experience, or skills do you want to bring to ACT
              presentation, or what would you like to learn<br />(select all
              that interest you or that you have previous experience with)
            </div>

            <div
              v-for="skill of store.skills"
              :key="`skill-${skill.id}`"
              class="col-6"
            >
              <q-checkbox
                v-model="volunteerForm.skills"
                :val="skill.id"
                :label="skill.name"
                dense
              ></q-checkbox>
            </div>
          </div>
          <div class="col-12 q-mt-xl">
            <div class="text-subtitle2">
              Previous theater experience?
            </div>
            <q-editor v-model="volunteerForm.experience"> </q-editor>
          </div>
        </div>

        <q-separator></q-separator>
        <q-card-actions class="justify-end">
          <q-btn
            color="negative"
            @click="
              volunteerForm = {
                name: null,
                date: null,
                email: null,
                phone: null,
                skills: [],
              }
            "
            >Clear</q-btn
          >
          <q-btn type="submit" color="positive">Submit</q-btn>
        </q-card-actions>
      </q-card>
    </q-form>
  </div>
</template>

<script setup>
import { cloneDeep } from "lodash-es";
import { Loading, Notify } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { ref } from "vue";
import { isValidEmail } from "@shelf/is-valid-email-address";

const store = useStore();

const volunteerForm = ref({
  name: null,
  email: null,
  phone: null,
  experience: "",
  skills: [],
});

const volunteerContact = async () => {
  Loading.show();

  const payload = cloneDeep(volunteerForm.value);

  const response = await callApi({
    path: "/volunteer-contact",
    method: "post",
    payload,
  });

  Notify.create({
    type: "positive",
    message: "Volunteer Coordinator contacted and will reach out to you soon",
  });

  Loading.hide();
};
</script>
