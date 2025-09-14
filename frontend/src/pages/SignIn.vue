<template>
  <div class="text-h4 text-center q-my-md">
    Admin Sign In
  </div>
  <q-separator></q-separator>
  <div class="flex flex-center">
    <q-form @submit.prevent="store.signIn({ email, password })">
      <q-input
        class="q-mt-md"
        type="text"
        label="Email"
        v-model="email"
        :style="Screen.gt.xs ? 'width: 50vw' : 'width: 80vw'"
        autocomplete="off"
        dense
        outlined
      ></q-input>
      <q-input
        class="q-mt-md"
        :type="showPass ? 'text' : 'password'"
        label="Password"
        v-model="password"
        :style="Screen.gt.xs ? 'width: 50vw' : 'width: 80vw'"
        autocomplete="off"
        dense
        outlined
      >
        <template #append>
          <q-btn
            :icon="showPass ? 'visibility_off' : 'visibility'"
            flat
            @click="showPass = !showPass"
          ></q-btn>
        </template>
      </q-input>
      <div class="text-right q-mt-md">
        <q-btn type="submit" label="Submit" color="primary"></q-btn>
      </div>
    </q-form>
  </div>
</template>

<script setup>
import { Screen } from "quasar";
import { useStore } from "src/stores/store";
import { ref } from "vue";

const store = useStore();

const email = ref(null);
const password = ref(null);
const showPass = ref(false);
</script>
