<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import AdminLayout from "@/components/admin/AdminLayout.vue";
import api from "@/services/api";

import {
  Plus,
  Search,
  Edit2,
  Trash2,
  X,
  CalendarDays,
  Clock3,
  MapPin,
  UserRound,
  Loader2,
  AlertCircle,
} from "lucide-vue-next";

/* 
   TYPES
 */

interface Department {
  id: number;
  code: string;
  name: string;
  status?: string;
}

interface Batch {
  id: number;
  department: string;
  batch_number: number;
  start_year?: string;
  current_semester: string;
  status: string;
}

interface Course {
  id: number;
  course_code: string;
  course_name: string;
  department_id: number;
  department?: Department;
  semester: string;
  credit?: number;
  course_type?: string;
  description?: string;
  status?: string;
}

interface Section {
  id: number;
  batch_id: number;
  section_name: string;
  teacher_name?: string;
  room?: string;
  capacity?: number;
  enrolled?: number;
  status: string;
  batch?: Batch;
}

interface Schedule {
  id: number;
  section_id: number;
  course_id: number;
  teacher_name: string;
  day: string;
  start_time: string;
  end_time: string;
  room: string;
  section?: Section;
  course?: Course;
}

interface Form {
  department: string;
  batch_id: string;
  semester: string;
  section_name: string;
  course_id: string;
  teacher_name: string;
  day: string;
  start_time: string;
  end_time: string;
  room: string;
}

/* 
   STATE
 */

const departments = ref<Department[]>([]);
const batches = ref<Batch[]>([]);
const courses = ref<Course[]>([]);
const sections = ref<Section[]>([]);
const schedules = ref<Schedule[]>([]);

const loading = ref(false);
const saving = ref(false);
const deleting = ref(false);

const showModal = ref(false);
const showDeleteModal = ref(false);

const editingId = ref<number | null>(null);
const selectedSchedule = ref<Schedule | null>(null);

const search = ref("");
const filterDepartment = ref("");
const filterBatch = ref("");
const filterDay = ref("");

const errorMessage = ref("");
const deleteError = ref("");

const form = ref<Form>({
  department: "",
  batch_id: "",
  semester: "",
  section_name: "",
  course_id: "",
  teacher_name: "",
  day: "Saturday",
  start_time: "",
  end_time: "",
  room: "",
});

/* 
   CONSTANTS
 */

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

const days = [
  "Saturday",
  "Sunday",
  "Monday",
  "Tuesday",
  "Wednesday",
  "Thursday",
  "Friday",
];

/* 
   FORM FILTERS
 */

const activeDepartments = computed(() => {
  return departments.value.filter(
    (department) =>
      department.status === "Active" ||
      department.status === undefined
  );
});

const filteredBatches = computed(() => {
  if (!form.value.department) {
    return [];
  }

  return batches.value.filter(
    (batch) =>
      batch.status === "Active" &&
      batch.department === form.value.department
  );
});

/*
  Course depends on:
  Department + Semester
 
 */
const formCourses = computed(() => {
  if (!form.value.department || !form.value.semester) {
    return [];
  }

  const selectedDepartment = departments.value.find(
    (department) =>
      department.code === form.value.department
  );

  if (!selectedDepartment) {
    return [];
  }

  return courses.value
    .filter(
      (course) =>
        Number(course.department_id) ===
          Number(selectedDepartment.id) &&
        course.semester === form.value.semester &&
        course.status !== "Inactive"
    )
    .sort((a, b) =>
      a.course_code.localeCompare(b.course_code)
    );
});

/*
  Section depends on:
  Department + Batch + Semester

 */
const formSections = computed(() => {
  if (
    !form.value.department ||
    !form.value.batch_id ||
    !form.value.semester
  ) {
    return [];
  }

  const unique = new Map<string, Section>();

  sections.value
    .filter(
      (section) =>
        section.status === "Active" &&
        String(section.batch_id) ===
          form.value.batch_id &&
        section.batch?.department ===
          form.value.department &&
        section.batch?.current_semester ===
          form.value.semester
    )
    .forEach((section) => {
      if (!unique.has(section.section_name)) {
        unique.set(
          section.section_name,
          section
        );
      }
    });

  return [...unique.values()].sort((a, b) =>
    a.section_name.localeCompare(b.section_name)
  );
});

/*
  Find section independently.
 */
const selectedSectionId = computed(() => {
  if (
    !form.value.batch_id ||
    !form.value.section_name
  ) {
    return "";
  }

  const section = sections.value.find(
    (item) =>
      String(item.batch_id) ===
        form.value.batch_id &&
      item.section_name ===
        form.value.section_name
  );

  return section?.id ?? "";
});

/* 
   TABLE FILTER
 */

const filteredSchedules = computed(() => {
  const q = search.value.toLowerCase().trim();

  return schedules.value.filter((schedule) => {
    const department =
      schedule.section?.batch?.department ?? "";

    const batch =
      schedule.section?.batch?.batch_number?.toString() ??
      "";

    const section =
      schedule.section?.section_name ?? "";

    const courseCode =
      schedule.course?.course_code ?? "";

    const courseName =
      schedule.course?.course_name ?? "";

    const teacher =
      schedule.teacher_name ?? "";

    const room =
      schedule.room ?? "";

    const matchesSearch =
      !q ||
      department.toLowerCase().includes(q) ||
      batch.includes(q) ||
      section.toLowerCase().includes(q) ||
      courseCode.toLowerCase().includes(q) ||
      courseName.toLowerCase().includes(q) ||
      teacher.toLowerCase().includes(q) ||
      room.toLowerCase().includes(q);

    const matchesDepartment =
      !filterDepartment.value ||
      department === filterDepartment.value;

    const matchesBatch =
      !filterBatch.value ||
      String(schedule.section?.batch_id) ===
        filterBatch.value;

    const matchesDay =
      !filterDay.value ||
      schedule.day === filterDay.value;

    return (
      matchesSearch &&
      matchesDepartment &&
      matchesBatch &&
      matchesDay
    );
  });
});

/* 
   FORM
 */

function resetForm() {
  form.value = {
    department: "",
    batch_id: "",
    semester: "",
    section_name: "",
    course_id: "",
    teacher_name: "",
    day: "Saturday",
    start_time: "",
    end_time: "",
    room: "",
  };

  errorMessage.value = "";
}

function openCreate() {
  editingId.value = null;
  resetForm();
  showModal.value = true;
}

function openEdit(schedule: Schedule) {
  editingId.value = schedule.id;

  form.value = {
    department:
      schedule.section?.batch?.department ?? "",

    batch_id:
      String(schedule.section?.batch_id ?? ""),

    semester:
      schedule.section?.batch?.current_semester ?? "",

    section_name:
      schedule.section?.section_name ?? "",

    course_id:
      String(schedule.course_id),

    teacher_name:
      schedule.teacher_name ?? "",

    day:
      schedule.day,

    start_time:
      schedule.start_time?.slice(0, 5) ?? "",

    end_time:
      schedule.end_time?.slice(0, 5) ?? "",

    room:
      schedule.room ?? "",
  };

  errorMessage.value = "";
  showModal.value = true;
}

function closeModal() {
  if (saving.value) {
    return;
  }

  showModal.value = false;
  errorMessage.value = "";
}

/* 
   DEPENDENCY HANDLERS
 */

function onDepartmentChange() {
  form.value.batch_id = "";
  form.value.semester = "";
  form.value.section_name = "";
  form.value.course_id = "";
  errorMessage.value = "";
}

function onBatchChange() {
  const batch = batches.value.find(
    (item) =>
      String(item.id) ===
      form.value.batch_id
  );

  form.value.semester =
    batch?.current_semester ?? "";

  form.value.section_name = "";
  form.value.course_id = "";
  errorMessage.value = "";
}

function onSectionChange() {
  errorMessage.value = "";
}

function onSemesterChange() {
  form.value.course_id = "";
  errorMessage.value = "";
}

/* 
   DATA
 */

async function fetchDepartments() {
  const response = await api.get(
    "/admin/departments"
  );

  departments.value =
    response.data.departments ?? [];
}

async function fetchBatches() {
  const response = await api.get(
    "/admin/batches"
  );

  batches.value =
    response.data.batches ??
    response.data.data ??
    [];
}

async function fetchCourses() {
  const response = await api.get(
    "/admin/courses"
  );

  courses.value =
    response.data.courses ??
    response.data.data ??
    [];
}

async function fetchSections() {
  const response = await api.get(
    "/admin/sections"
  );

  sections.value =
    response.data.sections ??
    response.data.data ??
    [];
}

async function fetchSchedules() {
  const params: Record<string, string> = {};

  if (filterDepartment.value) {
    params.department =
      filterDepartment.value;
  }

  if (filterBatch.value) {
    params.batch_id =
      filterBatch.value;
  }

  if (filterDay.value) {
    params.day = filterDay.value;
  }

  const response = await api.get(
    "/admin/schedules",
    { params }
  );

  schedules.value =
    response.data.data ?? [];
}

async function fetchData() {
  loading.value = true;
  errorMessage.value = "";

  try {
    await Promise.all([
      fetchDepartments(),
      fetchBatches(),
      fetchCourses(),
      fetchSections(),
      fetchSchedules(),
    ]);
  } catch (error: any) {
    errorMessage.value =
      error.response?.data?.message ||
      "Failed to load schedule data.";
  } finally {
    loading.value = false;
  }
}

/* 
   SAVE
 */

async function saveSchedule() {
  errorMessage.value = "";

  if (!form.value.department) {
    errorMessage.value =
      "Please select a department.";
    return;
  }

  if (!form.value.batch_id) {
    errorMessage.value =
      "Please select a batch.";
    return;
  }

  if (!form.value.semester) {
    errorMessage.value =
      "Please select a semester.";
    return;
  }

  if (!form.value.course_id) {
    errorMessage.value =
      "Please select a course.";
    return;
  }

  if (!form.value.section_name) {
    errorMessage.value =
      "Please select a section.";
    return;
  }

  if (!selectedSectionId.value) {
    errorMessage.value =
      "Selected section could not be found.";
    return;
  }

  if (!form.value.teacher_name.trim()) {
    errorMessage.value =
      "Teacher name is required.";
    return;
  }

  if (
    !form.value.start_time ||
    !form.value.end_time
  ) {
    errorMessage.value =
      "Start time and end time are required.";
    return;
  }

  if (
    form.value.start_time >=
    form.value.end_time
  ) {
    errorMessage.value =
      "End time must be later than start time.";
    return;
  }

  if (!form.value.room.trim()) {
    errorMessage.value =
      "Room is required.";
    return;
  }

  const payload = {
    section_id:
      Number(selectedSectionId.value),

    course_id:
      Number(form.value.course_id),

    teacher_name:
      form.value.teacher_name.trim(),

    day:
      form.value.day,

    start_time:
      form.value.start_time,

    end_time:
      form.value.end_time,

    room:
      form.value.room.trim(),
  };

  saving.value = true;

  try {
    if (editingId.value) {
      await api.put(
        `/admin/schedules/${editingId.value}`,
        payload
      );
    } else {
      await api.post(
        "/admin/schedules",
        payload
      );
    }

    showModal.value = false;
    errorMessage.value = "";

    await fetchData();
  } catch (error: any) {
    const errors =
      error.response?.data?.errors;

    if (errors) {
      errorMessage.value =
        Object.values(errors)
          .flat()
          .join(" ");
    } else {
      errorMessage.value =
        error.response?.data?.message ||
        "Unable to save class schedule.";
    }
  } finally {
    saving.value = false;
  }
}

/* 
   DELETE
 */

function openDeleteModal(
  schedule: Schedule
) {
  selectedSchedule.value = schedule;
  deleteError.value = "";
  showDeleteModal.value = true;
}

function closeDeleteModal() {
  if (deleting.value) {
    return;
  }

  showDeleteModal.value = false;
  deleteError.value = "";
  selectedSchedule.value = null;
}

async function deleteSchedule() {
  if (!selectedSchedule.value) {
    return;
  }

  deleting.value = true;
  deleteError.value = "";

  try {
    await api.delete(
      `/admin/schedules/${selectedSchedule.value.id}`
    );

    showDeleteModal.value = false;
    selectedSchedule.value = null;
    deleteError.value = "";

    await fetchData();
  } catch (error: any) {
    const errors =
      error.response?.data?.errors;

    if (errors) {
      deleteError.value =
        Object.values(errors)
          .flat()
          .join(" ");
    } else {
      deleteError.value =
        error.response?.data?.message ||
        "Unable to delete schedule.";
    }
  } finally {
    deleting.value = false;
  }
}

/* 
   HELPERS
 */

function formatTime(time: string) {
  if (!time) {
    return "";
  }

  const [hourString, minute] =
    time.slice(0, 5).split(":");

  let hour = Number(hourString);

  const period =
    hour >= 12 ? "PM" : "AM";

  hour = hour % 12 || 12;

  return `${hour}:${minute} ${period}`;
}

onMounted(fetchData);
</script>

<template>
  <AdminLayout
    title="Class Schedule"
    subtitle="Manage class schedules, teachers, rooms and time conflicts."
  >
    <div class="page">

      <!--  STATS  -->

      <div class="stats-grid">

        <div class="stat-card">
          <div class="stat-icon">
            <CalendarDays :size="20" />
          </div>

          <div>
            <span>Total Classes</span>
            <strong>
              {{ schedules.length }}
            </strong>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <Clock3 :size="20" />
          </div>

          <div>
            <span>Selected Day</span>

            <strong>
              {{ filterDay || "All Days" }}
            </strong>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <MapPin :size="20" />
          </div>

          <div>
            <span>Departments</span>

            <strong>
              {{ departments.length }}
            </strong>
          </div>
        </div>

      </div>

      <!--  TOOLBAR  -->

      <div class="toolbar-panel">

        <div class="search-filter-box">
          <Search :size="18" />

          <input
            v-model="search"
            type="text"
            placeholder="Search course, section, teacher or room..."
          />
        </div>

        <select v-model="filterDepartment">
          <option value="">
            All Departments
          </option>

          <option
            v-for="department in activeDepartments"
            :key="department.id"
            :value="department.code"
          >
            {{ department.code }}
          </option>
        </select>

        <select v-model="filterBatch">
          <option value="">
            All Batches
          </option>

          <option
            v-for="batch in batches"
            :key="batch.id"
            :value="String(batch.id)"
          >
            {{ batch.department }} -
            Batch {{ batch.batch_number }}
          </option>
        </select>

        <select v-model="filterDay">
          <option value="">
            All Days
          </option>

          <option
            v-for="day in days"
            :key="day"
            :value="day"
          >
            {{ day }}
          </option>
        </select>

        <button
          class="btn-primary"
          @click="openCreate"
        >
          <Plus :size="18" />
          Add Schedule
        </button>

      </div>

      <!--  TABLE  -->

      <div class="panel table-panel">

        <div class="panel-header">
          <div>
            <h3>Class Schedules</h3>

            <p>
              View and manage all scheduled classes.
            </p>
          </div>
        </div>

        <div
          v-if="loading"
          class="loading"
        >
          <Loader2
            :size="22"
            class="spin"
          />

          Loading schedules...
        </div>

        <div
          v-else
          class="table-wrap"
        >
          <table class="custom-table">

            <thead>
              <tr>
                <th>Department</th>
                <th>Batch</th>
                <th>Semester</th>
                <th>Section</th>
                <th>Course</th>
                <th>Teacher</th>
                <th>Day</th>
                <th>Time</th>
                <th>Room</th>
                <th>Actions</th>
              </tr>
            </thead>

            <tbody>

              <tr
                v-for="schedule in filteredSchedules"
                :key="schedule.id"
              >

                <td>
                  <strong>
                    {{
                      schedule.section?.batch
                        ?.department
                    }}
                  </strong>
                </td>

                <td>
                  Batch
                  {{
                    schedule.section?.batch
                      ?.batch_number
                  }}
                </td>

                <td>
                  {{
                    schedule.course?.semester ||
                    schedule.section?.batch
                      ?.current_semester ||
                    "—"
                  }}
                </td>

                <td>
                  Section
                  {{
                    schedule.section
                      ?.section_name
                  }}
                </td>

                <td>
                  <strong>
                    {{
                      schedule.course
                        ?.course_code
                    }}
                  </strong>

                  <small>
                    {{
                      schedule.course
                        ?.course_name
                    }}
                  </small>
                </td>

                <td>
                  <div class="icon-text">
                    <UserRound :size="15" />

                    {{ schedule.teacher_name }}
                  </div>
                </td>

                <td>
                  {{ schedule.day }}
                </td>

                <td>
                  <div class="time-cell">
                    {{
                      formatTime(
                        schedule.start_time
                      )
                    }}
                    -
                    {{
                      formatTime(
                        schedule.end_time
                      )
                    }}
                  </div>
                </td>

                <td>
                  <div class="icon-text">
                    <MapPin :size="15" />

                    {{ schedule.room }}
                  </div>
                </td>

                <td>
                  <div class="actions">

                    <button
                      class="action-btn edit"
                      title="Edit"
                      @click="openEdit(schedule)"
                    >
                      <Edit2 :size="16" />
                    </button>

                    <button
                      class="action-btn delete"
                      title="Delete"
                      @click="
                        openDeleteModal(schedule)
                      "
                    >
                      <Trash2 :size="16" />
                    </button>

                  </div>
                </td>

              </tr>

              <tr
                v-if="
                  !filteredSchedules.length
                "
              >
                <td
                  colspan="10"
                  class="empty"
                >
                  No schedules found.
                </td>
              </tr>

            </tbody>

          </table>
        </div>

      </div>

      <!--  CREATE / EDIT MODAL  -->

      <div
        v-if="showModal"
        class="modal-overlay"
        @click.self="closeModal"
      >

        <div class="modal">

          <div class="modal-header">

            <div>
              <h3>
                {{
                  editingId
                    ? "Edit Class Schedule"
                    : "Add Class Schedule"
                }}
              </h3>

              <p>
                Select department, batch,
                semester, course and section,
                then set class details.
              </p>
            </div>

            <button
              class="close-btn"
              :disabled="saving"
              @click="closeModal"
            >
              <X :size="20" />
            </button>

          </div>

          <div
            v-if="errorMessage"
            class="error-box"
          >
            <AlertCircle :size="17" />

            <span>
              {{ errorMessage }}
            </span>
          </div>

          <div class="form-grid">

            <!-- Department -->

            <div class="form-group">

              <label>
                Department
              </label>

              <select
                v-model="form.department"
                :disabled="!!editingId"
                @change="onDepartmentChange"
              >

                <option value="">
                  Select Department
                </option>

                <option
                  v-for="department in activeDepartments"
                  :key="department.id"
                  :value="department.code"
                >
                  {{ department.code }}
                  -
                  {{ department.name }}
                </option>

              </select>

            </div>

            <!-- Batch -->

            <div class="form-group">

              <label>
                Batch
              </label>

              <select
                v-model="form.batch_id"
                :disabled="
                  !form.department ||
                  !!editingId
                "
                @change="onBatchChange"
              >

                <option value="">
                  Select Batch
                </option>

                <option
                  v-for="batch in filteredBatches"
                  :key="batch.id"
                  :value="String(batch.id)"
                >
                  Batch
                  {{ batch.batch_number }}
                </option>

              </select>

            </div>

            <!-- Semester -->

            <div class="form-group">

              <label>
                Semester
              </label>

              <select
                v-model="form.semester"
                disabled
                @change="onSemesterChange"
              >

                <option value="">
                  Select Semester
                </option>

                <option
                  v-for="semester in semesters"
                  :key="semester"
                  :value="semester"
                >
                  {{ semester }} Semester
                </option>

              </select>

            </div>

            <!-- Course -->

            <div class="form-group">

              <label>
                Course
              </label>

              <select
                v-model="form.course_id"
                :disabled="
                  !form.department ||
                  !form.semester
                "
              >

                <option value="">
                  Select Course
                </option>

                <option
                  v-for="course in formCourses"
                  :key="course.id"
                  :value="String(course.id)"
                >
                  {{ course.course_code }}
                  -
                  {{ course.course_name }}
                </option>

              </select>

              <small
                v-if="
                  form.department &&
                  form.semester &&
                  !formCourses.length
                "
                class="helper-text"
              >
                No courses found for this
                department and semester.
              </small>

            </div>

            <!-- Section -->

            <div class="form-group">

              <label>
                Section
              </label>

              <select
                v-model="form.section_name"
                :disabled="
                  !form.batch_id ||
                  !form.semester
                "
                @change="onSectionChange"
              >

                <option value="">
                  Select Section
                </option>

                <option
                  v-for="section in formSections"
                  :key="section.id"
                  :value="section.section_name"
                >
                  Section
                  {{ section.section_name }}
                </option>

              </select>

              <small
                v-if="
                  form.batch_id &&
                  form.semester &&
                  !formSections.length
                "
                class="helper-text"
              >
                No active sections found for
                this batch.
              </small>

            </div>

            <!-- Teacher -->

            <div class="form-group">

              <label>
                Teacher
              </label>

              <input
                v-model="form.teacher_name"
                type="text"
                placeholder="Teacher name"
              />

            </div>

            <!-- Day -->

            <div class="form-group">

              <label>
                Day
              </label>

              <select v-model="form.day">

                <option
                  v-for="day in days"
                  :key="day"
                  :value="day"
                >
                  {{ day }}
                </option>

              </select>

            </div>

            <!-- Start Time -->

            <div class="form-group">

              <label>
                Start Time
              </label>

              <input
                v-model="form.start_time"
                type="time"
              />

            </div>

            <!-- End Time -->

            <div class="form-group">

              <label>
                End Time
              </label>

              <input
                v-model="form.end_time"
                type="time"
              />

            </div>

            <!-- Room -->

            <div class="form-group full">

              <label>
                Room
              </label>

              <input
                v-model="form.room"
                type="text"
                placeholder="e.g. Room 221"
              />

            </div>

          </div>

          <div class="modal-footer">

            <button
              class="btn-secondary"
              :disabled="saving"
              @click="closeModal"
            >
              Cancel
            </button>

            <button
              class="btn-primary"
              :disabled="saving"
              @click="saveSchedule"
            >

              <Loader2
                v-if="saving"
                :size="17"
                class="spin"
              />

              {{
                saving
                  ? "Saving..."
                  : editingId
                    ? "Update Schedule"
                    : "Create Schedule"
              }}

            </button>

          </div>

        </div>

      </div>

      <!--  DELETE MODAL  -->

      <div
        v-if="showDeleteModal"
        class="modal-overlay"
        @click.self="closeDeleteModal"
      >

        <div class="delete-modal">

          <div class="delete-icon">
            <Trash2 :size="24" />
          </div>

          <h3>
            Delete Schedule?
          </h3>

          <p>
            Are you sure you want to delete this
            class schedule?
          </p>

          <div
            v-if="selectedSchedule"
            class="delete-info"
          >

            <strong>
              {{
                selectedSchedule.course
                  ?.course_code ||
                "Course"
              }}
            </strong>

            <span>
              Section
              {{
                selectedSchedule.section
                  ?.section_name ||
                "—"
              }}
              ·
              {{ selectedSchedule.day }}
              ·
              {{
                formatTime(
                  selectedSchedule.start_time
                )
              }}
              -
              {{
                formatTime(
                  selectedSchedule.end_time
                )
              }}
            </span>

          </div>

          <div
            v-if="deleteError"
            class="error-box delete-error"
          >
            <AlertCircle :size="17" />

            <span>
              {{ deleteError }}
            </span>
          </div>

          <div class="delete-actions">

            <button
              class="btn-secondary"
              :disabled="deleting"
              @click="closeDeleteModal"
            >
              Cancel
            </button>

            <button
              class="btn-danger"
              :disabled="deleting"
              @click="deleteSchedule"
            >

              <Loader2
                v-if="deleting"
                :size="17"
                class="spin"
              />

              {{
                deleting
                  ? "Deleting..."
                  : "Delete Schedule"
              }}

            </button>

          </div>

        </div>

      </div>

    </div>
  </AdminLayout>
</template>

<style scoped>
.page {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* 
   STATS
 */

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
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
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: #eef2ff;
  color: #1e3a8a;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.stat-card span {
  display: block;
  color: #6b7280;
  font-size: 13px;
}

.stat-card strong {
  display: block;
  margin-top: 3px;
  font-size: 21px;
  color: #111827;
}

/* 
   TOOLBAR
 */

.toolbar-panel {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 14px;
}

.search-filter-box {
  flex: 1;
  min-width: 260px;
  height: 40px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 12px;
  color: #6b7280;
  box-sizing: border-box;
}

.search-filter-box:focus-within {
  border-color: #1e3a8a;
}

.search-filter-box input {
  border: 0;
  outline: 0;
  width: 100%;
  background: transparent;
  font-size: 13px;
  color: #111827;
}

.search-filter-box input::placeholder {
  color: #9ca3af;
}

.toolbar-panel select {
  height: 40px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  padding: 0 10px;
  background: #fff;
  color: #374151;
  outline: none;
  min-width: 140px;
}

.toolbar-panel select:focus {
  border-color: #1e3a8a;
}

/* 
   BUTTONS
 */

.btn-primary {
  border: 0;
  background: #172554;
  color: #fff;
  height: 40px;
  border-radius: 8px;
  padding: 0 15px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 500;
  white-space: nowrap;
}

.btn-primary:hover {
  background: #1e3a8a;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  height: 40px;
  padding: 0 15px;
  border: 1px solid #d1d5db;
  background: #fff;
  border-radius: 8px;
  cursor: pointer;
  color: #374151;
  font-size: 13px;
}

.btn-secondary:hover:not(:disabled) {
  background: #f9fafb;
}

.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-danger {
  height: 40px;
  padding: 0 15px;
  border: 0;
  background: #dc2626;
  color: #fff;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  cursor: pointer;
  font-size: 13px;
}

.btn-danger:hover {
  background: #b91c1c;
}

.btn-danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* 
   PANEL
 */

.panel {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
}

.panel-header {
  padding: 18px 20px;
  border-bottom: 1px solid #e5e7eb;
}

.panel-header h3 {
  margin: 0;
  font-size: 17px;
  color: #111827;
}

.panel-header p {
  margin: 4px 0 0;
  color: #6b7280;
  font-size: 13px;
}

/* 
   TABLE
 */

.table-wrap {
  overflow-x: auto;
}

.custom-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1050px;
}

.custom-table th,
.custom-table td {
  padding: 13px 15px;
  border-bottom: 1px solid #eef0f3;
  text-align: left;
  white-space: nowrap;
  font-size: 13px;
  color: #374151;
}

.custom-table th {
  background: #f8fafc;
  color: #4b5563;
  font-weight: 600;
}

.custom-table tbody tr:hover {
  background: #fafbfc;
}

.custom-table tbody tr:last-child td {
  border-bottom: 0;
}

.custom-table td strong {
  color: #111827;
  font-weight: 600;
}

.custom-table td small {
  display: block;
  color: #6b7280;
  margin-top: 3px;
  max-width: 180px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.icon-text {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #4b5563;
}

.icon-text svg {
  color: #64748b;
  flex-shrink: 0;
}

.time-cell {
  font-weight: 600;
  color: #111827;
}

.actions {
  display: flex;
  gap: 6px;
}

.action-btn {
  width: 32px;
  height: 32px;
  border: 0;
  border-radius: 7px;
  display: grid;
  place-items: center;
  cursor: pointer;
}

.action-btn.edit {
  background: #eef2ff;
  color: #3730a3;
}

.action-btn.edit:hover {
  background: #e0e7ff;
}

.action-btn.delete {
  background: #fef2f2;
  color: #dc2626;
}

.action-btn.delete:hover {
  background: #fee2e2;
}

.loading,
.empty {
  padding: 45px;
  text-align: center;
  color: #6b7280;
}

.loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
}

/* 
   SPINNER
 */

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* 
   MODAL
 */

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  display: grid;
  place-items: center;
  padding: 20px;
  z-index: 100;
}

.modal {
  width: min(720px, 100%);
  max-height: 90vh;
  overflow-y: auto;
  background: #fff;
  border-radius: 14px;
  box-shadow:
    0 20px 50px rgba(0, 0, 0, 0.18);
}

.modal-header {
  padding: 18px 20px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 15px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  color: #111827;
  font-size: 18px;
}

.modal-header p {
  margin: 4px 0 0;
  color: #6b7280;
  font-size: 13px;
  line-height: 1.5;
}

.close-btn {
  width: 34px;
  height: 34px;
  border: 0;
  background: #f3f4f6;
  color: #4b5563;
  border-radius: 8px;
  display: grid;
  place-items: center;
  cursor: pointer;
  flex-shrink: 0;
}

.close-btn:hover:not(:disabled) {
  background: #e5e7eb;
}

.close-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* 
   FORM
 */

.form-grid {
  padding: 20px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.form-grid > * {
  min-width: 0;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group.full {
  grid-column: 1 / -1;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #374151;
}

.form-group input,
.form-group select {
  width: 100%;
  min-width: 0;
  max-width: 100%;
  height: 40px;
  box-sizing: border-box;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  padding: 0 11px;
  outline: none;
  background: #fff;
  color: #111827;
  font-size: 13px;
}

.form-group input::placeholder {
  color: #9ca3af;
}

.form-group input:focus,
.form-group select:focus {
  border-color: #1e3a8a;
}

.form-group select:disabled,
.form-group input:disabled {
  background: #f3f4f6;
  color: #9ca3af;
  cursor: not-allowed;
}

.helper-text {
  color: #9ca3af;
  font-size: 11px;
  line-height: 1.4;
}

/* 
   ERROR
 */

.error-box {
  margin: 16px 20px 0;
  padding: 11px 13px;
  border-radius: 8px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #b91c1c;
  font-size: 13px;
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.error-box svg {
  flex-shrink: 0;
  margin-top: 1px;
}

.error-box span {
  line-height: 1.45;
}

/* 
   MODAL FOOTER
 */

.modal-footer {
  padding: 15px 20px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

/* 
   DELETE MODAL
 */

.delete-modal {
  width: min(420px, 100%);
  background: #fff;
  border-radius: 14px;
  padding: 25px;
  text-align: center;
  box-shadow:
    0 20px 50px rgba(0, 0, 0, 0.18);
}

.delete-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: #fee2e2;
  color: #dc2626;
  display: grid;
  place-items: center;
  margin: 0 auto 15px;
}

.delete-modal h3 {
  margin: 0;
  color: #111827;
  font-size: 18px;
}

.delete-modal p {
  color: #64748b;
  font-size: 14px;
  margin: 8px 0 15px;
  line-height: 1.5;
}

.delete-info {
  padding: 12px;
  border: 1px solid #e5e7eb;
  background: #f8fafc;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  text-align: left;
  margin-bottom: 15px;
}

.delete-info strong {
  color: #111827;
  font-size: 14px;
}

.delete-info span {
  color: #64748b;
  font-size: 12px;
  line-height: 1.45;
}

.delete-error {
  margin: 0 0 15px;
  text-align: left;
}

.delete-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

/* 
   RESPONSIVE
 */

@media (max-width: 900px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .toolbar-panel {
    align-items: stretch;
  }

  .search-filter-box {
    min-width: 100%;
  }

  .toolbar-panel select,
  .toolbar-panel .btn-primary {
    flex: 1;
  }
}

@media (max-width: 650px) {
  .page {
    gap: 15px;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-group.full {
    grid-column: auto;
  }

  .toolbar-panel {
    flex-direction: column;
  }

  .toolbar-panel select,
  .toolbar-panel .btn-primary {
    width: 100%;
    flex: none;
  }

  .modal-overlay {
    padding: 12px;
  }

  .modal {
    max-height: 94vh;
    border-radius: 12px;
  }

  .modal-header {
    padding: 16px;
  }

  .form-grid {
    padding: 16px;
  }

  .modal-footer {
    padding: 14px 16px;
  }

  .delete-modal {
    padding: 22px;
  }

  .delete-actions {
    flex-direction: column-reverse;
  }

  .delete-actions button {
    width: 100%;
  }
}
</style>