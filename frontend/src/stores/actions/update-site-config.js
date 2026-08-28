import callApi from "src/assets/call-api";
import { useStore } from "../store";
import { Notify } from "quasar";

export default async () => {
  const store = useStore();

  const response = await callApi({
    path: "/site-config",
    method: "put",
    payload: {
      ticket_price: store.config.ticket_price,
      sold_out_target: store.config.sold_out_target,
      ticket_email: store.config.ticket_email,
      contact_email: store.config.contact_email,
    },
    useAuth: true,
  });

  if (response && response.status === "success") {
    Notify.create({
      type: "positive",
      message: "Config updated.",
      group: false,
    });
  }
};
