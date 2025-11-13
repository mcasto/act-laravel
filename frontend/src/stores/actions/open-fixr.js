import { useStore } from "../store";

export default (link) => {
  const store = useStore();

  const id = link ? new URL(link).pathname.match(/[0-9]+$/)[0] : "";

  store.fixr.showCheckout(parseInt(id));
};
