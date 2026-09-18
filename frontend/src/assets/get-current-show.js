import { parseISO } from "date-fns";

const getShowDateRange = (show) => {
  if (show.performances?.length > 0) {
    const dates = show.performances.map(({ date }) => parseISO(date).getTime());
    return { earliest: Math.min(...dates), latest: Math.max(...dates) };
  }
  // Otherwise use ticket sales start date
  const date = parseISO(show.ticket_sales_start).getTime();
  return { earliest: date, latest: date };
};

// The show currently running or coming up next — same "Current Show"
// definition AdminShows.vue uses: whichever show hasn't finished its final
// performance day yet, earliest-starting among those. Returns null if every
// show in the list is already in the past.
export default (shows) => {
  if (!shows || shows.length === 0) return null;

  const startOfToday = new Date();
  startOfToday.setHours(0, 0, 0, 0);
  const startOfTodayTime = startOfToday.getTime();

  const activeShows = shows.filter((show) => getShowDateRange(show).latest >= startOfTodayTime);
  if (activeShows.length === 0) return null;

  return activeShows.reduce((closest, show) => {
    const { earliest } = getShowDateRange(show);
    const closestEarliest = getShowDateRange(closest).earliest;
    return earliest < closestEarliest ? show : closest;
  });
};
