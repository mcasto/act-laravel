import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();
  const config = await callApi({
    path: "/flex-purchase-config",
    method: "get",
  });

  store.flex = config;
};
