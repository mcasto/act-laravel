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
                      <q-icon name="school" />
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
                      <q-icon name="format_quote" />
                    </template>
                  </q-input>
                </div>

                <div class="col-12">
                  <q-field
                    :model-value="store.admin.editCourse.poster"
                    label="Course Image"
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
                      <q-icon name="person" />
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
                      <q-icon name="email" />
                    </template>
                  </q-input>
                </div>

                <div class="col-12">
                  <q-field
                    :model-value="store.admin.editCourse.instructor_photo"
                    label="Instructor Photo"
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
                      <q-icon name="place" />
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
                      <q-icon name="attach_money" />
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
                      <q-icon name="link" />
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
                      <q-icon name="event" class="cursor-pointer">
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
                      <q-icon name="event" class="cursor-pointer">
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
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";

const store = useStore();

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
