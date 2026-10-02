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

              <div class="flex items-center space-x-1.5">
                <button 
                  v-if="m.file_path" 
                  type="button" 
                  @click="openModulePreview(m)" 
                  class="px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-900 border border-emerald-200 font-semibold text-[11px] transition flex items-center space-x-1 cursor-pointer"
                >
                  <Eye class="w-3 h-3 text-emerald-700" />
                  <span>View Handout</span>
                </button>
                <a 
                  v-if="m.file_path" 
                  :href="getFileUrl(m.file_path)" 
                  target="_blank" 
                  download
                  class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[11px] transition flex items-center space-x-1 cursor-pointer"
                >
                  <Download class="w-3 h-3" />
                  <span>Download</span>
                </a>
                <a 
                  v-else-if="m.external_url" 
                  :href="m.external_url" 
                  target="_blank" 
                  rel="noopener noreferrer"
                  class="px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-800 font-semibold text-[11px] transition flex items-center space-x-1 cursor-pointer"
                >
                  <ExternalLink class="w-3 h-3" />
                  <span>Open Resource</span>
                </a>
              </div>
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
            class="p-5 bg-white rounded-2xl border transition space-y-4"
            :class="[
              asg.status === 'draft' 
                ? 'border-dashed border-amber-300 bg-amber-50/20 hover:border-amber-400' 
                : 'border-slate-200 hover:border-emerald-500 hover:shadow-xs'
            ]"
          >
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-3">
              <div class="space-y-1.5 flex-1">
                <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                  <!-- Draft Status Badge -->
                  <span 
                    v-if="asg.status === 'draft'" 
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-900 border border-amber-300 flex items-center space-x-1"
                  >
                    <Clock class="w-3 h-3 text-amber-700" />
                    <span>Draft • Hidden from Students</span>
                  </span>

                  <span 
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                    :class="asg.task_type === 'Performance Task' ? 'bg-purple-50 text-purple-800 border border-purple-200' : 'bg-blue-50 text-blue-800 border border-blue-200'"
                  >
                    {{ asg.task_type }}
                  </span>
                  <span v-if="asg.submission_format === 'quiz'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-violet-50 text-violet-800 border border-violet-200 flex items-center space-x-1">
                    <ListChecks class="w-3 h-3 text-violet-600" />
                    <span>Interactive Quiz</span>
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
                  <!-- DRAFT ACTIONS -->
                  <template v-if="asg.status === 'draft'">
                    <button 
                      v-if="!isCurrentClassArchived"
                      @click="publishDraftAssignment(asg)" 
                      type="button" 
                      class="px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-700 hover:bg-emerald-600 text-white shadow-2xs transition flex items-center space-x-1.5 cursor-pointer"
                      title="Publish this task now to all enrolled learners"
                    >
                      <Send class="w-3.5 h-3.5" />
                      <span>Publish Now</span>
                    </button>

                    <button 
                      v-if="!isCurrentClassArchived"
                      @click="editDraftAssignment(asg)" 
                      type="button" 
                      class="px-3 py-2 rounded-xl text-xs font-semibold bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 transition flex items-center space-x-1 cursor-pointer"
                      title="Edit draft task questions and details"
                    >
                      <FileText class="w-3.5 h-3.5 text-slate-500" />
                      <span>Edit</span>
                    </button>
                  </template>

                  <!-- PUBLISHED ACTIONS -->
                  <template v-else>
                    <button 
                      @click="openSubmissionsModal(asg)" 
                      type="button" 
                      class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-800 hover:bg-emerald-700 text-white shadow-xs transition flex items-center space-x-1.5 cursor-pointer"
                    >
                      <Users class="w-3.5 h-3.5" />
                      <span>Submissions ({{ asg.total_submissions || 0 }})</span>
                    </button>
                  </template>

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
            <div class="flex items-center justify-between mb-1">
              <label class="font-semibold text-slate-700 block">Upload Document File (Optional)</label>
              <span class="text-[10px] font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">Max 15MB Limit</span>
            </div>
            <input 
              type="file" 
              @change="handleModuleFileSelect" 
              accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.jpg,.jpeg,.png,.webp,.txt"
              class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-800 hover:file:bg-emerald-100 cursor-pointer border border-slate-200 rounded-xl p-1"
            />
            <p class="text-[10px] text-slate-400 mt-1">Allowed: PDF, Word (.docx, .doc), PowerPoint, Excel, Images, ZIP up to 15MB.</p>
            <div v-if="moduleForm.file" class="mt-2 p-2 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs text-emerald-900 font-medium">
              <span class="truncate max-w-[280px]">📎 {{ moduleForm.file.name }} ({{ (moduleForm.file.size / 1024).toFixed(1) }} KB)</span>
              <button type="button" @click="moduleForm.file = null" class="text-rose-600 hover:text-rose-800 font-bold ml-2">✕ Remove</button>
            </div>
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
      <div class="bg-white rounded-3xl max-w-3xl w-full max-h-[92vh] overflow-y-auto p-6 sm:p-7 shadow-2xl space-y-4 border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-2">
            <FileText class="w-5 h-5 text-emerald-600" />
            <h3 class="font-bold text-slate-900 text-base">
              {{ assignmentForm.id ? 'Edit Learning Task / Quiz (Draft)' : 'Create Learning Task / Interactive Quiz' }}
            </h3>
          </div>
          <button @click="showCreateAssignmentModal = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">✕</button>
        </div>

        <!-- Format Selector Toggle -->
        <div class="bg-slate-100 p-1 rounded-2xl grid grid-cols-2 gap-1 text-xs font-bold">
          <button 
            type="button" 
            @click="assignmentForm.submission_format = 'standard'"
            :class="[
              'py-2 px-3 rounded-xl transition flex items-center justify-center space-x-1.5 cursor-pointer',
              assignmentForm.submission_format === 'standard' 
                ? 'bg-white text-emerald-950 shadow-xs' 
                : 'text-slate-600 hover:text-slate-900'
            ]"
          >
            <FileText class="w-3.5 h-3.5 text-emerald-700" />
            <span>Standard Turn-In (Document / File)</span>
          </button>
          <button 
            type="button" 
            @click="assignmentForm.submission_format = 'quiz'; if (!assignmentForm.quiz_questions || assignmentForm.quiz_questions.length === 0) addQuizQuestion('multiple_choice');"
            :class="[
              'py-2 px-3 rounded-xl transition flex items-center justify-center space-x-1.5 cursor-pointer',
              assignmentForm.submission_format === 'quiz' 
                ? 'bg-white text-emerald-950 shadow-xs' 
                : 'text-slate-600 hover:text-slate-900'
            ]"
          >
            <ListChecks class="w-3.5 h-3.5 text-emerald-700" />
            <span>Interactive Online Quiz / Exam</span>
          </button>
        </div>

        <form @submit.prevent="saveLmsAssignment('published')" class="space-y-4 text-xs">
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
            <label class="font-semibold text-slate-700 block mb-1">Assignment / Quiz Title <span class="text-rose-500 font-bold">*</span></label>
            <input 
              v-model="assignmentForm.title" 
              type="text" 
              :placeholder="assignmentForm.submission_format === 'quiz' ? 'e.g. Unit Quiz 1: Chemical Reactions & Stoichiometry' : 'e.g. Problem Set 1: Inverse Functions & Applications'" 
              class="w-full px-3 py-2 rounded-xl border border-slate-300 font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="font-semibold text-slate-700">Maximum Score <span class="text-rose-500 font-bold">*</span></label>
                <span v-if="assignmentForm.submission_format === 'quiz'" class="text-[10px] text-emerald-700 font-bold">
                  Auto-calculated from items
                </span>
              </div>
              <input 
                v-model.number="assignmentForm.max_score" 
                type="number" 
                min="1" 
                max="500" 
                :readonly="assignmentForm.submission_format === 'quiz'"
                class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                :class="{'bg-slate-100 text-slate-600': assignmentForm.submission_format === 'quiz'}"
              />
            </div>
            <div>
              <label class="font-semibold text-slate-700 block mb-1">Due Date & Time <span class="text-rose-500 font-bold">*</span></label>
              <input 
                v-model="assignmentForm.due_date" 
                type="datetime-local" 
                class="w-full px-3 py-2 rounded-xl border border-slate-300 font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
              />
            </div>
          </div>

          <div>
            <label class="font-semibold text-slate-700 block mb-1">Instructions / Guide (Optional for Draft)</label>
            <textarea 
              v-model="assignmentForm.instructions" 
              rows="2" 
              :placeholder="assignmentForm.submission_format === 'quiz' ? 'Provide directions for this quiz, e.g. answer all questions carefully...' : 'Provide detailed instructions, rubric, or submission requirements...'" 
              class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            ></textarea>
          </div>

          <!-- ========================================== -->
          <!-- INTERACTIVE QUESTION BUILDER (QUIZ MODE)   -->
          <!-- ========================================== -->
          <div v-if="assignmentForm.submission_format === 'quiz'" class="p-4 bg-emerald-50/40 border border-emerald-200 rounded-2xl space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-emerald-200/80 pb-3">
              <div>
                <div class="flex items-center space-x-2">
                  <h4 class="font-extrabold text-slate-900 text-sm">Question & Exam Builder</h4>
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                    {{ assignmentForm.quiz_questions.length }} Items • {{ computedTotalQuizPoints }} Total Pts
                  </span>
                </div>
                <p class="text-[11px] text-slate-500">Configure Multiple Choice, Identification, or Essay items with auto-grading.</p>
              </div>

              <!-- Add Question Buttons Group -->
              <div class="flex items-center gap-2 flex-wrap">
                <button 
                  type="button" 
                  @click="addQuizQuestion('multiple_choice')" 
                  class="px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-50 hover:bg-blue-100 text-blue-900 border border-blue-200 hover:border-blue-300 shadow-2xs transition flex items-center space-x-1.5 cursor-pointer active:scale-95"
                  title="Add multiple choice question with auto-grading"
                >
                  <ListChecks class="w-3.5 h-3.5 text-blue-600" />
                  <span>Multiple Choice</span>
                </button>
                <button 
                  type="button" 
                  @click="addQuizQuestion('identification')" 
                  class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-900 border border-emerald-200 hover:border-emerald-300 shadow-2xs transition flex items-center space-x-1.5 cursor-pointer active:scale-95"
                  title="Add identification question with text answer key"
                >
                  <Sparkles class="w-3.5 h-3.5 text-emerald-600" />
                  <span>Identification</span>
                </button>
                <button 
                  type="button" 
                  @click="addQuizQuestion('essay')" 
                  class="px-3 py-1.5 rounded-xl text-xs font-bold bg-purple-50 hover:bg-purple-100 text-purple-900 border border-purple-200 hover:border-purple-300 shadow-2xs transition flex items-center space-x-1.5 cursor-pointer active:scale-95"
                  title="Add open-ended essay question for manual teacher scoring"
                >
                  <FileText class="w-3.5 h-3.5 text-purple-600" />
                  <span>Essay</span>
                </button>
              </div>
            </div>

            <!-- Empty State -->
            <div v-if="!assignmentForm.quiz_questions || assignmentForm.quiz_questions.length === 0" class="py-8 text-center text-slate-400 text-xs">
              No questions added yet. Click "+ Multiple Choice", "+ Identification", or "+ Essay" above.
            </div>

            <!-- Questions Items List -->
            <div class="space-y-3.5 max-h-96 overflow-y-auto pr-1">
              <div 
                v-for="(q, qIdx) in assignmentForm.quiz_questions" 
                :key="q.id"
                class="p-4 bg-white border border-slate-200 rounded-2xl shadow-2xs space-y-3 relative group"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-full bg-slate-900 text-white text-[11px] font-bold flex items-center justify-center font-mono">
                      {{ qIdx + 1 }}
                    </span>
                    <span 
                      class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                      :class="{
                        'bg-blue-50 text-blue-800 border border-blue-200': q.type === 'multiple_choice',
                        'bg-amber-50 text-amber-800 border border-amber-200': q.type === 'identification',
                        'bg-purple-50 text-purple-800 border border-purple-200': q.type === 'essay'
                      }"
                    >
                      {{ q.type === 'multiple_choice' ? 'Multiple Choice' : (q.type === 'identification' ? 'Identification' : 'Essay') }}
                    </span>
                  </div>

                  <div class="flex items-center space-x-2">
                    <div class="flex items-center space-x-1 text-slate-600 font-semibold text-[11px]">
                      <span>Points:</span>
                      <input 
                        v-model.number="q.points" 
                        type="number" 
                        min="1" 
                        max="100" 
                        class="w-14 px-2 py-0.5 rounded-lg border border-slate-300 font-mono font-bold text-center text-xs focus:ring-1 focus:ring-emerald-500 focus:outline-none"
                      />
                    </div>
                    <button 
                      type="button" 
                      @click="removeQuizQuestion(qIdx)" 
                      class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer"
                      title="Remove Question"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>

                <!-- Prompt -->
                <div>
                  <label class="block font-semibold text-slate-700 mb-1 text-[11px]">Question Prompt *</label>
                  <textarea 
                    v-model="q.question" 
                    rows="2" 
                    :placeholder="`Enter question #${qIdx + 1} prompt or instruction...`"
                    required 
                    class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                  ></textarea>
                </div>

                <!-- 1. MULTIPLE CHOICE -->
                <div v-if="q.type === 'multiple_choice'" class="space-y-2 pt-1 border-t border-slate-100">
                  <div class="flex items-center justify-between">
                    <label class="block font-semibold text-slate-700 text-[11px]">
                      Choices & Answer Key <span class="text-slate-400 font-normal">(Click radio to set correct answer)</span>
                    </label>
                    <span class="text-[10px] text-emerald-700 font-semibold">Auto-graded</span>
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div 
                      v-for="(opt, optIdx) in q.options" 
                      :key="optIdx"
                      :class="[
                        'flex items-center space-x-2 p-2 rounded-xl border transition',
                        q.correct_answer === q.options[optIdx] 
                          ? 'border-emerald-500 bg-emerald-50/70 text-emerald-950 font-semibold' 
                          : 'border-slate-200 bg-slate-50/50'
                      ]"
                    >
                      <input 
                        type="radio" 
                        :name="`q_correct_${q.id}`" 
                        :value="q.options[optIdx]" 
                        :checked="q.correct_answer === q.options[optIdx]"
                        @change="q.correct_answer = q.options[optIdx]"
                        class="text-emerald-600 focus:ring-emerald-500 shrink-0 cursor-pointer" 
                      />
                      <span class="font-mono font-bold text-slate-500 text-[11px] shrink-0">
                        {{ ['A', 'B', 'C', 'D'][optIdx] || (optIdx + 1) }}.
                      </span>
                      <input 
                        v-model="q.options[optIdx]" 
                        type="text" 
                        :placeholder="`Choice ${['A', 'B', 'C', 'D'][optIdx] || (optIdx + 1)}`"
                        required
                        class="w-full bg-transparent border-0 p-0 text-xs focus:ring-0 focus:outline-none"
                      />
                    </div>
                  </div>
                </div>

                <!-- 2. IDENTIFICATION -->
                <div v-else-if="q.type === 'identification'" class="space-y-1.5 pt-1 border-t border-slate-100">
                  <div class="flex items-center justify-between">
                    <label class="block font-semibold text-slate-700 text-[11px]">Exact Answer Key *</label>
                    <span class="text-[10px] text-emerald-700 font-semibold">Auto-graded (case-insensitive & trimmed)</span>
                  </div>
                  <input 
                    v-model="q.correct_answer" 
                    type="text" 
                    placeholder="e.g. Mitochondria, Photosynthesis, Douglas MacArthur..."
                    required 
                    class="w-full px-3 py-2 rounded-xl border border-emerald-300 bg-emerald-50/30 text-xs font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                  />
                </div>

                <!-- 3. ESSAY -->
                <div v-else-if="q.type === 'essay'" class="space-y-1.5 pt-1 border-t border-slate-100">
                  <div class="flex items-center justify-between">
                    <label class="block font-semibold text-slate-700 text-[11px]">Teacher Scoring Guide / Rubric (Optional)</label>
                    <span class="text-[10px] text-purple-700 font-semibold">Manual Teacher Grading</span>
                  </div>
                  <textarea 
                    v-model="q.rubric_guide" 
                    rows="2" 
                    placeholder="Guidelines or key concepts the student should include in their essay response..." 
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                  ></textarea>
                </div>
              </div>
            </div>
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

          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-3 border-t border-slate-100">
            <button 
              @click="showCreateAssignmentModal = false" 
              type="button" 
              class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition cursor-pointer order-last sm:order-first"
            >
              Cancel
            </button>

            <div class="flex items-center space-x-2 justify-end">
              <!-- Save as Draft Button -->
              <button 
                type="button"
                @click="saveLmsAssignment('draft')"
                :disabled="isLoadingLms"
                class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-700 transition flex items-center space-x-1.5 cursor-pointer disabled:opacity-50"
                title="Save progress as draft without publishing to learners"
              >
                <Clock class="w-3.5 h-3.5 text-slate-500" />
                <span>Save as Draft</span>
              </button>

              <!-- Publish Button -->
              <button 
                type="button" 
                @click="saveLmsAssignment('published')"
                :disabled="isLoadingLms" 
                class="px-5 py-2 rounded-xl text-xs font-bold bg-emerald-800 hover:bg-emerald-700 disabled:opacity-50 text-white shadow-xs transition flex items-center space-x-1.5 cursor-pointer"
              >
                <Send class="w-3.5 h-3.5" />
                <span>{{ isLoadingLms ? 'Publishing...' : (assignmentForm.submission_format === 'quiz' ? 'Publish Quiz' : 'Publish Task') }}</span>
              </button>
            </div>
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
                  <!-- Quiz submission inspector button -->
                  <div v-if="s.quiz_answers" class="space-y-1">
                    <button 
                      type="button" 
                      @click="openQuizInspectionModal(activeGradingAssignment, s)"
                      class="px-2.5 py-1 rounded-xl text-[11px] font-bold shadow-2xs transition flex items-center space-x-1 cursor-pointer"
                      :class="hasEssayQuestions(s.quiz_answers) && s.submission_status !== 'Graded' ? 'bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 ring-2 ring-amber-400/20' : 'bg-violet-50 hover:bg-violet-100 text-violet-800 border border-violet-200'"
                    >
                      <ListChecks class="w-3 h-3" :class="hasEssayQuestions(s.quiz_answers) && s.submission_status !== 'Graded' ? 'text-amber-700' : 'text-violet-700'" />
                      <span>{{ hasEssayQuestions(s.quiz_answers) ? (s.submission_status === 'Graded' ? 'Review & Edit Scores' : 'Grade Essays & Review') : `Review Quiz (${s.score !== null && s.score !== undefined ? s.score : (s.auto_graded_score ?? '--')} pts)` }}</span>
                    </button>
                    <div v-if="hasEssayQuestions(s.quiz_answers) && s.submission_status !== 'Graded'" class="text-[10px] text-amber-700 font-bold flex items-center space-x-1">
                      <span>⏳ Essays Pending Evaluation</span>
                    </div>
                    <div v-else class="text-[10px] text-slate-400">
                      {{ typeof s.quiz_answers === 'object' ? Object.keys(s.quiz_answers).length : '--' }} questions evaluated
                    </div>
                  </div>

                  <div v-else-if="s.submission_file" class="flex items-center space-x-1.5">
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
                  <div v-if="!s.quiz_answers && s.submission_text" class="text-[11px] text-slate-600 italic line-clamp-2 mt-0.5">
                    "{{ s.submission_text }}"
                  </div>
                  <span v-if="!s.quiz_answers && !s.submission_file && !s.submission_text" class="text-slate-400 text-[11px]">
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

    <!-- ======================================================== -->
    <!-- LMS MODAL 4: INSPECT STUDENT QUIZ SUBMISSION              -->
    <!-- ======================================================== -->
    <div v-if="quizInspectionModal.isOpen" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] flex flex-col p-6 shadow-2xl space-y-4 border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 shrink-0">
          <div>
            <div class="flex items-center space-x-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-violet-50 text-violet-800 border border-violet-200">
                Interactive Quiz Responses
              </span>
              <h3 class="font-bold text-slate-900 text-base">
                {{ quizInspectionModal.submission?.last_name }}, {{ quizInspectionModal.submission?.first_name }}
              </h3>
            </div>
            <p class="text-xs text-slate-500 mt-1 flex items-center flex-wrap gap-2">
              <span>{{ quizInspectionModal.assignment?.title }}</span>
              <span>•</span>
              <span>Auto-Scored Objectives: <strong class="text-slate-700 font-mono">{{ quizInspectionModal.submission?.auto_graded_score !== null && quizInspectionModal.submission?.auto_graded_score !== undefined ? quizInspectionModal.submission?.auto_graded_score : '--' }} pts</strong></span>
              <span v-if="hasEssayQuestions(quizInspectionModal.submission?.quiz_answers)" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                📝 Contains Essay Questions
              </span>
            </p>
          </div>
          <button @click="quizInspectionModal.isOpen = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">✕</button>
        </div>

        <!-- Questions & Student Answers List -->
        <div class="overflow-y-auto flex-1 space-y-3.5 pr-1 text-xs">
          <div 
            v-for="(ans, qid, idx) in quizInspectionModal.submission?.quiz_answers" 
            :key="qid"
            class="p-4 rounded-2xl border space-y-2.5"
            :class="[
              ans.type === 'essay' 
                ? 'border-purple-200 bg-purple-50/20' 
                : (ans.is_correct ? 'border-emerald-200 bg-emerald-50/20' : 'border-rose-200 bg-rose-50/20')
            ]"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <span class="font-bold text-slate-900">#{{ idx + 1 }}.</span>
                <span 
                  class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                  :class="{
                    'bg-blue-50 text-blue-800 border border-blue-200': ans.type === 'multiple_choice',
                    'bg-amber-50 text-amber-800 border border-amber-200': ans.type === 'identification',
                    'bg-purple-50 text-purple-800 border border-purple-200': ans.type === 'essay'
                  }"
                >
                  {{ ans.type === 'multiple_choice' ? 'Multiple Choice' : (ans.type === 'identification' ? 'Identification' : 'Essay') }}
                </span>
              </div>

              <!-- Score / Correctness Badge / Essay Points Evaluator -->
              <div>
                <span 
                  v-if="ans.type !== 'essay'"
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                  :class="ans.is_correct ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                >
                  {{ ans.is_correct ? `✓ Correct (+${ans.points_earned} pts)` : `✕ Incorrect (0 / ${ans.points_possible} pts)` }}
                </span>
                <div v-else class="flex items-center space-x-1.5 bg-purple-100 px-2.5 py-1 rounded-xl border border-purple-300">
                  <span class="text-[10px] text-purple-900 font-bold uppercase tracking-wider">Score Essay:</span>
                  <input 
                    v-model.number="ans.points_earned" 
                    @input="recalcInspectionTotalScore()" 
                    type="number" 
                    min="0" 
                    :max="ans.points_possible" 
                    step="0.5" 
                    class="w-16 px-1.5 py-0.5 rounded-lg border border-purple-300 bg-white font-mono font-bold text-center text-xs text-purple-900 focus:ring-2 focus:ring-purple-500 focus:outline-none"
                  />
                  <span class="text-[11px] font-bold text-purple-900">/ {{ ans.points_possible }} pts</span>
                </div>
              </div>
            </div>

            <p class="font-semibold text-slate-900 text-xs">{{ ans.question }}</p>

            <!-- Student Answer Display -->
            <div class="p-2.5 rounded-xl bg-white border border-slate-200 space-y-1">
              <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Learner's Submitted Answer:</div>
              <div class="font-medium text-slate-800 text-xs whitespace-pre-wrap">
                {{ ans.student_answer || '(No answer provided)' }}
              </div>
            </div>

            <!-- Rubric Guide if teacher provided one -->
            <div v-if="ans.type === 'essay' && ans.rubric_guide" class="p-2 rounded-xl bg-purple-50/70 border border-purple-200 text-[11px] text-purple-900 leading-relaxed">
              <strong class="font-bold">Grading Rubric / Criteria:</strong> {{ ans.rubric_guide }}
            </div>

            <!-- Correct Answer Key Display for Teacher -->
            <div v-if="ans.type !== 'essay'" class="text-[11px] text-emerald-800 font-medium flex items-center space-x-1">
              <span>Correct Key:</span>
              <strong class="font-bold underline">{{ ans.correct_answer }}</strong>
            </div>
          </div>
        </div>

        <!-- Quick Grade Adjust & Release Footer -->
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between shrink-0 flex-wrap gap-2">
          <div class="flex items-center space-x-2">
            <label class="font-bold text-slate-700 text-xs">Final Grade:</label>
            <div class="flex items-center space-x-1.5">
              <input 
                v-model.number="quizInspectionModal.submission.score" 
                type="number" 
                min="0" 
                :max="quizInspectionModal.assignment?.max_score" 
                step="0.5" 
                class="w-20 px-2 py-1 rounded-lg border border-slate-300 font-mono font-bold text-center text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
              />
              <span class="text-xs text-slate-500 font-medium">/ {{ quizInspectionModal.assignment?.max_score }} Pts</span>
            </div>
            <button 
              type="button" 
              @click="saveGradeForSubmission(quizInspectionModal.submission)" 
              class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-700 hover:bg-emerald-600 text-white shadow-2xs transition cursor-pointer"
            >
              Release & Save Grade
            </button>
          </div>

          <button 
            type="button" 
            @click="quizInspectionModal.isOpen = false" 
            class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer"
          >
            Close
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

    <!-- ======================================================== -->
    <!-- LMS MODAL: DOCUMENT PREVIEW (PDF / WORD / IMAGE / FILES) -->
    <!-- ======================================================== -->
    <div v-if="previewDocModal" class="no-print fixed inset-0 z-[60] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-4xl w-full max-h-[92vh] flex flex-col shadow-2xl overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
        <!-- Header -->
        <div class="p-4 sm:px-6 border-b border-slate-200 flex items-center justify-between bg-slate-900 text-white">
          <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-500/20 border border-emerald-400/30 flex items-center justify-center text-emerald-400 font-bold">
              <FileText class="w-5 h-5" />
            </div>
            <div>
              <h3 class="font-bold text-sm text-white line-clamp-1">{{ previewDocModal.title }}</h3>
              <p class="text-[11px] text-slate-400 font-mono line-clamp-1">{{ previewDocModal.file_path }}</p>
            </div>
          </div>
          <div class="flex items-center space-x-2 shrink-0">
            <a 
              :href="getFileUrl(previewDocModal.file_path)" 
              target="_blank" 
              download
              class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold flex items-center space-x-1.5 shadow-sm transition"
            >
              <Download class="w-3.5 h-3.5" />
              <span>Download</span>
            </a>
            <button 
              @click="previewDocModal = null" 
              class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center font-bold transition cursor-pointer"
            >
              ✕
            </button>
          </div>
        </div>

        <!-- Body -->
        <div class="flex-1 p-4 sm:p-6 overflow-y-auto bg-slate-100 flex items-center justify-center min-h-[420px]">
          <!-- PDF Document Embed -->
          <iframe 
            v-if="isPdf(previewDocModal.file_path, previewDocModal.title)" 
            :src="getFileUrl(previewDocModal.file_path)" 
            class="w-full h-[70vh] rounded-xl border border-slate-300 bg-white shadow-inner"
          ></iframe>

          <!-- In-Browser Word Document (.docx) Viewer -->
          <div v-else-if="isWord(previewDocModal.file_path, previewDocModal.title)" class="w-full flex flex-col items-center">
            <!-- Loading indicator while rendering -->
            <div v-if="isRenderingDocx" class="p-12 text-center space-y-3">
              <Loader2 class="w-8 h-8 animate-spin text-emerald-600 mx-auto" />
              <p class="text-xs text-slate-500 font-medium">Rendering Word document preview...</p>
            </div>

            <!-- Fallback error if docx cannot be rendered inline (e.g. legacy binary .doc) -->
            <div v-else-if="docxRenderError" class="p-8 text-center space-y-4 max-w-md bg-white rounded-3xl border border-slate-200 shadow-sm animate-in fade-in zoom-in-95">
              <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center mx-auto shadow-inner">
                <FileText class="w-8 h-8 text-emerald-600" />
              </div>
              <div>
                <div class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-900 mb-1.5 border border-emerald-200">
                  Microsoft Word Document
                </div>
                <h4 class="font-bold text-slate-800 text-sm truncate max-w-xs mx-auto">{{ previewDocModal.title || 'document.docx' }}</h4>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                  This Word file format requires download to view on your device.
                </p>
              </div>
              <a 
                :href="getFileUrl(previewDocModal.file_path)" 
                target="_blank" 
                download
                class="inline-flex items-center space-x-2 px-6 py-2.5 rounded-xl font-bold bg-emerald-700 hover:bg-emerald-600 text-white text-xs shadow-md transition cursor-pointer"
              >
                <Download class="w-4 h-4" />
                <span>Download & Open File</span>
              </a>
            </div>

            <!-- Live Rendered Docx Document Pages -->
            <div 
              v-show="!isRenderingDocx && !docxRenderError" 
              ref="docxContainerRef" 
              class="w-full max-h-[75vh] overflow-y-auto bg-slate-200/80 p-4 sm:p-6 rounded-2xl border border-slate-300 shadow-inner flex flex-col items-center"
            ></div>
          </div>

          <!-- Image Preview -->
          <div v-else-if="isImage(previewDocModal.file_path, previewDocModal.title)" class="max-h-[70vh] overflow-auto flex items-center justify-center">
            <img 
              :src="getFileUrl(previewDocModal.file_path)" 
              :alt="previewDocModal.title" 
              class="max-w-full max-h-[68vh] rounded-xl shadow-lg object-contain border border-slate-300 bg-white"
            />
          </div>

          <!-- Generic Binary / ZIP / PPT Resource Card -->
          <div v-else class="p-8 text-center space-y-4 max-w-md bg-white rounded-3xl border border-slate-200 shadow-sm">
            <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center mx-auto shadow-inner">
              <Paperclip class="w-8 h-8 text-amber-600" />
            </div>
            <div>
              <div class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-900 mb-1.5 border border-amber-200">
                Learning Handout Resource
              </div>
              <h4 class="font-bold text-slate-800 text-sm truncate max-w-xs mx-auto">{{ previewDocModal.title }}</h4>
              <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                Click below to download this material directly to your device.
              </p>
            </div>
            <a 
              :href="getFileUrl(previewDocModal.file_path)" 
              target="_blank" 
              download
              class="inline-flex items-center space-x-2 px-6 py-2.5 rounded-xl font-bold bg-emerald-700 hover:bg-emerald-600 text-white text-xs shadow-md transition cursor-pointer"
            >
              <Download class="w-4 h-4" />
              <span>Download Handout ({{ previewDocModal.file_size_kb || 0 }} KB)</span>
            </a>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { 
  GraduationCap, Clock, BookOpen, Users, Award, Calendar, 
  FileSpreadsheet, CheckCircle, Search, Check, RefreshCw, 
  Sparkles, AlertCircle, AlertTriangle, UploadCloud, FileText, CheckCircle2,
  MessageSquare, Paperclip, ExternalLink, Trash2, Pin, Clock3,
  Send, Plus, Download, Eye, Layers, Lock, ListChecks, HelpCircle, XCircle, Loader2
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
  submission_format: 'standard', // 'standard' | 'quiz'
  max_score: 50,
  due_date: '',
  quiz_questions: []
});

const quizInspectionModal = ref({
  isOpen: false,
  assignment: null,
  submission: null
});

const hasEssayQuestions = (answers) => {
  if (!answers) return false;
  let ansObj = answers;
  if (typeof ansObj === 'string') {
    try { ansObj = JSON.parse(ansObj); } catch(e) { return false; }
  }
  return Object.values(ansObj).some(a => a && a.type === 'essay');
};

const recalcInspectionTotalScore = () => {
  if (!quizInspectionModal.value.submission || !quizInspectionModal.value.submission.quiz_answers) return;
  let total = 0;
  const answers = quizInspectionModal.value.submission.quiz_answers;
  for (const qid in answers) {
    const ans = answers[qid];
    if (ans.points_earned !== null && ans.points_earned !== undefined && ans.points_earned !== '') {
      total += Math.max(0, parseFloat(ans.points_earned) || 0);
    }
  }
  quizInspectionModal.value.submission.score = Math.round(total * 10) / 10;
};

const openQuizInspectionModal = (assignment, submission) => {
  let answers = submission.quiz_answers;
  if (typeof answers === 'string') {
    try { answers = JSON.parse(answers); } catch(e) {}
  }
  submission.quiz_answers = answers;

  // If score is null or empty, calculate current points from quiz_answers (objective points + any essay points already awarded)
  if (submission.score === null || submission.score === undefined || submission.score === '') {
    let currentTotal = 0;
    if (answers) {
      for (const qid in answers) {
        const q = answers[qid];
        if (q.points_earned !== null && q.points_earned !== undefined && q.points_earned !== '') {
          currentTotal += parseFloat(q.points_earned) || 0;
        }
      }
    }
    submission.score = Math.round(currentTotal * 10) / 10;
  }

  quizInspectionModal.value = {
    isOpen: true,
    assignment,
    submission
  };
};

const addQuizQuestion = (type) => {
  const newId = 'q_' + Date.now() + '_' + Math.random().toString(36).substring(2, 6);
  if (!assignmentForm.value.quiz_questions) {
    assignmentForm.value.quiz_questions = [];
  }
  if (type === 'multiple_choice') {
    assignmentForm.value.quiz_questions.push({
      id: newId,
      type: 'multiple_choice',
      question: '',
      points: 1,
      options: ['Option A', 'Option B', 'Option C', 'Option D'],
      correct_answer: 'Option A'
    });
  } else if (type === 'identification') {
    assignmentForm.value.quiz_questions.push({
      id: newId,
      type: 'identification',
      question: '',
      points: 1,
      correct_answer: ''
    });
  } else if (type === 'essay') {
    assignmentForm.value.quiz_questions.push({
      id: newId,
      type: 'essay',
      question: '',
      points: 5,
      rubric_guide: ''
    });
  }
};

const removeQuizQuestion = (index) => {
  assignmentForm.value.quiz_questions.splice(index, 1);
};

const computedTotalQuizPoints = computed(() => {
  if (assignmentForm.value.submission_format !== 'quiz') return assignmentForm.value.max_score || 50;
  if (!assignmentForm.value.quiz_questions || assignmentForm.value.quiz_questions.length === 0) return 0;
  return assignmentForm.value.quiz_questions.reduce((sum, q) => sum + (Number(q.points) || 1), 0);
});

watch(computedTotalQuizPoints, (newTotal) => {
  if (assignmentForm.value.submission_format === 'quiz' && newTotal > 0) {
    assignmentForm.value.max_score = newTotal;
  }
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


const MAX_MODULE_SIZE_BYTES = 15 * 1024 * 1024; // 15MB

const handleModuleFileSelect = (e) => {
  const file = e.target.files[0];
  if (!file) return;
  if (file.size > MAX_MODULE_SIZE_BYTES) {
    errorMessage.value = `File size (${(file.size / (1024 * 1024)).toFixed(1)}MB) exceeds the maximum allowed limit of 15MB. Please upload a smaller file.`;
    e.target.value = '';
    moduleForm.value.file = null;
    return;
  }
  moduleForm.value.file = file;
  errorMessage.value = '';
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


const openCreateAssignmentModal = () => {
  resetDistributionTargets();
  assignmentForm.value = {
    id: null,
    quarter: '1st Quarter',
    title: '',
    instructions: '',
    task_type: 'Written Work',
    submission_format: 'standard',
    max_score: 50,
    due_date: '',
    quiz_questions: []
  };
  showCreateAssignmentModal.value = true;
};

const editDraftAssignment = (asg) => {
  resetDistributionTargets();
  let parsedQuestions = [];
  if (asg.quiz_questions) {
    try {
      parsedQuestions = typeof asg.quiz_questions === 'string' ? JSON.parse(asg.quiz_questions) : JSON.parse(JSON.stringify(asg.quiz_questions));
    } catch (e) {
      parsedQuestions = [];
    }
  }

  // Format due_date for datetime-local input (YYYY-MM-DDTHH:mm)
  let formattedDueDate = '';
  if (asg.due_date) {
    const d = new Date(asg.due_date);
    if (!isNaN(d.getTime())) {
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      const hours = String(d.getHours()).padStart(2, '0');
      const minutes = String(d.getMinutes()).padStart(2, '0');
      formattedDueDate = `${year}-${month}-${day}T${hours}:${minutes}`;
    }
  }

  assignmentForm.value = {
    id: asg.id,
    quarter: asg.quarter || '1st Quarter',
    title: asg.title || '',
    instructions: asg.instructions || '',
    task_type: asg.task_type || 'Written Work',
    submission_format: asg.submission_format || 'standard',
    max_score: asg.max_score || 50,
    due_date: formattedDueDate,
    quiz_questions: parsedQuestions
  };
  showCreateAssignmentModal.value = true;
};

const publishDraftAssignment = async (asg) => {
  if (isCurrentClassArchived.value) {
    errorMessage.value = 'Cannot publish: this class belongs to an archived academic semester.';
    return;
  }
  isLoadingLms.value = true;
  try {
    const res = await api.publishLmsAssignment({ assignment_id: asg.id });
    feedbackMessage.value = res.message || 'Draft task has been published and is now visible to students!';
    await loadLmsContent();
    setTimeout(() => { feedbackMessage.value = ''; }, 3500);
  } catch (err) {
    errorMessage.value = 'Failed to publish draft: ' + err.message;
  } finally {
    isLoadingLms.value = false;
  }
};

const saveLmsAssignment = async (targetStatus = 'published') => {
  if (!selectedClassKey.value) return;
  if (isCurrentClassArchived.value) {
    errorMessage.value = 'Cannot create assignment: this class belongs to an archived academic semester.';
    return;
  }
  const [secId, subId] = selectedClassKey.value.split('-').map(Number);
  
  if (targetStatus === 'published') {
    if (!assignmentForm.value.title || !assignmentForm.value.due_date) {
      errorMessage.value = 'Please provide an Assignment Title and Due Date before publishing to students.';
      return;
    }

    // Strict Quiz-specific validation when publishing
    if (assignmentForm.value.submission_format === 'quiz') {
      const questions = assignmentForm.value.quiz_questions || [];
      if (questions.length === 0) {
        errorMessage.value = 'Please add at least one question to the interactive quiz before publishing.';
        return;
      }
      for (let i = 0; i < questions.length; i++) {
        const q = questions[i];
        if (!q.question || !q.question.trim()) {
          errorMessage.value = `Question #${i + 1} is missing a question prompt.`;
          return;
        }
        if (q.type === 'multiple_choice') {
          if (!q.options || q.options.some(opt => !opt || !opt.trim())) {
            errorMessage.value = `Question #${i + 1} (Multiple Choice) has empty choices.`;
            return;
          }
          if (!q.correct_answer || !q.correct_answer.trim()) {
            errorMessage.value = `Please designate the correct answer key for Question #${i + 1}.`;
            return;
          }
        } else if (q.type === 'identification') {
          if (!q.correct_answer || !q.correct_answer.trim()) {
            errorMessage.value = `Please provide the answer key for Question #${i + 1} (Identification).`;
            return;
          }
        }
      }
    }
  } else {
    // DRAFT MODE: Allow saving even if incomplete! Provide friendly fallback if title is empty
    if (!assignmentForm.value.title || !assignmentForm.value.title.trim()) {
      assignmentForm.value.title = 'Untitled Draft Task';
    }
  }

  isLoadingLms.value = true;
  try {
    const res = await api.saveLmsAssignment({
      section_id: secId,
      subject_id: subId,
      ...assignmentForm.value,
      status: targetStatus,
      target_sections: assignmentTargetKeys.value
    });
    feedbackMessage.value = res.message || (targetStatus === 'draft' ? 'Task saved as draft (hidden from learners).' : 'Assignment published to class.');
    showCreateAssignmentModal.value = false;
    assignmentForm.value = { 
      id: null,
      quarter: '1st Quarter', 
      title: '', 
      instructions: '', 
      task_type: 'Written Work', 
      submission_format: 'standard',
      max_score: 50, 
      due_date: '',
      quiz_questions: []
    };
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
      teacher_feedback: student.teacher_feedback || '',
      quiz_answers: student.quiz_answers || null
    });
    student.submission_status = 'Graded';
    feedbackMessage.value = res.message || 'Grade recorded successfully.';
    setTimeout(() => { feedbackMessage.value = ''; }, 3500);
  } catch (err) {
    errorMessage.value = 'Failed to save grade: ' + err.message;
  }
};

const previewDocModal = ref(null);
const docxContainerRef = ref(null);
const isRenderingDocx = ref(false);
const docxRenderError = ref('');

const isPdf = (filePath, title = '') => {
  const combined = ((filePath || '') + ' ' + (title || '')).toLowerCase();
  return combined.includes('.pdf');
};

const isWord = (filePath, title = '') => {
  const combined = ((filePath || '') + ' ' + (title || '')).toLowerCase();
  return combined.includes('.docx') || combined.includes('.doc');
};

const isImage = (filePath, title = '') => {
  const combined = ((filePath || '') + ' ' + (title || '')).toLowerCase();
  return combined.includes('.jpg') || combined.includes('.jpeg') || combined.includes('.png') || combined.includes('.webp') || combined.includes('.gif');
};

const openModulePreview = async (m) => {
  if (!m || !m.file_path) return;
  previewDocModal.value = {
    title: m.title || 'Learning Handout',
    file_path: m.file_path,
    file_size_kb: m.file_size_kb || 0
  };
  docxRenderError.value = '';

  if (isWord(m.file_path, m.title)) {
    isRenderingDocx.value = true;
    await nextTick();
    try {
      const url = getFileUrl(m.file_path);
      const res = await fetch(url);
      if (!res.ok) throw new Error('Failed to fetch document file from server.');
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

onMounted(() => {
  loadTeacherDashboard();
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
  border-radius: 8px !important;
  background: #ffffff !important;
  color: #111827 !important;
  box-sizing: border-box !important;
  max-width: 100% !important;
}
:deep(.docx-wrapper article) {
  font-family: Calibri, 'Segoe UI', Arial, sans-serif !important;
}
</style>
