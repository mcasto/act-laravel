<template>
  <div>
    <q-splitter :model-value="20">
      <template #before>
        <q-page>
          <div class="column q-gutter-y-sm">
            <div class="flex justify-end">
              <q-btn round flat icon="add" size="sm">
                <q-menu v-model="uploaderRef">
                  <q-uploader
                    accept=".jpg,.jpeg,.png"
                    multiple
                    batch
                    no-thumbnails
                    url="/api/gallery"
                    method="post"
                    field-name="galleryUpload[]"
                    :headers="[
                      {
                        name: 'Authorization',
                        value: `Bearer ${store.admin.user.token}`,
                      },
                    ]"
                    :form-fields="[{ name: 'show_id', value: show.id }]"
                    @uploaded="onUploaded"
                  ></q-uploader>
                </q-menu>
              </q-btn>
            </div>

            <div v-if="gallery.length > 0">
              <q-list separator dense>
                <q-item
                  v-for="(item, idx) of gallery"
                  :key="`gallery-image-${item.id}`"
                >
                  <q-item>
                    <q-item-section avatar>
                      <q-img
                        :src="`/api/storage/${item.image}`"
                        class="cursor-pointer"
                        @click="photo = { ...item, idx }"
                        height="10vh"
                        width="10vw"
                        fit="cover"
                        v-if="gallery.length > 0"
                      >
                      </q-img>
                    </q-item-section>
                  </q-item>
                </q-item>
              </q-list>
            </div>

            <div v-else>
              Add Images to the Gallery
            </div>
          </div>
        </q-page>
      </template>
      <template #after>
        <q-page class="q-pl-md">
          <q-toolbar class="justify-end">
            <q-btn
              icon="delete"
              class="q-mr-md"
              flat
              color="negative"
              size="lg"
              @click="deleteImage"
              v-if="photo"
            ></q-btn>
            <q-btn
              icon="mdi-arrow-up"
              color="primary"
              @click="reorder(-1)"
              :disable="photo.idx == 0"
              v-if="photo"
            ></q-btn>
            <q-btn
              icon="mdi-arrow-down"
              color="primary"
              @click="reorder(1)"
              :disable="(photo.idx == gallery.length - 1)"
              v-if="photo"
            ></q-btn>
          </q-toolbar>
          <q-img
            :src="`/api/storage/${photo.image}`"
            height="60vh"
            fit="contain"
            v-if="photo"
          ></q-img>
          <div v-else>
            No Selected Photo
          </div>
        </q-page>
      </template>
    </q-splitter>
  </div>
</template>

<script setup>
import { remove } from "lodash-es";
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";

const store = useStore();

const uploaderRef = ref(null);

const show = computed(() => {
  return store.admin.show;
});

const gallery = ref(show.value.gallery_images);

const photo = ref(
  gallery.value.length > 0 ? { ...gallery.value[0], idx: 0 } : null
);

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

  for (let image of response.files) {
    store.admin.show.gallery_images.push(image);
  }

  uploaderRef.value = false;
};

const reorder = (direction) => {
  const idx = photo.value.idx;
  [
    store.admin.show.gallery_images[idx + direction],
    store.admin.show.gallery_images[idx],
  ] = [
    store.admin.show.gallery_images[idx],
    store.admin.show.gallery_images[idx + direction],
  ];

  photo.value.idx = photo.value.idx + direction;

  for (let index = 0; index < store.admin.show.gallery_images.length; index++) {
    store.admin.show.gallery_images[index].sort_order = index;
  }

  callApi({
    path: "/gallery",
    method: "put",
    payload: store.admin.show.gallery_images,
    useAuth: true,
  });
};

const deleteImage = async () => {
  Notify.create({
    type: "warning",
    position: "center",
    message: "Are you sure you want to delete this image?",
    actions: [
      {
        label: "No",
      },
      {
        label: "Yes",
        handler: async () => {
          const response = await callApi({
            path: `/gallery/${photo.value.id}`,
            method: "delete",
            useAuth: true,
          });

          if (response.status != "success") {
            Notify.create({
              type: "negative",
              position: "center",
              message: response.message,
            });
            return;
          }

          remove(gallery.value, ({ id }) => id == photo.value.id);

          photo.value = null;

          Notify.create({
            type: "positive",
            position: "center",
            message: "Image deleted",
          });
        },
      },
    ],
  });
};
</script>
