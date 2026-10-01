import { createRouter, createWebHashHistory } from 'vue-router';

// Public Views
import HomeView from '../views/public/HomeView.vue';
import LoginView from '../views/public/LoginView.vue';
import AdmissionLoginView from '../views/public/AdmissionLoginView.vue';
import StaffLoginView from '../views/public/StaffLoginView.vue';
import RegisterView from '../views/public/RegisterView.vue';
import SmtpSimulatorView from '../views/public/SmtpSimulatorView.vue';

// Role Portals
import AdmissionProcedureView from '../views/applicant/AdmissionProcedureView.vue';
import RegistrarDashboardView from '../views/registrar/RegistrarDashboardView.vue';
import TreasuryDashboardView from '../views/treasury/TreasuryDashboardView.vue';
import CoordinatorDashboardView from '../views/coordinator/CoordinatorDashboardView.vue';
import RecordsDashboardView from '../views/records/RecordsDashboardView.vue';
import StudentDashboardView from '../views/student/StudentDashboardView.vue';
import TeacherDashboardView from '../views/teacher/TeacherDashboardView.vue';
import AdminDashboardView from '../views/admin/AdminDashboardView.vue';

const routes = [
  { path: '/', name: 'Home', component: HomeView },
  { path: '/login', name: 'Login', component: LoginView },
  { path: '/admission-login', name: 'AdmissionLogin', component: AdmissionLoginView },
  { path: '/applicant-login', redirect: '/admission-login' },
  { path: '/staff-login', name: 'StaffLogin', component: StaffLoginView },
  { path: '/staff', redirect: '/staff-login' },
  { path: '/register', name: 'Register', component: RegisterView },
  { path: '/smtp-simulator', name: 'SmtpSimulator', component: SmtpSimulatorView },
  { path: '/smtp-test', redirect: '/smtp-simulator' },

  // Portals with Role Guards
  { path: '/admission', name: 'AdmissionProcedure', component: AdmissionProcedureView, meta: { requiresAuth: true, roles: ['applicant'] } },
  { path: '/registrar', name: 'RegistrarDashboard', component: RegistrarDashboardView, meta: { requiresAuth: true, roles: ['registrar', 'admin'] } },
  { path: '/treasury', name: 'TreasuryDashboard', component: TreasuryDashboardView, meta: { requiresAuth: true, roles: ['treasury', 'admin'] } },
  { path: '/coordinator', name: 'CoordinatorDashboard', component: CoordinatorDashboardView, meta: { requiresAuth: true, roles: ['coordinator', 'admin'] } },
  { path: '/records', name: 'RecordsDashboard', component: RecordsDashboardView, meta: { requiresAuth: true, roles: ['records', 'admin', 'registrar'] } },
  { path: '/teacher', name: 'TeacherDashboard', component: TeacherDashboardView, meta: { requiresAuth: true, roles: ['teacher'] } },
  { path: '/student', name: 'StudentDashboard', component: StudentDashboardView, meta: { requiresAuth: true, roles: ['student'] } },
  { path: '/admin', name: 'AdminDashboard', component: AdminDashboardView, meta: { requiresAuth: true, roles: ['admin'] } },

  // Fallback
  { path: '/:pathMatch(.*)*', redirect: '/' }
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 };
  }
});

export const getRoleRouteName = (roleSlug) => {
  switch (roleSlug) {
    case 'applicant': return 'AdmissionProcedure';
    case 'registrar': return 'RegistrarDashboard';
    case 'treasury': return 'TreasuryDashboard';
    case 'coordinator': return 'CoordinatorDashboard';
    case 'records': return 'RecordsDashboard';
    case 'teacher': return 'TeacherDashboard';
    case 'student': return 'StudentDashboard';
    case 'admin': return 'AdminDashboard';
    default: return 'Home';
  }
};

export const getRoleRoutePath = (roleSlug) => {
  switch (roleSlug) {
    case 'applicant': return '/admission';
    case 'registrar': return '/registrar';
    case 'treasury': return '/treasury';
    case 'coordinator': return '/coordinator';
    case 'records': return '/records';
    case 'teacher': return '/teacher';
    case 'student': return '/student';
    case 'admin': return '/admin';
    default: return '/';
  }
};

router.beforeEach((to, from, next) => {
  const token = sessionStorage.getItem('sia_auth_token') || localStorage.getItem('sia_auth_token');
  const userJson = sessionStorage.getItem('sia_auth_user') || localStorage.getItem('sia_auth_user');
  const user = userJson ? JSON.parse(userJson) : null;

  // 1. If logged in and trying to access public landing / auth pages (Home, Login, StaffLogin, Register)
  if (token && user && (['Home', 'Login', 'StaffLogin', 'Register'].includes(to.name) || to.path === '/')) {
    const targetRoute = getRoleRouteName(user.role_slug);
    if (targetRoute !== 'Home') {
      next({ name: targetRoute });
      return;
    }
  }

  // 2. If route requires auth and no token
  if (to.meta.requiresAuth && !token) {
    next({ name: 'Login' });
    return;
  }

  // 3. If route specifies allowed roles and current user is unauthorized
  if (to.meta.roles && user && !to.meta.roles.includes(user.role_slug) && user.role_slug !== 'admin') {
    const targetRoute = getRoleRouteName(user.role_slug);
    next({ name: targetRoute });
    return;
  }

  next();
});

export default router;
