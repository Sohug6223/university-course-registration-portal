<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import universityLogo from "@/assets/logo/versitylogo.png";

const router = useRouter();

const step = ref<"email" | "otp" | "password">("email");

const email = ref("");
const otp = ref("");
const password = ref("");
const passwordConfirmation = ref("");

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const isLoading = ref(false);
const errorMessage = ref("");
const successMessage = ref("");

const sendOtp = async () => {
  errorMessage.value = "";
  successMessage.value = "";
  isLoading.value = true;

  try {
    const response = await fetch(
      "http://127.0.0.1:8000/api/forgot-password",
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          email: email.value,
        }),
      }
    );

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || "Unable to send OTP.");
    }

    successMessage.value = "OTP has been sent to your email.";
    step.value = "otp";
  } catch (error) {
    errorMessage.value =
      error instanceof Error ? error.message : "Something went wrong.";
  } finally {
    isLoading.value = false;
  }
};

const verifyOtp = async () => {
  errorMessage.value = "";
  successMessage.value = "";
  isLoading.value = true;

  try {
    const response = await fetch(
      "http://127.0.0.1:8000/api/verify-otp",
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          email: email.value,
          otp: otp.value,
        }),
      }
    );

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || "Invalid OTP.");
    }

    successMessage.value = "OTP verified successfully.";
    step.value = "password";
  } catch (error) {
    errorMessage.value =
      error instanceof Error ? error.message : "Something went wrong.";
  } finally {
    isLoading.value = false;
  }
};

const resetPassword = async () => {
  errorMessage.value = "";
  successMessage.value = "";

  if (password.value !== passwordConfirmation.value) {
    errorMessage.value = "Passwords do not match.";
    return;
  }

  isLoading.value = true;

  try {
    const response = await fetch(
      "http://127.0.0.1:8000/api/reset-password",
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          email: email.value,
          password: password.value,
          password_confirmation: passwordConfirmation.value,
        }),
      }
    );

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || "Unable to reset password.");
    }

    successMessage.value = "Password reset successfully.";

    setTimeout(() => {
      router.push("/");
    }, 1500);
  } catch (error) {
    errorMessage.value =
      error instanceof Error ? error.message : "Something went wrong.";
  } finally {
    isLoading.value = false;
  }
};

const goBackToLogin = () => {
  router.push("/");
};
</script>

<template>
  <div class="portal-wrapper">
    <div class="bg-overlay"></div>

    <!-- Header -->
    <header class="top-header">
      <div class="logo-box">
        <img
          :src="universityLogo"
          alt="University Logo"
          class="brand-logo"
        />
      </div>

      <div class="brand-info">
        <h1>City University</h1>
      </div>
    </header>

    <!-- Main -->
    <main class="center-container">
      <div class="glass-card">

        <!-- Email Step -->
        <template v-if="step === 'email'">
          <div class="form-header">
            <h2>Forgot Password?</h2>
            <p>
              Enter your registered email address and we'll send you an OTP.
            </p>
          </div>

          <form @submit.prevent="sendOtp">
            <div class="form-group">
              <label for="email">Email Address</label>

              <input
                id="email"
                v-model="email"
                type="email"
                placeholder="Enter your email address"
                required
              />
            </div>

            <div v-if="errorMessage" class="error-alert">
              {{ errorMessage }}
            </div>

            <div v-if="successMessage" class="success-alert">
              {{ successMessage }}
            </div>

            <button
              type="submit"
              class="login-button"
              :disabled="isLoading"
            >
              {{ isLoading ? "Sending OTP..." : "Send OTP" }}
            </button>
          </form>
        </template>

        <!-- OTP Step -->
        <template v-else-if="step === 'otp'">
          <div class="form-header">
            <h2>Verify OTP</h2>
            <p>
              Enter the 6-digit OTP sent to your email address.
            </p>
          </div>

          <form @submit.prevent="verifyOtp">
            <div class="form-group">
              <label for="otp">OTP</label>

              <input
                id="otp"
                v-model="otp"
                type="text"
                inputmode="numeric"
                maxlength="6"
                autocomplete="one-time-code"
                placeholder="Enter 6-digit OTP"
                required
              />
            </div>

            <div v-if="errorMessage" class="error-alert">
              {{ errorMessage }}
            </div>

            <div v-if="successMessage" class="success-alert">
              {{ successMessage }}
            </div>

            <button
              type="submit"
              class="login-button"
              :disabled="isLoading"
            >
              {{ isLoading ? "Verifying..." : "Verify OTP" }}
            </button>
          </form>

          <button
            type="button"
            class="back-button"
            @click="step = 'email'"
          >
            Change Email
          </button>
        </template>

        <!-- Password Step -->
         <template v-else>
          <div class="form-header">
            <h2>Create New Password</h2>
            <p>Enter your new password below.</p>
          </div>

          <form @submit.prevent="resetPassword">
            
            <div class="form-group">
              <label for="password">New Password</label>
              <div class="password-input">
                <input id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Enter new password"
                minlength="8"
                required
                />
                <button type="button"
                class="toggle-password"@click="showPassword = !showPassword">
                {{ showPassword ? "Hide" : "Show" }}
              </button>
            </div>
          </div>
          
          <div class="form-group">
            <label for="passwordConfirmation">Confirm Password</label>
            <div class="password-input">
              <input id="passwordConfirmation"v-model="passwordConfirmation"
              :type="showConfirmPassword ? 'text' : 'password'"
              placeholder="Confirm new password"
              minlength="8"
              required
              />
              <button type="button"
              class="toggle-password"
              @click="showConfirmPassword = !showConfirmPassword">
              {{ showConfirmPassword ? "Hide" : "Show" }}
            </button>
          </div>
        </div>
        
        <div v-if="errorMessage" class="error-alert">
          {{ errorMessage }}
        </div>
        
        <div v-if="successMessage" class="success-alert">
          {{ successMessage }}
        
        </div>
        
        <button type="submit"
        class="login-button" :disabled="isLoading">
        {{ isLoading ? "Updating..." : "Reset Password" }}
      </button>
    </form>
  </template>

        <!-- Back to Login -->
        <div class="register-prompt">
          <button
            type="button"
            class="login-link"
            @click="goBackToLogin"
          >
            ← Back to Login
          </button>
        </div>

      </div>
    </main>

    <!-- Footer -->
    <footer class="bottom-footer">
      <span>© 2026 City University. All rights reserved.</span>
      <span class="dot">•</span>
      <span>Support: helpdesk@cityuniversity.edu</span>
    </footer>
  </div>
</template>

<style scoped>
.portal-wrapper {
  position: fixed;
  inset: 0;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  font-family: "Inter", system-ui, -apple-system, sans-serif;
  background: url("https://images.unsplash.com/photo-1541829070764-84a7d30dd3f3?q=80&w=1600")
    center/cover no-repeat;
  overflow: hidden;
  box-sizing: border-box;
}

.bg-overlay {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(
    135deg,
    rgba(10, 25, 47, 0.85) 0%,
    rgba(18, 59, 99, 0.75) 100%
  );
  z-index: 1;
}

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
  background: #fff;
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
  color: #fff;
  margin: 0;
}

.center-container {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 20px;
}

.glass-card {
  width: 100%;
  max-width: 380px;
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(16px);
  padding: 28px;
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
  box-sizing: border-box;
}

.form-header {
  text-align: center;
  margin-bottom: 22px;
}

.form-header h2 {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 6px;
}

.form-header p {
  font-size: 12px;
  line-height: 1.5;
  color: #64748b;
  margin: 0;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 6px;
}

input {
  width: 100%;
  height: 42px;
  padding: 0 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13px;
  color: #0f172a;
  outline: none;
  box-sizing: border-box;
  background: #fff;
}

input:focus {
  border-color: #123b63;
  box-shadow: 0 0 0 3px rgba(18, 59, 99, 0.12);
}

.password-input {
  position: relative;
}

.password-input input {
  padding-right: 50px;
}

.toggle-password {
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
  user-select: none;
}

.toggle-password:hover {
  color: #123b63;
}

.error-alert,
.success-alert {
  padding: 9px;
  border-radius: 8px;
  font-size: 12px;
  margin-bottom: 14px;
  text-align: center;
}

.error-alert {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
}

.success-alert {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #15803d;
}

.login-button {
  width: 100%;
  height: 42px;
  background: #123b63;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.login-button:hover {
  background: #0d2f50;
}

.login-button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.back-button {
  display: block;
  margin: 16px auto 0;
  border: none;
  background: transparent;
  color: #123b63;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.register-prompt {
  margin-top: 18px;
  padding-top: 14px;
  border-top: 1px solid #e2e8f0;
  text-align: center;
}

.login-link {
  border: none;
  background: transparent;
  color: #123b63;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

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

@media (max-width: 640px) {
  .top-header {
    padding: 16px;
    justify-content: center;
  }

  .glass-card {
    padding: 22px 18px;
  }
}
</style>