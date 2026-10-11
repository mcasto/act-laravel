<template>
  <q-page class="flex flex-center">
    <div class="q-pa-xl" style="max-width: 560px">
      <div v-if="!loaded" class="text-center">
        <q-spinner color="primary" size="48px" />
      </div>

      <div v-else-if="!request" class="text-center">
        <q-icon :name="matErrorOutline" color="negative" size="64px" />
        <h5 class="q-mt-md q-mb-sm">Link Not Found</h5>
        <p class="text-body1">
          This confirmation link isn't valid. Please check that you copied the
          whole link from your email.
        </p>
      </div>

      <div v-else-if="request.status === 'pending'">
        <h5 class="q-mt-none q-mb-md">Confirm Your Flex Tickets</h5>
        <p class="text-body1">Hello {{ request.first_name }},</p>
        <p class="text-body1">
          Please confirm that you'd like to use
          <strong>{{ request.quantity }}</strong> of your Flex
          {{ request.quantity === 1 ? "ticket" : "tickets" }} for:
        </p>
        <q-card flat bordered class="q-mb-md">
          <q-card-section>
            <div class="text-h6">{{ request.show_name }}</div>
            <div class="text-subtitle1">
              {{ request.performance_date }} at {{ request.performance_time }}
            </div>
          </q-card-section>
        </q-card>
        <p class="text-caption text-grey">
          You currently have {{ request.remaining }} Flex
          {{ request.remaining === 1 ? "ticket" : "tickets" }} remaining.
        </p>
        <div class="flex justify-between q-mt-lg">
          <q-btn
            flat
            color="negative"
            label="This wasn't me"
            no-caps
            :loading="cancelling"
            :disable="confirming"
            @click="onCancel"
          />
          <q-btn
            color="primary"
            label="Confirm"
            size="lg"
            :loading="confirming"
            :disable="cancelling"
            @click="onConfirm"
          />
        </div>
      </div>

      <div v-else class="text-center">
        <q-icon :name="statusIcon" :color="statusColor" size="64px" />
        <h5 class="q-mt-md q-mb-sm">{{ statusTitle }}</h5>
        <p class="text-body1">{{ statusMessage }}</p>
        <q-btn flat color="primary" label="Back to Home" to="/" class="q-mt-md" />
      </div>
    </div>
  </q-page>
</template>

<script setup>
import {
  matCancel,
  matCheckCircle,
  matErrorOutline,
  matSchedule,
} from "@quasar/extras/material-icons";
import callApi from "src/assets/call-api";
import notifySubmitError from "src/assets/notify-submit-error";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";
import { useRoute } from "vue-router";

const $route = useRoute();
const store = useStore();

const loaded = ref(false);
const request = ref(null);
const confirming = ref(false);
const cancelling = ref(false);

const statusIcon = computed(
  () =>
    ({
      confirmed: matCheckCircle,
      cancelled: matCancel,
      expired: matSchedule,
    })[request.value?.status],
);

const statusColor = computed(() =>
  request.value?.status === "confirmed" ? "positive" : "grey",
);

const statusTitle = computed(
  () =>
    ({
      confirmed: "Already Confirmed",
      cancelled: "Request Cancelled",
      expired: "Link Expired",
    })[request.value?.status],
);

const statusMessage = computed(
  () =>
    ({
      confirmed:
        "This request has already been confirmed — check your email for your ticket confirmation.",
      cancelled:
        "This request was cancelled. No Flex tickets were used.",
      expired:
        "This confirmation link has expired and no Flex tickets were used. Please submit your request again from the ticket purchase page.",
    })[request.value?.status],
);

// A 404 (bad/truncated link) leaves request null, which shows "Link Not
// Found". Loading only shows details — confirming always takes an explicit
// button click, so a mail scanner opening the link can't confirm it.
callApi({
  path: `/flex-redemptions/${$route.params.token}`,
  method: "get",
  showError: false,
})
  .then((response) => (request.value = response))
  .catch(() => (request.value = null))
  .finally(() => (loaded.value = true));

const onConfirm = async () => {
  confirming.value = true;

  try {
    const response = await callApi({
      path: `/flex-redemptions/${$route.params.token}/confirm`,
      method: "post",
    });

    if (response.transaction_id) {
      store.router.push({
        name: "ticket-confirmation",
        params: { uuid: response.transaction_id },
      });
    }
  } catch (error) {
    notifySubmitError(error, "Something went wrong confirming your request.");
  } finally {
    confirming.value = false;
  }
};

const onCancel = async () => {
  cancelling.value = true;

  try {
    request.value = await callApi({
      path: `/flex-redemptions/${$route.params.token}/cancel`,
      method: "post",
    });
  } catch (error) {
    notifySubmitError(error, "Something went wrong cancelling your request.");
  } finally {
    cancelling.value = false;
  }
};
</script>
