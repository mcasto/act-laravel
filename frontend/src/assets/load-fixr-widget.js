let fixrPromise = null;

export default function loadFixrWidget() {
  if (window.fixr) return Promise.resolve(window.fixr);
  if (fixrPromise) return fixrPromise;

  fixrPromise = new Promise((resolve) => {
    window.addEventListener("apiLoaded", () => resolve(window.fixr), {
      once: true,
    });

    const script = document.createElement("script");
    script.src =
      "https://web-cdn.fixr.co/scripts/fixr-shop-widget.v1.min.js?headless=true&callback=fixrCallback";
    script.dataset.fixrShopId = "de260e6a-5adb-4a57-9a0d-770c06620c9f";
    document.head.appendChild(script);
  });

  return fixrPromise;
}
