import { useStore } from "../store";
import callApi from "src/assets/call-api";
import { toPayload, withDefaults } from "src/assets/audition-form";
import { Notify } from "quasar";

export default async () => {
  const store = useStore();

  const payload = toPayload(store.admin.audition);
  const method = payload.id ? "put" : "post";
  const path = `/audition${method == "put" ? `/${payload.id}` : ""}`;

  const response = await callApi({ path, method, payload, useAuth: true });

  if (response.status == "success") {
    store.admin.audition = withDefaults(response.audition, store.admin.auditionDefaults, store.admin.show.id);

    Notify.create({
      type: "positive",
      position: "center",
      message: "Audition Saved",
    });
  }
};
