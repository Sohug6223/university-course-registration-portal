<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import universityLogo from "@/assets/logo/versitylogo.png";
import { User, Lock, LogOut, ChevronDown, ChevronUp, Menu } from "lucide-vue-next";

// Props
defineProps<{
  user: { name?: string; email?: string; role?: string };
  title?: string;
  subtitle?: string;
}>();

// Emits
const emit = defineEmits<{
  (e: "open-sidebar"): void;
  (e: "logout"): void;
}>();

const router = useRouter();
const profileMenuOpen = ref(false); 

// Profile page
const openProfile = () => {
  profileMenuOpen.value = false;
  router.push("/admin/profile");
};

// Password Page
const changePassword = () => {
  profileMenuOpen.value = false;
  router.push("/change-password");
};


const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as HTMLElement;
  if (!target.closest(".profile-wrapper")) {
    profileMenuOpen.value = false;
  }
};

onMounted(() => document.addEventListener("click", handleClickOutside));
onBeforeUnmount(() => document.removeEventListener("click", handleClickOutside));
</script>

<template>
  <header class="topbar">
    <!--Topbar left side -->
    <div class="topbar-left">
      <div class="mobile-logo-box">
        <img :src="universityLogo" alt="University Logo" class="brand-logo" />
      </div>
      <div class="topbar-title">
        <h1>{{ title || 'Admin Dashboard' }}</h1>
        <p>{{ subtitle || 'Manage students, courses and academic activities.' }}</p>
      </div>
    </div>

    <!-- Topbar right side -->
    <div class="topbar-right">
      <!-- Profile Dropdown -->
      <div class="profile-wrapper" @click.stop>
        <button class="admin-mini-profile" @click="profileMenuOpen = !profileMenuOpen">
          <div class="avatar">
            {{ user.name?.charAt(0)?.toUpperCase() || "A" }}
          </div>
          <div class="mini-info">
            <strong>{{ user.name }}</strong>
            <span>Administrator</span>
          </div>
          <component :is="profileMenuOpen ? ChevronUp : ChevronDown" class="profile-arrow" :size="14" />
        </button>

        <!-- Dropdown Content -->
        <div v-if="profileMenuOpen" class="profile-dropdown">
          <div class="dropdown-user">
            <div class="dropdown-avatar">
              {{ user.name?.charAt(0)?.toUpperCase() || "A" }}
            </div>
            <div>
              <strong>{{ user.name }}</strong>
              <span>{{ user.email }}</span>
            </div>
          </div>
          <div class="dropdown-divider"></div>
          <button class="dropdown-item" @click="openProfile">
            <User :size="15" />
            <span>My Profile</span>
          </button>
          <button class="dropdown-item" @click="changePassword">
            <Lock :size="15" />
            <span>Change Password</span>
          </button>
          <div class="dropdown-divider"></div>
          <button class="dropdown-item logout-item" @click="emit('logout')">
            <LogOut :size="15" />
            <span>Sign Out</span>
          </button>
        </div>
      </div>

      <!-- Mobile responsive sidebar -->
      <button class="hamburger-button" @click="emit('open-sidebar')" aria-label="Open menu">
        <Menu :size="22" />
      </button>
    </div>
  </header>
</template>

<style scoped>
.topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
}
.topbar-left { display: flex; align-items: center; gap: 14px; }
.mobile-logo-box {
  display: flex;
  width: 48px;
  height: 48px;
  flex-shrink: 0;
  background: white;
  border-radius: 12px;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  padding: 2px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}
.brand-logo { width: 100%; height: 100%; object-fit: contain; }
.topbar h1 { margin: 0; font-size: 23px; font-weight: 750; }
.topbar p { margin: 5px 0 0; font-size: 12px; color: #64748b; }
.topbar-right { display: flex; align-items: center; gap: 8px; }

.hamburger-button {
  display: none;
  width: 36px;
  height: 36px;
  border: none;
  background: transparent;
  border-radius: 8px;
  cursor: pointer;
  color: #0f172a;
  align-items: center;
  justify-content: center;
  padding: 0;
  transition: background 0.2s ease;
}
.hamburger-button:hover { background: #e2e8f0; }

.profile-wrapper { position: relative; }
.admin-mini-profile {
  display: flex;
  align-items: center;
  gap: 10px;
  border: none;
  background: transparent;
  cursor: pointer;
  padding: 5px;
  border-radius: 10px;
}
.admin-mini-profile:hover { background: #eef2f7; }
.avatar {
  width: 38px;
  height: 38px;
  background: #123b63;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  flex-shrink: 0;
}
.mini-info { text-align: left; max-width: 150px; }
.mini-info strong { display: block; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mini-info span { display: block; margin-top: 2px; font-size: 10px; color: #64748b; }
.profile-arrow { color: #64748b; margin-left: 3px; }

.profile-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 240px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 15px 35px rgba(15, 23, 42, 0.15);
  padding: 8px;
  z-index: 200;
}
.dropdown-user { display: flex; align-items: center; gap: 10px; padding: 10px; }
.dropdown-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #123b63;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
  flex-shrink: 0;
}
.dropdown-user strong { display: block; font-size: 11px; color: #0f172a; }
.dropdown-user span { display: block; margin-top: 3px; font-size: 9px; color: #64748b; max-width: 155px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dropdown-divider { height: 1px; background: #e2e8f0; margin: 6px 0; }
.dropdown-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  border: none;
  background: transparent;
  padding: 10px;
  border-radius: 7px;
  cursor: pointer;
  color: #334155;
  font-size: 11px;
  text-align: left;
}
.dropdown-item:hover { background: #f1f5f9; }
.logout-item { color: #dc2626; }
.logout-item:hover { background: #fef2f2; }

@media (max-width: 900px) {
  .hamburger-button { display: flex; }
}
@media (max-width: 650px) {
  .topbar { align-items: center; }
  .topbar-left { align-items: center; gap: 10px; }
  .topbar h1 { font-size: 17px; }
  .topbar p { font-size: 10px; }
  .mini-info, .profile-arrow { display: none; }
  .admin-mini-profile { padding: 2px; }
  .avatar { width: 36px; height: 36px; }
  .profile-dropdown { right: 0; width: 225px; }
}
@media (max-width: 400px) {
  .topbar h1 { font-size: 15px; }
  .topbar p { display: none; }
  .mobile-logo-box { width: 40px; height: 40px; }
  .avatar, .hamburger-button { width: 34px; height: 34px; }
}
</style>