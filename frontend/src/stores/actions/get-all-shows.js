import callApi from "src/assets/call-api";
import { useStore } from "../store";

// Returns the request promise (previously discarded) so callers that need
// store.admin.shows populated before proceeding — e.g. the admin-auditions/
// admin-volunteer-needs route guards, which compute the current show from
// it immediately after awaiting this — can actually await it instead of
// racing an in-flight request.
export default async () => {
  const store = useStore();
  const shows = await callApi({ path: "/all-shows", method: "get", useAuth: true });
  store.admin.shows = shows;
};
