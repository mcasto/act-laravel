// Sections a user can be granted per-section access to — every admin route
// that shows up in the sidebar, except Dashboard (always visible, mirrors
// the sidebar). Users IS included — "full" there grants managing other
// users, "read-only" (or unset) is self-edit-only; see
// UserController::hasFullUsersAccess(). It's never hidden by nav filtering
// regardless of its access value, unlike every other section here.
// Derived live from the route table so this never drifts out of sync with
// whatever admin sections actually exist.
export default (router) =>
  router
    .getRoutes()
    .filter(({ meta, aliasOf, path }) => {
      if (!meta.nav || !meta.admin || aliasOf) return false;
      const key = path.replace("/admin/", "");
      return key !== "dashboard";
    })
    .sort((a, b) => a.meta.order - b.meta.order)
    .map(({ meta, path }) => ({
      key: path.replace("/admin/", ""),
      label: meta.label,
    }));
