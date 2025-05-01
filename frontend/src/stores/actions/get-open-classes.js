import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();
  const classes = await callApi({ path: "/open-courses", method: "get" });

  store.courses = classes;
};
