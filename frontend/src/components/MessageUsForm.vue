<template>
  <div>
    <div>
      <q-card bordered class="q-mx-md">
        <q-form @submit.prevent="onSubmit">
          <q-card-section class="q-gutter-y-sm">
            <q-input
              type="email"
              label="Your Email"
              stack-label
              dense
              outlined
              v-model="form.email"
              :rules="[(v) => !!v || 'Required']"
            ></q-input>
            <div class="text-subtitle2">Your Message</div>
            <q-editor v-model="form.body"></q-editor>
          </q-card-section>

          <q-card-actions class="flex justify-end">
            <q-btn
              type="submit"
              label="Send"
              color="primary"
              :loading="loading"
            ></q-btn>
          </q-card-actions>
        </q-form>
      </q-card>
    </div>
  </div>
</template>

<script setup>
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import notifyNetworkError from "src/assets/notify-network-error";
import { ref } from "vue";

const loading = ref(false);

const form = ref({
  email: null,
  body: "",
});

const onSubmit = async () => {
  loading.value = true;

  try {
    const response = await callApi({
      path: "/message-us",
      method: "post",
      payload: {
        email: form.value.email,
        body: form.value.body,
      },
    });

    if (response.status == "success") {
      form.value.body = "";
      Notify.create({
        type: "positive",
        position: "center",
        message:
          "<div class='text-center'><div class='text-h6'>Message sent.</div><div class='q-mt-sm'>Thanks for reaching out.<br />We will respond promptly.</div></div>",
        html: true,
        icon: false,
      });
    }
  } catch (error) {
    notifyNetworkError("Something went wrong sending your message.");
  } finally {
    loading.value = false;
  }
};
</script>
