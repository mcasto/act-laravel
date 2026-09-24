<template>
  <div>
    <q-table
      :rows="filteredRows"
      :columns="columns"
      row-key="id"
      dense
      v-model:pagination="pagination"
    >
      <template #top>
        <div class="full-width">
          <div class="flex no-wrap items-center q-gutter-x-sm q-mb-sm">
            <div class="text-h6">Patron Management</div>
            <q-space />
            <q-input
              v-model="search"
              dense
              outlined
              placeholder="Search by name or email..."
              clearable
              style="min-width: 260px;"
              debounce="300"
            >
              <template #prepend>
                <q-icon :name="matSearch" />
              </template>
            </q-input>
            <q-btn
              color="primary"
              :icon="matAdd"
              label="Add Patron"
              :disable="isReadOnly"
              @click="openDialog()"
            />
          </div>
        </div>
      </template>

      <template #body-cell-email="props">
        <q-td :props="props">
          <a :href="`mailto:${props.value}`">{{ props.value }}</a>
        </q-td>
      </template>

      <template #body-cell-is_angel="props">
        <q-td :props="props">
          <template v-if="props.row.is_angel">
            <q-icon :name="matStar" color="amber" size="sm">
              <q-tooltip>{{ props.row.angel_level }}</q-tooltip>
            </q-icon>
          </template>
        </q-td>
      </template>

      <template #body-cell-flex_remaining="props">
        <q-td :props="props">
          <template v-if="props.value === null">
            <span class="text-grey">N/A</span>
          </template>
          <template v-else>
            <a href="#" @click.prevent="openFlexHistory(props.row.id)">{{ props.value }}</a>
          </template>
        </q-td>
      </template>

      <template #body-cell-front_row="props">
        <q-td :props="props">
          <q-input
            :model-value="props.value"
            type="number"
            dense
            outlined
            min="0"
            max="3"
            debounce="500"
            style="max-width: 90px;"
            :disable="isReadOnly"
            @update:model-value="(val) => updateFrontRow(props.row, val)"
          >
            <q-tooltip>
              Front-row seats needed (wheelchair, vision-impaired, caretakers, etc.)
            </q-tooltip>
          </q-input>
        </q-td>
      </template>

      <template #body-cell-comments="props">
        <q-td :props="props">
          <q-btn
            flat
            round
            dense
            :icon="matComment"
            :color="props.value ? 'primary' : 'grey-5'"
            @click="openComments(props.row)"
          >
            <q-tooltip v-if="props.value">{{ props.value }}</q-tooltip>
            <q-tooltip v-else>Add a note about this patron</q-tooltip>
          </q-btn>
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
            @click="deletePatron(props.row)"
          />
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="dialog" persistent>
      <q-card style="min-width: 400px;">
        <q-card-section>
          <div class="text-h6">{{ form.id ? "Edit" : "Add" }} Patron</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
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
            v-model="form.email"
            type="email"
            label="Email"
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
          <q-checkbox v-model="form.founding_angel" label="Founding Angel" />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn
            flat
            label="Save"
            color="primary"
            :loading="saving"
            :disable="!canSave"
            @click="saveDialog"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="flexHistoryDialog">
      <q-card style="min-width: 500px; max-width: 95vw;">
        <q-card-section>
          <div class="text-h6">{{ flexHistoryPatronName }}</div>
          <div class="text-caption text-grey-7">Flex Ticket History</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div v-if="flexHistoryLoading" class="text-center q-pa-md">
            <q-spinner color="primary" size="2em" />
          </div>

          <div v-else-if="flexHistorySeasons.length === 0" class="text-grey text-center q-pa-md">
            No flex package history found.
          </div>

          <div
            v-for="season in flexHistorySeasons"
            :key="season.season"
            class="q-mb-md"
          >
            <div class="text-subtitle1 text-bold">{{ season.season }}</div>
            <div class="text-caption q-mb-xs">
              Purchased: {{ season.tickets_purchased }} &nbsp;•&nbsp;
              Used: {{ season.tickets_used }} &nbsp;•&nbsp;
              Remaining: {{ season.tickets_remaining }}
            </div>

            <q-list v-if="season.usage.length" dense bordered separator>
              <q-item v-for="(u, i) in season.usage" :key="i">
                <q-item-section>{{ u.show }}</q-item-section>
                <q-item-section side>{{ u.date }}</q-item-section>
                <q-item-section side>x{{ u.quantity }}</q-item-section>
              </q-item>
            </q-list>
            <div v-else class="text-grey text-caption">No tickets used yet this season.</div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Close" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="commentsDialog.visible">
      <q-card style="min-width: 420px; max-width: 95vw;">
        <q-card-section>
          <div class="text-h6">{{ commentsDialog.patronName }}</div>
          <div class="text-caption text-grey-7">
            Admin-only notes — never shown to the patron
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input
            v-model="commentsDialog.value"
            type="textarea"
            outlined
            autogrow
            :disable="isReadOnly"
            placeholder="e.g. Usually ~15 min late — hold their seat if prepaid."
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn
            flat
            label="Save"
            color="primary"
            :disable="isReadOnly"
            :loading="commentsDialog.saving"
            @click="saveComments"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import {
  matAdd,
  matComment,
  matDelete,
  matEdit,
  matSearch,
  matStar,
} from "@quasar/extras/material-icons";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";
import callApi from "src/assets/call-api";
import getPermissionLevel from "src/assets/get-permission-level";
import { Notify } from "quasar";

const store = useStore();

const isReadOnly = computed(
  () => getPermissionLevel(store.admin.user, "patrons") === "read-only",
);

const search = ref("");
const pagination = ref({ page: 1, rowsPerPage: 15 });

const columns = [
  {
    name: "name",
    label: "Name",
    field: (row) => `${row.first_name} ${row.last_name}`,
    align: "left",
    sortable: true,
  },
  {
    name: "email",
    label: "Email",
    field: "email",
    align: "left",
    sortable: true,
  },
  {
    name: "is_angel",
    label: "Angel",
    field: "is_angel",
    align: "center",
    sortable: true,
  },
  {
    name: "flex_remaining",
    label: "Flex Remaining",
    field: "flex_remaining",
    align: "center",
    sortable: true,
  },
  {
    name: "front_row",
    label: "Front Row",
    field: "front_row",
    align: "center",
    sortable: true,
  },
  {
    name: "comments",
    label: "Notes",
    field: "comments",
    align: "center",
  },
  {
    name: "actions",
    label: "",
    field: "",
    align: "right",
  },
];

const filteredRows = computed(() => {
  const rows = store.admin.patrons ?? [];
  if (!search.value) return rows;

  const q = search.value.toLowerCase();
  return rows.filter(
    (row) =>
      `${row.first_name} ${row.last_name}`.toLowerCase().includes(q) ||
      row.email?.toLowerCase().includes(q),
  );
});

const flexHistoryDialog = ref(false);
const flexHistoryLoading = ref(false);
const flexHistoryPatronName = ref("");
const flexHistorySeasons = ref([]);

const openFlexHistory = async (patronId) => {
  flexHistoryDialog.value = true;
  flexHistoryLoading.value = true;
  flexHistorySeasons.value = [];

  const response = await callApi({
    path: "/admin/patrons/flex-history",
    method: "get",
    payload: patronId,
    useAuth: true,
  });

  if (response) {
    flexHistoryPatronName.value = response.patron.name;
    flexHistorySeasons.value = response.seasons;
  }

  flexHistoryLoading.value = false;
};

const updateFrontRow = async (row, value) => {
  const previous = row.front_row;
  const front_row = value === null || value === "" ? 0 : Number(value);

  if (front_row === previous) return;

  row.front_row = front_row;

  try {
    const response = await callApi({
      path: `/admin/patrons/${row.id}`,
      method: "put",
      payload: { front_row },
      useAuth: true,
      showError: false,
    });

    if (!response || response.status !== "success") {
      throw new Error("unexpected response");
    }
  } catch (e) {
    row.front_row = previous;
    Notify.create({
      type: "negative",
      message: "Failed to update front-row seats needed (must be 0-3).",
    });
  }
};

const commentsDialog = ref({
  visible: false,
  row: null,
  patronName: "",
  value: "",
  saving: false,
});

const openComments = (row) => {
  commentsDialog.value = {
    visible: true,
    row,
    patronName: `${row.first_name} ${row.last_name}`,
    value: row.comments ?? "",
    saving: false,
  };
};

const saveComments = async () => {
  const { row, value } = commentsDialog.value;
  commentsDialog.value.saving = true;

  const response = await callApi({
    path: `/admin/patrons/${row.id}`,
    method: "put",
    payload: { comments: value || null },
    useAuth: true,
    showError: false,
  });

  commentsDialog.value.saving = false;

  if (!response || response.status !== "success") {
    Notify.create({
      type: "negative",
      message: "Failed to save note.",
    });
    return;
  }

  row.comments = response.comments;
  commentsDialog.value.visible = false;
};

const dialog = ref(false);
const saving = ref(false);
const form = ref({
  id: null,
  first_name: "",
  last_name: "",
  email: "",
  phone: "",
  founding_angel: false,
});

const canSave = computed(
  () => !!form.value.first_name && !!form.value.last_name && !!form.value.email,
);

const openDialog = (row = null) => {
  form.value = row
    ? {
        id: row.id,
        first_name: row.first_name,
        last_name: row.last_name,
        email: row.email,
        phone: row.phone ?? "",
        founding_angel: !!row.founding_angel,
      }
    : {
        id: null,
        first_name: "",
        last_name: "",
        email: "",
        phone: "",
        founding_angel: false,
      };
  dialog.value = true;
};

const reload = async () => {
  store.admin.patrons = await callApi({
    path: "/admin/patrons",
    method: "get",
    useAuth: true,
  });
};

// Laravel's automatic validate() failure (e.g. a duplicate email on
// create) reaches here as a WretchError whose .message is the raw JSON
// response text, not a parsed object — this app has no global handler
// that reshapes it, so it has to be unpacked here or it'd otherwise only
// ever reach the browser console (see AdminTicketSaleForm.vue's
// special_seating fix for the same class of bug).
const parseApiErrorMessage = (error) => {
  try {
    const parsed = JSON.parse(error.message);
    if (parsed.errors) return Object.values(parsed.errors).flat().join(" ");
    return parsed.message;
  } catch {
    return error?.message;
  }
};

const saveDialog = async () => {
  saving.value = true;
  const isEdit = !!form.value.id;

  const payload = {
    first_name: form.value.first_name,
    last_name: form.value.last_name,
    email: form.value.email,
    phone: form.value.phone || null,
    founding_angel: form.value.founding_angel,
  };

  try {
    const response = await callApi({
      path: isEdit ? `/admin/patrons/${form.value.id}` : "/admin/patrons",
      method: isEdit ? "put" : "post",
      payload,
      useAuth: true,
      showError: false,
    });

    if (!response || response.status !== "success") {
      throw new Error(response?.message || "Something went wrong.");
    }

    Notify.create({
      type: "positive",
      message: `Patron ${isEdit ? "updated" : "added"}.`,
    });
    dialog.value = false;
    await reload();
  } catch (e) {
    Notify.create({
      type: "negative",
      message: parseApiErrorMessage(e) || "Something went wrong.",
    });
  } finally {
    saving.value = false;
  }
};

const deletePatron = (row) => {
  Notify.create({
    type: "warning",
    position: "center",
    message: `Delete ${row.first_name} ${row.last_name}? This can't be undone.`,
    actions: [
      { label: "No" },
      {
        label: "Yes",
        handler: async () => {
          const response = await callApi({
            path: `/admin/patrons/${row.id}`,
            method: "delete",
            useAuth: true,
          });

          if (!response || response.status !== "success") return;

          await reload();
        },
      },
    ],
  });
};
</script>
