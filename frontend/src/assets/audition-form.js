// The structured audition form (AdminAuditionConfig.vue): filling in the
// defaults the API sends, and building the payload for save / generate.
import { formatISO9075, isValid, parseISO, format } from "date-fns";
import { cloneDeep } from "lodash-es";

/**
 * The audition as the form edits it. Blank location / registration note are
 * shown as their defaults (the API stores a value equal to the default as
 * null, so it keeps following the default). contacts stays null until
 * someone edits them: null = "the show's director".
 */
export const withDefaults = (audition, defaults, showId) => {
  const today = formatISO9075(new Date(), { representation: "date" });
  const a = cloneDeep(audition) || {
    show_id: showId,
    display_date: today,
    end_display_date: today,
    html: "",
  };

  return {
    ...a,
    what_to_expect: a.what_to_expect ?? "",
    location: a.location || defaults?.location || "",
    registration_url: a.registration_url ?? "",
    registration_note: a.registration_note || defaults?.registration_note || "",
    script_url: a.script_url ?? "",
    rehearsal_start_date: a.rehearsal_start ? a.rehearsal_start.slice(0, 10) : null,
    rehearsal_start_time: a.rehearsal_start ? a.rehearsal_start.slice(11, 19) : "",
    rehearsal_schedule: a.rehearsal_schedule ?? "",
    contacts: a.contacts ?? null,
    sessions: a.sessions ?? [],
    callbacks: a.callbacks ?? [],
    roles: a.roles ?? [],
  };
};

const blankToNull = (v) => (typeof v === "string" && v.trim() === "" ? null : v);

const sessionRows = (rows) =>
  rows
    .filter((r) => r.date || r.start_time || r.end_time || r.note)
    .map((r) => ({
      id: r.id ?? null,
      date: r.date,
      start_time: r.start_time || null,
      end_time: r.end_time || null,
      note: blankToNull(r.note ?? ""),
    }));

/** What the API's store/update/generate-html validate. */
export const toPayload = (a) => ({
  id: a.id,
  show_id: a.show_id,
  display_date: a.display_date,
  end_display_date: a.end_display_date,
  html: a.html ?? "",
  what_to_expect: blankToNull(a.what_to_expect),
  location: blankToNull(a.location),
  registration_url: blankToNull(a.registration_url),
  registration_note: blankToNull(a.registration_note),
  script_url: blankToNull(a.script_url),
  rehearsal_start: a.rehearsal_start_date
    ? `${a.rehearsal_start_date} ${a.rehearsal_start_time || "00:00:00"}`
    : null,
  rehearsal_schedule: blankToNull(a.rehearsal_schedule),
  contacts: a.contacts
    ? a.contacts
        .map((c) => ({
          name: blankToNull(c.name ?? ""),
          title: blankToNull(c.title ?? ""),
          phone: blankToNull(c.phone ?? ""),
          email: blankToNull(c.email ?? ""),
        }))
        .filter((c) => c.name || c.phone || c.email)
    : null,
  sessions: sessionRows(a.sessions),
  callbacks: sessionRows(a.callbacks),
  roles: a.roles
    .filter((r) => r.name || r.gender_age || r.notes)
    .map((r) => ({
      id: r.id ?? null,
      name: r.name,
      gender_age: blankToNull(r.gender_age ?? ""),
      notes: blankToNull(r.notes ?? ""),
    })),
});

/** "Thursday" for "2026-12-10", "" for anything that isn't a full date. */
export const weekday = (date) => {
  if (!date || date.length !== 10) return "";
  const d = parseISO(date);
  return isValid(d) ? format(d, "EEEE") : "";
};
