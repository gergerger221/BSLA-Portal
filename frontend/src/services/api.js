// frontend/src/services/api.js

// Determine base API URL: dynamically detect project root folder or domain root (InfinityFree)
const getApiBase = () => {
  if (window.location.hostname === 'localhost' && window.location.port === '5173') {
    return 'http://localhost/sia-project2/backend/api/index.php';
  }
  const path = window.location.pathname;
  if (path.includes('/sia-project2/')) {
    return '/sia-project2/backend/api/index.php';
  }
  if (path.includes('/sia-project/')) {
    return '/sia-project/backend/api/index.php';
  }
  // Production root domain (e.g. InfinityFree https://your-site.infinityfreeapp.com/)
  return '/backend/api/index.php';
};

const API_BASE = getApiBase();

export const BASE_URL = `${window.location.origin}${API_BASE.replace(/\/api\/index\.php$/, '/')}`;
export const getFileUrl = (path) => {
  if (!path) return '';
  if (path.startsWith('http://') || path.startsWith('https://')) return path;
  const cleanPath = path.replace(/^\/+/, '');
  return `${BASE_URL}${cleanPath}`;
};


export async function apiRequest(endpoint, options = {}) {
  const token = sessionStorage.getItem('sia_auth_token') || localStorage.getItem('sia_auth_token');
  const headers = {
    ...(options.headers || {})
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
    headers['X-Auth-Token'] = token;
    headers['X-Authorization'] = `Bearer ${token}`;
  }

  // If payload is FormData (file uploads), do NOT manually set Content-Type
  if (!(options.body instanceof FormData) && options.body && typeof options.body === 'object') {
    headers['Content-Type'] = 'application/json';
    options.body = JSON.stringify(options.body);
  }

  const url = `${API_BASE}?route=${endpoint}`;
  try {
    const response = await fetch(url, {
      ...options,
      headers,
      credentials: 'include'
    });

    const text = await response.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch (parseErr) {
      if (text.includes('slowAES') || text.includes('__test') || text.includes('aes.js')) {
        throw new Error('Security verification in progress. Please refresh the page and try logging in again.');
      }
      const cleanError = text.replace(/<[^>]*>?/gm, '').trim();
      throw new Error(cleanError.substring(0, 150) || 'Server returned an invalid non-JSON response.');
    }

    if (!response.ok || !data.success) {
      if (response.status === 401) {
        sessionStorage.removeItem('sia_auth_token');
        sessionStorage.removeItem('sia_auth_user');
        localStorage.removeItem('sia_auth_token');
        localStorage.removeItem('sia_auth_user');
      }
      throw new Error(data.message || data.error || 'An error occurred during the request.');
    }

    return data;
  } catch (error) {
    console.error(`API Error on [${endpoint}]:`, error);
    throw error;
  }
}

export default {
  // Helpers
  getFileUrl,

  // Auth
  login: (credentials) => apiRequest('auth/login', { method: 'POST', body: credentials }),
  registerApplicant: (data) => apiRequest('auth/register-applicant', { method: 'POST', body: data }),
  getMe: () => apiRequest('auth/me'),
  logout: () => apiRequest('auth/logout', { method: 'POST' }),

  // Admission
  getMyApplication: () => apiRequest('admission/my-application'),
  updateApplication: (data) => apiRequest('admission/update', { method: 'POST', body: data }),
  uploadDocument: (formData) => apiRequest('admission/upload-document', { method: 'POST', body: formData }),
  setDocumentSubmissionMode: (data) => apiRequest('admission/set-document-mode', { method: 'POST', body: data }),
  deleteDocument: (documentId) => apiRequest('admission/delete-document', { method: 'POST', body: { document_id: documentId } }),
  submitApplication: () => apiRequest('admission/submit', { method: 'POST' }),
  getAcademicOptions: () => apiRequest('admission/academic-options'),
  checkoutPayment: (data) => apiRequest('admission/checkout-payment', { method: 'POST', body: data }),
  switchPaymentMode: (data) => apiRequest('admission/switch-payment-mode', { method: 'POST', body: data }),
  createPaymongoCheckout: (data) => apiRequest('paymongo/create-checkout', { method: 'POST', body: data }),
  verifyPaymongoSession: (sessionId) => apiRequest(`paymongo/verify-session&session_id=${encodeURIComponent(sessionId)}`),

  // Registrar
  getApplications: (params = '') => apiRequest(`registrar/applications${params ? '&' + params : ''}`),
  getApplicationDetails: (id) => apiRequest(`registrar/application-details&id=${id}`),
  verifyDocument: (data) => apiRequest('registrar/verify-document', { method: 'POST', body: data }),
  batchVerifyDocuments: (data) => apiRequest('registrar/batch-verify-documents', { method: 'POST', body: data }),
  approveAndQueue: (data) => apiRequest('registrar/approve-and-queue', { method: 'POST', body: data }),
  undoApproval: (applicationId) => apiRequest('registrar/undo-approval', { method: 'POST', body: { application_id: applicationId } }),
  getEnrollmentQueue: (params = '') => apiRequest(`registrar/queue${params ? '&' + params : ''}`),

  // Treasury
  getAssessments: (params = '') => apiRequest(`treasury/assessments${params ? '&' + params : ''}`),
  getAssessmentDetails: (id) => apiRequest(`treasury/assessment-details&id=${id}`),
  processPayment: (data) => apiRequest('treasury/process-payment', { method: 'POST', body: data }),
  getOnlinePaymentVerifications: (params = '') => apiRequest(`treasury/online-payments${params ? '&' + params : ''}`),
  verifyOnlinePayment: (data) => apiRequest('treasury/verify-online-payment', { method: 'POST', body: data }),
  getFeeStructures: () => apiRequest('treasury/fee-structures'),

  // Coordinator & Sectioning
  getCurriculum: (params = '') => apiRequest(`coordinator/curriculum${params ? '&' + params : ''}`),
  carryOverSubjects: (data) => apiRequest('coordinator/carry-over-subjects', { method: 'POST', body: data }),
  updateSubjectApproval: (data) => apiRequest('coordinator/update-subject-approval', { method: 'POST', body: data }),
  toggleCurriculumLock: (syId) => apiRequest('coordinator/toggle-curriculum-lock', { method: 'POST', body: { school_year_id: syId } }),
  declareShsSemester: (data) => apiRequest('coordinator/declare-shs-semester', { method: 'POST', body: data }),
  saveSubject: (data) => apiRequest('coordinator/save-subject', { method: 'POST', body: data }),
  deleteSubject: (id) => apiRequest('coordinator/delete-subject', { method: 'POST', body: { id } }),
  batchDeleteSubjects: (data) => apiRequest('coordinator/batch-delete-subjects', { method: 'POST', body: data }),
  saveStrand: (data) => apiRequest('coordinator/save-strand', { method: 'POST', body: data }),
  toggleStrandStatus: (data) => apiRequest('coordinator/toggle-strand-status', { method: 'POST', body: data }),
  deleteStrand: (id) => apiRequest('coordinator/delete-strand', { method: 'POST', body: { id } }),
  getSections: () => apiRequest('coordinator/sections'),
  saveSection: (data) => apiRequest('coordinator/save-section', { method: 'POST', body: data }),
  getSectionStudents: (sectionId) => apiRequest(`coordinator/section-students&section_id=${sectionId}`),
  transferStudentSection: (data) => apiRequest('coordinator/transfer-section', { method: 'POST', body: data }),

  // Records & Archives
  getStudentRecords: (params = '') => apiRequest(`records/students${params ? '&' + params : ''}`),
  getStudentTranscript: (studentId) => apiRequest(`records/transcript&student_id=${studentId}`),
  getDocumentRequests: () => apiRequest('records/document-requests'),
  saveDocumentRequest: (data) => apiRequest('records/save-document-request', { method: 'POST', body: data }),
  updateRequestStatus: (data) => apiRequest('records/update-request-status', { method: 'POST', body: data }),
  getSchoolForm1: (sectionId) => apiRequest(`records/school-form-1&section_id=${sectionId}`),
  getSchoolForm5: (sectionId) => apiRequest(`records/school-form-5&section_id=${sectionId}`),
  getHonorRoll: (params = '') => apiRequest(`records/honor-roll${params ? '&' + params : ''}`),
  updateTransfereeF137: (data) => apiRequest('records/update-transferee-f137', { method: 'POST', body: data }),

  // Student
  getStudentDashboard: () => apiRequest('student/dashboard'),
  getStudentRequirements: () => apiRequest('student/requirements'),
  uploadStudentFollowUpDoc: (formData) => apiRequest('student/upload-followup-doc', { method: 'POST', body: formData }),
  createStudentPaymongoCheckout: (data) => apiRequest('student/paymongo-checkout', { method: 'POST', body: data }),
  verifyStudentPaymongoSession: (sessionId) => apiRequest(`student/paymongo-verify&session_id=${sessionId}`),

  // Scheduler & School Events
  getSectionSchedule: (sectionId, semester = '') => apiRequest(`schedules/section&section_id=${sectionId}${semester ? '&semester=' + encodeURIComponent(semester) : ''}`),
  saveSchedule: (data) => apiRequest('schedules/save', { method: 'POST', body: data }),
  deleteSchedule: (scheduleId) => apiRequest('schedules/delete', { method: 'POST', body: { id: scheduleId } }),
  getEvents: (params = '') => apiRequest(`events/list${params ? '&' + params : ''}`),
  saveEvent: (data) => apiRequest('events/save', { method: 'POST', body: data }),
  deleteEvent: (eventId) => apiRequest('events/delete', { method: 'POST', body: { id: eventId } }),

  // Admin
  getDashboardStats: () => apiRequest('admin/stats'),
  getSchoolYears: () => apiRequest('admin/school-years'),
  getRolloverPreflightCheck: (targetSyId) => apiRequest(`admin/school-years/preflight&target_school_year_id=${targetSyId}`),
  executeSchoolYearRollover: (data) => apiRequest('admin/school-years/rollover', { method: 'POST', body: data }),
  toggleSchoolYearLock: (syId) => apiRequest('admin/toggle-school-year-lock', { method: 'POST', body: { school_year_id: syId } }),
  toggleSchoolYearSemester: (syId) => apiRequest('admin/toggle-semester', { method: 'POST', body: { school_year_id: syId } }),
  toggleAdminCurriculumLock: (syId) => apiRequest('admin/toggle-curriculum-lock', { method: 'POST', body: { school_year_id: syId } }),
  saveSchoolYear: (data) => apiRequest('admin/save-school-year', { method: 'POST', body: data }),
  deleteSchoolYear: (syId) => apiRequest('admin/delete-school-year', { method: 'POST', body: { school_year_id: syId } }),
  setActiveSchoolYear: (syId) => apiRequest('admin/set-active-school-year', { method: 'POST', body: { school_year_id: syId } }),
  getUsers: (role = '') => apiRequest(`admin/users${role ? '&role=' + role : ''}`),
  saveUser: (data) => apiRequest('admin/save-user', { method: 'POST', body: data }),

  // Teacher Portal
  getTeacherDashboard: () => apiRequest('teacher/dashboard'),
  getTeacherClassStudents: (sectionId, subjectId) => apiRequest(`teacher/class-students&section_id=${sectionId}&subject_id=${subjectId}`),
  saveTeacherGrades: (data) => apiRequest('teacher/save-grades', { method: 'POST', body: data }),
  getTeacherAdvisorySection: (sectionId = '') => apiRequest(`teacher/advisory-section${sectionId ? `&section_id=${sectionId}` : ''}`),
  saveTeacherAdvisoryValues: (data) => apiRequest('teacher/save-values', { method: 'POST', body: data }),
  saveTeacherAttendance: (data) => apiRequest('teacher/save-attendance', { method: 'POST', body: data }),

  // SMTP Testing Simulator
  getSmtpConfig: () => apiRequest('auth/smtp-config'),
  testSmtp: (data) => apiRequest('auth/test-smtp', { method: 'POST', body: data }),

  // LMS (Learning Management System)
  getLmsClassContent: (sectionId, subjectId) => apiRequest(`lms/class-content&section_id=${sectionId}&subject_id=${subjectId}`),
  saveLmsAnnouncement: (data) => apiRequest('lms/save-announcement', { method: 'POST', body: data }),
  deleteLmsAnnouncement: (id) => apiRequest('lms/delete-announcement', { method: 'POST', body: { id } }),
  uploadLmsModule: (formData) => apiRequest('lms/upload-module', { method: 'POST', body: formData }),
  deleteLmsModule: (id) => apiRequest('lms/delete-module', { method: 'POST', body: { id } }),
  saveLmsAssignment: (data) => apiRequest('lms/save-assignment', { method: 'POST', body: data }),
  deleteLmsAssignment: (id) => apiRequest('lms/delete-assignment', { method: 'POST', body: { id } }),
  submitLmsAssignment: (formDataOrData) => apiRequest('lms/submit-assignment', { method: 'POST', body: formDataOrData }),
  getLmsAssignmentSubmissions: (assignmentId) => apiRequest(`lms/assignment-submissions&assignment_id=${assignmentId}`),
  gradeLmsSubmission: (data) => apiRequest('lms/grade-submission', { method: 'POST', body: data })
};
