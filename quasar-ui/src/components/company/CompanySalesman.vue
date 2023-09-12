<template>
  <div class="row">
    <div class="col-12 q-mt-md">
      <q-table
        dense
        grid
        hide-header
        :loading="isLoading"
        :rows="items"
        row-key="id"
        :pagination.sync="pagination"
        @request="onRequest"
        :rows-per-page-options="[5, 10, 15, 20, 25, 50]"
      >
        <template v-slot:top>
          <div class="col-12 q-mb-md">
            <div class="row justify-end">
              <q-btn
                :label="$t('Adicionar')"
                icon="add"
                size="md"
                color="primary"
                class="q-ml-sm"
                @click="dialog = !dialog"
              />
            </div>
          </div>
        </template>

        <template v-slot:item="props">
          <div class="q-pa-xs col-xs-12 col-sm-4 col-md-3 col-lg-2">
            <q-card>
              <q-img
                :contain="true"
                src="~assets/business.png"
                style="height: 150px; max-width: 100%"
              >
                <div class="absolute-bottom text-subtitle1 text-center">
                  {{ props.row.name }}
                </div>
              </q-img>
              <q-card-section>
                <q-list>
                  <q-item dense>
                    <q-item-section avatar>
                      <q-icon name="perm_identity" />
                    </q-item-section>
                    <q-item-section side>
                      <q-item-label caption>{{ props.row.document }}</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item dense>
                    <q-item-section avatar>
                      <q-icon name="email" />
                    </q-item-section>
                    <q-item-section side>
                      <q-item-label caption>{{ props.row.email }}</q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-card-section>
              <q-separator />
              <q-card-actions align="around">
                <q-btn
                  flat
                  round
                  dense
                  :to="{
                    name: `${userTypeCapitalized}.Company.Employee`,
                    params: {
                      id: id,
                      employeeId: props.row.id,
                    },
                  }"
                  color="primary"
                  icon="edit"
                  :disable="props.row._bussy"
                >
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>

                <q-btn
                  flat
                  round
                  dense
                  color="red"
                  icon="delete"
                  @click="removeItem(props.row)"
                  :loading="props.row._bussy"
                >
                  <q-tooltip>Eliminar</q-tooltip>
                </q-btn>
              </q-card-actions>
            </q-card>
          </div>
        </template>
      </q-table>
    </div>

    <q-dialog v-model="dialog">
      <q-card style="width: 700px; max-width: 80vw">
        <q-card-section class="row items-center">
          <div class="text-h6">Novo administrativo</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section>
          <q-form ref="myForm" @submit="onSubmit" class="q-mt-md">
            <div class="row q-col-gutter-sm">
              <div class="col-xs-12">
                <q-input
                  dense
                  outlined
                  stack-label
                  lazy-rules
                  unmasked-value
                  v-model="item.document"
                  type="text"
                  class="q-mb-sm"
                  :label="$t('CNPJ')"
                  :mask="'##.###.###/####-##'"
                  :placeholder="'Digite o CNPJ'"
                  :rules="[isInvalid('document')]"
                />
              </div>
              <div class="col-xs-12 col-sm-6">
                <q-input
                  dense
                  outlined
                  stack-label
                  lazy-rules
                  v-model="item.name"
                  type="text"
                  class="q-mb-sm"
                  :label="$t('Razão social')"
                  :placeholder="'Razão social'"
                  :rules="[isInvalid('name')]"
                />
              </div>
              <div class="col-xs-12 col-sm-6">
                <q-input
                  dense
                  outlined
                  stack-label
                  lazy-rules
                  v-model="item.alias"
                  type="text"
                  class="q-mb-sm"
                  :label="$t('Nome Fantasia')"
                  :placeholder="'Nome fantasia'"
                  :rules="[isInvalid('alias')]"
                />
              </div>
            </div>
            <div class="row justify-end">
              <q-btn
                :loading="saving"
                icon="save"
                type="submit"
                :label="$t('Salvar')"
                size="md"
                color="primary"
                class="q-mt-md"
              />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script>
import { api } from "@controleonline/../../src/boot/api";
import { formatDocument } from "@controleonline/quasar-common-ui/src/utils/formatter";
import md5 from "md5";
import { mapGetters } from "vuex";

export default {
  props: {
    id: {
      required: true,
    },
  },

  data() {
    return {
      items: [],
      dialog: false,
      saving: false,
      isLoading: false,
      item: {
        document: "",
        name: "",
        alias: "",
      },
      pagination: {
        sortBy: "email",
        descending: false,
        page: 1,
        rowsPerPage: 15,
        rowsNumber: 15,
      },
    };
  },

  created() {
    this.onRequest({
      pagination: this.pagination,
    });
  },

  computed: {
    ...mapGetters({
      myProvider: "people/currentCompany",
      user: "auth/user",
    }),

    userTypeCapitalized() {
      return this.user
        ? `${this.user.type.charAt(0).toUpperCase()}${this.user.type.slice(1)}`
        : "Guest";
    },
  },

  methods: {
    // store method
    getItems(params) {
      return api.fetch
        (`companies/${this.id}/salesman`, { params })

        .then((result) => {
          return result.response.data;
        });
    },

    // store method
    save(values) {
      let options = {
        method: "PUT",
        
        body: (values),
      };

      return api.fetch
        (`companies/${this.id}/salesman`, options)

        .then((data) => {
          if (data.response) {
            if (data.response.success === false) throw new Error(data.response.error);

            return data.response.data;
          }

          return null;
        });
    },

    // store method
    delete(id) {
      let options = {
        method: "DELETE",
        
        body: ({ id }),
      };

      return api.fetch
        (`companies/${this.id}/salesman`, options)

        .then((data) => {
          if (data.response) {
            if (data.response.success === false) throw new Error(data.response.error);

            return data.response.data;
          }

          return null;
        });
    },

    gravatar(email) {
      return "https://www.gravatar.com/avatar/" + md5(email) + "?s=400";
    },

    onSubmit() {
      this.$refs.myForm.validate().then((success) => {
        if (success) {
          this.saving = true;

          this.save({
            document: this.item.document,
            name: this.item.name,
            alias: this.item.alias,
          })
            .then((data) => {
              this.$refs.myForm.reset();

              this.onRequest({
                pagination: this.pagination,
              });

              this.$emit("saved", data);
            })
            .catch((error) => {
              this.$refs.myForm.reset();

              this.$emit("error", { message: error.message });
            })
            .finally(() => {
              this.saving = false;
            });
        }
      });
    },

    removeItem(item) {
      if (window.confirm(this.$t("Are you sure about to remove this element?"))) {
        item._bussy = true;

        this.delete(item.id)
          .then((data) => {
            if (data) {
              this.cleanItem(item.id);
            }
          })
          .catch((error) => {
            this.$emit("error", { message: error.message });
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

    onRequest(props) {
      if (this.isLoading) return;

      let { page, rowsPerPage, rowsNumber, sortBy, descending } = props.pagination;
      let params = { itemsPerPage: rowsPerPage, page };

      this.isLoading = true;

      this.getItems(params)
        .then((data) => {
          let _items = [];

          if (data.members.length) {
            for (let index in data.members) {
              let image = data.members[index].image
                ? data.members[index].image.url
                : null;
              if (image == null) {
                image = this.gravatar(
                  data.members[index].email
                    ? data.members[index].email
                    : "email@gmail.com"
                );
              }

              _items.push({
                id: data.members[index].id,
                name:
                  data.members[index].type == "J"
                    ? data.members[index].name
                    : `${data.members[index].name} ${data.members[index].alias}`,
                image: image,
                email: data.members[index].email,
                document: formatDocument(data.members[index].document),
                _bussy: false,
              });
            }
          }

          this.items = _items;
          this.pagination.page = page;
          this.pagination.rowsPerPage = rowsPerPage;
          this.pagination.sortBy = sortBy;
          this.pagination.descending = descending;
          this.pagination.rowsNumber = data.total;
        })
        .finally(() => {
          this.isLoading = false;
        });
    },

    isInvalid(key) {
      return (val) => {
        if (!(val && val.length > 0)) return this.$t("messages.fieldRequired");

        if (key == "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val))
          return this.$t("messages.emailInvalid");

        return true;
      };
    },
  },
};
</script>
