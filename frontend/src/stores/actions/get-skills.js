import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();
  store.skills = await callApi({ path: "/skills", method: "get" });
};
