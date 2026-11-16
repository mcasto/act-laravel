<template>
  <div v-if="performances.length === 0" class="text-grey-7 text-center q-pa-lg">
    No performances are scheduled yet.
  </div>

  <div v-else class="row q-col-gutter-md">
    <div v-for="p in performances" :key="p.id" class="col-12 col-sm-6 col-md-4">
      <q-card flat bordered :class="{ 'performance-past': isPast(p) }">
        <q-card-section class="q-pb-sm">
          <div class="row items-start no-wrap">
            <div class="col">
              <div class="text-subtitle1 text-weight-medium">{{ formatDate(p.date) }}</div>
              <div class="text-caption text-grey-7">{{ formatTime(p.start_time) }}</div>
            </div>
            <q-badge v-if="p.sold_out" color="negative" class="text-weight-bold q-pa-sm">
              SOLD OUT
            </q-badge>
            <q-badge v-else-if="isPast(p)" color="grey-6" class="q-pa-xs">Past</q-badge>
          </div>
        </q-card-section>
        <q-card-section class="q-pt-none">
          <div class="row items-baseline">
            <span class="text-h4 text-weight-bold">{{ p.seats_taken }}</span>
            <span class="text-body1 text-grey-7 q-ml-xs">
              seats taken / {{ p.sold_out_target }} seat capacity
            </span>
          </div>
          <q-linear-progress
            :value="p.sold_out_target ? Math.min(1, p.seats_taken / p.sold_out_target) : 0"
            :color="p.sold_out ? 'negative' : 'secondary'"
            track-color="grey-3"
            size="10px"
            rounded
            class="q-my-sm"
          />
          <div class="text-body2">
            <b>{{ p.remaining }}</b> seats remaining
          </div>
          <!-- Extra per-performance lines (the admin page adds paid/revenue). -->
          <slot name="extra" :performance="p" />
        </q-card-section>
      </q-card>
    </div>
  </div>
</template>

<script setup>
// Per-performance seat cards, as served by Performance::seatSummariesForShow()
// (GET /director/shows/{show}/sales and GET /ticket-sales/summary). Used by
// the director page and the Summary view of the admin Ticket Sales page.
import { formatDate, formatTime, localToday } from "src/director/format";

defineProps({
  performances: { type: Array, required: true },
});

const isPast = (p) => p.date < localToday();
</script>

<style scoped>
.performance-past {
  opacity: 0.6;
}
</style>
