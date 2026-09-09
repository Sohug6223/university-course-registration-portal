<script setup lang="ts">
import {
  computed,
  onMounted,
  onBeforeUnmount,
  ref,
} from "vue";

import { useRouter } from "vue-router";

import StudentSidebar from "./StudentSidebar.vue";
import StudentTopbar from "./StudentTopbar.vue";

const props = withDefaults(
  defineProps<{
    title?: string;
    subtitle?: string;
  }>(),
  {
    title: "Student Dashboard",
    subtitle:
      "Manage your courses and academic information.",
  }
);

const router = useRouter();

const sidebarOpen = ref(false);

const user = computed(() => {
  const storedUser =
    localStorage.getItem("user") ||
    sessionStorage.getItem("user");

  if (!storedUser) {
    return {
      name: "Student",
      user_id: "N/A",
      email: "N/A",
      role: "student",
    };
  }

  try {
    return JSON.parse(storedUser);
  } catch {
    return {
      name: "Student",
      user_id: "N/A",
      email: "N/A",
      role: "student",
    };
  }
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

const handleResize = () => {
  if (window.innerWidth > 900) {
    sidebarOpen.value = false;
  }
};

const handleKeyDown = (event: KeyboardEvent) => {
  if (event.key === "Escape") {
    sidebarOpen.value = false;
  }
};

onMounted(() => {
  window.addEventListener(
    "resize",
    handleResize
  );

  window.addEventListener(
    "keydown",
    handleKeyDown
  );
});

onBeforeUnmount(() => {
  window.removeEventListener(
    "resize",
    handleResize
  );

  window.removeEventListener(
    "keydown",
    handleKeyDown
  );
});
</script>

<template>
  <div class="student-layout">
    <!-- Mobile Overlay -->
    <div
      v-if="sidebarOpen"
      class="sidebar-overlay"
      @click="sidebarOpen = false"
    />

    <!-- Sidebar -->
    <StudentSidebar
      :sidebar-open="sidebarOpen"
      @close="sidebarOpen = false"
      @logout="logout"
    />

    <!-- Main -->
    <main class="main-content">
      <StudentTopbar
        :user="user"
        :title="props.title"
        :subtitle="props.subtitle"
        @open-sidebar="sidebarOpen = true"
        @logout="logout"
      />

      <div class="page-content">
        <slot :user="user" />
      </div>
    </main>
  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.student-layout {
  min-height: 100vh;
  display: flex;
  background: #f5f7fb;
  color: #0f172a;
  font-family:
    "Inter",
    system-ui,
    -apple-system,
    sans-serif;
}

.main-content {
  margin-left: 245px;
  width: calc(100% - 245px);
  padding: 28px 34px;
}

.page-content {
  width: 100%;
}

.sidebar-overlay {
  display: none;
}

@media (max-width: 900px) {
  .main-content {
    margin-left: 0;
    width: 100%;
    padding: 20px;
  }

  .sidebar-overlay {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    z-index: 90;
  }
}

@media (max-width: 650px) {
  .main-content {
    padding: 15px;
  }
}
</style>