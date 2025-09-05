import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();

  const response = await callApi({
    path: "/announcement-banner",
    method: "get",
  });

  if (response.status === true) {
    store.announcement = response.contents;
  } else {
    store.announcement = false;
  }
};
