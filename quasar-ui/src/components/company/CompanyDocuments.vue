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
            Lista de documentos
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
            <q-td key="doctype"   :props="props">{{ props.cols[0].value }}</q-td>
            <q-td key="document"  :props="props">{{ props.cols[1].value }}</q-td>
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
          <div class="text-h6">Novo documento</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section>
          <FormDocument  ref="myForm" @save="onSave" />
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script>
import { formatDocument } from '@controleonline/quasar-common-ui/src/utils/formatter';
import { mapActions, mapGetters } from 'vuex';
import FormDocument from './FormDocument';

const SETTINGS = {
  visibleColumns: [
    'doctype' ,
    'document',
    'action'  ,
  ],
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
  columns       : [
    {
      name : 'doctype',
      field: 'type',
      align: 'left',
      label: 'Tipo de documento'
    },
    {
      name : 'document',
      field: row => formatDocument(row.document),
      align: 'left',
      label: 'Documento'
    },
    { name: 'action' },
  ],
};

Object.freeze(SETTINGS);

export default {
  props: {
    companyId: {
      type    : Number,
      required: true,
    },
  },

  components: {
    FormDocument,
  },

  created() {
    this.onRequest();
  },

  data() {
    return {
      items   : [],
      dialog  : false,
      settings: SETTINGS,
      saving  : false,
    };
  },

  computed: {
    ...mapGetters({
      isLoading: 'profile/isLoading',
    }),
  },

  methods: {
    ...mapActions({
      getItems: 'profile/getDocuments',
      save    : 'profile/updateProfile',
    }),

    onSave(data) {
      this.save({
        id       : this.companyId,
        component: 'document',
        payload  : {
          "operation": "post",
          "payload"  : {
            "type"    : data.type.value,
            "document": data.document
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
          id       : this.companyId,
          component: 'document',
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

      params['myCompany'] = this.companyId;

      this.getItems(params)
        .then(items => {
          if (items.length) {
            for (let index in items) {
              this.items.push({
                id      : items[index]['@id'].match(/^\/documents\/([a-z0-9-]*)$/)[1],
                type    : items[index].documentType.documentType,
                document: items[index].document,
                _bussy  : false,
              });
            }
          }
        });
    },
  },
};
</script>