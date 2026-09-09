<template>
  <div ref="container" class="q-pa-md"></div>
</template>

<script setup>
import { onMounted, ref } from "vue";

const container = ref(null);

// Fetches the raw Blade-rendered form (HTML, not JSON) and injects it as-is.
// innerHTML doesn't execute <script> tags it introduces, so the form's own
// inline submit-handling script (in project-charter-form.blade.php) has to
// be located and re-created afterward to actually run.
onMounted(async () => {
  const html = await fetch("/api/project-charter").then((r) => r.text());
  container.value.innerHTML = html;

  container.value.querySelectorAll("script").forEach((oldScript) => {
    const newScript = document.createElement("script");
    oldScript.getAttributeNames().forEach((name) => {
      newScript.setAttribute(name, oldScript.getAttribute(name));
    });
    newScript.textContent = oldScript.textContent;
    oldScript.replaceWith(newScript);
  });
});
</script>
