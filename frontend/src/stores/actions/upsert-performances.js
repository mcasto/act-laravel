import callApi from "src/assets/call-api";
import { useStore } from "../store";
import { Notify } from "quasar";
import { sortBy } from "lodash-es";

export default () => {
  const store = useStore();

  callApi({
    path: "/upsert-performances",
    method: "post",
    payload: { performances: store.admin.show.performances },
    useAuth: true,
  }).then((response) => {
    Notify.create({
      type: "positive",
      message: "Performances Updated",
    });

    // A performance just transitioned to sold out (not one that already
    // was) — confirm before emailing the notification list, since marking
    // the wrong performance sold out by mistake is an easy slip and this
    // is the last chance to catch it before mail goes out.
    if (response?.newly_sold_out?.length) {
      confirmSoldOutNotifications(response.newly_sold_out);
    }
  });

  store.admin.show.performances = sortBy(store.admin.show.performances, [
    "date",
    "start_time",
  ]);
};

// Performance labels are built server-side from admin-entered show names,
// so escape before interpolating into the HTML notification below.
const escapeHtml = (str) =>
  String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");

const confirmSoldOutNotifications = (performances) => {
  const list = performances
    .map((p) => `<div>• ${escapeHtml(p.label)}</div>`)
    .join("");

  Notify.create({
    type: "warning",
    position: "center",
    multiLine: true,
    html: true,
    message: `<div>Just marked sold out:</div>${list}<div class="q-mt-sm">Send the SOLD OUT notifications?</div>`,
    actions: [
      { label: "No" },
      {
        label: "Yes, Send",
        handler: async () => {
          const response = await callApi({
            path: "/performances/sold-out-notifications",
            method: "post",
            payload: { performance_ids: performances.map((p) => p.id) },
            useAuth: true,
          });

          if (!response || response.status !== "success") {
            Notify.create({
              type: "negative",
              message: response?.message || "Failed to send notifications.",
            });
            return;
          }

          Notify.create({
            type: "positive",
            message: `Sold out notifications sent (${response.sent} email(s)).`,
          });
        },
      },
    ],
  });
};
