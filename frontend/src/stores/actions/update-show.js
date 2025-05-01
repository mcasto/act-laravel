import callApi from "src/assets/call-api";
import { useStore } from "../store";
import { Notify } from "quasar";
import { kebabCase } from "lodash-es";

export default () => {
  const store = useStore();
  store.admin.show.slug = `${kebabCase(
    store.admin.show.name
  )}-${new Date().getFullYear()}`;

  callApi({
    path: "/update-show",
    method: "post",
    payload: store.admin.show,
    useAuth: true,
  }).then((response) => {
    Notify.create({
      type: "positive",
      message: "Show Updated",
    });
  });
};
