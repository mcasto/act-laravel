<template>
  <div class="q-pa-md">
    <div class="row" v-if="store.courses.length > 0">
      <div
        class="col-12 col-sm-6 col-md-4"
        v-for="course of store.courses"
        :key="`course-${course.id}`"
      >
        <course-card :course="course"></course-card>
      </div>
    </div>

    <div v-else>
      <div class="placeholder-container text-center q-pa-xl">
        <div class="placeholder-content">
          <i class="fas fa-theater-masks placeholder-icon"></i>
          <h3 class="q-mt-md text-h4 text-weight-bold">
            Curtain's Up On Learning
          </h3>
          <p class="q-mt-md text-h6 text-grey-8">
            We're crafting our next act of creative classes. Our theater may be
            quiet now, but behind the scenes, we're preparing transformative
            experiences where skills take center stage.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import CourseCard from "src/components/CourseCard.vue";

const store = useStore();
</script>

<style scoped>
.placeholder-container {
  min-height: 60vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.placeholder-content {
  max-width: 600px;
}

.placeholder-icon {
  font-size: 4rem;
  color: #9c27b0; /* Purple accent color - change to match your theme */
}
</style>
