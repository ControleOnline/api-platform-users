<template>
  <q-page padding>
    <div class="row">
      <div class="col-12">
        <q-card style="min-height: 90vh;">
          <q-card-section>
            <q-btn flat no-caps
              icon ="arrow_back"
              :to  ="{ name: 'Admin.Company.Index'}"
              label="Voltar às Minhas empresas"
            />
          </q-card-section>
          <div class="q-pa-md text-subtitle1 text-center">
            <q-avatar size="80px">
              <img :src="company.image">
            </q-avatar>
            <div class="q-pa-md text-subtitle1">
              {{ company.name }}
            </div>
          </div>

          <q-tabs
            :horizontal="$q.screen.gt.xs"
            align      ="justify"
            v-model    ="currentTab"
            class      ="bg-white text-primary"
          >
            <q-tab
              name ="employees"
              label="Funcionários"
            />
            <q-tab
              name ="salesman"
              label="Administrativos"
            />
            <q-tab
              name ="address"
              label="Endereços"
            />
            <q-tab
              name ="documents"
              label="Documentos"
            />
            <q-tab
              name ="invoicement"
              label="Faturamento"
            />
            <q-tab
              name ="integrations"
              label="Integrações"
            />
          </q-tabs>

          <q-separator />

          <q-tab-panels
            v-model="currentTab"
          >
            <q-tab-panel name="employees">
              <CompanyEmployees
                :id   ="companyId"
                @error="(error) => {
                  this.$q.notify({
                    message : error.message,
                    position: 'bottom',
                    type    : 'negative',
                  });
                }"
                @saved="(data) => {
                  this.$q.notify({
                    message : 'Data successfully saved',
                    position: 'bottom',
                    type    : 'positive',
                  });
                }"
              />
            </q-tab-panel>

            <q-tab-panel name="salesman">
              <CompanySalesman
                :id   ="companyId"
                @error="(error) => {
                  this.$q.notify({
                    message : error.message,
                    position: 'bottom',
                    type    : 'negative',
                  });
                }"
                @saved="(data) => {
                  this.$q.notify({
                    message : 'Data successfully saved',
                    position: 'bottom',
                    type    : 'positive',
                  });
                }"
              />
            </q-tab-panel>

            <q-tab-panel name="address">
              <CompanyAddress     :companyId="companyId" />
            </q-tab-panel>

            <q-tab-panel name="documents">
              <CompanyDocuments   :companyId="companyId" />
            </q-tab-panel>

            <q-tab-panel name="invoicement">
              <CompanyInvoicement :companyId="companyId" />
            </q-tab-panel>

            <q-tab-panel name="integrations">
              <CompanyIntegrations :companyId="companyId" />
            </q-tab-panel>
          </q-tab-panels>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
import { mapActions } from 'vuex';
import { ENTRYPOINT } from '../../../../../../../src/config/entrypoint';
import CompanyAddress from './../../../components/company/CompanyAddress';
import CompanyDocuments from './../../../components/company/CompanyDocuments';
import CompanyEmployees from './../../../components/company/CompanyEmployees';
import CompanyIntegrations from './../../../components/company/CompanyIntegrations';
import CompanyInvoicement from './../../../components/company/CompanyInvoicement';
import CompanySalesman from './../../../components/company/CompanySalesman';

export default {
  name      : 'CompanyDetailsPage',

  components: {
    CompanyEmployees  ,
    CompanyAddress    ,
    CompanyDocuments  ,
    CompanyInvoicement,
    CompanySalesman   ,
    CompanyIntegrations,
  },

  created() {
    if (this.$route.params.id) {
      this.companyId = parseInt(decodeURIComponent(this.$route.params.id));

      this.getCompany(this.companyId)
        .then(company => {
          if (company) {
            this.company.name  = company.alias;
            this.company.image = (
              company.image !== null ?
                `https://${ENTRYPOINT}${company.image.url}` : null
            );
          }
        });
    }
  },

  data () {
    return {
      currentTab: 'employees',
      companyId : -1,
      company   : {
        name : '',
        image: ''
      },
    };
  },

  methods: {
    ...mapActions({
      getCompany: 'people/getPeople',
    }),
  },
}
</script>
