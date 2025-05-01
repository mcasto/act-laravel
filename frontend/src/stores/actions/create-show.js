import callApi from "src/assets/call-api";
import { useStore } from "../store";
import { Notify } from "quasar";
import { cloneDeep, kebabCase } from "lodash-es";

export default () => {
  const store = useStore();
  store.admin.show.slug = `${kebabCase(
    store.admin.show.name
  )}-${new Date().getFullYear()}`;
  store.admin.show.poster = store.admin.show.poster.split("?")[0];

  const show = cloneDeep(store.admin.show);

  callApi({
    path: "/create-show",
    method: "post",
    payload: show,
    useAuth: true,
  }).then((response) => {
    if (!response) {
      return;
    }

    store.getAllShows();

    store.router.push(`/admin/edit-show/${response.id}`);

    Notify.create({
      type: "positive",
      message: "Show Created",
    });
  });
};
