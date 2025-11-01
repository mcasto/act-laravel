import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();

  const audition = await callApi({ path: "/current-audition", method: "get" });

  store.audition = audition;
};
