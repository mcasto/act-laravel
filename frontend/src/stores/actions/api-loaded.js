import { useStore } from "../store";

export default () => {
  const store = useStore();

  store.fixr = window.fixr;
};
