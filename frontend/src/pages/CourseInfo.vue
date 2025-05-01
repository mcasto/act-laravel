<template>
  <q-card flat bordered class="q-pa-md q-mx-auto" style="max-width: 800px;">
    <course-details :course="course"></course-details>
    <course-instructor :course="course"></course-instructor>

    <!-- HTML Snippet -->
    <q-separator />

    <div v-html="course.html"></div>

    <!-- Optional CTA -->
    <div class="q-mt-lg text-center">
      <q-btn
        label="Enroll Now"
        color="positive"
        @click="
          enrollForm = {
            visible: true,
            first_name: null,
            last_name: null,
            email: null,
            phone: null,
            questions: '',
          }
        "
      ></q-btn>
    </div>
  </q-card>

  <course-enroll-form
    v-model="enrollForm"
    @enroll="enroll"
  ></course-enroll-form>
</template>

<script setup>
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";
import CourseDetails from "src/components/CourseDetails.vue";
import CourseInstructor from "src/components/CourseInstructor.vue";
import CourseEnrollForm from "src/components/CourseEnrollForm.vue";
import { cloneDeep } from "lodash-es";
import callApi from "src/assets/call-api";
import * as cheerio from "cheerio";
import { Loading, Notify } from "quasar";

const store = useStore();

const enrollForm = ref({
  visible: false,
  first_name: null,
  last_name: null,
  email: null,
  phone: null,
  questions: "",
});

const course = computed(() => {
  return store.course;
});

const enroll = async () => {
  enrollForm.value.visible = false;

  Loading.show({ message: "Sending email to instructor" });

  const payload = cloneDeep(enrollForm.value);
  delete payload.visible;
  payload.course_id = store.course.id;

  const $ = cheerio.load(payload.questions);
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
  });

  Loading.hide();

  Notify.create({
    type: "positive",
    message: "Enrollment message sent. You will receive information soon.",
  });
};
</script>
