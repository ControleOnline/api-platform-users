<template>
  <div>
    <ViewProfileHeader
      :user_name="profile_provider.user_name"
      :image_profile="profile_provider.image_profile"
    >
    </ViewProfileHeader>

    <q-page>
      <q-tabs
        v-model="tab"
        no-caps
        class="text-grey-9 shadow-2 q-px-lg"
      >
        <q-tab name="aboutme" :label="$t('aboutme')" />
        <q-tab name="roudmap" :label="$t('guides')" />
        <q-tab name="assessments" :label="$t('assessments')" />
      </q-tabs>

      <q-tab-panels v-model="tab">
        <q-tab-panel name="aboutme" class="q-gutter-y-md">
          <div>
            <p class="q-py-sm q-ma-none q-px-sm text-grey-9 text-description">
              {{ profile_provider.description_provider }}
            </p>
          </div>

          <div v-if="profile_provider.cadastur && userRole === 'provider'" class="q-py-sm">
            <q-img
              src="https://logodownload.org/wp-content/uploads/2018/02/cadastur-logo.png"
              width="30%">
            </q-img>
            <p class="q-py-sm q-ma-none q-px-sm text-grey-9 text-description">
              {{ profile_provider.cadastur }}
            </p>
          </div>

          <div v-if="profile_provider.langs.length" class="q-pa-sm">
            <p class="q-ma-none q-mb-sm text-bold title-list">{{ $t('languages_you_speak') }}</p>
            <div class="q-pa-xs q-gutter-x-sm">
              <q-avatar
                v-for="lang in profile_provider.langs"
                :key="lang"
                size="2rem"
              >
                <img :src="`/flags/${lang}.png`">
              </q-avatar>
            </div>
          </div>

          <div
            v-if="profile_provider.sign_language"
            class="q-pa-sm"
          >
            <p class="q-ma-none q-mb-sm text-bold title-list">{{ $t('sign_language') }}</p>
            <div class="q-pa-xs q-gutter-x-sm">
              <q-icon name="front_hand" color="secondary" size="1.5rem"></q-icon>
            </div>
          </div>

          <div
            v-if="profile_provider.certifications.length && userRole === 'provider'"
            class="q-pa-sm"
          >
            <div>
              <p class="q-ma-none title-list text-bold">Certificações e cursos</p>
              <div class="q-pa-xs q-gutter-x-sm">
                <p
                  v-for="item in profile_provider.certifications"
                  :key="item"
                  class="q-ma-none q-px-sm q-py-sm text-grey-9 text-description"
                >
                  {{ item }}
                </p>
              </div>
            </div>
          </div>
        </q-tab-panel>

        <q-tab-panel class="q-pa-none" name="roudmap">
          <div v-if="userRole === 'provider'">
            <CardListGuides
              v-for="item in list"
              :key="item.user_name"
              :id="item.id"
              :title_guide="item.title_guide"
              :price="item.price"
              :images_slide="item.images_slide"
              :image_profile="item.image_profile"
              :user_name="item.user_name"
              :assessment="item.assessment"
              :difficulty="item.difficulty"
              :distance="item.distance"
              :duration="item.duration"
              :vacancies="item.vacancies">
            </CardListGuides>
          </div>

          <div v-if="userRole === 'cliente'">
            <CardGuidesCustomers
              v-for="(guide, index) in next_guides"
              :key="index"
              :image_guide="guide.image_guide"
              :title_guide="guide.title_guide"
              :provider="guide.provider"
              :date="guide.date"
            ></CardGuidesCustomers>
          </div>
        </q-tab-panel>

        <q-tab-panel class="q-px-lg" name="assessments">
          <div class="border-division" v-for="item in assessments" :key="item">
            <div class="flex items-center">
              <q-avatar size="2.3rem">
                <img :src="item.image_profile">
              </q-avatar>

              <div class="q-px-sm">
                <div class="flex items-center">
                  <q-item-section class="q-ma-none text-grey-9 text-bold">
                    {{ item.user_name }} | {{ item.value }}
                  </q-item-section>
                  <q-icon class="q-px-xs" size="1rem" name="star" color="orange-5"></q-icon>
                </div>

                <div>
                  <q-item-section class="q-ma-none text-grey-8 text-caption">
                    {{ item.date }}
                  </q-item-section>
                </div>
              </div>
            </div>

            <div>
              <q-icon
                v-for="n in item.value"
                :key="n"
                class="q-py-sm"
                size="1rem"
                name="star"
                color="orange-5">
              </q-icon>
              <q-item-section class="q-ma-none text-grey-9 text-caption text-italic text-description">
                {{ item.comment }}
              </q-item-section>
            </div>
          </div>
        </q-tab-panel>
      </q-tab-panels>
    </q-page>
  </div>
</template>

<script>
import CardGuidesCustomers from '@controleonline/quasar-common-ui/src/components/Card/CardGuidesCustomers';
import CardListGuides from '@controleonline/quasar-common-ui/src/components/Card/CardListGuides';
import ViewProfileHeader from '@controleonline/quasar-common-ui/src/components/Header/ViewProfileHeader';

export default {
  components: {
    ViewProfileHeader,
    CardListGuides,
    CardGuidesCustomers,
  },

  computed: {
    profile_provider: {
      get() { return this.$store.getters['User/getProfileProvider']; },
    },
    assessments: {
      get() { return this.$store.getters['User/getAssessments']; },
    },
    list: {
      get() { return this.$store.getters['Guides/getGuidesList']; },
      set(value) { this.$store.commit('Guides/setGuidesList', { list: value }); },
    },
    next_guides: {
      get() { return this.$store.getters['Guides/getNextGuides']; },
      set(value) { this.$store.commit('Guides/setNextGuides', { next_guides: value }); },
    },
  },

  data() {
    return {
      tab: 'aboutme',
      userRole: null ?? 'cliente',
    };
  },

  created() {
    this.userRole = this.$store.getters['User/getUser'].type_account;
  },
};
</script>

<style lang="scss" scoped>
.text-description {
  font-size: 1rem;
}
.title-list {
  font-size: 1.125rem;
}
.border-division {
  padding: 30px 0px 30px 0px;
  border-bottom: solid 1px grey;
}
</style>
