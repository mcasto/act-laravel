import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();
  store.config = await callApi({ path: "/site-config", method: "get" });
};
