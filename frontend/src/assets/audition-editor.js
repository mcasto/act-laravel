// For the admin audition config page (AdminAuditionConfig.vue): the q-editor
// toolbar/fonts for the audition page, and the show's poster URL. The page's
// HTML is generated server-side (AuditionController::generateHtml).
import { matFontDownload, matFormatSize } from "@quasar/extras/material-icons";

export const auditionToolbar = [
  ["left", "center", "right", "justify"],
  ["bold", "italic", "underline", "strike"],
  ["fullscreen"],
  [
    {
      label: "Font Size",
      icon: matFormatSize,
      fixedLabel: true,
      fixedIcon: true,
      list: "no-icons",
      options: ["size-1", "size-2", "size-3", "size-4", "size-5", "size-6", "size-7"],
    },
    {
      label: "Font Family",
      icon: matFontDownload,
      fixedIcon: true,
      list: "no-icons",
      options: [
        "arial",
        "arial_black",
        "comic_sans",
        "courier_new",
        "impact",
        "lucida_grande",
        "times_new_roman",
        "verdana",
      ],
    },
    "removeFormat",
  ],
  ["quote", "unordered", "ordered", "outdent", "indent"],
  ["link", "image"],
  ["viewsource"], // the "< >" button: edit the generated HTML directly
];

export const auditionFonts = {
  arial: "Arial",
  arial_black: "Arial Black",
  comic_sans: "Comic Sans MS",
  courier_new: "Courier New",
  impact: "Impact",
  lucida_grande: "Lucida Grande",
  times_new_roman: "Times New Roman",
  verdana: "Verdana",
};

/**
 * The show's poster as already served by the site, without the "?t=…"
 * cache-buster the admin adds after a re-upload. `poster` is either a full
 * URL (director API) or the bare filename stored on the show (admin).
 *
 * - path: root-relative ("/api/storage/posters/…").
 * - url: the same file as a full address.
 */
export const posterFor = (poster) => {
  if (!poster) return { path: "", url: "" };
  // Full URL, root-relative path (director API), or a bare filename (admin).
  const url = /^(https?:)?\//.test(poster)
    ? new URL(poster, window.location.origin)
    : new URL(`/api/storage/posters/${poster}`, window.location.origin);
  url.search = "";
  return { path: url.pathname, url: url.toString() };
};
