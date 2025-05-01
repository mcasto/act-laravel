import { cloneDeep, remove } from "lodash-es";
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default (id) => {
  const store = useStore();

  Notify.create({
    type: "warning",
    message: "Are you sure you want to delete this show?",
    actions: [
      {
        label: "No",
      },
      {
        label: "Yes",
        handler: () => {
          callApi({
            path: `/delete-show/${id}`,
            method: "post",
            useAuth: true,
          }).then(() => {
            remove(store.admin.shows, (show) => show.id == id);
          });
        },
      },
    ],
  });
};
