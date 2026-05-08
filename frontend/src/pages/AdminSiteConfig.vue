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
        <q-btn type="submit" label="Update" color="primary" />
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

      <!-- Support -->
      <div v-if="section === 'support'" class="row q-col-gutter-md q-px-sm">
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
          <q-btn label="Save" color="primary" @click="saveSupport" />
        </div>
      </div>

      <!-- Flex -->
      <div v-else-if="section === 'flex'" class="row q-col-gutter-md q-px-sm">
        <div class="col-12 col-sm-6">
          <q-input
            v-model="contentConfig.flex.title"
            label="Title"
            dense
            outlined
          />
        </div>
        <div class="col-12 col-sm-3">
          <q-input
            v-model="contentConfig.flex.price"
            label="Price"
            dense
            outlined
          />
        </div>
        <div class="col-12 col-sm-3">
          <q-input
            v-model.number="contentConfig.flex.num_tickets"
            type="number"
            label="# of Tickets"
            dense
            outlined
          />
        </div>
        <div class="col-12">
          <q-input
            v-model="contentConfig.flex.subtitle"
            label="Subtitle"
            dense
            outlined
          />
        </div>
        <div class="col-12 col-sm-6">
          <q-input
            v-model="contentConfig.flex.start_date"
            type="date"
            label="Available From"
            dense
            outlined
            stack-label
          />
        </div>
        <div class="col-12 col-sm-6">
          <q-input
            v-model="contentConfig.flex.end_date"
            type="date"
            label="Available Through"
            dense
            outlined
            stack-label
          />
        </div>
        <div class="col-12 col-sm-6">
          <q-input
            v-model="contentConfig.flex.fixr.label"
            label="Fixr Button Label"
            dense
            outlined
          />
        </div>
        <div class="col-12 col-sm-6">
          <q-input
            v-model="contentConfig.flex.fixr.link"
            label="Fixr Link"
            dense
            outlined
          />
        </div>
        <div class="col-12">
          <div class="text-caption text-grey-7 q-mb-xs">Body</div>
          <q-editor
            v-model="contentConfig.flex.body"
            min-height="6rem"
            :toolbar="bodyToolbar"
          />
        </div>
        <div class="col-12 flex justify-end">
          <q-btn label="Save" color="primary" @click="saveFlex" />
        </div>
      </div>

      <!-- Buttons -->
      <div v-else-if="section === 'buttons'" class="q-px-sm">
        <q-select
          v-model="selectedButtonId"
          :options="buttonOptions"
          label="Select Button"
          dense
          outlined
          emit-value
          map-options
          style="max-width: 360px;"
          class="q-mb-md"
        />
        <div v-if="selectedButton" class="row q-col-gutter-md">
          <div class="col-12 col-sm-8">
            <q-input
              v-model="selectedButton.label"
              label="Label"
              dense
              outlined
            />
          </div>
          <div class="col-12 col-sm-4">
            <q-input
              v-model.number="selectedButton.sort_order"
              type="number"
              label="Sort Order"
              dense
              outlined
            />
          </div>
          <div class="col-12">
            <div class="flex items-center q-gutter-x-sm q-mb-xs">
              <span class="text-caption text-grey-7">Template</span>
              <q-badge color="grey-3" text-color="grey-8" class="text-caption">
                use <code class="q-mx-xs">{{ templatePlaceholder }}</code> as a
                variable placeholder
              </q-badge>
            </div>
            <q-editor
              v-model="selectedButton.template"
              min-height="5rem"
              :toolbar="templateToolbar"
              :definitions="templateDefinitions"
            />
          </div>
          <div class="col-12 flex justify-end">
            <q-btn label="Save Button" color="primary" @click="saveButton" />
          </div>
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
import { useStore } from "src/stores/store";
import { computed, onMounted, ref } from "vue";

const store = useStore();

const contentConfig = ref(null);
const section = ref("support");
const selectedButtonId = ref(null);

const templatePlaceholder = "{{ $param }}";

const sectionOptions = [
  { label: "Support Us", value: "support" },
  { label: "Flex Tickets", value: "flex" },
  { label: "Payment Methods", value: "buttons" },
];

const selectedButton = computed(
  () =>
    contentConfig.value?.buttons.find((b) => b.id === selectedButtonId.value) ??
    null,
);

const buttonOptions = computed(
  () =>
    contentConfig.value?.buttons.map((b) => ({
      label: b.label,
      value: b.id,
    })) ?? [],
);

const templateDefinitions = {
  insertParam: {
    tip: "Insert $param placeholder",
    icon: "data_object",
    label: "{ $p }",
    handler() {
      const selection = window.getSelection();
      if (!selection?.rangeCount) return;
      const range = selection.getRangeAt(0);
      range.deleteContents();
      range.insertNode(document.createTextNode("{{ $param }}"));
      range.collapse(false);
    },
  },
};

const baseToolbar = [
  ["bold", "italic", "underline", "strike"],
  ["unordered", "ordered"],
  ["link", "code"],
  ["undo", "redo"],
];

const bodyToolbar = baseToolbar;

const templateToolbar = [...baseToolbar, ["insertParam"]];

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

const saveFlex = async () => {
  const response = await callApi({
    path: "/site-config/flex",
    method: "put",
    payload: contentConfig.value.flex,
    useAuth: true,
  });

  if (response.status == "success") {
    Notify.create({
      color: "positive",
      message: "Flex Tickets Config Updated",
    });
  }
};

const saveButton = async () => {
  const response = await callApi({
    path: "/site-config/standard-buttons",
    method: "put",
    payload: selectedButton.value,
    useAuth: true,
  });

  if (response.status == "success") {
    Notify.create({
      color: "positive",
      message: "Payment Method Config Updated",
    });
  }
};

onMounted(async () => {
  const response = await callApi({
    path: "/standard-buttons",
    method: "get",
    useAuth: true,
  });

  if (response) contentConfig.value = response;
});
</script>
