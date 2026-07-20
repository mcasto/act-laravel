<template>
  <div>
    <q-splitter :model-value="30" class="q-mb-xl">
      <template #before>
        <div class="q-mt-md q-mx-sm">
          <show-poster :show="store.admin.show" height="40vh"></show-poster>
          <div class="text-center q-mt-xs">
            <q-btn color="primary" :icon="matRefresh" label="Change Poster Image">
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
                      value: `${store.admin.show.poster}`,
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
            <q-btn :icon="matSettings" color="secondary">
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
                  <q-item
                    clickable
                    :to="{
                      name: 'admin-comp-config',
                    }"
                  >
                    <q-item-section>
                      <q-item-label>
                        Comp Tickets
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
                  <q-item clickable @click="openFlexDialog">
                    <q-item-section>
                      <q-item-label>
                        Flex Link
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

    <!-- Flex Link dialog -->
    <q-dialog v-model="flexDialog">
      <q-card style="min-width: 480px; max-width: 90vw;">
        <q-card-section class="row items-center bg-secondary text-white">
          <div class="text-h6">Flex Early-Access Link</div>
          <q-space />
          <q-btn :icon="matClose" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section v-if="flexLoading" class="text-center q-py-xl">
          <q-spinner size="2em" />
        </q-card-section>

        <q-card-section v-else>
          <div class="text-caption q-mb-sm text-grey-7">
            Share this link with flex ticket holders for early ticket access.
          </div>
          <q-input
            :model-value="flexUrl"
            readonly
            outlined
            dense
          >
            <template #append>
              <q-btn
                :icon="matContentCopy"
                flat
                round
                dense
                @click="copyFlexLink"
              >
                <q-tooltip>Copy to clipboard</q-tooltip>
              </q-btn>
            </template>
          </q-input>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog
      v-model="performancesDrawer"
      :maximized="false"
      transition-show="slide-left"
      transition-hide="slide-right"
    >
      <q-card style="width: 500px; max-width: 90vw;">
        <performances-drawer
          @close="performancesDrawer = false"
          @update="store.upsertPerformances"
        ></performances-drawer>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { matClose, matContentCopy, matRefresh, matSettings } from "@quasar/extras/material-icons";
import PerformancesDrawer from "src/components/PerformancesDrawer.vue";
import ShowPoster from "src/components/ShowPoster.vue";
import ShowInfoForm from "src/components/ShowInfoForm.vue";
import callApi from "src/assets/call-api";

import { Notify } from "quasar";
import { useStore } from "src/stores/store";
import { ref, computed } from "vue";
import { useRoute, useRouter } from "vue-router";

const route  = useRoute();
const router = useRouter();
const store  = useStore();

const uploadHeaders = ref([
  { name: "Authorization", value: `Bearer ${store.admin.user?.token}` },
]);

const uploadMenu = ref(false);

const performancesDrawer = ref(false);

const flexDialog  = ref(false);
const flexLoading = ref(false);
const flexUid     = ref(null);

const flexUrl = computed(() => {
  if (!flexUid.value) return "";
  const path = router.resolve({
    name: "flex-show-details",
    params: { uid: flexUid.value },
  }).href;
  return window.location.origin + path;
});

const openFlexDialog = async () => {
  flexDialog.value  = true;
  flexLoading.value = true;
  flexUid.value     = null;

  const response = await callApi({
    path: `/admin/flex-link/${store.admin.show.id}`,
    method: "get",
    useAuth: true,
  });

  flexUid.value     = response?.uid ?? null;
  flexLoading.value = false;
};

const copyFlexLink = async () => {
  await navigator.clipboard.writeText(flexUrl.value);
  Notify.create({ type: "positive", message: "Link copied to clipboard" });
};

const refreshPoster = ({ xhr }) => {
  const { filename } = JSON.parse(xhr.response);

  // Force <show-poster> component to reload the image
  store.admin.show.poster = `${filename}?t=${Date.now()}`;

  // close uploadMenu
  uploadMenu.value = false;
};
</script>
