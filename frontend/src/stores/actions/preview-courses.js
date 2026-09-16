import callApi from "src/assets/call-api";
import { useStore } from "../store";

// Deliberately a separate store key from `courses` — that one also drives
// the site-wide "Classes" nav item's visibility (see routes.js's `classes`
// route meta.display), which must keep reflecting real open-enrollment
// state regardless of whether someone's currently viewing the preview page.
export default async () => {
  const store = useStore();

  store.previewCourses = await callApi({ path: "/course-preview", method: "get" });
};
