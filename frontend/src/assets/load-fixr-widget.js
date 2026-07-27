const TIMEOUT_MS = 10000;

let fixrPromise = null;

export default function loadFixrWidget() {
  if (window.fixr) return Promise.resolve(window.fixr);
  if (fixrPromise) return fixrPromise;

  fixrPromise = new Promise((resolve, reject) => {
    const fail = (error) => {
      clearTimeout(timeout);
      fixrPromise = null; // allow a later retry (e.g. after the user disables their VPN)
      reject(error);
    };

    const timeout = setTimeout(
      () => fail(new Error("Timed out waiting for the Fixr widget to load.")),
      TIMEOUT_MS,
    );

    window.addEventListener(
      "apiLoaded",
      () => {
        clearTimeout(timeout);
        resolve(window.fixr);
      },
      { once: true },
    );

    const script = document.createElement("script");
    script.src =
      "https://web-cdn.fixr.co/scripts/fixr-shop-widget.v1.min.js?headless=true&callback=fixrCallback";
    script.dataset.fixrShopId = "de260e6a-5adb-4a57-9a0d-770c06620c9f";
    script.onerror = () => fail(new Error("Failed to load the Fixr widget script."));
    document.head.appendChild(script);
  });

  return fixrPromise;
}
