<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import {
  Award,
  BookOpen,
  CheckCircle2,
  Edit2,
  GraduationCap,
  Mail,
  Save,
  ShieldCheck,
  User,
  X,
  XCircle,
} from "lucide-vue-next";
import StudentLayout from "@/components/student/StudentLayout.vue";
import api from "@/services/api";

/*  TYPES  */

interface Student {
  id: number;
  student_id: string | null;
  name: string;
  email: string | null;

  department: string | null;
  batch: number | null;
  semester: string | null;
  section: string | null;

  role: string | null;
  status: string | null;
}

interface Academic {
  total_courses: number;
  passed_courses: number;
  failed_courses: number;
  total_credits: number;
  earned_credits: number;
  cgpa: number;
}

interface ProfileData {
  student: Student;
  academic: Academic;
}

/*  STATE  */

const data = ref<ProfileData | null>(null);

const loading = ref(true);
const saving = ref(false);
const error = ref("");
const success = ref("");

const editMode = ref(false);

const form = ref({
  name: "",
  email: "",
});

/*  COMPUTED  */

const student = computed(() => data.value?.student);

const academic = computed(() => data.value?.academic);

const initials = computed(() => {
  const name = student.value?.name || "Student";

  return name
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join("");
});

/*  LOAD  */

const loadProfile = async () => {
  loading.value = true;
  error.value = "";

  try {
    const response = await api.get<ProfileData>("/student/profile");

    data.value = response.data;

    form.value.name = response.data.student.name;
    form.value.email = response.data.student.email || "";
  } catch (err: any) {
    error.value =
      err?.response?.data?.message ||
      "Unable to load your profile.";
  } finally {
    loading.value = false;
  }
};

/*  EDIT  */

const startEdit = () => {
  if (!student.value) return;

  form.value.name = student.value.name;
  form.value.email = student.value.email || "";

  success.value = "";
  error.value = "";
  editMode.value = true;
};

const cancelEdit = () => {
  if (student.value) {
    form.value.name = student.value.name;
    form.value.email = student.value.email || "";
  }

  error.value = "";
  editMode.value = false;
};

/*  UPDATE  */

const updateProfile = async () => {
  error.value = "";
  success.value = "";

  if (!form.value.name.trim()) {
    error.value = "Name is required.";
    return;
  }

  if (!form.value.email.trim()) {
    error.value = "Email is required.";
    return;
  }

  saving.value = true;

  try {
    await api.put("/student/profile", {
      name: form.value.name.trim(),
      email: form.value.email.trim(),
    });

    success.value = "Profile updated successfully.";
    editMode.value = false;

    await loadProfile();

    setTimeout(() => {
      success.value = "";
    }, 3000);
  } catch (err: any) {
    error.value =
      err?.response?.data?.message ||
      err?.response?.data?.errors?.email?.[0] ||
      err?.response?.data?.errors?.name?.[0] ||
      "Unable to update profile.";
  } finally {
    saving.value = false;
  }
};

onMounted(loadProfile);
</script>

<template>
  <StudentLayout
    title="My Profile"
    subtitle="View and manage your student profile information."
  >
    <div class="profile-page">

      <!-- LOADING -->
      <div v-if="loading" class="state-card">
        <div class="spinner"></div>
        <p>Loading profile...</p>
      </div>

      <!-- ERROR -->
      <div v-else-if="error && !data" class="state-card error-state">
        <XCircle :size="34" />
        <h3>Unable to Load Profile</h3>
        <p>{{ error }}</p>

        <button class="btn-primary" @click="loadProfile">
          Try Again
        </button>
      </div>

      <template v-else-if="data">

        <!-- SUCCESS -->
        <div v-if="success" class="alert success-alert">
          <CheckCircle2 :size="18" />
          {{ success }}
        </div>

        <!-- ERROR -->
        <div v-if="error" class="alert error-alert">
          <XCircle :size="18" />
          {{ error }}
        </div>

        <!-- PROFILE HEADER -->
        <section class="profile-header">

          <div class="profile-avatar">
            {{ initials }}
          </div>

          <div class="profile-main-info">
            <h1>{{ student?.name }}</h1>

            <p class="student-id">
              Student ID: {{ student?.student_id || "Not Set" }}
            </p>

            <div class="header-meta">
              <span>
                <GraduationCap :size="16" />
                {{ student?.department || "Department Not Set" }}
              </span>

              <span>
                Batch {{ student?.batch ?? "N/A" }}
              </span>

              <span>
                Section {{ student?.section || "N/A" }}
              </span>

              <span>
                {{ student?.semester || "Semester Not Set" }}
              </span>
            </div>
          </div>

          <button
            v-if="!editMode"
            class="btn-edit"
            @click="startEdit"
          >
            <Edit2 :size="17" />
            Edit Profile
          </button>
        </section>

        <!-- PERSONAL + ACADEMIC -->
        <div class="info-grid">

          <!-- PERSONAL INFORMATION -->
          <section class="panel">

            <div class="panel-header">
              <div>
                <h2>Personal Information</h2>
                <p>Your basic account information.</p>
              </div>

              <User :size="21" />
            </div>

            <div class="info-list">

              <div class="info-row">
                <div class="info-label">
                  <User :size="17" />
                  <span>Name</span>
                </div>

                <div v-if="!editMode" class="info-value">
                  {{ student?.name }}
                </div>

                <input
                  v-else
                  v-model="form.name"
                  class="form-input"
                  type="text"
                  placeholder="Enter your name"
                />
              </div>

              <div class="info-row">
                <div class="info-label">
                  <Mail :size="17" />
                  <span>Email</span>
                </div>

                <div v-if="!editMode" class="info-value">
                  {{ student?.email || "Not Set" }}
                </div>

                <input
                  v-else
                  v-model="form.email"
                  class="form-input"
                  type="email"
                  placeholder="Enter your email"
                />
              </div>

              <div class="info-row">
                <div class="info-label">
                  <ShieldCheck :size="17" />
                  <span>Student ID</span>
                </div>

                <div class="info-value">
                  {{ student?.student_id || "Not Set" }}
                </div>
              </div>

            </div>

            <!-- EDIT BUTTONS -->
            <div v-if="editMode" class="edit-actions">

              <button
                class="btn-secondary"
                :disabled="saving"
                @click="cancelEdit"
              >
                <X :size="17" />
                Cancel
              </button>

              <button
                class="btn-primary"
                :disabled="saving"
                @click="updateProfile"
              >
                <Save :size="17" />

                {{ saving ? "Saving..." : "Save Changes" }}
              </button>

            </div>

          </section>

          <!-- ACADEMIC INFORMATION -->
          <section class="panel">

            <div class="panel-header">
              <div>
                <h2>Academic Information</h2>
                <p>Academic details managed by the university.</p>
              </div>

              <GraduationCap :size="21" />
            </div>

            <div class="academic-info-grid">

              <div class="academic-item">
                <span>Department</span>
                <strong>
                  {{ student?.department || "Not Set" }}
                </strong>
              </div>

              <div class="academic-item">
                <span>Batch</span>
                <strong>
                  {{ student?.batch ?? "Not Set" }}
                </strong>
              </div>

              <div class="academic-item">
                <span>Current Semester</span>
                <strong>
                  {{ student?.semester || "Not Set" }}
                </strong>
              </div>

              <div class="academic-item">
                <span>Section</span>
                <strong>
                  {{ student?.section || "Not Set" }}
                </strong>
              </div>

            </div>

          </section>

        </div>

        <!-- ACADEMIC OVERVIEW -->
        <section class="panel">

          <div class="panel-header">
            <div>
              <h2>Academic Overview</h2>
              <p>Your overall academic performance.</p>
            </div>

            <Award :size="21" />
          </div>

          <div class="stats-grid">

            <div class="stat-card cgpa-card">
              <div class="stat-icon">
                <Award :size="22" />
              </div>

              <div>
                <span>Current CGPA</span>
                <strong>
                  {{ academic?.cgpa?.toFixed(2) ?? "0.00" }}
                </strong>
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-icon">
                <BookOpen :size="22" />
              </div>

              <div>
                <span>Total Courses</span>
                <strong>
                  {{ academic?.total_courses ?? 0 }}
                </strong>
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-icon success-icon">
                <CheckCircle2 :size="22" />
              </div>

              <div>
                <span>Passed Courses</span>
                <strong>
                  {{ academic?.passed_courses ?? 0 }}
                </strong>
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-icon danger-icon">
                <XCircle :size="22" />
              </div>

              <div>
                <span>Failed Courses</span>
                <strong>
                  {{ academic?.failed_courses ?? 0 }}
                </strong>
              </div>
            </div>

          </div>

          <div class="credit-summary">

            <div>
              <span>Total Credits</span>
              <strong>
                {{ academic?.total_credits?.toFixed(2) ?? "0.00" }}
              </strong>
            </div>

            <div>
              <span>Earned Credits</span>
              <strong>
                {{ academic?.earned_credits?.toFixed(2) ?? "0.00" }}
              </strong>
            </div>

          </div>

        </section>

        <!-- ACCOUNT INFORMATION -->
        <section class="panel account-panel">

          <div class="panel-header">
            <div>
              <h2>Account Information</h2>
              <p>Your portal account details.</p>
            </div>

            <ShieldCheck :size="21" />
          </div>

          <div class="account-grid">

            <div>
              <span>Account Role</span>
              <strong>
                {{ student?.role || "Student" }}
              </strong>
            </div>

            <div>
              <span>Account Status</span>

              <strong
                class="status-badge"
                :class="
                  student?.status?.toLowerCase() === 'active'
                    ? 'active'
                    : 'inactive'
                "
              >
                {{ student?.status || "Active" }}
              </strong>
            </div>

            <div>
              <span>Registered Email</span>
              <strong>
                {{ student?.email || "Not Set" }}
              </strong>
            </div>

          </div>

        </section>

      </template>

    </div>
  </StudentLayout>
</template>

<style scoped>
.profile-page {
  max-width: 1200px;
  margin: 0 auto;
}

/*  ALERT  */

.alert {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 13px 16px;
  border-radius: 10px;
  margin-bottom: 18px;
  font-size: 14px;
  font-weight: 600;
}

.success-alert {
  color: #166534;
  background: #dcfce7;
  border: 1px solid #bbf7d0;
}

.error-alert {
  color: #b91c1c;
  background: #fee2e2;
  border: 1px solid #fecaca;
}

/*  PROFILE HEADER  */

.profile-header {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 28px;
  display: flex;
  align-items: center;
  gap: 22px;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.profile-avatar {
  width: 82px;
  height: 82px;
  min-width: 82px;
  border-radius: 50%;
  background: #0f2747;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 27px;
  font-weight: 800;
}

.profile-main-info {
  flex: 1;
}

.profile-main-info h1 {
  margin: 0;
  color: #0f172a;
  font-size: 25px;
  font-weight: 750;
}

.student-id {
  margin: 5px 0 13px;
  color: #64748b;
  font-size: 14px;
}

.header-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.header-meta span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  border-radius: 7px;
  background: #f1f5f9;
  color: #475569;
  font-size: 12px;
  font-weight: 600;
}

/*  BUTTONS  */

.btn-primary,
.btn-secondary,
.btn-edit {
  border: none;
  border-radius: 8px;
  padding: 10px 15px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 650;
  transition: 0.2s;
}

.btn-primary {
  background: #0f2747;
  color: white;
}

.btn-primary:hover {
  background: #173a63;
}

.btn-primary:disabled,
.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f1f5f9;
  color: #334155;
}

.btn-secondary:hover {
  background: #e2e8f0;
}

.btn-edit {
  background: #eff6ff;
  color: #1d4ed8;
  white-space: nowrap;
}

.btn-edit:hover {
  background: #dbeafe;
}

/*  GRID  */

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

/*  PANEL  */

.panel {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 22px;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.panel-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 15px;
  margin-bottom: 20px;
}

.panel-header h2 {
  margin: 0;
  color: #0f172a;
  font-size: 17px;
  font-weight: 750;
}

.panel-header p {
  margin: 5px 0 0;
  color: #64748b;
  font-size: 12px;
}

.panel-header > svg {
  color: #0f2747;
}

/*  PERSONAL  */

.info-list {
  display: flex;
  flex-direction: column;
}

.info-row {
  min-height: 58px;
  display: grid;
  grid-template-columns: 135px 1fr;
  align-items: center;
  gap: 15px;
  border-bottom: 1px solid #f1f5f9;
}

.info-row:last-child {
  border-bottom: none;
}

.info-label {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
}

.info-value {
  color: #0f172a;
  font-size: 14px;
  font-weight: 600;
  word-break: break-word;
}

.form-input {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 9px 11px;
  outline: none;
  font-size: 13px;
  color: #0f172a;
  background: #ffffff;
}

.form-input:focus {
  border-color: #0f2747;
  box-shadow: 0 0 0 3px rgba(15, 39, 71, 0.08);
}

.edit-actions {
  display: flex;
  justify-content: flex-end;
  gap: 9px;
  margin-top: 18px;
  padding-top: 16px;
  border-top: 1px solid #f1f5f9;
}

/*  ACADEMIC INFO  */

.academic-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.academic-item {
  padding: 14px;
  border-radius: 9px;
  background: #f8fafc;
  border: 1px solid #eef2f7;
}

.academic-item span,
.account-grid span,
.credit-summary span {
  display: block;
  color: #64748b;
  font-size: 11px;
  font-weight: 600;
  margin-bottom: 5px;
}

.academic-item strong {
  color: #0f172a;
  font-size: 14px;
}

/*  STATS  */

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #f8fafc;
}

.stat-icon {
  width: 42px;
  height: 42px;
  min-width: 42px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #dbeafe;
  color: #1d4ed8;
}

.success-icon {
  background: #dcfce7;
  color: #15803d;
}

.danger-icon {
  background: #fee2e2;
  color: #dc2626;
}

.stat-card span {
  display: block;
  color: #64748b;
  font-size: 11px;
  font-weight: 600;
  margin-bottom: 4px;
}

.stat-card strong {
  display: block;
  color: #0f172a;
  font-size: 22px;
  font-weight: 800;
}

.cgpa-card {
  background: #f8fafc;
}

/*  CREDITS  */

.credit-summary {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
  margin-top: 14px;
}

.credit-summary > div {
  padding: 15px;
  border-radius: 9px;
  background: #f8fafc;
  border: 1px solid #eef2f7;
}

.credit-summary strong {
  color: #0f172a;
  font-size: 18px;
}

/*  ACCOUNT  */

.account-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
}

.account-grid > div {
  padding: 14px;
  border-radius: 9px;
  background: #f8fafc;
  border: 1px solid #eef2f7;
}

.account-grid strong {
  color: #0f172a;
  font-size: 14px;
  word-break: break-word;
}

.status-badge {
  display: inline-flex;
  padding: 5px 9px;
  border-radius: 6px;
  font-size: 11px !important;
}

.status-badge.active {
  color: #166534;
  background: #dcfce7;
}

.status-badge.inactive {
  color: #b91c1c;
  background: #fee2e2;
}

/*  STATES  */

.state-card {
  min-height: 280px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: #64748b;
}

.state-card h3 {
  margin: 0;
  color: #0f172a;
}

.state-card p {
  margin: 0 0 8px;
  text-align: center;
  max-width: 420px;
}

.error-state svg {
  color: #dc2626;
}

.spinner {
  width: 30px;
  height: 30px;
  border: 3px solid #e2e8f0;
  border-top-color: #0f2747;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/*  RESPONSIVE  */

@media (max-width: 1000px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .account-grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 800px) {
  .info-grid {
    grid-template-columns: 1fr;
    gap: 0;
  }

  .profile-header {
    align-items: flex-start;
    flex-wrap: wrap;
  }

  .btn-edit {
    margin-left: 104px;
  }
}

@media (max-width: 600px) {
  .profile-header {
    padding: 20px;
  }

  .profile-avatar {
    width: 68px;
    height: 68px;
    min-width: 68px;
    font-size: 22px;
  }

  .profile-main-info h1 {
    font-size: 21px;
  }

  .btn-edit {
    width: 100%;
    margin-left: 0;
  }

  .panel {
    padding: 17px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .academic-info-grid,
  .credit-summary,
  .account-grid {
    grid-template-columns: 1fr;
  }

  .info-row {
    grid-template-columns: 1fr;
    gap: 6px;
    padding: 13px 0;
  }

  .edit-actions {
    flex-direction: column-reverse;
  }

  .edit-actions button {
    width: 100%;
  }
}
</style>