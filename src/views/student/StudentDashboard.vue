<script setup lang="ts">
import { onMounted, ref } from "vue";

import {
  Plus,
  BookOpen,
  Calendar,
  User,
  Check,
  GraduationCap,
  AlertCircle,
  ChevronRight,
} from "lucide-vue-next";

import StudentLayout from "@/components/student/StudentLayout.vue";
import api from "@/services/api";

/*  TYPES  */

interface DashboardNotice {
  id: number;
  title: string;
  description: string;
  published_date: string;
  expiry_date: string | null;
  image_path?: string | null;
  image_url?: string | null;
  status: "Active" | "Inactive";
}

/*  STATE  */

const stats = ref({
  enrolledCourses: 0,
  completedCourses: 0,
  completedCredits: 0,
  currentSemester: "Not Set",
});

const notices = ref<DashboardNotice[]>([]);
const noticesLoading = ref(false);

/*  DASHBOARD  */

async function loadDashboard() {
  try {
    const { data } = await api.get("/student/dashboard");

    if (data.stats) {
      stats.value = {
        ...stats.value,
        ...data.stats,
      };
    }
  } catch (error) {
    console.error("Failed to load student dashboard:", error);
  }
}

/*  NOTICES  */

async function loadNotices() {
  noticesLoading.value = true;

  try {
    const { data } = await api.get("/student/notices");

    if (data.success && Array.isArray(data.notices)) {
      notices.value = data.notices
        .filter((notice: DashboardNotice) => notice.status === "Active")
        .slice(0, 3);
    } else {
      notices.value = [];
    }
  } catch (error) {
    console.error("Failed to load student notices:", error);
    notices.value = [];
  } finally {
    noticesLoading.value = false;
  }
}

/*  HELPERS  */

function navigate(path: string) {
  window.location.href = path;
}

function formatDate(date: string) {
  if (!date) return "";

  return new Date(date).toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
}

/*  INIT  */

onMounted(() => {
  loadDashboard();
  loadNotices();
});
</script>

<template>
  <StudentLayout
    title="Student Dashboard"
    subtitle="Manage your courses and academic information."
  >
    <template #default="{ user }">

      <!-- Welcome Card -->
      <section class="welcome-card">
        <div>
          <span class="welcome-label">WELCOME BACK</span>

          <h2>Hello, {{ user.name }}</h2>

          <p>
            Here's an overview of your academic activities.
          </p>
        </div>

        <div class="student-id-box">
          <span>Student ID</span>
          <strong>{{ user.user_id }}</strong>
        </div>
      </section>

      <!-- Statistics -->
      <section class="stats-grid">

        <!-- Enrolled Courses -->
        <div class="stat-card">
          <div class="stat-icon blue">
            <BookOpen :size="20" />
          </div>

          <div>
            <span>Enrolled Courses</span>
            <strong>{{ stats.enrolledCourses }}</strong>
          </div>
        </div>

        <!-- Completed Courses -->
        <div class="stat-card">
          <div class="stat-icon green">
            <Check :size="20" />
          </div>

          <div>
            <span>Completed Courses</span>
            <strong>{{ stats.completedCourses }}</strong>
          </div>
        </div>

        <!-- Completed Credits -->
        <div class="stat-card">
          <div class="stat-icon purple">
            <GraduationCap :size="20" />
          </div>

          <div>
            <span>Completed Credits</span>
            <strong>{{ stats.completedCredits }}</strong>
          </div>
        </div>

      </section>

      <!-- Main Grid -->
      <section class="content-grid">

        <!-- Quick Actions -->
        <div class="panel">
          <div class="panel-header">
            <h3>Quick Actions</h3>
            <p>Frequently used student services</p>
          </div>

          <div class="quick-actions">

            <button
              class="action-card"
              @click="navigate('/student/registration')"
            >
              <div class="action-icon">
                <Plus :size="18" />
              </div>

              <div>
                <strong>Register Courses</strong>
                <span>Select courses for the semester</span>
              </div>
            </button>

            <button
              class="action-card"
              @click="navigate('/student/my-courses')"
            >
              <div class="action-icon">
                <BookOpen :size="18" />
              </div>

              <div>
                <strong>View My Courses</strong>
                <span>See your enrolled courses</span>
              </div>
            </button>

            <button
              class="action-card"
              @click="navigate('/student/routine')"
            >
              <div class="action-icon">
                <Calendar :size="18" />
              </div>

              <div>
                <strong>Class Schedule</strong>
                <span>View your weekly schedule</span>
              </div>
            </button>

            <button
              class="action-card"
              @click="navigate('/student/profile')"
            >
              <div class="action-icon">
                <User :size="18" />
              </div>

              <div>
                <strong>View Profile</strong>
                <span>Manage your account information</span>
              </div>
            </button>

          </div>
        </div>

        <!-- Academic Information -->
        <div class="panel">
          <div class="panel-header">
            <h3>Academic Information</h3>
            <p>Your current academic status</p>
          </div>

          <div class="academic-list">

            <div class="academic-row">
              <span>Student ID</span>
              <strong>{{ user.user_id }}</strong>
            </div>

            <div class="academic-row">
              <span>Email</span>
              <strong>{{ user.email }}</strong>
            </div>

            <div class="academic-row">
              <span>Account Type</span>
              <strong>Student</strong>
            </div>

            <div class="academic-row">
              <span>Current Semester</span>
              <strong>{{ stats.currentSemester }}</strong>
            </div>

          </div>
        </div>

      </section>

      <!-- Notices -->
      <section class="panel notice-panel">

        <div class="panel-header">

          <div>
            <h3>University Notices</h3>

            <p>
              Latest announcements and important information
            </p>
          </div>

          <button
            class="view-all"
            @click="navigate('/student/notices')"
          >
            View All
            <ChevronRight :size="13" />
          </button>

        </div>

        <!-- Loading -->
        <div
          v-if="noticesLoading"
          class="notice-loading"
        >
          Loading university notices...
        </div>

        <!-- Notices -->
        <div
          v-else-if="notices.length"
          class="notice-list"
        >
          <div
            v-for="notice in notices"
            :key="notice.id"
            class="notice-item"
          >

            <div class="notice-icon">
              <AlertCircle :size="17" />
            </div>

            <div class="notice-content">
              <div class="notice-title-row">
                <strong>{{ notice.title }}</strong>

                <span class="notice-date">
                  {{ formatDate(notice.published_date) }}
                </span>
              </div>

              <p>
                {{ notice.description }}
              </p>
            </div>

          </div>
        </div>

        <!-- Empty -->
        <div
          v-else
          class="empty-notice"
        >
          <div class="empty-icon">
            <AlertCircle :size="18" />
          </div>

          <div>
            <strong>No new notices</strong>

            <p>
              You don't have any new university announcements.
            </p>
          </div>
        </div>

      </section>

      <footer>
        © 2026 City University. All rights reserved.
      </footer>

    </template>
  </StudentLayout>
</template>

<style scoped>
.welcome-card {
  background: linear-gradient(135deg, #123b63, #0a1930);
  color: white;
  border-radius: 14px;
  padding: 24px 26px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  box-shadow: 0 10px 25px rgba(10, 25, 48, 0.15);
}

.welcome-label {
  font-size: 9px;
  letter-spacing: 1.5px;
  color: #93c5fd;
  font-weight: 700;
}

.welcome-card h2 {
  margin: 6px 0;
  font-size: 22px;
}

.welcome-card p {
  margin: 0;
  color: #cbd5e1;
  font-size: 11px;
}

.student-id-box {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 12px 18px;
  border-radius: 10px;
  text-align: right;
}

.student-id-box span,
.student-id-box strong {
  display: block;
}

.student-id-box span {
  color: #94a3b8;
  font-size: 9px;
}

.student-id-box strong {
  margin-top: 4px;
  font-size: 13px;
}

/* Statistics */

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
  margin-bottom: 20px;
}

.stat-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 17px;
  display: flex;
  align-items: center;
  gap: 13px;
}

.stat-icon {
  width: 38px;
  height: 38px;
  border-radius: 9px;
  display: grid;
  place-items: center;
}

.blue {
  background: #eff6ff;
  color: #2563eb;
}

.green {
  background: #f0fdf4;
  color: #16a34a;
}

.purple {
  background: #faf5ff;
  color: #9333ea;
}

.stat-card span {
  display: block;
  font-size: 10px;
  color: #64748b;
}

.stat-card strong {
  display: block;
  margin-top: 4px;
  font-size: 20px;
}

/* Main Grid */

.content-grid {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 18px;
  margin-bottom: 18px;
}

.panel {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
}

.panel-header {
  margin-bottom: 17px;
}

.panel-header h3 {
  margin: 0;
  font-size: 14px;
}

.panel-header p {
  margin: 4px 0 0;
  font-size: 10px;
  color: #64748b;
}

/* Quick Actions */

.quick-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.action-card {
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  border-radius: 9px;
  padding: 13px;
  display: flex;
  align-items: center;
  gap: 10px;
  text-align: left;
  cursor: pointer;
  transition: 0.2s;
}

.action-card:hover {
  border-color: #123b63;
  background: #f1f5f9;
}

.action-icon {
  width: 32px;
  height: 32px;
  border-radius: 7px;
  background: #123b63;
  color: white;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.action-card strong,
.action-card span {
  display: block;
}

.action-card strong {
  font-size: 11px;
}

.action-card span {
  margin-top: 3px;
  font-size: 9px;
  color: #64748b;
}

/* Academic Information */

.academic-list {
  display: flex;
  flex-direction: column;
}

.academic-row {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  padding: 11px 0;
  border-bottom: 1px solid #f1f5f9;
}

.academic-row:last-child {
  border-bottom: 0;
}

.academic-row span,
.academic-row strong {
  font-size: 10px;
}

.academic-row span {
  color: #64748b;
}

.academic-row strong {
  text-align: right;
  word-break: break-word;
}

/* Notices */

.notice-panel {
  margin-bottom: 20px;
}

.notice-panel .panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.view-all {
  border: 0;
  background: transparent;
  color: #123b63;
  font-size: 10px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 2px;
}

.notice-loading {
  border: 1px dashed #cbd5e1;
  border-radius: 9px;
  padding: 17px;
  text-align: center;
  color: #64748b;
  font-size: 10px;
}

.notice-list {
  display: flex;
  flex-direction: column;
  gap: 9px;
}

.notice-item {
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  border-radius: 9px;
  padding: 12px;
  display: flex;
  align-items: flex-start;
  gap: 11px;
}

.notice-item:hover {
  border-color: #cbd5e1;
}

.notice-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #eff6ff;
  color: #123b63;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.notice-content {
  min-width: 0;
  flex: 1;
}

.notice-title-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.notice-title-row strong {
  font-size: 11px;
  color: #0f172a;
}

.notice-date {
  font-size: 9px;
  color: #94a3b8;
  white-space: nowrap;
}

.notice-content p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 9px;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.empty-notice {
  border: 1px dashed #cbd5e1;
  border-radius: 9px;
  padding: 15px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.empty-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #f1f5f9;
  display: grid;
  place-items: center;
  color: #64748b;
}

.empty-notice strong {
  font-size: 11px;
}

.empty-notice p {
  margin: 3px 0 0;
  font-size: 9px;
  color: #64748b;
}

footer {
  text-align: center;
  padding: 10px;
  color: #94a3b8;
  font-size: 10px;
}

/* Responsive */

@media (max-width: 900px) {
  .stats-grid {
    grid-template-columns: repeat(3, 1fr);
  }

  .content-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 650px) {
  .welcome-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
    padding: 20px;
  }

  .student-id-box {
    width: 100%;
    text-align: left;
  }

  .stats-grid,
  .quick-actions {
    grid-template-columns: 1fr;
  }

  .panel {
    padding: 16px;
  }

  .notice-title-row {
    flex-direction: column;
    gap: 4px;
  }
}
</style>