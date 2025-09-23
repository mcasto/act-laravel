<template>
  <div>
    <q-table
      :rows="filteredSkills"
      :pagination="{ rowsPerPage: 0 }"
      :hide-bottom="filteredSkills.length > 0"
      dense
    >
      <template #top>
        <q-toolbar class="justify-between items-center">
          <q-select
            type="text"
            label="Search Skills"
            v-model="skillFilter"
            dense
            outlined
            :options="skillList"
            multiple
            use-chips
            style="min-width: 20rem;"
            hint="This is an 'OR' search"
          >
            <template #prepend> <q-icon name="mdi-magnify"></q-icon> </template
          ></q-select>

          <q-btn
            icon="add"
            round
            color="primary"
            to="/admin/edit-volunteer/new"
          ></q-btn>
        </q-toolbar>
      </template>

      <template #header>
        <q-tr>
          <q-th class="text-left">
            Name
          </q-th>
          <q-th class="text-left">
            Phone
          </q-th>
          <q-th class="text-left">
            Email
          </q-th>
          <q-th class="text-center">
            Skills
          </q-th>
          <q-th class="text-center">
            Experience
          </q-th>
        </q-tr>
      </template>
      <template #body="{row}">
        <q-tr :class="`bg-${row.active ? 'green-2' : 'red-2'}`">
          <q-td>
            {{ row.name }}
          </q-td>
          <q-td>
            {{ row.phone }}
          </q-td>
          <q-td>
            <a :href="`mailto:${row.email}`">{{ row.email }}</a>
          </q-td>
          <q-td class="text-center">
            <q-btn icon="mdi-tools" flat round></q-btn>
            <q-menu>
              <q-list dense separator>
                <q-item
                  v-for="vs of row.volunteer_skills"
                  :key="`skill-${vs.id}`"
                >
                  <q-item-section>
                    <q-item-label>
                      {{ vs.skill.name }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-menu>
          </q-td>
          <q-td class="text-center">
            <q-btn
              icon="mdi-drama-masks"
              flat
              round
              @click="
                showDialog = { visible: true, experience: row.experience }
              "
            ></q-btn>
          </q-td>
          <q-td>
            <q-btn icon="delete" flat round color="negative"></q-btn>
            <q-btn
              icon="edit"
              flat
              round
              :to="`/admin/edit-volunteer/${row.id}`"
            ></q-btn>
          </q-td>
        </q-tr>
      </template>
    </q-table>
    <volunteer-experience
      v-model="showDialog.visible"
      :experience="showDialog.experience"
    ></volunteer-experience>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import VolunteerExperience from "src/components/VolunteerExperience.vue";
import { computed, ref } from "vue";
import { intersection } from "lodash-es";

const store = useStore();

const showDialog = ref({
  visible: false,
  experience: null,
});

const skillFilter = ref([]);

const skillList = store.skills.map((skill) => skill.name);

const filteredSkills = computed(() => {
  if (skillFilter.value.length == 0) {
    return store.admin.volunteers;
  }

  return store.admin.volunteers.filter(({ volunteer_skills }) => {
    const skills = volunteer_skills.map((item) => item.skill.name);
    const hasSkill = intersection(skills, skillFilter.value).length > 0;

    return hasSkill;
  });
});
</script>
