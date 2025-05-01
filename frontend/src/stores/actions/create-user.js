import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default (model) => {
  const store = useStore();

  callApi({
    path: "/create-user",
    method: "post",
    payload: model.user,
    useAuth: true,
  }).then(() => {
    store.getUsers();
    model.visible = false;
  });
};
