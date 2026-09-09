<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import {
  AlertCircle,
  Bell,
  CalendarDays,
  ChevronDown,
  ChevronUp,
  Clock3,
  RefreshCw,
  X,
} from "lucide-vue-next";
import StudentLayout from "@/components/student/StudentLayout.vue";
import api from "@/services/api";

interface Notice {
  id: number;
  title: string;
  description: string | null;
  published_date: string;
  expiry_date: string | null;
  image_path: string | null;
  status: string;
}

interface NoticeData {
  notices: Notice[];
}

const loading = ref(true);
const refreshing = ref(false);
const error = ref("");
const data = ref<NoticeData | null>(null);
const expandedNotice = ref<number | null>(null);
const selectedImage = ref<string | null>(null);

const notices = computed(() => data.value?.notices ?? []);

const loadNotices = async () => {
  try {
    error.value = "";

    const response = await api.get<NoticeData>("/student/notices");

    data.value = response.data;

    if (notices.value.length > 0 && expandedNotice.value === null) {
      expandedNotice.value = notices.value[0]?.id ?? null;
    }
  } catch (err: any) {
    error.value =
      err?.response?.data?.message ||
      "Failed to load notices.";
  } finally {
    loading.value = false;
    refreshing.value = false;
  }
};

const refreshNotices = async () => {
  refreshing.value = true;
  await loadNotices();
};

const toggleNotice = (id: number) => {
  expandedNotice.value =
    expandedNotice.value === id ? null : id;
};

const formatDate = (date: string | null) => {
  if (!date) return "No expiry";

  const value = new Date(date);

  if (Number.isNaN(value.getTime())) {
    return "N/A";
  }

  return value.toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
};

const imageUrl = (path: string | null) => {
  if (!path) return "";

  if (path.startsWith("http")) {
    return path;
  }

  return `http://127.0.0.1:8000/storage/${path.replace(
    /^\/?storage\//,
    ""
  )}`;
};

const openImage = (path: string | null) => {
  if (path) {
    selectedImage.value = imageUrl(path);
  }
};

const closeImage = () => {
  selectedImage.value = null;
};

onMounted(loadNotices);
</script>

<template>
  <StudentLayout
    title="Notices"
    subtitle="View the latest university notices and announcements."
  >
    <div class="notices-page">

      <!-- Loading -->
      <div v-if="loading" class="state-box">
        <RefreshCw :size="28" class="spin" />
        <p>Loading notices...</p>
      </div>

      <!-- Error -->
      <div
        v-else-if="error"
        class="state-box error-state"
      >
        <AlertCircle :size="34" />

        <h3>Unable to load notices</h3>

        <p>{{ error }}</p>

        <button
          class="btn-primary"
          @click="refreshNotices"
        >
          <RefreshCw :size="17" />
          Try Again
        </button>
      </div>

      <!-- Content -->
      <template v-else>

        <!-- Header -->
        <section class="page-header">
          <div>
            <div class="header-icon">
              <Bell :size="22" />
            </div>

            <div>
              <h1>University Notices</h1>
              <p>
                Latest announcements and important academic information.
              </p>
            </div>
          </div>

          <button
            class="refresh-btn"
            title="Refresh notices"
            :disabled="refreshing"
            @click="refreshNotices"
          >
            <RefreshCw
              :size="18"
              :class="{ spin: refreshing }"
            />
          </button>
        </section>

        <!-- Empty -->
        <div
          v-if="!notices.length"
          class="empty-box"
        >
          <Bell :size="42" />

          <h3>No Notices Available</h3>

          <p>
            There are no published notices at the moment.
          </p>
        </div>

        <!-- Notices -->
        <section
          v-else
          class="notice-list"
        >
          <article
            v-for="(notice, index) in notices"
            :key="notice.id"
            class="notice-card"
            :class="{
              latest: index === 0,
              expanded: expandedNotice === notice.id
            }"
          >

            <!-- Notice Header -->
            <button
              class="notice-header"
              @click="toggleNotice(notice.id)"
            >
              <div class="notice-left">

                <div class="notice-icon">
                  <Bell :size="20" />
                </div>

                <div class="notice-heading">
                  <div class="title-row">
                    <h2>{{ notice.title }}</h2>

                    <span
                      v-if="index === 0"
                      class="latest-badge"
                    >
                      Latest
                    </span>
                  </div>

                  <div class="notice-meta">
                    <span>
                      <CalendarDays :size="13" />
                      {{ formatDate(notice.published_date) }}
                    </span>

                    <span v-if="notice.expiry_date">
                      <Clock3 :size="13" />
                      Expires {{ formatDate(notice.expiry_date) }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="expand-icon">
                <ChevronUp
                  v-if="expandedNotice === notice.id"
                  :size="20"
                />

                <ChevronDown
                  v-else
                  :size="20"
                />
              </div>
            </button>

            <!-- Notice Content -->
            <div
              v-if="expandedNotice === notice.id"
              class="notice-content"
            >
              <div
                v-if="notice.image_path"
                class="notice-image"
                @click="openImage(notice.image_path)"
              >
                <img
                  :src="imageUrl(notice.image_path)"
                  :alt="notice.title"
                />
              </div>

              <div class="notice-description">
                <p>
                  {{ notice.description || "No description provided." }}
                </p>
              </div>

              <div class="notice-footer">
                <div>
                  <span>Published</span>
                  <strong>
                    {{ formatDate(notice.published_date) }}
                  </strong>
                </div>

                <div v-if="notice.expiry_date">
                  <span>Expiry Date</span>
                  <strong>
                    {{ formatDate(notice.expiry_date) }}
                  </strong>
                </div>
              </div>
            </div>

          </article>
        </section>

      </template>
    </div>

    <!-- Image Modal -->
    <Teleport to="body">
      <div
        v-if="selectedImage"
        class="image-modal"
        @click.self="closeImage"
      >
        <button
          class="modal-close"
          @click="closeImage"
        >
          <X :size="22" />
        </button>

        <img
          :src="selectedImage"
          alt="Notice image"
          class="modal-image"
        />
      </div>
    </Teleport>

  </StudentLayout>
</template>

<style scoped>
.notices-page {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

/*  HEADER  */

.page-header {
  background: #fff;
  border: 1px solid #e5eaf0;
  border-radius: 14px;
  padding: 18px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.page-header > div {
  display: flex;
  align-items: center;
  gap: 13px;
}

.header-icon {
  width: 44px;
  height: 44px;
  border-radius: 11px;
  background: #eaf2f9;
  color: #123b63;
  display: flex;
  align-items: center;
  justify-content: center;
}

.page-header h1 {
  margin: 0;
  color: #16283b;
  font-size: 18px;
  font-weight: 750;
}

.page-header p {
  margin: 4px 0 0;
  color: #7c8996;
  font-size: 12px;
}

/*  REFRESH  */

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
  background: #f3f7fa;
}

.refresh-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/*  NOTICE LIST  */

.notice-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.notice-card {
  background: #fff;
  border: 1px solid #e2e8ef;
  border-radius: 13px;
  overflow: hidden;
  box-shadow: 0 2px 7px rgba(15, 23, 42, 0.035);
}

.notice-card.latest {
  border-color: #c9d9e8;
}

.notice-header {
  width: 100%;
  border: 0;
  background: #fff;
  padding: 16px 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  cursor: pointer;
  text-align: left;
}

.notice-header:hover {
  background: #fafcfe;
}

.notice-left {
  display: flex;
  align-items: center;
  gap: 13px;
  min-width: 0;
}

.notice-icon {
  width: 43px;
  height: 43px;
  flex-shrink: 0;
  border-radius: 10px;
  background: #eaf2f9;
  color: #123b63;
  display: flex;
  align-items: center;
  justify-content: center;
}

.notice-heading {
  min-width: 0;
}

.title-row {
  display: flex;
  align-items: center;
  gap: 9px;
  flex-wrap: wrap;
}

.notice-heading h2 {
  margin: 0;
  color: #1c2e40;
  font-size: 15px;
  font-weight: 700;
}

.latest-badge {
  padding: 4px 8px;
  border-radius: 6px;
  background: #123b63;
  color: #fff;
  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.notice-meta {
  margin-top: 6px;
  display: flex;
  align-items: center;
  gap: 15px;
  flex-wrap: wrap;
}

.notice-meta span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: #8a96a3;
  font-size: 11px;
}

.expand-icon {
  flex-shrink: 0;
  color: #6f7d8b;
}

/*  CONTENT  */

.notice-content {
  border-top: 1px solid #e8edf2;
  padding: 18px;
}

.notice-description {
  color: #4b5c6d;
  font-size: 13px;
  line-height: 1.75;
  white-space: pre-line;
}

.notice-description p {
  margin: 0;
}

.notice-image {
  margin-bottom: 16px;
  border-radius: 10px;
  overflow: hidden;
  background: #f5f7f9;
  cursor: pointer;
}

.notice-image img {
  width: 100%;
  max-height: 420px;
  display: block;
  object-fit: contain;
}

.notice-footer {
  margin-top: 18px;
  padding-top: 15px;
  border-top: 1px solid #edf1f5;
  display: flex;
  gap: 35px;
}

.notice-footer div {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.notice-footer span {
  color: #8995a2;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
}

.notice-footer strong {
  color: #33475a;
  font-size: 12px;
}

/*  STATE  */

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

/*  IMAGE MODAL  */

.image-modal {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(8, 18, 30, 0.78);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 30px;
}

.modal-image {
  max-width: 92vw;
  max-height: 88vh;
  object-fit: contain;
  border-radius: 10px;
  background: #fff;
}

.modal-close {
  position: fixed;
  top: 20px;
  right: 22px;
  width: 40px;
  height: 40px;
  border: 0;
  border-radius: 9px;
  background: #fff;
  color: #26394c;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

/*  SPIN  */

.spin {
  animation: spin 0.9s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/*  RESPONSIVE  */

@media (max-width: 700px) {
  .page-header {
    padding: 15px;
  }

  .page-header h1 {
    font-size: 16px;
  }

  .page-header p {
    display: none;
  }

  .notice-header {
    padding: 14px;
  }

  .notice-left {
    gap: 10px;
  }

  .notice-icon {
    width: 39px;
    height: 39px;
  }

  .notice-heading h2 {
    font-size: 14px;
  }

  .notice-content {
    padding: 14px;
  }

  .notice-footer {
    gap: 20px;
  }

  .image-modal {
    padding: 15px;
  }
}
</style>