<template>
  <q-form
    ref    ="myForm"
    @submit="onSubmit"
  >
    <div class="row q-col-gutter-xs q-pb-xs">
      <h6 class="col-xs-12 q-mt-sm q-mb-sm">Dados pessoais</h6>
      <div class="col-xs-12 col-sm-6 q-mb-sm">
        <q-input
      dense
      outlined stack-label lazy-rules unmasked-value hide-bottom-space
          v-model="item.name"
          type   ="text"
          label  ="Nome"
          :rules ="[isInvalid('name')]"
        />
      </div>
      <div class="col-xs-12 col-sm-6 q-mb-sm">
        <q-input
      dense
      outlined stack-label lazy-rules hide-bottom-space
          v-model="item.lastname"
          type   ="text"
          label  ="Sobrenome"
          :rules ="[isInvalid('lastname')]"
        />
      </div>
      <div class="col-xs-12 col-sm-2 q-mb-sm">
        <q-input
      dense
      outlined stack-label lazy-rules hide-bottom-space
          v-model="item.phone.ddd"
          type   ="text"
          label  ="DDD"
          mask   ="##"
          :rules ="[isInvalid('ddd')]"
        />
      </div>
      <div class="col-xs-12 col-sm-4 q-mb-sm">
        <q-input
      dense
      outlined stack-label hide-bottom-space
          v-model="item.phone.phone"
          type   ="text"
          label  ="Fone"
          mask   ="#########"
          :rules ="[isInvalid('phone')]"
        />
      </div>
      <div class="col-xs-12 col-sm-6 q-mb-sm">
        <q-input
      dense
      outlined stack-label lazy-rules hide-bottom-space
          v-model="item.email"
          type   ="text"
          label  ="Email"
          :rules ="[isInvalid('email')]"
        />
      </div>
      <div class="col-xs-12 col-sm-6 q-mb-sm">
        <q-input
      dense
      outlined stack-label hide-bottom-space
          v-model="item.document.rg"
          type   ="text"
          label  ="R.G"
          mask   ="#########"
        />
      </div>
      <div class="col-xs-12 col-sm-6 q-mb-sm">
        <q-input
      dense
      outlined stack-label hide-bottom-space unmasked-value
          v-model="item.document.cpf"
          type   ="text"
          label  ="CPF"
          mask   ="###.###.###-##"
        />
      </div>
      <h6 class="col-xs-12 q-mt-sm q-mb-sm">Dados de usuário</h6>
      <div class="col-xs-12 col-sm-6 q-mb-sm">
        <q-input
      dense
      outlined stack-label lazy-rules reverse-fill-mask
          v-model    ="item.username"
          type       ="text"
          :label     ="$t('Usuário')"
          placeholder="Digite seu usuário (nickname)"
          class      ="q-mb-md"
          mask       ="x"
          :rules     ="[isInvalid('username')]"
          hint       ="Use apenas letras e números sem espaços"
        />
      </div>
      <div class="col-xs-12 col-sm-6 q-mb-sm">
        <q-input
      dense
      outlined stack-label lazy-rules
          v-model    ="item.password"
          type       ="password"
          :label     ="$t('Senha')"
          placeholder="Digite sua senha"
          :rules     ="[isInvalid('password')]"
          hint       ="Use seis ou mais caracteres com uma combinação de letras, números e símbolos"
        />
      </div>
    </div>

    <div class="row justify-end">
      <q-btn
        :loading="saving"
        icon    ="save"
        type    ="submit"
        label   ="Salvar"
        size    ="md"
        color   ="primary"
        class   ="q-mt-md"
      />
    </div>
  </q-form>
</template>

<script>
export default {
  data() {
    return {
        saving   : false,
        item     : {
          name    : '',
          lastname: '',
          phone   : {
            ddd  : '',
            phone: '',
          },
          email   : '',
          document: {
            rg : null,
            cpf: null
          },
          username: '',
          password: '',
        },
    };
  },

  methods : {
    onSubmit () {
      this.$refs.myForm.validate()
        .then(success => {
          if (success) {
            let payload = {
              name     : this.item.name,
              lastname : this.item.lastname,
              phone    : {
                ddd  : this.item.phone.ddd,
                phone: this.item.phone.phone,
              },
              email    : this.item.email,
              documents: [],
              username : this.item.username,
              password : this.item.password,
            };

            payload.documents.push({
              type    : 1,
              document: this.item.document.rg
            });

            payload.documents.push({
              type    : 2,
              document: this.item.document.cpf
            });

            this.saving = true;

            this.$emit('save', payload);
          }
      })
    },

    reset() {
      this.saving = false;
    },

    isInvalid(key) {
      return val => {
        if (!(val && val.length > 0))
          return this.$t('messages.fieldRequired');

        if (key == 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val))
          return this.$t('messages.emailInvalid');

        if (key == 'password' && val.length < 6)
          return this.$t('A senha deve ter no mínimo 6 caracteres');

        if (key == 'confirm' && (this.item.password != this.item.confirmPassword))
          return this.$t('As senhas não coincidem');

        return true;
      };
    },
  },
};
</script>
