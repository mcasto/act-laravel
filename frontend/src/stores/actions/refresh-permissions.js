import callApi from "src/assets/call-api";
import { useStore } from "../store";

// Returns the request promise (previously discarded) so callers — the
// router guard, specifically — can await it and react to a failure, e.g. a
// token that's gone stale server-side (revoked/deleted/expired) without
// store.admin.user ever finding out on its own.
export default async () => {
  const store = useStore();
  const permissions = await callApi({
    path: "/refresh-permissions",
    method: "get",
    useAuth: true,
  });
  store.admin.user.permissions = permissions;
};
