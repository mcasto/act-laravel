import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default (user) => {
  const store = useStore();

  Notify.create({
    type: "warning",
    message: "Are you sure you want to delete this user?",
    actions: [
      {
        label: "No",
      },
      {
        label: "Yes",
        handler: () => {
          callApi({
            path: `/delete-user/${user.id}`,
            method: "post",
            useAuth: true,
          }).then(() => {
            store.getUsers();
          });
        },
      },
    ],
  });
};
