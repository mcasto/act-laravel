import { defineRouter } from "#q-app/wrappers";
import {
  createRouter,
  createMemoryHistory,
  createWebHistory,
  createWebHashHistory,
} from "vue-router";
import routes from "./routes";
import { useStore } from "src/stores/store";
import { Loading } from "quasar";

/*
 * If not building with SSR mode, you can
 * directly export the Router instantiation;
 *
 * The function below can be async too; either use
 * async/await or return a Promise which resolves
 * with the Router instance.
 */

export default defineRouter(function (/* { store, ssrContext } */) {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : process.env.VUE_ROUTER_MODE === "history"
    ? createWebHistory
    : createWebHashHistory;

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,

    // Leave this as is and make changes in quasar.conf.js instead!
    // quasar.conf.js -> build -> vueRouterMode
    // quasar.conf.js -> build -> publicPath
    history: createHistory(process.env.VUE_ROUTER_BASE),
  });

  Router.beforeEach(async (to, from, next) => {
    const store = useStore();

    // Admin route navigation (sidebar links, dashboard tiles) often waits on
    // a beforeEnter data fetch before the page actually changes — show a
    // spinner for that gap. Cleared unconditionally in afterEach/onError so
    // a mid-navigation redirect (e.g. to /sign-in) can never leave it stuck.
    if (to.meta.admin) {
      Loading.show({ delay: 200 });
    }

    if (
      (to.meta.requireAuth && !store.admin?.user) ||
      store.admin?.user?.status == "need-sign-in"
    ) {
      if (to.path != "/sign-in") {
        next("/sign-in");
        return;
      } else {
        next();
        return;
      }
    }

    // A token can go stale server-side (revoked, deleted from
    // personal_access_tokens, expired) with no client-side signal —
    // store.admin.user just sits in localStorage until an explicit sign-out.
    // Confirm it's genuinely still valid before letting the user into
    // anything gated by requireAuth, rather than trusting its mere
    // presence — this doubles as the existing permissions refresh.
    if (to.meta.requireAuth && store.admin?.user) {
      try {
        await store.refreshPermissions();
      } catch (error) {
        if (error?.status === 401) {
          store.admin.user = null;
          if (to.path != "/sign-in") {
            next("/sign-in");
            return;
          }
        }
        // Any other failure (network blip, 500, ...) isn't proof the
        // session itself is invalid — don't lock someone out of a page
        // they were legitimately signed into over a transient error.
      }
    }

    next();
  });

  Router.afterEach(() => {
    Loading.hide();
  });

  Router.onError(() => {
    Loading.hide();
  });

  return Router;
});
