import { useStore } from "../store";

export default (id) => {
  const store = useStore();
  store.fixr.showCheckout(parseInt(id));
};
