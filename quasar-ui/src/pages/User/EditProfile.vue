<template>
  <q-page>
    <MenuDefaultHeader
      @save="save"
    >
    </MenuDefaultHeader>

    <div class="q-px-lg">
      <q-item class="border-division q-my-sm flex items-center">
        <q-item-section class="q-px-sm" style="max-width: 60%;">
          <q-item-label
            class="q-ma-none q-pa-none text-bold text-secondary"
            style="font-size: 1.5rem;"
          >
            {{ $t('my_name') }} {{ userEdit.user_name }}
          </q-item-label>
        </q-item-section>
        <q-space></q-space>
        <q-avatar size="3rem">
          <img src="https://github.com/CarlosMendesDev.png">
        </q-avatar>
      </q-item>

      <q-item class="q-px-sm q-py-sm column">
        <q-item-section class="border-division">
          <q-item-label class="text-bold text-secondary title-list">
            {{ $t('about_yourself') }}
          </q-item-label>
          <q-input
      dense
      outlined
            v-model="userEdit.description_provider"
            
            label-color="secondary"
            color="secondary"
            autogrow
            placeholder="Ex: Sou legal..."
            borderless
            style="margin-bottom: -1rem;"
            class="q-pa-none full-width text-description"
          />
        </q-item-section>

        <div class="flex itemns-center section-no-margin q-pt-md row">
          <div
            v-for="(lang, index) in userEdit.langs"
            :key="index"
            class="col-6 flex items-center"
          >
            <div
              class="flex items-center tag-style q-px-sm"
              clickable="clickable"
              @click="openDialog('lang', lang)"
            >
              <q-item-label
                class="text-center text-secondary text-bold"
              >
                {{ $t(lang) }}
              </q-item-label>
              <q-icon color="secondary" size="1.3rem" name="expand_more"></q-icon>
            </div>
            <q-btn
              icon="close"
              no-caps
              size="10px"
              class="bg-white q-mb-md q-ml-xs text-secondary close-button"
              round
              flat
              @click="deleteLang(lang)"
            >
            </q-btn>
          </div>

          <div>
            <q-btn
              icon="add"
              no-caps
              class="bg-white q-ml-sm text-secondary add-button"
              round
              flat
              @click="openDialog('lang')"
            >
            </q-btn>
            <OptionsLangDialog
              v-model="openLangDialog"
              @select-lang="selectLang"
            >
            </OptionsLangDialog>
          </div>
        </div>

        <div class="border-division">
          <q-item-section class="q-py-sm">
            <q-item-label class="text-secondary text-bold title-list">
              {{ $t('communicate_sign_language') }}
              <q-icon name="front_hand" color="secondary" size="1rem"></q-icon>
            </q-item-label>
          </q-item-section>

          <div class="row text-grey-10 text-caption full-width">
            <div class="column q-pr-xs">
              <q-radio
                class="q-py-xs"
                color="grey-10"
                v-model="userEdit.sign_language"
                dense
                :val="false"
              />
              <q-item-label class="text-description">{{ $t('yes') }}</q-item-label>
            </div>

            <div class="column q-pl-xs">
              <q-radio
                class="q-py-xs"
                color="grey-10"
                v-model="userEdit.sign_language"
                dense
                :val="true"
              />
              <q-item-label class="text-description">{{ $t('no') }}</q-item-label>
            </div>
          </div>
        </div>

        <div v-if="userRole === 'provider'" class="border-division">
          <q-item-section class="q-py-sm">
            <q-item-label class="text-secondary text-bold title-list">
              {{ $t('you_act_like') }}
            </q-item-label>
            <q-item-label class="text-caption text-italic text-grey-7">
              {{ $t('you_act_like_desc') }}
            </q-item-label>

            <div
              v-for="(act, index) in userEdit.actuations"
              :key="index"
              class="col-12 flex items-center"
            >
              <div
                class="flex items-center tag-style q-px-sm"
                clickable="clickable"
                @click="openDialog('act', act)"
              >
                <q-item-label class="text-center text-secondary text-bold">
                  {{ $t(act) }}
                </q-item-label>
                <q-icon color="secondary" size="1.3rem" name="expand_more"></q-icon>
              </div>
              <q-btn
                icon="close"
                no-caps
                size="10px"
                class="bg-white q-mb-md q-ml-xs text-secondary close-button"
                round
                flat
                @click="deleteAct(act)"
              >
              </q-btn>
            </div>

            <div>
              <q-btn
                icon="add"
                no-caps
                class="bg-white q-ml-sm text-secondary add-button"
                round
                flat
                @click="openDialog('act')"
              >
              </q-btn>
              <OptionsActDialog
                v-model="openActDialog"
                @select-act="selectAct">
              </OptionsActDialog>
            </div>
          </q-item-section>
        </div>

        <div v-if="userRole === 'provider'" class="border-division">
          <q-item-section class="q-mx-none q-py-sm">
            <q-item-label class="text-secondary text-bold title-list">
              {{ $t('have_cadastur') }}
            </q-item-label>
            <q-item-label class="text-caption text-italic text-grey-7">
              {{ $t('have_cnpj') }}
            </q-item-label>
            <q-input
      dense
      outlined
              v-model="userEdit.cnpj_cpf"
              
              label-color="secondary"
              color="secondary"
              autogrow
              placeholder="Ex: 001.234.456/0001-78"
              class="q-pa-none full-width text-description"
            />
            <q-img
              class="q-mt-sm"
              src="https://logodownload.org/wp-content/uploads/2018/02/cadastur-logo.png"
              width="30%">
            </q-img>
          </q-item-section>
        </div>

        <div v-if="userRole === 'provider'" class="border-division">
          <q-item-section class="q-mx-none q-py-sm">
            <q-item-label class="text-secondary text-bold title-list">
              {{ $t('other_authorizations_credentials') }}
            </q-item-label>

            <q-item-label class="text-caption text-italic text-grey-7">
              Ex: {{ $t('other_authorizations_credentials_ex') }}
              Obs: {{ $t('other_authorizations_credentials_obs') }}
            </q-item-label>
          </q-item-section>

          <q-item-section
            v-for="(aut, index) in userEdit.authorizations_credentials"
            :key="index"
            class="section-no-margin q-py-sm"
          >
            <div class="q-my-sm">
              <q-item-label class="text-grey-10 text-description">
                {{ aut.label }}
                <q-btn
                  icon="close"
                  no-caps
                  size="10px"
                  class="bg-white q-ml-xs text-secondary close-button"
                  round
                  flat
                >
                </q-btn>
              </q-item-label>

              <q-item-label class="q-ma-none text-caption title-list text-grey text-italic">
                <q-icon name="verified_user" color="green" size="1.5rem"></q-icon>
                {{ $t('verified') }}
              </q-item-label>
            </div>
          </q-item-section>

          <div>
            <q-btn
              icon="add"
              no-caps
              class="bg-white q-ml-sm text-secondary add-button"
              round
              flat
              @click="openDialog('upload')"
            >
            </q-btn>
          </div>
        </div>

        <div v-if="userRole === 'provider'" class="border-division">
          <q-item-section class="q-mx-none q-py-sm">
            <q-item-label class="text-secondary text-bold title-list">
              {{ $t('certifications_courses') }}
            </q-item-label>

            <q-item-label class="text-caption text-italic text-grey-7">
              Obs: {{ $t('certifications_courses_obs') }}
            </q-item-label>
          </q-item-section>

          <q-item-section
            v-for="(certification, index) in userEdit.certifications"
            :key="index"
            class="section-no-margin q-py-sm"
          >
            <div class="q-my-sm">
              <q-item-label class="text-grey-10 text-description">
                {{ certification }}
                <q-btn
                  icon="close"
                  no-caps
                  size="10px"
                  class="bg-white q-ml-xs text-secondary close-button"
                  round
                  flat>
                </q-btn>
              </q-item-label>

              <q-item-label class="q-ma-none text-caption title-list text-grey text-italic">
                <q-icon name="verified_user" color="green" size="1.5 rem"></q-icon>
                {{ $t('verified') }}
              </q-item-label>
            </div>
          </q-item-section>

          <div>
            <q-btn
              icon="add"
              no-caps
              class="bg-white q-ml-sm text-secondary add-button"
              round
              flat
              @click="openDialog('upload')"
            >
            </q-btn>
            <UploadArchiveDialog v-model="openUploadDialog" :ok="file"></UploadArchiveDialog>
          </div>
        </div>
      </q-item>
    </div>
  </q-page>
</template>

<script>
import OptionsActDialog from '@controleonline/quasar-common-ui/src/components/Dialog/OptionsActDialog';
import OptionsLangDialog from '@controleonline/quasar-common-ui/src/components/Dialog/OptionsLangDialog';
import UploadArchiveDialog from '@controleonline/quasar-common-ui/src/components/Dialog/UploadArchiveDialog';
import MenuDefaultHeader from '@controleonline/quasar-common-ui/src/components/Header/MenuDefaultHeader';

export default {
  components: {
    MenuDefaultHeader,
    OptionsLangDialog,
    OptionsActDialog,
    UploadArchiveDialog,
  },

  data() {
    return {
      userRole: null ?? 'cliente',
      openLangDialog: false,
      openActDialog: false,
      openUploadDialog: false,
      file: null,
      userEdit: {},
      langEdit: '',
      actEdit: '',
    };
  },

  computed: {
    user: {
      get() { return this.$store.getters['User/getUser']; },
      set(value) { this.$store.commit('User/setUser', { user: value }); },
    },
  },

  methods: {
    save() {
      this.$q.loading.show();

      this.$store.commit('User/setUser', { user: this.userEdit });

      this.$q.loading.hide();
    },
    openDialog(value, valueEdit = '') {
      switch (value) {
        case 'lang':
          this.openLangDialog = true;

          this.langEdit = valueEdit;
          break;
        case 'act':
          this.openActDialog = true;

          this.actEdit = valueEdit;
          break;
        default:
          this.openUploadDialog = true;
          break;
      }
    },
    selectLang(lang) {
      const language = this.userEdit.langs
        .find((value) => value === lang);

      if (!language) {
        if (this.langEdit) {
          const index = this.userEdit.langs
            .findIndex((value) => value === this.langEdit);

          this.userEdit.langs[index] = lang;
        } else {
          this.userEdit.langs = [
            ...this.userEdit.langs,
            lang,
          ];
        }
      }
    },
    deleteLang(lang) {
      const newLangs = this.userEdit.langs.filter((langIndex) => langIndex !== lang);

      this.userEdit.langs = newLangs;
    },
    selectAct(act) {
      const action = this.userEdit.actuations
        .find((value) => value === act);

      if (!action) {
        if (this.actEdit) {
          const index = this.userEdit.actuations
            .findIndex((value) => value === this.actEdit);

          this.userEdit.actuations[index] = act;
        } else {
          this.userEdit.actuations = [
            ...this.userEdit.actuations,
            act,
          ];
        }
      }
    },
    deleteAct(act) {
      const newActuations = this.userEdit.actuations.filter((actIndex) => actIndex !== act);

      this.userEdit.actuations = newActuations;
    },
  },

  created() {
    this.userRole = this.$store.getters['User/getUser'].type_account;

    this.userEdit = ((this.$store.getters['User/getUser']));
  },
};
</script>

<style lang="scss" scoped>
.title-list {
  font-size: 1.25rem;
}
.text-description {
  font-size: 1rem;
}
.button-menu {
  border-bottom: solid 1px #C4C4C4;
  border-radius: 0!important;
}
.border-division {
  padding: 30px 0px 30px 0px;
  border-bottom: solid 1px rgb(187, 187, 187);
}
.border-division-dialog{
  border-bottom: solid 1px rgb(187, 187, 187);
}
.add-button {
  width: 1rem;
  height: 1rem;
}
.section-no-margin {
  margin: 0!important;
}
.tag-style {
  height: 2rem;
  border: solid 1px $secondary;
  border-radius: 4px;
}
</style>
