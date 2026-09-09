<script setup lang="ts">
import { computed, onMounted, ref } from "vue";

import {
  BookOpen,
  CheckCircle,
  AlertCircle,
  RefreshCw,
  User,
} from "lucide-vue-next";

import StudentLayout from "@/components/student/StudentLayout.vue";
import api from "@/services/api";

/*  TYPES  */

interface Student {
  id: number;
  user_id: string;
  name: string;
  email: string;
  department: string;
  batch: number;
  section: string | null;
  current_semester: string;
}

interface Prerequisite {
  id: number;
  course_code: string;
  course_name: string;
  passed: boolean;
}

interface Section {
  id: number;
  section_name: string;
  capacity: number;
  available: boolean;
}

interface Course {
  id: number;
  course_code: string;
  course_name: string;
  credit: number;
  prerequisites: Prerequisite[];
  eligible: boolean;
  reason?: string | null;
  already_enrolled?: boolean;
}

interface Enrollment {
  id: number;
  course_id: number;
  semester: string;
  status: string;
  display_status?: string;
  course: Course;
  section?: Section | null;
}

interface Completion {
  completed: boolean;
  total_courses: number;
  passed_courses: number;
  missing_courses: string[];
  failed_courses: string[];
  message: string;
}

interface RegistrationData {
  student: Student;
  registration_semester: string | null;
  courses: Course[];
  sections: Section[];
  enrollments: Enrollment[];
  registration_open: boolean;
  message?: string;
  completion?: Completion;
}

/*  STATE  */

const loading = ref(true);
const registering = ref<number | null>(null);
const refreshing = ref(false);

const data = ref<RegistrationData>({
  student: {
    id: 0,
    user_id: "",
    name: "",
    email: "",
    department: "",
    batch: 0,
    section: null,
    current_semester: "",
  },

  registration_semester: null,
  courses: [],
  sections: [],
  enrollments: [],
  registration_open: true,
});

const selectedSections = ref<Record<number, number | null>>({});

const successMessage = ref("");
const errorMessage = ref("");

/*  COMPUTED  */

const availableCourses = computed(() =>
  data.value.courses.filter(
    (course) => !course.already_enrolled
  )
);

/*  HELPERS  */

function clearMessages() {
  successMessage.value = "";
  errorMessage.value = "";
}

function getError(error: any) {
  return (
    error?.response?.data?.message ||
    error?.response?.data?.error ||
    "Something went wrong. Please try again."
  );
}

/*  LOAD  */

async function loadRegistration() {
  clearMessages();
  loading.value = true;

  try {
    const response = await api.get("/student/registration");

    data.value = response.data;

    selectedSections.value = {};

    data.value.courses.forEach((course) => {
      selectedSections.value[course.id] = null;
    });
  } catch (error) {
    errorMessage.value = getError(error);
  } finally {
    loading.value = false;
  }
}

/*  REGISTER  */

async function registerCourse(course: Course) {
  clearMessages();

  if (!data.value.registration_open) {
    errorMessage.value =
      data.value.message ||
      "Course registration is currently closed.";
    return;
  }

  if (!course.eligible) {
    errorMessage.value =
      course.reason ||
      "You are not eligible for this course.";
    return;
  }

  const sectionId = selectedSections.value[course.id];

  if (!sectionId) {
    errorMessage.value = "Please select a section first.";
    return;
  }

  const section = data.value.sections.find(
    (s) => s.id === sectionId
  );

  if (!section || !section.available) {
    errorMessage.value =
      "Selected section is currently full.";
    return;
  }

  registering.value = course.id;

  try {
    const response = await api.post(
      "/student/registration",
      {
        course_id: course.id,
        section_id: sectionId,
      }
    );

    successMessage.value =
      response.data?.message ||
      "Course registered successfully.";

    await loadRegistration();
  } catch (error) {
    errorMessage.value = getError(error);
  } finally {
    registering.value = null;
  }
}

/*  REFRESH  */

async function refresh() {
  refreshing.value = true;

  try {
    await loadRegistration();
  } finally {
    refreshing.value = false;
  }
}

onMounted(loadRegistration);
</script>

<template>
  <StudentLayout
    title="Course Registration"
    subtitle="Register for courses for your upcoming semester."
  >
    <template #default="{ user }">
      <div class="registration-page">

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
            Loading registration information...
          </span>
        </div>

        <template v-else>

          <!-- ALERTS -->

          <div
            v-if="successMessage"
            class="alert success"
          >
            <CheckCircle :size="20" />
            <span>{{ successMessage }}</span>
          </div>

          <div
            v-if="errorMessage"
            class="alert error"
          >
            <AlertCircle :size="20" />
            <span>{{ errorMessage }}</span>
          </div>


          <!-- STUDENT INFORMATION -->

          <section class="panel student-info">
            <div class="panel-title">
              <div class="title-icon">
                <User :size="20" />
              </div>

              <div>
                <h3>Student Information</h3>

                <p>
                  Your academic information is
                  automatically loaded.
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
                  {{ data.student.batch }}
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
                  {{ data.student.current_semester }}
                </strong>
              </div>

              <div class="registration-semester">
                <span>
                  Registration Semester
                </span>

                <strong>
                  {{
                    data.registration_semester ||
                    "Closed"
                  }}
                </strong>
              </div>

            </div>
          </section>


          <!-- REGISTRATION CLOSED -->

          <section
            v-if="!data.registration_open"
            class="panel closed-box"
          >
            <AlertCircle :size="42" />

            <h3>
              Course Registration Unavailable
            </h3>

            <p>
              {{
                data.message ||
                "Course registration is currently unavailable."
              }}
            </p>


            <!-- COMPLETION DETAILS -->

            <div
              v-if="data.completion"
              class="completion-summary"
            >
              <div>
                <span>
                  Current Semester Courses
                </span>

                <strong>
                  {{ data.completion.total_courses }}
                </strong>
              </div>

              <div>
                <span>
                  Passed Courses
                </span>

                <strong>
                  {{ data.completion.passed_courses }}
                </strong>
              </div>
            </div>


            <div
              v-if="
                data.completion?.failed_courses?.length
              "
              class="completion-reason failed"
            >
              <AlertCircle :size="16" />

              <span>
                Failed:
                {{
                  data.completion.failed_courses.join(
                    ", "
                  )
                }}
              </span>
            </div>


            <div
              v-if="
                data.completion?.missing_courses?.length
              "
              class="completion-reason missing"
            >
              <AlertCircle :size="16" />

              <span>
                Result pending:
                {{
                  data.completion.missing_courses.join(
                    ", "
                  )
                }}
              </span>
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
          </section>


          <!-- AVAILABLE COURSES -->

          <template v-else>

            <div class="section-header">
              <div>
                <h2>Available Courses</h2>

                <p>
                  Courses available for
                  <strong>
                    {{ data.registration_semester }}
                  </strong>
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


            <section class="panel courses-panel">

              <div
                v-if="!availableCourses.length"
                class="empty-box"
              >
                <BookOpen :size="38" />

                <h3>
                  No courses available
                </h3>

                <p>
                  No courses are currently
                  available for registration.
                </p>
              </div>


              <div
                v-else
                class="course-list"
              >

                <div
                  v-for="course in availableCourses"
                  :key="course.id"
                  class="course-card"
                  :class="{
                    blocked:
                      !course.eligible &&
                      !course.already_enrolled,

                    enrolled:
                      course.already_enrolled,
                  }"
                >

                  <div class="course-main">

                    <div class="course-icon">
                      <BookOpen :size="20" />
                    </div>


                    <div class="course-details">

                      <div class="course-code">
                        {{ course.course_code }}
                      </div>

                      <h3>
                        {{ course.course_name }}
                      </h3>

                      <span class="credit">
                        {{ course.credit }} Credit
                      </span>


                      <!-- PREREQUISITES -->

                      <div
                        v-if="
                          course.prerequisites.length
                        "
                        class="prerequisites"
                      >
                        <span class="label">
                          Prerequisite:
                        </span>

                        <span
                          v-for="
                            prereq in
                            course.prerequisites
                          "
                          :key="prereq.id"
                          class="prereq"
                          :class="{
                            passed:
                              prereq.passed,
                          }"
                        >
                          {{ prereq.course_code }}

                          <CheckCircle
                            v-if="prereq.passed"
                            :size="13"
                          />
                        </span>
                      </div>


                      <!-- BLOCK REASON -->

                      <div
                        v-if="
                          !course.eligible &&
                          !course.already_enrolled
                        "
                        class="reason"
                      >
                        <AlertCircle :size="15" />

                        {{
                          course.reason ||
                          "You are not eligible for this course."
                        }}
                      </div>

                    </div>
                  </div>


                  <!-- ACTION -->

                  <div class="course-action">

                    <template v-if="course.already_enrolled">

                      <div class="enrolled-badge">
                        <CheckCircle :size="17" />

                        Already Enrolled
                      </div>

                    </template>


                    <template v-else>

                      <select
                        v-model="
                          selectedSections[course.id]
                        "
                        :disabled="
                          !course.eligible
                        "
                        class="section-select"
                      >
                        <option :value="null">
                          Select Section
                        </option>

                        <option
                          v-for="
                            section in data.sections
                          "
                          :key="section.id"
                          :value="section.id"
                          :disabled="
                            !section.available
                          "
                        >
                          {{ section.section_name }}

                          {{
                            !section.available
                              ? " — Full"
                              : ""
                          }}
                        </option>
                      </select>


                      <button
                        class="register-btn"
                        :disabled="
                          !course.eligible ||
                          registering === course.id
                        "
                        @click="
                          registerCourse(course)
                        "
                      >
                        <RefreshCw
                          v-if="
                            registering === course.id
                          "
                          :size="16"
                          class="spin"
                        />

                        <span v-else>
                          Register
                        </span>
                      </button>

                    </template>

                  </div>

                </div>

              </div>

            </section>

          </template>


          <!-- EXISTING ENROLLMENTS
               IMPORTANT:
               This section is OUTSIDE the registration
               open/closed condition.
          -->

          <section
            v-if="data.enrollments.length"
            class="panel enrolled-panel"
          >

            <div class="panel-title">

              <div class="title-icon">
                <CheckCircle :size="20" />
              </div>

              <div>
                <h3>
                  My Registered Courses
                </h3>

                <p>
                  Courses already registered
                  for this semester.
                </p>
              </div>

            </div>


            <div class="registered-list">

              <div
                v-for="
                  enrollment in data.enrollments
                "
                :key="enrollment.id"
                class="registered-item"
              >

                <div>
                  <strong>
                    {{
                      enrollment.course
                        .course_code
                    }}
                  </strong>

                  <span>
                    {{
                      enrollment.course
                        .course_name
                    }}
                  </span>
                </div>


                <div class="registered-meta">

                  <span>
                    Section
                    {{
                      enrollment.section
                        ?.section_name ||
                      "N/A"
                    }}
                  </span>

                  <b>
                    {{
                      enrollment.display_status ||
                      enrollment.status
                    }}
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
.registration-page {
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

.registration-semester {
  border-color: #cbdced !important;
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
  gap: 12px;
}

.course-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  border: 1px solid #e4e9ef;
  border-radius: 10px;
  padding: 17px;
}

.course-card.blocked {
  background: #fafafa;
}

.course-card.enrolled {
  background: #f7fbf9;
  border-color: #cce5d7;
}

.course-main {
  display: flex;
  gap: 13px;
  min-width: 0;
}

.course-icon {
  flex-shrink: 0;
  width: 42px;
  height: 42px;
}

.course-details {
  min-width: 0;
}

.course-code {
  color: #123b63;
  font-size: 12px;
  font-weight: 700;
}

.course-details h3 {
  margin: 3px 0;
  color: #1a202c;
  font-size: 15px;
}

.credit {
  color: #718096;
  font-size: 12px;
}

.prerequisites {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
  margin-top: 9px;
}

.label {
  color: #718096;
  font-size: 12px;
}

.prereq {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 3px 7px;
  border-radius: 5px;
  background: #fef2f2;
  color: #b42318;
  font-size: 11px;
}

.prereq.passed {
  background: #ecfdf3;
  color: #18794e;
}

.reason {
  display: flex;
  align-items: center;
  gap: 5px;
  color: #b42318;
  font-size: 12px;
  margin-top: 8px;
}

.course-action {
  display: flex;
  align-items: center;
  gap: 9px;
  flex-shrink: 0;
}

.section-select {
  min-width: 145px;
  padding: 9px 10px;
  border: 1px solid #d5dce5;
  border-radius: 7px;
  background: #fff;
  color: #263445;
  outline: none;
}

.section-select:disabled {
  background: #f3f4f6;
  cursor: not-allowed;
}

.register-btn {
  min-width: 82px;
  border: 0;
  border-radius: 7px;
  padding: 10px 13px;
  background: #123b63;
  color: #fff;
  cursor: pointer;
  font-weight: 600;
}

.register-btn:hover:not(:disabled) {
  background: #0d2f50;
}

.register-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.enrolled-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #18794e;
  background: #ecfdf3;
  padding: 9px 12px;
  border-radius: 7px;
  font-size: 12px;
  font-weight: 600;
}

.registered-list {
  display: flex;
  flex-direction: column;
  gap: 9px;
}

.registered-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 13px 15px;
  border: 1px solid #e5e9ef;
  border-radius: 8px;
}

.registered-item strong {
  color: #123b63;
  margin-right: 10px;
}

.registered-item span {
  color: #4a5568;
  font-size: 13px;
}

.registered-meta {
  display: flex;
  align-items: center;
  gap: 15px;
}

.registered-meta b {
  color: #18794e;
  font-size: 12px;
}

.alert {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 12px 15px;
  border-radius: 8px;
  font-size: 13px;
}

.alert.success {
  color: #17663e;
  background: #ecfdf3;
  border: 1px solid #ccebd8;
}

.alert.error {
  color: #a61b1b;
  background: #fff1f1;
  border: 1px solid #f2cccc;
}

.loading-box,
.empty-box,
.closed-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #718096;
  text-align: center;
  padding: 55px 20px;
}

.loading-box {
  flex-direction: row;
  gap: 10px;
}

.closed-box h3,
.empty-box h3 {
  margin: 12px 0 5px;
  color: #123b63;
}

.closed-box p,
.empty-box p {
  max-width: 650px;
  margin: 0;
  font-size: 13px;
  line-height: 1.6;
}

.completion-summary {
  display: flex;
  justify-content: center;
  gap: 12px;
  margin: 20px 0;
  width: 100%;
  max-width: 500px;
}

.completion-summary > div {
  flex: 1;
  background: #f8fafc;
  border: 1px solid #e5e9ef;
  border-radius: 8px;
  padding: 12px;
}

.completion-summary span {
  display: block;
  font-size: 11px;
  color: #718096;
  margin-bottom: 5px;
}

.completion-summary strong {
  color: #123b63;
  font-size: 18px;
}

.completion-reason {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  width: 100%;
  max-width: 650px;
  padding: 10px 13px;
  border-radius: 7px;
  margin-top: 8px;
  font-size: 12px;
}

.completion-reason.failed {
  color: #a61b1b;
  background: #fff1f1;
  border: 1px solid #f2cccc;
}

.completion-reason.missing {
  color: #8a5a00;
  background: #fff8e6;
  border: 1px solid #f1dfaa;
}

.closed-box .refresh-btn {
  margin-top: 18px;
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
  .info-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .course-card {
    align-items: flex-start;
    flex-direction: column;
  }

  .course-action {
    width: 100%;
  }

  .section-select {
    flex: 1;
  }
}

@media (max-width: 600px) {
  .panel {
    padding: 16px;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .section-header {
    align-items: flex-start;
    gap: 12px;
    flex-direction: column;
  }

  .course-action {
    flex-wrap: wrap;
  }

  .section-select {
    width: 100%;
  }

  .register-btn {
    width: 100%;
  }

  .registered-item {
    align-items: flex-start;
    gap: 10px;
    flex-direction: column;
  }

  .registered-meta {
    width: 100%;
    justify-content: space-between;
  }

  .completion-summary {
    flex-direction: column;
  }
}
</style>