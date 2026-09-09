<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import {
  Plus,
  Search,
  Edit2,
  Trash2,
  X,
  Users,
  Layers,
  AlertCircle,
  CheckCircle2,
} from "lucide-vue-next";

import api from "@/services/api";
import AdminLayout from "@/components/admin/AdminLayout.vue";

interface User {
  id: number;
  user_id?: string | null;
  name: string;
  email: string;
}

interface Department {
  id: number;
  code: string;
  name: string;
  status: string;
}

interface Batch {
  id: number;
  department: string;
  batch_number: number;
  start_year?: string | null;
  current_semester: string;
  status: string;
}

interface Section {
  id: number;
  batch_id: number;
  section_name: string;
  capacity?: number;
  enrolled?: number;
  status: string;
}

interface Student {
  id: number;
  user_id: number;
  department: string;
  batch_id: number;
  section_id?: number | null;
  semester: string;
  status: string;
  user?: User;
  batch?: Batch;
  section?: Section | null;
}

const students = ref<Student[]>([]);
const departments = ref<Department[]>([]);
const batches = ref<Batch[]>([]);
const sections = ref<Section[]>([]);

const loading = ref(false);
const saving = ref(false);
const creatingSection = ref(false);

const showStudentModal = ref(false);
const showSectionModal = ref(false);
const showDeleteModal = ref(false);

const editingStudent = ref<Student | null>(null);
const deletingStudent = ref<Student | null>(null);

const search = ref("");
const departmentFilter = ref("");
const statusFilter = ref("");

/* MESSAGE SYSTEM */

const message = ref("");
const messageType = ref<"error" | "success">("error");

let messageTimer: ReturnType<typeof setTimeout> | null = null;

function showMessage(
  text: string,
  type: "error" | "success" = "error"
) {
  message.value = text;
  messageType.value = type;

  if (messageTimer) {
    clearTimeout(messageTimer);
  }

  messageTimer = setTimeout(() => {
    message.value = "";
  }, 4000);
}

function clearMessage() {
  message.value = "";

  if (messageTimer) {
    clearTimeout(messageTimer);
    messageTimer = null;
  }
}

/* FORMS */

const studentForm = reactive({
  user_id: "",
  name: "",
  email: "",
  password: "",
  department: "",
  batch_id: "",
  section_id: "",
  semester: "",
  status: "Active",
});

const sectionForm = reactive({
  department: "",
  batch_id: "",
  section_name: "",
  capacity: "",
  status: "Active",
  description: "",
});

/* COMPUTED */

const filteredStudents = computed(() => {
  const keyword = search.value.toLowerCase().trim();

  return students.value.filter((student) => {
    const studentId =
      student.user?.user_id?.toLowerCase() || "";

    const name =
      student.user?.name?.toLowerCase() || "";

    const email =
      student.user?.email?.toLowerCase() || "";

    const matchesSearch =
      !keyword ||
      studentId.includes(keyword) ||
      name.includes(keyword) ||
      email.includes(keyword);

    const matchesDepartment =
      !departmentFilter.value ||
      student.department === departmentFilter.value;

    const matchesStatus =
      !statusFilter.value ||
      student.status === statusFilter.value;

    return (
      matchesSearch &&
      matchesDepartment &&
      matchesStatus
    );
  });
});

const activeStudents = computed(
  () =>
    students.value.filter(
      (student) => student.status === "Active"
    ).length
);

const inactiveStudents = computed(
  () =>
    students.value.filter(
      (student) => student.status === "Inactive"
    ).length
);

/* HELPERS */

function getStudentId(student: Student) {
  return student.user?.user_id || "—";
}

function getSectionName(student: Student) {
  return student.section?.section_name || "—";
}

/* STUDENTS */

async function loadStudents() {
  loading.value = true;

  try {
    const response = await api.get("/admin/students");

    students.value = response.data.students || [];
  } catch (error) {
    console.error("Failed to load students:", error);
  } finally {
    loading.value = false;
  }
}

async function loadDepartments() {
  try {
    const response = await api.get(
      "/admin/departments"
    );

    departments.value =
      response.data.departments || [];
  } catch (error) {
    console.error(
      "Failed to load departments:",
      error
    );
  }
}

async function loadBatches(department?: string) {
  const selectedDepartment =
    department || studentForm.department;

  if (!selectedDepartment) {
    batches.value = [];
    return;
  }

  try {
    const response = await api.get("/admin/batches", {
      params: {
        department: selectedDepartment,
        status: "Active",
      },
    });

    batches.value = response.data.batches || [];
  } catch (error) {
    console.error(
      "Failed to load batches:",
      error
    );
  }
}

async function loadSections(
  batchId?: string | number
) {
  const selectedBatch =
    batchId || studentForm.batch_id;

  if (!selectedBatch) {
    sections.value = [];
    return;
  }

  try {
    const response = await api.get(
      "/admin/sections",
      {
        params: {
          batch_id: selectedBatch,
          status: "Active",
        },
      }
    );

    sections.value =
      response.data.sections || [];
  } catch (error) {
    console.error(
      "Failed to load sections:",
      error
    );
  }
}

/* DEPARTMENT / BATCH CHANGE */

async function departmentChanged() {
  clearMessage();

  studentForm.batch_id = "";
  studentForm.section_id = "";
  studentForm.semester = "";

  batches.value = [];
  sections.value = [];

  await loadBatches();
}

async function batchChanged() {
  clearMessage();

  studentForm.section_id = "";

  const batch = batches.value.find(
    (item) =>
      String(item.id) ===
      String(studentForm.batch_id)
  );

  studentForm.semester =
    batch?.current_semester || "";

  await loadSections();
}

/* CREATE STUDENT */

function openCreateStudent() {
  editingStudent.value = null;

  clearMessage();

  Object.assign(studentForm, {
    user_id: "",
    name: "",
    email: "",
    password: "",
    department: "",
    batch_id: "",
    section_id: "",
    semester: "",
    status: "Active",
  });

  batches.value = [];
  sections.value = [];

  showStudentModal.value = true;
}

/* EDIT STUDENT */

async function openEditStudent(student: Student) {
  editingStudent.value = student;

  clearMessage();

  Object.assign(studentForm, {
    user_id: student.user?.user_id || "",
    name: student.user?.name || "",
    email: student.user?.email || "",
    password: "",
    department: student.department || "",
    batch_id: String(student.batch_id || ""),
    section_id: String(student.section_id || ""),
    semester: student.semester || "",
    status: student.status || "Active",
  });

  showStudentModal.value = true;

  await loadBatches(studentForm.department);
  await loadSections(studentForm.batch_id);
}

/* CLOSE STUDENT MODAL */

function closeStudentModal() {
  if (!saving.value) {
    showStudentModal.value = false;
    clearMessage();
  }
}

/* SAVE STUDENT */

async function saveStudent() {
  clearMessage();

  if (
    !studentForm.user_id ||
    !studentForm.name ||
    !studentForm.email
  ) {
    showMessage(
      "Student ID, name and email are required.",
      "error"
    );
    return;
  }

  if (
    !editingStudent.value &&
    !studentForm.password
  ) {
    showMessage(
      "Password is required when creating a student.",
      "error"
    );
    return;
  }

  if (
    !studentForm.department ||
    !studentForm.batch_id ||
    !studentForm.semester
  ) {
    showMessage(
      "Department, batch and semester are required.",
      "error"
    );
    return;
  }

  saving.value = true;

  try {
    const payload: any = {
      user_id: studentForm.user_id,
      name: studentForm.name,
      email: studentForm.email,
      department: studentForm.department,
      batch_id: Number(studentForm.batch_id),
      section_id: studentForm.section_id
        ? Number(studentForm.section_id)
        : null,
      semester: studentForm.semester,
      status: studentForm.status,
    };

    if (studentForm.password) {
      payload.password = studentForm.password;
    }

    if (editingStudent.value) {
      await api.put(
        `/admin/students/${editingStudent.value.id}`,
        payload
      );
    } else {
      payload.password = studentForm.password;

      await api.post(
        "/admin/students",
        payload
      );
    }

    showStudentModal.value = false;

    clearMessage();

    await loadStudents();
  } catch (error: any) {
    console.error(error);

    showMessage(
      error.response?.data?.message ||
        "Failed to save student.",
      "error"
    );
  } finally {
    saving.value = false;
  }
}

/* CREATE SECTION */

function openCreateSection() {
  clearMessage();

  if (!studentForm.department) {
    showMessage(
      "Please select a department first.",
      "error"
    );
    return;
  }

  if (!studentForm.batch_id) {
    showMessage(
      "Please select a batch first.",
      "error"
    );
    return;
  }

  sectionForm.department =
    studentForm.department;

  sectionForm.batch_id =
    studentForm.batch_id;

  sectionForm.section_name = "";
  sectionForm.capacity = "";
  sectionForm.status = "Active";
  sectionForm.description = "";

  showSectionModal.value = true;
}

function closeSectionModal() {
  if (!creatingSection.value) {
    showSectionModal.value = false;
    clearMessage();
  }
}

/* SAVE SECTION */

async function saveSection() {
  clearMessage();

  const sectionName =
    sectionForm.section_name
      .trim()
      .toUpperCase();

  const capacity = Number(
    sectionForm.capacity
  );

  if (!sectionName) {
    showMessage(
      "Please enter a section name.",
      "error"
    );
    return;
  }

  if (!sectionForm.department) {
    showMessage(
      "Department is required.",
      "error"
    );
    return;
  }

  if (!sectionForm.batch_id) {
    showMessage(
      "Batch is required.",
      "error"
    );
    return;
  }

  if (
    !sectionForm.capacity ||
    !Number.isInteger(capacity) ||
    capacity <= 0
  ) {
    showMessage(
      "Please enter a valid section capacity.",
      "error"
    );
    return;
  }

  if (!sectionForm.status) {
    showMessage(
      "Section status is required.",
      "error"
    );
    return;
  }

  creatingSection.value = true;

  try {
    const response = await api.post(
      "/admin/sections",
      {
        batch_id: Number(
          sectionForm.batch_id
        ),
        section_name: sectionName,
        capacity,
        status: sectionForm.status,
        description:
          sectionForm.description.trim() || null,
      }
    );

    await loadSections(
      sectionForm.batch_id
    );

    const createdSection =
      response.data.section;

    if (createdSection?.id) {
      studentForm.section_id =
        String(createdSection.id);
    } else {
      const matchedSection =
        sections.value.find(
          (section) =>
            section.section_name.toUpperCase() ===
            sectionName
        );

      if (matchedSection) {
        studentForm.section_id =
          String(matchedSection.id);
      }
    }

    showSectionModal.value = false;

    Object.assign(sectionForm, {
      department: "",
      batch_id: "",
      section_name: "",
      capacity: "",
      status: "Active",
      description: "",
    });

    clearMessage();
  } catch (error: any) {
    console.error(error);

    showMessage(
      error.response?.data?.message ||
        "Failed to create section.",
      "error"
    );
  } finally {
    creatingSection.value = false;
  }
}

/* DELETE STUDENT */

function confirmDelete(student: Student) {
  clearMessage();

  deletingStudent.value = student;
  showDeleteModal.value = true;
}

async function deleteStudent() {
  if (!deletingStudent.value) return;

  try {
    await api.delete(
      `/admin/students/${deletingStudent.value.id}`
    );

    showDeleteModal.value = false;
    deletingStudent.value = null;

    await loadStudents();
  } catch (error: any) {
    console.error(error);

    showMessage(
      error.response?.data?.message ||
        "Failed to delete student.",
      "error"
    );

    showDeleteModal.value = false;
  }
}

/* MOUNT */

onMounted(async () => {
  await Promise.all([
    loadStudents(),
    loadDepartments(),
  ]);
});
</script>

<template>
  <AdminLayout
    title="Students"
    subtitle="Manage university students and academic information."
  >
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div>
        <h2>Student Management</h2>
        <p>
          Manage student profiles, batches and sections.
        </p>
      </div>

      <button
        class="btn-primary"
        @click="openCreateStudent"
      >
        <Plus :size="18" />
        Add Student
      </button>
    </div>

    <!-- GLOBAL MESSAGE -->
    <div
      v-if="
        message &&
        !showStudentModal &&
        !showSectionModal
      "
      class="message-box"
      :class="messageType"
    >
      <AlertCircle
        v-if="messageType === 'error'"
        :size="18"
      />

      <CheckCircle2
        v-else
        :size="18"
      />

      <span>{{ message }}</span>

      <button
        class="message-close"
        @click="clearMessage"
      >
        <X :size="16" />
      </button>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">
          <Users :size="22" />
        </div>

        <div>
          <span>Total Students</span>
          <strong>{{ students.length }}</strong>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">
          <Users :size="22" />
        </div>

        <div>
          <span>Active Students</span>
          <strong>{{ activeStudents }}</strong>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">
          <Users :size="22" />
        </div>

        <div>
          <span>Inactive Students</span>
          <strong>{{ inactiveStudents }}</strong>
        </div>
      </div>
    </div>

    <!-- SEARCH / FILTER -->
    <div class="toolbar-panel">
      <div class="search-filter-box">
        <Search :size="18" />

        <input
          v-model="search"
          type="text"
          placeholder="Search by Student ID, name or email..."
        />
      </div>

      <select v-model="departmentFilter">
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

      <select v-model="statusFilter">
        <option value="">
          All Status
        </option>

        <option value="Active">
          Active
        </option>

        <option value="Inactive">
          Inactive
        </option>
      </select>
    </div>

    <!-- TABLE -->
    <div class="panel table-panel">
      <div class="table-header">
        <div>
          <h3>Students</h3>

          <span>
            {{ filteredStudents.length }}
            students found
          </span>
        </div>
      </div>

      <div class="table-wrapper">
        <table class="custom-table">
          <thead>
            <tr>
              <th>Student ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Department</th>
              <th>Batch</th>
              <th>Semester</th>
              <th>Section</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="student in filteredStudents"
              :key="student.id"
            >
              <td class="student-id">
                {{ getStudentId(student) }}
              </td>

              <td>
                {{ student.user?.name || "—" }}
              </td>

              <td>
                {{ student.user?.email || "—" }}
              </td>

              <td>
                {{ student.department }}
              </td>

              <td>
                {{
                  student.batch
                    ? `Batch ${student.batch.batch_number}`
                    : "—"
                }}
              </td>

              <td>
                {{ student.semester }}
              </td>

              <td class="section-cell">
                {{ getSectionName(student) }}
              </td>

              <td>
                <span
                  class="status-badge"
                  :class="
                    student.status === 'Active'
                      ? 'active'
                      : 'inactive'
                  "
                >
                  {{ student.status }}
                </span>
              </td>

              <td>
                <div class="action-buttons">
                  <button
                    class="icon-btn edit"
                    title="Edit"
                    @click="openEditStudent(student)"
                  >
                    <Edit2 :size="17" />
                  </button>

                  <button
                    class="icon-btn delete"
                    title="Delete"
                    @click="confirmDelete(student)"
                  >
                    <Trash2 :size="17" />
                  </button>
                </div>
              </td>
            </tr>

            <tr
              v-if="
                !loading &&
                !filteredStudents.length
              "
            >
              <td
                colspan="9"
                class="empty-state"
              >
                No students found.
              </td>
            </tr>

            <tr v-if="loading">
              <td
                colspan="9"
                class="empty-state"
              >
                Loading students...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- STUDENT MODAL -->

    <div
      v-if="showStudentModal"
      class="modal-backdrop"
      @click.self="closeStudentModal"
    >
      <div class="modal">
        <div class="modal-header">
          <div>
            <h3>
              {{
                editingStudent
                  ? "Edit Student"
                  : "Add Student"
              }}
            </h3>

            <p>
              {{
                editingStudent
                  ? "Update student information."
                  : "Create a new student account."
              }}
            </p>
          </div>

          <button
            class="close-btn"
            @click="closeStudentModal"
          >
            <X :size="20" />
          </button>
        </div>

        <div class="modal-body">

          <!-- INLINE MESSAGE -->
          <div
            v-if="message"
            class="message-box modal-message"
            :class="messageType"
          >
            <AlertCircle
              v-if="messageType === 'error'"
              :size="18"
            />

            <CheckCircle2
              v-else
              :size="18"
            />

            <span>{{ message }}</span>

            <button
              class="message-close"
              @click="clearMessage"
            >
              <X :size="16" />
            </button>
          </div>

          <div class="form-grid">

            <!-- STUDENT ID -->
            <div class="form-group">
              <label>
                Student ID *
              </label>

              <input
                v-model="studentForm.user_id"
                type="text"
                placeholder="Enter student ID"
              />
            </div>

            <!-- NAME -->
            <div class="form-group">
              <label>
                Student Name *
              </label>

              <input
                v-model="studentForm.name"
                type="text"
                placeholder="Enter student name"
              />
            </div>

            <!-- EMAIL -->
            <div class="form-group">
              <label>Email *</label>

              <input
                v-model="studentForm.email"
                type="email"
                placeholder="Enter email"
              />
            </div>

            <!-- PASSWORD -->
            <div class="form-group">
              <label>
                Password
                <span v-if="!editingStudent">
                  *
                </span>
              </label>

              <input
                v-model="studentForm.password"
                type="password"
                :placeholder="
                  editingStudent
                    ? 'Leave blank to keep current password'
                    : 'Enter password'
                "
              />
            </div>

            <!-- DEPARTMENT -->
            <div class="form-group">
              <label>
                Department *
              </label>

              <select
                v-model="studentForm.department"
                @change="departmentChanged"
              >
                <option value="">
                  Select Department
                </option>

                <option
                  v-for="department in departments"
                  :key="department.id"
                  :value="department.code"
                >
                  {{ department.code }} -
                  {{ department.name }}
                </option>
              </select>
            </div>

            <!-- BATCH -->
            <div class="form-group">
              <label>Batch *</label>

              <select
                v-model="studentForm.batch_id"
                :disabled="
                  !studentForm.department
                "
                @change="batchChanged"
              >
                <option value="">
                  Select Batch
                </option>

                <option
                  v-for="batch in batches"
                  :key="batch.id"
                  :value="String(batch.id)"
                >
                  Batch
                  {{ batch.batch_number }}
                  <span
                    v-if="batch.start_year"
                  >
                    -
                    {{ batch.start_year }}
                  </span>
                </option>
              </select>
            </div>

            <!-- SEMESTER -->
            <div class="form-group">
              <label>Semester *</label>

              <input
                v-model="studentForm.semester"
                type="text"
                readonly
                placeholder="Select batch first"
              />
            </div>

            <!-- SECTION -->
            <div class="form-group section-form-group">
              <label>Section</label>

              <div class="section-select-row">
                <select
                  v-model="studentForm.section_id"
                  :disabled="
                    !studentForm.batch_id
                  "
                >
                  <option value="">
                    Select Section
                  </option>

                  <option
                    v-for="section in sections"
                    :key="section.id"
                    :value="String(section.id)"
                  >
                    {{ section.section_name }}
                  </option>
                </select>

                <button
                  type="button"
                  class="create-section-btn"
                  :disabled="
                    !studentForm.department ||
                    !studentForm.batch_id
                  "
                  @click="openCreateSection"
                >
                  <Plus :size="16" />
                  Create Section
                </button>
              </div>

              <small
                v-if="
                  studentForm.batch_id &&
                  !sections.length
                "
                class="section-help"
              >
                No section exists for this batch.
                Create one.
              </small>
            </div>

            <!-- STATUS -->
            <div class="form-group">
              <label>Status *</label>

              <select
                v-model="studentForm.status"
              >
                <option value="Active">
                  Active
                </option>

                <option value="Inactive">
                  Inactive
                </option>
              </select>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button
            class="btn-secondary"
            @click="closeStudentModal"
            :disabled="saving"
          >
            Cancel
          </button>

          <button
            class="btn-primary"
            @click="saveStudent"
            :disabled="saving"
          >
            {{
              saving
                ? "Saving..."
                : "Save Student"
            }}
          </button>
        </div>
      </div>
    </div>

    <!-- CREATE SECTION MODAL -->

    <div
      v-if="showSectionModal"
      class="modal-backdrop"
      @click.self="closeSectionModal"
    >
      <div class="small-modal">
        <div class="modal-header">
          <div>
            <div class="modal-title-icon">
              <Layers :size="19" />
            </div>

            <h3>Create Section</h3>

            <p>
              Create a section for the selected batch.
            </p>
          </div>

          <button
            class="close-btn"
            @click="closeSectionModal"
          >
            <X :size="20" />
          </button>
        </div>

        <div class="modal-body">

          <!-- INLINE MESSAGE -->
          <div
            v-if="message"
            class="message-box modal-message"
            :class="messageType"
          >
            <AlertCircle
              v-if="messageType === 'error'"
              :size="18"
            />

            <CheckCircle2
              v-else
              :size="18"
            />

            <span>{{ message }}</span>

            <button
              class="message-close"
              @click="clearMessage"
            >
              <X :size="16" />
            </button>
          </div>

          <!-- DEPARTMENT -->
          <div class="form-group">
            <label>Department</label>

            <input
              :value="sectionForm.department"
              type="text"
              readonly
            />
          </div>

          <!-- BATCH -->
          <div class="form-group section-modal-field">
            <label>Batch</label>

            <select
              v-model="sectionForm.batch_id"
              disabled
            >
              <option value="">
                Select Batch
              </option>

              <option
                v-for="batch in batches"
                :key="batch.id"
                :value="String(batch.id)"
              >
                Batch
                {{ batch.batch_number }}
              </option>
            </select>
          </div>

          <!-- SECTION NAME -->
          <div class="form-group section-modal-field">
            <label>
              Section Name *
            </label>

            <input
              v-model="
                sectionForm.section_name
              "
              type="text"
              maxlength="10"
              placeholder="Example: A"
              @input="
                sectionForm.section_name =
                  sectionForm.section_name
                    .toUpperCase()
                    .replace(/\s/g, '')
              "
            />
          </div>

          <!-- CAPACITY -->
          <div class="form-group section-modal-field">
            <label>
              Capacity *
            </label>

            <input
              v-model="sectionForm.capacity"
              type="number"
              min="1"
              max="1000"
              placeholder="Enter section capacity"
            />

            <small>
              Maximum number of students allowed in
              this section.
            </small>
          </div>

          <!-- STATUS -->
          <div class="form-group section-modal-field">
            <label>
              Status *
            </label>

            <select
              v-model="sectionForm.status"
            >
              <option value="Active">
                Active
              </option>

              <option value="Inactive">
                Inactive
              </option>
            </select>
          </div>

          <!-- DESCRIPTION -->
          <div class="form-group section-modal-field">
            <label>
              Description
            </label>

            <textarea
              v-model="sectionForm.description"
              rows="3"
              maxlength="500"
              placeholder="Optional section description"
            ></textarea>
          </div>

        </div>

        <div class="modal-footer">
          <button
            class="btn-secondary"
            @click="closeSectionModal"
            :disabled="creatingSection"
          >
            Cancel
          </button>

          <button
            class="btn-primary"
            @click="saveSection"
            :disabled="creatingSection"
          >
            {{
              creatingSection
                ? "Creating..."
                : "Create Section"
            }}
          </button>
        </div>
      </div>
    </div>

    <!-- DELETE MODAL -->

    <div
      v-if="showDeleteModal"
      class="modal-backdrop"
      @click.self="
        showDeleteModal = false
      "
    >
      <div class="confirm-modal">

        <div class="confirm-icon">
          <Trash2 :size="24" />
        </div>

        <h3>Delete Student?</h3>

        <p>
          Are you sure you want to delete
          <strong>
            {{
              deletingStudent?.user?.name
            }}
          </strong>
          ?
        </p>

        <div class="modal-footer">
          <button
            class="btn-secondary"
            @click="
              showDeleteModal = false
            "
          >
            Cancel
          </button>

          <button
            class="btn-danger"
            @click="deleteStudent"
          >
            Delete
          </button>
        </div>

      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  margin-bottom: 24px;
}

.page-header h2 {
  margin: 0 0 5px;
  font-size: 22px;
  color: #172033;
}

.page-header p {
  margin: 0;
  color: #697386;
}

/* MESSAGE BOX */

.message-box {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  border-radius: 8px;
  margin-bottom: 18px;
  font-size: 13px;
  font-weight: 500;
}

.message-box.error {
  background: #fff5f5;
  border: 1px solid #f1b5b3;
  color: #b42318;
}

.message-box.success {
  background: #f0fdf4;
  border: 1px solid #a7e3bb;
  color: #16834d;
}

.message-box span {
  flex: 1;
  line-height: 1.5;
}

.message-close {
  border: 0;
  background: transparent;
  color: inherit;
  cursor: pointer;
  display: grid;
  place-items: center;
  padding: 2px;
  opacity: 0.7;
}

.message-close:hover {
  opacity: 1;
}

.modal-message {
  margin-bottom: 20px;
}

/* STATS */

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
  margin-bottom: 22px;
}

.stat-card {
  background: #fff;
  border: 1px solid #e6eaf0;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
}

.stat-card span {
  display: block;
  color: #697386;
  font-size: 13px;
}

.stat-card strong {
  display: block;
  color: #172033;
  font-size: 24px;
  margin-top: 5px;
}

.stat-icon {
  width: 46px;
  height: 46px;
  border-radius: 10px;
  background: #eef3fb;
  color: #173b72;
  display: grid;
  place-items: center;
}

/* TOOLBAR */

.toolbar-panel {
  display: flex;
  gap: 12px;
  padding: 16px;
  background: #fff;
  border: 1px solid #e6eaf0;
  border-radius: 12px;
  margin-bottom: 20px;
}

.search-filter-box {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 9px;
  border: 1px solid #dfe4ea;
  border-radius: 8px;
  padding: 0 12px;
}

.search-filter-box input {
  width: 100%;
  border: 0;
  outline: 0;
  padding: 11px 0;
}

.toolbar-panel select {
  min-width: 150px;
  border: 1px solid #dfe4ea;
  border-radius: 8px;
  padding: 0 12px;
  background: #fff;
}

/* TABLE */

.panel {
  background: #fff;
  border: 1px solid #e6eaf0;
  border-radius: 12px;
}

.table-header {
  padding: 18px 20px;
  border-bottom: 1px solid #edf0f3;
}

.table-header h3 {
  margin: 0;
  color: #172033;
}

.table-header span {
  font-size: 13px;
  color: #697386;
}

.table-wrapper {
  overflow-x: auto;
}

.custom-table {
  width: 100%;
  border-collapse: collapse;
}

.custom-table th,
.custom-table td {
  padding: 14px 16px;
  text-align: left;
  border-bottom: 1px solid #edf0f3;
  white-space: nowrap;
}

.custom-table th {
  background: #fafbfc;
  font-size: 12px;
  color: #697386;
  text-transform: uppercase;
}

.custom-table td {
  color: #30394a;
  font-size: 14px;
}

.student-id {
  font-weight: 600;
  color: #173b72 !important;
}

.section-cell {
  font-weight: 600;
}

.status-badge {
  padding: 5px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.status-badge.active {
  background: #e8f7ee;
  color: #16834d;
}

.status-badge.inactive {
  background: #fcebea;
  color: #c53d38;
}

/* ACTIONS */

.action-buttons {
  display: flex;
  gap: 7px;
}

.icon-btn {
  width: 34px;
  height: 34px;
  border: 0;
  border-radius: 7px;
  display: grid;
  place-items: center;
  cursor: pointer;
}

.icon-btn.edit {
  background: #eef3fb;
  color: #173b72;
}

.icon-btn.delete {
  background: #fcebea;
  color: #c53d38;
}

/* BUTTONS */

.btn-primary,
.btn-secondary,
.btn-danger,
.create-section-btn {
  border: 0;
  border-radius: 8px;
  padding: 11px 17px;
  cursor: pointer;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-primary {
  background: #173b72;
  color: #fff;
}

.btn-secondary {
  background: #eef1f5;
  color: #344054;
}

.btn-danger {
  background: #c53d38;
  color: #fff;
}

.create-section-btn {
  background: #eef3fb;
  color: #173b72;
  white-space: nowrap;
  padding: 10px 13px;
}

.create-section-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary:disabled,
.btn-secondary:disabled,
.btn-danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* EMPTY STATE */

.empty-state {
  text-align: center !important;
  padding: 45px !important;
  color: #697386 !important;
}

/* MODAL */

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  display: grid;
  place-items: center;
  z-index: 1000;
  padding: 20px;
}

.modal {
  width: min(760px, 100%);
  max-height: 90vh;
  overflow-y: auto;
  background: #fff;
  border-radius: 14px;
}

.small-modal {
  width: min(480px, 100%);
  max-height: 90vh;
  overflow-y: auto;
  background: #fff;
  border-radius: 14px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  padding: 20px;
  border-bottom: 1px solid #edf0f3;
}

.modal-header h3 {
  margin: 0;
  color: #172033;
}

.modal-header p {
  margin: 5px 0 0;
  color: #697386;
  font-size: 13px;
}

.modal-title-icon {
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  background: #eef3fb;
  color: #173b72;
  border-radius: 8px;
  margin-bottom: 10px;
}

.close-btn {
  border: 0;
  background: transparent;
  cursor: pointer;
  height: fit-content;
}

.modal-body {
  padding: 20px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 17px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #344054;
}

.form-group input,
.form-group select,
.form-group textarea {
  border: 1px solid #dfe4ea;
  border-radius: 8px;
  padding: 11px 12px;
  outline: none;
  background: #fff;
  font-family: inherit;
  font-size: 14px;
}

.form-group textarea {
  resize: vertical;
  min-height: 80px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  border-color: #173b72;
}

.form-group input:disabled,
.form-group select:disabled,
.form-group input[readonly] {
  background: #f7f8fa;
  color: #697386;
  cursor: not-allowed;
}

.section-form-group {
  grid-column: span 2;
}

.section-select-row {
  display: flex;
  gap: 9px;
}

.section-select-row select {
  flex: 1;
}

.section-help {
  color: #c53d38;
  font-size: 12px;
}

.section-modal-field {
  margin-top: 18px;
}

.form-group small {
  color: #697386;
  font-size: 12px;
  line-height: 1.5;
}

/* MODAL FOOTER */

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 20px;
  border-top: 1px solid #edf0f3;
}

/* DELETE CONFIRM MODAL */

.confirm-modal {
  width: min(420px, 100%);
  background: #fff;
  border-radius: 14px;
  padding: 28px;
  text-align: center;
}

.confirm-icon {
  width: 52px;
  height: 52px;
  margin: 0 auto 15px;
  border-radius: 50%;
  background: #fcebea;
  color: #c53d38;
  display: grid;
  place-items: center;
}

.confirm-modal h3 {
  margin: 0 0 8px;
}

.confirm-modal p {
  color: #697386;
  margin-bottom: 22px;
}

/* RESPONSIVE */

@media (max-width: 900px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .toolbar-panel {
    flex-direction: column;
  }

  .toolbar-panel select {
    width: 100%;
    min-height: 42px;
  }
}

@media (max-width: 650px) {
  .page-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .section-form-group {
    grid-column: span 1;
  }

  .section-select-row {
    flex-direction: column;
  }
}
</style>