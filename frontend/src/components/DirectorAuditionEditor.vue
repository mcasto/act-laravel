<template>
  <!-- Edit form for the show's audition notice on the director page. The
       same record the admin's Auditions page edits; saved through
       PUT /api/director/shows/{show}/auditions (HTML is cleaned server-side). -->
  <div>
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-sm-6">
        <date-field v-model="draft.display_date" label="Show on the website from" />
      </div>
      <div class="col-12 col-sm-6">
        <date-field v-model="draft.end_display_date" label="Until" />
      </div>
    </div>

    <q-card flat bordered class="q-mb-md bg-blue-grey-1">
      <q-card-section class="q-pb-sm">
        <div class="text-subtitle2 q-mb-xs">
          <q-icon :name="matAutoAwesome" class="q-mr-xs" />Have your audition info in a Word document?
        </div>
        <ol class="q-pl-md q-my-none text-body2">
          <li>Click <b>Copy AI prompt</b> (it already includes this show's poster).</li>
          <li>In ChatGPT, Claude or similar, paste the prompt and attach your .docx.</li>
          <li>Copy the HTML it gives you, click the <b>&lt; &gt;</b> button in the editor below, and paste it in.</li>
          <li>Click the <b>&lt; &gt;</b> button again to preview it, then <b>Save</b>.</li>
        </ol>
      </q-card-section>
      <q-card-actions>
        <q-btn
          :icon="matContentCopy"
          label="Copy AI prompt"
          color="secondary"
          unelevated
          no-caps
          @click="copyPrompt"
        />
      </q-card-actions>
    </q-card>

    <q-editor
      v-model="draft.html"
      :toolbar="auditionToolbar"
      :fonts="auditionFonts"
      min-height="12rem"
      max-height="60vh"
    />

    <q-banner v-if="error" dense rounded class="bg-red-1 text-negative q-mt-md">{{ error }}</q-banner>

    <div class="row justify-end q-gutter-sm q-mt-md">
      <q-btn flat no-caps label="Cancel" :disable="saving" @click="emit('cancel')" />
      <q-btn
        color="secondary"
        unelevated
        no-caps
        label="Save"
        :loading="saving"
        :disable="offline"
        @click="save"
      >
        <q-tooltip v-if="offline">You're offline — reconnect to save</q-tooltip>
      </q-btn>
    </div>
  </div>
</template>

<script setup>
import { matAutoAwesome, matContentCopy } from "@quasar/extras/material-icons";
import { Notify } from "quasar";
import { computed, ref } from "vue";
import DateField from "src/components/DateField.vue";
import { auditionFonts, auditionToolbar, buildAuditionPrompt, posterFor } from "src/assets/audition-editor";
import { updateAuditions } from "src/director/api";
import { localToday } from "src/director/format";
import { offline } from "src/director/state";

const props = defineProps({
  show: { type: Object, required: true }, // { id, name, poster_url, first_performance }
  audition: { type: Object, default: null }, // { display_date, end_display_date, html } | null
});
const emit = defineEmits(["saved", "cancel"]);

// A new notice defaults to showing from today until the first performance.
const draft = ref({
  display_date: props.audition?.display_date ?? localToday(),
  end_display_date: props.audition?.end_display_date ?? props.show.first_performance ?? localToday(),
  html: props.audition?.html ?? "",
});
const saving = ref(false);
const error = ref("");

const poster = computed(() => posterFor(props.show.poster_url));

const copyPrompt = async () => {
  try {
    await navigator.clipboard.writeText(
      buildAuditionPrompt({
        showName: props.show.name,
        posterPath: poster.value.path,
        posterUrl: poster.value.url,
      }),
    );
    Notify.create({ type: "positive", message: "AI prompt copied — paste it into your AI chat with your .docx" });
  } catch {
    Notify.create({ type: "negative", message: "Couldn't copy to the clipboard in this browser." });
  }
};

const save = async () => {
  saving.value = true;
  error.value = "";
  try {
    const result = await updateAuditions(props.show.id, draft.value);
    Notify.create({ type: "positive", message: "Audition notice saved" });
    emit("saved", result);
  } catch (e) {
    const fieldErrors = e.body?.errors ? Object.values(e.body.errors).flat() : [];
    error.value = fieldErrors.length ? fieldErrors.join(" ") : e.message;
  } finally {
    saving.value = false;
  }
};
</script>
