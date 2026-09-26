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
import createUser from "./actions/create-user";
import deleteUser from "./actions/delete-user";
import homeShows from "./actions/home-shows";
import seasonShows from "./actions/season-shows";
import getSnippet from "./actions/get-snippet";
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
import openShowDetails from "./actions/open-show-details";
import openFlexShowDetails from "./actions/open-flex-show-details";
import showPurchaseConfig from "./actions/show-purchase-config";
import updateAnnouncementBanner from "./actions/update-announcement-banner";
import refreshPermissions from "./actions/refresh-permissions";
import getAuditionConfig from "./actions/get-audition-config";
import saveAuditionConfig from "./actions/save-audition-config";
import openCourses from "./actions/open-courses";
import previewCourses from "./actions/preview-courses";
import saveCompConfig from "./actions/save-comp-config";

export const useStore = defineStore(
  "store",
  () => {
    const state = {
      admin: ref({}),
      angelConfig: ref(null),
      announcement: ref(false),
      audition: ref(null),
      buttons: ref(null),
      config: ref(null),
      course: ref(null),
      courses: ref([]),
      previewCourses: ref([]),
      flex: ref(null),
      gallery: ref(null),
      home: ref(null),
      ourAngels: ref(null),
      patron: ref(null),
      paymentMethods: ref(null),
      // Default state for the "Send Emails" toggle on the New Ticket Sale
      // form — deliberately a persisted preference (not reset per form),
      // so a box office catching up on bulk data entry can turn it off
      // once and have it stay off across sales/sessions until turned back
      // on, rather than re-toggling on every single form.
      send_mail: 1,
      show: ref(null),
      showDetailsDialog: ref(false),
      // Set by openFlexShowDetails() — the show details dialog hides the
      // usual "Tickets On Sale: <date>" notice and routes "Reserve
      // Tickets" to /purchase-tickets?flex=1 when true, since a flex
      // early-access viewer can buy before the public sale opens.
      showDetailsIsFlexAccess: ref(false),
      // Consumed by the router's beforeEach (see router/index.js) — the
      // show-details/flex-show-details routes open the dialog and then
      // redirect to home, which is itself a second full navigation that
      // would otherwise immediately re-trigger the "close the dialog on
      // navigation" guard and wipe it out before it's ever seen. Set right
      // before that redirect so that one specific navigation skips the
      // auto-close.
      justOpenedShowDetails: ref(false),
      skills: ref([]),
      snippets: ref({}),
      supportUsConfig: ref(null),
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
      getAuditionConfig,
      getGallery,
      getSkills,
      getSiteConfig,
      getSnippet,
      getUsers,
      homeShows,
      newContact,
      newShow,
      openCourses,
      previewCourses,
      openFixr,
      openShowDetails,
      openFlexShowDetails,
      refreshPermissions,
      saveAuditionConfig,
      saveCompConfig,
      seasonShows,
      showPurchaseConfig,
      signIn,
      signOut,
      updateAnnouncementBanner,
      updateShow,
      upsertPerformances,
      updateSiteConfig,
      updateTentative,
    };

    return { ...state, ...getters, ...actions };
  },
  {
    persist: {
      key: "azuay-community-theater",
      // patron (email/first_name/last_name/phone/flex_packages) is PII looked
      // up during a purchase flow — keep it in memory for the current session
      // only, never written to localStorage.
      //
      // showDetailsDialog/showDetailsIsFlexAccess/justOpenedShowDetails are
      // transient UI state for ShowDetailsDialog.vue — persisting them would
      // let a stale "dialog was open"/"just opened" flag survive a full page
      // reload and either pop the dialog open unexpectedly or (worse) make
      // the router's close-on-navigate guard wrongly skip itself on the next
      // real navigation, thinking it's mid-redirect from opening the dialog.
      omit: [
        "patron",
        "showDetailsDialog",
        "showDetailsIsFlexAccess",
        "justOpenedShowDetails",
      ],
    },
  },
);
