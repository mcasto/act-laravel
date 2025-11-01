import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();
  const response = await callApi({ path: "/site-config", method: "get" });

  store.config = response.config;
  store.buttons = response.buttons;
};
