import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();

  store.admin.show = await callApi({
    path: "/new-show-template",
    method: "get",
    useAuth: true,
  });
};
