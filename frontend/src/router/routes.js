import { store } from "quasar/wrappers";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";

const routes = [
  {
    path: "/",
    component: () => import("layouts/MainLayout.vue"),
    beforeEnter: async (to, from) => {
      const store = useStore();

      await store.announcementBanner();
      await store.seasonShows();
      await store.homeShows();
      await store.openCourses();
    },
    children: [
      {
        name: "Home",
        path: "",
        component: () => import("pages/IndexPage.vue"),

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
        name: "Support Us",
        path: "support-us",
        component: () => import("pages/SupportUs.vue"),
        beforeEnter: async () => {
          const store = useStore();
          store.supportUsConfig = await callApi({
            path: "/support-us",
            method: "get",
          });
        },
        meta: { nav: true },
      },
      {
        name: "Be An Angel",
        path: "angel",
        component: () => import("pages/AngelPage.vue"),
        beforeEnter: async (to, from) => {
          const store = useStore();
          store.angelConfig = await callApi({ path: "/angels", method: "get" });
        },
        meta: { nav: true, global: true, sortOrder: 1, slug: "donate" },
      },
      {
        name: "Our ACT Angels",
        path: "our-act-angels",
        component: () => import("pages/OurAngels.vue"),
        beforeEnter: async () => {
          const store = useStore();
          store.ourAngels = await callApi({ path: "/angels", method: "get" });
        },
        meta: { nav: true },
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
            meta: { requireAuth: true, admin: true, nav: true, order: 10 },
          },
          {
            name: "Shows",
            path: "shows",
            component: () => import("src/pages/AdminShows.vue"),
            beforeEnter: (to, from) => {
              const store = useStore();
              store.getAllShows();
            },
            meta: {
              requireAuth: true,
              admin: true,
              nav: true,
              dash: true,
              order: 15,
            },
          },
          {
            name: "Ticket Sales",
            path: "ticket-sales",
            component: () => import("src/pages/AdminTicketSales.vue"),
            beforeEnter: async () => {
              const store = useStore();
              store.admin.tickets = await callApi({
                path: "/ticket-sales",
                method: "get",
                useAuth: true,
              });
            },
            meta: {
              requireAuth: true,
              admin: true,
              nav: true,
              dash: true,
              order: 18,
            },
          },
          {
            name: "Our Angels",
            path: "our-angels",
            component: () => import("src/pages/AdminOurAngels.vue"),
            beforeEnter: (to, from) => {
              // const store = useStore();
              // store.getAllShows();
            },
            meta: {
              requireAuth: true,
              admin: true,
              nav: true,
              dash: true,
              order: 55,
            },
          },
          {
            name: "Announcement Banner",
            path: "announcement-banner",

            component: () => import("src/pages/AdminAnnouncementBanner.vue"),
            beforeEnter: async () => {
              const store = useStore();
              store.announcementBanner();
            },
            meta: {
              requireAuth: true,
              admin: true,
              nav: true,
              dash: true,
              order: 20,
            },
          },
          {
            name: "Flex Purchase Config",
            path: "flex-purchase-config",
            component: () => import("src/pages/AdminFlexPurchase.vue"),
            beforeEnter: async () => {
              const store = useStore();
              store.flexshowPurchaseConfig();
            },
            meta: {
              requireAuth: true,
              admin: true,
              nav: true,
              dash: true,
              order: 21,
            },
          },
          {
            name: "Classes",
            path: "classes",
            component: () => import("src/pages/AdminClasses.vue"),
            meta: {
              requireAuth: true,
              admin: true,
              nav: true,
              dash: true,
              order: 25,
            },
          },

          {
            name: "Site Config",
            path: "site-config",
            component: () => import("src/pages/AdminSiteConfig.vue"),
            beforeEnter: async (to, from) => {
              const store = useStore();
              await store.getSiteConfig();
            },
            meta: {
              requireAuth: true,
              admin: true,
              nav: true,
              dash: true,
              order: 30,
            },
          },
          {
            name: "Contacts",
            path: "contacts",
            component: () => import("src/pages/AdminContacts.vue"),
            beforeEnter: async () => {
              const store = useStore();

              store.admin.contacts = await callApi({
                path: "/contacts",
                method: "get",
                useAuth: true,
              });
            },
            meta: {
              requireAuth: true,
              admin: true,
              nav: true,
              dash: true,
              order: 50,
            },
          },
          {
            name: "Users",
            path: "users",
            component: () => import("src/pages/AdminUsers.vue"),
            meta: {
              requireAuth: true,
              admin: true,
              nav: true,
              dash: true,
              order: 60,
            },
            beforeEnter: async (to, from) => {
              const store = useStore();
              await store.getUsers();
            },
          },
          {
            name: "Edit Show",
            path: "edit-show/:id",
            component: () => import("src/pages/AdminEditShow.vue"),
            beforeEnter: async (to) => {
              const store = useStore();
              await store.editShow(to.params.id);
            },
            meta: { requireAuth: true, admin: true, nav: false },
          },
          {
            name: "Audition Config",
            path: "audition-config",
            component: () => import("src/pages/AdminAuditionConfig.vue"),
            beforeEnter: async () => {
              const store = useStore();
              await store.getAuditionConfig();
            },
            meta: { requireAuth: true, admin: true, nav: false },
          },
          {
            name: "Show Gallery",
            path: "gallery",
            component: () => import("src/pages/AdminGallery.vue"),
            beforeEnter: async (to) => {
              const store = useStore();
            },
            meta: { requireAuth: true, admin: true, nav: false },
          },

          {
            name: "New Show",
            path: "new-show",
            component: () => import("src/pages/AdminEditShow.vue"),
            beforeEnter: async () => {
              const store = useStore();
              await store.newShow();
            },
            meta: { requireAuth: true, admin: true, nav: false },
          },
          // {
          //   name: "Tickets",
          //   path: "tickets",
          //   component: () => import("src/pages/AdminTickets.vue"),
          //   meta: { requireAuth: true, admin: true, nav: true, dash:true },
          // },
          // {
          //   name: "Volunteers",
          //   path: "volunteers",
          //   component: () => import("src/pages/AdminVolunteers.vue"),
          //   beforeEnter: async () => {
          //     const store = useStore();
          //     await store.getSkills();
          //     await store.getVolunteers();
          //   },
          //   meta: { requireAuth: true, admin: true, nav: true, dash:true },
          // },
          // {
          //   name: "Edit Volunteer",
          //   path: "edit-volunteer/:id",
          //   component: () => import("src/pages/AdminEditVolunteer.vue"),
          //   beforeEnter: async () => {
          //     const store = useStore();
          //     await store.getSkills();
          //     await store.getVolunteers();
          //   },
          //   meta: { requireAuth: true, admin: true },
          // },
          // {
          //   name: "Patrons",
          //   path: "patrons",
          //   component: () => import("src/pages/AdminPatrons.vue"),
          //   meta: { requireAuth: true, admin: true, nav: true, dash:true },
          // },
          // {
          //   name: "Donations",
          //   path: "donations",
          //   component: () => import("src/pages/AdminDonations.vue"),
          //   meta: { requireAuth: true, admin: true, nav: true, dash:true },
          // },
          // {
          //   name: "Payments",
          //   path: "payments",
          //   component: () => import("src/pages/AdminPayments.vue"),
          //   meta: { requireAuth: true, admin: true, nav: true, dash:true },
          // },
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
