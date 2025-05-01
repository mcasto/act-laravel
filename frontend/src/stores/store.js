import { defineStore } from "pinia";
import { ref, computed } from "vue";
import signIn from "./actions/sign-in";
import signOut from "./actions/sign-out";
import adminRoute from "./getters/admin-route";
import getAllShows from "./actions/get-all-shows";
import editShow from "./actions/edit-show";
import updateShow from "./actions/update-show";
import upsertPerformances from "./actions/upsert-performances";
import deleteShow from "./actions/delete-show";
import newShow from "./actions/new-show";
import createShow from "./actions/create-show";
import getSiteConfig from "./actions/get-site-config";
import updateSiteConfig from "./actions/update-site-config";
import getUsers from "./actions/get-users";
import updateUser from "./actions/updateUser";
import createUser from "./actions/create-user";
import deleteUser from "./actions/delete-user";
import homeShows from "./actions/home-shows";
import seasonShows from "./actions/season-shows";
import getSnippet from "./actions/get-snippet";
import getOpenClasses from "./actions/get-open-classes";
import getGallery from "./actions/get-gallery";
import newContact from "./actions/new-contact";
import currentAudition from "./actions/current-audition";
import classDetails from "./actions/class-details";
import getSkills from "./actions/get-skills";

export const useStore = defineStore(
  "store",
  () => {
    const state = {
      admin: ref({}),
      audition: ref(null),
      config: ref(null),
      course: ref(null),
      courses: ref([]),
      gallery: ref(null),
      home: ref({}),
      show: ref(null),
      skills: ref([]),
      snippets: ref({}),
      users: ref(null),
    };
    const getters = { adminRoute: computed(adminRoute) };
    const actions = {
      classDetails,
      createShow,
      createUser,
      currentAudition,
      deleteShow,
      deleteUser,
      editShow,
      getAllShows,
      getGallery,
      getOpenClasses,
      getSkills,
      getSiteConfig,
      getSnippet,
      getUsers,
      homeShows,
      newContact,
      newShow,
      seasonShows,
      signIn,
      signOut,
      updateShow,
      upsertPerformances,
      updateSiteConfig,
      updateUser,
    };

    return { ...state, ...getters, ...actions };
  },
  {
    persist: {
      key: "azuay-community-theater",
    },
  }
);
