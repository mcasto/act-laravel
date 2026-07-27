import { Notify } from "quasar";

// Shown when a request fails outright (network/CORS/timeout, not a graceful
// API error response) — VPNs are the most common cause reported by patrons.
export default function notifyNetworkError(message) {
  Notify.create({
    type: "negative",
    position: "center",
    message: `${message} If you're using a VPN, please turn it off and try again.`,
    timeout: 8000,
  });
}
