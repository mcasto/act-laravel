import { clone } from "lodash-es";
import callApi from "src/assets/call-api";
import { useStore } from "../store";

export default () => {
  const store = useStore();

  console.log({ updateUser: store.admin.editUser });

  // callApi({
  //   path: `/update-user/${user.id}`,
  //   method: "post",
  //   payload: clone(user),
  //   useAuth: true,
  // }).then((user) => {
  //   store.getUsers();
  // });
};
