<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import {
  ArrowLeft,
  Check,
  Edit2,
  Lock,
  Mail,
  ShieldCheck,
  User,
  UserCog,
  X,
  RefreshCw,
} from "lucide-vue-next";
import { useRouter } from "vue-router";

import AdminLayout from "@/components/admin/AdminLayout.vue";
import api from "@/services/api";

interface AdminUser {
  id: number;
  user_id?: string;
  name: string;
  email: string;
  role: string;
  status: string;
  created_at?: string | null;
}

const router = useRouter();

const loading = ref(true);
const saving = ref(false);
const editing = ref(false);
const error = ref("");
const success = ref("");

const user = ref<AdminUser | null>(null);

const form = reactive({
  name: "",
  email: "",
});

const initials = computed(() => {
  const name = user.value?.name?.trim();

  if (!name) return "A";

  return name
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0))
    .join("")
    .toUpperCase();
});

const displayRole = computed(() => {
  if (!user.value?.role) return "Administrator";

  return user.value.role.toLowerCase() === "admin"
    ? "Administrator"
    : user.value.role;
});

const loadProfile = async () => {
  loading.value = true;
  error.value = "";

  try {
    const { data } = await api.get("/admin/profile");

    user.value = data.user;

    form.name = data.user.name || "";
    form.email = data.user.email || "";

    updateStoredUser(data.user);
  } catch (err: any) {
    error.value =
      err?.response?.data?.message ||
      "Unable to load admin profile.";
  } finally {
    loading.value = false;
  }
};

const updateStoredUser = (admin: AdminUser) => {
  try {
    const stored = JSON.parse(localStorage.getItem("user") || "{}");

    localStorage.setItem(
      "user",
      JSON.stringify({
        ...stored,
        id: admin.id,
        user_id: admin.user_id,
        name: admin.name,
        email: admin.email,
        role: admin.role,
      })
    );
  } catch {
    // Ignore invalid localStorage data.
  }
};

const startEdit = () => {
  if (!user.value) return;

  form.name = user.value.name;
  form.email = user.value.email;

  error.value = "";
  success.value = "";
  editing.value = true;
};

const cancelEdit = () => {
  if (user.value) {
    form.name = user.value.name;
    form.email = user.value.email;
  }

  error.value = "";
  editing.value = false;
};

const saveProfile = async () => {
  error.value = "";
  success.value = "";

  if (!form.name.trim()) {
    error.value = "Name is required.";
    return;
  }

  if (!form.email.trim()) {
    error.value = "Email is required.";
    return;
  }

  saving.value = true;

  try {
    const { data } = await api.put("/admin/profile", {
      name: form.name.trim(),
      email: form.email.trim(),
    });

    user.value = data.user;

    form.name = data.user.name;
    form.email = data.user.email;

    updateStoredUser(data.user);

    editing.value = false;
    success.value = data.message || "Profile updated successfully.";

    setTimeout(() => {
      success.value = "";
    }, 3000);
  } catch (err: any) {
    const validation = err?.response?.data?.errors;

    if (validation?.email?.[0]) {
      error.value = validation.email[0];
    } else if (validation?.name?.[0]) {
      error.value = validation.name[0];
    } else {
      error.value =
        err?.response?.data?.message ||
        "Unable to update profile.";
    }
  } finally {
    saving.value = false;
  }
};

const changePassword = () => {
  router.push("/change-password");
};

const goBack = () => {
  router.back();
};

const formatDate = (date?: string | null) => {
  if (!date) return "Not available";

  const parsed = new Date(date);

  if (Number.isNaN(parsed.getTime())) {
    return date;
  }

  return parsed.toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
};

onMounted(loadProfile);
</script>

<template>
  <AdminLayout
    title="My Profile"
    subtitle="View and manage your administrator account."
  >
    <div class="profile-page">

      <!-- Loading -->
      <div v-if="loading" class="state-card">
        <RefreshCw :size="22" class="spin" />
        <p>Loading profile...</p>
      </div>

      <template v-else-if="user">

        <!-- Alerts -->
        <div v-if="error" class="alert error-alert">
          <X :size="17" />
          <span>{{ error }}</span>
        </div>

        <div v-if="success" class="alert success-alert">
          <Check :size="17" />
          <span>{{ success }}</span>
        </div>

        <!-- Profile Header -->
        <section class="profile-header panel">
          <div class="profile-main">
            <div class="large-avatar">
              {{ initials }}
            </div>

            <div class="profile-heading">
              <h2>{{ user.name }}</h2>

              <p>
                {{ user.user_id || "Administrator Account" }}
              </p>

              <div class="profile-badges">
                <span class="role-badge">
                  <ShieldCheck :size="13" />
                  {{ displayRole }}
                </span>

                <span
                  class="status-badge"
                  :class="{ inactive: user.status !== 'Active' }"
                >
                  {{ user.status }}
                </span>
              </div>
            </div>
          </div>

          <div class="header-actions">
            <button
              v-if="!editing"
              class="btn-primary"
              @click="startEdit"
            >
              <Edit2 :size="15" />
              Edit Profile
            </button>

            <template v-else>
              <button
                class="btn-secondary"
                @click="cancelEdit"
                :disabled="saving"
              >
                <X :size="15" />
                Cancel
              </button>

              <button
                class="btn-primary"
                @click="saveProfile"
                :disabled="saving"
              >
                <Check :size="15" />
                {{ saving ? "Saving..." : "Save Changes" }}
              </button>
            </template>
          </div>
        </section>

        <!-- Personal Information -->
        <section class="panel">
          <div class="section-heading">
            <div class="section-icon">
              <User :size="18" />
            </div>

            <div>
              <h3>Personal Information</h3>
              <p>Your basic account information.</p>
            </div>
          </div>

          <div class="info-grid">

            <div class="field">
              <label>Administrator Name</label>

              <div v-if="!editing" class="readonly-value">
                <User :size="15" />
                {{ user.name }}
              </div>

              <input
                v-else
                v-model="form.name"
                type="text"
                placeholder="Enter your name"
              />
            </div>

            <div class="field">
              <label>Email Address</label>

              <div v-if="!editing" class="readonly-value">
                <Mail :size="15" />
                {{ user.email }}
              </div>

              <input
                v-else
                v-model="form.email"
                type="email"
                placeholder="Enter your email"
              />
            </div>

            <div class="field">
              <label>Admin ID</label>

              <div class="readonly-value locked">
                <UserCog :size="15" />
                {{ user.user_id || "Not available" }}
              </div>
            </div>

            <div class="field">
              <label>Role</label>

              <div class="readonly-value locked">
                <ShieldCheck :size="15" />
                {{ displayRole }}
              </div>
            </div>

          </div>
        </section>

        <!-- Account Information -->
        <section class="panel">
          <div class="section-heading">
            <div class="section-icon">
              <ShieldCheck :size="18" />
            </div>

            <div>
              <h3>Account Information</h3>
              <p>Administrator account details and security.</p>
            </div>
          </div>

          <div class="account-grid">

            <div class="account-item">
              <span>Account Status</span>

              <strong
                :class="{
                  active: user.status === 'Active',
                  inactive: user.status !== 'Active',
                }"
              >
                {{ user.status }}
              </strong>
            </div>

            <div class="account-item">
              <span>Account Role</span>
              <strong>{{ displayRole }}</strong>
            </div>

            <div class="account-item">
              <span>Account Created</span>
              <strong>{{ formatDate(user.created_at) }}</strong>
            </div>

            <div class="account-item">
              <span>Registered Email</span>
              <strong>{{ user.email }}</strong>
            </div>

          </div>
        </section>

        <!-- Security -->
        <section class="security-card">
          <div class="security-icon">
            <Lock :size="20" />
          </div>

          <div class="security-content">
            <h3>Password & Security</h3>
            <p>
              Keep your administrator account secure by using a strong
              password and changing it regularly.
            </p>
          </div>

          <button
            class="btn-secondary security-button"
            @click="changePassword"
          >
            <Lock :size="15" />
            Change Password
          </button>
        </section>

        <!-- Back -->
        <button class="back-button" @click="goBack">
          <ArrowLeft :size="16" />
          Back
        </button>

      </template>

      <!-- Error State -->
      <div v-else class="state-card error-state">
        <X :size="22" />
        <p>{{ error || "Unable to load profile." }}</p>

        <button class="btn-primary" @click="loadProfile">
          <RefreshCw :size="15" />
          Try Again
        </button>
      </div>

    </div>
  </AdminLayout>
</template>

<style scoped>
.profile-page {
  max-width: 1100px;
  margin: 0 auto;
}

.panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 3px 12px rgba(15, 23, 42, 0.04);
  margin-bottom: 18px;
}

.profile-header {
  padding: 22px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
}

.profile-main {
  display: flex;
  align-items: center;
  gap: 16px;
}

.large-avatar {
  width: 76px;
  height: 76px;
  border-radius: 50%;
  background: #123b63;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  font-weight: 750;
  flex-shrink: 0;
}

.profile-heading h2 {
  margin: 0;
  color: #0f172a;
  font-size: 21px;
  font-weight: 750;
}

.profile-heading p {
  margin: 5px 0 9px;
  color: #64748b;
  font-size: 12px;
}

.profile-badges {
  display: flex;
  gap: 7px;
  flex-wrap: wrap;
}

.role-badge,
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 9px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 650;
}

.role-badge {
  background: #eff6ff;
  color: #1d4ed8;
}

.status-badge {
  background: #ecfdf5;
  color: #15803d;
}

.status-badge.inactive {
  background: #fef2f2;
  color: #dc2626;
}

.header-actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}

.btn-primary,
.btn-secondary {
  border: none;
  border-radius: 8px;
  padding: 9px 13px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  cursor: pointer;
  font-size: 11px;
  font-weight: 650;
  transition: 0.2s ease;
}

.btn-primary {
  background: #123b63;
  color: #fff;
}

.btn-primary:hover {
  background: #0e3153;
}

.btn-secondary {
  background: #f1f5f9;
  color: #334155;
  border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
  background: #e2e8f0;
}

.btn-primary:disabled,
.btn-secondary:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.section-heading {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 18px 20px;
  border-bottom: 1px solid #e2e8f0;
}

.section-icon {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  background: #eff6ff;
  color: #123b63;
  display: flex;
  align-items: center;
  justify-content: center;
}

.section-heading h3 {
  margin: 0;
  color: #0f172a;
  font-size: 14px;
  font-weight: 750;
}

.section-heading p {
  margin: 3px 0 0;
  color: #64748b;
  font-size: 10px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 18px;
  padding: 20px;
}

.field label {
  display: block;
  margin-bottom: 7px;
  color: #475569;
  font-size: 10px;
  font-weight: 700;
}

.field input {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 10px 11px;
  font-size: 11px;
  color: #0f172a;
  outline: none;
  background: #fff;
}

.field input:focus {
  border-color: #123b63;
  box-shadow: 0 0 0 3px rgba(18, 59, 99, 0.08);
}

.readonly-value {
  min-height: 36px;
  box-sizing: border-box;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  padding: 9px 11px;
  display: flex;
  align-items: center;
  gap: 7px;
  color: #334155;
  font-size: 11px;
}

.readonly-value svg {
  color: #64748b;
  flex-shrink: 0;
}

.readonly-value.locked {
  color: #64748b;
}

.account-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
}

.account-item {
  padding: 17px 20px;
  border-bottom: 1px solid #e2e8f0;
}

.account-item:nth-child(odd) {
  border-right: 1px solid #e2e8f0;
}

.account-item:nth-last-child(-n + 2) {
  border-bottom: none;
}

.account-item span {
  display: block;
  color: #64748b;
  font-size: 10px;
  margin-bottom: 5px;
}

.account-item strong {
  color: #334155;
  font-size: 12px;
}

.account-item strong.active {
  color: #15803d;
}

.account-item strong.inactive {
  color: #dc2626;
}

.security-card {
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 18px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  margin-bottom: 18px;
}

.security-icon {
  width: 40px;
  height: 40px;
  border-radius: 9px;
  background: #e0ecf8;
  color: #123b63;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.security-content {
  flex: 1;
}

.security-content h3 {
  margin: 0 0 4px;
  font-size: 13px;
  color: #0f172a;
}

.security-content p {
  margin: 0;
  font-size: 10px;
  line-height: 1.6;
  color: #64748b;
}

.security-button {
  flex-shrink: 0;
}

.back-button {
  border: none;
  background: transparent;
  color: #64748b;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  cursor: pointer;
  padding: 7px 2px;
}

.back-button:hover {
  color: #123b63;
}

.alert {
  border-radius: 9px;
  padding: 10px 12px;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 15px;
  font-size: 11px;
}

.error-alert {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #b91c1c;
}

.success-alert {
  background: #ecfdf5;
  border: 1px solid #bbf7d0;
  color: #15803d;
}

.state-card {
  min-height: 260px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: #64748b;
  font-size: 12px;
}

.state-card p {
  margin: 0;
}

.error-state {
  color: #dc2626;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 700px) {
  .profile-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .header-actions {
    width: 100%;
  }

  .header-actions button {
    flex: 1;
  }

  .info-grid {
    grid-template-columns: 1fr;
    gap: 14px;
  }

  .account-grid {
    grid-template-columns: 1fr;
  }

  .account-item,
  .account-item:nth-child(odd),
  .account-item:nth-last-child(-n + 2) {
    border-right: none;
    border-bottom: 1px solid #e2e8f0;
  }

  .account-item:last-child {
    border-bottom: none;
  }

  .security-card {
    align-items: flex-start;
    flex-wrap: wrap;
  }

  .security-content {
    min-width: calc(100% - 55px);
  }

  .security-button {
    width: 100%;
  }
}

@media (max-width: 450px) {
  .profile-header {
    padding: 16px;
  }

  .profile-main {
    align-items: flex-start;
  }

  .large-avatar {
    width: 62px;
    height: 62px;
    font-size: 20px;
  }

  .profile-heading h2 {
    font-size: 17px;
  }

  .section-heading,
  .info-grid {
    padding: 15px;
  }
}
</style>