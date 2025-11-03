<template>
  <div v-if="!show">
    <q-img src="/images/empty-theater.jpg">
      <div>
        <div class="text-h6 q-px-md q-mb-sm">
          Next Show: Coming Soon!
        </div>
        <div class="q-px-md">
          Thank you for visiting! We are currently in our pre-production phase
          for our next show. Please check back soon for exciting announcements,
          or better yet, join our email list to get updates delivered straight
          to your inbox.
        </div>
      </div>
    </q-img>
  </div>

  <div v-else>
    <q-toolbar class="q-py-none">
      <q-toolbar-title class="text-center">
        {{ show.active ? "Currently Playing!" : "In Production" }}
      </q-toolbar-title>
    </q-toolbar>
    <q-separator></q-separator>

    <div class="row q-gutter-x-sm q-pa-md">
      <div class="col-12 col-md-4">
        <q-img
          :src="`/api/storage/posters/${show.poster}`"
          fit="contain"
          style="max-height: 50vh;"
          v-if="show?.poster"
          :class="Screen.gt.xs ? 'q-ml-xl' : ''"
        ></q-img>
      </div>
      <div
        class="col-12 col-md-7 q-px-xl"
        :class="Screen.gt.xs ? 'q-mx-lg' : ''"
      >
        <div class="text-h6 text-center">
          {{ show?.name }}
        </div>
        <div class="text-caption q-mb-sm text-center">
          {{ performanceDates }}
        </div>

        <div class="text-subtitle1 text-center text-bold q-mb-sm">
          {{ show?.tagline }}
        </div>

        <div v-if="show?.writer" class="text-center">
          <span class="text-bold">
            Written By:
          </span>
          {{ show?.writer }}
        </div>

        <div class="text-center">
          <span class="text-bold">
            Directed By:
          </span>
          {{ show?.director }}
        </div>

        <div class="text-center q-mt-md">
          <div v-if="ticketsStart && !show?.tentative">
            <span class="text-bold">
              Tickets On Sale:
            </span>
            {{ ticketsStart }}
          </div>

          <div v-else v-if="!show?.tentative">
            <q-btn
              label="Reserve Tickets"
              color="primary"
              icon="fa-solid fa-ticket"
              to="/purchase-tickets"
            ></q-btn>
          </div>

          <div v-if="show?.audition">
            <q-separator spaced></q-separator>
            <div class="bg-grey-3 q-pa-md">
              <div>
                Have you ever thought about acting?
              </div>
              <div>
                <q-btn
                  label="Audition For This Show"
                  to="/audition"
                  color="primary"
                ></q-btn>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div
        class="col-10 offset-1 q-pt-md"
        :class="Screen.lt.md ? 'q-mt-md q-mb-md' : ''"
        v-html="show?.info"
      ></div>
    </div>
  </div>
</template>

<script setup>
import { Screen } from "quasar";
import { format, formatISO, isFuture, parseISO } from "date-fns";
import { useStore } from "src/stores/store";
import { computed } from "vue";

const store = useStore();

// saves some typing
const show = computed(() => {
  const active = store.home.currentShow.performances.find(
    ({ date }) => date == formatISO(new Date(), { representation: "date" })
  );

  return { ...store.home.currentShow, active };
});

const performanceDates = computed(() => {
  if (!show.value) {
    return false;
  }

  const performances = show.value.performances.map(({ date }) => date).sort();
  if (performances.length == 0) {
    return false;
  }

  let first = performances.shift();
  let last = performances.length > 0 ? performances.pop() : first;

  if (show.value.tentative) {
    return format(parseISO(first), "MMM y");
  }

  first = format(parseISO(first), "PP");
  last = format(parseISO(last), "PP");

  return `${first} - ${last}`;
});

const ticketsStart = computed(() => {
  if (!show.value) {
    return false;
  }

  // if ticket sales have started, no need to display ticket start
  if (!isFuture(parseISO(show.value.ticket_sales_start))) {
    return false;
  }

  return format(parseISO(show.value.ticket_sales_start), "PP");
});
</script>
