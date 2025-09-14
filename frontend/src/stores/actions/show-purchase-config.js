import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();
  store.purchase = await callApi({
    path: "/show-purchase-config",
    method: "get",
  });
};
