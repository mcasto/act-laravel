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

      <!-- Season -->
      <div v-if="section === 'season'" class="q-px-sm" style="max-width: 360px;">
        <div class="text-caption text-grey-7 q-mb-sm">
          Which season new Angel donations get tagged with. Separate from the
          show/Flex-ticket calendar, since Angel promotion for a season
          starts before the previous one technically ends.
        </div>
        <q-select
          v-model="selectedSeason"
          :options="seasonOptions"
          label="Active Angel Season"
          dense
          outlined
          emit-value
          map-options
          class="q-mb-md"
        />
        <div class="flex justify-end">
          <q-btn label="Save" color="primary" @click="saveSeason" />
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
          <div class="text-caption text-grey-7 q-mb-xs">
            Template for Display on Site
          </div>
          <q-editor
            v-model="contentConfig.flex.body"
            min-height="6rem"
            :toolbar="bodyToolbar"
            :definitions="colorDefinitions"
            @paste="handlePlainTextPaste"
          />
        </div>
        <div class="col-12">
          <div class="flex items-center q-gutter-x-sm q-mb-xs">
            <span class="text-caption text-grey-7">Template for Confirmation Email</span>
            <q-badge color="grey-3" text-color="grey-8" class="text-caption">
              use the <q-icon :name="mdiCodeBraces" size="14px" /> toolbar button to
              insert available placeholders
            </q-badge>
          </div>
          <q-editor
            v-model="contentConfig.flex.confirmation_body"
            min-height="6rem"
            :toolbar="flexConfirmationToolbar"
            :definitions="confirmationDefinitions"
            @paste="handlePlainTextPaste"
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
              <span class="text-caption text-grey-7">Template for Display on Site</span>
              <q-badge color="grey-3" text-color="grey-8" class="text-caption">
                use the <q-icon :name="mdiCodeBraces" size="14px" /> toolbar button to
                insert available placeholders
              </q-badge>
            </div>
            <q-editor
              v-model="selectedButton.template"
              min-height="5rem"
              :toolbar="templateToolbar"
              :definitions="templateDefinitions"
              @paste="handlePlainTextPaste"
            />
          </div>
          <div
            class="col-12"
            v-if="!['flex', 'questions'].includes(selectedButton.key)"
          >
            <div class="flex items-center q-gutter-x-sm q-mb-xs">
              <span class="text-caption text-grey-7">Template for Confirmation Email</span>
              <q-badge color="grey-3" text-color="grey-8" class="text-caption">
                use the <q-icon :name="mdiCodeBraces" size="14px" /> toolbar button to
                insert available placeholders
              </q-badge>
            </div>
            <q-editor
              v-model="selectedButton.confirmation_template"
              min-height="5rem"
              :toolbar="confirmationToolbar"
              :definitions="confirmationDefinitions"
              @paste="handlePlainTextPaste"
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
import { mdiCircle, mdiCodeBraces, mdiFormatColorText, mdiFormatSize } from "@quasar/extras/mdi-v7";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { computed, onMounted, ref } from "vue";

const store = useStore();

const contentConfig = ref(null);
const section = ref("support");
const selectedButtonId = ref(null);

const sectionOptions = [
  { label: "Season", value: "season" },
  { label: "Support Us", value: "support" },
  { label: "Flex Tickets", value: "flex" },
  { label: "Payment Methods", value: "buttons" },
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

// q-editor only syncs its v-model on the contentEditable's native "input"
// event, so DOM mutations must go through execCommand (which dispatches
// that event) rather than raw Range/Node manipulation — otherwise the
// change is visible on screen but never reaches the Vue model, and a save
// right after silently reverts to whatever was there before.
const insertText = (text) => document.execCommand("insertText", false, text);

// Rich content pasted from Gmail/Word/etc. carries along font/color spans
// and can corrupt or truncate embedded {{ $placeholder }} text; force paste
// to plain text so templates stay predictable and placeholders survive.
//
// q-editor doesn't set inheritAttrs: false, so Vue's automatic attrs
// fallthrough attaches this same @paste listener to q-editor's outer
// wrapper div *in addition to* the inner contentEditable div it's meant
// for. Since "paste" bubbles, one paste fires the handler twice (inner,
// then outer) and inserts the text twice — stopPropagation keeps it from
// reaching that second, duplicate binding.
const handlePlainTextPaste = (event) => {
  event.preventDefault();
  event.stopPropagation();
  insertText(event.clipboardData?.getData("text/plain") ?? "");
};

// Placeholders available to standard-button templates (see
// resources/views/standard-buttons/*.blade.php on the backend), which are
// rendered with `param` (the price/amount) and `subject` (what the payment
// is for) passed in as variables.
const insertPlaceholder = (text) => () => insertText(text);

// A small preset color palette (rather than a full color-picker popup) —
// these are plain {cmd, param} entries, so q-editor runs them the same way
// it runs its own built-in buttons (document.execCommand("foreColor", ...)),
// no custom handler needed.
const colorDefinitions = {
  colorBlack: { cmd: "foreColor", param: "#000000", tip: "Black", icon: mdiCircle, color: "black" },
  colorGrey: { cmd: "foreColor", param: "#666666", tip: "Grey", icon: mdiCircle, color: "grey-7" },
  colorRed: { cmd: "foreColor", param: "#d32f2f", tip: "Red", icon: mdiCircle, color: "red" },
  colorOrange: { cmd: "foreColor", param: "#f57c00", tip: "Orange", icon: mdiCircle, color: "orange" },
  colorGreen: { cmd: "foreColor", param: "#388e3c", tip: "Green", icon: mdiCircle, color: "green" },
  colorBlue: { cmd: "foreColor", param: "#1976d2", tip: "Blue", icon: mdiCircle, color: "blue" },
  colorPurple: { cmd: "foreColor", param: "#7b1fa2", tip: "Purple", icon: mdiCircle, color: "purple" },
};

const templateDefinitions = {
  ...colorDefinitions,
  insertParam: {
    tip: "The price or amount, e.g. \"$25\" or \"$25 per ticket\"",
    label: "Amount — {{ $param }}",
    handler: insertPlaceholder("{{ $param }}"),
  },
  insertSubject: {
    tip: "What the payment is for, e.g. \"Support Us\" or \"you purchased Flex Tickets\"",
    label: "Description — {{ $subject }}",
    handler: insertPlaceholder("{{ $subject }}"),
  },
};

// Placeholders available to confirmation emails (see the `$confirmationData`
// array built in TicketSaleController@store), rendered via Blade::render
// before being spliced into purchase-confirmation/flex-confirmation blade views.
const confirmationDefinitions = {
  ...colorDefinitions,
  insertName: {
    tip: "The patron's full name",
    label: "Patron Name — {{ $name }}",
    handler: insertPlaceholder("{{ $name }}"),
  },
  insertShowName: {
    tip: "The name of the show",
    label: "Show — {{ $show_name }}",
    handler: insertPlaceholder("{{ $show_name }}"),
  },
  insertNumTickets: {
    tip: "The number of tickets purchased",
    label: "# of Tickets — {{ $num_tickets }}",
    handler: insertPlaceholder("{{ $num_tickets }}"),
  },
  insertPerformanceDate: {
    tip: "The performance date, e.g. \"August 6, 2026\"",
    label: "Performance Date — {{ $performance_date }}",
    handler: insertPlaceholder("{{ $performance_date }}"),
  },
  insertPerformanceTime: {
    tip: "The performance start time, e.g. \"7:00 PM\"",
    label: "Performance Time — {{ $performance_time }}",
    handler: insertPlaceholder("{{ $performance_time }}"),
  },
  insertRemainingFlex: {
    tip: "The patron's remaining Flex ticket balance for the season",
    label: "Remaining Flex Tickets — {{ $remaining_flex }}",
    handler: insertPlaceholder("{{ $remaining_flex }}"),
  },
  insertSeason: {
    tip: "The current theater season",
    label: "Season — {{ $season }}",
    handler: insertPlaceholder("{{ $season }}"),
  },
};

const baseToolbar = [
  ["bold", "italic", "underline", "strike"],
  ["unordered", "ordered"],
  ["link", "code"],
  [
    {
      icon: mdiFormatSize,
      fixedIcon: true,
      list: "no-icons",
      options: ["size-1", "size-2", "size-3", "size-4", "size-5", "size-6", "size-7"],
    },
    {
      icon: mdiFormatColorText,
      fixedIcon: true,
      list: "only-icons",
      options: [
        "colorBlack",
        "colorGrey",
        "colorRed",
        "colorOrange",
        "colorGreen",
        "colorBlue",
        "colorPurple",
      ],
    },
  ],
  ["undo", "redo"],
];

const bodyToolbar = baseToolbar;

const templateToolbar = [
  ...baseToolbar,
  [
    {
      label: "Insert Parameter",
      icon: mdiCodeBraces,
      fixedLabel: true,
      list: "no-icons",
      options: ["insertParam", "insertSubject"],
    },
  ],
];

const confirmationToolbar = [
  ...baseToolbar,
  [
    {
      label: "Insert Parameter",
      icon: mdiCodeBraces,
      fixedLabel: true,
      list: "no-icons",
      options: [
        "insertName",
        "insertShowName",
        "insertNumTickets",
        "insertPerformanceDate",
        "insertPerformanceTime",
      ],
    },
  ],
];

const flexConfirmationToolbar = [
  ...baseToolbar,
  [
    {
      label: "Insert Parameter",
      icon: mdiCodeBraces,
      fixedLabel: true,
      list: "no-icons",
      options: [
        "insertName",
        "insertShowName",
        "insertNumTickets",
        "insertPerformanceDate",
        "insertPerformanceTime",
        "insertRemainingFlex",
        "insertSeason",
      ],
    },
  ],
];

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
      message: "Active Angel Season Updated",
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

  if (response) {
    // pre-existing configs saved before this field existed won't have it yet
    if (response.flex) response.flex.confirmation_body ??= "";
    response.buttons?.forEach((button) => {
      button.confirmation_template ??= "";
    });

    contentConfig.value = response;
    selectedSeason.value = response.season ?? null;
  }
});
</script>
