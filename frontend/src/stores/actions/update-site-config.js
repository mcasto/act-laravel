import callApi from "src/assets/call-api";
import { useStore } from "../store";
import { clone } from "lodash-es";
import { Notify } from "quasar";

export default () => {
  const store = useStore();

  callApi({
    path: "/update-site-config",
    method: "post",
    payload: { config: clone(store.config), buttons: clone(store.buttons) },
    useAuth: true,
  }).then(
    Notify.create({
      type: "positive",
      message: "Config updated.",
      group: false,
    })
  );
};
