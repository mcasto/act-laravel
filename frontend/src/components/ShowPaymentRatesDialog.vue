<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    @show="load"
  >
    <q-card style="width: 520px; max-width: 95vw;">
      <q-card-section class="row items-center bg-secondary text-white">
        <div class="text-h6">Revenue Multipliers</div>
        <q-space />
        <q-btn :icon="matClose" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section class="text-caption text-grey-7 q-pb-none">
        {{ show?.name }}'s own multipliers for the Per Show payment methods,
        copied from the global ones when the show was created (or when the
        method was made Per Show). Projected revenue for this show uses these,
        so changing a global multiplier later doesn't change this show. Other
        methods use their global multiplier.
      </q-card-section>

      <q-card-section>
        <div v-if="loading" class="text-center q-pa-md">
          <q-spinner size="md" color="primary" />
        </div>
        <div v-else-if="rates.length === 0" class="text-grey-7 q-pa-md text-center">
          No Per Show payment methods yet. Turn on Per Show for a method on
          the Payment Methods page.
        </div>
        <q-markup-table v-else flat dense separator="horizontal">
          <thead>
            <tr>
              <th class="text-left">Payment Method</th>
              <th class="text-center">This Show</th>
              <th class="text-center">Global</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="rate in rates" :key="rate.payment_method_id">
              <td>{{ rate.label }}</td>
              <td class="text-center">
                <q-input
                  :model-value="rate.revenue_multiplier"
                  type="number"
                  min="0"
                  max="2"
                  step="0.01"
                  dense
                  borderless
                  input-class="text-center"
                  style="width: 90px; display: inline-block;"
                  :class="{
                    'text-weight-bold':
                      rate.revenue_multiplier !== rate.global_multiplier,
                  }"
                  :disable="isReadOnly"
                  debounce="600"
                  @update:model-value="(value) => save(rate, value)"
                />
              </td>
              <td class="text-center text-grey-7">
                {{ rate.global_multiplier ?? "—" }}
              </td>
            </tr>
          </tbody>
        </q-markup-table>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { matClose } from "@quasar/extras/material-icons";
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import getPermissionLevel from "src/assets/get-permission-level";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";

const props = defineProps({
  modelValue: Boolean,
  show: Object,
});
const emit = defineEmits(["update:modelValue"]);

const store = useStore();

const isReadOnly = computed(
  () => getPermissionLevel(store.admin.user, "shows") === "read-only",
);

const loading = ref(false);
const rates = ref([]);

const load = async () => {
  if (!props.show?.id) return;
  loading.value = true;
  const response = await callApi({
    path: `/shows/${props.show.id}/payment-rates`,
    method: "get",
    useAuth: true,
  }).catch(() => null);
  rates.value = Array.isArray(response) ? response : [];
  loading.value = false;
};

// Saved at once; put back if the value isn't valid or the save fails.
const save = async (rate, value) => {
  const n = Number(value);
  if (value === "" || value == null || Number.isNaN(n) || n < 0 || n > 2) {
    Notify.create({
      type: "negative",
      message: "A multiplier must be between 0 and 2.",
    });
    return;
  }

  const previous = rate.revenue_multiplier;
  rate.revenue_multiplier = n;

  const response = await callApi({
    path: `/shows/${props.show.id}/payment-rates/${rate.payment_method_id}`,
    method: "put",
    payload: { revenue_multiplier: n },
    useAuth: true,
    showError: false,
  }).catch(() => null);

  if (!response || response.status !== "success") {
    rate.revenue_multiplier = previous;
    Notify.create({
      type: "negative",
      message: response?.message || "Couldn't save the multiplier.",
    });
  }
};
</script>
