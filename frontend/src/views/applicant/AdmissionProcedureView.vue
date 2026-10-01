<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Top Header Banner -->
    <div class="no-print bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-2xs mb-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
      <div>
        <div class="flex items-center space-x-2.5 flex-wrap gap-y-2">
          <div class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-mono font-bold bg-blue-50 text-blue-950 border border-blue-200 shadow-2xs">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
            <span>Application No: {{ application?.application_no || 'Loading...' }}</span>
          </div>
          <span :class="statusBadgeClass" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider shadow-2xs">
            {{ application?.status || 'Pending' }}
          </span>
          <span class="text-xs text-slate-400 font-medium hidden sm:inline">•</span>
          <span class="text-xs text-slate-500 font-medium hidden sm:inline">S.Y. 2026-2027</span>
          <button 
            type="button" 
            @click="refreshPortalData" 
            :disabled="isRefreshing"
            class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition shadow-2xs cursor-pointer disabled:opacity-60"
            title="Check for real-time status and credential evaluation updates"
          >
            <RefreshCw class="w-3.5 h-3.5 text-slate-600" :class="{ 'animate-spin': isRefreshing }" />
            <span>{{ isRefreshing ? 'Checking...' : 'Refresh Status' }}</span>
          </button>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black text-[#0c2340] mt-2 tracking-tight font-serif">BSLA Admission & Enrollment Portal</h1>
        <p class="text-xs text-slate-600 mt-1 max-w-2xl">
          Complete the required demographic information, select your academic strand, upload your DepEd credentials, and finalize your enrollment.
        </p>
      </div>

      <div v-if="application?.status === 'Enrolled'" class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-left shrink-0 shadow-2xs">
        <div class="text-[10px] uppercase font-bold text-emerald-800 tracking-wider">Enrollment Completed!</div>
        <div class="text-sm font-bold text-slate-900 mt-0.5">Permanent ID: <span class="font-mono text-emerald-700">{{ application?.student_no || application?.assessment_info?.student_number || 'Generated' }}</span></div>
        <router-link to="/student" class="inline-block mt-2 px-4 py-1.5 rounded-xl text-xs font-bold bg-emerald-700 hover:bg-emerald-600 text-white transition shadow-2xs">
          Go to Student Portal →
        </router-link>
      </div>
    </div>

    <!-- OFFICIALLY ENROLLED CELEBRATORY BANNER -->
    <div v-if="application?.status === 'Enrolled' || application?.assessment_info?.enrollment_status === 'Officially Enrolled'" class="no-print p-6 sm:p-7 rounded-3xl bg-gradient-to-br from-emerald-950 via-slate-900 to-emerald-900 border-2 border-emerald-500 text-white shadow-2xl mb-8 animate-in fade-in slide-in-from-top-4 duration-300">
      <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="flex items-start space-x-4">
          <div class="w-14 h-14 rounded-2xl bg-emerald-500 text-slate-950 flex items-center justify-center shrink-0 shadow-lg shadow-emerald-500/30">
            <GraduationCap class="w-8 h-8" />
          </div>
          <div>
            <div class="inline-flex items-center space-x-1.5 px-3 py-0.5 rounded-full bg-emerald-500/20 border border-emerald-400/40 text-emerald-300 text-[11px] font-bold uppercase tracking-wider mb-1.5">
              <CheckCircle class="w-3.5 h-3.5" />
              <span>Officially Enrolled & Validated</span>
            </div>
            <h3 class="text-xl font-extrabold text-white tracking-tight">Congratulations! Your Official Student Account is Ready!</h3>
            <p class="text-xs text-slate-300 mt-1 max-w-xl">
              Your payment has been successfully credited and official Certificate of Registration issued. You can now access your schedule, grades, and school timetable in the Student Portal.
            </p>
          </div>
        </div>

        <div class="bg-slate-950/90 p-4 rounded-2xl border border-emerald-500/50 w-full md:w-auto min-w-[280px] font-mono text-xs space-y-2 shrink-0 shadow-xl">
          <div class="text-[10px] uppercase font-bold text-emerald-400 tracking-wider flex items-center justify-between">
            <span>Student Portal Credentials</span>
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
          </div>
          <div class="flex justify-between border-b border-slate-800 pb-1.5 pt-1">
            <span class="text-slate-400">Student ID / Username:</span>
            <strong class="text-white font-bold text-sm text-emerald-300">{{ application?.student_no || application?.assessment_info?.student_number || 'Generated' }}</strong>
          </div>
          <div class="flex justify-between border-b border-slate-800 pb-1.5">
            <span class="text-slate-400">Default Password:</span>
            <strong class="text-amber-300 font-bold tracking-wider">{{ (application?.last_name || '').toUpperCase() }}</strong>
          </div>
          <div class="text-[10px] text-slate-400 italic pt-0.5">Password is your Last Name in ALL CAPS.</div>
          <button 
            @click="goToStudentLogin" 
            class="w-full mt-2 py-2.5 px-4 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition flex items-center justify-center space-x-1.5 shadow-md shadow-emerald-500/30 cursor-pointer"
          >
            <span>Proceed to Student Portal Login</span>
            <ArrowRight class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- DEFICIENT DOCUMENTS ALERT BANNER -->
    <div v-if="deficientDocs.length > 0" class="no-print p-4 sm:p-5 rounded-2xl bg-rose-50 border-2 border-rose-300 text-rose-950 text-xs mb-6 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div class="flex items-start space-x-3.5">
        <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 shadow-sm mt-0.5">
          <AlertTriangle class="w-5 h-5 animate-pulse" />
        </div>
        <div>
          <h4 class="font-extrabold text-sm text-rose-950">Action Required: {{ deficientDocs.length }} Document(s) Marked as Deficient</h4>
          <p class="text-xs text-rose-800 mt-0.5">
            The Registrar has reviewed your requirements and marked <span class="font-bold underline">{{ deficientDocs.map(d => d.document_type).join(', ') }}</span> as deficient. Please re-upload clearer copies in Step 3.
          </p>
        </div>
      </div>
      <button @click="selectStep(3)" class="whitespace-nowrap px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs shadow-sm transition flex items-center space-x-1.5 shrink-0 cursor-pointer">
        <span>Fix Deficient Files</span>
        <ArrowRight class="w-3.5 h-3.5" />
      </button>
    </div>

    <!-- Alert / Notifications (Hidden in Print) -->
    <div v-if="successMessage" class="no-print p-4 rounded-2xl bg-emerald-950/90 border border-emerald-500 text-emerald-300 text-xs mb-6 flex items-center justify-between shadow-md">
      <div class="flex items-center space-x-2">
        <CheckCircle class="w-4 h-4 text-emerald-400 shrink-0" />
        <span>{{ successMessage }}</span>
      </div>
      <button @click="successMessage = ''" class="text-emerald-400 hover:text-white font-bold text-xs cursor-pointer">✕</button>
    </div>

    <div v-if="errorMessage" class="no-print p-4 rounded-2xl bg-rose-950/90 border border-rose-800 text-rose-300 text-xs mb-6 flex items-center justify-between shadow-md">
      <div class="flex items-center space-x-2">
        <AlertCircle class="w-4 h-4 text-rose-400 shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>
      <button @click="errorMessage = ''" class="text-rose-400 hover:text-white font-bold text-xs cursor-pointer">✕</button>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW A: ADMISSION APPLICATION WIZARD (Steps 1 to 5)                       -->
    <!-- ========================================================================= -->
    <div v-if="currentApplicantTab === 'wizard'">
      <!-- HORIZONTAL PROGRESS STEPPER BAR (Full-Width, Responsive & Intuitive) -->
      <div class="no-print bg-white rounded-3xl p-5 sm:p-6 border border-slate-200 shadow-2xs mb-8">
        <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100 flex-wrap gap-2">
          <div class="flex items-center space-x-2">
            <div class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></div>
            <span class="text-xs font-black uppercase tracking-wider text-slate-900">Admission Process Stepper</span>
          </div>
          <div class="flex items-center space-x-2">
            <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-blue-50 text-blue-950 border border-blue-200">
              Step {{ activeStep }} of 5: {{ steps.find(s => s.id === activeStep)?.title }}
            </span>
          </div>
        </div>

      <!-- Linear Step Nodes Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
        <button
          v-for="st in steps"
          :key="st.id"
          type="button"
          :disabled="isSaving"
          @click="selectStep(st.id)"
          :class="[
            'p-3 sm:p-3.5 rounded-2xl text-left transition-all border flex items-center space-x-3 group',
            isSaving ? 'cursor-wait opacity-80' : '',
            activeStep === st.id 
              ? 'bg-[#08182b] text-white border-blue-900 shadow-md ring-2 ring-blue-900/20 cursor-default' 
              : isStepDone(st.id) 
                ? 'bg-emerald-50/70 hover:bg-emerald-100/70 border-emerald-300 text-slate-900 cursor-pointer' 
                : 'bg-slate-50 border-slate-200 text-slate-400 cursor-default opacity-80'
          ]"
        >
          <!-- Step Icon or Number -->
          <div 
            :class="[
              'w-8 h-8 rounded-xl flex items-center justify-center font-extrabold text-xs shrink-0 transition',
              activeStep === st.id 
                ? 'bg-blue-600 text-white shadow-sm' 
                : isStepDone(st.id) 
                  ? 'bg-emerald-500 text-white shadow-xs' 
                  : 'bg-slate-200 text-slate-500'
            ]"
          >
            <span v-if="isSaving && activeStep === st.id" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            <Check v-else-if="isStepDone(st.id) && activeStep !== st.id" class="w-4 h-4 stroke-[3]" />
            <Lock v-else-if="!canAccessStep(st.id)" class="w-3.5 h-3.5" />
            <span v-else>{{ st.id }}</span>
          </div>

          <!-- Step Info -->
          <div class="min-w-0 flex-1">
            <div class="font-bold text-xs truncate leading-tight" :class="activeStep === st.id ? 'text-white' : 'text-slate-800'">
              {{ st.title }}
            </div>
            <div class="text-[10px] truncate leading-tight mt-0.5" :class="activeStep === st.id ? 'text-blue-200' : (isStepDone(st.id) ? 'text-emerald-700 font-semibold' : 'text-slate-400')">
              {{ isSaving && activeStep === st.id ? 'Saving changes...' : (isStepDone(st.id) && activeStep !== st.id ? '✓ Completed (Click to Edit)' : (activeStep === st.id ? 'Currently Active' : 'Upcoming')) }}
            </div>
          </div>
        </button>
      </div>

      <!-- Step Lock / Incomplete Warning Notice -->
      <div v-if="stepLockNotice" class="mt-4 p-3.5 rounded-2xl bg-amber-50 border border-amber-300 text-amber-950 text-xs flex items-center justify-between shadow-2xs animate-in fade-in duration-200">
        <div class="flex items-center space-x-2.5">
          <div class="w-7 h-7 rounded-lg bg-amber-200 text-amber-900 flex items-center justify-center shrink-0">
            <Lock class="w-4 h-4 text-amber-800" />
          </div>
          <span class="font-semibold">{{ stepLockNotice }}</span>
        </div>
        <button @click="stepLockNotice = ''" class="text-amber-700 hover:text-amber-950 text-xs font-bold px-2 py-1 rounded cursor-pointer">✕</button>
      </div>
    </div>

    <!-- DOCUMENT REQUIREMENTS GUIDE MODAL / OVERLAY (When tab=checklist is clicked in Sidebar) -->
    <div v-if="route.query.tab === 'checklist'" class="no-print bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-lg mb-8 animate-in fade-in duration-200">
      <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-900 border border-blue-200 flex items-center justify-center font-bold">
            <FileCheck class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900">DepEd Admission Requirements Guide</h3>
            <p class="text-xs text-slate-500">Official checklist of credentials required by the Registrar for S.Y. 2026-2027.</p>
          </div>
        </div>
        <router-link to="/admission" class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
          ✕ Close Guide
        </router-link>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-for="req in requiredDocsList" :key="req.type" class="p-4 rounded-2xl border border-slate-200 bg-slate-50/60 flex items-start space-x-3.5">
          <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-900 flex items-center justify-center shrink-0 mt-0.5">
            <FileText class="w-4 h-4" />
          </div>
          <div>
            <div class="flex items-center space-x-2">
              <h4 class="font-bold text-xs text-slate-900">{{ req.type }}</h4>
              <span v-if="req.required" class="px-2 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-900">Required</span>
            </div>
            <p class="text-[11px] text-slate-600 mt-0.5">{{ req.desc }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- HELPDESK & HOTLINES CARD (When tab=hotlines is clicked in Sidebar) -->
    <div v-if="route.query.tab === 'hotlines'" class="no-print bg-[#08182b] text-white rounded-3xl p-6 sm:p-8 border border-slate-800 shadow-xl mb-8 animate-in fade-in duration-200">
      <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-6">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-2xl bg-blue-950 text-blue-300 border border-blue-500/30 flex items-center justify-center font-bold">
            <Phone class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-white">Admissions Helpdesk & Contact Center</h3>
            <p class="text-xs text-slate-400">Direct communication lines for admission assistance and inquiries.</p>
          </div>
        </div>
        <router-link to="/admission" class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition">
          ✕ Close
        </router-link>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
        <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1">
          <div class="font-bold text-blue-300 text-xs uppercase font-mono">Admissions Office</div>
          <div class="text-slate-200 font-bold text-sm">(055) 888-7766</div>
          <div class="text-slate-400 text-[11px]">Mon–Fri, 8:00 AM – 5:00 PM</div>
        </div>
        <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1">
          <div class="font-bold text-blue-300 text-xs uppercase font-mono">Mobile / SMS Support</div>
          <div class="text-slate-200 font-bold text-sm">0917-111-0001</div>
          <div class="text-slate-400 text-[11px]">Globe / TM SMS Inquiry Line</div>
        </div>
        <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1">
          <div class="font-bold text-blue-300 text-xs uppercase font-mono">Admissions Email</div>
          <div class="text-slate-200 font-bold text-sm">admissions@student.bsla.edu.ph</div>
          <div class="text-slate-400 text-[11px]">Online document validation support</div>
        </div>
      </div>
    </div>

    <!-- MAIN FULL-WIDTH WIZARD WORKSPACE -->
    <main class="space-y-6">
      
      <!-- STEP 1: PERSONAL & DEMOGRAPHIC DETAILS -->
      <div v-if="activeStep === 1" class="space-y-6">
          
          <form @submit.prevent="saveApplicationDetails(false)" novalidate class="space-y-6">

            <!-- Validation Error Summary Banner -->
            <div v-if="Object.keys(fieldErrors).length > 0" class="p-4 sm:p-5 rounded-2xl bg-rose-50 border-2 border-rose-300 text-rose-950 text-xs mb-4 shadow-sm flex items-start space-x-3.5 animate-in fade-in duration-200">
              <div class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 shadow-sm mt-0.5">
                <AlertTriangle class="w-5 h-5 animate-pulse" />
              </div>
              <div>
                <h4 class="font-extrabold text-sm text-rose-950">Incomplete Required Fields</h4>
                <p class="text-xs text-rose-800 mt-0.5 font-medium">Please correct the highlighted fields marked with an asterisk <span class="font-bold text-rose-600">(*)</span> before proceeding or switching steps in the admission process.</p>
              </div>
            </div>

            <!-- Card A: Learner Identification & LRN -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-5">
              <div class="flex items-center space-x-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-900 flex items-center justify-center font-bold text-xs shrink-0 border border-blue-200">
                  1A
                </div>
                <div>
                  <h3 class="text-sm font-bold text-slate-900">Learner Identification & Contact</h3>
                  <p class="text-[11px] text-slate-500">Official DepEd LRN and active mobile contact number for SMS updates.</p>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div :class="{ 'has-field-error': fieldErrors.lrn }" data-field-error="true">
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                    12-Digit DepEd LRN <span class="text-rose-500 font-bold">*</span>
                  </label>
                  <input 
                    v-model="form.lrn" 
                    type="text" 
                    maxlength="12" 
                    @keydown="blockNonNumeric($event)"
                    @input="handleNumericInput('lrn', $event, 12); clearFieldError('lrn')"
                    placeholder="e.g. 102938475611" 
                    :class="fieldErrors.lrn ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                    class="w-full px-3.5 py-2.5 rounded-xl border bg-slate-50/60 focus:bg-white text-xs font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition" 
                  />
                  <p v-if="fieldErrors.lrn" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1.5 font-semibold">
                    <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.lrn }}
                  </p>
                  <span v-else class="text-[10px] text-slate-500 block mt-1">Found on Form 137 / 138 report card</span>
                </div>
                <div :class="{ 'has-field-error': fieldErrors.applicant_type }">
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Applicant Type <span class="text-rose-500 font-bold">*</span>
                  </label>
                  <select 
                    v-model="form.applicant_type" 
                    @change="clearFieldError('applicant_type')"
                    :class="fieldErrors.applicant_type ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20' : 'border-slate-300'"
                    class="w-full px-3.5 py-2.5 rounded-xl border bg-slate-50/60 focus:bg-white text-xs font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition"
                  >
                    <option value="New Student">New Student (Fresh Enrollee)</option>
                    <option value="Transferee">Transferee from Other School</option>
                  </select>
                  <p v-if="fieldErrors.applicant_type" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1.5 font-semibold">
                    <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.applicant_type }}
                  </p>
                </div>
                <div :class="{ 'has-field-error': fieldErrors.contact_number }">
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Contact Mobile Number <span class="text-rose-500 font-bold">*</span>
                  </label>
                  <input 
                    v-model="form.contact_number" 
                    type="tel" 
                    maxlength="11"
                    @keydown="blockNonNumeric($event)"
                    @input="handleNumericInput('contact_number', $event, 11); clearFieldError('contact_number')"
                    placeholder="09171234567" 
                    :class="fieldErrors.contact_number ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                    class="w-full px-3.5 py-2.5 rounded-xl border bg-slate-50/60 focus:bg-white text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition" 
                  />
                  <p v-if="fieldErrors.contact_number" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1.5 font-semibold">
                    <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.contact_number }}
                  </p>
                  <span v-else class="text-[10px] text-slate-500 block mt-1">11-digit mobile (e.g. 0917XXXXXXX)</span>
                </div>
              </div>
            </div>

            <!-- Card B: Student Personal Information & Residence -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-5">
              <div class="flex items-center space-x-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-900 flex items-center justify-center font-bold text-xs shrink-0 border border-blue-200">
                  1B
                </div>
                <div>
                  <h3 class="text-sm font-bold text-slate-900">Student Personal & Demographic Details</h3>
                  <p class="text-[11px] text-slate-500">Must strictly match the student's PSA Birth Certificate copy.</p>
                </div>
              </div>

              <!-- Full Name Fields -->
              <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div :class="{ 'has-field-error': fieldErrors.first_name }">
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                    First Name <span class="text-rose-500 font-bold">*</span>
                  </label>
                  <input 
                    v-model="form.first_name" 
                    type="text" 
                    @keydown="blockNonAlphabetic($event)"
                    @input="handleAlphabeticInput('first_name', $event); clearFieldError('first_name')"
                    placeholder="Given Name" 
                    :class="fieldErrors.first_name ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                    class="w-full px-3.5 py-2.5 rounded-xl border bg-slate-50/60 focus:bg-white text-xs font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition" 
                  />
                  <p v-if="fieldErrors.first_name" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1 font-semibold">
                    <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.first_name }}
                  </p>
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Middle Name</label>
                  <input 
                    v-model="form.middle_name" 
                    type="text" 
                    @keydown="blockNonAlphabetic($event)"
                    @input="handleAlphabeticInput('middle_name', $event)"
                    placeholder="Middle Name" 
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-slate-50/60 focus:bg-white text-xs font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition" 
                  />
                </div>
                <div :class="{ 'has-field-error': fieldErrors.last_name }">
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Last Name <span class="text-rose-500 font-bold">*</span>
                  </label>
                  <input 
                    v-model="form.last_name" 
                    type="text" 
                    @keydown="blockNonAlphabetic($event)"
                    @input="handleAlphabeticInput('last_name', $event); clearFieldError('last_name')"
                    placeholder="Family Name" 
                    :class="fieldErrors.last_name ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                    class="w-full px-3.5 py-2.5 rounded-xl border bg-slate-50/60 focus:bg-white text-xs font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition" 
                  />
                  <p v-if="fieldErrors.last_name" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1 font-semibold">
                    <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.last_name }}
                  </p>
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Suffix</label>
                  <input 
                    v-model="form.suffix" 
                    type="text" 
                    @keydown="blockNonAlphabetic($event)"
                    @input="handleAlphabeticInput('suffix', $event)"
                    placeholder="Jr., III (Optional)" 
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-slate-50/60 focus:bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition" 
                  />
                </div>
              </div>

              <!-- Gender & Birth Details -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                <div :class="{ 'has-field-error': fieldErrors.gender }">
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Biological Gender <span class="text-rose-500 font-bold">*</span>
                  </label>
                  <select 
                    v-model="form.gender" 
                    @change="clearFieldError('gender')"
                    :class="fieldErrors.gender ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                    class="w-full px-3.5 py-2.5 rounded-xl border bg-slate-50/60 focus:bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition"
                  >
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                  </select>
                  <p v-if="fieldErrors.gender" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1 font-semibold">
                    <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.gender }}
                  </p>
                </div>
                <div :class="{ 'has-field-error': fieldErrors.birthdate }">
                  <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                      Date of Birth <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <span 
                      v-if="applicantAge !== null" 
                      class="text-[10px] font-bold px-2 py-0.5 rounded-md"
                      :class="applicantAge >= 11 && applicantAge <= 40 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                    >
                      Age: {{ applicantAge }} y/o {{ applicantAge >= 11 && applicantAge <= 40 ? '✓' : '(Min 11 y/o)' }}
                    </span>
                  </div>
                  <input 
                    v-model="form.birthdate" 
                    type="date" 
                    :min="minBirthdate" 
                    :max="maxBirthdate" 
                    @input="clearFieldError('birthdate')"
                    :class="fieldErrors.birthdate ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                    class="w-full px-3.5 py-2.5 rounded-xl border bg-slate-50/60 focus:bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition" 
                  />
                  <p v-if="fieldErrors.birthdate" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1 font-semibold">
                    <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.birthdate }}
                  </p>
                </div>
                <div :class="{ 'has-field-error': fieldErrors.birthplace }">
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Place of Birth <span class="text-rose-500 font-bold">*</span>
                  </label>
                  <input 
                    v-model="form.birthplace" 
                    type="text" 
                    @input="clearFieldError('birthplace')"
                    placeholder="City / Municipality" 
                    :class="fieldErrors.birthplace ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                    class="w-full px-3.5 py-2.5 rounded-xl border bg-slate-50/60 focus:bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition" 
                  />
                  <p v-if="fieldErrors.birthplace" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1 font-semibold">
                    <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.birthplace }}
                  </p>
                </div>
              </div>

              <!-- Address Fields -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                <div :class="{ 'has-field-error': fieldErrors.address_barangay }">
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Barangay <span class="text-rose-500 font-bold">*</span>
                  </label>
                  <input 
                    v-model="form.address_barangay" 
                    type="text" 
                    @input="clearFieldError('address_barangay')"
                    placeholder="e.g. Barangay 405" 
                    :class="fieldErrors.address_barangay ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                    class="w-full px-3.5 py-2.5 rounded-xl border bg-slate-50/60 focus:bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition" 
                  />
                  <p v-if="fieldErrors.address_barangay" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1 font-semibold">
                    <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.address_barangay }}
                  </p>
                </div>
                <div :class="{ 'has-field-error': fieldErrors.address_city }">
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                    City / Municipality <span class="text-rose-500 font-bold">*</span>
                  </label>
                  <input 
                    v-model="form.address_city" 
                    type="text" 
                    @input="clearFieldError('address_city')"
                    placeholder="e.g. Biringan City" 
                    :class="fieldErrors.address_city ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                    class="w-full px-3.5 py-2.5 rounded-xl border bg-slate-50/60 focus:bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition" 
                  />
                  <p v-if="fieldErrors.address_city" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1 font-semibold">
                    <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.address_city }}
                  </p>
                </div>
                <div :class="{ 'has-field-error': fieldErrors.address_province }">
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Province <span class="text-rose-500 font-bold">*</span>
                  </label>
                  <input 
                    v-model="form.address_province" 
                    type="text" 
                    @input="clearFieldError('address_province')"
                    placeholder="e.g. Samar" 
                    :class="fieldErrors.address_province ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                    class="w-full px-3.5 py-2.5 rounded-xl border bg-slate-50/60 focus:bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition" 
                  />
                  <p v-if="fieldErrors.address_province" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1 font-semibold">
                    <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.address_province }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Card C: Parent / Guardian & Emergency Contact -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-5">
              <div class="flex items-center space-x-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-900 flex items-center justify-center font-bold text-xs shrink-0 border border-blue-200">
                  1C
                </div>
                <div>
                  <h3 class="text-sm font-bold text-slate-900">Parent / Guardian & Emergency Information</h3>
                  <p class="text-[11px] text-slate-500">Contact information for official school notifications and academic notices.</p>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div :class="{ 'has-field-error': fieldErrors.guardian_name }">
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Parent / Guardian Full Name <span class="text-rose-500 font-bold">*</span>
                  </label>
                  <input 
                    v-model="form.guardian_name" 
                    type="text" 
                    @keydown="blockNonAlphabetic($event)"
                    @input="handleAlphabeticInput('guardian_name', $event); clearFieldError('guardian_name')"
                    placeholder="Guardian's Name" 
                    :class="fieldErrors.guardian_name ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                    class="w-full px-3.5 py-2.5 rounded-xl border bg-slate-50/60 focus:bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition" 
                  />
                  <p v-if="fieldErrors.guardian_name" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1 font-semibold">
                    <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.guardian_name }}
                  </p>
                </div>
                <div :class="{ 'has-field-error': fieldErrors.guardian_relationship }">
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Relationship to Student <span class="text-rose-500 font-bold">*</span>
                  </label>
                  <input 
                    v-model="form.guardian_relationship" 
                    type="text" 
                    @keydown="blockNonAlphabetic($event)"
                    @input="handleAlphabeticInput('guardian_relationship', $event); clearFieldError('guardian_relationship')"
                    placeholder="e.g. Mother, Father, Aunt" 
                    :class="fieldErrors.guardian_relationship ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                    class="w-full px-3.5 py-2.5 rounded-xl border bg-slate-50/60 focus:bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition" 
                  />
                  <p v-if="fieldErrors.guardian_relationship" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1 font-semibold">
                    <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.guardian_relationship }}
                  </p>
                </div>
                <div :class="{ 'has-field-error': fieldErrors.guardian_contact }">
                  <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Guardian Contact Number <span class="text-rose-500 font-bold">*</span>
                  </label>
                  <input 
                    v-model="form.guardian_contact" 
                    type="tel" 
                    maxlength="11"
                    @keydown="blockNonNumeric($event)"
                    @input="handleNumericInput('guardian_contact', $event, 11); clearFieldError('guardian_contact')"
                    placeholder="09171234567" 
                    :class="fieldErrors.guardian_contact ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                    class="w-full px-3.5 py-2.5 rounded-xl border bg-slate-50/60 focus:bg-white text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 transition" 
                  />
                  <p v-if="fieldErrors.guardian_contact" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1 font-semibold">
                    <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.guardian_contact }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Submit Button Area -->
            <div class="flex justify-end pt-2">
              <button 
                type="submit" 
                :disabled="isSaving"
                class="px-8 py-3.5 rounded-xl font-bold bg-blue-900 hover:bg-blue-800 text-white text-xs sm:text-sm shadow-md hover:scale-[1.01] active:scale-[0.99] transition flex items-center space-x-2 cursor-pointer border border-blue-800 disabled:opacity-60 disabled:cursor-not-allowed"
              >
                <span v-if="isSaving && activeStep === 1" class="flex items-center space-x-2">
                  <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                  <span>Saving Demographics...</span>
                </span>
                <span v-else class="flex items-center space-x-2">
                  <span>Save Demographics & Proceed to Step 2</span>
                  <ArrowRight class="w-4 h-4" />
                </span>
              </button>
            </div>
          </form>
        </div>

        <!-- STEP 2: GRADE LEVEL & ACADEMIC STRAND -->
        <div v-if="activeStep === 2" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
          <div class="border-b border-slate-100 pb-4 mb-2">
            <h2 class="text-lg font-bold text-slate-800">Step 2: Academic Program & DepEd Voucher Subsidy</h2>
            <p class="text-xs text-slate-500 mt-1">Select your desired Junior High School grade or Senior High School strand.</p>
          </div>

          <!-- Validation Error Summary Banner -->
          <div v-if="Object.keys(fieldErrors).length > 0" class="p-4 sm:p-5 rounded-2xl bg-rose-50 border-2 border-rose-300 text-rose-950 text-xs shadow-sm flex items-start space-x-3.5 animate-in fade-in duration-200">
            <div class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 shadow-sm mt-0.5">
              <AlertTriangle class="w-5 h-5 animate-pulse" />
            </div>
            <div>
              <h4 class="font-extrabold text-sm text-rose-950">Incomplete Academic Details</h4>
              <p class="text-xs text-rose-800 mt-0.5 font-medium">Please review and complete the highlighted required fields marked with <span class="font-bold text-rose-600">*</span> before proceeding or switching steps in the admission process.</p>
            </div>
          </div>

          <form @submit.prevent="saveApplicationDetails(true)" novalidate class="space-y-6">
            <!-- Grade Level Selector -->
            <div :class="{ 'has-field-error': fieldErrors.grade_level_id }" data-field-error="true">
              <label class="block text-xs font-semibold text-slate-700 mb-1">
                Grade Level <span class="text-rose-500 font-bold">*</span>
              </label>
              <select 
                v-model="form.grade_level_id" 
                @change="clearFieldError('grade_level_id')"
                :class="fieldErrors.grade_level_id ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                class="w-full px-3.5 py-2.5 rounded-xl border text-xs bg-white font-medium focus:outline-none focus:ring-2 focus:ring-blue-900 transition"
              >
                <option :value="null">-- Select Grade Level --</option>
                <optgroup label="Junior High School (JHS)">
                  <option v-for="gl in jhsLevels" :key="gl.id" :value="gl.id">{{ gl.name }} ({{ gl.code }})</option>
                </optgroup>
                <optgroup label="Senior High School (SHS)">
                  <option v-for="gl in shsLevels" :key="gl.id" :value="gl.id">{{ gl.name }} ({{ gl.code }})</option>
                </optgroup>
              </select>
              <p v-if="fieldErrors.grade_level_id" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1.5 font-semibold">
                <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.grade_level_id }}
              </p>
            </div>

            <!-- Track, Strand & Voucher Subsidy (ONLY IF SENIOR HIGH SCHOOL: GRADE 11 & 12) -->
            <div v-if="isSHS" class="space-y-4 p-5 rounded-2xl bg-blue-50/60 border border-blue-200 animate-in fade-in duration-200">
              <div class="flex items-center space-x-2 text-xs font-bold text-blue-950 border-b border-blue-200/60 pb-2">
                <GraduationCap class="w-4 h-4 text-blue-900" />
                <span>Senior High School Track, Strand & DepEd Voucher Subsidy</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div :class="{ 'has-field-error': fieldErrors.track_id }">
                  <label class="block text-xs font-bold text-slate-700 mb-1">
                    Senior High Track <span class="text-rose-500 font-bold">*</span>
                  </label>
                  <select 
                    v-model="form.track_id" 
                    @change="clearFieldError('track_id')"
                    :class="fieldErrors.track_id ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                    class="w-full px-3.5 py-2.5 rounded-xl border text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-900 transition"
                  >
                    <option :value="null">-- Select Track --</option>
                    <option v-for="tr in academicOptions.tracks" :key="tr.id" :value="tr.id">{{ tr.name }} ({{ tr.code }})</option>
                  </select>
                  <p v-if="fieldErrors.track_id" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1 font-semibold">
                    <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.track_id }}
                  </p>
                </div>

                <div :class="{ 'has-field-error': fieldErrors.strand_id }">
                  <label class="block text-xs font-bold text-slate-700 mb-1">
                    Senior High Strand <span class="text-rose-500 font-bold">*</span>
                  </label>
                  <select 
                    v-model="form.strand_id" 
                    @change="clearFieldError('strand_id')"
                    :class="fieldErrors.strand_id ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                    class="w-full px-3.5 py-2.5 rounded-xl border text-xs bg-white font-medium focus:outline-none focus:ring-2 focus:ring-blue-900 transition"
                  >
                    <option :value="null">-- Select Strand --</option>
                    <option v-for="st in filteredStrands" :key="st.id" :value="st.id">{{ st.name }} ({{ st.code }})</option>
                  </select>
                  <p v-if="fieldErrors.strand_id" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1 font-semibold">
                    <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.strand_id }}
                  </p>
                </div>

                <div :class="{ 'has-field-error': fieldErrors.voucher_status }">
                  <label class="block text-xs font-bold text-slate-700 mb-1">
                    DepEd Voucher Category <span class="text-rose-500 font-bold">*</span>
                  </label>
                  <select 
                    v-model="form.voucher_status" 
                    @change="clearFieldError('voucher_status')"
                    :class="fieldErrors.voucher_status ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                    class="w-full px-3.5 py-2.5 rounded-xl border text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-900 transition"
                  >
                    <option value="None">None (Regular Full Tuition)</option>
                    <option value="Public JHS Completer (100%)">Public JHS Completer (100% Voucher)</option>
                    <option value="Private ESC Grantee (80%)">Private ESC Grantee (80% Voucher)</option>
                    <option value="Private Non-ESC Voucher (50%)">Private Non-ESC Voucher (50% Subsidy)</option>
                  </select>
                  <p v-if="fieldErrors.voucher_status" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1 font-semibold">
                    <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.voucher_status }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Previous School History (Common to All Grades) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div :class="{ 'has-field-error': fieldErrors.last_school_attended }">
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Last School Attended <span class="text-rose-500 font-bold">*</span>
                </label>
                <input 
                  v-model="form.last_school_attended" 
                  type="text" 
                  @input="clearFieldError('last_school_attended')"
                  placeholder="Name of previous school" 
                  :class="fieldErrors.last_school_attended ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                  class="w-full px-3.5 py-2.5 rounded-xl border text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-900 transition" 
                />
                <p v-if="fieldErrors.last_school_attended" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1 font-semibold">
                  <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.last_school_attended }}
                </p>
              </div>
              <div :class="{ 'has-field-error': fieldErrors.last_school_type }">
                <label class="block text-xs font-bold text-slate-700 mb-1">
                  Previous School Type <span class="text-rose-500 font-bold">*</span>
                </label>
                <select 
                  v-model="form.last_school_type" 
                  @change="clearFieldError('last_school_type')"
                  :class="fieldErrors.last_school_type ? 'border-rose-400 ring-2 ring-rose-400/20 bg-rose-50/20 focus:ring-rose-500' : 'border-slate-300'"
                  class="w-full px-3.5 py-2.5 rounded-xl border text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-900 transition"
                >
                  <option value="Public">Public School</option>
                  <option value="Private">Private Institution</option>
                </select>
                <p v-if="fieldErrors.last_school_type" class="text-[11px] text-rose-600 flex items-center gap-1 mt-1 font-semibold">
                  <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ fieldErrors.last_school_type }}
                </p>
              </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
              <button 
                type="button" 
                @click="goBackToStep1" 
                :disabled="isSaving"
                class="px-5 py-2.5 rounded-xl font-semibold text-slate-600 hover:bg-slate-100 text-sm transition cursor-pointer disabled:opacity-50"
              >
                ← Back to Step 1
              </button>
              <button 
                type="submit" 
                :disabled="isSaving"
                class="px-6 py-2.5 rounded-xl font-bold bg-emerald-600 hover:bg-emerald-500 text-white text-sm shadow-md transition flex items-center space-x-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
              >
                <span v-if="isSaving && activeStep === 2" class="flex items-center space-x-2">
                  <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                  <span>Saving Academic Program...</span>
                </span>
                <span v-else class="flex items-center space-x-2">
                  <span>Save Program & Proceed to Step 3</span>
                  <ArrowRight class="w-4 h-4" />
                </span>
              </button>
            </div>
          </form>
        </div>

        <!-- STEP 3: DOCUMENT REQUIREMENTS & UPLOAD -->
        <div v-if="activeStep === 3" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm">
          <div class="border-b border-slate-100 pb-4 mb-6">
            <h2 class="text-lg font-bold text-slate-800">Step 3: Upload DepEd Admission Requirements</h2>
            <p class="text-xs text-slate-500 mt-1">Submit your official credentials via digital upload, on-campus physical submission, or a to follow up promissory note.</p>
          </div>

          <!-- Step 3 Info & Flexible Submission Notice -->
          <div class="p-4 rounded-2xl bg-blue-50/70 border border-blue-200 mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-start space-x-3">
              <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 mt-0.5 shadow-2xs">
                <Sparkles class="w-4 h-4" />
              </div>
              <div>
                <h4 class="text-xs font-bold text-blue-950 uppercase tracking-wider">Flexible DepEd Submission Options</h4>
                <p class="text-xs text-blue-900/80 mt-0.5 leading-relaxed">
                  You can <strong>Upload Digital Copies</strong> (PDF/PNG/JPG), pledge to <strong>Submit Physical Hard Copies</strong> on campus at the Registrar counter, or file a <strong>To Follow Up</strong> promissory note for pending credentials.
                </p>
              </div>
            </div>
            <button 
              type="button" 
              @click="showBulkPhysicalModal = true"
              :disabled="missingMandatoryDocs.length === 0 || isSavingDocMode"
              class="whitespace-nowrap px-3.5 py-2 rounded-xl text-xs font-bold border transition flex items-center space-x-1.5 shrink-0"
              :class="missingMandatoryDocs.length > 0 && !isSavingDocMode ? 'bg-white border-indigo-300 text-indigo-800 hover:bg-indigo-50 shadow-2xs cursor-pointer' : 'bg-slate-100 border-slate-200 text-slate-400 cursor-not-allowed'"
              title="Pledge to submit all remaining required documents physically at the Registrar's Office"
            >
              <Building class="w-3.5 h-3.5 text-indigo-600" />
              <span>Submit All Remaining Physically</span>
            </button>
          </div>

          <!-- Requirements Progress & Checklist Tracker -->
          <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 mb-6">
            <div class="flex items-center justify-between text-xs mb-2">
              <span class="font-bold text-slate-700 flex items-center space-x-1.5">
                <FileCheck class="w-4 h-4 text-blue-900" />
                <span>Admission Requirements Completion</span>
              </span>
              <span class="font-mono font-bold" :class="hasAllMandatoryDocs ? 'text-emerald-700' : 'text-slate-700'">
                {{ fulfilledDocsCount }} of {{ mandatoryDocs.length }} Requirements Fulfilled ({{ requirementsPercentage }}%)
              </span>
            </div>
            <!-- Multi-bar -->
            <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden flex">
              <div class="bg-emerald-500 h-full transition-all duration-300" :style="{ width: `${verifiedPercentage}%` }" title="Verified"></div>
              <div class="bg-blue-500 h-full transition-all duration-300" :style="{ width: `${digitalPercentage}%` }" title="Digital Uploaded"></div>
              <div class="bg-indigo-500 h-full transition-all duration-300" :style="{ width: `${physicalPercentage}%` }" title="Physical Hard Copy"></div>
              <div class="bg-amber-500 h-full transition-all duration-300" :style="{ width: `${promissoryPercentage}%` }" title="To Follow Up"></div>
              <div class="bg-rose-500 h-full transition-all duration-300" :style="{ width: `${deficientPercentage}%` }" title="Deficient"></div>
            </div>
            <!-- Status Badges breakdown -->
            <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-slate-200 text-[11px]">
              <span class="px-2 py-0.5 rounded-md font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                ✓ {{ verifiedDocsCount }} Verified
              </span>
              <span v-if="digitalUploadedCount > 0" class="px-2 py-0.5 rounded-md font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                📄 {{ digitalUploadedCount }} Digital Uploaded
              </span>
              <span v-if="physicalCount > 0" class="px-2 py-0.5 rounded-md font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200">
                🏢 {{ physicalCount }} Physical Hard Copy
              </span>
              <span v-if="promissoryCount > 0" class="px-2 py-0.5 rounded-md font-semibold bg-amber-100 text-amber-900 border border-amber-200">
                ⏳ {{ promissoryCount }} To Follow Up
              </span>
              <span v-if="deficientDocs.length > 0" class="px-2 py-0.5 rounded-md font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                ⚠️ {{ deficientDocs.length }} Action Required
              </span>
            </div>
          </div>

          <!-- DEFICIENCY RESOLUTION HUB BANNER -->
          <div v-if="deficientDocs.length > 0" class="mb-6 p-4 sm:p-5 rounded-2xl bg-rose-50 border-2 border-rose-300 text-rose-950 text-xs shadow-sm space-y-3">
            <div class="flex items-start space-x-3">
              <div class="w-8 h-8 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 mt-0.5 shadow-sm">
                <AlertTriangle class="w-4 h-4" />
              </div>
              <div>
                <h4 class="font-extrabold text-sm text-rose-950">Action Needed: {{ deficientDocs.length }} Requirement(s) Flagged by Registrar</h4>
                <p class="text-xs text-rose-800 mt-0.5 leading-relaxed">
                  The Registrar has flagged documents below that require correction. Please review the notes and re-upload or select an alternative submission mode:
                </p>
              </div>
            </div>
            <div class="space-y-2 pt-2 border-t border-rose-200/80">
              <div v-for="defDoc in deficientDocs" :key="defDoc.id" class="p-3 bg-white rounded-xl border border-rose-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                <div>
                  <span class="font-bold text-slate-800 text-xs flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                    <span>{{ defDoc.document_type }}</span>
                  </span>
                  <div class="text-[11px] text-rose-800 mt-1 bg-rose-50 p-2 rounded-lg border border-rose-200/70">
                    <strong>Registrar Note:</strong> {{ defDoc.verification_notes || 'Please replace with a clearer scan or submit physically on campus.' }}
                  </div>
                </div>
                <div class="flex items-center space-x-2 shrink-0 flex-wrap gap-y-1">
                  <label class="cursor-pointer px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-900 hover:bg-blue-800 text-white transition flex items-center space-x-1">
                    <Upload class="w-3 h-3 text-blue-200" />
                    <span>Re-upload File</span>
                    <input type="file" @change="handleFileUpload($event, defDoc.document_type)" class="hidden" accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx" />
                  </label>
                  <button type="button" @click="openPhysicalModal(defDoc.document_type)" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-white border border-indigo-200 hover:bg-indigo-50 text-indigo-700 transition">
                    Submit Physical
                  </button>
                  <button type="button" @click="openPromissoryModal(defDoc.document_type, defDoc)" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-white border border-amber-300 hover:bg-amber-50 text-amber-800 transition">
                    To Follow Up
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="space-y-4">
            <div 
              v-for="req in requiredDocsList" 
              :key="req.type" 
              class="p-4 sm:p-5 rounded-2xl border transition flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4"
              :class="[
                getDocUploaded(req.type)
                  ? (getDocUploaded(req.type).status === 'Deficient' || getDocUploaded(req.type).status === 'Rejected')
                    ? 'border-rose-300 bg-rose-50/40 ring-1 ring-rose-200'
                    : getDocUploaded(req.type).submission_mode === 'Physical Submission'
                      ? 'border-indigo-200 bg-indigo-50/30'
                      : getDocUploaded(req.type).submission_mode === 'To Follow Up'
                        ? 'border-amber-200 bg-amber-50/30'
                        : 'border-emerald-200 bg-emerald-50/40'
                  : req.required ? 'border-amber-200 bg-amber-50/20' : 'border-slate-200 bg-white'
              ]"
            >
              <div class="flex items-start space-x-3.5 min-w-0">
                <div 
                  class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 mt-0.5 shadow-2xs"
                  :class="[
                    getDocUploaded(req.type)
                      ? (getDocUploaded(req.type).status === 'Deficient' || getDocUploaded(req.type).status === 'Rejected')
                        ? 'bg-rose-100 text-rose-700 border border-rose-300'
                        : getDocUploaded(req.type).submission_mode === 'Physical Submission'
                          ? 'bg-indigo-100 text-indigo-700 border border-indigo-200'
                          : getDocUploaded(req.type).submission_mode === 'To Follow Up'
                            ? 'bg-amber-100 text-amber-800 border border-amber-200'
                            : 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                      : 'bg-slate-100 text-slate-500 border border-slate-200'
                  ]"
                >
                  <AlertTriangle v-if="getDocUploaded(req.type)?.status === 'Deficient' || getDocUploaded(req.type)?.status === 'Rejected'" class="w-5 h-5 text-rose-600" />
                  <Building v-else-if="getDocUploaded(req.type)?.submission_mode === 'Physical Submission'" class="w-5 h-5" />
                  <Clock v-else-if="getDocUploaded(req.type)?.submission_mode === 'To Follow Up'" class="w-5 h-5" />
                  <FileCheck v-else-if="getDocUploaded(req.type)" class="w-5 h-5" />
                  <FileText v-else class="w-5 h-5" />
                </div>

                <div class="min-w-0">
                  <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                    <h4 class="font-bold text-slate-800 text-xs sm:text-sm">{{ req.type }}</h4>
                    <span v-if="req.required" class="px-2 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-900 border border-amber-200">Required</span>
                    <span v-else class="px-2 py-0.5 rounded text-[9px] font-medium bg-slate-100 text-slate-500">Optional</span>

                    <!-- Submission Mode Badges -->
                    <span v-if="getDocUploaded(req.type)?.status === 'Deficient' || getDocUploaded(req.type)?.status === 'Rejected'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300 flex items-center space-x-1">
                      <AlertTriangle class="w-3 h-3 text-rose-600" />
                      <span>Action Required: Deficient</span>
                    </span>
                    <span v-else-if="getDocUploaded(req.type)?.submission_mode === 'Physical Submission'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200 flex items-center space-x-1">
                      <Building class="w-3 h-3 text-indigo-600" />
                      <span>Physical Hard Copy</span>
                    </span>
                    <span v-else-if="getDocUploaded(req.type)?.submission_mode === 'To Follow Up'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-200 flex items-center space-x-1">
                      <Clock class="w-3 h-3 text-amber-700" />
                      <span>To Follow Up</span>
                    </span>
                    <span v-else-if="getDocUploaded(req.type)" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center space-x-1">
                      <Check class="w-3 h-3 text-emerald-600" />
                      <span>Digital Upload</span>
                    </span>
                  </div>
                  
                  <p class="text-[11px] text-slate-500 mt-0.5">{{ req.desc }}</p>

                  <!-- Details when submitted -->
                  <div v-if="getDocUploaded(req.type)" class="mt-2 text-xs space-y-1">
                    <!-- Status Badge for all modes -->
                    <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                      <span :class="getDocStatusBadge(getDocUploaded(req.type).status)" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase">
                        {{ getDocUploaded(req.type).status === 'Verified' ? '✓ Verified by Registrar' : (getDocUploaded(req.type).status === 'Deficient' ? '⚠️ Deficient' : getDocUploaded(req.type).status) }}
                      </span>
                      <span v-if="getDocUploaded(req.type).file_path" class="text-[10px] font-mono text-slate-500 font-medium">
                        {{ getDocUploaded(req.type).original_filename }} ({{ ((getDocUploaded(req.type).file_size || 0) / 1024).toFixed(0) }} KB)
                      </span>
                    </div>

                    <!-- Deficient Remarks from Registrar -->
                    <div v-if="getDocUploaded(req.type).status === 'Deficient' || getDocUploaded(req.type).status === 'Rejected'" class="p-2.5 rounded-xl bg-rose-100/90 border border-rose-200 text-rose-950 text-[11px] font-medium flex items-start space-x-1.5">
                      <AlertCircle class="w-3.5 h-3.5 text-rose-600 shrink-0 mt-0.5" />
                      <div>
                        <strong>Registrar's Note:</strong> {{ getDocUploaded(req.type).verification_notes || 'Document unreadable or invalid. Please submit a clearer copy.' }}
                      </div>
                    </div>

                    <!-- If Physical Submission -->
                    <div v-else-if="getDocUploaded(req.type).submission_mode === 'Physical Submission'" class="flex items-center space-x-2 text-[11px] text-indigo-900 font-medium">
                      <Building class="w-3.5 h-3.5 text-indigo-600 shrink-0" />
                      <span>Pledged for in-person submission at Registrar Counter (Window 3).</span>
                    </div>

                    <!-- If To Follow Up -->
                    <div v-else-if="getDocUploaded(req.type).submission_mode === 'To Follow Up'" class="space-y-0.5 text-[11px]">
                      <div class="flex items-center space-x-1.5 text-amber-950 font-medium">
                        <Calendar class="w-3.5 h-3.5 text-amber-700 shrink-0" />
                        <span>Promised Target Date: <strong class="underline font-bold text-amber-900">{{ formatDate(getDocUploaded(req.type).target_date) }}</strong></span>
                      </div>
                      <div v-if="getDocUploaded(req.type).promissory_note" class="text-slate-600 italic">
                        Note: "{{ getDocUploaded(req.type).promissory_note }}"
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Controls -->
              <div class="flex items-center space-x-2 w-full lg:w-auto justify-end flex-wrap gap-y-2 shrink-0">
                <!-- If already submitted/uploaded -->
                <template v-if="getDocUploaded(req.type)">
                  <!-- View button if file uploaded -->
                  <button 
                    v-if="getDocUploaded(req.type).file_path"
                    type="button" 
                    @click="openPreviewModal(getDocUploaded(req.type))" 
                    class="px-3 py-2 rounded-xl text-xs font-bold bg-white text-emerald-700 border border-emerald-300 hover:bg-emerald-50 flex items-center space-x-1 shadow-2xs transition cursor-pointer"
                  >
                    <Eye class="w-3.5 h-3.5" />
                    <span>View</span>
                  </button>

                  <!-- If Deficient: show explicit resolution buttons -->
                  <template v-if="getDocUploaded(req.type).status === 'Deficient' || getDocUploaded(req.type).status === 'Rejected'">
                    <label class="cursor-pointer inline-flex items-center justify-center px-3 py-2 rounded-xl text-xs font-bold bg-blue-900 hover:bg-blue-800 text-white shadow-2xs transition">
                      <Upload class="w-3.5 h-3.5 mr-1 text-blue-200" />
                      <span>Re-upload Clear Copy</span>
                      <input type="file" @change="handleFileUpload($event, req.type)" class="hidden" accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx" />
                    </label>
                    <button 
                      type="button" 
                      @click="openPhysicalModal(req.type)" 
                      class="px-2.5 py-2 rounded-xl text-xs font-semibold bg-white border border-indigo-200 hover:bg-indigo-50 text-indigo-700 shadow-2xs transition"
                      title="Switch to in-person physical submission"
                    >
                      Physical
                    </button>
                    <button 
                      type="button" 
                      @click="openPromissoryModal(req.type, getDocUploaded(req.type))" 
                      class="px-2.5 py-2 rounded-xl text-xs font-semibold bg-white border border-amber-300 hover:bg-amber-50 text-amber-800 shadow-2xs transition"
                      title="Update promissory note"
                    >
                      To Follow Up
                    </button>
                  </template>

                  <!-- Otherwise (Verified or Pending) -->
                  <template v-else>
                    <!-- Edit Date button if To Follow Up -->
                    <button 
                      v-if="getDocUploaded(req.type).submission_mode === 'To Follow Up'"
                      type="button" 
                      @click="openPromissoryModal(req.type, getDocUploaded(req.type))" 
                      class="px-3 py-2 rounded-xl text-xs font-bold bg-white text-amber-800 border border-amber-300 hover:bg-amber-50 flex items-center space-x-1 shadow-2xs transition cursor-pointer"
                    >
                      <Clock class="w-3.5 h-3.5 text-amber-600" />
                      <span>Edit Note</span>
                    </button>

                    <!-- Upload / Replace with digital file (only if not verified) -->
                    <label v-if="getDocUploaded(req.type).status !== 'Verified'" class="cursor-pointer inline-flex items-center justify-center px-3 py-2 rounded-xl text-xs font-bold bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 shadow-2xs transition">
                      <Upload class="w-3.5 h-3.5 mr-1 text-slate-500" />
                      <span>{{ getDocUploaded(req.type).file_path ? 'Replace' : 'Upload Digital Copy' }}</span>
                      <input type="file" @change="handleFileUpload($event, req.type)" class="hidden" accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx" />
                    </label>

                    <!-- Verified indicator if officially verified -->
                    <span v-else class="px-3 py-2 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-300 flex items-center space-x-1">
                      <Check class="w-3.5 h-3.5 text-emerald-600" />
                      <span>Verified & Locked</span>
                    </span>

                    <!-- Delete / Remove button (only if not verified) -->
                    <button 
                      v-if="getDocUploaded(req.type).status !== 'Verified'"
                      type="button" 
                      @click="openDeleteDocModal(getDocUploaded(req.type))" 
                      class="p-2 rounded-xl text-xs font-bold bg-white text-rose-700 border border-rose-200 hover:bg-rose-50 shadow-2xs transition cursor-pointer"
                      title="Remove requirement commitment"
                    >
                      <Trash2 class="w-4 h-4 text-rose-600" />
                    </button>
                  </template>
                </template>

                <!-- If unsubmitted: show 3 clear options -->
                <template v-else>
                  <label class="cursor-pointer inline-flex items-center justify-center px-3.5 py-2 rounded-xl text-xs font-bold bg-blue-900 hover:bg-blue-800 text-white shadow-2xs transition">
                    <Upload class="w-3.5 h-3.5 mr-1.5 text-blue-200" />
                    <span>Upload File</span>
                    <input type="file" @change="handleFileUpload($event, req.type)" class="hidden" accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx" />
                  </label>

                  <button 
                    type="button" 
                    @click="openPhysicalModal(req.type)" 
                    class="px-3 py-2 rounded-xl text-xs font-bold bg-white border border-indigo-200 hover:bg-indigo-50 text-indigo-700 shadow-2xs transition flex items-center space-x-1 cursor-pointer"
                    title="Commit to bringing physical paper copy to Registrar counter"
                  >
                    <Building class="w-3.5 h-3.5 text-indigo-600" />
                    <span>Submit Physical</span>
                  </button>

                  <button 
                    type="button" 
                    @click="openPromissoryModal(req.type)" 
                    class="px-3 py-2 rounded-xl text-xs font-bold bg-white border border-amber-300 hover:bg-amber-50 text-amber-800 shadow-2xs transition flex items-center space-x-1 cursor-pointer"
                    title="Submit promissory note with promised target date"
                  >
                    <Clock class="w-3.5 h-3.5 text-amber-600" />
                    <span>To Follow Up</span>
                  </button>
                </template>
              </div>
            </div>
          </div>

          <!-- Mandatory Warning Banner -->
          <div v-if="!hasAllMandatoryDocs" class="mt-6 p-4 rounded-2xl bg-amber-50 border border-amber-300 text-amber-900 text-xs flex items-start space-x-3 shadow-sm">
            <AlertTriangle class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
            <div>
              <span class="font-bold block text-amber-950 text-sm">Mandatory Requirements Pending:</span>
              <p class="text-xs text-amber-800 mt-1 leading-relaxed">
                Please complete <strong class="underline">{{ missingMandatoryDocs.join(', ') }}</strong> by <strong>uploading a digital file</strong>, selecting <strong>Submit Physical</strong> (for in-person submission on campus), or filing a <strong>To Follow Up</strong> promissory note.
              </p>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row items-center justify-between pt-6 mt-6 border-t border-slate-100 gap-4">
            <div class="flex items-center space-x-3 text-xs font-medium text-slate-500 flex-wrap gap-y-1">
              <button type="button" @click="activeStep = 2" class="px-5 py-2.5 rounded-xl font-semibold text-slate-600 hover:bg-slate-100 text-sm transition cursor-pointer">
                ← Back to Step 2
              </button>
              <span class="hidden sm:inline text-slate-300">•</span>
              <span class="inline-flex items-center space-x-1 text-emerald-700 font-bold">
                <Check class="w-3.5 h-3.5" />
                <span>{{ digitalUploadedCount }} Uploaded</span>
              </span>
              <span class="inline-flex items-center space-x-1 text-indigo-700 font-bold">
                <Building class="w-3.5 h-3.5" />
                <span>{{ physicalCount }} Physical</span>
              </span>
              <span class="inline-flex items-center space-x-1 text-amber-700 font-bold">
                <Clock class="w-3.5 h-3.5" />
                <span>{{ promissoryCount }} To Follow Up</span>
              </span>
            </div>

            <button 
              @click="openSubmitReviewModal" 
              :disabled="isSubmitting || !hasAllMandatoryDocs"
              :class="[
                hasAllMandatoryDocs 
                  ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-md cursor-pointer' 
                  : 'bg-slate-200 text-slate-400 border border-slate-300 cursor-not-allowed'
              ]"
              class="w-full sm:w-auto px-6 py-2.5 rounded-xl font-bold text-sm transition flex items-center justify-center space-x-2"
            >
              <span v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span>Submit Requirements for Review →</span>
            </button>
          </div>
        </div>

        <!-- STEP 4: ASSESSMENT & PAYMENT (WALK-IN / ONLINE PAYMONGO) -->
        <div v-if="activeStep === 4" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-xl print:p-0 print:m-0 print:border-none print:shadow-none print:rounded-none">
          <!-- Action Bar (Hidden in Print) -->
          <div class="no-print flex flex-col sm:flex-row sm:items-center justify-between pb-6 mb-6 border-b border-slate-200 gap-4">
            <div>
              <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase tracking-wider mb-1">
                <span>Tuition Assessment & Payment Portal</span>
              </div>
              <h2 class="text-lg font-bold text-slate-800">Step 4: Official Assessment Slip & Payment Options</h2>
              <p class="text-xs text-slate-500">Choose between Walk-in Cashier Payment Ticket or Instant Online PayMongo (GCash / Maya).</p>
            </div>

            <div class="flex items-center space-x-2">
              <button @click="printSlip" class="px-4 py-2.5 rounded-xl font-bold bg-slate-900 hover:bg-slate-800 text-white text-xs shadow-md transition flex items-center space-x-1.5 cursor-pointer">
                <Printer class="w-4 h-4" />
                <span>Print Assessment Form</span>
              </button>
            </div>
          </div>

          <!-- Printable Assessment Sheet -->
          <div class="border-2 border-slate-800 p-6 print:p-3.5 rounded-2xl bg-white text-slate-900 font-sans mb-8">
            <!-- Header -->
            <div class="text-center border-b-2 border-slate-800 pb-3 mb-4">
              <div class="text-[10px] font-bold tracking-widest uppercase text-slate-600">Department of Education • Republic of the Philippines</div>
              <h2 class="text-lg font-black tracking-tight uppercase mt-0.5 text-slate-950">BIRINGAN SCIENCE & LEADERSHIP ACADEMY (BSLA)</h2>
              <p class="text-[11px] text-slate-600">"Innovating for the Nation" • Academic Boulevard, Biringan City, Samar • DepEd ID: 405621</p>
              <div class="inline-block mt-2 px-3 py-0.5 rounded-full bg-slate-900 text-white text-[11px] font-extrabold font-mono uppercase tracking-wider">
                OFFICIAL ASSESSMENT & ENROLLMENT SLIP (SY 2026-2027)
              </div>
            </div>

            <!-- Student Info Matrix -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs mb-4 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
              <div>
                <span class="text-slate-500 block text-[10px] uppercase font-bold">Applicant / Student ID:</span>
                <span class="font-bold font-mono text-emerald-800">{{ application?.student_no || application?.assessment_info?.student_number || 'Pending Final OR' }}</span>
              </div>
              <div>
                <span class="text-slate-500 block text-[10px] uppercase font-bold">Student Full Name:</span>
                <span class="font-bold">{{ application?.last_name }}, {{ application?.first_name }} {{ application?.middle_name || '' }}</span>
              </div>
              <div>
                <span class="text-slate-500 block text-[10px] uppercase font-bold">Grade & Track / Strand:</span>
                <span class="font-bold text-slate-900">{{ application?.grade_level_name }} {{ application?.strand_code ? '(' + application.strand_code + ')' : '' }}</span>
              </div>
              <div>
                <span class="text-slate-500 block text-[10px] uppercase font-bold">Voucher Category:</span>
                <span class="font-bold text-emerald-700">{{ application?.voucher_status || 'None' }}</span>
              </div>
            </div>

            <!-- Fee Assessment Calculation -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-mono mb-4 pt-2 border-t border-slate-200">
              <div class="space-y-1 bg-slate-50 p-3 rounded-xl border border-slate-200">
                <div class="font-bold text-slate-800 uppercase font-sans text-[11px] mb-1">DepEd Subsidy Credit:</div>
                <div class="flex justify-between">
                  <span>Gross Tuition & Fees:</span>
                  <span>₱{{ (estimatedAssessment.tuition + estimatedAssessment.misc).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
                </div>
                <div class="flex justify-between text-emerald-700 font-bold">
                  <span>Less Voucher Subsidy:</span>
                  <span>- ₱{{ estimatedAssessment.voucherDiscount.toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
                </div>
              </div>

              <div class="space-y-1 bg-emerald-50 p-3 rounded-xl border border-emerald-200">
                <div class="font-bold text-emerald-950 uppercase font-sans text-[11px] mb-1">Payable Summary:</div>
                <div class="flex justify-between font-extrabold text-sm text-slate-900">
                  <span>Net Payable:</span>
                  <span>₱{{ estimatedAssessment.netPayable.toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
                </div>
                <div class="flex justify-between text-emerald-800 font-bold border-t border-emerald-300 pt-1">
                  <span>Minimum Downpayment:</span>
                  <span>₱{{ estimatedAssessment.downpayment.toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- PAYMENT STATUS BANNERS & WORKFLOW -->
          <div class="no-print space-y-6">

            <!-- PAYMENT LOCKED UNTIL REGISTRAR APPROVAL -->
            <div v-if="!['Approved', 'Queued for Enrollment', 'Assessed', 'Enrolled'].includes(application?.status)" class="p-8 rounded-3xl bg-amber-50 border-2 border-amber-300 text-amber-950 text-center space-y-4 my-4 shadow-sm animate-in fade-in">
              <div class="w-16 h-16 rounded-full bg-amber-100 border border-amber-300 text-amber-700 flex items-center justify-center mx-auto shadow-inner">
                <Lock class="w-8 h-8" />
              </div>
              <div class="max-w-xl mx-auto space-y-2">
                <div class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-amber-200 text-amber-900 text-[10px] font-extrabold uppercase tracking-wider">
                  <span>Payment Locked — Awaiting Registrar Evaluation</span>
                </div>
                <h3 class="text-lg font-black text-amber-950">Application Must Be Approved by the Registrar First</h3>
                <p class="text-xs text-amber-900/90 leading-relaxed">
                  Your admission application is currently <strong>{{ application?.status || 'Under Review' }}</strong>. In accordance with institutional enrollment policies, all uploaded credentials and LRN records must first be authenticated and approved by the Office of the Registrar before tuition downpayment can be accepted.
                </p>
              </div>
              <div class="pt-2 flex items-center justify-center space-x-3">
                <button @click="activeStep = 3" class="px-5 py-2.5 rounded-xl font-bold bg-amber-700 hover:bg-amber-600 text-white text-xs shadow-md transition inline-flex items-center space-x-1.5 cursor-pointer">
                  <span>Review Uploaded Documents (Step 3)</span>
                </button>
              </div>
            </div>

            <!-- 1. ENROLLED / PAYMENT VERIFIED STATUS -->
            <div v-else-if="isEnrolled" class="p-6 sm:p-8 rounded-3xl bg-emerald-950/90 border-2 border-emerald-500 text-white shadow-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 animate-in fade-in duration-200">
              <div class="flex items-start space-x-3.5">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-500/30">
                  <CheckCircle class="w-6 h-6" />
                </div>
                <div>
                  <div class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 uppercase mb-1">
                    Payment Verified & Officially Enrolled
                  </div>
                  <h4 class="font-extrabold text-base text-white">Enrollment Confirmed ✓</h4>
                  <p class="text-xs text-emerald-200/80 mt-0.5">
                    Your downpayment has been confirmed by the Treasury. Your official Student ID and Certificate of Registration are now available.
                  </p>
                </div>
              </div>
              <button 
                @click="activeStep = 5" 
                class="px-6 py-3.5 rounded-xl font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs shadow-lg transition shrink-0 flex items-center justify-center space-x-2 cursor-pointer"
              >
                <span>View Official COR & Schedule</span>
                <ArrowRight class="w-4 h-4" />
              </button>
            </div>

            <!-- 2. PENDING TREASURY VERIFICATION STATUS (LOCKED PAYMENT STATE) -->
            <div v-else-if="isAwaitingVerification" class="p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-amber-950 via-slate-900 to-amber-950 border-2 border-amber-500 text-white shadow-2xl space-y-5 animate-in fade-in duration-300">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-amber-500/30 pb-4">
                <div class="flex items-center space-x-3.5">
                  <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0 border border-amber-500/40">
                    <Clock class="w-6 h-6 animate-pulse" />
                  </div>
                  <div>
                    <div class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 uppercase mb-1">
                      Awaiting Treasury Verification
                    </div>
                    <h4 class="font-extrabold text-base text-white">Online Payment Proof Submitted</h4>
                    <p class="text-xs text-amber-200/80 mt-0.5">
                      Your transaction details have been logged and sent to the Treasury Office for verification.
                    </p>
                  </div>
                </div>
              </div>

              <!-- Transaction Summary Card -->
              <div class="p-4 rounded-2xl bg-slate-950/90 border border-amber-500/30 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs font-mono">
                <div>
                  <span class="block text-[10px] text-slate-400 uppercase font-sans font-bold">Payment Method:</span>
                  <strong class="text-white">{{ onlineSubmission?.payment_channel || 'GCash' }}</strong>
                </div>
                <div>
                  <span class="block text-[10px] text-slate-400 uppercase font-sans font-bold">Reference Number:</span>
                  <strong class="text-amber-400 font-bold">{{ onlineSubmission?.reference_no || application?.assessment_info?.reference_no || 'Submitted' }}</strong>
                </div>
                <div>
                  <span class="block text-[10px] text-slate-400 uppercase font-sans font-bold">Amount Submitted:</span>
                  <strong class="text-emerald-400 font-bold">₱{{ Number(onlineSubmission?.amount_submitted || estimatedAssessment.downpayment).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</strong>
                </div>
                <div>
                  <span class="block text-[10px] text-slate-400 uppercase font-sans font-bold">Status:</span>
                  <strong class="text-amber-300 uppercase">Under Verification</strong>
                </div>
              </div>

              <div v-if="onlineSubmission?.receipt_file_path" class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-2 text-xs text-slate-300 truncate">
                  <FileText class="w-4 h-4 text-emerald-400 shrink-0" />
                  <span class="truncate">{{ onlineSubmission.receipt_original_name || 'Payment Receipt Proof' }}</span>
                </div>
                <button 
                  type="button"
                  @click="openPreviewModal({ file_path: onlineSubmission.receipt_file_path, original_filename: onlineSubmission.receipt_original_name, document_type: 'Payment Receipt Proof' })"
                  class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-emerald-400 font-bold text-xs flex items-center space-x-1.5 transition cursor-pointer"
                >
                  <Eye class="w-3.5 h-3.5" />
                  <span>View Proof</span>
                </button>
              </div>
            </div>

            <!-- 3. PAYMENT REJECTED / NEEDS REVIEW -->
            <div v-else-if="isPaymentFailed" class="p-6 sm:p-8 rounded-3xl bg-rose-950/90 border-2 border-rose-500 text-white shadow-2xl space-y-4 animate-in fade-in duration-200">
              <div class="flex items-start space-x-3.5 border-b border-rose-500/30 pb-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-500/20 text-rose-400 flex items-center justify-center shrink-0 border border-rose-500/40">
                  <AlertTriangle class="w-6 h-6" />
                </div>
                <div class="space-y-1">
                  <div class="inline-block px-3 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40 uppercase tracking-wider">
                    Payment Verification Failed – Needs Review
                  </div>
                  <h4 class="font-extrabold text-lg text-white">Payment Could Not Be Verified</h4>
                  <p class="text-xs text-rose-200/90 leading-relaxed">
                    The Treasury staff reviewed your transaction details and flagged an issue. Please read the reason below and choose how to proceed.
                  </p>
                </div>
              </div>

              <!-- Rejection Reason Card -->
              <div class="p-4 rounded-2xl bg-black/40 border border-rose-500/40 text-xs space-y-1">
                <span class="text-rose-400 font-bold uppercase tracking-wider text-[10px] block">Treasury Rejection Reason:</span>
                <p class="text-rose-100 font-medium text-sm leading-relaxed">{{ onlineSubmission?.rejection_reason || application?.rejection_reason || 'The submitted payment reference number or proof could not be verified in merchant records.' }}</p>
              </div>

              <!-- Post-Rejection Actions: Resubmit Online OR Switch to Walk-In -->
              <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
                <button 
                  @click="openPaymongoModal" 
                  class="w-full sm:w-auto px-6 py-3 rounded-xl font-extrabold bg-rose-600 hover:bg-rose-500 text-white text-xs shadow-lg shadow-rose-600/30 transition flex items-center justify-center space-x-2 cursor-pointer"
                >
                  <CreditCard class="w-4 h-4" />
                  <span>Resubmit Online Payment Proof / Details</span>
                </button>
                <button 
                  @click="switchToWalkinMode" 
                  :disabled="isSwitchingMode"
                  class="w-full sm:w-auto px-6 py-3 rounded-xl font-extrabold bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-slate-200 border border-slate-700 text-xs shadow-md transition flex items-center justify-center space-x-2 cursor-pointer"
                >
                  <Loader2 v-if="isSwitchingMode" class="w-4 h-4 animate-spin text-slate-400" />
                  <Building v-else class="w-4 h-4" />
                  <span>Switch to Walk-In Cashier Payment →</span>
                </button>
              </div>
            </div>

            <!-- 4 & 5. BEFORE PAYMENT SUBMISSION (UNLOCKED / CAN SWITCH FREELY) -->
            <div v-else class="space-y-6">

              <!-- ACTIVE WALK-IN PAYMENT SLIP (IF SCHEDULED) -->
              <div v-if="isWalkinScheduled" class="p-6 sm:p-8 rounded-3xl bg-slate-900 border-2 border-indigo-500 text-white shadow-2xl space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-4">
                  <div class="flex items-center space-x-3">
                    <div class="w-11 h-11 rounded-2xl bg-indigo-600/20 text-indigo-400 flex items-center justify-center border border-indigo-500/30">
                      <Ticket class="w-6 h-6" />
                    </div>
                    <div>
                      <h4 class="font-extrabold text-base text-white">Official Walk-in Payment Slip</h4>
                      <p class="text-[11px] text-slate-400">Cashier Window Payment Instructions & Blue Form</p>
                    </div>
                  </div>
                  <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-indigo-950 text-indigo-300 border border-indigo-500/40 text-[10px] font-bold uppercase">
                    <Clock class="w-3.5 h-3.5" />
                    <span>Walk-in Blue Form Issued / Awaiting Payment</span>
                  </div>
                </div>

                <!-- Printable Ticket Details -->
                <div class="p-4 rounded-2xl bg-slate-950/90 border border-slate-800 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-xs font-mono">
                  <div>
                    <span class="block text-[10px] text-slate-400 uppercase font-sans font-bold">Blue Form / Ticket Number:</span>
                    <strong class="text-white text-sm font-black text-indigo-300">{{ walkinTicket?.ticket_number || application?.assessment_info?.walkin_ticket_no || application?.assessment_info?.payment_ticket || 'PAY-2026-0101' }}</strong>
                  </div>
                  <div>
                    <span class="block text-[10px] text-slate-400 uppercase font-sans font-bold">Payment Location:</span>
                    <strong class="text-emerald-400 font-bold">Main Cashier Office, Bldg A</strong>
                  </div>
                  <div>
                    <span class="block text-[10px] text-slate-400 uppercase font-sans font-bold">Campus Address:</span>
                    <strong class="text-slate-200 text-[11px]">123 Education Blvd, U-Belt, Manila</strong>
                  </div>
                </div>

                <!-- Fixed Payment Instructions Box -->
                <div class="p-4 rounded-2xl bg-indigo-950/40 border border-indigo-500/30 text-xs text-indigo-200 space-y-2">
                  <div class="flex items-center space-x-2 font-bold text-white">
                    <Building class="w-4 h-4 text-indigo-400" />
                    <span>PAYMENT - Where to Send Payment</span>
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 text-[11px]">
                    <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800">
                      <strong class="text-blue-300 block">GCash Transfer:</strong>
                      <span class="font-mono font-bold text-white">0912-345-6789</span>
                      <span class="text-[10px] text-slate-400 block">(State Univ Cashier)</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800">
                      <strong class="text-emerald-300 block">Landbank Bank Account:</strong>
                      <span class="font-mono font-bold text-white">1234-5678-90</span>
                      <span class="text-[10px] text-slate-400 block">(State Univ Trust Fund)</span>
                    </div>
                  </div>
                  <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800 text-[11px]">
                    <strong class="text-amber-300 block">Walk-in Payment:</strong>
                    <span class="text-slate-300">Present printed Blue Form at the Main Cashier Window.</span>
                  </div>
                </div>

                <!-- Walk-in Slip Actions: Print Slip & SWITCH TO ONLINE PAYMENT -->
                <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-800">
                  <button 
                    @click="printSlip" 
                    class="px-5 py-2.5 rounded-xl font-bold bg-indigo-600 hover:bg-indigo-500 text-white text-xs shadow-md transition flex items-center space-x-1.5 cursor-pointer"
                  >
                    <Printer class="w-4 h-4" />
                    <span>Print Walk-in Payment Slip (Blue Form)</span>
                  </button>

                  <!-- SWITCH TO ONLINE PAYMENT BUTTON (EXPLICIT SWITCH BEFORE SUBMISSION) -->
                  <button 
                    @click="openPaymongoModal" 
                    class="px-5 py-2.5 rounded-xl font-extrabold bg-emerald-600 hover:bg-emerald-500 text-white text-xs shadow-lg shadow-emerald-600/30 transition flex items-center space-x-2 cursor-pointer"
                  >
                    <CreditCard class="w-4 h-4" />
                    <span>Switch to Online Payment (Pay with PayMongo) →</span>
                  </button>
                </div>
              </div>

              <!-- PAYMENT METHOD SELECTION & SWITCHING PANEL -->
              <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-lg space-y-6">
                <div class="text-center max-w-xl mx-auto">
                  <div class="inline-block px-3 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-100 text-emerald-900 mb-2">
                    Payment Method Selection
                  </div>
                  <h3 class="text-lg font-black text-slate-900">Choose or Switch Your Payment Method</h3>
                  <p class="text-xs text-slate-500 mt-1">
                    You can choose between <strong>Online Payment via PayMongo</strong> (GCash / Maya / Card) or <strong>Walk-in Cashier Payment</strong>. You may switch between them freely before payment completion.
                  </p>
                </div>

                <!-- SIDE-BY-SIDE METHOD CARDS -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                  <!-- OPTION 1: ONLINE PAYMENT (PAYMONGO) -->
                  <div class="p-6 rounded-3xl border-2 border-emerald-500 bg-gradient-to-br from-emerald-50/60 via-teal-50/40 to-white shadow-md flex flex-col justify-between space-y-4 hover:shadow-xl transition">
                    <div class="space-y-3">
                      <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shadow-md shadow-emerald-600/30">
                          <CreditCard class="w-6 h-6" />
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-900 border border-emerald-300">
                          Instant Online
                        </span>
                      </div>

                      <div>
                        <h4 class="font-extrabold text-base text-slate-900">Option 1: Online Payment (PayMongo)</h4>
                        <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                          Pay securely via <strong>GCash, Maya, or Credit/Debit Card</strong>. Submit your transaction reference number and receipt for Treasury verification.
                        </p>
                      </div>

                      <div class="p-3.5 rounded-2xl bg-white/90 border border-emerald-200 text-xs font-mono space-y-1 shadow-sm">
                        <div class="flex justify-between text-slate-600">
                          <span>Minimum Downpayment:</span>
                          <strong class="text-slate-900">₱{{ estimatedAssessment.downpayment.toLocaleString('en-US', {minimumFractionDigits: 2}) }}</strong>
                        </div>
                        <div class="flex justify-between text-slate-600">
                          <span>Supported Channels:</span>
                          <strong class="text-emerald-700">GCash • Maya • Visa/MC</strong>
                        </div>
                      </div>
                    </div>

                    <button 
                      @click="openPaymongoModal" 
                      class="w-full py-3.5 px-4 rounded-xl font-extrabold bg-emerald-600 hover:bg-emerald-500 text-white text-xs shadow-lg shadow-emerald-600/30 transition flex items-center justify-center space-x-2 cursor-pointer mt-2"
                    >
                      <CreditCard class="w-4 h-4" />
                      <span>{{ isWalkinScheduled ? 'Switch & Pay Online with PayMongo →' : 'Proceed with PayMongo Online Payment →' }}</span>
                    </button>
                  </div>

                  <!-- OPTION 2: WALK-IN CASHIER PAYMENT -->
                  <div class="p-6 rounded-3xl border-2 border-slate-200 hover:border-slate-400 bg-slate-50 transition flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                      <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center shadow-md">
                          <Building class="w-6 h-6" />
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-slate-200 text-slate-800">
                          On-Campus Cashier
                        </span>
                      </div>

                      <div>
                        <h4 class="font-extrabold text-base text-slate-900">Option 2: Walk-In Cashier Payment</h4>
                        <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                          Settle your tuition downpayment at the <strong>Main Cashier Office</strong> on campus.
                        </p>
                      </div>

                      <!-- FIXED PAYMENT INSTRUCTIONS -->
                      <div class="p-4 rounded-2xl bg-white border border-slate-200 text-xs space-y-2.5 shadow-inner">
                        <div class="font-bold text-slate-900 uppercase tracking-wider text-[10px] border-b border-slate-100 pb-1 flex items-center space-x-1.5">
                          <CreditCard class="w-3.5 h-3.5 text-blue-700" />
                          <span>PAYMENT - Where to Send Payment:</span>
                        </div>
                        <div class="space-y-1.5 text-[11px]">
                          <div class="p-2 rounded-xl bg-blue-50/70 border border-blue-100 flex items-start justify-between">
                            <div>
                              <strong class="text-blue-950 block">GCash Transfer:</strong>
                              <span class="text-blue-800 font-mono font-bold">0912-345-6789</span>
                            </div>
                            <span class="text-[10px] text-blue-700">(State Univ Cashier)</span>
                          </div>

                          <div class="p-2 rounded-xl bg-emerald-50/70 border border-emerald-100 flex items-start justify-between">
                            <div>
                              <strong class="text-emerald-950 block">Landbank Bank Account:</strong>
                              <span class="text-emerald-800 font-mono font-bold">1234-5678-90</span>
                            </div>
                            <span class="text-[10px] text-emerald-700">(State Univ Trust Fund)</span>
                          </div>

                          <div class="p-2 rounded-xl bg-slate-100 border border-slate-200 text-slate-800">
                            <strong class="block text-slate-900">Walk-in Payment:</strong>
                            <span>Present printed Blue Form at the Main Cashier Window.</span>
                          </div>

                          <div class="p-2 rounded-xl bg-amber-50/80 border border-amber-200 text-amber-950">
                            <strong class="block text-amber-900">School Campus Address:</strong>
                            <span>Main Cashier Office, Bldg A, 123 Education Blvd, U-Belt, Manila</span>
                          </div>
                        </div>
                      </div>
                    </div>

                    <button 
                      @click="generateWalkinTicket" 
                      class="w-full py-3.5 px-4 rounded-xl font-bold bg-slate-900 hover:bg-slate-800 text-white text-xs shadow-md transition flex items-center justify-center space-x-2 cursor-pointer mt-2"
                    >
                      <Ticket class="w-4 h-4" />
                      <span>{{ isWalkinScheduled ? 'View Printed Blue Form Slip' : 'Generate Walk-In Blue Form Slip →' }}</span>
                    </button>
                  </div>

                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- STEP 5: OFFICIAL REGISTRATION & CERTIFICATE OF ENROLLMENT (COR) -->
        <div v-if="activeStep === 5" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-xl">
          <div class="no-print border-b border-slate-100 pb-4 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase tracking-wider mb-1">
                <CheckCircle class="w-3 h-3" />
                <span>Final Academic Confirmation</span>
              </div>
              <h2 class="text-lg font-bold text-slate-800">Step 5: Official Certificate of Registration (COR) & Schedule</h2>
              <p class="text-xs text-slate-500 mt-1">Official section seating, subject timetable, and student portal credentials.</p>
            </div>

            <div class="flex items-center space-x-2">
              <button @click="printSlip" class="px-5 py-2.5 rounded-xl font-bold bg-slate-900 hover:bg-slate-800 text-white text-xs shadow-md transition flex items-center space-x-2 cursor-pointer">
                <Printer class="w-4 h-4" />
                <span>Print Official COR Form</span>
              </button>
            </div>
          </div>

          <!-- IF NOT YET PAID: PROMPT TO PAY IN STEP 4 -->
          <div v-if="application?.status !== 'Enrolled'" class="p-8 rounded-3xl bg-amber-50 border-2 border-amber-200 text-amber-950 text-center space-y-4 my-4">
            <div class="w-16 h-16 rounded-full bg-amber-100 border border-amber-300 text-amber-700 flex items-center justify-center mx-auto shadow-sm">
              <Clock class="w-8 h-8 animate-pulse" />
            </div>
            <div class="max-w-md mx-auto">
              <h3 class="text-lg font-black text-amber-950">Payment Required to Finalize Official Registration</h3>
              <p class="text-xs text-amber-900 mt-1 leading-relaxed">
                Your admission application is approved and section seating reserved. Please complete your tuition downpayment in <strong>Step 4</strong> via Walk-in Cashier or Online PayMongo to receive your permanent Student ID.
              </p>
            </div>
            <button @click="activeStep = 4" class="px-6 py-3 rounded-xl font-extrabold bg-emerald-600 hover:bg-emerald-500 text-white text-xs shadow-lg shadow-emerald-600/30 transition inline-flex items-center space-x-2 cursor-pointer">
              <span>Go to Step 4 (Assessment & Payment) →</span>
            </button>
          </div>

          <!-- IF OFFICIALLY ENROLLED: DISPLAY FULL OFFICIAL COR -->
          <div v-else class="border-2 border-slate-900 p-6 sm:p-8 rounded-2xl bg-white text-slate-900 font-sans print:p-0 print:border-none">
            <!-- Institutional Header -->
            <div class="text-center border-b-2 border-slate-900 pb-4 mb-4">
              <div class="text-[10px] font-bold uppercase tracking-widest text-slate-600">Department of Education • Region VIII (Eastern Visayas)</div>
              <h2 class="text-xl font-black uppercase text-slate-950">BIRINGAN SCIENCE & LEADERSHIP ACADEMY</h2>
              <p class="text-xs text-slate-600 font-medium">"Innovating for the Nation" • Junior & Senior High School (DepEd ID: 405621)</p>
              <div class="inline-block mt-2.5 px-4 py-1 rounded-full bg-slate-900 text-white text-xs font-black font-mono uppercase tracking-widest">
                OFFICIAL CERTIFICATE OF REGISTRATION (COR)
              </div>
            </div>

            <!-- Student Profile Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 p-4 rounded-xl border border-slate-300 text-xs font-mono mb-4">
              <div>
                <span class="text-slate-500 block text-[9.5px] uppercase font-bold">Permanent Student ID:</span>
                <strong class="text-emerald-800 text-sm font-black">{{ application?.student_no || application?.assessment_info?.student_number }}</strong>
              </div>
              <div>
                <span class="text-slate-500 block text-[9.5px] uppercase font-bold">Student Full Name:</span>
                <strong class="text-slate-900">{{ application?.last_name }}, {{ application?.first_name }} {{ application?.middle_name || '' }}</strong>
              </div>
              <div>
                <span class="text-slate-500 block text-[9.5px] uppercase font-bold">Assigned Class Section:</span>
                <strong class="text-blue-800">{{ application?.queue_info?.section_name || 'Grade 11 - STEM A' }}</strong>
              </div>
              <div>
                <span class="text-slate-500 block text-[9.5px] uppercase font-bold">Classroom / Wing:</span>
                <strong class="text-slate-900">{{ application?.queue_info?.room || 'Science Wing - Room 501' }}</strong>
              </div>
            </div>

            <!-- Subject Timetable -->
            <div class="mb-4">
              <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 mb-2">Curriculum Learning Areas & Class Timetable</h4>
              <table class="w-full text-xs text-left border-collapse border border-slate-300">
                <thead>
                  <tr class="bg-slate-100 text-slate-800 font-bold border-b border-slate-300">
                    <th class="p-2 border-r border-slate-300 font-mono">Code</th>
                    <th class="p-2 border-r border-slate-300">Descriptive Learning Area</th>
                    <th class="p-2 border-r border-slate-300 text-center">Units</th>
                    <th class="p-2 border-r border-slate-300">Schedule Day & Time</th>
                    <th class="p-2">Assigned Room</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                  <tr v-for="sub in sampleSubjects" :key="sub.code">
                    <td class="p-2 font-mono font-bold border-r border-slate-200 text-emerald-800">{{ sub.code }}</td>
                    <td class="p-2 font-semibold border-r border-slate-200">{{ sub.title }}</td>
                    <td class="p-2 text-center font-bold border-r border-slate-200">{{ sub.units }}</td>
                    <td class="p-2 border-r border-slate-200 font-mono text-[11px] text-slate-600">Mon & Thu 08:00 AM - 09:30 AM</td>
                    <td class="p-2 font-mono text-[11px]">{{ application?.queue_info?.room || 'Science Wing 501' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Official Seal & Signatures -->
            <div class="grid grid-cols-1 sm:grid-cols-3 print:grid-cols-3 gap-6 text-center text-xs pt-6 mt-6 border-t-2 border-slate-300">
              <div>
                <div class="border-b border-slate-900 pb-1 font-bold text-slate-900">{{ application?.first_name }} {{ application?.last_name }}</div>
                <span class="text-[9px] text-slate-500 uppercase">Student Enrollee</span>
              </div>
              <div>
                <div class="border-b border-slate-900 pb-1 font-bold text-slate-900">Office of the Registrar</div>
                <span class="text-[9px] text-slate-500 uppercase">Evaluated & Registered</span>
              </div>
              <div>
                <div class="border-b border-slate-900 pb-1 font-bold text-slate-900">Finance & Treasury</div>
                <span class="text-[9px] text-slate-500 uppercase">Official Receipt Settle</span>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW B: DEDICATED FULL-PAGE DOCUMENT REQUIREMENTS GUIDE (tab=checklist)   -->
    <!-- ========================================================================= -->
    <div v-else-if="currentApplicantTab === 'checklist'" class="space-y-8 animate-in fade-in duration-200">
      
      <!-- Top Guide Header -->
      <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-2xs flex items-start space-x-4">
        <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-900 border border-blue-200 flex items-center justify-center font-bold shrink-0 shadow-sm">
          <FileCheck class="w-7 h-7" />
        </div>
        <div>
          <div class="inline-flex items-center space-x-1.5 px-3 py-0.5 rounded-full bg-blue-50 border border-blue-200 text-blue-950 text-[10px] font-bold uppercase tracking-wider mb-1.5">
            <span>Registrar Compliance Catalog</span>
          </div>
          <h2 class="text-2xl font-black text-[#0c2340] tracking-tight font-serif">DepEd Admission Requirements & Credentials Guide</h2>
          <p class="text-xs text-slate-600 mt-1 max-w-2xl">
            Official catalog of required academic records, PSA documents, and certificates required by the Office of the Registrar for S.Y. 2026-2027 admission.
          </p>
        </div>
      </div>

      <!-- Quick Upload Standards Alert -->
      <div class="p-5 rounded-3xl bg-blue-50/80 border border-blue-200 text-slate-900 text-xs grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="flex items-start space-x-3">
          <div class="w-8 h-8 rounded-xl bg-blue-900 text-white flex items-center justify-center shrink-0 mt-0.5">
            <Check class="w-4 h-4" />
          </div>
          <div>
            <div class="font-bold text-slate-900 text-xs">Accepted File Formats</div>
            <p class="text-[11px] text-slate-600 mt-0.5">PDF documents, Word (.docx, .doc), high-res JPG, PNG, or WEBP scans up to 10MB per file.</p>
          </div>
        </div>
        <div class="flex items-start space-x-3">
          <div class="w-8 h-8 rounded-xl bg-blue-900 text-white flex items-center justify-center shrink-0 mt-0.5">
            <Eye class="w-4 h-4" />
          </div>
          <div>
            <div class="font-bold text-slate-900 text-xs">Clear & Readable Scans</div>
            <p class="text-[11px] text-slate-600 mt-0.5">Ensure all student details, LRN digits, grades, and school seals are sharp and legible.</p>
          </div>
        </div>
        <div class="flex items-start space-x-3">
          <div class="w-8 h-8 rounded-xl bg-blue-900 text-white flex items-center justify-center shrink-0 mt-0.5">
            <ShieldCheck class="w-4 h-4" />
          </div>
          <div>
            <div class="font-bold text-slate-900 text-xs">Authenticity Check</div>
            <p class="text-[11px] text-slate-600 mt-0.5">All credentials are cross-referenced with DepEd Learner Information System (LIS).</p>
          </div>
        </div>
      </div>

      <!-- Detailed Credential Breakdown Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- PSA Birth Certificate -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
          <div class="flex items-start justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 rounded-2xl bg-slate-100 text-blue-900 flex items-center justify-center font-bold shrink-0">
                1
              </div>
              <div>
                <h4 class="font-bold text-slate-900 text-sm">PSA Birth Certificate</h4>
                <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-900">Mandatory for All</span>
              </div>
            </div>
          </div>
          <p class="text-xs text-slate-600 leading-relaxed">
            Original copy issued by the Philippine Statistics Authority (PSA) with official barcode and dry seal clearly visible.
          </p>
          <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 text-[11px] space-y-1.5">
            <div class="font-bold text-slate-800">Why it is required:</div>
            <div class="text-slate-600">• Used to legally verify applicant's full name, exact date of birth, and biological parentage in DepEd LIS.</div>
            <div class="text-rose-700 font-medium pt-1">• <strong>Avoid Rejection:</strong> Do not upload cropped photos where the PSA Registry Number or Barcode is cut off.</div>
          </div>
        </div>

        <!-- Form 138 / SF9 -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
          <div class="flex items-start justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 rounded-2xl bg-slate-100 text-blue-900 flex items-center justify-center font-bold shrink-0">
                2
              </div>
              <div>
                <h4 class="font-bold text-slate-900 text-sm">SF9 / Form 138 (Report Card)</h4>
                <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-900">Mandatory for All</span>
              </div>
            </div>
          </div>
          <p class="text-xs text-slate-600 leading-relaxed">
            Official Grade Report Card from the previous school year signed by the Class Adviser and Principal/School Head.
          </p>
          <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 text-[11px] space-y-1.5">
            <div class="font-bold text-slate-800">Why it is required:</div>
            <div class="text-slate-600">• Evaluates academic eligibility, general weighted average (GWA), and promotes the student to the enrolled grade level.</div>
            <div class="text-rose-700 font-medium pt-1">• <strong>Avoid Rejection:</strong> Must show the final rating and promotion status signed by the school head.</div>
          </div>
        </div>

        <!-- Good Moral Certificate -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
          <div class="flex items-start justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 rounded-2xl bg-slate-100 text-blue-900 flex items-center justify-center font-bold shrink-0">
                3
              </div>
              <div>
                <h4 class="font-bold text-slate-900 text-sm">Certificate of Good Moral Character</h4>
                <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-900">Mandatory for All</span>
              </div>
            </div>
          </div>
          <p class="text-xs text-slate-600 leading-relaxed">
            Official certificate issued by the Guidance Office or Principal of the previous school certifying conduct.
          </p>
          <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 text-[11px] space-y-1.5">
            <div class="font-bold text-slate-800">Why it is required:</div>
            <div class="text-slate-600">• Required institutional character clearance for enrollment in BSLA junior and senior high programs.</div>
            <div class="text-rose-700 font-medium pt-1">• <strong>Avoid Rejection:</strong> Must be issued within the current academic year and bear the school dry seal.</div>
          </div>
        </div>

        <!-- 2x2 ID Photo -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
          <div class="flex items-start justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 rounded-2xl bg-slate-100 text-blue-900 flex items-center justify-center font-bold shrink-0">
                4
              </div>
              <div>
                <h4 class="font-bold text-slate-900 text-sm">2x2 ID Picture with White Background</h4>
                <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-900">Mandatory for All</span>
              </div>
            </div>
          </div>
          <p class="text-xs text-slate-600 leading-relaxed">
            Recent colored photograph in formal school or collared attire with white background and name tag.
          </p>
          <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 text-[11px] space-y-1.5">
            <div class="font-bold text-slate-800">Why it is required:</div>
            <div class="text-slate-600">• Used for printing the official BSLA Student ID Card and Permanent School Form 10 (SF10).</div>
            <div class="text-rose-700 font-medium pt-1">• <strong>Avoid Rejection:</strong> No selfies, side angles, filters, or dark background photos.</div>
          </div>
        </div>

        <!-- JHS Completion Diploma (For SHS) -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
          <div class="flex items-start justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 rounded-2xl bg-slate-100 text-blue-900 flex items-center justify-center font-bold shrink-0">
                5
              </div>
              <div>
                <h4 class="font-bold text-slate-900 text-sm">Certificate of JHS Completion</h4>
                <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold bg-blue-100 text-blue-900">SHS Enrollees (Grades 11–12)</span>
              </div>
            </div>
          </div>
          <p class="text-xs text-slate-600 leading-relaxed">
            Official Junior High School Completion Diploma / Certificate awarded upon completing Grade 10.
          </p>
          <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 text-[11px] space-y-1.5">
            <div class="font-bold text-slate-800">Why it is required:</div>
            <div class="text-slate-600">• Proves complete secondary basic education graduation prerequisite for Senior High tracks.</div>
          </div>
        </div>

        <!-- ESC / SHS Voucher Certificate -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
          <div class="flex items-start justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 rounded-2xl bg-slate-100 text-blue-900 flex items-center justify-center font-bold shrink-0">
                6
              </div>
              <div>
                <h4 class="font-bold text-slate-900 text-sm">DepEd ESC / SHS Voucher Certificate</h4>
                <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-900">Voucher Applicants</span>
              </div>
            </div>
          </div>
          <p class="text-xs text-slate-600 leading-relaxed">
            DepEd PEAC ESC Certificate or Qualified Voucher Applicant (QVR) certificate downloaded from the PEAC OVAP portal.
          </p>
          <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 text-[11px] space-y-1.5">
            <div class="font-bold text-slate-800">Why it is required:</div>
            <div class="text-slate-600">• Validates government tuition subsidy (100% for Public JHS completers, 80% for Private ESC grantees).</div>
          </div>
        </div>

      </div>

    </div>

    <!-- ========================================================================= -->
    <!-- VIEW C: DEDICATED FULL-PAGE ENROLLMENT ROADMAP & HELPDESK (tab=process)   -->
    <!-- ========================================================================= -->
    <div v-else-if="currentApplicantTab === 'process'" class="space-y-8 animate-in fade-in duration-200">
      
      <!-- Top Guide Header -->
      <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-2xs flex items-start space-x-4">
        <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-900 border border-blue-200 flex items-center justify-center font-bold shrink-0 shadow-sm">
          <Compass class="w-7 h-7" />
        </div>
        <div>
          <div class="inline-flex items-center space-x-1.5 px-3 py-0.5 rounded-full bg-blue-50 border border-blue-200 text-blue-950 text-[10px] font-bold uppercase tracking-wider mb-1.5">
            <span>Institutional Admissions Lifecycle</span>
          </div>
          <h2 class="text-2xl font-black text-[#0c2340] tracking-tight font-serif">Comprehensive Enrollment Process & Roadmap</h2>
          <p class="text-xs text-slate-600 mt-1 max-w-2xl">
            Step-by-step guide explaining how your application moves from initial registration to Registrar authentication, Treasury cashier assessment, and official Certificate of Registration issuance.
          </p>
        </div>
      </div>

      <!-- 5-STAGE INSTITUTIONAL ENROLLMENT LIFECYCLE ROADMAP -->
      <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
        <div class="border-b border-slate-100 pb-4">
          <h3 class="text-base font-black text-slate-900 font-serif">The 5 Stages of BSLA Admission & Enrollment</h3>
          <p class="text-xs text-slate-500 mt-0.5">Understanding the behind-the-scenes evaluation before official student onboarding.</p>
        </div>

        <div class="space-y-6">
          
          <!-- Stage 1 -->
          <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col md:flex-row items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-900 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-md">
              01
            </div>
            <div class="space-y-1.5 flex-1">
              <div class="flex items-center space-x-2">
                <h4 class="font-bold text-slate-900 text-sm">Stage 1: Student Demographics Encoding & 12-Digit LRN Verification</h4>
                <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-blue-100 text-blue-950">Applicant Action</span>
              </div>
              <p class="text-xs text-slate-600 leading-relaxed">
                The student fills out their full legal name, PSA birth details, address, and 12-digit Learner Reference Number (LRN). The system validates LRN format and creates an active applicant record (e.g. <code class="font-mono font-bold text-blue-900">ADM-2026-0009</code>).
              </p>
            </div>
          </div>

          <!-- Stage 2 -->
          <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col md:flex-row items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-900 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-md">
              02
            </div>
            <div class="space-y-1.5 flex-1">
              <div class="flex items-center space-x-2">
                <h4 class="font-bold text-slate-900 text-sm">Stage 2: Academic Track / Strand & DepEd Voucher Subsidy Selection</h4>
                <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-blue-100 text-blue-950">Applicant Action</span>
              </div>
              <p class="text-xs text-slate-600 leading-relaxed">
                The applicant chooses their desired Grade Level (Junior High School Grades 7–10 or Senior High School Grades 11–12 STEM, ABM, HUMSS, GAS, TVL-ICT, TVL-HE) and specifies their DepEd ESC/Voucher category to apply tuition discounts.
              </p>
            </div>
          </div>

          <!-- Stage 3 -->
          <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col md:flex-row items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-900 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-md">
              03
            </div>
            <div class="space-y-1.5 flex-1">
              <div class="flex items-center space-x-2">
                <h4 class="font-bold text-slate-900 text-sm">Stage 3: Requirement Submission & Office of the Registrar Authentication</h4>
                <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-900">Registrar Review</span>
              </div>
              <p class="text-xs text-slate-600 leading-relaxed">
                The applicant uploads clear scans of their PSA Birth Certificate, SF9 Form 138, Good Moral Certificate, and 2x2 Photo. Authorized Registrar Evaluators inspect the documents, authenticate school seals, and change status to <strong class="text-emerald-700">Approved for Enrollment</strong>. If a file is blurry, it is flagged as <strong class="text-rose-700">Deficient</strong> with feedback.
              </p>
            </div>
          </div>

          <!-- Stage 4 -->
          <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col md:flex-row items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-900 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-md">
              04
            </div>
            <div class="space-y-1.5 flex-1">
              <div class="flex items-center space-x-2">
                <h4 class="font-bold text-slate-900 text-sm">Stage 4: Tuition Fee Assessment & Downpayment Settlement</h4>
                <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-900">Treasury & Cashier</span>
              </div>
              <p class="text-xs text-slate-600 leading-relaxed">
                Once approved by the Registrar, the Treasury generates the official Assessment Slip showing net payable fees after voucher subsidies. The applicant can choose between:
              </p>
              <ul class="text-xs text-slate-600 list-disc pl-5 space-y-1 pt-1">
                <li><strong>Walk-in Cashier Ticket:</strong> Schedule a morning or afternoon appointment to pay at the school cashiers.</li>
                <li><strong>Online PayMongo:</strong> Submit real-time GCash, Maya, or Card reference numbers for immediate queue verification.</li>
              </ul>
            </div>
          </div>

          <!-- Stage 5 -->
          <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col md:flex-row items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-md">
              05
            </div>
            <div class="space-y-1.5 flex-1">
              <div class="flex items-center space-x-2">
                <h4 class="font-bold text-slate-900 text-sm">Stage 5: Official Enrollment Confirmation & Certificate of Registration (COR)</h4>
                <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-900">Final System Handover</span>
              </div>
              <p class="text-xs text-slate-600 leading-relaxed">
                Upon Treasury verification, the system automatically assigns the student's permanent institutional ID (e.g. <code class="font-mono font-bold text-emerald-700">2026-SHS-0005</code>), assigns class sectioning & classroom timetable, and generates the printable <strong>Official Certificate of Registration (COR)</strong>.
              </p>
            </div>
          </div>

        </div>
      </div>

      <!-- APPLICANT FREQUENTLY ASKED QUESTIONS (FAQS) -->
      <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-4">
        <div class="border-b border-slate-100 pb-3.5 flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold text-slate-900">Frequently Asked Questions (FAQs)</h3>
            <p class="text-xs text-slate-500">Quick answers to common admission and enrollment queries.</p>
          </div>
          <HelpCircle class="w-5 h-5 text-blue-900" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
          <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
            <h5 class="font-bold text-slate-900">Can I change my strand after applying?</h5>
            <p class="text-slate-600 leading-relaxed">
              Yes, before your application is finalized by the Registrar, you can return to Step 2 anytime to modify your track or strand.
            </p>
          </div>

          <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
            <h5 class="font-bold text-slate-900">What happens if a document is marked "Deficient"?</h5>
            <p class="text-slate-600 leading-relaxed">
              The system will notify you on your dashboard. Simply click "Fix Deficient Files" in Step 3 and upload a clearer scan.
            </p>
          </div>

          <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
            <h5 class="font-bold text-slate-900">How do I qualify for the 100% DepEd Voucher?</h5>
            <p class="text-slate-600 leading-relaxed">
              All Grade 10 completers from Public Junior High Schools automatically receive the 100% full voucher subsidy for Senior High School.
            </p>
          </div>

          <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
            <h5 class="font-bold text-slate-900">How do I access my official schedule after enrolling?</h5>
            <p class="text-slate-600 leading-relaxed">
              Once officially enrolled in Step 5, you can log in to the <strong>Student Portal</strong> using your permanent Student ID.
            </p>
          </div>
        </div>
      </div>

      <!-- ADMISSIONS HELPDESK & CONTACT CARDS -->
      <div class="bg-[#08182b] text-white rounded-3xl p-6 sm:p-8 border border-slate-800 shadow-xl space-y-6">
        <div class="flex items-center space-x-3 border-b border-slate-800 pb-4">
          <div class="w-10 h-10 rounded-2xl bg-blue-950 text-blue-300 border border-blue-500/30 flex items-center justify-center font-bold">
            <Phone class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-white">Admissions Helpdesk & Contact Center</h3>
            <p class="text-xs text-slate-400">Direct communication lines for admission assistance and inquiries.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
          <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1">
            <div class="font-bold text-blue-300 text-xs uppercase font-mono">Admissions Office</div>
            <div class="text-slate-200 font-bold text-sm">(055) 888-7766</div>
            <div class="text-slate-400 text-[11px]">Mon–Fri, 8:00 AM – 5:00 PM</div>
          </div>
          <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1">
            <div class="font-bold text-blue-300 text-xs uppercase font-mono">Mobile / SMS Support</div>
            <div class="text-slate-200 font-bold text-sm">0917-111-0001</div>
            <div class="text-slate-400 text-[11px]">Globe / TM SMS Inquiry Line</div>
          </div>
          <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1">
            <div class="font-bold text-blue-300 text-xs uppercase font-mono">Admissions Email</div>
            <div class="text-slate-200 font-bold text-sm">admissions@student.bsla.edu.ph</div>
            <div class="text-slate-400 text-[11px]">Online document validation support</div>
          </div>
        </div>
      </div>

    </div>

    <!-- SUBMIT FOR REVIEW CONFIRMATION MODAL -->
    <div v-if="showSubmitReviewConfirm" class="no-print fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150 text-slate-900">
        <div class="w-12 h-12 rounded-2xl bg-emerald-100 border border-emerald-200 text-emerald-600 flex items-center justify-center mx-auto mb-4 shadow-sm">
          <UploadCloud class="w-6 h-6" />
        </div>
        <div class="text-center">
          <h3 class="text-base font-extrabold text-slate-900">Submit Requirements for Review?</h3>
          <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
            Your admission credentials will be submitted to the Office of the Registrar for official evaluation and section assignment.
          </p>
        </div>

        <!-- Summary Badges -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-4 text-center">
          <div class="p-2 rounded-xl bg-emerald-50 border border-emerald-200">
            <div class="text-xs font-bold text-emerald-800">{{ digitalUploadedCount }}</div>
            <div class="text-[10px] text-emerald-600 font-medium">Digital Upload</div>
          </div>
          <div class="p-2 rounded-xl bg-indigo-50 border border-indigo-200">
            <div class="text-xs font-bold text-indigo-800">{{ physicalCount }}</div>
            <div class="text-[10px] text-indigo-600 font-medium">Physical Copy</div>
          </div>
          <div class="p-2 rounded-xl bg-amber-50 border border-amber-200">
            <div class="text-xs font-bold text-amber-900">{{ promissoryCount }}</div>
            <div class="text-[10px] text-amber-700 font-medium">To Follow Up</div>
          </div>
        </div>

        <!-- Checklist of Uploaded Docs -->
        <div class="my-4 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-2.5 max-h-56 overflow-y-auto">
          <div class="font-bold text-slate-700 uppercase tracking-wider text-[10px]">DepEd Requirements Breakdown:</div>
          <div v-for="req in requiredDocsList" :key="req.type" class="flex items-center justify-between text-[11px] py-1 border-b border-slate-100 last:border-b-0">
            <span class="text-slate-700 font-medium truncate max-w-[180px]" :title="req.type">{{ req.type }}</span>
            <template v-if="getDocUploaded(req.type)">
              <span v-if="getDocUploaded(req.type).submission_mode === 'Physical Submission'" class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200 flex items-center space-x-1 shrink-0">
                <Building class="w-3 h-3 text-indigo-600" />
                <span>Physical</span>
              </span>
              <span v-else-if="getDocUploaded(req.type).submission_mode === 'To Follow Up'" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-200 flex items-center space-x-1 shrink-0" :title="'Target: ' + formatDate(getDocUploaded(req.type).target_date)">
                <Clock class="w-3 h-3 text-amber-700" />
                <span>Follow Up</span>
              </span>
              <span v-else class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center space-x-1 shrink-0">
                <Check class="w-3 h-3 text-emerald-600" />
                <span>Digital</span>
              </span>
            </template>
            <span v-else class="text-slate-400 italic text-[10px]">Unsubmitted</span>
          </div>
        </div>

        <div class="flex items-center space-x-2.5 mt-6">
          <button 
            type="button" 
            @click="showSubmitReviewConfirm = false" 
            class="w-1/2 py-2.5 px-4 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer"
          >
            Review Files Again
          </button>
          <button 
            type="button" 
            @click="confirmSubmitForEvaluation" 
            :disabled="isSubmitting"
            class="w-1/2 py-2.5 px-4 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white shadow-md transition flex items-center justify-center space-x-1.5 cursor-pointer"
          >
            <span v-if="isSubmitting" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span v-else>Confirm & Submit</span>
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: DELETE DOCUMENT CONFIRMATION -->
    <div v-if="showDeleteDocModal" class="no-print fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150 text-slate-900">
        <div class="w-12 h-12 rounded-2xl bg-rose-100 border border-rose-200 text-rose-600 flex items-center justify-center mx-auto mb-4 shadow-sm">
          <Trash2 class="w-6 h-6" />
        </div>
        <div class="text-center">
          <h3 class="text-base font-extrabold text-slate-900">Remove Document?</h3>
          <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
            Are you sure you want to remove <strong class="text-slate-800">"{{ deleteDocTarget?.original_filename || deleteDocTarget?.document_type }}"</strong>?
          </p>
          <p class="text-[11px] text-slate-400 mt-1">You can upload or select another submission option afterward.</p>
        </div>

        <div class="flex items-center space-x-2.5 mt-6">
          <button 
            type="button" 
            @click="showDeleteDocModal = false" 
            class="w-1/2 py-2.5 px-4 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer"
          >
            Cancel
          </button>
          <button 
            type="button" 
            @click="confirmDeleteDocument" 
            :disabled="isDeletingDoc"
            class="w-1/2 py-2.5 px-4 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white shadow-md transition flex items-center justify-center space-x-1.5 cursor-pointer"
          >
            <span v-if="isDeletingDoc" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span v-else>Yes, Remove</span>
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: PHYSICAL SUBMISSION COMMITMENT -->
    <div v-if="showPhysicalModal" class="no-print fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150 text-slate-900">
        <div class="w-12 h-12 rounded-2xl bg-indigo-100 border border-indigo-200 text-indigo-700 flex items-center justify-center mx-auto mb-4 shadow-sm">
          <Building class="w-6 h-6" />
        </div>
        <div class="text-center">
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-100 text-indigo-800 border border-indigo-200 inline-block mb-1.5">
            On-Campus Document Submission
          </span>
          <h3 class="text-base font-extrabold text-slate-900">Submit Physical Hard Copy</h3>
          <p class="text-xs text-slate-500 mt-1 leading-relaxed">
            You are committing to submit original or certified hard copies of this credential directly to the school campus.
          </p>
        </div>

        <div class="my-4 p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
          <div class="flex items-center space-x-2">
            <span class="text-xs text-slate-500">Document:</span>
            <strong class="text-xs text-slate-800 font-bold">{{ selectedDocTypeForPhysical }}</strong>
          </div>

          <div class="p-3 rounded-xl bg-indigo-50/70 border border-indigo-200 text-xs text-indigo-950 space-y-1.5">
            <div class="font-bold flex items-center space-x-1.5">
              <MapPin class="w-3.5 h-3.5 text-indigo-700" />
              <span>Drop-off Location & Schedule:</span>
            </div>
            <p class="text-[11px] text-indigo-900/80 leading-relaxed">
              Office of the Registrar (Window 3 - Admissions Counter), Administration Hall. Monday to Friday, 8:00 AM – 4:30 PM.
            </p>
          </div>

          <label class="flex items-start space-x-2.5 pt-1 cursor-pointer select-none">
            <input 
              type="checkbox" 
              v-model="physicalConsent" 
              class="mt-0.5 w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300"
            />
            <span class="text-[11px] text-slate-600 leading-snug">
              I understand that official enrollment and enrollment credentials remain subject to verification of this hard copy upon physical submission.
            </span>
          </label>
        </div>

        <div class="flex items-center space-x-2.5 mt-5">
          <button 
            type="button" 
            @click="showPhysicalModal = false" 
            class="w-1/2 py-2.5 px-4 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer"
          >
            Cancel
          </button>
          <button 
            type="button" 
            @click="confirmPhysicalSubmission" 
            :disabled="!physicalConsent || isSavingDocMode"
            :class="[
              physicalConsent && !isSavingDocMode
                ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-md cursor-pointer'
                : 'bg-slate-200 text-slate-400 cursor-not-allowed'
            ]"
            class="w-1/2 py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center space-x-1.5"
          >
            <span v-if="isSavingDocMode" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span v-else>Confirm Physical</span>
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: BULK PHYSICAL SUBMISSION CONFIRMATION -->
    <div v-if="showBulkPhysicalModal" class="no-print fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150 text-slate-900">
        <div class="w-12 h-12 rounded-2xl bg-indigo-100 border border-indigo-200 text-indigo-700 flex items-center justify-center mx-auto mb-4 shadow-sm">
          <Building class="w-6 h-6" />
        </div>
        <div class="text-center">
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-100 text-indigo-800 border border-indigo-200 inline-block mb-1.5">
            Bulk Physical Submission
          </span>
          <h3 class="text-base font-extrabold text-slate-900">Submit All Remaining Physically?</h3>
          <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
            You are pledging to personally bring <strong class="text-indigo-800">{{ missingMandatoryDocs.length }}</strong> remaining required document(s) to the Registrar's Office on campus.
          </p>
        </div>

        <div class="my-4 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-2 max-h-48 overflow-y-auto">
          <div class="font-bold text-slate-700 uppercase tracking-wider text-[10px]">Unfulfilled documents to be pledged:</div>
          <div v-for="docName in missingMandatoryDocs" :key="docName" class="flex items-center space-x-2 text-[11px] py-1 border-b border-slate-100 last:border-b-0">
            <Building class="w-3.5 h-3.5 text-indigo-600 shrink-0" />
            <span class="text-slate-700 font-medium">{{ docName }}</span>
          </div>
          <p class="text-[10px] text-slate-400 italic pt-1">
            Note: Requirements already uploaded or filed as "To Follow Up" promissory notes will not be affected.
          </p>
        </div>

        <div class="p-3 rounded-xl bg-indigo-50/70 border border-indigo-200 text-[11px] text-indigo-950 flex items-start space-x-2 mb-4">
          <MapPin class="w-4 h-4 text-indigo-700 shrink-0 mt-0.5" />
          <p class="leading-relaxed">
            Submit at: <strong>Office of the Registrar (Window 3 — Admissions Counter)</strong>, Administration Hall. Monday to Friday, 8:00 AM – 4:30 PM.
          </p>
        </div>

        <div class="flex items-center space-x-2.5">
          <button 
            type="button" 
            @click="showBulkPhysicalModal = false" 
            class="w-1/2 py-2.5 px-4 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer"
          >
            Cancel
          </button>
          <button 
            type="button" 
            @click="confirmBulkPhysical" 
            :disabled="isSavingDocMode"
            class="w-1/2 py-2.5 px-4 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white shadow-md transition flex items-center justify-center space-x-1.5 cursor-pointer"
          >
            <span v-if="isSavingDocMode" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span v-else>Confirm All Physical</span>
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: TO FOLLOW UP (PROMISSORY NOTE) -->
    <div v-if="showPromissoryModal" class="no-print fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150 text-slate-900">
        <div class="w-12 h-12 rounded-2xl bg-amber-100 border border-amber-200 text-amber-700 flex items-center justify-center mx-auto mb-3.5 shadow-sm">
          <Clock class="w-6 h-6" />
        </div>
        <div class="text-center">
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-900 border border-amber-200 inline-block mb-1.5">
            DepEd Promissory Commitment
          </span>
          <h3 class="text-base font-extrabold text-slate-900">To Follow Up Document</h3>
          <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
            Submit a formal promissory note with a promised target submission date so your admission evaluation can proceed.
          </p>
        </div>

        <form @submit.prevent="confirmPromissorySubmission" class="mt-4 space-y-4 text-xs">
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
            <span class="text-slate-500">Requirement:</span>
            <strong class="text-slate-800 font-bold">{{ selectedDocTypeForPromissory }}</strong>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">
              Promised Submission Target Date <span class="text-rose-500">*</span>
            </label>
            <input 
              type="date" 
              v-model="promissoryForm.target_date" 
              :min="minPromissoryDate"
              required
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-xs font-semibold bg-white"
            />
            <p class="text-[10px] text-slate-400 mt-1">Select the realistic date by which you will submit this credential.</p>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1.5">
              Reason / Circumstance (Choose preset or type below)
            </label>
            <div class="flex flex-wrap gap-1.5 mb-2">
              <button 
                type="button" 
                v-for="preset in promissoryPresets" 
                :key="preset"
                @click="setPromissoryPreset(preset)"
                class="px-2.5 py-1 rounded-lg text-[10px] font-semibold border transition text-left cursor-pointer"
                :class="promissoryForm.promissory_note === preset ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
              >
                {{ preset }}
              </button>
            </div>
            <textarea 
              v-model="promissoryForm.promissory_note" 
              rows="3" 
              placeholder="e.g. Currently awaiting official release from previous school's registrar, expected within 2 weeks."
              class="w-full px-3.5 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-xs bg-white resize-none"
            ></textarea>
          </div>

          <div class="p-3 rounded-xl bg-amber-50/60 border border-amber-200 text-[11px] text-amber-900 flex items-start space-x-2">
            <AlertCircle class="w-4 h-4 text-amber-700 shrink-0 mt-0.5" />
            <p class="leading-relaxed">
              Filing this promissory commitment fulfills the Step 3 requirement and allows the Registrar to review your application and queue you for enrollment.
            </p>
          </div>

          <div class="flex items-center space-x-2.5 pt-2">
            <button 
              type="button" 
              @click="showPromissoryModal = false" 
              class="w-1/2 py-2.5 px-4 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="!promissoryForm.target_date || isSavingDocMode"
              :class="[
                promissoryForm.target_date && !isSavingDocMode
                  ? 'bg-amber-600 hover:bg-amber-500 text-white shadow-md cursor-pointer'
                  : 'bg-slate-200 text-slate-400 cursor-not-allowed'
              ]"
              class="w-1/2 py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center space-x-1.5"
            >
              <span v-if="isSavingDocMode" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span v-else>Save Promissory Note</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- PAYMONGO VERIFYING OVERLAY -->
    <div v-if="isVerifyingPaymongo" class="no-print fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-sm w-full p-8 shadow-2xl border border-slate-200 text-center space-y-4 animate-in fade-in zoom-in-95 text-slate-900">
        <div class="w-16 h-16 rounded-3xl bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto shadow-inner">
          <Loader2 class="w-8 h-8 animate-spin text-emerald-600" />
        </div>
        <div>
          <h3 class="text-lg font-black text-slate-900">Verifying PayMongo Payment...</h3>
          <p class="text-xs text-slate-500 mt-1 leading-relaxed">
            Please wait while we confirm your transaction with the gateway and finalize your official enrollment.
          </p>
        </div>
      </div>
    </div>

    <!-- PAYMONGO VERIFIED CELEBRATION MODAL -->
    <div v-if="paymongoVerifyResult" class="no-print fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-emerald-200 text-center space-y-5 animate-in fade-in zoom-in-95 text-slate-900">
        <div class="w-16 h-16 rounded-3xl bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto shadow-sm">
          <CheckCircle class="w-9 h-9 text-emerald-600" />
        </div>
        <div>
          <span class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 mb-2">
            Officially Enrolled
          </span>
          <h3 class="text-xl font-black text-slate-900">Payment Successfully Verified!</h3>
          <p class="text-xs text-slate-500 mt-1">Your enrollment downpayment via PayMongo has been settled and credited.</p>
        </div>
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-mono space-y-2 text-left">
          <div class="flex justify-between"><span class="text-slate-500 font-sans">Official Receipt:</span><strong class="text-slate-900 font-black">{{ paymongoVerifyResult.or_number }}</strong></div>
          <div class="flex justify-between"><span class="text-slate-500 font-sans">Student Number:</span><strong class="text-indigo-600 font-black">{{ paymongoVerifyResult.student_no }}</strong></div>
          <div class="flex justify-between"><span class="text-slate-500 font-sans">Amount Settled:</span><strong class="text-emerald-700 font-bold">₱{{ Number(paymongoVerifyResult.amount_paid || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</strong></div>
          <div class="flex justify-between"><span class="text-slate-500 font-sans">Method / Channel:</span><strong class="text-slate-800">{{ paymongoVerifyResult.payment_channel }}</strong></div>
        </div>
        <button @click="paymongoVerifyResult = null; selectStep(5)" class="w-full py-3.5 rounded-xl font-black bg-emerald-600 hover:bg-emerald-500 text-white text-xs shadow-lg shadow-emerald-600/30 transition cursor-pointer">
          View Certificate of Matriculation & Timetable →
        </button>
      </div>
    </div>

    <!-- PAYMONGO ONLINE PAYMENT CHECKOUT MODAL -->
    <div v-if="showPaymongoModal" class="no-print fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-200 text-slate-900 max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-black">
              PM
            </div>
            <div>
              <h3 class="font-extrabold text-base text-slate-900">PayMongo Online Payment</h3>
              <p class="text-[11px] text-slate-500">GCash • Maya • Visa / Mastercard • BPI • Billease</p>
            </div>
          </div>
          <button @click="showPaymongoModal = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center font-bold text-slate-500 transition cursor-pointer">
            ✕
          </button>
        </div>

        <!-- Mode Toggle Tabs -->
        <div class="grid grid-cols-2 gap-2 mb-4">
          <button 
            type="button" 
            @click="paymongoTab = 'checkout'"
            :class="paymongoTab === 'checkout' ? 'bg-emerald-600 text-white font-black shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold'"
            class="py-2.5 px-3 rounded-xl text-xs transition flex items-center justify-center space-x-1.5 cursor-pointer"
          >
            <span>⚡ Automated PayMongo Checkout</span>
          </button>
          <button 
            type="button" 
            @click="paymongoTab = 'manual'"
            :class="paymongoTab === 'manual' ? 'bg-emerald-600 text-white font-black shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold'"
            class="py-2.5 px-3 rounded-xl text-xs transition flex items-center justify-center space-x-1.5 cursor-pointer"
          >
            <span>Manual Receipt Upload</span>
          </button>
        </div>

        <!-- Error alert inside modal -->
        <div v-if="paymongoError" class="p-3 rounded-2xl bg-rose-50 border border-rose-300 text-rose-900 text-xs flex items-center justify-between mb-4 animate-in fade-in duration-150">
          <div class="flex items-center space-x-2">
            <AlertCircle class="w-4 h-4 text-rose-600 shrink-0" />
            <span>{{ paymongoError }}</span>
          </div>
          <button type="button" @click="paymongoError = ''" class="text-rose-600 font-bold ml-2 hover:text-rose-900">✕</button>
        </div>

        <!-- TAB A: AUTOMATED PAYMONGO HOSTED CHECKOUT -->
        <div v-if="paymongoTab === 'checkout'" class="space-y-4 text-xs">
          <div class="p-4 rounded-2xl bg-gradient-to-br from-emerald-50 to-teal-50/50 border border-emerald-200 text-slate-800 space-y-3">
            <div class="flex items-center justify-between">
              <span class="font-bold text-emerald-950 uppercase tracking-wider text-[10px]">Supported Online Gateways:</span>
              <span class="px-2 py-0.5 rounded text-[9px] font-black bg-emerald-200/80 text-emerald-900">Instant Verification</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center">
              <div class="p-2 rounded-xl bg-white border border-emerald-100 shadow-2xs">
                <span class="font-black text-blue-600 text-xs block">GCash</span>
                <span class="text-[9px] text-slate-400">E-Wallet</span>
              </div>
              <div class="p-2 rounded-xl bg-white border border-emerald-100 shadow-2xs">
                <span class="font-black text-emerald-600 text-xs block">Maya</span>
                <span class="text-[9px] text-slate-400">E-Wallet</span>
              </div>
              <div class="p-2 rounded-xl bg-white border border-emerald-100 shadow-2xs">
                <span class="font-black text-slate-900 text-xs block">Cards</span>
                <span class="text-[9px] text-slate-400">Visa / MC</span>
              </div>
              <div class="p-2 rounded-xl bg-white border border-emerald-100 shadow-2xs">
                <span class="font-black text-indigo-600 text-xs block">Online Bank</span>
                <span class="text-[9px] text-slate-400">Direct / BPI</span>
              </div>
            </div>
          </div>

          <!-- Amount to Settle -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block font-bold text-slate-700">Amount to Settle (PHP) *</label>
              <button 
                type="button" 
                @click="paymongoForm.amount = estimatedAssessment.netPayable" 
                class="text-[10px] text-emerald-700 font-bold underline cursor-pointer"
              >
                Pay Full ₱{{ estimatedAssessment.netPayable.toLocaleString('en-US', {minimumFractionDigits: 2}) }}
              </button>
            </div>
            <input 
              v-model.number="paymongoForm.amount" 
              type="number" 
              step="any" 
              :min="Math.min(estimatedAssessment.downpayment, estimatedAssessment.netPayable)"
              :max="estimatedAssessment.netPayable"
              required 
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-mono font-black text-emerald-800 text-base focus:ring-2 focus:ring-emerald-500" 
            />
            <div class="flex items-center justify-between text-[10px] mt-1">
              <span class="text-slate-500">Min Downpayment: ₱{{ Math.min(estimatedAssessment.downpayment, estimatedAssessment.netPayable).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
              <span class="text-emerald-700 font-bold">Max (Full Balance): ₱{{ estimatedAssessment.netPayable.toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
            </div>
          </div>

          <!-- Info Box -->
          <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 text-[11px] text-slate-600 space-y-1">
            <div class="font-bold text-slate-800 flex items-center space-x-1.5">
              <ShieldCheck class="w-4 h-4 text-emerald-600" />
              <span>Safe & Encrypted Checkout</span>
            </div>
            <p class="text-[10px] leading-relaxed text-slate-500">
              You will be securely redirected to PayMongo to authenticate your wallet or card. Once authorized, you will return here with an Official Receipt automatically generated.
            </p>
          </div>

          <button 
            type="button"
            @click="initiatePayMongoCheckout" 
            :disabled="isCreatingPaymongo" 
            class="w-full py-3.5 px-4 rounded-xl font-black bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-xs shadow-lg shadow-emerald-600/30 transition flex items-center justify-center space-x-2 cursor-pointer mt-3"
          >
            <span v-if="isCreatingPaymongo" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span v-else class="flex items-center space-x-1.5">
              <span>Proceed to PayMongo Checkout (₱{{ Number(paymongoForm.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}) →</span>
            </span>
          </button>
        </div>

        <!-- TAB B: MANUAL RECEIPT UPLOAD (OFFLINE / CASH-IN) -->
        <form v-else @submit.prevent="submitOnlinePayment" class="space-y-4 text-xs">
          <!-- Channel selector -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Select Payment Channel *</label>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
              <button 
                type="button" 
                @click="paymongoForm.channel = 'GCash'"
                :class="paymongoForm.channel === 'GCash' ? 'bg-blue-600 text-white font-bold border-blue-600 shadow-sm' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                class="p-2.5 rounded-xl border text-center transition cursor-pointer"
              >
                GCash
              </button>
              <button 
                type="button" 
                @click="paymongoForm.channel = 'Maya'"
                :class="paymongoForm.channel === 'Maya' ? 'bg-emerald-600 text-white font-bold border-emerald-600 shadow-sm' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                class="p-2.5 rounded-xl border text-center transition cursor-pointer"
              >
                Maya
              </button>
              <button 
                type="button" 
                @click="paymongoForm.channel = 'Card'"
                :class="paymongoForm.channel === 'Card' ? 'bg-slate-900 text-white font-bold border-slate-900 shadow-sm' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                class="p-2.5 rounded-xl border text-center transition cursor-pointer"
              >
                Visa / MC
              </button>
            </div>
          </div>

          <!-- Transaction Reference Number / ID -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">
              {{ paymongoForm.channel }} Payment Reference Number / Transaction ID *
            </label>
            <input 
              v-model="paymongoForm.reference_no" 
              type="text" 
              required 
              :placeholder="paymongoForm.channel === 'GCash' ? 'e.g. 102938475611 (10-13 digits)' : (paymongoForm.channel === 'Maya' ? 'e.g. 9823471029' : 'e.g. TXN-CARD-847294')" 
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-mono font-bold text-slate-900 focus:ring-2 focus:ring-emerald-500" 
            />
            <span class="text-[10px] text-slate-500 block mt-0.5">Found on your {{ paymongoForm.channel }} confirmation SMS or e-receipt.</span>
          </div>

          <!-- Account Name & Phone -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Sender Account Name</label>
              <input 
                v-model="paymongoForm.account_name" 
                type="text" 
                placeholder="Juan Dela Cruz"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-emerald-500" 
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Sender Mobile / Card No.</label>
              <input 
                v-model="paymongoForm.mobile" 
                type="text" 
                placeholder="0995 244 2435" 
                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-mono focus:ring-2 focus:ring-emerald-500" 
              />
            </div>
          </div>

          <!-- Amount to Settle -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block font-bold text-slate-700">Amount to Settle (PHP) *</label>
              <button 
                type="button" 
                @click="paymongoForm.amount = estimatedAssessment.netPayable" 
                class="text-[10px] text-emerald-700 font-bold underline cursor-pointer"
              >
                Pay Full ₱{{ estimatedAssessment.netPayable.toLocaleString('en-US', {minimumFractionDigits: 2}) }}
              </button>
            </div>
            <input 
              v-model.number="paymongoForm.amount" 
              type="number" 
              step="any" 
              :min="Math.min(estimatedAssessment.downpayment, estimatedAssessment.netPayable)"
              :max="estimatedAssessment.netPayable"
              required 
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-mono font-black text-emerald-800 text-sm focus:ring-2 focus:ring-emerald-500" 
            />
          </div>

          <!-- Proof of Payment / Receipt File Upload -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block font-bold text-slate-700">
                Upload Payment Receipt Proof (JPG, PNG, PDF) *
              </label>
              <span class="text-[10px] text-emerald-700 font-bold">Required for Verification</span>
            </div>
            <input 
              type="file" 
              accept=".png,.jpg,.jpeg,.pdf"
              @change="handleReceiptFileChange"
              class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-slate-50 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-500 cursor-pointer"
            />
            <div v-if="paymongoForm.receipt_file" class="text-[11px] text-emerald-700 font-bold mt-1 flex items-center space-x-1">
              <CheckCircle class="w-3.5 h-3.5" />
              <span>Attached: {{ paymongoForm.receipt_file.name }} ({{ (paymongoForm.receipt_file.size / 1024).toFixed(1) }} KB)</span>
            </div>
          </div>

          <!-- Notice Box -->
          <div class="p-3 rounded-2xl bg-amber-50 border border-amber-200 text-[11px] text-amber-900 space-y-1">
            <div class="font-bold flex items-center space-x-1 text-amber-800">
              <Clock class="w-3.5 h-3.5 shrink-0" />
              <span>Treasury Verification Notice:</span>
            </div>
            <p class="leading-relaxed text-amber-800/90 text-[10px]">
              Upon submitting manual receipt, your transaction will be placed in the Treasury Verification Queue. Finance staff will verify the reference number and amount before issuing your Official Student ID and COR.
            </p>
          </div>

          <button 
            type="submit" 
            :disabled="isPayingOnline" 
            class="w-full py-3.5 px-4 rounded-xl font-black bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-xs shadow-lg shadow-emerald-600/30 transition flex items-center justify-center space-x-2 cursor-pointer mt-2"
          >
            <span v-if="isPayingOnline" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span v-else class="flex items-center space-x-1.5">
              <span>Submit Payment for Verification (₱{{ Number(paymongoForm.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}) →</span>
            </span>
          </button>
        </form>
      </div>
    </div>


    <!-- DOCUMENT PREVIEW POP-UP MODAL (PDF & IMAGE VIEWER) -->
    <div v-if="previewDoc" class="no-print fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-4xl w-full max-h-[92vh] flex flex-col shadow-2xl overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-200">
        <!-- Modal Header -->
        <div class="p-4 sm:px-6 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
          <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-950 border border-emerald-500/30 text-emerald-400 flex items-center justify-center shrink-0">
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
              class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center font-bold transition cursor-pointer"
            >
              ✕
            </button>
          </div>
        </div>

        <!-- Modal Body -->
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { 
  CheckCircle, AlertCircle, FileText, Upload, Check, Printer, Eye, Trash2, Download,
  AlertTriangle, Clock, ArrowRight, FileCheck, GraduationCap, CreditCard,
  Lock, ChevronRight, User, BookOpen, UploadCloud, Activity, Building, Ticket,
  Compass, HelpCircle, ShieldCheck, Phone, Mail, Sparkles, MapPin, Calendar, Layers,
  RefreshCw, Loader2
} from 'lucide-vue-next';
import api, { getFileUrl } from '../../services/api';

const router = useRouter();
const route = useRoute();
const activeStep = ref(1);
const stepLockNotice = ref('');
const application = ref(null);
const academicOptions = ref({ grade_levels: [], tracks: [], strands: [] });
const isLoading = ref(false);
const isRefreshing = ref(false);
const isSubmitting = ref(false);
const isSaving = ref(false);
const isPayingOnline = ref(false);
const showPaymongoModal = ref(false);
const paymongoError = ref('');
const paymongoTab = ref('checkout');
const isCreatingPaymongo = ref(false);
const isVerifyingPaymongo = ref(false);
const paymongoVerifyResult = ref(null);
const walkinTicket = ref(null);
const successMessage = ref('');
const errorMessage = ref('');
const fieldErrors = ref({});

const clearFieldError = (field) => {
  if (fieldErrors.value[field]) {
    delete fieldErrors.value[field];
  }
};

const applicantAge = computed(() => {
  if (!form.value.birthdate) return null;
  const bdate = new Date(form.value.birthdate);
  if (isNaN(bdate.getTime())) return null;
  const today = new Date();
  let age = today.getFullYear() - bdate.getFullYear();
  const m = today.getMonth() - bdate.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < bdate.getDate())) {
    age--;
  }
  return age;
});

const validateStep1 = () => {
  const errors = {};
  const cleanLrn = (form.value.lrn || '').replace(/\D/g, '');
  if (!cleanLrn) {
    errors.lrn = 'DepEd 12-digit LRN is required.';
  } else if (cleanLrn.length !== 12) {
    errors.lrn = 'DepEd LRN must be exactly 12 numeric digits.';
  }

  if (!form.value.applicant_type) {
    errors.applicant_type = 'Please select applicant type.';
  }

  const cleanContact = (form.value.contact_number || '').replace(/\D/g, '');
  if (!cleanContact) {
    errors.contact_number = 'Contact mobile number is required.';
  } else if (!/^09\d{9}$/.test(cleanContact)) {
    errors.contact_number = 'Must be an 11-digit Philippine mobile number starting with 09 (e.g. 09171234567).';
  }

  const firstName = (form.value.first_name || '').trim();
  if (!firstName || firstName.length < 2) {
    errors.first_name = 'First name is required (minimum 2 letters).';
  }

  const lastName = (form.value.last_name || '').trim();
  if (!lastName || lastName.length < 2) {
    errors.last_name = 'Last name is required (minimum 2 letters).';
  }

  if (!['Male', 'Female'].includes(form.value.gender)) {
    errors.gender = 'Please select biological gender.';
  }

  if (!form.value.birthdate) {
    errors.birthdate = 'Date of birth is required.';
  } else if (applicantAge.value !== null && applicantAge.value < 11) {
    errors.birthdate = 'Applicant must be at least 11 years old for high school admission.';
  } else if (applicantAge.value !== null && applicantAge.value > 40) {
    errors.birthdate = 'Please check date of birth.';
  }

  const birthplace = (form.value.birthplace || '').trim();
  if (!birthplace || birthplace.length < 2) {
    errors.birthplace = 'Place of birth is required as per PSA certificate.';
  }

  if (!(form.value.address_barangay || '').trim()) {
    errors.address_barangay = 'Barangay is required.';
  }
  if (!(form.value.address_city || '').trim()) {
    errors.address_city = 'City / Municipality is required.';
  }
  if (!(form.value.address_province || '').trim()) {
    errors.address_province = 'Province is required.';
  }

  const guardianName = (form.value.guardian_name || '').trim();
  if (!guardianName || guardianName.length < 2) {
    errors.guardian_name = 'Parent / Guardian full name is required.';
  }

  const guardianRel = (form.value.guardian_relationship || '').trim();
  if (!guardianRel || guardianRel.length < 2) {
    errors.guardian_relationship = 'Relationship to student is required (e.g. Mother, Father, Guardian).';
  }

  const cleanGuardian = (form.value.guardian_contact || '').replace(/\D/g, '');
  if (!cleanGuardian) {
    errors.guardian_contact = 'Guardian contact number is required.';
  } else if (!/^09\d{9}$/.test(cleanGuardian)) {
    errors.guardian_contact = 'Guardian contact must be an 11-digit mobile number starting with 09.';
  }

  fieldErrors.value = errors;
  return Object.keys(errors).length === 0;
};

const validateStep2 = () => {
  const errors = {};
  if (!form.value.grade_level_id) {
    errors.grade_level_id = 'Please select your target grade level.';
  } else {
    const gl = academicOptions.value?.grade_levels?.find(g => g.id === form.value.grade_level_id);
    const isSHSLevel = gl?.category === 'SHS' || (form.value.grade_level_id >= 5);
    if (isSHSLevel) {
      if (!form.value.track_id) {
        errors.track_id = 'Please select a Senior High academic track.';
      }
      if (!form.value.strand_id) {
        errors.strand_id = 'Please select a Senior High strand.';
      }
      if (!form.value.voucher_status) {
        errors.voucher_status = 'Please select a DepEd voucher category.';
      }
    }
  }

  const lastSchool = (form.value.last_school_attended || '').trim();
  if (!lastSchool || lastSchool.length < 2) {
    errors.last_school_attended = 'Please provide the name of the last school attended.';
  }

  if (!['Public', 'Private'].includes(form.value.last_school_type)) {
    errors.last_school_type = 'Please select previous school type.';
  }

  fieldErrors.value = errors;
  return Object.keys(errors).length === 0;
};

const scrollToFirstError = () => {
  setTimeout(() => {
    const firstError = document.querySelector('.has-field-error, [data-field-error="true"]');
    if (firstError) {
      firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
      const inputEl = firstError.querySelector('input, select, textarea') || firstError;
      if (inputEl && typeof inputEl.focus === 'function') {
        inputEl.focus();
      }
    }
  }, 100);
};

const currentApplicantTab = computed(() => {
  const tab = route.query.tab;
  if (tab === 'checklist') return 'checklist';
  if (tab === 'process' || tab === 'hotlines') return 'process';
  return 'wizard';
});

const navigateToStep = (stepNumber) => {
  activeStep.value = stepNumber;
  router.push({ path: '/admission', query: { tab: 'wizard' } });
};

const walkinForm = ref({
  scheduled_date: '',
  time_slot: '08:00 AM - 11:30 AM (Morning Batch)'
});

const paymongoForm = ref({
  channel: 'GCash',
  reference_no: '',
  account_name: '',
  mobile: '',
  amount: 3000,
  receipt_file: null
});

const onlineSubmission = computed(() => application.value?.online_payment_submission || null);

const isEnrolled = computed(() => {
  return application.value?.status === 'Enrolled' || application.value?.assessment_info?.enrollment_status === 'Officially Enrolled';
});

const isAwaitingVerification = computed(() => {
  if (isEnrolled.value) return false;
  return application.value?.status === 'Payment Submitted – Awaiting Verification' || 
         onlineSubmission.value?.status === 'Pending Verification';
});

const isPaymentFailed = computed(() => {
  if (isEnrolled.value || isAwaitingVerification.value || application.value?.status === 'Walk-in Payment Scheduled') return false;
  return application.value?.status === 'Payment Verification Failed' || 
         (onlineSubmission.value?.status === 'Rejected' && application.value?.status !== 'Walk-in Payment Scheduled');
});

const isWalkinScheduled = computed(() => {
  if (isEnrolled.value || isAwaitingVerification.value) return false;
  if (application.value?.status === 'Walk-in Payment Scheduled') return true;
  if (isPaymentFailed.value) return false;
  return !!walkinTicket.value || 
         !!application.value?.assessment_info?.walkin_ticket_no;
});

const handleReceiptFileChange = (e) => {
  const file = e.target.files?.[0];
  if (file) {
    paymongoForm.value.receipt_file = file;
  }
};

const isSwitchingMode = ref(false);

const switchToWalkinMode = async () => {
  if (isAwaitingVerification.value || isSwitchingMode.value) return;
  isSwitchingMode.value = true;
  errorMessage.value = '';
  try {
    const res = await api.switchPaymentMode({ payment_mode: 'walkin' });
    if (res.data?.ticket_number) {
      walkinTicket.value = res.data;
    }
    await loadData();
    successMessage.value = 'Switched to Walk-In Cashier Payment mode. You can now view and print your Blue Form!';
  } catch (err) {
    errorMessage.value = err.message || 'Failed to switch payment mode.';
  } finally {
    isSwitchingMode.value = false;
  }
};

const switchToOnlineMode = async () => {
  if (isAwaitingVerification.value) return;
  errorMessage.value = '';
  try {
    await api.switchPaymentMode({ payment_mode: 'online' });
    await loadData();
    openPaymongoModal();
  } catch (err) {
    errorMessage.value = err.message || 'Failed to switch payment mode.';
  }
};

const goToStudentLogin = () => {
  sessionStorage.removeItem('sia_auth_token');
  sessionStorage.removeItem('sia_auth_user');
  localStorage.removeItem('sia_auth_token');
  localStorage.removeItem('sia_auth_user');
  window.dispatchEvent(new Event('auth-changed'));
  router.push('/login');
};

const deficientDocs = computed(() => {
  return application.value?.documents?.filter(d => d.status === 'Deficient' || d.status === 'Rejected') || [];
});

const steps = [
  { id: 1, title: 'Personal Demographics', subtitle: 'LRN, Name & Contact' },
  { id: 2, title: 'Grade Level & Strand', subtitle: 'Academic Program Selection' },
  { id: 3, title: 'Send Requirements', subtitle: 'Upload PSA, SF9 & Credentials' },
  { id: 4, title: 'Assessment & Payment', subtitle: 'Walk-in Ticket / Online PayMongo' },
  { id: 5, title: 'Official Registration (COR)', subtitle: 'Permanent ID, Section & Schedule' }
];

const normalizeDocName = (t) => {
  if (!t) return '';
  const trimmed = t.trim();
  if (trimmed === 'Certificate of Good Moral' || trimmed === 'Good Moral Certificate') return 'Certificate of Good Moral Character';
  if (trimmed === '2x2 ID Photo' || trimmed === 'ID Photo' || trimmed === 'ID Picture') return '2x2 ID Picture';
  if (trimmed === 'JHS Completion Certificate') return 'Certificate of JHS Completion';
  return trimmed;
};

const requiredDocsList = computed(() => {
  const glId = application.value?.grade_level_id || form.value.grade_level_id;
  const gl = academicOptions.value?.grade_levels?.find(g => g.id === glId);
  const isSHSLevel = gl?.category === 'SHS' || (glId >= 5);
  const isTransferee = (application.value?.applicant_type || form.value.applicant_type) === 'Transferee';
  const voucher = application.value?.voucher_status || form.value.voucher_status;
  const hasVoucher = isSHSLevel && voucher && voucher !== 'None';

  const list = [
    { type: 'PSA Birth Certificate', desc: 'Original Philippine Statistics Authority (PSA) copy', required: true },
    { type: 'SF9 / Form 138 (Report Card)', desc: 'Previous grade report card signed by Principal', required: true },
    { type: 'Certificate of Good Moral Character', desc: 'Issued by Guidance Counselor or Principal', required: true },
    { type: '2x2 ID Picture', desc: 'Recent formal photo with white background', required: true }
  ];

  if (isSHSLevel && !isTransferee) {
    list.push({ type: 'Certificate of JHS Completion', desc: 'Official Junior High School Completion Diploma / Certificate', required: true });
  }

  if (isTransferee) {
    list.push({ type: 'Certificate of Transfer Credential / Honorable Dismissal', desc: 'Official Transfer Credential / Honorable Dismissal from previous school', required: true });
  }

  if (hasVoucher) {
    list.push({ type: 'ESC Certificate / Voucher Cert', desc: 'DepEd PEAC ESC Certificate or Qualified Voucher Applicant (QVR) Cert', required: true });
  }

  return list;
});

const mandatoryDocs = computed(() => {
  return requiredDocsList.value.filter(r => r.required).map(r => r.type);
});

const hasAllMandatoryDocs = computed(() => {
  const uploaded = (application.value?.documents || [])
    .filter(d => d.status !== 'Rejected' && d.status !== 'Deficient')
    .map(d => normalizeDocName(d.document_type));
  return mandatoryDocs.value.length > 0 && mandatoryDocs.value.every(m => uploaded.includes(normalizeDocName(m)));
});

const missingMandatoryDocs = computed(() => {
  const uploaded = (application.value?.documents || [])
    .filter(d => d.status !== 'Rejected' && d.status !== 'Deficient')
    .map(d => normalizeDocName(d.document_type));
  return mandatoryDocs.value.filter(m => !uploaded.includes(normalizeDocName(m)));
});

const digitalUploadedCount = computed(() => {
  return (application.value?.documents || []).filter(d => 
    d.status !== 'Rejected' && d.status !== 'Deficient' && 
    (d.submission_mode === 'Digital Upload' || (!d.submission_mode && d.file_path))
  ).length;
});

const physicalCount = computed(() => {
  return (application.value?.documents || []).filter(d => 
    d.status !== 'Rejected' && d.status !== 'Deficient' && 
    d.submission_mode === 'Physical Submission'
  ).length;
});

const promissoryCount = computed(() => {
  return (application.value?.documents || []).filter(d => 
    d.status !== 'Rejected' && d.status !== 'Deficient' && 
    d.submission_mode === 'To Follow Up'
  ).length;
});

const verifiedDocsCount = computed(() => {
  return (application.value?.documents || []).filter(d => d.status === 'Verified').length;
});

const fulfilledDocsCount = computed(() => {
  const uploaded = (application.value?.documents || [])
    .filter(d => d.status !== 'Rejected' && d.status !== 'Deficient')
    .map(d => normalizeDocName(d.document_type));
  return mandatoryDocs.value.filter(m => uploaded.includes(normalizeDocName(m))).length;
});

const requirementsPercentage = computed(() => {
  if (!mandatoryDocs.value.length) return 0;
  return Math.min(100, Math.round((fulfilledDocsCount.value / mandatoryDocs.value.length) * 100));
});

const verifiedPercentage = computed(() => {
  if (!mandatoryDocs.value.length) return 0;
  return Math.min(100, Math.round((verifiedDocsCount.value / mandatoryDocs.value.length) * 100));
});

const digitalPercentage = computed(() => {
  if (!mandatoryDocs.value.length) return 0;
  return Math.min(100, Math.round((digitalUploadedCount.value / mandatoryDocs.value.length) * 100));
});

const physicalPercentage = computed(() => {
  if (!mandatoryDocs.value.length) return 0;
  return Math.min(100, Math.round((physicalCount.value / mandatoryDocs.value.length) * 100));
});

const promissoryPercentage = computed(() => {
  if (!mandatoryDocs.value.length) return 0;
  return Math.min(100, Math.round((promissoryCount.value / mandatoryDocs.value.length) * 100));
});

const deficientPercentage = computed(() => {
  if (!mandatoryDocs.value.length) return 0;
  return Math.min(100, Math.round((deficientDocs.value.length / mandatoryDocs.value.length) * 100));
});

const isStep1Completed = computed(() => {
  const app = application.value;
  if (!app) return false;
  return !!(
    app.lrn && String(app.lrn).trim().length === 12 &&
    app.first_name?.trim() &&
    app.last_name?.trim() &&
    app.gender &&
    app.birthdate &&
    app.birthplace?.trim() &&
    app.address_barangay?.trim() &&
    app.address_city?.trim() &&
    app.address_province?.trim() &&
    app.guardian_name?.trim() &&
    app.guardian_relationship?.trim() &&
    app.contact_number?.trim()
  );
});

const isStep2Completed = computed(() => {
  if (!isStep1Completed.value) return false;
  const app = application.value;
  if (!app?.grade_level_id) return false;
  if (!app.last_school_attended?.trim() || !app.last_school_type) return false;
  const gl = academicOptions.value.grade_levels.find(g => g.id === app.grade_level_id);
  const isSHSLevel = gl?.category === 'SHS' || (app.grade_level_id >= 5);
  if (isSHSLevel) {
    return !!(app.track_id && app.strand_id);
  }
  return true;
});

const isStep3Completed = computed(() => {
  if (!isStep2Completed.value) return false;
  const status = application.value?.status;
  return hasAllMandatoryDocs.value && status && !['Draft', 'Pending'].includes(status);
});

const isStep4Completed = computed(() => {
  return application.value?.status === 'Enrolled';
});

const isStep5Completed = computed(() => {
  return application.value?.status === 'Enrolled';
});

const isStepDone = (stepId) => {
  if (stepId === 1) return isStep1Completed.value;
  if (stepId === 2) return isStep2Completed.value;
  if (stepId === 3) return isStep3Completed.value;
  if (stepId === 4) return isStep4Completed.value;
  if (stepId === 5) return isStep5Completed.value;
  return false;
};

const canAccessStep = (stepNumber) => {
  if (stepNumber === 1) return true;
  if (stepNumber === 2) return isStep1Completed.value;
  if (stepNumber === 3) return isStep1Completed.value && isStep2Completed.value;
  if (stepNumber === 4) return isStep3Completed.value;
  if (stepNumber === 5) return isStep4Completed.value;
  return false;
};

const selectStep = async (stepNumber) => {
  if (isSaving.value) return;
  if (stepNumber === activeStep.value) return;

  // 1. If currently on Step 1 (Personal Demographics)
  if (activeStep.value === 1) {
    const isValid = validateStep1();
    if (!isValid) {
      errorMessage.value = 'Please complete all required demographic fields highlighted in red below before navigating to another step.';
      scrollToFirstError();
      return; // CANNOT go to other forms in process stepper when a field is incomplete!
    }

    // Automatically save Step 1 info
    isSaving.value = true;
    errorMessage.value = '';
    try {
      const payload = {
        ...form.value,
        step: 1
      };
      await api.updateApplication(payload);
      fieldErrors.value = {};
      successMessage.value = 'Demographics saved successfully!';
      await loadData();
    } catch (err) {
      if (err.data && err.data.errors) {
        fieldErrors.value = err.data.errors;
        scrollToFirstError();
      }
      errorMessage.value = err.message || 'Failed to save demographics.';
      return; // Stop and do not switch steps
    } finally {
      isSaving.value = false;
    }
  }

  // 2. If currently on Step 2 (Academic Program & Strand)
  else if (activeStep.value === 2) {
    const isValid = validateStep2();
    if (!isValid) {
      errorMessage.value = 'Please complete all required academic program details highlighted below before navigating to another step.';
      scrollToFirstError();
      return; // CANNOT go to other forms in process stepper when a field is incomplete!
    }

    // Automatically save Step 2 info
    isSaving.value = true;
    errorMessage.value = '';
    try {
      const payload = {
        ...form.value,
        step: 2
      };
      await api.updateApplication(payload);
      fieldErrors.value = {};
      successMessage.value = 'Academic program details saved successfully!';
      await loadData();
    } catch (err) {
      if (err.data && err.data.errors) {
        fieldErrors.value = err.data.errors;
        scrollToFirstError();
      }
      errorMessage.value = err.message || 'Failed to save academic program details.';
      return; // Stop and do not switch steps
    } finally {
      isSaving.value = false;
    }
  }

  // 3. Now verify if target step is accessible
  if (canAccessStep(stepNumber)) {
    activeStep.value = stepNumber;
    stepLockNotice.value = '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } else {
    if (stepNumber === 2) {
      stepLockNotice.value = 'Please complete Demographics (Step 1) first.';
    } else if (stepNumber === 3) {
      stepLockNotice.value = 'Please select your Grade Level & Strand (Step 2) first.';
    } else if (stepNumber === 4) {
      if (!hasAllMandatoryDocs.value) {
        stepLockNotice.value = `Please upload mandatory documents (${missingMandatoryDocs.value.join(' and ')}) in Step 3 first.`;
      } else {
        stepLockNotice.value = 'Please submit your requirements for evaluation in Step 3.';
      }
    } else if (stepNumber === 5) {
      if (application.value?.status === 'Payment Submitted – Awaiting Verification') {
        stepLockNotice.value = 'Your payment is currently awaiting Treasury verification. Step 5 will unlock automatically once approved.';
      } else if (application.value?.status === 'Walk-in Payment Scheduled') {
        stepLockNotice.value = 'Please settle your downpayment at the Treasury Cashier (Windows 1 & 2) to unlock Step 5.';
      } else {
        stepLockNotice.value = 'Step 5 (Official Registration & COR) is locked until your downpayment is verified and confirmed by the Treasury.';
      }
    }
  }
};

const goBackToStep1 = async () => {
  if (isSaving.value) return;
  // If Step 2 happens to be valid, auto-save it
  if (validateStep2()) {
    try {
      isSaving.value = true;
      await api.updateApplication({ ...form.value, step: 2 });
      await loadData();
    } catch (e) {
      // Ignore background save errors when user deliberately presses back
    } finally {
      isSaving.value = false;
    }
  }
  fieldErrors.value = {};
  errorMessage.value = '';
  activeStep.value = 1;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const getSidebarStepClass = (stepId) => {
  if (activeStep.value === stepId) {
    return 'bg-slate-900 text-white border-2 border-blue-500 shadow-md ring-2 ring-blue-500/20';
  }
  if (isStepDone(stepId)) {
    return 'bg-white text-slate-800 border border-slate-200 hover:bg-slate-50 cursor-pointer shadow-xs';
  }
  if (!canAccessStep(stepId)) {
    return 'bg-slate-50 text-slate-400 border border-slate-200 opacity-60 cursor-not-allowed';
  }
  return 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 cursor-pointer';
};

const getSidebarIconClass = (stepId) => {
  if (activeStep.value === stepId) {
    return 'bg-blue-500 text-white shadow-sm';
  }
  if (isStepDone(stepId)) {
    return 'bg-emerald-100 text-emerald-800';
  }
  if (!canAccessStep(stepId)) {
    return 'bg-slate-200 text-slate-400';
  }
  return 'bg-slate-100 text-slate-700';
};

const getStepSubtitle = (stepId) => {
  if (activeStep.value === stepId) return 'Currently Working';
  if (stepId === 1) return isStep1Completed.value ? 'Completed ✓' : 'Ready to Start';
  if (stepId === 2) return isStep2Completed.value ? 'Completed ✓' : (!canAccessStep(2) ? 'Locked 🔒' : 'Ready to Start');
  if (stepId === 3) return isStep3Completed.value ? 'Submitted ✓' : (!canAccessStep(3) ? 'Locked 🔒' : (hasAllMandatoryDocs.value ? 'Ready to Submit' : 'Uploads Incomplete'));
  if (stepId === 4) return isStep3Completed.value ? (isStep4Completed.value ? 'Assessment Ready ✓' : 'Pre-Assessment') : 'Locked 🔒';
  if (stepId === 5) {
    if (!isStep3Completed.value) return 'Locked 🔒';
    if (application.value?.status === 'Enrolled') return 'Enrolled ✓';
    return 'Pending Payment';
  }
  return 'Ready to Start';
};

const form = ref({
  applicant_type: 'New Student',
  lrn: '',
  first_name: '',
  middle_name: '',
  last_name: '',
  suffix: '',
  gender: 'Male',
  birthdate: '2010-01-01',
  birthplace: '',
  contact_number: '',
  email: '',
  address_barangay: '',
  address_city: '',
  address_province: '',
  guardian_name: '',
  guardian_relationship: '',
  guardian_contact: '',
  grade_level_id: null,
  track_id: null,
  strand_id: null,
  voucher_status: 'None',
  last_school_attended: '',
  last_school_type: 'Public'
});

const blockNonNumeric = (e) => {
  if (
    ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(e.key) ||
    (e.ctrlKey || e.metaKey)
  ) {
    return;
  }
  if (!/^[0-9]$/.test(e.key)) {
    e.preventDefault();
  }
};

const blockNonAlphabetic = (e) => {
  if (
    ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', ' '].includes(e.key) ||
    (e.ctrlKey || e.metaKey)
  ) {
    return;
  }
  if (/^[0-9]$/.test(e.key) || !/^[a-zA-ZñÑ\-\.\']$/.test(e.key)) {
    e.preventDefault();
  }
};

const handleNumericInput = (field, e, maxLen = null) => {
  let val = (e.target.value || '').replace(/\D/g, '');
  if (maxLen && val.length > maxLen) {
    val = val.slice(0, maxLen);
  }
  form.value[field] = val;
  e.target.value = val;
};

const handleAlphabeticInput = (field, e) => {
  let val = (e.target.value || '').replace(/[^a-zA-ZñÑ\s\-\.\']/g, '');
  form.value[field] = val;
  e.target.value = val;
};

// Reactive sanitizers (runs on every reactive state change)
watch(() => form.value.lrn, (v) => { if (v && /\D/.test(v)) form.value.lrn = v.replace(/\D/g, '').slice(0, 12); });
watch(() => form.value.contact_number, (v) => { if (v && /\D/.test(v)) form.value.contact_number = v.replace(/\D/g, '').slice(0, 11); });
watch(() => form.value.guardian_contact, (v) => { if (v && /\D/.test(v)) form.value.guardian_contact = v.replace(/\D/g, '').slice(0, 11); });
watch(() => form.value.first_name, (v) => { if (v && /[^a-zA-ZñÑ\s\-\.\']/g.test(v)) form.value.first_name = v.replace(/[^a-zA-ZñÑ\s\-\.\']/g, ''); });
watch(() => form.value.middle_name, (v) => { if (v && /[^a-zA-ZñÑ\s\-\.\']/g.test(v)) form.value.middle_name = v.replace(/[^a-zA-ZñÑ\s\-\.\']/g, ''); });
watch(() => form.value.last_name, (v) => { if (v && /[^a-zA-ZñÑ\s\-\.\']/g.test(v)) form.value.last_name = v.replace(/[^a-zA-ZñÑ\s\-\.\']/g, ''); });
watch(() => form.value.suffix, (v) => { if (v && /[^a-zA-ZñÑ\s\-\.\']/g.test(v)) form.value.suffix = v.replace(/[^a-zA-ZñÑ\s\-\.\']/g, ''); });
watch(() => form.value.guardian_name, (v) => { if (v && /[^a-zA-ZñÑ\s\-\.\']/g.test(v)) form.value.guardian_name = v.replace(/[^a-zA-ZñÑ\s\-\.\']/g, ''); });
watch(() => form.value.guardian_relationship, (v) => { if (v && /[^a-zA-ZñÑ\s\-\.\']/g.test(v)) form.value.guardian_relationship = v.replace(/[^a-zA-ZñÑ\s\-\.\']/g, ''); });

const minBirthdate = computed(() => {
  const d = new Date();
  d.setFullYear(d.getFullYear() - 40);
  return d.toISOString().split('T')[0];
});

const maxBirthdate = computed(() => {
  const d = new Date();
  d.setFullYear(d.getFullYear() - 11);
  return d.toISOString().split('T')[0];
});

const isSHS = computed(() => {
  const gl = academicOptions.value.grade_levels.find(g => g.id === form.value.grade_level_id);
  return gl?.category === 'SHS' || (form.value.grade_level_id && form.value.grade_level_id >= 5);
});

watch(() => form.value.grade_level_id, (newGlId) => {
  if (newGlId) {
    const gl = academicOptions.value.grade_levels.find(g => g.id === newGlId);
    const isSHSLevel = gl?.category === 'SHS' || (newGlId >= 5);
    if (!isSHSLevel) {
      form.value.track_id = null;
      form.value.strand_id = null;
      form.value.voucher_status = 'None';
    }
  }
});

const jhsLevels = computed(() => {
  return academicOptions.value.grade_levels.filter(g => g.category === 'JHS' || g.id <= 4);
});

const shsLevels = computed(() => {
  return academicOptions.value.grade_levels.filter(g => g.category === 'SHS' || g.id >= 5);
});

const filteredStrands = computed(() => {
  if (!form.value.track_id) return academicOptions.value.strands;
  return academicOptions.value.strands.filter(s => s.track_id === form.value.track_id);
});

const statusBadgeClass = computed(() => {
  const s = application.value?.status;
  if (s === 'Enrolled') return 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40';
  if (['Approved', 'Queued for Enrollment', 'Assessed'].includes(s)) return 'bg-blue-500/20 text-blue-300 border border-blue-500/40';
  if (s === 'Under Review') return 'bg-amber-500/20 text-amber-300 border border-amber-500/40';
  if (s === 'Requirements Deficient' || s === 'Deficient') return 'bg-rose-500/20 text-rose-300 border border-rose-500/40';
  return 'bg-slate-800 text-slate-300 border border-slate-700';
});

const getDocUploaded = (docType) => {
  const target = normalizeDocName(docType);
  return application.value?.documents?.find(d => normalizeDocName(d.document_type) === target);
};

const getDocStatusBadge = (status) => {
  if (status === 'Verified') return 'bg-emerald-100 text-emerald-800 border border-emerald-300';
  if (status === 'Deficient' || status === 'Rejected') return 'bg-rose-600 text-white shadow-sm';
  if (status === 'Physical Submission') return 'bg-indigo-100 text-indigo-800 border border-indigo-300';
  if (status === 'To Follow Up') return 'bg-amber-100 text-amber-900 border border-amber-300';
  return 'bg-amber-100 text-amber-800 border border-amber-300';
};

const sampleSubjects = computed(() => {
  const glId = application.value?.grade_level_id || form.value.grade_level_id || 1;
  const strandId = application.value?.strand_id || form.value.strand_id;
  const allSubs = academicOptions.value?.subjects || [];

  if (allSubs.length > 0) {
    if (isSHS.value) {
      const filtered = allSubs.filter(sub => {
        const matchesGl = sub.grade_level_id === glId;
        const matchesSem = sub.semester === '1st Semester';
        const matchesStrand = !sub.strand_id || sub.strand_id === strandId;
        return matchesGl && matchesSem && matchesStrand;
      });
      if (filtered.length > 0) return filtered;
    } else {
      const filtered = allSubs.filter(sub => sub.grade_level_id === glId);
      if (filtered.length > 0) return filtered;
    }
  }

  if (isSHS.value) {
    return [
      { code: 'SHS-ORAL', title: 'Oral Communication in Context', category: 'SHS Core', units: '1.0' },
      { code: 'SHS-GENMATH', title: 'General Mathematics', category: 'SHS Core', units: '1.0' },
      { code: 'SHS-ELS', title: 'Earth and Life Science', category: 'SHS Core', units: '1.0' },
      { code: 'SHS-EAPP', title: 'English for Academic & Professional Purposes', category: 'SHS Applied', units: '1.0' },
      { code: 'SHS-EMPTECH', title: 'Empowerment Technologies (ICT)', category: 'SHS Applied', units: '1.0' }
    ];
  }
  return [
    { code: 'ENG-7', title: 'English 7 (Philippine Literature)', category: 'JHS Core', units: '1.0' },
    { code: 'FIL-7', title: 'Filipino 7 (Ibong Adarna)', category: 'JHS Core', units: '1.0' },
    { code: 'MATH-7', title: 'Mathematics 7 (Algebra & Numbers)', category: 'JHS Core', units: '1.0' },
    { code: 'SCI-7', title: 'Science 7 (Integrated Science)', category: 'JHS Core', units: '1.0' },
    { code: 'AP-7', title: 'Araling Panlipunan 7 (Araling Asyano)', category: 'JHS Core', units: '1.0' }
  ];
});

const estimatedAssessment = computed(() => {
  if (application.value?.assessment_info) {
    const info = application.value.assessment_info;
    const tuition = parseFloat(info.total_tuition || 0) || 12500;
    const misc = (parseFloat(info.total_miscellaneous || 0) + parseFloat(info.total_laboratory || 0) + parseFloat(info.total_other_fees || 0)) || 4500;
    const voucherDiscount = parseFloat(info.voucher_discount || 0);
    const netPayable = parseFloat(info.net_payable || (tuition + misc - voucherDiscount));
    const downpayment = parseFloat(info.minimum_downpayment || Math.min(3000, netPayable));
    return {
      tuition,
      misc,
      voucherDiscount,
      netPayable,
      downpayment
    };
  }

  const tuition = 12500;
  const misc = 4500;
  const gross = tuition + misc;
  let voucherDiscount = 0;

  const vStatus = application.value?.voucher_status || form.value.voucher_status;
  const gl = academicOptions.value.grade_levels.find(g => g.id === (application.value?.grade_level_id || form.value.grade_level_id));
  const isSHSLevel = gl?.category === 'SHS' || (application.value?.grade_level_id >= 5) || (form.value.grade_level_id >= 5);

  if (isSHSLevel) {
    if (vStatus === 'Public JHS Completer (100%)') {
      voucherDiscount = 12500;
    } else if (vStatus === 'Private ESC Grantee (80%)') {
      voucherDiscount = 10000;
    } else if (vStatus === 'Private Non-ESC Voucher (50%)') {
      voucherDiscount = 6250;
    }
  }

  const netPayable = Math.max(0, gross - voucherDiscount);

  return {
    tuition,
    misc,
    voucherDiscount,
    netPayable,
    downpayment: Math.min(3000, netPayable)
  };
});

const loadData = async () => {
  isLoading.value = true;
  try {
    const [appRes, optRes] = await Promise.all([
      api.getMyApplication(),
      api.getAcademicOptions()
    ]);

    application.value = appRes.data;
    academicOptions.value = optRes.data;

    // Populate form
    Object.assign(form.value, {
      applicant_type: appRes.data.applicant_type || 'New Student',
      lrn: appRes.data.lrn || '',
      first_name: appRes.data.first_name || '',
      middle_name: appRes.data.middle_name || '',
      last_name: appRes.data.last_name || '',
      suffix: appRes.data.suffix || '',
      gender: appRes.data.gender || 'Male',
      birthdate: appRes.data.birthdate || '2010-01-01',
      birthplace: appRes.data.birthplace || '',
      contact_number: appRes.data.contact_number || '',
      email: appRes.data.email || '',
      address_barangay: appRes.data.address_barangay || '',
      address_city: appRes.data.address_city || '',
      address_province: appRes.data.address_province || '',
      guardian_name: appRes.data.guardian_name || '',
      guardian_relationship: appRes.data.guardian_relationship || '',
      guardian_contact: appRes.data.guardian_contact || '',
      grade_level_id: appRes.data.grade_level_id || null,
      track_id: appRes.data.track_id || null,
      strand_id: appRes.data.strand_id || null,
      voucher_status: appRes.data.voucher_status || 'None',
      last_school_attended: appRes.data.last_school_attended || '',
      last_school_type: appRes.data.last_school_type || 'Public'
    });

    // Auto-populate pre-selected strand if applicant enrolled from HomeView modal
    const savedStrandJson = sessionStorage.getItem('selected_enroll_strand') || localStorage.getItem('selected_enroll_strand');
    if (savedStrandJson) {
      try {
        const savedStrand = JSON.parse(savedStrandJson);
        if (savedStrand.grade_level_id) form.value.grade_level_id = savedStrand.grade_level_id;
        if (savedStrand.track_id) form.value.track_id = savedStrand.track_id;
        if (savedStrand.strand_id) form.value.strand_id = savedStrand.strand_id;
      } catch (e) {
        // Ignore parse error
      }
    }

    // Smart Step Auto-Restoration on load (if not overridden by URL query tab)
    if (!route.query.tab) {
      if (appRes.data.status === 'Enrolled') {
        activeStep.value = 5;
      } else if (['Approved', 'Queued for Enrollment', 'Assessed', 'Payment Submitted – Awaiting Verification', 'Walk-in Payment Scheduled'].includes(appRes.data.status)) {
        activeStep.value = 4;
      } else if (['Under Review', 'Requirements Deficient'].includes(appRes.data.status) || (isStep1Completed.value && isStep2Completed.value)) {
        activeStep.value = 3;
      } else if (isStep1Completed.value) {
        activeStep.value = 2;
      } else {
        activeStep.value = 1;
      }
    }
  } catch (err) {
    errorMessage.value = err.message || 'Failed to load admission details.';
  } finally {
    isLoading.value = false;
  }
};

const refreshPortalData = async () => {
  if (isRefreshing.value) return;
  isRefreshing.value = true;
  try {
    await loadData();
    successMessage.value = 'Admission status refreshed with the latest Registrar evaluation.';
  } catch (err) {
    errorMessage.value = err.message || 'Failed to refresh status.';
  } finally {
    isRefreshing.value = false;
  }
};

const saveApplicationDetails = async (isStep2 = false) => {
  await selectStep(isStep2 ? 3 : 2);
};

const handleFileUpload = async (event, docType) => {
  const file = event.target.files?.[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('document_type', docType);
  formData.append('file', file);

  errorMessage.value = '';
  try {
    await api.uploadDocument(formData);
    successMessage.value = `${docType} uploaded successfully!`;
    await loadData();
  } catch (err) {
    errorMessage.value = err.message || 'Upload failed.';
  }
};

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

const minPromissoryDate = computed(() => {
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  return tomorrow.toISOString().split('T')[0];
});

const defaultPromissoryTargetDate = () => {
  const nextTwoWeeks = new Date();
  nextTwoWeeks.setDate(nextTwoWeeks.getDate() + 14);
  return nextTwoWeeks.toISOString().split('T')[0];
};

const showPhysicalModal = ref(false);
const selectedDocTypeForPhysical = ref('');
const physicalConsent = ref(false);
const isSavingDocMode = ref(false);

const openPhysicalModal = (docType) => {
  selectedDocTypeForPhysical.value = docType;
  physicalConsent.value = false;
  showPhysicalModal.value = true;
};

const confirmPhysicalSubmission = async () => {
  if (!selectedDocTypeForPhysical.value) return;
  isSavingDocMode.value = true;
  errorMessage.value = '';
  try {
    await api.setDocumentSubmissionMode({
      document_type: selectedDocTypeForPhysical.value,
      submission_mode: 'Physical Submission'
    });
    successMessage.value = `Pledged physical submission for ${selectedDocTypeForPhysical.value}. Please submit at Registrar Counter!`;
    showPhysicalModal.value = false;
    await loadData();
  } catch (err) {
    errorMessage.value = err.message || 'Failed to set physical submission mode.';
  } finally {
    isSavingDocMode.value = false;
  }
};

const showBulkPhysicalModal = ref(false);

const confirmBulkPhysical = async () => {
  if (missingMandatoryDocs.value.length === 0) return;

  isSavingDocMode.value = true;
  errorMessage.value = '';
  try {
    await api.setDocumentSubmissionMode({
      document_type: 'ALL',
      submission_mode: 'Physical Submission',
      target_docs: missingMandatoryDocs.value
    });
    successMessage.value = 'Remaining unfulfilled requirements marked for physical submission on campus!';
    showBulkPhysicalModal.value = false;
    await loadData();
  } catch (err) {
    errorMessage.value = err.message || 'Failed to set physical submissions.';
  } finally {
    isSavingDocMode.value = false;
  }
};

const showPromissoryModal = ref(false);
const selectedDocTypeForPromissory = ref('');
const promissoryForm = ref({
  target_date: '',
  reason_preset: '',
  promissory_note: ''
});

const promissoryPresets = [
  'Awaiting release from previous school / PSA office',
  'Currently being processed / requested from Registrar',
  'Delayed due to clearance or certificate processing',
  'Document lost; affidavit of loss / replacement in progress',
  'Parent/Guardian will bring document upon enrollment schedule'
];

const openPromissoryModal = (docType, existingDoc = null) => {
  selectedDocTypeForPromissory.value = docType;
  promissoryForm.value = {
    target_date: existingDoc?.target_date || defaultPromissoryTargetDate(),
    reason_preset: '',
    promissory_note: existingDoc?.promissory_note || ''
  };
  showPromissoryModal.value = true;
};

const setPromissoryPreset = (preset) => {
  promissoryForm.value.reason_preset = preset;
  promissoryForm.value.promissory_note = preset;
};

const confirmPromissorySubmission = async () => {
  if (!selectedDocTypeForPromissory.value || !promissoryForm.value.target_date) return;
  
  isSavingDocMode.value = true;
  errorMessage.value = '';
  try {
    await api.setDocumentSubmissionMode({
      document_type: selectedDocTypeForPromissory.value,
      submission_mode: 'To Follow Up',
      target_date: promissoryForm.value.target_date,
      promissory_note: promissoryForm.value.promissory_note || promissoryForm.value.reason_preset || 'To follow up before start of classes.'
    });
    successMessage.value = `To follow up commitment saved for ${selectedDocTypeForPromissory.value} (Target Date: ${formatDate(promissoryForm.value.target_date)}).`;
    showPromissoryModal.value = false;
    await loadData();
  } catch (err) {
    errorMessage.value = err.message || 'Failed to save promissory note.';
  } finally {
    isSavingDocMode.value = false;
  }
};

const showSubmitReviewConfirm = ref(false);

const openSubmitReviewModal = () => {
  if (!hasAllMandatoryDocs.value) return;
  showSubmitReviewConfirm.value = true;
};

const confirmSubmitForEvaluation = async () => {
  isSubmitting.value = true;
  errorMessage.value = '';
  try {
    await api.submitApplication();
    showSubmitReviewConfirm.value = false;
    successMessage.value = 'Application successfully submitted for Registrar review!';
    activeStep.value = 4;
    await loadData();
  } catch (err) {
    errorMessage.value = err.message || 'Submission failed. Make sure all required documents are uploaded.';
  } finally {
    isSubmitting.value = false;
  }
};

const generateWalkinTicket = async () => {
  errorMessage.value = '';
  try {
    const res = await api.checkoutPayment({
      payment_type: 'walkin',
      walkin_date: walkinForm.value.scheduled_date,
      time_slot: walkinForm.value.time_slot,
      amount: estimatedAssessment.value.downpayment
    });
    walkinTicket.value = res.data;
    successMessage.value = 'Walk-in Cashier Payment Slip generated successfully! Present this at Window 1 or 2.';
    await loadData();
  } catch (err) {
    errorMessage.value = err.message || 'Failed to generate walk-in ticket.';
  }
};

const openPaymongoModal = () => {
  paymongoError.value = '';
  paymongoForm.value.amount = estimatedAssessment.value.downpayment;
  paymongoForm.value.mobile = application.value?.contact_number || '';
  paymongoForm.value.account_name = `${application.value?.first_name || ''} ${application.value?.last_name || ''}`.trim();
  paymongoForm.value.reference_no = '';
  paymongoForm.value.receipt_file = null;
  paymongoTab.value = 'checkout';
  showPaymongoModal.value = true;
};

const initiatePayMongoCheckout = async () => {
  paymongoError.value = '';
  const amt = Number(paymongoForm.value.amount || estimatedAssessment.value.downpayment || 3000);
  if (!amt || amt <= 0) {
    paymongoError.value = 'Please enter a valid payment amount.';
    return;
  }
  const minRequired = Math.min(estimatedAssessment.value.downpayment || 3000, estimatedAssessment.value.netPayable || 0);
  const maxAllowed = estimatedAssessment.value.netPayable || 0;

  if (amt < minRequired) {
    paymongoError.value = `Payment amount (₱${amt.toLocaleString('en-US', {minimumFractionDigits: 2})}) is below the required minimum of ₱${minRequired.toLocaleString('en-US', {minimumFractionDigits: 2})}.`;
    return;
  }

  if (maxAllowed > 0 && amt > (maxAllowed + 0.01)) {
    paymongoError.value = `Payment amount (₱${amt.toLocaleString('en-US', {minimumFractionDigits: 2})}) exceeds the maximum balance of ₱${maxAllowed.toLocaleString('en-US', {minimumFractionDigits: 2})}.`;
    return;
  }

  isCreatingPaymongo.value = true;
  try {
    const res = await api.createPaymongoCheckout({
      amount: amt
    });

    if (res.data && res.data.checkout_url) {
      window.location.href = res.data.checkout_url;
    } else {
      throw new Error(res.message || 'No checkout URL returned by PayMongo gateway.');
    }
  } catch (err) {
    paymongoError.value = err.message || 'Failed to initiate PayMongo checkout session.';
  } finally {
    isCreatingPaymongo.value = false;
  }
};

const submitOnlinePayment = async () => {
  paymongoError.value = '';
  if (!paymongoForm.value.reference_no?.trim()) {
    paymongoError.value = 'Please enter your payment reference number / transaction ID.';
    return;
  }
  if (!paymongoForm.value.amount || paymongoForm.value.amount <= 0) {
    paymongoError.value = 'Please enter a valid payment amount.';
    return;
  }
  const minRequired = Math.min(estimatedAssessment.value.downpayment || 3000, estimatedAssessment.value.netPayable || 0);
  const maxAllowed = estimatedAssessment.value.netPayable || 0;

  if (paymongoForm.value.amount < minRequired) {
    paymongoError.value = `Payment amount (₱${Number(paymongoForm.value.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}) is below the required minimum of ₱${Number(minRequired).toLocaleString('en-US', {minimumFractionDigits: 2})}.`;
    return;
  }

  if (maxAllowed > 0 && paymongoForm.value.amount > maxAllowed) {
    paymongoError.value = `Payment amount (₱${Number(paymongoForm.value.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}) exceeds the maximum settle limit / remaining balance of ₱${Number(maxAllowed).toLocaleString('en-US', {minimumFractionDigits: 2})}.`;
    return;
  }
  if (!paymongoForm.value.receipt_file && !onlineSubmission.value?.receipt_file_path) {
    paymongoError.value = 'Please upload your official payment receipt screenshot or PDF for Treasury verification.';
    return;
  }
  isPayingOnline.value = true;
  errorMessage.value = '';
  try {
    const formData = new FormData();
    formData.append('payment_type', 'online');
    formData.append('payment_channel', paymongoForm.value.channel || 'GCash');
    formData.append('reference_no', paymongoForm.value.reference_no.trim());
    formData.append('account_name', (paymongoForm.value.account_name || '').trim());
    formData.append('account_number', (paymongoForm.value.mobile || '').trim());
    formData.append('amount', paymongoForm.value.amount);
    if (paymongoForm.value.receipt_file) {
      formData.append('receipt', paymongoForm.value.receipt_file);
    }

    const res = await api.checkoutPayment(formData);

    showPaymongoModal.value = false;
    successMessage.value = res.message || 'Payment submitted successfully! Awaiting Treasury verification.';
    await loadData();
  } catch (err) {
    paymongoError.value = err.message || 'Online payment submission failed. Please check your reference number.';
    errorMessage.value = err.message || 'Online payment submission failed.';
  } finally {
    isPayingOnline.value = false;
  }
};

const printSlip = () => {
  window.print();
};

const previewDoc = ref(null);
const docxContainerRef = ref(null);
const isRenderingDocx = ref(false);
const docxRenderError = ref('');

const openPreviewModal = async (doc) => {
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

const showDeleteDocModal = ref(false);
const deleteDocTarget = ref(null);
const isDeletingDoc = ref(false);

const openDeleteDocModal = (doc) => {
  if (!doc || !doc.id) return;
  deleteDocTarget.value = doc;
  showDeleteDocModal.value = true;
};

const confirmDeleteDocument = async () => {
  const doc = deleteDocTarget.value;
  if (!doc || !doc.id) return;

  isDeletingDoc.value = true;
  errorMessage.value = '';
  try {
    const res = await api.deleteDocument(doc.id);
    successMessage.value = res.message || `${doc.document_type} removed successfully.`;
    showDeleteDocModal.value = false;
    deleteDocTarget.value = null;
    await loadData();
  } catch (err) {
    errorMessage.value = err.message || 'Failed to remove document.';
  } finally {
    isDeletingDoc.value = false;
  }
};

watch(() => route.query.tab, (tab) => {
  if (!tab) return;
  if (tab === 'demographics') selectStep(1);
  else if (tab === 'strand') selectStep(2);
  else if (tab === 'documents') selectStep(3);
  else if (tab === 'payment') selectStep(4);
  else if (tab === 'overview' || tab === 'status') {
    if (canAccessStep(5)) selectStep(5);
    else if (canAccessStep(4)) selectStep(4);
    else selectStep(1);
  }
}, { immediate: true });

const checkPayMongoReturn = async () => {
  const hashParts = window.location.hash.split('?');
  const searchStr = hashParts[1] || window.location.search.replace(/^\?/, '');
  if (!searchStr) return;

  const urlParams = new URLSearchParams(searchStr);
  const paymongoStatus = urlParams.get('paymongo_status');
  const sessionId = urlParams.get('session_id');

  if (paymongoStatus === 'success' && sessionId) {
    isVerifyingPaymongo.value = true;
    errorMessage.value = '';
    try {
      const res = await api.verifyPaymongoSession(sessionId);
      paymongoVerifyResult.value = res.data;
      successMessage.value = res.message || 'Payment verified successfully! You are now officially enrolled.';
      await loadData();
      selectStep(5);
    } catch (err) {
      errorMessage.value = err.message || 'Failed to verify PayMongo payment session.';
    } finally {
      isVerifyingPaymongo.value = false;
      const cleanHash = window.location.hash.split('?')[0];
      window.history.replaceState(null, '', cleanHash);
    }
  } else if (paymongoStatus === 'cancelled') {
    errorMessage.value = 'PayMongo online payment was cancelled. You may retry whenever you are ready.';
    const cleanHash = window.location.hash.split('?')[0];
    window.history.replaceState(null, '', cleanHash);
  }
};

onMounted(async () => {
  await loadData();
  checkPayMongoReturn();
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
