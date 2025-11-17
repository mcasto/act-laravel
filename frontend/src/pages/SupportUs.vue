<template>
  <q-page>
    <div class="container">
      <div class="flex justify-center q-mb-md">
        <q-img
          src="/images/logo-home-2025.png"
          width="150px"
          fit="contain"
          class="q-mr-md"
        ></q-img>
        <h1>Support Us</h1>
      </div>

      <p>
        <strong
          >Please support us as we continue to bring live English-language
          theater to Cuenca!</strong
        >
      </p>

      <p>
        Like most community theaters, ACT operates on 100% volunteer power: from
        cast and crew to hospitality and operations — no one is paid. Even with
        all that volunteer value being donated, our ticket sales still represent
        only 70% of our operating costs. Donations and 50/50 ticket and bar
        sales make up the rest. So, we count on our generous donors to enable us
        to cover a significant amount of our operating costs.
      </p>

      <p>
        Donations make it possible for us to continuously improve our
        productions with better sets, costumes, and props. For example, just
        this past year we had to replace lights, and purchase new lighting and
        sound software and a new computer to run it. We also improved our guest
        experience with a new bar, new lobby chairs, and a complete paint job
        for the interior of the theater.
      </p>

      <p>
        A donation of just $25.00 will help. Just click on the button below to
        make a donation online. If you’d like to donate more than that, just
        enter the number of $25.00 donations you’d like to make.
      </p>

      <div class="q-mt-md">
        <purchase-options
          :fixr-link="config.fixr_link"
          :payment-methods="paymentMethods"
          :buttons="config.buttons"
          v-model="paymentMethod"
        ></purchase-options>
      </div>

      <p class="note">
        <a href="#" class="learn-more">
          Click here to learn more about our Angel Program
        </a>
        where donations of $100.00 or more receive special recognition and
        benefits.
      </p>
    </div>
  </q-page>
</template>

<script setup>
import PurchaseOptions from "src/components/PurchaseOptions.vue";
import { useStore } from "src/stores/store";
import { computed, onMounted, ref } from "vue";

const store = useStore();

const config = store.supportUsConfig;

const paymentMethod = ref(null);

const paymentMethods = computed(() => {
  return [
    {
      label: config.fixr_label,
      value: "fixr",
    },
    ...config.buttons.map((button) => {
      return {
        label: button.label,
        value: button.id,
      };
    }),
  ];
});

onMounted(() => {
  paymentMethod.value = {
    id: config.fixr_link,
    label: config.fixr_label,
    value: "fixr",
  };
});
</script>
