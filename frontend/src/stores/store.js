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
import updateTentative from "./actions/update-tentative";
import announcementBanner from "./actions/announcement-banner";
import flexshowPurchaseConfig from "./actions/flex-purchase-config";
import apiLoaded from "./actions/api-loaded";
import openFixr from "./actions/open-fixr";
import showPurchaseConfig from "./actions/show-purchase-config";
import updateAnnouncementBanner from "./actions/update-announcement-banner";
import getVolunteers from "./actions/get-volunteers";
import refreshPermissions from "./actions/refresh-permissions";

export const useStore = defineStore(
  "store",
  () => {
    const state = {
      admin: ref({}),
      announcement: ref(false),
      audition: ref(null),
      config: ref(null),
      course: ref(null),
      courses: ref([]),
      flex: ref(null),
      gallery: ref(null),
      home: ref(null),
      show: ref(null),
      skills: ref([]),
      snippets: ref({}),
      users: ref(null),
    };
    const getters = { adminRoute: computed(adminRoute) };
    const actions = {
      announcementBanner,
      apiLoaded,
      classDetails,
      createShow,
      createUser,
      currentAudition,
      deleteShow,
      deleteUser,
      editShow,
      flexshowPurchaseConfig,
      getAllShows,
      getGallery,
      getOpenClasses,
      getSkills,
      getSiteConfig,
      getSnippet,
      getVolunteers,
      getUsers,
      homeShows,
      newContact,
      newShow,
      openFixr,
      refreshPermissions,
      seasonShows,
      showPurchaseConfig,
      signIn,
      signOut,
      updateAnnouncementBanner,
      updateShow,
      upsertPerformances,
      updateSiteConfig,
      updateTentative,
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
