<template>
  <div>
    <router-view></router-view>
  </div>
</template>

<script setup>
import { useStore } from "./stores/store";
import callApi from "src/assets/call-api";

const store = useStore();

window.addEventListener("apiLoaded", store.apiLoaded);

window.addEventListener("message", (e) => {
  if (e.data?.type === "fixr:purchase_complete" || e.data?.type === "fixr.widget:purchaseCompleted") {
    console.log("Fixr purchase complete", e.data);

    callApi({
      path: "/fixr-investigate",
      method: "post",
      payload: typeof e.data === "string" ? e.data : JSON.stringify(e.data),
      showError: false,
    }).catch(() => {});
  }
});
</script>
