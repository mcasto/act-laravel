import callApi from "src/assets/call-api";
import { useStore } from "../store";
import { Notify } from "quasar";
import { sortBy } from "lodash-es";

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

  store.admin.show.performances = sortBy(store.admin.show.performances, [
    "date",
    "start_time",
  ]);
};
