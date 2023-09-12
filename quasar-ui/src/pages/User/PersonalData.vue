<template>
  <q-page>
    <MenuDefaultHeader
      :title="$t('edit_personal_data_header')"
      icon="o_person"
      @save="save"
    >
    </MenuDefaultHeader>

    <div class="q-px-lg">
      <q-item class="border-division">
        <q-item-section>
          <div class="flex text-secondary text-bold full-width text-description">
            <q-radio
              left-label
              v-model="userEdit.type_user"
              dense
              class="text-description"
              color="secondary"
              val="pf"
              :label="$t('pf')"
            />
            <q-space></q-space>
            <q-radio
              left-label
              v-model="userEdit.type_user"
              dense
              class="text-description"
              color="secondary"
              val="pj"
              :label="$t('pj')"
            />
          </div>
        </q-item-section>
      </q-item>

      <q-item class="q-px-none q-py-sm column">
        <q-item-section class="q-py-md">
          <q-item-label class="text-bold text-secondary text-description">
            {{ $t("full_name_company_name") }}
          </q-item-label>
          <q-input
            dense
            outlined
            v-model="userEdit.name_company"
            label-color="secondary"
            color="secondary"
            placeholder="Ex: Juliano Belchior Adventure ME"
            class="q-pa-none full-width text-description"
          />
        </q-item-section>

        <q-item-section class="q-py-md section-no-margin">
          <q-item-label class="text-bold text-secondary text-description">
            CNPJ
          </q-item-label>
          <q-input
            dense
            outlined
            v-model="userEdit.cnpj_cpf"
            label-color="secondary"
            color="secondary"
            placeholder="Ex: 001.234.456/0001-78"
            class="q-pa-none full-width text-description"
          />
        </q-item-section>

        <q-item-section class="q-py-md section-no-margin">
          <q-item-label class="text-bold text-secondary text-description">
            {{ $t("name_of_responsible_person") }}
          </q-item-label>
          <q-input
            dense
            outlined
            v-model="userEdit.user_name"
            label-color="secondary"
            color="secondary"
            placeholder="Ex: Juliano..."
            class="q-pa-none full-width text-description"
          />
        </q-item-section>

        <q-item-section class="q-py-md section-no-margin">
          <q-item-label class="text-bold text-secondary text-description">
            {{ $t("social_name") }}
          </q-item-label>
          <q-input
            dense
            outlined
            v-model="userEdit.social_name"
            label-color="secondary"
            color="secondary"
            placeholder="Ex: Juliano..."
            class="q-pa-none full-width text-description"
          />
        </q-item-section>

        <div class="row q-py-md">
          <q-item-section class="col-5 q-pt-sm section-no-margin">
            <q-item-label class="text-bold text-secondary text-description">
              {{ $t("genre") }}
            </q-item-label>
            <q-input
              dense
              outlined
              v-model="userEdit.gender"
              label-color="secondary"
              color="secondary"
              placeholder="Ex: Outros"
              class="q-pa-none full-width text-description"
            />
          </q-item-section>

          <q-space></q-space>

          <q-item-section class="col-6 q-pt-sm section-no-margin">
            <q-item-label class="text-bold text-secondary text-description">
              {{ $t("birth_date") }}
            </q-item-label>
            <q-input
              dense
              outlined
              v-model="userEdit.birth_date"
              label-color="secondary"
              color="secondary"
              mask="##/##/####"
              placeholder="Ex: 12/12/1222"
              class="q-pa-none full-width text-description"
            />
          </q-item-section>
        </div>

        <div class="row q-py-md">
          <q-item-section class="col-5 q-pt-sm section-no-margin">
            <q-item-label class="text-bold text-secondary text-description">
              RG
            </q-item-label>
            <q-input
              dense
              outlined
              v-model="userEdit.rg"
              label-color="secondary"
              color="secondary"
              mask="##.###.###-##"
              placeholder="Ex: 11.111.111-1"
              class="q-pa-none full-width text-description"
            />
          </q-item-section>

          <q-space></q-space>

          <q-item-section class="col-6 q-pt-sm section-no-margin">
            <q-item-label class="text-bold text-secondary text-description">
              CPF
            </q-item-label>
            <q-input
              dense
              outlined
              v-model="userEdit.cpf"
              label-color="secondary"
              color="secondary"
              mask="###.###.###-##"
              placeholder="Ex: 111.111.111-1"
              class="q-pa-none full-width text-description"
            />
          </q-item-section>
        </div>

        <q-item-section class="q-py-md section-no-margin">
          <q-item-label class="text-bold text-secondary text-description">
            {{ $t("address") }}
          </q-item-label>
          <q-input
            dense
            outlined
            v-model="userEdit.address"
            label-color="secondary"
            color="secondary"
            placeholder="Ex: Rua tal..."
            class="q-pa-none full-width text-description"
          />
        </q-item-section>

        <q-item-section class="q-py-md section-no-margin">
          <q-item-label class="text-bold text-secondary text-description">
            {{ $t("Cidade") }}
          </q-item-label>
          <q-input
            dense
            outlined
            v-model="userEdit.city"
            label-color="secondary"
            color="secondary"
            placeholder="Ex: São Paulo..."
            class="q-pa-none full-width text-description"
          />
        </q-item-section>

        <q-item-section class="q-py-md section-no-margin">
          <q-item-label class="text-bold text-secondary text-description">
            {{ $t("district") }}
          </q-item-label>
          <q-input
            dense
            outlined
            v-model="userEdit.district"
            label-color="secondary"
            color="secondary"
            placeholder="Ex: Jardins..."
            class="q-pa-none full-width text-description"
          />
        </q-item-section>

        <div class="row q-py-md">
          <q-item-section class="col-6 q-pt-sm section-no-margin">
            <q-item-label class="text-bold text-secondary text-description">
              Estado
            </q-item-label>
            <q-select
              dense
              outlined
              v-model="userEdit.state"
              label-color="grey-9"
              color="secondary"
              label="Selecione"
              :options="states"
              class="q-pa-none full-width text-description"
            />
          </q-item-section>

          <q-space></q-space>

          <q-item-section class="col-5 q-pt-sm section-no-margin">
            <q-item-label class="text-bold text-secondary text-description">
              CEP
            </q-item-label>
            <q-input
              dense
              outlined
              v-model="userEdit.cep"
              label-color="secondary"
              color="secondary"
              mask="#####-###"
              placeholder="Ex: 11111-111"
              class="q-pa-none full-width text-description"
            />
          </q-item-section>
        </div>

        <div class="row q-py-md">
          <q-item-section class="col-5 q-pt-sm section-no-margin">
            <q-item-label class="text-bold text-secondary text-description">
              {{ $t("telephone") }}
            </q-item-label>
            <q-input
              dense
              outlined
              v-model="userEdit.telephone"
              label-color="secondary"
              color="secondary"
              mask="(##) ####-####"
              placeholder="Ex: (11) 1111-1111"
              class="q-pa-none full-width text-description"
            />
          </q-item-section>

          <q-space></q-space>

          <q-item-section class="col-6 q-pt-sm section-no-margin">
            <q-item-label class="text-bold text-secondary text-description">
              {{ $t("cell_phone") }}
            </q-item-label>
            <q-input
              dense
              outlined
              v-model="userEdit.cell"
              label-color="secondary"
              color="secondary"
              mask="(##) #####-####"
              placeholder="Ex: (11) 11111-1111"
              class="q-pa-none full-width text-description"
            />
          </q-item-section>
        </div>

        <q-item-section class="q-py-md section-no-margin">
          <q-item-label class="text-bold text-secondary text-descriptions">
            E-mail
          </q-item-label>
          <q-input
            dense
            outlined
            v-model="userEdit.email"
            label-color="secondary"
            color="secondary"
            placeholder="Ex: fulano@gmail.com"
            class="q-pa-none full-width text-description"
          />
        </q-item-section>
      </q-item>
    </div>

    <q-item v-if="userRole === 'cliente'" class="q-pa-none">
      <q-item-section class="q-pa-md bg-grey-4 shadow-6">
        <div class="q-pb-sm flex items-center">
          <q-item-label class="q-ma-none text-bold text-secondary title-list">
            {{ $t("emergency_data") }}
          </q-item-label>
          <q-space></q-space>
          <q-icon name="o_report" color="secondary"></q-icon>
        </div>
        <q-item-label class="q-ma-none text-grey-9 text-description">
          {{ $t("emergency_data_desc") }}
        </q-item-label>
      </q-item-section>
    </q-item>

    <q-item v-if="userRole === 'cliente'" class="column">
      <q-item-section class="q-py-md section-no-margin">
        <q-item-label class="text-bold text-secondary text-description">
          {{ $t("name_of_contact") }}
        </q-item-label>
        <q-input
          dense
          outlined
          v-model="userEdit.name_of_contacts"
          label-color="secondary"
          color="secondary"
          autogrow
          placeholder="Ex: Juliano..."
          class="q-pa-none full-width text-description"
        />
      </q-item-section>

      <q-item-section class="q-py-md section-no-margin">
        <q-item-label class="text-bold text-secondary text-description">
          {{ $t("contact_phone") }}
        </q-item-label>
        <q-input
          dense
          outlined
          v-model="userEdit.contact_phone"
          label-color="secondary"
          color="secondary"
          mask="(##) ####-####"
          placeholder="Ex: Juliano..."
          class="q-pa-none full-width text-description"
        />
      </q-item-section>

      <q-item-section class="q-py-md section-no-margin">
        <q-item-label class="text-bold text-secondary text-description">
          {{ $t("contact_phone_secondary") }}
        </q-item-label>
        <q-input
          dense
          outlined
          v-model="userEdit.contact_phone_secondary"
          label-color="secondary"
          color="secondary"
          mask="(##) #####-####"
          placeholder="Ex: Juliano..."
          class="q-pa-none full-width text-description"
        />
      </q-item-section>

      <q-item-section class="col-6 q-py-md section-no-margin">
        <q-item-label class="text-bold text-secondary text-description">
          {{ $t("blood_type") }}
        </q-item-label>
        <q-select
          dense
          outlined
          v-model="userEdit.blood_type"
          label-color="grey-9"
          color="secondary"
          label="Selecione"
          :options="options"
          class="q-pa-none full-width text-description"
        />
      </q-item-section>

      <q-item-section class="q-py-md section-no-margin">
        <q-item-label class="text-bold text-secondary text-description">
          {{ $t("have_medical_insurance") }}
        </q-item-label>
        <q-input
          dense
          outlined
          v-model="userEdit.medical_insurance"
          label-color="secondary"
          color="secondary"
          placeholder="Ex: Juliano..."
          class="q-pa-none full-width text-description"
        />
      </q-item-section>

      <q-item-section class="q-py-md section-no-margin">
        <q-item-label class="text-bold text-secondary text-description">
          {{ $t("use_any_medication") }}
        </q-item-label>
        <q-input
          dense
          outlined
          v-model="userEdit.use_any_medication"
          label-color="secondary"
          color="secondary"
          placeholder="Ex: Juliano..."
          class="q-pa-none full-width text-description"
        />
      </q-item-section>

      <q-item-section class="q-py-md section-no-margin">
        <q-item-label class="text-bold text-secondary text-description">
          {{ $t("have_any_particularities") }}
        </q-item-label>
        <q-checkbox
          v-model="check_model"
          v-for="check in checks"
          :key="check.value"
          :val="check.label"
          :label="check.label"
          color="secondary"
        />
      </q-item-section>

      <div class="border-division q-py-md">
        <q-item-section class="q-py-sm">
          <q-item-label class="text-secondary text-bold text-description">
            {{ $t("have_heart_problem") }}
          </q-item-label>

          <div
            v-for="(problem, index) in userEdit.problems"
            :key="index"
            class="col-12 flex items-center q-pb-none q-pt-md"
          >
            <q-item-label class="text-center">
              {{ problem }}
            </q-item-label>
          </div>

          <div>
            <q-btn
              icon="add"
              no-caps
              class="bg-white q-ml-sm text-secondary add-button"
              round
              flat
              @click="openProblemDialog = true"
            >
            </q-btn>
            <DefaultAddDialog
              :title="$t('problems')"
              :subtitle="$t('what_prolem')"
              v-model="openProblemDialog"
              @add-included="addIncluded($event, 'problems')"
            >
            </DefaultAddDialog>
          </div>
        </q-item-section>
      </div>

      <div class="border-division q-py-md">
        <q-item-section class="q-py-sm">
          <q-item-label class="text-secondary text-bold text-description">
            {{ $t("have_allergy_medication") }}
          </q-item-label>

          <div
            v-for="(allergy, index) in userEdit.allergies"
            :key="index"
            class="col-12 flex items-center q-pb-none q-pt-md"
          >
            <q-item-label class="text-center">
              {{ allergy }}
            </q-item-label>
          </div>

          <div>
            <q-btn
              icon="add"
              no-caps
              class="bg-white q-ml-sm text-secondary add-button"
              round
              flat
              @click="openUserAllergyDialog = true"
            >
            </q-btn>
            <DefaultAddDialog
              :title="$t('allergies')"
              :subtitle="$t('what_allergy')"
              v-model="openUserAllergyDialog"
              @add-included="addIncluded($event, 'allergies')"
            >
            </DefaultAddDialog>
          </div>
        </q-item-section>
      </div>
    </q-item>
  </q-page>
</template>

<script>
import DefaultAddDialog from "@controleonline/quasar-common-ui/src/components/Dialog/DefaultAddDialog";
import MenuDefaultHeader from "@controleonline/quasar-common-ui/src/components/Header/MenuDefaultHeader";

export default {
  components: {
    MenuDefaultHeader,
    DefaultAddDialog,
  },

  data() {
    return {
      userRole: null ?? "cliente",
      check_model: [],
      openProblemDialog: false,
      openUserAllergyDialog: false,
      userEdit: {},
    };
  },

  computed: {
    states: {
      get() {
        return this.$store.getters["Menu/getStates"];
      },
      set(value) {
        this.$store.commit("Menu/setStates", { states: value });
      },
    },
    user: {
      get() {
        return this.$store.getters["User/getUser"];
      },
      set(value) {
        this.$store.commit("User/setUser", { user: value });
      },
    },
    options: {
      get() {
        return this.$store.getters["User/getOptionsBloodType"];
      },
      set(value) {
        this.$store.commit("User/setOptionsBloodType", { optionsBloodType: value });
      },
    },
    checks: {
      get() {
        return this.$store.getters["User/getOptionsChecksIllnesses"];
      },
      set(value) {
        this.$store.commit("User/setOptionsChecksIllnesses", {
          optionsChecksIllnesses: value,
        });
      },
    },
  },

  watch: {
    check_model() {
      const activeOuther = this.check_model.find((check) => check === "Outro");

      if (!activeOuther) {
        this.outher_check = false;
      } else {
        this.outher_check = true;
      }
    },
  },

  methods: {
    save() {
      this.$q.loading.show();

      this.$store.commit("User/setUser", { user: this.userEdit });

      this.$q.loading.hide();
    },
    addIncluded(value, module) {
      this.userEdit[module].push(value);
    },
  },

  created() {
    this.userRole = this.$store.getters["User/getUser"].type_account;

    this.userEdit = ((this.$store.getters["User/getUser"]));
  },
};
</script>

<style lang="scss" scoped>
.button-menu {
  border-bottom: solid 1px #c4c4c4;
  border-radius: 0 !important;
}
.text-description {
  font-size: 1rem;
}
.border-division {
  padding: 30px 0px 30px 0px;
  border-bottom: solid 1px rgb(187, 187, 187);
}
.section-no-margin {
  margin: 0 !important;
}
</style>
