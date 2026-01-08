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
        name: "home",
        path: "",
        component: () => import("pages/IndexPage.vue"),

        meta: { nav: true, label: "Home" },
      },
      {
        name: "season",
        path: "season",
        component: () => import("pages/SeasonPage.vue"),
        beforeEnter: async (to, from) => {
          const store = useStore();
          await store.seasonShows();
        },
        meta: { nav: true, label: "Season" },
      },
      {
        name: "audition",
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
          label: "Audition",
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
        name: "classes",
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
          label: "Classes",
          get display() {
            const store = useStore();
            return store.courses.length > 0;
          },
        },
      },
      {
        name: "volunteer",
        path: "volunteer",
        component: () => import("pages/VolunteerPage.vue"),
        meta: {
          nav: false,
          global: true,
          sortOrder: 1,
          slug: "volunteer",
          label: "Volunteer",
        },
        beforeEnter: async (to, from) => {
          const store = useStore();
          await store.getSkills();
        },
      },
      {
        name: "find-us",
        path: "find-us",
        component: () => import("pages/SnippetPage.vue"),
        beforeEnter: async (to, from) => {
          const store = useStore();
          await store.getSnippet(to.meta.slug);
        },
        meta: {
          nav: false,
          global: true,
          sortOrder: 1,
          slug: "find-us",
          label: "Find Us",
        },
      },
      {
        name: "join-mailing-list",
        path: "join-mailing-list",
        component: () => import("pages/JoinMailingList.vue"),
        meta: {
          nav: false,
          global: true,
          sortOrder: 1,
          label: "Join Mailing List",
        },
      },
      {
        name: "about-us",
        path: "about-us",
        component: () => import("pages/SnippetPage.vue"),
        beforeEnter: async (to, from) => {
          const store = useStore();
          await store.getSnippet(to.meta.slug);
        },
        meta: { nav: true, slug: "about-us", label: "About us" },
      },
      {
        name: "support-us",
        path: "support-us",
        component: () => import("pages/SupportUs.vue"),
        beforeEnter: async () => {
          const store = useStore();
          store.supportUsConfig = await callApi({
            path: "/support-us",
            method: "get",
          });
        },
        meta: { nav: true, label: "Support Us" },
      },
      {
        name: "be-an-angel",
        path: "angel",
        component: () => import("pages/AngelPage.vue"),
        beforeEnter: async (to, from) => {
          const store = useStore();
          store.angelConfig = await callApi({ path: "/angels", method: "get" });
        },
        meta: {
          nav: true,
          global: true,
          sortOrder: 1,
          slug: "donate",
          label: "Be An Angel",
        },
      },
      {
        name: "our-act-angels",
        path: "our-act-angels",
        component: () => import("pages/OurAngels.vue"),
        beforeEnter: async () => {
          const store = useStore();
          store.ourAngels = await callApi({ path: "/angels", method: "get" });
        },
        meta: { nav: true, label: "Our ACT Angels" },
      },
      {
        name: "news",
        path: "news",
        meta: { nav: true, external: true, label: "News" },
        beforeEnter: (to, from) => {
          window.open("https://www.facebook.com/cuencacommunitytheater");
          return false;
        },
      },
      {
        name: "contact-us",
        path: "contact",
        component: () => import("pages/ContactPage.vue"),
        meta: { nav: true, label: "Contact Us" },
      },
      {
        name: "gallery",
        path: "gallery",
        component: () => import("pages/GalleryPage.vue"),
        meta: { nav: true, label: "Gallery" },
        beforeEnter: async (to, from) => {
          const store = useStore();
          await store.getGallery();
        },
      },

      {
        name: "show-details",
        path: "show-details/:slug",
        component: () => import("pages/ShowDetails.vue"),
        beforeEnter: async (to, from) => {
          const store = useStore();
          store.show = store.admin.shows.find(
            ({ slug }) => slug == to.params.slug
          );
        },
        meta: { nav: false, label: "Show Details" },
      },
      {
        name: "purchase-tickets",
        path: "purchase-tickets",
        component: () => import("pages/PurchaseTickets.vue"),
        beforeEnter: async (to, from) => {
          const store = useStore();
          await store.homeShows();
        },
        meta: { nav: false, label: "Purchase Tickets" },
      },
      {
        name: "class-details",
        path: "class-details/:slug",
        component: () => import("pages/CourseInfo.vue"),
        beforeEnter: async (to) => {
          const store = useStore();
          await store.classDetails(to.params.slug);
        },
        meta: { nav: false, label: "Class Details" },
      },
      {
        name: "flex-purchase",
        path: "flex-purchase",
        component: () => import("pages/FlexPurchase.vue"),
        beforeEnter: async () => {
          const store = useStore();
          store.flexshowPurchaseConfig();
        },
        meta: { nav: false, label: "Flex Purchase" },
      },
      {
        name: "login",
        path: "sign-in",
        component: () => import("pages/SignIn.vue"),
        meta: { label: "Login" },
      },

      /**
       * Admin Routes
       */
      {
        name: "admin",
        path: "admin",
        component: () => import("src/pages/AdminPage.vue"),
        meta: { requireAuth: true, admin: true, nav: false, label: "Admin" },
        children: [
          {
            name: "admin-dashboard",
            path: "dashboard",
            alias: "",
            component: () => import("src/pages/AdminDashboard.vue"),
            meta: {
              requireAuth: true,
              admin: true,
              nav: true,
              order: 10,
              label: "Dashboard",
            },
          },
          {
            name: "admin-shows",
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
              label: "Shows",
            },
          },
          {
            name: "admin-ticket-sales",
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
              label: "Ticket Sales",
            },
          },
          {
            name: "admin-our-angels",
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
              label: "Our Angels",
            },
          },
          {
            name: "admin-announcement-banner",
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
              label: "Announcement Banner",
            },
          },
          {
            name: "admin-flex-purchase-config",
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
              label: "Flex Purchase Config",
            },
          },
          {
            name: "admin-classes",
            path: "classes",
            component: () => import("src/pages/AdminClasses.vue"),
            beforeEnter: async () => {
              const store = useStore();

              store.admin.courses = await callApi({
                path: "/admin/courses",
                method: "get",
                useAuth: true,
              });
            },
            meta: {
              requireAuth: true,
              admin: true,
              nav: true,
              dash: true,
              order: 25,
              label: "Classes",
            },
          },
          {
            name: "admin-edit-course",
            path: "edit-course/:id",
            component: () => import("src/pages/AdminEditCourse.vue"),
            beforeEnter: async (to) => {
              const store = useStore();

              store.admin.editCourse = await callApi({
                path: `/admin/courses/${to.params.id}`,
                method: "get",
                useAuth: true,
              });
            },
            meta: {
              requireAuth: true,
              admin: true,
              nav: false,
              dash: false,
              label: "Edit Course",
            },
          },
          {
            name: "admin-site-config",
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
              label: "Site Config",
            },
          },
          {
            name: "admin-contacts",
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
              label: "Contacts",
            },
          },
          {
            name: "admin-users",
            path: "users",
            component: () => import("src/pages/AdminUsers.vue"),
            meta: {
              requireAuth: true,
              admin: true,
              nav: true,
              dash: true,
              order: 60,
              label: "Users",
            },
            beforeEnter: async (to, from) => {
              const store = useStore();
              await store.getUsers();
            },
          },
          {
            name: "admin-edit-show",
            path: "edit-show/:id",
            component: () => import("src/pages/AdminEditShow.vue"),
            beforeEnter: async (to) => {
              const store = useStore();
              await store.editShow(to.params.id);
            },
            meta: {
              requireAuth: true,
              admin: true,
              nav: false,
              label: "Edit Show",
            },
          },
          {
            name: "admin-audition-config",
            path: "audition-config",
            component: () => import("src/pages/AdminAuditionConfig.vue"),
            beforeEnter: async () => {
              const store = useStore();
              await store.getAuditionConfig();
            },
            meta: {
              requireAuth: true,
              admin: true,
              nav: false,
              label: "Audition Config",
            },
          },
          {
            name: "admin-show-gallery",
            path: "gallery",
            component: () => import("src/pages/AdminGallery.vue"),
            beforeEnter: async (to) => {
              const store = useStore();
            },
            meta: {
              requireAuth: true,
              admin: true,
              nav: false,
              label: "Show Gallery",
            },
          },

          {
            name: "admin-new-show",
            path: "new-show",
            component: () => import("src/pages/AdminEditShow.vue"),
            beforeEnter: async () => {
              const store = useStore();
              await store.newShow();
            },
            meta: {
              requireAuth: true,
              admin: true,
              nav: false,
              label: "New Show",
            },
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
