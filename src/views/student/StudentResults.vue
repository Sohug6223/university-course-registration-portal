<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import {
  Award,
  BookOpen,
  CheckCircle2,
  ChevronDown,
  ChevronUp,
  GraduationCap,
  RefreshCw,
  XCircle,
} from "lucide-vue-next";

import StudentLayout from "@/components/student/StudentLayout.vue";
import api from "@/services/api";

interface Student {
  id: number;
  student_id: string | null;
  name: string;
  email: string | null;
  department: string | null;
  batch: number | null;
  semester: string | null;
  section: string | null;
}

interface CourseResult {
  id: number;
  course_code: string;
  course_name: string;
  credit: number;
  marks: number | null;
  grade: string | null;
  grade_point: number;
  status: "Passed" | "Failed";
}

interface SemesterResult {
  semester: string;
  gpa: number;
  credits: number;
  courses: CourseResult[];
}

interface ResultData {
  student: Student;
  summary: {
    cgpa: number;
    total_credits: number;
    earned_credits: number;
    total_courses: number;
    passed_courses: number;
    failed_courses: number;
  };
  semesters: SemesterResult[];
}

const loading = ref(true);
const refreshing = ref(false);
const error = ref("");
const data = ref<ResultData | null>(null);

const expandedSemesters = ref<string[]>([]);

const semesterOrder = [
  "1st",
  "2nd",
  "3rd",
  "4th",
  "5th",
  "6th",
  "7th",
  "8th",
];

const sortedSemesters = computed(() => {
  if (!data.value) return [];

  return [...data.value.semesters].sort(
    (a, b) =>
      semesterOrder.indexOf(a.semester) -
      semesterOrder.indexOf(b.semester)
  );
});

const loadResults = async () => {
  try {
    error.value = "";

    const response = await api.get<ResultData>("/student/results");
    data.value = response.data;

    if (
      expandedSemesters.value.length === 0 &&
      data.value.semesters.length > 0
    ) {
      const firstSemester = data.value.semesters[0];

      if (firstSemester) {
        expandedSemesters.value = [firstSemester.semester];
      }
    }
  } catch (err: any) {
    error.value =
      err?.response?.data?.message ||
      "Failed to load your results.";
  } finally {
    loading.value = false;
    refreshing.value = false;
  }
};

const refreshResults = async () => {
  refreshing.value = true;
  await loadResults();
};

const toggleSemester = (semester: string) => {
  if (expandedSemesters.value.includes(semester)) {
    expandedSemesters.value = expandedSemesters.value.filter(
      (item) => item !== semester
    );
  } else {
    expandedSemesters.value.push(semester);
  }
};

const isExpanded = (semester: string) =>
  expandedSemesters.value.includes(semester);

const formatNumber = (value: number) =>
  Number(value).toFixed(2).replace(/\.00$/, "");

onMounted(loadResults);
</script>

<template>
  <StudentLayout title="Results" subtitle="View your semester-wise academic performance and CGPA.">
    <div class="results-page">

      <!-- Loading -->
      <div v-if="loading" class="state-box">
        <RefreshCw :size="28" class="spin" />
        <p>Loading your results...</p>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="state-box error-state">
        <XCircle :size="32" />
        <h3>Unable to load results</h3>
        <p>{{ error }}</p>

        <button class="btn-primary" @click="refreshResults">
          <RefreshCw :size="17" />
          Try Again
        </button>
      </div>

      <template v-else-if="data">

        <!-- Student Information -->
        <section class="panel student-panel">
          <div class="panel-heading">
            <div class="heading-left">
              <div class="heading-icon">
                <GraduationCap :size="21" />
              </div>

              <div>
                <h2>Student Information</h2>
                <p>Academic profile</p>
              </div>
            </div>

            <button class="refresh-btn" title="Refresh results" @click="refreshResults" :disabled="refreshing">
              <RefreshCw :size="18" :class="{ spin: refreshing }" />
            </button>
          </div>

          <div class="student-info">
            <div>
              <span>Student ID</span>
              <strong>{{ data.student.student_id || "N/A" }}</strong>
            </div>

            <div>
              <span>Name</span>
              <strong>{{ data.student.name }}</strong>
            </div>

            <div>
              <span>Department</span>
              <strong>{{ data.student.department || "N/A" }}</strong>
            </div>

            <div>
              <span>Batch</span>
              <strong>{{ data.student.batch || "N/A" }}</strong>
            </div>

            <div>
              <span>Current Semester</span>
              <strong>{{ data.student.semester || "N/A" }}</strong>
            </div>

            <div>
              <span>Section</span>
              <strong>{{ data.student.section || "N/A" }}</strong>
            </div>
          </div>
        </section>

        <!-- Overall Summary -->
        <section class="summary-grid">

          <div class="summary-card cgpa-card">
            <div class="summary-icon">
              <Award :size="23" />
            </div>

            <div>
              <span>Overall CGPA</span>
              <strong>{{ data.summary.cgpa.toFixed(2) }}</strong>
            </div>
          </div>

          <div class="summary-card">
            <div class="summary-icon">
              <BookOpen :size="23" />
            </div>

            <div>
              <span>Earned Credits</span>
              <strong>{{ formatNumber(data.summary.earned_credits) }}</strong>
            </div>
          </div>

          <div class="summary-card">
            <div class="summary-icon">
              <GraduationCap :size="23" />
            </div>

            <div>
              <span>Total Courses</span>
              <strong>{{ data.summary.total_courses }}</strong>
            </div>
          </div>

          <div class="summary-card">
            <div class="summary-icon">
              <CheckCircle2 :size="23" />
            </div>

            <div>
              <span>Passed Courses</span>
              <strong>{{ data.summary.passed_courses }}</strong>
            </div>
          </div>

          <div class="summary-card failed-card">
            <div class="summary-icon">
              <XCircle :size="23" />
            </div>

            <div>
              <span>Failed Courses</span>
              <strong>{{ data.summary.failed_courses }}</strong>
            </div>
          </div>

          <div class="summary-card">
            <div class="summary-icon">
              <BookOpen :size="23" />
            </div>

            <div>
              <span>Total Credits</span>
              <strong>{{ formatNumber(data.summary.total_credits) }}</strong>
            </div>
          </div>

        </section>

        <!-- Semester Results -->
        <section class="panel semester-panel">
          <div class="panel-heading">
            <div class="heading-left">
              <div class="heading-icon">
                <BookOpen :size="21" />
              </div>

              <div>
                <h2>Semester-wise Results</h2>
                <p>Your academic performance by semester</p>
              </div>
            </div>
          </div>

          <div v-if="sortedSemesters.length" class="semester-list">

            <div v-for="semester in sortedSemesters" :key="semester.semester" class="semester-card">

              <!-- Semester Header -->
              <button class="semester-header" @click="toggleSemester(semester.semester)">
                <div class="semester-title">
                  <div class="semester-number">
                    {{ semester.semester }}
                  </div>

                  <div>
                    <h3>{{ semester.semester }} Semester</h3>
                    <p>
                      {{ semester.courses.length }}
                      {{ semester.courses.length === 1 ? "Course" : "Courses" }}
                    </p>
                  </div>
                </div>

                <div class="semester-right">
                  <div class="gpa-box">
                    <span>GPA</span>
                    <strong>{{ semester.gpa.toFixed(2) }}</strong>
                  </div>

                  <div class="credit-box">
                    <span>Credits</span>
                    <strong>{{ formatNumber(semester.credits) }}</strong>
                  </div>

                  <ChevronUp v-if="isExpanded(semester.semester)" :size="20" />

                  <ChevronDown v-else :size="20" />
                </div>
              </button>

              <!-- Course Table -->
              <div v-if="isExpanded(semester.semester)" class="semester-content">
                <div class="table-wrapper">
                  <table class="results-table">
                    <thead>
                      <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Credit</th>
                        <th>Marks</th>
                        <th>Grade</th>
                        <th>Point</th>
                        <th>Status</th>
                      </tr>
                    </thead>

                    <tbody>
                      <tr v-for="course in semester.courses" :key="course.id">
                        <td>
                          <strong>{{ course.course_code }}</strong>
                        </td>

                        <td>{{ course.course_name }}</td>

                        <td>{{ formatNumber(course.credit) }}</td>

                        <td>
                          {{
                            course.marks !== null
                              ? course.marks
                              : "—"
                          }}
                        </td>

                        <td>
                          <span class="grade" :class="{
                            failed: course.status === 'Failed',
                          }">
                            {{ course.grade || "—" }}
                          </span>
                        </td>

                        <td>
                          {{ course.grade_point.toFixed(2) }}
                        </td>

                        <td>
                          <span class="status-badge" :class="{
                            passed: course.status === 'Passed',
                            failed: course.status === 'Failed',
                          }">
                            <CheckCircle2 v-if="course.status === 'Passed'" :size="14" />

                            <XCircle v-else :size="14" />

                            {{ course.status }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

            </div>

          </div>

          <div v-else class="empty-box">
            <Award :size="42" />
            <h3>No Results Available</h3>
            <p>Your results have not been published yet.</p>
          </div>
        </section>

        <!-- CGPA Information -->
        <section class="panel cgpa-panel">
          <div class="cgpa-summary">
            <div class="cgpa-main">
              <span>Overall CGPA</span>
              <strong>{{ data.summary.cgpa.toFixed(2) }}</strong>
              <small>Based on completed/passed courses</small>
            </div>

            <div class="cgpa-details">
              <div>
                <span>Total Credits</span>
                <strong>
                  {{ formatNumber(data.summary.total_credits) }}
                </strong>
              </div>

              <div>
                <span>Earned Credits</span>
                <strong>
                  {{ formatNumber(data.summary.earned_credits) }}
                </strong>
              </div>

              <div>
                <span>Passed</span>
                <strong>{{ data.summary.passed_courses }}</strong>
              </div>

              <div>
                <span>Failed</span>
                <strong>{{ data.summary.failed_courses }}</strong>
              </div>
            </div>
          </div>
        </section>

      </template>
    </div>
  </StudentLayout>
</template>

<style scoped>
.results-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/*  PANEL  */

.panel {
  background: #fff;
  border: 1px solid #e5eaf0;
  border-radius: 14px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
  overflow: hidden;
}

.panel-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 22px;
  border-bottom: 1px solid #edf1f5;
}

.heading-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.heading-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: #eaf2f9;
  color: #123b63;
  display: flex;
  align-items: center;
  justify-content: center;
}

.panel-heading h2 {
  margin: 0;
  color: #16283b;
  font-size: 17px;
  font-weight: 700;
}

.panel-heading p {
  margin: 4px 0 0;
  color: #7a8795;
  font-size: 13px;
}

.refresh-btn {
  width: 38px;
  height: 38px;
  border: 1px solid #dce4ec;
  background: #fff;
  color: #123b63;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.refresh-btn:hover {
  background: #f2f6fa;
}

/*  STUDENT INFO  */

.student-info {
  padding: 20px 22px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px 24px;
}

.student-info div {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.student-info span {
  color: #8793a0;
  font-size: 12px;
  font-weight: 600;
}

.student-info strong {
  color: #1c2d3f;
  font-size: 14px;
}

/*  SUMMARY  */

.summary-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.summary-card {
  min-height: 105px;
  background: #fff;
  border: 1px solid #e5eaf0;
  border-radius: 14px;
  padding: 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.summary-icon {
  width: 45px;
  height: 45px;
  flex-shrink: 0;
  border-radius: 11px;
  background: #eaf2f9;
  color: #123b63;
  display: flex;
  align-items: center;
  justify-content: center;
}

.summary-card span {
  display: block;
  color: #7d8996;
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 5px;
}

.summary-card strong {
  display: block;
  color: #16283b;
  font-size: 24px;
  font-weight: 750;
}

.failed-card .summary-icon {
  background: #fdf0f0;
  color: #c44747;
}

.failed-card strong {
  color: #c44747;
}

/*  SEMESTER  */

.semester-list {
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.semester-card {
  border: 1px solid #e3e9ef;
  border-radius: 12px;
  overflow: hidden;
}

.semester-header {
  width: 100%;
  border: 0;
  background: #f9fbfd;
  padding: 14px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  color: #34475a;
  text-align: left;
}

.semester-header:hover {
  background: #f4f8fb;
}

.semester-title {
  display: flex;
  align-items: center;
  gap: 12px;
}

.semester-number {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: #123b63;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
}

.semester-title h3 {
  margin: 0;
  color: #1b3045;
  font-size: 15px;
}

.semester-title p {
  margin: 4px 0 0;
  color: #8a96a3;
  font-size: 12px;
}

.semester-right {
  display: flex;
  align-items: center;
  gap: 20px;
}

.gpa-box,
.credit-box {
  min-width: 70px;
  text-align: center;
}

.gpa-box span,
.credit-box span {
  display: block;
  color: #8a96a3;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
}

.gpa-box strong {
  color: #123b63;
  font-size: 20px;
}

.credit-box strong {
  color: #27394b;
  font-size: 16px;
}

.semester-content {
  border-top: 1px solid #e5eaf0;
}

/*  TABLE  */

.table-wrapper {
  width: 100%;
  overflow-x: auto;
}

.results-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 850px;
}

.results-table th {
  padding: 12px 14px;
  background: #f8fafc;
  color: #718092;
  text-align: left;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  border-bottom: 1px solid #e5eaf0;
}

.results-table td {
  padding: 13px 14px;
  color: #425366;
  font-size: 13px;
  border-bottom: 1px solid #edf1f5;
}

.results-table tbody tr:last-child td {
  border-bottom: 0;
}

.results-table tbody tr:hover {
  background: #fbfcfd;
}

.results-table td strong {
  color: #123b63;
}

.grade {
  display: inline-flex;
  min-width: 38px;
  justify-content: center;
  padding: 4px 8px;
  border-radius: 6px;
  background: #edf5ef;
  color: #2d7448;
  font-weight: 700;
}

.grade.failed {
  background: #fdf0f0;
  color: #c44747;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 9px;
  border-radius: 7px;
  font-size: 11px;
  font-weight: 700;
}

.status-badge.passed {
  background: #edf7f0;
  color: #2f7548;
}

.status-badge.failed {
  background: #fdf0f0;
  color: #c44747;
}

/*  CGPA  */

.cgpa-summary {
  padding: 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 30px;
}

.cgpa-main {
  min-width: 220px;
}

.cgpa-main span {
  display: block;
  color: #7d8996;
  font-size: 13px;
  font-weight: 600;
}

.cgpa-main strong {
  display: block;
  margin: 4px 0;
  color: #123b63;
  font-size: 42px;
  line-height: 1;
}

.cgpa-main small {
  color: #8a96a3;
  font-size: 11px;
}

.cgpa-details {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  flex: 1;
}

.cgpa-details div {
  padding: 0 22px;
  border-left: 1px solid #e5eaf0;
}

.cgpa-details span {
  display: block;
  color: #8995a2;
  font-size: 11px;
  margin-bottom: 6px;
}

.cgpa-details strong {
  color: #26394c;
  font-size: 18px;
}

/*  STATES  */

.state-box,
.empty-box {
  min-height: 280px;
  background: #fff;
  border: 1px solid #e5eaf0;
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: #7d8996;
  padding: 30px;
}

.state-box p,
.empty-box p {
  margin: 8px 0 0;
  font-size: 13px;
}

.state-box h3,
.empty-box h3 {
  margin: 12px 0 0;
  color: #26394c;
  font-size: 17px;
}

.error-state {
  color: #c44747;
}

.error-state h3 {
  color: #c44747;
}

.btn-primary {
  margin-top: 18px;
  border: 0;
  background: #123b63;
  color: #fff;
  border-radius: 8px;
  padding: 10px 16px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.btn-primary:hover {
  background: #0e3152;
}

.spin {
  animation: spin 0.9s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/*  RESPONSIVE  */

@media (max-width: 1100px) {
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .student-info {
    grid-template-columns: repeat(2, 1fr);
  }

  .cgpa-summary {
    flex-direction: column;
    align-items: stretch;
  }

  .cgpa-details {
    width: 100%;
  }
}

@media (max-width: 700px) {
  .summary-grid {
    grid-template-columns: 1fr;
  }

  .student-info {
    grid-template-columns: 1fr;
  }

  .panel-heading {
    padding: 16px;
  }

  .student-info {
    padding: 16px;
  }

  .semester-list {
    padding: 12px;
  }

  .semester-header {
    padding: 12px;
  }

  .semester-right {
    gap: 8px;
  }

  .credit-box {
    display: none;
  }

  .cgpa-summary {
    padding: 18px;
  }

  .cgpa-details {
    grid-template-columns: repeat(2, 1fr);
    gap: 18px 0;
  }

  .cgpa-details div {
    padding: 0 12px;
  }

  .cgpa-details div:nth-child(3) {
    border-left: 0;
  }
}
</style>