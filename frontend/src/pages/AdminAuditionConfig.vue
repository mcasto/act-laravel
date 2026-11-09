<template>
  <div>
    <q-form @submit.prevent="store.saveAuditionConfig" ref="formRef">
      <div class="row">
        <div class="col-12 text-center text-h6">
          Audition Config for {{ store.admin.show.name }}
        </div>

        <div class="col-12 col-md-6 q-pa-md">
          <q-input
            dense
            outlined
            type="date"
            v-model="store.admin.audition.display_date"
            label="Start Display"
            :disable="isReadOnly"
          ></q-input>
        </div>
        <div class="col-12 col-md-6 q-pa-md">
          <q-input
            dense
            outlined
            type="date"
            v-model="store.admin.audition.end_display_date"
            label="End Display"
            :disable="isReadOnly"
          ></q-input>
        </div>

        <div class="col-12 q-px-md row items-center q-gutter-x-md">
          <q-toggle
            v-model="store.admin.audition.preview"
            label="Preview"
            :disable="isReadOnly"
          />
          <q-btn
            v-if="store.admin.audition.preview"
            :icon="matOpenInNew"
            label="Open preview"
            href="/audition/preview"
            target="_blank"
            flat
            dense
            no-caps
            color="primary"
          />
          <div class="col text-caption text-grey-7">
            When on (and saved), the saved page can be viewed at
            {{ previewUrl }} regardless of the display dates. Only one show's
            audition can be previewed at a time.
          </div>
          <!-- Same as the buttons below the editor, so a quick change
               (e.g. the Preview toggle) doesn't need a scroll to save. -->
          <div class="no-wrap">
            <q-btn
              label="Reset"
              @click="store.getAuditionConfig"
              color="negative"
            ></q-btn>
            <q-btn
              type="submit"
              label="Save"
              color="positive"
              class="q-ml-md text-black"
              :disable="isReadOnly"
            ></q-btn>
          </div>
        </div>

        <div class="col-12 q-pa-md">
          <div class="text-h6">
            Audition Page Content
          </div>
          <q-editor
            v-model="store.admin.audition.html"
            :toolbar="auditionToolbar"
            :fonts="auditionFonts"
            max-height="35vh"
            :disable="isReadOnly"
          ></q-editor>
          <div class="row items-start q-mt-sm no-wrap">
            <div class="col text-caption">
              You can build directly in the editor, or start from the director's
              Word document: click <b>Copy AI prompt</b> (it points the HTML at
              this show's poster on the site), paste it into ChatGPT, Claude or
              similar and attach the .docx, then click the "&lt; &gt;" icon in
              the editor's toolbar and paste the AI's HTML there. Production-team members can do the same from the
              Auditions tab on the director page.
            </div>
            <q-btn
              :icon="matContentCopy"
              label="Copy AI prompt"
              color="secondary"
              unelevated
              no-caps
              size="sm"
              class="q-ml-md"
              @click="copyPrompt"
            />
          </div>
        </div>
      </div>
      <div class="flex justify-end q-pa-md">
        <q-btn
          label="Reset"
          @click="store.getAuditionConfig"
          color="negative"
        ></q-btn>

        <q-btn
          type="submit"
          label="Save"
          color="positive"
          class="q-ml-md text-black"
          :disable="isReadOnly"
        ></q-btn>
      </div>
    </q-form>
  </div>
</template>

<script setup>
import { matContentCopy, matOpenInNew } from "@quasar/extras/material-icons";
import { Notify } from "quasar";
import {
  auditionFonts,
  auditionToolbar,
  buildAuditionPrompt,
  posterFor,
} from "src/assets/audition-editor";
import getPermissionLevel from "src/assets/get-permission-level";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";

const store = useStore();

const isReadOnly = computed(
  () => getPermissionLevel(store.admin.user, "auditions") === "read-only",
);

const formRef = ref(null);

const previewUrl = `${window.location.host}/audition/preview`;

const copyPrompt = async () => {
  try {
    const poster = posterFor(store.admin.show.poster);
    await navigator.clipboard.writeText(
      buildAuditionPrompt({
        showName: store.admin.show.name,
        posterPath: poster.path,
        posterUrl: poster.url,
      }),
    );
    Notify.create({ type: "positive", message: "AI prompt copied" });
  } catch {
    Notify.create({ type: "negative", message: "Couldn't copy to the clipboard in this browser." });
  }
};
</script>
