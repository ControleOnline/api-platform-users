<template>
  <div class="row">
    <div class="col-12 q-mt-md">
      <q-table dense 
        :data           ="items"
        :columns        ="settings.columns"
        :visible-columns="settings.visibleColumns"
        row-key         ="id"
        :loading        ="isLoading"
      >
        <template v-slot:top>
          <div class="col-3 q-mb-md text-h6">
            Lista de endereços
          </div>
          <div class="col-9 q-mb-md">
            <div class="row justify-end">
              <q-btn
                label ="Adicionar"
                icon  ="add"
                size  ="md"
                color ="primary"
                class ="q-ml-sm"
                @click="dialog = !dialog"
              />
            </div>
          </div>
        </template>

        <template v-slot:body="props">
          <q-tr :props="props">
            <q-td key="nickname"    :props="props">{{ props.cols[0].value }}</q-td>
            <q-td key="postalCode"  :props="props">{{ props.cols[1].value }}</q-td>
            <q-td key="street"      :props="props">{{ props.cols[2].value }}</q-td>
            <q-td key="number"      :props="props">{{ props.cols[3].value }}</q-td>
            <q-td key="complement"  :props="props">{{ props.cols[4].value }}</q-td>
            <q-td key="district"    :props="props">{{ props.cols[5].value }}</q-td>
            <q-td key="cityName"    :props="props">{{ props.cols[6].value }}</q-td>
            <q-td key="stateName"   :props="props">{{ props.cols[7].value }}</q-td>
            <q-td key="countryName" :props="props">{{ props.cols[8].value }}</q-td>
            <q-td auto-width>
              <q-btn flat round dense
                color   ="red"
                icon    ="delete"
                @click  ="removeItem(props.row)"
                :disable="items.length == 1"
                :loading="props.row._bussy"
              />
            </q-td>
          </q-tr>
        </template>
      </q-table>
    </div>

    <q-dialog v-model="dialog">
      <q-card style="width: 700px; max-width: 80vw;">
        <q-card-section class="row items-center">
          <div class="text-h6">Novo endereço</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section>
          <FormAddress ref="myForm" @save="onSave" />
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script>
import { formatCEP } from '@controleonline/quasar-common-ui/src/utils/formatter';
import { mapActions, mapGetters } from 'vuex';
import FormAddress from './FormAddress';

const SETTINGS = {
  visibleColumns: [
    'nickname'   ,
    'postalCode' ,
    'street'     ,
    'number'     ,
    'complement' ,
    'district'   ,
    'cityName'   ,
    'stateName'  ,
    'countryName',
    'action'     ,
  ],
  columns       : [
    {
      name : 'nickname',
      field: row => row.nickname,
      align: 'left',
      label: 'Apelido'
    },
    {
      name : 'postalCode',
      field: row => formatCEP(`${row.postalCode}`),
      align: 'left',
      label: 'CEP'
    },
    {
      name : 'street',
      field: row => row.street,
      align: 'left',
      label: 'Rua / Avenida'
    },
    {
      name : 'number',
      field: row => row.number,
      align: 'left',
      label: 'Número'
    },
    {
      name : 'complement',
      field: row => row.complement,
      align: 'left',
      label: 'Complemento'
    },
    {
      name : 'district',
      field: row => row.district,
      align: 'left',
      label: 'Bairro'
    },
    {
      name : 'cityName',
      field: row => row.city,
      align: 'left',
      label: 'Cidade'
    },
    {
      name : 'stateName',
      field: row => row.state,
      align: 'left',
      label: 'Estado'
    },
    {
      name : 'countryName',
      field: row => row.country,
      align: 'left',
      label: 'País'
    },
    { name: 'action' },
  ],
};

Object.freeze(SETTINGS);

export default {
  components: {
    FormAddress,
  },

  data() {
    return {
      items   : [],
      dialog  : false,
      settings: SETTINGS,
      saving  : false,
    };
  },

  created() {
    this.onRequest();
  },

  computed: {
    ...mapGetters({
      isLoading: 'profile/isLoading'    ,
      myCompany: 'people/currentCompany',
    }),

    user() {
      return this.$store.getters['auth/user'];
    },
  },

  watch: {
    myCompany(company) {
      if (company !== null)
        this.onRequest();
    },
  },

  methods: {
    ...mapActions({
      getItems: 'profile/getAddress',
      save    : 'profile/updateProfile',
    }),

    onSave(data) {
      this.save({
        id       : this.user.people,
        component: 'address',
        payload  : {
          "operation": "post",
          "payload"  : {
            nickname   : data.nickname,
            country    : data.country,
            state      : data.state,
            city       : data.city,
            district   : data.district,
            postal_code: data.postalCode,
            street     : data.street,
            number     : data.number,
            complement : data.complement,
          }
        }
      })
        .then (data => {
          if (data) {
            this.$refs.myForm.reset();

            this.onRequest();

            this.$q.notify({
              message : 'Os dados foram salvos com sucesso',
              position: 'bottom',
              type    : 'positive',
            });
          }
        })
        .catch(error => {
          this.$refs.myForm.reset();

          this.$q.notify({
            message : error.message,
            position: 'bottom',
            type    : 'negative',
          });
        });
    },

    removeItem(item) {
      if (window.confirm('Tem certeza que deseja eliminar este registro?')) {
        item._bussy = true;

        this.save({
          id       : this.user.people,
          component: 'address',
          payload  : {
            "operation": "delete",
            "payload"  : {
              "id": item.id
            }
          }
        })
          .then (data => {
            if (data) {
              this.cleanItem(item['id']);
            }
          })
          .catch(error => {
            this.$q.notify({
              message : error.message,
              position: 'bottom',
              type    : 'negative',
            });
          })
          .finally(() => {
            item._bussy = false;
          });
      }
    },

    cleanItem(id) {
      let item   = this.items.find(obj => obj['id'] == id);
      let indx   = this.items.indexOf(item);
      this.items = [...this.items.slice(0, indx), ...this.items.slice(indx + 1)];
    },

    onRequest() {
      let params = {};

      this.items = [];

      this.getItems(params)
        .then(items => {
          if (items.length) {
            for (let index in items) {
              this.items.push({
                id        : items[index]['@id'].match(/^\/addresses\/([a-z0-9-]*)$/)[1],
                nickname  : items[index].nickname,
                country   : items[index].street.district.city.state.country.countryname,
                state     : items[index].street.district.city.state.uf,
                city      : items[index].street.district.city.city,
                district  : items[index].street.district.district,
                postalCode: items[index].street.cep.cep,
                street    : items[index].street.street,
                number    : items[index].number,
                complement: items[index].complement,
                _bussy    : false,
              });
            }
          }
        });
    },
  },
};
</script>