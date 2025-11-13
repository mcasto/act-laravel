<template>
  <q-dialog v-model="model">
    <q-card>
      <q-form @submit.prevent="onSubmit">
        <q-card-section>
          <q-input
            :type="showPass ? 'text' : 'password'"
            label="Password"
            dense
            outlined
            v-model="password"
            required
          >
            <template #append>
              <q-btn
                tabindex="-1"
                :icon="showPass ? 'visibility_off' : 'visibility'"
                round
                flat
                color="primary"
                @click="showPass = !showPass"
              ></q-btn>
            </template>
          </q-input>

          <q-input
            :type="showPass ? 'text' : 'password'"
            label="Confirm Password"
            dense
            outlined
            v-model="confirmPassword"
            required
            class="q-mt-sm"
            :rules="[(v) => v == password || 'Must match password']"
          ></q-input>
        </q-card-section>

        <q-card-actions class="justify-end">
          <q-btn label="Cancel" @click="onCancel" color="negative"></q-btn>
          <q-btn label="Update" type="submit" color="primary"></q-btn>
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { ref } from "vue";

const model = defineModel();
const props = defineProps(["row"]);

const showPass = ref(false);
const password = ref(null);
const confirmPassword = ref(null);

const onCancel = () => {
  password.value = null;
  confirmPassword.value = null;
  model.value = false;
};

const onSubmit = async () => {
  const response = await callApi({
    path: `/change-password/${props.row.id}`,
    method: "put",
    payload: { password: password.value },
    useAuth: true,
  });

  const type = response.status == "success" ? "positive" : "negative";

  Notify.create({ type, message: response.message });

  if (type == "positive") {
    onCancel();
  }
};
</script>
