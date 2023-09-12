<template>
  <q-page class="flex flex-center" style="align-items: end;">
    <q-header class="q-mt-md bg-transparent" style="max-width: 102px; margin-left: auto;">
      <LangSwitch class="q-pr-lg"></LangSwitch>
    </q-header>

    <div class="full-width q-gutter-y-md q-mt-xl">
      <q-item class="column q-px-lg full-width justify-between">
        <q-item-section style="align-items: end;">
          <q-img
            src="../../assets/logo-g-trailintravel.png"
            width="12.5rem">
          </q-img>
        </q-item-section>

        <q-item-section class="section-no-margin">
          <h4 class="login-label">{{ $t('new_register') }}</h4>
        </q-item-section>
      </q-item>

      <div class="">
        <q-select dense outlined 
          v-model="model"
          :options="options"
          :label="$t('registration_type')"
          class="q-px-lg"
          color="secondary"
          :popup-content-style="{ fontSize: '1rem' }"
          outlined
          dense
        />

        <q-input
      dense
      outlined
          v-model="register_informations.email"
          color="secondary"
          class="q-px-lg"
          outlined
          placeholder="E-mail"
          dense
        />

        <div class="row q-px-lg">
          <q-input
      dense
      outlined
            v-model="register_informations.password"
            color="secondary"
            class="col-6 q-pr-xs"
            outlined
            :placeholder="$t('password_register')"
            dense
            type="password"
          />
          <q-input
      dense
      outlined
            v-model="register_informations.confirm_password"
            color="secondary"
            class="col-6 q-pl-xs"
            outlined
            :placeholder="$t('repeat_password')"
            @keydown.enter.prevent="register"
            dense
            type="password"
          />
        </div>
      </div>

      <div class="q-px-lg q-ma-none">
        <q-btn flat class="q-pa-sm q-mr-md shadow-button">
          <q-avatar square size="1.3rem">
            <img src="../../assets/google.png">
          </q-avatar>
        </q-btn>

        <q-btn flat class="q-pa-sm shadow-button">
          <q-avatar square size="1.3rem">
            <img src="../../assets/facebook.png">
          </q-avatar>
        </q-btn>
      </div>
    </div>

    <q-item class="full-width column">
      <q-item-section style="align-items: flex-end;">
        <q-btn
          @click ="register"
          class="login-submit-button"
          dense
          size="1.3rem"
        >
          {{ $t('register_button') }}
        </q-btn>
      </q-item-section>

      <q-item-section class="section-no-margin">
        <q-item-label class="text-bold text-white" style="font-size: 1rem;">
          {{ $t('already_have_registration') }}
        </q-item-label>
      </q-item-section>

      <div class="section-no-margin row">
        <q-btn @click="login" class="q-pa-none recovery-link" flat :label="$t('login')" />
      </div>
    </q-item>
  </q-page>
</template>

<script>
import LangSwitch from '@controleonline/quasar-common-ui/src/components/UI/LangSwitch';

export default {
  name: 'PageRegister',

  components: {
    LangSwitch,
  },

  data() {
    return {
      model: '',
      register_informations: {
        type_account: '',
        email: '',
        password: '',
        confirm_password: '',
      },
    };
  },

  computed: {
    options() {
      return [
        {
          label: this.$t('provide_services'),
          value: this.$t('provide_services'),
        },
        {
          label: this.$t('look_for_guide'),
          value: this.$t('look_for_guide'),
        },
      ];
    },
  },

  methods: {
    login() {
      this.$router.push({ name: 'login' });
    },
    register() {
      const { email, password, confirm_password: confirmPassword } = this.register_informations;

      if (!email || !password || !confirmPassword) {
        this.$q.dialog({
          title: this.$t('error'),
          message: this.$t('all_fields'),
          color: 'secondary',
        });
      } else if (password !== confirmPassword) {
        this.$q.dialog({
          title: this.$t('error'),
          message: this.$t('different_passwords'),
          color: 'secondary',
        });
      } else {
        this.login();
      }
    },
  },
};
</script>

<style lang="scss" scoped>
.bg-primary {
  background-color: #182840 !important;
}

.shadow-button {
  box-shadow: 3px 3px 12px rgba(0, 0, 0, 0.15);
  border-radius: 5px;
}

/* theme fixes */
.login-app-name,
.signup-app-name {
  position: relative;
  max-width: 65px;
  display: inline-block;
  color: transparent;
  overflow: visible;
  font-weight: 700;
  font-size: 18px;
  margin: 0px;
  margin-top: 40%;
}

.signup-container {
  max-width: 318px;
  margin: 0 auto;
}

.login-app-name::after,
.signup-app-name::after {
  background-image: url("../../assets/logo-g-trailintravel.png");
  content: " ";
  position: absolute;
  background-position: 50%;
  background-size: 100% 100%;
  background-repeat: no-repeat;
  display: block;
  width: 152px;
  height: 85px;
  left: -80px;
  top: -10px;
}

.register-link,
.signin-link {
  margin-top: -10px;
  color: #ffc107;
  font-size: 11px;
  z-index: 1000000;
}

.register-link-label,
.signin-link-label {
  font-weight: 700;
  color: #fff;
  font-size: 11px;
  display: block;
}

.recovery-link {
  color: #ffc107;
  font-size: 1rem;
  text-decoration: underline;
}

.section-no-margin {
  margin: 0!important;
}

.login-form,
.signup-page-card {
  box-shadow: none;
  background-color: transparent;
  margin-top: -50px;
}

.alignment-login {
  display: inline-grid;
}

.login-submit-button,
.signup-submit-button {
  background-color: #12679a !important;
  border: 1px solid white !important;
  color: white;
  border-radius: 10px;
  font-size: 20px;
  min-width: 45%;
}

.login-label,
.signup-label {
  color: #12679a;
  font-size: 28px;
  font-weight: bold;
  margin: 0px;
}

.q-btn {
  text-transform: none !important;
}

.q-select.q-field,
.q-input.q-field {
  padding-bottom: 10px;
}

.q-select-label,
.q-input-label {
  color: #12679a;
}
.q-select.q-field--outlined .q-field__control,
.q-input.q-field--outlined .q-field__control {
  height: 40px;
  border-color: #12679a;
  border-radius: 10px;
  font-size: 12px;

  .q-field__append {
    height: 36px;
    font-size: 18px;
  }
}
.q-field__messages > div {
  margin-top: -30px !important;
}

</style>
