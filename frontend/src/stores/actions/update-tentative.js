import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async () => {
  const store = useStore();

  const response = await callApi({
    path: `/update-tentative/${store.admin.show.id}`,
    method: "put",
    payload: { tentative: store.admin.show.tentative },
    useAuth: true,
  });

  console.log({ response });
};
