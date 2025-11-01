import callApi from "src/assets/call-api";
import { useStore } from "../store";
import { Notify } from "quasar";
import { formatISO9075 } from "date-fns";

export default async () => {
  const store = useStore();

  const response = await callApi({
    path: `/audition/${store.admin.show.id}`,
    method: "get",
    useAuth: true,
  });

  if (response.status != "success") {
    Notify.create({
      type: "negative",
      message: response.message,
    });

    return;
  }

  store.admin.audition = response.audition || {
    show_id: store.admin.show.id,
    display_date: formatISO9075(new Date(), { representation: "date" }),
    end_display_date: formatISO9075(new Date(), { representation: "date" }),
    html: "",
  };
};
