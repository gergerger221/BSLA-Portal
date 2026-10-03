<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Top Header & Actions -->
    <div class="no-print flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6 pb-5 border-b border-slate-200">
      <div>
        <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
          <FileText class="w-3.5 h-3.5 text-blue-600" />
          <span>Registrar Admissions & Enrollment Management</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Student Requirement Verification & Queue</h1>
        <p class="text-xs text-slate-500 mt-0.5">Review incoming student credentials, approve verified applicants, and manage the enrollment queue.</p>
      </div>

      <div class="flex items-center space-x-2.5 shrink-0">
        <div class="hidden sm:flex items-center space-x-2 bg-blue-50 text-blue-800 border border-blue-200 px-3.5 py-1.5 rounded-xl text-xs font-medium font-mono">
          <span>Applications:</span>
          <strong class="text-blue-900 font-bold">{{ applications.length }}</strong>
          <span class="text-blue-300">•</span>
          <span>In Queue:</span>
          <strong class="text-blue-900 font-bold">{{ queueList.length }}</strong>
          <span class="text-blue-300">•</span>
          <span>Enrolled:</span>
          <strong class="text-blue-900 font-bold">{{ enrolledSummary.total_enrolled || enrolledStudents.length }}</strong>
        </div>
        <button 
          @click="loadApplications(); loadQueue();"
          class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-medium shadow-2xs transition flex items-center space-x-1.5 cursor-pointer"
        >
          <RefreshCw class="w-3.5 h-3.5" />
          <span>Refresh</span>
        </button>
      </div>
    </div>

    <!-- Alert Notifications -->
    <div v-if="successMessage" class="p-4 rounded-2xl bg-emerald-950/80 border border-emerald-500 text-emerald-300 text-xs mb-6 flex items-center justify-between shadow-md">
      <span>{{ successMessage }}</span>
      <button @click="successMessage = ''" class="font-bold cursor-pointer">✕</button>
    </div>

    <div v-if="errorMessage" class="p-4 rounded-2xl bg-rose-950/80 border border-rose-500 text-rose-300 text-xs mb-6 flex items-center justify-between shadow-md">
      <span>{{ errorMessage }}</span>
      <button @click="errorMessage = ''" class="font-bold cursor-pointer">✕</button>
    </div>

    <!-- Tab Switcher Navigation Bar -->
    <div class="no-print flex items-center space-x-2 mb-6 border-b border-slate-200 pb-3 flex-wrap gap-y-2">
      <button 
        @click="switchTab('applications')" 
        :class="activeTab === 'applications' ? 'bg-blue-900 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-semibold'"
        class="px-4 py-2 rounded-xl text-xs transition cursor-pointer flex items-center space-x-2"
      >
        <FileCheck class="w-4 h-4" />
        <span>Admission Applications</span>
        <span class="px-2 py-0.5 rounded-full text-[10px] bg-white/20 text-white font-mono font-bold">{{ applications.length }}</span>
      </button>

      <button 
        @click="switchTab('queue')" 
        :class="activeTab === 'queue' ? 'bg-blue-900 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-semibold'"
        class="px-4 py-2 rounded-xl text-xs transition cursor-pointer flex items-center space-x-2"
      >
        <ListOrdered class="w-4 h-4" />
        <span>Enrollment Queue</span>
        <span class="px-2 py-0.5 rounded-full text-[10px] bg-white/20 text-white font-mono font-bold">{{ queueList.length }}</span>
      </button>

      <button 
        @click="switchTab('enrolled_docs')" 
        :class="activeTab === 'enrolled_docs' ? 'bg-blue-900 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-semibold'"
        class="px-4 py-2 rounded-xl text-xs transition cursor-pointer flex items-center space-x-2"
      >
        <FolderArchive class="w-4 h-4" />
        <span>Enrolled Students Documents</span>
        <span v-if="enrolledSummary.needs_review_count > 0" class="px-2 py-0.5 rounded-full text-[10px] bg-amber-500 text-white font-bold animate-pulse">
          {{ enrolledSummary.needs_review_count }} Needs Review
        </span>
        <span v-else class="px-2 py-0.5 rounded-full text-[10px] bg-slate-200 text-slate-700 font-mono font-bold">
          {{ enrolledStudents.length }}
        </span>
      </button>
    </div>

    <!-- TAB 1: ADMISSION APPLICATIONS REVIEW -->
    <div v-if="activeTab === 'applications'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
      <!-- Filter Bar -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
        <div class="relative w-full sm:w-80">
          <input 
            v-model="searchQuery" 
            @input="loadApplications"
            type="text" 
            placeholder="Search by name, LRN, or App #..."
            class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500"
          />
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
        </div>

        <div class="flex items-center space-x-2 w-full sm:w-auto">
          <select v-model="filterStatus" @change="loadApplications" class="px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white">
            <option value="">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Under Review">Under Review</option>
            <option value="Approved">Approved</option>
            <option value="Queued for Enrollment">Queued for Enrollment</option>
            <option value="Enrolled">Enrolled</option>
          </select>
          <button @click="loadApplications" class="p-2 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-600" title="Refresh">
            <RefreshCw class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Applications Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse min-w-[700px]">
          <thead>
            <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
              <th class="p-3.5">App #</th>
              <th class="p-3.5">Applicant Name</th>
              <th class="p-3.5">DepEd LRN</th>
              <th class="p-3.5">Grade & Strand</th>
              <th class="p-3.5">Docs Uploaded</th>
              <th class="p-3.5">Status</th>
              <th class="p-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="app in applications" :key="app.id" class="hover:bg-slate-50/80 transition">
              <td class="p-3.5 font-mono font-bold text-slate-900">{{ app.application_no }}</td>
              <td class="p-3.5">
                <div class="font-bold text-slate-800">{{ app.last_name }}, {{ app.first_name }} {{ app.middle_name || '' }}</div>
                <div class="text-[11px] text-slate-400">{{ app.email }} • {{ app.contact_number }}</div>
              </td>
              <td class="p-3.5 font-mono">{{ app.lrn || 'N/A' }}</td>
              <td class="p-3.5">
                <span class="font-semibold text-slate-800">{{ app.grade_level_name }}</span>
                <span v-if="app.strand_code" class="ml-1 px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold text-[10px]">
                  {{ app.strand_code }}
                </span>
              </td>
              <td class="p-3.5">
                <span class="font-bold" :class="app.verified_doc_count >= 2 ? 'text-emerald-600' : 'text-amber-600'">
                  {{ app.verified_doc_count }} / {{ app.doc_count }} Verified
                </span>
              </td>
              <td class="p-3.5">
                <span :class="getStatusBadgeClass(app.status)" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">
                  {{ app.status }}
                </span>
              </td>
              <td class="p-3.5 text-right">
                <button 
                  @click="openReviewModal(app.id)" 
                  class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-blue-900 hover:bg-blue-800 text-white shadow-xs transition cursor-pointer"
                >
                  Evaluate Docs
                </button>
              </td>
            </tr>
            <tr v-if="applications.length === 0">
              <td colspan="7" class="p-8 text-center text-slate-400 text-xs">
                No admission applications found matching the selected filter.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 2: LIVE ENROLLMENT QUEUE -->
    <div v-if="activeTab === 'queue'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-base font-bold text-slate-800">
            {{ queueFilterStatus === 'active' ? 'Active Enrollment Queue' : queueFilterStatus === 'completed' ? 'Completed / Enrolled Queue History' : 'All Enrollment Queues' }}
          </h2>
          <p class="text-xs text-slate-500">
            {{ queueFilterStatus === 'active' ? 'Approved students waiting for Assessment Form generation and Cashier payment.' : 'Historical queue records and payment statuses.' }}
          </p>
        </div>
        
        <div class="flex items-center space-x-2">
          <select 
            v-model="queueFilterStatus" 
            @change="loadQueue" 
            class="px-3 py-1.5 rounded-xl border border-slate-300 text-xs bg-white font-medium focus:ring-2 focus:ring-blue-500"
          >
            <option value="active">Active Pending Queue</option>
            <option value="completed">Completed / Enrolled</option>
            <option value="all">All Queue Entries</option>
          </select>

          <button @click="loadQueue" class="px-3 py-1.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-xs flex items-center space-x-1">
            <RefreshCw class="w-3.5 h-3.5" />
            <span>Refresh Queue</span>
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse min-w-[700px]">
          <thead>
            <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
              <th class="p-3.5">Queue #</th>
              <th class="p-3.5">Student Name</th>
              <th class="p-3.5">Grade & Strand</th>
              <th class="p-3.5">Assigned Section</th>
              <th class="p-3.5">Assessment Net</th>
              <th class="p-3.5">Queue Status</th>
              <th class="p-3.5">Enrollment Status</th>
              <th class="p-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="q in queueList" :key="q.id" class="hover:bg-slate-50 transition">
              <td class="p-3.5 font-bold font-mono text-blue-600 text-sm">#{{ q.queue_number }}</td>
              <td class="p-3.5 font-bold text-slate-800">{{ q.last_name }}, {{ q.first_name }}</td>
              <td class="p-3.5">{{ q.grade_level_name }} {{ q.strand_code ? '(' + q.strand_code + ')' : '' }}</td>
              <td class="p-3.5 font-semibold text-slate-700">{{ q.section_name || 'Pending Section' }}</td>
              <td class="p-3.5 font-mono font-bold text-slate-900">
                ₱{{ (q.net_payable || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}
              </td>
              <td class="p-3.5">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                  {{ q.status }}
                </span>
              </td>
              <td class="p-3.5">
                <span :class="getStatusBadgeClass(q.enrollment_status)" class="px-2 py-0.5 rounded text-[10px] font-bold">
                  {{ q.enrollment_status }}
                </span>
              </td>
              <td class="p-3.5 text-right whitespace-nowrap space-x-1.5">
                <button 
                  type="button" 
                  @click="openPrintForm(q.application_id)" 
                  class="px-2.5 py-1.5 rounded-xl font-bold bg-slate-900 text-white hover:bg-slate-800 text-[11px] shadow-sm transition inline-flex items-center space-x-1"
                  title="Print Official Student Pre-Enrollment / Registration Form"
                >
                  <Printer class="w-3.5 h-3.5" />
                  <span>Print Form</span>
                </button>
                <button 
                  v-if="q.enrollment_status !== 'Enrolled' && q.payment_status !== 'Paid'"
                  type="button" 
                  @click="openUndoModal(q.application_id, q.application_no, `${q.first_name} ${q.last_name}`)" 
                  class="px-2.5 py-1.5 rounded-xl font-bold bg-amber-50 text-amber-800 border border-amber-300 hover:bg-amber-100 text-[11px] shadow-sm transition inline-flex items-center space-x-1"
                  title="Undo approval and return applicant to review"
                >
                  <RotateCcw class="w-3.5 h-3.5 text-amber-700" />
                  <span>Undo Approval</span>
                </button>
                <span v-else class="text-[10px] text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                  Enrolled
                </span>
              </td>
            </tr>
            <tr v-if="queueList.length === 0">
              <td colspan="8" class="p-8 text-center text-slate-400 text-xs">
                Enrollment queue is currently empty.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 3: ENROLLED STUDENTS DOCUMENTS EVALUATION (TO-FOLLOW CREDENTIALS) -->
    <div v-if="activeTab === 'enrolled_docs'" class="space-y-6">
      
      <!-- Top Metrics Banner -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Officially Enrolled</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200">Active</span>
          </div>
          <strong class="text-2xl font-bold text-slate-900 font-mono mt-1 block">{{ enrolledSummary.total_enrolled }}</strong>
          <span class="text-[10px] text-slate-400">Total enrolled student body</span>
        </div>

        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Needs Evaluation</span>
            <span v-if="enrolledSummary.needs_review_count > 0" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 animate-pulse">Action Required</span>
            <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-50 text-slate-500 border border-slate-200">Clear</span>
          </div>
          <strong class="text-2xl font-bold text-amber-600 font-mono mt-1 block">{{ enrolledSummary.needs_review_count }}</strong>
          <span class="text-[10px] text-slate-400">Uploaded to-follow documents pending review</span>
        </div>

        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">With To-Follow Docs</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-800 border border-purple-200">Pending</span>
          </div>
          <strong class="text-2xl font-bold text-purple-700 font-mono mt-1 block">{{ enrolledSummary.has_to_follow_count }}</strong>
          <span class="text-[10px] text-slate-400">Learners with outstanding credentials</span>
        </div>

        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Fully Compliant</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono">100%</span>
          </div>
          <strong class="text-2xl font-bold text-emerald-600 font-mono mt-1 block">{{ enrolledSummary.fully_compliant_count }}</strong>
          <span class="text-[10px] text-slate-400">All required documents verified</span>
        </div>
      </div>

      <!-- Main Enrolled Students Container -->
      <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-5">
        
        <!-- Controls & Search -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
          <div>
            <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
              <FolderArchive class="w-4 h-4 text-blue-900" />
              <span>Enrolled Student Document Verification</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
              Evaluate and verify to-follow documents submitted by enrolled students (Form 137/SF10, PSA, Form 138, etc.).
            </p>
          </div>

          <div class="flex items-center space-x-2 w-full sm:w-auto flex-wrap gap-y-2">
            <!-- Search -->
            <div class="relative w-full sm:w-72">
              <input 
                v-model="enrolledSearchQuery" 
                @input="loadEnrolledStudents"
                type="text" 
                placeholder="Search name, LRN, Student ID, section..." 
                class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500"
              />
              <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
            </div>

            <!-- Filter Status -->
            <select 
              v-model="enrolledFilter" 
              @change="loadEnrolledStudents" 
              class="px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white font-medium text-slate-700 focus:ring-2 focus:ring-blue-500 cursor-pointer"
            >
              <option value="all">All Enrolled Students</option>
              <option value="needs_review">Needs Review (Pending Uploads)</option>
              <option value="to_follow">Has Outstanding / To-Follow</option>
              <option value="compliant">Fully Compliant (All Verified)</option>
            </select>

            <button 
              @click="loadEnrolledStudents" 
              type="button"
              class="p-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-600 transition cursor-pointer"
              title="Reload Enrolled Students"
            >
              <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isLoadingEnrolled }" />
            </button>
          </div>
        </div>

        <!-- Enrolled Students Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left border-collapse min-w-[800px]">
            <thead>
              <tr class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider text-[11px] border-b border-slate-200">
                <th class="py-3 px-4 w-12 text-slate-400 font-mono">#</th>
                <th class="py-3 px-4">Learner Information</th>
                <th class="py-3 px-4">Class Section & Grade</th>
                <th class="py-3 px-4 text-center">Compliance Status</th>
                <th class="py-3 px-4">Submitted & To-Follow Items</th>
                <th class="py-3 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr 
                v-for="(st, idx) in enrolledStudents" 
                :key="st.enrollment_id" 
                class="hover:bg-slate-50/80 transition"
              >
                <td class="py-3.5 px-4 font-mono text-slate-400 text-[11px]">{{ idx + 1 }}</td>
                
                <!-- Learner Info -->
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-900 text-sm flex items-center space-x-1.5">
                    <span>{{ st.last_name }}, {{ st.first_name }} {{ st.middle_name || '' }}</span>
                    <span v-if="st.stats?.has_pending_uploads" class="w-2 h-2 rounded-full bg-amber-500 animate-ping" title="Has pending uploads"></span>
                  </div>
                  <div class="text-[11px] text-slate-500 font-mono mt-0.5 flex items-center space-x-2">
                    <span>ID: <strong class="text-blue-900">{{ st.student_no || 'Pending ID' }}</strong></span>
                    <span class="text-slate-300">•</span>
                    <span>LRN: <strong class="text-slate-700">{{ st.lrn || 'No LRN' }}</strong></span>
                  </div>
                </td>

                <!-- Section & Grade -->
                <td class="py-3.5 px-4">
                  <div class="font-semibold text-slate-800">{{ st.section_name || 'No Section' }}</div>
                  <div class="text-[11px] text-slate-500 mt-0.5">
                    {{ st.grade_level_name || 'Grade Level' }}
                    <span v-if="st.strand_code" class="text-blue-900 font-bold"> • {{ st.strand_code }}</span>
                  </div>
                </td>

                <!-- Compliance Status Progress -->
                <td class="py-3.5 px-4 text-center">
                  <div class="inline-flex flex-col items-center">
                    <span 
                      v-if="st.stats?.is_fully_compliant" 
                      class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center space-x-1"
                    >
                      <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600" />
                      <span>Complete ({{ st.stats?.verified }}/{{ st.stats?.total }})</span>
                    </span>
                    <span 
                      v-else-if="st.stats?.has_pending_uploads" 
                      class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 flex items-center space-x-1 animate-pulse"
                    >
                      <AlertCircle class="w-3.5 h-3.5 text-amber-600" />
                      <span>{{ st.stats?.pending }} Pending Review</span>
                    </span>
                    <span 
                      v-else 
                      class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200"
                    >
                      {{ st.stats?.verified || 0 }}/{{ st.stats?.total || 0 }} Verified
                    </span>

                    <!-- Mini Progress bar -->
                    <div class="w-24 bg-slate-100 rounded-full h-1.5 mt-1.5 overflow-hidden">
                      <div 
                        class="h-1.5 rounded-full transition-all"
                        :class="st.stats?.is_fully_compliant ? 'bg-emerald-500' : 'bg-blue-600'"
                        :style="{ width: `${st.stats?.total ? Math.round((st.stats.verified / st.stats.total) * 100) : 0}%` }"
                      ></div>
                    </div>
                  </div>
                </td>

                <!-- Submitted & To-Follow Items Pills -->
                <td class="py-3.5 px-4">
                  <div class="flex flex-wrap gap-1 max-w-xs">
                    <span 
                      v-for="d in st.documents" 
                      :key="d.id"
                      class="px-2 py-0.5 rounded-md text-[10px] font-semibold border flex items-center space-x-1"
                      :class="d.status === 'Verified' 
                        ? 'bg-emerald-50 text-emerald-800 border-emerald-200' 
                        : (d.status === 'Pending' || d.status === 'Under Review')
                        ? 'bg-amber-50 text-amber-900 border-amber-300 font-bold'
                        : d.status === 'Deficient' || d.status === 'Rejected'
                        ? 'bg-rose-50 text-rose-800 border-rose-200'
                        : 'bg-slate-100 text-slate-600 border-slate-200'"
                      :title="`${d.document_type}: ${d.status}`"
                    >
                      <span v-if="d.status === 'Verified'" class="text-emerald-600">✓</span>
                      <span v-else-if="d.status === 'Pending'" class="text-amber-600">●</span>
                      <span v-else-if="d.status === 'Deficient'" class="text-rose-600">✕</span>
                      <span class="truncate max-w-[120px]">{{ d.document_type }}</span>
                    </span>
                  </div>
                </td>

                <!-- Actions -->
                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                  <button 
                    @click="openEnrolledStudentModal(st)"
                    class="px-3.5 py-1.5 rounded-xl bg-blue-900 hover:bg-blue-800 text-white font-semibold text-xs shadow-2xs transition flex items-center space-x-1.5 ml-auto cursor-pointer"
                  >
                    <FolderArchive class="w-3.5 h-3.5" />
                    <span>Evaluate Docs</span>
                  </button>
                </td>
              </tr>

              <tr v-if="enrolledStudents.length === 0">
                <td colspan="6" class="p-8 text-center text-slate-400 text-xs">
                  No enrolled students matching the selected filter.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ENROLLED STUDENT DOCUMENTS EVALUATION MODAL -->
    <div v-if="selectedEnrolledStudent" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-3xl w-full max-h-[90vh] overflow-y-auto p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-6">
        
        <!-- Modal Top Bar -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
          <div class="space-y-0.5">
            <div class="flex items-center space-x-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200 font-mono">
                {{ selectedEnrolledStudent.enrollment_no }}
              </span>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                Officially Enrolled
              </span>
            </div>
            <h3 class="text-xl font-bold text-slate-900 pt-1">
              {{ selectedEnrolledStudent.first_name }} {{ selectedEnrolledStudent.middle_name || '' }} {{ selectedEnrolledStudent.last_name }}
            </h3>
            <p class="text-xs text-slate-500 font-mono">
              Student ID: <strong class="text-blue-950">{{ selectedEnrolledStudent.student_no }}</strong> • 
              LRN: <strong class="text-slate-800">{{ selectedEnrolledStudent.lrn || 'N/A' }}</strong> • 
              Class: <strong class="text-slate-800">{{ selectedEnrolledStudent.section_name }} ({{ selectedEnrolledStudent.grade_level_name }})</strong>
            </p>
          </div>
          <button 
            @click="selectedEnrolledStudent = null" 
            class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-sm cursor-pointer transition"
          >
            ✕
          </button>
        </div>

        <!-- Compliance Summary Pill -->
        <div class="p-4 rounded-2xl bg-blue-50/70 border border-blue-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
          <div>
            <div class="font-extrabold text-blue-950 text-sm">
              Document Compliance: {{ selectedEnrolledStudent.stats?.verified || 0 }} of {{ selectedEnrolledStudent.stats?.total || 0 }} Verified
            </div>
            <p class="text-slate-600 text-[11px] mt-0.5">
              Evaluate and verify to-follow documents submitted by this student. Once marked as Verified, the student's compliance record and DepEd archives are updated.
            </p>
          </div>
          <div class="shrink-0 font-mono font-bold text-blue-900 text-sm bg-white px-3 py-1.5 rounded-xl border border-blue-200">
            {{ selectedEnrolledStudent.stats?.total ? Math.round((selectedEnrolledStudent.stats.verified / selectedEnrolledStudent.stats.total) * 100) : 0 }}% Compliant
          </div>
        </div>

        <!-- Documents List -->
        <div class="space-y-3">
          <div 
            v-for="doc in selectedEnrolledStudent.documents" 
            :key="doc.id" 
            class="p-4 rounded-2xl border transition flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs"
            :class="doc.status === 'Verified' 
              ? 'border-emerald-200 bg-emerald-50/40' 
              : (doc.status === 'Pending' || doc.status === 'Under Review')
              ? 'border-amber-300 bg-amber-50/60 shadow-xs'
              : doc.status === 'Deficient' || doc.status === 'Rejected'
              ? 'border-rose-200 bg-rose-50/40'
              : 'border-slate-200 bg-slate-50/60'"
          >
            <!-- Left Info -->
            <div class="space-y-1 min-w-0">
              <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                <span class="font-bold text-slate-900 text-sm">{{ doc.document_type }}</span>
                
                <!-- Status Badge -->
                <span 
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                  :class="doc.status === 'Verified' 
                    ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' 
                    : (doc.status === 'Pending' || doc.status === 'Under Review')
                    ? 'bg-amber-100 text-amber-900 border border-amber-300 animate-pulse'
                    : doc.status === 'Deficient' || doc.status === 'Rejected'
                    ? 'bg-rose-100 text-rose-800 border border-rose-300'
                    : 'bg-slate-200 text-slate-700 border border-slate-300'"
                >
                  {{ doc.status }}
                </span>

                <!-- Submission Mode -->
                <span class="px-2 py-0.5 rounded text-[10px] bg-white border border-slate-200 text-slate-600 font-mono">
                  {{ doc.submission_mode || 'Submission Mode' }}
                </span>
              </div>

              <!-- File Info & Notes -->
              <div class="text-[11px] text-slate-600 space-y-0.5">
                <div v-if="doc.file_path" class="flex items-center space-x-2 text-slate-500">
                  <FileText class="w-3.5 h-3.5 text-blue-700 shrink-0" />
                  <span class="font-mono text-slate-700 truncate max-w-xs">{{ doc.original_filename || 'Uploaded Document File' }}</span>
                  <span v-if="doc.file_size" class="text-slate-400">({{ ((doc.file_size || 0) / 1024).toFixed(1) }} KB)</span>
                  <span v-if="doc.uploaded_at" class="text-slate-400">• {{ formatDate(doc.uploaded_at) }}</span>
                </div>
                <div v-else class="text-slate-400 italic">
                  No digital file attached (Promissory or Physical Submission).
                </div>

                <div v-if="doc.verification_notes" class="text-[11px] text-slate-700 font-medium bg-white/70 px-2 py-1 rounded border border-slate-200">
                  <strong>Notes:</strong> {{ doc.verification_notes }}
                </div>
              </div>
            </div>

            <!-- Right Actions -->
            <div class="flex items-center space-x-2 shrink-0 flex-wrap gap-y-1 sm:justify-end">
              <!-- View Document Button -->
              <button 
                v-if="doc.file_path"
                type="button" 
                @click="openPreviewDoc(doc)"
                class="px-3 py-1.5 rounded-xl border border-blue-200 bg-white hover:bg-blue-50 text-blue-900 font-bold text-xs transition flex items-center space-x-1 cursor-pointer shadow-2xs"
                title="Preview document in browser"
              >
                <Eye class="w-3.5 h-3.5 text-blue-700" />
                <span>View</span>
              </button>

              <!-- If Already Verified -->
              <div v-if="doc.status === 'Verified'" class="flex items-center space-x-1.5">
                <span class="text-emerald-700 font-bold text-xs flex items-center space-x-1 bg-emerald-100/60 px-2.5 py-1 rounded-xl">
                  <Check class="w-3.5 h-3.5 text-emerald-600" />
                  <span>Verified</span>
                </span>
                <button 
                  type="button" 
                  @click="docBeingMarkedDeficient = doc; deficiencyReasonInput = '';"
                  class="px-2.5 py-1 rounded-xl border border-slate-300 hover:bg-rose-50 hover:text-rose-700 text-slate-500 text-[11px] font-semibold transition cursor-pointer"
                  title="Mark Deficient if issue found"
                >
                  Re-evaluate
                </button>
              </div>

              <!-- If Pending / Deficient / To Follow -->
              <template v-else>
                <button 
                  type="button" 
                  @click="verifyEnrolledDoc(doc, 'Verified')"
                  :disabled="isVerifyingEnrolledDoc"
                  class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold text-xs transition flex items-center space-x-1 cursor-pointer shadow-2xs"
                >
                  <Check class="w-3.5 h-3.5" />
                  <span>Verify</span>
                </button>

                <button 
                  type="button" 
                  @click="docBeingMarkedDeficient = doc; deficiencyReasonInput = '';"
                  class="px-3.5 py-1.5 rounded-xl border border-rose-300 bg-white hover:bg-rose-50 text-rose-700 font-bold text-xs transition flex items-center space-x-1 cursor-pointer shadow-2xs"
                >
                  <span>Mark Deficient</span>
                </button>
              </template>
            </div>
          </div>
        </div>

        <!-- Inline Form: When Marking Document Deficient -->
        <div v-if="docBeingMarkedDeficient" class="p-4 rounded-2xl bg-rose-50 border border-rose-300 text-xs space-y-3">
          <div class="flex items-center justify-between">
            <span class="font-bold text-rose-950 flex items-center space-x-1.5">
              <AlertTriangle class="w-4 h-4 text-rose-600" />
              <span>Flag '{{ docBeingMarkedDeficient.document_type }}' as Deficient / Incomplete</span>
            </span>
            <button @click="docBeingMarkedDeficient = null" class="text-rose-500 hover:text-rose-700 font-bold text-sm cursor-pointer">✕</button>
          </div>
          <p class="text-slate-600 text-[11px]">
            Please enter a remark or reason so the student understands what to fix in their portal (e.g., "Blurry scan", "Missing second page", "Certified copy required").
          </p>
          <input 
            v-model="deficiencyReasonInput" 
            type="text" 
            placeholder="e.g. Unreadable / Missing back page / Wrong document uploaded"
            class="w-full px-3 py-2 rounded-xl border border-rose-300 bg-white text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none"
            @keyup.enter="verifyEnrolledDoc(docBeingMarkedDeficient, 'Deficient', deficiencyReasonInput)"
          />
          <div class="flex items-center justify-end space-x-2">
            <button 
              type="button" 
              @click="docBeingMarkedDeficient = null"
              class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="button" 
              @click="verifyEnrolledDoc(docBeingMarkedDeficient, 'Deficient', deficiencyReasonInput)"
              :disabled="isVerifyingEnrolledDoc"
              class="px-4 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold transition cursor-pointer shadow-xs"
            >
              Save Deficiency
            </button>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-end pt-4 border-t border-slate-100">
          <button 
            type="button" 
            @click="selectedEnrolledStudent = null"
            class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition cursor-pointer"
          >
            Close
          </button>
        </div>
      </div>
    </div>

    <!-- EVALUATION & APPROVAL MODAL -->
    <div v-if="selectedApp" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-3xl w-full max-h-[90vh] overflow-y-auto p-6 sm:p-8 shadow-2xl border border-slate-200">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
          <div>
            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
              {{ selectedApp.application_no }}
            </span>
            <h3 class="text-xl font-bold text-slate-900 mt-1">
              {{ selectedApp.first_name }} {{ selectedApp.middle_name || '' }} {{ selectedApp.last_name }}
            </h3>
          </div>
          <button @click="selectedApp = null" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold">✕</button>
        </div>

        <!-- Modal Sub-Navigation Tabs (Demographics First) -->
        <div class="flex items-center space-x-2 border-b border-slate-200 mb-6 pb-2 flex-wrap gap-y-2">
          <button 
            type="button" 
            @click="modalTab = 'profile'"
            :class="[
              'px-4 py-2 rounded-xl font-bold text-xs transition flex items-center space-x-2 cursor-pointer',
              modalTab === 'profile' 
                ? 'bg-blue-900 text-white shadow-xs' 
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
            ]"
          >
            <User class="w-4 h-4" />
            <span>Detailed Student Demographics</span>
          </button>

          <button 
            type="button" 
            @click="modalTab = 'docs'"
            :class="[
              'px-4 py-2 rounded-xl font-bold text-xs transition flex items-center space-x-2 cursor-pointer',
              modalTab === 'docs' 
                ? 'bg-blue-900 text-white shadow-xs' 
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
            ]"
          >
            <FileText class="w-4 h-4" />
            <span>Submitted Credentials & Approval</span>
            <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-blue-800 text-white font-mono">
              {{ (selectedApp.documents || []).filter(d => d.status === 'Verified').length }}/{{ (selectedApp.documents || []).length }}
            </span>
          </button>
        </div>

        <!-- TAB 1: SUBMITTED DOCUMENTS & EVALUATION -->
        <div v-if="modalTab === 'docs'">
          <!-- Student Profile Overview -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 p-4 rounded-2xl border border-slate-200 text-xs mb-4">
            <div><span class="text-slate-400 block">LRN:</span> <strong class="font-mono">{{ selectedApp.lrn || 'N/A' }}</strong></div>
            <div><span class="text-slate-400 block">Grade Level:</span> <strong>{{ selectedApp.grade_level_name }}</strong></div>
            <div><span class="text-slate-400 block">Strand:</span> <strong>{{ selectedApp.strand_name || 'JHS General' }}</strong></div>
            <div><span class="text-slate-400 block">Voucher Subsidy:</span> <strong class="text-emerald-700">{{ selectedApp.voucher_status }}</strong></div>
          </div>

          <!-- Credential Progress & Status Breakdown Bar -->
          <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 mb-6">
            <div class="flex items-center justify-between text-xs mb-2">
              <span class="font-bold text-slate-700 flex items-center space-x-1.5">
                <ShieldCheck class="w-4 h-4 text-indigo-600" />
                <span>Verification Readiness</span>
              </span>
              <span class="font-mono font-bold" :class="verifiedCount === totalDocsCount && totalDocsCount > 0 ? 'text-emerald-700' : 'text-slate-700'">
                {{ verifiedCount }} of {{ totalDocsCount }} Verified ({{ verifiedPercentage }}%)
              </span>
            </div>
            <!-- Multi-colored progress bar -->
            <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden flex">
              <div 
                class="bg-emerald-500 h-full transition-all duration-300" 
                :style="{ width: `${verifiedPercentage}%` }" 
                title="Verified"
              ></div>
              <div 
                class="bg-rose-500 h-full transition-all duration-300" 
                :style="{ width: `${deficientPercentage}%` }" 
                title="Deficient"
              ></div>
            </div>
            <!-- Status badges breakdown -->
            <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-slate-200/80 text-[11px]">
              <span class="px-2 py-0.5 rounded-md font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                ✓ {{ verifiedCount }} Verified
              </span>
              <span v-if="pendingPhysicalCount > 0" class="px-2 py-0.5 rounded-md font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200">
                🏢 {{ pendingPhysicalCount }} Physical Pending
              </span>
              <span v-if="promissoryDocsCount > 0" class="px-2 py-0.5 rounded-md font-semibold bg-amber-100 text-amber-900 border border-amber-200">
                ⏳ {{ promissoryDocsCount }} Promissory (To Follow Up)
              </span>
              <span v-if="deficientDocsList.length > 0" class="px-2 py-0.5 rounded-md font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                ⚠️ {{ deficientDocsList.length }} Deficient
              </span>
            </div>
          </div>

        <!-- Uploaded Documents Evaluation -->
        <div class="mb-6">
          <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Submitted Admission Credentials</h4>
            <button
              v-if="unverifiedPhysicalDocs.length > 0 && !isOfficiallyEnrolled && !isAlreadyQueued"
              type="button"
              @click="batchVerifyPhysicalDocs"
              :disabled="isBatchVerifying"
              class="px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition flex items-center space-x-1.5 disabled:opacity-50"
            >
              <Check class="w-3.5 h-3.5" />
              <span>{{ isBatchVerifying ? 'Verifying...' : `Verify All Physical Copies (${unverifiedPhysicalDocs.length})` }}</span>
            </button>
          </div>
          <div class="space-y-3">
            <div 
              v-for="doc in selectedApp.documents" 
              :key="doc.id"
              class="p-3.5 rounded-xl border bg-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs"
              :class="[
                doc.submission_mode === 'Physical Submission' ? 'border-indigo-200 bg-indigo-50/20' :
                doc.submission_mode === 'To Follow Up' ? 'border-amber-200 bg-amber-50/20' :
                'border-slate-200 bg-white'
              ]"
            >
              <div class="space-y-1">
                <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                  <div class="font-bold text-slate-800">{{ doc.document_type }}</div>
                  <!-- Mode Badge -->
                  <span v-if="doc.submission_mode === 'Physical Submission'" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200 flex items-center space-x-1">
                    <Building class="w-3 h-3 text-indigo-600" />
                    <span>Physical Hard Copy</span>
                  </span>
                  <span v-else-if="doc.submission_mode === 'To Follow Up'" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-200 flex items-center space-x-1">
                    <Clock class="w-3 h-3 text-amber-700" />
                    <span>To Follow Up</span>
                  </span>
                  <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center space-x-1">
                    <FileText class="w-3 h-3 text-emerald-600" />
                    <span>Digital File</span>
                  </span>
                </div>

                <!-- Details based on submission mode -->
                <div v-if="doc.submission_mode === 'Physical Submission'" class="text-[11px] text-indigo-900 flex items-center space-x-1.5">
                  <Building class="w-3.5 h-3.5 text-indigo-600 shrink-0" />
                  <span>Physical submission commitment on-campus (Window 3). Verify upon receiving hard copy.</span>
                </div>
                <div v-else-if="doc.submission_mode === 'To Follow Up'" class="space-y-0.5 text-[11px]">
                  <div class="text-amber-900 font-semibold flex items-center space-x-1.5">
                    <Calendar class="w-3.5 h-3.5 text-amber-700 shrink-0" />
                    <span>Promised Target Date: <strong class="underline font-bold text-amber-950">{{ formatDate(doc.target_date) }}</strong></span>
                  </div>
                  <div v-if="doc.promissory_note" class="text-slate-600 italic">
                    Reason: "{{ doc.promissory_note }}"
                  </div>
                </div>
                <div v-else class="text-[11px] text-slate-400">
                  {{ doc.original_filename }} ({{ ((doc.file_size || 0) / 1024).toFixed(1) }} KB)
                </div>

                <div v-if="doc.verification_notes" class="text-amber-700 text-[11px] mt-0.5">Note: {{ doc.verification_notes }}</div>
              </div>

              <!-- Verification Actions -->
              <div class="flex items-center space-x-2 shrink-0">
                <button 
                  v-if="doc.file_path"
                  type="button" 
                  @click="openPreviewDoc(doc)" 
                  class="px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 flex items-center space-x-1"
                  title="View document in pop-up modal"
                >
                  <Eye class="w-3.5 h-3.5" />
                  <span>View</span>
                </button>
                <span :class="getStatusBadgeClass(doc.status)" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase">
                  {{ doc.status }}
                </span>

                <!-- IF OFFICIALLY ENROLLED: LOCK DOCUMENT STATUS -->
                <span v-if="isOfficiallyEnrolled" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-300 flex items-center space-x-1" title="Document locked because student is officially enrolled">
                  <Lock class="w-3 h-3 text-slate-500" />
                  <span>Locked</span>
                </span>

                <!-- OTHERWISE SHOW CONTEXTUAL VERIFICATION BUTTONS -->
                <template v-else>
                  <!-- Already Verified: Show confirmed badge and option to flag deficient if mistake found -->
                  <div v-if="doc.status === 'Verified'" class="flex items-center space-x-1.5">
                    <span class="px-2 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-300 flex items-center space-x-1">
                      <Check class="w-3 h-3" />
                      <span>Verified</span>
                    </span>
                    <button 
                      @click="openDeficiencyModal(doc)"
                      class="px-2 py-1 rounded-lg text-[11px] font-semibold text-rose-700 hover:bg-rose-50 border border-rose-200 transition"
                      title="Change status to deficient"
                    >
                      Flag Deficient
                    </button>
                  </div>

                  <!-- Deficient: Provide primary 'Resolve & Verify' button -->
                  <div v-else-if="doc.status === 'Deficient' || doc.status === 'Rejected'" class="flex items-center space-x-1.5">
                    <button 
                      @click="verifyDoc(doc.id, 'Verified')"
                      class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition flex items-center space-x-1"
                    >
                      <Check class="w-3 h-3" />
                      <span>Resolve & Verify</span>
                    </button>
                  </div>

                  <!-- To Follow Up: No Verify or Deficient buttons (Promissory note pledged) -->
                  <template v-else-if="doc.submission_mode === 'To Follow Up' || doc.status === 'To Follow Up'">
                    <!-- Intentionally empty: To follow up documents do not have verify/deficient buttons until actual submission -->
                  </template>

                  <!-- Pending Physical or Digital Upload: Standard Verify & Deficient -->
                  <template v-else>
                    <button 
                      @click="verifyDoc(doc.id, 'Verified')"
                      class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition"
                    >
                      ✓ Verify
                    </button>
                    <button 
                      @click="openDeficiencyModal(doc)"
                      class="px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white shadow-sm transition"
                    >
                      ✕ Deficient
                    </button>
                  </template>
                </template>
              </div>
            </div>
            <div v-if="!selectedApp.documents || selectedApp.documents.length === 0" class="text-xs text-slate-400 text-center py-4">
              No documents uploaded yet by the applicant.
            </div>
          </div>
        </div>

        <!-- Section Assignment & Approve to Queue (With Deficiency Guard) -->
        <div class="p-5 rounded-2xl border text-xs" :class="isAlreadyQueued ? 'bg-emerald-50/70 border-emerald-200' : 'bg-blue-50/70 border-blue-200'">
          <div class="flex items-center justify-between mb-3">
            <h4 class="font-bold uppercase" :class="isAlreadyQueued ? 'text-emerald-950' : 'text-blue-950'">
              {{ isAlreadyQueued ? 'Enrollment Queue Status (Active)' : 'Approve & Push to Enrollment Queue' }}
            </h4>
            <span v-if="isAlreadyQueued" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center space-x-1">
              <span>✓ Currently in Queue</span>
            </span>
          </div>

          <!-- IF ALREADY IN QUEUE: SHOW CONFIRMED DETAILS & ONLY UNDO BUTTON (APPROVE BUTTON HIDDEN) -->
          <template v-if="isAlreadyQueued">
            <div class="p-3.5 rounded-xl bg-white border border-emerald-200 text-slate-700 space-y-2 mb-4">
              <div class="flex items-center justify-between">
                <span class="font-semibold text-slate-500">Queue & Evaluation Status:</span>
                <span class="font-bold text-emerald-800">{{ selectedApp.status }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="font-semibold text-slate-500">Evaluation Remarks:</span>
                <span class="font-medium text-slate-800">{{ selectedApp.remarks || 'Requirements verified and approved.' }}</span>
              </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-emerald-200/60">
              <!-- IF OFFICIALLY ENROLLED: SHOW PERMANENT BADGE INSTEAD OF UNDO BUTTON -->
              <div v-if="isOfficiallyEnrolled" class="px-3.5 py-2 rounded-xl bg-emerald-100/90 text-emerald-900 border border-emerald-300 font-bold text-xs flex items-center space-x-1.5 shadow-sm">
                <CheckCircle2 class="w-4 h-4 text-emerald-700 shrink-0" />
                <span>Student Officially Enrolled (Approval Permanent)</span>
              </div>

              <!-- IF IN QUEUE BUT NOT YET ENROLLED: ALLOW UNDO APPROVAL -->
              <button 
                v-else
                type="button" 
                @click="openUndoModal(selectedApp.id, selectedApp.application_no, `${selectedApp.first_name} ${selectedApp.last_name}`)" 
                class="px-4 py-2.5 rounded-xl font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs shadow-md transition flex items-center space-x-1.5"
                title="Revert application back to Under Review"
              >
                <RotateCcw class="w-3.5 h-3.5" />
                <span>Undo Approval & Return to Review</span>
              </button>

              <div class="flex items-center space-x-2">
                <button 
                  type="button" 
                  @click="openPrintForm(selectedApp.id)" 
                  class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-md transition flex items-center space-x-1.5"
                >
                  <Printer class="w-3.5 h-3.5" />
                  <span>Print Student Form</span>
                </button>

                <button @click="selectedApp = null" class="px-5 py-2.5 rounded-xl bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-xs shadow-sm">
                  Close
                </button>
              </div>
            </div>
          </template>

          <!-- IF NOT YET QUEUED: SHOW APPROVAL CONTROLS -->
          <template v-else>
            <!-- DEFICIENCY BLOCKER WARNING BANNER -->
            <div v-if="hasDeficiencies" class="mb-4 p-3.5 rounded-xl bg-rose-100/90 border border-rose-300 text-rose-950 flex items-start space-x-2.5">
              <AlertTriangle class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
              <div>
                <span class="font-extrabold text-xs text-rose-900 uppercase tracking-wider block">Approval Blocked: Deficiencies Detected</span>
                <p class="text-xs text-rose-800 mt-0.5">
                  This applicant has <strong class="underline">{{ deficientDocsList.length }} deficient requirement(s)</strong> ({{ deficientDocsList.map(d => d.document_type).join(', ') }}).
                  All requirements must be resolved and verified before queuing this student.
                </p>
              </div>
            </div>

            <!-- CONDITIONAL ADMISSION / PROMISSORY NOTE NOTICE -->
            <div v-else-if="hasActivePromissory" class="mb-4 p-3.5 rounded-xl bg-amber-50 border border-amber-300 text-amber-950 flex items-start space-x-2.5">
              <Clock class="w-4 h-4 text-amber-700 shrink-0 mt-0.5" />
              <div>
                <span class="font-extrabold text-xs text-amber-900 uppercase tracking-wider block">DepEd Conditional Admission Notice</span>
                <p class="text-xs text-amber-900 mt-0.5">
                  Applicant has <strong>{{ promissoryDocsCount }} pending promissory credential(s)</strong> marked <em>To Follow Up</em>:
                  <span class="font-semibold">{{ activePromissorySummary }}</span>.
                </p>
                <p class="text-[11px] text-amber-800 mt-1">
                  💡 This applicant can be approved under <strong>Conditional Admission</strong>. The remarks below have been pre-filled with the promissory commitments.
                </p>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Assign Section *</label>
                <select v-model="approvalForm.section_id" :disabled="hasDeficiencies" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-xs disabled:bg-slate-100 disabled:opacity-60">
                  <option :value="0">-- Auto Assign Available Section --</option>
                  <option v-for="sec in selectedApp.available_sections" :key="sec.id" :value="sec.id">
                    {{ sec.name }} ({{ sec.current_enrolled }}/{{ sec.max_capacity }} enrolled)
                  </option>
                </select>
              </div>
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Evaluation Remarks</label>
                <input v-model="approvalForm.remarks" :disabled="hasDeficiencies" type="text" placeholder="e.g. Complete documents verified" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs disabled:bg-slate-100 disabled:opacity-60" />
              </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-2 border-t border-blue-200/60">
              <button @click="selectedApp = null" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold text-xs">
                Cancel
              </button>
              <button 
                @click="submitApproval" 
                :disabled="hasDeficiencies || isApproving"
                :class="[
                  hasDeficiencies 
                    ? 'bg-slate-200 text-slate-400 cursor-not-allowed border border-slate-300' 
                    : 'bg-blue-900 hover:bg-blue-800 text-white shadow-xs cursor-pointer'
                ]"
                class="px-5 py-2.5 rounded-xl font-semibold text-xs transition flex items-center space-x-1.5"
              >
                <span v-if="isApproving" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span>{{ hasDeficiencies ? 'Cannot Approve (Resolve Deficiencies)' : 'Approve & Add to Enrollment Queue' }}</span>
              </button>
            </div>
          </template>
        </div>

        </div>

        <!-- TAB 2: DETAILED STUDENT PROFILE & DEMOGRAPHICS -->
        <div v-else-if="modalTab === 'profile'" class="space-y-6">
          
          <!-- Summary Strip -->
          <div class="p-4 rounded-2xl bg-blue-50 border border-blue-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
            <div>
              <span class="text-blue-900 font-bold uppercase tracking-wider text-[10px]">DepEd Learner Reference Number (LRN)</span>
              <div class="font-mono text-base font-black text-blue-950">{{ selectedApp.lrn || 'No LRN Recorded' }}</div>
            </div>
            <div class="flex items-center space-x-2">
              <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-white text-slate-800 border border-blue-200">
                Type: {{ selectedApp.applicant_type || 'New Student' }}
              </span>
              <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                Voucher: {{ selectedApp.voucher_status || 'None' }}
              </span>
            </div>
          </div>

          <!-- 1. Personal & Civil Demographics Card -->
          <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-3 text-xs">
            <div class="flex items-center space-x-2 text-slate-900 font-bold uppercase tracking-wider text-[11px] border-b border-slate-200 pb-2">
              <User class="w-4 h-4 text-blue-900" />
              <span>1. Learner Identification & Civil Status</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Full Legal Name</span>
                <strong class="text-slate-900 text-[13px]">{{ selectedApp.last_name }}, {{ selectedApp.first_name }} {{ selectedApp.middle_name || '' }} {{ selectedApp.suffix || selectedApp.extension_name || '' }}</strong>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Biological Sex / Gender</span>
                <strong class="text-slate-900">{{ selectedApp.gender || 'Unspecified' }}</strong>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Date of Birth</span>
                <strong class="text-slate-900">{{ formatDate(selectedApp.birthdate || selectedApp.birth_date || selectedApp.dob) }}</strong>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Place of Birth</span>
                <strong class="text-slate-900">{{ selectedApp.birthplace || selectedApp.birth_place || selectedApp.pob }}</strong>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Citizenship / Nationality</span>
                <strong class="text-slate-900">{{ selectedApp.nationality || 'Filipino' }}</strong>
              </div>
              <div v-if="selectedApp.religion && selectedApp.religion !== 'N/A'">
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Religion</span>
                <strong class="text-slate-900">{{ selectedApp.religion }}</strong>
              </div>
            </div>
          </div>

          <!-- 2. Residential Address & Contact Info Card -->
          <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-3 text-xs">
            <div class="flex items-center space-x-2 text-slate-900 font-bold uppercase tracking-wider text-[11px] border-b border-slate-200 pb-2">
              <MapPin class="w-4 h-4 text-blue-900" />
              <span>2. Residence & Direct Contact Channels</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div v-if="selectedApp.address_street && selectedApp.address_street !== 'N/A'">
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Permanent Home Address</span>
                <strong class="text-slate-900">{{ selectedApp.address_street }}</strong>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Barangay / Village</span>
                <strong class="text-slate-900">{{ selectedApp.address_barangay || selectedApp.barangay }}</strong>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">City / Municipality & Province</span>
                <strong class="text-slate-900">{{ (selectedApp.address_city || selectedApp.city) + ', ' + (selectedApp.address_province || selectedApp.province) + ((selectedApp.address_zip || selectedApp.postal_code) ? ' (' + (selectedApp.address_zip || selectedApp.postal_code) + ')' : '') }}</strong>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Mobile / SMS Hotline</span>
                <strong class="text-slate-900 font-mono">{{ selectedApp.contact_number || selectedApp.phone }}</strong>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Email Address</span>
                <strong class="text-slate-900 font-mono">{{ selectedApp.email }}</strong>
              </div>
              <div v-if="selectedApp.facebook_profile && selectedApp.facebook_profile !== 'N/A'">
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Facebook / Online Profile</span>
                <strong class="text-slate-900">{{ selectedApp.facebook_profile }}</strong>
              </div>
            </div>
          </div>

          <!-- 3. Parent, Guardian & Emergency Contact Card -->
          <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-3 text-xs">
            <div class="flex items-center space-x-2 text-slate-900 font-bold uppercase tracking-wider text-[11px] border-b border-slate-200 pb-2">
              <Phone class="w-4 h-4 text-blue-900" />
              <span>3. Parent, Guardian & Emergency Verification</span>
            </div>

            <!-- Optional Father/Mother info if recorded -->
            <div v-if="(selectedApp.father_name && selectedApp.father_name !== 'N/A') || (selectedApp.mother_name && selectedApp.mother_name !== 'N/A')" class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
              <div v-if="selectedApp.father_name && selectedApp.father_name !== 'N/A'" class="p-3 bg-white rounded-xl border border-slate-200 space-y-1">
                <span class="text-[10px] uppercase font-bold text-blue-900 block">Father's Information</span>
                <div class="font-bold text-slate-900">{{ selectedApp.father_name }}</div>
                <div v-if="selectedApp.father_occupation && selectedApp.father_occupation !== 'N/A'" class="text-slate-500 text-[11px]">Occ: {{ selectedApp.father_occupation }}</div>
                <div v-if="selectedApp.father_contact && selectedApp.father_contact !== 'N/A'" class="text-slate-700 font-mono text-[11px]">Tel: {{ selectedApp.father_contact }}</div>
              </div>
              <div v-if="selectedApp.mother_name && selectedApp.mother_name !== 'N/A'" class="p-3 bg-white rounded-xl border border-slate-200 space-y-1">
                <span class="text-[10px] uppercase font-bold text-blue-900 block">Mother's Information</span>
                <div class="font-bold text-slate-900">{{ selectedApp.mother_name }}</div>
                <div v-if="selectedApp.mother_occupation && selectedApp.mother_occupation !== 'N/A'" class="text-slate-500 text-[11px]">Occ: {{ selectedApp.mother_occupation }}</div>
                <div v-if="selectedApp.mother_contact && selectedApp.mother_contact !== 'N/A'" class="text-slate-700 font-mono text-[11px]">Tel: {{ selectedApp.mother_contact }}</div>
              </div>
            </div>

            <!-- Primary Legal Guardian / Emergency Contact (Standard DepEd admission contact) -->
            <div class="p-4 bg-white rounded-xl border border-slate-200">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-2 mb-3">
                <div>
                  <span class="text-[10px] uppercase font-bold text-emerald-800 block">Primary Parent / Legal Guardian</span>
                  <div class="font-extrabold text-slate-900 text-sm">{{ selectedApp.guardian_name || selectedApp.emergency_contact_name }}</div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-900 border border-blue-200 inline-block self-start sm:self-auto">
                  Relationship: {{ selectedApp.guardian_relationship || selectedApp.guardian_relation || selectedApp.emergency_contact_relation || 'Parent/Guardian' }}
                </span>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                <div>
                  <span class="text-slate-400 block text-[10px] uppercase font-bold">Guardian Mobile / Hotline</span>
                  <strong class="text-slate-900 font-mono text-xs">{{ selectedApp.guardian_contact || selectedApp.emergency_contact_phone }}</strong>
                </div>
                <div v-if="selectedApp.guardian_occupation && selectedApp.guardian_occupation !== 'N/A'">
                  <span class="text-slate-400 block text-[10px] uppercase font-bold">Occupation</span>
                  <strong class="text-slate-900">{{ selectedApp.guardian_occupation }}</strong>
                </div>
              </div>
            </div>
          </div>

          <!-- 4. Academic History & School of Origin Card -->
          <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-3 text-xs">
            <div class="flex items-center space-x-2 text-slate-900 font-bold uppercase tracking-wider text-[11px] border-b border-slate-200 pb-2">
              <GraduationCap class="w-4 h-4 text-blue-900" />
              <span>4. Academic Background & School of Origin</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Last School Attended</span>
                <strong class="text-slate-900">{{ selectedApp.last_school_attended || 'None' }}</strong>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Previous School Type</span>
                <strong class="text-slate-900">{{ selectedApp.last_school_type || selectedApp.previous_school_type || 'Public' }}</strong>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Academic Evaluation</span>
                <strong class="text-blue-900 font-medium text-xs">Verified via SF9 / Form 138 Credentials</strong>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Target Grade & Strand</span>
                <strong class="text-slate-900">{{ selectedApp.grade_level_name }} — {{ selectedApp.strand_name || 'General Curriculum' }}</strong>
              </div>
            </div>
          </div>

          <!-- Footer Actions inside Profile Tab -->
          <div class="flex items-center justify-between pt-4 border-t border-slate-200">
            <button 
              type="button"
              @click="openPrintForm(selectedApp.id)"
              class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-sm transition flex items-center space-x-1.5 cursor-pointer"
            >
              <Printer class="w-3.5 h-3.5" />
              <span>Print Application Summary</span>
            </button>

            <button 
              type="button" 
              @click="modalTab = 'docs'" 
              class="px-5 py-2 rounded-xl bg-blue-900 hover:bg-blue-800 text-white font-bold text-xs shadow-sm transition flex items-center space-x-1.5 cursor-pointer"
            >
              <span>Back to Documents & Verification →</span>
            </button>
          </div>

        </div>
      </div>
    </div>

    <!-- DOCUMENT PREVIEW POP-UP MODAL FOR REGISTRAR -->
    <div v-if="previewDoc" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-4xl w-full max-h-[92vh] flex flex-col shadow-2xl overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-200">
        <div class="p-4 sm:px-6 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
          <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-blue-950 border border-blue-500/30 text-blue-400 flex items-center justify-center shrink-0">
              <FileText class="w-5 h-5" />
            </div>
            <div>
              <h3 class="font-bold text-sm text-white">{{ previewDoc.document_type }}</h3>
              <p class="text-[11px] text-slate-400 font-mono">{{ previewDoc.original_filename }}</p>
            </div>
          </div>
          <div class="flex items-center space-x-2">
            <a 
              :href="getFileUrl(previewDoc.file_path)" 
              target="_blank" 
              download
              class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center space-x-1.5 transition"
            >
              <Download class="w-3.5 h-3.5" />
              <span>Download</span>
            </a>
            <button 
              @click="previewDoc = null" 
              class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center font-bold transition"
            >
              ✕
            </button>
          </div>
        </div>

        <div class="flex-1 p-4 sm:p-6 overflow-y-auto bg-slate-100 flex items-center justify-center min-h-[400px]">
          <iframe 
            v-if="isPdf(previewDoc.file_path, previewDoc.original_filename)" 
            :src="getFileUrl(previewDoc.file_path)" 
            class="w-full h-[70vh] rounded-xl border border-slate-300 bg-white shadow-inner"
          ></iframe>
          <!-- In-Browser Word Document (.docx) Viewer -->
          <div v-else-if="isWord(previewDoc.file_path, previewDoc.original_filename)" class="w-full flex flex-col items-center">
            <!-- Loading indicator while rendering -->
            <div v-if="isRenderingDocx" class="p-12 text-center space-y-3">
              <Loader2 class="w-8 h-8 animate-spin text-blue-600 mx-auto" />
              <p class="text-xs text-slate-500 font-medium">Rendering Word document preview...</p>
            </div>

            <!-- Fallback error if docx cannot be rendered inline (e.g. legacy binary .doc) -->
            <div v-else-if="docxRenderError" class="p-8 text-center space-y-4 max-w-md bg-white rounded-3xl border border-slate-200 shadow-sm animate-in fade-in zoom-in-95">
              <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center mx-auto shadow-inner">
                <FileText class="w-8 h-8 text-blue-600" />
              </div>
              <div>
                <div class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-blue-100 text-blue-900 mb-1.5 border border-blue-200">
                  Microsoft Word Document
                </div>
                <h4 class="font-bold text-slate-800 text-sm truncate max-w-xs mx-auto">{{ previewDoc.original_filename || 'document.docx' }}</h4>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                  This Word document cannot be rendered inline. Click below to download and open it in Word.
                </p>
              </div>
              <a 
                :href="getFileUrl(previewDoc.file_path)" 
                target="_blank" 
                download
                class="inline-flex items-center space-x-2 px-6 py-2.5 rounded-xl font-bold bg-blue-700 hover:bg-blue-600 text-white text-xs shadow-md shadow-blue-700/20 transition cursor-pointer"
              >
                <Download class="w-4 h-4" />
                <span>Download & Open in Word</span>
              </a>
            </div>

            <!-- Live Rendered Docx Document Pages -->
            <div 
              v-show="!isRenderingDocx && !docxRenderError" 
              ref="docxContainerRef" 
              class="w-full max-h-[75vh] overflow-y-auto bg-slate-200/80 p-4 sm:p-6 rounded-2xl border border-slate-300 shadow-inner flex flex-col items-center"
            ></div>
          </div>
          <div v-else class="max-h-[70vh] overflow-auto flex items-center justify-center">
            <img 
              :src="getFileUrl(previewDoc.file_path)" 
              :alt="previewDoc.document_type" 
              class="max-w-full max-h-[68vh] rounded-xl shadow-lg object-contain border border-slate-300 bg-white"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- CUSTOM DEFICIENCY REMARK MODAL (REPLACES BROWSER PROMPT) -->
    <div v-if="deficiencyModal.isOpen" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
        <!-- Header -->
        <div class="p-5 bg-gradient-to-r from-rose-700 to-rose-900 text-white flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-2xl bg-white/15 border border-white/20 flex items-center justify-center text-white shrink-0">
              <AlertTriangle class="w-5 h-5 text-amber-300" />
            </div>
            <div>
              <h3 class="font-extrabold text-base text-white">Mark as Deficient</h3>
              <p class="text-xs text-rose-200">{{ deficiencyModal.docType }}</p>
            </div>
          </div>
          <button @click="closeDeficiencyModal" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center font-bold">
            ✕
          </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-4 text-xs">
          <!-- Document Name Tag -->
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between text-slate-600">
            <span class="font-semibold text-slate-700">Uploaded File:</span>
            <span class="font-mono text-[11px] text-slate-800 bg-white px-2 py-0.5 rounded border border-slate-200 truncate max-w-[200px]">
              {{ deficiencyModal.fileName }}
            </span>
          </div>

          <!-- Quick Preset Reason Chips -->
          <div>
            <label class="block font-bold text-slate-700 mb-2 uppercase text-[10px] tracking-wider">
              Quick Preset Reasons (Click to Select)
            </label>
            <div class="flex flex-wrap gap-1.5">
              <button 
                type="button" 
                v-for="(preset, pIdx) in deficiencyPresets" 
                :key="pIdx"
                @click="deficiencyModal.reason = preset"
                :class="deficiencyModal.reason === preset ? 'bg-rose-600 text-white font-bold border-rose-600' : 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200'"
                class="px-2.5 py-1 rounded-lg border text-[11px] transition text-left"
              >
                {{ preset }}
              </button>
            </div>
          </div>

          <!-- Custom Remarks Textarea -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Specific Deficiency Instructions for Student *</label>
            <textarea 
              v-model="deficiencyModal.reason" 
              rows="3" 
              placeholder="e.g. Blurry photo. Please upload a clear scanned copy showing complete text and signatures."
              class="w-full p-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-rose-500 text-xs text-slate-800"
            ></textarea>
            <span class="text-[10px] text-slate-400">This instruction will appear directly on the student's admission dashboard.</span>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end space-x-2">
          <button 
            type="button" 
            @click="closeDeficiencyModal" 
            class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-200 font-semibold text-xs transition"
          >
            Cancel
          </button>
          <button 
            type="button" 
            @click="submitDeficiencyModal" 
            :disabled="!deficiencyModal.reason.trim() || isSubmittingDeficiency"
            class="px-5 py-2.5 rounded-xl font-bold bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white text-xs shadow-md transition flex items-center space-x-1.5"
          >
            <span v-if="isSubmittingDeficiency" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span>Confirm Deficiency Remark</span>
          </button>
        </div>
      </div>
    </div>

    <!-- CUSTOM UNDO APPROVAL CONFIRMATION MODAL -->
    <div v-if="undoModal.isOpen" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-200">
        <!-- Modal Header -->
        <div class="p-5 bg-gradient-to-r from-amber-500 to-orange-500 text-white flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-white shrink-0">
              <RotateCcw class="w-5 h-5" />
            </div>
            <div>
              <h3 class="font-extrabold text-base leading-tight">Undo Approval</h3>
              <p class="text-amber-100 text-xs font-medium">Revoke Enrollment Queue Status</p>
            </div>
          </div>
          <button 
            type="button" 
            @click="closeUndoModal" 
            class="w-8 h-8 rounded-full bg-black/10 hover:bg-black/20 text-white flex items-center justify-center font-bold text-sm transition"
          >
            ✕
          </button>
        </div>

        <!-- Body Content -->
        <div class="p-6 space-y-4 text-xs">
          <!-- Applicant Info Pill -->
          <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-slate-800">
            <div class="flex items-center justify-between mb-1">
              <span class="text-slate-500 font-semibold">Application Number:</span>
              <span class="font-mono font-bold text-amber-900 bg-amber-200/60 px-2 py-0.5 rounded text-[11px]">{{ undoModal.appNo }}</span>
            </div>
            <div v-if="undoModal.studentName" class="flex items-center justify-between">
              <span class="text-slate-500 font-semibold">Student Name:</span>
              <span class="font-bold text-slate-900">{{ undoModal.studentName }}</span>
            </div>
          </div>

          <p class="text-slate-600 leading-relaxed font-medium">
            Are you sure you want to revert this approved application? This action will perform the following adjustments:
          </p>

          <!-- Impact Checklist -->
          <div class="space-y-2 bg-slate-50 p-3.5 rounded-2xl border border-slate-200">
            <div class="flex items-start space-x-2.5">
              <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-800 font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">1</span>
              <span class="text-slate-700 font-medium"><strong>Remove from Queue:</strong> The applicant is pulled out of Treasury assessment.</span>
            </div>
            <div class="flex items-start space-x-2.5">
              <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-800 font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">2</span>
              <span class="text-slate-700 font-medium"><strong>Release Section Seat:</strong> The reserved section capacity counter is decremented by 1.</span>
            </div>
            <div class="flex items-start space-x-2.5">
              <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-800 font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">3</span>
              <span class="text-slate-700 font-medium"><strong>Revert Status:</strong> Application returns to <em>"Under Review"</em> for re-evaluation.</span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end space-x-2.5">
          <button 
            type="button" 
            @click="closeUndoModal" 
            :disabled="isUndoing"
            class="px-4 py-2.5 rounded-xl text-slate-600 hover:bg-slate-200 font-semibold text-xs transition"
          >
            Keep Approved
          </button>
          <button 
            type="button" 
            @click="confirmUndoApproval" 
            :disabled="isUndoing"
            class="px-5 py-2.5 rounded-xl font-bold bg-amber-500 hover:bg-amber-400 disabled:opacity-50 text-slate-950 text-xs shadow-md transition flex items-center space-x-1.5"
          >
            <span v-if="isUndoing" class="w-3.5 h-3.5 border-2 border-slate-950 border-t-transparent rounded-full animate-spin"></span>
            <span>{{ isUndoing ? 'Undoing Approval...' : 'Confirm Undo Approval' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- REGISTRAR PRINTABLE STUDENT PRE-ENROLLMENT & ASSESSMENT FORM -->
    <div v-if="selectedPrintForm" class="fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm overflow-y-auto p-4 sm:p-6 flex flex-col items-center">
      <!-- Action Bar (Hidden in Print) -->
      <div class="no-print w-full max-w-4xl flex items-center justify-between mb-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-lg">
        <button 
          @click="selectedPrintForm = null" 
          class="px-4 py-2 rounded-xl text-slate-700 hover:bg-slate-100 font-semibold text-xs transition flex items-center space-x-2"
        >
          <ArrowLeft class="w-4 h-4" />
          <span>Back to Registrar Portal</span>
        </button>

        <div class="flex items-center space-x-3">
          <button 
            @click="printStudentForm" 
            class="px-6 py-2.5 rounded-xl font-bold bg-slate-900 hover:bg-slate-800 text-white text-xs shadow-md transition flex items-center space-x-2"
          >
            <Printer class="w-4 h-4" />
            <span>Print Student Form</span>
          </button>
        </div>
      </div>

      <!-- Printable Document Sheet (1-Page Optimized) -->
      <div class="bg-white text-slate-900 rounded-3xl p-6 sm:p-8 print:p-3.5 border-2 border-slate-800 shadow-2xl max-w-4xl w-full text-xs font-sans print:border-slate-800 print:shadow-none print:rounded-none">
        <!-- School & DepEd Header -->
        <div class="text-center border-b-2 border-slate-800 pb-2.5 mb-3 print:pb-2 print:mb-2">
          <div class="text-[11px] print:text-[9.5px] font-semibold tracking-widest uppercase text-slate-600">Republic of the Philippines • Department of Education</div>
          <h2 class="text-lg print:text-base font-extrabold tracking-tight uppercase mt-0.5 mb-0.5 text-slate-900">SIA HIGH SCHOOL - BASIC EDUCATION DEPARTMENT</h2>
          <p class="text-xs print:text-[10px] text-slate-600 font-medium">Office of the Registrar • Student Registration & Pre-Enrollment Assessment Form</p>
          <div class="inline-block mt-1.5 px-3 py-0.5 rounded bg-slate-900 text-white text-xs print:text-[10px] font-bold font-mono uppercase tracking-wider">
            SY 2026-2027 • {{ selectedPrintForm.grade_category === 'SHS' ? '1st Semester' : 'Full Academic Year' }}
          </div>
        </div>

        <!-- Student & Academic Meta Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 print:gap-2 text-xs print:text-[10.5px] mb-3 print:mb-2 bg-slate-50 p-3 print:p-2 rounded-lg border border-slate-200">
          <div>
            <span class="text-slate-500 block text-[9.5px] uppercase font-bold">Generated Student Number:</span>
            <span class="font-bold font-mono text-sm print:text-xs text-blue-700">{{ selectedPrintForm.student_no || selectedPrintForm.enrollment_info?.student_no || 'PENDING' }}</span>
          </div>
          <div>
            <span class="text-slate-500 block text-[9.5px] uppercase font-bold">Applicant / Student Name:</span>
            <span class="font-bold text-xs print:text-[11px] uppercase text-slate-900">{{ selectedPrintForm.last_name }}, {{ selectedPrintForm.first_name }} {{ selectedPrintForm.middle_name || '' }}</span>
          </div>
          <div>
            <span class="text-slate-500 block text-[9.5px] uppercase font-bold">DepEd LRN:</span>
            <span class="font-bold font-mono text-xs print:text-[11px]">{{ selectedPrintForm.lrn || 'N/A' }}</span>
          </div>
          <div>
            <span class="text-slate-500 block text-[9.5px] uppercase font-bold">Application Ref #:</span>
            <span class="font-bold font-mono text-xs print:text-[11px] text-slate-700">{{ selectedPrintForm.application_no }}</span>
          </div>
          <div>
            <span class="text-slate-500 block text-[9.5px] uppercase font-bold">Grade & Program:</span>
            <span class="font-bold text-emerald-800 text-xs print:text-[11px]">{{ selectedPrintForm.grade_level_name }} {{ selectedPrintForm.strand_code ? '(' + selectedPrintForm.strand_code + ')' : '' }}</span>
          </div>
          <div>
            <span class="text-slate-500 block text-[9.5px] uppercase font-bold">Assigned Section:</span>
            <span class="font-bold text-blue-800 text-xs print:text-[11px]">{{ selectedPrintForm.queue_info?.section_name || selectedPrintForm.enrollment_info?.section_name || 'Main Section' }}</span>
          </div>
          <div>
            <span class="text-slate-500 block text-[9.5px] uppercase font-bold">Assigned Room:</span>
            <span class="font-semibold text-slate-700 text-xs print:text-[11px]">{{ selectedPrintForm.queue_info?.section_room || selectedPrintForm.enrollment_info?.section_room || 'Room Assigned' }}</span>
          </div>
          <div>
            <span class="text-slate-500 block text-[9.5px] uppercase font-bold">Contact Number:</span>
            <span class="font-mono text-xs print:text-[11px] text-slate-700">{{ selectedPrintForm.contact_number }}</span>
          </div>
        </div>

        <!-- Enrolled Subjects & Section Schedule Table -->
        <div class="mb-3 print:mb-2">
          <h3 class="text-xs print:text-[10.5px] font-bold uppercase tracking-wider text-slate-700 mb-1.5 print:mb-1">Enrolled Subjects & Section Class Schedule</h3>
          <table class="w-full text-xs print:text-[10px] text-left border-collapse border border-slate-300">
            <thead>
              <tr class="bg-slate-100 border-b border-slate-300 font-bold">
                <th class="p-1.5 print:py-0.5 print:px-1.5 border-r border-slate-300">Subject Code</th>
                <th class="p-1.5 print:py-0.5 print:px-1.5 border-r border-slate-300">Descriptive Title</th>
                <th class="p-1.5 print:py-0.5 print:px-1.5 border-r border-slate-300">Classification</th>
                <th class="p-1.5 print:py-0.5 print:px-1.5 border-r border-slate-300">Schedule / Day</th>
                <th class="p-1.5 print:py-0.5 print:px-1.5 border-r border-slate-300">Time Slot</th>
                <th class="p-1.5 print:py-0.5 print:px-1.5 text-center">Units</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="sub in (selectedPrintForm.enrolled_subjects || [])" :key="sub.id" class="border-b border-slate-200">
                <td class="p-1.5 print:py-0.5 print:px-1.5 font-mono font-bold border-r border-slate-200">{{ sub.subject_code }}</td>
                <td class="p-1.5 print:py-0.5 print:px-1.5 border-r border-slate-200">{{ sub.subject_title }}</td>
                <td class="p-1.5 print:py-0.5 print:px-1.5 border-r border-slate-200">{{ sub.category }}</td>
                <td class="p-1.5 print:py-0.5 print:px-1.5 border-r border-slate-200 font-medium">{{ sub.day_of_week || 'Mon-Fri' }}</td>
                <td class="p-1.5 print:py-0.5 print:px-1.5 border-r border-slate-200 font-mono text-[10.5px] print:text-[9.5px]">
                  {{ sub.time_start && sub.time_end ? sub.time_start.slice(0,5) + ' - ' + sub.time_end.slice(0,5) : '08:00 - 09:00' }}
                </td>
                <td class="p-1.5 print:py-0.5 print:px-1.5 text-center font-bold">{{ sub.units || '1.0' }}</td>
              </tr>
              <tr v-if="!selectedPrintForm.enrolled_subjects || selectedPrintForm.enrolled_subjects.length === 0" class="border-b border-slate-200">
                <td colspan="6" class="p-2 text-center text-slate-400 italic">Curriculum core subjects automatically assigned based on track and section loading.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Fee Assessment & Subsidy Breakdown -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 print:gap-3 pt-2.5 print:pt-1.5 border-t border-slate-200 text-xs print:text-[10px]">
          <div>
            <h4 class="font-bold text-slate-800 uppercase mb-1">DepEd Subsidy / Voucher Information</h4>
            <div class="p-2.5 print:p-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-600 space-y-0.5">
              <div>Voucher Category: <span class="font-bold text-slate-900">{{ selectedPrintForm.voucher_status || 'None' }}</span></div>
              <div>School of Origin: <span class="font-bold text-slate-900">{{ selectedPrintForm.last_school_attended }}</span></div>
              <div class="text-[9.5px] text-slate-500 italic mt-0.5">Government voucher subsidies apply directly to base assessment fees upon Treasury verification.</div>
            </div>
          </div>

          <div>
            <h4 class="font-bold text-slate-800 uppercase mb-1">Treasury Assessment Summary</h4>
            <div class="bg-slate-50 p-2.5 print:p-2 rounded-lg border border-slate-200 space-y-0.5 font-mono">
              <div class="flex justify-between">
                <span>Tuition Base:</span>
                <span>₱{{ Number(selectedPrintForm.assessment_info?.total_tuition || 12000).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Miscellaneous & Lab Fees:</span>
                <span>₱{{ ((Number(selectedPrintForm.assessment_info?.total_miscellaneous) || 4000) + (Number(selectedPrintForm.assessment_info?.total_laboratory) || 2500)).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
              </div>
              <div class="flex justify-between text-emerald-700 font-bold border-t border-slate-200 pt-0.5">
                <span>Less: DepEd Voucher Subsidy:</span>
                <span>- ₱{{ Number(selectedPrintForm.assessment_info?.voucher_discount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
              </div>
              <div class="flex justify-between text-xs print:text-[11px] font-extrabold text-slate-900 border-t-2 border-slate-800 pt-0.5">
                <span>Total Net Payable:</span>
                <span>₱{{ Number(selectedPrintForm.assessment_info?.net_payable || 18500).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
              </div>
              <div class="flex justify-between text-[10px] text-slate-600 pt-0.5">
                <span>Required Minimum Downpayment:</span>
                <span>₱3,000.00</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Signatures & Authority Section -->
        <div class="grid grid-cols-1 sm:grid-cols-3 print:grid-cols-3 gap-6 print:gap-4 text-center text-xs print:text-[10px] mt-6 print:mt-4 pt-4 print:pt-2.5 border-t border-slate-300">
          <div>
            <div class="border-b border-slate-400 pb-0.5 mb-0.5 font-bold uppercase">{{ selectedPrintForm.first_name }} {{ selectedPrintForm.last_name }}</div>
            <span class="text-[9px] text-slate-500 uppercase">Student / Applicant Signature</span>
          </div>
          <div>
            <div class="border-b border-slate-400 pb-0.5 mb-0.5 font-bold">Office of the Registrar</div>
            <span class="text-[9px] text-slate-500 uppercase">Evaluated & Approved</span>
          </div>
          <div>
            <div class="border-b border-slate-400 pb-0.5 mb-0.5 font-bold">Treasury / Cashier</div>
            <span class="text-[9px] text-slate-500 uppercase">Official Receipt Stamp</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { 
  Search, RefreshCw, Eye, Download, FileText, AlertTriangle, RotateCcw, Printer, 
  ArrowLeft, Lock, CheckCircle2, User, MapPin, Phone, Mail, GraduationCap, Building,
  Calendar, ShieldCheck, BookOpen, Clock, Layers, Check, X, Loader2, FileCheck,
  ListOrdered, FolderArchive, Sparkles, Filter, ChevronRight, UserCheck, AlertCircle
} from 'lucide-vue-next';
import api, { getFileUrl } from '../../services/api';

const route = useRoute();
const router = useRouter();
const activeTab = ref('applications');
const modalTab = ref('profile');

const enrolledStudents = ref([]);
const enrolledSummary = ref({
  total_enrolled: 0,
  needs_review_count: 0,
  has_to_follow_count: 0,
  fully_compliant_count: 0
});
const enrolledSearchQuery = ref('');
const enrolledFilter = ref('all');
const isLoadingEnrolled = ref(false);
const selectedEnrolledStudent = ref(null);
const isVerifyingEnrolledDoc = ref(false);
const deficiencyReasonInput = ref('');
const docBeingMarkedDeficient = ref(null);

const switchTab = (tab) => {
  activeTab.value = tab;
  router.push({ query: { ...route.query, tab } });
  if (tab === 'enrolled_docs') {
    loadEnrolledStudents();
  } else if (tab === 'queue') {
    loadQueue();
  } else {
    loadApplications();
  }
};

watch(() => route.query.tab, (newTab) => {
  if (newTab === 'queue') {
    activeTab.value = 'queue';
  } else if (newTab === 'enrolled_docs' || newTab === 'enrolled-docs') {
    activeTab.value = 'enrolled_docs';
    loadEnrolledStudents();
  } else {
    activeTab.value = 'applications';
  }
}, { immediate: true });

const applications = ref([]);
const queueList = ref([]);
const queueFilterStatus = ref('active');
const selectedApp = ref(null);
const selectedPrintForm = ref(null);
const searchQuery = ref('');
const filterStatus = ref('');
const isApproving = ref(false);
const isSubmittingDeficiency = ref(false);
const isUndoing = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A';
  try {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  } catch {
    return dateStr;
  }
};

const undoModal = ref({
  isOpen: false,
  appId: null,
  appNo: '',
  studentName: ''
});

const isOfficiallyEnrolled = computed(() => {
  return selectedApp.value?.status === 'Enrolled';
});

const openUndoModal = (appId, appNo, studentName = '') => {
  if (isOfficiallyEnrolled.value) {
    errorMessage.value = 'Cannot undo approval: This student has already completed treasury payment and is officially enrolled.';
    return;
  }
  undoModal.value = {
    isOpen: true,
    appId,
    appNo,
    studentName
  };
};

const closeUndoModal = () => {
  undoModal.value.isOpen = false;
};

const confirmUndoApproval = async () => {
  if (!undoModal.value.appId) return;
  if (isOfficiallyEnrolled.value) {
    errorMessage.value = 'Cannot undo approval: This student is already officially enrolled.';
    return;
  }
  isUndoing.value = true;
  errorMessage.value = '';
  try {
    const res = await api.undoApproval(undoModal.value.appId);
    successMessage.value = res.message || `Approval successfully undone for #${undoModal.value.appNo}.`;
    const reversedAppId = undoModal.value.appId;
    closeUndoModal();
    if (selectedApp.value && selectedApp.value.id === reversedAppId) {
      selectedApp.value = null;
    }
    await Promise.all([loadApplications(), loadQueue()]);
  } catch (err) {
    errorMessage.value = err.message || 'Failed to undo approval.';
  } finally {
    isUndoing.value = false;
  }
};

const deficientDocsList = computed(() => {
  return selectedApp.value?.documents?.filter(d => d.status === 'Deficient' || d.status === 'Rejected') || [];
});

const hasDeficiencies = computed(() => {
  return deficientDocsList.value.length > 0;
});

const isAlreadyQueued = computed(() => {
  return ['Queued for Enrollment', 'Approved', 'Assessed', 'Enrolled'].includes(selectedApp.value?.status);
});

const isBatchVerifying = ref(false);

const totalDocsCount = computed(() => {
  return selectedApp.value?.documents?.length || 0;
});

const verifiedCount = computed(() => {
  return selectedApp.value?.documents?.filter(d => d.status === 'Verified').length || 0;
});

const verifiedPercentage = computed(() => {
  if (!totalDocsCount.value) return 0;
  return Math.round((verifiedCount.value / totalDocsCount.value) * 100);
});

const deficientPercentage = computed(() => {
  if (!totalDocsCount.value) return 0;
  return Math.round((deficientDocsList.value.length / totalDocsCount.value) * 100);
});

const pendingPhysicalCount = computed(() => {
  return selectedApp.value?.documents?.filter(d => d.submission_mode === 'Physical Submission' && d.status !== 'Verified').length || 0;
});

const unverifiedPhysicalDocs = computed(() => {
  return selectedApp.value?.documents?.filter(d => d.submission_mode === 'Physical Submission' && d.status !== 'Verified') || [];
});

const promissoryDocs = computed(() => {
  return selectedApp.value?.documents?.filter(d => d.submission_mode === 'To Follow Up' && d.status !== 'Verified') || [];
});

const promissoryDocsCount = computed(() => {
  return promissoryDocs.value.length;
});

const hasActivePromissory = computed(() => {
  return promissoryDocsCount.value > 0;
});

const activePromissorySummary = computed(() => {
  return promissoryDocs.value.map(d => `${d.document_type} (due: ${formatDate(d.target_date)})`).join(', ');
});

const deficiencyPresets = [
  'Blurry or unreadable scanned copy. Please re-upload.',
  'Missing back page / Principal or Adviser signature.',
  'Unauthenticated PSA SECPA certificate copy.',
  'Incorrect or invalid document uploaded for this requirement.',
  'Expired or outdated certificate.'
];

const deficiencyModal = ref({
  isOpen: false,
  docId: null,
  docType: '',
  fileName: '',
  reason: ''
});

const openDeficiencyModal = (doc) => {
  if (!doc) return;
  if (isOfficiallyEnrolled.value) {
    errorMessage.value = 'Cannot mark documents as deficient: This student is already officially enrolled.';
    return;
  }
  deficiencyModal.value = {
    isOpen: true,
    docId: doc.id,
    docType: doc.document_type,
    fileName: doc.original_filename,
    reason: doc.verification_notes || deficiencyPresets[0]
  };
};

const closeDeficiencyModal = () => {
  deficiencyModal.value.isOpen = false;
};

const submitDeficiencyModal = async () => {
  if (!deficiencyModal.value.docId) return;
  isSubmittingDeficiency.value = true;
  try {
    await api.verifyDocument({
      document_id: deficiencyModal.value.docId,
      status: 'Deficient',
      verification_notes: deficiencyModal.value.reason.trim()
    });
    closeDeficiencyModal();
    if (selectedApp.value) {
      await openReviewModal(selectedApp.value.id, 'docs');
      await loadApplications();
    }
  } catch (err) {
    errorMessage.value = err.message || 'Failed to update document status.';
  } finally {
    isSubmittingDeficiency.value = false;
  }
};

const approvalForm = ref({
  section_id: 0,
  remarks: 'Requirements verified and approved.'
});

const getStatusBadgeClass = (status) => {
  if (status === 'Enrolled' || status === 'Verified') return 'bg-emerald-100 text-emerald-800';
  if (status === 'Approved' || status === 'Queued for Enrollment') return 'bg-blue-100 text-blue-800';
  if (status === 'Deficient' || status === 'Rejected' || status === 'Requirements Deficient') return 'bg-rose-100 text-rose-800';
  return 'bg-amber-100 text-amber-800';
};

const loadApplications = async () => {
  try {
    let params = '';
    if (filterStatus.value) params += `status=${filterStatus.value}&`;
    if (searchQuery.value) params += `search=${encodeURIComponent(searchQuery.value)}`;
    const res = await api.getApplications(params);
    applications.value = res.data;
  } catch (err) {
    console.error('Failed to load applications:', err);
  }
};

const loadQueue = async () => {
  try {
    const params = queueFilterStatus.value ? `status=${queueFilterStatus.value}` : '';
    const res = await api.getEnrollmentQueue(params);
    queueList.value = res.data;
  } catch (err) {
    console.error('Failed to load queue:', err);
  }
};

const openReviewModal = async (id, tab = null) => {
  try {
    if (tab) {
      modalTab.value = tab;
    } else if (!selectedApp.value || selectedApp.value.id !== id) {
      modalTab.value = 'profile';
    }
    const res = await api.getApplicationDetails(id);
    selectedApp.value = res.data;
    approvalForm.value.section_id = res.data.available_sections?.[0]?.id || 0;

    // Auto-fill remarks based on promissory state if not already queued
    if (!isAlreadyQueued.value) {
      if (hasActivePromissory.value) {
        approvalForm.value.remarks = `Conditional Admission: Pending submission of ${activePromissorySummary.value}`;
      } else {
        approvalForm.value.remarks = 'Requirements verified and approved.';
      }
    } else {
      approvalForm.value.remarks = res.data.remarks || 'Requirements verified and approved.';
    }
  } catch (err) {
    console.error('Failed to load application details:', err);
  }
};

const batchVerifyPhysicalDocs = async () => {
  if (!selectedApp.value || unverifiedPhysicalDocs.value.length === 0) return;
  if (isOfficiallyEnrolled.value) {
    errorMessage.value = 'Cannot modify documents: Student is already officially enrolled.';
    return;
  }
  isBatchVerifying.value = true;
  errorMessage.value = '';
  try {
    const res = await api.batchVerifyDocuments({
      application_id: selectedApp.value.id,
      submission_mode: 'Physical Submission'
    });
    successMessage.value = res.message || 'All physical hard-copy documents verified.';
    await openReviewModal(selectedApp.value.id, 'docs');
    await loadApplications();
  } catch (err) {
    errorMessage.value = err.message || 'Failed to batch verify physical documents.';
  } finally {
    isBatchVerifying.value = false;
  }
};

const verifyDoc = async (docId, status) => {
  if (isOfficiallyEnrolled.value) {
    errorMessage.value = 'Cannot modify document verification: This student is already officially enrolled.';
    return;
  }

  if (status === 'Deficient') {
    const doc = selectedApp.value?.documents?.find(d => d.id === docId);
    if (doc) {
      openDeficiencyModal(doc);
      return;
    }
  }

  try {
    await api.verifyDocument({
      document_id: docId,
      status: 'Verified',
      verification_notes: 'Document verified by Registrar'
    });
    if (selectedApp.value) {
      await openReviewModal(selectedApp.value.id, 'docs');
      await loadApplications();
    }
  } catch (err) {
    errorMessage.value = err.message || 'Verification failed.';
    console.error('Verification failed:', err);
  }
};

const submitApproval = async () => {
  if (!selectedApp.value || hasDeficiencies.value) return;
  isApproving.value = true;
  errorMessage.value = '';
  const appNo = selectedApp.value.application_no || 'Applicant';
  try {
    const res = await api.approveAndQueue({
      application_id: selectedApp.value.id,
      section_id: approvalForm.value.section_id,
      remarks: approvalForm.value.remarks
    });
    successMessage.value = res?.message || `Applicant ${appNo} successfully approved & added to the Enrollment Queue!`;
    selectedApp.value = null;
    await Promise.all([loadApplications(), loadQueue()]);
  } catch (err) {
    errorMessage.value = err.message || 'Approval failed.';
  } finally {
    isApproving.value = false;
  }
};

const previewDoc = ref(null);
const docxContainerRef = ref(null);
const isRenderingDocx = ref(false);
const docxRenderError = ref('');

const openPreviewDoc = async (doc) => {
  if (!doc) return;
  previewDoc.value = doc;
  docxRenderError.value = '';

  if (isWord(doc.file_path, doc.original_filename)) {
    isRenderingDocx.value = true;
    await nextTick();
    try {
      const url = getFileUrl(doc.file_path);
      const res = await fetch(url);
      if (!res.ok) throw new Error('Failed to fetch document file.');
      const blob = await res.blob();

      if (docxContainerRef.value) {
        docxContainerRef.value.innerHTML = '';
        const { renderAsync } = await import('docx-preview');
        await renderAsync(blob, docxContainerRef.value, null, {
          className: 'docx-preview-doc',
          inWrapper: true,
          ignoreWidth: false,
          ignoreHeight: false,
          ignoreFonts: false,
          breakPages: true,
          experimental: true
        });
      }
    } catch (err) {
      console.warn('Docx rendering error:', err);
      docxRenderError.value = err.message || 'Could not render document inline.';
    } finally {
      isRenderingDocx.value = false;
    }
  }
};

const isPdf = (filePath, originalFilename = '') => {
  const combined = ((filePath || '') + ' ' + (originalFilename || '')).toLowerCase();
  return combined.includes('.pdf');
};

const isWord = (filePath, originalFilename = '') => {
  const combined = ((filePath || '') + ' ' + (originalFilename || '')).toLowerCase();
  return combined.includes('.docx') || combined.includes('.doc');
};

const openPrintForm = async (appId) => {
  try {
    const res = await api.getApplicationDetails(appId);
    selectedPrintForm.value = res.data;
  } catch (err) {
    errorMessage.value = err.message || 'Failed to load student registration details.';
  }
};

const printStudentForm = () => {
  window.print();
};

const loadEnrolledStudents = async () => {
  isLoadingEnrolled.value = true;
  try {
    const params = new URLSearchParams();
    if (enrolledSearchQuery.value) params.append('search', enrolledSearchQuery.value);
    if (enrolledFilter.value && enrolledFilter.value !== 'all') params.append('filter', enrolledFilter.value);
    const res = await api.getEnrolledStudentsDocuments(params.toString());
    enrolledStudents.value = res.data?.students || [];
    enrolledSummary.value = res.data?.summary || {
      total_enrolled: 0,
      needs_review_count: 0,
      has_to_follow_count: 0,
      fully_compliant_count: 0
    };
  } catch (err) {
    console.error('Failed to load enrolled students documents:', err);
    errorMessage.value = err.message || 'Failed to load enrolled students documents.';
  } finally {
    isLoadingEnrolled.value = false;
  }
};

const openEnrolledStudentModal = (student) => {
  selectedEnrolledStudent.value = student;
  docBeingMarkedDeficient.value = null;
  deficiencyReasonInput.value = '';
};

const verifyEnrolledDoc = async (doc, newStatus, notes = '') => {
  isVerifyingEnrolledDoc.value = true;
  try {
    await api.verifyDocument({
      document_id: doc.id,
      status: newStatus,
      verification_notes: notes || doc.verification_notes || `Marked as ${newStatus} by Registrar.`
    });

    successMessage.value = `Document '${doc.document_type}' has been marked as ${newStatus}.`;
    docBeingMarkedDeficient.value = null;
    deficiencyReasonInput.value = '';

    await loadEnrolledStudents();
    if (selectedEnrolledStudent.value) {
      const refreshed = enrolledStudents.value.find(s => s.enrollment_id === selectedEnrolledStudent.value.enrollment_id);
      if (refreshed) {
        selectedEnrolledStudent.value = refreshed;
      }
    }
    setTimeout(() => { successMessage.value = ''; }, 4000);
  } catch (err) {
    errorMessage.value = err.message || `Failed to update document status to ${newStatus}.`;
  } finally {
    isVerifyingEnrolledDoc.value = false;
  }
};

onMounted(() => {
  loadApplications();
  loadQueue();
  loadEnrolledStudents();
});
</script>

<style scoped>
:deep(.docx-wrapper) {
  background: transparent !important;
  padding: 8px !important;
  display: flex !important;
  flex-direction: column !important;
  align-items: center !important;
  gap: 16px !important;
  width: 100% !important;
}
:deep(.docx-wrapper > section.docx) {
  margin-bottom: 16px !important;
  box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.15) !important;
  border-radius: 6px !important;
  background: #ffffff !important;
  color: #111827 !important;
  box-sizing: border-box !important;
  max-width: 100% !important;
}
:deep(.docx-wrapper article) {
  font-family: Calibri, 'Segoe UI', Arial, sans-serif !important;
}
</style>
