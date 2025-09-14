import callApi from "src/assets/call-api";
import { useStore } from "../store";
import { clone } from "lodash-es";
import { Notify } from "quasar";

export default async () => {
  /*
   <div class="text-center q-mt-sm q-pa-lg bg-secondary q-mx-xl announcement-banner">
<div class='text-h5'>
Welcome to the 2025-2026 Season!
</div>
<div class='text-h6'>
<a href="/flex-purchase" class='text-blue-10 text-bold'>Purchase Flex Tickets Now!</a>
</div>
</div>
  */

  const store = useStore();

  await callApi({
    path: "/announcement-banner",
    method: "put",
    payload: clone(store.announcement),
    useAuth: true,
  });

  Notify.create({
    type: "positive",
    message: "Announcement Banner Updated",
  });
};
