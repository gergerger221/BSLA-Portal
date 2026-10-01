<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 font-sans">
    
    <!-- Top Header & Actions Bar (Standard Staff Layout) -->
    <div class="no-print flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6 pb-5 border-b border-slate-200">
      <div>
        <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
          <GraduationCap class="w-3.5 h-3.5 text-amber-600" />
          <span>Faculty Instruction & Academic Evaluation</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
          {{ teacherProfile?.first_name ? `Prof. ${teacherProfile.first_name} ${teacherProfile.last_name}` : 'Teacher & Faculty Portal' }}
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">
          DepEd Electronic Class Record (E-Class Record), Weekly Bell Timetable, Class Masterlists, and Advisory SF9 Core Values.
        </p>
      </div>

      <div class="flex items-center space-x-2.5 flex-wrap gap-y-2 shrink-0">
        <!-- School Year & Load Summary Pill -->
        <div class="hidden sm:flex items-center space-x-2 bg-amber-50 text-amber-900 border border-amber-200 px-3.5 py-1.5 rounded-xl text-xs font-medium font-mono">
          <span>S.Y. {{ activeSchoolYear?.name || '2026-2027' }}</span>
          <span class="text-amber-300">•</span>
          <span>Classes:</span>
          <strong class="text-amber-950 font-bold">{{ teachingClasses.length }}</strong>
          <span class="text-amber-300">•</span>
          <span>Periods:</span>
          <strong class="text-amber-950 font-bold">{{ weeklySchedules.length }}</strong>
        </div>

        <button 
          @click="loadTeacherDashboard()" 
          class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-medium shadow-2xs transition flex items-center space-x-1.5 cursor-pointer"
          title="Refresh teacher portal data"
        >
          <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isLoading }" />
          <span>Refresh</span>
        </button>
      </div>
    </div>

    <!-- Quick Metrics Summary (Aligned with DepEd RA 4670 & 1-to-1 Homeroom Standard) -->
    <div class="no-print grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      
      <!-- Card 1: Assigned Teaching Loads -->
      <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-2xs">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Assigned Classes</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200 font-mono">JHS & SHS</span>
        </div>
        <strong class="text-2xl font-bold text-slate-900 font-mono mt-1 block">{{ dashboardStats.total_classes || 0 }}</strong>
        <span class="text-[10px] text-slate-400">Distinct subject-section teaching loads</span>
      </div>

      <!-- Card 2: DepEd Workload Meter (RA 4670 & DO 005, s. 2024) -->
      <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-2xs space-y-1">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Weekly Workload</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">RA 4670 Compliant</span>
        </div>
        <div class="flex items-baseline space-x-1.5 mt-1">
          <strong class="text-2xl font-bold text-amber-600 font-mono">
            {{ activeTimetableSemester === '1st Semester' ? (dashboardStats.workload_1st_sem_hours || 25) : (dashboardStats.workload_2nd_sem_hours || 25) }}
          </strong>
          <span class="text-xs text-slate-400 font-mono">/ {{ dashboardStats.max_deped_hours || 30 }} hrs/wk</span>
        </div>
        <!-- Progress Bar (Max 30 hrs/week) -->
        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
          <div 
            class="bg-amber-500 h-1.5 rounded-full transition-all duration-300"
            :style="{ width: `${Math.min(100, Math.round(((activeTimetableSemester === '1st Semester' ? (dashboardStats.workload_1st_sem_hours || 25) : (dashboardStats.workload_2nd_sem_hours || 25)) / (dashboardStats.max_deped_hours || 30)) * 100))}%` }"
          ></div>
        </div>
        <span class="text-[10px] text-slate-400 block pt-0.5">{{ activeTimetableSemester }} Load • Max 6h/day</span>
      </div>

      <!-- Card 3: Enrolled Learners -->
      <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-2xs">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Enrolled Learners</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono">Active</span>
        </div>
        <strong class="text-2xl font-bold text-emerald-600 font-mono mt-1 block">{{ dashboardStats.total_students || 0 }}</strong>
        <span class="text-[10px] text-slate-400">Total learners across sections</span>
      </div>

      <!-- Card 4: Official Advisory Homeroom (1-to-1 DepEd Standard) -->
      <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-2xs">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Advisory Homeroom</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-800 border border-purple-200">1-to-1 DepEd</span>
        </div>
        <strong class="text-base font-bold text-purple-900 mt-1 block truncate">
          {{ dashboardStats.advisory_section ? dashboardStats.advisory_section.section_name : 'No Advisory Assigned' }}
        </strong>
        <span class="text-[10px] text-slate-500">
          {{ dashboardStats.advisory_section ? (dashboardStats.advisory_section.room || 'Room 401') : 'Pure Subject Teacher' }}
        </span>
      </div>
    </div>

    <!-- Feedback Alerts -->
    <div v-if="feedbackMessage" class="no-print mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center justify-between shadow-xs">
      <div class="flex items-center space-x-2">
        <CheckCircle class="w-4 h-4 text-emerald-600 shrink-0" />
        <span>{{ feedbackMessage }}</span>
      </div>
      <button @click="feedbackMessage = ''" class="text-emerald-500 hover:text-emerald-700 font-bold cursor-pointer">✕</button>
    </div>

    <div v-if="errorMessage" class="no-print mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center justify-between shadow-xs">
      <div class="flex items-center space-x-2">
        <AlertCircle class="w-4 h-4 text-rose-600 shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>
      <button @click="errorMessage = ''" class="text-rose-500 hover:text-rose-700 font-bold cursor-pointer">✕</button>
    </div>

    <!-- ======================================================== -->
    <!-- TAB 1: WEEKLY MASTER TIMETABLE & TEACHING LOADS          -->
    <!-- ======================================================== -->
    <div v-if="activeTab === 'schedule'" class="space-y-6">
      
      <!-- Weekly Bell Timetable (Monday - Friday) -->
      <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
          <div>
            <h2 class="text-base font-bold text-slate-900">Weekly Master Bell Timetable</h2>
            <p class="text-xs text-slate-500">Conflict-free timetable matrix for your instructional periods across all assigned sections.</p>
          </div>
          
          <div class="flex items-center space-x-3 flex-wrap gap-y-2">
            <!-- Semester Switcher Pill Bar (Resolves Cross-Semester Double Booking) -->
            <div class="flex items-center space-x-1 bg-slate-100 p-1 rounded-xl border border-slate-200">
              <button 
                @click="activeTimetableSemester = '1st Semester'" 
                type="button" 
                :class="activeTimetableSemester === '1st Semester' ? 'bg-blue-900 text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer"
              >
                1st Semester
              </button>
              <button 
                @click="activeTimetableSemester = '2nd Semester'" 
                type="button" 
                :class="activeTimetableSemester === '2nd Semester' ? 'bg-blue-900 text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer"
              >
                2nd Semester
              </button>
            </div>

            <div class="text-xs font-semibold text-slate-600 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-xl font-mono">
              {{ currentSemesterSchedules.length }} Assigned Periods / wk
            </div>
          </div>
        </div>

        <!-- Schedule Days Columns -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
          <div v-for="day in ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']" :key="day" class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
            <div class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-200 pb-2 flex items-center justify-between">
              <span>{{ day }}</span>
              <span class="text-[10px] font-mono font-normal text-slate-500">{{ getDaySchedules(day).length }} classes</span>
            </div>

            <div v-if="getDaySchedules(day).length === 0" class="py-8 text-center text-slate-400 text-[11px]">
              No classes scheduled
            </div>

            <div 
              v-for="s in getDaySchedules(day)" 
              :key="s.id"
              class="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs hover:border-blue-500 hover:shadow-xs transition space-y-2 group cursor-pointer"
              @click="openClassRecord(s.section_id, s.subject_id)"
            >
              <div class="flex items-center justify-between text-[10px] font-mono text-slate-500">
                <span class="font-bold text-blue-900">{{ formatTime(s.time_start) }} - {{ formatTime(s.time_end) }}</span>
                <span class="px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-sans">{{ s.room || 'Room' }}</span>
              </div>
              <div class="font-bold text-xs text-slate-900 group-hover:text-blue-900 transition leading-snug">
                {{ s.subject_name }}
              </div>
              <div class="flex items-center justify-between text-[11px] text-slate-600 pt-1 border-t border-slate-50">
                <span class="font-medium text-slate-700 truncate mr-2">{{ s.section_name }}</span>
                <div class="flex items-center space-x-1 shrink-0">
                  <span class="text-[9px] px-1.5 py-0.2 rounded font-bold uppercase bg-slate-100 text-slate-700 font-mono">
                    {{ s.subject_classification || 'Core' }}
                  </span>
                  <span class="text-[10px] px-1.5 py-0.5 rounded font-bold uppercase bg-blue-50 text-blue-800 font-mono">
                    {{ s.grade_level_code }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Assigned Teaching Loads Directory Cards -->
      <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
          <div>
            <h2 class="text-base font-bold text-slate-900">Teaching Loads Directory</h2>
            <p class="text-xs text-slate-500">All registered subject-section combinations assigned to your instructional load.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div 
            v-for="c in teachingClasses" 
            :key="`${c.section_id}-${c.subject_id}`"
            class="p-5 rounded-2xl border border-slate-200 bg-white hover:border-blue-900 hover:shadow-xs transition space-y-4 flex flex-col justify-between"
          >
            <div class="space-y-2.5">
              <div class="flex items-center justify-between">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-blue-900 border border-blue-200">
                  {{ c.grade_level_code }} • {{ c.section_name }}
                </span>
                <span class="text-[11px] font-mono font-medium text-slate-500">{{ c.units }} Units</span>
              </div>
              <h3 class="font-bold text-sm text-slate-900 leading-snug">{{ c.subject_name }}</h3>
              <div class="text-xs text-slate-500 flex items-center space-x-2">
                <span class="font-mono text-slate-600">{{ c.subject_code }}</span>
                <span>•</span>
                <span>{{ c.section_room || 'Room' }}</span>
                <span>•</span>
                <span>{{ c.subject_category || 'Core' }}</span>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
              <div class="text-xs text-slate-600">
                <span class="font-bold text-slate-900">{{ c.enrolled_count || 0 }}</span> Enrolled Learners
              </div>
              <button 
                @click="openClassRecord(c.section_id, c.subject_id)"
                type="button" 
                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-blue-900 hover:bg-blue-800 text-white shadow-2xs transition flex items-center space-x-1.5 cursor-pointer"
              >
                <FileSpreadsheet class="w-3.5 h-3.5" />
                <span>Open E-Class Record</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- TAB: CLASSROOM & LMS MODULES (PHASE 1)                   -->
    <!-- ======================================================== -->
    <div v-if="activeTab === 'lms'" class="space-y-6">
      
      <!-- LMS Top Bar & Class Selector -->
      <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-100 pb-5">
          <div>
            <div class="flex items-center space-x-2">
              <h2 class="text-base font-bold text-slate-900">Virtual Classroom & Learning Management</h2>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono">
                LMS Hub
              </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">
              Publish class stream announcements, distribute quarterly handouts and slides, and review student assignment submissions.
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2.5">
            <!-- Class Selector Dropdown -->
            <select 
              v-model="selectedClassKey" 
              @change="handleClassChange()"
              class="px-3.5 py-2 rounded-xl border border-slate-300 bg-white text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none cursor-pointer shadow-2xs"
            >
              <option value="">-- Select Class Section & Subject --</option>
              <optgroup label="Junior High School" v-if="jhsClasses.length > 0">
                <option 
                  v-for="c in jhsClasses" 
                  :key="`lms-${c.section_id}-${c.subject_id}`" 
                  :value="`${c.section_id}-${c.subject_id}`"
                >
                  {{ c.grade_level_code }} - {{ c.section_name }} : {{ c.subject_name }}
                </option>
              </optgroup>
              <optgroup 
                :label="`Senior High School - 1st Semester${activeSchoolYear?.active_semester === '1st Semester' ? ' (Active Term)' : ' (Archived - Read Only)'}`" 
                v-if="shs1stSemClasses.length > 0"
              >
                <option 
                  v-for="c in shs1stSemClasses" 
                  :key="`lms-${c.section_id}-${c.subject_id}`" 
                  :value="`${c.section_id}-${c.subject_id}`"
                >
                  {{ c.grade_level_code }} - {{ c.section_name }} : {{ c.subject_name }} {{ activeSchoolYear?.active_semester === '1st Semester' ? '' : '🔒 (Archived)' }}
                </option>
              </optgroup>
              <optgroup 
                :label="`Senior High School - 2nd Semester${activeSchoolYear?.active_semester === '2nd Semester' ? ' (Active Term)' : ' (Archived - Read Only)'}`" 
                v-if="shs2ndSemClasses.length > 0"
              >
                <option 
                  v-for="c in shs2ndSemClasses" 
                  :key="`lms-${c.section_id}-${c.subject_id}`" 
                  :value="`${c.section_id}-${c.subject_id}`"
                >
                  {{ c.grade_level_code }} - {{ c.section_name }} : {{ c.subject_name }} {{ activeSchoolYear?.active_semester === '2nd Semester' ? '' : '🔒 (Archived)' }}
                </option>
              </optgroup>
            </select>

            <button 
              @click="loadLmsContent()" 
              type="button" 
              class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition flex items-center space-x-1.5 cursor-pointer"
            >
              <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isLoadingLms }" />
              <span>Refresh LMS</span>
            </button>
          </div>
        </div>

        <!-- Active Class Banner -->
        <div v-if="lmsClassData.class_info" class="p-4 rounded-2xl bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
          <div class="space-y-0.5">
            <div class="font-extrabold text-emerald-950 text-sm flex items-center space-x-2">
              <span>{{ lmsClassData.class_info.section_name }}</span>
              <span class="text-emerald-400">•</span>
              <span>{{ lmsClassData.class_info.subject_name }} ({{ lmsClassData.class_info.subject_code }})</span>
            </div>
            <div class="text-slate-600 text-[11px]">
              Grade: <strong class="text-slate-900">{{ lmsClassData.class_info.grade_level_name }}</strong> | 
              Room: <strong class="text-slate-900">{{ lmsClassData.class_info.section_room || 'Designated Room' }}</strong> | 
              Units: <strong class="text-slate-900">{{ lmsClassData.class_info.units || 1 }}</strong>
            </div>
          </div>

          <!-- LMS Sub-Tabs Pill Bar -->
          <div class="flex items-center space-x-1 bg-white/80 backdrop-blur-xs p-1 rounded-xl border border-emerald-200">
            <button 
              @click="lmsSubTab = 'stream'" 
              type="button" 
              :class="lmsSubTab === 'stream' ? 'bg-emerald-800 text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
              class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center space-x-1.5"
            >
              <MessageSquare class="w-3.5 h-3.5" />
              <span>Stream ({{ lmsClassData.announcements.length }})</span>
            </button>
            <button 
              @click="lmsSubTab = 'modules'" 
              type="button" 
              :class="lmsSubTab === 'modules' ? 'bg-emerald-800 text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
              class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center space-x-1.5"
            >
              <BookOpen class="w-3.5 h-3.5" />
              <span>Modules ({{ lmsClassData.modules.length }})</span>
            </button>
            <button 
              @click="lmsSubTab = 'assignments'" 
              type="button" 
              :class="lmsSubTab === 'assignments' ? 'bg-emerald-800 text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
              class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center space-x-1.5"
            >
              <FileText class="w-3.5 h-3.5" />
              <span>Assignments ({{ lmsClassData.assignments.length }})</span>
            </button>
          </div>
        </div>

        <div v-else class="py-12 text-center text-slate-400 text-xs">
          Please select a class section above to view the virtual classroom stream and learning modules.
        </div>
      </div>

      <!-- Archived Academic Term Notice (Read-Only Mode) -->
      <div v-if="lmsClassData.class_info && isCurrentClassArchived" class="p-4 rounded-2xl bg-amber-50 border border-amber-200 flex items-start space-x-3 text-amber-900 shadow-2xs">
        <Lock class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
        <div class="space-y-1 text-xs">
          <div class="font-bold text-sm text-amber-950 flex items-center space-x-2">
            <span>Read-Only Archived Class ({{ currentSelectedClass?.semester || 'Inactive Semester' }})</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-amber-200 text-amber-900 border border-amber-300">
              🔒 Archive Mode
            </span>
          </div>
          <p class="text-amber-800 leading-relaxed">
            This class belongs to an inactive Senior High School semester. New announcements, learning module uploads, and assignment task creations are disabled to preserve historical DepEd records. You can still review past handouts, homework instructions, and student submissions.
          </p>
        </div>
      </div>

      <!-- LMS SUB-TAB 1: CLASS STREAM & ANNOUNCEMENTS -->
      <div v-if="lmsClassData.class_info && lmsSubTab === 'stream'" class="space-y-6">
        <!-- Post Announcement Card -->
        <div v-if="!isCurrentClassArchived" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center space-x-1.5">
            <Send class="w-3.5 h-3.5 text-emerald-600" />
            <span>Post Class Announcement</span>
          </h3>

          <div class="space-y-3">
            <input 
              v-model="announcementForm.title" 
              type="text" 
              placeholder="Announcement Title (e.g. Coverage for Midterm Examination / Homework Reminder)" 
              class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
            <textarea 
              v-model="announcementForm.content" 
              rows="3" 
              placeholder="Write your announcement or instructions to the learners..." 
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            ></textarea>

            <!-- Multi-Section Collective Distribution -->
            <div v-if="activeTeachingClasses.length > 1" class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl space-y-2.5">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center space-x-2">
                  <Layers class="w-4 h-4 text-emerald-700 shrink-0" />
                  <span class="text-xs font-bold text-slate-800">Distribute to Multiple Class Sections</span>
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    {{ announcementTargetKeys.length }} / {{ activeTeachingClasses.length }} Selected
                  </span>
                </div>

                <!-- Quick Select Shortcuts -->
                <div class="flex items-center space-x-1.5 flex-wrap gap-y-1">
                  <button 
                    v-if="sameSubjectTeachingClasses.length > 1" 
                    @click="selectAllSameSubject('announcement')" 
                    type="button" 
                    class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-emerald-100 text-emerald-800 hover:bg-emerald-200 transition cursor-pointer"
                  >
                    All Same Subject ({{ sameSubjectTeachingClasses.length }})
                  </button>
                  <button 
                    @click="selectAllClasses('announcement')" 
                    type="button" 
                    class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 transition cursor-pointer"
                  >
                    Select All Classes
                  </button>
                  <button 
                    v-if="announcementTargetKeys.length > 1" 
                    @click="resetToOnlyCurrent('announcement')" 
                    type="button" 
                    class="px-2.5 py-1 rounded-lg text-[11px] font-medium text-slate-500 hover:text-slate-800 transition cursor-pointer"
                  >
                    Only Current
                  </button>
                </div>
              </div>

              <!-- Checkbox list of classes -->
              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 pt-1 max-h-36 overflow-y-auto pr-1">
                <label 
                  v-for="c in activeTeachingClasses" 
                  :key="`ann-tgt-${c.section_id}-${c.subject_id}`" 
                  :class="[
                    'p-2 rounded-xl border text-xs flex items-center space-x-2 cursor-pointer transition select-none',
                    announcementTargetKeys.includes(`${c.section_id}-${c.subject_id}`)
                      ? 'border-emerald-500 bg-emerald-50/80 text-emerald-950 font-semibold shadow-2xs'
                      : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                  ]"
                >
                  <input 
                    type="checkbox" 
                    :value="`${c.section_id}-${c.subject_id}`" 
                    v-model="announcementTargetKeys" 
                    class="rounded text-emerald-600 focus:ring-emerald-500 shrink-0" 
                  />
                  <div class="min-w-0 flex-1 leading-tight">
                    <div class="truncate text-[11px] font-bold">{{ c.grade_level_code }} - {{ c.section_name }}</div>
                    <div class="truncate text-[10px] text-slate-500">{{ c.subject_name }}</div>
                  </div>
                </label>
              </div>
            </div>

            <div class="flex items-center justify-between pt-1">
              <label class="flex items-center space-x-2 text-xs text-slate-600 cursor-pointer select-none">
                <input v-model="announcementForm.is_pinned" type="checkbox" class="rounded text-emerald-600 focus:ring-emerald-500" />
                <span>Pin this announcement to top of class stream</span>
              </label>

              <button 
                @click="postLmsAnnouncement()" 
                type="button" 
                class="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-700 hover:bg-emerald-600 text-white shadow-xs transition flex items-center space-x-1.5 cursor-pointer"
              >
                <Send class="w-3.5 h-3.5" />
                <span>Post Announcement</span>
              </button>
            </div>
          </div>
        </div>

        <div v-else class="p-4 bg-slate-50 border border-slate-200 rounded-3xl flex items-center space-x-3 text-xs text-slate-500">
          <Lock class="w-4 h-4 text-slate-400 shrink-0" />
          <span>Announcements cannot be posted for this class because it belongs to an archived academic semester.</span>
        </div>

        <!-- Announcements Feed -->
        <div class="space-y-3.5">
          <div v-if="lmsClassData.announcements.length === 0" class="py-12 bg-white rounded-3xl border border-dashed border-slate-200 text-center text-slate-400 text-xs">
            <MessageSquare class="w-8 h-8 mx-auto mb-2 text-slate-300" />
            No announcements posted yet for this class.
          </div>

          <div 
            v-for="a in lmsClassData.announcements" 
            :key="a.id"
            :class="[
              'p-5 bg-white rounded-2xl border transition space-y-2.5',
              a.is_pinned ? 'border-amber-300 bg-amber-50/20 shadow-xs' : 'border-slate-200'
            ]"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-1">
                <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                  <span v-if="a.is_pinned" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300 flex items-center space-x-1">
                    <Pin class="w-2.5 h-2.5" />
                    <span>Pinned Notice</span>
                  </span>
                  <h4 class="font-bold text-slate-900 text-sm">{{ a.title }}</h4>
                </div>
                <div class="text-[11px] text-slate-400 flex items-center space-x-2">
                  <span>By {{ a.author_first_name ? `Prof. ${a.author_first_name} ${a.author_last_name}` : a.author_username }}</span>
                  <span>•</span>
                  <span>{{ new Date(a.created_at).toLocaleString() }}</span>
                </div>
              </div>

              <button 
                v-if="!isCurrentClassArchived"
                @click="openConfirmDeleteAnnouncement(a)" 
                type="button" 
                class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition cursor-pointer"
                title="Delete announcement"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>

            <p class="text-xs text-slate-700 whitespace-pre-line leading-relaxed border-t border-slate-100 pt-2.5">
              {{ a.content }}
            </p>
          </div>
        </div>
      </div>

      <!-- LMS SUB-TAB 2: LEARNING MODULES & HANDOUTS -->
      <div v-if="lmsClassData.class_info && lmsSubTab === 'modules'" class="space-y-6">
        <!-- Controls & Upload Trigger -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-center space-x-2 flex-wrap gap-y-2">
            <span class="text-xs font-semibold text-slate-500">Filter Quarter:</span>
            <select 
              v-model="selectedLmsQuarter" 
              class="px-3 py-1.5 rounded-xl border border-slate-300 bg-white text-xs font-semibold text-slate-700"
            >
              <option value="all">All Quarters</option>
              <option value="1st Quarter">1st Quarter</option>
              <option value="2nd Quarter">2nd Quarter</option>
              <option value="3rd Quarter">3rd Quarter</option>
              <option value="4th Quarter">4th Quarter</option>
            </select>
          </div>

          <button 
            v-if="!isCurrentClassArchived"
            @click="openUploadModuleModal()" 
            type="button" 
            class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-700 hover:bg-emerald-600 text-white shadow-xs transition flex items-center space-x-1.5 cursor-pointer shrink-0"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Upload New Handout / Module</span>
          </button>
          <div v-else class="px-3.5 py-2 rounded-xl bg-slate-100 border border-slate-200 text-slate-500 text-xs font-semibold flex items-center space-x-1.5 shrink-0">
            <Lock class="w-3.5 h-3.5 text-slate-400" />
            <span>Uploads Closed (Archived Term)</span>
          </div>
        </div>

        <!-- Modules Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div 
            v-if="filteredLmsModules.length === 0" 
            class="col-span-full py-12 bg-white rounded-3xl border border-dashed border-slate-200 text-center text-slate-400 text-xs"
          >
            <BookOpen class="w-8 h-8 mx-auto mb-2 text-slate-300" />
            No learning modules uploaded yet for this period. Click "Upload New Handout" to add slides or worksheets.
          </div>

          <div 
            v-for="m in filteredLmsModules" 
            :key="m.id"
            class="p-5 bg-white rounded-2xl border border-slate-200 hover:border-emerald-500 hover:shadow-xs transition space-y-3.5 flex flex-col justify-between"
          >
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200 font-mono">
                  {{ m.quarter }} • {{ m.week_label }}
                </span>
                <button 
                  v-if="!isCurrentClassArchived"
                  @click="openConfirmDeleteModule(m)" 
                  type="button" 
                  class="text-slate-400 hover:text-rose-600 p-1 rounded transition cursor-pointer"
                  title="Delete module"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>

              <h4 class="font-bold text-slate-900 text-sm leading-snug">{{ m.title }}</h4>
              <p v-if="m.description" class="text-xs text-slate-500 line-clamp-2">{{ m.description }}</p>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
              <span v-if="m.file_size_kb" class="text-[11px] text-slate-400 font-mono">{{ m.file_size_kb }} KB</span>
              <span v-else-if="m.external_url" class="text-[11px] text-emerald-600 font-medium">Online Link</span>
              <span v-else class="text-[11px] text-slate-400 font-mono">Attachment</span>

              <a 
                v-if="m.file_path" 
                :href="getFileUrl(m.file_path)" 
                target="_blank" 
                download
                class="px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-semibold text-[11px] transition flex items-center space-x-1 cursor-pointer"
              >
                <Download class="w-3 h-3" />
                <span>Download</span>
              </a>
              <a 
                v-else-if="m.external_url" 
                :href="m.external_url" 
                target="_blank" 
                rel="noopener noreferrer"
                class="px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-800 font-semibold text-[11px] transition flex items-center space-x-1 cursor-pointer"
              >
                <ExternalLink class="w-3 h-3" />
                <span>Open Resource</span>
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- LMS SUB-TAB 3: ASSIGNMENTS & TASKS -->
      <div v-if="lmsClassData.class_info && lmsSubTab === 'assignments'" class="space-y-6">
        <!-- Controls & Create Trigger -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h3 class="text-sm font-bold text-slate-900">Homework, Seatworks & Performance Tasks</h3>
            <p class="text-xs text-slate-500">Collect learner submissions and record evaluated scores.</p>
          </div>

          <button 
            v-if="!isCurrentClassArchived"
            @click="openCreateAssignmentModal()" 
            type="button" 
            class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-700 hover:bg-emerald-600 text-white shadow-xs transition flex items-center space-x-1.5 cursor-pointer shrink-0"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Create Assignment Task</span>
          </button>
          <div v-else class="px-3.5 py-2 rounded-xl bg-slate-100 border border-slate-200 text-slate-500 text-xs font-semibold flex items-center space-x-1.5 shrink-0">
            <Lock class="w-3.5 h-3.5 text-slate-400" />
            <span>Creation Closed (Archived Term)</span>
          </div>
        </div>

        <!-- Assignments List -->
        <div class="space-y-4">
          <div 
            v-if="lmsClassData.assignments.length === 0" 
            class="py-12 bg-white rounded-3xl border border-dashed border-slate-200 text-center text-slate-400 text-xs"
          >
            <FileText class="w-8 h-8 mx-auto mb-2 text-slate-300" />
            No assignment tasks published yet. Click "Create Assignment Task" to post homework.
          </div>

          <div 
            v-for="asg in lmsClassData.assignments" 
            :key="asg.id"
            class="p-5 bg-white rounded-2xl border border-slate-200 hover:border-emerald-500 hover:shadow-xs transition space-y-4"
          >
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-3">
              <div class="space-y-1.5 flex-1">
                <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                  <span 
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                    :class="asg.task_type === 'Performance Task' ? 'bg-purple-50 text-purple-800 border border-purple-200' : 'bg-blue-50 text-blue-800 border border-blue-200'"
                  >
                    {{ asg.task_type }}
                  </span>
                  <span class="text-xs font-mono font-bold text-slate-700">{{ asg.max_score }} Points Max</span>
                  <span class="text-slate-300">•</span>
                  <span class="text-xs text-slate-500">{{ asg.quarter }}</span>
                </div>
                <h4 class="font-extrabold text-slate-900 text-base">{{ asg.title }}</h4>
                <p class="text-xs text-slate-600 whitespace-pre-line">{{ asg.instructions }}</p>
              </div>

              <!-- Stats & Submissions Button -->
              <div class="flex flex-col sm:flex-row md:flex-col items-end gap-2 shrink-0">
                <div class="text-right text-xs">
                  <div class="text-[11px] text-slate-400">Due Date:</div>
                  <strong class="text-slate-800 font-mono">{{ new Date(asg.due_date).toLocaleString() }}</strong>
                </div>

                <div class="flex items-center space-x-2">
                  <button 
                    @click="openSubmissionsModal(asg)" 
                    type="button" 
                    class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-800 hover:bg-emerald-700 text-white shadow-xs transition flex items-center space-x-1.5 cursor-pointer"
                  >
                    <Users class="w-3.5 h-3.5" />
                    <span>Submissions ({{ asg.total_submissions || 0 }})</span>
                  </button>

                  <button 
                    v-if="!isCurrentClassArchived"
                    @click="openConfirmDeleteAssignment(asg)" 
                    type="button" 
                    class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition cursor-pointer"
                    title="Delete assignment"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- TAB 2: ELECTRONIC CLASS RECORD (E-CLASS RECORD & GRADES) -->
    <!-- ======================================================== -->
    <div v-if="activeTab === 'grading'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-6">
      
      <!-- Top Action & Selection Bar -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-100 pb-5">
        <div>
          <div class="flex items-center space-x-2">
            <h2 class="text-base font-bold text-slate-900">DepEd Electronic Class Record (E-Class Record)</h2>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-900 font-mono">
              DepEd Order 8, s. 2015
            </span>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">
            Encode quarterly grades (0–100). Passing mark is 75.00. Final ratings calculate dynamically according to DepEd curriculum guidelines.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <!-- Departmentalized Class Selector Dropdown -->
          <select 
            v-model="selectedClassKey" 
            @change="handleClassChange()"
            class="px-3.5 py-2 rounded-xl border border-slate-300 bg-white text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-blue-500 focus:outline-none cursor-pointer shadow-2xs"
          >
            <option value="">-- Select Class Section & Subject --</option>
            <optgroup label="Junior High School (Full Year Learning Areas)" v-if="jhsClasses.length > 0">
              <option 
                v-for="c in jhsClasses" 
                :key="`${c.section_id}-${c.subject_id}`" 
                :value="`${c.section_id}-${c.subject_id}`"
              >
                {{ c.grade_level_code }} - {{ c.section_name }} : {{ c.subject_name }} ({{ c.subject_classification || 'Core' }})
              </option>
            </optgroup>
            <optgroup 
              :label="`Senior High School - 1st Semester${activeSchoolYear?.active_semester === '1st Semester' ? ' (Active Term)' : ' (Archived)'}`" 
              v-if="shs1stSemClasses.length > 0"
            >
              <option 
                v-for="c in shs1stSemClasses" 
                :key="`${c.section_id}-${c.subject_id}`" 
                :value="`${c.section_id}-${c.subject_id}`"
              >
                {{ c.grade_level_code }} - {{ c.section_name }} : {{ c.subject_name }} ({{ c.subject_classification || 'Core' }}) {{ activeSchoolYear?.active_semester === '1st Semester' ? '' : '🔒 (Archived)' }}
              </option>
            </optgroup>
            <optgroup 
              :label="`Senior High School - 2nd Semester${activeSchoolYear?.active_semester === '2nd Semester' ? ' (Active Term)' : ' (Archived)'}`" 
              v-if="shs2ndSemClasses.length > 0"
            >
              <option 
                v-for="c in shs2ndSemClasses" 
                :key="`${c.section_id}-${c.subject_id}`" 
                :value="`${c.section_id}-${c.subject_id}`"
              >
                {{ c.grade_level_code }} - {{ c.section_name }} : {{ c.subject_name }} ({{ c.subject_classification || 'Core' }}) {{ activeSchoolYear?.active_semester === '2nd Semester' ? '' : '🔒 (Archived)' }}
              </option>
            </optgroup>
          </select>

          <!-- Quick Fill Helper Dropdown -->
          <button 
            v-if="selectedClassKey"
            @click="showQuickFillModal = true"
            type="button"
            class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition flex items-center space-x-1.5 cursor-pointer"
          >
            <Sparkles class="w-3.5 h-3.5 text-amber-500" />
            <span>Quick Batch Fill</span>
          </button>

          <!-- Save Grades Button -->
          <button 
            @click="saveGradesBatch()" 
            :disabled="isSavingGrades || !selectedClassKey" 
            type="button" 
            class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white shadow-xs transition flex items-center space-x-1.5 cursor-pointer"
          >
            <Check class="w-4 h-4" />
            <span>{{ isSavingGrades ? 'Saving Grades...' : 'Save & Submit Grades' }}</span>
          </button>
        </div>
      </div>

      <!-- Class Active Header Details -->
      <div v-if="currentClassData.section" class="p-4 rounded-2xl bg-blue-50/70 border border-blue-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
        <div class="space-y-0.5">
          <div class="font-extrabold text-blue-950 text-sm">
            {{ currentClassData.section.name }} • {{ currentClassData.subject.name }}
          </div>
          <div class="text-slate-600 text-[11px]">
            Grade Level: <strong class="text-slate-900">{{ currentClassData.section.grade_level_name }}</strong> | 
            Classification: <strong class="text-slate-900">{{ currentClassData.subject.classification }}</strong> | 
            Term: <strong class="text-slate-900">{{ currentClassData.subject.semester || 'Full Academic Year' }}</strong>
          </div>
        </div>

        <!-- Filter / Search In Roster -->
        <div class="relative w-full sm:w-64">
          <Search class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" />
          <input 
            v-model="searchGradeQuery" 
            type="text" 
            placeholder="Search student or LRN..." 
            class="w-full pl-9 pr-3 py-1.5 rounded-xl bg-white border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
          />
        </div>
      </div>

      <!-- Electronic Class Record Table -->
      <div v-if="selectedClassKey" class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse min-w-[780px]">
          <thead>
            <tr class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider text-[11px] border-b border-slate-200">
              <th class="py-3 px-4 w-12 text-slate-400 font-mono">#</th>
              <th class="py-3 px-4">Learner Name</th>
              <th class="py-3 px-4 font-mono">LRN / Student No</th>
              <th class="py-3 px-4 text-center">Gender</th>
              
              <!-- Adaptive DepEd Quarterly Headers -->
              <th class="py-3 px-3 text-center w-24" :class="{ 'opacity-40': isSHS2ndSem }">
                Q1 (1st)
                <span v-if="isSHS2ndSem" class="block text-[9px] font-normal text-slate-400 font-mono">N/A (2nd Sem)</span>
              </th>
              <th class="py-3 px-3 text-center w-24" :class="{ 'opacity-40': isSHS2ndSem }">
                Q2 (2nd)
                <span v-if="isSHS2ndSem" class="block text-[9px] font-normal text-slate-400 font-mono">N/A (2nd Sem)</span>
              </th>
              <th class="py-3 px-3 text-center w-24" :class="{ 'opacity-40': isSHS1stSem }">
                Q3 (3rd)
                <span v-if="isSHS1stSem" class="block text-[9px] font-normal text-slate-400 font-mono">N/A (1st Sem)</span>
              </th>
              <th class="py-3 px-3 text-center w-24" :class="{ 'opacity-40': isSHS1stSem }">
                Q4 (4th)
                <span v-if="isSHS1stSem" class="block text-[9px] font-normal text-slate-400 font-mono">N/A (1st Sem)</span>
              </th>

              <!-- Adaptive Final Grade Header -->
              <th class="py-3 px-4 text-center w-32">
                {{ isSHS ? (isSHS2ndSem ? 'Semestral (Sem 2)' : 'Semestral (Sem 1)') : 'Annual Final' }}
              </th>
              <th class="py-3 px-4 text-center">Remarks</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(s, idx) in filteredClassStudents" :key="s.student_id" class="hover:bg-slate-50/80 transition">
              <td class="py-3.5 px-4 font-mono text-slate-400 text-[11px]">{{ idx + 1 }}</td>
              <td class="py-3.5 px-4 font-bold text-slate-900 whitespace-nowrap">{{ s.full_name }}</td>
              <td class="py-3.5 px-4 font-mono text-slate-600 whitespace-nowrap text-[11px]">
                <div>{{ s.lrn || 'No LRN' }}</div>
                <div class="text-[10px] text-slate-400">{{ s.student_no }}</div>
              </td>
              <td class="py-3.5 px-4 text-center">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold" :class="s.gender === 'Female' ? 'bg-pink-50 text-pink-700 border border-pink-200' : 'bg-blue-50 text-blue-700 border border-blue-200'">
                  {{ s.gender }}
                </span>
              </td>

              <!-- Q1 Input (Active for JHS and SHS 1st Sem) -->
              <td class="py-2.5 px-2 text-center">
                <input 
                  v-if="!isSHS2ndSem"
                  v-model.number="s.q1" 
                  @input="recalculateStudentGrade(s)"
                  type="number" 
                  min="0" 
                  max="100" 
                  step="0.01"
                  placeholder="--"
                  class="w-18 px-2 py-1.5 rounded-lg border border-slate-300 text-center font-mono font-bold text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none focus:bg-blue-50"
                />
                <span v-else class="text-[10px] font-mono font-bold text-slate-400 py-1.5 px-2 rounded-lg bg-slate-100 inline-block w-18" title="Not applicable for 2nd Semester subjects">N/A</span>
              </td>

              <!-- Q2 Input (Active for JHS and SHS 1st Sem) -->
              <td class="py-2.5 px-2 text-center">
                <input 
                  v-if="!isSHS2ndSem"
                  v-model.number="s.q2" 
                  @input="recalculateStudentGrade(s)"
                  type="number" 
                  min="0" 
                  max="100" 
                  step="0.01"
                  placeholder="--"
                  class="w-18 px-2 py-1.5 rounded-lg border border-slate-300 text-center font-mono font-bold text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none focus:bg-blue-50"
                />
                <span v-else class="text-[10px] font-mono font-bold text-slate-400 py-1.5 px-2 rounded-lg bg-slate-100 inline-block w-18" title="Not applicable for 2nd Semester subjects">N/A</span>
              </td>

              <!-- Q3 Input (Active for JHS and SHS 2nd Sem) -->
              <td class="py-2.5 px-2 text-center">
                <input 
                  v-if="!isSHS1stSem"
                  v-model.number="s.q3" 
                  @input="recalculateStudentGrade(s)"
                  type="number" 
                  min="0" 
                  max="100" 
                  step="0.01"
                  placeholder="--"
                  class="w-18 px-2 py-1.5 rounded-lg border border-slate-300 text-center font-mono font-bold text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none focus:bg-blue-50"
                />
                <span v-else class="text-[10px] font-mono font-bold text-slate-400 py-1.5 px-2 rounded-lg bg-slate-100 inline-block w-18" title="Not applicable for 1st Semester subjects">N/A</span>
              </td>

              <!-- Q4 Input (Active for JHS and SHS 2nd Sem) -->
              <td class="py-2.5 px-2 text-center">
                <input 
                  v-if="!isSHS1stSem"
                  v-model.number="s.q4" 
                  @input="recalculateStudentGrade(s)"
                  type="number" 
                  min="0" 
                  max="100" 
                  step="0.01"
                  placeholder="--"
                  class="w-18 px-2 py-1.5 rounded-lg border border-slate-300 text-center font-mono font-bold text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none focus:bg-blue-50"
                />
                <span v-else class="text-[10px] font-mono font-bold text-slate-400 py-1.5 px-2 rounded-lg bg-slate-100 inline-block w-18" title="Not applicable for 1st Semester subjects">N/A</span>
              </td>

              <!-- Final Grade (Computed) -->
              <td class="py-3.5 px-4 text-center font-mono font-bold text-xs">
                <span v-if="s.final_grade !== null" :class="s.final_grade >= 75 ? 'text-emerald-700' : 'text-rose-700'">
                  {{ s.final_grade.toFixed(2) }}
                </span>
                <span v-else class="text-slate-300">--</span>
              </td>

              <!-- Remarks Badge -->
              <td class="py-3.5 px-4 text-center">
                <span 
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                  :class="{
                    'bg-emerald-50 text-emerald-800 border border-emerald-200': s.remarks === 'Passed',
                    'bg-rose-50 text-rose-800 border border-rose-200': s.remarks === 'Failed',
                    'bg-slate-100 text-slate-600': s.remarks === 'Ongoing' || !s.remarks
                  }"
                >
                  {{ s.remarks || 'Ongoing' }}
                </span>
              </td>
            </tr>

            <tr v-if="filteredClassStudents.length === 0">
              <td colspan="10" class="py-12 text-center text-slate-400 text-xs">
                <Users class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                <div>No learners enrolled in this section match your search filter.</div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="py-16 text-center text-slate-400 text-xs border border-dashed border-slate-200 rounded-3xl">
        <BookOpen class="w-10 h-10 text-slate-300 mx-auto mb-3" />
        <div class="font-bold text-slate-700 text-sm">Select a Class Section & Subject to begin grading</div>
        <p class="text-slate-400 mt-1">Choose an assigned teaching load from the dropdown above to load the E-Class Record.</p>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- TAB 3: CLASS MASTERLISTS & STUDENT ROSTER                -->
    <!-- ======================================================== -->
    <div v-if="activeTab === 'roster'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
        <div>
          <h2 class="text-base font-bold text-slate-900">Class Masterlists & Student Directory</h2>
          <p class="text-xs text-slate-500">Official student profile list, LRNs, contact details, and enrollment verifications.</p>
        </div>
        
        <select 
          v-model="selectedClassKey" 
          @change="handleClassChange()"
          class="px-3.5 py-2 rounded-xl border border-slate-300 bg-white text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-blue-500 focus:outline-none cursor-pointer shadow-2xs"
        >
          <option value="">-- Select Class Section --</option>
          <option 
            v-for="c in teachingClasses" 
            :key="`${c.section_id}-${c.subject_id}`" 
            :value="`${c.section_id}-${c.subject_id}`"
          >
            {{ c.grade_level_code }} - {{ c.section_name }} : {{ c.subject_name }}
          </option>
        </select>
      </div>

      <div v-if="selectedClassKey" class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse min-w-[750px]">
          <thead>
            <tr class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider text-[11px] border-b border-slate-200">
              <th class="py-3 px-4 w-12 text-slate-400 font-mono">#</th>
              <th class="py-3 px-4">Learner Full Name</th>
              <th class="py-3 px-4 font-mono">LRN</th>
              <th class="py-3 px-4 font-mono">Student No</th>
              <th class="py-3 px-4 text-center">Gender</th>
              <th class="py-3 px-4">Contact Number</th>
              <th class="py-3 px-4">Email Address</th>
              <th class="py-3 px-4 text-center">Enrollment Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(s, idx) in currentClassData.students || []" :key="s.student_id" class="hover:bg-slate-50/80 transition">
              <td class="py-3.5 px-4 font-mono text-slate-400 text-[11px]">{{ idx + 1 }}</td>
              <td class="py-3.5 px-4 font-bold text-slate-900">{{ s.full_name }}</td>
              <td class="py-3.5 px-4 font-mono text-slate-600">{{ s.lrn || 'N/A' }}</td>
              <td class="py-3.5 px-4 font-mono text-blue-900 font-bold">{{ s.student_no }}</td>
              <td class="py-3.5 px-4 text-center">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold" :class="s.gender === 'Female' ? 'bg-pink-50 text-pink-700 border border-pink-200' : 'bg-blue-50 text-blue-700 border border-blue-200'">
                  {{ s.gender }}
                </span>
              </td>
              <td class="py-3.5 px-4 font-mono text-slate-600">{{ s.contact_number || 'N/A' }}</td>
              <td class="py-3.5 px-4 text-slate-600">{{ s.email || 'N/A' }}</td>
              <td class="py-3.5 px-4 text-center">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                  Officially Enrolled
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- TAB 4: ADVISORY SECTION & SF9 CORE VALUES                -->
    <!-- ======================================================== -->
    <div v-if="activeTab === 'advisory'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
        <div>
          <div class="flex items-center space-x-2">
            <h2 class="text-base font-bold text-slate-900">Homeroom Advisory Section & SF9 Core Values</h2>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-900 font-mono">
              Class Adviser
            </span>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">
            DepEd SF9 Learner Core Values Matrix (AO = Always Observed, SO = Sometimes Observed, RO = Rarely Observed, NO = Not Observed).
          </p>
        </div>

        <div class="flex items-center space-x-2">
          <button 
            v-if="advisoryData.has_advisory"
            @click="setAllValues('AO')"
            type="button"
            class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition flex items-center space-x-1.5 cursor-pointer"
          >
            <Sparkles class="w-3.5 h-3.5 text-purple-600" />
            <span>Mark All AO</span>
          </button>

          <button 
            @click="saveAdvisoryValues()" 
            :disabled="!advisoryData.has_advisory" 
            type="button" 
            class="px-4 py-2 rounded-xl text-xs font-semibold bg-purple-900 hover:bg-purple-800 disabled:opacity-50 text-white shadow-xs transition flex items-center space-x-1.5 cursor-pointer"
          >
            <Check class="w-4 h-4" />
            <span>Save SF9 Ratings</span>
          </button>
        </div>
      </div>

      <div v-if="advisoryData.has_advisory" class="space-y-4">
        <!-- Advisory Section Card -->
        <div class="p-4 rounded-2xl bg-purple-50/70 border border-purple-200 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div>
            <div class="font-extrabold text-purple-950 text-sm">
              {{ advisoryData.section?.name }} ({{ advisoryData.section?.grade_level_name }})
            </div>
            <div class="text-slate-600 text-[11px] mt-0.5">
              Room: <strong>{{ advisoryData.section?.room }}</strong> | Total Learners: <strong>{{ advisoryData.total_learners }}</strong>
            </div>
          </div>
        </div>

        <!-- SF9 Core Values Matrix Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left border-collapse min-w-[700px]">
            <thead>
              <tr class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider text-[11px] border-b border-slate-200">
                <th class="py-3 px-4 w-12 text-slate-400 font-mono">#</th>
                <th class="py-3 px-4">Learner Name</th>
                <th class="py-3 px-4 text-center">Maka-Diyos</th>
                <th class="py-3 px-4 text-center">Makatao</th>
                <th class="py-3 px-4 text-center">Makakalikasan</th>
                <th class="py-3 px-4 text-center">Makabansa</th>
                <th class="py-3 px-4 text-center font-mono">Gen. Average</th>
                <th class="py-3 px-4 text-center">Academic Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(l, idx) in advisoryData.learners" :key="l.student_id" class="hover:bg-slate-50/80 transition">
                <td class="py-3.5 px-4 font-mono text-slate-400 text-[11px]">{{ idx + 1 }}</td>
                <td class="py-3.5 px-4 font-bold text-slate-900 whitespace-nowrap">{{ l.full_name }}</td>

                <!-- Maka-Diyos Select -->
                <td class="py-2.5 px-3 text-center">
                  <select v-model="l.values_ratings.maka_diyos_q1" class="px-2.5 py-1 rounded-lg border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none cursor-pointer">
                    <option value="AO">AO</option>
                    <option value="SO">SO</option>
                    <option value="RO">RO</option>
                    <option value="NO">NO</option>
                  </select>
                </td>

                <!-- Makatao Select -->
                <td class="py-2.5 px-3 text-center">
                  <select v-model="l.values_ratings.maka_tao_q1" class="px-2.5 py-1 rounded-lg border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none cursor-pointer">
                    <option value="AO">AO</option>
                    <option value="SO">SO</option>
                    <option value="RO">RO</option>
                    <option value="NO">NO</option>
                  </select>
                </td>

                <!-- Makakalikasan Select -->
                <td class="py-2.5 px-3 text-center">
                  <select v-model="l.values_ratings.makakalikasan_q1" class="px-2.5 py-1 rounded-lg border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none cursor-pointer">
                    <option value="AO">AO</option>
                    <option value="SO">SO</option>
                    <option value="RO">RO</option>
                    <option value="NO">NO</option>
                  </select>
                </td>

                <!-- Makabansa Select -->
                <td class="py-2.5 px-3 text-center">
                  <select v-model="l.values_ratings.makabansa_q1" class="px-2.5 py-1 rounded-lg border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none cursor-pointer">
                    <option value="AO">AO</option>
                    <option value="SO">SO</option>
                    <option value="RO">RO</option>
                    <option value="NO">NO</option>
                  </select>
                </td>

                <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-800">
                  {{ l.general_average ? l.general_average.toFixed(2) : '--' }}
                </td>

                <!-- DepEd SARDO / Early Warning Academic Status -->
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                  <span 
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                    :class="{
                      'bg-emerald-50 text-emerald-800 border border-emerald-200': l.academic_status === 'On Track',
                      'bg-amber-50 text-amber-800 border border-amber-200': l.academic_status === 'Needs Support',
                      'bg-rose-50 text-rose-800 border border-rose-200': l.academic_status === 'Critical SARDO'
                    }"
                  >
                    {{ l.academic_status }}
                    <span v-if="l.failing_subjects_count > 0" class="text-[9px] font-mono ml-1">({{ l.failing_subjects_count }} Failed)</span>
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else class="py-16 text-center text-slate-400 text-xs border border-dashed border-slate-200 rounded-3xl">
        <Award class="w-10 h-10 text-slate-300 mx-auto mb-2" />
        <div class="font-bold text-slate-700">No Advisory Section Assigned</div>
        <p class="text-slate-400 mt-1">This faculty account is currently assigned purely for subject instruction.</p>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- TAB 5: DAILY ATTENDANCE TRACKER (SF2)                    -->
    <!-- ======================================================== -->
    <div v-if="activeTab === 'attendance'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
        <div>
          <h2 class="text-base font-bold text-slate-900">Daily Attendance Log (DepEd SF2)</h2>
          <p class="text-xs text-slate-500">Record daily learner attendance status (Present, Absent, Late, Excused).</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <input 
            v-model="attendanceDate" 
            type="date" 
            class="px-3 py-1.5 rounded-xl border border-slate-300 bg-white text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-blue-500 focus:outline-none cursor-pointer"
          />
          <button 
            @click="markAllPresent()" 
            type="button" 
            class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-800 transition cursor-pointer"
          >
            Mark All Present
          </button>
          <button 
            @click="saveAttendanceLog()" 
            type="button" 
            class="px-4 py-1.5 rounded-xl text-xs font-semibold bg-blue-900 hover:bg-blue-800 text-white shadow-xs transition cursor-pointer"
          >
            Save Attendance Log
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse min-w-[500px]">
          <thead>
            <tr class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider text-[11px] border-b border-slate-200">
              <th class="py-3 px-4 w-12 text-slate-400 font-mono">#</th>
              <th class="py-3 px-4">Learner Name</th>
              <th class="py-3 px-4 font-mono">LRN</th>
              <th class="py-3 px-4 text-center">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(s, idx) in attendanceStudents" :key="s.student_id" class="hover:bg-slate-50/80 transition">
              <td class="py-3.5 px-4 font-mono text-slate-400 text-[11px]">{{ idx + 1 }}</td>
              <td class="py-3.5 px-4 font-bold text-slate-900">{{ s.full_name }}</td>
              <td class="py-3.5 px-4 font-mono text-slate-600">{{ s.lrn || 'N/A' }}</td>
              <td class="py-2.5 px-4 text-center">
                <div class="inline-flex rounded-xl p-1 bg-slate-100 space-x-1">
                  <button 
                    v-for="st in ['Present', 'Late', 'Absent', 'Excused']" 
                    :key="st"
                    @click="s.attendance_status = st"
                    type="button"
                    :class="[
                      'px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase transition cursor-pointer',
                      s.attendance_status === st 
                        ? (st === 'Present' ? 'bg-emerald-600 text-white shadow-2xs' : st === 'Late' ? 'bg-amber-500 text-white shadow-2xs' : st === 'Absent' ? 'bg-rose-600 text-white shadow-2xs' : 'bg-blue-600 text-white shadow-2xs')
                        : 'text-slate-500 hover:text-slate-800'
                    ]"
                  >
                    {{ st }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Quick Batch Fill Grades Modal -->
    <div v-if="showQuickFillModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-2">
            <Sparkles class="w-5 h-5 text-amber-500" />
            <h3 class="font-bold text-slate-900 text-base">Quick Fill Quarterly Scores</h3>
          </div>
          <button @click="showQuickFillModal = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">✕</button>
        </div>

        <p class="text-xs text-slate-500">
          Quickly populate empty score fields for all learners in this class for rapid drafting.
        </p>

        <div class="space-y-3 text-xs">
          <div>
            <label class="font-semibold text-slate-700 block mb-1">Select Target Quarter</label>
            <select v-model="quickFillQuarter" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none">
              <!-- Adaptive Quarter Options -->
              <template v-if="isSHS1stSem">
                <option value="all">All Applicable Quarters (Q1 & Q2)</option>
                <option value="q1">Quarter 1 (1st Quarter)</option>
                <option value="q2">Quarter 2 (2nd Quarter)</option>
              </template>
              <template v-else-if="isSHS2ndSem">
                <option value="all">All Applicable Quarters (Q3 & Q4)</option>
                <option value="q3">Quarter 3 (3rd Quarter)</option>
                <option value="q4">Quarter 4 (4th Quarter)</option>
              </template>
              <template v-else>
                <option value="all">All Quarters (Q1 to Q4)</option>
                <option value="q1">Quarter 1 (1st Quarter)</option>
                <option value="q2">Quarter 2 (2nd Quarter)</option>
                <option value="q3">Quarter 3 (3rd Quarter)</option>
                <option value="q4">Quarter 4 (4th Quarter)</option>
              </template>
            </select>
          </div>

          <div>
            <label class="font-semibold text-slate-700 block mb-1">Score Value (0 - 100)</label>
            <input 
              v-model.number="quickFillScore" 
              type="number" 
              min="0" 
              max="100" 
              placeholder="e.g. 85.00" 
              class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-mono font-bold focus:ring-2 focus:ring-blue-500 focus:outline-none"
            />
          </div>
        </div>

        <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
          <button 
            @click="showQuickFillModal = false" 
            type="button" 
            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer"
          >
            Cancel
          </button>
          <button 
            @click="applyQuickFill()" 
            type="button" 
            class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-900 hover:bg-blue-800 text-white shadow-xs transition cursor-pointer"
          >
            Apply to Class
          </button>
        </div>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- LMS MODAL 1: UPLOAD LEARNING MODULE / HANDOUT            -->
    <!-- ======================================================== -->
    <div v-if="showUploadModuleModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl space-y-4 border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-2">
            <BookOpen class="w-5 h-5 text-emerald-600" />
            <h3 class="font-bold text-slate-900 text-base">Upload Learning Handout / Module</h3>
          </div>
          <button @click="showUploadModuleModal = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">✕</button>
        </div>

        <form @submit.prevent="uploadLmsModule()" class="space-y-3.5 text-xs">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="font-semibold text-slate-700 block mb-1">Target Quarter *</label>
              <select v-model="moduleForm.quarter" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                <option value="1st Quarter">1st Quarter</option>
                <option value="2nd Quarter">2nd Quarter</option>
                <option value="3rd Quarter">3rd Quarter</option>
                <option value="4th Quarter">4th Quarter</option>
              </select>
            </div>
            <div>
              <label class="font-semibold text-slate-700 block mb-1">Week Label *</label>
              <input 
                v-model="moduleForm.week_label" 
                type="text" 
                placeholder="e.g. Week 1 - 2" 
                required 
                class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
              />
            </div>
          </div>

          <div>
            <label class="font-semibold text-slate-700 block mb-1">Module / Topic Title *</label>
            <input 
              v-model="moduleForm.title" 
              type="text" 
              placeholder="e.g. Lesson 1: Introduction to Functions & Rational Relations" 
              required 
              class="w-full px-3 py-2 rounded-xl border border-slate-300 font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
          </div>

          <div>
            <label class="font-semibold text-slate-700 block mb-1">Description / Notes for Students</label>
            <textarea 
              v-model="moduleForm.description" 
              rows="2" 
              placeholder="Brief instructions or summary of learning competencies..." 
              class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            ></textarea>
          </div>

          <div>
            <label class="font-semibold text-slate-700 block mb-1">Upload Document File (PDF, Word, PPT, ZIP, Image - Max 10MB)</label>
            <input 
              type="file" 
              @change="handleModuleFileSelect" 
              class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-800 hover:file:bg-emerald-100 cursor-pointer"
            />
          </div>

          <div>
            <label class="font-semibold text-slate-700 block mb-1">Or External Web / Video Link (Optional)</label>
            <input 
              v-model="moduleForm.external_url" 
              type="url" 
              placeholder="https://www.youtube.com/watch?v=... or DepEd Commons link" 
              class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
          </div>

          <!-- Multi-Section Collective Distribution -->
          <div v-if="activeTeachingClasses.length > 1" class="p-3 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
              <div class="flex items-center space-x-1.5">
                <Layers class="w-3.5 h-3.5 text-emerald-700" />
                <span class="font-bold text-slate-800 text-[11px]">Distribute Module to Other Sections</span>
                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-emerald-100 text-emerald-800">
                  {{ moduleTargetKeys.length }} / {{ activeTeachingClasses.length }}
                </span>
              </div>
              <div class="flex items-center space-x-1 flex-wrap gap-y-1">
                <button 
                  v-if="sameSubjectTeachingClasses.length > 1" 
                  @click="selectAllSameSubject('module')" 
                  type="button" 
                  class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 hover:bg-emerald-200 cursor-pointer"
                >
                  All Same Subject ({{ sameSubjectTeachingClasses.length }})
                </button>
                <button 
                  @click="selectAllClasses('module')" 
                  type="button" 
                  class="px-2 py-0.5 rounded text-[10px] bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 cursor-pointer"
                >
                  All Classes
                </button>
                <button 
                  v-if="moduleTargetKeys.length > 1" 
                  @click="resetToOnlyCurrent('module')" 
                  type="button" 
                  class="px-2 py-0.5 rounded text-[10px] text-slate-500 hover:text-slate-800 cursor-pointer"
                >
                  Reset
                </button>
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-28 overflow-y-auto pr-1">
              <label 
                v-for="c in activeTeachingClasses" 
                :key="`mod-tgt-${c.section_id}-${c.subject_id}`" 
                :class="[
                  'p-1.5 rounded-lg border text-[11px] flex items-center space-x-2 cursor-pointer transition select-none',
                  moduleTargetKeys.includes(`${c.section_id}-${c.subject_id}`)
                    ? 'border-emerald-500 bg-emerald-50/80 text-emerald-950 font-semibold'
                    : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                ]"
              >
                <input 
                  type="checkbox" 
                  :value="`${c.section_id}-${c.subject_id}`" 
                  v-model="moduleTargetKeys" 
                  class="rounded text-emerald-600 focus:ring-emerald-500 shrink-0" 
                />
                <div class="min-w-0 flex-1 leading-tight">
                  <div class="truncate font-bold">{{ c.grade_level_code }} - {{ c.section_name }}</div>
                  <div class="truncate text-[10px] text-slate-500">{{ c.subject_name }}</div>
                </div>
              </label>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
            <button 
              @click="showUploadModuleModal = false" 
              type="button" 
              class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="isLoadingLms" 
              class="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-800 hover:bg-emerald-700 disabled:opacity-50 text-white shadow-xs transition flex items-center space-x-1.5 cursor-pointer"
            >
              <UploadCloud class="w-3.5 h-3.5" />
              <span>{{ isLoadingLms ? 'Uploading...' : 'Publish Module' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- LMS MODAL 2: CREATE ASSIGNMENT TASK                      -->
    <!-- ======================================================== -->
    <div v-if="showCreateAssignmentModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl space-y-4 border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-2">
            <FileText class="w-5 h-5 text-emerald-600" />
            <h3 class="font-bold text-slate-900 text-base">Create Assignment / Task</h3>
          </div>
          <button @click="showCreateAssignmentModal = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">✕</button>
        </div>

        <form @submit.prevent="saveLmsAssignment()" class="space-y-3.5 text-xs">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="font-semibold text-slate-700 block mb-1">Target Quarter *</label>
              <select v-model="assignmentForm.quarter" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                <option value="1st Quarter">1st Quarter</option>
                <option value="2nd Quarter">2nd Quarter</option>
                <option value="3rd Quarter">3rd Quarter</option>
                <option value="4th Quarter">4th Quarter</option>
              </select>
            </div>
            <div>
              <label class="font-semibold text-slate-700 block mb-1">Task Type *</label>
              <select v-model="assignmentForm.task_type" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                <option value="Written Work">Written Work (WW)</option>
                <option value="Performance Task">Performance Task (PT)</option>
                <option value="Quarterly Assessment">Quarterly Assessment (QA)</option>
              </select>
            </div>
          </div>

          <div>
            <label class="font-semibold text-slate-700 block mb-1">Assignment Title *</label>
            <input 
              v-model="assignmentForm.title" 
              type="text" 
              placeholder="e.g. Problem Set 1: Inverse Functions & Applications" 
              required 
              class="w-full px-3 py-2 rounded-xl border border-slate-300 font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="font-semibold text-slate-700 block mb-1">Maximum Score *</label>
              <input 
                v-model.number="assignmentForm.max_score" 
                type="number" 
                min="1" 
                max="500" 
                required 
                class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="font-semibold text-slate-700 block mb-1">Due Date & Time *</label>
              <input 
                v-model="assignmentForm.due_date" 
                type="datetime-local" 
                required 
                class="w-full px-3 py-2 rounded-xl border border-slate-300 font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
              />
            </div>
          </div>

          <div>
            <label class="font-semibold text-slate-700 block mb-1">Instructions / Guide *</label>
            <textarea 
              v-model="assignmentForm.instructions" 
              rows="3" 
              placeholder="Provide detailed instructions, rubric, or submission requirements..." 
              required 
              class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            ></textarea>
          </div>

          <!-- Multi-Section Collective Distribution -->
          <div v-if="activeTeachingClasses.length > 1" class="p-3 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
              <div class="flex items-center space-x-1.5">
                <Layers class="w-3.5 h-3.5 text-emerald-700" />
                <span class="font-bold text-slate-800 text-[11px]">Distribute Assignment to Other Sections</span>
                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-emerald-100 text-emerald-800">
                  {{ assignmentTargetKeys.length }} / {{ activeTeachingClasses.length }}
                </span>
              </div>
              <div class="flex items-center space-x-1 flex-wrap gap-y-1">
                <button 
                  v-if="sameSubjectTeachingClasses.length > 1" 
                  @click="selectAllSameSubject('assignment')" 
                  type="button" 
                  class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 hover:bg-emerald-200 cursor-pointer"
                >
                  All Same Subject ({{ sameSubjectTeachingClasses.length }})
                </button>
                <button 
                  @click="selectAllClasses('assignment')" 
                  type="button" 
                  class="px-2 py-0.5 rounded text-[10px] bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 cursor-pointer"
                >
                  All Classes
                </button>
                <button 
                  v-if="assignmentTargetKeys.length > 1" 
                  @click="resetToOnlyCurrent('assignment')" 
                  type="button" 
                  class="px-2 py-0.5 rounded text-[10px] text-slate-500 hover:text-slate-800 cursor-pointer"
                >
                  Reset
                </button>
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-28 overflow-y-auto pr-1">
              <label 
                v-for="c in activeTeachingClasses" 
                :key="`asg-tgt-${c.section_id}-${c.subject_id}`" 
                :class="[
                  'p-1.5 rounded-lg border text-[11px] flex items-center space-x-2 cursor-pointer transition select-none',
                  assignmentTargetKeys.includes(`${c.section_id}-${c.subject_id}`)
                    ? 'border-emerald-500 bg-emerald-50/80 text-emerald-950 font-semibold'
                    : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                ]"
              >
                <input 
                  type="checkbox" 
                  :value="`${c.section_id}-${c.subject_id}`" 
                  v-model="assignmentTargetKeys" 
                  class="rounded text-emerald-600 focus:ring-emerald-500 shrink-0" 
                />
                <div class="min-w-0 flex-1 leading-tight">
                  <div class="truncate font-bold">{{ c.grade_level_code }} - {{ c.section_name }}</div>
                  <div class="truncate text-[10px] text-slate-500">{{ c.subject_name }}</div>
                </div>
              </label>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
            <button 
              @click="showCreateAssignmentModal = false" 
              type="button" 
              class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="isLoadingLms" 
              class="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-800 hover:bg-emerald-700 disabled:opacity-50 text-white shadow-xs transition flex items-center space-x-1.5 cursor-pointer"
            >
              <Check class="w-3.5 h-3.5" />
              <span>{{ isLoadingLms ? 'Publishing...' : 'Publish Task' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- LMS MODAL 3: REVIEW SUBMISSIONS & GRADING DESK           -->
    <!-- ======================================================== -->
    <div v-if="showSubmissionsModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl max-w-4xl w-full p-6 shadow-2xl space-y-4 border border-slate-200 max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 shrink-0">
          <div>
            <div class="flex items-center space-x-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                {{ activeGradingAssignment?.task_type }} • {{ activeGradingAssignment?.max_score }} Points Max
              </span>
              <h3 class="font-bold text-slate-900 text-base">{{ activeGradingAssignment?.title }}</h3>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">
              Review student submitted files, assign numerical marks, and provide evaluation remarks.
            </p>
          </div>
          <button @click="showSubmissionsModal = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">✕</button>
        </div>

        <!-- Student Submissions Table -->
        <div class="overflow-x-auto overflow-y-auto flex-1 border border-slate-200 rounded-2xl">
          <table class="w-full text-xs text-left border-collapse min-w-[650px]">
            <thead class="bg-slate-50 text-slate-600 font-bold sticky top-0 border-b border-slate-200 z-10">
              <tr>
                <th class="py-3 px-3.5">Learner Name</th>
                <th class="py-3 px-3">Status</th>
                <th class="py-3 px-3">Submitted Work</th>
                <th class="py-3 px-3 w-28 text-center">Score (Max {{ activeGradingAssignment?.max_score }})</th>
                <th class="py-3 px-3">Teacher Remarks</th>
                <th class="py-3 px-3 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="submissionsRoster.length === 0">
                <td colspan="6" class="py-8 text-center text-slate-400">
                  No enrolled learners found in this class section.
                </td>
              </tr>
              <tr v-for="s in submissionsRoster" :key="s.student_id" class="hover:bg-slate-50/70 transition">
                <td class="py-3 px-3.5">
                  <div class="font-bold text-slate-900">{{ s.last_name }}, {{ s.first_name }}</div>
                  <div class="text-[10px] text-slate-400 font-mono">{{ s.official_student_no || 'No ID' }}</div>
                </td>

                <td class="py-3 px-3 whitespace-nowrap">
                  <span 
                    v-if="s.submission_status === 'Graded'" 
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200"
                  >
                    Graded
                  </span>
                  <span 
                    v-else-if="s.submission_status === 'Submitted'" 
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200"
                  >
                    Submitted
                  </span>
                  <span 
                    v-else-if="s.submission_status === 'Late'" 
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200"
                  >
                    Late
                  </span>
                  <span 
                    v-else 
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500"
                  >
                    Missing
                  </span>
                </td>

                <td class="py-3 px-3 max-w-xs">
                  <div v-if="s.submission_file" class="flex items-center space-x-1.5">
                    <a 
                      :href="getFileUrl(s.submission_file)" 
                      target="_blank" 
                      download 
                      class="text-blue-600 hover:text-blue-800 font-semibold underline flex items-center space-x-1"
                    >
                      <Paperclip class="w-3 h-3" />
                      <span class="truncate max-w-[120px]">Download File</span>
                    </a>
                  </div>
                  <div v-if="s.submission_text" class="text-[11px] text-slate-600 italic line-clamp-2 mt-0.5">
                    "{{ s.submission_text }}"
                  </div>
                  <span v-if="!s.submission_file && !s.submission_text" class="text-slate-400 text-[11px]">
                    No submission
                  </span>
                </td>

                <td class="py-3 px-3 text-center">
                  <input 
                    v-model.number="s.score" 
                    type="number" 
                    min="0" 
                    :max="activeGradingAssignment?.max_score || 100" 
                    step="0.5" 
                    placeholder="--" 
                    class="w-20 px-2 py-1.5 rounded-lg border border-slate-300 font-mono font-bold text-center text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                  />
                </td>

                <td class="py-3 px-3">
                  <input 
                    v-model="s.teacher_feedback" 
                    type="text" 
                    placeholder="Feedback note..." 
                    class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                  />
                </td>

                <td class="py-3 px-3 text-right">
                  <button 
                    @click="saveGradeForSubmission(s)" 
                    type="button" 
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-700 hover:bg-emerald-600 text-white shadow-2xs transition cursor-pointer"
                  >
                    Save Grade
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-end pt-3 border-t border-slate-100 shrink-0">
          <button 
            @click="showSubmissionsModal = false" 
            type="button" 
            class="px-5 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer"
          >
            Close Submissions Desk
          </button>
        </div>
      </div>
    </div>

    <!-- CUSTOM CONFIRMATION MODAL (REPLACES NATIVE BROWSER CONFIRM) -->
    <div v-if="confirmModal.isOpen" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-slate-200 text-xs space-y-4 animate-in fade-in zoom-in-95 duration-150 text-slate-900">
        <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center mx-auto shadow-2xs">
          <Trash2 class="w-6 h-6" />
        </div>
        <div class="text-center space-y-1">
          <h3 class="text-base font-extrabold text-slate-900">{{ confirmModal.title }}</h3>
          <p class="text-xs text-slate-700 leading-relaxed font-medium">{{ confirmModal.message }}</p>
        </div>

        <div v-if="confirmModal.subMessage" class="p-3.5 rounded-2xl bg-amber-50 border border-amber-300 text-amber-950 text-[11px] leading-relaxed flex items-start space-x-2.5">
          <AlertTriangle class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
          <span class="font-medium">{{ confirmModal.subMessage }}</span>
        </div>

        <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
          <button 
            type="button" 
            @click="confirmModal.isOpen = false" 
            :disabled="confirmModal.isDeleting"
            class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer disabled:opacity-50"
          >
            Cancel
          </button>
          <button 
            type="button" 
            @click="executeConfirmAction()" 
            :disabled="confirmModal.isDeleting"
            class="px-5 py-2.5 rounded-xl font-bold text-white shadow-xs transition flex items-center space-x-1.5 cursor-pointer disabled:opacity-50"
            :class="confirmModal.confirmColor"
          >
            <span v-if="confirmModal.isDeleting" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <Trash2 v-else class="w-3.5 h-3.5" />
            <span>{{ confirmModal.isDeleting ? 'Deleting...' : confirmModal.confirmText }}</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { 
  GraduationCap, Clock, BookOpen, Users, Award, Calendar, 
  FileSpreadsheet, CheckCircle, Search, Check, RefreshCw, 
  Sparkles, AlertCircle, AlertTriangle, UploadCloud, FileText, CheckCircle2,
  MessageSquare, Paperclip, ExternalLink, Trash2, Pin, Clock3,
  Send, Plus, Download, Eye, Layers, Lock
} from 'lucide-vue-next';
import api, { getFileUrl } from '../../services/api';

const route = useRoute();
const router = useRouter();

const activeTab = ref('schedule');
const isLoading = ref(false);
const isSavingGrades = ref(false);
const feedbackMessage = ref('');
const errorMessage = ref('');

// Auto-sync tab and trigger immediate LMS loading if LMS tab is selected
watch(() => route.query.tab, async (newTab) => {
  if (newTab && ['schedule', 'lms', 'grading', 'roster', 'advisory', 'attendance'].includes(newTab)) {
    activeTab.value = newTab;
    if (newTab === 'lms' && selectedClassKey.value) {
      await loadLmsContent();
      resetDistributionTargets();
    }
  }
}, { immediate: true });

// Listen to custom sidebar portal-tab-selected event
const handlePortalTabEvent = async (e) => {
  if (e.detail?.tab === 'lms' && selectedClassKey.value) {
    await loadLmsContent();
    resetDistributionTargets();
  }
};
window.addEventListener('portal-tab-selected', handlePortalTabEvent);
onUnmounted(() => {
  window.removeEventListener('portal-tab-selected', handlePortalTabEvent);
});

// Data State
const teacherProfile = ref(null);
const activeSchoolYear = ref(null);
const dashboardStats = ref({});
const weeklySchedules = ref([]);
const teachingClasses = ref([]);
const advisorySections = ref([]);

const selectedClassKey = ref('');
const currentClassData = ref({ section: null, subject: null, students: [] });
const searchGradeQuery = ref('');

const advisoryData = ref({ has_advisory: false, section: null, learners: [] });
const attendanceDate = ref(new Date().toISOString().split('T')[0]);
const attendanceStudents = ref([]);

// Quick fill modal state
const showQuickFillModal = ref(false);
const quickFillQuarter = ref('q1');
const quickFillScore = ref(85);

// Load Dashboard Data
const loadTeacherDashboard = async () => {
  isLoading.value = true;
  feedbackMessage.value = '';
  errorMessage.value = '';
  try {
    const res = await api.getTeacherDashboard();
    const data = res.data;
    teacherProfile.value = data.teacher;
    activeSchoolYear.value = data.school_year;
    dashboardStats.value = data.stats || {};
    weeklySchedules.value = data.weekly_schedules || [];
    teachingClasses.value = data.classes || [];
    advisorySections.value = data.advisory_sections || [];

    // If first class exists and no class selected, default to active semester class
    if (teachingClasses.value.length > 0 && !selectedClassKey.value) {
      const activeSem = activeSchoolYear.value?.active_semester || '1st Semester';
      const activeClass = teachingClasses.value.find(c => (c.level_category || '') === 'JHS' || (c.semester || '').includes(activeSem.substring(0, 3))) || teachingClasses.value[0];
      selectedClassKey.value = `${activeClass.section_id}-${activeClass.subject_id}`;
      await loadClassStudents(activeClass.section_id, activeClass.subject_id);
      if (activeTab.value === 'lms') {
        await loadLmsContent();
      }
      resetDistributionTargets();
    }

    // Load Advisory Data
    await loadAdvisorySection();
  } catch (err) {
    console.error('Failed to load teacher dashboard:', err);
    errorMessage.value = err.message || 'Failed to load teacher portal data.';
  } finally {
    isLoading.value = false;
  }
};

// Timetable Semester Toggle State (Resolves cross-semester schedule overlaps)
const activeTimetableSemester = ref('1st Semester');

const getDaySchedules = (day) => {
  return weeklySchedules.value.filter(s => {
    const matchesDay = s.day_of_week === day;
    const matchesSem = s.semester === 'Full Year' || s.semester === activeTimetableSemester.value;
    return matchesDay && matchesSem;
  });
};

const currentSemesterSchedules = computed(() => {
  return weeklySchedules.value.filter(s => s.semester === 'Full Year' || s.semester === activeTimetableSemester.value);
});

// Grouped Classes for Departmentalized Optgroups
const jhsClasses = computed(() => teachingClasses.value.filter(c => (c.level_category || '') === 'JHS'));
const shs1stSemClasses = computed(() => teachingClasses.value.filter(c => (c.level_category || '') === 'SHS' && (c.semester || '').includes('1st')));
const shs2ndSemClasses = computed(() => teachingClasses.value.filter(c => (c.level_category || '') === 'SHS' && (c.semester || '').includes('2nd')));

const currentSelectedClass = computed(() => {
  if (!selectedClassKey.value) return null;
  const [secId, subId] = selectedClassKey.value.split('-').map(Number);
  return teachingClasses.value.find(c => c.section_id === secId && c.subject_id === subId) || null;
});

const isCurrentClassArchived = computed(() => {
  if (!currentSelectedClass.value) return false;
  if ((currentSelectedClass.value.level_category || '') !== 'SHS') return false;
  const activeSem = activeSchoolYear.value?.active_semester || '1st Semester';
  const classSem = currentSelectedClass.value.semester || '';
  return Boolean(classSem && !classSem.includes(activeSem.substring(0, 3)) && classSem !== 'Full Year');
});

const activeTeachingClasses = computed(() => {
  const activeSem = activeSchoolYear.value?.active_semester || '1st Semester';
  return teachingClasses.value.filter(c => {
    if ((c.level_category || '') === 'JHS') return true;
    const sem = c.semester || '';
    return !sem || sem === 'Full Year' || sem.includes(activeSem.substring(0, 3));
  });
});

// Adaptive Grading state (DepEd Order 8, s. 2015)
const isSHS = computed(() => (currentClassData.value.section?.level_category || '') === 'SHS');
const isSHS1stSem = computed(() => isSHS.value && (currentClassData.value.subject?.semester || '').includes('1st'));
const isSHS2ndSem = computed(() => isSHS.value && (currentClassData.value.subject?.semester || '').includes('2nd'));

const formatTime = (timeStr) => {
  if (!timeStr) return '';
  const [h, m] = timeStr.split(':');
  let hour = parseInt(h, 10);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  hour = hour % 12 || 12;
  return `${hour}:${m} ${ampm}`;
};

const openClassRecord = async (sectionId, subjectId) => {
  selectedClassKey.value = `${sectionId}-${subjectId}`;
  activeTab.value = 'grading';
  router.push({ query: { tab: 'grading' } });
  await loadClassStudents(sectionId, subjectId);
};

// Seamless 1-Click Dropdown Switch (Instantly refreshes LMS & Roster without manual reload)
const handleClassChange = async () => {
  if (!selectedClassKey.value) return;
  const [secId, subId] = selectedClassKey.value.split('-').map(Number);
  await loadClassStudents(secId, subId);
  if (activeTab.value === 'lms') {
    await loadLmsContent();
  }
  resetDistributionTargets();
};

// Watch selectedClassKey to automatically update LMS tab if active
watch(selectedClassKey, async (newKey) => {
  if (newKey && activeTab.value === 'lms') {
    await loadLmsContent();
    resetDistributionTargets();
  }
});

const loadClassStudents = async (sectionId, subjectId) => {
  try {
    const res = await api.getTeacherClassStudents(sectionId, subjectId);
    currentClassData.value = res.data;

    // Adjust quick fill default quarter based on class
    if (isSHS2ndSem.value) {
      quickFillQuarter.value = 'q3';
    } else {
      quickFillQuarter.value = 'q1';
    }

    // Prepare attendance list clone
    attendanceStudents.value = (res.data.students || []).map(s => ({
      ...s,
      attendance_status: 'Present'
    }));
  } catch (err) {
    console.error('Failed to load class students:', err);
  }
};

const recalculateStudentGrade = (student) => {
  const isSHSVal = isSHS.value;
  const is1st = isSHS1stSem.value;
  const is2nd = isSHS2ndSem.value;

  const q1 = student.q1 !== null && student.q1 !== '' ? Number(student.q1) : null;
  const q2 = student.q2 !== null && student.q2 !== '' ? Number(student.q2) : null;
  const q3 = student.q3 !== null && student.q3 !== '' ? Number(student.q3) : null;
  const q4 = student.q4 !== null && student.q4 !== '' ? Number(student.q4) : null;

  if (isSHSVal) {
    if (is2nd) {
      if (q3 !== null && q4 !== null) {
        student.final_grade = Math.round(((q3 + q4) / 2) * 100) / 100;
        student.remarks = student.final_grade >= 75 ? 'Passed' : 'Failed';
      } else {
        student.final_grade = null;
        student.remarks = 'Ongoing';
      }
    } else {
      if (q1 !== null && q2 !== null) {
        student.final_grade = Math.round(((q1 + q2) / 2) * 100) / 100;
        student.remarks = student.final_grade >= 75 ? 'Passed' : 'Failed';
      } else {
        student.final_grade = null;
        student.remarks = 'Ongoing';
      }
    }
  } else {
    // JHS Full Year (Q1 to Q4)
    if (q1 !== null && q2 !== null && q3 !== null && q4 !== null) {
      student.final_grade = Math.round(((q1 + q2 + q3 + q4) / 4) * 100) / 100;
      student.remarks = student.final_grade >= 75 ? 'Passed' : 'Failed';
    } else {
      student.final_grade = null;
      student.remarks = 'Ongoing';
    }
  }
};

const filteredClassStudents = computed(() => {
  const list = currentClassData.value.students || [];
  if (!searchGradeQuery.value.trim()) return list;
  const q = searchGradeQuery.value.toLowerCase().trim();
  return list.filter(s => 
    s.full_name.toLowerCase().includes(q) ||
    (s.lrn && s.lrn.toLowerCase().includes(q)) ||
    (s.student_no && s.student_no.toLowerCase().includes(q))
  );
});

const applyQuickFill = () => {
  const score = Number(quickFillScore.value) || 0;
  const list = currentClassData.value.students || [];

  list.forEach(s => {
    if (quickFillQuarter.value === 'all') {
      if (isSHS.value) {
        if (isSHS2ndSem.value) {
          s.q3 = score;
          s.q4 = score;
        } else {
          s.q1 = score;
          s.q2 = score;
        }
      } else {
        s.q1 = score;
        s.q2 = score;
        s.q3 = score;
        s.q4 = score;
      }
    } else {
      s[quickFillQuarter.value] = score;
    }
    recalculateStudentGrade(s);
  });

  showQuickFillModal.value = false;
  feedbackMessage.value = `Applied score of ${score} to applicable quarters.`;
  setTimeout(() => { feedbackMessage.value = ''; }, 3500);
};

const saveGradesBatch = async () => {
  if (!selectedClassKey.value) return;
  const [secId, subId] = selectedClassKey.value.split('-').map(Number);

  isSavingGrades.value = true;
  feedbackMessage.value = '';
  errorMessage.value = '';

  try {
    const gradesPayload = (currentClassData.value.students || []).map(s => ({
      student_id: s.student_id,
      q1: s.q1,
      q2: s.q2,
      q3: s.q3,
      q4: s.q4
    }));

    const res = await api.saveTeacherGrades({
      section_id: secId,
      subject_id: subId,
      grades: gradesPayload
    });

    feedbackMessage.value = res.message || 'Grades saved successfully!';
    setTimeout(() => { feedbackMessage.value = ''; }, 4000);
    await loadClassStudents(secId, subId);
  } catch (err) {
    errorMessage.value = 'Failed to save grades: ' + (err.message || 'Error occurred.');
  } finally {
    isSavingGrades.value = false;
  }
};

const loadAdvisorySection = async () => {
  try {
    const res = await api.getTeacherAdvisorySection();
    advisoryData.value = res.data;
  } catch (err) {
    console.error('Failed to load advisory section:', err);
  }
};

const setAllValues = (rating) => {
  if (!advisoryData.value.learners) return;
  advisoryData.value.learners.forEach(l => {
    l.values_ratings = {
      maka_diyos_q1: rating,
      maka_diyos_q2: rating,
      maka_tao_q1: rating,
      maka_tao_q2: rating,
      makakalikasan_q1: rating,
      makakalikasan_q2: rating,
      makabansa_q1: rating,
      makabansa_q2: rating
    };
  });
  feedbackMessage.value = `Marked all Core Values as ${rating} (${rating === 'AO' ? 'Always Observed' : rating}).`;
  setTimeout(() => { feedbackMessage.value = ''; }, 3500);
};

const saveAdvisoryValues = async () => {
  if (!advisoryData.value.section?.id) return;
  try {
    await api.saveTeacherAdvisoryValues({
      section_id: advisoryData.value.section.id,
      values: advisoryData.value.learners
    });
    feedbackMessage.value = 'DepEd SF9 Learner Core Values successfully recorded.';
    setTimeout(() => { feedbackMessage.value = ''; }, 4000);
  } catch (err) {
    errorMessage.value = 'Failed to save values: ' + err.message;
  }
};

const markAllPresent = () => {
  attendanceStudents.value.forEach(s => {
    s.attendance_status = 'Present';
  });
  feedbackMessage.value = 'Marked all enrolled learners as Present.';
  setTimeout(() => { feedbackMessage.value = ''; }, 3500);
};

const saveAttendanceLog = async () => {
  if (!selectedClassKey.value) {
    errorMessage.value = 'Please select a class section first.';
    return;
  }
  const [secId] = selectedClassKey.value.split('-').map(Number);
  try {
    const res = await api.saveTeacherAttendance({
      section_id: secId,
      date: attendanceDate.value,
      attendance: attendanceStudents.value
    });
    feedbackMessage.value = res.message || 'Attendance logged successfully.';
    setTimeout(() => { feedbackMessage.value = ''; }, 4000);
  } catch (err) {
    errorMessage.value = 'Failed to save attendance: ' + err.message;
  }
};

// ========================================================
// LMS HUB STATE & METHODS
// ========================================================
// LMS VIRTUAL CLASSROOM & LEARNING MANAGEMENT (MULTI-SECTION)
// ========================================================
const lmsSubTab = ref('stream');
const lmsClassData = ref({
  class_info: null,
  announcements: [],
  modules: [],
  assignments: []
});
const isLoadingLms = ref(false);
const selectedLmsQuarter = ref('all');

const showUploadModuleModal = ref(false);
const showCreateAssignmentModal = ref(false);
const showSubmissionsModal = ref(false);

// Multi-Section Collective Distribution Keys
const announcementTargetKeys = ref([]);
const moduleTargetKeys = ref([]);
const assignmentTargetKeys = ref([]);

const currentSelectedSubject = computed(() => {
  if (!selectedClassKey.value) return null;
  const [secId, subId] = selectedClassKey.value.split('-').map(Number);
  return teachingClasses.value.find(c => c.section_id === secId && c.subject_id === subId) || null;
});

const sameSubjectTeachingClasses = computed(() => {
  if (!currentSelectedSubject.value) return [];
  return activeTeachingClasses.value.filter(c => c.subject_id === currentSelectedSubject.value.subject_id);
});

const resetDistributionTargets = () => {
  if (selectedClassKey.value) {
    announcementTargetKeys.value = [selectedClassKey.value];
    moduleTargetKeys.value = [selectedClassKey.value];
    assignmentTargetKeys.value = [selectedClassKey.value];
  } else {
    announcementTargetKeys.value = [];
    moduleTargetKeys.value = [];
    assignmentTargetKeys.value = [];
  }
};

const selectAllSameSubject = (type) => {
  const keys = sameSubjectTeachingClasses.value.map(c => `${c.section_id}-${c.subject_id}`);
  if (type === 'announcement') announcementTargetKeys.value = keys;
  if (type === 'module') moduleTargetKeys.value = keys;
  if (type === 'assignment') assignmentTargetKeys.value = keys;
};

const selectAllClasses = (type) => {
  const keys = activeTeachingClasses.value.map(c => `${c.section_id}-${c.subject_id}`);
  if (type === 'announcement') announcementTargetKeys.value = keys;
  if (type === 'module') moduleTargetKeys.value = keys;
  if (type === 'assignment') assignmentTargetKeys.value = keys;
};

const resetToOnlyCurrent = (type) => {
  if (!selectedClassKey.value) return;
  if (type === 'announcement') announcementTargetKeys.value = [selectedClassKey.value];
  if (type === 'module') moduleTargetKeys.value = [selectedClassKey.value];
  if (type === 'assignment') assignmentTargetKeys.value = [selectedClassKey.value];
};

const openUploadModuleModal = () => {
  resetDistributionTargets();
  showUploadModuleModal.value = true;
};

const openCreateAssignmentModal = () => {
  resetDistributionTargets();
  showCreateAssignmentModal.value = true;
};

const announcementForm = ref({
  title: '',
  content: '',
  is_pinned: false
});

const moduleForm = ref({
  quarter: '1st Quarter',
  week_label: 'Week 1',
  title: '',
  description: '',
  external_url: '',
  file: null
});

const assignmentForm = ref({
  quarter: '1st Quarter',
  title: '',
  instructions: '',
  task_type: 'Written Work',
  max_score: 50,
  due_date: ''
});

const activeGradingAssignment = ref(null);
const submissionsRoster = ref([]);
const isLoadingSubmissions = ref(false);

const filteredLmsModules = computed(() => {
  if (selectedLmsQuarter.value === 'all') {
    return lmsClassData.value.modules || [];
  }
  return (lmsClassData.value.modules || []).filter(m => m.quarter === selectedLmsQuarter.value);
});

const loadLmsContent = async () => {
  if (!selectedClassKey.value) return;
  const [secId, subId] = selectedClassKey.value.split('-').map(Number);
  isLoadingLms.value = true;
  try {
    const res = await api.getLmsClassContent(secId, subId);
    lmsClassData.value = res.data;
  } catch (err) {
    console.error('Failed to load LMS content:', err);
    errorMessage.value = 'Failed to load classroom content: ' + err.message;
  } finally {
    isLoadingLms.value = false;
  }
};

const postLmsAnnouncement = async () => {
  if (!selectedClassKey.value) {
    errorMessage.value = 'Please select a class section first.';
    return;
  }
  if (isCurrentClassArchived.value) {
    errorMessage.value = 'Cannot post announcement: this class belongs to an archived academic semester.';
    return;
  }
  if (!announcementForm.value.title || !announcementForm.value.content) {
    errorMessage.value = 'Please provide an announcement title and message.';
    return;
  }
  const [secId, subId] = selectedClassKey.value.split('-').map(Number);
  try {
    const res = await api.saveLmsAnnouncement({
      section_id: secId,
      subject_id: subId,
      title: announcementForm.value.title,
      content: announcementForm.value.content,
      is_pinned: announcementForm.value.is_pinned,
      target_sections: announcementTargetKeys.value
    });
    feedbackMessage.value = res.message || 'Announcement posted to class stream.';
    announcementForm.value = { title: '', content: '', is_pinned: false };
    resetDistributionTargets();
    await loadLmsContent();
    setTimeout(() => { feedbackMessage.value = ''; }, 3500);
  } catch (err) {
    errorMessage.value = 'Failed to post announcement: ' + err.message;
  }
};


const handleModuleFileSelect = (e) => {
  const file = e.target.files[0];
  if (file) {
    moduleForm.value.file = file;
  }
};

const uploadLmsModule = async () => {
  if (!selectedClassKey.value) return;
  if (isCurrentClassArchived.value) {
    errorMessage.value = 'Cannot upload module: this class belongs to an archived academic semester.';
    return;
  }
  const [secId, subId] = selectedClassKey.value.split('-').map(Number);
  if (!moduleForm.value.title) {
    errorMessage.value = 'Module title is required.';
    return;
  }
  if (!moduleForm.value.file && !moduleForm.value.external_url) {
    errorMessage.value = 'Please select a document file to upload or enter a web link.';
    return;
  }

  const formData = new FormData();
  formData.append('section_id', secId);
  formData.append('subject_id', subId);
  formData.append('quarter', moduleForm.value.quarter);
  formData.append('week_label', moduleForm.value.week_label);
  formData.append('title', moduleForm.value.title);
  formData.append('description', moduleForm.value.description);
  formData.append('external_url', moduleForm.value.external_url);
  formData.append('target_sections', JSON.stringify(moduleTargetKeys.value));
  if (moduleForm.value.file) {
    formData.append('file', moduleForm.value.file);
  }

  isLoadingLms.value = true;
  try {
    const res = await api.uploadLmsModule(formData);
    feedbackMessage.value = res.message || 'Module uploaded successfully.';
    showUploadModuleModal.value = false;
    moduleForm.value = { quarter: '1st Quarter', week_label: 'Week 1', title: '', description: '', external_url: '', file: null };
    resetDistributionTargets();
    await loadLmsContent();
    setTimeout(() => { feedbackMessage.value = ''; }, 3500);
  } catch (err) {
    errorMessage.value = 'Failed to upload module: ' + err.message;
  } finally {
    isLoadingLms.value = false;
  }
};


const saveLmsAssignment = async () => {
  if (!selectedClassKey.value) return;
  if (isCurrentClassArchived.value) {
    errorMessage.value = 'Cannot create assignment: this class belongs to an archived academic semester.';
    return;
  }
  const [secId, subId] = selectedClassKey.value.split('-').map(Number);
  if (!assignmentForm.value.title || !assignmentForm.value.due_date || !assignmentForm.value.instructions) {
    errorMessage.value = 'Please complete all required assignment fields.';
    return;
  }

  isLoadingLms.value = true;
  try {
    const res = await api.saveLmsAssignment({
      section_id: secId,
      subject_id: subId,
      ...assignmentForm.value,
      target_sections: assignmentTargetKeys.value
    });
    feedbackMessage.value = res.message || 'Assignment published to class.';
    showCreateAssignmentModal.value = false;
    assignmentForm.value = { quarter: '1st Quarter', title: '', instructions: '', task_type: 'Written Work', max_score: 50, due_date: '' };
    resetDistributionTargets();
    await loadLmsContent();
    setTimeout(() => { feedbackMessage.value = ''; }, 3500);
  } catch (err) {
    errorMessage.value = 'Failed to save assignment: ' + err.message;
  } finally {
    isLoadingLms.value = false;
  }
};

// Custom Confirmation Modal State (replaces native browser confirm())
const confirmModal = ref({
  isOpen: false,
  title: '',
  message: '',
  subMessage: '',
  confirmText: 'Delete',
  confirmColor: 'bg-rose-700 hover:bg-rose-800',
  isDeleting: false,
  action: null
});

const openConfirmDeleteAnnouncement = (a) => {
  confirmModal.value = {
    isOpen: true,
    title: 'Delete Class Announcement',
    message: `Are you sure you want to delete the announcement "${a.title}"?`,
    subMessage: 'This notice will be permanently removed from this class stream and learners will no longer see it.',
    confirmText: 'Delete Announcement',
    confirmColor: 'bg-rose-700 hover:bg-rose-800',
    isDeleting: false,
    action: async () => {
      const res = await api.deleteLmsAnnouncement(a.id);
      feedbackMessage.value = res.message || 'Announcement deleted.';
      await loadLmsContent();
      setTimeout(() => { feedbackMessage.value = ''; }, 3500);
    }
  };
};

const openConfirmDeleteModule = (m) => {
  confirmModal.value = {
    isOpen: true,
    title: 'Delete Learning Module',
    message: `Are you sure you want to delete "${m.title}"?`,
    subMessage: 'The uploaded file or reference link will be permanently removed for all enrolled students.',
    confirmText: 'Delete Module',
    confirmColor: 'bg-rose-700 hover:bg-rose-800',
    isDeleting: false,
    action: async () => {
      const res = await api.deleteLmsModule(m.id);
      feedbackMessage.value = res.message || 'Module removed.';
      await loadLmsContent();
      setTimeout(() => { feedbackMessage.value = ''; }, 3500);
    }
  };
};

const openConfirmDeleteAssignment = (asg) => {
  confirmModal.value = {
    isOpen: true,
    title: 'Delete Assignment Task',
    message: `Are you sure you want to delete "${asg.title}"?`,
    subMessage: '⚠️ Critical Notice: All student submissions, attached files, scores, and grading records for this assignment task will also be permanently deleted.',
    confirmText: 'Delete Assignment & Submissions',
    confirmColor: 'bg-rose-700 hover:bg-rose-800',
    isDeleting: false,
    action: async () => {
      const res = await api.deleteLmsAssignment(asg.id);
      feedbackMessage.value = res.message || 'Assignment deleted.';
      await loadLmsContent();
      setTimeout(() => { feedbackMessage.value = ''; }, 3500);
    }
  };
};

const executeConfirmAction = async () => {
  if (!confirmModal.value.action) return;
  confirmModal.value.isDeleting = true;
  try {
    await confirmModal.value.action();
    confirmModal.value.isOpen = false;
  } catch (err) {
    errorMessage.value = err.message || 'Failed to complete action.';
  } finally {
    confirmModal.value.isDeleting = false;
  }
};

const openSubmissionsModal = async (assignment) => {
  activeGradingAssignment.value = assignment;
  showSubmissionsModal.value = true;
  isLoadingSubmissions.value = true;
  try {
    const res = await api.getLmsAssignmentSubmissions(assignment.id);
    submissionsRoster.value = res.data.roster || [];
  } catch (err) {
    errorMessage.value = 'Failed to load submissions: ' + err.message;
  } finally {
    isLoadingSubmissions.value = false;
  }
};

const saveGradeForSubmission = async (student) => {
  if (student.score === null || student.score === undefined || student.score === '') {
    errorMessage.value = 'Please enter a numerical score before saving.';
    return;
  }
  if (!student.submission_id) {
    errorMessage.value = 'Learner has not submitted any work yet.';
    return;
  }
  try {
    const res = await api.gradeLmsSubmission({
      submission_id: student.submission_id,
      score: student.score,
      teacher_feedback: student.teacher_feedback || ''
    });
    student.submission_status = 'Graded';
    feedbackMessage.value = res.message || 'Grade recorded successfully.';
    setTimeout(() => { feedbackMessage.value = ''; }, 3500);
  } catch (err) {
    errorMessage.value = 'Failed to save grade: ' + err.message;
  }
};

onMounted(() => {
  loadTeacherDashboard();
});
</script>
