<template>
  <div class="q-mt-md">
    <q-form @submit.prevent="store.updateSiteConfig">
      <div class="row q-gutter-y-md">
        <div class="col-6 q-px-md">
          <q-input
            type="number"
            v-model.number="store.config.ticket_price"
            label="Default Ticket Price"
            dense
            outlined
          />
        </div>
        <div class="col-6 q-px-md">
          <q-input
            type="number"
            v-model.number="store.config.sold_out_target"
            label="Default Sold Out Target"
            hint="Seats every performance counts as full at, unless its show or the performance sets its own target"
            dense
            outlined
          />
        </div>
        <div class="col-6 q-px-md">
          <q-input
            type="number"
            min="0"
            v-model.number="store.config.nearly_sold_out_threshold"
            label="Nearly Sold Out Threshold"
            hint="When a patron picks a performance with this many seats left or fewer, they're warned, and can't reserve more than the seats left (except by card)"
            dense
            outlined
          />
        </div>
        <div class="col-6 q-px-md">
          <q-input
            v-model="store.config.ticket_email"
            label="Email for Ticket Orders"
            dense
            outlined
          />
        </div>
        <div class="col-6 q-px-md">
          <q-input
            v-model="store.config.contact_email"
            label="Email for Contacts"
            dense
            outlined
          />
        </div>
      </div>
      <div class="flex justify-end q-mt-md q-mr-md">
        <q-btn type="submit" label="Update" color="primary" :disable="isReadOnly" />
      </div>
    </q-form>

    <q-separator class="q-my-xl" />

    <div v-if="contentConfig">
      <div class="flex items-center q-gutter-md q-mb-lg">
        <span class="text-subtitle1 text-weight-medium">Content</span>
        <q-btn-toggle
          v-model="section"
          :options="sectionOptions"
          no-caps
          unelevated
          toggle-color="primary"
          outline
        />
      </div>

      <!-- Season -->
      <div v-if="section === 'season'" class="q-px-sm" style="max-width: 360px;">
        <div class="text-caption text-grey-7 q-mb-sm">
          Which season new Angel donations and new Flex ticket purchases get
          tagged with. Separate from the show/Flex-redemption calendar, since
          promotion for a season starts before the previous one technically
          ends.
        </div>
        <q-select
          v-model="selectedSeason"
          :options="seasonOptions"
          label="Active Season"
          dense
          outlined
          emit-value
          map-options
          class="q-mb-md"
        />
        <div class="flex justify-end">
          <q-btn label="Save" color="primary" @click="saveSeason" :disable="isReadOnly" />
        </div>
      </div>

      <!-- Angels -->
      <div v-else-if="section === 'angels'" class="row q-col-gutter-md q-px-sm">
        <div class="col-12">
          <q-input
            v-model="contentConfig.angels.title"
            label="Page Title"
            hint="Shown at the top of the Angels page. Use {season} where the current season (e.g. 2026-2027) should go — it fills in automatically and rolls over on its own each year."
            dense
            outlined
          />
        </div>
        <div class="col-12 col-sm-6">
          <q-input
            v-model="contentConfig.angels.note"
            label="Note"
            hint="e.g. explains what an asterisk next to a name means"
            dense
            outlined
          />
        </div>
        <div class="col-12 col-sm-6">
          <q-input
            v-model="contentConfig.angels.fixr_label"
            label="Fixr Button Label"
            dense
            outlined
          />
        </div>
        <div class="col-12 flex justify-end">
          <q-btn label="Save" color="primary" @click="saveAngels" :disable="isReadOnly" />
        </div>
      </div>

      <!-- Support -->
      <div v-else-if="section === 'support'" class="row q-col-gutter-md q-px-sm">
        <div class="col-12 col-sm-4">
          <q-input
            v-model="contentConfig.support.price"
            label="Price"
            dense
            outlined
          />
        </div>
        <div class="col-12 col-sm-8">
          <q-input
            v-model="contentConfig.support.fixr_label"
            label="Fixr Button Label"
            dense
            outlined
          />
        </div>
        <div class="col-12">
          <q-input
            v-model="contentConfig.support.fixr_link"
            label="Fixr Link"
            dense
            outlined
          />
        </div>
        <div class="col-12 flex justify-end">
          <q-btn label="Save" color="primary" @click="saveSupport" :disable="isReadOnly" />
        </div>
      </div>
    </div>

    <div v-else class="flex items-center q-gutter-sm q-pa-md text-grey-6">
      <q-spinner size="1.2em" />
      <span>Loading…</span>
    </div>
  </div>
</template>

<script setup>
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import getPermissionLevel from "src/assets/get-permission-level";
import { useStore } from "src/stores/store";
import { computed, onMounted, ref } from "vue";

const store = useStore();

const isReadOnly = computed(
  () => getPermissionLevel(store.admin.user, "site-config") === "read-only",
);

const contentConfig = ref(null);
const section = ref("season");

const sectionOptions = [
  { label: "Season", value: "season" },
  { label: "Angels", value: "angels" },
  { label: "Support Us", value: "support" },
];

const currentYear = new Date().getFullYear();

// { last year }-{ this year }, { this year }-{ next year }, { next year }-{ following year }
const seasonOptions = [-1, 0, 1].map((offset) => {
  const startYear = currentYear + offset;
  const endYear = startYear + 1;
  return {
    label: `${startYear} - ${endYear}`,
    value: `${String(startYear).slice(-2)}-${String(endYear).slice(-2)}`,
  };
});

const selectedSeason = ref(null);

const saveSeason = async () => {
  const response = await callApi({
    path: "/site-config/season",
    method: "put",
    payload: { season: selectedSeason.value },
    useAuth: true,
  });

  if (response.status == "success") {
    Notify.create({
      color: "positive",
      message: "Active Season Updated",
    });
  }
};

const saveAngels = async () => {
  const response = await callApi({
    path: "/site-config/angels",
    method: "put",
    payload: contentConfig.value.angels,
    useAuth: true,
  });

  if (response.status == "success") {
    Notify.create({
      color: "positive",
      message: "Angels Page Config Updated",
    });
  }
};

const saveSupport = async () => {
  const response = await callApi({
    path: "/site-config/support",
    method: "put",
    payload: contentConfig.value.support,
    useAuth: true,
  });

  if (response.status == "success") {
    Notify.create({
      color: "positive",
      message: "Support Us Config Updated",
    });
  }
};

onMounted(async () => {
  const response = await callApi({
    path: "/site-config/content",
    method: "get",
    useAuth: true,
  });

  if (response) {
    contentConfig.value = response;
    selectedSeason.value = response.season ?? null;
  }
});
</script>
