import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();

  if (store.home) {
    callApi({ path: "/home-shows", method: "get" }).then(
      (home) => (store.home = home)
    );

    return;
  }

  const home = await callApi({ path: "/home-shows", method: "get" });

  store.home = home;
};
