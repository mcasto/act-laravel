import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();

  const shows = await callApi({ path: "/season-shows", method: "get" });

  store.admin.shows = shows;
};
