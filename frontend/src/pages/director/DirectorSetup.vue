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

      <!-- Only on this Setup screen, i.e. only while this device isn't
           connected yet. Covers both sides, since a new director is often
           setting this up together with an admin. -->
      <q-card flat bordered class="q-mt-lg">
        <q-expansion-item
          v-model="howToOpen"
          :icon="matHelpOutline"
          label="How setup works"
          caption="Don't have a code yet? Start here."
          header-class="text-weight-medium"
        >
          <q-separator />
          <q-card-section class="text-body2">
            <div class="text-subtitle2 q-mb-xs">1. The theater's admin gives you access</div>
            <div class="text-grey-8 q-mb-sm">
              Someone on the admin team with full access to Shows and Users does this
              once per person, on the website's admin pages:
            </div>
            <ol class="q-pl-md q-mt-none q-mb-md">
              <li>
                <b>Users:</b> if you don't have an account yet, they add you with the
                <b>+</b> button. You don't need any admin permissions, and the account
                doesn't give you access to the rest of the admin.
              </li>
              <li>
                <b>Shows &rarr; edit your show &rarr; the gear (&#9881;) menu &rarr;
                Production Team Access:</b> they click <b>Add person</b>, pick you, set
                your role (Director, Assistant Director, Stage Manager…) and check the
                start and end dates, then save. You'll see the show only between those
                dates.
              </li>
              <li>
                <b>Users &rarr; the devices icon on your row &rarr; Generate setup
                code:</b> they send you the 8-character code and this page's address,
                <b>{{ directorUrl }}</b>.
              </li>
            </ol>

            <div class="text-subtitle2 q-mb-xs">2. You connect this device</div>
            <ol class="q-pl-md q-mt-none q-mb-md">
              <li v-if="isIosDevice">
                On an iPhone or iPad, first add this page to your Home Screen (Share
                &rarr; <b>Add to Home Screen</b>) and open it from there.
              </li>
              <li v-else>
                Optional: install it like an app with the <b>Install</b> button (or your
                browser's install / "Add to Home screen" option).
              </li>
              <li>
                Enter the code above and click <b>Connect</b>. Codes work once and expire
                15 minutes after they're made — if yours has expired, ask for a new one.
              </li>
              <li>
                That's it: this device stays connected. To connect another phone or
                computer, ask for another code.
              </li>
            </ol>

            <div class="text-caption text-grey-7">
              If you see "No active shows" after connecting, your access dates haven't
              started yet or have ended. The admin can change them under Production Team
              Access. Lost a device? The admin can disconnect it under Users &rarr; the
              devices icon &rarr; Revoke.
            </div>
          </q-card-section>
        </q-expansion-item>
      </q-card>
    </div>
  </q-page>
</template>

<script setup>
import { matHelpOutline } from "@quasar/extras/material-icons";
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

const isIosDevice = isIos();
const directorUrl = `${window.location.host}/director`;

// Open by default on a first visit; someone who's just come back to enter
// a code (e.g. reopening the installed app) gets the form without the wall
// of text — they can still expand it.
const howToOpen = ref(!isStandalone());

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
