<template>
  <div ref="containerRef">
    <div v-if="shows.length === 0" class="text-grey-6 q-pa-md">
      No ticket sales on record.
    </div>

    <template v-else>
      <q-select
        :options="shows"
        v-model="show"
        :label="show ? 'Show' : 'Select Show'"
        dense
        outlined
        stack-label
        class="q-mb-sm"
        style="min-width: 8rem;"
      ></q-select>

      <div
        v-if="show"
        class="flex no-wrap items-center q-gutter-x-md q-mb-sm q-px-sm"
      >
        <q-btn
          icon="add"
          size="sm"
          color="primary"
          round
          @click="onNewTicket"
        ></q-btn>
        <q-space></q-space>
        <div
          v-for="(color, label) in paymentMethodColors"
          :key="label"
          class="flex items-center q-gutter-x-xs"
        >
          <div
            :style="`width: 10px; height: 10px; border-radius: 50%; background: ${color};`"
          ></div>
          <span class="text-caption">{{ label }}</span>
        </div>
      </div>

      <div
        v-if="show"
        class="flex no-wrap items-center text-weight-bold text-caption q-py-xs bg-grey-3"
        style="
          gap: 8px;
          border-bottom: 2px solid #bdbdbd;
          padding-left: 16px;
          padding-right: 16px;
        "
      >
        <div style="width: 180px; min-width: 180px;">Name</div>
        <div style="width: 36px; min-width: 36px;"></div>
        <div
          style="
            width: 50px;
            min-width: 50px;
            display: flex;
            justify-content: center;
          "
        >
          Qty
        </div>
        <div style="width: 100px; min-width: 100px;">Date Sold</div>
        <div style="width: 160px; min-width: 160px;">Show</div>
        <div style="width: 100px; min-width: 100px;">Performance</div>
        <div style="width: 16px; min-width: 16px;">
          <q-icon name="mdi-currency-usd" />
        </div>
        <div
          style="
            width: 70px;
            min-width: 36px;
            margin-left: 16px;
            display: flex;
            justify-content: center;
          "
        >
          <q-icon name="mdi-cog" />
        </div>
      </div>

      <q-virtual-scroll
        :items="recs"
        v-slot="{ item: rec, index }"
        :style="{ height: scrollHeight }"
      >
        <div
          :key="rec.id"
          class="flex no-wrap items-center"
          style="gap: 8px; padding: 4px 16px; border-bottom: 1px solid #e0e0e0;"
          :style="{ background: index % 2 === 0 ? '#f5f5f5' : 'white' }"
        >
          <div style="width: 180px; min-width: 180px;" class="ellipsis">
            {{ rec.patron.first_name }} {{ rec.patron.last_name }}
          </div>
          <div style="width: 36px; min-width: 36px;">
            <q-btn icon="info" flat round size="sm">
              <q-menu>
                <q-list dense separator>
                  <q-item>
                    <q-item-section side>Email:</q-item-section>
                    <q-item-section>
                      <q-item-label>
                        <a
                          :href="`mailto:${rec.patron.first_name} ${rec.patron.last_name} <${rec.patron.email}>`"
                        >
                          {{ rec.patron.email }}
                        </a>
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item>
                    <q-item-section side>Phone:</q-item-section>
                    <q-item-section>
                      <q-item-label>{{ rec.patron.phone }}</q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-menu>
            </q-btn>
          </div>
          <div style="width: 50px; min-width: 50px; text-align: center;">
            {{ rec.quantity || "?" }}
          </div>
          <div style="width: 100px; min-width: 100px;">
            {{ format(parseISO(rec.sold_at), "PP") }}
          </div>
          <div style="width: 160px; min-width: 160px;">
            {{ rec.performance.show.name }}
          </div>
          <div style="width: 100px; min-width: 100px;">
            {{ rec.performance.formatted_date }}
            {{ rec.performance.formatted_time }}
          </div>
          <div
            style="
              width: 16px;
              min-width: 16px;
              display: flex;
              align-items: center;
            "
          >
            <div
              :style="`width: 10px; height: 10px; border-radius: 50%; background: ${
                paymentMethodColors[rec.payment_method.label]
              };`"
            ></div>
          </div>
          <div
            style="
              width: 70px;
              min-width: 36px;
              margin-left: 16px;
              display: flex;
              justify-content: center;
            "
          >
            <q-btn
              icon="edit"
              flat
              round
              color="primary"
              size="sm"
              @click="onEditSale(rec)"
            ></q-btn>
            <q-btn
              icon="delete"
              flat
              round
              color="negative"
              @click="onDelete(rec)"
              size="sm"
            ></q-btn>
          </div>
        </div>
      </q-virtual-scroll>
    </template>
  </div>
</template>

<script setup>
import { format, parseISO } from "date-fns";
import { uniqBy } from "lodash-es";
import { Dialog } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from "vue";

const store = useStore();

const currentShow = store.home.currentShow;
const show = ref(
  currentShow ? { label: currentShow.name, value: currentShow.id } : null,
);

const recs = computed(() => {
  if (!show.value) return [];

  const recs = store.admin.ticket_sales.filter(
    (rec) => rec.performance.show.id == show.value.value,
  );

  return recs;
});

const shows = computed(() => {
  const fromSales = uniqBy(
    store.admin.ticket_sales,
    (rec) => rec.performance.show.id,
  ).map((rec) => ({
    label: rec.performance.show.name,
    value: rec.performance.show.id,
  }));

  const current = store.home.currentShow;
  if (current && !fromSales.some((s) => s.value === current.id)) {
    fromSales.unshift({ label: current.name, value: current.id });
  }

  return fromSales;
});

const paymentMethodColors = computed(() => {
  const methods = uniqBy(recs.value, (rec) => rec.payment_method.id).map(
    (rec) => rec.payment_method,
  );

  return Object.fromEntries(methods.map((m) => [m.label, m.color]));
});

const containerRef = ref(null);
const scrollHeight = ref("400px");

const updateHeight = () => {
  if (!containerRef.value) return;
  const virtualScroll = containerRef.value.querySelector(".q-virtual-scroll");
  if (!virtualScroll) return;
  const top = virtualScroll.getBoundingClientRect().top;
  const footer = document.querySelector(".q-footer");
  const footerHeight = footer ? footer.getBoundingClientRect().height : 0;
  scrollHeight.value = `${window.innerHeight - top - footerHeight - 30}px`;
};

const resizeObserver = new ResizeObserver(updateHeight);

const onDelete = async (row) => {
  Dialog.create({
    title: "Delete Ticket Sale",
    message: `Are you sure you want to delete the ticket sold to ${row.patron.first_name} ${row.patron.last_name} for the ${row.performance.formatted_date} performance at ${row.performance.formatted_time}?`,
    ok: "Yes",
    cancel: "No",
  }).onOk(async () => {
    const response = await callApi({
      path: `/ticket-sales`,
      method: "delete",
      useAuth: true,
      payload: row,
    });

    store.admin.ticket_sales = response;
  });
};

const onNewTicket = async () => {
  store.router.push({
    name: "admin-ticket-sale-new",
    params: { show_id: show.value.value },
  });
};

const onEditSale = async (row) => {
  store.router.push({ name: "admin-ticket-sale-edit", params: { id: row.id } });
};

onMounted(() => {
  updateHeight();
  resizeObserver.observe(containerRef.value);
  window.addEventListener("resize", updateHeight);
});

watch(show, () => nextTick(updateHeight));

onUnmounted(() => {
  resizeObserver.disconnect();
  window.removeEventListener("resize", updateHeight);
});
</script>
