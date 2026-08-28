<template>
  <div class="q-pa-md">
    <div class="row items-center justify-between q-mb-md">
      <div class="text-h6">Payment Methods</div>
      <q-btn
        color="primary"
        :icon="matAdd"
        label="Add Payment Method"
        @click="openDialog()"
      />
    </div>

    <q-table
      :rows="store.admin.payment_methods"
      :columns="columns"
      row-key="id"
      dense
      :pagination="{ rowsPerPage: 0 }"
      hide-bottom
    >
      <template #body-cell-color="props">
        <q-td :props="props" class="text-center">
          <div
            class="inline-block"
            :style="`width: 16px; height: 16px; border-radius: 50%; margin: auto; background: ${props.row.color || 'transparent'}; border: 1px solid #ccc;`"
          ></div>
        </q-td>
      </template>

      <template #body-cell-revenue_multiplier="props">
        <q-td :props="props" class="text-center">
          {{ props.row.revenue_multiplier ?? "—" }}
        </q-td>
      </template>

      <template #body-cell-actions="props">
        <q-td :props="props" class="text-right">
          <q-btn
            :icon="matEdit"
            flat
            round
            dense
            size="sm"
            color="primary"
            @click="openDialog(props.row)"
          />
          <q-btn
            :icon="matDelete"
            flat
            round
            dense
            size="sm"
            color="negative"
            @click="deleteMethod(props.row)"
          />
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="dialog" persistent>
      <q-card style="min-width: 400px;">
        <q-card-section>
          <div class="text-h6">
            {{ form.id ? "Edit" : "Add" }} Payment Method
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input
            v-model="form.label"
            label="Label"
            outlined
            dense
            class="q-mb-md"
          />

          <q-input
            v-model="form.value"
            label="Value (internal key)"
            outlined
            dense
            class="q-mb-md"
            :disable="!!form.id"
            :hint="form.id ? 'Can\'t be changed — other parts of the app look sales up by this key.' : 'A short, unique, lowercase key, e.g. paypal.'"
          />

          <div class="q-mb-md">
            <div class="text-caption text-grey-7 q-mb-xs">Color</div>
            <q-input v-model="form.color" outlined dense readonly>
              <template #prepend>
                <div
                  class="cursor-pointer"
                  :style="`width: 20px; height: 20px; border-radius: 50%; background: ${form.color || 'transparent'}; border: 1px solid #ccc;`"
                >
                  <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                    <q-color v-model="form.color" />
                  </q-popup-proxy>
                </div>
              </template>
              <template #append>
                <q-btn
                  v-if="form.color"
                  :icon="matClose"
                  flat
                  round
                  dense
                  size="sm"
                  @click="form.color = null"
                />
              </template>
            </q-input>
          </div>

          <q-input
            v-model.number="form.revenue_multiplier"
            label="Revenue Multiplier"
            type="number"
            step="0.01"
            min="0"
            max="2"
            outlined
            dense
            class="q-mb-md"
            hint="Used in revenue projections — e.g. 0.92 to account for a processing fee. Leave blank for full face value."
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn
            flat
            label="Save"
            color="primary"
            @click="save"
            :disable="!form.label || !form.value"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import {
  matAdd,
  matClose,
  matDelete,
  matEdit,
} from "@quasar/extras/material-icons";
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { ref } from "vue";

const store = useStore();

const dialog = ref(false);
const form = ref({
  id: null,
  label: "",
  value: "",
  color: null,
  revenue_multiplier: null,
});

const columns = [
  { name: "color", label: "", field: "color", align: "center", style: "width: 40px;" },
  { name: "label", label: "Label", field: "label", align: "left" },
  { name: "value", label: "Value", field: "value", align: "left" },
  {
    name: "revenue_multiplier",
    label: "Revenue Multiplier",
    field: "revenue_multiplier",
    align: "center",
  },
  { name: "actions", label: "", field: "", align: "right" },
];

const openDialog = (method = null) => {
  form.value = method
    ? { ...method }
    : {
        id: null,
        label: "",
        value: "",
        color: null,
        revenue_multiplier: null,
      };
  dialog.value = true;
};

const reload = async () => {
  store.admin.payment_methods = await callApi({
    path: "/payment-methods",
    method: "get",
    useAuth: true,
  });
};

const save = async () => {
  const isEdit = !!form.value.id;
  const response = await callApi({
    path: isEdit ? `/payment-methods/${form.value.id}` : "/payment-methods",
    method: isEdit ? "put" : "post",
    payload: form.value,
    useAuth: true,
  });

  if (!response || response.status !== "success") {
    Notify.create({
      type: "negative",
      message: response?.message || "Something went wrong.",
    });
    return;
  }

  Notify.create({
    type: "positive",
    message: `Payment method ${isEdit ? "updated" : "created"}.`,
  });
  dialog.value = false;
  await reload();
};

const deleteMethod = (method) => {
  Notify.create({
    type: "warning",
    position: "center",
    message: `Delete "${method.label}"? This can't be undone, and any past ticket sales recorded under it will lose their payment-method label.`,
    actions: [
      { label: "No" },
      {
        label: "Yes",
        handler: async () => {
          const response = await callApi({
            path: `/payment-methods/${method.id}`,
            method: "delete",
            useAuth: true,
          });

          if (!response || response.status !== "success") {
            Notify.create({
              type: "negative",
              message: response?.message || "Delete failed.",
            });
            return;
          }

          await reload();
        },
      },
    ],
  });
};
</script>
