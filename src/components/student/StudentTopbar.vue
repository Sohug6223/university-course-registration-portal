<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import {
  ChevronDown,
  ChevronUp,
  User,
  Lock,
  LogOut,
  Menu,
} from "lucide-vue-next";

interface StudentUser {
  name?: string;
  user_id?: string;
  email?: string;
}

defineProps<{
  user: StudentUser;
  title: string;
  subtitle: string;
}>();

const emit = defineEmits<{
  openSidebar: [];
  logout: [];
}>();

const router = useRouter();
const profileMenuOpen = ref(false);

const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as HTMLElement;

  if (!target.closest(".profile-wrapper")) {
    profileMenuOpen.value = false;
  }
};

const handleKeyDown = (event: KeyboardEvent) => {
  if (event.key === "Escape") {
    profileMenuOpen.value = false;
  }
};

const openProfile = () => {
  profileMenuOpen.value = false;
  router.push("/student/profile");
};

const changePassword = () => {
  profileMenuOpen.value = false;
  router.push("/change-password");
};

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
  window.addEventListener("keydown", handleKeyDown);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
  window.removeEventListener("keydown", handleKeyDown);
});
</script>

<template>
  <header class="topbar">
    <div class="topbar-left">
      <div class="mobile-logo-box">
        <img
          src="@/assets/logo/versitylogo.png"
          alt="University Logo"
          class="brand-logo"
        />
      </div>

      <div>
        <h1>{{ title }}</h1>
        <p>{{ subtitle }}</p>
      </div>
    </div>

    <div class="topbar-right">
      <!-- Profile -->
      <div class="profile-wrapper" @click.stop>
        <button
          class="student-mini-profile"
          @click="profileMenuOpen = !profileMenuOpen"
        >
          <div class="avatar">
            {{ user.name?.charAt(0)?.toUpperCase() || "S" }}
          </div>

          <div class="mini-info">
            <strong>{{ user.name || "Student" }}</strong>
            <span>{{ user.user_id || "N/A" }}</span>
          </div>

          <component
            :is="profileMenuOpen ? ChevronUp : ChevronDown"
            :size="14"
            class="profile-arrow"
          />
        </button>

        <!-- Dropdown -->
        <div
          v-if="profileMenuOpen"
          class="profile-dropdown"
        >
          <div class="dropdown-user">
            <div class="dropdown-avatar">
              {{ user.name?.charAt(0)?.toUpperCase() || "S" }}
            </div>

            <div>
              <strong>{{ user.name || "Student" }}</strong>
              <span>{{ user.email || "N/A" }}</span>
            </div>
          </div>

          <div class="dropdown-divider" />

          <button class="dropdown-item" @click="openProfile">
            <User :size="15" />
            <span>My Profile</span>
          </button>

          <button class="dropdown-item" @click="changePassword">
            <Lock :size="15" />
            <span>Change Password</span>
          </button>

          <div class="dropdown-divider" />

          <button
            class="dropdown-item logout-item"
            @click="emit('logout')"
          >
            <LogOut :size="15" />
            <span>Sign Out</span>
          </button>
        </div>
      </div>

      <!-- Mobile Menu -->
      <button
        class="hamburger-button"
        aria-label="Open menu"
        @click="emit('openSidebar')"
      >
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

.topbar-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

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
  box-shadow: 0 2px 6px rgba(0,0,0,.04);
}

.brand-logo {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.topbar h1 {
  margin: 0;
  font-size: 23px;
  font-weight: 750;
}

.topbar p {
  margin: 5px 0 0;
  font-size: 12px;
  color: #64748b;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.profile-wrapper {
  position: relative;
}

.student-mini-profile {
  display: flex;
  align-items: center;
  gap: 10px;
  border: 0;
  background: transparent;
  cursor: pointer;
  padding: 5px;
  border-radius: 10px;
}

.student-mini-profile:hover {
  background: #eef2f7;
}

.avatar,
.dropdown-avatar {
  border-radius: 50%;
  background: #123b63;
  color: white;
  display: grid;
  place-items: center;
  font-weight: 700;
}

.avatar {
  width: 38px;
  height: 38px;
}

.dropdown-avatar {
  width: 36px;
  height: 36px;
  font-size: 13px;
}

.mini-info {
  text-align: left;
  max-width: 150px;
}

.mini-info strong,
.mini-info span,
.dropdown-user strong,
.dropdown-user span {
  display: block;
}

.mini-info strong {
  font-size: 12px;
}

.mini-info span {
  margin-top: 2px;
  font-size: 10px;
  color: #64748b;
}

.profile-arrow {
  color: #64748b;
}

.profile-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 240px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 15px 35px rgba(15,23,42,.15);
  padding: 8px;
  z-index: 200;
}

.dropdown-user {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
}

.dropdown-user strong {
  font-size: 11px;
  color: #0f172a;
}

.dropdown-user span {
  margin-top: 3px;
  max-width: 155px;
  font-size: 9px;
  color: #64748b;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dropdown-divider {
  height: 1px;
  background: #e2e8f0;
  margin: 6px 0;
}

.dropdown-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  border: 0;
  border-radius: 7px;
  background: transparent;
  color: #334155;
  font-size: 11px;
  text-align: left;
  cursor: pointer;
}

.dropdown-item:hover {
  background: #f1f5f9;
}

.logout-item {
  color: #dc2626;
}

.hamburger-button {
  display: none;
  width: 36px;
  height: 36px;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: #0f172a;
  cursor: pointer;
  align-items: center;
  justify-content: center;
}

@media (max-width: 900px) {
  .hamburger-button {
    display: flex;
  }
}

@media (max-width: 650px) {
  .topbar h1 {
    font-size: 17px;
  }

  .topbar p {
    font-size: 10px;
  }

  .mini-info,
  .profile-arrow {
    display: none;
  }

  .avatar {
    width: 36px;
    height: 36px;
  }

  .profile-dropdown {
    width: 225px;
  }
}

@media (max-width: 400px) {
  .topbar h1 {
    font-size: 15px;
  }

  .topbar p {
    display: none;
  }

  .mobile-logo-box {
    width: 40px;
    height: 40px;
  }
}
</style>