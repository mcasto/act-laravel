<template>
  <div class="course-info-page q-mx-auto q-pa-md" style="max-width: 900px;">
    <div class="q-gutter-y-lg">
      <course-details
        :course="course"
        :enrollment-status="enrollmentStatus"
        :is-full="isFull"
        @enroll="openEnroll"
      ></course-details>

      <course-sessions
        v-if="course.sessions?.length > 0"
        :course="course"
      ></course-sessions>

      <course-instructor :course="course"></course-instructor>

      <q-card flat bordered class="overflow-hidden" v-if="course.html">
        <div class="section-band bg-secondary text-white q-py-sm q-px-md">
          <q-icon :name="matInfo" />
          <div class="text-subtitle1 text-weight-bold">About This Class</div>
        </div>
        <div class="q-pa-md about-content" v-html="course.html"></div>
      </q-card>

      <q-card flat class="cta-banner bg-primary text-white text-center q-pa-lg">
        <template v-if="enrollmentOpen && isFull">
          <div class="text-h6 q-mb-xs">This Class Is Full</div>
          <div class="text-subtitle1 q-mb-md">
            ${{ course.cost }} · All spots have been taken
          </div>
          <q-btn
            label="Enroll Now"
            color="white"
            text-color="primary"
            size="lg"
            unelevated
            disable
          ></q-btn>
        </template>
        <template v-else-if="enrollmentOpen">
          <div class="text-h6 q-mb-xs">Ready to join {{ course.name }}?</div>
          <div class="text-subtitle1 q-mb-md">
            ${{ course.cost }} · Enrollment through
            {{ formatDate(course.enrollment_end) }}
          </div>
          <q-btn
            label="Enroll Now"
            color="white"
            text-color="primary"
            size="lg"
            unelevated
            @click="openEnroll"
          ></q-btn>
        </template>
        <template v-else>
          <div class="text-h6 q-mb-xs">Enrollment Is Not Currently Open</div>
          <div class="text-subtitle1 q-mb-md">
            ${{ course.cost }} ·
            <template v-if="enrollmentStatus === 'upcoming'">
              Enrollment opens {{ formatDate(course.enrollment_start) }}
            </template>
            <template v-else>
              Enrollment closed {{ formatDate(course.enrollment_end) }}
            </template>
          </div>
          <q-btn
            label="Enroll Now"
            color="white"
            text-color="primary"
            size="lg"
            unelevated
            disable
          ></q-btn>
        </template>
      </q-card>
    </div>
  </div>

  <course-enroll-form
    v-model="enrollForm"
    @enroll="enroll"
  ></course-enroll-form>
</template>

<script setup>
import { matInfo } from "@quasar/extras/material-icons";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";
import { format, parseISO } from "date-fns";
import CourseDetails from "src/components/CourseDetails.vue";
import CourseSessions from "src/components/CourseSessions.vue";
import CourseInstructor from "src/components/CourseInstructor.vue";
import CourseEnrollForm from "src/components/CourseEnrollForm.vue";
import { cloneDeep } from "lodash-es";
import callApi from "src/assets/call-api";
import * as cheerio from "cheerio";
import { Loading, Notify } from "quasar";

const store = useStore();

const emptyEnrollForm = () => ({
  visible: false,
  first_name: null,
  last_name: null,
  email: null,
  phone: null,
  questions: "",
  payment_method_value: null,
  transfer_date: null,
});

const enrollForm = ref(emptyEnrollForm());

const course = computed(() => {
  return store.course;
});

// "upcoming"/"open"/"closed". A class page stays reachable from enrollment
// start through its final session (Course::isPubliclyVisible()), and
// /classes/preview shows the next class before enrollment even starts — so
// reachability says nothing about whether Enroll Now should work. "open"
// comes from the server (Course::enrollmentOpen(), which counts the whole
// enrollment_end day in Cuenca time); upcoming vs closed is just which side
// of the window we're on.
const enrollmentStatus = computed(() => {
  if (course.value?.enrollment_open) return "open";
  if (!course.value?.enrollment_start) return "closed";
  if (new Date() < parseISO(course.value.enrollment_start)) return "upcoming";
  return "closed";
});

const enrollmentOpen = computed(() => enrollmentStatus.value === "open");

const isFull = computed(() => !!course.value?.is_full);

const formatDate = (date) => {
  return format(parseISO(date), "PP");
};

const openEnroll = () => {
  enrollForm.value = { ...emptyEnrollForm(), visible: true };
};

const enroll = async () => {
  Loading.show({ message: "Sending email to instructor" });

  const payload = cloneDeep(enrollForm.value);
  delete payload.visible;
  payload.course_id = store.course.id;

  const $ = cheerio.load(payload.questions || "");
  $("div").each(function () {
    // Replace <div> with <p> and preserve inner HTML
    const content = $(this).html();
    $(this).replaceWith(`<p>${content}</p>`);
  });

  payload.questions = $.html().replace(/<\/?(html|head|body)[^>]*>/g, "");

  const response = await callApi({
    path: "/course-contact",
    payload,
    method: "post",
    showError: false,
  });

  Loading.hide();

  if (!response || response.errors) {
    const message = response?.errors
      ? Object.values(response.errors).flat().join(" ")
      : "Something went wrong. Please try again.";
    Notify.create({ type: "negative", message });
    return;
  }

  enrollForm.value.visible = false;

  Notify.create({
    type: "positive",
    message: "Enrollment message sent. You will receive information soon.",
  });
};
</script>

<style lang="scss" scoped>
.cta-banner {
  border-radius: 8px;
}
</style>
