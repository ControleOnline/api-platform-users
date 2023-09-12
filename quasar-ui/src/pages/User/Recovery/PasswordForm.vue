<template>
  <q-form @submit="onSubmit" class="q-mt-md">
    <q-input
      dense
      outlined lazy-rules stack-label
      ref    ="password"
      v-model="user.password"
      type   ="password"
      :label ="$t('Nova senha')"
      class  ="q-mt-md"
      :rules ="[isInvalid('password')]"
      hint   ="Use seis ou mais caracteres com uma combinação de letras, números e símbolos"
    />

    <q-input
      dense
      outlined lazy-rules stack-label
      ref    ="confirm"
      v-model="user.confirm"
      type   ="password"
      :label ="$t('Confirme sua senha')"
      class  ="q-mt-md"
      :rules ="[isInvalid('confirm')]"
    />

    <q-btn
      type    ="submit"
      :label ="$t('Salvar')"
      size    ="lg"
      color   ="primary"
      class   ="full-width q-mt-md"
      :loading="isLoading"
    />
  </q-form>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';

export default {
  data() {
    return {
      user   : {
        password: null,
        confirm : null,
      },
      loading: false,
    };
  },

  created() {

  },

  computed: {
    ...mapGetters({
      isLoading: 'user/isLoading',
    }),
  },

  methods: {
    ...mapActions({
      recovery: 'user/recoveryAccess',
    }),

    onSubmit() {
      this.recovery({
            "hash"    : this.$route.params.hash,
            "lost"    : this.$route.params.lost,
            "password": this.user.password,
            "confirm" : this.user.confirm
        })
        .then(response => {
          if (response.success === true) {
            this.$q.notify({
              message : 'Sua senha foi atualizada com sucesso',
              position: 'bottom',
              type    : 'positive',
            });

            this.$emit('success', response.data);
          }
          else {
            if (response.success === false && response.error)
              this.$q.notify({
                message : response.error,
                position: 'bottom',
                type    : 'negative',
              });
            else {
              let message = 'Ocorreu um erro no envio da solicitação';

              if (/This password has been leaked in a data breach/gi.test(response)) {
                message = 'Esta senha não é segura. Crie uma senha mais forte';
              }

              this.$q.notify({
                message : message,
                position: 'bottom',
                type    : 'negative',
              });
            }
          }
        });
    },

    isInvalid(key) {
      return val => {
        switch (key) {
          case 'password':
            if (!val || val.length < 6)
              return this.$t('A senha deve ter pelo menos 6 caracteres');
          break;
          case 'confirm' :
            if (this.user.password != this.user.confirm)
              return this.$t('As senhas não coincidem');
          break;
          default:
            if (!(val && val.length > 0))
              return this.$t('Este campo é obrigatório');
        }

        return true;
      };
    },
  },
};
</script>