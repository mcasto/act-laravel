<template>
  <q-dialog v-model="model.visible" full-width>
    <q-card>
      <q-form @submit.prevent="$emit('enroll')">
        <q-card-section v-if="store.course?.cost > 0">
          <div class="text-subtitle2 q-mb-sm">Payment</div>
          <q-select
            v-model="selectedPayment"
            label="How would you like to pay?"
            :options="paymentOptions"
            option-label="label"
            option-value="value"
            stack-label
            dense
            outlined
            required
          ></q-select>
        </q-card-section>

        <q-card-section v-if="selectedPayment?.value === 'fixr'">
          <purchase-credit :id="store.course.fixr"></purchase-credit>
        </q-card-section>

        <template v-else>
          <q-card-section>
            <div class="row">
              <div class="col-12 col-sm-6 q-pa-xs">
                <q-input
                  type="text"
                  label="First Name"
                  v-model="model.first_name"
                  stack-label
                  dense
                  outlined
                  required
                ></q-input>
              </div>
              <div class="col-12 col-sm-6 q-pa-xs">
                <q-input
                  type="text"
                  label="Last Name"
                  v-model="model.last_name"
                  stack-label
                  dense
                  outlined
                  required
                ></q-input>
              </div>
              <div class="col-12 col-sm-6 q-pa-xs">
                <q-input
                  type="email"
                  label="Email"
                  v-model="model.email"
                  stack-label
                  dense
                  outlined
                  required
                  :rules="[(v) => isValidEmail(v) || 'Invalid Email']"
                ></q-input>
              </div>
              <div class="col-12 col-sm-6 q-pa-xs">
                <q-input
                  type="tel"
                  label="Phone / Whatsapp"
                  v-model="model.phone"
                  stack-label
                  dense
                  outlined
                  required
                ></q-input>
              </div>

              <div v-if="selectedPayment?.value === 'transfer'" class="col-12 col-sm-6 q-pa-xs">
                <q-input
                  type="date"
                  label="Transfer Date"
                  v-model="model.transfer_date"
                  stack-label
                  dense
                  outlined
                  required
                ></q-input>
              </div>

              <div v-if="selectedPayment?.popupText" class="col-12 q-pa-xs" v-html="selectedPayment.popupText"></div>

              <div class="col-12 q-pa-xs">
                <div class="text-subtitle2">Specific Questions?</div>
                <q-editor v-model="model.questions"></q-editor>
              </div>
            </div>
          </q-card-section>
          <q-card-actions class="justify-end q-mr-md">
            <q-btn label="Cancel" color="negative" v-close-popup></q-btn>
            <q-btn
              label="Continue"
              color="positive"
              type="submit"
              :disable="requiresPayment && !selectedPayment"
            ></q-btn>
          </q-card-actions>
        </template>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { isValidEmail } from "@shelf/is-valid-email-address";
import { useStore } from "src/stores/store";
import PurchaseCredit from "src/components/PurchaseCredit.vue";
import { computed, ref, watch } from "vue";

const model = defineModel();
const emits = defineEmits(["enroll"]);

const store = useStore();

const requiresPayment = computed(() => store.course?.cost > 0);

// Same standard_buttons-driven list as ticket purchases (PurchaseTickets.vue's
// paymentMethods computed) — paypal/transfer come from the course payload,
// FixR is a synthetic entry added only when this course has a link configured.
const paymentOptions = computed(() => {
  if (!store.course) return [];

  const options = (store.course.buttons ?? []).map((button) => ({
    label: button.label,
    value: button.key,
    popupText: button.popupText,
  }));

  if (store.course.fixr) {
    options.push({ label: store.course.fixrLabel, value: "fixr" });
  }

  return options;
});

const selectedPayment = ref(null);

// Keep the submitted payload in sync with whatever's picked, without the
// parent (CourseInfo.vue) needing to know anything about payment options.
watch(selectedPayment, (option) => {
  model.value.payment_method_value = option?.value === "fixr" ? null : option?.value ?? null;
  if (option?.value !== "transfer") {
    model.value.transfer_date = null;
  }
});

// Reset the payment selection each time the dialog opens fresh.
watch(
  () => model.value.visible,
  (visible) => {
    if (visible) selectedPayment.value = null;
  },
);
</script>
