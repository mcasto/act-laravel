import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default () => {
  const store = useStore();
  callApi({ path: "/refresh-permissions", method: "get", useAuth: true }).then(
    (permissions) => {
      store.admin.user.permissions = permissions;
    }
  );
};
