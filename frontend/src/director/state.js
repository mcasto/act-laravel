// Shared reactive state for the /director pages.
import { ref } from "vue";

/** True while the server can't be reached (set by api.js and browser events). */
export const offline = ref(typeof navigator !== "undefined" && navigator.onLine === false);

/**
 * When the data currently on screen was fetched (ms epoch), or null. Pages
 * set it so the offline banner can say how old the info is.
 */
export const shownAt = ref(null);
