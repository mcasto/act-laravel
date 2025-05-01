import { useStore } from "../store";

export default () => {
  const store = useStore();

  return store.router.currentRoute.value.meta.admin;
};
