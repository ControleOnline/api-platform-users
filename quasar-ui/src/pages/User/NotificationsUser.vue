<template>
  <div>
    <MenuDefaultHeader
      :title="$t('notifications_header')"
      :save_button="false"
      icon="o_notifications"
    >
    </MenuDefaultHeader>

    <q-tabs
      v-model="tab"
      no-caps
      class="text-grey-9 shadow-2 q-px-md "
    >
      <q-tab name="all" :label="$t('all')" />
      <q-tab name="unread" :label="$t('unread')" />
    </q-tabs>

    <q-tab-panels v-model="tab">
      <q-tab-panel name="all">
        <CardNotifications
          v-for="notification in notifications"
          :key="notification.id"
          @click="openDialog(notification)"
          :image_profile="notification.image_profile"
          :user_name="notification.user_name"
          :event="notification.event"
          :action_notification="notification.action_notification"
          :date="notification.date"
          :read="notification.read"
        >
        </CardNotifications>
      </q-tab-panel>

      <q-tab-panel name="unread">
        <CardNotifications
          v-for="notification in filteredUnreadNotification"
          :key="notification.id"
          @click="openDialog(notification)"
          :image_profile="notification.image_profile"
          :user_name="notification.user_name"
          :event="notification.event"
          :action_notification="notification.action_notification"
          :date="notification.date"
          :read="notification.read"
        >
        </CardNotifications>
      </q-tab-panel>
    </q-tab-panels>
  </div>
</template>

<script>
import CardNotifications from '@controleonline/quasar-common-ui/src/components/Card/CardNotifications';
import NotificationDialog from '@controleonline/quasar-common-ui/src/components/Dialog/NotificationDialog';
import MenuDefaultHeader from '@controleonline/quasar-common-ui/src/components/Header/MenuDefaultHeader';

export default {
  components: {
    MenuDefaultHeader,
    CardNotifications,
  },

  data() {
    return {
      tab: 'all',
    };
  },

  computed: {
    notifications: {
      get() { return this.$store.getters['User/getNotifications']; },
      set(value) { this.$store.commit('User/setNotifications', { notifications: value }); },
    },
    filteredUnreadNotification() {
      return this.notifications.filter((notification) => !notification.read);
    },
  },

  methods: {
    openDialog(notification) {
      const { id, message } = notification;

      const indexNotification = this.notifications
        .findIndex((notify) => notify.id === id);

      this.$store.commit('User/setReadNotification', { index: indexNotification });

      this.$q.dialog({ component: NotificationDialog, parent: this, componentProps: { message } });
    },
  },
};
</script>
