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
          ></q-input>
        </div>
        <div class="col-12 col-md-6 q-pa-md">
          <q-input
            dense
            outlined
            type="date"
            v-model="store.admin.audition.end_display_date"
            label="End Display"
          ></q-input>
        </div>

        <div class="col-12 q-pa-md">
          <div class="text-h6">
            Audition Page Content
          </div>
          <q-editor
            v-model="store.admin.audition.html"
            :toolbar="toolbar"
            :fonts="fonts"
            max-height="35vh"
          ></q-editor>
          <div class="text-caption">
            You can build directly in the editor, or you can go to ChatGPT,
            upload the poster image and upload or enter the information for the
            audition, then ask Chat to generate HTML/CSS to display that
            information with the poster image and use the colors in the poster
            image as the theme. Then click on the "< >" icon in the toolbar for
            the editor & paste Chat's output there.
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
        ></q-btn>
      </div>
    </q-form>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import { ref } from "vue";

const store = useStore();

const formRef = ref(null);

const toolbar = [
  ["left", "center", "right", "justify"],
  ["bold", "italic", "underline", "strike"],
  ["fullscreen"],
  [
    {
      label: "Font Size",
      icon: "format_size",
      fixedLabel: true,
      fixedIcon: true,
      list: "no-icons",
      options: [
        "size-1",
        "size-2",
        "size-3",
        "size-4",
        "size-5",
        "size-6",
        "size-7",
      ],
    },
    {
      label: "Font Family",
      icon: "font_download",
      fixedIcon: true,
      list: "no-icons",
      options: [
        "arial",
        "arial_black",
        "comic_sans",
        "courier_new",
        "impact",
        "lucida_grande",
        "times_new_roman",
        "verdana",
      ],
    },
    "removeFormat",
  ],
  ["quote", "unordered", "ordered", "outdent", "indent"],
  ["link", "image"],
  ["viewsource"], // This allows viewing HTML source
];

const fonts = {
  arial: "Arial",
  arial_black: "Arial Black",
  comic_sans: "Comic Sans MS",
  courier_new: "Courier New",
  impact: "Impact",
  lucida_grande: "Lucida Grande",
  times_new_roman: "Times New Roman",
  verdana: "Verdana",
};
</script>
