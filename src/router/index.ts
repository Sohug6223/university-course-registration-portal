import { createRouter, createWebHistory } from "vue-router";

import Login from "@/views/auth/login.vue";
import ForgotPassword from "@/views/ForgotPassword.vue";
import ChangePassword from "@/views/ChangePassword.vue";

import StudentDashboard from "@/views/student/StudentDashboard.vue";
import Registration from "@/views/student/Registration.vue";
import MyCourses from "@/views/student/MyCourses.vue";
import Routine from "@/views/student/Routine.vue";
import StudentResults from "@/views/student/StudentResults.vue";
import StudentNotices from "@/views/student/StudentNotices.vue";
import StudentProfile from "@/views/student/StudentProfile.vue";

import AdminProfile from "@/views/admin/AdminProfile.vue";
import Students from "@/views/admin/Students.vue";
import Courses from "@/views/admin/Courses.vue";
import Department from "@/views/admin/Department.vue";
import Results from "@/views/admin/Results.vue";
import Prerequisites from "@/views/admin/Prerequisites.vue";
import ClassSchedule from "@/views/admin/ClassSchedule.vue";
import Enrollments from "@/views/admin/Enrollments.vue";
import Notices from "@/views/admin/Notices.vue";

const router = createRouter({
  history: createWebHistory(),

  routes: [
    /*  PUBLIC  */

    {
      path: "/",
      name: "login",
      component: Login,
    },

    {
      path: "/forgot-password",
      name: "ForgotPassword",
      component: ForgotPassword,
    },

    /*  COMMON AUTHENTICATED  */

    {
      path: "/change-password",
      name: "ChangePassword",
      component: ChangePassword,
      meta: {
        requiresAuth: true,
      },
    },

    /*  STUDENT  */

    {
      path: "/student",
      name: "student-dashboard",
      component: StudentDashboard,
      meta: {
        requiresAuth: true,
        role: "student",
      },
    },

    {
      path: "/student/registration",
      name: "student-registration",
      component: Registration,
      meta: {
        requiresAuth: true,
        role: "student",
      },
    },

    {
      path: "/student/my-courses",
      name: "student-my-courses",
      component: MyCourses,
      meta: {
        requiresAuth: true,
        role: "student",
      },
    },

    {
      path: "/student/routine",
      name: "student-routine",
      component: Routine,
      meta: {
        requiresAuth: true,
        role: "student",
      },
    },

    {
      path: "/student/results",
      name: "student-results",
      component: StudentResults,
      meta: {
        requiresAuth: true,
        role: "student",
      },
    },

    {
      path: "/student/notices",
      name: "StudentNotices",
      component: StudentNotices,
      meta: {
        requiresAuth: true,
        role: "student",
      },
    },

    {
      path: "/student/profile",
      name: "StudentProfile",
      component: StudentProfile,
      meta: {
        requiresAuth: true,
        role: "student",
      },
    },

    /* ADMIN  */

    {
      path: "/admin",
      name: "AdminDashboard",
      component: () =>
        import("@/views/admin/AdminDashboard.vue"),
      meta: {
        requiresAuth: true,
        role: "admin",
      },
    },

    {
      path: "/admin/profile",
      name: "AdminProfile",
      component: AdminProfile,
      meta: {
        requiresAuth: true,
        role: "admin",
      },
    },

    {
      path: "/admin/students",
      name: "AdminStudents",
      component: Students,
      meta: {
        requiresAuth: true,
        role: "admin",
      },
    },

    {
      path: "/admin/courses",
      name: "admin-courses",
      component: Courses,
      meta: {
        requiresAuth: true,
        role: "admin",
      },
    },

    {
      path: "/admin/department",
      name: "admin-department",
      component: Department,
      meta: {
        requiresAuth: true,
        role: "admin",
      },
    },

    {
      path: "/admin/results",
      name: "admin-results",
      component: Results,
      meta: {
        requiresAuth: true,
        role: "admin",
      },
    },

    {
      path: "/admin/prerequisites",
      name: "admin-prerequisites",
      component: Prerequisites,
      meta: {
        requiresAuth: true,
        role: "admin",
      },
    },

    {
      path: "/admin/schedules",
      name: "admin-schedules",
      component: ClassSchedule,
      meta: {
        requiresAuth: true,
        role: "admin",
      },
    },

    {
      path: "/admin/enrollments",
      name: "admin-enrollments",
      component: Enrollments,
      meta: {
        requiresAuth: true,
        role: "admin",
      },
    },

    {
      path: "/admin/notices",
      name: "admin-notices",
      component: Notices,
      meta: {
        requiresAuth: true,
        role: "admin",
      },
    },
  ],
});

/*  ROUTE GUARD  */

router.beforeEach((to) => {
  const token =
    localStorage.getItem("auth_token") ||
    sessionStorage.getItem("auth_token");

  const storedUser =
    localStorage.getItem("user") ||
    sessionStorage.getItem("user");

  let user: {
    id?: number;
    name?: string;
    user_id?: string;
    email?: string;
    role?: string;
  } | null = null;

  try {
    user = storedUser ? JSON.parse(storedUser) : null;
  } catch {
    user = null;
  }

  /* 
     Protected route without login
   */

  if (to.meta.requiresAuth && !token) {
    return "/";
  }

  /* 
     Protected route without user info
   */

  if (to.meta.requiresAuth && !user) {
    localStorage.removeItem("auth_token");
    localStorage.removeItem("user");
    sessionStorage.removeItem("auth_token");
    sessionStorage.removeItem("user");

    return "/";
  }

  /* 
     Role protection
   */

  const requiredRole = to.meta.role as
    | "admin"
    | "student"
    | undefined;

  if (requiredRole && user?.role !== requiredRole) {
    if (user?.role === "admin") {
      return "/admin";
    }

    if (user?.role === "student") {
      return "/student";
    }

    return "/";
  }

  /* 
     Logged-in user visiting login
   */

  if (to.path === "/" && token && user) {
    if (user.role === "admin") {
      return "/admin";
    }

    if (user.role === "student") {
      return "/student";
    }
  }

  return true;
});

export default router;