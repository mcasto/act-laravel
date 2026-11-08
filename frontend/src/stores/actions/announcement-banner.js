import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();

  // Awaited by the home route's beforeEnter — a rejection there aborts the
  // navigation and leaves a blank page. The banner is optional content, so
  // a failed fetch (cold dev server, proxy hiccup, 500) just keeps whatever
  // banner is already in the persisted store instead of taking the page down.
  try {
    store.announcement = await callApi({
      path: "/announcement-banner",
      method: "get",
    });
  } catch (error) {
    console.warn("Announcement banner could not be loaded.", error);
  }
};
