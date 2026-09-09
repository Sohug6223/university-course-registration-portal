<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import {
  ArrowLeft,
  Building2,
  ChevronRight,
  Edit2,
  Plus,
  Search,
  Trash2,
  Users,
  Layers3,
  BookOpen,
  X,
  GraduationCap,
} from "lucide-vue-next";

import AdminLayout from "@/components/admin/AdminLayout.vue";
import api from "@/services/api";

/* 
   TYPES
 */

type Department = {
  id: number;
  code: string;
  name: string;
  status: "Active" | "Inactive";
  student_count: number;
  batch_count: number;
  course_count: number;
};

type Batch = {
  id: number;
  department: string;
  batch_number: number;
  start_year: string | null;
  current_semester: string;
  status: "Active" | "Inactive" | "Graduated";
  advisor_name: string | null;
  students_count: number;
  sections_count: number;
};

type Section = {
  id: number;
  course_id?: number;
  batch_id: number;
  section_name: string;
  teacher_name?: string | null;
  room?: string | null;
  capacity: number;
  enrolled: number;
  status: "Active" | "Inactive";
  description: string | null;
};

/* 
   STATE
 */

const departments = ref<Department[]>([]);
const batches = ref<Batch[]>([]);
const sections = ref<Section[]>([]);

const loading = ref(false);
const search = ref("");

const selectedDepartment = ref<Department | null>(null);
const selectedBatch = ref<Batch | null>(null);

const modal = ref<
  "department" | "batch" | "section" | null
>(null);

const editingId = ref<number | null>(null);
const saving = ref(false);

const feedback = ref<{
  type: "success" | "error";
  message: string;
} | null>(null);

let feedbackTimer: ReturnType<typeof setTimeout> | null = null;

const deleteType = ref<
  "department" | "batch" | "section" | null
>(null);

const deleteId = ref<number | null>(null);

/* 
   FORMS
 */

const departmentForm = ref({
  code: "",
  name: "",
  status: "Active" as "Active" | "Inactive",
});

const batchForm = ref({
  batch_number: "",
  start_year: "",
  current_semester: "1st",
  advisor_name: "",
  status: "Active" as "Active" | "Inactive",
});

/*
  IMPORTANT:
  Section form contains ONLY:
  Section Name
  Capacity
  Status
  Description
 */
const sectionForm = ref({
  section_name: "",
  capacity: "40",
  status: "Active" as "Active" | "Inactive",
  description: "",
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

/* 
   COMPUTED
 */

const filteredDepartments = computed(() => {
  const value = search.value.toLowerCase().trim();

  if (!value) {
    return departments.value;
  }

  return departments.value.filter(
    (department) =>
      department.code.toLowerCase().includes(value) ||
      department.name.toLowerCase().includes(value)
  );
});

const sortedBatches = computed(() => {
  return [...batches.value].sort(
    (a, b) =>
      Number(a.batch_number) -
      Number(b.batch_number)
  );
});

const totalStudents = computed(() => {
  return departments.value.reduce(
    (sum, department) =>
      sum + Number(department.student_count || 0),
    0
  );
});

const activeDepartments = computed(() => {
  return departments.value.filter(
    (department) =>
      department.status === "Active"
  ).length;
});

/* 
   FEEDBACK
 */

function notify(
  message: string,
  type: "success" | "error" = "success"
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
  }, 3500);
}

/* 
   MODAL
 */

function closeModal() {
  modal.value = null;
  editingId.value = null;
  saving.value = false;
}

function resetForms() {
  departmentForm.value = {
    code: "",
    name: "",
    status: "Active",
  };

  batchForm.value = {
    batch_number: "",
    start_year: "",
    current_semester: "1st",
    advisor_name: "",
    status: "Active",
  };

  sectionForm.value = {
    section_name: "",
    capacity: "40",
    status: "Active",
    description: "",
  };
}

/* 
   LOAD DEPARTMENTS
 */

async function loadDepartments() {
  loading.value = true;

  try {
    const { data } = await api.get(
      "/admin/departments"
    );

    departments.value =
      data.departments || [];
  } catch (error: any) {
    notify(
      error?.response?.data?.message ||
      "Failed to load departments.",
      "error"
    );
  } finally {
    loading.value = false;
  }
}

/* 
   LOAD BATCHES
 */

async function loadBatches() {
  if (!selectedDepartment.value) {
    return;
  }

  try {
    const { data } = await api.get(
      "/admin/batches",
      {
        params: {
          department:
            selectedDepartment.value.code,
        },
      }
    );

    batches.value = data.batches || [];

    batches.value.sort(
      (a: Batch, b: Batch) =>
        Number(a.batch_number) -
        Number(b.batch_number)
    );

    if (selectedBatch.value) {
      const updated = batches.value.find(
        (batch) =>
          batch.id === selectedBatch.value?.id
      );

      if (updated) {
        selectedBatch.value = updated;
      }
    }
  } catch (error: any) {
    notify(
      error?.response?.data?.message ||
      "Failed to load batches.",
      "error"
    );
  }
}

/* 
   LOAD SECTIONS
 */

async function loadSections() {
  if (!selectedBatch.value) {
    return;
  }

  try {
    const { data } = await api.get(
      "/admin/sections",
      {
        params: {
          batch_id: selectedBatch.value.id,
        },
      }
    );

    sections.value = data.sections || [];
  } catch (error: any) {
    notify(
      error?.response?.data?.message ||
      "Failed to load sections.",
      "error"
    );
  }
}

/* 
   NAVIGATION
 */

async function selectDepartment(
  department: Department
) {
  selectedDepartment.value = department;
  selectedBatch.value = null;

  batches.value = [];
  sections.value = [];

  await loadBatches();
}

async function selectBatch(batch: Batch) {
  selectedBatch.value = batch;
  sections.value = [];

  await loadSections();
}

function backToDepartments() {
  selectedDepartment.value = null;
  selectedBatch.value = null;

  batches.value = [];
  sections.value = [];

  search.value = "";
}

function backToBatches() {
  selectedBatch.value = null;
  sections.value = [];
}

/* 
   DEPARTMENT
 */

function openAddDepartment() {
  resetForms();
  editingId.value = null;
  modal.value = "department";
}

function openEditDepartment(
  department: Department
) {
  editingId.value = department.id;

  departmentForm.value = {
    code: department.code,
    name: department.name,
    status: department.status,
  };

  modal.value = "department";
}

async function saveDepartment() {
  saving.value = true;

  try {
    const isEditing =
      editingId.value !== null;

    if (isEditing) {
      await api.put(
        `/admin/departments/${editingId.value}`,
        departmentForm.value
      );
    } else {
      await api.post(
        "/admin/departments",
        departmentForm.value
      );
    }

    closeModal();

    await loadDepartments();

    if (selectedDepartment.value) {
      const updated =
        departments.value.find(
          (department) =>
            department.id ===
            selectedDepartment.value?.id
        );

      if (updated) {
        selectedDepartment.value =
          updated;
      }

      await loadBatches();
    }

    notify(
      isEditing
        ? "Department updated successfully."
        : "Department created successfully."
    );
  } catch (error: any) {
    notify(
      error?.response?.data?.message ||
      "Failed to save department.",
      "error"
    );
  } finally {
    saving.value = false;
  }
}

/* 
   BATCH
 */

function openAddBatch() {
  resetForms();
  editingId.value = null;
  modal.value = "batch";
}

function openEditBatch(batch: Batch) {
  editingId.value = batch.id;

  /*
    Graduated batches cannot be edited.
    
    Therefore batch.status here is already
    Active or Inactive.
   
    This also fixes TypeScript error TS2367.
   */
  batchForm.value = {
    batch_number: String(
      batch.batch_number
    ),
    start_year:
      batch.start_year || "",
    current_semester:
      batch.current_semester,
    advisor_name:
      batch.advisor_name || "",
    status:
      batch.status === "Graduated"
        ? "Active"
        : batch.status,
  };

  modal.value = "batch";
}

async function saveBatch() {
  if (!selectedDepartment.value) {
    return;
  }

  saving.value = true;

  try {
    const isEditing =
      editingId.value !== null;

    const payload = {
      department:
        selectedDepartment.value.code,

      batch_number: Number(
        batchForm.value.batch_number
      ),

      start_year:
        batchForm.value.start_year ||
        null,

      current_semester:
        batchForm.value.current_semester,

      advisor_name:
        batchForm.value.advisor_name.trim() ||
        null,

      status:
        batchForm.value.status,
    };

    let updatedBatch: Batch | null = null;

    if (isEditing) {
      const { data } = await api.put(
        `/admin/batches/${editingId.value}`,
        payload
      );

      updatedBatch =
        data.batch || null;
    } else {
      const { data } = await api.post(
        "/admin/batches",
        payload
      );

      updatedBatch =
        data.batch || null;
    }

    /*
      Close immediately after successful request.
     */
    closeModal();

    if (updatedBatch) {
      const index =
        batches.value.findIndex(
          (batch) =>
            batch.id ===
            updatedBatch!.id
        );

      if (index >= 0) {
        batches.value[index] =
          updatedBatch;
      } else {
        batches.value.push(
          updatedBatch
        );
      }
    }

    batches.value.sort(
      (a, b) =>
        Number(a.batch_number) -
        Number(b.batch_number)
    );

    await loadBatches();
    await loadDepartments();

    if (selectedDepartment.value) {
      const updatedDepartment =
        departments.value.find(
          (department) =>
            department.id ===
            selectedDepartment.value?.id
        );

      if (updatedDepartment) {
        selectedDepartment.value =
          updatedDepartment;
      }
    }

    notify(
      isEditing
        ? "Batch updated successfully."
        : "Batch created successfully."
    );
  } catch (error: any) {
    notify(
      error?.response?.data?.message ||
      "Failed to save batch.",
      "error"
    );
  } finally {
    saving.value = false;
  }
}

/* 
   ADVANCE SEMESTER
 */

async function advanceSemester(
  batch: Batch
) {
  if (batch.status === "Graduated") {
    return;
  }

  try {
    const { data } = await api.post(
      `/admin/batches/${batch.id}/advance-semester`
    );

    const updatedBatch:
      | Batch
      | null =
      data.batch || null;

    if (updatedBatch) {
      const index =
        batches.value.findIndex(
          (item) =>
            item.id ===
            updatedBatch.id
        );

      if (index !== -1) {
        batches.value[index] =
          updatedBatch;
      }

      if (
        selectedBatch.value?.id ===
        updatedBatch.id
      ) {
        selectedBatch.value =
          updatedBatch;
      }
    }

    await loadBatches();

    notify(
      data.message ||
      "Batch semester advanced successfully."
    );
  } catch (error: any) {
    notify(
      error?.response?.data?.message ||
      "Failed to advance semester.",
      "error"
    );
  }
}

/* 
   GRADUATE
 */

async function graduateBatch(
  batch: Batch
) {
  if (
    batch.current_semester !==
    "8th"
  ) {
    notify(
      "A batch can only be graduated after reaching 8th semester.",
      "error"
    );

    return;
  }

  try {
    const { data } = await api.post(
      `/admin/batches/${batch.id}/graduate`
    );

    const updatedBatch:
      | Batch
      | null =
      data.batch || null;

    if (updatedBatch) {
      const index =
        batches.value.findIndex(
          (item) =>
            item.id ===
            updatedBatch.id
        );

      if (index !== -1) {
        batches.value[index] =
          updatedBatch;
      }

      if (
        selectedBatch.value?.id ===
        updatedBatch.id
      ) {
        selectedBatch.value =
          updatedBatch;
      }
    }

    await loadBatches();

    notify(
      data.message ||
      "Batch graduated successfully."
    );
  } catch (error: any) {
    notify(
      error?.response?.data?.message ||
      "Failed to graduate batch.",
      "error"
    );
  }
}

/* 
   RESTORE GRADUATED BATCH
 */

async function restoreBatch(
  batch: Batch
) {
  /*
   * Restore is only allowed for
   * Graduated batches.
   */
  if (batch.status !== "Graduated") {
    return;
  }

  try {
    const { data } = await api.post(
      `/admin/batches/${batch.id}/restore`
    );

    const updatedBatch:
      | Batch
      | null =
      data.batch || null;

    if (updatedBatch) {
      const index =
        batches.value.findIndex(
          (item) =>
            item.id ===
            updatedBatch.id
        );

      if (index !== -1) {
        batches.value[index] =
          updatedBatch;
      }

      if (
        selectedBatch.value?.id ===
        updatedBatch.id
      ) {
        selectedBatch.value =
          updatedBatch;
      }
    }

    /*
      Refresh batch list and department
      student/batch counts.
     */
    await loadBatches();
    await loadDepartments();

    /*
      Keep selected department information
      up to date after refresh.
     */
    if (selectedDepartment.value) {
      const updatedDepartment =
        departments.value.find(
          (department) =>
            department.id ===
            selectedDepartment.value?.id
        );

      if (updatedDepartment) {
        selectedDepartment.value =
          updatedDepartment;
      }
    }

    notify(
      data.message ||
      "Batch restored successfully."
    );
  } catch (error: any) {
    notify(
      error?.response?.data?.message ||
      "Failed to restore batch.",
      "error"
    );
  }
}

/* 
   SECTION
 */

function openAddSection() {
  resetForms();
  editingId.value = null;
  modal.value = "section";
}

function openEditSection(
  section: Section
) {
  editingId.value = section.id;

  sectionForm.value = {
    section_name:
      section.section_name,

    capacity: String(
      section.capacity
    ),

    status: section.status,

    description:
      section.description || "",
  };

  modal.value = "section";
}

async function saveSection() {
  if (!selectedBatch.value) {
    return;
  }

  saving.value = true;

  try {
    const isEditing =
      editingId.value !== null;

    /*
     * IMPORTANT:
      No course_id
      No teacher_name
      No room
     
      Only these fields are sent from
      the Section form.
     */
    const payload = {
      batch_id:
        selectedBatch.value.id,

      section_name:
        sectionForm.value.section_name.trim(),

      capacity: Number(
        sectionForm.value.capacity
      ),

      status:
        sectionForm.value.status,

      description:
        sectionForm.value.description.trim() ||
        null,
    };

    if (isEditing) {
      await api.put(
        `/admin/sections/${editingId.value}`,
        payload
      );
    } else {
      await api.post(
        "/admin/sections",
        payload
      );
    }

    closeModal();

    await loadSections();
    await loadBatches();

    notify(
      isEditing
        ? "Section updated successfully."
        : "Section created successfully."
    );
  } catch (error: any) {
    notify(
      error?.response?.data?.message ||
      "Failed to save section.",
      "error"
    );
  } finally {
    saving.value = false;
  }
}

/* 
   DELETE
 */

function askDelete(
  type:
    | "department"
    | "batch"
    | "section",
  id: number
) {
  deleteType.value = type;
  deleteId.value = id;
}

function cancelDelete() {
  deleteType.value = null;
  deleteId.value = null;
}

async function confirmDelete() {
  if (
    !deleteType.value ||
    !deleteId.value
  ) {
    return;
  }

  const type = deleteType.value;
  const id = deleteId.value;

  try {
    const endpoint =
      type === "department"
        ? "departments"
        : type === "batch"
          ? "batches"
          : "sections";

    await api.delete(
      `/admin/${endpoint}/${id}`
    );

    cancelDelete();

    if (type === "department") {
      backToDepartments();
      await loadDepartments();
    } else if (type === "batch") {
      await loadBatches();
      await loadDepartments();
    } else {
      await loadSections();
      await loadBatches();
    }

    notify(
      `${type.charAt(0).toUpperCase() +
      type.slice(1)
      } deleted successfully.`
    );
  } catch (error: any) {
    notify(
      error?.response?.data?.message ||
      "This item cannot be deleted.",
      "error"
    );

    cancelDelete();
  }
}

/* 
   MOUNT
 */

onMounted(() => {
  loadDepartments();
});
</script>

<template>
  <AdminLayout title="Department Management" subtitle="Manage departments, batches and sections.">
    <div class="department-page">

      <!--  FEEDBACK  -->

      <div v-if="feedback" class="feedback" :class="feedback.type">
        <span>
          {{ feedback.message }}
        </span>

        <button type="button" @click="feedback = null">
          <X :size="16" />
        </button>
      </div>

      <!-- DEPARTMENT LIST -->

      <template v-if="!selectedDepartment">
        <div class="page-header">
          <div>
            <h2>Departments</h2>

            <p>
              Manage academic departments and their batches.
            </p>
          </div>

          <button class="btn-primary" @click="openAddDepartment">
            <Plus :size="18" />
            Add Department
          </button>
        </div>

        <div class="stats-grid">

          <div class="stat-card">
            <div class="stat-icon">
              <Building2 :size="21" />
            </div>

            <div>
              <span>
                Total Departments
              </span>

              <strong>
                {{ departments.length }}
              </strong>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon">
              <Users :size="21" />
            </div>

            <div>
              <span>
                Total Students
              </span>

              <strong>
                {{ totalStudents }}
              </strong>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon">
              <Layers3 :size="21" />
            </div>

            <div>
              <span>
                Active Departments
              </span>

              <strong>
                {{ activeDepartments }}
              </strong>
            </div>
          </div>

        </div>

        <div class="toolbar-panel">
          <div class="search-box">
            <Search :size="18" />

            <input v-model="search" type="text" placeholder="Search department..." />
          </div>
        </div>

        <div class="panel">

          <div class="panel-title">
            <div>
              <h3>
                Department List
              </h3>

              <p>
                View department students and batches.
              </p>
            </div>
          </div>

          <div v-if="loading" class="empty-state">
            Loading departments...
          </div>

          <div v-else-if="
            filteredDepartments.length === 0
          " class="empty-state">
            No departments found.
          </div>

          <div v-else class="department-grid">
            <div v-for="department in filteredDepartments" :key="department.id" class="department-card">

              <div class="department-card-top">

                <div class="department-logo">
                  <Building2 :size="22" />
                </div>

                <span class="status" :class="department.status ===
                    'Active'
                    ? 'active'
                    : 'inactive'
                  ">
                  {{ department.status }}
                </span>

              </div>

              <div class="department-info">

                <span class="department-code">
                  {{ department.code }}
                </span>

                <h3>
                  {{ department.name }}
                </h3>

              </div>

              <div class="department-stats">

                <div>
                  <Users :size="16" />

                  <span>
                    <strong>
                      {{ department.student_count }}
                    </strong>
                    Students
                  </span>
                </div>

                <div>
                  <Layers3 :size="16" />

                  <span>
                    <strong>
                      {{ department.batch_count }}
                    </strong>
                    Batches
                  </span>
                </div>

                <div>
                  <BookOpen :size="16" />

                  <span>
                    <strong>
                      {{ department.course_count }}
                    </strong>
                    Courses
                  </span>
                </div>

              </div>

              <div class="card-actions">

                <button class="btn-manage" @click="
                  selectDepartment(
                    department
                  )
                  ">
                  Manage
                  <ChevronRight :size="17" />
                </button>

                <button class="icon-btn edit" title="Edit" @click="
                  openEditDepartment(
                    department
                  )
                  ">
                  <Edit2 :size="17" />
                </button>

                <button class="icon-btn delete" title="Delete" @click="
                  askDelete(
                    'department',
                    department.id
                  )
                  ">
                  <Trash2 :size="17" />
                </button>

              </div>

            </div>
          </div>

        </div>
      </template>

      <!--  BATCH LIST -->

      <template v-else-if="!selectedBatch">

        <div class="breadcrumb">

          <button @click="backToDepartments">
            <ArrowLeft :size="17" />
            Departments
          </button>

          <ChevronRight :size="16" />

          <strong>
            {{ selectedDepartment.name }}
          </strong>

        </div>

        <div class="page-header">

          <div>

            <div class="heading-code">
              {{ selectedDepartment.code }}
            </div>

            <h2>
              {{ selectedDepartment.name }}
            </h2>

            <p>
              Manage batches under this department.
            </p>

          </div>

          <button class="btn-primary" @click="openAddBatch">
            <Plus :size="18" />
            Add Batch
          </button>

        </div>

        <div class="stats-grid">

          <div class="stat-card">
            <div class="stat-icon">
              <Layers3 :size="21" />
            </div>

            <div>
              <span>
                Total Batches
              </span>

              <strong>
                {{ batches.length }}
              </strong>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon">
              <Users :size="21" />
            </div>

            <div>
              <span>
                Department Students
              </span>

              <strong>
                {{ selectedDepartment.student_count }}
              </strong>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon">
              <BookOpen :size="21" />
            </div>

            <div>
              <span>
                Department Courses
              </span>

              <strong>
                {{ selectedDepartment.course_count }}
              </strong>
            </div>
          </div>

        </div>

        <div class="panel">

          <div class="panel-title">
            <div>
              <h3>
                Batch List
              </h3>

              <p>
                Select a batch to manage its sections.
              </p>
            </div>
          </div>

          <div v-if="sortedBatches.length === 0" class="empty-state">
            No batches found for this department.
          </div>

          <div v-else class="table-wrap">

            <table class="custom-table batch-table">

              <thead>
                <tr>
                  <th>Batch</th>
                  <th>Start Year</th>
                  <th>Current Semester</th>
                  <th>Teacher</th>
                  <th>Students</th>
                  <th>Sections</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>

              <tbody>

                <tr v-for="batch in sortedBatches" :key="batch.id">

                  <td>
                    <strong>
                      Batch
                      {{ batch.batch_number }}
                    </strong>
                  </td>

                  <td>
                    {{ batch.start_year || "—" }}
                  </td>

                  <td>
                    <span class="semester-badge">
                      {{ batch.current_semester }}
                    </span>
                  </td>

                  <td>
                    <div class="advisor-cell">
                      <GraduationCap :size="16" />

                      <span>
                        {{
                          batch.advisor_name ||
                          "Not assigned"
                        }}
                      </span>
                    </div>
                  </td>

                  <td>
                    <div class="count-cell">
                      <Users :size="16" />
                      {{ batch.students_count }}
                    </div>
                  </td>

                  <td>
                    <div class="count-cell">
                      <Layers3 :size="16" />
                      {{ batch.sections_count }}
                    </div>
                  </td>

                  <td>
                    <span class="status" :class="batch.status ===
                        'Active'
                        ? 'active'
                        : batch.status ===
                          'Graduated'
                          ? 'graduated'
                          : 'inactive'
                      ">
                      {{ batch.status }}
                    </span>
                  </td>

                  <td>
                    <div class="table-actions">

                      <button class="btn-manage small" @click="
                        selectBatch(batch)
                        ">
                        Manage
                        <ChevronRight :size="15" />
                      </button>

                      <button v-if="
                        batch.status !==
                        'Graduated' &&
                        batch.current_semester !==
                        '8th'
                      " class="action-text advance" @click="
                          advanceSemester(
                            batch
                          )
                          ">
                        Advance
                      </button>

                      <button v-if="
                        batch.status !==
                        'Graduated' &&
                        batch.current_semester ===
                        '8th'
                      " class="action-text graduate" @click="
                          graduateBatch(
                            batch
                          )
                          ">
                        Graduate
                      </button>

                      <!--  RESTORE  -->

                      <button v-if="
                        batch.status ===
                        'Graduated'
                      " class="action-text restore" @click="
                          restoreBatch(
                            batch
                          )
                          ">
                        Restore
                      </button>

                      <!--  EDIT  -->

                      <button v-if="
                        batch.status !==
                        'Graduated'
                      " class="icon-btn edit" title="Edit" @click="
                          openEditBatch(
                            batch
                          )
                          ">
                        <Edit2 :size="16" />
                      </button>

                      <button class="icon-btn delete" title="Delete" @click="
                        askDelete(
                          'batch',
                          batch.id
                        )
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

      </template>

      <!-- SECTION LIST -->

      <template v-else>

        <div class="breadcrumb">

          <button @click="backToDepartments">
            Departments
          </button>

          <ChevronRight :size="16" />

          <button @click="backToBatches">
            {{ selectedDepartment.name }}
          </button>

          <ChevronRight :size="16" />

          <strong>
            Batch
            {{ selectedBatch.batch_number }}
          </strong>

        </div>

        <div class="page-header">

          <div>

            <div class="heading-code">
              {{ selectedDepartment.code }}
            </div>

            <h2>
              Batch
              {{ selectedBatch.batch_number }}
            </h2>

            <p>
              Semester
              {{ selectedBatch.current_semester }}
              · Manage sections and students.
            </p>

          </div>

          <button class="btn-primary" @click="openAddSection">
            <Plus :size="18" />
            Add Section
          </button>

        </div>

        <div class="stats-grid">

          <div class="stat-card">
            <div class="stat-icon">
              <Users :size="21" />
            </div>

            <div>
              <span>
                Students
              </span>

              <strong>
                {{ selectedBatch.students_count }}
              </strong>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon">
              <Layers3 :size="21" />
            </div>

            <div>
              <span>
                Sections
              </span>

              <strong>
                {{ sections.length }}
              </strong>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon">
              <BookOpen :size="21" />
            </div>

            <div>
              <span>
                Semester
              </span>

              <strong>
                {{ selectedBatch.current_semester }}
              </strong>
            </div>
          </div>

        </div>

        <div class="panel">

          <div class="panel-title">

            <div>
              <h3>
                Section List
              </h3>

              <p>
                Sections available for this batch.
              </p>
            </div>

          </div>

          <div v-if="sections.length === 0" class="empty-state">
            No sections found for this batch.
          </div>

          <div v-else class="table-wrap">

            <table class="custom-table">

              <thead>
                <tr>
                  <th>Section</th>
                  <th>Students</th>
                  <th>Capacity</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>

              <tbody>

                <tr v-for="section in sections" :key="section.id">

                  <td>
                    <strong>
                      Section
                      {{ section.section_name }}
                    </strong>
                  </td>

                  <td>
                    <div class="count-cell">
                      <Users :size="16" />
                      {{ section.enrolled }}
                    </div>
                  </td>

                  <td>
                    {{ section.capacity }}
                  </td>

                  <td>
                    <span class="status" :class="section.status ===
                        'Active'
                        ? 'active'
                        : 'inactive'
                      ">
                      {{ section.status }}
                    </span>
                  </td>

                  <td>

                    <div class="table-actions">

                      <button class="icon-btn edit" title="Edit" @click="
                        openEditSection(
                          section
                        )
                        ">
                        <Edit2 :size="16" />
                      </button>

                      <button class="icon-btn delete" title="Delete" @click="
                        askDelete(
                          'section',
                          section.id
                        )
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

      </template>

      <!-- ADD / EDIT MODAL -->

      <div v-if="modal" class="modal-overlay" @click.self="closeModal">

        <div class="modal">

          <div class="modal-header">

            <div>

              <h3>
                {{ editingId ? "Edit" : "Add" }}

                {{
                  modal === "department"
                    ? "Department"
                    : modal === "batch"
                      ? "Batch"
                      : "Section"
                }}
              </h3>

              <p>
                {{
                  modal === "department"
                    ? "Enter department information."
                    : modal === "batch"
                      ? "Enter batch and advisor information."
                      : "Enter section information."
                }}
              </p>

            </div>

            <button class="close-btn" @click="closeModal">
              <X :size="19" />
            </button>

          </div>

          <!--  DEPARTMENT FORM  -->

          <form v-if="
            modal === 'department'
          " @submit.prevent="saveDepartment">

            <div class="form-grid">

              <div class="form-group">

                <label>
                  Department Code
                </label>

                <input v-model="departmentForm.code
                  " type="text" maxlength="20" required placeholder="e.g. CSE" />

              </div>

              <div class="form-group">

                <label>
                  Status
                </label>

                <select v-model="departmentForm.status
                  ">
                  <option value="Active">
                    Active
                  </option>

                  <option value="Inactive">
                    Inactive
                  </option>
                </select>

              </div>

              <div class="form-group full">

                <label>
                  Department Name
                </label>

                <!-- NO SMALL maxlength HERE -->
                <input v-model="departmentForm.name
                  " type="text" required placeholder="e.g. Mechanical Engineering" />

              </div>

            </div>

            <div class="modal-actions">

              <button type="button" class="btn-secondary" @click="closeModal">
                Cancel
              </button>

              <button type="submit" class="btn-primary" :disabled="saving">
                {{
                  saving
                    ? "Saving..."
                    : editingId
                      ? "Update"
                      : "Create"
                }}
              </button>

            </div>

          </form>

          <!--  BATCH FORM  -->

          <form v-if="modal === 'batch'" @submit.prevent="saveBatch">

            <div class="form-grid">

              <div class="form-group">

                <label>
                  Department
                </label>

                <input :value="selectedDepartment?.code ||
                  ''
                  " disabled />

              </div>

              <div class="form-group">

                <label>
                  Batch Number
                </label>

                <input v-model="batchForm.batch_number
                  " type="number" min="1" required placeholder="62" />

              </div>

              <div class="form-group">

                <label>
                  Start Year
                </label>

                <input v-model="batchForm.start_year
                  " type="text" maxlength="4" placeholder="2026" />

              </div>

              <div class="form-group">

                <label>
                  Current Semester
                </label>

                <select v-model="batchForm.current_semester
                  ">

                  <option v-for="semester in semesters" :key="semester" :value="semester">
                    {{ semester }}
                    Semester
                  </option>

                </select>

              </div>

              <div class="form-group full">

                <label>
                  Teacher / Batch Advisor
                </label>

                <input v-model="batchForm.advisor_name
                  " type="text" placeholder="e.g. Dr. Md. Rahman" />

                <small>
                  This teacher will maintain and
                  supervise this batch.
                </small>

              </div>

              <div class="form-group">

                <label>
                  Status
                </label>

                <select v-model="batchForm.status
                  ">

                  <option value="Active">
                    Active
                  </option>

                  <option value="Inactive">
                    Inactive
                  </option>

                </select>

              </div>

            </div>

            <div class="modal-actions">

              <button type="button" class="btn-secondary" @click="closeModal">
                Cancel
              </button>

              <button type="submit" class="btn-primary" :disabled="saving">
                {{
                  saving
                    ? "Saving..."
                    : editingId
                      ? "Update"
                      : "Create"
                }}
              </button>

            </div>

          </form>

          <!--  SECTION FORM  -->

          <form v-if="modal === 'section'" @submit.prevent="saveSection">

            <div class="form-grid">

              <!-- ONLY SECTION NAME -->
              <div class="form-group full">

                <label>
                  Section Name
                </label>

                <input v-model="sectionForm.section_name
                  " type="text" maxlength="20" required placeholder="e.g. A" />

              </div>

              <!-- ONLY CAPACITY -->
              <div class="form-group">

                <label>
                  Capacity
                </label>

                <input v-model="sectionForm.capacity
                  " type="number" min="1" max="1000" required />

              </div>

              <!-- ONLY STATUS -->
              <div class="form-group">

                <label>
                  Status
                </label>

                <select v-model="sectionForm.status
                  ">

                  <option value="Active">
                    Active
                  </option>

                  <option value="Inactive">
                    Inactive
                  </option>

                </select>

              </div>

              <!-- ONLY DESCRIPTION -->
              <div class="form-group full">

                <label>
                  Description
                </label>

                <textarea v-model="sectionForm.description
                  " rows="4" placeholder="Optional description..."></textarea>

              </div>

            </div>

            <div class="modal-actions">

              <button type="button" class="btn-secondary" @click="closeModal">
                Cancel
              </button>

              <button type="submit" class="btn-primary" :disabled="saving">
                {{
                  saving
                    ? "Saving..."
                    : editingId
                      ? "Update"
                      : "Create"
                }}
              </button>

            </div>

          </form>

        </div>

      </div>

      <!-- DELETE MODAL -->

      <div v-if="deleteType" class="modal-overlay" @click.self="cancelDelete">

        <div class="delete-modal">

          <div class="delete-icon">
            <Trash2 :size="23" />
          </div>

          <h3>
            Delete this
            {{ deleteType }}?
          </h3>

          <p>
            This action cannot be undone.
            If this item has related records,
            deletion will be blocked.
          </p>

          <div class="modal-actions">

            <button class="btn-secondary" @click="cancelDelete">
              Cancel
            </button>

            <button class="btn-danger" @click="confirmDelete">
              Delete
            </button>

          </div>

        </div>

      </div>

    </div>
  </AdminLayout>
</template>

<style scoped>
.department-page {
  width: 100%;
}

/*  FEEDBACK  */

.feedback {
  position: fixed;
  top: 78px;
  right: 25px;
  z-index: 2000;
  min-width: 280px;
  max-width: 420px;
  padding: 12px 14px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  font-size: 13px;
  font-weight: 600;
  box-shadow: 0 8px 25px rgba(0, 0, 0, .12);
}

.feedback.success {
  background: #e8f7ef;
  color: #087443;
  border: 1px solid #b9e7ce;
}

.feedback.error {
  background: #fff0ef;
  color: #b42318;
  border: 1px solid #f5c2c0;
}

.feedback button {
  border: 0;
  background: transparent;
  cursor: pointer;
  color: inherit;
}

/*  HEADER  */

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 22px;
}

.page-header h2 {
  margin: 3px 0 5px;
  font-size: 25px;
  color: #172033;
}

.page-header p,
.panel-title p {
  margin: 0;
  color: #7a8495;
  font-size: 14px;
}

.heading-code {
  color: #3154d8;
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .5px;
}

/*  BUTTONS  */

.btn-primary,
.btn-secondary,
.btn-danger,
.btn-manage,
.icon-btn,
.close-btn,
.breadcrumb button,
.action-text {
  border: 0;
  cursor: pointer;
  font-family: inherit;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: #172b4d;
  color: #fff;
  padding: 11px 17px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
}

.btn-primary:hover {
  background: #10213d;
}

.btn-primary:disabled {
  opacity: .65;
  cursor: not-allowed;
}

.btn-secondary {
  padding: 10px 17px;
  background: #eef1f5;
  color: #475467;
  border-radius: 8px;
  font-weight: 600;
}

.btn-danger {
  padding: 10px 17px;
  background: #d92d20;
  color: #fff;
  border-radius: 8px;
  font-weight: 600;
}

/*  STATS  */

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px;
  background: #fff;
  border: 1px solid #e7eaf0;
  border-radius: 12px;
}

.stat-icon {
  width: 42px;
  height: 42px;
  display: grid;
  place-items: center;
  border-radius: 9px;
  background: #edf2ff;
  color: #3154d8;
}

.stat-card span {
  display: block;
  color: #7a8495;
  font-size: 12px;
  margin-bottom: 4px;
}

.stat-card strong {
  font-size: 21px;
  color: #172033;
}

/*  PANELS  */

.toolbar-panel,
.panel {
  background: #fff;
  border: 1px solid #e7eaf0;
  border-radius: 12px;
  margin-bottom: 20px;
}

.toolbar-panel {
  padding: 13px;
}

.search-box {
  max-width: 350px;
  height: 42px;
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 0 13px;
  border: 1px solid #dce1e8;
  border-radius: 8px;
  color: #98a2b3;
}

.search-box input {
  width: 100%;
  border: 0;
  outline: 0;
  font-size: 14px;
}

.panel {
  overflow: hidden;
}

.panel-title {
  padding: 18px 20px;
  border-bottom: 1px solid #edf0f4;
}

.panel-title h3 {
  margin: 0 0 5px;
  color: #172033;
  font-size: 16px;
}

/*  DEPARTMENT CARDS  */

.department-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  padding: 20px;
}

.department-card {
  border: 1px solid #e5e9f0;
  border-radius: 11px;
  padding: 18px;
  transition: .2s;
}

.department-card:hover {
  border-color: #b9c5df;
  box-shadow: 0 5px 18px rgba(16, 33, 61, .06);
}

.department-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.department-logo {
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  border-radius: 9px;
  background: #edf2ff;
  color: #3154d8;
}

.department-info {
  margin: 17px 0;
}

.department-code {
  color: #3154d8;
  font-size: 12px;
  font-weight: 700;
}

.department-info h3 {
  margin: 5px 0 0;
  color: #172033;
  font-size: 18px;
  line-height: 1.35;
}

.department-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  padding: 12px 0;
  border-top: 1px solid #edf0f4;
  border-bottom: 1px solid #edf0f4;
}

.department-stats div {
  display: flex;
  align-items: center;
  gap: 7px;
  color: #7a8495;
  font-size: 11px;
}

.department-stats svg {
  color: #667085;
}

.department-stats strong {
  color: #344054;
  display: block;
  font-size: 13px;
}

.card-actions,
.table-actions {
  display: flex;
  align-items: center;
  gap: 7px;
}

.card-actions {
  margin-top: 14px;
}

.btn-manage {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 8px 12px;
  background: #172b4d;
  color: #fff;
  border-radius: 7px;
  font-size: 12px;
  font-weight: 600;
}

.btn-manage.small {
  padding: 7px 10px;
}

.icon-btn {
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  border-radius: 7px;
}

.icon-btn.edit {
  background: #edf2ff;
  color: #3154d8;
}

.icon-btn.delete {
  background: #fff0ef;
  color: #d92d20;
}

/*  STATUS  */

.status {
  display: inline-flex;
  padding: 4px 9px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
}

.status.active {
  color: #087443;
  background: #e8f7ef;
}

.status.inactive {
  color: #667085;
  background: #f0f2f5;
}

.status.graduated {
  color: #3154d8;
  background: #edf2ff;
}

/*  BREADCRUMB  */

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 7px;
  margin-bottom: 18px;
  color: #667085;
  font-size: 13px;
}

.breadcrumb button {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: none;
  color: #3154d8;
  font-weight: 600;
  padding: 0;
}

.breadcrumb strong {
  color: #344054;
}

/*  TABLE  */

.table-wrap {
  width: 100%;
  overflow-x: auto;
}

.custom-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 750px;
}

.batch-table {
  min-width: 1050px;
}

.custom-table th {
  background: #f8fafc;
  color: #667085;
  font-size: 11px;
  text-align: left;
  text-transform: uppercase;
  letter-spacing: .3px;
  padding: 13px 17px;
  border-bottom: 1px solid #e7eaf0;
}

.custom-table td {
  padding: 14px 17px;
  border-bottom: 1px solid #edf0f4;
  color: #475467;
  font-size: 13px;
}

.custom-table tbody tr:last-child td {
  border-bottom: 0;
}

.custom-table td strong {
  color: #172033;
}

.semester-badge {
  display: inline-block;
  padding: 5px 9px;
  background: #f0f4ff;
  color: #3154d8;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 700;
}

.count-cell,
.advisor-cell {
  display: flex;
  align-items: center;
  gap: 6px;
}

.count-cell svg,
.advisor-cell svg {
  color: #667085;
}

.advisor-cell span {
  white-space: nowrap;
}

/*  ACTION TEXT  */

.action-text {
  padding: 7px 9px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
}

.action-text.advance {
  background: #eef5ff;
  color: #3154d8;
}

.action-text.graduate {
  background: #e8f7ef;
  color: #087443;
}

/*  RESTORE  */

.action-text.restore {
  background: #fff4e5;
  color: #b54708;
}

/*  EMPTY  */

.empty-state {
  padding: 55px 20px;
  text-align: center;
  color: #98a2b3;
  font-size: 14px;
}

/*  MODAL  */

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(15, 23, 42, .48);
}

.modal,
.delete-modal {
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  background: #fff;
  border-radius: 13px;
  box-shadow: 0 20px 50px rgba(0, 0, 0, .18);
}

.delete-modal {
  max-width: 420px;
  padding: 28px;
  text-align: center;
}

.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 20px;
  border-bottom: 1px solid #edf0f4;
}

.modal-header h3 {
  margin: 0 0 5px;
  color: #172033;
}

.modal-header p {
  margin: 0;
  color: #7a8495;
  font-size: 13px;
}

.close-btn {
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  background: #f3f5f8;
  color: #667085;
  border-radius: 7px;
}

form {
  padding: 20px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.form-group.full {
  grid-column: 1 / -1;
}

.form-group label {
  color: #344054;
  font-size: 13px;
  font-weight: 600;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #dce1e8;
  border-radius: 8px;
  padding: 10px 11px;
  outline: none;
  font: inherit;
  font-size: 13px;
  color: #344054;
  background: #fff;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  border-color: #3154d8;
}

.form-group input:disabled {
  background: #f5f7fa;
  color: #667085;
}

.form-group small {
  color: #98a2b3;
  font-size: 11px;
  line-height: 1.4;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 9px;
  margin-top: 22px;
}

.delete-icon {
  width: 48px;
  height: 48px;
  margin: 0 auto 15px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #fff0ef;
  color: #d92d20;
}

.delete-modal h3 {
  margin: 0 0 8px;
  color: #172033;
}

.delete-modal p {
  margin: 0;
  color: #667085;
  font-size: 13px;
  line-height: 1.6;
}

/*  RESPONSIVE  */

@media (max-width: 900px) {
  .stats-grid {
    grid-template-columns: 1fr 1fr;
  }

  .department-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 650px) {
  .page-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-group.full {
    grid-column: auto;
  }

  .department-stats {
    grid-template-columns: 1fr;
  }

  .modal-overlay {
    padding: 10px;
  }

  .feedback {
    left: 15px;
    right: 15px;
    top: 70px;
    min-width: 0;
  }
}
</style>