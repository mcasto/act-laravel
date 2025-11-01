import { Loading, Notify } from "quasar";
import callApi from "src/assets/call-api";

export default async (values) => {
  Loading.show();

  const response = await callApi({
    path: "/create-contact",
    method: "post",
    payload: values,
  });

  Loading.hide();

  if (response.status == "success") {
    Notify.create({
      type: "positive",
      position: "center",
      message:
        "<div class='text-center'><div class='text-h6'>Contact email sent.</div><div class='q-mt-sm'>Thanks for reaching out.<br />We will respond promptly.</div></div>",
      html: true,
      icon: false,
    });
  }
};
