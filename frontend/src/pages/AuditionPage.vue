<template>
  <div v-if="audition">
    <div class="row">
      <div class="col-12 col-md-6">
        <div class="text-center">
          <div class="text-h6">
            Audition Notice
          </div>
          <div>
            {{ show.name }}
          </div>
          <div>by {{ show.writer }}</div>
          <div>Directed by {{ show.director }}</div>
          <q-img
            :src="`/api/storage/images/${show.poster}`"
            style="max-width: 35vw;"
          ></q-img>
        </div>
      </div>
      <div class="col-12 col-md-6 q-pa-sm column q-gutter-y-sm">
        <audition-roles :roles="audition.roles"></audition-roles>
        <audition-sessions :sessions="audition.sessions"></audition-sessions>
      </div>
    </div>

    <div class="q-px-xl">
      <audition-form :roles="audition.roles" :show="show"></audition-form>
    </div>
  </div>

  <div v-else class="q-pa-xl">
    <div class="text-h4">Alas, there are no current auditions.</div>
    <div class="text-h5 q-mb-md">But we always need help!</div>
    <p>
      While the stage is quiet, the buzz backstage never stops! The magic of our
      shows relies on a huge team of dedicated volunteers. If you have a passion
      for theater—whether with a hammer, a sewing needle, a paintbrush, or a
      smile—we have a place for you.
    </p>

    <div class="volunteer-teaser">
      <p><strong>We're always looking for volunteers!</strong></p>
      <ul>
        <li>
          <strong>Build & Create:</strong> Set construction, painting, costumes,
          and props.
        </li>
        <li>
          <strong>Run the Show:</strong> Sound, lights, stage management, and
          photography.
        </li>
        <li>
          <strong>Welcome the Audience:</strong> Tickets, ushering, and
          hospitality.
        </li>
        <li>
          <strong>Lead & Support:</strong> Board members, fundraising, and
          marketing.
        </li>
      </ul>
    </div>

    <p>
      No experience is necessary, just enthusiasm. We'll gladly teach you the
      ropes no matter where you decide to help. And if you have experience, you
      can teach us! Find your role behind the curtain and become a vital part of
      our theater family.
    </p>

    <div class="flex justify-center">
      <q-btn
        label="Join our volunteer mailing list today!"
        color="primary"
        to="/volunteer"
      ></q-btn>
    </div>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import { computed, onBeforeMount, onMounted, ref } from "vue";
import AuditionForm from "src/components/AuditionForm.vue";
import AuditionRoles from "src/components/AuditionRoles.vue";
import AuditionSessions from "src/components/AuditionSessions.vue";

const store = useStore();

const audition = computed(() => {
  if (!store.audition?.roles) {
    return false;
  }
  return store.audition;
});

const show = computed(() => {
  return audition.value.show;
});
</script>
