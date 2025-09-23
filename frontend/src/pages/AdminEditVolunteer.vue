<template>
  <div>
    <q-form @submit.prevent="saveVolunteer">
      <div class="row q-gutter-sm justify-center">
        <q-checkbox
          v-model="volunteer.active"
          label="Active"
          :true-value="1"
          :false-value="0"
          class="col-1"
        ></q-checkbox>
        <q-input
          type="text"
          label="Name"
          v-model="volunteer.name"
          dense
          outlined
          required
          class="col-4"
        ></q-input>

        <q-input
          type="text"
          label="Phone"
          v-model="volunteer.phone"
          dense
          outlined
          required
        ></q-input>

        <q-input
          type="email"
          label="Email"
          v-model="volunteer.email"
          dense
          outlined
          required
          class="col"
        ></q-input>

        <q-card flat bordered class="col-6">
          <q-card-section>
            <q-toolbar>
              <q-toolbar-title>
                Experience
              </q-toolbar-title>
            </q-toolbar>

            <q-editor v-model="volunteer.experience"></q-editor>
          </q-card-section>
        </q-card>

        <q-card flat bordered class="col-5">
          <q-card-section>
            <q-toolbar>
              <q-toolbar-title>
                Skills
              </q-toolbar-title>
            </q-toolbar>

            <div class="row">
              <div
                v-for="skill of store.skills"
                :key="`skill-${skill.id}`"
                class="col-6"
              >
                <q-checkbox
                  v-model="skills"
                  :val="skill.id"
                  :label="skill.name"
                  dense
                ></q-checkbox>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="flex justify-between q-mt-sm">
        <q-btn color="negative" label="Cancel" to="/admin/volunteers"></q-btn>
        <q-btn color="positive" label="Save" type="submit"></q-btn>
      </div>
    </q-form>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import { ref } from "vue";
import { useRoute } from "vue-router";

const route = useRoute();
const store = useStore();

const volunteer = ref(
  store.admin.volunteers.find(({ id }) => id == route.params.id) || {
    active: 0,
    email: null,
    experience: "",
    name: null,
    phone: null,
    volunteer_skills: [],
  }
);

const skills = ref(volunteer.value.volunteer_skills.map(({ id }) => id));

console.log({ volunteer: volunteer.value });

const saveVolunteer = async () => {
  // mc-todo: update volunteer.value.volunteer_skills with skills
  // mc-todo: post/put (based on volunteer.value.id) to update database

  console.log({ save: volunteer.value, skills: skills.value });
};
</script>
