import callApi from "src/assets/call-api";
import { useStore } from "../store";

// Fetches a show by slug and opens it in the shared ShowDetailsDialog
// (rendered once in MainLayout.vue) rather than navigating to a dedicated
// page — used by every in-app "view this show" trigger (posters, View
// Details/Gallery buttons) so clicking one never leaves the page you're on.
export default async (slug) => {
  const store = useStore();

  const response = await callApi({
    path: "/shows/slug",
    method: "get",
    payload: slug,
  });

  if (!response || response.status === "error") return false;

  store.show = response;
  // Needed for isActiveShow (Reserve Tickets visibility) — no-ops as a
  // background refresh if already loaded.
  await store.homeShows();
  store.showDetailsIsFlexAccess = false;
  store.justOpenedShowDetails = true;
  store.showDetailsDialog = true;

  return true;
};
