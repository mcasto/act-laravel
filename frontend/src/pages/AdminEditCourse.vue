<template>
  <div class="q-pa-md">
    <div class="row justify-center">
      <div class="col-10">
        <q-card class="q-pa-md shadow-2">
          <q-card-section>
            <div class="text-h5 q-mb-md">Edit Class Info</div>

            <q-form @submit.prevent="onSubmit" class="q-gutter-md">
              <!-- Basic Information Section -->
              <div class="text-h6 text-grey-8 q-mt-md q-mb-sm">
                Basic Information
              </div>
              <q-separator class="q-mb-md" />

              <div class="row q-col-gutter-md">
                <div class="col-12">
                  <q-input
                    v-model="store.admin.editCourse.name"
                    label="Course Name"
                    outlined
                    filled
                    required
                    :rules="[(val) => !!val || 'Course name is required']"
                  >
                    <template #prepend>
                      <q-icon :name="matSchool" />
                    </template>
                  </q-input>
                </div>

                <div class="col-12">
                  <q-input
                    v-model="store.admin.editCourse.tagline"
                    label="Tagline"
                    outlined
                    filled
                    required
                    hint="A short catchy description"
                    :rules="[(val) => !!val || 'Tagline is required']"
                  >
                    <template #prepend>
                      <q-icon :name="matFormatQuote" />
                    </template>
                  </q-input>
                </div>

                <div class="col-12" v-if="originalPoster">
                  <div class="text-caption text-grey-7 q-mb-xs">Current Image</div>
                  <q-img
                    :src="POSTER_BASE_URL + originalPoster"
                    style="max-width: 200px; max-height: 200px;"
                    class="rounded-borders"
                  />
                </div>

                <div class="col-12">
                  <q-field
                    :model-value="store.admin.editCourse.poster"
                    :label="originalPoster ? 'Replace Course Image' : 'Course Image'"
                    outlined
                    stack-label
                    :rules="[
                      (val) =>
                        (val && val.length > 0) || 'Course image is required',
                    ]"
                  >
                    <template v-slot:control>
                      <q-uploader
                        auto-upload
                        v-model="store.admin.editCourse.poster"
                        accept="image/*"
                        max-file-size="1048576"
                        :auto-upload="false"
                        flat
                        bordered
                        color="grey-3"
                        text-color="dark"
                        style="max-width: 400px; width: 100%;"
                        class="bg-grey-2"
                        :url="`/api/admin/courses/poster/${
                          store.admin.editCourse.id || 'new'
                        }`"
                        method="POST"
                        field-name="poster"
                        :headers="headers"
                        @uploaded="posterUploaded"
                        @rejected="onFileRejected"
                        :disable="isReadOnly"
                      />
                    </template>
                    <template v-slot:hint>
                      Max size: 1MB (Required)
                    </template>
                  </q-field>
                </div>
              </div>

              <!-- Instructor Information Section -->
              <div class="text-h6 text-grey-8 q-mt-lg q-mb-sm">
                Instructor Information
              </div>
              <q-separator class="q-mb-md" />

              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <q-input
                    v-model="store.admin.editCourse.instructor_name"
                    label="Instructor Name"
                    outlined
                    filled
                    required
                    :rules="[(val) => !!val || 'Instructor name is required']"
                  >
                    <template #prepend>
                      <q-icon :name="matPerson" />
                    </template>
                  </q-input>
                </div>

                <div class="col-6">
                  <q-input
                    v-model="store.admin.editCourse.instructor_email"
                    type="email"
                    label="Instructor Email"
                    outlined
                    filled
                    required
                    :rules="[
                      (val) => !!val || 'Email is required',
                      (val) =>
                        /.+@.+\..+/.test(val) || 'Please enter a valid email',
                    ]"
                  >
                    <template #prepend>
                      <q-icon :name="matEmail" />
                    </template>
                  </q-input>
                </div>

                <div class="col-12" v-if="originalInstructorPhoto">
                  <div class="text-caption text-grey-7 q-mb-xs">Current Image</div>
                  <q-img
                    :src="`/api/storage/images/${originalInstructorPhoto}`"
                    style="max-width: 200px; max-height: 200px;"
                    class="rounded-borders"
                  />
                </div>

                <div class="col-12">
                  <q-field
                    :model-value="store.admin.editCourse.instructor_photo"
                    :label="originalInstructorPhoto ? 'Replace Instructor Photo' : 'Instructor Photo'"
                    outlined
                    stack-label
                    :rules="[
                      (val) =>
                        (val && val.length > 0) ||
                        'Instructor photo is required',
                    ]"
                  >
                    <template v-slot:control>
                      <q-uploader
                        auto-upload
                        v-model="store.admin.editCourse.instructor_photo"
                        accept="image/*"
                        max-file-size="1048576"
                        :auto-upload="false"
                        flat
                        bordered
                        color="grey-3"
                        text-color="dark"
                        style="max-width: 400px; width: 100%;"
                        class="bg-grey-2"
                        :url="`/api/admin/courses/instructor/${
                          store.admin.editCourse.id || 'new'
                        }`"
                        method="POST"
                        field-name="instructor_photo"
                        :headers="headers"
                        @uploaded="instructorImageUploaded"
                        @rejected="onFileRejected"
                        :disable="isReadOnly"
                      />
                    </template>
                    <template v-slot:hint>
                      Max size: 1MB (Required)
                    </template>
                  </q-field>
                </div>

                <div class="col-12">
                  <div class="text-subtitle2 q-mb-sm text-grey-8">
                    Instructor Info *
                  </div>
                  <q-editor
                    v-model="store.admin.editCourse.instructor_info"
                    min-height="200px"
                    class="shadow-1"
                    :toolbar="courseEditorToolbar"
                  />
                </div>

                <div class="col-12">
                  <div class="text-subtitle2 q-mb-sm text-grey-8">
                    Message from Instructor *
                  </div>
                  <q-editor
                    v-model="store.admin.editCourse.message"
                    min-height="200px"
                    class="shadow-1"
                    :toolbar="courseEditorToolbar"
                  />
                </div>
              </div>

              <!-- Course Details Section -->
              <div class="text-h6 text-grey-8 q-mt-lg q-mb-sm">
                Course Details
              </div>
              <q-separator class="q-mb-md" />

              <div class="row q-col-gutter-md">
                <div class="col-12">
                  <q-input
                    v-model="store.admin.editCourse.location"
                    label="Location"
                    outlined
                    filled
                    required
                    type="textarea"
                    rows="2"
                    :rules="[(val) => !!val || 'Location is required']"
                    hint="Full address of the venue"
                  >
                    <template #prepend>
                      <q-icon :name="matPlace" />
                    </template>
                  </q-input>
                </div>

                <div class="col-6">
                  <q-input
                    v-model.number="store.admin.editCourse.cost"
                    type="number"
                    label="Cost"
                    outlined
                    filled
                    required
                    prefix="$"
                    :rules="[(val) => val >= 0 || 'Cost must be 0 or greater']"
                  >
                    <template #prepend>
                      <q-icon :name="matAttachMoney" />
                    </template>
                  </q-input>
                </div>

                <div class="col-6">
                  <q-input
                    v-model="store.admin.editCourse.fixr"
                    label="Fixr URL (Optional)"
                    outlined
                    filled
                    hint="Optional ticketing URL"
                  >
                    <template #prepend>
                      <q-icon :name="matLink" />
                    </template>
                  </q-input>
                </div>

                <div class="col-6">
                  <q-input
                    v-model.number="store.admin.editCourse.max_participants"
                    type="number"
                    label="Maximum Participants (Optional)"
                    outlined
                    filled
                    min="1"
                    hint="Leave blank for no cap"
                    :rules="[
                      (val) => !val || val >= 1 || 'Must be at least 1',
                    ]"
                  >
                    <template #prepend>
                      <q-icon :name="matGroups" />
                    </template>
                  </q-input>
                </div>
              </div>

              <!-- Enrollment Period Section -->
              <div class="text-h6 text-grey-8 q-mt-lg q-mb-sm">
                Enrollment Period
              </div>
              <q-separator class="q-mb-md" />

              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <q-input
                    v-model="store.admin.editCourse.enrollment_start"
                    label="Enrollment Start Date"
                    outlined
                    filled
                    required
                    mask="####-##-##"
                    :rules="[(val) => !!val || 'Start date is required']"
                  >
                    <template #prepend>
                      <q-icon :name="matEvent" class="cursor-pointer">
                        <q-popup-proxy
                          cover
                          transition-show="scale"
                          transition-hide="scale"
                        >
                          <q-date
                            v-model="store.admin.editCourse.enrollment_start"
                            mask="YYYY-MM-DD"
                          >
                            <div class="row items-center justify-end">
                              <q-btn
                                v-close-popup
                                label="Close"
                                color="primary"
                                flat
                              />
                            </div>
                          </q-date>
                        </q-popup-proxy>
                      </q-icon>
                    </template>
                  </q-input>
                </div>

                <div class="col-6">
                  <q-input
                    v-model="store.admin.editCourse.enrollment_end"
                    label="Enrollment End Date"
                    outlined
                    filled
                    required
                    mask="####-##-##"
                    :rules="[(val) => !!val || 'End date is required']"
                  >
                    <template #prepend>
                      <q-icon :name="matEvent" class="cursor-pointer">
                        <q-popup-proxy
                          cover
                          transition-show="scale"
                          transition-hide="scale"
                        >
                          <q-date
                            v-model="store.admin.editCourse.enrollment_end"
                            mask="YYYY-MM-DD"
                          >
                            <div class="row items-center justify-end">
                              <q-btn
                                v-close-popup
                                label="Close"
                                color="primary"
                                flat
                              />
                            </div>
                          </q-date>
                        </q-popup-proxy>
                      </q-icon>
                    </template>
                  </q-input>
                </div>
              </div>

              <!-- Class Sessions Section -->
              <div class="row items-center justify-between q-mt-lg q-mb-sm">
                <div class="text-h6 text-grey-8">Class Sessions</div>
                <q-btn
                  :icon="matAdd"
                  label="Add Session"
                  color="primary"
                  outline
                  dense
                  :disable="isReadOnly"
                  @click="addSession"
                />
              </div>
              <q-separator class="q-mb-md" />

              <div
                v-if="activeSessions.length === 0"
                class="text-grey-7 q-mb-md"
              >
                No sessions yet — add at least one date/time below.
              </div>

              <div
                v-for="session in activeSessions"
                :key="session._key"
                class="row q-col-gutter-md items-start q-mb-sm"
              >
                <div class="col-4">
                  <q-input
                    v-model="session.date"
                    label="Date"
                    outlined
                    filled
                    dense
                    mask="####-##-##"
                    :rules="[(val) => !!val || 'Required']"
                  >
                    <template #prepend>
                      <q-icon :name="matEvent" class="cursor-pointer">
                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                          <q-date v-model="session.date" mask="YYYY-MM-DD">
                            <div class="row items-center justify-end">
                              <q-btn v-close-popup label="Close" color="primary" flat />
                            </div>
                          </q-date>
                        </q-popup-proxy>
                      </q-icon>
                    </template>
                  </q-input>
                </div>

                <div class="col-3">
                  <time-select
                    v-model="session.start"
                    label="Start Time"
                    :rules="[(val) => !!val || 'Required']"
                    :disable="isReadOnly"
                  />
                </div>

                <div class="col-3">
                  <time-select
                    v-model="session.end"
                    label="End Time"
                    :rules="[(val) => !!val || 'Required']"
                    :disable="isReadOnly"
                  />
                </div>

                <div class="col-2 flex items-center" style="height: 48px;">
                  <q-btn
                    :icon="matDelete"
                    flat
                    round
                    dense
                    color="negative"
                    :disable="isReadOnly"
                    @click="removeSession(session)"
                  />
                </div>
              </div>

              <!-- Form Actions -->
              <q-separator class="q-mt-lg q-mb-md" />

              <div class="row justify-end q-gutter-sm">
                <q-btn
                  label="Cancel"
                  color="grey-7"
                  outline
                  to="/admin/classes"
                  class="q-px-lg"
                />
                <q-btn
                  type="submit"
                  label="Save Course"
                  color="positive"
                  unelevated
                  class="q-px-lg"
                  :disable="isReadOnly"
                />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { matAdd, matAttachMoney, matDelete, matEmail, matEvent, matFormatQuote, matGroups, matLink, matPerson, matPlace, matSchool } from "@quasar/extras/material-icons";
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { POSTER_BASE_URL } from "src/assets/constants";
import TimeSelect from "src/components/TimeSelect.vue";
import getPermissionLevel from "src/assets/get-permission-level";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";

const store = useStore();

// Same as QEditor's own default toolbar, plus a "View Source" button so
// admins can drop into/out of raw HTML when the rich-text controls aren't
// enough (e.g. pasting pre-formatted content).
const courseEditorToolbar = [
  ["left", "center", "right", "justify"],
  ["bold", "italic", "underline", "strike"],
  ["undo", "redo"],
  ["viewsource"],
];

const isReadOnly = computed(
  () => getPermissionLevel(store.admin.user, "classes") === "read-only",
);

// Snapshotted once when the form loads, so the "current image" preview
// keeps showing the actual saved file even after a replacement is picked in
// the uploader below (which reassigns the live field to an unpublished temp
// filename until the form is actually saved).
const originalPoster = ref(store.admin.editCourse.poster);
const originalInstructorPhoto = ref(store.admin.editCourse.instructor_photo);

// Sessions are edited locally (add/remove) and only sent to the backend
// when the whole form is saved — same "stage changes, upsert on save" shape
// as PerformancesDrawer.vue uses for a show's performances. Existing rows
// get a stable key from their id; brand-new ones (no id yet) get a
// client-side key so the list doesn't lose track of them across re-renders.
let sessionKeyCounter = 0;
store.admin.editCourse.sessions = (store.admin.editCourse.sessions ?? []).map(
  (session) => ({ ...session, _key: session.id ?? `new-${sessionKeyCounter++}` }),
);

const activeSessions = computed(() =>
  store.admin.editCourse.sessions.filter((session) => !session.deleted),
);

const addSession = () => {
  store.admin.editCourse.sessions.push({
    _key: `new-${sessionKeyCounter++}`,
    course_id: store.admin.editCourse.id,
    date: "",
    start: "",
    end: "",
  });
};

const removeSession = (session) => {
  if (session.id) {
    // Existing, saved session — stage for deletion so its id survives to
    // the upsert call, matching the "deleted" flag convention Performances
    // uses; the actual DB delete only happens on save.
    session.deleted = true;
    return;
  }

  const index = store.admin.editCourse.sessions.indexOf(session);
  if (index !== -1) {
    store.admin.editCourse.sessions.splice(index, 1);
  }
};

const onFileRejected = (rejectedEntries) => {
  rejectedEntries.forEach(({ failedPropValidation, file }) => {
    const reason = failedPropValidation === "max-file-size"
      ? `"${file.name}" is too large (max 1MB)`
      : `"${file.name}" is not an accepted image type`;
    Notify.create({ type: "negative", message: reason });
  });
};

const headers = [
  { name: "Authorization", value: `Bearer ${store.admin.user.token}` },
];

const onSubmit = async () => {
  const config = {
    method: store.admin.editCourse.id ? "put" : "post",
    path: store.admin.editCourse.id
      ? `/admin/courses/${store.admin.editCourse.id}`
      : "/admin/courses",
    payload: store.admin.editCourse,
    useAuth: true,
  };

  const response = await callApi(config);

  if (response.status == "fail") {
    Notify.create({
      type: "negative",
      message: response.message || "Instructor image upload failed",
    });

    return;
  }

  const courseId = response.id ?? store.admin.editCourse.id;

  if (store.admin.editCourse.sessions.length > 0) {
    await callApi({
      path: "/upsert-course-sessions",
      method: "post",
      payload: {
        sessions: store.admin.editCourse.sessions.map((session) => ({
          id: session.id,
          course_id: courseId,
          date: session.date,
          start: session.start,
          end: session.end,
          deleted: session.deleted,
        })),
      },
      useAuth: true,
    });
  }

  Notify.create({
    type: "positive",
    message: "Class saved successfully",
  });

  store.router.push("/admin/classes");
};

const posterUploaded = ({ xhr }) => {
  const response = JSON.parse(xhr.response);
  store.admin.editCourse.poster = response.poster;
};

const instructorImageUploaded = ({ xhr }) => {
  const response = JSON.parse(xhr.response);
  store.admin.editCourse.instructor_photo = response.instructor_photo;
};
</script>
