<template>
  <div v-if="preview && loading" class="q-pa-xl text-center">
    <q-spinner size="2em" />
  </div>

  <div v-else-if="audition?.id">
    <q-banner v-if="preview" dense class="bg-warning text-black text-center">
      Preview — this audition page isn't public yet
      <template v-if="audition.display_date">
        (scheduled to show {{ audition.display_date }} to {{ audition.end_display_date }}).
      </template>
    </q-banner>
    <div v-html="audition.html"></div>
  </div>

  <div v-else-if="preview" class="q-pa-xl">
    <div class="text-h5">No audition preview is turned on.</div>
    <p class="q-mt-md">
      Turn on <b>Preview</b> in the admin Auditions page and save to see it here.
    </p>
  </div>

  <div v-else class="q-pa-xl">
    <div class="text-h4">There are no auditions open right now.</div>
    <div class="text-h5 q-mb-md">
      Check back soon — and in the meantime, we can always use a hand!
    </div>
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
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { computed, onBeforeMount, ref } from "vue";

const props = defineProps({ preview: { type: Boolean, default: false } });

const store = useStore();

// The API sends {} (not null) when there's no audition, so templates test
// audition?.id rather than truthiness.
// In preview mode the page fetches its own audition (ignoring display dates)
// rather than using store.audition, which drives the public nav item.
const previewAudition = ref(null);
const loading = ref(props.preview);

onBeforeMount(async () => {
  if (!props.preview) return;
  try {
    previewAudition.value = await callApi({ path: "/audition-preview", method: "get" });
  } finally {
    loading.value = false;
  }
});

const audition = computed(() => {
  return props.preview ? previewAudition.value : store.audition;
});
</script>
