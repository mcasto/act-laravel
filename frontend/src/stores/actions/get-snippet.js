import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async (slug) => {
  const store = useStore();

  if (store.snippets[slug]) {
    callApi({
      path: `/get-snippet/${slug}`,
      method: "get",
    }).then((snippet) => (store.snippets[slug] = snippet));
    return;
  }

  const snippet = await callApi({
    path: `/get-snippet/${slug}`,
    method: "get",
  });

  store.snippets[slug] = snippet;
};
