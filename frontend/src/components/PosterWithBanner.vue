<template>
  <div class="poster-wrapper">
    <img :src="src" class="poster-img" :style="imgStyle" />
    <div v-if="soldOut" class="sold-out-ribbon">Sold Out</div>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  src: { type: String, required: true },
  maxHeight: { type: String, default: "40vh" },
  maxWidth: { type: String, default: null },
  soldOut: { type: Boolean, default: false },
});

const imgStyle = computed(() => ({
  maxHeight: props.maxHeight,
  maxWidth: props.maxWidth ?? "100%",
}));
</script>

<style scoped>
.poster-wrapper {
  position: relative;
  overflow: hidden;
  display: inline-block;
}

.poster-img {
  display: block;
  width: auto;
}

.sold-out-ribbon {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 200%;
  padding: 8px 0;
  background: #fdd835;
  color: #000;
  font-weight: 700;
  font-size: 1rem;
  letter-spacing: 1px;
  text-align: center;
  transform: translate(-50%, -50%) rotate(-45deg);
  z-index: 2;
  pointer-events: none;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
}
</style>
