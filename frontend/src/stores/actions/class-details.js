import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async (slug) => {
  const store = useStore();
  const course = await callApi({
    path: `/course-details/${slug}`,
    method: "get",
  });

  store.course = course;
};
