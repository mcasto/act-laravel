import { useStore } from "src/stores/store";
import { Notify } from "quasar";
import wretch from "wretch";
import { isArray } from "lodash-es";

export default ({
  path,
  method,
  payload,
  useAuth = false,
  showError = true,
}) => {
  const store = useStore();

  if (method == "get" && payload) {
    payload = `/${payload}`;
  }

  const apiCall = useAuth
    ? wretch("/api")
        .auth(`Bearer ${store.admin.user?.token}`)
        .url(path)
        [method](payload)
    : wretch("/api").url(path)[method](payload);

  return apiCall
    .json()
    .then((response) => {
      if (response.status == "error" && showError) {
        Notify.create({
          type: "negative",
          message: response.message,
          html: true,
        });

        return false;
      }

      return response;
    })
    .catch((error) => {
      if (error.status === 500 && path == "/logout") {
        console.error("Invalid or expired authentication token.");
        return;
      }

      console.log(error);
    });
};
