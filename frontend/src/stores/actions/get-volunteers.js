import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();
  store.admin.volunteers = await callApi({
    path: "/volunteers",
    method: "get",
    useAuth: true,
  });
};
