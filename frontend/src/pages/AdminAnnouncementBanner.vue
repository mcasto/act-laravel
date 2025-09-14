<template>
  <div class="flex justify-center">
    <q-form @submit.prevent="updateBanner">
      <q-card flat style="width: 50vw;">
        <q-toolbar>
          <q-toolbar-title class="text-center">
            Announcement Banner
          </q-toolbar-title>
        </q-toolbar>

        <q-card-section>
          <q-editor v-model="announcement.html" :toolbar="toolbar"></q-editor>
        </q-card-section>
        <q-card-actions class="justify-between">
          <div>
            <q-checkbox
              v-model="announcement.status"
              label="Active"
            ></q-checkbox>
          </div>

          <div>
            <q-btn type="submit" label="Update" color="primary"></q-btn>
          </div>
        </q-card-actions>
      </q-card>
    </q-form>
  </div>
</template>

<script setup>
import { trim } from "lodash-es";
import { Notify } from "quasar";
import { useStore } from "src/stores/store";
import { ref } from "vue";

const store = useStore();

const announcement = ref(store.announcement);

const toolbar = [
  ["bold", "italic", "strike", "underline", "subscript", "superscript"],
  ["token", "hr", "link", "custom_btn"],
  ["quote", "unordered", "ordered", "outdent", "indent"],
  ["undo", "redo"],
  ["viewsource"],
];

const updateBanner = () => {
  if (trim(announcement.value.html) == "") {
    announcement.value.status = false;
    Notify.create({
      type: "negative",
      message: "Banner can't be active without contents.",
    });

    return;
  }

  store.announcement = announcement.value;
  store.updateAnnouncementBanner();
};
</script>
