<template>
  <q-dialog v-model="model" full-width>
    <q-card>
      <q-card-actions class="justify-between">
        <div>
          Enrollees
          <span v-if="course?.max_participants" class="text-grey-7">
            ({{ course.contacts.length }} of {{ course.max_participants }})
          </span>
        </div>
        <div>
          <q-btn
            color="primary"
            :icon="matAdd"
            label="Add Enrollee"
            size="sm"
            class="q-mr-sm"
            :disable="isReadOnly || !course"
            @click="openAddDialog"
          ></q-btn>
          <q-btn :icon="matClose" round flat @click="model = false" size="sm"></q-btn>
        </div>
      </q-card-actions>
      <q-card-section>
        <q-table
          dense
          flat
          bordered
          :rows="course?.contacts ?? []"
          :columns="columns"
          :pagination="{ rowsPerPage: 0 }"
          hide-bottom
        >
          <template #body-cell-email="props">
            <q-td class="text-left">
              {{ props.value }}
            </q-td>
          </template>

          <template #body-cell-questions="props">
            <q-td class="text-center">
              <div v-if="!!!props.row.questions">
                N/A
              </div>
              <div v-else>
                <q-btn
                  size="sm"
                  color="primary"
                  label="View"
                  @click="openQuestions(props.row.questions)"
                ></q-btn>
              </div>
            </q-td>
          </template>

          <template #body-cell-payment_method="props">
            <q-td class="text-left">
              {{ props.row.payment_method?.label ?? "Free" }}
            </q-td>
          </template>

          <template #body-cell-transfer_date="props">
            <q-td class="text-center">
              {{ props.row.transfer_date ?? "—" }}
            </q-td>
          </template>

          <template #body-cell-confirmed="props">
            <q-td class="text-center">
              <q-toggle
                :model-value="!!props.row.confirmed"
                :disable="isReadOnly"
                @update:model-value="(val) => updateConfirmed(props.row, val)"
              ></q-toggle>
            </q-td>
          </template>

          <template #body-cell-actions="props">
            <q-td class="text-right">
              <q-btn
                color="negative"
                :icon="matDelete"
                round
                flat
                size="sm"
                :disable="isReadOnly"
                @click="onDeleteEnrollee(props.row)"
              />
              <q-btn
                color="primary"
                :icon="matEdit"
                round
                flat
                size="sm"
                :disable="isReadOnly"
                @click="openEditDialog(props.row)"
              />
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>
    <EnrolleeQuestionsDialog
      v-model="enrolleeQuestions.visible"
      :questions="enrolleeQuestions.questions"
    ></EnrolleeQuestionsDialog>

    <q-dialog v-model="addDialog" persistent>
      <q-card style="min-width: 400px;">
        <q-card-section>
          <div class="text-h6">{{ editingId ? "Edit Enrollee" : "Add Enrollee" }}</div>
          <div class="text-caption text-grey-7">{{ course?.name }}</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-btn-toggle
            v-if="!editingId"
            v-model="patronMode"
            spread
            no-caps
            toggle-color="primary"
            outline
            :options="[
              { label: 'Existing Patron', value: 'existing' },
              { label: 'New Patron', value: 'new' },
            ]"
            class="q-mb-md full-width"
          />

          <template v-if="patronMode === 'existing'">
            <q-select
              v-model="selectedPatron"
              :options="patronSearchResults"
              use-input
              hide-selected
              fill-input
              input-debounce="300"
              label="Search by name or email"
              outlined
              dense
              class="q-mb-md"
              :option-label="(p) => `${p.first_name} ${p.last_name} — ${p.email}`"
              @filter="onPatronFilter"
              @update:model-value="onPatronSelected"
            >
              <template #no-option>
                <q-item>
                  <q-item-section class="text-grey">
                    Type at least 2 characters to search
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
            <div v-if="selectedPatron" class="text-caption text-grey-7 q-mb-md">
              Selected: {{ selectedPatron.first_name }} {{ selectedPatron.last_name }} ({{ selectedPatron.email }})
            </div>
          </template>

          <template v-else>
            <q-input
              v-model="form.email"
              type="email"
              label="Email"
              outlined
              dense
              class="q-mb-md"
              @blur="getPatron"
              :hint="editingId ? undefined : `If this email already exists, we'll use that patron instead of creating a duplicate.`"
            />
            <q-input
              v-model="form.first_name"
              label="First Name"
              outlined
              dense
              class="q-mb-md"
            />
            <q-input
              v-model="form.last_name"
              label="Last Name"
              outlined
              dense
              class="q-mb-md"
            />
            <q-input
              v-model="form.phone"
              label="Phone (optional)"
              outlined
              dense
              class="q-mb-md"
            />
          </template>

          <q-select
            v-model="form.payment_method_value"
            :options="paymentMethodOptions"
            label="Payment Method"
            hint="Leave blank for a free / comped enrollment"
            emit-value
            map-options
            clearable
            outlined
            dense
            class="q-mb-md"
          />
          <q-input
            v-if="form.payment_method_value === 'transfer'"
            v-model="form.transfer_date"
            label="Transfer Date"
            outlined
            dense
            mask="####-##-##"
            class="q-mb-md"
          >
            <template #prepend>
              <q-icon :name="matEvent" class="cursor-pointer">
                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                  <q-date v-model="form.transfer_date" mask="YYYY-MM-DD">
                    <div class="row items-center justify-end">
                      <q-btn v-close-popup label="Close" color="primary" flat />
                    </div>
                  </q-date>
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>
          <q-input
            v-model="form.questions"
            label="Notes / Questions (optional)"
            type="textarea"
            autogrow
            outlined
            dense
            class="q-mb-md"
          />
          <template v-if="!editingId">
            <q-toggle
              v-model="form.confirmed"
              label="Payment confirmed"
            />
            <div class="text-caption text-grey-7">
              The instructor is emailed when the enrollment is confirmed.
            </div>
          </template>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn
            flat
            :label="editingId ? 'Save' : 'Add'"
            color="primary"
            :loading="saving"
            :disable="!canSave"
            @click="saveEnrollee"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-dialog>
</template>

<script setup>
import {
  matAdd,
  matClose,
  matDelete,
  matEdit,
  matEvent,
} from "@quasar/extras/material-icons";
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { computed, ref } from "vue";
import EnrolleeQuestionsDialog from "./EnrolleeQuestionsDialog.vue";

const model = defineModel();
const props = defineProps(["course", "isReadOnly"]);

const enrolleeQuestions = ref({
  visible: false,
  questions: null,
});

const columns = [
  {
    label: "Name",
    name: "name",
    field: (row) => `${row.first_name} ${row.last_name}`,
    align: "left",
  },
  {
    label: "Email",
    name: "email",
    field: "email",
    align: "left",
  },
  {
    label: "Phone",
    name: "phone",
    field: "phone",
    align: "left",
  },
  {
    label: "Questions",
    name: "questions",
    field: "questions",
    align: "center",
  },
  {
    label: "Payment Method",
    name: "payment_method",
    field: (row) => row.payment_method?.label ?? "Free",
    align: "left",
  },
  {
    label: "Transfer Date",
    name: "transfer_date",
    field: "transfer_date",
    align: "center",
  },
  {
    label: "Confirmed",
    name: "confirmed",
    field: "confirmed",
    align: "center",
  },
  {
    label: "",
    name: "actions",
    align: "right",
  },
];

const openQuestions = (questions) => {
  enrolleeQuestions.value = {
    visible: true,
    questions,
  };
};

const updateConfirmed = async (row, confirmed) => {
  const response = await callApi({
    path: `/admin/course-contacts/${row.id}/confirmed`,
    method: "put",
    payload: { confirmed },
    useAuth: true,
  });

  if (!response || response.status !== "success") {
    Notify.create({ type: "negative", message: "Failed to update confirmation status." });
    return;
  }

  row.confirmed = response.confirmed;
};

const addDialog = ref(false);
// Set while the add dialog is editing an existing enrollment instead.
const editingId = ref(null);
const saving = ref(false);
const patronMode = ref("existing");
const selectedPatron = ref(null);
const patronSearchResults = ref([]);
const paymentMethods = ref([]);

const emptyForm = () => ({
  email: "",
  first_name: "",
  last_name: "",
  phone: "",
  payment_method_value: null,
  transfer_date: "",
  questions: "",
  confirmed: true,
});

const form = ref(emptyForm());

// Card payment always listed first, the rest alphabetical (as returned).
const paymentMethodOptions = computed(() =>
  [...paymentMethods.value]
    .sort((a, b) => (b.value === "credit_card") - (a.value === "credit_card"))
    .map((pm) => ({ label: pm.label, value: pm.value })),
);

const loadPaymentMethods = async () => {
  if (paymentMethods.value.length === 0) {
    paymentMethods.value = await callApi({
      path: "/payment-methods",
      method: "get",
      useAuth: true,
    }).catch(() => []);
  }
};

const openAddDialog = async () => {
  editingId.value = null;
  patronMode.value = "existing";
  selectedPatron.value = null;
  patronSearchResults.value = [];
  form.value = emptyForm();
  addDialog.value = true;

  await loadPaymentMethods();
};

const openEditDialog = async (row) => {
  editingId.value = row.id;
  // "new" shows the plain name/email/phone inputs.
  patronMode.value = "new";
  form.value = {
    email: row.email,
    first_name: row.first_name,
    last_name: row.last_name,
    phone: row.phone ?? "",
    payment_method_value: row.payment_method?.value ?? null,
    transfer_date: row.transfer_date ?? "",
    questions: row.questions ?? "",
    confirmed: row.confirmed,
  };
  addDialog.value = true;

  await loadPaymentMethods();
};

const onDeleteEnrollee = (row) => {
  const name = `${row.first_name} ${row.last_name}`;

  Notify.create({
    type: "warning",
    position: "center",
    html: true,
    message: `Remove <em>${name}</em> from this class?`,
    actions: [
      {
        label: "No",
        color: "negative",
      },
      {
        color: "positive",
        label: "Yes",
        handler: async () => {
          const response = await callApi({
            path: `/admin/course-contacts/${row.id}`,
            method: "delete",
            useAuth: true,
          }).catch(() => null);

          if (!response || response.status !== "success") {
            Notify.create({ type: "negative", message: "Failed to remove enrollee." });
            return;
          }

          // Mutating the store's row keeps the classes table count in sync.
          const index = props.course.contacts.findIndex((c) => c.id === row.id);
          if (index !== -1) props.course.contacts.splice(index, 1);

          Notify.create({ type: "positive", message: `${name} removed.` });
        },
      },
    ],
  });
};

const onPatronFilter = (val, update) => {
  if (val.length < 2) {
    update(() => {
      patronSearchResults.value = [];
    });
    return;
  }

  update(async () => {
    patronSearchResults.value = await callApi({
      path: `/admin/patrons/search?q=${encodeURIComponent(val)}`,
      method: "get",
      useAuth: true,
      showError: false,
    }).catch(() => []);
  });
};

const onPatronSelected = (patron) => {
  if (!patron) return;
  form.value.email = patron.email;
  form.value.first_name = patron.first_name;
  form.value.last_name = patron.last_name;
  form.value.phone = patron.phone ?? "";
};

const getPatron = async () => {
  // When editing, don't overwrite the name the admin may be correcting.
  if (!form.value.email || editingId.value) return;

  const patron = await callApi({
    path: `/admin/patrons/lookup?email=${encodeURIComponent(form.value.email)}`,
    method: "get",
    useAuth: true,
    showError: false,
  }).catch(() => null);

  if (!patron) return;

  form.value.first_name = patron.first_name;
  form.value.last_name = patron.last_name;
  form.value.phone = patron.phone ?? "";
};

const canSave = computed(() => {
  if (patronMode.value === "existing" && !selectedPatron.value) return false;
  return !!form.value.email && !!form.value.first_name && !!form.value.last_name;
});

const saveEnrollee = async () => {
  saving.value = true;

  const payload = {
    email: form.value.email,
    first_name: form.value.first_name,
    last_name: form.value.last_name,
    phone: form.value.phone || null,
    payment_method_value: form.value.payment_method_value || null,
    transfer_date:
      form.value.payment_method_value === "transfer" && form.value.transfer_date
        ? form.value.transfer_date
        : null,
    questions: form.value.questions || null,
  };

  const response = await callApi(
    editingId.value
      ? {
          path: `/admin/course-contacts/${editingId.value}`,
          method: "put",
          payload,
          useAuth: true,
        }
      : {
          path: `/admin/courses/${props.course.id}/contacts`,
          method: "post",
          payload: { ...payload, confirmed: form.value.confirmed },
          useAuth: true,
        },
  ).catch(() => null);

  saving.value = false;

  // A status "error" response (e.g. already enrolled) is shown by callApi itself.
  if (response === false) return;

  const verb = editingId.value ? "update" : "add";

  if (!response || response.status !== "success") {
    Notify.create({ type: "negative", message: `Failed to ${verb} enrollee.` });
    return;
  }

  // course is the row from store.admin.courses, so this also updates the
  // enrollee count on the classes table.
  const index = props.course.contacts.findIndex((c) => c.id === response.contact.id);
  if (index !== -1) {
    props.course.contacts.splice(index, 1, response.contact);
  } else {
    props.course.contacts.push(response.contact);
  }

  Notify.create({
    type: "positive",
    message: `${response.contact.first_name} ${response.contact.last_name} ${verb === "update" ? "updated" : "added"}.`,
  });
  addDialog.value = false;
};
</script>
