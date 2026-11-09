// Date/time formatting for the director pages. Performance dates and times
// are local Cuenca calendar values (Y-m-d / H:i:s) — parsed as local, never
// as UTC, so they show exactly as entered.
import { format, parseISO } from "date-fns";

export const formatDate = (ymd, pattern = "EEE, MMM d") => (ymd ? format(parseISO(ymd), pattern) : "");

export const formatTime = (hms) => (hms ? format(parseISO(`2000-01-01T${hms}`), "h:mm a") : "");

export const performanceRange = (first, last) => {
  if (!first) return "No performances scheduled";
  if (!last || first === last) return formatDate(first, "MMM d, yyyy");
  const sameYear = first.slice(0, 4) === last.slice(0, 4);
  return `${formatDate(first, sameYear ? "MMM d" : "MMM d, yyyy")} – ${formatDate(last, "MMM d, yyyy")}`;
};

/** Today's date in Cuenca as Y-m-d (what the server uses for switch-overs). */
export const localToday = () =>
  new Intl.DateTimeFormat("en-CA", { timeZone: "America/Guayaquil" }).format(new Date());
