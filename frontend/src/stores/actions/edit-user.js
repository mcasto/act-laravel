import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async (id) => {
  const store = useStore();

  store.admin.permissionLevels = await callApi({
    path: "/permission-levels",
    method: "get",
    useAuth: true,
  });

  store.admin.editUser = store.admin.users.find((user) => user.id == id);
};
