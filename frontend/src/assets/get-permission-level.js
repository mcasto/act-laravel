// Owner always has full access everywhere, regardless of stored
// UserPermission rows — see UserController::updatePermissions(), which
// refuses to ever create/change rows for the owner.
export default (user, section) => {
  if (!user) return "none";
  if (user.is_owner) return "full";
  return user.permissions?.find((p) => p.section === section)?.access ?? "none";
};
