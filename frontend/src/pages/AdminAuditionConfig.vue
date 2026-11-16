<template>
  <div v-if="a">
    <q-form @submit.prevent="store.saveAuditionConfig" ref="formRef">
      <div class="row">
        <div class="col-12 text-center text-h6">
          Audition Config for {{ show.name }}
        </div>

        <div class="col-12 col-md-6 q-pa-md">
          <DateField v-model="a.display_date" label="Start Display" :disable="isReadOnly" />
        </div>
        <div class="col-12 col-md-6 q-pa-md">
          <DateField v-model="a.end_display_date" label="End Display" :disable="isReadOnly" />
        </div>

        <div class="col-12 q-px-md row items-center q-gutter-x-md">
          <q-btn
            :icon="matContentCopy"
            label="Copy preview link"
            color="primary"
            outline
            dense
            no-caps
            :disable="!previewUrl"
            @click="copyPreviewLink"
          />
          <div class="col text-caption text-grey-7">
            <a v-if="previewUrl" :href="previewUrl" target="_blank">{{ previewUrl }}</a>
            <div>
              Shows this show's saved audition page, whatever the display
              dates. Anyone with the link can see it.
            </div>
          </div>
          <div class="no-wrap">
            <q-btn label="Reset" @click="store.getAuditionConfig" color="negative" />
            <q-btn
              type="submit"
              label="Save"
              color="positive"
              class="q-ml-md text-black"
              :disable="isReadOnly"
            />
          </div>
        </div>

        <!-- 1. Show — read from the show itself -->
        <div class="col-12 q-pa-md">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-h6">Show</div>
              <div class="text-caption text-grey-7">
                From the show's own details — change them on the show, not here.
              </div>
            </q-card-section>
            <q-card-section class="row q-col-gutter-md">
              <div class="col-12 col-sm-3" v-if="posterUrl">
                <q-img :src="posterUrl" :alt="`${show.name} poster`" fit="contain" style="max-height: 260px" />
              </div>
              <div class="col">
                <div><b>Title:</b> {{ show.name }}</div>
                <div><b>Playwright:</b> {{ show.writer || "—" }}</div>
                <div><b>Director:</b> {{ show.director || "TBA" }}</div>
                <div class="q-mt-sm"><b>Synopsis:</b></div>
                <div class="text-body2" style="white-space: pre-line">
                  {{ synopsisText || "— none yet (Show › Info) —" }}
                </div>
              </div>
            </q-card-section>
            <q-card-section>
              <q-input
                v-model="a.what_to_expect"
                type="textarea"
                autogrow
                outlined
                label="What to expect at auditions (optional)"
                :disable="isReadOnly"
              />
            </q-card-section>
          </q-card>
        </div>

        <!-- 2. Auditions -->
        <div class="col-12 q-pa-md">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-h6">Auditions</div>
            </q-card-section>
            <q-card-section>
              <AuditionSessionList
                v-model="a.sessions"
                noun="Session"
                note-placeholder="Please plan to stay for the entire session"
                :disable="isReadOnly"
              />
            </q-card-section>
            <q-card-section>
              <div class="text-subtitle1">Callbacks (optional)</div>
              <AuditionSessionList v-model="a.callbacks" noun="Callback" :disable="isReadOnly" />
            </q-card-section>
            <q-card-section class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <q-input
                  v-model="a.registration_url"
                  label="Registration form URL"
                  hint="Leave blank if there's no sign-up form"
                  outlined
                  dense
                  :disable="isReadOnly"
                />
              </div>
              <div class="col-12 col-md-6">
                <q-input
                  v-model="a.script_url"
                  label="Script / excerpts URL"
                  outlined
                  dense
                  :disable="isReadOnly"
                />
              </div>
              <div class="col-12">
                <q-input
                  v-model="a.registration_note"
                  label="Registration note"
                  hint="Shown with the registration link"
                  outlined
                  dense
                  :disable="isReadOnly"
                />
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- 3. Location -->
        <div class="col-12 q-pa-md">
          <q-card flat bordered>
            <q-card-section class="row items-center">
              <div class="text-h6 col">Location</div>
              <q-btn
                v-if="a.location !== defaults.location"
                label="Use ACT's address"
                flat
                dense
                no-caps
                color="primary"
                :disable="isReadOnly"
                @click="a.location = defaults.location"
              />
            </q-card-section>
            <q-card-section>
              <q-input
                v-model="a.location"
                type="textarea"
                autogrow
                outlined
                hint="One line per line of the address; the first line is shown in bold"
                :disable="isReadOnly"
              />
            </q-card-section>
          </q-card>
        </div>

        <!-- 4. Roles -->
        <div class="col-12 q-pa-md">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-h6">Roles</div>
              <div class="text-caption text-grey-7">
                The heading ("{{ rolesHeading }}") is worked out from how many there are.
              </div>
            </q-card-section>
            <q-card-section>
              <div
                v-for="(role, i) in a.roles"
                :key="role.id ?? `new-${i}`"
                class="row q-col-gutter-sm q-mb-md items-start"
              >
                <div class="col-12 col-sm-3">
                  <q-input v-model="role.name" label="Name" outlined dense :disable="isReadOnly" />
                </div>
                <div class="col-12 col-sm-3">
                  <q-input
                    v-model="role.gender_age"
                    label="Gender & age"
                    placeholder="Woman, over 50"
                    outlined
                    dense
                    :disable="isReadOnly"
                  />
                </div>
                <div class="col">
                  <q-input
                    v-model="role.notes"
                    label="Description"
                    type="textarea"
                    autogrow
                    outlined
                    dense
                    :disable="isReadOnly"
                  />
                </div>
                <div class="col-auto column">
                  <q-btn flat round dense :icon="matArrowUpward" :disable="isReadOnly || i === 0" @click="move(i, -1)" />
                  <q-btn
                    flat
                    round
                    dense
                    :icon="matArrowDownward"
                    :disable="isReadOnly || i === a.roles.length - 1"
                    @click="move(i, 1)"
                  />
                  <q-btn flat round dense :icon="matDelete" color="negative" :disable="isReadOnly" @click="a.roles.splice(i, 1)" />
                </div>
              </div>
              <q-btn
                :icon="matAdd"
                label="Add role"
                color="primary"
                flat
                dense
                no-caps
                :disable="isReadOnly"
                @click="a.roles.push({ name: '', gender_age: '', notes: '' })"
              />
            </q-card-section>
          </q-card>
        </div>

        <!-- 5. Schedule -->
        <div class="col-12 q-pa-md">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-h6">Schedule</div>
            </q-card-section>
            <q-card-section class="row q-col-gutter-md items-start">
              <div class="col-12 col-sm-4 col-md-3">
                <DateField v-model="a.rehearsal_start_date" label="Rehearsals begin" :disable="isReadOnly" />
                <div class="text-caption text-grey-7 q-pl-sm">{{ weekday(a.rehearsal_start_date) }}</div>
              </div>
              <div class="col-auto">
                <TimeSelect v-model="a.rehearsal_start_time" label="At" :disable="isReadOnly" />
              </div>
              <div class="col-12">
                <q-input
                  v-model="a.rehearsal_schedule"
                  type="textarea"
                  autogrow
                  outlined
                  label="Rehearsal schedule"
                  placeholder="December 15, 17, 21, 22, 28 and 29 for 2–3 hours; January three to four days per week, TBA"
                  :disable="isReadOnly"
                />
              </div>
              <div class="col-12">
                <b>Performance dates:</b>
                {{ defaults.performance_dates || "— no performances on the show yet —" }}
                <div class="text-caption text-grey-7">From the show's performances.</div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- 6. Contact -->
        <div class="col-12 q-pa-md">
          <q-card flat bordered>
            <q-card-section class="row items-center">
              <div class="col">
                <div class="text-h6">Contact</div>
                <div class="text-caption text-grey-7">
                  <template v-if="a.contacts === null">
                    From the show's production team (director's user account and patron record).
                  </template>
                  <template v-else>Edited for this audition.</template>
                </div>
              </div>
              <q-btn
                v-if="a.contacts === null"
                label="Edit"
                :icon="matEdit"
                flat
                dense
                no-caps
                color="primary"
                :disable="isReadOnly"
                @click="editContacts"
              />
              <q-btn
                v-else
                label="Reset to show's director"
                flat
                dense
                no-caps
                color="primary"
                :disable="isReadOnly"
                @click="a.contacts = null"
              />
            </q-card-section>

            <q-card-section v-if="a.contacts === null">
              <div v-if="!defaults.contacts?.length" class="text-grey-7">
                No director on the show's production team yet — no contact will be shown.
              </div>
              <div v-for="(c, i) in defaults.contacts" :key="i" class="q-mb-sm">
                <b>{{ c.name }}</b><span v-if="c.title">, {{ c.title }}</span><br />
                WhatsApp/phone: {{ c.phone || "— not on their patron record —" }}<br />
                Email: {{ c.email || "—" }}
              </div>
            </q-card-section>

            <q-card-section v-else>
              <div v-for="(c, i) in a.contacts" :key="i" class="row q-col-gutter-sm q-mb-sm items-center">
                <div class="col-12 col-sm-3"><q-input v-model="c.name" label="Name" outlined dense :disable="isReadOnly" /></div>
                <div class="col-12 col-sm-2"><q-input v-model="c.title" label="Title" outlined dense :disable="isReadOnly" /></div>
                <div class="col-12 col-sm-3"><q-input v-model="c.phone" label="WhatsApp / phone" outlined dense :disable="isReadOnly" /></div>
                <div class="col"><q-input v-model="c.email" label="Email" type="email" outlined dense :disable="isReadOnly" /></div>
                <div class="col-auto">
                  <q-btn flat round dense :icon="matDelete" color="negative" :disable="isReadOnly" @click="a.contacts.splice(i, 1)" />
                </div>
              </div>
              <q-btn
                :icon="matAdd"
                label="Add contact"
                color="primary"
                flat
                dense
                no-caps
                :disable="isReadOnly"
                @click="a.contacts.push({ name: '', title: '', phone: '', email: '' })"
              />
            </q-card-section>
          </q-card>
        </div>

        <!-- Page HTML -->
        <div class="col-12 q-pa-md">
          <div class="row items-center q-gutter-sm">
            <div class="text-h6 col">Audition Page</div>
            <q-btn
              :icon="matAutoAwesome"
              label="Build with AI"
              color="primary"
              unelevated
              no-caps
              :loading="generating === 'ai'"
              :disable="isReadOnly || !!generating || !aiAvailable"
              @click="generate('ai')"
            >
              <q-tooltip v-if="!aiAvailable">GEMINI_API_KEY isn't set on the server</q-tooltip>
            </q-btn>
            <q-btn
              label="Build without AI"
              color="primary"
              outline
              no-caps
              :loading="generating === 'plain'"
              :disable="isReadOnly || !!generating"
              @click="generate('plain')"
            />
          </div>
          <div class="text-caption text-grey-7 q-mb-sm">
            <b>Build with AI</b> sends the details above and the poster to Google Gemini, which
            designs the page from the poster's colors. It usually takes about a minute, and longer
            when Google is busy (it retries on its own). <b>Build without AI</b> makes a plain
            version instantly. Either one replaces what's in the editor below; nothing is saved
            until you click Save.
          </div>
          <q-banner v-if="generating === 'ai'" dense class="bg-blue-1 q-mb-sm">
            <template #avatar><q-spinner color="primary" /></template>
            Asking Gemini… {{ elapsed }}s. You can keep editing below, but changes made now
            won't be in this version.
          </q-banner>

          <q-editor
            v-model="a.html"
            :toolbar="auditionToolbar"
            :fonts="auditionFonts"
            max-height="35vh"
            :disable="isReadOnly"
          />
        </div>
      </div>
      <div class="flex justify-end q-pa-md">
        <q-btn label="Reset" @click="store.getAuditionConfig" color="negative" />
        <q-btn type="submit" label="Save" color="positive" class="q-ml-md text-black" :disable="isReadOnly" />
      </div>
    </q-form>
  </div>
</template>

<script setup>
import {
  matAdd,
  matArrowDownward,
  matArrowUpward,
  matAutoAwesome,
  matContentCopy,
  matDelete,
  matEdit,
} from "@quasar/extras/material-icons";
import { Dialog, Notify } from "quasar";
import { cloneDeep } from "lodash-es";
import callApi from "src/assets/call-api";
import { auditionFonts, auditionToolbar, posterFor } from "src/assets/audition-editor";
import { toPayload, weekday } from "src/assets/audition-form";
import getPermissionLevel from "src/assets/get-permission-level";
import AuditionSessionList from "src/components/AuditionSessionList.vue";
import DateField from "src/components/DateField.vue";
import TimeSelect from "src/components/TimeSelect.vue";
import { useStore } from "src/stores/store";
import { computed, onBeforeUnmount, ref } from "vue";

const store = useStore();

const a = computed(() => store.admin.audition);
const show = computed(() => store.admin.show || {});
const defaults = computed(() => store.admin.auditionDefaults || {});
const aiAvailable = computed(() => !!store.admin.auditionAiAvailable);

const isReadOnly = computed(() => getPermissionLevel(store.admin.user, "auditions") === "read-only");

const formRef = ref(null);

const posterUrl = computed(() => posterFor(show.value.poster).url);

// shows.info may be plain text or simple HTML.
const synopsisText = computed(() => {
  const div = document.createElement("div");
  div.innerHTML = (show.value.info || "").replace(/<br\s*\/?>|<\/p>/gi, "\n");
  return div.textContent.trim();
});

const NUMBER_WORDS = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve"];
const rolesHeading = computed(() => {
  const n = a.value.roles.filter((r) => r.name).length || a.value.roles.length;
  return n ? `${NUMBER_WORDS[n] ?? n} ${n === 1 ? "Role" : "Roles"}` : "Four Roles";
});

const move = (i, dir) => {
  const roles = a.value.roles;
  [roles[i], roles[i + dir]] = [roles[i + dir], roles[i]];
};

const editContacts = () => {
  a.value.contacts = cloneDeep(defaults.value.contacts || []);
  if (!a.value.contacts.length) {
    a.value.contacts.push({ name: show.value.director || "", title: "Director", phone: "", email: "" });
  }
};

const previewUrl = computed(() =>
  store.admin.auditionPreviewUid
    ? `${window.location.origin}/audition/preview/${store.admin.auditionPreviewUid}`
    : "",
);

const copyPreviewLink = async () => {
  try {
    await navigator.clipboard.writeText(previewUrl.value);
    Notify.create({ type: "positive", message: "Preview link copied" });
  } catch {
    Notify.create({ type: "negative", message: "Couldn't copy to the clipboard in this browser." });
  }
};

// ---- Generating the page HTML ----

const generating = ref(null); // 'ai' | 'plain' | null
const elapsed = ref(0);
let timer = null;
let cancelled = false;

onBeforeUnmount(() => {
  cancelled = true;
  clearInterval(timer);
});

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

const confirmReplace = () =>
  new Promise((resolve) => {
    if (!a.value.html?.trim()) return resolve(true);
    Dialog.create({
      title: "Replace the page?",
      message: "This replaces the audition page currently in the editor (the saved one changes only when you Save).",
      cancel: true,
      persistent: true,
    })
      .onOk(() => resolve(true))
      .onCancel(() => resolve(false));
  });

const showError = (res) => {
  const busy = res?.code === "busy";
  Notify.create({
    type: busy ? "warning" : "negative",
    message: res?.message || "Couldn't build the page. Try again.",
    timeout: busy ? 15000 : 8000,
    actions: busy
      ? [
          { label: "Build without AI", color: "black", handler: () => generate("plain") },
          { label: "Dismiss", color: "black" },
        ]
      : [{ label: "Dismiss", color: "white" }],
  });
};

const applyHtml = (html, engine) => {
  a.value.html = html;
  Notify.create({
    type: "positive",
    message:
      engine === "ai"
        ? "Gemini's page is in the editor. Check it over (preview link shows it after Save), then Save."
        : "Plain page is in the editor. Check it over, then Save.",
  });
};

const generate = async (engine) => {
  if (generating.value) return;
  if (!(await confirmReplace())) return;

  generating.value = engine;
  elapsed.value = 0;
  clearInterval(timer);
  timer = setInterval(() => elapsed.value++, 1000);

  try {
    let res = await callApi({
      path: "/audition/generate-html",
      method: "post",
      payload: { ...toPayload(a.value), engine },
      useAuth: true,
      showError: false,
    });

    // AI runs on the server after the reply; poll until it's done (Gemini
    // can take several minutes when it's overloaded and retrying).
    const token = res?.token;
    const started = Date.now();
    while (res?.status === "pending" && !cancelled) {
      if (Date.now() - started > 10 * 60 * 1000) {
        res = {
          status: "error",
          code: "busy",
          message: "Gemini hasn't answered after 10 minutes. Try again later, or use Build without AI.",
        };
        break;
      }
      await sleep(4000);
      try {
        res = await callApi({
          path: `/audition/generate-html/${token}`,
          method: "get",
          useAuth: true,
          showError: false,
        });
      } catch {
        // A dropped poll is fine — keep waiting.
        res = { status: "pending" };
      }
    }

    if (cancelled) return;

    if (res?.status === "success") {
      applyHtml(res.html, engine);
    } else {
      showError(res);
    }
  } catch (e) {
    showError({ message: e?.json?.message || "Couldn't reach the server. Try again." });
  } finally {
    generating.value = null;
    clearInterval(timer);
  }
};
</script>
