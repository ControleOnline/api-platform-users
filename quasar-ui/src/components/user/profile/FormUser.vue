<template>
  <q-form ref="myForm" @submit="onSubmit" class="q-mt-md">
    <q-input
      dense
      outlined lazy-rules stack-label
      v-model="item.username"
      type   ="text"
      label  ="Nome de usuário"
      class  ="q-mt-md"
      :rules ="[isInvalid('username')]"
    />

    <q-input
      dense
      outlined lazy-rules stack-label
      v-model="item.password"
      type   ="password"
      label  ="Senha"
      class  ="q-mt-md"
      :rules ="[isInvalid('password')]"
    />

    <q-input
      dense
      outlined lazy-rules stack-label
      v-model="item.confirm"
      type   ="password"
      label  ="Confirme sua senha"
      class  ="q-mt-md"
      :rules ="[isInvalid('confirm')]"
    />

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
      saving: false,
      item  : {},
    };
  },

  methods : {
    onSubmit () {
      this.$refs.myForm.validate()
        .then(success => {
          if (success) {
            let payload = {
              "username": this.item.username,
              "password": this.item.password,
            };

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
        switch (key) {
          case 'email'   :
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val))
              return 'O email informado não é válido';
          break;
          case 'password':
            if (!val || val.length < 6)
              return 'A senha deve ser no mínimo de 6 caracteres';
          break;
          case 'confirm' :
            if (this.item.password != this.item.confirm)
              return 'As senhas não coincidem';
          break;
          default:
            if (!(val && val.length > 0))
              return 'Este campo é obrigatório';
        }
        return true;
      };
    },
  },
};
</script>
