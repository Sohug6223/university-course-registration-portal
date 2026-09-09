<script setup lang="ts">
import { computed, ref } from "vue";
import { useRouter } from "vue-router";

import AdminSidebar from "./AdminSidebar.vue";
import AdminTopbar from "./AdminTopbar.vue";

interface User {
  name?: string;
  email?: string;
  role?: string;
}

const props = withDefaults(
  defineProps<{
    title?: string;
    subtitle?: string;
  }>(),
  {
    title: "Admin Dashboard",
    subtitle:
      "Manage students, courses and academic activities.",
  }
);

const router = useRouter();

const sidebarOpen = ref(false);

const user = computed<User>(() => {
  try {
    const storedUser =
      localStorage.getItem("user") ||
      sessionStorage.getItem("user");

    if (storedUser) {
      return JSON.parse(storedUser);
    }
  } catch (error) {
    console.error(
      "Failed to load user:",
      error
    );
  }

  return {
    name: "Administrator",
    email: "admin@example.com",
    role: "admin",
  };
});

const logout = async () => {
  // Clear persistent authentication
  localStorage.removeItem("auth_token");
  localStorage.removeItem("user");
  localStorage.removeItem("remember_me");

  // Clear session authentication
  sessionStorage.removeItem("auth_token");
  sessionStorage.removeItem("user");

  // Close sidebar
  sidebarOpen.value = false;

  // Go to login
  await router.replace("/");
};
</script>

<template>
  <div class="admin-layout">
    <!-- Sidebar -->
    <AdminSidebar
      :sidebar-open="sidebarOpen"
      @close-sidebar="sidebarOpen = false"
      @logout="logout"
    />

    <!-- Main Area -->
    <main class="admin-main">
      <!-- Topbar -->
      <AdminTopbar
        :user="user"
        :title="props.title"
        :subtitle="props.subtitle"
        @open-sidebar="sidebarOpen = true"
        @logout="logout"
      />

      <!-- Page Content -->
      <div class="admin-content">
        <slot :user="user" />
      </div>
    </main>
  </div>
</template>

<style scoped>
.admin-layout {
  min-height: 100vh;
  background: #f5f7fb;
}

.admin-main {
  min-height: 100vh;
  margin-left: 245px;
  padding: 28px 34px 30px;
  box-sizing: border-box;
}

.admin-content {
  width: 100%;
}

/* Tablet */
@media (max-width: 1100px) {
  .admin-main {
    padding: 24px;
  }
}

/* Mobile */
@media (max-width: 900px) {
  .admin-main {
    margin-left: 0;
    padding: 20px;
  }
}

/* Small Mobile */
@media (max-width: 600px) {
  .admin-main {
    padding: 16px;
  }
}
</style>