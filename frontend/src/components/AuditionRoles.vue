<template>
  <q-table
    :rows="roles"
    :columns="roleColumns"
    dense
    :pagination="{ rowsPerPage: 0 }"
    hide-bottom
  >
    <template #top-left>
      <div class="text-h6">
        ROLES
      </div>
    </template>

    <template #body-cell-info="props">
      <q-td class="text-center">
        <q-btn
          icon="info"
          round
          flat
          dense
          @click="
            infoDialog = {
              visible: true,
              character: props.row.name,
              html: props.value,
            }
          "
        >
          <q-tooltip>View Character Info</q-tooltip>
        </q-btn>
      </q-td>
    </template>

    <template #body-cell-side="props">
      <q-td class="text-center">
        <q-btn
          icon="fa-solid fa-file-arrow-down"
          round
          flat
          dense
          size="small"
          @click="downloadSide(props.value)"
        >
          <q-tooltip>Download Audition Side</q-tooltip>
        </q-btn>
      </q-td>
    </template>
  </q-table>

  <q-dialog v-model="infoDialog.visible">
    <q-card>
      <q-toolbar>
        <q-toolbar-title>
          {{ infoDialog.character }}
        </q-toolbar-title>
        <q-btn icon="close" v-close-popup round flat dense></q-btn>
      </q-toolbar>
      <q-separator></q-separator>
      <q-card-section>
        <div v-html="infoDialog.html"></div>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { Notify } from "quasar";
import { ref } from "vue";
import wretch from "wretch";

const props = defineProps(["roles"]);

const infoDialog = ref({
  visible: false,
  character: null,
  html: null,
});

const roleColumns = [
  {
    label: "Name",
    name: "name",
    field: "name",
    align: "left",
  },
  {
    label: "Sex",
    name: "sex",
    field: "sex",
    align: "left",
  },
  {
    label: "Info",
    name: "info",
    field: "info",
    align: "center",
  },
  {
    label: "Side",
    name: "side",
    field: "side",
    align: "center",
  },
];

function downloadSide(filename) {
  filename = filename.replace("/", "");

  wretch(`/api/storage/sides/${filename}`)
    .get()
    .error(400, 404, 500, (error) => {
      error.json().then((json) => {
        console.error("Download failed:", json.error);
        Notify.create({
          type: "negative",
          message: json.error || "File not found",
        });
      });
    })
    .res((res) => {
      // If response is JSON, it’s an error
      const contentType = res.headers.get("Content-Type");
      if (contentType && contentType.includes("application/json")) {
        res.json().then((json) => {
          Notify.create({
            type: "negative",
            message: json.error || "Invalid file request",
          });
        });
        return;
      }

      // Otherwise, assume it's a blob (file)
      res.blob().then((blob) => {
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", filename);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
      });
    });
}
</script>
