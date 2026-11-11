<template>
  <div class="q-pa-md">
    <div class="text-h6">Email Templates</div>
    <div class="text-caption text-grey-7 q-mb-md">
      Every email the site sends, what sends it, and who gets it. Switch an
      email off to stop it going out; edit its template to change what it
      says.
    </div>

    <div v-if="loading" class="text-center q-pa-xl">
      <q-spinner size="lg" color="primary" />
    </div>

    <template v-else>
      <div v-for="group in groups" :key="group.audience" class="q-mb-lg">
        <div class="text-subtitle1 text-weight-medium q-mb-sm">
          {{ group.label }}
        </div>
        <q-markup-table flat bordered separator="horizontal" wrap-cells>
          <thead>
            <tr>
              <th class="text-center" style="width: 60px;">On</th>
              <th class="text-left" style="width: 26%;">Email</th>
              <th class="text-left">What sends it</th>
              <th class="text-left" style="width: 20%;">Sent to</th>
              <th style="width: 60px;"></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="t in group.templates"
              :key="t.key"
              :class="{ 'text-grey-6': !t.enabled }"
            >
              <td class="text-center">
                <q-toggle
                  :model-value="t.enabled"
                  color="positive"
                  dense
                  :disable="isReadOnly"
                  @update:model-value="(value) => setEnabled(t, value)"
                >
                  <q-tooltip>{{ t.enabled ? "On — sent" : "Off — not sent" }}</q-tooltip>
                </q-toggle>
              </td>
              <td>
                <div class="text-weight-medium" :class="{ 'q-pl-md': t.section }">
                  {{ t.name }}
                </div>
                <div v-if="t.subject" class="text-caption text-grey-7" :class="{ 'q-pl-md': t.section }">
                  Subject: {{ t.subject }}
                </div>
                <div class="q-mt-xs" :class="{ 'q-pl-md': t.section }">
                  <q-badge v-if="t.customized" color="info" label="Edited" />
                  <q-badge v-if="!t.enabled" color="grey-6" label="Off" class="q-ml-xs" />
                </div>
              </td>
              <td class="text-body2">
                <div>{{ t.trigger }}</div>
                <div v-if="t.notes" class="text-caption text-grey-7 q-mt-xs">{{ t.notes }}</div>
              </td>
              <td class="text-body2">{{ t.recipient }}</td>
              <td class="text-right">
                <q-btn
                  :icon="isReadOnly ? matVisibility : matEdit"
                  flat
                  round
                  dense
                  color="primary"
                  @click="openEditor(t)"
                >
                  <q-tooltip>{{ isReadOnly ? "View template" : "Edit template" }}</q-tooltip>
                </q-btn>
              </td>
            </tr>
          </tbody>
        </q-markup-table>
      </div>
    </template>

    <q-dialog v-model="editor" maximized persistent>
      <q-card v-if="current" class="column no-wrap">
        <q-card-section class="row items-center bg-secondary text-white q-py-sm">
          <div>
            <div class="text-h6">{{ current.name }}</div>
            <div class="text-caption">{{ current.trigger }}</div>
          </div>
          <q-space />
          <q-btn :icon="matClose" flat round dense @click="closeEditor" />
        </q-card-section>

        <q-card-section class="col row q-col-gutter-md no-wrap" style="min-height: 0;">
          <div class="col-6 column no-wrap">
            <div class="row items-center q-mb-xs">
              <div class="text-caption text-grey-7">
                Template (HTML with Blade placeholders, e.g. <code v-pre>{{ $name }}</code>)
              </div>
              <q-space />
              <q-badge v-if="dirty" color="warning" label="Unsaved changes" />
            </div>
            <div v-if="placeholders.length" class="q-mb-sm">
              <span class="text-caption text-grey-7 q-mr-xs">Insert:</span>
              <q-chip
                v-for="p in placeholders"
                :key="p"
                dense
                clickable
                size="sm"
                :disable="isReadOnly"
                @click="insert(p)"
              >
                {{ p }}
              </q-chip>
            </div>
            <textarea
              ref="sourceInput"
              v-model="source"
              class="template-source col"
              spellcheck="false"
              :readonly="isReadOnly"
            ></textarea>
          </div>

          <div class="col-6 column no-wrap">
            <div class="row items-center q-mb-xs">
              <div class="text-caption text-grey-7">Preview, with sample data</div>
              <q-space />
              <q-spinner v-if="previewing" size="xs" color="primary" />
            </div>
            <div v-if="preview.subject" class="text-body2 q-mb-xs">
              <b>Subject:</b> {{ preview.subject }}
            </div>
            <q-banner v-if="previewError" dense class="bg-red-1 text-negative q-mb-sm">
              {{ previewError }}
            </q-banner>
            <iframe
              class="col preview-frame"
              :srcdoc="preview.html"
              sandbox=""
              title="Email preview"
            ></iframe>
          </div>
        </q-card-section>

        <q-card-actions class="q-px-md q-pb-md">
          <q-btn
            v-if="customized"
            flat
            color="negative"
            label="Reset to default"
            :disable="isReadOnly"
            @click="resetTemplate"
          />
          <q-space />
          <q-btn flat label="Close" @click="closeEditor" />
          <q-btn
            unelevated
            color="primary"
            label="Save"
            :loading="saving"
            :disable="isReadOnly || !dirty"
            @click="save"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import {
  matClose,
  matEdit,
  matVisibility,
} from "@quasar/extras/material-icons";
import { Dialog, Notify } from "quasar";
import callApi from "src/assets/call-api";
import getPermissionLevel from "src/assets/get-permission-level";
import { useStore } from "src/stores/store";
import { computed, nextTick, onMounted, ref, watch } from "vue";

const store = useStore();

const isReadOnly = computed(
  () => getPermissionLevel(store.admin.user, "email-templates") === "read-only",
);

const loading = ref(true);
const templates = ref([]);

const groups = computed(() =>
  [
    { audience: "Patron", label: "Emails to patrons" },
    { audience: "Staff", label: "Emails to staff" },
  ].map((g) => ({
    ...g,
    templates: templates.value.filter((t) => t.audience === g.audience),
  })),
);

const load = async () => {
  loading.value = true;
  const response = await callApi({
    path: "/email-templates",
    method: "get",
    useAuth: true,
  }).catch(() => null);
  templates.value = Array.isArray(response) ? response : [];
  loading.value = false;
};

onMounted(load);

// Saved at once; put back if the save fails.
const setEnabled = async (t, value) => {
  const previous = t.enabled;
  t.enabled = value;

  const response = await callApi({
    path: `/email-templates/${encodeURIComponent(t.key)}/enabled`,
    method: "put",
    payload: { enabled: value },
    useAuth: true,
    showError: false,
  }).catch(() => null);

  if (!response || response.status !== "success") {
    t.enabled = previous;
    Notify.create({ type: "negative", message: "Couldn't switch that email." });
    return;
  }

  Notify.create({
    type: value ? "positive" : "warning",
    message: value ? `"${t.name}" is on.` : `"${t.name}" is off — it won't be sent.`,
  });
};

// ── Editor ──────────────────────────────────────────────────────────────

const editor = ref(false);
const current = ref(null);
const source = ref("");
const savedSource = ref("");
const defaultSource = ref("");
const customized = ref(false);
const saving = ref(false);
const sourceInput = ref(null);

const preview = ref({ subject: "", html: "" });
const previewError = ref("");
const previewing = ref(false);

const dirty = computed(() => source.value !== savedSource.value);

// The placeholders the original template uses — the data this email has.
const placeholders = computed(() => {
  const found = new Set();
  for (const m of defaultSource.value.matchAll(/\{\{\s*(\$[a-zA-Z_]+(?:\[['"][a-z_]+['"]\]|->[a-zA-Z_]+)?)/g)) {
    found.add(`{{ ${m[1]} }}`);
  }
  return [...found];
});

const openEditor = async (t) => {
  current.value = t;
  preview.value = { subject: "", html: "" };
  previewError.value = "";

  const response = await callApi({
    path: `/email-templates/${encodeURIComponent(t.key)}`,
    method: "get",
    useAuth: true,
  }).catch(() => null);
  if (!response) return;

  source.value = response.source;
  savedSource.value = response.source;
  defaultSource.value = response.default;
  customized.value = response.customized;
  editor.value = true;
  refreshPreview();
};

const closeEditor = () => {
  if (!dirty.value) {
    editor.value = false;
    return;
  }
  Dialog.create({
    title: "Discard changes?",
    message: "Your edits to this template haven't been saved.",
    cancel: { label: "Keep editing", flat: true },
    ok: { label: "Discard", color: "negative", flat: true },
  }).onOk(() => {
    editor.value = false;
  });
};

let previewTimer = null;
watch(source, () => {
  if (!editor.value) return;
  clearTimeout(previewTimer);
  previewTimer = setTimeout(refreshPreview, 600);
});

const refreshPreview = async () => {
  if (!current.value) return;
  previewing.value = true;
  const response = await callApi({
    path: `/email-templates/${encodeURIComponent(current.value.key)}/preview`,
    method: "post",
    payload: { source: source.value },
    useAuth: true,
    showError: false,
  }).catch(() => null);
  previewing.value = false;

  if (response?.status === "success") {
    preview.value = { subject: response.subject, html: response.html };
    previewError.value = "";
  } else {
    previewError.value = response?.message || "Couldn't build the preview.";
  }
};

// Inserts at the cursor in the template.
const insert = async (text) => {
  const el = sourceInput.value;
  if (!el) return;
  const start = el.selectionStart ?? source.value.length;
  const end = el.selectionEnd ?? start;
  source.value = source.value.slice(0, start) + text + source.value.slice(end);
  await nextTick();
  el.focus();
  el.selectionStart = el.selectionEnd = start + text.length;
};

const save = async () => {
  saving.value = true;
  const response = await callApi({
    path: `/email-templates/${encodeURIComponent(current.value.key)}`,
    method: "put",
    payload: { source: source.value },
    useAuth: true,
    showError: false,
  }).catch(() => null);
  saving.value = false;

  if (!response || response.status !== "success") {
    Notify.create({
      type: "negative",
      message: response?.message || "Couldn't save the template.",
      timeout: 8000,
    });
    return;
  }

  savedSource.value = source.value;
  customized.value = true;
  current.value.customized = true;
  Notify.create({ type: "positive", message: "Template saved." });
};

const resetTemplate = () => {
  Dialog.create({
    title: "Reset to default?",
    message: "This replaces the edited template with the original. Your edits will be lost.",
    cancel: { label: "Cancel", flat: true },
    ok: { label: "Reset", color: "negative", flat: true },
  }).onOk(async () => {
    const response = await callApi({
      path: `/email-templates/${encodeURIComponent(current.value.key)}`,
      method: "delete",
      useAuth: true,
      showError: false,
    }).catch(() => null);

    if (!response || response.status !== "success") {
      Notify.create({ type: "negative", message: "Couldn't reset the template." });
      return;
    }

    source.value = response.source;
    savedSource.value = response.source;
    customized.value = false;
    current.value.customized = false;
    Notify.create({ type: "positive", message: "Template reset to default." });
  });
};
</script>

<style scoped>
.template-source {
  width: 100%;
  min-height: 0;
  resize: none;
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 12px;
  line-height: 1.5;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
  tab-size: 4;
}
.preview-frame {
  width: 100%;
  min-height: 0;
  border: 1px solid #ccc;
  border-radius: 4px;
  background: #fff;
}
</style>
