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
            Lista de emails
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
            <q-td key="email" :props="props">{{ props.cols[0].value }}</q-td>
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
          <div class="text-h6">Novo email</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section>
          <FormEmail ref="myForm" @save="onSave" />
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import FormEmail from './FormEmail';

const SETTINGS = {
  visibleColumns: [
    'email' ,
    'action',
  ],
  columns       : [
    {
      name : 'email',
      field: row => row.email,
      align: 'left',
      label: 'Email'
    },
    { name: 'action' },
  ],
};

Object.freeze(SETTINGS);

export default {
  components: {
    FormEmail,
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
      if (company !== null) {
        this.onRequest();
      }
    },
  },

  methods: {
    ...mapActions({
      getItems: 'profile/getEmails',
      save    : 'profile/updateProfile',
    }),

    onSave(data) {
      this.save({
        id       : this.user.people,
        component: 'email',
        payload  : {
          "operation": "post",
          "payload"  : {
            "email": data.email,
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
          component: 'email',
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
                id    : items[index]['@id'].match(/^\/emails\/([a-z0-9-]*)$/)[1],
                email : items[index].email,
                _bussy: false,
              });
            }
          }
        });
    },
  },
};
</script>