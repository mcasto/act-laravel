import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default () => {
  const store = useStore();
  callApi({ path: "/all-shows", method: "get" }).then((shows) => {
    store.admin.shows = shows;
  });
};
