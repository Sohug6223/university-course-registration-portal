<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import universityLogo from "@/assets/logo/versitylogo.png";

const router = useRouter();

const role = ref<"student" | "admin">("student");
const userId = ref("");
const password = ref("");
const rememberMe = ref(false);
const showPassword = ref(false);
const isLoading = ref(false);
const errorMessage = ref("");

onMounted(() => {
  rememberMe.value =
    localStorage.getItem("remember_me") === "true";
});

const handleLogin = async () => {
  errorMessage.value = "";

  if (!userId.value || !password.value) {
    errorMessage.value =
      "Please enter your User ID and password.";
    return;
  }

  isLoading.value = true;

  try {
    const response = await fetch(
      "http://127.0.0.1:8000/api/login",
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          user_id: userId.value,
          password: password.value,
          role: role.value,
        }),
      }
    );

    const data = await response.json();

    if (!response.ok) {
      throw new Error(
        data.message || "Invalid credentials."
      );
    }

    /*  SAVE AUTH  */

    if (rememberMe.value) {
      // Persistent login
      localStorage.setItem(
        "auth_token",
        data.token
      );

      localStorage.setItem(
        "user",
        JSON.stringify(data.user)
      );

      localStorage.setItem(
        "remember_me",
        "true"
      );

      // Remove old session login
      sessionStorage.removeItem("auth_token");
      sessionStorage.removeItem("user");
    } else {
      // Session-only login
      sessionStorage.setItem(
        "auth_token",
        data.token
      );

      sessionStorage.setItem(
        "user",
        JSON.stringify(data.user)
      );

      // Remove old persistent login
      localStorage.removeItem("auth_token");
      localStorage.removeItem("user");
      localStorage.removeItem("remember_me");
    }

    console.log("Login successful:", data);

    /*  REDIRECT  */

    if (data.user.role === "student") {
      await router.push("/student");
    } else if (data.user.role === "admin") {
      await router.push("/admin");
    } else {
      throw new Error("Invalid user role.");
    }

  } catch (error) {
    if (error instanceof Error) {
      errorMessage.value = error.message;
    } else {
      errorMessage.value =
        "Something went wrong. Please try again.";
    }
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <div class="portal-wrapper">

    <!-- Background Overlay -->
    <div class="bg-overlay"></div>

    <!-- Top Left Header -->
    <header class="top-header">
      <div class="logo-box">
        <img :src="universityLogo" alt="University Logo" class="brand-logo" />
      </div>

      <div class="brand-info">
        <h1>City University</h1>
      </div>
    </header>

    <!-- Centered Card Container -->
    <main class="center-container">
      <div class="glass-card">

        <!-- Role Switcher Tabs -->
        <div class="role-tabs">
          <button type="button" :class="{ active: role === 'student' }" @click="role = 'student'">
            Student Portal
          </button>

          <button type="button" :class="{ active: role === 'admin' }" @click="role = 'admin'">
            Admin / Faculty
          </button>
        </div>

        <div class="form-header">
          <h2>Welcome Back</h2>

          <p>
            Sign in to access your
            {{ role === "student" ? "student" : "admin" }}
            account
          </p>
        </div>

        <!-- Error Alert -->
        <div v-if="errorMessage" class="error-alert">
          {{ errorMessage }}
        </div>

        <form @submit.prevent="handleLogin">

          <!-- User ID -->
          <div class="form-group">
            <label for="userId">
              {{
                role === "student"
                  ? "Student ID"
                  : "Admin / Faculty"
              }}
            </label>

            <input id="userId" v-model="userId" type="text" :placeholder="role === 'student'
              ? 'e.g. 0272320005101147'
              : 'Enter Admin ID'
              " required />
          </div>

          <!-- Password -->
          <div class="form-group">

            <div class="password-label">
              <label for="password">
                Password
              </label>

              <a href="#" @click.prevent="
                router.push('/forgot-password')
                ">
                Forgot password?
              </a>
            </div>

            <div class="password-input">
              <input id="password" v-model="password" :type="showPassword
                ? 'text'
                : 'password'
                " placeholder="••••••••" required />

              <button type="button" @click="
                showPassword = !showPassword
                ">
                {{
                  showPassword
                    ? "Hide"
                    : "Show"
                }}
              </button>
            </div>

          </div>

          <!-- Options -->
          <div class="form-options">
            <label class="remember">
              <input v-model="rememberMe" type="checkbox" />

              <span>Remember me</span>
            </label>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="login-button" :disabled="isLoading">
            <span v-if="isLoading">
              Signing in...
            </span>

            <span v-else>
              Sign In
            </span>
          </button>

        </form>

        <!-- Admin Note -->
        <div class="register-prompt">
          <p v-if="role === 'student'" class="student-note">
            Student accounts are created by the
            university administration.
          </p>

          <p v-else class="admin-note">
            Unauthorized admin access is strictly monitored.
          </p>
        </div>

      </div>
    </main>

    <!-- Bottom Footer -->
    <footer class="bottom-footer">
      <span>
        © 2026 City University. All rights reserved.
      </span>

      <span class="dot">•</span>

      <span>
        Support: helpdesk@cityuniv.edu
      </span>
    </footer>

  </div>
</template>

<style scoped>
.portal-wrapper {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  background: url('https://images.unsplash.com/photo-1541829070764-84a7d30dd3f3?q=80&w=1600') center/cover no-repeat;
  overflow: hidden;
  box-sizing: border-box;
}

/* Background Overlay */
.bg-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg,
      rgba(10, 25, 47, 0.85) 0%,
      rgba(18, 59, 99, 0.75) 100%);
  z-index: 1;
}

/* Header */
.top-header {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 24px 40px;
}

.logo-box {
  width: 50px;
  height: 50px;
  background: #ffffff;
  padding: 6px;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.brand-logo {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.brand-info h1 {
  font-size: 20px;
  font-weight: 800;
  color: #ffffff;
  margin: 0;
  letter-spacing: -0.3px;
}

.brand-info p {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.8);
  margin: 2px 0 0 0;
}

/* Center Form Area */
.center-container {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 20px;
  margin-top: -24px;
}

.glass-card {
  width: 100%;
  max-width: 380px;
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(16px);
  padding: 28px 28px 24px 28px;
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
  border: 1px solid rgba(255, 255, 255, 0.4);
}

/* Role Switcher Tabs */
.role-tabs {
  display: flex;
  background: #e2e8f0;
  padding: 4px;
  border-radius: 10px;
  margin-bottom: 20px;
}

.role-tabs button {
  flex: 1;
  padding: 8px 0;
  border: none;
  background: transparent;
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.role-tabs button.active {
  background: #ffffff;
  color: #123b63;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.form-header {
  text-align: center;
  margin-bottom: 20px;
}

.form-header h2 {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 4px 0;
}

.form-header p {
  font-size: 12px;
  color: #64748b;
  margin: 0;
}

.error-alert {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 8px;
  border-radius: 8px;
  font-size: 12px;
  margin-bottom: 14px;
  text-align: center;
}

.form-group {
  margin-bottom: 14px;
}

.form-group label,
.password-label label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 5px;
}

input[type="text"],
.password-input input {
  width: 100%;
  height: 40px;
  padding: 0 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13px;
  color: #0f172a;
  outline: none;
  box-sizing: border-box;
  background: #ffffff;
  transition: all 0.2s;
}

input:focus {
  border-color: #123b63;
  box-shadow: 0 0 0 3px rgba(18, 59, 99, 0.12);
}

.password-label {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.password-label a {
  font-size: 11px;
  color: #123b63;
  text-decoration: none;
  font-weight: 600;
}

.password-label a:hover {
  text-decoration: underline;
}

.password-input {
  position: relative;
}

.password-input button {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: transparent;
  color: #64748b;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
}

.form-options {
  margin-bottom: 16px;
}

.remember {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #475569;
  cursor: pointer;
}

.remember input {
  width: 14px;
  height: 14px;
  accent-color: #123b63;
}

.login-button {
  width: 100%;
  height: 40px;
  background: #123b63;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.login-button:hover {
  background: #0d2f50;
}

.register-prompt {
  margin-top: 16px;
  padding-top: 14px;
  border-top: 1px solid #e2e8f0;
  text-align: center;
  font-size: 12px;
  color: #64748b;
}

.student-note {
  font-size: 11px;
  color: #94a3b8;
  margin: 0;
}

.admin-note {
  font-size: 11px;
  color: #94a3b8;
  margin: 0;
}

/* Footer */
.bottom-footer {
  position: relative;
  z-index: 2;
  text-align: center;
  padding: 18px 20px;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.7);
}

.dot {
  margin: 0 8px;
}

/* Mobile Layout */
@media (max-width: 640px) {
  .top-header {
    padding: 16px;
    justify-content: center;
    text-align: center;
    flex-direction: column;
    gap: 8px;
  }

  .center-container {
    margin-top: 0;
  }

  .glass-card {
    padding: 20px 16px;
  }
}
</style>