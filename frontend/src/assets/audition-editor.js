// Shared by the admin audition config page (AdminAuditionConfig.vue) and the
// director page's Auditions tab (DirectorAuditionEditor.vue): the q-editor
// toolbar/fonts for the audition notice, and the AI prompt that turns the
// director's .docx into HTML to paste into the editor's "< >" source view.
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
  ["viewsource"], // the "< >" button: paste the AI's HTML here
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
 * - path: root-relative ("/api/storage/posters/…"), what the HTML uses, so
 *   the notice shows the site's own poster instead of a duplicate copy.
 * - url: the same file as a full address, only so the AI can look at it to
 *   pick colors.
 */
export const posterFor = (poster) => {
  if (!poster) return { path: "", url: "" };
  const url = poster.startsWith("http")
    ? new URL(poster)
    : new URL(`/api/storage/posters/${poster}`, window.location.origin);
  url.search = "";
  return { path: url.pathname, url: url.toString() };
};

/**
 * The prompt to give an AI chat (ChatGPT, Claude…) together with the
 * audition .docx. Only the .docx needs attaching: the poster is referenced
 * by its root-relative path on our site (`posterPath`), and `posterUrl` is
 * offered just so the AI can look at it for colors. Its output is meant to
 * be pasted into the editor's source view as-is.
 */
export const buildAuditionPrompt = ({ showName, posterPath, posterUrl }) => `I've attached a Word document (.docx) with the audition information for "${showName}", a production of Azuay Community Theater (ACT) in Cuenca, Ecuador. Please turn it into an HTML fragment for the audition page on our website.

What to build:
- A single, attractive audition announcement that features the show's poster near the top. The poster is already on our website, so use exactly this tag (keep the src as this path, don't change it to a full URL or a copy of the image):
  <img src="${posterPath}" alt="${showName.replace(/"/g, "&quot;")} poster">
- Theme it from the poster: pick a background, a text color and one or two accent colors from it, and match its overall mood. You can view the poster at ${posterUrl} — if you can't open it, choose a palette that suits the show's title and tone instead, and tell me after the HTML which colors you used so I can adjust them. Keep text easy to read (strong contrast) everywhere.
- Include everything in the document, accurately, in a sensible order: what the show is about, audition dates/times/location, the roles (with ages, descriptions and requirements where given), rehearsal and performance dates, how to sign up or register, what to prepare (sides, songs, monologues), and who to contact. Don't invent, change or leave out any dates, times, names, places, links or contact details. If something seems missing or contradictory, keep it as written and tell me after the HTML.
- Make email addresses mailto: links and web addresses real links that open in a new tab (target="_blank" rel="noopener").

Technical rules (the page will reject or break otherwise):
1. Output ONLY the fragment: one <style> block followed by one <div class="act-audition"> that contains all the content. No <html>, <head>, <body>, <!DOCTYPE>, markdown or explanations inside it.
2. Every CSS selector must start with .act-audition (for example ".act-audition h2"), so nothing affects the rest of the website. Do not style html, body, :root or * on their own, and don't use class names that might already exist on our site (like text-h4, text-white, bg-*, q-*); invent your own, prefixed with "act-".
3. No JavaScript of any kind: no <script>, no onclick/onload or other on… attributes, no javascript: links.
4. No external resources other than the poster <img> above and normal links: no @import, no web fonts, no other images, no <link>, <iframe>, <svg> or forms. Use common system fonts (for example Georgia, "Times New Roman", Arial, Helvetica, sans-serif/serif fallbacks).
5. It must look good on a phone and on a desktop: max-width around 900px centered, images with max-width: 100% and height: auto, no fixed widths wider than a phone, no position: fixed.
6. Use simple, standard tags (div, section, h1–h4, p, ul/ol/li, strong/em, a, img, table if really needed). Keep the CSS in the <style> block and only use inline style="" attributes where unavoidable.

Give me the complete HTML in one code block so I can copy it and paste it into our page editor's source (< >) view.`;
