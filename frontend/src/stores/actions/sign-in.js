import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default ({ email, password }) => {
  const store = useStore();

  callApi({
    path: "/login",
    method: "post",
    payload: { email, password },
  }).then((response) => {
    if (response) {
      store.admin.user = response;
      store.router.push("/admin/dashboard");
    }
  });
};
