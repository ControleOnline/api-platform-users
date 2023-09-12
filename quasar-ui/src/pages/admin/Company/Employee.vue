<template>
  <q-page padding>
    <div class="row">
      <div class="col-12">
        <q-card style="min-height: 90vh;">
          <q-card-section>
            <q-btn flat no-caps
              icon ="arrow_back"
              :to  ="{
                name  : `${userTypeCapitalized}.Company.Details`,
                params: {
                  id: this.companyId
                }
              }"
              label="Voltar à Minha empresa"
            />
          </q-card-section>
          <div class="q-pa-md text-subtitle1 text-center">
            <q-avatar size="80px">
              <q-img :src="employee.avatar" />
            </q-avatar>
            <div class="q-pa-md text-subtitle1">
              {{ employee.realname || 'John Doe' }}
            </div>
          </div>

          <q-tabs
            :horizontal="$q.screen.gt.xs"
            align      ="justify"
            v-model    ="currentTab"
            class      ="bg-white text-primary"
          >
            <q-tab v-if="employee.type === 'J'"
              name ="employees"
              label="Funcionários"
            />
            <q-tab v-if="employee.type === 'F'"
              name ="phones"
              label="Telefones"
            />
            <q-tab
              name ="address"
              label="Endereços"
            />
            <q-tab v-if="employee.type === 'F'"
              name ="emails"
              label="Emails"
            />
            <q-tab v-if="employee.type === 'F'"
              name ="users"
              label="Usuários"
            />
            <q-tab
              name ="documents"
              label="Documentos"
            />
          </q-tabs>

          <q-separator />

          <q-tab-panels
            v-model="currentTab"
          >
            <q-tab-panel name="employees">
              <CompanyEmployees
                :id   ="id"
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

            <q-tab-panel name="phones">
              <UserPhones
                :id="id"
              />
            </q-tab-panel>

            <q-tab-panel name="address">
              <UserAddress
                :id="id"
              />
            </q-tab-panel>

            <q-tab-panel name="emails">
              <UserEmails
                :id="id"
              />
            </q-tab-panel>

            <q-tab-panel name="users">
              <UserUsers
                :id="id"
              />
            </q-tab-panel>

            <q-tab-panel name="documents">
              <UserDocuments
                :id="id"
              />
            </q-tab-panel>
          </q-tab-panels>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import CompanyEmployees from './../../../components/company/CompanyEmployees';
import UserAddress from './../../../components/user/profile/UserAddress';
import UserDocuments from './../../../components/user/profile/UserDocuments';
import UserEmails from './../../../components/user/profile/UserEmails';
import UserPhones from './../../../components/user/profile/UserPhones';
import UserUsers from './../../../components/user/profile/UserUsers';

export default {
  name      : 'UserProfilePage',

  components: {
    UserPhones      ,
    UserAddress     ,
    UserEmails      ,
    UserUsers       ,
    UserDocuments   ,
    CompanyEmployees,
  },

  data () {
    return {
      id        : parseInt(decodeURIComponent(this.$route.params.employeeId)),
      companyId : parseInt(decodeURIComponent(this.$route.params.id)),
      currentTab: null,
      employee  : {
        avatar  : null,
        realname: null,
        type    : null,
      },
    }
  },

  created() {
    this.getEmployee();
  },

  computed: {
    ...mapGetters({
      user: 'auth/user',
    }),

    userTypeCapitalized() {
      return this.user ?
        `${this.user.type.charAt(0).toUpperCase()}${this.user.type.slice(1)}`
        : 'Guest';
    },
  },

  methods: {
    ...mapActions({
      getPeople: 'people/getPeople',
    }),

    getEmployee() {
      return this.getPeople(this.id)
        .then(data => {
          if (data['@id']) {
            this.employee.realname = data.peopleType == 'J' ?
              `${data.name} / ${data.alias}` : `${data.name} ${data.alias}`;
            this.employee.avatar   = data.image ? data.image.url : null;
            this.employee.type     = data.peopleType;

            this.currentTab        = data.peopleType == 'J' ? 'employees' : 'phones';
          }
        });
    }
  },
}
</script>
