<template>
  <div class="q-pa-md">
    <div class="row items-center justify-between q-mb-md">
      <div class="text-h6">Project Charters</div>
      <q-toggle v-model="showArchived" label="Show Archived" />
    </div>

    <div v-if="store.admin.project_charters.length === 0" class="text-grey-6 q-pa-md">
      No {{ showArchived ? "archived " : "" }}project charters on record.
    </div>

    <q-table
      v-else
      :rows="store.admin.project_charters"
      :columns="columns"
      row-key="id"
      dense
      v-model:pagination="pagination"
    >
      <template #body-cell-feasibility_score="props">
        <q-td :props="props">
          {{ props.row.feasibility_score ?? "—" }}
        </q-td>
      </template>

      <template #body-cell-actions="props">
        <q-td :props="props" class="text-right">
          <q-btn
            :icon="matVisibility"
            flat
            round
            dense
            size="sm"
            color="primary"
            @click="openDialog(props.row)"
          />
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="dialog">
      <q-card v-if="active" style="min-width: 600px; max-width: 800px;">
        <q-card-section>
          <div class="text-h6">{{ active.title }}</div>
          <div class="text-subtitle2 text-grey-7">Sponsor: {{ active.sponsor }}</div>
        </q-card-section>

        <q-separator />

        <q-card-section class="bg-grey-2">
          <div class="text-subtitle1 q-mb-sm">Feasibility</div>
          <div class="row q-col-gutter-md">
            <div class="col-6 col-sm-3" v-for="score in scoreFields" :key="score.key">
              <q-select
                v-model="feasibility[score.key]"
                :options="[1, 2, 3, 4, 5]"
                :label="score.label"
                :hint="score.hint"
                outlined
                dense
                clearable
                :disable="isReadOnly"
              />
            </div>
          </div>
          <div class="q-mt-sm text-weight-medium">
            Overall Feasibility: {{ computedAverage ?? "—" }}
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section style="max-height: 50vh; overflow-y: auto;">
          <div v-for="section in sections" :key="section.title" class="q-mb-md">
            <div class="text-subtitle1 text-primary">{{ section.title }}</div>
            <template v-for="field in section.fields" :key="field.label">
              <div v-if="showField(field)" class="q-mb-sm">
                <div class="text-caption text-grey-7">{{ field.label }}</div>
                <div style="white-space: pre-wrap;">{{ fieldValue(field) }}</div>
              </div>
            </template>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-input
            v-model="feasibility.admin_notes"
            label="Admin Notes (private)"
            type="textarea"
            outlined
            rows="3"
            :disable="isReadOnly"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            :label="active.deleted_at ? 'Restore' : 'Archive'"
            :color="active.deleted_at ? 'positive' : 'negative'"
            :disable="isReadOnly"
            @click="active.deleted_at ? restore(active) : archive(active)"
          />
          <q-space />
          <q-btn flat label="Close" v-close-popup />
          <q-btn
            flat
            label="Save"
            color="primary"
            :disable="isReadOnly"
            @click="saveFeasibility"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { matVisibility } from "@quasar/extras/material-icons";
import { format, parseISO } from "date-fns";
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import getPermissionLevel from "src/assets/get-permission-level";
import { useStore } from "src/stores/store";
import { computed, ref, watch } from "vue";

const store = useStore();

const isReadOnly = computed(
  () => getPermissionLevel(store.admin.user, "project-charters") === "read-only",
);

const pagination = ref({ page: 1, rowsPerPage: 12 });
const showArchived = ref(false);

const columns = [
  { name: "title", label: "Title", field: "title", align: "left", sortable: true },
  { name: "sponsor", label: "Sponsor", field: "sponsor", align: "left", sortable: true },
  {
    name: "feasibility_score",
    label: "Feasibility",
    field: "feasibility_score",
    align: "center",
    sortable: true,
  },
  { name: "actions", label: "", field: "", align: "right" },
];

const reload = async () => {
  store.admin.project_charters = await callApi({
    path: `/admin/project-charters${showArchived.value ? "?archived=1" : ""}`,
    method: "get",
    useAuth: true,
  });
};

watch(showArchived, reload);

const dialog = ref(false);
const active = ref(null);
const feasibility = ref({
  price_score: null,
  complexity_score: null,
  time_score: null,
  risk_score: null,
  admin_notes: "",
});

const scoreFields = [
  { key: "price_score", label: "Price", hint: "5 = cheapest" },
  { key: "complexity_score", label: "Ease/Complexity", hint: "5 = easiest" },
  { key: "time_score", label: "Time & Volunteer Demand", hint: "5 = lowest demand" },
  { key: "risk_score", label: "Risk", hint: "5 = lowest risk" },
];

const computedAverage = computed(() => {
  const scores = scoreFields
    .map((s) => feasibility.value[s.key])
    .filter((v) => v !== null && v !== undefined);
  if (scores.length === 0) return null;
  return Math.round((scores.reduce((a, b) => a + b, 0) / scores.length) * 10) / 10;
});

const MILESTONE_LABELS = {
  planning_completed: "Project Planning Completed",
  partners_confirmed: "Partners / Volunteers Confirmed",
  launch: "Project Launch",
  main_activity: "Main Activity / Event",
  evaluation: "Evaluation & Reflection",
  final_report: "Final Report Submitted",
};

const dateOrDash = (val) => (val ? format(parseISO(val), "PP") : "—");

const sections = computed(() => {
  if (!active.value) return [];
  const c = active.value;

  return [
    {
      title: "1. Project Overview",
      fields: [{ label: "Project Description", value: c.description }],
    },
    {
      title: "2. Impact & Beneficiaries",
      fields: [{ label: "Why Is This Project Important?", value: c.importance }],
    },
    {
      title: "3. Project Goals & Success Measures",
      fields: [
        { label: "Project Goal", value: c.goal },
        {
          label: "What Does Success Look Like?",
          value: (c.success_measures ?? []).length
            ? c.success_measures.map((m, i) => `${i + 1}. ${m}`).join("\n")
            : null,
        },
        {
          label: "How Will Success Be Measured?",
          value: (c.measurement_methods ?? []).join(", ") || null,
        },
        { label: "Measurement Approach", value: c.measurement_approach },
      ],
    },
    {
      title: "4. Project Plan & Timeline",
      fields: [
        { label: "Start Date", value: dateOrDash(c.start_date) },
        { label: "Completion Date", value: dateOrDash(c.completion_date) },
        {
          label: "Key Milestones & Target Dates",
          value: Object.keys(c.milestones ?? {}).length
            ? Object.entries(c.milestones)
                .map(([key, date]) => `${MILESTONE_LABELS[key] ?? key}: ${dateOrDash(date)}`)
                .join("\n")
            : null,
        },
      ],
    },
    {
      title: "5. Resources & Partnerships",
      fields: [
        { label: "Team Members / Volunteers", value: c.team_members },
        { label: "Community Partners", value: c.community_partners },
        {
          label: "Resources Needed",
          value: (c.resources_needed ?? []).join(", ") || null,
        },
        { label: "Other Resources", value: c.resources_other },
      ],
    },
    {
      title: "6. Documentation & Storytelling",
      fields: [
        {
          label: "Documentation Methods",
          value: (c.documentation_methods ?? []).join(", ") || null,
        },
        { label: "Documentation Plan", value: c.documentation_plan },
      ],
    },
    {
      title: "7. Risks & Challenges",
      fields: [
        {
          label: "Challenges & Mitigation",
          value: (c.risks ?? []).length
            ? c.risks
                .map((r, i) => `${i + 1}. ${r.challenge || "—"} → ${r.mitigation || "—"}`)
                .join("\n")
            : null,
        },
      ],
    },
    {
      title: "8. Leadership Reflection",
      fields: [
        {
          label: "How does this project connect to ACT's growth?",
          value: c.leadership_reflection,
        },
      ],
    },
    {
      title: "Sponsor Commitment",
      fields: [
        { label: "Signature", value: c.commitment_signature },
        { label: "Date", value: dateOrDash(c.commitment_date) },
      ],
    },
  ];
});

const showField = (field) => field.value !== null && field.value !== undefined && field.value !== "";
const fieldValue = (field) => field.value;

const openDialog = (charter) => {
  active.value = charter;
  feasibility.value = {
    price_score: charter.price_score,
    complexity_score: charter.complexity_score,
    time_score: charter.time_score,
    risk_score: charter.risk_score,
    admin_notes: charter.admin_notes ?? "",
  };
  dialog.value = true;
};

const saveFeasibility = async () => {
  const response = await callApi({
    path: `/admin/project-charters/${active.value.id}/feasibility`,
    method: "put",
    payload: feasibility.value,
    useAuth: true,
  });

  if (!response || response.status !== "success") {
    Notify.create({
      type: "negative",
      message: response?.message || "Something went wrong.",
    });
    return;
  }

  Notify.create({ type: "positive", message: "Feasibility saved." });
  dialog.value = false;
  await reload();
};

const archive = (charter) => {
  Notify.create({
    type: "warning",
    position: "center",
    message: `Archive "${charter.title}"? It'll move to the archived list.`,
    actions: [
      { label: "No" },
      {
        label: "Yes",
        handler: async () => {
          const response = await callApi({
            path: `/admin/project-charters/${charter.id}`,
            method: "delete",
            useAuth: true,
          });

          if (!response || response.status !== "success") {
            Notify.create({
              type: "negative",
              message: response?.message || "Archive failed.",
            });
            return;
          }

          dialog.value = false;
          await reload();
        },
      },
    ],
  });
};

const restore = async (charter) => {
  const response = await callApi({
    path: `/admin/project-charters/${charter.id}/restore`,
    method: "post",
    useAuth: true,
  });

  if (!response || response.status !== "success") {
    Notify.create({
      type: "negative",
      message: response?.message || "Restore failed.",
    });
    return;
  }

  Notify.create({ type: "positive", message: "Project charter restored." });
  dialog.value = false;
  await reload();
};
</script>
