<template>
  <q-form
    ref    ="myForm"
    @submit="onSubmit"
  >
    <div class="row q-col-gutter-xs q-pb-xs">
      <div class="col-xs-12 col-sm-4 q-mb-sm">
        <q-input
      dense
      outlined stack-label lazy-rules unmasked-value hide-bottom-space
          v-model    ="item.postalCode"
          type       ="text"
          label      ="CEP"
          mask       ="#####-###"
          :rules     ="[isInvalid('postal_code')]"
          placeholder="Busque o endereço pelo CEP"
          :loading   ="loading"
        />
      </div>
      <div class="col-xs-12 col-sm-8 q-mb-sm">
        <q-input
      dense
      outlined stack-label lazy-rules unmasked-value hide-bottom-space
          v-model="item.nickname"
          type       ="text"
          label      ="Apelido"
          :rules     ="[isInvalid('nickname')]"
          placeholder="Informe um apelido para este endereço"
        />
      </div>
      <div class="col-xs-12 col-sm-8 q-mb-sm">
        <q-input
      dense
      outlined stack-label lazy-rules hide-bottom-space
          v-model="item.street"
          type   ="text"
          label  ="Rua / Avenida"
          :rules ="[isInvalid('street')]"
        />
      </div>
      <div class="col-xs-12 col-sm-4 q-mb-sm">
        <q-input
      dense
      outlined stack-label lazy-rules hide-bottom-space
          v-model="item.number"
          type   ="text"
          label  ="Número"
          :rules ="[isInvalid('number')]"
        />
      </div>
      <div class="col-xs-12 col-sm-grow q-mb-sm">
        <q-input
      dense
      outlined stack-label hide-bottom-space
          v-model="item.complement"
          type   ="text"
          label  ="Complemento"
        />
      </div>
      <div class="col-xs-12 col-sm-grow q-mb-sm">
        <q-input
      dense
      outlined stack-label lazy-rules hide-bottom-space
          v-model="item.district"
          type   ="text"
          label  ="Bairro"
          :rules ="[isInvalid('district')]"
        />
      </div>
      <div class="col-xs-12 col-sm-grow q-mb-sm">
        <q-input
      dense
      outlined stack-label lazy-rules hide-bottom-space
          v-model="item.city"
          type   ="text"
          label  ="Cidade"
          :rules ="[isInvalid('city')]"
        />
      </div>
      <div class="col-xs-12 col-sm-grow q-mb-sm">
        <q-input
      dense
      outlined stack-label lazy-rules hide-bottom-space
          v-model="item.state"
          type   ="text"
          label  ="UF"
          mask   ="AA"
          :rules ="[isInvalid('state')]"
        />
      </div>
      <div class="col-xs-12 col-sm-grow q-mb-sm">
        <q-input
      dense
      outlined stack-label lazy-rules hide-bottom-space
          v-model="item.country"
          type   ="text"
          label  ="País"
          :rules ="[isInvalid('country')]"
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
import { mapActions } from 'vuex';

export default {
  data() {
    return {
        saving : false,
        loading: false,
        item   : {
          nickname  : '',
          country   : '',
          state     : '',
          city      : '',
          district  : '',
          postalCode: '',
          street    : '',
          number    : '',
          complement: '',
        },
    };
  },

  watch: {
    'item.postalCode'(code) {
      if (code.length == 8) {
        this.loading = true;

        this.getAddress(code)
          .then(address => {
            if (address['@id']) {
              this.item.country  = address.country;
              this.item.state    = address.state;
              this.item.city     = address.city;
              this.item.district = address.district;
              this.item.street   = address.street;
              this.item.number   = address.number;
            }
          })
          .catch(error => {
            this.$q.notify({
              message : 'Nenhum endereço foi encontrado',
              position: 'bottom',
              type    : 'negative',
            });
          })
          .finally(() => {
            this.loading = false;
          });
      }
    },
  },

  methods: {
    ...mapActions({
      getAddress: 'gmaps/getAddressByCEP',
    }),

    onSubmit () {
      this.$refs.myForm.validate()
        .then(success => {
          if (success) {
            let payload = {
              nickname   : this.item.nickname,
              country    : this.item.country,
              state      : this.item.state,
              city       : this.item.city,
              district   : this.item.district,
              postalCode : this.item.postalCode,
              street     : this.item.street,
              number     : this.item.number,
              complement : this.item.complement,
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
