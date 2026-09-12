<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import {
  Plus,
  Search,
  Edit2,
  Trash2,
  X,
  GraduationCap,
  BookOpen,
  Award,
  ChevronDown,
  ChevronUp,
  Filter,
  RotateCcw,
} from "lucide-vue-next";
import AdminLayout from "@/components/admin/AdminLayout.vue";
import api from "@/services/api";

/*  TYPES  */

interface User {
  id: number;
  user_id: string;
  name: string;
  email?: string;
}

interface Batch {
  id: number;
  batch_number: number;
  current_semester: string;
}

interface Student {
  id: number;
  department: string;
  semester: string;
  status: string;
  user: User;
  batch?: Batch;
}

interface Course {
  id: number;
  course_code: string;
  course_name: string;
  department: string;
  semester: string;
  credit: string | number;
  course_type?: string;
  status?: string;
}
interface Department {
  id: number;
  code: string;
  name: string;
  status: "Active" | "Inactive";
  student_count: number;
  batch_count: number;
  course_count: number;
}
interface Result {
  id: number;
  student_id: number;
  course_id: number;
  semester: string;
  marks: number;
  grade: string;
  grade_point: string | number;
  status: string;
  student: Student;
  course: Course;
}

interface CourseMark {
  course: Course;
  marks: string;
}

interface SemesterSummary {
  semester: string;
  gpa: number;
  credits: number;
  results: Result[];
}

interface Summary {
  student: {
    student_id: string;
    name: string;
    department: string;
    batch_number?: number;
  };
  cgpa: number;
  total_credits: number;
  semesters: SemesterSummary[];
}

/*  CONSTANTS  */

const semesters = [
  "1st",
  "2nd",
  "3rd",
  "4th",
  "5th",
  "6th",
  "7th",
  "8th",
];

const departments = ref<Department[]>([]);
/*  STATE  */

const results = ref<Result[]>([]);
const students = ref<Student[]>([]);
const courses = ref<Course[]>([]);

const loading = ref(false);
const saving = ref(false);

/* Management */
const managementSearch = ref("");
const filterDepartment = ref("");
const filterSemester = ref("");
const hasSearchedResults = ref(false);

/* Summary */
const summarySearch = ref("");
const summaryLoading = ref(false);
const summarySearchError = ref("");
const summarySearchErrorTitle = ref("");
const summary = ref<Summary | null>(null);
const expandedSemester = ref<string | null>(null);

/* Modal */
const showModal = ref(false);
const editingId = ref<number | null>(null);

/* Add */
const selectedStudent = ref<Student | null>(null);

const form = ref({
  student_id: "",
  department: "",
  semester: "",
});

const courseMarks = ref<CourseMark[]>([]);
const singleCourseMode = ref(false);

/* Edit */
const editForm = ref({
  course_id: "",
  course_name: "",
  course_code: "",
  semester: "",
  marks: "",
});

/* Errors */
const errors = ref<Record<string, string[]>>({});

/* Delete */
const showDeleteModal = ref(false);
const deleting = ref(false);
const resultToDelete = ref<Result | null>(null);

/*  COMPUTED  */

const hasManagementFilter = computed(() =>
  Boolean(
    managementSearch.value.trim() ||
    filterDepartment.value ||
    filterSemester.value
  )
);

/*  HELPERS  */

function gradeClass(grade: string) {
  if (grade === "F") return "grade-f";
  if (grade.startsWith("A")) return "grade-a";
  if (grade.startsWith("B")) return "grade-b";
  return "grade-c";
}

function resetErrors() {
  errors.value = {};
}

function errorMessage(error: any, fallback = "Something went wrong.") {
  return error?.response?.data?.message || fallback;
}

/*  STUDENTS  */

async function loadStudents() {
  try {
    const response = await api.get("/admin/students");

    students.value =
      response.data.students ?? response.data.data ?? [];
  } catch (error) {
    console.error("Failed to load students:", error);
  }
}
/*  DEPARTMENTS  */

async function loadDepartments() {
  try {
    const response = await api.get("/admin/departments");

    departments.value =
      response.data.departments ??
      response.data.data ??
      [];
  } catch (error) {
    console.error("Failed to load departments:", error);
    departments.value = [];
  }
}

function findStudentById() {
  const id = form.value.student_id.trim().toLowerCase();

  if (!id) {
    selectedStudent.value = null;
    form.value.department = "";
    form.value.semester = "";
    courses.value = [];
    courseMarks.value = [];
    errors.value.student_id = ["Please enter a Student ID."];
    return;
  }

  const student = students.value.find(
    (item) => item.user?.user_id?.toLowerCase() === id
  );

  if (!student) {
    selectedStudent.value = null;
    form.value.department = "";
    form.value.semester = "";
    courses.value = [];
    courseMarks.value = [];
    errors.value.student_id = ["Student not found."];
    return;
  }

  selectedStudent.value = student;
  form.value.department = student.department;
  form.value.semester = "";
  courses.value = [];
  courseMarks.value = [];

  resetErrors();
}

/*  COURSES  */

async function loadCourses() {
  if (!form.value.department || !form.value.semester) {
    courses.value = [];
    return;
  }

  try {
    const response = await api.get("/admin/courses", {
      params: {
        department: form.value.department,
        semester: form.value.semester,
      },
    });

    courses.value =
      response.data.courses ?? response.data.data ?? [];
  } catch (error) {
    console.error("Failed to load courses:", error);
    courses.value = [];
  }
}

async function handleSemesterChange() {
  courseMarks.value = [];
  singleCourseMode.value = false;

  if (!form.value.department || !form.value.semester) {
    courses.value = [];
    return;
  }

  await loadCourses();

  courseMarks.value = courses.value.map((course) => ({
    course,
    marks: "",
  }));
}

function switchToSingleCourseMode() {
  singleCourseMode.value = true;
}

function switchToAllCoursesMode() {
  singleCourseMode.value = false;
}

/*  RESULT MANAGEMENT  */

async function searchManagementResults() {
  if (!hasManagementFilter.value) {
    results.value = [];
    hasSearchedResults.value = false;
    return;
  }

  loading.value = true;
  hasSearchedResults.value = true;

  try {
    const params: Record<string, string> = {};

    if (managementSearch.value.trim()) {
      params.search = managementSearch.value.trim();
    }

    if (filterDepartment.value) {
      params.department = filterDepartment.value;
    }

    if (filterSemester.value) {
      params.semester = filterSemester.value;
    }

    const response = await api.get("/admin/results", { params });

    results.value = response.data.results ?? [];
  } catch (error) {
    console.error("Failed to load results:", error);
    results.value = [];
  } finally {
    loading.value = false;
  }
}

function clearManagementFilters() {
  managementSearch.value = "";
  filterDepartment.value = "";
  filterSemester.value = "";
  results.value = [];
  hasSearchedResults.value = false;
}

/*  STUDENT RESULT SUMMARY  */

/*
  Summary uses:
  /admin/results?search=StudentID
 
  First checks whether the Student ID exists.
  Then checks whether that student has results.
  Finally calculates GPA and CGPA.
 */

async function searchStudentResult() {
  const id = summarySearch.value.trim();

  if (!id) {
    summary.value = null;
    summarySearchError.value = "";
    summarySearchErrorTitle.value = "";
    expandedSemester.value = null;
    return;
  }

  summaryLoading.value = true;
  summary.value = null;
  summarySearchError.value = "";
  summarySearchErrorTitle.value = "";
  expandedSemester.value = null;

  try {
    /*  CHECK STUDENT  */

    const student = students.value.find(
      (item) =>
        item.user?.user_id?.toLowerCase() === id.toLowerCase()
    );

    if (!student) {
      summarySearchErrorTitle.value = "Student not found";

      summarySearchError.value =
        "Student not found. Please check the Student ID and try again.";

      return;
    }

    /*  LOAD RESULTS  */

    const response = await api.get("/admin/results", {
      params: {
        search: id,
      },
    });

    const foundResults: Result[] =
      response.data.results ?? [];

    /*  EXACT STUDENT MATCH  */

    const studentResults = foundResults.filter(
      (result) =>
        result.student?.user?.user_id?.toLowerCase() ===
        id.toLowerCase()
    );

    /*  NO RESULT  */

    if (!studentResults.length) {
      summarySearchErrorTitle.value = "Result not found";

      summarySearchError.value =
        "No academic result has been added for this student yet.";

      return;
    }

    /*  GROUP BY SEMESTER  */

    const grouped = new Map<string, Result[]>();

    for (const result of studentResults) {
      if (!grouped.has(result.semester)) {
        grouped.set(result.semester, []);
      }

      grouped.get(result.semester)!.push(result);
    }

    const semesterData: SemesterSummary[] = [];

    for (const [semester, semesterResults] of grouped) {
      let credits = 0;
      let qualityPoints = 0;

      for (const result of semesterResults) {
        const credit = Number(result.course?.credit ?? 0);
        const gp = Number(result.grade_point ?? 0);

        credits += credit;
        qualityPoints += credit * gp;
      }

      const gpa =
        credits > 0
          ? Number((qualityPoints / credits).toFixed(2))
          : 0;

      semesterData.push({
        semester,
        gpa,
        credits: Number(credits.toFixed(2)),
        results: semesterResults,
      });
    }

    /*  SORT SEMESTERS  */

    semesterData.sort(
      (a, b) =>
        Number(a.semester[0]) -
        Number(b.semester[0])
    );

    /*  CGPA  */

    let totalCredits = 0;
    let totalQualityPoints = 0;

    for (const result of studentResults) {
      const credit = Number(result.course?.credit ?? 0);
      const gp = Number(result.grade_point ?? 0);

      totalCredits += credit;
      totalQualityPoints += credit * gp;
    }

    const cgpa =
      totalCredits > 0
        ? Number(
          (totalQualityPoints / totalCredits).toFixed(2)
        )
        : 0;

    /*  SUMMARY  */

    summary.value = {
      student: {
        student_id: student.user.user_id,
        name: student.user.name,
        department: student.department,
        batch_number: student.batch?.batch_number,
      },

      cgpa,

      total_credits: Number(
        totalCredits.toFixed(2)
      ),

      semesters: semesterData,
    };
  } catch (error) {
    console.error(
      "Failed to load student summary:",
      error
    );

    summarySearchErrorTitle.value =
      "Unable to load result";

    summarySearchError.value = errorMessage(
      error,
      "Failed to load student result. Please try again."
    );
  } finally {
    summaryLoading.value = false;
  }
}

function clearSummaryError() {
  summarySearchError.value = "";
  summarySearchErrorTitle.value = "";
}

function toggleSemester(semester: string) {
  expandedSemester.value =
    expandedSemester.value === semester
      ? null
      : semester;
}

/*  ADD MODAL  */

function openAddModal() {
  editingId.value = null;

  form.value = {
    student_id: "",
    department: "",
    semester: "",
  };

  selectedStudent.value = null;
  courses.value = [];
  courseMarks.value = [];
  singleCourseMode.value = false;

  editForm.value = {
    course_id: "",
    course_name: "",
    course_code: "",
    semester: "",
    marks: "",
  };

  resetErrors();
  showModal.value = true;
}

/*  EDIT MODAL  */

function openEditModal(result: Result) {
  editingId.value = result.id;
  selectedStudent.value = result.student;

  form.value = {
    student_id:
      result.student.user?.user_id ?? "",
    department: result.student.department,
    semester: result.semester,
  };

  editForm.value = {
    course_id: String(result.course_id),
    course_name:
      result.course?.course_name ?? "",
    course_code:
      result.course?.course_code ?? "",
    semester: result.semester,
    marks: String(result.marks),
  };

  resetErrors();
  showModal.value = true;
}

function closeModal() {
  if (saving.value) return;

  showModal.value = false;
  editingId.value = null;
  resetErrors();
}

/*  SAVE RESULT  */

async function saveResult() {
  resetErrors();

  if (!selectedStudent.value) {
    errors.value.student_id = [
      "Please enter a valid Student ID.",
    ];
    return;
  }

  if (!form.value.semester) {
    errors.value.semester = [
      "Please select a semester.",
    ];
    return;
  }

  saving.value = true;

  try {
    /*  EDIT  */

    if (editingId.value) {
      await api.put(
        `/admin/results/${editingId.value}`,
        {
          student_id: selectedStudent.value.id,
          course_id: Number(
            editForm.value.course_id
          ),
          semester: editForm.value.semester,
          marks: Number(editForm.value.marks),
        }
      );

      const studentId =
        selectedStudent.value.user.user_id;

      closeModal();

      if (hasManagementFilter.value) {
        await searchManagementResults();
      }

      if (
        summary.value?.student.student_id ===
        studentId
      ) {
        await searchStudentResult();
      }

      return;
    }

    /*  ADD SINGLE  */

    if (singleCourseMode.value) {
      const selectedCourse =
        courseMarks.value.find(
          (item) => item.marks !== ""
        );

      if (!selectedCourse) {
        errors.value.course_id = [
          "Please enter marks for a course.",
        ];
        return;
      }

      await api.post("/admin/results", {
        student_id: selectedStudent.value.id,
        course_id: selectedCourse.course.id,
        semester: form.value.semester,
        marks: Number(selectedCourse.marks),
      });

      closeModal();

      if (hasManagementFilter.value) {
        await searchManagementResults();
      }

      return;
    }

    /*  ADD MULTIPLE  */

    const filled = courseMarks.value.filter(
      (item) => item.marks !== ""
    );

    if (!filled.length) {
      errors.value.marks = [
        "Please enter marks for at least one course.",
      ];
      return;
    }

    for (const item of filled) {
      await api.post("/admin/results", {
        student_id: selectedStudent.value.id,
        course_id: item.course.id,
        semester: form.value.semester,
        marks: Number(item.marks),
      });
    }

    closeModal();

    if (hasManagementFilter.value) {
      await searchManagementResults();
    }
  } catch (error: any) {
    if (error.response?.status === 422) {
      errors.value =
        error.response.data.errors ?? {};

      if (
        error.response.data.message &&
        !Object.keys(errors.value).length
      ) {
        errors.value.marks = [
          error.response.data.message,
        ];
      }
    } else {
      console.error(
        "Failed to save result:",
        error
      );
    }
  } finally {
    saving.value = false;
  }
}

/*  DELETE  */

function deleteResult(result: Result) {
  resultToDelete.value = result;
  showDeleteModal.value = true;
}

function closeDeleteModal() {
  if (deleting.value) return;

  showDeleteModal.value = false;
  resultToDelete.value = null;
}

async function confirmDelete() {
  if (!resultToDelete.value) return;

  deleting.value = true;

  const deletedStudentId =
    resultToDelete.value.student?.user?.user_id;

  try {
    await api.delete(
      `/admin/results/${resultToDelete.value.id}`
    );

    showDeleteModal.value = false;
    resultToDelete.value = null;

    if (hasManagementFilter.value) {
      await searchManagementResults();
    } else {
      results.value = [];
    }

    if (
      summary.value &&
      summary.value.student.student_id ===
      deletedStudentId
    ) {
      await searchStudentResult();
    }
  } catch (error) {
    console.error(
      "Failed to delete result:",
      error
    );
  } finally {
    deleting.value = false;
  }
}

/*  LIFECYCLE  */

onMounted(() => {
  loadStudents();
  loadDepartments();
});
</script>

<template>
  <AdminLayout title="Results" subtitle="Manage student academic results and performance.">
    <div class="results-page">

      <!--  SUMMARY SEARCH  -->

      <section class="summary-search-panel">
        <div class="section-heading">
          <div class="heading-icon">
            <GraduationCap :size="21" />
          </div>

          <div>
            <h2>Student Result Summary</h2>

            <p>
              Search by Student ID to view semester GPA and overall CGPA.
            </p>
          </div>
        </div>

        <div class="student-search">
          <div class="search-input" :class="{ 'search-error': summarySearchError }">
            <Search :size="18" />

            <input v-model="summarySearch" type="text" placeholder="Enter Student ID" @input="clearSummaryError"
              @keyup.enter="searchStudentResult" />
          </div>

          <button class="btn-primary" :disabled="summaryLoading" @click="searchStudentResult">
            <Search :size="17" />

            {{
              summaryLoading
                ? "Searching..."
                : "Search"
            }}
          </button>
        </div>

        <div v-if="summarySearchError" class="inline-error">
          <span class="error-dot"></span>

          <div>
            <strong>
              {{ summarySearchErrorTitle }}
            </strong>

            <p>
              {{ summarySearchError }}
            </p>
          </div>
        </div>
      </section>

      <!--  STUDENT SUMMARY  -->

      <section v-if="summary" class="student-summary">
        <div class="student-info-card">
          <div class="student-avatar">
            <GraduationCap :size="26" />
          </div>

          <div>
            <h2>
              {{ summary.student.name }}
            </h2>

            <p>
              {{ summary.student.student_id }}

              <span>•</span>

              {{ summary.student.department }}

              <template v-if="summary.student.batch_number">
                <span>•</span>

                Batch
                {{ summary.student.batch_number }}
              </template>
            </p>
          </div>
        </div>

        <div class="summary-cards">
          <div class="summary-card">
            <div class="summary-card-icon">
              <Award :size="20" />
            </div>

            <div>
              <span>Overall CGPA</span>

              <strong>
                {{ summary.cgpa.toFixed(2) }}
              </strong>
            </div>
          </div>

          <div class="summary-card">
            <div class="summary-card-icon">
              <BookOpen :size="20" />
            </div>

            <div>
              <span>Completed Credits</span>

              <strong>
                {{ summary.total_credits.toFixed(2) }}
              </strong>
            </div>
          </div>
        </div>

        <div class="semester-summary">
          <div class="panel-header">
            <div>
              <h3>Semester Performance</h3>

              <p>
                GPA and course-wise academic performance.
              </p>
            </div>
          </div>

          <div v-for="semester in summary.semesters" :key="semester.semester" class="semester-block">
            <button class="semester-header" @click="
              toggleSemester(semester.semester)
              ">
              <div>
                <strong>
                  {{ semester.semester }} Semester
                </strong>

                <span>
                  {{ semester.credits.toFixed(2) }}
                  Credits
                </span>
              </div>

              <div class="semester-right">
                <b>
                  GPA
                  {{ semester.gpa.toFixed(2) }}
                </b>

                <ChevronUp v-if="
                  expandedSemester ===
                  semester.semester
                " :size="19" />

                <ChevronDown v-else :size="19" />
              </div>
            </button>

            <div v-if="
              expandedSemester ===
              semester.semester
            " class="semester-results">
              <div v-for="result in semester.results" :key="result.id" class="semester-result-row">
                <div>
                  <strong>
                    {{ result.course.course_code }}
                  </strong>

                  <span>
                    {{ result.course.course_name }}
                  </span>
                </div>

                <div class="result-mark">
                  {{ result.marks }}
                </div>

                <div :class="[
                  'grade-badge',
                  gradeClass(result.grade),
                ]">
                  {{ result.grade }}
                </div>

                <div class="gp-value">
                  {{
                    Number(
                      result.grade_point
                    ).toFixed(2)
                  }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!--  RESULT MANAGEMENT  -->

      <section class="management-section">
        <div class="page-heading">
          <div>
            <h2>Result Management</h2>

            <p>
              Search and manage student academic results.
            </p>
          </div>

          <button class="btn-primary" @click="openAddModal">
            <Plus :size="18" />
            Add Result
          </button>
        </div>

        <div class="toolbar-panel">
          <div class="filter-title">
            <Filter :size="17" />

            <span>Filter Results</span>
          </div>

          <div class="search-filter-box">
            <Search :size="18" />

            <input v-model="managementSearch" type="text" placeholder="Student ID, student name or course..."
              @keyup.enter="searchManagementResults" />
          </div>

          <select v-model="filterDepartment">
            <option value="">
              All Departments
            </option>

            <option v-for="department in departments" :key="department.id" :value="department.code">
              {{ department.code }}
            </option>
          </select>
          <select v-model="filterSemester">
            <option value="">
              All Semesters
            </option>

            <option v-for="semester in semesters" :key="semester" :value="semester">
              {{ semester }}
            </option>
          </select>

          <button class="filter-search-btn" :disabled="loading" @click="searchManagementResults">
            <Search :size="16" />

            {{
              loading
                ? "Searching..."
                : "Search"
            }}
          </button>

          <button class="clear-filter-btn" title="Clear filters" @click="clearManagementFilters">
            <RotateCcw :size="16" />
          </button>
        </div>

        <div class="panel table-panel">
          <div v-if="!hasSearchedResults" class="management-empty">
            <div class="management-empty-icon">
              <Search :size="27" />
            </div>

            <h3>
              Search results to view records
            </h3>

            <p>
              Enter a Student ID, student name, course,
              or select department/semester from the filters above.
            </p>
          </div>

          <div v-else class="table-wrapper">
            <table class="custom-table">
              <thead>
                <tr>
                  <th>Student ID</th>
                  <th>Student</th>
                  <th>Course</th>
                  <th>Semester</th>
                  <th>Marks</th>
                  <th>Grade</th>
                  <th>Grade Point</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>

              <tbody>
                <tr v-if="loading">
                  <td colspan="9" class="empty-state">
                    Loading results...
                  </td>
                </tr>

                <tr v-else-if="!results.length">
                  <td colspan="9" class="empty-state">
                    No results found for the selected filter.
                  </td>
                </tr>

                <tr v-for="result in results" :key="result.id">
                  <td>
                    <strong>
                      {{
                        result.student?.user?.user_id ??
                        "-"
                      }}
                    </strong>
                  </td>

                  <td>
                    {{
                      result.student?.user?.name ??
                      "-"
                    }}
                  </td>

                  <td>
                    <div class="course-cell">
                      <strong>
                        {{
                          result.course?.course_code ??
                          "-"
                        }}
                      </strong>

                      <span>
                        {{
                          result.course?.course_name ??
                          "-"
                        }}
                      </span>
                    </div>
                  </td>

                  <td>
                    {{ result.semester }}
                  </td>

                  <td>
                    <strong>
                      {{ result.marks }}
                    </strong>
                  </td>

                  <td>
                    <span :class="[
                      'grade-badge',
                      gradeClass(result.grade),
                    ]">
                      {{ result.grade }}
                    </span>
                  </td>

                  <td>
                    {{
                      Number(
                        result.grade_point
                      ).toFixed(2)
                    }}
                  </td>

                  <td>
                    <span :class="[
                      'status-badge',
                      result.status === 'Passed'
                        ? 'status-passed'
                        : 'status-failed',
                    ]">
                      {{ result.status }}
                    </span>
                  </td>

                  <td>
                    <div class="action-buttons">
                      <button class="action-btn edit-btn" title="Edit marks" @click="
                        openEditModal(result)
                        ">
                        <Edit2 :size="16" />
                      </button>

                      <button class="action-btn delete-btn" title="Delete result" @click="
                        deleteResult(result)
                        ">
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>

    <!--  ADD / EDIT MODAL  -->

    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="result-modal">

        <div class="modal-header">
          <div>
            <h2>
              {{
                editingId
                  ? "Edit Result"
                  : "Add Result"
              }}
            </h2>

            <p v-if="editingId">
              Update marks for this subject only.
            </p>

            <p v-else>
              Add one or multiple course results.
            </p>
          </div>

          <button class="close-btn" @click="closeModal">
            <X :size="20" />
          </button>
        </div>

        <!-- EDIT -->

        <form v-if="editingId" @submit.prevent="saveResult">
          <div class="form-group">
            <label>Student ID</label>

            <input v-model="form.student_id" type="text" disabled />
          </div>

          <div class="selected-student-card">
            <div class="selected-student-icon">
              <GraduationCap :size="20" />
            </div>

            <div>
              <strong>
                {{ selectedStudent?.user?.name }}
              </strong>

              <span>
                {{ selectedStudent?.user?.user_id }}
                •
                {{ selectedStudent?.department }}
              </span>
            </div>
          </div>

          <div class="form-group">
            <label>Subject</label>

            <div class="readonly-course">
              <div>
                <strong>
                  {{ editForm.course_code }}
                </strong>

                <span>
                  {{ editForm.course_name }}
                </span>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Semester</label>

            <input v-model="editForm.semester" type="text" disabled />
          </div>

          <div class="form-group">
            <label>
              Marks <span>*</span>
            </label>

            <input v-model="editForm.marks" type="number" min="0" max="100" placeholder="Enter marks (0-100)"
              required />

            <small v-if="errors.marks">
              {{ errors.marks[0] }}
            </small>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="closeModal">
              Cancel
            </button>

            <button type="submit" class="btn-primary" :disabled="saving">
              {{
                saving
                  ? "Updating..."
                  : "Update Result"
              }}
            </button>
          </div>
        </form>

        <!-- ADD -->

        <form v-else @submit.prevent="saveResult">
          <div class="form-group">
            <label>
              Student ID <span>*</span>
            </label>

            <div class="student-id-input">
              <input v-model="form.student_id" type="text" placeholder="Enter Student ID" required @keyup.enter.prevent="
                findStudentById
              " />

              <button type="button" class="find-student-btn" @click="findStudentById">
                <Search :size="16" />
                Find
              </button>
            </div>

            <small v-if="errors.student_id">
              {{ errors.student_id[0] }}
            </small>
          </div>

          <div v-if="selectedStudent" class="selected-student-card">
            <div class="selected-student-icon">
              <GraduationCap :size="20" />
            </div>

            <div>
              <strong>
                {{ selectedStudent.user?.name }}
              </strong>

              <span>
                {{ selectedStudent.user?.user_id }}
                •
                {{ selectedStudent.department }}
              </span>
            </div>
          </div>

          <div class="form-group">
            <label>Department</label>

            <input v-model="form.department" type="text" disabled placeholder="Student department" />
          </div>

          <div class="form-group">
            <label>
              Semester <span>*</span>
            </label>

            <select v-model="form.semester" required :disabled="!selectedStudent" @change="handleSemesterChange">
              <option value="">
                Select Semester
              </option>

              <option v-for="semester in semesters" :key="semester" :value="semester">
                {{ semester }}
              </option>
            </select>

            <small v-if="errors.semester">
              {{ errors.semester[0] }}
            </small>
          </div>

          <!-- MODE -->

          <div v-if="
            form.semester &&
            courses.length
          " class="course-mode-switch">
            <button type="button" :class="{
              active: !singleCourseMode,
            }" @click="switchToAllCoursesMode">
              All Courses
            </button>

            <button type="button" :class="{
              active: singleCourseMode,
            }" @click="switchToSingleCourseMode">
              Individual Course
            </button>
          </div>

          <!-- ALL COURSES -->

          <div v-if="
            form.semester &&
            courses.length &&
            !singleCourseMode
          " class="courses-mark-list">
            <div class="courses-list-header">
              <strong>
                {{ courses.length }} Courses
              </strong>

              <span>
                Enter marks for the courses you want to add.
              </span>
            </div>

            <div v-for="item in courseMarks" :key="item.course.id" class="course-mark-row">
              <div class="course-info">
                <strong>
                  {{ item.course.course_code }}
                </strong>

                <span>
                  {{ item.course.course_name }}
                </span>
              </div>

              <div class="course-credit">
                {{ item.course.credit }}
                Credit
              </div>

              <input v-model="item.marks" type="number" min="0" max="100" placeholder="Marks" />
            </div>

            <small v-if="errors.marks" class="course-error">
              {{ errors.marks[0] }}
            </small>
          </div>

          <!-- INDIVIDUAL -->

          <div v-if="
            form.semester &&
            courses.length &&
            singleCourseMode
          " class="courses-mark-list">
            <div class="courses-list-header">
              <strong>
                Select Course
              </strong>

              <span>
                Add result for one course.
              </span>
            </div>

            <div v-for="item in courseMarks" :key="item.course.id" class="single-course-option" :class="{
              selected: item.marks !== '',
            }" @click="
                courseMarks.forEach(
                  (c) => (c.marks = '')
                );
              item.marks = '0';
              ">
              <div class="course-info">
                <strong>
                  {{ item.course.course_code }}
                </strong>

                <span>
                  {{ item.course.course_name }}
                </span>
              </div>

              <div class="single-course-mark">
                <input v-model="item.marks" type="number" min="0" max="100" placeholder="Marks" @click.stop />
              </div>
            </div>

            <small v-if="errors.course_id" class="course-error">
              {{ errors.course_id[0] }}
            </small>
          </div>

          <!-- NO COURSE -->

          <div v-if="
            form.semester &&
            !courses.length
          " class="no-course-message">
            <BookOpen :size="20" />

            <div>
              <strong>
                No courses found
              </strong>

              <span>
                No course is available for this department and semester.
              </span>
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="closeModal">
              Cancel
            </button>

            <button type="submit" class="btn-primary" :disabled="saving ||
              !selectedStudent ||
              !form.semester ||
              !courses.length
              ">
              {{
                saving
                  ? "Saving..."
                  : "Save Result"
              }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!--  DELETE  -->

    <div v-if="showDeleteModal" class="modal-overlay delete-overlay" @click.self="closeDeleteModal">
      <div class="delete-modal">
        <div class="delete-icon">
          <Trash2 :size="23" />
        </div>

        <h3>Delete Result?</h3>

        <p>
          Are you sure you want to delete the result for

          <strong>
            {{ resultToDelete?.course?.course_code }}
          </strong>

          of

          <strong>
            {{ resultToDelete?.student?.user?.name }}
          </strong>
          ?
        </p>

        <div class="delete-actions">
          <button class="btn-secondary" :disabled="deleting" @click="closeDeleteModal">
            Cancel
          </button>

          <button class="delete-confirm-btn" :disabled="deleting" @click="confirmDelete">
            {{
              deleting
                ? "Deleting..."
                : "Delete Result"
            }}
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
.results-page {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.summary-search-panel,
.management-section,
.student-summary {
  width: 100%;
}

.summary-search-panel {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 22px;
}

.section-heading {
  display: flex;
  align-items: center;
  gap: 13px;
  margin-bottom: 18px;
}

.heading-icon,
.summary-card-icon,
.student-avatar,
.selected-student-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eef3f9;
  color: #12345b;
  border-radius: 10px;
}

.heading-icon {
  width: 42px;
  height: 42px;
}

.section-heading h2,
.page-heading h2 {
  margin: 0;
  color: #16253d;
  font-size: 19px;
}

.section-heading p,
.page-heading p {
  margin: 4px 0 0;
  color: #718096;
  font-size: 13px;
}

/* SEARCH */

.student-search {
  display: flex;
  gap: 12px;
}

.search-input,
.search-filter-box {
  display: flex;
  align-items: center;
  gap: 9px;
  background: #f8fafc;
  border: 1px solid #dce2ea;
  border-radius: 9px;
  padding: 0 13px;
}

.student-search .search-input {
  flex: 1;
  height: 44px;
}

.search-input input,
.search-filter-box input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  font-size: 14px;
}

.search-error {
  border-color: #e2a4a4;
  background: #fffafa;
}

/* ERROR */

.inline-error {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-top: 12px;
  padding: 11px 13px;
  border: 1px solid #f1caca;
  border-left: 3px solid #c0392b;
  background: #fff8f8;
  border-radius: 8px;
}

.error-dot {
  width: 7px;
  height: 7px;
  margin-top: 5px;
  flex-shrink: 0;
  background: #c0392b;
  border-radius: 50%;
}

.inline-error strong {
  display: block;
  color: #a83232;
  font-size: 13px;
}

.inline-error p {
  margin: 3px 0 0;
  color: #8b5b5b;
  font-size: 12px;
}

/* BUTTONS */

.btn-primary,
.btn-secondary {
  height: 42px;
  padding: 0 17px;
  border-radius: 8px;
  border: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.btn-primary {
  background: #12345b;
  color: #fff;
}

.btn-primary:hover {
  background: #0d2948;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #eef1f5;
  color: #334155;
}

.btn-secondary:hover {
  background: #e4e8ee;
}

/* SUMMARY */

.student-summary {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  overflow: hidden;
}

.student-info-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 22px;
  border-bottom: 1px solid #e5e7eb;
}

.student-avatar {
  width: 52px;
  height: 52px;
  border-radius: 50%;
}

.student-info-card h2 {
  margin: 0;
  font-size: 19px;
  color: #16253d;
}

.student-info-card p {
  margin: 5px 0 0;
  color: #718096;
  font-size: 13px;
}

.student-info-card p span {
  margin: 0 5px;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  padding: 20px;
  background: #f8fafc;
}

.summary-card {
  display: flex;
  align-items: center;
  gap: 13px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 11px;
  padding: 17px;
}

.summary-card-icon {
  width: 40px;
  height: 40px;
}

.summary-card span {
  display: block;
  color: #718096;
  font-size: 12px;
}

.summary-card strong {
  display: block;
  margin-top: 3px;
  color: #16253d;
  font-size: 21px;
}

/* SEMESTER */

.semester-summary {
  padding: 20px;
}

.panel-header {
  margin-bottom: 14px;
}

.panel-header h3 {
  margin: 0;
  font-size: 16px;
  color: #16253d;
}

.panel-header p {
  margin: 4px 0 0;
  color: #718096;
  font-size: 12px;
}

.semester-block {
  border: 1px solid #e5e7eb;
  border-radius: 9px;
  margin-bottom: 10px;
  overflow: hidden;
}

.semester-header {
  width: 100%;
  border: 0;
  background: #fff;
  padding: 15px 17px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  text-align: left;
}

.semester-header:hover {
  background: #f8fafc;
}

.semester-header strong {
  display: block;
  color: #16253d;
  font-size: 14px;
}

.semester-header span {
  display: block;
  margin-top: 3px;
  color: #718096;
  font-size: 12px;
}

.semester-right {
  display: flex;
  align-items: center;
  gap: 13px;
  color: #12345b;
}

.semester-results {
  border-top: 1px solid #e5e7eb;
}

.semester-result-row {
  display: grid;
  grid-template-columns: 1fr 80px 70px 70px;
  align-items: center;
  gap: 15px;
  padding: 13px 17px;
  border-bottom: 1px solid #edf0f4;
}

.semester-result-row:last-child {
  border-bottom: 0;
}

.semester-result-row strong,
.semester-result-row span {
  display: block;
}

.semester-result-row strong {
  color: #16253d;
  font-size: 13px;
}

.semester-result-row span {
  color: #718096;
  font-size: 11px;
  margin-top: 2px;
}

.result-mark,
.gp-value {
  font-weight: 600;
  color: #334155;
}

/* BADGES */

.grade-badge,
.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 48px;
  padding: 4px 9px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 700;
}

.grade-a,
.status-passed {
  background: #e8f5ee;
  color: #177245;
}

.grade-b {
  background: #edf3fb;
  color: #24558c;
}

.grade-c {
  background: #fff5df;
  color: #986b13;
}

.grade-f,
.status-failed {
  background: #fdecec;
  color: #c0392b;
}

/* MANAGEMENT */

.management-section {
  display: flex;
  flex-direction: column;
  gap: 17px;
}

.page-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* FILTER */

.toolbar-panel {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 15px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
}

.filter-title {
  display: flex;
  align-items: center;
  gap: 7px;
  color: #475569;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

.search-filter-box {
  flex: 1;
  height: 42px;
}

.toolbar-panel select {
  height: 42px;
  min-width: 145px;
  padding: 0 11px;
  border: 1px solid #dce2ea;
  border-radius: 8px;
  background: #fff;
  color: #334155;
  outline: none;
}

.filter-search-btn,
.clear-filter-btn {
  height: 42px;
  border: 0;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
}

.filter-search-btn {
  padding: 0 14px;
  background: #12345b;
  color: #fff;
}

.filter-search-btn:disabled {
  opacity: 0.6;
}

.clear-filter-btn {
  width: 42px;
  background: #eef1f5;
  color: #64748b;
}

/* TABLE */

.panel {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
}

.table-panel {
  overflow: hidden;
}

.table-wrapper {
  width: 100%;
  overflow-x: auto;
}

.custom-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1050px;
}

.custom-table th {
  padding: 13px 15px;
  background: #f8fafc;
  color: #64748b;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  text-align: left;
  white-space: nowrap;
}

.custom-table td {
  padding: 14px 15px;
  border-top: 1px solid #edf0f4;
  color: #475569;
  font-size: 13px;
  vertical-align: middle;
}

.custom-table td strong {
  color: #16253d;
}

.course-cell strong,
.course-cell span {
  display: block;
}

.course-cell span {
  margin-top: 3px;
  color: #718096;
  font-size: 11px;
}

.action-buttons {
  display: flex;
  gap: 7px;
}

.action-btn {
  width: 32px;
  height: 32px;
  border: 0;
  border-radius: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.edit-btn {
  background: #edf3fb;
  color: #24558c;
}

.delete-btn {
  background: #fdecec;
  color: #c0392b;
}

.empty-state {
  text-align: center;
  padding: 35px !important;
  color: #718096 !important;
}

.management-empty {
  min-height: 260px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 30px;
}

.management-empty-icon {
  width: 58px;
  height: 58px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #eef3f9;
  color: #12345b;
  margin-bottom: 13px;
}

.management-empty h3 {
  margin: 0;
  color: #334155;
  font-size: 15px;
}

.management-empty p {
  max-width: 450px;
  margin: 7px 0 0;
  color: #94a3b8;
  font-size: 12px;
  line-height: 1.6;
}

/* MODAL */

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.result-modal {
  width: 100%;
  max-width: 580px;
  max-height: 90vh;
  overflow-y: auto;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.18);
}

.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 0;
  color: #16253d;
  font-size: 19px;
}

.modal-header p {
  margin: 4px 0 0;
  color: #718096;
  font-size: 12px;
}

.close-btn {
  width: 34px;
  height: 34px;
  border: 0;
  background: #f1f5f9;
  border-radius: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #64748b;
}

.result-modal form {
  padding: 20px;
}

/* FORM */

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 7px;
  color: #334155;
  font-size: 13px;
  font-weight: 600;
}

.form-group label span {
  color: #d33;
}

.form-group input,
.form-group select {
  width: 100%;
  height: 42px;
  box-sizing: border-box;
  border: 1px solid #dce2ea;
  border-radius: 8px;
  padding: 0 11px;
  outline: none;
  color: #334155;
  background: #fff;
}

.form-group input:focus,
.form-group select:focus {
  border-color: #12345b;
}

.form-group input:disabled,
.form-group select:disabled {
  background: #f1f5f9;
  color: #94a3b8;
  cursor: not-allowed;
}

.form-group small {
  display: block;
  margin-top: 5px;
  color: #c0392b;
  font-size: 11px;
}

/* STUDENT */

.student-id-input {
  display: flex;
  gap: 8px;
}

.student-id-input input {
  flex: 1;
}

.find-student-btn {
  height: 42px;
  padding: 0 14px;
  border: 0;
  border-radius: 8px;
  background: #eef3f9;
  color: #12345b;
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 600;
  cursor: pointer;
}

.selected-student-card {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 11px 13px;
  margin-bottom: 16px;
  border: 1px solid #dce5ef;
  border-radius: 9px;
  background: #f8fafc;
}

.selected-student-icon {
  width: 38px;
  height: 38px;
  flex-shrink: 0;
}

.selected-student-card strong,
.selected-student-card span {
  display: block;
}

.selected-student-card strong {
  color: #16253d;
  font-size: 13px;
}

.selected-student-card span {
  margin-top: 3px;
  color: #718096;
  font-size: 11px;
}

/* COURSE MODE */

.course-mode-switch {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 5px;
  padding: 4px;
  margin-bottom: 15px;
  background: #f1f5f9;
  border-radius: 9px;
}

.course-mode-switch button {
  height: 35px;
  border: 0;
  border-radius: 7px;
  background: transparent;
  color: #64748b;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.course-mode-switch button.active {
  background: #fff;
  color: #12345b;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

/* COURSES */

.courses-mark-list {
  margin-bottom: 17px;
  border: 1px solid #e1e6ed;
  border-radius: 10px;
  overflow: hidden;
}

.courses-list-header {
  padding: 13px 14px;
  background: #f8fafc;
  border-bottom: 1px solid #e1e6ed;
}

.courses-list-header strong,
.courses-list-header span {
  display: block;
}

.courses-list-header strong {
  color: #334155;
  font-size: 13px;
}

.courses-list-header span {
  margin-top: 3px;
  color: #94a3b8;
  font-size: 11px;
}

.course-mark-row {
  display: grid;
  grid-template-columns: 1fr 80px 85px;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-bottom: 1px solid #edf0f4;
}

.course-info strong,
.course-info span {
  display: block;
}

.course-info strong {
  color: #334155;
  font-size: 12px;
}

.course-info span {
  margin-top: 2px;
  color: #718096;
  font-size: 10px;
}

.course-credit {
  color: #64748b;
  font-size: 11px;
}

.course-mark-row input,
.single-course-mark input {
  width: 100%;
  height: 36px;
  box-sizing: border-box;
  border: 1px solid #dce2ea;
  border-radius: 7px;
  padding: 0 8px;
  outline: none;
  font-size: 12px;
}

.course-error {
  display: block;
  padding: 0 12px 10px;
  color: #c0392b;
  font-size: 11px;
}

/* INDIVIDUAL */

.single-course-option {
  display: grid;
  grid-template-columns: 1fr 90px;
  align-items: center;
  gap: 12px;
  padding: 11px 13px;
  border-bottom: 1px solid #edf0f4;
  cursor: pointer;
}

.single-course-option:hover,
.single-course-option.selected {
  background: #f8fafc;
}

/* NO COURSE */

.no-course-message {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 13px;
  margin-bottom: 17px;
  border: 1px solid #e5e7eb;
  border-radius: 9px;
  background: #f8fafc;
  color: #718096;
}

.no-course-message strong,
.no-course-message span {
  display: block;
}

.no-course-message strong {
  color: #475569;
  font-size: 12px;
}

.no-course-message span {
  margin-top: 2px;
  font-size: 11px;
}

/* EDIT COURSE */

.readonly-course {
  padding: 10px 12px;
  border: 1px solid #dce2ea;
  border-radius: 8px;
  background: #f8fafc;
}

.readonly-course strong,
.readonly-course span {
  display: block;
}

.readonly-course strong {
  color: #12345b;
  font-size: 12px;
}

.readonly-course span {
  margin-top: 2px;
  color: #718096;
  font-size: 11px;
}

/* ACTIONS */

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 9px;
  margin-top: 22px;
  padding-top: 17px;
  border-top: 1px solid #e5e7eb;
}

/* DELETE */

.delete-overlay {
  z-index: 1100;
}

.delete-modal {
  width: 100%;
  max-width: 390px;
  padding: 24px;
  background: #fff;
  border-radius: 14px;
  text-align: center;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.18);
}

.delete-icon {
  width: 48px;
  height: 48px;
  margin: 0 auto 13px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #fdecec;
  color: #c0392b;
}

.delete-modal h3 {
  margin: 0;
  color: #16253d;
  font-size: 17px;
}

.delete-modal p {
  margin: 8px 0 20px;
  color: #718096;
  font-size: 12px;
  line-height: 1.6;
}

.delete-modal p strong {
  color: #475569;
}

.delete-actions {
  display: flex;
  justify-content: center;
  gap: 9px;
}

.delete-confirm-btn {
  height: 42px;
  padding: 0 17px;
  border: 0;
  border-radius: 8px;
  background: #c0392b;
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.delete-confirm-btn:hover {
  background: #a93226;
}

.delete-confirm-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* RESPONSIVE */

@media (max-width: 900px) {
  .toolbar-panel {
    flex-wrap: wrap;
  }

  .filter-title {
    width: 100%;
  }

  .search-filter-box {
    min-width: 220px;
  }

  .toolbar-panel select {
    flex: 1;
    min-width: 140px;
  }
}

@media (max-width: 768px) {
  .student-search {
    flex-direction: row;
  }

  .student-search .search-input {
    min-width: 0;
  }

  .student-search .btn-primary {
    min-width: 88px;
    flex-shrink: 0;
  }

  .toolbar-panel {
    flex-wrap: wrap;
  }

  .search-filter-box {
    width: 100%;
    flex: none;
    min-width: 0;
  }

  .toolbar-panel select {
    flex: 1;
    min-width: 0;
  }

  .summary-cards {
    grid-template-columns: 1fr;
  }

  .semester-result-row {
    grid-template-columns: 1fr 55px 55px;
  }

  .gp-value {
    display: none;
  }

  .course-mark-row {
    grid-template-columns: 1fr 75px;
  }

  .course-mark-row .course-credit {
    display: none;
  }
}

@media (max-width: 500px) {
  .results-page {
    gap: 17px;
  }

  .summary-search-panel {
    padding: 15px;
  }

  .section-heading h2,
  .page-heading h2 {
    font-size: 16px;
  }

  .section-heading p,
  .page-heading p {
    font-size: 11px;
  }

  .student-search {
    gap: 7px;
  }

  .student-search .search-input {
    height: 40px;
    padding: 0 10px;
  }

  .student-search .btn-primary {
    height: 40px;
    min-width: 76px;
    padding: 0 10px;
    font-size: 12px;
  }

  .page-heading {
    gap: 8px;
  }

  .page-heading .btn-primary {
    height: 38px;
    padding: 0 10px;
    font-size: 11px;
  }

  .toolbar-panel {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    padding: 11px;
  }

  .filter-title,
  .search-filter-box {
    grid-column: 1 / -1;
  }

  .toolbar-panel select,
  .filter-search-btn {
    width: 100%;
    min-width: 0;
  }

  .clear-filter-btn {
    width: 40px;
    justify-self: end;
  }

  .student-info-card {
    padding: 15px;
  }

  .student-info-card h2 {
    font-size: 16px;
  }

  .student-info-card p {
    font-size: 11px;
  }

  .summary-cards {
    padding: 11px;
  }

  .summary-card {
    padding: 12px;
  }

  .semester-summary {
    padding: 13px;
  }

  .semester-result-row {
    grid-template-columns: 1fr 48px 48px;
    gap: 7px;
    padding: 11px;
  }

  .modal-overlay {
    padding: 10px;
  }

  .result-modal {
    max-height: 94vh;
  }

  .modal-header,
  .result-modal form {
    padding: 15px;
  }

  .student-id-input {
    gap: 7px;
  }

  .find-student-btn {
    min-width: 70px;
    padding: 0 10px;
    font-size: 11px;
  }

  .form-group {
    margin-bottom: 13px;
  }

  .form-group label {
    font-size: 12px;
  }

  .form-group input,
  .form-group select {
    height: 40px;
    font-size: 12px;
  }

  .course-mode-switch button {
    height: 34px;
    font-size: 11px;
  }

  .course-mark-row {
    grid-template-columns: 1fr 70px;
  }

  .course-mark-row input,
  .single-course-mark input {
    width: 70px;
    height: 35px;
    font-size: 11px;
  }

  .modal-actions,
  .delete-actions {
    gap: 7px;
  }

  .modal-actions button,
  .delete-actions button {
    flex: 1;
  }
}
</style>
