<template>
  <div>
    <MenuDefaultHeader
      :title="$t('your_favorites_header')"
      icon="o_favorite"
    >
    </MenuDefaultHeader>

    <CardListGuides
      v-for="(item, index) in list"
      :key="item.user_name"
      :id="item.id"
      :index="index"
      :title_guide="item.title_guide"
      :price="item.price"
      :images_slide="item.images_slide"
      :favorite="item.favorite"
      :image_profile="item.image_profile"
      :user_name="item.user_name"
      :assessment="item.assessment"
      :difficulty="item.difficulty"
      :distance="item.distance"
      :duration="item.duration"
      :vacancies="item.vacancies">
    </CardListGuides>
    <ToolsFooter></ToolsFooter>
  </div>
</template>

<script>
import CardListGuides from '@controleonline/quasar-common-ui/src/components/Card/CardListGuides';
import MenuDefaultHeader from '@controleonline/quasar-common-ui/src/components/Header/MenuDefaultHeader';

export default {
  components: {
    MenuDefaultHeader,
    CardListGuides,
  },

  computed: {
    list: {
      get() { return this.$store.getters['Guides/getGuidesList']; },
      set(value) { this.$store.commit('Guides/setGuidesList', { list: value }); },
    },
  },
};
</script>
