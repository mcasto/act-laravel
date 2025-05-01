import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();
  store.admin.users = await callApi({
    path: "/get-users",
    method: "get",
    useAuth: true,
  });
};
