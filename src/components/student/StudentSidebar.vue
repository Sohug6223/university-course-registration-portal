<script setup lang="ts">
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  Home,
  BookOpen,
  Calendar,
  Award,
  User,
  LogOut,
  LayoutGrid,
  Bell,
} from "lucide-vue-next";

const props = defineProps<{ sidebarOpen: boolean }>();

const emit = defineEmits<{
  close: [];
  logout: [];
}>();

const route = useRoute();
const router = useRouter();
const touchStartX = ref(0);

const isActive = (path: string) => route.path === path;

const navigateTo = (path: string) => {
  emit("close");
  router.push(path);
};

const handleTouchStart = (e: TouchEvent) => {
  touchStartX.value = e.changedTouches[0]?.screenX ?? 0;
};

const handleTouchEnd = (e: TouchEvent) => {
  const x = e.changedTouches[0]?.screenX ?? 0;
  if (x - touchStartX.value > 50) emit("close");
};
</script>

<template>
  <aside
    class="sidebar"
    :class="{ 'sidebar-open': props.sidebarOpen }"
    @touchstart="handleTouchStart"
    @touchend="handleTouchEnd"
  >
    <!-- Header -->
    <div class="sidebar-header">
      <div class="logo-box">
        <img
          src="@/assets/logo/versitylogo.png"
          alt="University Logo"
          class="brand-logo"
        />
      </div>

      <div class="sidebar-brand">
        <h2>City University</h2>
        <span>Student Portal</span>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="navigation">
      <router-link
        to="/student"
        class="nav-item"
        :class="{ active: isActive('/student') }"
        @click="emit('close')"
      >
        <Home :size="18" />
        <span>Dashboard</span>
      </router-link>

      <button
        class="nav-item"
        :class="{ active: isActive('/student/registration') }"
        @click="navigateTo('/student/registration')"
      >
        <LayoutGrid :size="18" />
        <span>Course Registration</span>
      </button>

      <button
        class="nav-item"
        :class="{ active: isActive('/student/my-courses') }"
        @click="navigateTo('/student/my-courses')"
      >
        <BookOpen :size="18" />
        <span>My Courses</span>
      </button>

      <button
        class="nav-item"
        :class="{ active: isActive('/student/routine') }"
        @click="navigateTo('/student/routine')"
      >
        <Calendar :size="18" />
        <span>Class Schedule</span>
      </button>

      <button
        class="nav-item"
        :class="{ active: isActive('/student/results') }"
        @click="navigateTo('/student/results')"
      >
        <Award :size="18" />
        <span>Results</span>
      </button>

      <button
        class="nav-item"
        :class="{ active: isActive('/student/notices') }"
        @click="navigateTo('/student/notices')"
      >
        <Bell :size="18" />
        <span>Notices</span>
      </button>

      <button
        class="nav-item"
        :class="{ active: isActive('/student/profile') }"
        @click="navigateTo('/student/profile')"
      >
        <User :size="18" />
        <span>Profile</span>
      </button>
    </nav>

    <!-- Sign Out -->
    <div class="sidebar-bottom">
      <button class="logout-button" @click="emit('logout')">
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

.sidebar-brand h2 {
  margin: 0;
  font-size: 15px;
  white-space: nowrap;
}

.sidebar-brand span {
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
}

.nav-item {
  width: 100%;
  box-sizing: border-box;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 13px;
  margin-bottom: 5px;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: #cbd5e1;
  text-decoration: none;
  font-size: 12px;
  text-align: left;
  cursor: pointer;
  transition: background .2s, color .2s;
  flex-shrink: 0;
}

.nav-item:hover,
.nav-item.active {
  background: #123b63;
  color: white;
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
  border: 0;
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
}

/* Smaller mobile height */
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