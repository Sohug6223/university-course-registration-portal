<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import {
  Plus,
  Search,
  Edit2,
  Trash2,
  X,
  Megaphone,
  Image as ImageIcon,
  Upload,
  CalendarDays,
} from "lucide-vue-next";

import AdminLayout from "@/components/admin/AdminLayout.vue";
import api from "@/services/api";

/*  TYPES  */

interface Notice {
  id: number;
  title: string;
  description: string;
  published_date: string;
  expiry_date: string | null;
  image_path: string | null;
  image_url: string | null;
  status: "Active" | "Inactive";
  created_by: number | null;
  creator?: {
    id: number;
    name: string;
  } | null;
  created_at: string;
}

interface NoticeForm {
  title: string;
  description: string;
  published_date: string;
  expiry_date: string;
  status: "Active" | "Inactive";
  image: File | null;
}

/*  STATE  */

const notices = ref<Notice[]>([]);
const loading = ref(false);

const search = ref("");
const statusFilter = ref("");

const showModal = ref(false);
const showDeleteModal = ref(false);

const editingNotice = ref<Notice | null>(null);
const deletingNotice = ref<Notice | null>(null);

const imagePreview = ref<string | null>(null);
const imageInput = ref<HTMLInputElement | null>(null);

const toast = ref({
  show: false,
  message: "",
  type: "success" as "success" | "error",
});

const form = ref<NoticeForm>(getEmptyForm());

/*  HELPERS  */

function getEmptyForm(): NoticeForm {
  return {
    title: "",
    description: "",
    published_date: new Date().toISOString().split("T")[0] ?? "",
    expiry_date: "",
    status: "Active",
    image: null,
  };
}

function showToast(
  message: string,
  type: "success" | "error" = "success"
) {
  toast.value = {
    show: true,
    message,
    type,
  };

  setTimeout(() => {
    toast.value.show = false;
  }, 3000);
}

function formatDate(date: string | null) {
  if (!date) return "—";

  return new Date(date).toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
}

function truncate(text: string, length = 90) {
  return text.length > length
    ? `${text.substring(0, length)}...`
    : text;
}

/*  COMPUTED  */

const totalNotices = computed(() => notices.value.length);

const activeNotices = computed(
  () => notices.value.filter((n) => n.status === "Active").length
);

const inactiveNotices = computed(
  () => notices.value.filter((n) => n.status === "Inactive").length
);

const filteredNotices = computed(() => {
  const keyword = search.value.trim().toLowerCase();

  return notices.value.filter((notice) => {
    const matchesSearch =
      !keyword ||
      notice.title.toLowerCase().includes(keyword) ||
      notice.description.toLowerCase().includes(keyword);

    const matchesStatus =
      !statusFilter.value ||
      notice.status === statusFilter.value;

    return matchesSearch && matchesStatus;
  });
});

/*  LOAD  */

async function loadNotices() {
  loading.value = true;

  try {
    const { data } = await api.get("/admin/notices");

    notices.value = data.notices ?? [];
  } catch (error: any) {
    showToast(
      error?.response?.data?.message ||
      "Failed to load notices.",
      "error"
    );
  } finally {
    loading.value = false;
  }
}

/*  MODAL  */

function openCreateModal() {
  editingNotice.value = null;
  form.value = getEmptyForm();

  imagePreview.value = null;

  showModal.value = true;
}

function openEditModal(notice: Notice) {
  editingNotice.value = notice;

  form.value = {
    title: notice.title,
    description: notice.description,
    published_date: notice.published_date?.substring(0, 10) || "",
    expiry_date: notice.expiry_date
      ? notice.expiry_date.substring(0, 10)
      : "",
    status: notice.status,
    image: null,
  };

  imagePreview.value = notice.image_url;

  showModal.value = true;
}

function closeModal() {
  showModal.value = false;

  editingNotice.value = null;
  form.value = getEmptyForm();

  imagePreview.value = null;

  if (imageInput.value) {
    imageInput.value.value = "";
  }
}

/*  IMAGE  */

function triggerImageUpload() {
  imageInput.value?.click();
}

function handleImageChange(event: Event) {
  const target = event.target as HTMLInputElement;

  const file = target.files?.[0];

  if (!file) return;

  const allowedTypes = [
    "image/jpeg",
    "image/jpg",
    "image/png",
    "image/webp",
  ];

  if (!allowedTypes.includes(file.type)) {
    showToast(
      "Only JPG, JPEG, PNG and WEBP images are allowed.",
      "error"
    );

    target.value = "";
    return;
  }

  if (file.size > 5 * 1024 * 1024) {
    showToast(
      "Image size must be 5 MB or less.",
      "error"
    );

    target.value = "";
    return;
  }

  form.value.image = file;

  imagePreview.value = URL.createObjectURL(file);
}

function removeSelectedImage() {
  form.value.image = null;
  imagePreview.value = editingNotice.value?.image_url || null;

  if (imageInput.value) {
    imageInput.value.value = "";
  }
}

/*  SAVE  */

async function saveNotice() {
  if (!form.value.title.trim()) {
    showToast("Please enter notice title.", "error");
    return;
  }

  if (!form.value.description.trim()) {
    showToast("Please enter notice description.", "error");
    return;
  }

  if (!form.value.published_date) {
    showToast("Please select published date.", "error");
    return;
  }

  if (
    form.value.expiry_date &&
    form.value.expiry_date < form.value.published_date
  ) {
    showToast(
      "Expiry date cannot be before published date.",
      "error"
    );
    return;
  }

  const data = new FormData();

  data.append("title", form.value.title.trim());
  data.append("description", form.value.description.trim());
  data.append(
    "published_date",
    form.value.published_date
  );

  data.append(
    "expiry_date",
    form.value.expiry_date || ""
  );

  data.append("status", form.value.status);

  if (form.value.image) {
    data.append("image", form.value.image);
  }

  try {
    if (editingNotice.value) {
      data.append("_method", "PUT");

      await api.post(
        `/admin/notices/${editingNotice.value.id}`,
        data,
        {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        }
      );

      showToast("Notice updated successfully.");
    } else {
      await api.post("/admin/notices", data, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });

      showToast("Notice created successfully.");
    }

    closeModal();
    await loadNotices();
  } catch (error: any) {
    const message =
      error?.response?.data?.message ||
      "Something went wrong.";

    showToast(message, "error");
  }
}

/*  DELETE  */

function askDelete(notice: Notice) {
  deletingNotice.value = notice;
  showDeleteModal.value = true;
}

function closeDeleteModal() {
  showDeleteModal.value = false;
  deletingNotice.value = null;
}

async function deleteNotice() {
  if (!deletingNotice.value) return;

  try {
    await api.delete(
      `/admin/notices/${deletingNotice.value.id}`
    );

    closeDeleteModal();

    showToast("Notice deleted successfully.");

    await loadNotices();
  } catch (error: any) {
    showToast(
      error?.response?.data?.message ||
      "Failed to delete notice.",
      "error"
    );
  }
}

/*  INIT  */

onMounted(loadNotices);
</script>

<template>
  <AdminLayout title="Notice Management" subtitle="Create and manage university notices.">
    <div class="notice-page">

      <!-- TOAST -->
      <Transition name="toast">
        <div v-if="toast.show" class="toast" :class="toast.type">
          <span>{{ toast.message }}</span>

          <button @click="toast.show = false">
            <X :size="17" />
          </button>
        </div>
      </Transition>

      <!-- HEADER -->
      <div class="page-header">
        <div>
          <h2>University Notices</h2>
          <p>
            Publish important academic and administrative
            announcements.
          </p>
        </div>

        <button class="btn-primary" @click="openCreateModal">
          <Plus :size="18" />
          Add Notice
        </button>
      </div>

      <!-- STATS -->
      <div class="stats-grid">

        <div class="stat-card">
          <div class="stat-icon">
            <Megaphone :size="21" />
          </div>

          <div>
            <span>Total Notices</span>
            <strong>{{ totalNotices }}</strong>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <Megaphone :size="21" />
          </div>

          <div>
            <span>Active Notices</span>
            <strong>{{ activeNotices }}</strong>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <Megaphone :size="21" />
          </div>

          <div>
            <span>Inactive Notices</span>
            <strong>{{ inactiveNotices }}</strong>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <ImageIcon :size="21" />
          </div>

          <div>
            <span>With Images</span>
            <strong>
              {{notices.filter(n => n.image_url).length}}
            </strong>
          </div>
        </div>

      </div>

      <!-- TOOLBAR -->
      <div class="toolbar-panel">

        <div class="search-box">
          <Search :size="18" />

          <input v-model="search" type="text" placeholder="Search notices..." />
        </div>

        <select v-model="statusFilter">
          <option value="">All Status</option>
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
        </select>

      </div>

      <!-- TABLE -->
      <div class="panel table-panel">

        <div class="table-header">
          <div>
            <h3>Notice List</h3>
            <span>
              {{ filteredNotices.length }} notices found
            </span>
          </div>
        </div>

        <div v-if="loading" class="loading-state">
          Loading notices...
        </div>

        <div v-else-if="filteredNotices.length === 0" class="empty-state">
          <Megaphone :size="42" />
          <h3>No notices found</h3>
          <p>
            Create your first notice to get started.
          </p>
        </div>

        <div v-else class="table-wrapper">
          <table class="custom-table">

            <thead>
              <tr>
                <th>Notice</th>
                <th>Published</th>
                <th>Expiry</th>
                <th>Status</th>
                <th>Created By</th>
                <th>Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="notice in filteredNotices" :key="notice.id">

                <td>
                  <div class="notice-cell">

                    <div v-if="notice.image_url" class="notice-image">
                      <img :src="notice.image_url" :alt="notice.title" />
                    </div>

                    <div v-else class="notice-image no-image">
                      <Megaphone :size="20" />
                    </div>

                    <div class="notice-info">
                      <strong>
                        {{ notice.title }}
                      </strong>

                      <span>
                        {{ truncate(notice.description) }}
                      </span>
                    </div>

                  </div>
                </td>

                <td>
                  <div class="date-cell">
                    <CalendarDays :size="15" />
                    {{ formatDate(notice.published_date) }}
                  </div>
                </td>

                <td>
                  {{ formatDate(notice.expiry_date) }}
                </td>

                <td>
                  <span class="status-badge" :class="notice.status.toLowerCase()
                    ">
                    {{ notice.status }}
                  </span>
                </td>

                <td>
                  {{ notice.creator?.name || "Admin" }}
                </td>

                <td>
                  <div class="action-buttons">

                    <button class="action-btn edit" title="Edit Notice" @click="openEditModal(notice)">
                      <Edit2 :size="16" />
                    </button>

                    <button class="action-btn delete" title="Delete Notice" @click="askDelete(notice)">
                      <Trash2 :size="16" />
                    </button>

                  </div>
                </td>

              </tr>
            </tbody>

          </table>
        </div>

      </div>

      <!-- CREATE / EDIT MODAL -->
      <Transition name="modal">
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
          <div class="modal">

            <div class="modal-header">
              <div>
                <h3>
                  {{
                    editingNotice
                      ? "Edit Notice"
                      : "Create Notice"
                  }}
                </h3>

                <p>
                  {{
                    editingNotice
                      ? "Update notice information."
                      : "Publish a new university notice."
                  }}
                </p>
              </div>

              <button class="close-btn" @click="closeModal">
                <X :size="20" />
              </button>
            </div>

            <div class="modal-body">

              <!-- TITLE -->
              <div class="form-group">
                <label>
                  Notice Title
                  <span>*</span>
                </label>

                <input v-model="form.title" type="text" placeholder="Enter notice title" />
              </div>

              <!-- DESCRIPTION -->
              <div class="form-group">
                <label>
                  Description
                  <span>*</span>
                </label>

                <textarea v-model="form.description" rows="6" placeholder="Write notice details..."></textarea>
              </div>

              <!-- DATES -->
              <div class="form-grid">

                <div class="form-group">
                  <label>
                    Published Date
                    <span>*</span>
                  </label>

                  <input v-model="form.published_date" type="date" />
                </div>

                <div class="form-group">
                  <label>
                    Expiry Date
                    <small>(Optional)</small>
                  </label>

                  <input v-model="form.expiry_date" type="date" />

                  <small class="help-text">
                    Leave empty if the notice has no expiry.
                  </small>
                </div>

              </div>

              <!-- STATUS -->
              <div class="form-group">
                <label>Status</label>

                <select v-model="form.status">
                  <option value="Active">
                    Active
                  </option>

                  <option value="Inactive">
                    Inactive
                  </option>
                </select>
              </div>

              <!-- IMAGE -->
              <div class="form-group">
                <label>
                  Notice Image
                  <small>(Optional)</small>
                </label>

                <input ref="imageInput" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                  hidden @change="handleImageChange" />

                <div v-if="imagePreview" class="image-preview-box">
                  <img :src="imagePreview" alt="Notice preview" />

                  <button type="button" class="remove-image" @click="removeSelectedImage">
                    <X :size="16" />
                  </button>
                </div>

                <button v-else type="button" class="upload-box" @click="triggerImageUpload">
                  <Upload :size="23" />

                  <strong>
                    Upload Notice Image
                  </strong>

                  <span>
                    JPG, PNG, WEBP · Maximum 5 MB
                  </span>
                </button>

                <button v-if="imagePreview" type="button" class="change-image-btn" @click="triggerImageUpload">
                  <Upload :size="15" />
                  Change Image
                </button>

                <p class="image-note">
                  You can upload a photo/scan of a printed
                  university notice.
                </p>
              </div>

            </div>

            <div class="modal-footer">

              <button class="btn-secondary" @click="closeModal">
                Cancel
              </button>

              <button class="btn-primary" @click="saveNotice">
                {{
                  editingNotice
                    ? "Update Notice"
                    : "Publish Notice"
                }}
              </button>

            </div>

          </div>
        </div>
      </Transition>

      <!-- DELETE MODAL -->
      <Transition name="modal">
        <div v-if="showDeleteModal" class="modal-overlay" @click.self="closeDeleteModal">
          <div class="confirm-modal">

            <div class="confirm-icon">
              <Trash2 :size="24" />
            </div>

            <h3>Delete Notice?</h3>

            <p>
              Are you sure you want to delete
              <strong>
                "{{ deletingNotice?.title }}"
              </strong>
              ?
              This action cannot be undone.
            </p>

            <div class="confirm-actions">

              <button class="btn-secondary" @click="closeDeleteModal">
                Cancel
              </button>

              <button class="btn-danger" @click="deleteNotice">
                Delete Notice
              </button>

            </div>

          </div>
        </div>
      </Transition>

    </div>
  </AdminLayout>
</template>

<style scoped>
.notice-page {
  position: relative;
}

/* HEADER */

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  margin-bottom: 22px;
}

.page-header h2 {
  margin: 0 0 5px;
  font-size: 23px;
  color: #172033;
}

.page-header p {
  margin: 0;
  color: #6b7280;
  font-size: 14px;
}

/* BUTTONS */

.btn-primary,
.btn-secondary,
.btn-danger {
  height: 42px;
  padding: 0 17px;
  border-radius: 8px;
  border: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
}

.btn-primary {
  background: #172554;
  color: white;
}

.btn-primary:hover {
  background: #10204b;
}

.btn-secondary {
  background: #f1f3f6;
  color: #374151;
}

.btn-danger {
  background: #dc2626;
  color: white;
}

/* STATS */

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}

.stat-card {
  background: white;
  border: 1px solid #e7eaf0;
  border-radius: 11px;
  padding: 17px;
  display: flex;
  align-items: center;
  gap: 13px;
}

.stat-icon {
  width: 43px;
  height: 43px;
  border-radius: 9px;
  background: #eef2ff;
  color: #172554;
  display: grid;
  place-items: center;
}

.stat-card span {
  display: block;
  font-size: 12px;
  color: #737b8c;
  margin-bottom: 3px;
}

.stat-card strong {
  color: #172033;
  font-size: 21px;
}

/* TOOLBAR */

.toolbar-panel {
  background: white;
  border: 1px solid #e7eaf0;
  border-radius: 11px;
  padding: 15px;
  display: flex;
  gap: 12px;
  margin-bottom: 18px;
}

.search-box {
  flex: 1;
  height: 42px;
  border: 1px solid #dfe3ea;
  border-radius: 8px;
  display: flex;
  align-items: center;
  padding: 0 12px;
  color: #8b93a2;
}

.search-box input {
  width: 100%;
  border: 0;
  outline: 0;
  padding-left: 9px;
  font-size: 14px;
}

.toolbar-panel select,
.form-group select,
.form-group input,
.form-group textarea {
  border: 1px solid #dfe3ea;
  border-radius: 8px;
  outline: none;
  font-size: 14px;
  background: white;
  color: #1f2937;
}

.toolbar-panel select {
  width: 150px;
  padding: 0 11px;
}

/* PANEL */

.panel {
  background: white;
  border: 1px solid #e7eaf0;
  border-radius: 11px;
}

.table-header {
  padding: 18px 20px;
  border-bottom: 1px solid #edf0f4;
}

.table-header h3 {
  margin: 0 0 4px;
  color: #172033;
  font-size: 16px;
}

.table-header span {
  font-size: 12px;
  color: #7b8494;
}

.table-wrapper {
  overflow-x: auto;
}

.custom-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 950px;
}

.custom-table th {
  text-align: left;
  padding: 13px 18px;
  background: #fafbfc;
  color: #687182;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: .04em;
  font-weight: 700;
}

.custom-table td {
  padding: 14px 18px;
  border-top: 1px solid #edf0f4;
  color: #394150;
  font-size: 13px;
}

.notice-cell {
  display: flex;
  align-items: center;
  gap: 11px;
  max-width: 430px;
}

.notice-image {
  flex-shrink: 0;
  width: 52px;
  height: 52px;
  border-radius: 7px;
  overflow: hidden;
  border: 1px solid #e2e5ea;
  background: #f6f7f9;
  display: grid;
  place-items: center;
  color: #172554;
}

.notice-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.notice-info {
  min-width: 0;
}

.notice-info strong {
  display: block;
  color: #202938;
  margin-bottom: 3px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.notice-info span {
  display: block;
  color: #7a8392;
  font-size: 12px;
  line-height: 1.4;
}

.date-cell {
  display: flex;
  align-items: center;
  gap: 6px;
}

.status-badge {
  display: inline-flex;
  padding: 5px 9px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
}

.status-badge.active {
  background: #e9f8ef;
  color: #18834a;
}

.status-badge.inactive {
  background: #f1f2f4;
  color: #6b7280;
}

.action-buttons {
  display: flex;
  gap: 7px;
}

.action-btn {
  width: 34px;
  height: 34px;
  border-radius: 7px;
  border: 1px solid #e2e5ea;
  background: white;
  cursor: pointer;
  display: grid;
  place-items: center;
}

.action-btn.edit {
  color: #172554;
}

.action-btn.delete {
  color: #dc2626;
}

.action-btn:hover {
  background: #f6f7f9;
}

/* EMPTY */

.loading-state,
.empty-state {
  min-height: 260px;
  display: grid;
  place-items: center;
  align-content: center;
  color: #7b8494;
}

.empty-state h3 {
  margin: 12px 0 5px;
  color: #374151;
}

.empty-state p {
  margin: 0;
  font-size: 13px;
}

/* MODAL */

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(15, 23, 42, .55);
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

.modal {
  width: min(680px, 100%);
  max-height: 92vh;
  overflow-y: auto;
  background: white;
  border-radius: 13px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, .2);
}

.modal-header {
  padding: 20px;
  display: flex;
  justify-content: space-between;
  border-bottom: 1px solid #edf0f4;
}

.modal-header h3 {
  margin: 0 0 4px;
  color: #172033;
}

.modal-header p {
  margin: 0;
  color: #7b8494;
  font-size: 13px;
}

.close-btn {
  width: 35px;
  height: 35px;
  border: 0;
  border-radius: 7px;
  background: #f3f4f6;
  cursor: pointer;
  display: grid;
  place-items: center;
  color: #596273;
}

.modal-body {
  padding: 20px;
}

.form-group {
  margin-bottom: 17px;
}

.form-group label {
  display: block;
  margin-bottom: 7px;
  color: #374151;
  font-size: 13px;
  font-weight: 600;
}

.form-group label span {
  color: #dc2626;
}

.form-group label small {
  font-weight: 400;
  color: #8a93a2;
}

.form-group input,
.form-group select {
  width: 100%;
  height: 42px;
  padding: 0 12px;
  box-sizing: border-box;
}

.form-group textarea {
  width: 100%;
  padding: 11px 12px;
  resize: vertical;
  box-sizing: border-box;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  border-color: #172554;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.help-text,
.image-note {
  display: block;
  margin: 5px 0 0;
  color: #8a93a2;
  font-size: 11px;
}

/* IMAGE UPLOAD */

.upload-box {
  width: 100%;
  min-height: 130px;
  border: 1.5px dashed #cfd5df;
  border-radius: 9px;
  background: #fafbfc;
  color: #596273;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 7px;
}

.upload-box:hover {
  border-color: #172554;
  background: #f8f9fc;
}

.upload-box strong {
  color: #374151;
  font-size: 13px;
}

.upload-box span {
  font-size: 11px;
  color: #8a93a2;
}

.image-preview-box {
  position: relative;
  border: 1px solid #dfe3ea;
  border-radius: 9px;
  padding: 7px;
  background: #fafbfc;
}

.image-preview-box img {
  width: 100%;
  max-height: 250px;
  object-fit: contain;
  display: block;
  border-radius: 6px;
}

.remove-image {
  position: absolute;
  right: 14px;
  top: 14px;
  width: 32px;
  height: 32px;
  border: 0;
  border-radius: 50%;
  background: rgba(0, 0, 0, .7);
  color: white;
  display: grid;
  place-items: center;
  cursor: pointer;
}

.change-image-btn {
  margin-top: 8px;
  border: 0;
  background: transparent;
  color: #172554;
  cursor: pointer;
  font-size: 12px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 0;
}

/* FOOTER */

.modal-footer {
  padding: 15px 20px;
  border-top: 1px solid #edf0f4;
  display: flex;
  justify-content: flex-end;
  gap: 9px;
}

/* CONFIRM */

.confirm-modal {
  width: min(420px, 100%);
  background: white;
  border-radius: 13px;
  padding: 28px;
  text-align: center;
}

.confirm-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: #fee2e2;
  color: #dc2626;
  display: grid;
  place-items: center;
  margin: 0 auto 14px;
}

.confirm-modal h3 {
  margin: 0 0 8px;
  color: #172033;
}

.confirm-modal p {
  margin: 0;
  color: #697386;
  font-size: 13px;
  line-height: 1.6;
}

.confirm-actions {
  display: flex;
  justify-content: center;
  gap: 9px;
  margin-top: 22px;
}

/* TOAST */

.toast {
  position: fixed;
  right: 25px;
  top: 25px;
  z-index: 2000;
  min-width: 280px;
  max-width: 400px;
  background: white;
  border: 1px solid #e3e7ed;
  border-radius: 9px;
  padding: 13px 14px;
  box-shadow: 0 10px 35px rgba(0, 0, 0, .12);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  font-size: 13px;
}

.toast.success {
  border-left: 4px solid #16a34a;
}

.toast.error {
  border-left: 4px solid #dc2626;
}

.toast button {
  border: 0;
  background: transparent;
  cursor: pointer;
  color: #7b8494;
}

/* TRANSITIONS */

.toast-enter-active,
.toast-leave-active,
.modal-enter-active,
.modal-leave-active {
  transition: .2s ease;
}

.toast-enter-from,
.toast-leave-to,
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal,
.modal-enter-from .confirm-modal {
  transform: translateY(10px) scale(.98);
}

/* RESPONSIVE */

@media (max-width: 1000px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 650px) {
  .page-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .btn-primary {
    width: 100%;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .toolbar-panel {
    flex-direction: column;
  }

  .toolbar-panel select {
    width: 100%;
    height: 42px;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .modal-overlay {
    padding: 10px;
  }

  .modal {
    max-height: 95vh;
  }
}
</style>