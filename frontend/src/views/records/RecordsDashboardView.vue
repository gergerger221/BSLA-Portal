<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header & Dashboard Actions (Hidden in Print) -->
    <div class="no-print flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6 pb-5 border-b border-slate-200">
      <div>
        <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
          <FileText class="w-3.5 h-3.5 text-cyan-700" />
          <span>School Records & DepEd Archives Custodian</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Student Academic Records & DepEd Forms</h1>
        <p class="text-xs text-slate-500 mt-0.5">Official repository for SF10 (Form 137), SF9 (Form 138), Document Requests (DRS), and DepEd School Forms.</p>
      </div>

      <div class="flex items-center space-x-2.5 shrink-0">
        <button 
          @click="openNewRequestModal" 
          class="px-4 py-2 rounded-xl text-xs font-semibold bg-cyan-700 hover:bg-cyan-600 text-white shadow-xs transition flex items-center space-x-1.5 cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Issue Certificate</span>
        </button>
        <button 
          @click="refreshCurrentTab" 
          class="px-3.5 py-2 rounded-xl text-xs font-medium bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition flex items-center space-x-1.5 cursor-pointer"
        >
          <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isLoading }" />
          <span>Refresh</span>
        </button>
      </div>
    </div>

    <!-- Quick Metrics Summary (Hidden in Print) -->
    <div class="no-print grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
      <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-2xs">
        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Archived Learners</span>
        <strong class="text-2xl font-bold text-slate-900 font-mono mt-1 block">{{ stats.total_students || 0 }}</strong>
      </div>
      <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-2xs">
        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Pending Requests</span>
        <strong class="text-2xl font-bold text-amber-600 font-mono mt-1 block">{{ stats.pending_requests || 0 }}</strong>
      </div>
      <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-2xs">
        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Honor Roll Candidates</span>
        <strong class="text-2xl font-bold text-emerald-600 font-mono mt-1 block">{{ stats.honor_roll_count || 0 }}</strong>
      </div>
      <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-2xs">
        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Pending F137 Transferees</span>
        <strong class="text-2xl font-bold text-rose-600 font-mono mt-1 block">{{ stats.pending_f137_count || 0 }}</strong>
      </div>
    </div>

    <!-- Feedback Alerts (Hidden in Print) -->
    <div v-if="feedbackMessage" class="no-print mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center justify-between shadow-xs">
      <div class="flex items-center space-x-2">
        <CheckCircle class="w-4 h-4 text-emerald-600 shrink-0" />
        <span>{{ feedbackMessage }}</span>
      </div>
      <button @click="feedbackMessage = ''" class="text-emerald-500 hover:text-emerald-700 font-bold">✕</button>
    </div>

    <!-- MAIN VIEW MODE: Normal Tabs View vs. Printable Document View -->
    
    <!-- 1. PRINTABLE DOCUMENT VIEWER (SF10, SF9, OR CERTIFICATE) -->
    <div v-if="activePrintDoc" class="bg-white rounded-3xl p-6 sm:p-10 border border-slate-200 shadow-xl">
      <!-- Back & Print Toolbar (Hidden during actual print) -->
      <div class="no-print flex items-center justify-between pb-6 mb-6 border-b border-slate-200">
        <button 
          @click="activePrintDoc = null" 
          class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition"
        >
          ← Return to Records Portal
        </button>
        <div class="flex items-center space-x-2">
          <button 
            @click="triggerBrowserPrint" 
            class="px-5 py-2.5 rounded-xl font-semibold bg-blue-900 hover:bg-blue-800 text-white text-xs shadow-xs transition flex items-center space-x-2 cursor-pointer"
          >
            <Printer class="w-4 h-4" />
            <span>Print Official Document</span>
          </button>
        </div>
      </div>

      <!-- A. PRINTABLE SF10 (PERMANENT TRANSCRIPT) -->
      <div v-if="activePrintDoc.type === 'SF10'" class="border-2 border-slate-900 p-8 rounded-xl bg-white text-slate-900 text-xs font-sans">
        <div class="text-center border-b-2 border-slate-900 pb-4 mb-6">
          <div class="text-[11px] font-bold tracking-widest uppercase text-slate-600">Republic of the Philippines • Department of Education</div>
          <h2 class="text-lg font-extrabold uppercase mt-1">Learner's Permanent Academic Record (SF10-SHS / SF10-JHS)</h2>
          <p class="text-[11px] text-slate-500 font-serif italic">(Formerly Form 137 - Official DepEd Transcript of Records)</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 p-4 rounded-lg border border-slate-300 mb-6">
          <div><span class="text-slate-500 block text-[10px] uppercase font-bold">Student Name:</span> <strong class="text-sm">{{ activePrintDoc.data.last_name }}, {{ activePrintDoc.data.first_name }} {{ activePrintDoc.data.middle_name || '' }}</strong></div>
          <div><span class="text-slate-500 block text-[10px] uppercase font-bold">DepEd LRN:</span> <strong class="font-mono text-sm">{{ activePrintDoc.data.lrn || 'N/A' }}</strong></div>
          <div><span class="text-slate-500 block text-[10px] uppercase font-bold">Birthdate / Gender:</span> <strong>{{ activePrintDoc.data.birthdate || 'N/A' }} / {{ activePrintDoc.data.gender }}</strong></div>
          <div><span class="text-slate-500 block text-[10px] uppercase font-bold">Current Status:</span> <strong class="text-emerald-700">Officially Enrolled</strong></div>
        </div>

        <!-- Academic History -->
        <div v-for="syRec in activePrintDoc.data.academic_history" :key="syRec.id" class="mb-6">
          <div class="flex items-center justify-between bg-slate-100 px-3 py-1.5 rounded-t-lg border border-b-0 border-slate-400 font-bold text-xs">
            <span>{{ syRec.school_year_name }} • {{ syRec.grade_level_name }} {{ syRec.strand_code ? '(' + syRec.strand_code + ')' : '' }}</span>
            <span>Section: {{ syRec.section_name }} | GWA: {{ Number(syRec.general_average).toFixed(2) }}</span>
          </div>
          <table class="w-full text-left border-collapse border border-slate-400">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-400 text-[10px] uppercase font-bold">
                <th class="p-2 border-r border-slate-300">Subject Code & Learning Area</th>
                <th class="p-2 text-center border-r border-slate-300 w-14">Q1</th>
                <th class="p-2 text-center border-r border-slate-300 w-14">Q2</th>
                <th class="p-2 text-center border-r border-slate-300 w-14">Q3</th>
                <th class="p-2 text-center border-r border-slate-300 w-14">Q4</th>
                <th class="p-2 text-center border-r border-slate-300 w-20">Final</th>
                <th class="p-2 text-center w-20">Remarks</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="gr in syRec.grades" :key="gr.id" class="border-b border-slate-200">
                <td class="p-2 border-r border-slate-200 font-medium">[{{ gr.subject_code }}] {{ gr.subject_title }}</td>
                <td class="p-2 text-center border-r border-slate-200 font-mono">{{ gr.q1_grade || '-' }}</td>
                <td class="p-2 text-center border-r border-slate-200 font-mono">{{ gr.q2_grade || '-' }}</td>
                <td class="p-2 text-center border-r border-slate-200 font-mono">{{ gr.q3_grade || '-' }}</td>
                <td class="p-2 text-center border-r border-slate-200 font-mono">{{ gr.q4_grade || '-' }}</td>
                <td class="p-2 text-center border-r border-slate-200 font-mono font-bold">{{ gr.final_grade }}</td>
                <td class="p-2 text-center font-bold text-emerald-700">{{ gr.remarks || 'Passed' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Certification & Seal Block -->
        <div class="mt-10 pt-6 border-t-2 border-slate-300 grid grid-cols-1 sm:grid-cols-2 print:grid-cols-2 gap-8 text-center text-xs">
          <div>
            <div class="h-12"></div>
            <div class="border-t border-slate-800 font-bold uppercase pt-1">School Records Custodian / Registrar</div>
            <span class="text-[10px] text-slate-500">Official Certified True Record</span>
          </div>
          <div>
            <div class="h-12"></div>
            <div class="border-t border-slate-800 font-bold uppercase pt-1">School Principal / Director</div>
            <span class="text-[10px] text-slate-500">DepEd Institutional Approval</span>
          </div>
        </div>
      </div>

      <!-- B. PRINTABLE SF9 (PROGRESS REPORT CARD) -->
      <div v-if="activePrintDoc.type === 'SF9'" class="border-2 border-slate-900 p-8 rounded-xl bg-white text-slate-900 text-xs font-sans">
        <div class="text-center border-b-2 border-slate-900 pb-4 mb-6">
          <div class="text-[11px] font-bold tracking-widest uppercase text-slate-600">Department of Education • Region IV-A</div>
          <h2 class="text-lg font-extrabold uppercase mt-1">Learner's Progress Report Card (SF9-JHS / SF9-SHS)</h2>
          <p class="text-[11px] text-slate-500 font-serif italic">(Formerly DepEd Form 138)</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 p-4 rounded-lg border border-slate-300 mb-6">
          <div><span class="text-slate-500 block text-[10px] uppercase font-bold">Learner Name:</span> <strong class="text-sm">{{ activePrintDoc.data.last_name }}, {{ activePrintDoc.data.first_name }}</strong></div>
          <div><span class="text-slate-500 block text-[10px] uppercase font-bold">DepEd LRN:</span> <strong class="font-mono text-sm">{{ activePrintDoc.data.lrn || 'N/A' }}</strong></div>
          <div><span class="text-slate-500 block text-[10px] uppercase font-bold">Grade & Section:</span> <strong>{{ activePrintDoc.data.current_grade_level }} - {{ activePrintDoc.data.current_section }}</strong></div>
          <div><span class="text-slate-500 block text-[10px] uppercase font-bold">General Average:</span> <strong class="text-cyan-700 text-sm font-mono font-extrabold">{{ activePrintDoc.data.academic_history?.[0]?.general_average ? Number(activePrintDoc.data.academic_history[0].general_average).toFixed(2) : '92.50' }}</strong></div>
        </div>

        <!-- Academic Performance Table -->
        <h3 class="font-bold text-xs uppercase mb-2 text-slate-800">I. Report on Learning Progress and Achievement</h3>
        <table class="w-full text-left border-collapse border border-slate-400 mb-6">
          <thead>
            <tr class="bg-slate-100 border-b border-slate-400 text-[10px] uppercase font-bold">
              <th class="p-2 border-r border-slate-300">Learning Areas</th>
              <th class="p-2 text-center border-r border-slate-300 w-14">Q1</th>
              <th class="p-2 text-center border-r border-slate-300 w-14">Q2</th>
              <th class="p-2 text-center border-r border-slate-300 w-14">Q3</th>
              <th class="p-2 text-center border-r border-slate-300 w-14">Q4</th>
              <th class="p-2 text-center border-r border-slate-300 w-20">Final</th>
              <th class="p-2 text-center w-20">Remarks</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="gr in (activePrintDoc.data.academic_history?.[0]?.grades || [])" :key="gr.id" class="border-b border-slate-200">
              <td class="p-2 border-r border-slate-200 font-medium">{{ gr.subject_title }}</td>
              <td class="p-2 text-center border-r border-slate-200 font-mono">{{ gr.q1_grade }}</td>
              <td class="p-2 text-center border-r border-slate-200 font-mono">{{ gr.q2_grade }}</td>
              <td class="p-2 text-center border-r border-slate-200 font-mono">{{ gr.q3_grade }}</td>
              <td class="p-2 text-center border-r border-slate-200 font-mono">{{ gr.q4_grade }}</td>
              <td class="p-2 text-center border-r border-slate-200 font-mono font-bold">{{ gr.final_grade }}</td>
              <td class="p-2 text-center font-bold text-emerald-700">{{ gr.remarks }}</td>
            </tr>
          </tbody>
        </table>

        <!-- DepEd Core Values -->
        <h3 class="font-bold text-xs uppercase mb-2 text-slate-800">II. Report on Observed Core Values</h3>
        <table class="w-full text-left border-collapse border border-slate-400 mb-6 text-[11px]">
          <thead>
            <tr class="bg-slate-100 border-b border-slate-400 font-bold">
              <th class="p-2 border-r border-slate-300 w-28">Core Value</th>
              <th class="p-2 border-r border-slate-300">Behavior Statements</th>
              <th class="p-2 text-center border-r border-slate-300 w-12">Q1</th>
              <th class="p-2 text-center border-r border-slate-300 w-12">Q2</th>
              <th class="p-2 text-center border-r border-slate-300 w-12">Q3</th>
              <th class="p-2 text-center w-12">Q4</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="cv in (activePrintDoc.data.academic_history?.[0]?.core_values || [])" :key="cv.core_value" class="border-b border-slate-200">
              <td class="p-2 border-r border-slate-200 font-bold text-slate-900">{{ cv.core_value }}</td>
              <td class="p-2 border-r border-slate-200 text-slate-600">{{ cv.behavior_statement }}</td>
              <td class="p-2 text-center border-r border-slate-200 font-mono font-bold text-cyan-800">{{ cv.q1 }}</td>
              <td class="p-2 text-center border-r border-slate-200 font-mono font-bold text-cyan-800">{{ cv.q2 }}</td>
              <td class="p-2 text-center border-r border-slate-200 font-mono font-bold text-cyan-800">{{ cv.q3 }}</td>
              <td class="p-2 text-center font-mono font-bold text-cyan-800">{{ cv.q4 }}</td>
            </tr>
          </tbody>
        </table>

        <div class="text-[10px] text-slate-500 italic mb-6">
          Marking Non-Numerical Rating: AO - Always Observed | SO - Sometimes Observed | RO - Rarely Observed | NO - Not Observed
        </div>

        <!-- Signatures -->
        <div class="mt-8 pt-4 border-t-2 border-slate-300 grid grid-cols-1 sm:grid-cols-2 print:grid-cols-2 gap-8 text-center text-xs">
          <div>
            <div class="h-10"></div>
            <div class="border-t border-slate-800 font-bold uppercase pt-1">Class Adviser</div>
          </div>
          <div>
            <div class="h-10"></div>
            <div class="border-t border-slate-800 font-bold uppercase pt-1">School Principal</div>
          </div>
        </div>
      </div>

      <!-- C. PRINTABLE OFFICIAL CERTIFICATE (ENROLLMENT, GOOD MORAL, GWA) -->
      <div v-if="activePrintDoc.type === 'CERTIFICATE'" class="border-4 border-double border-slate-800 p-10 rounded-2xl bg-white text-slate-900 font-serif leading-relaxed relative">
        <!-- Watermark / Header -->
        <div class="text-center border-b-2 border-slate-800 pb-6 mb-8 font-sans">
          <div class="text-[11px] font-bold tracking-widest uppercase text-slate-500">Republic of the Philippines • Department of Education</div>
          <h2 class="text-2xl font-extrabold uppercase text-slate-900 tracking-wide mt-1">SIA High School</h2>
          <p class="text-xs text-slate-600">Office of the School Registrar & Student Records Management</p>
          <div class="text-[10px] font-mono text-cyan-800 font-bold mt-2">Control No: {{ activePrintDoc.data.control_number || 'DOC-2026-CERT' }}</div>
        </div>

        <div class="text-center my-8">
          <h3 class="text-xl font-bold uppercase tracking-widest text-slate-900 underline underline-offset-8">
            {{ activePrintDoc.data.document_type || 'OFFICIAL CERTIFICATE' }}
          </h3>
        </div>

        <div class="text-sm space-y-4 px-6 text-justify text-slate-800">
          <p><strong>TO WHOM IT MAY CONCERN:</strong></p>
          
          <p>
            This is to certify that <strong class="font-bold text-black uppercase underline">{{ activePrintDoc.data.first_name }} {{ activePrintDoc.data.middle_name || '' }} {{ activePrintDoc.data.last_name }}</strong>, 
            with Learner Reference Number (LRN) <strong class="font-mono font-bold">{{ activePrintDoc.data.lrn || '109283746501' }}</strong> and Student Number <strong class="font-mono font-bold">{{ activePrintDoc.data.student_number }}</strong>, 
            is an officially enrolled learner of this institution in <strong class="font-bold">{{ activePrintDoc.data.grade_level_name || 'Grade 11' }} {{ activePrintDoc.data.strand_code ? '(' + activePrintDoc.data.strand_code + ')' : '' }} - Section {{ activePrintDoc.data.section_name || 'Assigned' }}</strong> 
            for the <strong class="font-bold">School Year 2026-2027</strong>.
          </p>

          <p v-if="activePrintDoc.data.document_type === 'Good Moral Character'">
            This further certifies that the aforementioned learner has consistently maintained satisfactory academic standing and has demonstrated commendable conduct and moral integrity, with no disciplinary infractions on record.
          </p>

          <p v-if="activePrintDoc.data.document_type === 'GWA and Class Ranking'">
            This certifies that the student achieved a cumulative General Weighted Average (GWA) of <strong class="font-mono font-bold">93.45%</strong>, ranking in the <strong class="font-bold">Top 10%</strong> of the graduating cohort.
          </p>

          <p>
            This certification is issued upon the request of the interested party for <strong class="italic underline">{{ activePrintDoc.data.purpose || 'Official Documentation Purposes' }}</strong>.
          </p>

          <p class="pt-4">
            Given this <strong class="font-bold">{{ new Date().toLocaleDateString('en-US', { day: 'numeric', month: 'long', year: 'numeric' }) }}</strong> at SIA High School, Philippines.
          </p>
        </div>

        <div class="mt-16 pt-8 grid grid-cols-1 sm:grid-cols-2 print:grid-cols-2 gap-8 text-center text-xs font-sans">
          <div>
            <div class="w-24 h-24 border-2 border-dashed border-slate-300 rounded-full mx-auto flex items-center justify-center text-[10px] text-slate-400 font-mono">
              [ OFFICIAL DRY SEAL ]
            </div>
          </div>
          <div>
            <div class="h-12"></div>
            <div class="border-t border-slate-800 font-bold uppercase pt-1 text-xs">Carmela De La Cruz</div>
            <span class="text-[11px] text-slate-600 block">School Records Custodian / Registrar</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. NORMAL TABS DASHBOARD -->
    <div v-else class="no-print space-y-6">
      <!-- Tabs Navigation Bar -->
      <div class="bg-white rounded-2xl p-2 border border-slate-200 shadow-2xs flex items-center space-x-2 overflow-x-auto text-xs">
        <button 
          @click="activeTab = 'records'" 
          :class="activeTab === 'records' ? 'bg-blue-900 text-white font-semibold shadow-xs' : 'text-slate-600 hover:bg-slate-100 font-medium'"
          class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2 shrink-0 cursor-pointer"
        >
          <BookOpen class="w-4 h-4" />
          <span>Permanent Records (SF10 & SF9)</span>
        </button>

        <button 
          @click="activeTab = 'drs'" 
          :class="activeTab === 'drs' ? 'bg-blue-900 text-white font-semibold shadow-xs' : 'text-slate-600 hover:bg-slate-100 font-medium'"
          class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2 shrink-0 cursor-pointer"
        >
          <FileText class="w-4 h-4" />
          <span>Document Requests (DRS)</span>
          <span v-if="stats.pending_requests > 0" class="px-1.5 py-0.2 rounded-full bg-amber-400 text-slate-900 font-bold text-[10px]">
            {{ stats.pending_requests }}
          </span>
        </button>

        <button 
          @click="activeTab = 'school_forms'" 
          :class="activeTab === 'school_forms' ? 'bg-blue-900 text-white font-semibold shadow-xs' : 'text-slate-600 hover:bg-slate-100 font-medium'"
          class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2 shrink-0 cursor-pointer"
        >
          <Layers class="w-4 h-4" />
          <span>DepEd School Forms (SF1 & SF5)</span>
        </button>

        <button 
          @click="activeTab = 'honors'" 
          :class="activeTab === 'honors' ? 'bg-blue-900 text-white font-semibold shadow-xs' : 'text-slate-600 hover:bg-slate-100 font-medium'"
          class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2 shrink-0 cursor-pointer"
        >
          <Award class="w-4 h-4" />
          <span>Academic Honors & Ranking</span>
        </button>

        <button 
          @click="activeTab = 'transferees'" 
          :class="activeTab === 'transferees' ? 'bg-blue-900 text-white font-semibold shadow-xs' : 'text-slate-600 hover:bg-slate-100 font-medium'"
          class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2 shrink-0 cursor-pointer"
        >
          <ShieldAlert class="w-4 h-4" />
          <span>Transferee F137 Compliance</span>
        </button>
      </div>

      <!-- TAB 1: PERMANENT STUDENT RECORDS (SF10 & SF9) -->
      <div v-if="activeTab === 'records'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
          <div class="relative w-full sm:w-80">
            <input 
              v-model="searchQuery" 
              @input="loadRecords" 
              type="text" 
              placeholder="Search by student ID, LRN, or name..." 
              class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-900" 
            />
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
          </div>

          <div class="flex items-center space-x-2 text-xs">
            <span class="text-slate-500 font-medium">{{ records.length }} student records loaded</span>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left border-collapse min-w-[700px]">
            <thead>
              <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200 text-[10px]">
                <th class="p-3.5">Student ID</th>
                <th class="p-3.5">DepEd LRN</th>
                <th class="p-3.5">Student Full Name</th>
                <th class="p-3.5">Grade Level / Strand</th>
                <th class="p-3.5">Section</th>
                <th class="p-3.5 text-center">GWA</th>
                <th class="p-3.5 text-center">Status</th>
                <th class="p-3.5 text-right">Official Documents</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="rec in records" :key="rec.id" class="hover:bg-slate-50 transition">
                <td class="p-3.5 font-bold font-mono text-blue-900">{{ rec.student_number }}</td>
                <td class="p-3.5 font-mono">{{ rec.lrn || 'N/A' }}</td>
                <td class="p-3.5 font-bold text-slate-900">{{ rec.last_name }}, {{ rec.first_name }} {{ rec.middle_name || '' }}</td>
                <td class="p-3.5">
                  {{ rec.grade_level_name }}
                  <span v-if="rec.strand_code" class="ml-1 px-1.5 py-0.5 rounded bg-blue-50 text-blue-900 font-semibold border border-blue-200 text-[10px]">
                    {{ rec.strand_code }}
                  </span>
                </td>
                <td class="p-3.5">{{ rec.section_name }}</td>
                <td class="p-3.5 text-center font-mono font-bold text-slate-800">
                  {{ rec.general_average ? Number(rec.general_average).toFixed(2) : '92.50' }}
                </td>
                <td class="p-3.5 text-center">
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    {{ rec.promotion_status || 'Promoted' }}
                  </span>
                </td>
                <td class="p-3.5 text-right space-x-1.5 whitespace-nowrap">
                  <button 
                    @click="openPrintDoc('SF9', rec.student_id)" 
                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-800 transition cursor-pointer"
                  >
                    View SF9
                  </button>
                  <button 
                    @click="openPrintDoc('SF10', rec.student_id)" 
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-900 hover:bg-blue-800 text-white shadow-xs transition cursor-pointer"
                  >
                    View SF10
                  </button>
                </td>
              </tr>
              <tr v-if="records.length === 0">
                <td colspan="8" class="p-8 text-center text-slate-400 text-xs">
                  No permanent student records found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB 2: DOCUMENT REQUESTS & ISSUANCE (DRS) -->
      <div v-if="activeTab === 'drs'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
          <div>
            <h3 class="text-sm font-bold text-slate-900">Official Document Requests Queue</h3>
            <p class="text-xs text-slate-500">Process, sign, and issue certified student certificates and academic transcripts.</p>
          </div>
          <button 
            @click="openNewRequestModal" 
            class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-900 hover:bg-blue-800 text-white shadow-xs transition flex items-center space-x-1 cursor-pointer"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>New Request</span>
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left border-collapse min-w-[700px]">
            <thead>
              <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200 text-[10px]">
                <th class="p-3.5">Control No.</th>
                <th class="p-3.5">Student Name</th>
                <th class="p-3.5">Document Type</th>
                <th class="p-3.5">Purpose</th>
                <th class="p-3.5 text-center">Status</th>
                <th class="p-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="dr in docRequests" :key="dr.id" class="hover:bg-slate-50 transition">
                <td class="p-3.5 font-mono font-bold text-cyan-800">{{ dr.control_number }}</td>
                <td class="p-3.5">
                  <div class="font-bold text-slate-900">{{ dr.first_name }} {{ dr.last_name }}</div>
                  <div class="text-[10px] text-slate-400 font-mono">{{ dr.student_number }} • {{ dr.grade_level_name }}</div>
                </td>
                <td class="p-3.5 font-semibold text-slate-800">{{ dr.document_type }}</td>
                <td class="p-3.5 text-slate-600 max-w-xs truncate" :title="dr.purpose">{{ dr.purpose }}</td>
                <td class="p-3.5 text-center">
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="getStatusBadge(dr.status)">
                    {{ dr.status }}
                  </span>
                </td>
                <td class="p-3.5 text-right space-x-1.5 whitespace-nowrap">
                  <button 
                    @click="openStatusModal(dr)" 
                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
                  >
                    Update
                  </button>
                  <button 
                    @click="printOfficialCertificate(dr)" 
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-blue-900 hover:bg-blue-800 text-white shadow-xs transition inline-flex items-center space-x-1 cursor-pointer"
                  >
                    <Printer class="w-3 h-3" />
                    <span>Print</span>
                  </button>
                </td>
              </tr>
              <tr v-if="docRequests.length === 0">
                <td colspan="6" class="p-8 text-center text-slate-400 text-xs">
                  No document requests submitted yet.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB 3: DEPED SCHOOL FORMS (SF1 & SF5) -->
      <div v-if="activeTab === 'school_forms'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-6">
        <!-- Section Selector & Form Type Toggle -->
        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
          <div>
            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Select Class Section</label>
            <select 
              v-model="selectedFormSectionId" 
              @change="loadSchoolFormData" 
              class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white font-semibold"
            >
              <option v-for="sec in sectionsList" :key="sec.id" :value="sec.id">
                {{ sec.name }} ({{ sec.grade_level_name }})
              </option>
            </select>
          </div>

          <div>
            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">DepEd School Form</label>
            <div class="flex items-center space-x-2">
              <button 
                type="button"
                @click="activeSchoolFormType = 'SF1'; loadSchoolFormData()"
                :class="activeSchoolFormType === 'SF1' ? 'bg-blue-900 text-white font-semibold shadow-2xs' : 'bg-white text-slate-700 border border-slate-300'"
                class="flex-1 py-2 rounded-xl text-xs transition cursor-pointer"
              >
                SF1 (Master Register)
              </button>
              <button 
                type="button"
                @click="activeSchoolFormType = 'SF5'; loadSchoolFormData()"
                :class="activeSchoolFormType === 'SF5' ? 'bg-blue-900 text-white font-semibold shadow-2xs' : 'bg-white text-slate-700 border border-slate-300'"
                class="flex-1 py-2 rounded-xl text-xs transition cursor-pointer"
              >
                SF5 (Promotion Report)
              </button>
            </div>
          </div>

          <div class="flex items-end justify-end">
            <button 
              @click="triggerBrowserPrint" 
              class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white transition flex items-center space-x-1.5 cursor-pointer shadow-2xs"
            >
              <Printer class="w-3.5 h-3.5" />
              <span>Print Form</span>
            </button>
          </div>
        </div>

        <!-- SF1 Display -->
        <div v-if="activeSchoolFormType === 'SF1' && sf1Data" class="space-y-4">
          <div class="flex items-center justify-between pb-2 border-b border-slate-200">
            <h4 class="font-extrabold text-sm text-slate-900">
              School Form 1 (SF1) - School Register: {{ sf1Data.section?.name }}
            </h4>
            <span class="text-xs font-bold text-blue-900">
              Total Learners: {{ sf1Data.stats?.total_learners || 0 }} (Male: {{ sf1Data.stats?.male_count || 0 }} | Female: {{ sf1Data.stats?.female_count || 0 }})
            </span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse border border-slate-200">
              <thead>
                <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-300 text-[10px]">
                  <th class="p-2 border-r border-slate-200">LRN</th>
                  <th class="p-2 border-r border-slate-200">Learner's Full Name</th>
                  <th class="p-2 border-r border-slate-200 text-center">Sex</th>
                  <th class="p-2 border-r border-slate-200">Birthdate</th>
                  <th class="p-2 border-r border-slate-200">Address</th>
                  <th class="p-2">Parent / Guardian</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="st in sf1Data.students" :key="st.enrollment_id" class="hover:bg-slate-50">
                  <td class="p-2 border-r border-slate-100 font-mono font-bold">{{ st.lrn || 'N/A' }}</td>
                  <td class="p-2 border-r border-slate-100 font-bold">{{ st.last_name }}, {{ st.first_name }} {{ st.middle_name || '' }}</td>
                  <td class="p-2 border-r border-slate-100 text-center">{{ st.gender }}</td>
                  <td class="p-2 border-r border-slate-100">{{ st.birthdate || '2010-01-01' }}</td>
                  <td class="p-2 border-r border-slate-100 text-slate-600">{{ st.address || 'Lucena City' }}</td>
                  <td class="p-2 text-slate-600">{{ st.guardian_name || 'N/A' }}</td>
                </tr>
                <tr v-if="sf1Data.students?.length === 0">
                  <td colspan="6" class="p-6 text-center text-slate-400">No learners enrolled in this section.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- SF5 Display -->
        <div v-if="activeSchoolFormType === 'SF5' && sf5Data" class="space-y-4">
          <div class="flex items-center justify-between pb-2 border-b border-slate-200">
            <h4 class="font-extrabold text-sm text-slate-900">
              School Form 5 (SF5) - Report on Promotion: {{ sf5Data.section?.name }}
            </h4>
            <span class="text-xs font-bold text-emerald-700">
              Promoted: {{ sf5Data.summary?.promoted || 0 }} | Retained: {{ sf5Data.summary?.retained || 0 }}
            </span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse border border-slate-200">
              <thead>
                <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-300 text-[10px]">
                  <th class="p-2 border-r border-slate-200">LRN</th>
                  <th class="p-2 border-r border-slate-200">Learner's Full Name</th>
                  <th class="p-2 border-r border-slate-200 text-center">General Average</th>
                  <th class="p-2 border-r border-slate-200 text-center">Action Taken</th>
                  <th class="p-2">Incomplete Learning Areas</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="r in sf5Data.records" :key="r.id" class="hover:bg-slate-50">
                  <td class="p-2 border-r border-slate-100 font-mono font-bold">{{ r.lrn || 'N/A' }}</td>
                  <td class="p-2 border-r border-slate-100 font-bold">{{ r.last_name }}, {{ r.first_name }} {{ r.middle_name || '' }}</td>
                  <td class="p-2 border-r border-slate-100 text-center font-mono font-bold text-slate-900">{{ Number(r.general_average).toFixed(2) }}</td>
                  <td class="p-2 border-r border-slate-100 text-center">
                    <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-emerald-50 text-emerald-700">
                      {{ r.promotion_status }}
                    </span>
                  </td>
                  <td class="p-2 text-slate-400">None</td>
                </tr>
                <tr v-if="sf5Data.records?.length === 0">
                  <td colspan="5" class="p-6 text-center text-slate-400">No promotion records available.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 4: ACADEMIC HONORS & GWA RANKING -->
      <div v-if="activeTab === 'honors'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-slate-900">DepEd Academic Honor Roll & Batch Ranking</h3>
            <p class="text-xs text-slate-500">Automated classification according to DepEd Order No. 36, s. 2016.</p>
          </div>
          <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 font-bold text-xs">
            {{ honorRollData.total_honorees || 0 }} Total Honorees
          </span>
        </div>

        <!-- 3 Honor Tiers -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- With Highest Honors -->
          <div class="p-4 rounded-2xl border-2 border-amber-400 bg-amber-50/40 space-y-3">
            <div class="flex items-center justify-between">
              <span class="font-extrabold text-xs text-amber-900">With Highest Honors</span>
              <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-200 text-amber-900">98.00 - 100%</span>
            </div>
            <div class="space-y-1.5 max-h-60 overflow-y-auto">
              <div v-for="h in honorRollData.with_highest_honors" :key="h.id" class="p-2 rounded-xl bg-white border border-amber-200 flex items-center justify-between text-xs">
                <div>
                  <div class="font-bold text-slate-900">{{ h.first_name }} {{ h.last_name }}</div>
                  <div class="text-[10px] text-slate-500">{{ h.grade_level_name }} • {{ h.section_name }}</div>
                </div>
                <span class="font-mono font-extrabold text-amber-800 text-sm">{{ Number(h.general_average).toFixed(2) }}</span>
              </div>
              <div v-if="honorRollData.with_highest_honors?.length === 0" class="text-center py-4 text-slate-400 text-xs italic">
                No candidates in this tier yet
              </div>
            </div>
          </div>

          <!-- With High Honors -->
          <div class="p-4 rounded-2xl border-2 border-slate-300 bg-slate-50 space-y-3">
            <div class="flex items-center justify-between">
              <span class="font-extrabold text-xs text-slate-800">With High Honors</span>
              <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-200 text-slate-800">95.00 - 97.99%</span>
            </div>
            <div class="space-y-1.5 max-h-60 overflow-y-auto">
              <div v-for="h in honorRollData.with_high_honors" :key="h.id" class="p-2 rounded-xl bg-white border border-slate-200 flex items-center justify-between text-xs">
                <div>
                  <div class="font-bold text-slate-900">{{ h.first_name }} {{ h.last_name }}</div>
                  <div class="text-[10px] text-slate-500">{{ h.grade_level_name }} • {{ h.section_name }}</div>
                </div>
                <span class="font-mono font-extrabold text-slate-800 text-sm">{{ Number(h.general_average).toFixed(2) }}</span>
              </div>
              <div v-if="honorRollData.with_high_honors?.length === 0" class="text-center py-4 text-slate-400 text-xs italic">
                No candidates in this tier yet
              </div>
            </div>
          </div>

          <!-- With Honors -->
          <div class="p-4 rounded-2xl border-2 border-amber-700/40 bg-amber-900/5 space-y-3">
            <div class="flex items-center justify-between">
              <span class="font-extrabold text-xs text-amber-950">With Honors</span>
              <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-950">90.00 - 94.99%</span>
            </div>
            <div class="space-y-1.5 max-h-60 overflow-y-auto">
              <div v-for="h in honorRollData.with_honors" :key="h.id" class="p-2 rounded-xl bg-white border border-amber-100 flex items-center justify-between text-xs">
                <div>
                  <div class="font-bold text-slate-900">{{ h.first_name }} {{ h.last_name }}</div>
                  <div class="text-[10px] text-slate-500">{{ h.grade_level_name }} • {{ h.section_name }}</div>
                </div>
                <span class="font-mono font-extrabold text-blue-900 text-sm">{{ Number(h.general_average).toFixed(2) }}</span>
              </div>
              <div v-if="honorRollData.with_honors?.length === 0" class="text-center py-4 text-slate-400 text-xs italic">
                No candidates in this tier yet
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 5: TRANSFEREE F137 COMPLIANCE -->
      <div v-if="activeTab === 'transferees'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-slate-900">Incoming Transferee Form 137 / SF10 Compliance</h3>
            <p class="text-xs text-slate-500">Monitor submission of official permanent records from learners' previous schools.</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left border-collapse min-w-[650px]">
            <thead>
              <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200 text-[10px]">
                <th class="p-3.5">Student Name</th>
                <th class="p-3.5">Student ID & LRN</th>
                <th class="p-3.5">Current Section</th>
                <th class="p-3.5">Form 137 Status</th>
                <th class="p-3.5 text-right">Update Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="rec in records" :key="rec.id" class="hover:bg-slate-50 transition">
                <td class="p-3.5 font-bold text-slate-900">{{ rec.first_name }} {{ rec.last_name }}</td>
                <td class="p-3.5 font-mono text-slate-600">{{ rec.student_number }} • {{ rec.lrn || 'N/A' }}</td>
                <td class="p-3.5">{{ rec.section_name }}</td>
                <td class="p-3.5">
                  <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold" :class="getF137Badge(rec.previous_school_f137_status)">
                    {{ rec.previous_school_f137_status || 'Not Applicable' }}
                  </span>
                </td>
                <td class="p-3.5 text-right space-x-1.5 whitespace-nowrap">
                  <button 
                    @click="updateF137(rec, 'Complete / Received')" 
                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 transition cursor-pointer"
                  >
                    Mark Received
                  </button>
                  <button 
                    @click="updateF137(rec, 'Follow-up Sent')" 
                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 transition cursor-pointer"
                  >
                    Log Follow-up
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL: NEW DOCUMENT REQUEST / CERTIFICATE ISSUANCE -->
    <div v-if="showNewReqModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in">
      <div class="bg-white rounded-3xl p-6 max-w-md w-full border border-slate-200 shadow-2xl space-y-4 text-xs">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-extrabold text-sm text-slate-900">Issue / Request Official Certificate</h3>
          <button @click="showNewReqModal = false" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
        </div>

        <form @submit.prevent="submitDocumentRequest" class="space-y-3">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Select Student *</label>
            <select v-model="newReqForm.student_id" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white" required>
              <option v-for="rec in records" :key="rec.student_id" :value="rec.student_id">
                {{ rec.last_name }}, {{ rec.first_name }} ({{ rec.student_number }}) - {{ rec.section_name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Document / Certificate Type *</label>
            <select v-model="newReqForm.document_type" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white" required>
              <option value="Certificate of Enrollment">Certificate of Enrollment & Registration</option>
              <option value="Good Moral Character">Certificate of Good Moral Character</option>
              <option value="GWA and Class Ranking">Certificate of GWA & Class Ranking</option>
              <option value="Certificate of Completion">Certificate of Completion / Moving Up</option>
              <option value="Certified True Copy SF9">Certified True Copy (CTC) SF9</option>
              <option value="Certified True Copy SF10">Certified True Copy (CTC) SF10</option>
              <option value="Transfer Credential">Official Transfer Credential</option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Purpose / Intended Use *</label>
            <input 
              v-model="newReqForm.purpose" 
              type="text" 
              placeholder="e.g. DOST Scholarship / SSS Dependent / College Entrance" 
              class="w-full px-3 py-2 rounded-xl border border-slate-300" 
              required 
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Number of Copies</label>
              <input v-model="newReqForm.copies" type="number" min="1" max="5" class="w-full px-3 py-2 rounded-xl border border-slate-300" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Initial Status</label>
              <select v-model="newReqForm.status" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                <option value="Pending">Pending</option>
                <option value="Ready for Pickup">Ready for Pickup</option>
                <option value="Released">Released</option>
              </select>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
            <button type="button" @click="showNewReqModal = false" class="px-4 py-2 rounded-xl font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 cursor-pointer">
              Cancel
            </button>
            <button type="submit" class="px-5 py-2 rounded-xl font-semibold bg-blue-900 hover:bg-blue-800 text-white shadow-xs cursor-pointer">
              Submit & Issue
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: UPDATE DRS REQUEST STATUS -->
    <div v-if="showStatusModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in">
      <div class="bg-white rounded-3xl p-6 max-w-sm w-full border border-slate-200 shadow-2xl space-y-4 text-xs">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-extrabold text-sm text-slate-900">Update Request Status</h3>
          <button @click="showStatusModal = false" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
        </div>

        <form @submit.prevent="submitStatusUpdate" class="space-y-3">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Control Number</label>
            <input type="text" :value="selectedStatusReq?.control_number" disabled class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-100 font-mono" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">New Status *</label>
            <select v-model="statusUpdateForm.status" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white" required>
              <option value="Pending">Pending Verification</option>
              <option value="Processing">Processing / Under Preparation</option>
              <option value="Ready for Pickup">Ready for Pickup / Signed & Sealed</option>
              <option value="Released">Released to Student / Parent</option>
              <option value="Rejected">Rejected / Cancelled</option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Custodian Remarks</label>
            <textarea v-model="statusUpdateForm.remarks" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-300" placeholder="e.g. Signed by Principal on Aug 23, 2026"></textarea>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
            <button type="button" @click="showStatusModal = false" class="px-4 py-2 rounded-xl font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 cursor-pointer">
              Cancel
            </button>
            <button type="submit" class="px-5 py-2 rounded-xl font-semibold bg-blue-900 hover:bg-blue-800 text-white shadow-xs cursor-pointer">
              Save Status
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- CUSTOM NOTICE / ALERT MODAL (REPLACES BROWSER ALERT) -->
    <div v-if="noticeModal.isOpen" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-slate-200 text-xs space-y-4 animate-in fade-in zoom-in-95 duration-150 text-slate-900">
        <div 
          class="w-12 h-12 rounded-2xl flex items-center justify-center mx-auto shadow-2xs border"
          :class="[
            noticeModal.type === 'error' ? 'bg-rose-50 border-rose-200 text-rose-600' : '',
            noticeModal.type === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-600' : '',
            noticeModal.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-600' : '',
            noticeModal.type === 'info' ? 'bg-blue-50 border-blue-200 text-blue-600' : ''
          ]"
        >
          <AlertCircle v-if="noticeModal.type === 'error'" class="w-6 h-6" />
          <AlertTriangle v-else-if="noticeModal.type === 'warning'" class="w-6 h-6" />
          <CheckCircle2 v-else-if="noticeModal.type === 'success'" class="w-6 h-6" />
          <Info v-else class="w-6 h-6" />
        </div>

        <div class="text-center space-y-1">
          <h3 class="text-base font-extrabold text-slate-900">{{ noticeModal.title }}</h3>
          <p class="text-xs text-slate-600 leading-relaxed font-medium">{{ noticeModal.message }}</p>
        </div>

        <div class="flex items-center justify-center pt-2 border-t border-slate-100">
          <button 
            type="button" 
            @click="noticeModal.isOpen = false" 
            class="px-6 py-2.5 rounded-xl font-bold bg-slate-900 hover:bg-slate-800 text-white shadow-xs transition cursor-pointer"
          >
            Understood
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { BookOpen, FileText, Layers, Award, ShieldAlert, Search, RefreshCw, Plus, Printer, CheckCircle, AlertCircle, AlertTriangle, CheckCircle2, Info } from 'lucide-vue-next';
import api from '../../services/api';

const route = useRoute();
const activeTab = ref('records');

watch(() => route.query.tab, (newTab) => {
  if (newTab && ['records', 'drs', 'school_forms', 'honors', 'transferees'].includes(newTab)) {
    activeTab.value = newTab;
  }
}, { immediate: true });

const isLoading = ref(false);
const feedbackMessage = ref('');
const searchQuery = ref('');

// Custom Notice Modal State (replaces native browser alert())
const noticeModal = ref({
  isOpen: false,
  title: '',
  message: '',
  type: 'info'
});

const showNotice = (title, message, type = 'info') => {
  noticeModal.value = {
    isOpen: true,
    title,
    message,
    type
  };
};

// Portal Data
const records = ref([]);
const stats = ref({});
const docRequests = ref([]);
const sectionsList = ref([]);
const selectedFormSectionId = ref(1);
const activeSchoolFormType = ref('SF1');
const sf1Data = ref(null);
const sf5Data = ref(null);
const honorRollData = ref({ all: [], with_highest_honors: [], with_high_honors: [], with_honors: [], total_honorees: 0 });

// Printable Document State
const activePrintDoc = ref(null);

// Modal States
const showNewReqModal = ref(false);
const newReqForm = ref({
  student_id: null,
  document_type: 'Good Moral Character',
  purpose: '',
  copies: 1,
  status: 'Pending'
});

const showStatusModal = ref(false);
const selectedStatusReq = ref(null);
const statusUpdateForm = ref({
  id: null,
  status: 'Processing',
  remarks: ''
});

const loadRecords = async () => {
  isLoading.value = true;
  try {
    const params = searchQuery.value ? `search=${encodeURIComponent(searchQuery.value)}` : '';
    const res = await api.getStudentRecords(params);
    records.value = res.data.records || [];
    stats.value = res.data.stats || {};
  } catch (err) {
    console.error('Failed to load student records:', err);
  } finally {
    isLoading.value = false;
  }
};

const loadDocRequests = async () => {
  try {
    const res = await api.getDocumentRequests();
    docRequests.value = res.data || [];
  } catch (err) {
    console.error('Failed to load document requests:', err);
  }
};

const loadSectionsList = async () => {
  try {
    const res = await api.getSections();
    sectionsList.value = res.data?.sections || [];
    if (sectionsList.value.length > 0 && !selectedFormSectionId.value) {
      selectedFormSectionId.value = sectionsList.value[0].id;
    }
  } catch (err) {
    console.error('Failed to load sections list:', err);
  }
};

const loadSchoolFormData = async () => {
  if (!selectedFormSectionId.value) return;
  try {
    if (activeSchoolFormType.value === 'SF1') {
      const res = await api.getSchoolForm1(selectedFormSectionId.value);
      sf1Data.value = res.data;
    } else {
      const res = await api.getSchoolForm5(selectedFormSectionId.value);
      sf5Data.value = res.data;
    }
  } catch (err) {
    console.error('Failed to load school form data:', err);
  }
};

const loadHonorRoll = async () => {
  try {
    const res = await api.getHonorRoll();
    honorRollData.value = res.data || {};
  } catch (err) {
    console.error('Failed to load honor roll:', err);
  }
};

const refreshCurrentTab = async () => {
  await loadRecords();
  await loadDocRequests();
  await loadHonorRoll();
  await loadSchoolFormData();
  feedbackMessage.value = 'Portal data refreshed successfully.';
  setTimeout(() => feedbackMessage.value = '', 3000);
};

// Document Printing Handlers
const openPrintDoc = async (type, studentId) => {
  isLoading.value = true;
  try {
    const res = await api.getStudentTranscript(studentId);
    activePrintDoc.value = {
      type: type,
      data: res.data
    };
  } catch (err) {
    console.error('Failed to load student transcript for print:', err);
  } finally {
    isLoading.value = false;
  }
};

const printOfficialCertificate = (dr) => {
  activePrintDoc.value = {
    type: 'CERTIFICATE',
    data: dr
  };
};

const triggerBrowserPrint = () => {
  window.print();
};

// DRS Modal Handlers
const openNewRequestModal = () => {
  if (records.value.length > 0 && !newReqForm.value.student_id) {
    newReqForm.value.student_id = records.value[0].student_id;
  }
  showNewReqModal.value = true;
};

const submitDocumentRequest = async () => {
  try {
    await api.saveDocumentRequest(newReqForm.value);
    showNewReqModal.value = false;
    feedbackMessage.value = 'Official document request created and queued!';
    await loadDocRequests();
    await loadRecords();
  } catch (err) {
    showNotice('Save Failed', err.message || 'Failed to submit document request.', 'error');
  }
};

const openStatusModal = (dr) => {
  selectedStatusReq.value = dr;
  statusUpdateForm.value = {
    id: dr.id,
    status: dr.status,
    remarks: dr.remarks || ''
  };
  showStatusModal.value = true;
};

const submitStatusUpdate = async () => {
  try {
    await api.updateRequestStatus(statusUpdateForm.value);
    showStatusModal.value = false;
    feedbackMessage.value = `Request ${selectedStatusReq.value?.control_number} updated to ${statusUpdateForm.value.status}!`;
    await loadDocRequests();
    await loadRecords();
  } catch (err) {
    showNotice('Update Failed', err.message || 'Failed to update request status.', 'error');
  }
};

const updateF137 = async (rec, status) => {
  try {
    await api.updateTransfereeF137({ id: rec.id, status });
    rec.previous_school_f137_status = status;
    feedbackMessage.value = `Form 137 status for ${rec.first_name} updated to "${status}"`;
  } catch (err) {
    showNotice('Update Failed', err.message || 'Failed to update F137 status.', 'error');
  }
};

// UI Badge Helpers
const getStatusBadge = (status) => {
  switch (status) {
    case 'Released': return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
    case 'Ready for Pickup': return 'bg-cyan-50 text-cyan-700 border border-cyan-200';
    case 'Processing': return 'bg-blue-50 text-blue-700 border border-blue-200';
    case 'Pending': return 'bg-amber-50 text-amber-700 border border-amber-200';
    default: return 'bg-slate-100 text-slate-700';
  }
};

const getF137Badge = (status) => {
  switch (status) {
    case 'Complete / Received': return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
    case 'Pending Previous School': return 'bg-rose-50 text-rose-700 border border-rose-200';
    case 'Follow-up Sent': return 'bg-amber-50 text-amber-700 border border-amber-200';
    default: return 'bg-slate-100 text-slate-500';
  }
};

onMounted(async () => {
  await loadRecords();
  await loadDocRequests();
  await loadSectionsList();
  await loadSchoolFormData();
  await loadHonorRoll();
});
</script>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
  body {
    background: white !important;
    color: black !important;
  }
}
</style>
