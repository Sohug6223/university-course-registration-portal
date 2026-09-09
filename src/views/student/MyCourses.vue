<script setup lang="ts">
import { computed, onMounted, ref } from "vue";

import {
  BookOpen,
  CheckCircle,
  AlertCircle,
  RefreshCw,
  GraduationCap,
  User,
} from "lucide-vue-next";

import StudentLayout from "@/components/student/StudentLayout.vue";
import api from "@/services/api";

/*  TYPES  */

interface Student {
  id: number;
  user_id: string;
  name: string;
  department: string;
  batch: number | null;
  section: string | null;
  current_semester: string | null;
}

interface Course {
  id: number;
  course_id: number;
  course_code: string;
  course_name: string;
  credit: number;
  semester: string;
  section: string | null;
  status: "Active" | "Completed" | "Failed" | string;
  grade: string | null;
  enrollment_status: string;
}

interface Summary {
  active: number;
  completed: number;
  failed: number;
  total: number;
}

interface CoursesData {
  student: Student;
  summary: Summary;
  courses: Course[];
}

/*  STATE  */

const loading = ref(true);
const refreshing = ref(false);
const errorMessage = ref("");

const data = ref<CoursesData>({
  student: {
    id: 0,
    user_id: "",
    name: "",
    department: "",
    batch: null,
    section: null,
    current_semester: null,
  },

  summary: {
    active: 0,
    completed: 0,
    failed: 0,
    total: 0,
  },

  courses: [],
});

/*  COMPUTED  */

const activeCourses = computed(() =>
  data.value.courses.filter(
    (course) => course.status === "Active"
  )
);

const completedCourses = computed(() =>
  data.value.courses.filter(
    (course) => course.status === "Completed"
  )
);

const failedCourses = computed(() =>
  data.value.courses.filter(
    (course) => course.status === "Failed"
  )
);

/*  HELPERS  */

function getError(error: any) {
  return (
    error?.response?.data?.message ||
    error?.response?.data?.error ||
    "Unable to load your courses."
  );
}

/*  LOAD  */

async function loadCourses() {
  errorMessage.value = "";
  loading.value = true;

  try {
    const response = await api.get(
      "/student/my-courses"
    );

    data.value = response.data;
  } catch (error) {
    errorMessage.value = getError(error);
  } finally {
    loading.value = false;
  }
}

/*  REFRESH  */

async function refresh() {
  refreshing.value = true;

  try {
    await loadCourses();
  } finally {
    refreshing.value = false;
  }
}

onMounted(loadCourses);
</script>

<template>
  <StudentLayout
    title="My Courses"
    subtitle="View your active, completed and failed courses."
  >
    <template #default="{ user }">

      <div class="courses-page">

        <!-- LOADING -->

        <div
          v-if="loading"
          class="loading-box"
        >
          <RefreshCw
            :size="22"
            class="spin"
          />

          <span>
            Loading your courses...
          </span>
        </div>


        <template v-else>

          <!-- ERROR -->

          <div
            v-if="errorMessage"
            class="alert error"
          >
            <AlertCircle :size="19" />

            <span>
              {{ errorMessage }}
            </span>

            <button
              class="retry-btn"
              @click="loadCourses"
            >
              Retry
            </button>
          </div>


          <!-- STUDENT INFORMATION -->

          <section class="panel student-info">

            <div class="panel-title">

              <div class="title-icon">
                <User :size="20" />
              </div>

              <div>
                <h3>
                  Student Information
                </h3>

                <p>
                  Your academic information.
                </p>
              </div>

            </div>


            <div class="info-grid">

              <div>
                <span>Student ID</span>

                <strong>
                  {{ data.student.user_id }}
                </strong>
              </div>

              <div>
                <span>Name</span>

                <strong>
                  {{ data.student.name }}
                </strong>
              </div>

              <div>
                <span>Department</span>

                <strong>
                  {{ data.student.department }}
                </strong>
              </div>

              <div>
                <span>Batch</span>

                <strong>
                  {{ data.student.batch ?? "N/A" }}
                </strong>
              </div>

              <div>
                <span>Section</span>

                <strong>
                  {{
                    data.student.section ||
                    "Not Assigned"
                  }}
                </strong>
              </div>

              <div>
                <span>Current Semester</span>

                <strong>
                  {{
                    data.student.current_semester ||
                    "N/A"
                  }}
                </strong>
              </div>

            </div>

          </section>


          <!-- SUMMARY -->

          <div class="stats-grid">

            <div class="stat-card">

              <div class="stat-icon active">
                <BookOpen :size="20" />
              </div>

              <div>
                <span>
                  Active Courses
                </span>

                <strong>
                  {{ data.summary.active }}
                </strong>
              </div>

            </div>


            <div class="stat-card">

              <div class="stat-icon completed">
                <CheckCircle :size="20" />
              </div>

              <div>
                <span>
                  Completed Courses
                </span>

                <strong>
                  {{ data.summary.completed }}
                </strong>
              </div>

            </div>


            <div class="stat-card">

              <div class="stat-icon failed">
                <AlertCircle :size="20" />
              </div>

              <div>
                <span>
                  Failed Courses
                </span>

                <strong>
                  {{ data.summary.failed }}
                </strong>
              </div>

            </div>


            <div class="stat-card">

              <div class="stat-icon total">
                <GraduationCap :size="20" />
              </div>

              <div>
                <span>
                  Total Courses
                </span>

                <strong>
                  {{ data.summary.total }}
                </strong>
              </div>

            </div>

          </div>


          <!-- PAGE HEADER -->

          <div class="section-header">

            <div>
              <h2>
                My Courses
              </h2>

              <p>
                All courses associated with your
                academic record.
              </p>
            </div>


            <button
              class="refresh-btn"
              @click="refresh"
              :disabled="refreshing"
            >
              <RefreshCw
                :size="17"
                :class="{ spin: refreshing }"
              />

              Refresh
            </button>

          </div>


          <!-- ACTIVE COURSES -->

          <section class="panel">

            <div class="panel-title">

              <div class="title-icon">
                <BookOpen :size="20" />
              </div>

              <div>
                <h3>
                  Active Courses
                </h3>

                <p>
                  Courses you are currently taking.
                </p>
              </div>

            </div>


            <div
              v-if="!activeCourses.length"
              class="empty-box"
            >
              <BookOpen :size="35" />

              <h3>
                No Active Courses
              </h3>

              <p>
                You currently have no active courses.
              </p>
            </div>


            <div
              v-else
              class="course-list"
            >

              <div
                v-for="course in activeCourses"
                :key="course.id"
                class="course-item"
              >

                <div class="course-left">

                  <div class="course-icon">
                    <BookOpen :size="19" />
                  </div>

                  <div>

                    <strong>
                      {{ course.course_code }}
                    </strong>

                    <h4>
                      {{ course.course_name }}
                    </h4>

                  </div>

                </div>


                <div class="course-details">

                  <span>
                    {{ course.credit }} Credit
                  </span>

                  <span>
                    {{ course.semester }} Semester
                  </span>

                  <span>
                    Section
                    {{ course.section || "N/A" }}
                  </span>

                  <b class="status active">
                    Active
                  </b>

                </div>

              </div>

            </div>

          </section>


          <!-- COMPLETED COURSES -->

          <section class="panel">

            <div class="panel-title">

              <div class="title-icon">
                <CheckCircle :size="20" />
              </div>

              <div>
                <h3>
                  Completed Courses
                </h3>

                <p>
                  Courses you have successfully
                  completed.
                </p>
              </div>

            </div>


            <div
              v-if="!completedCourses.length"
              class="empty-box"
            >
              <CheckCircle :size="35" />

              <h3>
                No Completed Courses
              </h3>

              <p>
                No completed courses are available
                in your academic record.
              </p>
            </div>


            <div
              v-else
              class="course-list"
            >

              <div
                v-for="course in completedCourses"
                :key="course.id"
                class="course-item"
              >

                <div class="course-left">

                  <div class="course-icon">
                    <CheckCircle :size="19" />
                  </div>

                  <div>

                    <strong>
                      {{ course.course_code }}
                    </strong>

                    <h4>
                      {{ course.course_name }}
                    </h4>

                  </div>

                </div>


                <div class="course-details">

                  <span>
                    {{ course.credit }} Credit
                  </span>

                  <span>
                    {{ course.semester }} Semester
                  </span>

                  <span>
                    Grade
                    {{ course.grade || "N/A" }}
                  </span>

                  <b class="status completed">
                    Completed
                  </b>

                </div>

              </div>

            </div>

          </section>


          <!-- FAILED COURSES -->

          <section
            v-if="failedCourses.length"
            class="panel"
          >

            <div class="panel-title">

              <div class="title-icon failed-icon">
                <AlertCircle :size="20" />
              </div>

              <div>
                <h3>
                  Failed Courses
                </h3>

                <p>
                  Courses that require attention.
                </p>
              </div>

            </div>


            <div class="course-list">

              <div
                v-for="course in failedCourses"
                :key="course.id"
                class="course-item failed-item"
              >

                <div class="course-left">

                  <div class="course-icon failed-course-icon">
                    <AlertCircle :size="19" />
                  </div>

                  <div>

                    <strong>
                      {{ course.course_code }}
                    </strong>

                    <h4>
                      {{ course.course_name }}
                    </h4>

                  </div>

                </div>


                <div class="course-details">

                  <span>
                    {{ course.credit }} Credit
                  </span>

                  <span>
                    {{ course.semester }} Semester
                  </span>

                  <span>
                    Grade
                    {{ course.grade || "F" }}
                  </span>

                  <b class="status failed">
                    Failed
                  </b>

                </div>

              </div>

            </div>

          </section>


        </template>

      </div>

    </template>
  </StudentLayout>
</template>

<style scoped>
.courses-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.panel {
  background: #fff;
  border: 1px solid #e5e9ef;
  border-radius: 12px;
  padding: 22px;
  box-shadow: 0 2px 8px rgba(10, 25, 48, 0.04);
}

.panel-title {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.panel-title h3 {
  margin: 0;
  color: #123b63;
  font-size: 17px;
}

.panel-title p {
  margin: 4px 0 0;
  color: #718096;
  font-size: 13px;
}

.title-icon,
.course-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #123b63;
  background: #edf4fa;
  border-radius: 9px;
}

.title-icon {
  width: 42px;
  height: 42px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.info-grid > div {
  background: #f8fafc;
  border: 1px solid #edf0f4;
  padding: 13px 15px;
  border-radius: 8px;
}

.info-grid span {
  display: block;
  color: #718096;
  font-size: 12px;
  margin-bottom: 5px;
}

.info-grid strong {
  color: #1a202c;
  font-size: 14px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 15px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 13px;
  background: #fff;
  border: 1px solid #e5e9ef;
  border-radius: 10px;
  padding: 17px;
}

.stat-icon {
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 9px;
}

.stat-icon.active {
  color: #123b63;
  background: #edf4fa;
}

.stat-icon.completed {
  color: #18794e;
  background: #ecfdf3;
}

.stat-icon.failed {
  color: #b42318;
  background: #fff1f1;
}

.stat-icon.total {
  color: #6b4ca5;
  background: #f4effb;
}

.stat-card span {
  display: block;
  color: #718096;
  font-size: 12px;
  margin-bottom: 4px;
}

.stat-card strong {
  color: #123b63;
  font-size: 21px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.section-header h2 {
  margin: 0;
  color: #123b63;
  font-size: 20px;
}

.section-header p {
  margin: 5px 0 0;
  color: #718096;
  font-size: 13px;
}

.refresh-btn {
  border: 1px solid #d7e0e9;
  background: #fff;
  color: #123b63;
  padding: 9px 14px;
  border-radius: 7px;
  display: flex;
  align-items: center;
  gap: 7px;
  cursor: pointer;
}

.refresh-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.course-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.course-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  padding: 15px;
  border: 1px solid #e5e9ef;
  border-radius: 9px;
}

.course-left {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.course-icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
}

.course-left strong {
  color: #123b63;
  font-size: 12px;
}

.course-left h4 {
  margin: 3px 0 0;
  color: #1a202c;
  font-size: 14px;
}

.course-details {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-shrink: 0;
}

.course-details span {
  color: #718096;
  font-size: 12px;
}

.status {
  padding: 5px 9px;
  border-radius: 5px;
  font-size: 11px;
  font-weight: 600;
}

.status.active {
  color: #123b63;
  background: #edf4fa;
}

.status.completed {
  color: #18794e;
  background: #ecfdf3;
}

.status.failed {
  color: #b42318;
  background: #fff1f1;
}

.failed-item {
  border-color: #f0d2d2;
}

.failed-icon {
  color: #b42318;
  background: #fff1f1;
}

.failed-course-icon {
  color: #b42318;
  background: #fff1f1;
}

.alert {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 12px 15px;
  border-radius: 8px;
  font-size: 13px;
}

.alert.error {
  color: #a61b1b;
  background: #fff1f1;
  border: 1px solid #f2cccc;
}

.retry-btn {
  margin-left: auto;
  border: 0;
  background: transparent;
  color: #123b63;
  font-weight: 600;
  cursor: pointer;
}

.loading-box,
.empty-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #718096;
  text-align: center;
}

.loading-box {
  flex-direction: row;
  gap: 10px;
  padding: 55px 20px;
}

.empty-box {
  padding: 35px 20px;
}

.empty-box h3 {
  margin: 10px 0 5px;
  color: #123b63;
  font-size: 16px;
}

.empty-box p {
  margin: 0;
  font-size: 13px;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 1000px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .info-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .course-item {
    align-items: flex-start;
    flex-direction: column;
  }

  .course-details {
    width: 100%;
    flex-wrap: wrap;
  }
}

@media (max-width: 600px) {
  .panel {
    padding: 16px;
  }

  .stats-grid,
  .info-grid {
    grid-template-columns: 1fr;
  }

  .section-header {
    align-items: flex-start;
    gap: 12px;
    flex-direction: column;
  }

  .refresh-btn {
    width: 100%;
    justify-content: center;
  }

  .course-details {
    align-items: flex-start;
    gap: 8px;
    flex-direction: column;
  }
}
</style>