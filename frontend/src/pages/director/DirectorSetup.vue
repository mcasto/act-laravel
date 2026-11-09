<template>
  <q-page class="q-pa-md row justify-center">
    <div class="col-12" style="max-width: 460px">
      <div class="text-h5 q-mt-md q-mb-xs">Welcome</div>
      <div class="text-body2 text-grey-8 q-mb-lg">
        See ticket sales and audition details for the show you're working on.
        Enter the setup code the theater gave you to connect this device.
      </div>

      <!-- iPhone/iPad: an app added to the home screen has its own storage,
           so pairing in Safari first would leave the home-screen app
           unpaired with an already-used code. Install first. -->
      <q-card v-if="showIosInstallFirst" flat bordered class="q-mb-md">
        <q-card-section>
          <div class="text-subtitle1 text-weight-medium q-mb-sm">
            Add it to your Home Screen first
          </div>
          <ol class="q-pl-md q-my-none text-body2">
            <li>Tap the <b>Share</b> button in Safari's toolbar.</li>
            <li>Choose <b>Add to Home Screen</b>, then <b>Add</b>.</li>
            <li>Open <b>ACT Director</b> from your Home Screen and enter your code there.</li>
          </ol>
          <div class="text-caption text-grey-7 q-mt-sm">
            Your code only works once, so enter it in the Home Screen app, not here.
          </div>
        </q-card-section>
        <q-card-actions>
          <q-btn
            flat
            no-caps
            color="secondary"
            label="I'll just use it in Safari"
            @click="iosUseBrowser = true"
          />
        </q-card-actions>
      </q-card>

      <q-card v-else flat bordered>
        <q-card-section>
          <q-form @submit="submit" class="q-gutter-md">
            <q-input
              v-model="code"
              label="Setup code"
              outlined
              autofocus
              maxlength="8"
              autocapitalize="characters"
              autocomplete="one-time-code"
              input-class="text-h6 text-weight-bold"
              input-style="letter-spacing: 0.2em; text-transform: uppercase; font-family: monospace;"
              :rules="[(v) => (v || '').trim().length === 8 || 'Codes are 8 characters']"
              lazy-rules
            />
            <q-input
              v-model="deviceName"
              label="Name for this device"
              hint="Helps the theater tell your devices apart"
              outlined
              maxlength="100"
              :rules="[(v) => !!(v || '').trim() || 'Give this device a name']"
              lazy-rules
            />
            <q-banner v-if="error" dense rounded class="bg-red-1 text-negative">
              {{ error }}
            </q-banner>
            <q-btn
              type="submit"
              color="secondary"
              label="Connect"
              no-caps
              unelevated
              class="full-width"
              size="lg"
              :loading="submitting"
            />
          </q-form>
        </q-card-section>
      </q-card>

      <q-card v-if="installPrompt" flat bordered class="q-mt-md">
        <q-card-section class="row items-center no-wrap">
          <div class="col text-body2">
            Install ACT Director to open it like an app, without the browser bars.
          </div>
          <q-btn color="primary" no-caps unelevated label="Install" @click="install" />
        </q-card-section>
      </q-card>

      <div class="text-caption text-grey-7 q-mt-lg text-center">
        Don't have a code? Ask the theater's admin team for one. Codes expire
        15 minutes after they're made.
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { computed, inject, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { getToken, guessDeviceName, isIos, isStandalone, pair } from "src/director/api";

const router = useRouter();
const installPrompt = inject("directorInstallPrompt");
const install = inject("directorInstall");

const code = ref("");
const deviceName = ref(guessDeviceName() + (isStandalone() ? " (app)" : ""));
const submitting = ref(false);
const error = ref("");
const iosUseBrowser = ref(false);

const showIosInstallFirst = computed(() => isIos() && !isStandalone() && !iosUseBrowser.value);

onMounted(() => {
  if (getToken()) router.replace({ name: "director-home" });
});

const submit = async () => {
  submitting.value = true;
  error.value = "";
  try {
    await pair(code.value.toUpperCase(), deviceName.value);
    router.replace({ name: "director-home" });
  } catch (e) {
    error.value =
      e.status === 429
        ? "Too many attempts. Wait a minute and try again."
        : e.status === 422 && e.body?.errors
          ? "Check the code and device name."
          : e.message;
  } finally {
    submitting.value = false;
  }
};
</script>
