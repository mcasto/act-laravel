<template>
  <div>
    <q-form @submit.prevent="onSubmit">
      <div class="column q-gutter-y-md">
        <div class="row q-gutter-x-md">
          <q-input
            type="text"
            dense
            outlined
            label="Header"
            v-model="form.title"
            class="col-12 col-md-5"
          ></q-input>

          <q-input
            type="text"
            dense
            outlined
            label="Subheader"
            v-model="form.subtitle"
            class="col-12 col-md-5"
          ></q-input>
        </div>

        <q-input
          type="text"
          dense
          outlined
          label="Fixr Link"
          v-model="form.fixr.link"
        >
          <template #after>
            <q-btn
              round
              size="sm"
              icon="link"
              color="primary"
              @click="openLink"
            ></q-btn>
          </template>
        </q-input>

        <q-field label="Body" stack-label outlined>
          <template v-slot:control>
            <q-editor
              v-model="form.body"
              :toolbar="[
                ['left', 'center', 'right', 'justify'],
                ['bold', 'italic', 'underline'],
                ['link'],
                ['undo', 'redo'],
                ['viewsource'],
              ]"
            ></q-editor>
          </template>
        </q-field>

        <q-toolbar>
          <q-toolbar-title>
            Image
          </q-toolbar-title>
          <q-btn label="Change Image" color="primary">
            <q-menu v-model="uploaderMenu">
              <q-uploader
                auto-upload
                url="/api/flex-purchase-config/image"
                method="post"
                field-name="image"
                :headers="[
                  {
                    name: 'Authorization',
                    value: `Bearer ${store.admin.user?.token}`,
                  },
                ]"
                @uploaded="onUploaded"
              ></q-uploader>
            </q-menu>
          </q-btn>
        </q-toolbar>
        <q-img
          :src="imagePath"
          width="40vw"
          fit="contain"
          class="q-mt-xs"
        ></q-img>
      </div>
      <q-separator spaced></q-separator>
      <div class="flex justify-end">
        <q-btn color="positive" label="Update" type="submit"></q-btn>
      </div>
    </q-form>
  </div>
</template>

<script setup>
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { ref } from "vue";

const store = useStore();

const form = ref(store.flex);
const uploaderMenu = ref(false);
const imagePath = ref("/api/storage/flex-image");

const openLink = () => {
  window.open(form.value.fixr.link);
};

const onUploaded = ({ files, xhr }) => {
  const response = JSON.parse(xhr.response);
  if (response.status != "success") {
    Notify.create({
      type: "negative",
      position: "center",
      message: response.message,
    });
    return;
  }

  form.value.image = response.path;
  imagePath.value = `/api/storage/${response.path}`;

  uploaderMenu.value = false;
};

const onSubmit = async () => {
  const response = await callApi({
    path: "/flex-purchase-config",
    method: "put",
    useAuth: true,
    payload: form.value,
  });

  console.log({ response });
};
</script>
