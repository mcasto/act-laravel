import callApi from "src/assets/call-api";

export default async (values) => {
  const response = await callApi({
    path: "/create-contact",
    method: "post",
    payload: values,
  });
};
