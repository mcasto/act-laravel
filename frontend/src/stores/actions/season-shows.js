import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();

  if (store.admin.shows) {
    callApi({ path: "/season-shows", method: "get" }).then(
      (shows) => (store.admin.shows = shows)
    );
    return;
  }

  const shows = await callApi({ path: "/season-shows", method: "get" });
  store.admin.shows = shows;
};
