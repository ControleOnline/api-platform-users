<template>
  <q-page padding>
    <div class="row">
      <div class="col-12">
        <q-card style="min-height: 90vh;">
          <div class="q-pa-md text-h6">
            Meu perfil
          </div>

          <div class="q-pa-md text-subtitle1 text-center">
            <q-avatar size="80px">
              <q-img :src="user.avatar || gravatar" />
            </q-avatar>
            <div class="q-pa-md text-subtitle1">
              {{ user.realname || 'John Doe' }}
            </div>
          </div>

          <q-tabs
            :horizontal="$q.screen.gt.xs"
            align      ="justify"
            v-model    ="currentTab"
            class      ="bg-white text-primary"
          >
            <q-tab
              name ="phones"
              label="Telefones"
            />
            <q-tab
              name ="address"
              label="Endereços"
            />
            <q-tab
              name ="emails"
              label="Emails"
            />
            <q-tab
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
            <q-tab-panel name="phones">
              <UserPhones   />
            </q-tab-panel>

            <q-tab-panel name="address">
              <UserAddress  />
            </q-tab-panel>

            <q-tab-panel name="emails">
              <UserEmails   />
            </q-tab-panel>

            <q-tab-panel name="users">
              <UserUsers    />
            </q-tab-panel>

            <q-tab-panel name="documents">
              <UserDocuments />
            </q-tab-panel>
          </q-tab-panels>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
 import md5 from 'md5';
import UserAddress from '../../components/user/profile/UserAddress';
import UserDocuments from '../../components/user/profile/UserDocuments';
import UserEmails from '../../components/user/profile/UserEmails';
import UserPhones from '../../components/user/profile/UserPhones';
import UserUsers from '../../components/user/profile/UserUsers';

export default {
  name      : 'UserProfilePage',

  components: {
    UserPhones   ,
    UserAddress  ,
    UserEmails   ,
    UserUsers    ,
    UserDocuments,
  },

  computed: {
    user() {
      return this.$store.getters['auth/user'] || {};
    },

    gravatar() {
      if (this.user.email === undefined)
        return '';

      return `https://www.gravatar.com/avatar/${md5(this.user.email)}?s=400`;
    },
  },

  data () {
    return {
      currentTab: 'phones',
    }
  },

  methods: {

  },
}
</script>