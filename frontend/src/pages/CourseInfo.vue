<template>
  <div class="course-info-page q-mx-auto q-pa-md" style="max-width: 900px;">
    <div class="q-gutter-y-lg">
      <course-details
        :course="course"
        :enrollment-open="enrollmentOpen"
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
        <template v-if="enrollmentOpen">
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

// "upcoming"/"open"/"closed" — mirrors the exact enrollment_start <= now <=
// enrollment_end window CourseController uses to decide whether a class
// shows up on /classes at all (courseDetails()/previewEnrollment()). Real
// /class-details pages are only reachable for classes already inside that
// window, so this is normally "open" there — but /classes/preview
// deliberately bypasses the window to show the next upcoming class before
// enrollment starts, so Enroll Now needs its own live check rather than
// assuming reachability implies eligibility.
const enrollmentStatus = computed(() => {
  if (!course.value?.enrollment_start || !course.value?.enrollment_end) {
    return "closed";
  }

  const now = new Date();
  if (now < parseISO(course.value.enrollment_start)) return "upcoming";
  if (now > parseISO(course.value.enrollment_end)) return "closed";
  return "open";
});

const enrollmentOpen = computed(() => enrollmentStatus.value === "open");

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
