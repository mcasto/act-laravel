import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default () => {
  const store = useStore();

  if (!store.admin.user) {
    return;
  }

  callApi({ path: "/logout", method: "post", useAuth: true }).then(() => {
    store.admin.user = null;
    store.router.push("/sign-in");
  });
};
