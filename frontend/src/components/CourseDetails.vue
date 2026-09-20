<template>
  <q-card flat bordered class="overflow-hidden">
    <div class="hero-band text-white text-center q-py-md q-px-md">
      <div class="text-h4 text-weight-bold">{{ course.name }}</div>
    </div>

    <div class="row q-col-gutter-lg q-pa-md">
      <div class="col-12 col-sm-5">
        <div class="poster-wrap rounded-borders shadow-3 overflow-hidden relative-position">
          <q-img :src="POSTER_BASE_URL + course.poster" class="poster-img" />
          <div v-if="course.tagline" class="poster-tagline text-white text-subtitle1 text-center">
            {{ course.tagline }}
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-7 column justify-between">
        <div class="row q-col-gutter-sm">
          <div class="col-12">
            <div class="fact-chip">
              <q-icon :name="matPlace" />
              <div>
                <div class="fact-label">Location</div>
                <div class="fact-value">{{ course.location }}</div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6">
            <div class="fact-chip">
              <q-icon :name="matEvent" />
              <div>
                <div class="fact-label">Enrollment</div>
                <div class="fact-value">
                  {{ formatDate(course.enrollment_start) }} –
                  {{ formatDate(course.enrollment_end) }}
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6">
            <div class="fact-chip">
              <q-icon :name="matPayments" />
              <div>
                <div class="fact-label">Cost</div>
                <div class="fact-value">${{ course.cost }}</div>
              </div>
            </div>
          </div>
        </div>

        <q-btn
          label="Enroll Now"
          color="secondary"
          size="lg"
          unelevated
          class="full-width q-mt-md"
          @click="$emit('enroll')"
        ></q-btn>
      </div>
    </div>
  </q-card>
</template>

<script setup>
import { matEvent, matPayments, matPlace } from "@quasar/extras/material-icons";
import { POSTER_BASE_URL } from "src/assets/constants";
import { format, parseISO } from "date-fns";

const props = defineProps(["course"]);

defineEmits(["enroll"]);

const formatDate = (date) => {
  return format(parseISO(date), "PP");
};
</script>

<style lang="scss" scoped>
.poster-wrap {
  aspect-ratio: 3 / 4;
}

.poster-img {
  height: 100%;
}

.poster-tagline {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 2rem 0.75rem 0.75rem;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0));
}

.fact-chip {
  display: flex;
  align-items: center;
  gap: 12px;
  background: rgba($primary, 0.08);
  border-radius: 8px;
  padding: 10px 12px;
  height: 100%;

  .q-icon {
    font-size: 1.4rem;
    color: $primary;
  }
}

.fact-label {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: $secondary;
  font-weight: 600;
}

.fact-value {
  font-size: 0.95rem;
  font-weight: 500;
}
</style>
