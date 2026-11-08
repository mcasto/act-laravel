import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();

  if (store.home) {
    // Background refresh over the persisted copy — a failure just keeps
    // that copy, but must be caught or it's an unhandled rejection.
    callApi({ path: "/home-shows", method: "get" })
      .then((home) => (store.home = home))
      .catch((error) => console.warn("Home shows refresh failed.", error));

    return;
  }

  const home = await callApi({ path: "/home-shows", method: "get" });

  store.home = home;
};
