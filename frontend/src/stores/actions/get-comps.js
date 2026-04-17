import { useStore } from "../store";

export default async () => {
  const store = useStore();
  const show_id = store.admin.show.id;
};
