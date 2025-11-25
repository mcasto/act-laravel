<template>
  <div>
    <q-splitter :model-value="30" class="q-mb-xl">
      <template #before>
        <div class="q-mt-md q-mx-sm">
          <show-poster :show="store.admin.show" height="40vh"></show-poster>
          <div class="text-center q-mt-xs">
            <q-btn color="primary" icon="refresh" label="Change Poster Image">
              <q-tooltip>
                Replace Poster Image
              </q-tooltip>
              <q-menu cover anchor="top left" v-model="uploadMenu">
                <q-uploader
                  label="Upload File"
                  url="/api/update-image"
                  :headers="uploadHeaders"
                  :form-fields="[
                    {
                      name: 'filename',
                      value: `${store.admin.show.poster}.jpeg`,
                    },
                  ]"
                  field-name="image"
                  accept=".jpeg, .jpg, .png"
                  @uploaded="refreshPoster"
                  auto-upload
                ></q-uploader>
              </q-menu>
            </q-btn>
          </div>
          <q-separator class="q-my-md" v-if="store.admin.show.id"></q-separator>

          <div class="text-center">
            <q-btn icon="settings" color="secondary">
              <q-menu auto-close>
                <q-list
                  dense
                  separator
                  class="bg-secondary text-white text-uppercase"
                >
                  <q-item
                    clickable
                    @click="performancesDrawer = !performancesDrawer"
                  >
                    <q-item-section>
                      <q-item-label>
                        Performances
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item clickable to="/admin/audition-config">
                    <q-item-section>
                      <q-item-label>
                        Audition Config
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item clickable to="/admin/gallery">
                    <q-item-section>
                      <q-item-label>
                        Gallery
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-menu>
            </q-btn>
          </div>

          <q-separator class="q-my-md" v-if="store.admin.show.id"></q-separator>
          <div class="text-center" v-if="store.admin.show.id">
            <q-checkbox
              v-model="store.admin.show.tentative"
              :true-value="1"
              :false-value="0"
              label="Performance Dates are Tentative"
              @update:model-value="store.updateTentative"
            ></q-checkbox>
          </div>
        </div>
      </template>
      <template #after>
        <div class="q-ml-xs q-mt-md">
          <show-info-form :show="store.admin.show"></show-info-form>
        </div>
      </template>
    </q-splitter>

    <q-drawer v-model="performancesDrawer" bordered :width="500">
      <performances-drawer
        @close="performancesDrawer = false"
        @update="store.upsertPerformances"
      ></performances-drawer>
    </q-drawer>
  </div>
</template>

<script setup>
import PerformancesDrawer from "src/components/PerformancesDrawer.vue";
import ShowPoster from "src/components/ShowPoster.vue";
import ShowInfoForm from "src/components/ShowInfoForm.vue";

import { useStore } from "src/stores/store";
import { ref } from "vue";

const store = useStore();

const uploadHeaders = ref([
  { name: "Authorization", value: `Bearer ${store.admin.user?.token}` },
]);

const uploadMenu = ref(false);

const performancesDrawer = ref(false);

const refreshPoster = ({ xhr }) => {
  const { filename } = JSON.parse(xhr.response);

  // Force <show-poster> component to reload the image
  store.admin.show.poster = `${filename}?t=${Date.now()}`;

  // close uploadMenu
  uploadMenu.value = false;
};
</script>
