<script setup lang="ts">
import { computed, onMounted, ref } from "vue";

import {
  Plus,
  Search,
  Edit2,
  Trash2,
  X,
  RefreshCw,
  BookOpen,
  GraduationCap,
  Filter,
  ChevronLeft,
  ChevronRight,
  RotateCcw,
} from "lucide-vue-next";

import AdminLayout from "@/components/admin/AdminLayout.vue";
import api from "@/services/api";


/*  TYPES  */

interface Department {
  id: number;
  code: string;
  name: string;
  status?: string;
}

interface Batch {
  id: number;
  department?: Department | string | null;
  batch_number: string | number;
  start_year?: number | string;
  current_semester?: string;
  status?: string;
}

interface Section {
  id: number;
  batch_id: number;
  section_name: string;
  capacity: number;
  enrolled: number;
  status?: string;
}

interface Course {
  id: number;
  course_code: string;
  course_name: string;
  credit: number;
  department?: Department | string | null;
  semester?: string;
}

interface Student {
  id: number;
  user_id?: string;
  name: string;
  email?: string;
  department?: Department | string | null;
  batch: Batch | null;
  section: Section | null;
  current_semester?: string;
}

interface ResultInfo {
  id: number;
  marks?: number | string | null;
  grade?: string | null;
}

interface Enrollment {
  id: number;

  student: {
    id: number;

    user?: {
      user_id?: string;
      name?: string;
      email?: string;
    };

    department?: Department | string | null;
    batch?: Batch | null;
    section?: Section | null;
  };

  course: Course;

  section?: Section | null;

  semester: string;

  status:
  | "Active"
  | "Completed"
  | "Dropped";

  display_status?:
  | "Active"
  | "Completed"
  | "Dropped"
  | "Failed";

  result?: ResultInfo | null;
}

interface RestoreResult {
  id: number;
  student_id?: number;
  course_id: number;
  semester: string;
  marks?: number | string | null;
  grade?: string | null;
  course: Course;
}

interface SelectedCourse {
  course_id: number;
  section_id: number;
}

interface HistoricalCourse extends Course {
  already_enrolled?: boolean;
}

type FeedbackType = "success" | "error";


/*  STATE  */

const departments = ref<Department[]>([]);
const batches = ref<Batch[]>([]);
const sections = ref<Section[]>([]);
const courses = ref<Course[]>([]);

const enrollments = ref<Enrollment[]>([]);
const enrollmentCountRecords =
  ref<Enrollment[]>([]);

const studentHistory =
  ref<Enrollment[]>([]);

const restoreResults =
  ref<RestoreResult[]>([]);

const student =
  ref<Student | null>(null);

const loading = ref(false);
const searchingStudent = ref(false);
const saving = ref(false);
const loadingSectionCounts = ref(false);
const loadingRestore = ref(false);

const showAddModal = ref(false);
const showEditModal = ref(false);
const showStudentHistory = ref(false);
const showDeleteModal = ref(false);
const showRestoreModal = ref(false);

const searchStudentId = ref("");
const formStudentId = ref("");
const enrollmentSemester = ref("");

const selectedCourses =
  ref<SelectedCourse[]>([]);

const editingEnrollment =
  ref<Enrollment | null>(null);

const deleteTarget =
  ref<Enrollment | null>(null);

const historicalCourses =
  ref<HistoricalCourse[]>([]);

const historicalSections =
  ref<Section[]>([]);

const historicalSemester =
  ref("");

const historicalCourseId =
  ref(0);

const historicalSectionId =
  ref(0);

const historicalStatus =
  ref<Enrollment["status"]>("Completed");

const loadingHistoricalOptions =
  ref(false);

const showHistoricalRestoreModal =
  ref(false);

const restoreTarget =
  ref<RestoreResult | null>(null);

const editForm = ref({
  section_id: 0,
  status: "Active" as Enrollment["status"],
});

const filters = ref({
  department: "",
  batch_id: "",
  section_id: "",
  semester: "",
  status: "",
});

const feedback = ref<{
  type: FeedbackType;
  message: string;
} | null>(null);

let feedbackTimer:
  | ReturnType<typeof setTimeout>
  | undefined;


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

const statuses: Enrollment["status"][] = [
  "Active",
  "Completed",
  "Dropped",
];

const perPage = 10;
const currentPage = ref(1);


/*  HELPERS  */

function notify(
  message: string,
  type: FeedbackType = "success"
) {
  feedback.value = {
    type,
    message,
  };

  if (feedbackTimer) {
    clearTimeout(feedbackTimer);
  }

  feedbackTimer = setTimeout(() => {
    feedback.value = null;
  }, 4000);
}

function clearFeedback() {
  feedback.value = null;

  if (feedbackTimer) {
    clearTimeout(feedbackTimer);
  }
}

function errorMessage(
  error: any,
  fallback: string
) {
  return (
    error?.response?.data?.message ||
    fallback
  );
}

function departmentCode(
  department?: Department | string | null
) {
  if (!department) return "-";

  return typeof department === "string"
    ? department
    : department.code || "-";
}

function batchLabel(
  batch?: Batch | null
) {
  return batch
    ? String(batch.batch_number)
    : "-";
}

function sectionName(
  enrollment: Enrollment
) {
  return (
    enrollment.section?.section_name ||
    "-"
  );
}

function displayStatus(
  enrollment: Enrollment
) {
  if (
    enrollment.result?.grade
      ?.toUpperCase()
      .trim() === "F"
  ) {
    return "Failed";
  }

  return (
    enrollment.display_status ||
    enrollment.status
  );
}

function statusClass(status: string) {
  return {
    active: status === "Active",
    completed: status === "Completed",
    dropped: status === "Dropped",
    failed: status === "Failed",
  };
}


/*  SEAT COUNTS  */

function getCourseSectionCount(
  courseId: number,
  sectionId: number
) {
  return enrollmentCountRecords.value.filter(
    (e) =>
      Number(e.course?.id) ===
      Number(courseId) &&
      Number(e.section?.id) ===
      Number(sectionId) &&
      e.status === "Active"
  ).length;
}

function isCourseSectionFull(
  courseId: number,
  section: Section
) {
  return (
    getCourseSectionCount(
      courseId,
      section.id
    ) >= Number(section.capacity)
  );
}

function getEditSectionCount(
  section: Section
) {
  const enrollment =
    editingEnrollment.value;

  if (!enrollment) {
    return section.enrolled;
  }

  const current =
    Number(enrollment.section?.id) ===
    Number(section.id);

  if (!current) {
    return section.enrolled;
  }

  /*
  
   Failed / Active / Dropped = 1
   Completed = 0
   
  */

  if (
    enrollment.result?.grade
      ?.toUpperCase()
      .trim() === "F"
  ) {
    return 1;
  }

  return editForm.value.status ===
    "Completed"
    ? 0
    : 1;
}


/*  COMPUTED  */

const selectedCourseIds = computed(() =>
  selectedCourses.value.map(
    (x) => x.course_id
  )
);

const missingSection = computed(() =>
  selectedCourses.value.some(
    (x) => !x.section_id
  )
);

const filteredBatches = computed(() =>
  !filters.value.department
    ? batches.value
    : batches.value.filter((b) =>
      typeof b.department === "string"
        ? b.department ===
        filters.value.department
        : b.department?.code ===
        filters.value.department
    )
);

const filteredSections = computed(() =>
  !filters.value.batch_id
    ? sections.value
    : sections.value.filter(
      (s) =>
        String(s.batch_id) ===
        String(filters.value.batch_id)
    )
);

const enrollmentCount = computed(
  () => enrollments.value.length
);

const historyCount = computed(
  () => studentHistory.value.length
);

const totalPages = computed(() =>
  Math.max(
    1,
    Math.ceil(
      enrollments.value.length /
      perPage
    )
  )
);

const paginatedEnrollments = computed(
  () => {
    const start =
      (currentPage.value - 1) *
      perPage;

    return enrollments.value.slice(
      start,
      start + perPage
    );
  }
);

const pageStart = computed(() =>
  enrollmentCount.value
    ? (currentPage.value - 1) *
    perPage +
    1
    : 0
);

const pageEnd = computed(() =>
  Math.min(
    currentPage.value * perPage,
    enrollmentCount.value
  )
);


/*  API  */

async function loadDepartments() {
  try {
    const { data } =
      await api.get(
        "/admin/departments"
      );

    departments.value =
      data.departments || [];
  } catch (e) {
    notify(
      errorMessage(
        e,
        "Failed to load departments."
      ),
      "error"
    );
  }
}

async function loadBatches() {
  try {
    const { data } =
      await api.get(
        "/admin/batches"
      );

    batches.value =
      data.batches || [];
  } catch (e) {
    notify(
      errorMessage(
        e,
        "Failed to load batches."
      ),
      "error"
    );
  }
}

async function loadSections() {
  try {
    const { data } =
      await api.get(
        "/admin/sections"
      );

    sections.value =
      data.sections || [];
  } catch (e) {
    notify(
      errorMessage(
        e,
        "Failed to load sections."
      ),
      "error"
    );
  }
}

async function loadEnrollments() {
  loading.value = true;

  try {
    const params: Record<
      string,
      string
    > = {};

    Object.entries(
      filters.value
    ).forEach(
      ([key, value]) => {
        if (value) {
          params[key] = value;
        }
      }
    );

    const { data } =
      await api.get(
        "/admin/enrollments",
        { params }
      );

    enrollments.value =
      Array.isArray(
        data?.enrollments
      )
        ? data.enrollments
        : [];

    currentPage.value = 1;
  } catch (e) {
    enrollments.value = [];

    notify(
      errorMessage(
        e,
        "Failed to load enrollment records."
      ),
      "error"
    );
  } finally {
    loading.value = false;
  }
}

async function loadEnrollmentCountRecords() {
  loadingSectionCounts.value =
    true;

  try {
    const { data } =
      await api.get(
        "/admin/enrollments"
      );

    enrollmentCountRecords.value =
      Array.isArray(
        data?.enrollments
      )
        ? data.enrollments
        : [];
  } catch {
    enrollmentCountRecords.value =
      [];
  } finally {
    loadingSectionCounts.value =
      false;
  }
}


/*  FILTERS  */

async function applyFilters() {
  showStudentHistory.value =
    false;

  student.value = null;

  await loadEnrollments();
}

async function resetFilters() {
  filters.value = {
    department: "",
    batch_id: "",
    section_id: "",
    semester: "",
    status: "",
  };

  await loadEnrollments();
}


/*  STUDENT SEARCH  */

async function searchStudent() {
  const id =
    searchStudentId.value.trim();

  if (!id) {
    notify(
      "Please enter a Student ID.",
      "error"
    );
    return;
  }

  searchingStudent.value =
    true;

  clearFeedback();

  student.value = null;
  studentHistory.value = [];
  restoreResults.value = [];
  showStudentHistory.value =
    false;

  try {
    const { data } =
      await api.get(
        "/admin/enrollments/student-history",
        {
          params: {
            student_id: id,
          },
        }
      );

    student.value =
      data.student || null;

    studentHistory.value =
      Array.isArray(
        data.enrollments
      )
        ? data.enrollments
        : [];

    /*
  
     IMPORTANT
   
     Backend now returns orphan_results directly.
     No separate Result API call needed.
    */

    restoreResults.value =
      Array.isArray(
        data.orphan_results
      )
        ? data.orphan_results
        : [];

    showStudentHistory.value =
      !!student.value;

    if (!student.value) {
      notify(
        "Student not found.",
        "error"
      );
    }
  } catch (e) {
    student.value = null;
    studentHistory.value = [];
    restoreResults.value = [];
    showStudentHistory.value =
      false;

    notify(
      errorMessage(
        e,
        "Student not found."
      ),
      "error"
    );
  } finally {
    searchingStudent.value =
      false;
  }
}

/*  RESTORE  */

function openRestoreModal(result: RestoreResult) {
  restoreTarget.value = result;
  showRestoreModal.value = true;
}

function closeRestoreModal() {
  if (saving.value) return;

  showRestoreModal.value = false;
  restoreTarget.value = null;
}

async function restoreEnrollment() {
  const target = restoreTarget.value;
  const currentStudent = student.value;

  if (!target || !currentStudent) {
    return;
  }

  saving.value = true;
  clearFeedback();

  try {
    const { data } = await api.post(
      "/admin/enrollments/restore",
      {
        student_id: currentStudent.id,
        course_id: target.course_id,
        semester: target.semester,
      }
    );

    /*
    
     Close restore modal immediately after success
    
    */
    closeRestoreModal();

    /*
    
     Remove restored orphan result locally
    
    */
    restoreResults.value = restoreResults.value.filter(
      (item) => item.id !== target.id
    );

    /*
    
     Add returned enrollment to history
    
    */
    if (data?.enrollment) {
      studentHistory.value.push(data.enrollment);

      studentHistory.value.sort((a, b) => {
        const order: Record<string, number> = {
          "1st": 1,
          "2nd": 2,
          "3rd": 3,
          "4th": 4,
          "5th": 5,
          "6th": 6,
          "7th": 7,
          "8th": 8,
        };

        const semesterDiff =
          (order[a.semester] || 99) -
          (order[b.semester] || 99);

        if (semesterDiff !== 0) {
          return semesterDiff;
        }

        return (
          Number(a.course?.id || 0) -
          Number(b.course?.id || 0)
        );
      });
    }

    /*
    
     Reload section counts and enrollment records
    
    
     Completed enrollment should NOT increase Active seat count.
    
    */
    await Promise.all([
      loadSections(),
      loadEnrollmentCountRecords(),
      loadEnrollments(),
    ]);

    notify(
      `${target.course.course_code} enrollment restored successfully.`,
      "success"
    );
  } catch (e) {
    notify(
      errorMessage(
        e,
        "Failed to restore enrollment."
      ),
      "error"
    );
  } finally {
    saving.value = false;
  }
}


/*  HISTORICAL RESTORE  */

async function openHistoricalRestoreModal() {
  if (!student.value) {
    notify(
      "Please search for a student first.",
      "error"
    );
    return;
  }

  historicalSemester.value = "";
  historicalCourseId.value = 0;

  historicalSectionId.value =
    student.value.section?.id || 0;

  historicalStatus.value = "Completed";

  historicalCourses.value = [];
  historicalSections.value = [];

  showHistoricalRestoreModal.value = true;
}

async function loadHistoricalOptions() {
  if (
    !student.value ||
    !historicalSemester.value
  ) {
    historicalCourses.value = [];
    historicalSections.value = [];
    return;
  }

  loadingHistoricalOptions.value = true;

  try {
    const { data } = await api.get(
      "/admin/enrollments/historical-options",
      {
        params: {
          student_id: student.value.id,
          semester: historicalSemester.value,
        },
      }
    );

    historicalCourses.value =
      Array.isArray(data?.courses)
        ? data.courses
        : [];

    historicalSections.value =
      Array.isArray(data?.sections)
        ? data.sections
        : [];

    historicalCourseId.value = 0;

    historicalSectionId.value =
      student.value.section?.id ||
      historicalSections.value[0]?.id ||
      0;
  } catch (e) {
    historicalCourses.value = [];
    historicalSections.value = [];

    notify(
      errorMessage(
        e,
        "Failed to load historical enrollment options."
      ),
      "error"
    );
  } finally {
    loadingHistoricalOptions.value = false;
  }
}

function closeHistoricalRestoreModal() {
  if (saving.value) return;

  showHistoricalRestoreModal.value = false;

  historicalCourses.value = [];
  historicalSections.value = [];

  historicalSemester.value = "";
  historicalCourseId.value = 0;
  historicalSectionId.value = 0;
  historicalStatus.value = "Completed";
}

async function restoreHistoricalEnrollment() {
  if (!student.value) {
    return;
  }

  if (!historicalSemester.value) {
    notify(
      "Please select a semester.",
      "error"
    );
    return;
  }

  if (!historicalCourseId.value) {
    notify(
      "Please select a course.",
      "error"
    );
    return;
  }

  if (!historicalSectionId.value) {
    notify(
      "Please select a section.",
      "error"
    );
    return;
  }

  saving.value = true;
  clearFeedback();

  try {
    const { data } = await api.post(
      "/admin/enrollments/restore",
      {
        student_id: student.value.id,

        course_id:
          historicalCourseId.value,

        semester:
          historicalSemester.value,

        section_id:
          historicalSectionId.value,

        status:
          historicalStatus.value,
      }
    );

    /*
    
     Close modal
    
    */
    closeHistoricalRestoreModal();

    /*
    
     Add restored enrollment to history
    
    */
    if (data?.enrollment) {
      studentHistory.value.push(
        data.enrollment
      );

      studentHistory.value.sort((a, b) => {
        const order: Record<string, number> = {
          "1st": 1,
          "2nd": 2,
          "3rd": 3,
          "4th": 4,
          "5th": 5,
          "6th": 6,
          "7th": 7,
          "8th": 8,
        };

        const semesterDiff =
          (order[a.semester] || 99) -
          (order[b.semester] || 99);

        if (semesterDiff !== 0) {
          return semesterDiff;
        }

        return (
          Number(a.course?.id || 0) -
          Number(b.course?.id || 0)
        );
      });
    }

    /*
    
     Refresh all affected data
    
    */
    await Promise.all([
      loadSections(),
      loadEnrollmentCountRecords(),
      loadEnrollments(),
      searchStudent(),
    ]);

    notify(
      "Historical enrollment restored successfully.",
      "success"
    );
  } catch (e) {
    notify(
      errorMessage(
        e,
        "Failed to restore historical enrollment."
      ),
      "error"
    );
  } finally {
    saving.value = false;
  }
}


/*  CLEAR  */

async function clearStudentSearch() {
  searchStudentId.value = "";

  student.value = null;

  studentHistory.value = [];

  restoreResults.value = [];

  showStudentHistory.value = false;

  clearFeedback();

  await loadEnrollments();
}

/*  ADD  */

async function openAddModal() {
  formStudentId.value = "";

  enrollmentSemester.value =
    "";

  selectedCourses.value = [];

  courses.value = [];

  student.value = null;

  clearFeedback();

  showAddModal.value = true;

  await loadEnrollmentCountRecords();
}

function closeAddModal() {
  showAddModal.value = false;

  formStudentId.value = "";

  enrollmentSemester.value =
    "";

  selectedCourses.value = [];

  courses.value = [];

  student.value = null;
}

async function loadStudentInfo() {
  const id =
    formStudentId.value.trim();

  if (!id) {
    notify(
      "Please enter a Student ID.",
      "error"
    );
    return;
  }

  saving.value = true;
  clearFeedback();

  try {
    const { data } =
      await api.get(
        "/admin/enrollments/student-info",
        {
          params: {
            student_id: id,
          },
        }
      );

    student.value =
      data.student || null;

    enrollmentSemester.value =
      data.enrollment_semester ||
      data.semester ||
      student.value
        ?.current_semester ||
      "";

    courses.value =
      data.courses || [];

    if (data.sections) {
      sections.value =
        data.sections;
    }

    selectedCourses.value = [];

    await loadEnrollmentCountRecords();

    if (!student.value) {
      notify(
        "Student information could not be loaded.",
        "error"
      );
    }
  } catch (e) {
    student.value = null;

    courses.value = [];

    selectedCourses.value = [];

    notify(
      errorMessage(
        e,
        "Student information could not be loaded."
      ),
      "error"
    );
  } finally {
    saving.value = false;
  }
}


/*  COURSE SELECTION  */

function toggleCourse(
  courseId: number
) {
  const index =
    selectedCourses.value.findIndex(
      (x) =>
        x.course_id === courseId
    );

  if (index >= 0) {
    selectedCourses.value.splice(
      index,
      1
    );
  } else {
    selectedCourses.value.push({
      course_id: courseId,
      section_id: 0,
    });
  }
}

function selectSection(
  courseId: number,
  sectionId: number
) {
  const item =
    selectedCourses.value.find(
      (x) =>
        x.course_id === courseId
    );

  if (item) {
    item.section_id =
      sectionId;
  }
}


/*  SAVE  */

async function saveEnrollment() {
  if (!student.value) {
    notify(
      "Please find a valid student first.",
      "error"
    );
    return;
  }

  if (!selectedCourses.value.length) {
    notify(
      "Please select at least one course.",
      "error"
    );
    return;
  }

  if (!enrollmentSemester.value) {
    notify(
      "Enrollment semester is required.",
      "error"
    );
    return;
  }

  if (missingSection.value) {
    notify(
      "Please select a section for every course.",
      "error"
    );
    return;
  }

  saving.value = true;

  try {
    await api.post(
      "/admin/enrollments",
      {
        student_id:
          student.value.id,

        semester:
          enrollmentSemester.value,

        courses:
          selectedCourses.value,
      }
    );

    closeAddModal();

    notify(
      "Enrollment created successfully."
    );

    await Promise.all([
      loadSections(),
      loadEnrollments(),
      loadEnrollmentCountRecords(),
    ]);
  } catch (e) {
    notify(
      errorMessage(
        e,
        "Failed to create enrollment."
      ),
      "error"
    );
  } finally {
    saving.value = false;
  }
}


/*  EDIT  */

function openEdit(
  enrollment: Enrollment
) {
  editingEnrollment.value =
    enrollment;

  editForm.value = {
    section_id:
      enrollment.section?.id ||
      0,

    status:
      enrollment.status,
  };

  showEditModal.value =
    true;
}

function closeEditModal() {
  showEditModal.value =
    false;

  editingEnrollment.value =
    null;

  editForm.value = {
    section_id: 0,
    status: "Active",
  };
}

function updateEnrollmentInList(
  updated: Enrollment
) {
  const index =
    enrollments.value.findIndex(
      (x) =>
        x.id === updated.id
    );

  if (index !== -1) {
    enrollments.value[index] =
      updated;
  }

  const historyIndex =
    studentHistory.value.findIndex(
      (x) =>
        x.id === updated.id
    );

  if (historyIndex !== -1) {
    studentHistory.value[
      historyIndex
    ] = updated;
  }
}

async function updateEnrollment() {
  if (!editingEnrollment.value) {
    return;
  }

  if (!editForm.value.section_id) {
    notify(
      "Please select a section.",
      "error"
    );
    return;
  }

  saving.value = true;

  try {
    const { data } =
      await api.put(
        `/admin/enrollments/${editingEnrollment.value.id}`,
        {
          section_id:
            editForm.value.section_id,

          status:
            editForm.value.status,
        }
      );

    let updated:
      | Enrollment
      | null =
      data?.enrollment ||
      null;

    if (!updated) {
      const selectedSection =
        sections.value.find(
          (s) =>
            Number(s.id) ===
            Number(
              editForm.value
                .section_id
            )
        );

      updated = {
        ...editingEnrollment.value,

        status:
          editForm.value.status,

        section:
          selectedSection ||
          editingEnrollment.value
            .section,
      };
    }

    if (
      !updated.result &&
      editingEnrollment.value.result
    ) {
      updated.result =
        editingEnrollment.value.result;
    }

    updated.display_status =
      updated.result?.grade
        ?.toUpperCase()
        .trim() === "F"
        ? "Failed"
        : updated.status;

    updateEnrollmentInList(
      updated
    );

    closeEditModal();

    notify(
      "Enrollment updated successfully."
    );

    await Promise.all([
      loadSections(),
      loadEnrollmentCountRecords(),
    ]);
  } catch (e) {
    notify(
      errorMessage(
        e,
        "Failed to update enrollment."
      ),
      "error"
    );
  } finally {
    saving.value = false;
  }
}


/*  DELETE  */

function openDeleteModal(
  enrollment: Enrollment
) {
  deleteTarget.value =
    enrollment;

  showDeleteModal.value =
    true;
}

function closeDeleteModal() {
  if (saving.value) return;

  showDeleteModal.value =
    false;

  deleteTarget.value = null;
}

async function deleteEnrollment() {
  if (!deleteTarget.value) {
    return;
  }

  saving.value = true;

  try {
    await api.delete(
      `/admin/enrollments/${deleteTarget.value.id}`
    );

    closeDeleteModal();

    notify(
      "Enrollment deleted successfully."
    );

    await loadSections();

    if (
      showStudentHistory.value &&
      student.value
    ) {
      await searchStudent();
    } else {
      await loadEnrollments();
    }

    await loadEnrollmentCountRecords();
  } catch (e) {
    notify(
      errorMessage(
        e,
        "Failed to delete enrollment."
      ),
      "error"
    );
  } finally {
    saving.value = false;
  }
}


/*  PAGINATION  */

function previousPage() {
  if (currentPage.value > 1) {
    currentPage.value--;
  }
}

function nextPage() {
  if (
    currentPage.value <
    totalPages.value
  ) {
    currentPage.value++;
  }
}


/*  INIT  */

onMounted(async () => {
  await Promise.all([
    loadDepartments(),
    loadBatches(),
    loadSections(),
  ]);

  await loadEnrollments();
});
</script>

<template>
  <AdminLayout title="Enrollment Management" subtitle="Search and manage student course enrollments.">
    <div class="enrollment-page">

      <!-- HEADER -->
      <div class="page-header">
        <div>
          <h1>Enrollment Records</h1>
          <p>Manage student course enrollments, sections and statuses.</p>
        </div>

        <button class="btn btn-primary" @click="openAddModal">
          <Plus :size="17" />
          Add Enrollment
        </button>
      </div>

      <!-- FEEDBACK -->
      <div v-if="feedback" class="feedback" :class="feedback.type">
        <span>{{ feedback.message }}</span>
        <button class="feedback-close" @click="clearFeedback">
          <X :size="16" />
        </button>
      </div>

      <!-- SEARCH -->
      <div class="card">
        <div class="card-title">
          <div class="title-icon">
            <Search :size="18" />
          </div>
          <div>
            <h2>Search by Student ID</h2>
            <p>View all enrollment history of a student.</p>
          </div>
        </div>

        <div class="student-search-row">
          <div class="search-input">
            <Search :size="18" />
            <input v-model="searchStudentId" placeholder="Enter Student ID" @keyup.enter="searchStudent" />
          </div>

          <button class="btn btn-primary" :disabled="searchingStudent" @click="searchStudent">
            <Search :size="16" />
            {{ searchingStudent ? "Searching..." : "Search Student" }}
          </button>

          <button v-if="showStudentHistory || searchStudentId" class="btn btn-secondary" @click="clearStudentSearch">
            <X :size="16" />
            Clear
          </button>
        </div>
      </div>

      <!-- STUDENT HISTORY -->
      <div v-if="showStudentHistory && student" class="card">
        <div class="panel-header">
          <div>
            <h2>Student Enrollment History</h2>
            <p>{{ student.name }} · {{ student.user_id || "-" }}</p>
          </div>

          <div class="history-header-actions">
            <button class="btn btn-secondary" @click="openHistoricalRestoreModal">
              <RotateCcw :size="15" />
              Restore Historical
            </button>

            <span class="record-count">
              {{ historyCount }} records
            </span>
          </div>
        </div>

        <div class="student-info">
          <div>
            <span>Name</span>
            <strong>{{ student.name }}</strong>
          </div>

          <div>
            <span>Student ID</span>
            <strong>{{ student.user_id || "-" }}</strong>
          </div>

          <div>
            <span>Department</span>
            <strong>{{ departmentCode(student.department) }}</strong>
          </div>

          <div>
            <span>Batch</span>
            <strong>{{ batchLabel(student.batch) }}</strong>
          </div>

          <div>
            <span>Section</span>
            <strong>{{ student.section?.section_name || "-" }}</strong>
          </div>

          <div>
            <span>Current Semester</span>
            <strong>
              {{
                student.current_semester ||
                student.batch?.current_semester ||
                "-"
              }}
            </strong>
          </div>
        </div>

        <!-- RESTORE NOTICE -->
        <div v-if="loadingRestore" class="restore-loading">
          Checking deleted result enrollments...
        </div>

        <div v-if="restoreResults.length" class="restore-panel">
          <div class="restore-header">
            <div>
              <h3>Deleted Enrollment(s) Found</h3>
              <p>
                These courses have results but their enrollment
                record is missing.
              </p>
            </div>

            <span class="record-count">
              {{ restoreResults.length }}
            </span>
          </div>

          <div class="restore-list">
            <div v-for="result in restoreResults" :key="result.id" class="restore-item">
              <div>
                <strong>{{ result.course.course_code }}</strong>
                <span>{{ result.course.course_name }}</span>
              </div>

              <div class="restore-meta">
                <span>{{ result.semester }}</span>
                <span>Grade: {{ result.grade || "-" }}</span>

                <button class="btn btn-restore" @click="openRestoreModal(result)">
                  <RotateCcw :size="14" />
                  Restore
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="table-wrapper">
          <table class="custom-table">
            <thead>
              <tr>
                <th>Course</th>
                <th>Course Name</th>
                <th>Credit</th>
                <th>Semester</th>
                <th>Section</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="enrollment in studentHistory" :key="enrollment.id">
                <td>
                  <strong>{{ enrollment.course.course_code }}</strong>
                </td>

                <td>{{ enrollment.course.course_name }}</td>
                <td>{{ enrollment.course.credit }}</td>
                <td>{{ enrollment.semester }}</td>
                <td>{{ sectionName(enrollment) }}</td>

                <td>
                  <span class="status-badge" :class="statusClass(displayStatus(enrollment))">
                    {{ displayStatus(enrollment) }}
                  </span>
                </td>

                <td>
                  <div class="actions">
                    <button class="icon-btn edit" title="Edit" @click="openEdit(enrollment)">
                      <Edit2 :size="15" />
                    </button>

                    <button class="icon-btn delete" title="Delete" @click="openDeleteModal(enrollment)">
                      <Trash2 :size="15" />
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!studentHistory.length">
                <td colspan="7" class="empty-state">
                  No enrollment history found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- FILTERS -->
      <div v-if="!showStudentHistory" class="card filter-card">
        <div class="card-title">
          <div class="title-icon">
            <Filter :size="18" />
          </div>
          <div>
            <h2>Filter Enrollment Records</h2>
            <p>Narrow down records using filters.</p>
          </div>
        </div>

        <div class="filter-grid">
          <div class="form-group">
            <label>Department</label>
            <select v-model="filters.department" @change="
              filters.batch_id = '';
            filters.section_id = '';
            ">
              <option value="">All Departments</option>

              <option v-for="department in departments" :key="department.id" :value="department.code">
                {{ department.code }} - {{ department.name }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Batch</label>
            <select v-model="filters.batch_id" @change="filters.section_id = ''">
              <option value="">All Batches</option>

              <option v-for="batch in filteredBatches" :key="batch.id" :value="batch.id">
                Batch {{ batch.batch_number }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Section</label>
            <select v-model="filters.section_id">
              <option value="">All Sections</option>

              <option v-for="section in filteredSections" :key="section.id" :value="section.id">
                Section {{ section.section_name }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Semester</label>
            <select v-model="filters.semester">
              <option value="">All Semesters</option>

              <option v-for="semester in semesters" :key="semester" :value="semester">
                {{ semester }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Status</label>
            <select v-model="filters.status">
              <option value="">All Status</option>

              <option v-for="status in statuses" :key="status" :value="status">
                {{ status }}
              </option>
            </select>
          </div>
        </div>

        <div class="filter-actions">
          <button class="btn btn-primary" @click="applyFilters">
            <Search :size="16" />
            Search
          </button>

          <button class="btn btn-secondary" @click="resetFilters">
            <RefreshCw :size="16" />
            Reset
          </button>
        </div>
      </div>

      <!-- MAIN RECORDS -->
      <div v-if="!showStudentHistory" class="card">
        <div class="panel-header">
          <div>
            <h2>Enrollment Records</h2>
            <p>{{ enrollmentCount }} records found</p>
          </div>

          <span v-if="enrollmentCount" class="record-count">
            {{ pageStart }}–{{ pageEnd }} of {{ enrollmentCount }}
          </span>
        </div>

        <div class="table-wrapper">
          <table class="custom-table">
            <thead>
              <tr>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Department</th>
                <th>Batch</th>
                <th>Section</th>
                <th>Course</th>
                <th>Semester</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="loading">
                <td colspan="9" class="empty-state">
                  Loading enrollment records...
                </td>
              </tr>

              <tr v-else v-for="enrollment in paginatedEnrollments" :key="enrollment.id">
                <td>
                  <strong>
                    {{ enrollment.student.user?.user_id || "-" }}
                  </strong>
                </td>

                <td>
                  {{ enrollment.student.user?.name || "-" }}
                </td>

                <td>
                  {{
                    departmentCode(
                      enrollment.student.department
                    )
                  }}
                </td>

                <td>
                  Batch {{ batchLabel(enrollment.student.batch) }}
                </td>

                <td>
                  {{ enrollment.section?.section_name || "-" }}
                </td>

                <td>
                  <div class="course-cell">
                    <strong>
                      {{ enrollment.course.course_code }}
                    </strong>
                    <small>
                      {{ enrollment.course.course_name }}
                    </small>
                  </div>
                </td>

                <td>{{ enrollment.semester }}</td>

                <td>
                  <span class="status-badge" :class="statusClass(displayStatus(enrollment))">
                    {{ displayStatus(enrollment) }}
                  </span>
                </td>

                <td>
                  <div class="actions">
                    <button class="icon-btn edit" title="Edit" @click="openEdit(enrollment)">
                      <Edit2 :size="15" />
                    </button>

                    <button class="icon-btn delete" title="Delete" @click="openDeleteModal(enrollment)">
                      <Trash2 :size="15" />
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!loading && !enrollments.length">
                <td colspan="9" class="empty-state">
                  No enrollment records found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="!loading && enrollmentCount" class="pagination">
          <div class="page-info">
            Showing <strong>{{ pageStart }}</strong>–
            <strong>{{ pageEnd }}</strong> of
            <strong>{{ enrollmentCount }}</strong>
          </div>

          <div class="page-buttons">
            <button class="page-btn" :disabled="currentPage === 1" @click="previousPage">
              <ChevronLeft :size="15" />
              Previous
            </button>

            <span class="page-number">
              Page {{ currentPage }} of {{ totalPages }}
            </span>

            <button class="page-btn" :disabled="currentPage === totalPages" @click="nextPage">
              Next
              <ChevronRight :size="15" />
            </button>
          </div>
        </div>
      </div>

      <!--  ADD MODAL  -->

      <div v-if="showAddModal" class="modal-overlay" @click.self="closeAddModal">
        <div class="modal">
          <div class="modal-header">
            <div>
              <h2>Add Enrollment</h2>
              <p>Enroll a student into one or more courses.</p>
            </div>

            <button class="modal-close" @click="closeAddModal">
              <X :size="19" />
            </button>
          </div>

          <div class="modal-body">
            <div class="student-find">
              <div class="form-group">
                <label>Student ID</label>
                <input v-model="formStudentId" placeholder="Enter Student ID" @keyup.enter="loadStudentInfo" />
              </div>

              <button class="btn btn-primary find-btn" :disabled="saving" @click="loadStudentInfo">
                <Search :size="16" />
                Find Student
              </button>
            </div>

            <div v-if="student" class="student-summary">
              <div class="summary-icon">
                <GraduationCap :size="22" />
              </div>

              <div>
                <strong>{{ student.name }}</strong>

                <span>
                  {{ student.user_id || formStudentId }}
                  · {{ departmentCode(student.department) }}
                  · Batch {{ batchLabel(student.batch) }}
                </span>
              </div>
            </div>

            <div v-if="student" class="form-group">
              <label>Enrollment Semester</label>
              <input v-model="enrollmentSemester" readonly />
            </div>

            <div v-if="student" class="course-section">
              <div class="course-section-header">
                <div>
                  <h3>Select Courses</h3>
                  <p>Choose courses and assign sections.</p>
                </div>

                <span>{{ selectedCourses.length }} selected</span>
              </div>

              <div v-if="courses.length" class="course-list">
                <div v-for="course in courses" :key="course.id" class="course-item" :class="{
                  selected: selectedCourseIds.includes(course.id),
                }">
                  <label class="course-check">
                    <input type="checkbox" :checked="selectedCourseIds.includes(course.id)"
                      @change="toggleCourse(course.id)" />

                    <span class="custom-checkbox">✓</span>
                  </label>

                  <div class="course-info">
                    <strong>{{ course.course_code }}</strong>
                    <span>{{ course.course_name }}</span>
                  </div>

                  <span class="course-credit">
                    {{ course.credit }} Credit
                  </span>

                  <select v-if="selectedCourseIds.includes(course.id)" :value="selectedCourses.find(
                    x => x.course_id === course.id
                  )?.section_id || 0
                    " @change="
                      selectSection(
                        course.id,
                        Number(
                          ($event.target as HTMLSelectElement).value
                        )
                      )
                      ">
                    <option value="0">Select Section</option>

                    <option v-for="section in sections" :key="section.id" :value="section.id" :disabled="isCourseSectionFull(course.id, section)
                      ">
                      {{ section.section_name }} —
                      {{
                        loadingSectionCounts
                          ? "..."
                          : getCourseSectionCount(
                            course.id,
                            section.id
                          )
                      }}/{{ section.capacity }}
                      {{
                        isCourseSectionFull(course.id, section)
                          ? "(Full)"
                          : ""
                      }}
                    </option>
                  </select>
                </div>
              </div>

              <div v-else class="empty-course">
                <BookOpen :size="25" />
                <p>No courses available.</p>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn btn-secondary" @click="closeAddModal">
              Cancel
            </button>

            <button class="btn btn-primary" :disabled="saving" @click="saveEnrollment">
              <Plus :size="16" />
              {{ saving ? "Enrolling..." : "Enroll Selected" }}
            </button>
          </div>
        </div>
      </div>

      <!--  EDIT MODAL  -->

      <div v-if="showEditModal && editingEnrollment" class="modal-overlay" @click.self="closeEditModal">
        <div class="modal small-modal">
          <div class="modal-header">
            <div>
              <h2>Edit Enrollment</h2>
              <p>Update section or enrollment status.</p>
            </div>

            <button class="modal-close" @click="closeEditModal">
              <X :size="19" />
            </button>
          </div>

          <div class="modal-body">
            <div class="edit-course-info">
              <div class="summary-icon">
                <BookOpen :size="21" />
              </div>

              <div>
                <strong>
                  {{ editingEnrollment.course.course_code }}
                </strong>

                <span>
                  {{ editingEnrollment.course.course_name }}
                </span>
              </div>
            </div>

            <div class="form-group">
              <label>Section</label>

              <select v-model.number="editForm.section_id">
                <option :value="0">Select Section</option>

                <option v-for="section in sections.filter(
                  x =>
                    x.batch_id ===
                    editingEnrollment?.student?.batch?.id
                )" :key="section.id" :value="section.id">
                  {{ section.section_name }} —
                  {{ getEditSectionCount(section) }}/{{ section.capacity }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Status</label>

              <select v-model="editForm.status">
                <option v-for="status in statuses" :key="status" :value="status">
                  {{ status }}
                </option>
              </select>
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn btn-secondary" @click="closeEditModal">
              Cancel
            </button>

            <button class="btn btn-primary" :disabled="saving" @click="updateEnrollment">
              <RefreshCw :size="16" />
              {{ saving ? "Saving..." : "Save Changes" }}
            </button>
          </div>
        </div>
      </div>

      <!--  HISTORICAL RESTORE MODAL  -->

      <div v-if="showHistoricalRestoreModal && student" class="modal-overlay" @click.self="closeHistoricalRestoreModal">
        <div class="modal small-modal">
          <div class="modal-header">
            <div>
              <h2>Restore Historical Enrollment</h2>
              <p>
                Restore an old semester enrollment without changing
                the student's current batch semester.
              </p>
            </div>

            <button class="modal-close" @click="closeHistoricalRestoreModal">
              <X :size="19" />
            </button>
          </div>

          <div class="modal-body">

            <div class="student-summary">
              <div class="summary-icon">
                <GraduationCap :size="22" />
              </div>

              <div>
                <strong>{{ student.name }}</strong>

                <span>
                  {{ student.user_id || "-" }}
                  · {{ departmentCode(student.department) }}
                  · Batch {{ batchLabel(student.batch) }}
                  · Current {{ student.current_semester || "-" }}
                </span>
              </div>
            </div>

            <div class="form-group">
              <label>Historical Semester</label>

              <select v-model="historicalSemester" @change="loadHistoricalOptions">
                <option value="">
                  Select Semester
                </option>

                <option v-for="semester in semesters" :key="semester" :value="semester">
                  {{ semester }}
                </option>
              </select>
            </div>

            <div v-if="historicalSemester" class="form-group">
              <label>Course</label>

              <select v-model.number="historicalCourseId" :disabled="loadingHistoricalOptions">
                <option :value="0">
                  {{
                    loadingHistoricalOptions
                      ? "Loading courses..."
                      : "Select Course"
                  }}
                </option>

                <option v-for="course in historicalCourses" :key="course.id" :value="course.id"
                  :disabled="course.already_enrolled">
                  {{ course.course_code }}
                  —
                  {{ course.course_name }}
                  {{
                    course.already_enrolled
                      ? "(Already Enrolled)"
                      : ""
                  }}
                </option>
              </select>
            </div>

            <div v-if="historicalSemester" class="form-group">
              <label>Section</label>

              <select v-model.number="historicalSectionId" :disabled="loadingHistoricalOptions">
                <option :value="0">
                  Select Section
                </option>

                <option v-for="section in historicalSections" :key="section.id" :value="section.id">
                  Section {{ section.section_name }}
                </option>
              </select>
            </div>

            <div v-if="historicalSemester" class="form-group">
              <label>Status</label>

              <select v-model="historicalStatus">
                <option value="Active">
                  Active
                </option>

                <option value="Completed">
                  Completed
                </option>

                <option value="Dropped">
                  Dropped
                </option>
              </select>

              <small class="form-help">
                If a result already exists for this course,
                the system will automatically use Completed.
              </small>
            </div>

          </div>

          <div class="modal-footer">
            <button class="btn btn-secondary" :disabled="saving" @click="closeHistoricalRestoreModal">
              Cancel
            </button>

            <button class="btn btn-primary" :disabled="saving ||
              loadingHistoricalOptions ||
              !historicalSemester ||
              !historicalCourseId ||
              !historicalSectionId
              " @click="restoreHistoricalEnrollment">
              <RotateCcw :size="16" />
              {{
                saving
                  ? "Restoring..."
                  : "Restore Enrollment"
              }}
            </button>
          </div>
        </div>
      </div>

      <!--  RESTORE MODAL  -->

      <div v-if="showRestoreModal && restoreTarget" class="modal-overlay" @click.self="closeRestoreModal">
        <div class="modal delete-modal">
          <div class="restore-icon">
            <RotateCcw :size="24" />
          </div>

          <div class="delete-content">
            <h2>Restore Enrollment?</h2>

            <p>
              Restore
              <strong>{{ restoreTarget.course.course_code }}</strong>
              for
              <strong>{{ restoreTarget.semester }}</strong>
              semester?
              <br />
              Existing result will be preserved.
            </p>
          </div>

          <div class="modal-footer">
            <button class="btn btn-secondary" :disabled="saving" @click="closeRestoreModal">
              Cancel
            </button>

            <button class="btn btn-primary" :disabled="saving" @click="restoreEnrollment">
              <RotateCcw :size="16" />
              {{ saving ? "Restoring..." : "Restore Enrollment" }}
            </button>
          </div>
        </div>
      </div>

      <!--  DELETE MODAL  -->

      <div v-if="showDeleteModal && deleteTarget" class="modal-overlay" @click.self="closeDeleteModal">
        <div class="modal delete-modal">
          <div class="delete-icon">
            <Trash2 :size="24" />
          </div>

          <div class="delete-content">
            <h2>Delete Enrollment?</h2>

            <p>
              Are you sure you want to delete the enrollment for
              <strong>
                {{ deleteTarget.course.course_code }}
              </strong>
              ?
              <br />
              This action cannot be undone.
            </p>
          </div>

          <div class="modal-footer">
            <button class="btn btn-secondary" :disabled="saving" @click="closeDeleteModal">
              Cancel
            </button>

            <button class="btn btn-danger" :disabled="saving" @click="deleteEnrollment">
              <Trash2 :size="16" />
              {{ saving ? "Deleting..." : "Delete Enrollment" }}
            </button>
          </div>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.enrollment-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.page-header,
.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
}

.page-header h1 {
  margin: 0;
  font-size: 24px;
  color: #111827;
}

.page-header p,
.card-title p,
.panel-header p {
  margin: 4px 0 0;
  color: #6b7280;
  font-size: 12px;
}

.card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
}

.btn {
  height: 40px;
  padding: 0 14px;
  border-radius: 7px;
  border: 1px solid transparent;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.btn-primary {
  background: #0f2747;
  color: #fff;
  border-color: #0f2747;
}

.btn-primary:hover {
  background: #17385f;
}

.btn-primary:disabled,
.btn-danger:disabled {
  opacity: .55;
  cursor: not-allowed;
}

.btn-secondary {
  background: #fff;
  color: #374151;
  border-color: #d1d5db;
}

.btn-secondary:hover {
  background: #f9fafb;
}

.btn-danger {
  background: #dc2626;
  color: #fff;
  border-color: #dc2626;
}

.feedback {
  min-height: 44px;
  padding: 10px 13px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 13px;
}

.feedback.success {
  color: #166534;
  background: #f0fdf4;
  border: 1px solid #86efac;
}

.feedback.error {
  color: #b91c1c;
  background: #fef2f2;
  border: 1px solid #fca5a5;
}

.feedback-close,
.modal-close {
  border: 0;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.feedback-close {
  background: transparent;
  color: inherit;
}

.card-title {
  padding: 18px 20px;
  display: flex;
  align-items: center;
  gap: 11px;
}

.title-icon,
.summary-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eef3f8;
  color: #0f2747;
  border-radius: 8px;
}

.title-icon {
  width: 36px;
  height: 36px;
}

.card-title h2,
.panel-header h2 {
  margin: 0;
  font-size: 15px;
  color: #111827;
}

.student-search-row {
  padding: 0 20px 20px;
  display: flex;
  gap: 9px;
}

.search-input {
  height: 42px;
  flex: 1;
  max-width: 500px;
  padding: 0 13px;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  display: flex;
  align-items: center;
  gap: 9px;
  color: #9ca3af;
}

.search-input:focus-within {
  border-color: #0f2747;
  box-shadow: 0 0 0 3px #e5edf5;
}

.search-input input {
  width: 100%;
  height: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  font-size: 13px;
}

.filter-card {
  padding-bottom: 20px;
}

.filter-grid {
  padding: 0 20px;
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 12px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  color: #374151;
  font-size: 12px;
  font-weight: 600;
}

.form-group input,
.form-group select {
  width: 100%;
  height: 40px;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  padding: 0 11px;
  outline: 0;
  background: #fff;
  color: #111827;
  font-size: 13px;
}

.form-group input:focus,
.form-group select:focus {
  border-color: #0f2747;
  box-shadow: 0 0 0 3px #e5edf5;
}

.filter-actions {
  padding: 15px 20px 0;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.panel-header {
  padding: 17px 20px;
  border-bottom: 1px solid #e5e7eb;
}

.record-count {
  padding: 5px 9px;
  border-radius: 6px;
  background: #f3f4f6;
  color: #4b5563;
  font-size: 11px;
  font-weight: 600;
}

.student-info {
  padding: 17px 20px;
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 15px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.student-info div {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.student-info span {
  color: #6b7280;
  font-size: 11px;
}

.student-info strong {
  color: #111827;
  font-size: 12px;
}

.table-wrapper {
  width: 100%;
  overflow-x: auto;
}

.custom-table {
  width: 100%;
  min-width: 1000px;
  border-collapse: collapse;
}

.custom-table th {
  padding: 11px 15px;
  text-align: left;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  color: #6b7280;
  font-size: 10px;
  text-transform: uppercase;
  white-space: nowrap;
}

.custom-table td {
  padding: 12px 15px;
  border-bottom: 1px solid #f0f0f0;
  color: #374151;
  font-size: 12px;
  white-space: nowrap;
}

.custom-table tbody tr:hover {
  background: #fafafa;
}

.empty-state {
  text-align: center !important;
  padding: 35px !important;
  color: #9ca3af !important;
}

.status-badge {
  display: inline-flex;
  padding: 4px 8px;
  border-radius: 5px;
  font-size: 10px;
  font-weight: 700;
}

.status-badge.active {
  color: #166534;
  background: #dcfce7;
}

.status-badge.completed {
  color: #1d4ed8;
  background: #dbeafe;
}

.status-badge.dropped {
  color: #b91c1c;
  background: #fee2e2;
}

.status-badge.failed {
  color: #991b1b;
  background: #fecaca;
}

.actions {
  display: flex;
  gap: 5px;
}

.icon-btn {
  width: 30px;
  height: 30px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.icon-btn.edit {
  color: #2563eb;
}

.icon-btn.delete {
  color: #dc2626;
}

.icon-btn.edit:hover {
  background: #eff6ff;
}

.icon-btn.delete:hover {
  background: #fef2f2;
}

.pagination {
  min-height: 58px;
  padding: 10px 20px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.page-info {
  color: #6b7280;
  font-size: 11px;
}

.page-buttons {
  display: flex;
  align-items: center;
  gap: 7px;
}

.page-btn {
  height: 34px;
  padding: 0 10px;
  border: 1px solid #d1d5db;
  background: #fff;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  cursor: pointer;
  font-size: 11px;
}

.page-btn:disabled {
  opacity: .4;
  cursor: not-allowed;
}

.page-number {
  min-width: 80px;
  text-align: center;
  font-size: 11px;
  font-weight: 600;
}

.form-help {
  color: #6b7280;
  font-size: 10px;
  line-height: 1.5;
}

/*  RESTORE  */

.restore-loading {
  padding: 10px 20px;
  color: #6b7280;
  font-size: 11px;
  background: #f9fafb;
}

.restore-panel {
  margin: 15px 20px;
  border: 1px solid #d7e1eb;
  border-radius: 8px;
  background: #f5f8fb;
  overflow: hidden;
}

.restore-header {
  padding: 12px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #d7e1eb;
}

.restore-header h3 {
  margin: 0;
  font-size: 13px;
  color: #111827;
}

.restore-header p {
  margin: 3px 0 0;
  font-size: 11px;
  color: #6b7280;
}

.restore-list {
  display: flex;
  flex-direction: column;
}

.restore-item {
  padding: 11px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  border-bottom: 1px solid #e5e7eb;
  background: #fff;
}

.restore-item:last-child {
  border-bottom: 0;
}

.restore-item>div:first-child {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.restore-item strong {
  font-size: 12px;
  color: #111827;
}

.restore-item span {
  color: #6b7280;
  font-size: 10px;
}

.restore-meta {
  display: flex;
  align-items: center;
  gap: 9px;
}

.btn-restore {
  height: 32px;
  padding: 0 10px;
  color: #0f2747;
  background: #eef3f8;
  border-color: #cbd8e5;
}

.restore-icon {
  width: 52px;
  height: 52px;
  margin-bottom: 13px;
  border-radius: 50%;
  background: #eef3f8;
  color: #0f2747;
  display: flex;
  align-items: center;
  justify-content: center;
}

.history-header-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

/*  MODAL  */

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(17, 24, 39, .55);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal {
  width: min(760px, 100%);
  max-height: 90vh;
  background: #fff;
  border-radius: 11px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 45px rgba(0, 0, 0, .15);
}

.small-modal {
  width: min(500px, 100%);
}

.modal-header {
  padding: 17px 20px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 0;
  font-size: 17px;
  color: #111827;
}

.modal-header p {
  margin: 4px 0 0;
  color: #6b7280;
  font-size: 12px;
}

.modal-close {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  background: #f3f4f6;
  color: #4b5563;
}

.modal-body {
  padding: 20px;
  overflow-y: auto;
}

.modal-footer {
  padding: 13px 20px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

/*  ADD  */

.student-find {
  display: flex;
  align-items: flex-end;
  gap: 9px;
  margin-bottom: 15px;
}

.student-find .form-group {
  flex: 1;
}

.student-summary,
.edit-course-info {
  padding: 12px;
  margin-bottom: 16px;
  border: 1px solid #d7e1eb;
  background: #f5f8fb;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.summary-icon {
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  background: #fff;
}

.student-summary div:last-child,
.edit-course-info div:last-child {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.student-summary strong,
.edit-course-info strong {
  color: #111827;
  font-size: 13px;
}

.student-summary span,
.edit-course-info span {
  color: #6b7280;
  font-size: 11px;
}

.course-section {
  margin-top: 20px;
}

.course-section-header {
  margin-bottom: 9px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.course-section-header h3 {
  margin: 0;
  font-size: 14px;
  color: #111827;
}

.course-section-header p {
  margin: 3px 0 0;
  color: #6b7280;
  font-size: 11px;
}

.course-section-header>span {
  padding: 4px 8px;
  border-radius: 5px;
  background: #f3f4f6;
  color: #4b5563;
  font-size: 10px;
}

.course-list {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}

.course-item {
  min-height: 60px;
  padding: 10px 12px;
  display: flex;
  align-items: center;
  gap: 10px;
  border-bottom: 1px solid #f0f0f0;
}

.course-item:last-child {
  border-bottom: 0;
}

.course-item.selected {
  background: #f5f8fb;
}

.course-check {
  width: 20px;
  flex-shrink: 0;
  cursor: pointer;
}

.course-check input {
  display: none;
}

.custom-checkbox {
  width: 18px;
  height: 18px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: transparent;
  font-size: 11px;
}

.course-check input:checked+.custom-checkbox {
  background: #0f2747;
  border-color: #0f2747;
  color: #fff;
}

.course-info {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.course-info strong {
  color: #111827;
  font-size: 12px;
}

.course-info span {
  color: #6b7280;
  font-size: 10px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.course-credit {
  color: #6b7280;
  font-size: 10px;
  white-space: nowrap;
}

.course-item select {
  width: 175px;
  height: 34px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 0 8px;
  background: #fff;
  font-size: 11px;
}

.empty-course {
  padding: 35px;
  border: 1px dashed #d1d5db;
  border-radius: 8px;
  color: #9ca3af;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 7px;
}

.empty-course p {
  margin: 0;
  font-size: 12px;
}

/*  DELETE  */

.delete-modal {
  width: min(430px, 100%);
  padding: 25px;
  align-items: center;
  text-align: center;
}

.delete-icon {
  width: 52px;
  height: 52px;
  margin-bottom: 13px;
  border-radius: 50%;
  background: #fee2e2;
  color: #dc2626;
  display: flex;
  align-items: center;
  justify-content: center;
}

.delete-content h2 {
  margin: 0;
  font-size: 17px;
}

.delete-content p {
  margin: 8px 0 0;
  color: #6b7280;
  font-size: 12px;
  line-height: 1.6;
}

/*  RESPONSIVE  */

@media (max-width: 1100px) {
  .filter-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .student-info {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 760px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .history-header-actions {
    width: 100%;
    flex-wrap: wrap;
  }

  .student-search-row {
    flex-direction: column;
  }

  .search-input {
    max-width: none;
  }

  .filter-grid {
    grid-template-columns: 1fr;
  }

  .student-info {
    grid-template-columns: 1fr 1fr;
  }

  .student-find {
    flex-direction: column;
    align-items: stretch;
  }

  .course-item {
    flex-wrap: wrap;
  }

  .course-item select {
    width: 100%;
  }

  .pagination {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }

  .restore-item {
    flex-direction: column;
    align-items: flex-start;
  }

  .restore-meta {
    width: 100%;
    justify-content: space-between;
  }
}

@media (max-width: 500px) {
  .student-info {
    grid-template-columns: 1fr;
  }

  .page-buttons {
    width: 100%;
    justify-content: space-between;
  }
}
</style>