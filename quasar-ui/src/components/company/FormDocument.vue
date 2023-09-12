<template>
  <q-form ref="myForm" @submit="onSubmit" class="q-mt-md">
    <q-select dense outlined  stack-label
        label   ="Tipo de documento"
        v-model="item.type"
        :options="settings.select.doctypes"
    >
        <template v-slot:no-option>
            <q-item>
                <q-item-section class="text-grey">
                Sem resultados
                </q-item-section>
            </q-item>
        </template>
    </q-select>

    <q-input
      dense
      outlined lazy-rules stack-label
      v-model="item.document"
      type   ="text"
      label  ="Documento"
      class  ="q-mt-md"
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
const SETTINGS = {
  select        : {
    doctypes: [
      {
        label: 'CNPJ',
        value: 3,
      },
      {
        label: 'Inscrição Estadual',
        value: 4,
      },
      {
        label: 'Inscrição Municipal',
        value: 5,
      },
    ],
  },
};

Object.freeze(SETTINGS);

export default {
  data() {
    return {
      settings: SETTINGS,
      item    : {},
      saving  : false,
    };
  },

  methods : {
    onSubmit () {
      this.$refs.myForm.validate()
        .then(success => {
          if (success) {
            let payload = {
              "type"    : this.item.type,
              "document": this.item.document,
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
