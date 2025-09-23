import { store } from "quasar/wrappers";
import { useStore } from "src/stores/store";

const routes = [
  {
    path: "/",
    component: () => import("layouts/MainLayout.vue"),
    children: [
      {
        name: "Home",
        path: "",
        component: () => import("pages/IndexPage.vue"),
        beforeEnter: async (to, from) => {
          const store = useStore();

          await store.announcementBanner();
          await store.seasonShows();
          await store.homeShows();
        },
        meta: { nav: true },
      },
      {
        name: "Season",
        path: "season",
        component: () => import("pages/SeasonPage.vue"),
        beforeEnter: async (to, from) => {
          const store = useStore();
          await store.seasonShows();
        },
        meta: { nav: true },
      },
      {
        name: "Donate",
        path: "donate",
        component: () => import("pages/SnippetPage.vue"),
        beforeEnter: async (to, from) => {
          const store = useStore();
          await store.getSnippet(to.meta.slug);
        },
        meta: { nav: false, global: true, sortOrder: 1, slug: "donate" },
      },
      {
        name: "Audition",
        path: "audition",
        component: () => import("pages/AuditionPage.vue"),
        beforeEnter: async () => {
          const store = useStore();
          await store.currentAudition();
        },
        meta: {
          nav: false,
          global: true,
          sortOrder: 1,
          slug: "audition",
          conditional: "audition",
          get display() {
            const store = useStore();
            if (!store.audition) {
              return false;
            }

            return Object.keys(store.audition).length > 0;
          },
        },
      },
      {
        name: "Classes",
        path: "classes",
        component: () => import("pages/CoursesPage.vue"),
        beforeEnter: async (to, from, next) => {
          const store = useStore();
          await store.getOpenClasses();

          if (store.courses.length === 1) {
            const route = `/class-details/${store.courses[0].slug}`;
            next(route);
            return;
          }
          next();
        },
        meta: {
          nav: false,
          global: true,
          sortOrder: 1,
          slug: "classes",
          conditional: "courses",
          get display() {
            const store = useStore();
            return store.courses.length > 0;
          },
        },
      },
      {
        name: "Volunteer",
        path: "volunteer",
        component: () => import("pages/VolunteerPage.vue"),
        meta: { nav: false, global: true, sortOrder: 1, slug: "volunteer" },
        beforeEnter: async (to, from) => {
          const store = useStore();
          await store.getSkills();
        },
      },
      {
        name: "Find Us",
        path: "find-us",
        component: () => import("pages/SnippetPage.vue"),
        beforeEnter: async (to, from) => {
          const store = useStore();
          await store.getSnippet(to.meta.slug);
        },
        meta: { nav: false, global: true, sortOrder: 1, slug: "find-us" },
      },
      {
        name: "Join Mailing List",
        path: "join-mailing-list",
        component: () => import("pages/JoinMailingList.vue"),
        meta: { nav: false, global: true, sortOrder: 1 },
      },
      {
        name: "About us",
        path: "about-us",
        component: () => import("pages/SnippetPage.vue"),
        beforeEnter: async (to, from) => {
          const store = useStore();
          await store.getSnippet(to.meta.slug);
        },
        meta: { nav: true, slug: "about-us" },
      },
      {
        name: "News",
        path: "news",
        meta: { nav: true, external: true },
        beforeEnter: (to, from) => {
          window.open("https://www.facebook.com/cuencacommunitytheater");
          return false;
        },
      },
      {
        name: "Contact Us",
        path: "contact",
        component: () => import("pages/ContactPage.vue"),
        meta: { nav: true },
      },
      {
        name: "Gallery",
        path: "gallery",
        component: () => import("pages/GalleryPage.vue"),
        meta: { nav: true },
        beforeEnter: async (to, from) => {
          const store = useStore();
          await store.getGallery();
        },
      },
      {
        name: "Show Details",
        path: "show-details/:slug",
        component: () => import("pages/ShowDetails.vue"),
        beforeEnter: async (to, from) => {
          const store = useStore();
          store.show = store.admin.shows.find(
            ({ slug }) => slug == to.params.slug
          );
        },
        meta: { nav: false },
      },
      {
        name: "Purchase Tickets",
        path: "purchase-tickets",
        component: () => import("pages/PurchaseTickets.vue"),
        beforeEnter: async (to, from) => {
          const store = useStore();
          await store.homeShows();
        },
        meta: { nav: false },
      },
      {
        name: "Class Details",
        path: "class-details/:slug",
        component: () => import("pages/CourseInfo.vue"),
        beforeEnter: async (to) => {
          const store = useStore();
          await store.classDetails(to.params.slug);
        },
        meta: { nav: false },
      },
      {
        name: "Flex Purchase",
        path: "flex-purchase",
        component: () => import("pages/FlexPurchase.vue"),
        beforeEnter: async () => {
          const store = useStore();
          store.flexshowPurchaseConfig();
        },
        meta: { nav: false },
      },
      {
        name: "Login",
        path: "sign-in",
        component: () => import("pages/SignIn.vue"),
      },

      /**
       * Admin Routes
       */
      {
        name: "Admin",
        path: "admin",
        component: () => import("src/pages/AdminPage.vue"),
        meta: { requireAuth: true, admin: true, nav: false },
        children: [
          {
            name: "Dashboard",
            path: "dashboard",
            alias: "",
            component: () => import("src/pages/AdminDashboard.vue"),
            meta: { requireAuth: true, admin: true, nav: true },
          },
          {
            name: "Announcement",
            path: "announcement-banner",
            alias: "",
            component: () => import("src/pages/AdminAnnouncementBanner.vue"),
            beforeEnter: async () => {
              const store = useStore();
              store.announcementBanner();
            },
            meta: { requireAuth: true, admin: true, nav: true },
          },
          {
            name: "Site Config",
            path: "site-config",
            component: () => import("src/pages/AdminSiteConfig.vue"),
            beforeEnter: async (to, from) => {
              const store = useStore();
              await store.getSiteConfig();
            },
            meta: { requireAuth: true, admin: true, nav: true },
          },
          {
            name: "Shows",
            path: "shows",
            component: () => import("src/pages/AdminShows.vue"),
            beforeEnter: (to, from) => {
              const store = useStore();
              store.getAllShows();
            },
            meta: { requireAuth: true, admin: true, nav: true },
          },
          {
            name: "Tickets",
            path: "tickets",
            component: () => import("src/pages/AdminTickets.vue"),
            meta: { requireAuth: true, admin: true, nav: true },
          },
          {
            name: "Volunteers",
            path: "volunteers",
            component: () => import("src/pages/AdminVolunteers.vue"),
            beforeEnter: async () => {
              const store = useStore();
              await store.getSkills();
              await store.getVolunteers();
            },
            meta: { requireAuth: true, admin: true, nav: true },
          },
          {
            name: "Edit Volunteer",
            path: "edit-volunteer/:id",
            component: () => import("src/pages/AdminEditVolunteer.vue"),
            beforeEnter: async () => {
              const store = useStore();
              await store.getSkills();
              await store.getVolunteers();
            },
            meta: { requireAuth: true, admin: true },
          },
          {
            name: "Patrons",
            path: "patrons",
            component: () => import("src/pages/AdminPatrons.vue"),
            meta: { requireAuth: true, admin: true, nav: true },
          },
          {
            name: "Donations",
            path: "donations",
            component: () => import("src/pages/AdminDonations.vue"),
            meta: { requireAuth: true, admin: true, nav: true },
          },
          {
            name: "Payments",
            path: "payments",
            component: () => import("src/pages/AdminPayments.vue"),
            meta: { requireAuth: true, admin: true, nav: true },
          },
          {
            name: "Contacts",
            path: "contacts",
            component: () => import("src/pages/AdminContacts.vue"),
            meta: { requireAuth: true, admin: true, nav: true },
          },
          {
            name: "Users",
            path: "users",
            component: () => import("src/pages/AdminUsers.vue"),
            meta: { requireAuth: true, admin: true, nav: true },
            beforeEnter: async (to, from) => {
              const store = useStore();
              await store.getUsers();
            },
          },
          {
            name: "Edit Show",
            path: "edit-show/:id",
            component: () => import("src/pages/EditShow.vue"),
            beforeEnter: async (to, from) => {
              const store = useStore();
              await store.editShow(to.params.id);
            },
            meta: { requireAuth: true, admin: true, nav: false },
          },
          {
            name: "New Show",
            path: "new-show",
            component: () => import("src/pages/EditShow.vue"),
            beforeEnter: async (to, from) => {
              const store = useStore();
              await store.newShow();
            },
            meta: { requireAuth: true, admin: true, nav: false },
          },
        ],
      },
    ],
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: "/:catchAll(.*)*",
    component: () => import("pages/ErrorNotFound.vue"),
    meta: { nav: false },
  },
];

export default routes;
