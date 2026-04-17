import { uid } from "quasar";
import { useStore } from "../store";
import callApi from "src/assets/call-api";

export default async () => {
  const store = useStore();

  const comp = store.admin.comp;

  const payload = { ...comp, show_id: store.admin.show.id };

  return await callApi({
    path: "/comp",
    method: "post",
    payload,
    useAuth: true,
  });
};
