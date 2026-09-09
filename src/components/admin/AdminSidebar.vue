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

// Props 
defineProps<{
  sidebarOpen: boolean;
}>();

// Emits 
const emit = defineEmits<{
  (e: "close-sidebar"): void; 
  (e: "logout"): void;       
}>();

const router = useRouter();
const route = useRoute();

// route
const isActive = (path: string) => {
  if (path === "/admin") {
    return route.path === "/admin";
  }

  return route.path.startsWith(path);
};

// Navigation
const navigateTo = (path: string) => {
  emit("close-sidebar");
  router.push(path);
};
</script>

<template>
  <!-- Mobile overlay -->
  <div v-if="sidebarOpen" class="sidebar-overlay" @click="emit('close-sidebar')"></div>

  <!-- Mian sidebar container -->
  <aside class="sidebar" :class="{ 'sidebar-open': sidebarOpen }">
    <!-- Sidebar header -->
    <div class="sidebar-header">
      <div class="logo-box">
        <img :src="universityLogo" alt="University Logo" class="brand-logo" />
      </div>
      <div class="sidebar-brand">
        <h2>City University</h2>
        <span>Admin Portal</span>
      </div>
    </div>

    <!-- Navigation Link -->
    <nav class="navigation">
      <button class="nav-item" :class="{ active: isActive('/admin') }" @click="navigateTo('/admin')">
        <Home class="nav-icon" :size="18" />
        <span>Dashboard</span>
      </button>

      <button class="nav-item" :class="{ active: isActive('/admin/students') }" @click="navigateTo('/admin/students')">
        <Users class="nav-icon" :size="18" />
        <span>Students</span>
      </button>

      <button class="nav-item" :class="{ active: isActive('/admin/courses') }" @click="navigateTo('/admin/courses')">
        <BookOpen class="nav-icon" :size="18" />
        <span>Courses</span>
      </button>

      <button class="nav-item" :class="{ active: isActive('/admin/department') }" @click="navigateTo('/admin/department')">
        <Layers class="nav-icon" :size="18" />
        <span>Department</span>
      </button>

      <button class="nav-item" :class="{ active: isActive('/admin/results') }" @click="navigateTo('/admin/results')">
        <GraduationCap class="nav-icon" :size="18" />
        <span>Results</span>
      </button>

      <button class="nav-item" :class="{ active: isActive('/admin/prerequisites') }" @click="navigateTo('/admin/prerequisites')">
        <ListChecks class="nav-icon" :size="18" />
        <span>Prerequisites</span>
      </button>

      <button class="nav-item" :class="{ active: isActive('/admin/schedules') }" @click="navigateTo('/admin/schedules')">
        <Calendar class="nav-icon" :size="18" />
        <span>Class Schedules</span>
      </button>

      <button class="nav-item" :class="{ active: isActive('/admin/enrollments') }" @click="navigateTo('/admin/enrollments')">
        <ClipboardList class="nav-icon" :size="18" />
        <span>Enrollments</span>
      </button>

      <button class="nav-item" :class="{ active: isActive('/admin/notices') }" @click="navigateTo('/admin/notices')">
        <Bell class="nav-icon" :size="18" />
        <span>Notices</span>
      </button>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-bottom">
      <button class="logout-button" @click="emit('logout')">
        <LogOut class="nav-icon" :size="18" />
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
  left: 0;
  top: 0;
  bottom: 0;
  z-index: 100;
  transition: transform 0.25s ease;
  touch-action: pan-y;
}
.sidebar-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 24px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.logo-box {
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  background: white;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  padding: 4px;
}
.brand-logo { width: 100%; height: 100%; object-fit: contain; }
.sidebar-brand { min-width: 0; }
.sidebar-header h2 { margin: 0; font-size: 15px; white-space: nowrap; }
.sidebar-header span { display: block; margin-top: 3px; color: #94a3b8; font-size: 10px; }

.navigation {
  padding: 20px 12px;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}
.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 13px;
  margin-bottom: 5px;
  border-radius: 8px;
  color: #cbd5e1;
  font-size: 12px;
  border: none;
  background: transparent;
  width: 100%;
  text-align: left;
  cursor: pointer;
  transition: 0.2s;
}
.nav-item:hover { background: rgba(255, 255, 255, 0.08); color: white; }
.nav-item.active { background: #123b63; color: white; }
.nav-icon { flex-shrink: 0; }

.sidebar-bottom {
  margin-top: auto;
  padding: 16px 12px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}
.logout-button {
  width: 100%;
  border: none;
  background: transparent;
  color: #cbd5e1;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 13px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 12px;
}
.logout-button:hover { background: rgba(255, 255, 255, 0.08); color: white; }
.sidebar-overlay { display: none; }

/* Mobile Responsive */
@media (max-width: 900px) {
  .sidebar {
    left: auto;
    right: 0;
    transform: translateX(100%);
    transition: transform 0.25s ease-in-out;
  }
  .sidebar.sidebar-open { transform: translateX(0); }
  .sidebar-header { justify-content: flex-start; gap: 12px; }
  .sidebar-overlay {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    z-index: 90;
  }
}
</style>