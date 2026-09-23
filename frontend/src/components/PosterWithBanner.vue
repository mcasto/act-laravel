<template>
  <div class="poster-wrapper">
    <img
      ref="imgEl"
      :src="src"
      :srcset="srcset"
      sizes="600px"
      :width="width"
      :height="height"
      class="poster-img"
      :style="imgStyle"
      :class="{ 'poster-img--loading': !loaded }"
      @load="loaded = true"
      @error="loaded = true"
    />
    <div v-if="!loaded" class="poster-spinner">
      <q-spinner color="primary" size="2.5em" />
    </div>
    <div v-if="soldOut" class="sold-out-ribbon">Sold Out</div>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from "vue";

const props = defineProps({
  src: { type: String, required: true },
  maxHeight: { type: String, default: "40vh" },
  maxWidth: { type: String, default: null },
  soldOut: { type: Boolean, default: false },
  // Most show posters are portrait-oriented; used only as a layout hint so
  // the browser can reserve space before the real image loads (avoids CLS).
  // The image still renders at its own natural aspect ratio once loaded —
  // UNLESS aspectRatio is set (below), which crops it to fit instead.
  width: { type: [String, Number], default: 400 },
  height: { type: [String, Number], default: 600 },
  // Opt-in — when set (e.g. "2/3"), the poster is cropped via object-fit
  // to always render at exactly this ratio instead of its own natural one,
  // so a grid of posters with inconsistent real dimensions still lines up
  // uniformly. Off by default so every existing call site keeps its
  // current "natural size, just capped" look.
  aspectRatio: { type: String, default: null },
});

// A definite aspect-ratio + object-fit on the <img> itself (a replaced
// element) lets the browser's native sizing algorithm fit it within both
// maxWidth and maxHeight simultaneously while preserving that ratio — the
// same mechanism that makes plain "max-width:100%; height:auto" images
// responsive, just with a forced ratio and cropping instead of the image's
// own natural one.
const imgStyle = computed(() => {
  if (props.aspectRatio) {
    return {
      width: "auto",
      height: "auto",
      aspectRatio: props.aspectRatio,
      objectFit: "cover",
      maxHeight: props.maxHeight,
      maxWidth: props.maxWidth ?? "100%",
    };
  }
  return {
    maxHeight: props.maxHeight,
    maxWidth: props.maxWidth ?? "100%",
  };
});

// Starts false so a freshly-mounted card (e.g. a new page of gallery
// results) shows the spinner immediately; also resets if the same
// component instance ever gets pointed at a different src.
const loaded = ref(false);
const imgEl = ref(null);

// A cached image can finish loading before Vue even attaches the @load
// listener, in which case that event never fires — check .complete once
// the DOM has actually patched to the current src, so the spinner doesn't
// spin forever on an already-loaded (cached) image.
const checkAlreadyLoaded = () => {
  nextTick(() => {
    if (imgEl.value?.complete) loaded.value = true;
  });
};

onMounted(checkAlreadyLoaded);

watch(
  () => props.src,
  () => {
    loaded.value = false;
    checkAlreadyLoaded();
  },
);

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
  opacity: 1;
  transition: opacity 0.2s ease;
}

.poster-img--loading {
  opacity: 0;
}

.poster-spinner {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f0f0f0;
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
