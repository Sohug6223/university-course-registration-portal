<script setup lang="ts">
import { computed, onMounted, ref } from "vue";

import {
  CalendarDays,
  Clock3,
  MapPin,
  User,
  RefreshCw,
  AlertCircle,
  BookOpen,
} from "lucide-vue-next";

import StudentLayout from "@/components/student/StudentLayout.vue";
import api from "@/services/api";

/*  TYPES  */

interface Student {
  name: string;
  user_id: string;
  department: string;
  batch: number | null;
  section: string | null;
  semester: string | null;
}

interface Schedule {
  id: number;
  day: string;
  start_time: string;
  end_time: string;
  room: string | null;
  teacher: string | null;
  course_code: string | null;
  course_name: string | null;
  credit: number | null;
  section: string | null;
}

interface RoutineData {
  student: Student;
  routine: Schedule[];
  message: string | null;
}

/*  STATE  */

const loading = ref(true);
const refreshing = ref(false);
const errorMessage = ref("");

const data = ref<RoutineData>({
  student: {
    name: "",
    user_id: "",
    department: "",
    batch: null,
    section: null,
    semester: null,
  },

  routine: [],

  message: null,
});

/*  DAYS  */

const days = [
  "Saturday",
  "Sunday",
  "Monday",
  "Tuesday",
  "Wednesday",
  "Thursday",
  "Friday",
];

/*  COMPUTED  */

const routineByDay = computed<Record<string, Schedule[]>>(() => {
  const grouped: Record<string, Schedule[]> = {};

  for (const day of days) {
    grouped[day] = data.value.routine.filter(
      (item) => item.day === day
    );
  }

  return grouped;
});

/*  HELPERS  */

function formatTime(time: string | null) {
  if (!time) {
    return "N/A";
  }

  const parts = time.split(":");

  if (parts.length < 2) {
    return time;
  }

  const hour = Number(parts[0]);
  const minute = Number(parts[1]);

  if (Number.isNaN(hour) || Number.isNaN(minute)) {
    return time;
  }

  const date = new Date();

  date.setHours(hour);
  date.setMinutes(minute);
  date.setSeconds(0);

  return date.toLocaleTimeString("en-US", {
    hour: "numeric",
    minute: "2-digit",
    hour12: true,
  });
}

function getError(error: any) {
  return (
    error?.response?.data?.message ||
    error?.response?.data?.error ||
    "Unable to load class schedule."
  );
}

/*  LOAD ROUTINE  */

async function loadRoutine() {
  loading.value = true;
  errorMessage.value = "";

  try {
    const response = await api.get<RoutineData>(
      "/student/routine"
    );

    data.value = response.data;
  } catch (error) {
    errorMessage.value = getError(error);
  } finally {
    loading.value = false;
  }
}

/*  REFRESH  */

async function refresh() {
  if (refreshing.value) {
    return;
  }

  refreshing.value = true;

  try {
    await loadRoutine();
  } finally {
    refreshing.value = false;
  }
}

/*  INIT  */

onMounted(() => {
  loadRoutine();
});
</script>

<template>
  <StudentLayout
    title="Class Schedule"
    subtitle="View your weekly class routine."
  >
    <template #default>
      <div class="routine-page">

        <!--  LOADING  -->

        <div
          v-if="loading"
          class="loading-box"
        >
          <RefreshCw
            :size="22"
            class="spin"
          />

          <span>
            Loading class schedule...
          </span>
        </div>


        <!--  CONTENT  -->

        <template v-else>

          <!-- ERROR -->

          <div
            v-if="errorMessage"
            class="alert error"
          >
            <AlertCircle :size="19" />

            <span>
              {{ errorMessage }}
            </span>

            <button
              type="button"
              class="retry-btn"
              @click="loadRoutine"
            >
              Retry
            </button>
          </div>


          <!--  STUDENT INFO  -->

          <section class="panel">

            <div class="panel-title">

              <div class="title-icon">
                <CalendarDays :size="20" />
              </div>

              <div class="title-content">
                <h3>
                  My Class Schedule
                </h3>

                <p>
                  Your current semester routine.
                </p>
              </div>

              <button
                type="button"
                class="refresh-btn"
                :disabled="refreshing"
                @click="refresh"
              >
                <RefreshCw
                  :size="16"
                  :class="{ spin: refreshing }"
                />

                Refresh
              </button>

            </div>


            <div class="info-grid">

              <div class="info-item">
                <span>Student</span>

                <strong>
                  {{ data.student.name || "N/A" }}
                </strong>
              </div>


              <div class="info-item">
                <span>Student ID</span>

                <strong>
                  {{ data.student.user_id || "N/A" }}
                </strong>
              </div>


              <div class="info-item">
                <span>Department</span>

                <strong>
                  {{ data.student.department || "N/A" }}
                </strong>
              </div>


              <div class="info-item">
                <span>Batch</span>

                <strong>
                  {{ data.student.batch ?? "N/A" }}
                </strong>
              </div>


              <div class="info-item">
                <span>Section</span>

                <strong>
                  {{ data.student.section || "Not Assigned" }}
                </strong>
              </div>


              <div class="info-item">
                <span>Semester</span>

                <strong>
                  {{ data.student.semester || "N/A" }}
                </strong>
              </div>

            </div>

          </section>


          <!--  EMPTY  -->

          <section
            v-if="!data.routine.length"
            class="panel empty-box"
          >

            <CalendarDays :size="42" />

            <h3>
              No Class Schedule
            </h3>

            <p>
              {{
                data.message ||
                "No routine is available for your section."
              }}
            </p>

          </section>


          <!--  ROUTINE  -->

          <template v-else>

            <section
              v-for="day in days"
              :key="day"
              v-show="routineByDay[day]?.length"
              class="day-section"
            >

              <!-- DAY HEADER -->

              <div class="day-header">

                <div class="day-title">
                  <CalendarDays :size="18" />

                  <span>
                    {{ day }}
                  </span>
                </div>

                <span class="class-count">
                  {{
                    routineByDay[day]?.length ?? 0
                  }}

                  {{
                    (routineByDay[day]?.length ?? 0) === 1
                      ? "Class"
                      : "Classes"
                  }}
                </span>

              </div>


              <!-- CLASSES -->

              <div class="schedule-list">

                <div
                  v-for="item in routineByDay[day]"
                  :key="item.id"
                  class="schedule-card"
                >

                  <!-- TIME -->

                  <div class="time-box">

                    <Clock3 :size="18" />

                    <strong>
                      {{ formatTime(item.start_time) }}
                    </strong>

                    <span>
                      {{ formatTime(item.end_time) }}
                    </span>

                  </div>


                  <!-- COURSE -->

                  <div class="course-box">

                    <div class="course-code">
                      {{ item.course_code || "N/A" }}
                    </div>

                    <h3>
                      {{ item.course_name || "Course" }}
                    </h3>

                    <span
                      v-if="item.credit !== null"
                      class="credit"
                    >
                      {{ item.credit }} Credit
                    </span>

                  </div>


                  <!-- DETAILS -->

                  <div class="schedule-details">

                    <div>
                      <User :size="16" />

                      <span>
                        {{ item.teacher || "Not Assigned" }}
                      </span>
                    </div>


                    <div>
                      <MapPin :size="16" />

                      <span>
                        {{ item.room || "Not Assigned" }}
                      </span>
                    </div>


                    <div>
                      <BookOpen :size="16" />

                      <span>
                        Section
                        {{ item.section || "N/A" }}
                      </span>
                    </div>

                  </div>

                </div>

              </div>

            </section>

          </template>

        </template>

      </div>
    </template>
  </StudentLayout>
</template>

<style scoped>
.routine-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/*  PANEL  */

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

.title-icon {
  width: 42px;
  height: 42px;
  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  color: #123b63;
  background: #edf4fa;
  border-radius: 9px;
}

.title-content {
  min-width: 0;
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

/*  REFRESH  */

.refresh-btn {
  margin-left: auto;

  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;

  padding: 8px 13px;

  border: 1px solid #d7e0e9;
  border-radius: 7px;

  background: #fff;
  color: #123b63;

  font-size: 13px;
  font-weight: 500;

  cursor: pointer;
}

.refresh-btn:hover:not(:disabled) {
  background: #f6f9fc;
}

.refresh-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/*  INFO  */

.info-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
}

.info-item {
  background: #f8fafc;
  border: 1px solid #edf0f4;
  padding: 13px 15px;
  border-radius: 8px;
}

.info-item span {
  display: block;
  color: #718096;
  font-size: 12px;
  margin-bottom: 5px;
}

.info-item strong {
  color: #1a202c;
  font-size: 14px;
  font-weight: 600;
}

/*  DAY  */

.day-section {
  background: #fff;
  border: 1px solid #e5e9ef;
  border-radius: 12px;
  overflow: hidden;
}

.day-header {
  display: flex;
  justify-content: space-between;
  align-items: center;

  padding: 14px 18px;

  background: #123b63;
  color: #fff;
}

.day-title {
  display: flex;
  align-items: center;
  gap: 8px;

  font-size: 15px;
  font-weight: 600;
}

.class-count {
  font-size: 12px;
  opacity: 0.85;
}

/*  SCHEDULE  */

.schedule-list {
  display: flex;
  flex-direction: column;
}

.schedule-card {
  display: grid;
  grid-template-columns: 145px 1fr 270px;

  align-items: center;
  gap: 22px;

  padding: 18px;

  border-bottom: 1px solid #edf0f4;
}

.schedule-card:last-child {
  border-bottom: 0;
}

/*  TIME  */

.time-box {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 3px;

  color: #123b63;
}

.time-box svg {
  margin-bottom: 3px;
}

.time-box strong {
  font-size: 14px;
  font-weight: 600;
}

.time-box span {
  color: #718096;
  font-size: 12px;
}

/*  COURSE  */

.course-box {
  min-width: 0;
}

.course-code {
  color: #123b63;
  font-size: 12px;
  font-weight: 700;
}

.course-box h3 {
  margin: 4px 0;
  color: #1a202c;
  font-size: 15px;
  font-weight: 600;
}

.credit {
  color: #718096;
  font-size: 12px;
}

/*  DETAILS  */

.schedule-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.schedule-details div {
  display: flex;
  align-items: center;
  gap: 8px;

  color: #5f6b7a;
  font-size: 12px;
}

.schedule-details svg {
  flex-shrink: 0;
  color: #123b63;
}

/*  LOADING  */

.loading-box {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;

  padding: 60px 20px;

  color: #718096;
  font-size: 14px;
}

/*  EMPTY  */

.empty-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  text-align: center;
  color: #718096;

  padding: 50px 20px;
}

.empty-box h3 {
  margin: 12px 0 5px;

  color: #123b63;
  font-size: 17px;
}

.empty-box p {
  max-width: 500px;
  margin: 0;

  font-size: 13px;
  line-height: 1.6;
}

/*  ALERT  */

.alert {
  display: flex;
  align-items: center;
  gap: 9px;

  padding: 12px 15px;

  border-radius: 8px;

  font-size: 13px;
}

.alert.error {
  color: #a61b1b;
  background: #fff1f1;
  border: 1px solid #f2cccc;
}

.retry-btn {
  margin-left: auto;

  border: 0;
  background: transparent;

  color: #123b63;

  font-size: 13px;
  font-weight: 600;

  cursor: pointer;
}

/*  ANIMATION  */

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/*  RESPONSIVE  */

@media (max-width: 950px) {
  .info-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .schedule-card {
    grid-template-columns: 120px 1fr;
  }

  .schedule-details {
    grid-column: 2;
  }
}

@media (max-width: 600px) {
  .panel {
    padding: 16px;
  }

  .panel-title {
    align-items: flex-start;
    flex-wrap: wrap;
  }

  .title-content {
    flex: 1;
  }

  .refresh-btn {
    width: 100%;
    margin-left: 0;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .day-header {
    padding: 13px 14px;
  }

  .schedule-card {
    grid-template-columns: 1fr;
    gap: 14px;
    padding: 16px;
  }

  .schedule-details {
    grid-column: auto;
  }

  .time-box {
    flex-direction: row;
    align-items: center;
    gap: 7px;
  }

  .time-box svg {
    margin-bottom: 0;
  }
}
</style>