import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();

  store.announcement = await callApi({
    path: "/announcement-banner",
    method: "get",
  });
};
