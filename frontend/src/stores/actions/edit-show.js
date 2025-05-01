import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default (id) => {
  const store = useStore();

  return callApi({
    path: "/show",
    method: "get",
    payload: id,
    useAuth: true,
  }).then((show) => (store.admin.show = show));
};
