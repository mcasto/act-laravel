import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();
  store.flex = await callApi({ path: "/flex-purchase-config", method: "get" });
};
