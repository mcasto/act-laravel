<template>
  <q-dialog v-model="model" persistent>
    <q-card style="width: 700px; max-width: 95vw;">
      <q-card-section>
        <div class="text-h6">{{ enrollee ? "Email Enrollee" : "Email All Enrollees" }}</div>
        <div class="text-caption text-grey-7">{{ course?.name }}</div>
      </q-card-section>

      <q-card-section class="q-pt-none">
        <div class="q-mb-sm">
          <strong>To:</strong>
          <template v-if="enrollee">
            {{ enrollee.first_name }} {{ enrollee.last_name }} ({{ enrollee.email }})
          </template>
          <template v-else>
            {{ recipients.length }} enrollee{{ recipients.length === 1 ? "" : "s" }}
          </template>
        </div>

        <q-toggle
          v-if="!enrollee && unconfirmedCount > 0"
          v-model="includeUnconfirmed"
          :label="`Include unconfirmed enrollees (${unconfirmedCount})`"
          class="q-mb-sm"
        />

        <q-input
          v-model="subject"
          label="Subject"
          outlined
          dense
          class="q-mb-md"
        />

        <q-editor
          v-model="body"
          min-height="10rem"
          :toolbar="[
            ['bold', 'italic', 'underline', 'strike'],
            ['unordered', 'ordered'],
            ['link'],
            ['undo', 'redo'],
          ]"
        />

        <div class="text-caption text-grey-7 q-mt-sm">
          Each enrollee gets their own email, starting with "Hello [first name],".
          Replies go to the instructor, {{ course?.instructor_name }}
          ({{ course?.instructor_email }}).
        </div>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat label="Cancel" v-close-popup :disable="sending" />
        <q-btn
          flat
          color="primary"
          :label="sendLabel"
          :loading="sending"
          :disable="!canSend"
          @click="send"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { computed, ref, watch } from "vue";

const model = defineModel();
// enrollee set = email just that person; null = email the whole class.
const props = defineProps(["course", "enrollee"]);

const subject = ref("");
const body = ref("");
const includeUnconfirmed = ref(true);
const sending = ref(false);

watch(model, (visible) => {
  if (!visible) return;
  subject.value = props.course?.name ?? "";
  body.value = "";
  includeUnconfirmed.value = true;
});

const unconfirmedCount = computed(
  () => (props.course?.contacts ?? []).filter((c) => !c.confirmed).length,
);

const recipients = computed(() => {
  if (props.enrollee) return [props.enrollee];
  return (props.course?.contacts ?? []).filter(
    (c) => includeUnconfirmed.value || c.confirmed,
  );
});

// q-editor leaves markup like "<br>" behind when cleared.
const bodyHasText = computed(
  () => body.value.replace(/<[^>]*>|&nbsp;/g, "").trim() !== "",
);

const canSend = computed(
  () => recipients.value.length > 0 && !!subject.value.trim() && bodyHasText.value,
);

const sendLabel = computed(() =>
  props.enrollee
    ? "Send"
    : `Send to ${recipients.value.length}`,
);

const send = async () => {
  sending.value = true;

  const response = await callApi({
    path: `/admin/courses/${props.course.id}/email`,
    method: "post",
    payload: {
      contact_ids: recipients.value.map((c) => c.id),
      subject: subject.value.trim(),
      body: body.value,
    },
    useAuth: true,
  }).catch(() => null);

  sending.value = false;

  // A status "error" response is shown by callApi itself.
  if (response === false) return;

  if (!response || response.status !== "success") {
    Notify.create({ type: "negative", message: "Failed to send email." });
    return;
  }

  if (response.failed?.length) {
    Notify.create({
      type: "warning",
      message: `Sent ${response.sent}. Failed: ${response.failed.join(", ")}`,
      timeout: 0,
      actions: [{ label: "OK", color: "white" }],
    });
  } else {
    Notify.create({
      type: "positive",
      message: `Email sent to ${response.sent} enrollee${response.sent === 1 ? "" : "s"}.`,
    });
  }

  model.value = false;
};
</script>
