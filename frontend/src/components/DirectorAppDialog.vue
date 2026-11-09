<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    @show="onShow"
    @hide="onHide"
  >
    <q-card style="width: 560px; max-width: 95vw;">
      <q-card-section class="row items-center bg-secondary text-white">
        <div>
          <div class="text-h6">Director App</div>
          <div class="text-caption">{{ user?.name }} ({{ user?.email }})</div>
        </div>
        <q-space />
        <q-btn :icon="matClose" flat round dense v-close-popup />
      </q-card-section>

      <!-- Setup code -->
      <q-card-section>
        <div class="text-subtitle1 q-mb-xs">Set up a computer</div>
        <div class="text-caption text-grey-7 q-mb-md">
          Generate a one-time code and give it to {{ user?.name }}. They enter it
          in the director app to connect it to their account. The code works
          once and expires after 15 minutes. Making a new code cancels any
          unused one. The app only lists the shows they've been added to under
          each show's Production Team Access.
        </div>

        <div v-if="code && !codeExpired" class="text-center q-mb-md">
          <div
            class="text-h4 text-weight-bold q-py-sm"
            style="letter-spacing: 0.25em; font-family: monospace;"
          >
            {{ code.code }}
            <q-btn :icon="matContentCopy" flat round dense size="md" @click="copyCode">
              <q-tooltip>Copy code</q-tooltip>
            </q-btn>
          </div>
          <div class="text-caption text-grey-7">
            Expires at {{ expiresAtLabel }} ({{ remainingLabel }})
          </div>
        </div>
        <div v-else-if="code && codeExpired" class="text-center text-negative q-mb-md">
          That code has expired. Generate a new one.
        </div>

        <div class="text-center">
          <q-btn
            :label="code ? 'Generate new code' : 'Generate setup code'"
            color="primary"
            no-caps
            :loading="generating"
            @click="generate"
          />
        </div>
      </q-card-section>

      <q-separator />

      <!-- Paired devices -->
      <q-card-section>
        <div class="row items-center q-mb-sm">
          <div class="text-subtitle1">Connected computers</div>
          <q-space />
          <q-btn :icon="matRefresh" flat round dense :loading="loadingDevices" @click="loadDevices">
            <q-tooltip>Refresh</q-tooltip>
          </q-btn>
        </div>

        <div v-if="!loadingDevices && devices.length === 0" class="text-grey text-caption">
          No computers connected.
        </div>

        <q-list v-else separator dense>
          <q-item v-for="device in devices" :key="device.id">
            <q-item-section>
              <q-item-label>{{ device.device_name }}</q-item-label>
              <q-item-label caption>
                Connected {{ formatDateTime(device.paired_at) }} ·
                Last used {{ device.last_used_at ? formatDateTime(device.last_used_at) : "never" }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-btn
                label="Revoke"
                color="negative"
                flat
                dense
                no-caps
                @click="revoke(device)"
              />
            </q-item-section>
          </q-item>
        </q-list>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { matClose, matContentCopy, matRefresh } from "@quasar/extras/material-icons";
import { Dialog, Notify } from "quasar";
import { computed, ref } from "vue";
import callApi from "src/assets/call-api";

const props = defineProps({
  modelValue: Boolean,
  user: Object,
});
const emit = defineEmits(["update:modelValue"]);

const code = ref(null); // { code, expires_at }
const generating = ref(false);
const devices = ref([]);
const loadingDevices = ref(false);

// Ticks once a second while a code is on screen, to drive the countdown.
const now = ref(Date.now());
let timer = null;

const expiresAt = computed(() => (code.value ? new Date(code.value.expires_at) : null));
const codeExpired = computed(() => expiresAt.value && now.value >= expiresAt.value.getTime());
const expiresAtLabel = computed(() =>
  expiresAt.value?.toLocaleTimeString([], { hour: "numeric", minute: "2-digit" }),
);
const remainingLabel = computed(() => {
  if (!expiresAt.value) return "";
  const secs = Math.max(0, Math.round((expiresAt.value.getTime() - now.value) / 1000));
  const m = Math.floor(secs / 60);
  const s = String(secs % 60).padStart(2, "0");
  return `${m}:${s} left`;
});

const formatDateTime = (iso) =>
  new Date(iso).toLocaleString([], {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });

const errorMessage = (error) =>
  error?.json?.message || error?.message || "Something went wrong.";

const onShow = () => {
  code.value = null;
  devices.value = [];
  timer = setInterval(() => (now.value = Date.now()), 1000);
  loadDevices();
};

// The plain code is never stored anywhere, so it's gone once the dialog closes.
const onHide = () => {
  clearInterval(timer);
  timer = null;
  code.value = null;
};

const loadDevices = async () => {
  if (!props.user) return;
  loadingDevices.value = true;
  try {
    devices.value =
      (await callApi({
        path: `/users/${props.user.id}/app-devices`,
        method: "get",
        useAuth: true,
      })) || [];
  } catch (error) {
    Notify.create({ type: "negative", message: errorMessage(error) });
  } finally {
    loadingDevices.value = false;
  }
};

const generate = async () => {
  generating.value = true;
  try {
    const response = await callApi({
      path: "/admin/app-pairing-codes",
      method: "post",
      payload: { user_id: props.user.id },
      useAuth: true,
    });
    if (response) {
      code.value = response;
      now.value = Date.now();
    }
  } catch (error) {
    Notify.create({ type: "negative", message: errorMessage(error) });
  } finally {
    generating.value = false;
  }
};

const copyCode = async () => {
  await navigator.clipboard.writeText(code.value.code);
  Notify.create({ type: "positive", message: "Code copied to clipboard" });
};

const revoke = (device) => {
  Dialog.create({
    title: "Revoke this computer?",
    message: `The director app on "${device.device_name}" will be signed out and need a new setup code to reconnect. ${props.user.name}'s website login isn't affected.`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      const response = await callApi({
        path: `/users/${props.user.id}/app-devices/${device.id}`,
        method: "delete",
        useAuth: true,
      });
      if (!response) return;
      devices.value = devices.value.filter((d) => d.id !== device.id);
      Notify.create({ type: "positive", message: "Computer revoked" });
    } catch (error) {
      Notify.create({ type: "negative", message: errorMessage(error) });
    }
  });
};
</script>
