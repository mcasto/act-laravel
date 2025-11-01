import { cloneDeep } from "lodash-es";
import { useStore } from "../store";
import callApi from "src/assets/call-api";
import { Notify } from "quasar";

export default async () => {
  const store = useStore();

  const payload = cloneDeep(store.admin.audition);
  const method = payload.id ? "put" : "post";
  const path = `/audition${method == "put" ? `/${payload.id}` : ""}`;

  const response = await callApi({ path, method, payload, useAuth: true });

  if (response.status == "success") {
    store.admin.audition = response.audition;

    Notify.create({
      type: "positive",
      position: "center",
      message: "Audition Saved",
    });
  }
};
