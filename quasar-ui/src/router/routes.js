export const routes = [
  {
    path: "/user/",
    component: () => import("@controleonline/quasar-layout-ui/src/layouts/AdminLayout.vue"),
    children: [
      {
        path: "/view-profile",
        name: "view-profile",
        component: () => import("../pages/User/ViewProfile.vue"),
      },

      {
        path: "/personal-data",
        name: "personal-data",
        component: () => import("../pages/User/PersonalData.vue"),
      },

      {
        path: "/edit-profile",
        name: "edit-profile",
        component: () => import("../pages/User/EditProfile.vue"),
      },

      {
        path: "/your-favorites",
        name: "your-favorites",
        component: () => import("../pages/User/YourFavorites.vue"),
      },

      {
        path: "/notifications-user",
        name: "notifications-user",
        component: () => import("../pages/User/NotificationsUser.vue"),
      },
      {
        name: "UserProfile",
        path: "profile",
        component: () => import("../pages/User/Profile.vue"),
      },
    ],
  },
  {
    path: "/company/",
    component: () => import("@controleonline/quasar-layout-ui/src/layouts/AdminLayout.vue"),
    children: [
      {
        path: "id/:id/employees/:employeeId",
        name: "CompanyEmployee",
        component: () => import("../pages/admin/Company/Employee.vue"),
      },
      {
        name: "CompanyIndex",
        path: "",
        component: () => import("../pages/admin/Company/Index.vue"),
      },
      {
        name: "CompanyDetailsPage",
        path: "id/:id",
        component: () => import("../pages/admin/Company/Details.vue"),
      },
    ],
  },
  {
    path: "/forgot-password",
    component: () => import("@controleonline/quasar-layout-ui/src/layouts/MainLayout.vue"),
    children: [
      {
        name: "ForgotPassword",
        path: ":hash/:lost",
        component: () => import("../pages/User/Recovery/AccessRecovery.vue"),
      },
    ],
  },
];
