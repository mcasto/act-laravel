<template>
  <div v-if="audition">
    <div v-html="audition.html"></div>
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

const store = useStore();

console.log({ audition: store.audition });

const audition = computed(() => {
  return store.home.currentShow.audition;
});

const show = computed(() => {
  return audition.value.show;
});
</script>
