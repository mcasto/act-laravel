import { clone } from "lodash-es";
import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default (user) => {
  const store = useStore();

  callApi({
    path: `/update-user/${user.id}`,
    method: "post",
    payload: clone(user),
    useAuth: true,
  }).then((user) => {
    store.getUsers();
  });
};
