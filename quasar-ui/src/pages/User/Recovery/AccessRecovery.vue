<template>
  <q-page padding class="row justify-center items-center">
    <q-card class="login my-card">
      <q-card-section class="bg-primary text-white text-center">
        <div class="text-h6 text-uppercase"       >{{ $t('Recupere sua senha') }}</div>
        <div class="text-subtitle2 text-uppercase">{{ $t('Sistema Cotafácil') }}</div>
      </q-card-section>

      <q-card-section>
        <PasswordForm @success="onRecoverySuccess" />
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
import PasswordForm from './PasswordForm';

export default {
  name      : 'AccessRecoveryPage',

  components: {
    PasswordForm,
  },

  computed: {
    isLogged() {
      return this.$store.getters['auth/user'] !== null && this.$store.getters['auth/user'].user;
    },
  },

  watch: {
    isLogged(logged) {
      if (logged === true) {
        this.$router.push({ name: 'PurchasingOrderIndex' });
      }
    },
  },

  methods: {
    onRecoverySuccess(userData) {
      this.$store.dispatch(
        'auth/logIn',
        {
          username: userData.username,
          api_key : userData.api_key,
          people  : userData.people,
          company : userData.company,
          email   : userData.email,
          phone   : userData.phone,
          avatar  : userData.avatar,
          realname: userData.realname,
          roles   : userData.roles,
        }
      );
    },
  },
}
</script>

<style lang="stylus">
.login.my-card
  margin-left  5px
  margin-right 5px
  @media (min-width: $breakpoint-sm-min)
    min-width 350px
  @media (max-width: $breakpoint-xs-max)
    min-width 100%

</style>
