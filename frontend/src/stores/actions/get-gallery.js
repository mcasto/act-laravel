import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();
  const gallery = await callApi({ path: "/gallery", method: "get" });

  store.gallery = gallery;
};
