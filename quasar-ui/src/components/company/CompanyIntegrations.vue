<template>
  <div>
    <q-tabs no-caps align="justify" v-model="currentTab" class="bg-white text-primary">
      <q-tab name="configs" label="Configurações gerais" />
      <q-tab name="zapsign" label="Zapsign" />
      <q-tab name="clicksign" label="Clicksign" />
    </q-tabs>

    <q-tab-panels v-model="currentTab">
      <q-tab-panel v-if="integrations !== null" name="configs">
        <div class="row q-pt-md">
          <div class="col-xs-12 q-mb-md">
            <q-select
              dense
              outlined
              stack-label
              emit-value
              map-options
              label="Serviço de assinaturas padrão"
              v-model="integrations.default_provider['provider-signature'].value"
              :options="select.signature_providers"
              @input="
                (value) => {
                  this.integrations.default_provider['provider-signature'].changed = true;
                }
              "
            />
          </div>
          <div class="col-xs-12 q-mb-md">
            <q-input
              dense
              outlined
              lazy-rules
              v-model="items.email.value"
              type="text"
              :label="$t('Email para integrações')"
              :stack-label="true"
              :rules="[isInvalid('email')]"
              @input="
                (value) => {
                  items.email.changed = true;
                }
              "
            />
          </div>
        </div>
      </q-tab-panel>

      <q-tab-panel v-if="integrations !== null" name="zapsign">
        <div v-if="integrations.zapsign" class="row q-pt-md">
          <div
            v-for="(field, fieldId) in integrations.zapsign"
            :key="fieldId"
            class="col-xs-12 q-mb-md"
          >
            <q-input
              dense
              outlined
              v-if="field.type == 'string'"
              v-model="field.value"
              type="text"
              :label="$t(fieldId)"
              :stack-label="true"
              :readonly="field.readonly === true"
              @input="
                (value) => {
                  field.changed = true;
                }
              "
            />
            <q-checkbox
              v-else-if="field.type === 'bool'"
              v-model="field.value"
              :label="$t(fieldId)"
              true-value="1"
              false-value="0"
              @input="
                (value) => {
                  field.changed = true;
                }
              "
            />
          </div>
        </div>
      </q-tab-panel>

      <q-tab-panel v-if="integrations !== null" name="clicksign">
        <div v-if="integrations !== null && integrations.clicksign" class="row q-pt-md">
          <div
            v-for="(field, fieldId) in integrations.clicksign"
            :key="fieldId"
            class="col-xs-12 q-mb-md"
          >
            <q-input
              dense
              outlined
              v-if="field.type == 'string'"
              v-model="field.value"
              type="text"
              :label="$t(fieldId)"
              :stack-label="true"
              :readonly="field.readonly === true"
              @input="
                (value) => {
                  field.changed = true;
                }
              "
            />
            <q-checkbox
              v-else-if="field.type === 'bool'"
              v-model="field.value"
              :label="$t(fieldId)"
              true-value="1"
              false-value="0"
              @input="
                (value) => {
                  field.changed = true;
                }
              "
            />
          </div>
        </div>
      </q-tab-panel>
    </q-tab-panels>

    <div class="col-12">
      <div class="row justify-end">
        <q-btn
          :loading="saving"
          icon="save"
          type="submit"
          label="Salvar"
          size="md"
          color="primary"
          class="q-mt-md"
          @click="onSave"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex";

export default {
  props: {
    companyId: {
      type: Number,
      required: true,
    },
  },

  data() {
    return {
      saving: false,
      integrations: null,
      currentTab: "configs",
      items: {
        email: {
          value: null,
          changed: false,
        },
      },
      select: {
        signature_providers: [
          {
            label: "Selecione uma opção",
            value: null,
          },
          {
            label: "Clicksign",
            value: "clicksign",
          },
          {
            label: "Zapsign",
            value: "zapsign",
          },
        ],
      },
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
  },

  methods: {
    ...mapActions({
      getConfig: "config/getConfig",
      saveConfig: "config/saveConfig",
      getEmails: "profile/getEmails",
      saveEmail: "profile/updateProfile",
    }),

    onRequest() {
      this.getConfig(this.companyId).then((config) => {
        if (config && config !== null) {
          if (config.integrations) {
            this.integrations = config.integrations;
          }
        }
      });

      this.getEmails({
        people: this.companyId,
      }).then((items) => {
        if (items.length) {
          this.items.email.value = items[0].email;
        }
      });
    },

    onSave() {
      this.saveProfileConfigs();
      this.saveIntegrationConfigs();
    },

    saveProfileConfigs() {
      if (this.items.email.changed) {
        this.saveEmail({
          id: this.companyId,
          component: "email",
          payload: {
            operation: "post",
            payload: {
              email: this.items.email.value,
            },
          },
        }).then((data) => {
          this.$q.notify({
            message: "Dados salvos com sucesso!",
            position: "bottom",
            type: "positive",
          });
        });
      }
    },

    saveIntegrationConfigs() {
      if (this.integrations == null) {
        return;
      }

      let config = {};

      Object.entries(this.integrations.default_provider).forEach(([fieldId, field]) => {
        if (field.changed && field.value !== null) {
          config[fieldId] = field.value;
        }
      });

      Object.entries(this.integrations.zapsign).forEach(([fieldId, field]) => {
        if (field.changed && field.value !== null) {
          config[fieldId] = field.value;
        }
      });

      Object.entries(this.integrations.clicksign).forEach(([fieldId, field]) => {
        if (field.changed && field.value !== null) {
          config[fieldId] = field.value;
        }
      });

      if (!Object.keys(config).length) {
        return;
      }

      this.saving = true;
      this.saveConfig({
        peopleId: this.companyId,
        values: {
          integrations: config,
        },
      })
        .then((data) => {
          if (data.response && data.response.success) {
            this.$q.notify({
              message: "Dados salvos com sucesso!",
              color: "positive",
            });
          }
        })
        .finally(() => {
          this.saving = false;
        });
    },

    isInvalid(key) {
      return (val) => {
        switch (key) {
          case "email":
            if (!(val && val.length > 0)) {
              return true;
            } else {
              if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val))
                return "O email informado não é válido";
            }
            break;
        }

        if (!(val && val.length > 0)) return "Este campo é obrigatório";

        return true;
      };
    },
  },
};
</script>
