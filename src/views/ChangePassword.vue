
<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import {
  ArrowLeft,
  CheckCircle2,
  Eye,
  EyeOff,
  KeyRound,
  LockKeyhole,
  ShieldCheck,
  XCircle,
} from "lucide-vue-next";

import api from "@/services/api";

const router = useRouter();

/*  STATE  */

const currentPassword = ref("");
const newPassword = ref("");
const confirmPassword = ref("");

const showCurrent = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);

const loading = ref(false);
const successMessage = ref("");
const errorMessage = ref("");

/*  BACK  */

const goBack = () => {
  router.back();
};

/*  VALIDATION  */

const validateForm = () => {
  errorMessage.value = "";

  if (
    !currentPassword.value ||
    !newPassword.value ||
    !confirmPassword.value
  ) {
    errorMessage.value = "Please fill in all password fields.";
    return false;
  }

  if (newPassword.value.length < 8) {
    errorMessage.value =
      "New password must be at least 8 characters long.";
    return false;
  }

  if (newPassword.value === currentPassword.value) {
    errorMessage.value =
      "New password must be different from your current password.";
    return false;
  }

  if (newPassword.value !== confirmPassword.value) {
    errorMessage.value =
      "New password and confirmation password do not match.";
    return false;
  }

  return true;
};

/*  CHANGE PASSWORD  */

const changePassword = async () => {
  successMessage.value = "";

  if (!validateForm()) return;

  loading.value = true;

  try {
    const response = await api.put("/change-password", {
      current_password: currentPassword.value,
      new_password: newPassword.value,
      new_password_confirmation: confirmPassword.value,
    });

    successMessage.value =
      response.data?.message ||
      "Password changed successfully.";

    currentPassword.value = "";
    newPassword.value = "";
    confirmPassword.value = "";
  } catch (error: any) {
    const errors = error?.response?.data?.errors;

    if (errors) {
      const firstError = Object.values(errors)
        .flat()
        .find((message) => typeof message === "string");

      errorMessage.value =
        firstError ||
        error?.response?.data?.message ||
        "Unable to change password.";
    } else {
      errorMessage.value =
        error?.response?.data?.message ||
        "Unable to change password. Please try again.";
    }
  } finally {
    loading.value = false;
  }
};

/*  CLEAR  */

const clearForm = () => {
  currentPassword.value = "";
  newPassword.value = "";
  confirmPassword.value = "";

  successMessage.value = "";
  errorMessage.value = "";
};
</script>

<template>
  <div class="change-password-page">

    <!-- HEADER -->
    <header class="page-header">
      <div class="header-inner">

        <button
          type="button"
          class="back-btn"
          @click="goBack"
        >
          <ArrowLeft :size="18" />
          <span>Back</span>
        </button>

        <div class="header-title">
          <div class="header-icon">
            <KeyRound :size="22" />
          </div>

          <div>
            <h1>Change Password</h1>
            <p>Update your account password securely.</p>
          </div>
        </div>

      </div>
    </header>

    <!-- MAIN -->
    <main class="page-content">

      <div class="password-layout">

        <!-- FORM CARD -->
        <section class="password-card">

          <div class="card-heading">
            <div class="heading-icon">
              <LockKeyhole :size="22" />
            </div>

            <div>
              <h2>Update Password</h2>
              <p>
                Enter your current password and choose a new
                password for your account.
              </p>
            </div>
          </div>

          <!-- SUCCESS -->
          <div
            v-if="successMessage"
            class="alert success-alert"
          >
            <CheckCircle2 :size="20" />
            <span>{{ successMessage }}</span>
          </div>

          <!-- ERROR -->
          <div
            v-if="errorMessage"
            class="alert error-alert"
          >
            <XCircle :size="20" />
            <span>{{ errorMessage }}</span>
          </div>

          <form @submit.prevent="changePassword">

            <!-- CURRENT PASSWORD -->
            <div class="form-group">
              <label for="current-password">
                Current Password
              </label>

              <div class="password-input">
                <LockKeyhole :size="18" />

                <input
                  id="current-password"
                  v-model="currentPassword"
                  :type="showCurrent ? 'text' : 'password'"
                  placeholder="Enter current password"
                  autocomplete="current-password"
                />

                <button
                  type="button"
                  class="toggle-password"
                  @click="showCurrent = !showCurrent"
                  :aria-label="
                    showCurrent
                      ? 'Hide current password'
                      : 'Show current password'
                  "
                >
                  <EyeOff
                    v-if="showCurrent"
                    :size="18"
                  />
                  <Eye
                    v-else
                    :size="18"
                  />
                </button>
              </div>
            </div>

            <!-- NEW PASSWORD -->
            <div class="form-group">
              <label for="new-password">
                New Password
              </label>

              <div class="password-input">
                <KeyRound :size="18" />

                <input
                  id="new-password"
                  v-model="newPassword"
                  :type="showNew ? 'text' : 'password'"
                  placeholder="Enter new password"
                  autocomplete="new-password"
                />

                <button
                  type="button"
                  class="toggle-password"
                  @click="showNew = !showNew"
                  :aria-label="
                    showNew
                      ? 'Hide new password'
                      : 'Show new password'
                  "
                >
                  <EyeOff
                    v-if="showNew"
                    :size="18"
                  />
                  <Eye
                    v-else
                    :size="18"
                  />
                </button>
              </div>

              <span class="field-help">
                Minimum 8 characters.
              </span>
            </div>

            <!-- CONFIRM PASSWORD -->
            <div class="form-group">
              <label for="confirm-password">
                Confirm New Password
              </label>

              <div class="password-input">
                <ShieldCheck :size="18" />

                <input
                  id="confirm-password"
                  v-model="confirmPassword"
                  :type="
                    showConfirm
                      ? 'text'
                      : 'password'
                  "
                  placeholder="Confirm new password"
                  autocomplete="new-password"
                />

                <button
                  type="button"
                  class="toggle-password"
                  @click="
                    showConfirm = !showConfirm
                  "
                  :aria-label="
                    showConfirm
                      ? 'Hide confirmation password'
                      : 'Show confirmation password'
                  "
                >
                  <EyeOff
                    v-if="showConfirm"
                    :size="18"
                  />
                  <Eye
                    v-else
                    :size="18"
                  />
                </button>
              </div>
            </div>

            <!-- ACTIONS -->
            <div class="form-actions">

              <button
                type="button"
                class="btn-secondary"
                @click="clearForm"
                :disabled="loading"
              >
                Clear
              </button>

              <button
                type="submit"
                class="btn-primary"
                :disabled="loading"
              >
                <span
                  v-if="loading"
                  class="spinner"
                ></span>

                <LockKeyhole
                  v-else
                  :size="18"
                />

                {{
                  loading
                    ? "Changing..."
                    : "Change Password"
                }}
              </button>

            </div>

          </form>
        </section>

        <!-- SECURITY TIPS -->
        <aside class="security-card">

          <div class="security-icon">
            <ShieldCheck :size="25" />
          </div>

          <h2>Password Security</h2>

          <p class="security-intro">
            Keep your account secure by following these
            password guidelines.
          </p>

          <ul class="security-list">
            <li>
              <span class="check">✓</span>
              <span>Use at least 8 characters.</span>
            </li>

            <li>
              <span class="check">✓</span>
              <span>
                Use a combination of letters, numbers and
                symbols.
              </span>
            </li>

            <li>
              <span class="check">✓</span>
              <span>
                Do not reuse your current password.
              </span>
            </li>

            <li>
              <span class="check">✓</span>
              <span>
                Never share your password with anyone.
              </span>
            </li>
          </ul>

          <div class="security-note">
            <LockKeyhole :size="17" />

            <span>
              Your password is securely encrypted and cannot
              be viewed by administrators.
            </span>
          </div>

        </aside>

      </div>

    </main>
  </div>
</template>

<style scoped>
/*  PAGE  */

.change-password-page {
  min-height: 100vh;
  background: #f5f7fb;
  color: #172033;
}

/*  HEADER  */

.page-header {
  background: #ffffff;
  border-bottom: 1px solid #e5e9f0;
  position: sticky;
  top: 0;
  z-index: 20;
}

.header-inner {
  max-width: 1180px;
  margin: 0 auto;
  min-height: 76px;
  padding: 14px 24px;
  display: flex;
  align-items: center;
  gap: 28px;
}

.back-btn {
  border: 1px solid #d9dee8;
  background: #ffffff;
  color: #243b64;
  height: 40px;
  padding: 0 14px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: 0.2s;
  flex-shrink: 0;
}

.back-btn:hover {
  background: #f1f5fa;
  border-color: #c6cedb;
}

.header-title {
  display: flex;
  align-items: center;
  gap: 13px;
}

.header-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: #eaf0fa;
  color: #163b70;
  display: flex;
  align-items: center;
  justify-content: center;
}

.header-title h1 {
  margin: 0;
  font-size: 21px;
  font-weight: 700;
  color: #172b4d;
}

.header-title p {
  margin: 3px 0 0;
  color: #718096;
  font-size: 13px;
}

/*  CONTENT  */

.page-content {
  max-width: 1180px;
  margin: 0 auto;
  padding: 34px 24px 50px;
}

.password-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.55fr) minmax(280px, 0.75fr);
  gap: 24px;
  align-items: start;
}

/*  CARDS  */

.password-card,
.security-card {
  background: #ffffff;
  border: 1px solid #e3e8ef;
  border-radius: 12px;
  box-shadow: 0 3px 14px rgba(25, 45, 75, 0.05);
}

.password-card {
  padding: 28px;
}

.security-card {
  padding: 25px;
}

/*  CARD HEADING  */

.card-heading {
  display: flex;
  gap: 13px;
  align-items: flex-start;
  padding-bottom: 23px;
  margin-bottom: 22px;
  border-bottom: 1px solid #edf0f4;
}

.heading-icon {
  width: 42px;
  height: 42px;
  border-radius: 9px;
  background: #eef3fa;
  color: #173f73;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.card-heading h2,
.security-card h2 {
  margin: 0;
  color: #172b4d;
  font-size: 18px;
  font-weight: 700;
}

.card-heading p {
  margin: 5px 0 0;
  color: #718096;
  font-size: 13px;
  line-height: 1.55;
}

/*  ALERT  */

.alert {
  border-radius: 8px;
  padding: 12px 14px;
  display: flex;
  align-items: center;
  gap: 9px;
  font-size: 13px;
  font-weight: 500;
  margin-bottom: 20px;
}

.success-alert {
  background: #edf8f1;
  color: #207044;
  border: 1px solid #ccebd7;
}

.error-alert {
  background: #fff1f1;
  color: #b42318;
  border: 1px solid #f2cccc;
}

/*  FORM  */

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #344054;
  font-size: 13px;
  font-weight: 600;
}

.password-input {
  height: 46px;
  border: 1px solid #d5dce6;
  border-radius: 8px;
  background: #ffffff;
  display: flex;
  align-items: center;
  padding: 0 12px;
  gap: 10px;
  color: #667085;
  transition: 0.2s;
}

.password-input:focus-within {
  border-color: #315b91;
  box-shadow: 0 0 0 3px rgba(49, 91, 145, 0.1);
}

.password-input input {
  flex: 1;
  min-width: 0;
  border: none;
  outline: none;
  background: transparent;
  color: #172033;
  font-size: 14px;
}

.password-input input::placeholder {
  color: #98a2b3;
}

.toggle-password {
  border: none;
  background: transparent;
  color: #667085;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4px;
  cursor: pointer;
}

.toggle-password:hover {
  color: #173f73;
}

.field-help {
  display: block;
  margin-top: 6px;
  color: #98a2b3;
  font-size: 12px;
}

/*  ACTIONS  */

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 28px;
  padding-top: 22px;
  border-top: 1px solid #edf0f4;
}

.btn-primary,
.btn-secondary {
  min-height: 42px;
  padding: 0 17px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  transition: 0.2s;
}

.btn-primary {
  border: 1px solid #173f73;
  background: #173f73;
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
  background: #12345f;
  border-color: #12345f;
}

.btn-secondary {
  border: 1px solid #d5dce6;
  background: #ffffff;
  color: #344054;
}

.btn-secondary:hover:not(:disabled) {
  background: #f7f8fa;
}

.btn-primary:disabled,
.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/*  SPINNER  */

.spinner {
  width: 15px;
  height: 15px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/*  SECURITY  */

.security-icon {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  background: #edf4ee;
  color: #277443;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.security-intro {
  margin: 8px 0 20px;
  color: #667085;
  font-size: 13px;
  line-height: 1.6;
}

.security-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 13px;
}

.security-list li {
  display: flex;
  align-items: flex-start;
  gap: 9px;
  color: #475467;
  font-size: 13px;
  line-height: 1.5;
}

.check {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #edf8f1;
  color: #24733f;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  flex-shrink: 0;
  margin-top: 1px;
}

.security-note {
  margin-top: 22px;
  padding: 13px;
  border-radius: 8px;
  background: #f6f8fb;
  color: #667085;
  display: flex;
  align-items: flex-start;
  gap: 9px;
  font-size: 12px;
  line-height: 1.55;
}

/*  RESPONSIVE  */

@media (max-width: 850px) {
  .password-layout {
    grid-template-columns: 1fr;
  }

  .security-card {
    order: 2;
  }
}

@media (max-width: 600px) {
  .header-inner {
    padding: 12px 16px;
    gap: 14px;
  }

  .back-btn span {
    display: none;
  }

  .back-btn {
    width: 40px;
    padding: 0;
    justify-content: center;
  }

  .header-icon {
    width: 40px;
    height: 40px;
  }

  .header-title h1 {
    font-size: 18px;
  }

  .header-title p {
    display: none;
  }

  .page-content {
    padding: 20px 14px 35px;
  }

  .password-card,
  .security-card {
    padding: 20px;
  }

  .form-actions {
    flex-direction: column-reverse;
  }

  .btn-primary,
  .btn-secondary {
    width: 100%;
  }
}
</style>
