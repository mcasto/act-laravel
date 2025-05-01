import callApi from "src/assets/call-api";
import { useStore } from "../store";
import { clone } from "lodash-es";

export default () => {
  const store = useStore();

  callApi({
    path: "/update-site-config",
    method: "post",
    payload: clone(store.config),
    useAuth: true,
  }).then(console.log);
};
