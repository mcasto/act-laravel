<template>
  <q-page>
    <!-- Overview -->
    <div class="q-px-md q-px-lg-xl q-pt-xl q-pb-md text-body1">
      <p>
        As a company of volunteers, we look for others who want to help bring
        live theater to Cuenca. We offer camaraderie, fun and an outlet for your
        creative and other talents. We have something for everybody to do and
        everybody is eligible to contribute.
      </p>
      <p>
        Many ACT company members specialize in a particular aspect of theater
        and performing arts production. But many others wear lots of hats over
        the course of a season, taking a lead role in one show, directing the
        next, running sound or working with patrons in hospitality.
      </p>
      <p>
        The options are many, and the opportunities whatever you want to make of
        them!
      </p>
    </div>

    <!-- Podcast callout -->
    <div class="text-center q-px-md q-mb-xl">
      <a
        href="https://yapatree.com/joining-act-theatre-curious-cuenca-podcast-ep-2/"
        target="_blank"
        rel="noopener"
      >
        <img
          src="/storage/images/volunteer/joining-the-theater-podcast.jpeg"
          style="max-width: 560px; width: 100%; border-radius: 8px;"
          alt="Yapatree podcast featuring ACT volunteers"
        />
      </a>
      <div class="text-subtitle1 q-mt-sm">
        <span class="text-bold">PODCAST</span> — Click the pic for a recent
        Yapatree.com podcast<br class="gt-xs" />
        with some of our volunteers sharing about the fun of their experience.
      </div>
    </div>

    <!-- Volunteer button -->
    <div class="flex justify-center q-mb-xl">
      <q-btn
        color="primary"
        label="Volunteer with ACT"
        size="lg"
        unelevated
        @click="formDialog = true"
      />
    </div>

    <!-- Section cards -->
    <div class="row q-col-gutter-md q-px-md q-px-lg-xl q-pb-xl">
      <div
        v-for="section in sections"
        :key="section.title"
        class="col-12 col-sm-6"
      >
        <q-card flat bordered class="full-height">
          <q-img
            :src="`/storage/images/volunteer/${section.image}`"
            :ratio="16 / 9"
            :alt="section.title"
          />
          <q-card-section>
            <div class="text-h6 q-mb-sm">{{ section.title }}</div>
            <div v-html="section.body" class="text-body2 volunteer-body" />
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Bottom volunteer button -->
    <div class="flex justify-center q-mb-xl">
      <q-btn
        color="primary"
        label="Volunteer with ACT"
        size="lg"
        unelevated
        @click="formDialog = true"
      />
    </div>

    <!-- Volunteer form dialog -->
    <q-dialog v-model="formDialog" maximized>
      <q-card>
        <q-card-section class="row items-center bg-primary text-white">
          <div class="text-h6">Volunteer with ACT</div>
          <q-space />
          <q-btn :icon="matClose" flat round dense v-close-popup />
        </q-card-section>

        <q-scroll-area style="height: calc(100vh - 56px);">
          <div class="q-pa-md">
            <div class="text-subtitle2 q-mb-md">
              Fill out this form to contact our volunteer coordinator.
            </div>
            <q-form @submit.prevent="volunteerContact">
              <div class="row q-gutter-y-sm q-mb-md">
                <q-input
                  type="text"
                  label="Name"
                  v-model="volunteerForm.name"
                  stack-label
                  dense
                  outlined
                  class="col-12 col-sm-4 q-px-sm"
                  required
                />
                <q-input
                  type="email"
                  label="Email"
                  v-model="volunteerForm.email"
                  stack-label
                  dense
                  outlined
                  class="col-12 col-sm-4 q-px-sm"
                  required
                  :rules="[(v) => isValidEmail(v) || 'Invalid Email']"
                />
                <q-input
                  type="tel"
                  label="Phone / Whatsapp"
                  v-model="volunteerForm.phone"
                  stack-label
                  dense
                  outlined
                  class="col-12 col-sm-4 q-px-sm"
                  required
                />
              </div>

              <div class="text-subtitle2 q-mb-sm">
                What interest, experience, or skills do you want to bring to ACT
                presentation, or what would you like to learn (select all that
                interest you or that you have previous experience with)
              </div>

              <div class="row q-mb-md">
                <div
                  v-for="skill of store.skills"
                  :key="`skill-${skill.id}`"
                  class="col-6 col-sm-4 q-pa-xs"
                >
                  <q-checkbox
                    v-model="volunteerForm.skills"
                    :val="skill.id"
                    :label="skill.name"
                    dense
                  />
                </div>
              </div>

              <div class="q-mb-md">
                <div class="text-subtitle2 q-mb-sm">
                  Previous theater experience?
                </div>
                <q-editor v-model="volunteerForm.experience" />
              </div>

              <div class="row justify-end q-gutter-x-sm">
                <q-btn color="negative" flat label="Clear" @click="resetForm" />
                <q-btn type="submit" color="positive" label="Submit" />
              </div>
            </q-form>
          </div>
        </q-scroll-area>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { matClose } from "@quasar/extras/material-icons";
import { cloneDeep } from "lodash-es";
import { Loading, Notify } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { ref } from "vue";
import { isValidEmail } from "@shelf/is-valid-email-address";

const store = useStore();

const formDialog = ref(false);

const emptyForm = {
  name: null,
  email: null,
  phone: null,
  experience: "",
  skills: [],
};

const volunteerForm = ref(cloneDeep(emptyForm));

const resetForm = () => {
  volunteerForm.value = cloneDeep(emptyForm);
};

const volunteerContact = async () => {
  Loading.show();

  const response = await callApi({
    path: "/volunteer-contact",
    method: "post",
    payload: cloneDeep(volunteerForm.value),
  });

  Loading.hide();

  Notify.create({
    type: "positive",
    message: "Volunteer Coordinator contacted and will reach out to you soon",
  });

  formDialog.value = false;
  resetForm();
};

const sections = [
  {
    title: "Acting and Directing",
    image: "acting-directing.jpeg",
    body:
      "<p>Do you have experience performing or directing live theater? We'd love to have you join the team! Want to learn to perform? Many of our best actors learned their craft at ACT and we offer formal and &ldquo;on the job&rdquo; education. Interested in directing? Our experienced directors are always looking for students to shadow them as they assemble and produce a show.</p>",
  },
  {
    title: "Backstage",
    image: "backstage.jpeg",
    body:
      "<p>Back stage is an entire community, overseen by the stage manager who is the director's right hand during rehearsals, but is the absolute boss during performances. Meanwhile, the prop and costume folks find (or make) that hookah pipe or full length fur coat that couldn't possibly exist in Cuenca, but must be in the show. And once the show begins, the stage hands must make the elegant Saturday night dinner party become the depressing Sunday 6 am remains of the debacle. In 30 seconds. In the dark.</p>",
  },
  {
    title: "Hospitality",
    image: "hospitality.png",
    body:
      "<p>Hospitality is where ACT's famous friendliness shines! Hospitality volunteers are the first to greet our guests and the last to wish them well, making every performance feel like a celebration. From checking patrons in at the front desk to serving drinks and snacks during Happy Hour to handing out Playbills and getting folks seated, this is the crew that gets to meet, greet, eat, and seat hundreds of theatergoers for every show. It's lively, social, and full of laughs — because at ACT, being welcoming is part of the show.</p>",
  },
  {
    title: "Leadership and Management",
    image: "leadership.jpeg",
    body:
      "<p>There's the care and feeding of the physical plant. There's planning for the shows and analysis of what's required physically and financially to produce them and our other events. We need leaders to help recruit and manage groups of volunteers at our performances and in the community. And of course running our finances and record-keeping requires people with such experience. Ideally each of these critical functions operate with a team rather than a single individual because we all want to go on vacation sometimes!</p><p>If your strength is managing functions, people or finance, we'd love to explore where you might fit in this part of ACT.</p>",
  },
  {
    title: "Lights",
    image: "lights.jpeg",
    body:
      "<p>Learn the latest in digital techniques for precisely controlling constellations of light fixtures to instantly isolate sections of the stage, or to subtly change the mood of a scene from benign to benighted simply painting with light!</p>",
  },
  {
    title: "Marketing",
    image: "marketing.jpeg",
    body:
      "<p>Cuenca presents an interesting challenge for marketing an English language theater. Ways to communicate with our target audience include both in-person and digital channels. So, in addition to folks skilled in photography, graphic arts, and effective copywriting, we are on the lookout for folks with digital skills. Are you a web design whiz? Do you rule in Social Media? Or are you the in-person maven, ready to host a desk at a feria or Newcomers' Luncheon? Are you the person who can get our posters into every major restaurant and bar in town?</p>",
  },
  {
    title: "Set Design and Construction",
    image: "set-design.jpeg",
    body:
      "<p>It's Legos for giants! Learn to get the most out of arranging pre-made modules, with just the right touch of paint and custom construction, to generate a starkly real urban bedroom for one show, but in the next production to simply suggest scenes that can shift from a village street to a forest path with the movement of a prop and a change of light.</p>",
  },
  {
    title: "Sound",
    image: "sound.jpeg",
    body:
      "<p>Learn the ins and outs of theater sound, from the basics of recorded onstage doorbells and phone calls, to offstage sirens and traffic noise, to the elaborate suite needed to control and mix eight microphones and four channels of recorded sound to produce ACT's holiday productions of 1940s-style staged radio plays.</p>",
  },
];
</script>

<style scoped>
.volunteer-body :deep(p) {
  margin-bottom: 0.5em;
}
.volunteer-body :deep(p:last-child) {
  margin-bottom: 0;
}
</style>
