<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import {
  Plus,
  Search,
  Edit2,
  Trash2,
  X,
  BookOpen,
  GitBranch,
  Filter,
  RotateCcw,
} from "lucide-vue-next";
import AdminLayout from "@/components/admin/AdminLayout.vue";
import api from "@/services/api";

interface Department {
  id: number;
  code: string;
  name: string;
  status: string;
}

interface Course {
  id: number;
  course_code: string;
  course_name: string;
  department: string;
  semester: string;
  credit: number | string;
  course_type: string;
  description?: string;
  status: string;
}

interface Prerequisite {
  id: number;
  course_id: number;
  prerequisite_course_id: number;
  course?: Course;
  prerequisiteCourse?: Course;
  prerequisite_course?: Course;
}

const departments = ref<Department[]>([]);
const courses = ref<Course[]>([]);
const prerequisites = ref<Prerequisite[]>([]);

const loadingDepartments = ref(false);
const loadingCourses = ref(false);
const loadingPrerequisites = ref(false);
const saving = ref(false);

const search = ref("");
const departmentFilter = ref("");
const semesterFilter = ref("");

const showModal = ref(false);
const editingId = ref<number | null>(null);

const form = ref({
  department: "",
  course_id: "",
  prerequisite_course_id: "",
});

/*
  MESSAGE STATES
  All user-facing errors are shown inside bordered boxes.
  No browser alert() is used.
 */
const pageErrorMessage = ref("");
const modalErrorMessage = ref("");
const successMessage = ref("");
const deleteErrorMessage = ref("");

const deleteId = ref<number | null>(null);

/* PAGINATION */
const currentPage = ref(1);
const itemsPerPage = 10;

/* HELPERS */

const getCourse = (courseId: number) => {
  return courses.value.find((course) => course.id === courseId);
};

const getPrerequisiteCourse = (item: Prerequisite) => {
  return (
    item.prerequisiteCourse ||
    item.prerequisite_course ||
    getCourse(item.prerequisite_course_id)
  );
};

const getMainCourse = (item: Prerequisite) => {
  return item.course || getCourse(item.course_id);
};

/*
  Convert API error into a readable message.
 */
const getApiErrorMessage = (error: any, fallback: string) => {
  if (error.response?.data?.errors) {
    const errors = error.response.data.errors;

    const messages = Object.values(errors)
      .flat()
      .map((message) => String(message));

    if (messages.length) {
      return messages.join(" ");
    }
  }

  return (
    error.response?.data?.message ||
    error.message ||
    fallback
  );
};

/* LOAD DEPARTMENTS */

const loadDepartments = async () => {
  loadingDepartments.value = true;

  try {
    const response = await api.get("/admin/departments");

    departments.value = (response.data.departments || []).filter(
      (department: Department) => department.status === "Active"
    );
  } catch (error: any) {
    console.error("Departments API Error:", error);

    pageErrorMessage.value = getApiErrorMessage(
      error,
      "Failed to load departments."
    );
  } finally {
    loadingDepartments.value = false;
  }
};

/* LOAD COURSES */

const loadCourses = async () => {
  loadingCourses.value = true;

  try {
    const response = await api.get("/admin/courses");

    courses.value = response.data.courses || [];
  } catch (error: any) {
    console.error("Courses API Error:", error);

    pageErrorMessage.value = getApiErrorMessage(
      error,
      "Failed to load courses."
    );
  } finally {
    loadingCourses.value = false;
  }
};

/* LOAD PREREQUISITES */

const loadPrerequisites = async () => {
  loadingPrerequisites.value = true;

  try {
    const response = await api.get("/admin/prerequisites", {
      timeout: 10000,
    });

    prerequisites.value = (response.data.prerequisites || []).map(
      (item: any) => ({
        ...item,
        prerequisiteCourse:
          item.prerequisiteCourse ||
          item.prerequisite_course ||
          undefined,
        course: item.course || undefined,
      })
    );
  } catch (error: any) {
    console.error("Prerequisite API Error:", error);

    pageErrorMessage.value = getApiErrorMessage(
      error,
      "Failed to load prerequisites."
    );
  } finally {
    loadingPrerequisites.value = false;
  }
};

/* FILTER OPTIONS */

const semesters = computed(() => {
  return [...new Set(courses.value.map((course) => course.semester))].sort(
    (a, b) => {
      const first = parseInt(a) || 0;
      const second = parseInt(b) || 0;

      return first - second;
    }
  );
});

/* FILTERED COURSES */

const filteredCourses = computed(() => {
  const text = search.value.trim().toLowerCase();

  return courses.value.filter((course) => {
    const matchesSearch =
      !text ||
      course.course_code.toLowerCase().includes(text) ||
      course.course_name.toLowerCase().includes(text);

    const matchesDepartment =
      !departmentFilter.value ||
      course.department === departmentFilter.value;

    const matchesSemester =
      !semesterFilter.value ||
      course.semester === semesterFilter.value;

    return (
      matchesSearch &&
      matchesDepartment &&
      matchesSemester
    );
  });
});

/* PAGINATION */

const totalPages = computed(() => {
  return Math.ceil(
    filteredCourses.value.length / itemsPerPage
  );
});

const paginatedCourses = computed(() => {
  const start =
    (currentPage.value - 1) * itemsPerPage;

  const end = start + itemsPerPage;

  return filteredCourses.value.slice(start, end);
});

const visiblePages = computed(() => {
  const total = totalPages.value;
  const current = currentPage.value;

  if (total <= 5) {
    return Array.from(
      { length: total },
      (_, index) => index + 1
    );
  }

  if (current <= 3) {
    return [1, 2, 3, 4, 5];
  }

  if (current >= total - 2) {
    return [
      total - 4,
      total - 3,
      total - 2,
      total - 1,
      total,
    ];
  }

  return [
    current - 2,
    current - 1,
    current,
    current + 1,
    current + 2,
  ];
});

const goToPage = (page: number) => {
  if (
    page < 1 ||
    page > totalPages.value
  ) {
    return;
  }

  currentPage.value = page;
};

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
  }
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
  }
};

/*
  Whenever search or filters change,
  return to page 1.
 */
watch(
  [search, departmentFilter, semesterFilter],
  () => {
    currentPage.value = 1;
  }
);

/*
  If deleting/filtering causes the current page
  to become invalid, automatically move to the
  last available page.
 */
watch(
  totalPages,
  (newTotalPages) => {
    if (
      newTotalPages > 0 &&
      currentPage.value > newTotalPages
    ) {
      currentPage.value = newTotalPages;
    }

    if (newTotalPages === 0) {
      currentPage.value = 1;
    }
  }
);

/* COURSES FOR SELECTED DEPARTMENT */

const departmentCourses = computed(() => {
  if (!form.value.department) {
    return [];
  }

  return courses.value.filter(
    (course) =>
      course.department === form.value.department
  );
});

/* GET PREREQUISITES FOR A COURSE */

const getPrerequisites = (courseId: number) => {
  return prerequisites.value.filter(
    (item) =>
      Number(item.course_id) === Number(courseId)
  );
};

/* AVAILABLE PREREQUISITE COURSES */

const availablePrerequisiteCourses = computed(() => {
  if (
    !form.value.department ||
    !form.value.course_id
  ) {
    return [];
  }

  const selectedCourse = getCourse(
    Number(form.value.course_id)
  );

  if (!selectedCourse) {
    return [];
  }

  const existingIds = prerequisites.value
    .filter(
      (item) =>
        Number(item.course_id) === selectedCourse.id &&
        Number(item.id) !== Number(editingId.value)
    )
    .map((item) =>
      Number(item.prerequisite_course_id)
    );

  return courses.value.filter((course) => {
    const isSameCourse =
      course.id === selectedCourse.id;

    const isSameDepartment =
      course.department ===
      form.value.department;

    const alreadyExists =
      existingIds.includes(course.id);

    return (
      !isSameCourse &&
      isSameDepartment &&
      !alreadyExists
    );
  });
});

/* STATISTICS */

const coursesWithPrerequisites = computed(() => {
  return new Set(
    prerequisites.value.map(
      (item) => item.course_id
    )
  ).size;
});

/* MODAL */

const openAddModal = (courseId?: number) => {
  editingId.value = null;

  const selectedCourse = courseId
    ? getCourse(courseId)
    : undefined;

  form.value = {
    department:
      selectedCourse?.department || "",

    course_id: courseId
      ? String(courseId)
      : "",

    prerequisite_course_id: "",
  };

  modalErrorMessage.value = "";
  pageErrorMessage.value = "";
  deleteErrorMessage.value = "";

  showModal.value = true;
};

const openEditModal = (
  item: Prerequisite
) => {
  editingId.value = item.id;

  const course = getCourse(
    item.course_id
  );

  form.value = {
    department:
      course?.department || "",

    course_id: String(
      item.course_id
    ),

    prerequisite_course_id:
      String(
        item.prerequisite_course_id
      ),
  };

  modalErrorMessage.value = "";
  pageErrorMessage.value = "";
  deleteErrorMessage.value = "";

  showModal.value = true;
};

const closeModal = () => {
  if (saving.value) {
    return;
  }

  showModal.value = false;
  editingId.value = null;
  modalErrorMessage.value = "";

  form.value = {
    department: "",
    course_id: "",
    prerequisite_course_id: "",
  };
};

const departmentChanged = () => {
  form.value.course_id = "";
  form.value.prerequisite_course_id = "";
  modalErrorMessage.value = "";
};

const courseChanged = () => {
  form.value.prerequisite_course_id = "";
  modalErrorMessage.value = "";
};

/* SAVE */

const savePrerequisite = async () => {
  modalErrorMessage.value = "";

  if (
    !form.value.department ||
    !form.value.course_id ||
    !form.value.prerequisite_course_id
  ) {
    modalErrorMessage.value =
      "Please select department, course and prerequisite course.";

    return;
  }

  if (
    Number(form.value.course_id) ===
    Number(form.value.prerequisite_course_id)
  ) {
    modalErrorMessage.value =
      "A course cannot be its own prerequisite.";

    return;
  }

  const selectedCourse = getCourse(
    Number(form.value.course_id)
  );

  const selectedPrerequisite = getCourse(
    Number(
      form.value.prerequisite_course_id
    )
  );

  if (
    !selectedCourse ||
    !selectedPrerequisite ||
    selectedCourse.department !==
      form.value.department ||
    selectedPrerequisite.department !==
      form.value.department
  ) {
    modalErrorMessage.value =
      "Selected courses must belong to the selected department.";

    return;
  }

  saving.value = true;

  try {
    if (editingId.value) {
      await api.put(
        `/admin/prerequisites/${editingId.value}`,
        {
          course_id: Number(
            form.value.course_id
          ),

          prerequisite_course_id:
            Number(
              form.value
                .prerequisite_course_id
            ),
        }
      );

      successMessage.value =
        "Prerequisite updated successfully.";
    } else {
      await api.post(
        "/admin/prerequisites",
        {
          course_id: Number(
            form.value.course_id
          ),

          prerequisite_course_id:
            Number(
              form.value
                .prerequisite_course_id
            ),
        }
      );

      successMessage.value =
        "Prerequisite added successfully.";
    }

    showModal.value = false;
    editingId.value = null;

    form.value = {
      department: "",
      course_id: "",
      prerequisite_course_id: "",
    };

    await loadPrerequisites();

    setTimeout(() => {
      successMessage.value = "";
    }, 3000);
  } catch (error: any) {
    console.error(
      "Save prerequisite error:",
      error
    );

    modalErrorMessage.value =
      getApiErrorMessage(
        error,
        "Something went wrong. Please try again."
      );
  } finally {
    saving.value = false;
  }
};

/* DELETE */

const askDelete = (id: number) => {
  deleteId.value = id;
  deleteErrorMessage.value = "";
};

const cancelDelete = () => {
  if (saving.value) {
    return;
  }

  deleteId.value = null;
  deleteErrorMessage.value = "";
};

const deletePrerequisite = async () => {
  if (!deleteId.value) {
    return;
  }

  deleteErrorMessage.value = "";
  saving.value = true;

  try {
    await api.delete(
      `/admin/prerequisites/${deleteId.value}`
    );

    deleteId.value = null;

    successMessage.value =
      "Prerequisite deleted successfully.";

    await loadPrerequisites();

    setTimeout(() => {
      successMessage.value = "";
    }, 3000);
  } catch (error: any) {
    console.error(
      "Delete prerequisite error:",
      error
    );

    /*
      Do NOT close the confirmation modal.
      Show the error inside the bordered box
      so the user knows what happened.
     */
    deleteErrorMessage.value =
      getApiErrorMessage(
        error,
        "Failed to delete prerequisite."
      );
  } finally {
    saving.value = false;
  }
};

/* RESET FILTERS */

const resetFilters = () => {
  search.value = "";
  departmentFilter.value = "";
  semesterFilter.value = "";
  currentPage.value = 1;
};

/* INITIAL LOAD */

onMounted(async () => {
  await Promise.all([
    loadDepartments(),
    loadCourses(),
    loadPrerequisites(),
  ]);
});
</script>

<template>
  <AdminLayout
    title="Prerequisite Management"
    subtitle="Define prerequisite courses required before enrollment."
  >
    <template #default="{ user }">
      <div class="page">

        <!-- SUCCESS MESSAGE -->
        <div
          v-if="successMessage"
          class="success-message"
        >
          <span>
            {{ successMessage }}
          </span>

          <button
            type="button"
            @click="successMessage = ''"
          >
            <X :size="16" />
          </button>
        </div>

        <!-- PAGE ERROR MESSAGE -->
        <div
          v-if="pageErrorMessage && !showModal && !deleteId"
          class="error-message"
        >
          <span>
            {{ pageErrorMessage }}
          </span>

          <button
            type="button"
            @click="pageErrorMessage = ''"
          >
            <X :size="16" />
          </button>
        </div>

        <!-- STATS -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <BookOpen :size="21" />
            </div>

            <div class="stat-content">
              <span>Total Courses</span>

              <strong>
                {{ courses.length }}
              </strong>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon">
              <GitBranch :size="21" />
            </div>

            <div class="stat-content">
              <span>Total Prerequisites</span>

              <strong>
                {{ prerequisites.length }}
              </strong>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon">
              <Filter :size="21" />
            </div>

            <div class="stat-content">
              <span>
                Courses With Prerequisites
              </span>

              <strong>
                {{ coursesWithPrerequisites }}
              </strong>
            </div>
          </div>
        </div>

        <!-- TOOLBAR -->
        <div class="toolbar-panel">
          <div class="search-filter-box">

            <div class="search-box">
              <Search :size="18" />

              <input
                v-model="search"
                type="text"
                placeholder="Search course code or name..."
              />
            </div>

            <select
              v-model="departmentFilter"
            >
              <option value="">
                All Departments
              </option>

              <option
                v-for="department in departments"
                :key="department.id"
                :value="department.code"
              >
                {{ department.code }}
              </option>
            </select>

            <select
              v-model="semesterFilter"
            >
              <option value="">
                All Semesters
              </option>

              <option
                v-for="semester in semesters"
                :key="semester"
                :value="semester"
              >
                {{ semester }}
              </option>
            </select>

            <button
              type="button"
              class="btn-reset"
              title="Reset filters"
              @click="resetFilters"
            >
              <RotateCcw :size="17" />
            </button>
          </div>

          <button
            type="button"
            class="btn-primary"
            @click="openAddModal()"
          >
            <Plus :size="18" />
            Add Prerequisite
          </button>
        </div>

        <!-- TABLE PANEL -->
        <div class="panel table-panel">

          <div class="panel-header">
            <div>
              <h2>
                Course Prerequisites
              </h2>

              <p>
                Select which courses must be completed
                before taking another course.
              </p>
            </div>

            <span class="result-count">
              {{ filteredCourses.length }} courses
            </span>
          </div>

          <!-- LOADING -->
          <div
            v-if="
              loadingCourses ||
              loadingPrerequisites ||
              loadingDepartments
            "
            class="loading"
          >
            <div class="loading-spinner"></div>

            <span>
              Loading prerequisites...
            </span>
          </div>

          <!-- EMPTY -->
          <div
            v-else-if="filteredCourses.length === 0"
            class="empty-state"
          >
            <BookOpen :size="38" />

            <h3>
              No courses found
            </h3>

            <p>
              Try changing your search or filters.
            </p>
          </div>

          <!-- TABLE -->
          <div
            v-else
            class="table-wrapper"
          >
            <table class="custom-table">
              <thead>
                <tr>
                  <th>Course</th>
                  <th>Department</th>
                  <th>Semester</th>
                  <th>Prerequisites</th>
                  <th>Action</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="course in paginatedCourses"
                  :key="course.id"
                >

                  <!-- COURSE -->
                  <td>
                    <div class="course-cell">
                      <strong>
                        {{ course.course_code }}
                      </strong>

                      <span>
                        {{ course.course_name }}
                      </span>
                    </div>
                  </td>

                  <!-- DEPARTMENT -->
                  <td>
                    <span class="department-badge">
                      {{ course.department }}
                    </span>
                  </td>

                  <!-- SEMESTER -->
                  <td>
                    {{ course.semester }}
                  </td>

                  <!-- PREREQUISITES -->
                  <td>
                    <div
                      v-if="
                        getPrerequisites(course.id).length
                      "
                      class="prerequisite-list"
                    >
                      <div
                        v-for="
                          item in getPrerequisites(course.id)
                        "
                        :key="item.id"
                        class="prerequisite-item"
                      >
                        <div class="prerequisite-info">
                          <strong>
                            {{
                              getPrerequisiteCourse(item)
                                ?.course_code ||
                              "Unknown Course"
                            }}
                          </strong>

                          <span>
                            {{
                              getPrerequisiteCourse(item)
                                ?.course_name ||
                              "Course information unavailable"
                            }}
                          </span>
                        </div>

                        <div class="item-actions">
                          <button
                            type="button"
                            class="action-btn edit"
                            title="Edit"
                            @click="openEditModal(item)"
                          >
                            <Edit2 :size="15" />
                          </button>

                          <button
                            type="button"
                            class="action-btn delete"
                            title="Delete"
                            @click="askDelete(item.id)"
                          >
                            <Trash2 :size="15" />
                          </button>
                        </div>
                      </div>
                    </div>

                    <span
                      v-else
                      class="no-prerequisite"
                    >
                      No prerequisite
                    </span>
                  </td>

                  <!-- ACTION -->
                  <td>
                    <button
                      type="button"
                      class="add-small-btn"
                      @click="openAddModal(course.id)"
                    >
                      <Plus :size="15" />
                      Add
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- PAGINATION -->
          <div
            v-if="totalPages > 1"
            class="pagination"
          >
            <div class="pagination-info">
              Showing

              <strong>
                {{
                  (currentPage - 1) *
                  itemsPerPage + 1
                }}
              </strong>

              -

              <strong>
                {{
                  Math.min(
                    currentPage *
                      itemsPerPage,
                    filteredCourses.length
                  )
                }}
              </strong>

              of

              <strong>
                {{ filteredCourses.length }}
              </strong>

              courses
            </div>

            <div class="pagination-controls">

              <!-- PREVIOUS -->
              <button
                type="button"
                class="pagination-btn arrow"
                :disabled="currentPage === 1"
                @click="previousPage"
                title="Previous page"
                aria-label="Previous page"
              >
                ‹
              </button>

              <!-- PAGE NUMBERS -->
              <button
                v-for="page in visiblePages"
                :key="page"
                type="button"
                class="pagination-btn"
                :class="{
                  active:
                    currentPage === page
                }"
                @click="goToPage(page)"
              >
                {{ page }}
              </button>

              <!-- NEXT -->
              <button
                type="button"
                class="pagination-btn arrow"
                :disabled="
                  currentPage === totalPages
                "
                @click="nextPage"
                title="Next page"
                aria-label="Next page"
              >
                ›
              </button>
            </div>
          </div>
        </div>

        <!-- ADD / EDIT MODAL -->
        <div
          v-if="showModal"
          class="modal-overlay"
          @click.self="closeModal"
        >
          <div class="modal">

            <div class="modal-header">
              <div>
                <h2>
                  {{
                    editingId
                      ? "Edit Prerequisite"
                      : "Add Prerequisite"
                  }}
                </h2>

                <p>
                  {{
                    editingId
                      ? "Update the prerequisite relationship."
                      : "Select department, course and prerequisite."
                  }}
                </p>
              </div>

              <button
                type="button"
                class="close-btn"
                :disabled="saving"
                @click="closeModal"
              >
                <X :size="20" />
              </button>
            </div>

            <!-- MODAL ERROR -->
            <div
              v-if="modalErrorMessage"
              class="modal-error"
            >
              <div class="modal-error-content">
                <strong>
                  Unable to save prerequisite
                </strong>

                <span>
                  {{ modalErrorMessage }}
                </span>
              </div>

              <button
                type="button"
                :disabled="saving"
                @click="modalErrorMessage = ''"
              >
                <X :size="15" />
              </button>
            </div>

            <!-- DEPARTMENT -->
            <div class="form-group">
              <label>
                Department
              </label>

              <select
                v-model="form.department"
                :disabled="!!editingId || saving"
                @change="departmentChanged"
              >
                <option value="">
                  Select department
                </option>

                <option
                  v-for="department in departments"
                  :key="department.id"
                  :value="department.code"
                >
                  {{ department.code }}
                  —
                  {{ department.name }}
                </option>
              </select>

              <small>
                Select the department first.
              </small>
            </div>

            <!-- COURSE -->
            <div class="form-group">
              <label>
                Course
              </label>

              <select
                v-model="form.course_id"
                :disabled="
                  !form.department ||
                  !!editingId ||
                  saving
                "
                @change="courseChanged"
              >
                <option value="">
                  Select course
                </option>

                <option
                  v-for="course in departmentCourses"
                  :key="course.id"
                  :value="String(course.id)"
                >
                  {{ course.course_code }}
                  —
                  {{ course.course_name }}
                </option>
              </select>

              <small v-if="form.department">
                Only courses from the selected department
                are shown.
              </small>
            </div>

            <!-- RELATIONSHIP -->
            <div class="relationship-arrow">
              <GitBranch :size="17" />

              <span>
                requires
              </span>
            </div>

            <!-- PREREQUISITE -->
            <div class="form-group">
              <label>
                Prerequisite Course
              </label>

              <select
                v-model="form.prerequisite_course_id"
                :disabled="
                  !form.course_id ||
                  saving
                "
              >
                <option value="">
                  Select prerequisite course
                </option>

                <option
                  v-for="
                    course in availablePrerequisiteCourses
                  "
                  :key="course.id"
                  :value="String(course.id)"
                >
                  {{ course.course_code }}
                  —
                  {{ course.course_name }}
                </option>
              </select>

              <small v-if="form.course_id">
                Only courses from the same department
                are shown.
              </small>
            </div>

            <!-- NO OPTIONS -->
            <div
              v-if="
                form.course_id &&
                availablePrerequisiteCourses.length === 0
              "
              class="no-options"
            >
              No available prerequisite courses for this
              course.
            </div>

            <!-- FOOTER -->
            <div class="modal-footer">
              <button
                type="button"
                class="btn-secondary"
                :disabled="saving"
                @click="closeModal"
              >
                Cancel
              </button>

              <button
                type="button"
                class="btn-primary"
                :disabled="saving"
                @click="savePrerequisite"
              >
                {{
                  saving
                    ? "Saving..."
                    : editingId
                      ? "Update Prerequisite"
                      : "Save Prerequisite"
                }}
              </button>
            </div>
          </div>
        </div>

        <!-- DELETE CONFIRMATION -->
        <div
          v-if="deleteId"
          class="modal-overlay"
        >
          <div class="confirm-modal">

            <div class="confirm-icon">
              <Trash2 :size="22" />
            </div>

            <h3>
              Remove Prerequisite?
            </h3>

            <p>
              This prerequisite relationship will be removed.
              The courses themselves will not be deleted.
            </p>

            <!-- DELETE ERROR -->
            <div
              v-if="deleteErrorMessage"
              class="delete-error"
            >
              <div>
                <strong>
                  Unable to remove prerequisite
                </strong>

                <p>
                  {{ deleteErrorMessage }}
                </p>
              </div>

              <button
                type="button"
                :disabled="saving"
                @click="deleteErrorMessage = ''"
              >
                <X :size="15" />
              </button>
            </div>

            <div class="confirm-actions">
              <button
                type="button"
                class="btn-secondary"
                :disabled="saving"
                @click="cancelDelete"
              >
                Cancel
              </button>

              <button
                type="button"
                class="btn-danger"
                :disabled="saving"
                @click="deletePrerequisite"
              >
                {{
                  saving
                    ? "Removing..."
                    : "Remove"
                }}
              </button>
            </div>
          </div>
        </div>

      </div>
    </template>
  </AdminLayout>
</template>

<style scoped>
.page {
  width: 100%;
}

.page,
.page *,
.page *::before,
.page *::after {
  box-sizing: border-box;
}

/* MESSAGES */

.success-message,
.error-message {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 15px;
  border-radius: 8px;
  margin-bottom: 18px;
  font-size: 14px;
}

.success-message {
  color: #166534;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
}

.error-message {
  color: #991b1b;
  background: #fef2f2;
  border: 1px solid #fecaca;
}

.success-message button,
.error-message button {
  border: 0;
  background: transparent;
  color: inherit;
  cursor: pointer;
  display: flex;
}

/* STATS */

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
  margin-bottom: 20px;
}

.stat-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 18px;
  display: flex;
  align-items: center;
  gap: 14px;
}

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: #eff6ff;
  color: #1e3a8a;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-content span {
  display: block;
  color: #6b7280;
  font-size: 13px;
  margin-bottom: 4px;
}

.stat-content strong {
  color: #111827;
  font-size: 23px;
}

/* TOOLBAR */

.toolbar-panel {
  width: 100%;
  max-width: 100%;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 15px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  overflow: hidden;
}

.search-filter-box {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1 1 auto;
  min-width: 0;
  max-width: 100%;
}

.search-box {
  min-width: 0;
  flex: 1 1 260px;
  height: 40px;
  display: flex;
  align-items: center;
  gap: 9px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  padding: 0 12px;
  overflow: hidden;
}

.search-box input {
  border: 0;
  outline: none;
  width: 100%;
  min-width: 0;
  font-size: 14px;
}

.search-filter-box select {
  flex: 0 1 auto;
  min-width: 0;
  max-width: 100%;
}

.toolbar-panel > .btn-primary {
  flex: 0 0 auto;
  max-width: 100%;
}

.search-box svg {
  color: #6b7280;
  flex-shrink: 0;
}

select {
  height: 40px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  padding: 0 12px;
  background: #fff;
  color: #374151;
  outline: none;
  font-size: 14px;
}

select:focus,
.search-box:focus-within {
  border-color: #1e3a8a;
}

.btn-primary {
  height: 40px;
  padding: 0 16px;
  border: 0;
  border-radius: 8px;
  background: #172554;
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  white-space: nowrap;
}

.btn-primary:hover {
  background: #1e3a8a;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-reset {
  width: 40px;
  height: 40px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: #fff;
  color: #4b5563;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.btn-reset:hover {
  background: #f9fafb;
}

/* PANEL */

.panel {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
}

.panel-header {
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
}

.panel-header h2 {
  margin: 0 0 4px;
  color: #111827;
  font-size: 17px;
}

.panel-header p {
  margin: 0;
  color: #6b7280;
  font-size: 13px;
}

.result-count {
  color: #6b7280;
  font-size: 13px;
  white-space: nowrap;
}

/* TABLE */

.table-wrapper {
  overflow-x: auto;
}

.custom-table {
  width: 100%;
  border-collapse: collapse;
}

.custom-table th {
  background: #f8fafc;
  color: #4b5563;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  text-align: left;
  padding: 13px 18px;
  border-bottom: 1px solid #e5e7eb;
  white-space: nowrap;
}

.custom-table td {
  padding: 15px 18px;
  border-bottom: 1px solid #f1f5f9;
  color: #374151;
  font-size: 14px;
  vertical-align: top;
}

.custom-table tbody tr:hover {
  background: #fafafa;
}

.course-cell {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.course-cell strong {
  color: #172554;
  font-size: 14px;
}

.course-cell span {
  color: #6b7280;
  font-size: 13px;
}

.department-badge {
  display: inline-flex;
  padding: 4px 9px;
  border-radius: 6px;
  background: #eff6ff;
  color: #1e3a8a;
  font-size: 12px;
  font-weight: 600;
}

/* PREREQUISITE LIST */

.prerequisite-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 320px;
}

.prerequisite-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 8px 10px;
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  border-radius: 7px;
}

.prerequisite-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.prerequisite-info strong {
  color: #172554;
  font-size: 13px;
}

.prerequisite-info span {
  color: #6b7280;
  font-size: 12px;
}

.item-actions {
  display: flex;
  gap: 5px;
  flex-shrink: 0;
}

.action-btn {
  width: 30px;
  height: 30px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.action-btn.edit {
  color: #1e3a8a;
}

.action-btn.delete {
  color: #b91c1c;
}

.action-btn:hover {
  background: #f9fafb;
}

.no-prerequisite {
  color: #9ca3af;
  font-size: 13px;
}

.add-small-btn {
  height: 32px;
  padding: 0 10px;
  border: 1px solid #dbeafe;
  border-radius: 7px;
  background: #eff6ff;
  color: #1e3a8a;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 600;
}

.add-small-btn:hover {
  background: #dbeafe;
}

/* PAGINATION */

.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  padding: 15px 18px;
  border-top: 1px solid #e5e7eb;
  background: #fff;
  border-radius: 0 0 12px 12px;
}

.pagination-info {
  color: #6b7280;
  font-size: 13px;
  white-space: nowrap;
}

.pagination-info strong {
  color: #374151;
  font-weight: 600;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 5px;
}

.pagination-btn {
  min-width: 34px;
  height: 34px;
  padding: 0 9px;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  background: #fff;
  color: #374151;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  transition:
    background 0.15s ease,
    border-color 0.15s ease,
    color 0.15s ease;
}

.pagination-btn:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #9ca3af;
}

.pagination-btn.active {
  background: #172554;
  border-color: #172554;
  color: #fff;
}

.pagination-btn.arrow {
  font-size: 22px;
  font-weight: 400;
  line-height: 1;
}

.pagination-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  background: #f9fafb;
}

/* LOADING */

.loading {
  min-height: 220px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 10px;
  color: #6b7280;
  font-size: 13px;
}

.loading-spinner {
  width: 28px;
  height: 28px;
  border: 3px solid #e5e7eb;
  border-top-color: #1e3a8a;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* EMPTY */

.empty-state {
  min-height: 220px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  color: #9ca3af;
  gap: 8px;
}

.empty-state h3 {
  color: #374151;
  margin: 5px 0 0;
}

.empty-state p {
  margin: 0;
  font-size: 13px;
}

/* MODAL */

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  z-index: 1000;
}

.modal {
  width: 100%;
  max-width: 540px;
  background: #fff;
  border-radius: 13px;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.18);
  overflow: hidden;
}

.modal-header {
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  gap: 15px;
}

.modal-header h2 {
  margin: 0 0 5px;
  color: #111827;
  font-size: 18px;
}

.modal-header p {
  margin: 0;
  color: #6b7280;
  font-size: 13px;
}

.close-btn {
  width: 34px;
  height: 34px;
  border: 0;
  border-radius: 7px;
  background: #f3f4f6;
  color: #4b5563;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.close-btn:hover:not(:disabled) {
  background: #e5e7eb;
}

.close-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* FORM */

.form-group {
  padding: 16px 20px 0;
}

.form-group label {
  display: block;
  margin-bottom: 7px;
  color: #374151;
  font-size: 13px;
  font-weight: 600;
}

.form-group select {
  width: 100%;
}

.form-group select:disabled {
  background: #f3f4f6;
  color: #6b7280;
  cursor: not-allowed;
}

.form-group small {
  display: block;
  margin-top: 6px;
  color: #9ca3af;
  font-size: 11px;
}

.relationship-arrow {
  display: flex;
  align-items: center;
  gap: 7px;
  margin: 15px 20px 0;
  color: #1e3a8a;
  font-size: 12px;
  font-weight: 600;
}

/* MODAL ERROR */

.modal-error {
  margin: 15px 20px 0;
  padding: 11px 12px;
  border: 1px solid #fecaca;
  border-left: 3px solid #dc2626;
  border-radius: 7px;
  background: #fff8f8;
  color: #991b1b;
  font-size: 13px;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}

.modal-error-content {
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
}

.modal-error-content strong {
  font-size: 12px;
  color: #991b1b;
}

.modal-error-content span {
  color: #8b5b5b;
  line-height: 1.45;
}

.modal-error button {
  border: 0;
  background: transparent;
  color: #991b1b;
  cursor: pointer;
  display: flex;
  flex-shrink: 0;
}

.modal-error button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.no-options {
  margin: 12px 20px 0;
  padding: 10px;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 7px;
  color: #6b7280;
  font-size: 12px;
}

.modal-footer {
  margin-top: 22px;
  padding: 15px 20px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btn-secondary {
  height: 40px;
  padding: 0 15px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: #fff;
  color: #374151;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
}

.btn-secondary:hover:not(:disabled) {
  background: #f9fafb;
}

.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* DELETE CONFIRM */

.confirm-modal {
  width: 100%;
  max-width: 400px;
  background: #fff;
  border-radius: 13px;
  padding: 25px;
  text-align: center;
}

.confirm-icon {
  width: 46px;
  height: 46px;
  margin: 0 auto 12px;
  border-radius: 50%;
  background: #fef2f2;
  color: #b91c1c;
  display: flex;
  align-items: center;
  justify-content: center;
}

.confirm-modal h3 {
  margin: 0 0 7px;
  color: #111827;
  font-size: 17px;
}

.confirm-modal > p {
  margin: 0;
  color: #6b7280;
  font-size: 13px;
  line-height: 1.6;
}

/* DELETE ERROR */

.delete-error {
  margin-top: 15px;
  padding: 10px 12px;
  border: 1px solid #fecaca;
  border-left: 3px solid #dc2626;
  border-radius: 7px;
  background: #fff8f8;
  color: #991b1b;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
  text-align: left;
}

.delete-error strong {
  display: block;
  font-size: 12px;
  margin-bottom: 3px;
}

.delete-error p {
  margin: 0;
  color: #8b5b5b;
  font-size: 12px;
  line-height: 1.45;
}

.delete-error button {
  border: 0;
  background: transparent;
  color: #991b1b;
  cursor: pointer;
  display: flex;
  flex-shrink: 0;
}

.delete-error button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.confirm-actions {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-top: 20px;
}

.btn-danger {
  height: 40px;
  padding: 0 17px;
  border: 0;
  border-radius: 8px;
  background: #b91c1c;
  color: #fff;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
}

.btn-danger:hover:not(:disabled) {
  background: #991b1b;
}

.btn-danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* RESPONSIVE */

@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .toolbar-panel {
    align-items: stretch;
    flex-direction: column;
  }

  .search-filter-box {
    width: 100%;
    flex-wrap: wrap;
  }

  .search-box {
    flex: 1 1 300px;
    min-width: 0;
  }

  .toolbar-panel > .btn-primary {
    width: 100%;
    max-width: 100%;
  }
}

@media (max-width: 650px) {
  .search-filter-box {
    width: 100%;
    min-width: 0;
    flex-direction: column;
    align-items: stretch;
  }

  .search-box,
  .search-filter-box select,
  .btn-reset {
    width: 100%;
    max-width: 100%;
    min-width: 0;
  }

  .search-box {
    flex: none;
  }

  .search-box input {
    min-width: 0;
  }

  .toolbar-panel {
    width: 100%;
    padding: 12px;
  }

  .toolbar-panel > .btn-primary {
    width: 100%;
    max-width: 100%;
  }

  .panel-header {
    align-items: flex-start;
    flex-direction: column;
    gap: 8px;
  }

  .prerequisite-list {
    min-width: 260px;
  }

  .modal {
    max-height: 90vh;
    overflow-y: auto;
  }

  /* MOBILE PAGINATION */

  .pagination {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }

  .pagination-info {
    text-align: center;
    white-space: normal;
  }

  .pagination-controls {
    justify-content: center;
    flex-wrap: wrap;
  }

  .pagination-btn {
    min-width: 32px;
    height: 32px;
  }

  .confirm-modal {
    max-width: 100%;
  }
}
</style>