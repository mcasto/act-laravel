import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async (slug) => {
  const store = useStore();

  const snippet = await callApi({
    path: `/get-snippet/${slug}`,
    method: "get",
  });

  store.snippets[slug] = snippet;
};
