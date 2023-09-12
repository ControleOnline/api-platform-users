<template>
  <div class="row">
    <div class="col-12 q-mt-md">
      <q-table
        dense
        :rows="items"
        :columns="settings.columns"
        :visible-columns="settings.visibleColumns"
        row-key="id"
        :loading="isLoading"
      >
        <template v-slot:top>
          <div class="col-3 q-mb-md text-h6">Lista de telefones</div>
          <div class="col-9 q-mb-md">
            <div class="row justify-end">
              <q-btn
                label="Adicionar"
                icon="add"
                size="md"
                color="primary"
                class="q-ml-sm"
                @click="dialog = !dialog"
              />
            </div>
          </div>
        </template>

        <template v-slot:body="props">
          <q-tr :props="props">
            <q-td key="phoneDdd" :props="props">{{ props.cols[0].value }}</q-td>
            <q-td key="phoneNumber" :props="props">{{ props.cols[1].value }}</q-td>
            <q-td auto-width>
              <q-btn
                flat
                round
                dense
                color="red"
                icon="delete"
                @click="removeItem(props.row)"
                :disable="items.length == 1"
                :loading="props.row._bussy"
              />
            </q-td>
          </q-tr>
        </template>
      </q-table>
    </div>

    <q-dialog v-model="dialog">
      <q-card style="width: 700px; max-width: 80vw">
        <q-card-section class="row items-center">
          <div class="text-h6">Novo telefone</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section>
          <FormPhone ref="myForm" @save="onSave" />
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import FormPhone from "./FormPhone";

const SETTINGS = {
  visibleColumns: ["phoneDdd", "phoneNumber", "action"],
  columns: [
    {
      name: "phoneDdd",
      field: (row) => row.ddd,
      label: "DDD",
      align: "left",
    },
    {
      name: "phoneNumber",
      field: (row) => row.phone,
      label: "Telefone",
      align: "left",
    },
    { name: "action" },
  ],
};

Object.freeze(SETTINGS);

export default {
  components: {
    FormPhone,
  },

  data() {
    return {
      items: [],
      dialog: false,
      settings: SETTINGS,
      saving: false,
    };
  },

  created() {
    this.onRequest();
  },

  computed: {
    ...mapGetters({
      isLoading: "profile/isLoading",
      myCompany: "people/currentCompany",
    }),

    user() {
      return this.$store.getters["auth/user"];
    },
  },

  watch: {
    myCompany(company) {
      if (company !== null) this.onRequest();
    },
  },

  methods: {
    ...mapActions({
      getItems: "profile/getPhones",
      save: "profile/updateProfile",
    }),

    onSave(data) {
      this.save({
        id: this.user.people,
        component: "phone",
        payload: {
          operation: "post",
          payload: {
            ddd: data.ddd,
            phone: data.phone,
          },
        },
      })
        .then((data) => {
          if (data) {
            this.$refs.myForm.reset();

            this.onRequest();

            this.$q.notify({
              message: "Os dados foram salvos com sucesso",
              position: "bottom",
              type: "positive",
            });
          }
        })
        .catch((error) => {
          this.$refs.myForm.reset();

          this.$q.notify({
            message: error.message,
            position: "bottom",
            type: "negative",
          });
        });
    },

    removeItem(item) {
      if (window.confirm("Tem certeza que deseja eliminar este registro?")) {
        item._bussy = true;

        this.save({
          id: this.user.people,
          component: "phone",
          payload: {
            operation: "delete",
            payload: {
              id: item.id,
            },
          },
        })
          .then((data) => {
            if (data) {
              this.cleanItem(item["id"]);
            }
          })
          .catch((error) => {
            this.$q.notify({
              message: error.message,
              position: "bottom",
              type: "negative",
            });
          })
          .finally(() => {
            item._bussy = false;
          });
      }
    },

    cleanItem(id) {
      let item = this.items.find((obj) => obj["id"] == id);
      let indx = this.items.indexOf(item);
      this.items = [...this.items.slice(0, indx), ...this.items.slice(indx + 1)];
    },

    onRequest() {
      let params = {};

      this.items = [];

      this.getItems(params).then((items) => {
        if (items.length) {
          for (let index in items) {
            this.items.push({
              id: items[index]["@id"].match(/^\/phones\/([a-z0-9-]*)$/)[1],
              ddd: items[index].ddd,
              phone: items[index].phone,
              _bussy: false,
            });
          }
        }
      });
    },
  },
};
</script>
