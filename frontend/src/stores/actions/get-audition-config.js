import callApi from "src/assets/call-api";
import { withDefaults } from "src/assets/audition-form";
import { useStore } from "../store";
import { Notify } from "quasar";

export default async () => {
  const store = useStore();

  const response = await callApi({
    path: `/audition/${store.admin.show.id}`,
    method: "get",
    useAuth: true,
  });

  if (response.status != "success") {
    Notify.create({
      type: "negative",
      message: response.message,
    });

    return;
  }

  // Kept apart from store.admin.audition, which a save replaces wholesale.
  store.admin.auditionPreviewUid = response.preview_uid;
  // { contacts, location, registration_note, performance_dates } from the show / ACT
  store.admin.auditionDefaults = response.defaults;
  store.admin.auditionAiAvailable = response.ai_available;

  store.admin.audition = withDefaults(response.audition, response.defaults, store.admin.show.id);
};
