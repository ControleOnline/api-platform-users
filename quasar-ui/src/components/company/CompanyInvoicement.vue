<template>
  <div class="row items-center justify-center">
    <div class="flex flex-center" v-if="isLoading">
      <q-circular-progress :indeterminate="isLoading"
        size ="sm"
        color="primary"
        class="q-ma-md"
      />
      Carregando...
    </div>

    <div class="col-12 q-mt-md">
      <q-markup-table v-if="item !== null">
        <tbody>
          <tr>
            <td class="text-left text-bold">Faturamento</td>
            <td class="text-left">{{ `${item.amount} ${item.period}` }}</td>
          </tr>
          <tr>
            <td class="text-left text-bold">Fechamento</td>
            <td class="text-left">{{ item.period }}</td>
          </tr>
          <tr>
            <td class="text-left text-bold">Prazo de pagamento</td>
            <td class="text-left">{{ item.dueDays }}</td>
          </tr>
        </tbody>
      </q-markup-table>
    </div>
  </div>
</template>

<script>
import { formatMoney } from '@controleonline/quasar-common-ui/src/utils/formatter';
import { mapActions, mapGetters } from 'vuex';

export default {
  props: {
    companyId: {
      type    : Number,
      required: true,
    },
  },

  data() {
    return {
      item: null,
    };
  },

  created() {
    this.onRequest();
  },

  computed: {
    ...mapGetters({
      isLoading: 'profile/isLoading',
    }),
  },

  methods: {
    ...mapActions({
      getItem: 'profile/getBilling',
    }),

    onRequest() {
      this.item = null;

      let periods = {
        daily   : 'diário',
        weekly  : 'semanal',
        biweekly: 'quinzenal',
        monthly : 'mensal',
      };

      this.getItem(this.companyId)
        .then(billing => {
          if (billing !== null) {
            this.item = {
              amount : billing.amount  > 0 ? formatMoney(billing.amount, 'BRL', 'pt-br') : 'À Vista',
              period : billing.amount  > 0 ? (periods[billing.period] ? periods[billing.period] : billing.period) : 'Por Pedido',
              dueDays: billing.dueDays > 1 ? `${billing.dueDays} dias` : `${billing.dueDays} dia`,
            };
          }
        });
    },
  },
};
</script>