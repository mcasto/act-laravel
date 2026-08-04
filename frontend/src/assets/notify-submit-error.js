import { Notify } from "quasar";
import notifyNetworkError from "./notify-network-error";

// wretch rejects with an error whose `.status` is the HTTP status and whose
// `.message` is the raw response body text. A 422 here means Laravel's
// validation rejected the request — show the actual field error instead of
// the generic "check your VPN" message, which would be misleading for a
// data problem rather than a connectivity one.
export default function notifySubmitError(error, fallbackMessage) {
  if (error?.status === 422) {
    try {
      const body = JSON.parse(error.message);
      const firstError = Object.values(body.errors ?? {})[0]?.[0];

      Notify.create({
        type: "negative",
        position: "center",
        message: firstError || body.message || "Please check the form and try again.",
      });
      return;
    } catch {
      // not parseable JSON — fall through to the generic handler below
    }
  }

  notifyNetworkError(fallbackMessage);
}
