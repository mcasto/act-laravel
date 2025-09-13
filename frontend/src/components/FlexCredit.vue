<template>
  <div class="text-center">
    <div class="flex justify-center items-center q-my-md">
      Credit / Debit cards are processed through
      <svg
        class="sc-4c44c463-2 fHcExi q-ml-md"
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 435 138"
        style="width: 100px;"
      >
        <g clip-path="url(#:R6b9rmH1:)" fill-rule="evenodd" clip-rule="evenodd">
          <path
            d="M405.825 92.722c16.275-8.108 25.206-23.33 25.206-44.485 0-30.842-21.038-48.24-57.754-48.24H341.39l-30.234 42.6v95.4h35.33V99.049h27.787l20.641 38.948h40.09l-29.179-45.276Zm-32.548-20.958h-26.793V27.677h26.791c15.282 0 24.213 7.314 24.213 21.747.002 14.633-8.929 22.343-24.213 22.343l.002-.003ZM35.329 138H0V0h103.005v27.285H35.327v32.032H97.65v27.675h-62.32V138Zm175.198-69 20.472-28.843L259.5 0h40.941L251.47 68.996l-20.471 28.842-28.501 40.157h-40.942l48.971-68.996Zm-8.029-69 8.026 11.31-20.47 28.844L161.556 0h40.942Zm48.973 126.69 8.027 11.309h40.941l-28.497-40.152-20.471 28.843Zm-95.532-38.465L120.61 138V0h35.329v88.225Z"
          ></path>
          <path
            d="M405.825 92.722c16.275-8.108 25.206-23.33 25.206-44.485 0-30.842-21.038-48.24-57.754-48.24H341.39l-30.234 42.6v95.4h35.33V99.049h27.787l20.641 38.948h40.09l-29.179-45.276Zm-32.548-20.958h-26.793V27.677h26.791c15.282 0 24.213 7.314 24.213 21.747.002 14.633-8.929 22.343-24.213 22.343l.002-.003ZM35.329 138H0V0h103.005v27.285H35.327v32.032H97.65v27.675h-62.32V138Zm175.198-69 20.472-28.843L259.5 0h40.941L251.47 68.996l-20.471 28.842-28.501 40.157h-40.942l48.971-68.996Zm-8.029-69 8.026 11.31-20.47 28.844L161.556 0h40.942Zm48.973 126.69 8.027 11.309h40.941l-28.497-40.152-20.471 28.843Zm-95.532-38.465L120.61 138V0h35.329v88.225Z"
            fill="url(#:R6b9rm:)"
          ></path>
        </g>
        <defs>
          <linearGradient
            id=":R6b9rm:"
            x1="-1.61"
            y1="147.67"
            x2="433.181"
            y2="157.816"
            gradientUnits="userSpaceOnUse"
          >
            <stop offset=".123" stop-color="#CC013E"></stop>
            <stop offset="1" stop-color="#FF2000"></stop>
          </linearGradient>
          <clipPath id=":R6b9rmH1:">
            <path fill="#fff" d="M0 0h435v138H0z"></path>
          </clipPath>
        </defs>
      </svg>
    </div>
    <q-btn
      label="Continue to FIXR to complete your purchase"
      color="primary"
    ></q-btn>
  </div>
</template>

<script setup>
const props = defineProps(["id"]);
</script>
