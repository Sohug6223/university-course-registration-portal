<script setup lang="ts">
import {
  computed,
  onMounted,
  reactive,
  ref,
  watch
} from 'vue'

import {
  AlertCircle,
  BookOpen,
  Check,
  FlaskConical,
  Pencil,
  Plus,
  Search,
  Trash2,
  X
} from 'lucide-vue-next'

import AdminLayout from '@/components/admin/AdminLayout.vue'
import api from '@/services/api'

interface Department {
  id: number
  code: string
  name: string
  status: string
}

interface Course {
  id: number
  course_code: string
  course_name: string
  department: string
  department_id?: number
  semester: string
  credit: number
  course_type: string
  description?: string
  status: string
}

const semesters = [
  '1st',
  '2nd',
  '3rd',
  '4th',
  '5th',
  '6th',
  '7th',
  '8th'
]

const courses = ref<Course[]>([])
const departments = ref<Department[]>([])

const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)

const error = ref('')
const formError = ref('')
const deleteError = ref('')

const search = ref('')
const departmentFilter = ref('')
const semesterFilter = ref('')
const typeFilter = ref('')
const statusFilter = ref('')

const showModal = ref(false)
const deleteModal = ref(false)
const editing = ref(false)

const selectedCourse = ref<Course | null>(null)

/*  PAGINATION  */

const currentPage = ref(1)
const perPage = 10

const form = reactive({
  course_code: '',
  course_name: '',
  department: '',
  semester: '',
  credit: 3,
  course_type: 'Theory',
  description: '',
  status: 'Active'
})

const activeDepartments = computed(() =>
  departments.value.filter(
    d => d.status === 'Active'
  )
)

const filteredCourses = computed(() => {
  const keyword = search.value
    .toLowerCase()
    .trim()

  return courses.value
    .filter(course => {
      const matchesSearch =
        !keyword ||
        course.course_code
          .toLowerCase()
          .includes(keyword) ||
        course.course_name
          .toLowerCase()
          .includes(keyword)

      const matchesDepartment =
        !departmentFilter.value ||
        course.department === departmentFilter.value

      const matchesSemester =
        !semesterFilter.value ||
        course.semester === semesterFilter.value

      const matchesType =
        !typeFilter.value ||
        course.course_type === typeFilter.value

      const matchesStatus =
        !statusFilter.value ||
        course.status === statusFilter.value

      return (
        matchesSearch &&
        matchesDepartment &&
        matchesSemester &&
        matchesType &&
        matchesStatus
      )
    })
    .sort((a, b) => {
      const semesterOrder =
        semesters.indexOf(a.semester) -
        semesters.indexOf(b.semester)

      return (
        semesterOrder ||
        a.course_code.localeCompare(
          b.course_code
        )
      )
    })
})

const totalPages = computed(() =>
  Math.ceil(
    filteredCourses.value.length / perPage
  )
)

const paginatedCourses = computed(() => {
  const start =
    (currentPage.value - 1) * perPage

  const end = start + perPage

  return filteredCourses.value.slice(
    start,
    end
  )
})

const pageNumbers = computed(() => {
  const total = totalPages.value

  if (total <= 5) {
    return Array.from(
      { length: total },
      (_, index) => index + 1
    )
  }

  const pages: number[] = []

  if (currentPage.value <= 3) {
    pages.push(1, 2, 3, 4, 5)
  } else if (currentPage.value >= total - 2) {
    pages.push(
      total - 4,
      total - 3,
      total - 2,
      total - 1,
      total
    )
  } else {
    pages.push(
      currentPage.value - 2,
      currentPage.value - 1,
      currentPage.value,
      currentPage.value + 1,
      currentPage.value + 2
    )
  }

  return pages
})

const hasFilters = computed(() =>
  !!(
    search.value ||
    departmentFilter.value ||
    semesterFilter.value ||
    typeFilter.value ||
    statusFilter.value
  )
)

/*  PAGINATION METHODS  */

function goToPage(page: number) {
  if (
    page < 1 ||
    page > totalPages.value
  ) {
    return
  }

  currentPage.value = page
}

function previousPage() {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

function nextPage() {
  if (
    currentPage.value < totalPages.value
  ) {
    currentPage.value++
  }
}

/*  FORM  */

function resetForm() {
  Object.assign(form, {
    course_code: '',
    course_name: '',
    department: '',
    semester: '',
    credit: 3,
    course_type: 'Theory',
    description: '',
    status: 'Active'
  })
}

function openCreate() {
  editing.value = false
  formError.value = ''
  selectedCourse.value = null

  resetForm()

  showModal.value = true
}

function openEdit(course: Course) {
  editing.value = true
  formError.value = ''

  Object.assign(form, {
    course_code: course.course_code,
    course_name: course.course_name,
    department: course.department,
    semester: course.semester,
    credit: Number(course.credit),
    course_type: course.course_type,
    description: course.description || '',
    status: course.status
  })

  selectedCourse.value = course
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  formError.value = ''
}

function clearFilters() {
  search.value = ''
  departmentFilter.value = ''
  semesterFilter.value = ''
  typeFilter.value = ''
  statusFilter.value = ''
}

function confirmDelete(course: Course) {
  selectedCourse.value = course
  deleteError.value = ''
  deleteModal.value = true
}

function closeDeleteModal() {
  if (deleting.value) {
    return
  }

  deleteModal.value = false
  deleteError.value = ''
  selectedCourse.value = null
}

function formatCredit(credit: number) {
  return Number(credit).toFixed(2)
}

function getErrorMessage(err: any) {
  if (err.response?.data?.errors) {
    return Object.values(
      err.response.data.errors
    )
      .flat()
      .join(' ')
  }

  return (
    err.response?.data?.message ||
    'Something went wrong. Please try again.'
  )
}

/*  API  */

async function fetchDepartments() {
  try {
    const response = await api.get(
      '/admin/departments'
    )

    departments.value =
      response.data.departments || []
  } catch (err) {
    console.error(
      'Department loading failed:',
      err
    )
  }
}

async function fetchCourses() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get(
      '/admin/courses'
    )

    courses.value =
      response.data.courses || []

    /*
      Keep current page valid after
      loading / deleting courses.
     */
    if (
      totalPages.value > 0 &&
      currentPage.value > totalPages.value
    ) {
      currentPage.value = totalPages.value
    }

    if (totalPages.value === 0) {
      currentPage.value = 1
    }
  } catch (err: any) {
    error.value = getErrorMessage(err)
  } finally {
    loading.value = false
  }
}

async function saveCourse() {
  saving.value = true
  formError.value = ''

  try {
    if (
      editing.value &&
      selectedCourse.value
    ) {
      await api.put(
        `/admin/courses/${selectedCourse.value.id}`,
        form
      )
    } else {
      await api.post(
        '/admin/courses',
        form
      )
    }

    closeModal()

    await fetchCourses()
  } catch (err: any) {
    formError.value =
      getErrorMessage(err)
  } finally {
    saving.value = false
  }
}

async function deleteCourse() {
  if (!selectedCourse.value) {
    return
  }

  deleting.value = true
  deleteError.value = ''

  try {
    await api.delete(
      `/admin/courses/${selectedCourse.value.id}`
    )

    deleteModal.value = false
    selectedCourse.value = null
    deleteError.value = ''

    await fetchCourses()
  } catch (err: any) {
    deleteError.value =
      getErrorMessage(err)
  } finally {
    deleting.value = false
  }
}

/*  WATCHERS  */

/*
  When search/filter changes,
  always return to first page.
 */
watch(
  [
    search,
    departmentFilter,
    semesterFilter,
    typeFilter,
    statusFilter
  ],
  () => {
    currentPage.value = 1
  }
)

onMounted(async () => {
  await Promise.all([
    fetchDepartments(),
    fetchCourses()
  ])
})
</script>

<template>
  <AdminLayout
    title="Course Management"
    subtitle="Manage university courses, credits and academic information."
  >
    <div class="courses-page">

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div>
            <span>Total Courses</span>
            <strong>{{ courses.length }}</strong>
          </div>
          <BookOpen />
        </div>

        <div class="stat-card">
          <div>
            <span>Active Courses</span>
            <strong>{{ courses.filter(c => c.status === 'Active').length }}</strong>
          </div>
          <Check />
        </div>

        <div class="stat-card">
          <div>
            <span>Theory</span>
            <strong>{{ courses.filter(c => c.course_type === 'Theory').length }}</strong>
          </div>
          <BookOpen />
        </div>

        <div class="stat-card">
          <div>
            <span>Lab</span>
            <strong>{{ courses.filter(c => c.course_type === 'Lab').length }}</strong>
          </div>
          <FlaskConical />
        </div>
      </div>

      <!-- Toolbar -->
      <div class="toolbar-panel">
        <div class="search-box">
          <Search />
          <input
            v-model="search"
            type="text"
            placeholder="Search course code or name..."
          />
        </div>

        <select v-model="departmentFilter">
          <option value="">All Departments</option>
          <option
            v-for="department in departments"
            :key="department.id"
            :value="department.code"
          >
            {{ department.code }} - {{ department.name }}
          </option>
        </select>

        <select v-model="semesterFilter">
          <option value="">All Semesters</option>
          <option
            v-for="semester in semesters"
            :key="semester"
            :value="semester"
          >
            {{ semester }}
          </option>
        </select>

        <select v-model="typeFilter">
          <option value="">All Types</option>
          <option value="Theory">Theory</option>
          <option value="Lab">Lab</option>
        </select>

        <select v-model="statusFilter">
          <option value="">All Status</option>
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
        </select>

        <button
          class="btn-primary add-course-btn"
          @click="openCreate"
        >
          <Plus :size="18" />
          Add Course
        </button>
      </div>

      <!-- Loading -->
      <div
        v-if="loading"
        class="state-box"
      >
        Loading courses...
      </div>

      <!-- Error -->
      <div
        v-else-if="error"
        class="error-box"
      >
        <AlertCircle :size="18" />
        {{ error }}
      </div>

      <!-- Table -->
      <div
        v-else
        class="panel table-panel"
      >
        <div class="panel-header">
          <div>
            <h3>Courses</h3>
            <p>
              {{ filteredCourses.length }} course(s) found
            </p>
          </div>

          <button
            v-if="hasFilters"
            class="clear-btn"
            @click="clearFilters"
          >
            <X :size="16" />
            Clear Filters
          </button>
        </div>

        <div class="table-wrap">
          <table class="custom-table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Course Name</th>
                <th>Department</th>
                <th>Semester</th>
                <th>Credit</th>
                <th>Type</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="course in paginatedCourses"
                :key="course.id"
              >
                <td>
                  <strong>{{ course.course_code }}</strong>
                </td>

                <td>
                  <div class="course-name">
                    {{ course.course_name }}

                    <small v-if="course.description">
                      {{ course.description }}
                    </small>
                  </div>
                </td>

                <td>
                  <span class="department-badge">
                    {{ course.department }}
                  </span>
                </td>

                <td>{{ course.semester }}</td>

                <td>{{ formatCredit(course.credit) }}</td>

                <td>
                  <span class="type-badge">
                    {{ course.course_type }}
                  </span>
                </td>

                <td>
                  <span
                    class="status-badge"
                    :class="
                      course.status === 'Active'
                        ? 'active'
                        : 'inactive'
                    "
                  >
                    {{ course.status }}
                  </span>
                </td>

                <td>
                  <div class="actions">
                    <button
                      class="action-btn edit"
                      title="Edit"
                      @click="openEdit(course)"
                    >
                      <Pencil :size="16" />
                    </button>

                    <button
                      class="action-btn delete"
                      title="Delete"
                      @click="confirmDelete(course)"
                    >
                      <Trash2 :size="16" />
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!paginatedCourses.length">
                <td
                  colspan="8"
                  class="empty"
                >
                  No courses found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="filteredCourses.length > perPage"
          class="pagination"
        >
          <div class="pagination-info">
            Showing
            {{
              (currentPage - 1) * perPage + 1
            }}
            -
            {{
              Math.min(
                currentPage * perPage,
                filteredCourses.length
              )
            }}
            of
            {{ filteredCourses.length }}
            courses
          </div>

          <div class="pagination-controls">

            <button
              class="pagination-btn"
              :disabled="currentPage === 1"
              @click="previousPage"
            >
              <span>‹</span>
              Previous
            </button>

            <button
              v-for="page in pageNumbers"
              :key="page"
              class="pagination-number"
              :class="{
                active: currentPage === page
              }"
              @click="goToPage(page)"
            >
              {{ page }}
            </button>

            <button
              class="pagination-btn"
              :disabled="
                currentPage === totalPages
              "
              @click="nextPage"
            >
              Next
              <span>›</span>
            </button>

          </div>
        </div>
      </div>

      <!-- Course Modal -->
      <div
        v-if="showModal"
        class="modal-overlay"
        @click.self="closeModal"
      >
        <div class="modal">

          <div class="modal-header">
            <div>
              <h3>
                {{ editing ? 'Edit Course' : 'Add New Course' }}
              </h3>

              <p>Enter course information below.</p>
            </div>

            <button
              class="close-btn"
              @click="closeModal"
            >
              <X />
            </button>
          </div>

          <!-- Form Error -->
          <div
            v-if="formError"
            class="form-error"
          >
            <AlertCircle :size="17" />
            <span>{{ formError }}</span>
          </div>

          <form @submit.prevent="saveCourse">

            <div class="form-grid">

              <!-- Course Code -->
              <div class="form-group">
                <label>Course Code *</label>

                <input
                  v-model="form.course_code"
                  type="text"
                  placeholder="e.g. CSE101"
                  required
                />
              </div>

              <!-- Course Name -->
              <div class="form-group">
                <label>Course Name *</label>

                <input
                  v-model="form.course_name"
                  type="text"
                  placeholder="Course name"
                  required
                />
              </div>

              <!-- Department -->
              <div class="form-group">
                <label>Department *</label>

                <select
                  v-model="form.department"
                  required
                >
                  <option value="">
                    Select Department
                  </option>

                  <option
                    v-for="department in activeDepartments"
                    :key="department.id"
                    :value="department.code"
                  >
                    {{ department.code }} -
                    {{ department.name }}
                  </option>
                </select>
              </div>

              <!-- Semester -->
              <div class="form-group">
                <label>Semester *</label>

                <select
                  v-model="form.semester"
                  required
                >
                  <option value="">
                    Select Semester
                  </option>

                  <option
                    v-for="semester in semesters"
                    :key="semester"
                    :value="semester"
                  >
                    {{ semester }}
                  </option>
                </select>
              </div>

              <!-- Credit -->
              <div class="form-group">
                <label>Credit *</label>

                <input
                  v-model.number="form.credit"
                  type="number"
                  min="0.25"
                  step="0.25"
                  placeholder="3.00"
                  required
                />
              </div>

              <!-- Course Type -->
              <div class="form-group">
                <label>Course Type *</label>

                <select
                  v-model="form.course_type"
                  required
                >
                  <option value="">
                    Select Type
                  </option>

                  <option value="Theory">
                    Theory
                  </option>

                  <option value="Lab">
                    Lab
                  </option>
                </select>
              </div>

              <!-- Status -->
              <div class="form-group">
                <label>Status *</label>

                <select
                  v-model="form.status"
                  required
                >
                  <option value="Active">
                    Active
                  </option>

                  <option value="Inactive">
                    Inactive
                  </option>
                </select>
              </div>

              <!-- Description -->
              <div class="form-group full">
                <label>Description</label>

                <textarea
                  v-model="form.description"
                  rows="3"
                  placeholder="Course description..."
                ></textarea>
              </div>

            </div>

            <div class="modal-actions">
              <button
                type="button"
                class="btn-secondary"
                @click="closeModal"
              >
                Cancel
              </button>

              <button
                type="submit"
                class="btn-primary"
                :disabled="saving"
              >
                {{
                  saving
                    ? 'Saving...'
                    : editing
                      ? 'Update Course'
                      : 'Create Course'
                }}
              </button>
            </div>

          </form>
        </div>
      </div>

      <!-- Delete Modal -->
      <div
        v-if="deleteModal"
        class="modal-overlay"
        @click.self="closeDeleteModal"
      >
        <div class="delete-modal">

          <div class="delete-icon">
            <Trash2 />
          </div>

          <h3>Delete Course?</h3>

          <p>
            Are you sure you want to delete
            <strong>
              {{ selectedCourse?.course_code }}
            </strong>?
          </p>

          <!-- Delete Error -->
          <div
            v-if="deleteError"
            class="form-error delete-error"
          >
            <AlertCircle :size="17" />
            <span>{{ deleteError }}</span>
          </div>

          <div class="modal-actions">
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
              @click="deleteCourse"
            >
              {{
                deleting
                  ? 'Deleting...'
                  : 'Delete'
              }}
            </button>
          </div>

        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<style scoped>
/*
   PAGE
 */

.courses-page {
  width: 100%;
  min-width: 0;
}

/*
   STATS
 */

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 18px;
  margin-bottom: 20px;
}

.stat-card {
  min-width: 0;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.stat-card span {
  display: block;
  color: #64748b;
  font-size: 13px;
  margin-bottom: 6px;
}

.stat-card strong {
  font-size: 25px;
  color: #0f172a;
}

.stat-card svg {
  color: #1e3a8a;
  flex-shrink: 0;
}

/*
   TOOLBAR
 */

.toolbar-panel {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 16px;

  display: grid;
  grid-template-columns:
    minmax(220px, 1fr)
    minmax(150px, auto)
    minmax(140px, auto)
    minmax(130px, auto)
    minmax(130px, auto)
    auto;

  gap: 10px;

  margin-bottom: 20px;

  width: 100%;
  box-sizing: border-box;
}

.search-box {
  position: relative;
  min-width: 0;
  width: 100%;
}

.search-box svg {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}

.search-box input {
  width: 100%;
  padding-left: 38px;
}

.toolbar-panel select {
  width: 100%;
  min-width: 0;
}

/*
   FORM ELEMENTS
 */

input,
select,
textarea {
  width: 100%;
  max-width: 100%;
  min-width: 0;

  box-sizing: border-box;

  border: 1px solid #dbe1e8;
  border-radius: 8px;

  padding: 10px 12px;

  font-size: 14px;
  outline: none;
  background: #fff;
}

input:focus,
select:focus,
textarea:focus {
  border-color: #1e3a8a;
}

/*
   BUTTONS
 */

.btn-primary {
  border: 0;
  background: #1e3a8a;
  color: #fff;

  padding: 10px 16px;
  border-radius: 8px;

  cursor: pointer;

  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;

  font-weight: 600;

  white-space: nowrap;
}

.btn-primary:hover {
  background: #172554;
}

.btn-primary:disabled,
.btn-danger:disabled,
.btn-secondary:disabled {
  opacity: .6;
  cursor: not-allowed;
}

/*
   PANEL
 */

.panel {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;

  width: 100%;
  min-width: 0;
}

.panel-header {
  padding: 18px 20px;

  display: flex;
  justify-content: space-between;
  align-items: center;

  gap: 15px;

  border-bottom: 1px solid #eef0f3;
}

.panel-header h3 {
  margin: 0;
  color: #0f172a;
}

.panel-header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.clear-btn {
  border: 0;
  background: transparent;
  color: #1e3a8a;

  display: flex;
  align-items: center;
  gap: 5px;

  cursor: pointer;
  white-space: nowrap;
}

/*
   TABLE
 */

.table-wrap {
  width: 100%;
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
  border-bottom: 1px solid #eef0f3;
  white-space: nowrap;
}

.custom-table th {
  font-size: 12px;
  text-transform: uppercase;
  color: #64748b;
  background: #f8fafc;
}

.custom-table td {
  color: #334155;
  font-size: 14px;
}

.course-name {
  min-width: 160px;
}

.course-name small {
  display: block;
  color: #94a3b8;
  margin-top: 3px;

  max-width: 240px;

  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/*
   BADGES
 */

.department-badge,
.type-badge,
.status-badge {
  padding: 5px 9px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
}

.department-badge {
  background: #eff6ff;
  color: #1d4ed8;
}

.type-badge {
  background: #f1f5f9;
  color: #475569;
}

.status-badge.active {
  background: #dcfce7;
  color: #15803d;
}

.status-badge.inactive {
  background: #fee2e2;
  color: #b91c1c;
}

/*
   ACTIONS
 */

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
  background: #eff6ff;
  color: #1d4ed8;
}

.action-btn.delete {
  background: #fef2f2;
  color: #dc2626;
}

/*
   STATES
 */

.empty,
.state-box {
  text-align: center;
  padding: 40px !important;
  color: #64748b;
}

.error-box,
.form-error {
  padding: 12px 15px;

  background: #fef2f2;
  border: 1px solid #fecaca;

  color: #b91c1c;

  border-radius: 8px;

  display: flex;
  align-items: flex-start;
  gap: 8px;

  margin-bottom: 15px;
}

.form-error {
  line-height: 1.5;
}

.form-error svg {
  flex-shrink: 0;
  margin-top: 2px;
}

.delete-error {
  text-align: left;
  margin-top: 15px;
  margin-bottom: 0;
}

/*
   PAGINATION
 */

.pagination {
  padding: 16px 20px;

  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 15px;

  border-top: 1px solid #eef0f3;
}

.pagination-info {
  color: #64748b;
  font-size: 13px;
  white-space: nowrap;
}

.pagination-controls {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 5px;
}

.pagination-btn,
.pagination-number {
  height: 34px;

  border: 1px solid #dbe1e8;
  background: #fff;
  color: #334155;

  border-radius: 7px;

  cursor: pointer;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  font-size: 13px;
  font-weight: 500;
}

.pagination-btn {
  padding: 0 11px;
  gap: 6px;
}

.pagination-number {
  width: 34px;
}

.pagination-btn:hover:not(:disabled),
.pagination-number:hover {
  border-color: #1e3a8a;
  color: #1e3a8a;
}

.pagination-number.active {
  background: #1e3a8a;
  border-color: #1e3a8a;
  color: #fff;
}

.pagination-btn:disabled {
  opacity: .5;
  cursor: not-allowed;
}

.pagination-btn span {
  font-size: 18px;
  line-height: 1;
}

/*
   MODAL
 */

.modal-overlay {
  position: fixed;
  inset: 0;

  background: rgba(15, 23, 42, .55);

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 20px;

  z-index: 1000;

  box-sizing: border-box;
}

.modal,
.delete-modal {
  width: 100%;
  max-width: 650px;

  background: #fff;

  border-radius: 14px;
  padding: 24px;

  max-height: 90vh;
  overflow-y: auto;

  box-sizing: border-box;
}

.delete-modal {
  max-width: 420px;
  text-align: center;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;

  gap: 15px;

  margin-bottom: 20px;
}

.modal-header h3,
.delete-modal h3 {
  margin: 0;
  color: #0f172a;
}

.modal-header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.close-btn {
  flex-shrink: 0;

  border: 0;
  background: #f1f5f9;

  border-radius: 7px;

  width: 34px;
  height: 34px;

  cursor: pointer;

  display: grid;
  place-items: center;
}

/*
   FORM
 */

.form-grid {
  display: grid;

  grid-template-columns:
    minmax(0, 1fr)
    minmax(0, 1fr);

  gap: 16px;

  width: 100%;
}

.form-group {
  min-width: 0;
  width: 100%;
}

.form-group label {
  display: block;

  margin-bottom: 6px;

  font-size: 13px;
  font-weight: 600;

  color: #334155;
}

.form-group.full {
  grid-column: 1 / -1;
}

textarea {
  resize: vertical;
  display: block;
}

/*
   MODAL ACTIONS
 */

.modal-actions {
  display: flex;
  justify-content: flex-end;
  align-items: center;

  gap: 10px;

  margin-top: 22px;
}

.btn-secondary,
.btn-danger {
  border: 0;

  padding: 10px 16px;

  border-radius: 8px;

  cursor: pointer;
  font-weight: 600;
}

.btn-secondary {
  background: #f1f5f9;
  color: #334155;
}

.btn-danger {
  background: #dc2626;
  color: #fff;
}

/*
   DELETE MODAL
 */

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

.delete-modal p {
  color: #64748b;
  font-size: 14px;
}

/*
   TABLET
 */

@media (max-width: 1100px) {
  .toolbar-panel {
    grid-template-columns:
      minmax(200px, 1fr)
      minmax(150px, 1fr)
      minmax(140px, 1fr);

    align-items: stretch;
  }

  .search-box {
    grid-column: span 2;
  }

  .add-course-btn {
    width: 100%;
  }
}

/*
   MOBILE
 */

@media (max-width: 700px) {

  .stats-grid {
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }

  .stat-card {
    padding: 15px;
  }

  .stat-card strong {
    font-size: 22px;
  }

  /* Filters */

  .toolbar-panel {
    display: flex;
    flex-direction: column;

    width: 100%;

    padding: 14px;
    gap: 10px;

    box-sizing: border-box;
  }

  .search-box,
  .toolbar-panel select,
  .add-course-btn {
    width: 100%;
    min-width: 0;
    max-width: 100%;
    box-sizing: border-box;
  }

  .search-box input {
    width: 100%;
    box-sizing: border-box;
  }

  /* Table panel */

  .panel-header {
    padding: 15px;

    flex-direction: column;
    align-items: flex-start;
  }

  .clear-btn {
    margin-top: 2px;
  }

  /* Pagination */

  .pagination {
    flex-direction: column;
    align-items: stretch;
    padding: 14px 15px;
  }

  .pagination-info {
    text-align: center;
  }

  .pagination-controls {
    justify-content: center;
    flex-wrap: wrap;
  }

  .pagination-btn {
    padding: 0 9px;
  }

  /* Modal */

  .modal-overlay {
    padding: 12px;
  }

  .modal,
  .delete-modal {
    width: 100%;
    max-width: 100%;

    padding: 18px;

    border-radius: 12px;
  }

  /* Form stays two columns on mobile
     for Code/Name, Department/Semester,
     Credit/Type */

  .form-grid {
    grid-template-columns:
      minmax(0, 1fr)
      minmax(0, 1fr);

    gap: 12px;
  }

  .form-group {
    min-width: 0;
  }

  .form-group.full {
    grid-column: 1 / -1;
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    width: 100%;
    max-width: 100%;
    min-width: 0;

    box-sizing: border-box;
  }

  .modal-actions {
    flex-direction: column-reverse;
    width: 100%;
  }

  .modal-actions button {
    width: 100%;
  }
}

/*
   VERY SMALL MOBILE
 */

@media (max-width: 430px) {

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .form-grid {
    grid-template-columns:
      minmax(0, 1fr)
      minmax(0, 1fr);

    gap: 10px;
  }

  .modal {
    padding: 15px;
  }

  .form-group label {
    font-size: 12px;
  }

  input,
  select,
  textarea {
    font-size: 13px;
    padding: 9px 10px;
  }

  .pagination-controls {
    gap: 4px;
  }

  .pagination-number {
    width: 31px;
    height: 32px;
  }

  .pagination-btn {
    height: 32px;
    font-size: 12px;
  }
}
</style>