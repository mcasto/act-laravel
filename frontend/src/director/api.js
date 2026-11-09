// API client for the /director pages. Deliberately separate from
// src/assets/call-api.js and the admin store: the director token is a
// 'director'-ability Sanctum token (POST /api/app/pair) that the server
// refuses on every regular admin route, and it lives under its own
// localStorage key so it never mixes with an admin login on the same browser.

const TOKEN_KEY = "act-director-token";
const USER_KEY = "act-director-user";

const read = (key) => {
  try {
    return localStorage.getItem(key);
  } catch {
    return null;
  }
};
const write = (key, value) => {
  try {
    if (value === null) localStorage.removeItem(key);
    else localStorage.setItem(key, value);
  } catch {
    // storage blocked (private mode etc.) — pairing just won't persist
  }
};

export const getToken = () => read(TOKEN_KEY);

export const getUser = () => {
  try {
    return JSON.parse(read(USER_KEY) || "null");
  } catch {
    return null;
  }
};

export const clearSession = () => {
  write(TOKEN_KEY, null);
  write(USER_KEY, null);
};

/**
 * Error thrown for any non-2xx response. `status` is the HTTP status;
 * `body` is the parsed JSON (for 403s from the director API it carries
 * `reason`: 'ended' | 'not_started' | 'no_access' | 'not_app_token').
 */
export class DirectorApiError extends Error {
  constructor(status, body) {
    super(body?.message || `Request failed (${status})`);
    this.status = status;
    this.body = body || {};
  }
}

const request = async (method, path, payload, { auth = true } = {}) => {
  const headers = { Accept: "application/json" };
  if (payload !== undefined) headers["Content-Type"] = "application/json";
  if (auth) {
    const token = getToken();
    if (!token) throw new DirectorApiError(401, { message: "Not paired" });
    headers.Authorization = `Bearer ${token}`;
  }

  let response;
  try {
    response = await fetch(`/api${path}`, {
      method,
      headers,
      body: payload !== undefined ? JSON.stringify(payload) : undefined,
      cache: "no-store",
    });
  } catch {
    throw new DirectorApiError(0, { message: "Can't reach the server. Check your internet connection." });
  }

  let body = null;
  try {
    body = await response.json();
  } catch {
    // non-JSON body
  }

  if (!response.ok) {
    // Token revoked/deleted (or user removed): forget it so the page drops
    // back to Setup instead of retrying forever.
    if (response.status === 401 && auth) clearSession();
    throw new DirectorApiError(response.status, body);
  }

  return body;
};

export const pair = async (code, deviceName) => {
  const body = await request(
    "POST",
    "/app/pair",
    { code: code.trim(), device_name: deviceName.trim() },
    { auth: false },
  );
  write(TOKEN_KEY, body.token);
  write(USER_KEY, JSON.stringify(body.user));
  return body.user;
};

export const unpair = async () => {
  try {
    await request("POST", "/app/unpair");
  } finally {
    clearSession();
  }
};

export const fetchShows = () => request("GET", "/director/shows");
export const fetchSales = (showId) => request("GET", `/director/shows/${showId}/sales`);
export const fetchAuditions = (showId) => request("GET", `/director/shows/${showId}/auditions`);

/** A readable default device name, e.g. "Safari on iPhone". */
export const guessDeviceName = () => {
  const ua = navigator.userAgent;
  const device = /iPhone/.test(ua)
    ? "iPhone"
    : /iPad/.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1)
      ? "iPad"
      : /Android/.test(ua)
        ? "Android"
        : /Windows/.test(ua)
          ? "Windows"
          : /Macintosh|Mac OS X/.test(ua)
            ? "Mac"
            : /Linux/.test(ua)
              ? "Linux"
              : "this device";
  const browser = /Edg\//.test(ua)
    ? "Edge"
    : /OPR\//.test(ua)
      ? "Opera"
      : /Firefox\/|FxiOS/.test(ua)
        ? "Firefox"
        : /Chrome\/|CriOS/.test(ua)
          ? "Chrome"
          : /Safari\//.test(ua)
            ? "Safari"
            : "Browser";
  return `${browser} on ${device}`;
};

/** iPhone/iPad Safari (where Add to Home Screen gets separate storage). */
export const isIos = () =>
  /iPhone|iPad|iPod/.test(navigator.userAgent) ||
  (/Macintosh/.test(navigator.userAgent) && navigator.maxTouchPoints > 1);

/** Running as an installed app (home screen / desktop install). */
export const isStandalone = () =>
  window.matchMedia?.("(display-mode: standalone)").matches || window.navigator.standalone === true;
