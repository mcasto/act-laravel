import callApi from "src/assets/call-api";
import { useStore } from "../store";

// Same as openShowDetails, but resolves the show from a Flex early-access
// link's uid instead of a public slug — used by the /show-details/flex/:uid
// route (shared directly with flex ticket holders).
export default async (uid) => {
  const store = useStore();

  const response = await callApi({
    path: `/shows/flex/${uid}`,
    method: "get",
  });

  if (!response?.show) return false;

  store.show = response.show;
  await store.homeShows();
  store.showDetailsIsFlexAccess = true;
  store.justOpenedShowDetails = true;
  store.showDetailsDialog = true;

  return true;
};
