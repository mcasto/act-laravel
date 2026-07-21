<template>
  <div class="poster-wrapper">
    <img
      :src="src"
      :srcset="srcset"
      sizes="600px"
      :width="width"
      :height="height"
      class="poster-img"
      :style="imgStyle"
    />
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
  // Most show posters are portrait-oriented; used only as a layout hint so
  // the browser can reserve space before the real image loads (avoids CLS).
  // The image still renders at its own natural aspect ratio once loaded.
  width: { type: [String, Number], default: 400 },
  height: { type: [String, Number], default: 600 },
});

const imgStyle = computed(() => ({
  maxHeight: props.maxHeight,
  maxWidth: props.maxWidth ?? "100%",
}));

// Posters are saved as two variants (see ImageController::update): a 1200px-wide
// original and a 600px-wide small variant at the same filename under "posters-sm/".
const srcset = computed(() => {
  if (!props.src.includes("/posters/")) return undefined;
  const smallSrc = props.src.replace("/posters/", "/posters-sm/");
  return `${smallSrc} 600w, ${props.src} 1200w`;
});
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
