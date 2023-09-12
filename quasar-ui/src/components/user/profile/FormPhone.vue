<template>
  <q-form ref="myForm" @submit="onSubmit" class="q-mt-md">
    <q-input
      dense
      outlined lazy-rules stack-label
      v-model="item.ddd"
      type   ="text"
      label  ="DDD"
      mask   ="##"
      class  ="q-mt-md"
      :rules ="[isInvalid('ddd')]"
    />

    <q-input
      dense
      outlined lazy-rules stack-label
      v-model="item.number"
      type   ="text"
      label  ="Telefone"
      class  ="q-mt-md"
      mask   ="#########"
      :rules ="[isInvalid('phone')]"
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
      item  : {},
      saving: false,
    };
  },

  methods : {
    onSubmit () {
      this.$refs.myForm.validate()
        .then(success => {
          if (success) {
            let payload = {
              "ddd"  : this.item.ddd,
              "phone": this.item.number,
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
