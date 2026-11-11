<template>
  <div class="q-pa-md">
    <div class="row items-center justify-between q-mb-md">
      <div class="text-h6">Payment Methods</div>
      <q-btn
        color="primary"
        :icon="matAdd"
        label="Add Payment Method"
        :disable="isReadOnly"
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

      <template #body-cell-per_show="props">
        <q-td :props="props" class="text-center">
          <q-toggle
            :model-value="!!props.row.per_show"
            dense
            :disable="isReadOnly"
            @update:model-value="(value) => updatePerShow(props.row, value)"
          />
        </q-td>
      </template>

      <template #header-cell-per_show="props">
        <q-th :props="props">
          Per Show
          <q-tooltip max-width="320px">
            Each show keeps its own copy of this method's revenue multiplier,
            so changing it here doesn't change past shows' revenue. Turning
            this on copies the current multiplier to every show that doesn't
            have one yet; new shows get a copy when they're created. A show's
            copy is never changed from here — edit it on the show (gear menu
            → Revenue Multipliers).
          </q-tooltip>
        </q-th>
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
            :disable="isReadOnly"
            @click="openDialog(props.row)"
          />
          <q-btn
            :icon="matDelete"
            flat
            round
            dense
            size="sm"
            color="negative"
            :disable="isReadOnly"
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
            :disable="isReadOnly || !form.label || !form.value"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-separator class="q-my-xl" />

    <div class="text-h6">Patron Purchase Options</div>
    <div class="text-caption text-grey-7 q-mb-md">
      The choices patrons see when buying tickets, Flex packages, classes,
      Angel donations and Support Us. "Pay with Credit / Debit" is always
      shown first and isn't listed here.
    </div>

    <q-table
      :rows="buttons ?? []"
      :columns="buttonColumns"
      row-key="id"
      dense
      :pagination="{ rowsPerPage: 0, sortBy: 'sort_order' }"
      :loading="buttons === null"
      hide-bottom
    >
      <template #body-cell-display_to_patrons="props">
        <q-td :props="props" class="text-center">
          <q-toggle
            :model-value="props.row.display_to_patrons"
            :disable="isReadOnly"
            dense
            @update:model-value="(value) => toggleDisplay(props.row, value)"
          />
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
            :disable="isReadOnly"
            @click="openButtonDialog(props.row)"
          />
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="buttonDialog" persistent>
      <q-card style="width: 800px; max-width: 95vw;">
        <q-card-section>
          <div class="text-h6">Edit Patron Purchase Option</div>
        </q-card-section>

        <q-card-section v-if="buttonForm" class="q-pt-none">
          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-8">
              <q-input v-model="buttonForm.label" label="Label" dense outlined />
            </div>
            <div class="col-12 col-sm-4">
              <q-input
                v-model.number="buttonForm.sort_order"
                type="number"
                label="Sort Order"
                dense
                outlined
              />
            </div>
            <div class="col-12">
              <q-toggle
                v-model="buttonForm.display_to_patrons"
                label="Display to patrons"
              />
            </div>
            <div class="col-12">
              <div class="flex items-center q-gutter-x-sm q-mb-xs">
                <span class="text-caption text-grey-7">Template for Display on Site</span>
                <q-badge color="grey-3" text-color="grey-8" class="text-caption">
                  use the <q-icon :name="mdiCodeBraces" size="14px" /> toolbar button to
                  insert available placeholders
                </q-badge>
              </div>
              <q-editor
                v-model="buttonForm.template"
                min-height="5rem"
                :toolbar="templateToolbar"
                :definitions="templateDefinitions"
                @paste="handlePlainTextPaste"
              />
            </div>
            <div
              class="col-12 text-caption text-grey-7"
              v-if="!['flex', 'questions'].includes(buttonForm.key)"
            >
              The confirmation email for this option is edited on
              <router-link to="/admin/email-templates">Email Templates</router-link>.
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn
            flat
            label="Save"
            color="primary"
            @click="saveButton"
            :disable="isReadOnly || !buttonForm?.label || !buttonForm?.template"
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
import { mdiCodeBraces } from "@quasar/extras/mdi-v7";
import { Notify } from "quasar";
import {
  handlePlainTextPaste,
  templateDefinitions,
  templateToolbar,
} from "src/assets/button-template-editor";
import callApi from "src/assets/call-api";
import getPermissionLevel from "src/assets/get-permission-level";
import { useStore } from "src/stores/store";
import { computed, onMounted, ref } from "vue";

const store = useStore();

const isReadOnly = computed(
  () => getPermissionLevel(store.admin.user, "payment-methods") === "read-only",
);

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
  { name: "per_show", label: "Per Show", field: "per_show", align: "center" },
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

// Saved at once; put back if the save fails.
const updatePerShow = async (method, value) => {
  const previous = method.per_show;
  method.per_show = value;

  const response = await callApi({
    path: `/payment-methods/${method.id}/per-show`,
    method: "put",
    payload: { per_show: value },
    useAuth: true,
    showError: false,
  }).catch(() => null);

  if (!response || response.status !== "success") {
    method.per_show = previous;
    Notify.create({
      type: "negative",
      message: response?.message || "Couldn't update Per Show.",
    });
    return;
  }

  if (value) {
    const n = response.shows_added;
    Notify.create({
      type: "positive",
      message: n
        ? `${method.label}'s multiplier (${method.revenue_multiplier ?? "none"}) copied to ${n} show${n === 1 ? "" : "s"}.`
        : `Every show already has its own ${method.label} multiplier.`,
    });
  }
};

const deleteMethod = (method) => {
  Notify.create({
    type: "warning",
    position: "center",
    message: `Delete "${method.label}"? This can't be undone. Methods already recorded on sales, Angels, Flex purchases or enrollments can't be deleted.`,
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

// Patron purchase options (standard_buttons)

const buttons = ref(null);
const buttonDialog = ref(false);
const buttonForm = ref(null);

const buttonColumns = [
  { name: "label", label: "Label", field: "label", align: "left" },
  { name: "sort_order", label: "Sort Order", field: "sort_order", align: "center", sortable: true },
  {
    name: "display_to_patrons",
    label: "Display to Patrons",
    field: "display_to_patrons",
    align: "center",
  },
  { name: "actions", label: "", field: "", align: "right" },
];

const loadButtons = async () => {
  const response = await callApi({
    path: "/standard-buttons",
    method: "get",
    useAuth: true,
  });

  buttons.value = Array.isArray(response) ? response : [];
};

const openButtonDialog = (button) => {
  buttonForm.value = { ...button };
  buttonDialog.value = true;
};

const saveButton = async () => {
  const response = await callApi({
    path: `/standard-buttons/${buttonForm.value.id}`,
    method: "put",
    payload: buttonForm.value,
    useAuth: true,
  });

  if (!response || response.status !== "success") {
    Notify.create({
      type: "negative",
      message: response?.message || "Something went wrong.",
    });
    return;
  }

  Notify.create({ type: "positive", message: "Purchase option updated." });
  buttonDialog.value = false;
  await loadButtons();
};

const toggleDisplay = async (button, value) => {
  const previous = button.display_to_patrons;
  button.display_to_patrons = value;

  const response = await callApi({
    path: `/standard-buttons/${button.id}/display`,
    method: "put",
    payload: { display_to_patrons: value },
    useAuth: true,
  });

  if (!response || response.status !== "success") {
    button.display_to_patrons = previous;
    Notify.create({
      type: "negative",
      message: response?.message || "Couldn't update that option.",
    });
    return;
  }

  Notify.create({
    type: "positive",
    message: `"${button.label}" is now ${value ? "shown to" : "hidden from"} patrons.`,
  });
};

onMounted(loadButtons);
</script>
