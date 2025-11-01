import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();

  store.courses = await callApi({ path: "/open-courses", method: "get" });
};
