import callApi from "src/assets/call-api";
import { useStore } from "../store";
import { Notify } from "quasar";

export default () => {
  const store = useStore();

  callApi({
    path: "/upsert-performances",
    method: "post",
    payload: { performances: store.admin.show.performances },
    useAuth: true,
  }).then(() => {
    Notify.create({
      type: "positive",
      message: "Performances Updated",
    });
  });
};
