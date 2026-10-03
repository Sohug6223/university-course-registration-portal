<script setup lang="ts">
import { useRoute, useRouter } from "vue-router";
import universityLogo from "@/assets/logo/versitylogo.png";

import {
  Home,
  Users,
  BookOpen,
  Layers,
  GraduationCap,
  ListChecks,
  Calendar,
  ClipboardList,
  Bell,
  LogOut,
} from "lucide-vue-next";

defineProps<{
  sidebarOpen: boolean;
}>();

const emit = defineEmits<{
  (e: "close-sidebar"): void;
  (e: "logout"): void;
}>();

const router = useRouter();
const route = useRoute();

const isActive = (path: string) =>
  path === "/admin"
    ? route.path === "/admin"
    : route.path.startsWith(path);

const navigateTo = (path: string) => {
  emit("close-sidebar");
  router.push(path);
};
</script>

<template>
  <!-- Mobile Overlay -->
  <div
    v-if="sidebarOpen"
    class="sidebar-overlay"
    @click="emit('close-sidebar')"
  ></div>

  <aside
    class="sidebar"
    :class="{ 'sidebar-open': sidebarOpen }"
  >
    <!-- Header -->
    <div class="sidebar-header">
      <div class="logo-box">
        <img
          :src="universityLogo"
          alt="University Logo"
          class="brand-logo"
        />
      </div>

      <div class="sidebar-brand">
        <h2>City University</h2>
        <span>Admin Portal</span>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="navigation">
      <button
        class="nav-item"
        :class="{ active: isActive('/admin') }"
        @click="navigateTo('/admin')"
      >
        <Home :size="18" />
        <span>Dashboard</span>
      </button>

      <button
        class="nav-item"
        :class="{ active: isActive('/admin/students') }"
        @click="navigateTo('/admin/students')"
      >
        <Users :size="18" />
        <span>Students</span>
      </button>

      <button
        class="nav-item"
        :class="{ active: isActive('/admin/courses') }"
        @click="navigateTo('/admin/courses')"
      >
        <BookOpen :size="18" />
        <span>Courses</span>
      </button>

      <button
        class="nav-item"
        :class="{ active: isActive('/admin/department') }"
        @click="navigateTo('/admin/department')"
      >
        <Layers :size="18" />
        <span>Department</span>
      </button>

      <button
        class="nav-item"
        :class="{ active: isActive('/admin/results') }"
        @click="navigateTo('/admin/results')"
      >
        <GraduationCap :size="18" />
        <span>Results</span>
      </button>

      <button
        class="nav-item"
        :class="{ active: isActive('/admin/prerequisites') }"
        @click="navigateTo('/admin/prerequisites')"
      >
        <ListChecks :size="18" />
        <span>Prerequisites</span>
      </button>

      <button
        class="nav-item"
        :class="{ active: isActive('/admin/schedules') }"
        @click="navigateTo('/admin/schedules')"
      >
        <Calendar :size="18" />
        <span>Class Schedules</span>
      </button>

      <button
        class="nav-item"
        :class="{ active: isActive('/admin/enrollments') }"
        @click="navigateTo('/admin/enrollments')"
      >
        <ClipboardList :size="18" />
        <span>Enrollments</span>
      </button>

      <button
        class="nav-item"
        :class="{ active: isActive('/admin/notices') }"
        @click="navigateTo('/admin/notices')"
      >
        <Bell :size="18" />
        <span>Notices</span>
      </button>
    </nav>

    <!-- Sign Out -->
    <div class="sidebar-bottom">
      <button
        class="logout-button"
        @click="emit('logout')"
      >
        <LogOut :size="18" />
        <span>Sign Out</span>
      </button>
    </div>
  </aside>
</template>

<style scoped>
.sidebar {
  width: 245px;
  min-height: 100vh;
  background: #0a1930;
  color: white;
  display: flex;
  flex-direction: column;
  position: fixed;
  inset: 0 auto 0 0;
  z-index: 100;
  transition: transform 0.25s ease;
  touch-action: pan-y;
}

/* Header */
.sidebar-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 24px 20px;
  border-bottom: 1px solid rgba(255,255,255,.08);
  flex-shrink: 0;
}

.logo-box {
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  background: white;
  border-radius: 10px;
  padding: 4px;
  display: grid;
  place-items: center;
  overflow: hidden;
}

.brand-logo {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.sidebar-brand {
  min-width: 0;
}

.sidebar-header h2 {
  margin: 0;
  font-size: 15px;
  white-space: nowrap;
}

.sidebar-header span {
  display: block;
  margin-top: 3px;
  color: #94a3b8;
  font-size: 10px;
}

/* Navigation */
.navigation {
  padding: 20px 12px;
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
  overflow-y: auto;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 11px 13px;
  margin-bottom: 5px;
  box-sizing: border-box;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: #cbd5e1;
  font-size: 12px;
  text-align: left;
  cursor: pointer;
  transition: background .2s, color .2s;
  flex-shrink: 0;
}

.nav-item:hover {
  background: rgba(255,255,255,.08);
  color: white;
}

.nav-item.active {
  background: #123b63;
  color: white;
}

.nav-icon {
  flex-shrink: 0;
}

/* Sign Out */
.sidebar-bottom {
  margin-top: auto;
  padding: 16px 12px;
  border-top: 1px solid rgba(255,255,255,.08);
  flex-shrink: 0;
}

.logout-button {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 13px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: #cbd5e1;
  font-size: 12px;
  cursor: pointer;
}

.logout-button:hover {
  background: rgba(255,255,255,.08);
  color: white;
}

.sidebar-overlay {
  display: none;
}

/* Mobile */
@media (max-width: 900px) {
  .sidebar {
    left: auto;
    right: 0;
    width: 245px;
    height: 100dvh;
    min-height: 100dvh;
    transform: translateX(100%);
    overflow: hidden;
  }

  .sidebar.sidebar-open {
    transform: translateX(0);
  }

  .sidebar-header {
    padding: 16px 20px;
  }

  .navigation {
    padding: 12px;
    overflow: hidden;
  }

  .nav-item {
    padding: 9px 13px;
    margin-bottom: 3px;
    min-height: 38px;
  }

  .sidebar-bottom {
    padding: 10px 12px;
  }

  .logout-button {
    padding: 9px 13px;
    min-height: 38px;
  }

  .sidebar-overlay {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,.45);
    z-index: 90;
  }
}

/* Small-height Mobile */
@media (max-width: 900px) and (max-height: 700px) {
  .sidebar-header {
    padding: 12px 20px;
  }

  .navigation {
    padding: 8px 12px;
  }

  .nav-item {
    padding: 7px 13px;
    margin-bottom: 2px;
    min-height: 34px;
  }

  .sidebar-bottom {
    padding: 7px 12px;
  }

  .logout-button {
    padding: 7px 13px;
    min-height: 34px;
  }
}
</style>