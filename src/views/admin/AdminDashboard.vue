<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";

import AdminLayout from "@/components/admin/AdminLayout.vue";
import api from "@/services/api";

import {
  Users,
  BookOpen,
  Building2,
  UserPlus,
  FilePlus,
  Megaphone,
  ClipboardList,
  AlertCircle,
  CalendarDays,
  RefreshCw,
} from "lucide-vue-next";

/*  ROUTER  */

const router = useRouter();

/*  TYPES  */

interface DashboardNotice {
  id: number;
  title: string;
  description: string;
  published_date: string;
  expiry_date: string | null;
  status: "Active" | "Inactive";
  image_url: string | null;
}

interface RecentRegistration {
  id: number;
  student_id: string | null;
  student_name: string;
  course_code: string;
  course_name: string;
  semester: string | null;
  status: string;
  registered_at: string | null;
}

/*  STATE  */

const stats = ref({
  totalStudents: 0,
  totalCourses: 0,
  totalDepartments: 0,
});

const notices = ref<DashboardNotice[]>([]);
const recentRegistrations = ref<RecentRegistration[]>([]);

const loading = ref(false);
const noticesLoading = ref(false);
const registrationsLoading = ref(false);

const errorMessage = ref("");

/*  HELPERS  */

const formatNoticeDate = (date: string | null) => {
  if (!date) return "—";

  return new Date(date).toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
};

const formatRegistrationDate = (date: string | null) => {
  if (!date) return "—";

  return new Date(date).toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
};

const truncateNotice = (text: string, length = 110) => {
  return text.length > length
    ? `${text.substring(0, length)}...`
    : text;
};

/*  DASHBOARD DATA  */

const loadDashboard = async () => {
  loading.value = true;
  registrationsLoading.value = true;
  errorMessage.value = "";

  try {
    const response = await api.get("/admin/dashboard");

    console.log("Dashboard response:", response.data);

    if (response.data?.success) {
      const data = response.data.stats;

      stats.value = {
        totalStudents: data.total_students ?? 0,
        totalCourses: data.total_courses ?? 0,
        totalDepartments: data.total_departments ?? 0,
      };

      recentRegistrations.value =
        response.data.recent_registrations ?? [];
    } else {
      errorMessage.value =
        response.data?.message ||
        "Failed to load dashboard data.";
    }
  } catch (error: any) {
    console.error(
      "Failed to load dashboard:",
      error
    );

    if (error.response?.status === 401) {
      localStorage.removeItem("auth_token");
      localStorage.removeItem("user");
      localStorage.removeItem("remember_me");

      router.push("/");
      return;
    }

    const errors = error.response?.data?.errors;

    if (errors) {
      errorMessage.value = Object.values(errors)
        .flat()
        .join(" ");
    } else {
      errorMessage.value =
        error.response?.data?.message ||
        "Failed to load dashboard data. Please try again.";
    }
  } finally {
    loading.value = false;
    registrationsLoading.value = false;
  }
};

/*  DASHBOARD NOTICES  */

const loadDashboardNotices = async () => {
  noticesLoading.value = true;

  try {
    const { data } = await api.get("/admin/notices");

    notices.value = (data.notices ?? [])
      .filter(
        (notice: DashboardNotice) =>
          notice.status === "Active"
      )
      .slice(0, 3);
  } catch (error) {
    console.error(
      "Failed to load dashboard notices:",
      error
    );

    notices.value = [];
  } finally {
    noticesLoading.value = false;
  }
};

/*  NAVIGATION  */

const navigateTo = (path: string) => {
  router.push(path);
};

/*  INIT  */

onMounted(() => {
  loadDashboard();
  loadDashboardNotices();
});
</script>

<template>
  <AdminLayout
    title="Admin Dashboard"
    subtitle="Manage students, courses and academic activities."
  >
    <template #default="{ user }">

      <!-- Dashboard Error -->

      <div
        v-if="errorMessage"
        class="error-box"
      >
        <AlertCircle :size="17" />

        <span>
          {{ errorMessage }}
        </span>
      </div>


      <!-- Welcome Card -->

      <section class="welcome-card">

        <div>
          <span class="welcome-label">
            ADMINISTRATOR
          </span>

          <h2>
            Welcome, {{ user.name }}
          </h2>

          <p>
            Here's an overview of the university academic system.
          </p>
        </div>

        <div class="admin-role-box">
          <span>
            Account Type
          </span>

          <strong>
            Administrator
          </strong>
        </div>

      </section>


      <!-- Summary Cards -->

      <section class="stats-grid">

        <!-- Total Students -->

        <div class="stat-card">

          <div class="stat-icon blue">
            <Users
              :size="20"
              class="icon-blue"
            />
          </div>

          <div>
            <span>
              Total Students
            </span>

            <strong>
              {{ stats.totalStudents }}
            </strong>
          </div>

        </div>


        <!-- Total Courses -->

        <div class="stat-card">

          <div class="stat-icon green">
            <BookOpen
              :size="20"
              class="icon-green"
            />
          </div>

          <div>
            <span>
              Total Courses
            </span>

            <strong>
              {{ stats.totalCourses }}
            </strong>
          </div>

        </div>


        <!-- Total Departments -->

        <div class="stat-card">

          <div class="stat-icon purple">
            <Building2
              :size="20"
              class="icon-purple"
            />
          </div>

          <div>
            <span>
              Total Departments
            </span>

            <strong>
              {{ stats.totalDepartments }}
            </strong>
          </div>

        </div>

      </section>


      <!-- Quick Actions + System Overview -->

      <section class="content-grid">

        <!-- Quick Actions -->

        <div class="panel">

          <div class="panel-header">

            <div>
              <h3>
                Quick Actions
              </h3>

              <p>
                Frequently used administrative services
              </p>
            </div>

          </div>


          <div class="quick-actions">

            <!-- Add Student -->

            <button
              class="action-card"
              @click="navigateTo('/admin/students')"
            >

              <div class="action-icon">
                <UserPlus :size="18" />
              </div>

              <div>
                <strong>
                  Add Student
                </strong>

                <span>
                  Register a new student
                </span>
              </div>

            </button>


            <!-- Add Course -->

            <button
              class="action-card"
              @click="navigateTo('/admin/courses')"
            >

              <div class="action-icon">
                <FilePlus :size="18" />
              </div>

              <div>
                <strong>
                  Add Course
                </strong>

                <span>
                  Create a new course
                </span>
              </div>

            </button>


            <!-- Add Department -->

            <button
              class="action-card"
              @click="navigateTo('/admin/department')"
            >

              <div class="action-icon">
                <Building2 :size="18" />
              </div>

              <div>
                <strong>
                  Add Department
                </strong>

                <span>
                  Create a new department
                </span>
              </div>

            </button>


            <!-- Post Notice -->

            <button
              class="action-card"
              @click="navigateTo('/admin/notices')"
            >

              <div class="action-icon">
                <Megaphone :size="18" />
              </div>

              <div>
                <strong>
                  Post Notice
                </strong>

                <span>
                  Publish a university notice
                </span>
              </div>

            </button>

          </div>

        </div>


        <!-- System Overview -->

        <div class="panel">

          <div class="panel-header">

            <div>
              <h3>
                System Overview
              </h3>

              <p>
                Current academic system status
              </p>
            </div>

          </div>


          <div class="academic-list">

            <div class="academic-row">

              <span>
                Students
              </span>

              <strong>
                {{ stats.totalStudents }}
              </strong>

            </div>


            <div class="academic-row">

              <span>
                Courses
              </span>

              <strong>
                {{ stats.totalCourses }}
              </strong>

            </div>


            <div class="academic-row">

              <span>
                Departments
              </span>

              <strong>
                {{ stats.totalDepartments }}
              </strong>

            </div>

          </div>

        </div>

      </section>


      <!-- Recent Registration -->

      <section class="panel registrations-panel">

        <div class="panel-header">

          <div>
            <h3>
              Recent Registrations
            </h3>

            <p>
              Latest student course registration activities
            </p>
          </div>

          <button
            class="view-all"
            @click="navigateTo('/admin/enrollments')"
          >
            View All
          </button>

        </div>


        <!-- Loading -->

        <div
          v-if="registrationsLoading"
          class="registration-loading"
        >

          <RefreshCw
            :size="17"
            class="spin"
          />

          <span>
            Loading registrations...
          </span>

        </div>


        <!-- Registration List -->

        <div
          v-else-if="recentRegistrations.length"
          class="recent-registrations"
        >

          <div
            v-for="registration in recentRegistrations"
            :key="registration.id"
            class="registration-row"
          >

            <!-- Student -->

            <div class="registration-student">

              <div class="registration-avatar">
                {{
                  registration.student_name
                    .charAt(0)
                    .toUpperCase()
                }}
              </div>

              <div class="registration-student-info">

                <strong>
                  {{ registration.student_name }}
                </strong>

                <span>
                  {{ registration.student_id || "N/A" }}
                </span>

              </div>

            </div>


            <!-- Course -->

            <div class="registration-course">

              <strong>
                {{ registration.course_code }}
              </strong>

              <span>
                {{ registration.course_name }}
              </span>

            </div>


            <!-- Semester -->

            <div class="registration-semester">

              <span>
                Semester
              </span>

              <strong>
                {{ registration.semester || "N/A" }}
              </strong>

            </div>


            <!-- Date -->

            <div class="registration-date">

              <span>
                Registered
              </span>

              <strong>
                {{
                  formatRegistrationDate(
                    registration.registered_at
                  )
                }}
              </strong>

            </div>


            <!-- Status -->

            <div class="registration-status">

              <span
                :class="[
                  'status-badge',
                  registration.status
                    .toLowerCase()
                    .replace(/\s+/g, '-')
                ]"
              >
                {{ registration.status }}
              </span>

            </div>

          </div>

        </div>


        <!-- Empty -->

        <div
          v-else
          class="empty-state"
        >

          <div class="empty-icon">
            <ClipboardList :size="18" />
          </div>

          <div>

            <strong>
              No recent registrations
            </strong>

            <p>
              New course registration activities will appear here.
            </p>

          </div>

        </div>

      </section>


      <!-- University Notices -->

      <section class="panel notice-panel">

        <div class="panel-header">

          <div>

            <h3>
              University Notices
            </h3>

            <p>
              Latest active university announcements
            </p>

          </div>

          <button
            class="view-all"
            @click="navigateTo('/admin/notices')"
          >
            Manage
          </button>

        </div>


        <!-- Loading -->

        <div
          v-if="noticesLoading"
          class="notice-loading"
        >

          <RefreshCw
            :size="17"
            class="spin"
          />

          <span>
            Loading notices...
          </span>

        </div>


        <!-- Notices -->

        <div
          v-else-if="notices.length"
          class="dashboard-notices"
        >

          <div
            v-for="notice in notices"
            :key="notice.id"
            class="dashboard-notice"
          >

            <!-- Notice Image -->

            <div
              v-if="notice.image_url"
              class="dashboard-notice-image"
            >

              <img
                :src="notice.image_url"
                :alt="notice.title"
              />

            </div>


            <!-- No Image -->

            <div
              v-else
              class="dashboard-notice-icon"
            >
              <Megaphone :size="18" />
            </div>


            <!-- Notice Content -->

            <div class="dashboard-notice-content">

              <div class="dashboard-notice-title">
                {{ notice.title }}
              </div>

              <p>
                {{ truncateNotice(notice.description) }}
              </p>


              <div class="dashboard-notice-meta">

                <span>

                  <CalendarDays :size="13" />

                  {{
                    formatNoticeDate(
                      notice.published_date
                    )
                  }}

                </span>

                <span class="dashboard-notice-status">
                  Active
                </span>

              </div>

            </div>

          </div>

        </div>


        <!-- Empty -->

        <div
          v-else
          class="empty-state"
        >

          <div class="empty-icon">
            <Megaphone :size="18" />
          </div>

          <div>

            <strong>
              No active notices
            </strong>

            <p>
              Active university announcements will appear here.
            </p>

          </div>

        </div>

      </section>

    </template>
  </AdminLayout>
</template>

<style scoped>

/*  ERROR  */

.error-box {
  margin-bottom: 20px;

  padding: 11px 14px;

  border-radius: 8px;

  background: #fef2f2;

  border: 1px solid #fecaca;

  color: #b91c1c;

  font-size: 13px;

  display: flex;

  align-items: flex-start;

  gap: 8px;
}

.error-box span {
  line-height: 1.45;
}


/*  WELCOME  */

.welcome-card {
  background: linear-gradient(
    135deg,
    #123b63,
    #0a1930
  );

  color: white;

  border-radius: 14px;

  padding: 24px 26px;

  display: flex;

  justify-content: space-between;

  align-items: center;

  margin-bottom: 20px;

  box-shadow:
    0 10px 25px rgba(10, 25, 48, 0.15);
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

.admin-role-box {
  background: rgba(255,255,255,.08);

  border: 1px solid rgba(255,255,255,.12);

  padding: 12px 18px;

  border-radius: 10px;

  text-align: right;
}

.admin-role-box span {
  display: block;

  color: #94a3b8;

  font-size: 9px;
}

.admin-role-box strong {
  display: block;

  margin-top: 4px;

  font-size: 13px;
}


/*  STATS  */

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

  display: flex;

  align-items: center;

  justify-content: center;

  flex-shrink: 0;
}

.blue {
  background: #eff6ff;
}

.green {
  background: #f0fdf4;
}

.purple {
  background: #faf5ff;
}

.icon-blue {
  color: #2563eb;
}

.icon-green {
  color: #16a34a;
}

.icon-purple {
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


/*  CONTENT GRID  */

.content-grid {
  display: grid;

  grid-template-columns: 1.4fr 1fr;

  gap: 18px;

  margin-bottom: 18px;
}


/*  PANEL  */

.panel {
  background: white;

  border: 1px solid #e2e8f0;

  border-radius: 12px;

  padding: 20px;

  margin-bottom: 18px;
}

.panel-header {
  display: flex;

  justify-content: space-between;

  align-items: center;

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


/*  QUICK ACTIONS  */

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

  transition: .2s;
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

  display: flex;

  align-items: center;

  justify-content: center;

  flex-shrink: 0;
}

.action-card strong {
  display: block;

  font-size: 11px;
}

.action-card span {
  display: block;

  margin-top: 3px;

  font-size: 9px;

  color: #64748b;
}


/*  ACADEMIC LIST  */

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
  border-bottom: none;
}

.academic-row span,
.academic-row strong {
  font-size: 10px;
}

.academic-row span {
  color: #64748b;
}


/*  EMPTY  */

.empty-state {
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

  color: #64748b;

  display: flex;

  align-items: center;

  justify-content: center;

  flex-shrink: 0;
}

.empty-state strong {
  font-size: 11px;
}

.empty-state p {
  margin: 3px 0 0;

  font-size: 9px;

  color: #64748b;
}


/*  VIEW ALL  */

.view-all {
  border: none;

  background: transparent;

  color: #123b63;

  font-size: 10px;

  font-weight: 600;

  cursor: pointer;
}

.view-all:hover {
  text-decoration: underline;
}


/*  REGISTRATION LOADING  */

.registration-loading {
  min-height: 100px;

  display: flex;

  align-items: center;

  justify-content: center;

  gap: 8px;

  color: #64748b;

  font-size: 12px;
}


/*  RECENT REGISTRATIONS  */

.recent-registrations {
  display: flex;

  flex-direction: column;

  gap: 9px;
}

.registration-row {
  display: grid;

  grid-template-columns:
    1.4fr
    1.5fr
    .65fr
    .8fr
    .6fr;

  align-items: center;

  gap: 15px;

  padding: 12px;

  border: 1px solid #e2e8f0;

  border-radius: 9px;

  background: #f8fafc;

  transition: .2s;
}

.registration-row:hover {
  border-color: #cbd5e1;

  background: #f1f5f9;
}


/* Student */

.registration-student {
  display: flex;

  align-items: center;

  gap: 9px;

  min-width: 0;
}

.registration-avatar {
  width: 34px;

  height: 34px;

  border-radius: 50%;

  background: #123b63;

  color: white;

  display: flex;

  align-items: center;

  justify-content: center;

  font-size: 11px;

  font-weight: 700;

  flex-shrink: 0;
}

.registration-student-info {
  min-width: 0;
}

.registration-student-info strong {
  display: block;

  font-size: 10px;

  white-space: nowrap;

  overflow: hidden;

  text-overflow: ellipsis;
}

.registration-student-info span {
  display: block;

  margin-top: 3px;

  font-size: 9px;

  color: #64748b;
}


/* Course */

.registration-course {
  min-width: 0;
}

.registration-course strong {
  display: block;

  font-size: 10px;
}

.registration-course span {
  display: block;

  margin-top: 3px;

  font-size: 9px;

  color: #64748b;

  white-space: nowrap;

  overflow: hidden;

  text-overflow: ellipsis;
}


/* Semester */

.registration-semester span,
.registration-date span {
  display: block;

  font-size: 8px;

  color: #94a3b8;
}

.registration-semester strong,
.registration-date strong {
  display: block;

  margin-top: 3px;

  font-size: 9px;
}


/* Status */

.status-badge {
  display: inline-flex;

  align-items: center;

  justify-content: center;

  padding: 4px 8px;

  border-radius: 12px;

  font-size: 8px;

  font-weight: 700;

  white-space: nowrap;
}

.status-badge.active {
  background: #e9f8ef;

  color: #18834a;
}

.status-badge.completed {
  background: #eff6ff;

  color: #2563eb;
}

.status-badge.failed {
  background: #fef2f2;

  color: #dc2626;
}

.status-badge.dropped {
  background: #fefce8;

  color: #a16207;
}


/*  DASHBOARD NOTICES  */

.notice-loading {
  min-height: 100px;

  display: flex;

  align-items: center;

  justify-content: center;

  gap: 8px;

  color: #64748b;

  font-size: 12px;
}

.spin {
  animation: spin 1s linear infinite;
}

.dashboard-notices {
  display: flex;

  flex-direction: column;

  gap: 10px;
}

.dashboard-notice {
  display: flex;

  align-items: flex-start;

  gap: 12px;

  padding: 12px;

  border: 1px solid #e2e8f0;

  border-radius: 9px;

  background: #f8fafc;
}

.dashboard-notice-image,
.dashboard-notice-icon {
  width: 45px;

  height: 45px;

  flex-shrink: 0;

  border-radius: 8px;

  overflow: hidden;
}

.dashboard-notice-image {
  border: 1px solid #e2e8f0;

  background: white;
}

.dashboard-notice-image img {
  width: 100%;

  height: 100%;

  object-fit: cover;
}

.dashboard-notice-icon {
  display: flex;

  align-items: center;

  justify-content: center;

  background: #eef2ff;

  color: #172554;
}

.dashboard-notice-content {
  min-width: 0;

  flex: 1;
}

.dashboard-notice-title {
  color: #172033;

  font-size: 12px;

  font-weight: 700;

  white-space: nowrap;

  overflow: hidden;

  text-overflow: ellipsis;
}

.dashboard-notice-content p {
  margin: 4px 0 7px;

  color: #64748b;

  font-size: 10px;

  line-height: 1.45;
}

.dashboard-notice-meta {
  display: flex;

  align-items: center;

  gap: 10px;

  color: #7b8494;

  font-size: 9px;
}

.dashboard-notice-meta span {
  display: inline-flex;

  align-items: center;

  gap: 4px;
}

.dashboard-notice-status {
  padding: 3px 7px;

  border-radius: 10px;

  background: #e9f8ef;

  color: #18834a;

  font-weight: 700;
}


/*  ANIMATION  */

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}


/*  RESPONSIVE  */

@media (max-width: 1100px) {

  .registration-row {
    grid-template-columns:
      1.4fr
      1.5fr
      .7fr
      .8fr;
  }

  .registration-status {
    grid-column: 4;
  }

}

@media (max-width: 900px) {

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .content-grid {
    grid-template-columns: 1fr;
  }

  .registration-row {
    grid-template-columns: 1fr 1fr;
  }

  .registration-status {
    grid-column: auto;
  }

}

@media (max-width: 650px) {

  .welcome-card {
    flex-direction: column;

    align-items: flex-start;

    gap: 15px;

    padding: 20px;
  }

  .welcome-card h2 {
    font-size: 19px;
  }

  .admin-role-box {
    text-align: left;

    width: 100%;
  }

  .stats-grid,
  .quick-actions {
    grid-template-columns: 1fr;
  }

  .panel {
    padding: 16px;
  }

  .panel-header {
    margin-bottom: 13px;
  }

  .academic-row {
    align-items: flex-start;
  }

  .registration-row {
    grid-template-columns: 1fr;

    gap: 10px;
  }

  .registration-status {
    grid-column: auto;
  }

  .dashboard-notice {
    padding: 10px;
  }

  .dashboard-notice-image,
  .dashboard-notice-icon {
    width: 40px;

    height: 40px;
  }

}

@media (max-width: 400px) {

  .welcome-card {
    padding: 17px;
  }

  .welcome-card h2 {
    font-size: 17px;
  }

}
</style>
