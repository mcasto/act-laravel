import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();
  const home = await callApi({ path: "/home-shows", method: "get" });

  store.home = home;
};
