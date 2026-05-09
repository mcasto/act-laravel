import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default async (slug) => {
  const store = useStore();
  try {
    const course = await callApi({
      path: `/course-details/${slug}`,
      method: "get",
      showError: false,
    });
    if (!course) return false;
    store.course = course;
    return true;
  } catch {
    return false;
  }
};
