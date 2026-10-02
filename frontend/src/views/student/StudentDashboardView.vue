<template>
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- TOP WELCOME & SECTION BADGE HEADER -->
    <div class="no-print bg-white rounded-2xl p-6 sm:p-7 border border-slate-200 shadow-2xs flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
      <div>
        <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-blue-50 text-blue-900 border border-blue-200 text-xs font-semibold uppercase tracking-wider mb-2.5">
          <Sparkles class="w-3.5 h-3.5 text-blue-700" />
          <span>Student Portal • {{ dashboardData.enrollment?.school_year_name || 'SY 2026-2027' }}</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
          Welcome, {{ studentDisplayName }}!
        </h1>
        <p class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-2">
          <span>Student ID: <strong class="text-blue-900 font-mono font-bold">{{ studentDisplayId }}</strong></span>
          <span class="text-slate-300">•</span>
          <span>LRN: <strong class="text-slate-700 font-mono">{{ studentDisplayLrn }}</strong></span>
          <span v-if="dashboardData.enrollment?.enrollment_no" class="text-slate-300">•</span>
          <span v-if="dashboardData.enrollment?.enrollment_no">Enr Ref: <strong class="text-slate-600 font-mono">{{ dashboardData.enrollment.enrollment_no }}</strong></span>
        </p>
      </div>

      <!-- ASSIGNED SECTION & CLASSROOM BADGE -->
      <div class="text-left md:text-right bg-slate-50 p-4 sm:p-4.5 rounded-xl border border-slate-200 w-full md:w-auto min-w-0 md:min-w-[240px] shrink-0">
        <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider mb-0.5 flex items-center md:justify-end space-x-1">
          <Layers class="w-3.5 h-3.5 text-blue-700" />
          <span>Assigned Class Section</span>
        </div>
        <div class="text-base font-bold text-slate-900">
          {{ dashboardData.enrollment?.section_name || 'Class Section Pending' }}
        </div>
        <div class="text-xs text-blue-900 font-semibold mt-0.5">
          {{ dashboardData.enrollment?.grade_level_name || 'Grade Level' }}
          <span v-if="dashboardData.enrollment?.strand_code"> • {{ dashboardData.enrollment.strand_code }}</span>
        </div>
        <div class="text-[11px] text-slate-500 mt-1 flex items-center md:justify-end space-x-1">
          <MapPin class="w-3.5 h-3.5 text-slate-400 shrink-0" />
          <span>{{ dashboardData.enrollment?.section_room || 'Designated Homeroom' }}</span>
        </div>
      </div>
    </div>

    <!-- Feedback & Payment Alerts -->
    <div v-if="paymongoVerifyError" class="no-print p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center justify-between shadow-xs">
      <div class="flex items-center space-x-2">
        <AlertCircle class="w-4 h-4 text-rose-600 shrink-0" />
        <span>{{ paymongoVerifyError }}</span>
      </div>
      <button @click="paymongoVerifyError = ''" class="text-rose-500 hover:text-rose-700 font-bold cursor-pointer">✕</button>
    </div>

    <!-- MAIN DASHBOARD CONTENT GRID -->
    <div v-if="activeTab !== 'lms' && activeTab !== 'events'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN: ENROLLED SUBJECTS & TIMETABLE (Expands to 3 cols on timetable mode) -->
      <div 
        v-show="activeTab === 'all' || activeTab === 'schedule'" 
        class="space-y-6" 
        :class="(activeTab === 'schedule' || scheduleViewMode === 'timetable') ? 'lg:col-span-3' : 'lg:col-span-2'"
      >
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5">
          <!-- SECTION HEADER & CONTROLS -->
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
              <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                <BookOpen class="w-4 h-4 text-blue-800" />
                <span>Class Schedule & Enrolled Learning Areas</span>
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">Official DepEd instructional schedule and teacher assignments for Grade 10 - Pearl</p>
            </div>

            <div class="flex items-center space-x-2 flex-wrap gap-y-2">
              <!-- Primary View Mode Toggle (Subjects List vs Weekly Timetable) -->
              <div class="flex items-center space-x-1 bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs">
                <button 
                  @click="scheduleViewMode = 'cards'" 
                  type="button" 
                  :class="scheduleViewMode === 'cards' ? 'bg-white text-blue-950 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800 font-medium'"
                  class="px-3 py-1.5 rounded-lg transition cursor-pointer flex items-center space-x-1.5"
                >
                  <List class="w-3.5 h-3.5" />
                  <span>Subjects ({{ currentSemesterSubjects.length }})</span>
                </button>
                <button 
                  @click="scheduleViewMode = 'timetable'" 
                  type="button" 
                  :class="scheduleViewMode === 'timetable' ? 'bg-white text-blue-950 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800 font-medium'"
                  class="px-3 py-1.5 rounded-lg transition cursor-pointer flex items-center space-x-1.5"
                >
                  <LayoutGrid class="w-3.5 h-3.5" />
                  <span>Weekly Timetable</span>
                </button>
              </div>

              <!-- Sub-view switcher for Timetable mode (Matrix vs Daily) -->
              <div v-if="scheduleViewMode === 'timetable'" class="flex items-center space-x-1 bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs">
                <button 
                  @click="timetableSubView = 'matrix'" 
                  type="button" 
                  :class="timetableSubView === 'matrix' ? 'bg-blue-900 text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                  class="px-2.5 py-1.5 rounded-lg transition cursor-pointer"
                >
                  Master Matrix
                </button>
                <button 
                  @click="timetableSubView = 'daily'" 
                  type="button" 
                  :class="timetableSubView === 'daily' ? 'bg-blue-900 text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                  class="px-2.5 py-1.5 rounded-lg transition cursor-pointer"
                >
                  Day by Day
                </button>
              </div>

              <!-- Semester filter if SHS -->
              <div v-if="isSHSStudent" class="flex items-center space-x-1.5">
                <span v-if="dashboardData.enrollment?.active_semester" class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                  Active: {{ dashboardData.enrollment.active_semester }}
                </span>
                <select 
                  v-model="selectedSemesterFilter" 
                  class="px-2.5 py-1.5 rounded-xl border border-slate-300 bg-white text-xs font-bold text-slate-700"
                >
                  <option value="1st Semester">1st Semester{{ dashboardData.enrollment?.active_semester === '1st Semester' ? ' (Active)' : '' }}</option>
                  <option value="2nd Semester">2nd Semester{{ dashboardData.enrollment?.active_semester === '2nd Semester' ? ' (Active)' : '' }}</option>
                  <option value="All">All Semesters</option>
                </select>
              </div>
            </div>
          </div>

          <!-- ============================================================== -->
          <!-- 1. SUBJECT SCHEDULE CARDS VIEW (Clean 8 Learning Areas)         -->
          <!-- ============================================================== -->
          <div v-if="scheduleViewMode === 'cards'" class="space-y-3.5">
            <div 
              v-for="sub in currentSemesterSubjects" 
              :key="sub.enrollment_subject_id || sub.subject_id"
              :class="[
                'p-4 sm:p-5 rounded-2xl border border-slate-200/90 bg-white transition-all duration-200 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4 text-xs group cursor-pointer',
                getSubjectTheme(sub.subject_code).accentBorder,
                hoveredSubjectCode && isSameSubject(hoveredSubjectCode, sub.subject_code)
                  ? 'ring-2 ring-slate-800 shadow-md scale-[1.005]'
                  : hoveredSubjectCode
                    ? 'opacity-70'
                    : 'hover:shadow-xs hover:border-slate-300'
              ]"
              @mouseenter="hoveredSubjectCode = sub.subject_code"
              @mouseleave="hoveredSubjectCode = null"
            >
              <div class="space-y-1.5 min-w-0 flex-1">
                <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                  <span :class="['font-extrabold font-mono px-2 py-0.5 rounded text-[11px]', getSubjectTheme(sub.subject_code).badge]">
                    {{ sub.subject_code }}
                  </span>
                  <span class="text-[10px] text-slate-500 font-bold uppercase bg-white/80 border border-slate-200 px-2 py-0.5 rounded">
                    {{ sub.subject_category || 'Core' }}
                  </span>
                  <span v-if="sub.semester" class="text-[10px] text-blue-700 font-bold bg-blue-50 px-2 py-0.5 rounded border border-blue-200/60">
                    {{ sub.semester }}
                  </span>
                  <span class="text-slate-500 text-[11px] font-mono font-medium">{{ sub.units || '1.0' }} Units</span>
                  <span v-if="sub.total_sessions > 1" class="text-[10px] text-emerald-800 font-bold bg-white px-2 py-0.5 rounded border border-emerald-300 shadow-2xs">
                    {{ sub.total_sessions }} sessions / wk
                  </span>
                </div>

                <div class="font-bold text-sm text-slate-900 leading-snug pt-0.5 group-hover:text-emerald-900 transition">
                  {{ sub.subject_title }}
                </div>

                <!-- Assigned Teacher -->
                <div class="text-[11px] text-slate-600 flex items-center space-x-1.5 pt-0.5">
                  <User class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                  <span>Teacher: <strong class="text-slate-800 font-medium">{{ sub.teacher_first }} {{ sub.teacher_last }}</strong></span>
                </div>
              </div>

              <!-- Schedule Time Slot & Room Badge -->
              <div class="text-left md:text-right bg-white p-3.5 rounded-xl border border-slate-200/90 shrink-0 shadow-2xs space-y-1 min-w-[200px]">
                <div class="font-extrabold text-slate-900 flex items-center md:justify-end space-x-1 text-xs">
                  <Clock class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                  <span>{{ sub.day_of_week || 'Mon-Fri' }}</span>
                </div>
                <div class="text-[11px] text-slate-600 font-mono font-medium">
                  {{ formatTime(sub.time_start) }} - {{ formatTime(sub.time_end) }}
                </div>
                <div class="text-[10px] text-slate-500 flex items-center md:justify-end space-x-1 pt-0.5">
                  <MapPin class="w-3 h-3 text-slate-400 shrink-0" />
                  <span class="truncate">{{ sub.room || dashboardData.enrollment?.section_room || 'Homeroom' }}</span>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-if="currentSemesterSubjects.length === 0" class="text-center py-10 text-slate-400 text-xs bg-slate-50 rounded-2xl border border-dashed border-slate-200">
              <BookOpen class="w-8 h-8 text-slate-300 mx-auto mb-2 stroke-[1.5]" />
              <p class="font-medium text-slate-600">No enrolled subjects found for this selection.</p>
              <p class="text-[11px] text-slate-400 mt-0.5">Please consult the Registrar or Section Adviser if your schedule is not listed.</p>
            </div>
          </div>

          <!-- ============================================================== -->
          <!-- 2. WEEKLY BELL TIMETABLE: MASTER SYNCHRONIZED MATRIX           -->
          <!-- ============================================================== -->
          <div v-if="scheduleViewMode === 'timetable' && timetableSubView === 'matrix'" class="space-y-4">
            <div class="sm:hidden flex items-center justify-between px-3 py-1.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 text-[11px] font-medium">
              <span>👉 Swipe table horizontally to view all days</span>
              <button @click="timetableSubView = 'daily'" class="font-bold underline text-blue-950">Switch to Daily</button>
            </div>
            <div class="overflow-x-auto rounded-2xl border border-slate-200/90 shadow-2xs bg-white">
              <table class="w-full text-left border-collapse min-w-[940px]">
                <thead>
                  <tr class="bg-slate-900 text-white text-xs">
                    <th class="py-3.5 px-3 font-bold tracking-wider uppercase w-[150px] text-center border-r border-slate-800">
                      Bell Period
                    </th>
                    <th 
                      v-for="day in daysOfWeek" 
                      :key="day" 
                      class="py-3.5 px-3 font-bold tracking-wider uppercase text-center border-r border-slate-800 last:border-r-0"
                    >
                      <div class="font-bold text-xs tracking-wide">{{ day }}</div>
                      <div class="text-[10px] text-emerald-400 font-mono font-normal mt-0.5">6 Classes</div>
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                  <template v-for="(slot, idx) in timeSlots" :key="idx">
                    <!-- RECESS OR LUNCH BREAK ROW -->
                    <tr v-if="slot.isBreak" class="bg-slate-100/70 border-y border-slate-200/80">
                      <td colspan="6" class="py-2.5 px-4 text-center font-bold text-slate-700 text-xs tracking-wide">
                        <span class="mr-1.5">{{ slot.icon }}</span>
                        <span>{{ slot.label }}</span>
                        <span class="font-mono text-slate-500 text-[11px] ml-2 font-normal">({{ slot.time }})</span>
                      </td>
                    </tr>

                    <!-- CLASS INSTRUCTIONAL PERIOD ROW -->
                    <tr v-else class="hover:bg-slate-50/40 transition">
                      <!-- Left Column: Period Badge and Time Range -->
                      <td class="py-3 px-3 text-center bg-slate-50/80 border-r border-slate-200 shrink-0 align-middle w-[150px]">
                        <div class="font-extrabold text-slate-900 text-[11px] tracking-wide">{{ slot.label }}</div>
                        <div class="text-[10px] font-mono text-slate-500 font-medium mt-0.5">{{ slot.time }}</div>
                      </td>

                      <!-- 5 Columns: Monday through Friday -->
                      <td 
                        v-for="day in daysOfWeek" 
                        :key="day" 
                        class="p-2 border-r border-slate-100 last:border-r-0 align-middle w-[190px]"
                      >
                        <div 
                          v-if="getScheduleAt(day, slot.start)" 
                          @mouseenter="hoveredSubjectCode = getScheduleAt(day, slot.start).subject_code"
                          @mouseleave="hoveredSubjectCode = null"
                          :class="[
                            'p-2.5 rounded-xl border border-slate-200/90 bg-white transition-all duration-200 shadow-2xs space-y-1.5 cursor-pointer relative',
                            getSubjectTheme(getScheduleAt(day, slot.start).subject_code).accentBorder,
                            hoveredSubjectCode && isSameSubject(hoveredSubjectCode, getScheduleAt(day, slot.start).subject_code)
                              ? 'ring-2 ring-slate-800 shadow-md scale-[1.02] z-10'
                              : hoveredSubjectCode
                                ? 'opacity-70'
                                : 'hover:shadow-xs hover:border-slate-300'
                          ]"
                        >
                          <div class="flex items-center justify-between gap-1">
                            <span 
                              :class="getSubjectTheme(getScheduleAt(day, slot.start).subject_code).badge" 
                              class="font-mono font-bold text-[10px] px-1.5 py-0.5 rounded"
                            >
                              {{ getScheduleAt(day, slot.start).subject_code }}
                            </span>
                            <span class="text-[9px] font-semibold text-slate-500 px-1.5 py-0.5 rounded bg-slate-50 border border-slate-200/80">
                              {{ getScheduleAt(day, slot.start).room || 'Room 401' }}
                            </span>
                          </div>
                          <div class="font-bold text-xs text-slate-900 leading-snug line-clamp-2">
                            {{ getScheduleAt(day, slot.start).subject_title || getScheduleAt(day, slot.start).title }}
                          </div>
                          <div class="text-[10px] text-slate-600 flex items-center space-x-1 pt-1 border-t border-slate-100">
                            <User class="w-3 h-3 text-slate-400 shrink-0" />
                            <span class="truncate font-medium text-slate-700">
                              {{ getScheduleAt(day, slot.start).teacher_first }} {{ getScheduleAt(day, slot.start).teacher_last }}
                            </span>
                          </div>
                        </div>
                        <div v-else class="text-center py-3 text-slate-300 text-[10px] italic">
                          — Free —
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ============================================================== -->
          <!-- 3. WEEKLY BELL TIMETABLE: DAY BY DAY TIMELINE VIEW             -->
          <!-- ============================================================== -->
          <div v-if="scheduleViewMode === 'timetable' && timetableSubView === 'daily'" class="space-y-5">
            <!-- Day Pill Selector -->
            <div class="flex items-center space-x-2 overflow-x-auto pb-1">
              <button
                v-for="day in daysOfWeek"
                :key="day"
                @click="selectedDayTab = day"
                type="button"
                :class="selectedDayTab === day ? 'bg-slate-900 text-white font-bold shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium'"
                class="px-4 py-2 rounded-xl text-xs transition shrink-0 cursor-pointer flex items-center space-x-2"
              >
                <span>{{ day }}</span>
                <span class="text-[10px] font-mono px-1.5 py-0.2 rounded" :class="selectedDayTab === day ? 'bg-slate-800 text-emerald-300' : 'bg-slate-200 text-slate-600'">
                  {{ getDaySchedules(day).length }}
                </span>
              </button>
            </div>

            <!-- Timeline Cards for the Selected Day -->
            <div class="space-y-3">
              <template v-for="(s, sIdx) in getDaySchedules(selectedDayTab)" :key="s.schedule_id || s.id">
                <!-- Morning Recess Marker (between Period 2 and Period 3) -->
                <div v-if="s.time_start === '09:50:00'" class="p-3 rounded-2xl bg-amber-50/80 border border-amber-200 text-amber-900 flex items-center justify-between text-xs">
                  <div class="flex items-center space-x-2">
                    <Coffee class="w-4 h-4 text-amber-700 shrink-0" />
                    <span class="font-bold">Morning Recess & Snack Break</span>
                  </div>
                  <span class="font-mono text-amber-800 text-[11px]">09:30 AM – 09:50 AM (20 mins)</span>
                </div>

                <!-- Lunch Break Marker (between Period 4 and Period 5) -->
                <div v-if="s.time_start === '12:50:00'" class="p-3 rounded-2xl bg-amber-50/80 border border-amber-200 text-amber-900 flex items-center justify-between text-xs">
                  <div class="flex items-center space-x-2">
                    <Utensils class="w-4 h-4 text-amber-700 shrink-0" />
                    <span class="font-bold">Institutional Lunch & Noon Recess</span>
                  </div>
                  <span class="font-mono text-amber-800 text-[11px]">11:50 AM – 12:50 PM (60 mins)</span>
                </div>

                <!-- Instructional Period Card -->
                <div 
                  :class="[
                    'p-4 sm:p-5 rounded-2xl border border-slate-200/90 bg-white transition-all duration-200 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4 text-xs group cursor-pointer',
                    getSubjectTheme(s.subject_code).accentBorder,
                    hoveredSubjectCode && isSameSubject(hoveredSubjectCode, s.subject_code)
                      ? 'ring-2 ring-slate-800 shadow-md scale-[1.005]'
                      : hoveredSubjectCode
                        ? 'opacity-70'
                        : 'hover:shadow-xs hover:border-slate-300'
                  ]"
                  @mouseenter="hoveredSubjectCode = s.subject_code"
                  @mouseleave="hoveredSubjectCode = null"
                >
                  <div class="flex items-start sm:items-center space-x-4">
                    <!-- Period Number & Time Box -->
                    <div class="bg-white p-3 rounded-xl border border-slate-200 shrink-0 text-center min-w-[120px] shadow-2xs">
                      <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Period {{ sIdx + 1 }}</div>
                      <div class="font-bold font-mono text-slate-900 text-xs mt-0.5">
                        {{ formatTime(s.time_start) }}
                      </div>
                      <div class="text-[10px] font-mono text-slate-400">
                        to {{ formatTime(s.time_end) }}
                      </div>
                    </div>

                    <!-- Subject Info -->
                    <div class="space-y-1">
                      <div class="flex items-center space-x-2">
                        <span :class="['font-extrabold font-mono px-2 py-0.5 rounded text-[11px]', getSubjectTheme(s.subject_code).badge]">
                          {{ s.subject_code }}
                        </span>
                        <span class="text-[10px] text-slate-500 font-bold uppercase bg-white px-2 py-0.5 rounded border border-slate-200">
                          {{ s.subject_category || 'Core' }}
                        </span>
                      </div>
                      <div class="font-bold text-sm text-slate-900 group-hover:text-emerald-900 transition">
                        {{ s.subject_title || s.title }}
                      </div>
                      <div class="text-[11px] text-slate-600 flex items-center space-x-1.5 pt-0.5">
                        <User class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                        <span>Teacher: <strong class="text-slate-800 font-medium">{{ s.teacher_first }} {{ s.teacher_last }}</strong></span>
                      </div>
                    </div>
                  </div>

                  <!-- Room & Classroom Details -->
                  <div class="text-left md:text-right bg-white/90 p-3 rounded-xl border border-slate-200/80 shrink-0 shadow-2xs space-y-1 min-w-[160px]">
                    <div class="font-bold text-slate-900 flex items-center md:justify-end space-x-1.5">
                      <MapPin class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                      <span>{{ s.room || 'Room 401' }}</span>
                    </div>
                    <div class="text-[10px] text-slate-500">Designated Instructional Venue</div>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN: ACTIVITY FEEDS, NOTICES, SOA & DRS -->
      <div 
        class="space-y-6" 
        :class="activeTab === 'schedule' ? 'hidden' : (scheduleViewMode === 'timetable' ? 'lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-6 space-y-0' : (activeTab !== 'all' ? 'lg:col-span-3' : 'lg:col-span-1'))"
      >
        <!-- RECENT CLASSROOM ACTIVITY & TEACHER UPLOADS FEED (Visible on Overview) -->
        <div v-show="activeTab === 'all'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center space-x-2">
              <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
                <Bell class="w-4 h-4" />
              </div>
              <div>
                <h2 class="text-sm sm:text-base font-bold text-slate-900">Classroom Updates & Notices</h2>
                <p class="text-[11px] text-slate-500">Live stream of learning modules & teacher tasks.</p>
              </div>
            </div>

            <!-- Summary Pills -->
            <div class="flex items-center space-x-1">
              <span 
                v-if="dashboardData.lms_stats?.pending_assignments > 0" 
                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-900 border border-amber-300"
              >
                {{ dashboardData.lms_stats.pending_assignments }} Due
              </span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                {{ dashboardData.lms_stats?.total_modules || 0 }} Handouts
              </span>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="!dashboardData.lms_updates || dashboardData.lms_updates.length === 0" class="py-6 text-center text-slate-400 text-xs">
            <Layers class="w-7 h-7 mx-auto mb-1.5 text-slate-300" />
            No classroom announcements posted yet.
          </div>

          <!-- Updates List -->
          <div v-else class="space-y-2.5">
            <div 
              v-for="item in (dashboardData.lms_updates || []).slice(0, 5)" 
              :key="`${item.item_type}-${item.id}`"
              class="p-3.5 rounded-2xl border transition flex flex-col space-y-2 text-xs"
              :class="item.item_type === 'assignment' && !item.submission_status ? 'border-amber-200 bg-amber-50/20 hover:border-amber-400' : 'border-slate-200 bg-slate-50/50 hover:bg-white hover:border-emerald-300'"
            >
              <div class="flex items-start space-x-2.5 min-w-0">
                <!-- Type Icon Box -->
                <div 
                  class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
                  :class="[
                    item.item_type === 'announcement' ? 'bg-amber-100 text-amber-800' : '',
                    item.item_type === 'module' ? 'bg-blue-100 text-blue-800' : '',
                    item.item_type === 'assignment' ? (item.submission_status ? 'bg-emerald-100 text-emerald-800' : 'bg-purple-100 text-purple-800') : ''
                  ]"
                >
                  <Pin v-if="item.item_type === 'announcement'" class="w-3.5 h-3.5" />
                  <BookOpen v-else-if="item.item_type === 'module'" class="w-3.5 h-3.5" />
                  <FileText v-else-if="item.item_type === 'assignment'" class="w-3.5 h-3.5" />
                </div>

                <div class="space-y-1 min-w-0 flex-1">
                  <div class="flex items-center space-x-1.5 flex-wrap gap-y-0.5">
                    <!-- Subject Code Badge -->
                    <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold bg-white border border-slate-200 text-slate-800">
                      {{ item.subject_code }}
                    </span>
                    <!-- Type Pill -->
                    <span 
                      class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider"
                      :class="[
                        item.item_type === 'announcement' ? 'bg-amber-100 text-amber-900 border border-amber-200' : '',
                        item.item_type === 'module' ? 'bg-blue-100 text-blue-900 border border-blue-200' : '',
                        item.item_type === 'assignment' ? 'bg-purple-100 text-purple-900 border border-purple-200' : ''
                      ]"
                    >
                      {{ item.item_type === 'module' ? 'Handout' : (item.item_type === 'assignment' ? (item.task_type || 'Assignment') : 'Notice') }}
                    </span>
                    <!-- Due Date if assignment -->
                    <span 
                      v-if="item.due_date" 
                      class="text-[9px] font-mono flex items-center space-x-1"
                      :class="isPastDeadline(item.due_date) ? 'text-rose-600 font-bold' : 'text-slate-500'"
                    >
                      <Clock v-if="!isPastDeadline(item.due_date)" class="w-2.5 h-2.5 text-slate-400" />
                      <AlertTriangle v-else class="w-2.5 h-2.5 text-rose-500" />
                      <span>Due: {{ formatDeadline(item.due_date) }}</span>
                    </span>
                  </div>

                  <!-- Title -->
                  <div class="font-bold text-slate-900 text-xs line-clamp-1">
                    {{ item.title }}
                  </div>

                  <!-- Author & Date -->
                  <div class="text-[10px] text-slate-500 flex items-center space-x-1">
                    <span>{{ item.teacher_first ? `Prof. ${item.teacher_first} ${item.teacher_last}` : 'Instructor' }}</span>
                    <span>•</span>
                    <span>{{ new Date(item.created_at).toLocaleDateString([], { month: 'short', day: 'numeric' }) }}</span>
                  </div>
                </div>
              </div>

              <!-- Action Buttons Row -->
              <div class="flex items-center justify-end space-x-1.5 pt-1 border-t border-slate-200/50">
                <button 
                  v-if="item.file_path" 
                  type="button" 
                  @click="openModulePreview(item)" 
                  class="px-2.5 py-1 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-900 border border-blue-200 font-semibold text-[10px] transition inline-flex items-center space-x-1 cursor-pointer"
                >
                  <Eye class="w-3 h-3 text-blue-700" />
                  <span>View</span>
                </button>
                <a 
                  v-if="item.file_path" 
                  :href="getFileUrl(item.file_path)" 
                  target="_blank" 
                  download 
                  class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[10px] transition inline-flex items-center space-x-1 cursor-pointer"
                >
                  <Download class="w-3 h-3" />
                  <span>Download</span>
                </a>
                <a 
                  v-else-if="item.external_url" 
                  :href="item.external_url" 
                  target="_blank" 
                  rel="noopener noreferrer" 
                  class="px-2.5 py-1 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-800 font-semibold text-[10px] transition inline-flex items-center space-x-1 cursor-pointer"
                >
                  <ExternalLink class="w-3 h-3" />
                  <span>Link</span>
                </a>

                <button 
                  v-if="item.item_type === 'assignment' && !item.submission_status"
                  @click="openDirectAssignmentSubmission(item)" 
                  type="button" 
                  class="px-3 py-1 rounded-xl font-bold text-[10px] text-white shadow-2xs transition inline-flex items-center space-x-1 cursor-pointer"
                  :class="isPastDeadline(item.due_date) ? 'bg-amber-700 hover:bg-amber-800' : 'bg-emerald-800 hover:bg-emerald-700'"
                >
                  <span>{{ isPastDeadline(item.due_date) ? 'Turn In Late' : 'Submit Work' }}</span>
                  <ChevronRight class="w-3 h-3" />
                </button>
                <button 
                  v-else
                  @click="navigateToLmsSubject(item.subject_id)" 
                  type="button" 
                  class="px-2.5 py-1 rounded-xl font-bold text-[10px] bg-emerald-800 hover:bg-emerald-700 text-white shadow-2xs transition inline-flex items-center space-x-1 cursor-pointer"
                >
                  <span>{{ item.submission_status === 'Graded' ? 'View Graded' : 'Open in LMS' }}</span>
                  <ChevronRight class="w-3 h-3" />
                </button>
              </div>
            </div>

            <!-- View all in LMS button -->
            <div v-if="(dashboardData.lms_updates || []).length > 5" class="pt-1 text-center">
              <button 
                @click="activeTab = 'lms'; router.push({ query: { tab: 'lms' } })" 
                type="button" 
                class="text-[11px] font-bold text-emerald-800 hover:text-emerald-950 underline cursor-pointer"
              >
                View all {{ dashboardData.lms_updates.length }} announcements in LMS →
              </button>
            </div>
          </div>
        </div>

        <!-- STATEMENT OF ACCOUNT (SOA) -->
        <div v-show="activeTab === 'all' || activeTab === 'account'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm text-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900">Statement of Account</h2>
            <span class="px-2.5 py-0.5 rounded text-[10px] font-extrabold uppercase" :class="getPaymentBadge(dashboardData.enrollment?.payment_status)">
              {{ dashboardData.enrollment?.payment_status || 'Assessed' }}
            </span>
          </div>

          <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/90 font-mono space-y-2 text-[11px]">
            <div class="flex justify-between text-slate-600">
              <span>Gross Tuition & Fees:</span>
              <span class="font-bold text-slate-900">₱{{ Number(dashboardData.enrollment?.gross_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
            </div>
            <div v-if="Number(dashboardData.enrollment?.voucher_discount) > 0" class="flex justify-between text-emerald-700 font-bold">
              <span>Voucher Subsidy:</span>
              <span>- ₱{{ Number(dashboardData.enrollment?.voucher_discount).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
            </div>
            <div class="flex justify-between font-bold text-slate-900 border-t border-slate-200 pt-1.5 text-xs">
              <span>Total Net Payable:</span>
              <span>₱{{ Number(dashboardData.enrollment?.net_payable || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
            </div>
            <div class="flex justify-between text-emerald-600 font-bold">
              <span>Total Paid to Date:</span>
              <span>₱{{ Number(dashboardData.enrollment?.total_paid || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
            </div>
            <div class="flex justify-between text-rose-600 font-bold border-t border-slate-200 pt-1.5">
              <span>Remaining Balance:</span>
              <span>₱{{ Number(dashboardData.enrollment?.remaining_balance || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
            </div>
          </div>

          <!-- Online Payment Button (PayMongo) -->
          <div v-if="remainingBalance > 0" class="pt-1">
            <button 
              @click="openStudentPaymongoModal"
              type="button" 
              class="w-full py-3 px-4 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white font-bold text-xs shadow-md shadow-emerald-700/20 transition flex items-center justify-between cursor-pointer group"
            >
              <div class="flex items-center space-x-2.5">
                <div class="w-7 h-7 rounded-xl bg-white/20 flex items-center justify-center">
                  <CreditCard class="w-4 h-4 text-white" />
                </div>
                <div class="text-left">
                  <div class="leading-tight font-extrabold text-[12px]">Pay Remaining Balance</div>
                  <div class="text-[10px] text-emerald-100 font-normal">GCash • Maya • Cards • Billease</div>
                </div>
              </div>
              <div class="flex items-center space-x-1 font-bold text-[11px] bg-white/20 px-2.5 py-1 rounded-xl group-hover:bg-white/30 transition">
                <span>Pay Online</span>
                <span class="text-xs">→</span>
              </div>
            </button>
          </div>
          <div v-else-if="Number(dashboardData.enrollment?.total_paid || 0) > 0" class="pt-1">
            <div class="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center space-x-2 font-semibold">
              <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
              <span>Tuition Account Fully Settled. No outstanding balance.</span>
            </div>
          </div>

          <!-- Official Receipts History -->
          <div v-if="dashboardData.payments && dashboardData.payments.length > 0" class="pt-2">
            <h3 class="text-[10px] font-extrabold uppercase text-slate-400 tracking-wider mb-2">Issued Receipts</h3>
            <div class="space-y-2">
              <div 
                v-for="p in dashboardData.payments" 
                :key="p.id"
                class="p-2.5 rounded-xl border border-slate-200 bg-white flex items-center justify-between text-[11px] font-mono shadow-2xs"
              >
                <div>
                  <strong class="text-slate-800">{{ p.or_number }}</strong>
                  <div class="text-[10px] text-slate-400">{{ p.payment_date }} • {{ p.payment_method }}</div>
                </div>
                <strong class="text-emerald-700">₱{{ Number(p.amount_paid).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</strong>
              </div>
            </div>
          </div>
        </div>

        <!-- SCHOOL EVENTS & ACADEMIC CALENDAR (PREVIEW ON DASHBOARD) -->
        <div v-show="activeTab === 'all'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm text-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900 flex items-center space-x-1.5">
              <Calendar class="w-4 h-4 text-purple-700" />
              <span>School Events Calendar</span>
            </h2>
            <button 
              @click="router.push({ query: { tab: 'events' } }); activeTab = 'events'" 
              type="button"
              class="text-purple-700 hover:text-purple-900 font-bold text-[11px] flex items-center space-x-0.5 cursor-pointer group"
            >
              <span>View Full Calendar</span>
              <ChevronRight class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition" />
            </button>
          </div>

          <div class="space-y-3">
            <div 
              v-for="ev in upcomingEventsPreview" 
              :key="ev.id"
              @click="openEventModal(ev)"
              class="p-3.5 rounded-2xl border border-slate-200/90 bg-slate-50/70 hover:bg-purple-50/40 hover:border-purple-300 transition cursor-pointer space-y-2 group"
            >
              <div class="flex items-center justify-between text-[10px] font-bold">
                <span class="px-2 py-0.5 rounded uppercase" :class="getEventCategoryMeta(ev.event_category).badge">
                  {{ ev.event_category }}
                </span>
                <span class="text-slate-500 font-mono font-bold">{{ formatEvDateRange(ev.start_date, ev.end_date) }}</span>
              </div>
              <h4 class="font-bold text-slate-900 text-xs leading-snug group-hover:text-purple-900 transition">{{ ev.title }}</h4>
              <p class="text-[11px] text-slate-500 line-clamp-2 leading-relaxed">{{ ev.description }}</p>
              <div v-if="ev.location" class="text-[10px] text-slate-500 font-medium flex items-center space-x-1 pt-0.5">
                <MapPin class="w-3 h-3 text-slate-400 shrink-0" />
                <span class="truncate">{{ ev.location }}</span>
              </div>
            </div>

            <div v-if="eventsList.length === 0" class="text-center py-6 text-slate-400 text-xs">
              No school events posted for this term.
            </div>
          </div>
        </div>

        <!-- ADMISSION REQUIREMENTS & FOLLOW-UP COMPLIANCE -->
        <div v-show="activeTab === 'all' || activeTab === 'records'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm text-xs space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 pb-3 gap-2">
            <div>
              <div class="flex items-center space-x-2">
                <h2 class="text-base font-bold text-slate-900 flex items-center space-x-1.5">
                  <ShieldCheck class="w-4 h-4 text-emerald-600" />
                  <span>Pending Requirements & Follow-up</span>
                </h2>
                <span 
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                  :class="pendingFollowUpDocuments.length === 0 
                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' 
                    : 'bg-amber-50 text-amber-800 border border-amber-300'"
                >
                  {{ pendingFollowUpDocuments.length === 0 ? 'All Completed' : `${pendingFollowUpDocuments.length} To Follow Up` }}
                </span>
              </div>
              <p class="text-[11px] text-slate-500 mt-0.5">
                Submit digital scans or copies for any missing or to-follow-up admission credentials. Verified documents are cleared automatically.
              </p>
            </div>
            
            <div class="flex items-center space-x-2 self-start sm:self-auto">
              <button 
                v-if="verifiedDocuments.length > 0"
                @click="showAllRequirements = !showAllRequirements" 
                type="button" 
                class="px-2.5 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 transition cursor-pointer text-[11px] font-medium"
              >
                {{ showAllRequirements ? 'Hide Completed' : `View Verified (${verifiedDocuments.length})` }}
              </button>
              <button 
                @click="loadStudentRequirements" 
                type="button" 
                class="p-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-500 transition cursor-pointer"
                title="Refresh requirements status"
              >
                <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isLoadingRequirements }" />
              </button>
            </div>
          </div>

          <!-- Alert banner if student has pending / to-follow-up / deficient requirements -->
          <div 
            v-if="pendingFollowUpDocuments.length > 0" 
            class="p-3.5 rounded-2xl bg-amber-50/90 border border-amber-200 text-amber-950 text-xs flex items-start space-x-2.5"
          >
            <AlertTriangle class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
            <div class="space-y-0.5">
              <strong class="font-bold text-[11px] block">Pending Document Submission Required</strong>
              <p class="text-[11px] text-amber-900 leading-relaxed">
                You have <strong>{{ pendingFollowUpDocuments.length }}</strong> requirement(s) awaiting completion (such as Form 137 / SF10 or PSA). Please upload a digital scan below or submit hard copies to the Registrar / Records Custodian.
              </p>
            </div>
          </div>

          <!-- ALL REQUIREMENTS COMPLETED BANNER (WHEN 0 PENDING) -->
          <div 
            v-if="pendingFollowUpDocuments.length === 0 && requirementsData.documents.length > 0 && !showAllRequirements" 
            class="p-6 rounded-2xl bg-emerald-50/70 border border-emerald-200 text-center space-y-2"
          >
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto shadow-2xs">
              <CheckCircle2 class="w-6 h-6 text-emerald-600" />
            </div>
            <div class="font-extrabold text-slate-900 text-sm">All Requirements Complete & Verified</div>
            <p class="text-xs text-slate-600 max-w-md mx-auto leading-relaxed">
              All required admission credentials and records (Form 137 / SF10, PSA Birth Certificate, Form 138, Good Moral, etc.) have been verified and cleared by the Registrar & Records Custodian. No pending submissions required.
            </p>
          </div>

          <!-- Active Follow-up Requirements List (Non-verified only) -->
          <div v-if="pendingFollowUpDocuments.length > 0" class="space-y-2.5">
            <div 
              v-for="req in pendingFollowUpDocuments" 
              :key="req.document_type"
              class="p-3.5 rounded-2xl border border-amber-200/80 bg-amber-50/30 hover:bg-white hover:border-amber-400 transition space-y-2"
            >
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="space-y-0.5">
                  <div class="flex items-center space-x-2">
                    <span class="font-bold text-slate-900 text-xs">{{ req.title }}</span>
                    <span v-if="req.is_mandatory" class="text-[10px] text-rose-600 font-bold uppercase tracking-wider bg-rose-50 px-1.5 py-0.2 rounded border border-rose-200">
                      Required
                    </span>
                  </div>
                  <p class="text-[11px] text-slate-500 leading-snug">{{ req.description }}</p>
                </div>

                <div class="flex items-center space-x-2 shrink-0 self-start sm:self-auto">
                  <span 
                    class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider inline-flex items-center space-x-1"
                    :class="getRequirementBadgeClass(req.status)"
                  >
                    <span v-if="req.status === 'Pending'" class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse mr-0.5"></span>
                    <Clock v-else-if="req.status === 'To Follow Up'" class="w-3 h-3 text-amber-600" />
                    <AlertCircle v-else-if="req.status === 'Deficient' || req.status === 'Rejected'" class="w-3 h-3 text-rose-600" />
                    <span>{{ req.status === 'Pending' ? 'Pending Review' : req.status }}</span>
                  </span>
                </div>
              </div>

              <!-- Notes / Deficiency remarks if any -->
              <div v-if="req.verification_notes" class="p-2 rounded-xl bg-white border border-slate-200 text-[11px] text-slate-600 flex items-start space-x-1.5">
                <Info class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" />
                <span>{{ req.verification_notes }}</span>
              </div>

              <!-- Action Buttons -->
              <div class="flex items-center justify-end space-x-2 pt-1 border-t border-slate-200/60">
                <!-- View existing file if uploaded -->
                <button 
                  v-if="req.file_path"
                  type="button" 
                  @click="openPreviewDoc(req.file_path, req.title)"
                  class="px-2.5 py-1.5 rounded-xl bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 font-semibold text-[11px] inline-flex items-center space-x-1 transition cursor-pointer shadow-2xs"
                >
                  <Eye class="w-3 h-3 text-slate-500" />
                  <span>View Uploaded File</span>
                </button>

                <!-- Upload / Re-upload Button -->
                <button 
                  type="button" 
                  @click="openUploadDocModal(req)"
                  class="px-3.5 py-1.5 rounded-xl text-[11px] font-bold bg-[#0c2340] hover:bg-[#163a66] text-white shadow-xs transition inline-flex items-center space-x-1.5 cursor-pointer"
                >
                  <UploadCloud class="w-3 h-3 text-emerald-400" />
                  <span>{{ req.file_path ? 'Update / Re-upload' : 'Upload Document' }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Optional Accordion: Verified Documents History -->
          <div v-if="showAllRequirements && verifiedDocuments.length > 0" class="pt-2 border-t border-slate-100 space-y-2">
            <h4 class="text-[11px] font-bold text-slate-700 uppercase tracking-wider flex items-center space-x-1.5">
              <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600" />
              <span>Verified Documents Archive ({{ verifiedDocuments.length }})</span>
            </h4>
            
            <div class="space-y-2">
              <div 
                v-for="vdoc in verifiedDocuments" 
                :key="vdoc.document_type"
                class="p-3 rounded-2xl border border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-2"
              >
                <div>
                  <div class="flex items-center space-x-2">
                    <span class="font-bold text-slate-800 text-xs">{{ vdoc.title }}</span>
                    <span class="px-2 py-0.2 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                      ✓ Verified
                    </span>
                  </div>
                  <p class="text-[10px] text-slate-500">{{ vdoc.description }}</p>
                </div>

                <div v-if="vdoc.file_path" class="shrink-0">
                  <button 
                    type="button" 
                    @click="openPreviewDoc(vdoc.file_path, vdoc.title)"
                    class="px-2.5 py-1 rounded-xl bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 font-semibold text-[11px] inline-flex items-center space-x-1 transition cursor-pointer"
                  >
                    <Eye class="w-3 h-3 text-slate-500" />
                    <span>View File</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-if="requirementsData.documents.length === 0" class="text-center py-6 text-slate-400 text-xs">
            No admission requirements record found.
          </div>
        </div>

        <!-- OFFICIAL DOCUMENT REQUESTS (DRS) -->
        <div v-show="activeTab === 'all' || activeTab === 'records'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm text-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900 flex items-center space-x-1.5">
              <FileText class="w-4 h-4 text-blue-900" />
              <span>Official Document Requests</span>
            </h2>
            <button 
              @click="showStudentDocModal = true"
              class="px-3.5 py-1.5 rounded-xl bg-blue-900 hover:bg-blue-800 text-white font-semibold text-[11px] transition shadow-xs cursor-pointer"
            >
              + Request Document
            </button>
          </div>

          <!-- Requests List -->
          <div class="space-y-2.5">
            <div 
              v-for="dr in myDocRequests" 
              :key="dr.id" 
              class="p-3 rounded-2xl border border-slate-200 bg-slate-50/70 flex items-center justify-between text-xs hover:bg-slate-50 transition"
            >
              <div>
                <div class="font-bold text-slate-900">{{ dr.document_type }}</div>
                <div class="text-[10px] text-slate-400 font-mono">Control #: {{ dr.control_number || 'Pending' }} • {{ dr.copies }} Copy/Copies</div>
                <div v-if="dr.purpose" class="text-[10px] text-slate-500 italic mt-0.5">Purpose: {{ dr.purpose }}</div>
              </div>
              <span class="px-2.5 py-1 rounded-full text-[10px] font-bold" :class="getDRSBadge(dr.status)">
                {{ dr.status }}
              </span>
            </div>

            <div v-if="myDocRequests.length === 0" class="text-center py-6 text-slate-400 text-xs">
              You have no active document requests.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- TAB: FULL-WIDTH DEDICATED SCHOOL EVENTS & CALENDAR VIEW  -->
    <!-- ======================================================== -->
    <div v-else-if="activeTab === 'events'" class="space-y-6">
      
      <!-- Top Calendar Header Banner -->
      <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
          <div class="flex items-center space-x-2.5">
            <div class="w-10 h-10 rounded-2xl bg-purple-100 text-purple-800 flex items-center justify-center font-bold shadow-2xs">
              <CalendarDays class="w-5 h-5 text-purple-700" />
            </div>
            <div>
              <div class="flex items-center space-x-2">
                <h2 class="text-lg font-black text-slate-900">School Events & Academic Calendar</h2>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                  {{ dashboardData.enrollment?.school_year_name || 'S.Y. 2026-2027 Active' }}
                </span>
              </div>
              <p class="text-xs text-slate-500 mt-0.5">
                Official DepEd bell schedules, quarterly examinations, national holidays, and campus milestones.
              </p>
            </div>
          </div>
        </div>

        <div class="flex items-center space-x-2.5 flex-wrap gap-y-2">
          <!-- Calendar / Timeline View Mode Switcher -->
          <div class="flex items-center space-x-1 bg-slate-100 p-1 rounded-2xl border border-slate-200 text-xs">
            <button 
              @click="calendarViewMode = 'calendar'" 
              type="button" 
              :class="calendarViewMode === 'calendar' ? 'bg-white text-purple-900 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800 font-medium'"
              class="px-3.5 py-1.5 rounded-xl transition cursor-pointer flex items-center space-x-1.5"
            >
              <Calendar class="w-3.5 h-3.5" />
              <span>Month Grid</span>
            </button>
            <button 
              @click="calendarViewMode = 'timeline'" 
              type="button" 
              :class="calendarViewMode === 'timeline' ? 'bg-white text-purple-900 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800 font-medium'"
              class="px-3.5 py-1.5 rounded-xl transition cursor-pointer flex items-center space-x-1.5"
            >
              <List class="w-3.5 h-3.5" />
              <span>Timeline / Agenda</span>
            </button>
          </div>

          <!-- Go To Today Button -->
          <button 
            @click="goToToday" 
            type="button"
            class="px-3.5 py-2 rounded-2xl bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition cursor-pointer flex items-center space-x-1.5 border border-slate-200 shadow-2xs"
          >
            <Clock class="w-3.5 h-3.5 text-purple-600" />
            <span>Today</span>
          </button>
        </div>
      </div>

      <!-- Quick Metrics Summary Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metric 1: Total Events -->
        <div class="p-4 rounded-3xl bg-white border border-slate-200 shadow-sm flex items-center space-x-3.5">
          <div class="w-11 h-11 rounded-2xl bg-purple-50 border border-purple-200 flex items-center justify-center text-purple-700 shrink-0">
            <Calendar class="w-5 h-5" />
          </div>
          <div class="min-w-0">
            <div class="text-xl font-black text-slate-900 font-mono">{{ eventsList.length }}</div>
            <div class="text-[11px] font-medium text-slate-500 truncate">Total Scheduled Events</div>
          </div>
        </div>

        <!-- Metric 2: Next Event -->
        <div class="p-4 rounded-3xl bg-white border border-slate-200 shadow-sm flex items-center space-x-3.5">
          <div class="w-11 h-11 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-700 shrink-0">
            <Clock class="w-5 h-5" />
          </div>
          <div class="min-w-0">
            <div class="text-xs font-bold text-slate-900 truncate">
              {{ nextMajorEvent ? nextMajorEvent.title : 'None Scheduled' }}
            </div>
            <div class="text-[10px] text-emerald-700 font-mono font-bold">
              {{ nextMajorEvent ? formatEvDateRange(nextMajorEvent.start_date, nextMajorEvent.end_date) : 'Up to date' }}
            </div>
          </div>
        </div>

        <!-- Metric 3: Holidays & Suspensions -->
        <div class="p-4 rounded-3xl bg-white border border-slate-200 shadow-sm flex items-center space-x-3.5">
          <div class="w-11 h-11 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-700 shrink-0">
            <Coffee class="w-5 h-5" />
          </div>
          <div class="min-w-0">
            <div class="text-xl font-black text-slate-900 font-mono">{{ holidayCount }}</div>
            <div class="text-[11px] font-medium text-slate-500 truncate">Official No-Class Days</div>
          </div>
        </div>

        <!-- Metric 4: Academic Milestones -->
        <div class="p-4 rounded-3xl bg-white border border-slate-200 shadow-sm flex items-center space-x-3.5">
          <div class="w-11 h-11 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-700 shrink-0">
            <BookOpen class="w-5 h-5" />
          </div>
          <div class="min-w-0">
            <div class="text-xl font-black text-slate-900 font-mono">{{ academicMilestoneCount }}</div>
            <div class="text-[11px] font-medium text-slate-500 truncate">Academic Milestones</div>
          </div>
        </div>
      </div>

      <!-- Filter & Search Toolbar -->
      <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm space-y-3.5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
          <!-- Search Input -->
          <div class="relative flex-1 max-w-md">
            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input 
              v-model="eventSearchQuery"
              type="text" 
              placeholder="Search events by title, description, or venue..." 
              class="w-full pl-9 pr-3.5 py-2.5 rounded-2xl border border-slate-300 text-xs focus:ring-2 focus:ring-purple-500 focus:outline-hidden"
            />
            <button 
              v-if="eventSearchQuery" 
              @click="eventSearchQuery = ''" 
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 font-bold text-xs"
            >
              ✕
            </button>
          </div>

          <!-- Timeline Filter Tabs (Only in Timeline mode) -->
          <div v-if="calendarViewMode === 'timeline'" class="flex items-center space-x-1 bg-slate-100 p-1 rounded-2xl border border-slate-200 text-xs">
            <button 
              @click="eventTimelineFilter = 'all'" 
              :class="eventTimelineFilter === 'all' ? 'bg-white font-bold text-slate-900 shadow-2xs' : 'text-slate-600'"
              class="px-3 py-1 rounded-xl transition cursor-pointer"
            >
              All Events
            </button>
            <button 
              @click="eventTimelineFilter = 'upcoming'" 
              :class="eventTimelineFilter === 'upcoming' ? 'bg-white font-bold text-slate-900 shadow-2xs' : 'text-slate-600'"
              class="px-3 py-1 rounded-xl transition cursor-pointer"
            >
              Upcoming
            </button>
            <button 
              @click="eventTimelineFilter = 'past'" 
              :class="eventTimelineFilter === 'past' ? 'bg-white font-bold text-slate-900 shadow-2xs' : 'text-slate-600'"
              class="px-3 py-1 rounded-xl transition cursor-pointer"
            >
              Past
            </button>
          </div>
        </div>

        <!-- Category Filter Pills -->
        <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 text-xs no-scrollbar">
          <button 
            @click="eventCategoryFilter = 'All'" 
            :class="eventCategoryFilter === 'All' ? 'bg-purple-900 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            class="px-3 py-1.5 rounded-xl transition cursor-pointer shrink-0 font-medium text-[11px]"
          >
            All Categories ({{ eventsList.length }})
          </button>
          <button 
            v-for="cat in availableEventCategories" 
            :key="cat"
            @click="eventCategoryFilter = cat" 
            :class="eventCategoryFilter === cat ? 'bg-purple-900 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            class="px-3 py-1.5 rounded-xl transition cursor-pointer shrink-0 font-medium text-[11px] flex items-center space-x-1.5"
          >
            <span class="w-2 h-2 rounded-full" :class="getEventCategoryMeta(cat).dot"></span>
            <span>{{ cat }}</span>
          </button>
        </div>
      </div>

      <!-- ====================================================== -->
      <!-- VIEW 1: INTERACTIVE MONTHLY CALENDAR GRID               -->
      <!-- ====================================================== -->
      <div v-if="calendarViewMode === 'calendar'" class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-6">
        
        <!-- Month Navigation Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
          <div class="flex items-center space-x-3">
            <button 
              @click="prevMonth" 
              class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition cursor-pointer border border-slate-200/80"
              title="Previous Month"
            >
              <ChevronLeft class="w-5 h-5" />
            </button>
            <h3 class="text-lg font-black text-slate-900 font-mono tracking-tight min-w-[180px] text-center sm:text-left">
              {{ currentMonthYearTitle }}
            </h3>
            <button 
              @click="nextMonth" 
              class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition cursor-pointer border border-slate-200/80"
              title="Next Month"
            >
              <ChevronRight class="w-5 h-5" />
            </button>
          </div>

          <!-- Quick Month Jumper Strip -->
          <div class="flex items-center space-x-1.5 overflow-x-auto py-0.5 no-scrollbar">
            <button 
              v-for="m in availableEventMonths" 
              :key="m.key"
              @click="jumpToMonth(m)"
              :class="isMonthActive(m) ? 'bg-purple-100 text-purple-900 border-purple-300 font-bold' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border-slate-200'"
              class="px-2.5 py-1 rounded-xl text-[11px] font-mono border transition cursor-pointer shrink-0"
            >
              {{ m.label }}
            </button>
          </div>
        </div>

        <!-- 7-Day Calendar Grid -->
        <div class="overflow-x-auto">
          <div class="min-w-[700px]">
            <!-- Day of Week Headers -->
            <div class="grid grid-cols-7 gap-1 text-center mb-1">
              <div v-for="d in ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT']" :key="d" class="py-2 text-[11px] font-black text-slate-400 font-mono">
                {{ d }}
              </div>
            </div>

            <!-- Calendar Cells -->
            <div class="grid grid-cols-7 gap-1.5">
              <div 
                v-for="cell in calendarGridDays" 
                :key="cell.dateStr"
                @click="selectDayCell(cell)"
                :class="[
                  'min-h-[115px] p-2.5 rounded-2xl border transition flex flex-col justify-between cursor-pointer group',
                  cell.isCurrentMonth ? 'bg-white border-slate-200 hover:border-purple-300 hover:shadow-xs' : 'bg-slate-50/60 border-slate-100 text-slate-400',
                  selectedCalendarDay === cell.dateStr ? 'ring-2 ring-purple-600 border-purple-600 bg-purple-50/20' : '',
                  cell.isToday ? 'border-emerald-500 bg-emerald-50/10' : ''
                ]"
              >
                <!-- Day Number & Today indicator -->
                <div class="flex items-center justify-between">
                  <span 
                    :class="[
                      'w-6 h-6 flex items-center justify-center rounded-full text-xs font-bold font-mono',
                      cell.isToday ? 'bg-emerald-600 text-white shadow-2xs font-black' : (cell.isCurrentMonth ? 'text-slate-800' : 'text-slate-400')
                    ]"
                  >
                    {{ cell.dayNumber }}
                  </span>
                  <span v-if="cell.isToday" class="text-[9px] font-extrabold uppercase text-emerald-700 bg-emerald-100 px-1.5 py-0.2 rounded">Today</span>
                  <span v-else-if="cell.events.length > 0" class="text-[9px] font-mono font-bold text-purple-700 bg-purple-100 px-1.5 py-0.2 rounded-full">
                    {{ cell.events.length }}
                  </span>
                </div>

                <!-- Event Badges inside Cell -->
                <div class="space-y-1 my-1.5 flex-1">
                  <div 
                    v-for="ev in cell.events.slice(0, 2)" 
                    :key="ev.id"
                    @click.stop="openEventModal(ev)"
                    :class="[
                      'px-2 py-0.5 rounded-lg text-[10px] font-bold truncate leading-tight transition cursor-pointer hover:brightness-110 shadow-2xs',
                      getEventCategoryMeta(ev.event_category).chip
                    ]"
                    :title="ev.title + ' (' + ev.event_category + ')'"
                  >
                    {{ ev.title }}
                  </div>
                  <div 
                    v-if="cell.events.length > 2" 
                    class="text-[9px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded-md text-center"
                  >
                    +{{ cell.events.length - 2 }} more
                  </div>
                </div>

                <div class="text-[9px] text-slate-400 group-hover:text-purple-600 text-right transition font-medium">
                  {{ cell.events.length > 0 ? 'Click to inspect' : '' }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Selected Date Inspector Drawer -->
        <div v-if="selectedCalendarDay" class="p-5 rounded-3xl bg-slate-50 border border-slate-200 space-y-4">
          <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <div class="flex items-center space-x-2">
              <Calendar class="w-4 h-4 text-purple-700" />
              <h4 class="font-bold text-slate-900 text-sm">
                Events on {{ formatSelectedDayLabel(selectedCalendarDay) }}
              </h4>
              <span class="px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 text-[10px] font-extrabold font-mono">
                {{ eventsOnSelectedDay.length }} Event{{ eventsOnSelectedDay.length !== 1 ? 's' : '' }}
              </span>
            </div>
            <button @click="selectedCalendarDay = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold cursor-pointer">
              ✕ Close
            </button>
          </div>

          <div v-if="eventsOnSelectedDay.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
            <div 
              v-for="ev in eventsOnSelectedDay" 
              :key="ev.id"
              class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs space-y-2 hover:border-purple-300 transition"
            >
              <div class="flex items-center justify-between text-[10px] font-bold">
                <span class="px-2 py-0.5 rounded uppercase" :class="getEventCategoryMeta(ev.event_category).badge">
                  {{ ev.event_category }}
                </span>
                <span class="text-slate-500 font-mono">{{ formatEvDateRange(ev.start_date, ev.end_date) }}</span>
              </div>
              <h5 class="font-bold text-slate-900 text-sm leading-snug">{{ ev.title }}</h5>
              <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">{{ ev.description }}</p>
              
              <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-[10px] text-slate-500">
                <div v-if="ev.location" class="flex items-center space-x-1">
                  <MapPin class="w-3.5 h-3.5 text-slate-400" />
                  <span class="truncate">{{ ev.location }}</span>
                </div>
                <button 
                  @click="openEventModal(ev)" 
                  class="text-purple-700 hover:text-purple-900 font-bold ml-auto cursor-pointer"
                >
                  View Details →
                </button>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-6 text-slate-400 text-xs">
            No scheduled school events on this specific date.
          </div>
        </div>

      </div>

      <!-- ====================================================== -->
      <!-- VIEW 2: CHRONOLOGICAL TIMELINE / AGENDA VIEW            -->
      <!-- ====================================================== -->
      <div v-else class="space-y-6">
        
        <div v-for="(group, groupMonth) in eventsGroupedByMonth" :key="groupMonth" class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5">
          <!-- Month Header -->
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-black text-slate-900 flex items-center space-x-2 font-mono">
              <Calendar class="w-4 h-4 text-purple-700" />
              <span>{{ groupMonth }}</span>
            </h3>
            <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 font-mono text-[10px] font-bold">
              {{ group.length }} Event{{ group.length !== 1 ? 's' : '' }}
            </span>
          </div>

          <!-- Month Events List -->
          <div class="space-y-3.5">
            <div 
              v-for="ev in group" 
              :key="ev.id"
              class="p-4 sm:p-5 rounded-2xl border border-slate-200/90 bg-slate-50/50 hover:bg-white hover:border-purple-300 hover:shadow-xs transition flex flex-col md:flex-row md:items-center justify-between gap-4 text-xs group"
            >
              <div class="flex items-start sm:items-center space-x-4 min-w-0 flex-1">
                <!-- Large Date Box -->
                <div class="w-16 h-16 rounded-2xl bg-white border border-slate-200 shadow-2xs flex flex-col items-center justify-center shrink-0 text-center">
                  <span class="text-[10px] font-extrabold uppercase text-purple-700 font-mono tracking-wider">
                    {{ getEventMonthShort(ev.start_date) }}
                  </span>
                  <span class="text-lg font-black text-slate-900 font-mono leading-none my-0.5">
                    {{ getEventDayNumber(ev.start_date, ev.end_date) }}
                  </span>
                  <span class="text-[9px] font-semibold text-slate-400 uppercase">
                    {{ getEventDayOfWeek(ev.start_date) }}
                  </span>
                </div>

                <!-- Event Details -->
                <div class="space-y-1.5 min-w-0 flex-1">
                  <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                    <span class="px-2 py-0.5 rounded uppercase font-bold text-[10px]" :class="getEventCategoryMeta(ev.event_category).badge">
                      {{ ev.event_category }}
                    </span>
                    <span v-if="ev.target_audience" class="px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-500 font-mono text-[10px] font-semibold">
                      {{ ev.target_audience }}
                    </span>
                    <span :class="['px-2 py-0.5 rounded text-[10px] font-mono font-bold border', getEventStatusText(ev.start_date, ev.end_date).class]">
                      {{ getEventStatusText(ev.start_date, ev.end_date).label }}
                    </span>
                  </div>

                  <h4 class="font-black text-sm text-slate-900 group-hover:text-purple-900 transition leading-snug">
                    {{ ev.title }}
                  </h4>
                  <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                    {{ ev.description }}
                  </p>

                  <div class="flex items-center space-x-3 text-[11px] text-slate-500 pt-1 flex-wrap gap-y-1">
                    <div v-if="ev.location" class="flex items-center space-x-1">
                      <MapPin class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                      <span>{{ ev.location }}</span>
                    </div>
                    <div v-if="ev.start_time" class="flex items-center space-x-1 font-mono">
                      <Clock class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                      <span>{{ formatTime(ev.start_time) }} – {{ formatTime(ev.end_time || ev.start_time) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Button -->
              <div class="shrink-0 self-end md:self-center">
                <button 
                  @click="openEventModal(ev)" 
                  class="px-4 py-2 rounded-xl bg-white hover:bg-purple-50 text-purple-900 font-bold text-xs border border-slate-200 hover:border-purple-300 transition shadow-2xs cursor-pointer flex items-center space-x-1"
                >
                  <span>View Details</span>
                  <ChevronRight class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="Object.keys(eventsGroupedByMonth).length === 0" class="bg-white rounded-3xl p-12 text-center text-slate-400 border border-slate-200">
          <Calendar class="w-12 h-12 mx-auto text-slate-300 mb-2" />
          <h4 class="text-sm font-bold text-slate-700">No Events Found</h4>
          <p class="text-xs text-slate-500 mt-1">Try adjusting your category filter or search keywords.</p>
        </div>

      </div>

    </div>

    <!-- ======================================================== -->
    <!-- TAB: STUDENT CLASSROOM & LMS MODULES                     -->
    <!-- ======================================================== -->
    <div v-else-if="activeTab === 'lms'" class="space-y-6">
      
      <!-- Top Course Shelf & Selector -->
      <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
          <div>
            <div class="flex items-center space-x-2">
              <BookOpen class="w-4 h-4 text-blue-800" />
              <h2 class="text-base font-bold text-slate-900">My Virtual Classrooms & Learning Areas</h2>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">
              Select an enrolled subject to view lesson handouts, announcements, and submit assigned tasks.
            </p>
          </div>

          <div v-if="selectedLmsSubject" class="text-xs font-mono font-bold px-3 py-1 rounded-xl bg-blue-50 text-blue-900 border border-blue-200">
            {{ dashboardData.enrollment?.section_name || 'Enrolled Section' }}
          </div>
        </div>

        <!-- If SHS Student: Segmented Term Switcher -->
        <div v-if="isSHSStudent" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-2 bg-slate-100/80 rounded-2xl border border-slate-200">
          <div class="flex items-center space-x-1 p-1 bg-white rounded-xl shadow-2xs">
            <button 
              type="button" 
              @click="setLmsSemesterFilter('active')"
              :class="selectedLmsSemesterFilter === 'active' ? 'bg-blue-900 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
              class="px-3 py-1.5 rounded-lg text-xs transition flex items-center space-x-1.5 cursor-pointer"
            >
              <Sparkles class="w-3.5 h-3.5" />
              <span>Active Term ({{ activeLmsSubjects.length }})</span>
            </button>

            <button 
              type="button" 
              @click="setLmsSemesterFilter('archived')"
              :class="selectedLmsSemesterFilter === 'archived' ? 'bg-amber-700 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
              class="px-3 py-1.5 rounded-lg text-xs transition flex items-center space-x-1.5 cursor-pointer"
            >
              <Lock class="w-3.5 h-3.5" />
              <span>Archived / Read-Only ({{ archivedLmsSubjects.length }})</span>
            </button>

            <button 
              type="button" 
              @click="setLmsSemesterFilter('all')"
              :class="selectedLmsSemesterFilter === 'all' ? 'bg-slate-800 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
              class="px-3 py-1.5 rounded-lg text-xs transition flex items-center space-x-1.5 cursor-pointer"
            >
              <span>All ({{ (dashboardData.subjects || []).length }})</span>
            </button>
          </div>

          <div class="text-[11px] text-slate-500 font-medium flex items-center space-x-2 px-2">
            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>Active Semester: <strong>{{ dashboardData.enrollment?.active_semester || '1st Semester' }}</strong></span>
          </div>
        </div>

        <!-- Subject Cards Shelf -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
          <button 
            v-for="sub in displayedLmsSubjects" 
            :key="sub.subject_id"
            @click="selectLmsSubject(sub)"
            type="button"
            :class="[
              'p-3.5 rounded-2xl border text-left transition cursor-pointer flex flex-col justify-between space-y-2 group relative',
              selectedLmsSubject?.subject_id === sub.subject_id 
                ? (isSubjectTermArchived(sub) ? 'bg-amber-950 text-white border-amber-900 shadow-sm' : 'bg-gradient-to-br from-slate-900 to-blue-950 text-white border-blue-900 shadow-md') 
                : (isSubjectTermArchived(sub) ? 'bg-amber-50/40 hover:bg-amber-50/80 text-slate-800 border-amber-200/80' : 'bg-slate-50/80 hover:bg-white hover:border-blue-300 text-slate-800 border-slate-200')
            ]"
          >
            <div>
              <div class="flex items-center justify-between gap-1 mb-1.5">
                <span 
                  :class="selectedLmsSubject?.subject_id === sub.subject_id ? 'bg-white/20 text-white' : 'bg-slate-200/80 text-slate-700'"
                  class="px-2 py-0.5 rounded-full text-[9px] font-mono font-bold uppercase tracking-wider inline-block"
                >
                  {{ sub.subject_code }}
                </span>

                <span 
                  v-if="isSubjectTermArchived(sub)" 
                  class="px-1.5 py-0.5 rounded text-[8px] font-bold font-mono border"
                  :class="selectedLmsSubject?.subject_id === sub.subject_id ? 'bg-amber-500/30 text-amber-200 border-amber-400/40' : 'bg-amber-100 text-amber-800 border-amber-300'"
                >
                  Archived
                </span>
                <span 
                  v-else-if="sub.semester"
                  class="px-1.5 py-0.5 rounded text-[8px] font-bold font-mono border"
                  :class="selectedLmsSubject?.subject_id === sub.subject_id ? 'bg-blue-500/30 text-blue-200 border-blue-400/40' : 'bg-blue-50 text-blue-800 border-blue-200'"
                >
                  Active
                </span>
              </div>

              <div 
                :class="selectedLmsSubject?.subject_id === sub.subject_id ? 'text-white' : 'text-slate-900 group-hover:text-blue-900'"
                class="font-bold text-xs line-clamp-2 leading-snug"
              >
                {{ sub.subject_title || sub.subject_name }}
              </div>

              <!-- Live Classroom Upload Counters & Pending Badge -->
              <div class="flex items-center space-x-1 flex-wrap gap-1 pt-1.5 text-[9px] font-mono">
                <span 
                  v-if="sub.modules_count > 0" 
                  class="px-1.5 py-0.5 rounded font-semibold"
                  :class="selectedLmsSubject?.subject_id === sub.subject_id ? 'bg-white/20 text-white' : 'bg-blue-100/70 text-blue-900 border border-blue-200'"
                >
                  {{ sub.modules_count }} Handout{{ sub.modules_count > 1 ? 's' : '' }}
                </span>
                <span 
                  v-if="sub.assignments_count > 0" 
                  class="px-1.5 py-0.5 rounded font-semibold"
                  :class="selectedLmsSubject?.subject_id === sub.subject_id ? 'bg-white/20 text-white' : 'bg-purple-100/70 text-purple-900 border border-purple-200'"
                >
                  {{ sub.assignments_count }} Task{{ sub.assignments_count > 1 ? 's' : '' }}
                </span>
                <span 
                  v-if="sub.pending_assignments_count > 0" 
                  class="px-1.5 py-0.5 rounded font-bold"
                  :class="selectedLmsSubject?.subject_id === sub.subject_id ? 'bg-amber-400 text-amber-950 shadow-2xs' : 'bg-amber-100 text-amber-900 border border-amber-300'"
                >
                  ⚠️ {{ sub.pending_assignments_count }} Due
                </span>
              </div>
            </div>

            <div 
              :class="selectedLmsSubject?.subject_id === sub.subject_id ? 'text-white/70' : 'text-slate-400'"
              class="text-[10px] font-medium pt-1 border-t border-white/10 flex items-center justify-between"
            >
              <span>{{ sub.semester || `${sub.units || 1} Units` }}</span>
              <span class="font-bold">→</span>
            </div>
          </button>
        </div>

        <div v-if="displayedLmsSubjects.length === 0" class="py-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-slate-500 text-xs">
          No subjects found in this filter view.
        </div>
      </div>

      <!-- Feedback / Notification Banner -->
      <div v-if="lmsMessage" class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center justify-between shadow-xs">
        <div class="flex items-center space-x-2">
          <CheckCircle class="w-4 h-4 text-emerald-600 shrink-0" />
          <span>{{ lmsMessage }}</span>
        </div>
        <button @click="lmsMessage = ''" class="text-emerald-500 hover:text-emerald-700 font-bold cursor-pointer">✕</button>
      </div>

      <div v-if="lmsError" class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center justify-between shadow-xs">
        <div class="flex items-center space-x-2">
          <AlertCircle class="w-4 h-4 text-rose-600 shrink-0" />
          <span>{{ lmsError }}</span>
        </div>
        <button @click="lmsError = ''" class="text-rose-500 hover:text-rose-700 font-bold cursor-pointer">✕</button>
      </div>

      <!-- Active Subject Classroom Feed -->
      <div v-if="selectedLmsSubject && lmsClassData.class_info" class="space-y-6">
        
        <!-- Classroom Header Banner -->
        <div 
          class="p-6 rounded-3xl text-white shadow-md space-y-2 relative overflow-hidden transition"
          :class="isSubjectTermArchived(selectedLmsSubject) ? 'bg-gradient-to-r from-amber-950 via-slate-900 to-amber-950' : 'bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-950'"
        >
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative z-10">
            <div>
              <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white uppercase tracking-wider font-mono">
                  {{ lmsClassData.class_info.grade_level_name }} • {{ lmsClassData.class_info.section_name }}
                </span>
                <span 
                  v-if="isSubjectTermArchived(selectedLmsSubject)" 
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/30 text-amber-200 border border-amber-400/40 uppercase tracking-wider font-mono flex items-center space-x-1"
                >
                  <Lock class="w-3 h-3" />
                  <span>Archived Term (Read-Only)</span>
                </span>
                <span 
                  v-else-if="selectedLmsSubject.semester" 
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/30 text-blue-200 border border-blue-400/40 uppercase tracking-wider font-mono"
                >
                  Active Term
                </span>
              </div>
              <h2 class="text-xl sm:text-2xl font-bold mt-1.5">{{ lmsClassData.class_info.subject_name }}</h2>
              <p class="text-xs text-blue-200/90 mt-0.5" :class="{ 'text-amber-200/90': isSubjectTermArchived(selectedLmsSubject) }">
                Instructor: {{ lmsClassData.class_info.teacher_first_name ? `Prof. ${lmsClassData.class_info.teacher_first_name} ${lmsClassData.class_info.teacher_last_name}` : 'Subject Teacher' }}
                • Room: {{ lmsClassData.class_info.section_room || 'Designated Room' }}
                • Term: {{ selectedLmsSubject.semester || 'Full Year' }}
              </p>
            </div>

            <button 
              @click="loadStudentLmsContent(dashboardData.enrollment.section_id, selectedLmsSubject.subject_id)" 
              type="button" 
              class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-semibold backdrop-blur-xs transition flex items-center space-x-1.5 self-start cursor-pointer"
            >
              <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isLoadingLms }" />
              <span>Refresh Content</span>
            </button>
          </div>
        </div>

        <!-- ARCHIVED TERM READ-ONLY BANNER -->
        <div v-if="isSubjectTermArchived(selectedLmsSubject)" class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-950 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
          <div class="flex items-start space-x-3">
            <div class="p-2.5 rounded-xl bg-amber-100 text-amber-800 border border-amber-200 shrink-0 mt-0.5">
              <Lock class="w-5 h-5 text-amber-700" />
            </div>
            <div>
              <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                <h4 class="font-bold text-sm text-slate-900">Archived Academic Term (Read-Only Review Mode)</h4>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-amber-100 text-amber-900 border border-amber-300">
                  {{ selectedLmsSubject.semester }} Concluded
                </span>
              </div>
              <p class="text-slate-600 text-xs mt-1 leading-relaxed max-w-3xl">
                This subject was completed in <strong>{{ selectedLmsSubject.semester }}</strong>. You can freely review lecture handouts, download modules, and inspect previous submissions. To safeguard DepEd grade freeze integrity, new assignment submissions are closed.
              </p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          
          <!-- LEFT 2 COLS: LEARNING MODULES & ASSIGNMENTS -->
          <div class="lg:col-span-2 space-y-6">
            
            <!-- SECTION 1: LEARNING MODULES & HANDOUTS -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center space-x-2">
                  <BookOpen class="w-4 h-4 text-blue-800" />
                  <h3 class="text-sm font-bold text-slate-900">Learning Handouts & Modules</h3>
                </div>
                <span class="text-xs text-slate-400 font-mono">{{ lmsClassData.modules.length }} Available</span>
              </div>

              <div class="space-y-3">
                <div v-if="lmsClassData.modules.length === 0" class="py-8 text-center text-slate-400 text-xs">
                  No learning modules uploaded yet for this subject.
                </div>

                <div 
                  v-for="m in lmsClassData.modules" 
                  :key="m.id"
                  class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:border-blue-400 hover:shadow-xs transition flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                >
                  <div class="space-y-1">
                    <div class="flex items-center space-x-2">
                      <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-50 text-blue-900 font-mono">
                        {{ m.quarter }} • {{ m.week_label }}
                      </span>
                      <h4 class="font-bold text-slate-900 text-xs">{{ m.title }}</h4>
                    </div>
                    <p v-if="m.description" class="text-[11px] text-slate-500 line-clamp-1">{{ m.description }}</p>
                  </div>

                  <div class="shrink-0 flex items-center space-x-2">
                    <button 
                      v-if="m.file_path" 
                      type="button" 
                      @click="openModulePreview(m)" 
                      class="px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-900 border border-blue-200 font-semibold text-xs transition flex items-center space-x-1.5 shadow-2xs cursor-pointer"
                    >
                      <Eye class="w-3.5 h-3.5 text-blue-700" />
                      <span>View Handout</span>
                    </button>
                    <a 
                      v-if="m.file_path" 
                      :href="getFileUrl(m.file_path)" 
                      target="_blank" 
                      download 
                      class="px-3 py-1.5 rounded-xl bg-blue-900 hover:bg-blue-800 text-white font-semibold text-xs transition flex items-center space-x-1.5 shadow-2xs cursor-pointer"
                    >
                      <Download class="w-3.5 h-3.5" />
                      <span>Download ({{ m.file_size_kb || 0 }} KB)</span>
                    </a>
                    <a 
                      v-else-if="m.external_url" 
                      :href="m.external_url" 
                      target="_blank" 
                      rel="noopener noreferrer"
                      class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition flex items-center space-x-1.5 shadow-2xs cursor-pointer"
                    >
                      <ExternalLink class="w-3.5 h-3.5" />
                      <span>Open Link</span>
                    </a>
                  </div>
                </div>
              </div>
            </div>

            <!-- SECTION 2: ASSIGNMENTS & HOMEWORK SUBMISSION -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center space-x-2">
                  <FileText class="w-4 h-4 text-blue-800" />
                  <h3 class="text-sm font-bold text-slate-900">Assigned Tasks & Submissions</h3>
                </div>
                <span class="text-xs text-slate-400 font-mono">{{ lmsClassData.assignments.length }} Tasks</span>
              </div>

              <div class="space-y-4">
                <div v-if="lmsClassData.assignments.length === 0" class="py-8 text-center text-slate-400 text-xs">
                  No active assignments for this subject.
                </div>

                <div 
                  v-for="asg in lmsClassData.assignments" 
                  :key="asg.id"
                  class="p-5 rounded-2xl border border-slate-200 bg-white hover:border-blue-400 hover:shadow-xs transition space-y-3.5"
                >
                  <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                    <div class="space-y-1">
                      <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                        <span 
                          class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider"
                          :class="asg.task_type === 'Performance Task' ? 'bg-purple-50 text-purple-800 border border-purple-200' : 'bg-blue-50 text-blue-800 border border-blue-200'"
                        >
                          {{ asg.task_type }}
                        </span>
                        <span v-if="asg.submission_format === 'quiz'" class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-violet-50 text-violet-800 border border-violet-200 flex items-center space-x-1">
                          <ListChecks class="w-3 h-3 text-violet-600" />
                          <span>Interactive Quiz</span>
                        </span>
                        <span class="text-xs font-mono font-bold text-slate-700">{{ asg.max_score }} Pts</span>
                        <span class="text-slate-300">•</span>
                        <span class="text-xs text-slate-500">{{ asg.quarter }}</span>
                      </div>
                      <h4 class="font-extrabold text-slate-900 text-sm">{{ asg.title }}</h4>
                      <p class="text-xs text-slate-600 whitespace-pre-line leading-relaxed">{{ asg.instructions }}</p>
                    </div>

                    <!-- Submission Status Badge -->
                    <div class="shrink-0 text-right space-y-1.5">
                      <div class="text-[10px] text-slate-400">Deadline:</div>
                      <div 
                        class="text-xs font-mono font-bold flex items-center justify-end space-x-1"
                        :class="isPastDeadline(asg.due_date) ? 'text-rose-600' : 'text-slate-700'"
                      >
                        <Clock v-if="!isPastDeadline(asg.due_date)" class="w-3 h-3 text-slate-400" />
                        <AlertTriangle v-else class="w-3 h-3 text-rose-500" />
                        <span>{{ formatDeadline(asg.due_date) }}</span>
                      </div>

                      <!-- Past Deadline Badge -->
                      <div v-if="isPastDeadline(asg.due_date) && !asg.my_submission" class="text-right">
                        <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                          <AlertTriangle class="w-2.5 h-2.5 text-rose-600" />
                          <span>Past Deadline (Overdue)</span>
                        </span>
                      </div>
                      <div v-else-if="isPastDeadline(asg.due_date) && asg.my_submission && asg.my_submission.status !== 'Graded'" class="text-right">
                        <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                          <Clock class="w-2.5 h-2.5 text-amber-600" />
                          <span>Submitted Late</span>
                        </span>
                      </div>
                      
                      <!-- Status Indicator -->
                      <div>
                        <span 
                          v-if="isAssignmentGraded(asg)" 
                          class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300 block text-center"
                        >
                          Graded: {{ asg.my_submission.score }} / {{ asg.max_score }}
                        </span>
                        <span 
                          v-else-if="asg.my_submission?.status === 'Submitted'" 
                          class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200 block text-center"
                        >
                          Submitted
                        </span>
                        <span 
                          v-else-if="asg.my_submission?.status === 'Late'" 
                          class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 block text-center"
                        >
                          Submitted Late
                        </span>
                        <span 
                          v-else-if="isPastDeadline(asg.due_date)" 
                          class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-900 border border-rose-300 block text-center"
                        >
                          Missing / Overdue
                        </span>
                        <span 
                          v-else 
                          class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-200 block text-center"
                        >
                          Pending Work
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Submission Details & Teacher Feedback Note -->
                  <div v-if="asg.my_submission" class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                      <span>Submitted on {{ new Date(asg.my_submission.submitted_at).toLocaleString() }}</span>
                      <a 
                        v-if="asg.my_submission.submission_file" 
                        :href="getFileUrl(asg.my_submission.submission_file)" 
                        target="_blank" 
                        download
                        class="text-blue-700 hover:text-blue-900 font-semibold underline flex items-center space-x-1"
                      >
                        <Paperclip class="w-3 h-3" />
                        <span>View My Upload</span>
                      </a>
                    </div>

                    <div v-if="asg.my_submission.submission_text" class="text-slate-700 italic text-[11px]">
                      "{{ asg.my_submission.submission_text }}"
                    </div>

                    <div v-if="asg.my_submission.teacher_feedback" class="pt-2 border-t border-slate-200/80 text-blue-950 font-medium">
                      <strong>Teacher Feedback:</strong> {{ asg.my_submission.teacher_feedback }}
                    </div>
                  </div>

                  <!-- Action Buttons -->
                  <div class="pt-2 flex items-center justify-end space-x-2">
                    <!-- QUIZ FORMAT BUTTONS -->
                    <template v-if="asg.submission_format === 'quiz'">
                      <!-- 1. If Graded -->
                      <button 
                        v-if="isAssignmentGraded(asg)"
                        @click="openQuizTakerModal(asg)"
                        type="button"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-2xs transition flex items-center space-x-1.5 cursor-pointer"
                      >
                        <ListChecks class="w-3.5 h-3.5 text-emerald-700" />
                        <span>View Results ({{ asg.my_submission.score ?? asg.my_submission.auto_graded_score }} / {{ asg.max_score }} Pts)</span>
                      </button>

                      <!-- 2. If Submitted (Pending Essay Review) -->
                      <button 
                        v-else-if="asg.my_submission"
                        @click="openQuizTakerModal(asg)"
                        type="button"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-violet-50 hover:bg-violet-100 text-violet-800 border border-violet-300 shadow-2xs transition flex items-center space-x-1.5 cursor-pointer"
                      >
                        <Eye class="w-3.5 h-3.5 text-violet-700" />
                        <span>View Submitted Answers</span>
                      </button>

                      <!-- 3. If Subject Term is Archived -->
                      <div 
                        v-else-if="isSubjectTermArchived(selectedLmsSubject)" 
                        class="px-3.5 py-2 rounded-xl bg-slate-100 text-slate-500 text-xs font-semibold flex items-center space-x-1.5 border border-slate-200 cursor-not-allowed select-none"
                      >
                        <Lock class="w-3.5 h-3.5 text-slate-400" />
                        <span>Quiz Closed (Archived Term)</span>
                      </div>

                      <!-- 4. Not yet submitted -> Take Quiz -->
                      <button 
                        v-else
                        @click="openQuizTakerModal(asg)" 
                        type="button" 
                        class="px-4 py-2 rounded-xl text-xs font-bold shadow-2xs transition flex items-center space-x-1.5 cursor-pointer"
                        :class="isPastDeadline(asg.due_date) ? 'bg-amber-700 hover:bg-amber-800 text-white' : 'bg-emerald-800 hover:bg-emerald-700 text-white'"
                      >
                        <ListChecks class="w-3.5 h-3.5" />
                        <span>{{ isPastDeadline(asg.due_date) ? 'Take Quiz (Late)' : 'Take Online Quiz' }}</span>
                      </button>
                    </template>

                    <!-- STANDARD TURN-IN FORMAT BUTTONS -->
                    <template v-else>
                      <!-- 1. If Assignment is Graded: Lock it permanently -->
                      <div 
                        v-if="isAssignmentGraded(asg)"
                        class="px-3.5 py-2 rounded-xl bg-emerald-50 text-emerald-800 text-xs font-semibold flex items-center space-x-1.5 border border-emerald-200 select-none shadow-2xs"
                        title="This assignment has already been evaluated and graded by the teacher. Submissions are finalized and locked."
                      >
                        <Lock class="w-3.5 h-3.5 text-emerald-700" />
                        <span>Graded & Locked</span>
                      </div>

                      <!-- 2. If Subject Term is Archived -->
                      <div 
                        v-else-if="isSubjectTermArchived(selectedLmsSubject)" 
                        class="px-3.5 py-2 rounded-xl bg-slate-100 text-slate-500 text-xs font-semibold flex items-center space-x-1.5 border border-slate-200 cursor-not-allowed select-none"
                        title="New submissions are disabled because this academic term has concluded"
                      >
                        <Lock class="w-3.5 h-3.5 text-slate-400" />
                        <span>Submissions Closed (Archived Term)</span>
                      </div>

                      <!-- 3. Otherwise: Submit or Resubmit Work with Deadline Awareness -->
                      <button 
                        v-else
                        @click="openSubmitModal(asg)" 
                        type="button" 
                        class="px-4 py-2 rounded-xl text-xs font-semibold shadow-2xs transition flex items-center space-x-1.5 cursor-pointer"
                        :class="isPastDeadline(asg.due_date) ? 'bg-amber-700 hover:bg-amber-800 text-white' : 'bg-blue-900 hover:bg-blue-800 text-white'"
                      >
                        <UploadCloud class="w-3.5 h-3.5" />
                        <span>{{ asg.my_submission ? 'Resubmit / Edit Work' : (isPastDeadline(asg.due_date) ? 'Turn In Late' : 'Submit Work') }}</span>
                      </button>
                    </template>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- RIGHT COLUMN: CLASS NOTICES & ANNOUNCEMENTS -->
          <div class="space-y-4">
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center space-x-2">
                  <MessageSquare class="w-4 h-4 text-blue-800" />
                  <h3 class="text-sm font-bold text-slate-900">Teacher Announcements</h3>
                </div>
                <span class="text-xs text-slate-400 font-mono">{{ lmsClassData.announcements.length }}</span>
              </div>

              <div class="space-y-3">
                <div v-if="lmsClassData.announcements.length === 0" class="py-6 text-center text-slate-400 text-xs">
                  No announcements posted.
                </div>

                <div 
                  v-for="a in lmsClassData.announcements" 
                  :key="a.id"
                  :class="[
                    'p-4 rounded-2xl border transition space-y-2',
                    a.is_pinned ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200 bg-slate-50/50'
                  ]"
                >
                  <div class="flex items-center justify-between">
                    <h4 class="font-bold text-slate-900 text-xs">{{ a.title }}</h4>
                    <span v-if="a.is_pinned" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-900 font-mono">
                      Pinned
                    </span>
                  </div>
                  <p class="text-[11px] text-slate-600 whitespace-pre-line leading-relaxed">{{ a.content }}</p>
                  <div class="text-[10px] text-slate-400 pt-1 border-t border-slate-100">
                    {{ new Date(a.created_at).toLocaleDateString() }}
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- MODAL: SUBMIT ASSIGNMENT WORK -->
    <div v-if="showSubmitModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200 text-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-900 font-mono">
              {{ activeSubmittingAssignment?.task_type }} • Max {{ activeSubmittingAssignment?.max_score }} Pts
            </span>
            <h3 class="text-base font-extrabold text-slate-900 mt-1">{{ activeSubmittingAssignment?.title }}</h3>
          </div>
          <button @click="showSubmitModal = false" class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold">✕</button>
        </div>

        <form @submit.prevent="submitStudentWork()" class="space-y-3.5">
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 text-[11px] leading-relaxed">
            <strong>Instructions:</strong> {{ activeSubmittingAssignment?.instructions }}
          </div>

          <!-- Deadline Display & Past Deadline Warning Banner -->
          <div 
            v-if="activeSubmittingAssignment?.due_date"
            :class="[
              'p-3.5 rounded-xl border text-xs flex items-start space-x-2.5',
              isPastDeadline(activeSubmittingAssignment.due_date) 
                ? 'bg-rose-50 border-rose-200 text-rose-950' 
                : 'bg-blue-50 border-blue-200 text-blue-950'
            ]"
          >
            <AlertTriangle v-if="isPastDeadline(activeSubmittingAssignment.due_date)" class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
            <Clock v-else class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" />
            <div class="space-y-0.5 text-[11px] leading-relaxed">
              <div class="font-bold flex items-center space-x-1">
                <span>{{ isPastDeadline(activeSubmittingAssignment.due_date) ? '⚠️ Past Deadline Warning' : 'Submission Deadline' }}</span>
              </div>
              <p v-if="isPastDeadline(activeSubmittingAssignment.due_date)">
                The deadline for this assignment was <strong>{{ formatDeadline(activeSubmittingAssignment.due_date) }}</strong>. 
                Submitting your work now will be marked as <strong class="text-rose-700">Late</strong> and recorded in your teacher's class log.
              </p>
              <p v-else class="text-blue-800">
                Due on <strong>{{ formatDeadline(activeSubmittingAssignment.due_date) }}</strong>. Please ensure your submission is finalized before the cutoff.
              </p>
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Upload Work File (PDF, Word, PPTX, Image - Max 15MB)</label>
            <input 
              type="file" 
              @change="handleSubmissionFileSelect" 
              class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-900 hover:file:bg-blue-100 cursor-pointer"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Student Notes / Remarks / Written Response</label>
            <textarea 
              v-model="submissionForm.text" 
              rows="3" 
              placeholder="Type your explanation or summary of work..." 
              class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-600 focus:outline-none"
            ></textarea>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
            <button type="button" @click="showSubmitModal = false" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer">Cancel</button>
            <button 
              type="submit" 
              :disabled="isSubmittingWork" 
              class="px-5 py-2.5 rounded-xl font-semibold bg-blue-900 hover:bg-blue-800 disabled:opacity-50 text-white shadow-xs transition flex items-center space-x-1.5 cursor-pointer"
            >
              <Send class="w-3.5 h-3.5" />
              <span>{{ isSubmittingWork ? 'Submitting...' : 'Turn In Work' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: INTERACTIVE ONLINE QUIZ TAKER                      -->
    <!-- ======================================================== -->
    <div v-if="showQuizTakerModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] flex flex-col p-6 sm:p-7 shadow-2xl border border-slate-200 text-xs space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 shrink-0">
          <div>
            <div class="flex items-center space-x-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-violet-50 text-violet-800 border border-violet-200 font-mono">
                {{ activeQuizAssignment?.task_type }} • Max {{ activeQuizAssignment?.max_score }} Pts
              </span>
              <span v-if="activeQuizAssignment?.my_submission" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                {{ activeQuizAssignment.my_submission.status === 'Graded' ? 'Graded / Finalized' : 'Submitted' }}
              </span>
            </div>
            <h3 class="text-base font-extrabold text-slate-900 mt-1">{{ activeQuizAssignment?.title }}</h3>
          </div>
          <button @click="showQuizTakerModal = false" class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold">✕</button>
        </div>

        <!-- Instructions & Deadline Banner -->
        <div class="shrink-0 space-y-2">
          <div v-if="activeQuizAssignment?.instructions" class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 text-[11px] leading-relaxed">
            <strong>Instructions:</strong> {{ activeQuizAssignment.instructions }}
          </div>

          <div 
            v-if="!activeQuizAssignment?.my_submission && activeQuizAssignment?.due_date"
            :class="[
              'p-2.5 rounded-xl border text-[11px] flex items-center space-x-2',
              isPastDeadline(activeQuizAssignment.due_date) ? 'bg-rose-50 border-rose-200 text-rose-950 font-medium' : 'bg-blue-50 border-blue-200 text-blue-950'
            ]"
          >
            <AlertTriangle v-if="isPastDeadline(activeQuizAssignment.due_date)" class="w-3.5 h-3.5 text-rose-600 shrink-0" />
            <Clock v-else class="w-3.5 h-3.5 text-blue-600 shrink-0" />
            <span>{{ isPastDeadline(activeQuizAssignment.due_date) ? 'Past Deadline: Quiz submission will be recorded as Late.' : `Deadline: ${formatDeadline(activeQuizAssignment.due_date)}` }}</span>
          </div>

          <!-- Submission Review Banner -->
          <div v-if="activeQuizAssignment?.my_submission">
            <!-- 1. Officially Graded -->
            <div 
              v-if="activeQuizAssignment.my_submission.status === 'Graded' && activeQuizAssignment.my_submission.score !== null" 
              class="p-3.5 rounded-2xl bg-emerald-50/80 border border-emerald-200 text-emerald-950 flex items-center justify-between"
            >
              <div class="space-y-0.5">
                <div class="font-bold text-xs flex items-center space-x-1.5">
                  <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                  <span>Official Score: {{ activeQuizAssignment.my_submission.score }} / {{ activeQuizAssignment.max_score }} Points</span>
                </div>
                <div v-if="activeQuizAssignment.my_submission.teacher_feedback" class="text-[11px] text-emerald-800">
                  <strong>Teacher Feedback:</strong> {{ activeQuizAssignment.my_submission.teacher_feedback }}
                </div>
              </div>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white border border-emerald-300 text-emerald-800 font-mono">
                Graded
              </span>
            </div>

            <!-- 2. Pending Teacher Essay Review -->
            <div 
              v-else 
              class="p-3.5 rounded-2xl bg-amber-50/80 border border-amber-300 text-amber-950 flex items-center justify-between"
            >
              <div class="space-y-0.5">
                <div class="font-bold text-xs flex items-center space-x-1.5">
                  <Clock class="w-4 h-4 text-amber-600" />
                  <span>Submission Received • Essay Pending Teacher Evaluation</span>
                </div>
                <div class="text-[11px] text-amber-800">
                  Objective answers recorded. Your official score will be released once your teacher evaluates your essay responses.
                </div>
              </div>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 border border-amber-300 text-amber-900 font-mono">
                Under Review
              </span>
            </div>
          </div>
        </div>

        <!-- Questions List Body -->
        <div class="overflow-y-auto flex-1 space-y-4 pr-1">
          <div 
            v-for="(q, idx) in activeQuizAssignment?.quiz_questions" 
            :key="q.id"
            class="p-4 rounded-2xl border space-y-3 bg-white"
            :class="[
              activeQuizAssignment?.my_submission?.quiz_answers?.[q.id]
                ? (activeQuizAssignment.my_submission.quiz_answers[q.id].type === 'essay'
                    ? (activeQuizAssignment.my_submission.status === 'Graded' && activeQuizAssignment.my_submission.quiz_answers[q.id].points_earned !== null ? 'border-purple-300 bg-purple-50/20' : 'border-amber-300 bg-amber-50/20')
                    : (activeQuizAssignment.my_submission.quiz_answers[q.id].is_correct ? 'border-emerald-300 bg-emerald-50/20' : 'border-rose-300 bg-rose-50/20'))
                : 'border-slate-200'
            ]"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <span class="w-6 h-6 rounded-full bg-slate-900 text-white text-[11px] font-bold flex items-center justify-center font-mono">
                  {{ idx + 1 }}
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

              <!-- Question Score / Result Tag -->
              <div>
                <span v-if="activeQuizAssignment?.my_submission?.quiz_answers?.[q.id]" class="font-bold text-[11px]">
                  <template v-if="activeQuizAssignment.my_submission.quiz_answers[q.id].type === 'essay'">
                    <span v-if="activeQuizAssignment.my_submission.status === 'Graded' && activeQuizAssignment.my_submission.quiz_answers[q.id].points_earned !== null" class="text-purple-700 font-mono">
                      ✓ +{{ activeQuizAssignment.my_submission.quiz_answers[q.id].points_earned }} / {{ q.points }} pts
                    </span>
                    <span v-else class="text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full border border-amber-300 text-[10px]">
                      ⏳ Pending Evaluation (Max {{ q.points }} pts)
                    </span>
                  </template>
                  <span v-else-if="activeQuizAssignment.my_submission.quiz_answers[q.id].is_correct" class="text-emerald-700 font-mono">
                    ✓ +{{ activeQuizAssignment.my_submission.quiz_answers[q.id].points_earned }} pts
                  </span>
                  <span v-else class="text-rose-600 font-mono">
                    ✕ 0 / {{ q.points }} pts
                  </span>
                </span>
                <span v-else class="font-mono text-slate-500 font-bold text-[11px]">
                  {{ q.points }} {{ q.points === 1 ? 'pt' : 'pts' }}
                </span>
              </div>
            </div>

            <!-- Question Prompt -->
            <p class="font-bold text-slate-900 text-xs leading-relaxed whitespace-pre-line">{{ q.question }}</p>

            <!-- 1. MULTIPLE CHOICE -->
            <div v-if="q.type === 'multiple_choice'" class="space-y-2 pt-1">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <label 
                  v-for="(opt, optIdx) in q.options" 
                  :key="optIdx"
                  :class="[
                    'p-2.5 rounded-xl border flex items-center space-x-2.5 transition select-none',
                    isQuizReadOnly 
                      ? 'cursor-default' 
                      : 'cursor-pointer hover:border-violet-400 hover:bg-violet-50/50',
                    quizAnswersState[q.id] === opt 
                      ? 'border-violet-600 bg-violet-50 text-violet-950 font-bold shadow-2xs' 
                      : 'border-slate-200 bg-white text-slate-700'
                  ]"
                >
                  <input 
                    type="radio" 
                    :name="`quiz_q_${q.id}`" 
                    :value="opt" 
                    :disabled="isQuizReadOnly"
                    v-model="quizAnswersState[q.id]" 
                    class="text-violet-600 focus:ring-violet-500 shrink-0" 
                  />
                  <span class="font-mono font-bold text-slate-400 text-[11px] shrink-0">
                    {{ ['A', 'B', 'C', 'D'][optIdx] || (optIdx + 1) }}.
                  </span>
                  <span class="text-xs">{{ opt }}</span>
                </label>
              </div>

              <!-- Read-only correctness banner -->
              <div v-if="activeQuizAssignment?.my_submission?.quiz_answers?.[q.id] && activeQuizAssignment.my_submission.status === 'Graded'" class="mt-1 text-[11px]">
                <div v-if="!activeQuizAssignment.my_submission.quiz_answers[q.id].is_correct" class="text-rose-600 font-semibold">
                  Correct Answer: <strong class="underline">{{ activeQuizAssignment.my_submission.quiz_answers[q.id].correct_answer }}</strong>
                </div>
              </div>
            </div>

            <!-- 2. IDENTIFICATION -->
            <div v-else-if="q.type === 'identification'" class="space-y-1.5 pt-1">
              <input 
                v-model="quizAnswersState[q.id]" 
                type="text" 
                :disabled="isQuizReadOnly"
                placeholder="Type your exact answer here..." 
                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-violet-500 focus:outline-none disabled:bg-slate-50 disabled:text-slate-700"
              />
              <div v-if="activeQuizAssignment?.my_submission?.quiz_answers?.[q.id] && activeQuizAssignment.my_submission.status === 'Graded' && !activeQuizAssignment.my_submission.quiz_answers[q.id].is_correct" class="text-[11px] text-rose-600 font-semibold">
                Correct Answer: <strong class="underline">{{ activeQuizAssignment.my_submission.quiz_answers[q.id].correct_answer }}</strong>
              </div>
            </div>

            <!-- 3. ESSAY -->
            <div v-else-if="q.type === 'essay'" class="space-y-1.5 pt-1">
              <textarea 
                v-model="quizAnswersState[q.id]" 
                rows="3" 
                :disabled="isQuizReadOnly"
                placeholder="Write your comprehensive essay response here..." 
                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-violet-500 focus:outline-none disabled:bg-slate-50 disabled:text-slate-700"
              ></textarea>
              <div class="flex items-center justify-between text-[10px] text-slate-400">
                <span>Evaluated manually by instructor</span>
                <span>{{ (quizAnswersState[q.id] || '').trim().split(/\s+/).filter(Boolean).length }} words</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between shrink-0">
          <div class="text-[11px] font-semibold text-slate-500">
            <span v-if="!isQuizReadOnly">
              Answered {{ answeredQuizCount }} / {{ activeQuizAssignment?.quiz_questions?.length || 0 }} questions
            </span>
          </div>

          <div class="flex items-center space-x-2">
            <button 
              type="button" 
              @click="showQuizTakerModal = false" 
              class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer"
            >
              {{ isQuizReadOnly ? 'Close' : 'Cancel' }}
            </button>
            <button 
              v-if="!isQuizReadOnly"
              type="button" 
              @click="submitStudentQuiz()"
              :disabled="isSubmittingWork" 
              class="px-5 py-2.5 rounded-xl font-bold bg-emerald-800 hover:bg-emerald-700 disabled:opacity-50 text-white shadow-xs transition flex items-center space-x-1.5 cursor-pointer"
            >
              <Send class="w-3.5 h-3.5" />
              <span>{{ isSubmittingWork ? 'Submitting & Grading...' : 'Submit Answers' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: SUBMIT NEW DOCUMENT REQUEST -->
    <div v-if="showStudentDocModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-slate-200 text-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="text-base font-extrabold text-slate-900">Request Official Document</h3>
            <p class="text-[11px] text-slate-500">Submitted directly to the School Records Custodian.</p>
          </div>
          <button @click="showStudentDocModal = false" class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold">✕</button>
        </div>

        <form @submit.prevent="submitStudentDocRequest" class="space-y-3">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Document Type *</label>
            <select v-model="studentDocForm.document_type" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white" required>
              <option value="Certificate of Enrollment">Certificate of Enrollment (COE)</option>
              <option value="Good Moral Character">Certificate of Good Moral Character</option>
              <option value="Certified True Copy of SF9 / Form 138">Certified True Copy of SF9 (Report Card)</option>
              <option value="Certificate of Academic Ranking">Certificate of Academic Ranking</option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Purpose / Intended Use *</label>
            <input 
              v-model="studentDocForm.purpose" 
              type="text" 
              placeholder="e.g. Scholarship Application / Passport Renewal / Transfer" 
              class="w-full px-3 py-2 rounded-xl border border-slate-300"
              required 
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Number of Copies *</label>
            <input 
              v-model.number="studentDocForm.copies" 
              type="number" 
              min="1" 
              max="5" 
              class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono"
              required 
            />
          </div>

          <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
            <button type="button" @click="showStudentDocModal = false" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer">Cancel</button>
            <button type="submit" class="px-5 py-2.5 rounded-xl font-semibold bg-blue-900 hover:bg-blue-800 text-white shadow-xs transition cursor-pointer">
              Submit Request
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- PAYMONGO PAYMENT MODAL (FOR ENROLLED STUDENTS)           -->
    <!-- ======================================================== -->
    <div v-if="showPaymongoModal" class="no-print fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 sm:p-7 shadow-2xl border border-slate-200 text-xs space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-2.5">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-700">
              <CreditCard class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-base font-bold text-slate-900">Pay Tuition Balance Online</h3>
              <p class="text-[11px] text-slate-500">Secure real-time checkout powered by PayMongo</p>
            </div>
          </div>
          <button @click="showPaymongoModal = false" class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold cursor-pointer">✕</button>
        </div>

        <!-- Error alert -->
        <div v-if="paymongoError" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <AlertCircle class="w-4 h-4 text-rose-600 shrink-0" />
            <span>{{ paymongoError }}</span>
          </div>
          <button @click="paymongoError = ''" class="text-rose-500 font-bold cursor-pointer">✕</button>
        </div>

        <!-- Student & Account Details Card -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
          <div class="flex items-center justify-between text-[11px]">
            <span class="text-slate-500">Student Learner:</span>
            <strong class="text-slate-900">{{ studentDisplayName }} ({{ studentDisplayId }})</strong>
          </div>
          <div class="flex items-center justify-between text-[11px]">
            <span class="text-slate-500">Class Section:</span>
            <span class="text-slate-800 font-semibold">{{ dashboardData.enrollment?.section_name }}</span>
          </div>
          <div class="flex items-center justify-between text-xs pt-1.5 border-t border-slate-200">
            <span class="text-slate-600 font-bold">Outstanding Balance:</span>
            <strong class="text-rose-600 font-mono font-bold text-sm">₱{{ remainingBalance.toLocaleString('en-US', {minimumFractionDigits: 2}) }}</strong>
          </div>
        </div>

        <!-- Amount Selection -->
        <div class="space-y-2.5">
          <div class="flex items-center justify-between">
            <label class="font-bold text-slate-800">Select or Enter Payment Amount *</label>
            <span class="text-[10px] text-slate-400 font-mono">Min ₱20.00</span>
          </div>

          <!-- Quick Select Preset Chips -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <button 
              type="button" 
              @click="setQuickAmount('full')"
              :class="paymongoAmount === remainingBalance ? 'bg-emerald-700 text-white font-bold shadow-2xs border-emerald-700' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
              class="py-2 px-2.5 rounded-xl border text-[11px] transition text-center cursor-pointer font-mono"
            >
              Full Balance
            </button>
            <button 
              v-for="amt in [1000, 2500, 5000].filter(a => a <= remainingBalance)" 
              :key="amt"
              type="button" 
              @click="setQuickAmount(amt)"
              :class="paymongoAmount === amt ? 'bg-emerald-700 text-white font-bold shadow-2xs border-emerald-700' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
              class="py-2 px-2.5 rounded-xl border text-[11px] transition text-center cursor-pointer font-mono"
            >
              ₱{{ amt.toLocaleString() }}
            </button>
          </div>

          <!-- Custom Amount Input -->
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-sm">₱</span>
            <input 
              v-model.number="paymongoAmount" 
              type="number" 
              min="20" 
              :max="remainingBalance" 
              step="50" 
              required 
              class="w-full pl-8 pr-4 py-2.5 rounded-xl border border-slate-300 font-mono font-bold text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-none" 
            />
          </div>

          <!-- Projected Balance Preview -->
          <div class="p-3 rounded-xl bg-emerald-50/60 border border-emerald-100 flex items-center justify-between text-[11px]">
            <span class="text-slate-600">Balance after this payment:</span>
            <strong class="font-mono text-emerald-900 font-bold">
              ₱{{ Math.max(0, remainingBalance - (Number(paymongoAmount) || 0)).toLocaleString('en-US', {minimumFractionDigits: 2}) }}
            </strong>
          </div>
        </div>

        <!-- Accepted Channels Badges -->
        <div class="space-y-1.5 pt-1">
          <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Accepted Payment Channels</div>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-[10px] font-bold">
            <div class="p-2 rounded-xl bg-blue-50 border border-blue-200 text-blue-700">GCash</div>
            <div class="p-2 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700">Maya</div>
            <div class="p-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-700">Credit / Debit</div>
            <div class="p-2 rounded-xl bg-purple-50 border border-purple-200 text-purple-700">Billease</div>
          </div>
        </div>

        <!-- Security Notice -->
        <p class="text-[10px] text-slate-400 leading-normal flex items-start space-x-1.5">
          <ShieldCheck class="w-3.5 h-3.5 text-emerald-600 shrink-0 mt-0.5" />
          <span>You will be securely redirected to PayMongo to authenticate your wallet or card. Upon authorization, your Official Receipt will be generated automatically.</span>
        </p>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
          <button 
            type="button" 
            @click="showPaymongoModal = false" 
            class="px-4 py-2.5 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer"
          >
            Cancel
          </button>
          <button 
            type="button" 
            @click="initiateStudentPaymongoCheckout" 
            :disabled="paymongoIsSubmitting || !paymongoAmount || paymongoAmount < 20 || paymongoAmount > remainingBalance + 0.01" 
            class="px-5 py-2.5 rounded-xl font-bold bg-emerald-700 hover:bg-emerald-600 disabled:opacity-50 text-white shadow-xs transition flex items-center space-x-2 cursor-pointer"
          >
            <RefreshCw v-if="paymongoIsSubmitting" class="w-4 h-4 animate-spin" />
            <span>{{ paymongoIsSubmitting ? 'Connecting...' : `Proceed to Pay ₱${Number(paymongoAmount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})} →` }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- PAYMONGO VERIFYING OVERLAY -->
    <div v-if="isVerifyingPaymongo" class="no-print fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-sm w-full p-6 text-center space-y-4 shadow-2xl border border-slate-100">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600">
          <RefreshCw class="w-7 h-7 animate-spin" />
        </div>
        <div>
          <h3 class="text-base font-bold text-slate-900">Verifying PayMongo Payment...</h3>
          <p class="text-xs text-slate-500 mt-1">
            Please wait while we authenticate your transaction with PayMongo and issue your Official Receipt.
          </p>
        </div>
      </div>
    </div>

    <!-- PAYMONGO PAYMENT SUCCESS MODAL -->
    <div v-if="paymongoVerifyResult" class="no-print fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-slate-100 space-y-5 text-center">
        <div class="w-16 h-16 mx-auto rounded-full bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600">
          <CheckCircle2 class="w-8 h-8" />
        </div>

        <div>
          <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-100 text-emerald-800 font-mono">
            Payment Verified & Credited
          </span>
          <h3 class="text-lg font-extrabold text-slate-900 mt-2">Tuition Payment Successful</h3>
          <p class="text-xs text-slate-500 mt-0.5">Your official receipt has been issued and credited to your Statement of Account.</p>
        </div>

        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 text-xs space-y-2 text-left font-mono">
          <div class="flex justify-between">
            <span class="text-slate-500 font-sans">Official Receipt:</span>
            <strong class="text-slate-900 font-bold">{{ paymongoVerifyResult.or_number }}</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-sans">Amount Paid:</span>
            <strong class="text-emerald-700 font-bold">₱{{ Number(paymongoVerifyResult.amount_paid || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-sans">Channel / Method:</span>
            <strong class="text-slate-800 font-semibold">{{ paymongoVerifyResult.payment_channel }}</strong>
          </div>
          <div class="flex justify-between border-t border-slate-200 pt-1.5 font-bold">
            <span class="text-slate-500 font-sans">Updated Remaining:</span>
            <strong class="text-rose-600">₱{{ Number(paymongoVerifyResult.remaining_balance || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</strong>
          </div>
        </div>

        <button 
          @click="paymongoVerifyResult = null; loadDashboard();" 
          type="button" 
          class="w-full py-3 rounded-xl font-bold bg-emerald-700 hover:bg-emerald-600 text-white text-xs shadow-md shadow-emerald-700/20 transition cursor-pointer"
        >
          View Updated Statement of Account
        </button>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: SCHOOL EVENT DETAILS                              -->
    <!-- ======================================================== -->
    <div v-if="showEventDetailModal && selectedEventDetail" class="no-print fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200 text-xs space-y-5 animate-in fade-in zoom-in-95 duration-150">
        <!-- Modal Top Bar -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-2">
            <span class="px-2.5 py-1 rounded-xl text-[10px] font-extrabold uppercase tracking-wider" :class="getEventCategoryMeta(selectedEventDetail.event_category).badge">
              {{ selectedEventDetail.event_category }}
            </span>
            <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-600 font-mono text-[10px] font-bold">
              {{ selectedEventDetail.target_audience || 'All Campuses' }}
            </span>
            <span :class="['px-2 py-0.5 rounded text-[10px] font-mono font-bold border', getEventStatusText(selectedEventDetail.start_date, selectedEventDetail.end_date).class]">
              {{ getEventStatusText(selectedEventDetail.start_date, selectedEventDetail.end_date).label }}
            </span>
          </div>
          <button @click="showEventDetailModal = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold cursor-pointer transition">✕</button>
        </div>

        <!-- Title & Description -->
        <div>
          <h3 class="text-base font-black text-slate-900 leading-snug">{{ selectedEventDetail.title }}</h3>
          <p class="text-xs text-slate-600 mt-2 leading-relaxed whitespace-pre-line">{{ selectedEventDetail.description }}</p>
        </div>

        <!-- Key Information Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/90 space-y-1">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center space-x-1.5">
              <Calendar class="w-3.5 h-3.5 text-purple-600" />
              <span>Date Schedule</span>
            </div>
            <div class="font-bold text-slate-900 font-mono text-xs">
              {{ formatEvDateRange(selectedEventDetail.start_date, selectedEventDetail.end_date) }}
            </div>
          </div>

          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/90 space-y-1">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center space-x-1.5">
              <Clock class="w-3.5 h-3.5 text-emerald-600" />
              <span>Designated Time</span>
            </div>
            <div class="font-bold text-slate-900 font-mono text-xs">
              {{ selectedEventDetail.start_time ? `${formatTime(selectedEventDetail.start_time)} – ${formatTime(selectedEventDetail.end_time || selectedEventDetail.start_time)}` : 'All Day Program' }}
            </div>
          </div>

          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/90 space-y-1 col-span-full">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center space-x-1.5">
              <MapPin class="w-3.5 h-3.5 text-rose-600" />
              <span>Designated Campus Venue</span>
            </div>
            <div class="font-bold text-slate-900 text-xs">
              {{ selectedEventDetail.location || 'Campus Grounds & Designated Instructional Venues' }}
            </div>
          </div>
        </div>

        <!-- Institutional DepEd Advisory Banner -->
        <div class="p-3.5 rounded-2xl bg-purple-50/70 border border-purple-200 text-purple-950 text-[11px] flex items-start space-x-2.5">
          <Sparkles class="w-4 h-4 text-purple-600 shrink-0 mt-0.5" />
          <div class="leading-relaxed">
            <strong>Official DepEd Academic Calendar Event:</strong> All students are requested to be properly guided by this schedule. For further queries, consult your class homeroom adviser or the Principal's Office.
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-end pt-2 border-t border-slate-100">
          <button @click="showEventDetailModal = false" type="button" class="px-5 py-2.5 rounded-xl bg-purple-900 hover:bg-purple-800 text-white font-bold text-xs transition cursor-pointer shadow-xs">
            Close Advisory
          </button>
        </div>
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

    <!-- ======================================================== -->
    <!-- MODAL: UPLOAD FOLLOW-UP REQUIREMENT DOCUMENT            -->
    <!-- ======================================================== -->
    <div v-if="showUploadDocModal && selectedDocToUpload" class="no-print fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200 text-xs space-y-5 animate-in fade-in zoom-in-95 duration-150 text-slate-900">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-2.5">
            <div class="w-9 h-9 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center shadow-2xs">
              <UploadCloud class="w-5 h-5 text-emerald-600" />
            </div>
            <div>
              <h3 class="font-bold text-sm text-slate-900">Upload Follow-up Requirement</h3>
              <p class="text-[11px] text-slate-500 font-mono">{{ selectedDocToUpload.title }}</p>
            </div>
          </div>
          <button @click="showUploadDocModal = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold cursor-pointer transition">✕</button>
        </div>

        <!-- Instructions -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/90 text-[11px] text-slate-600 space-y-1">
          <p><strong>Requirement Details:</strong> {{ selectedDocToUpload.description }}</p>
          <p class="text-[10px] text-slate-500">Accepted formats: <strong>PDF, JPG, JPEG, PNG</strong> (Max file size: 10MB). Ensure document scans are clear and readable.</p>
        </div>

        <!-- Error banner if any -->
        <div v-if="uploadDocError" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-[11px] flex items-center space-x-1.5">
          <AlertCircle class="w-4 h-4 text-rose-600 shrink-0" />
          <span>{{ uploadDocError }}</span>
        </div>

        <!-- File Upload Area -->
        <div class="space-y-3">
          <label class="block font-bold text-slate-700">Select Document File *</label>
          <div 
            @click="$refs.followUpFileInput.click()" 
            class="border-2 border-dashed border-slate-300 hover:border-emerald-500 hover:bg-emerald-50/20 rounded-2xl p-6 text-center cursor-pointer transition"
          >
            <input 
              ref="followUpFileInput" 
              type="file" 
              accept=".pdf,.jpg,.jpeg,.png" 
              @change="handleFollowUpFileSelect" 
              class="hidden" 
            />
            <div v-if="!selectedFollowUpFile" class="space-y-2">
              <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                <UploadCloud class="w-6 h-6 text-slate-500" />
              </div>
              <div class="text-xs font-bold text-slate-700">Click to choose a file or drag here</div>
              <div class="text-[10px] text-slate-400">PDF, PNG, or JPG up to 10MB</div>
            </div>
            <div v-else class="space-y-1">
              <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto">
                <CheckCircle2 class="w-6 h-6 text-emerald-600" />
              </div>
              <div class="font-bold text-slate-900 text-xs truncate max-w-xs mx-auto">{{ selectedFollowUpFile.name }}</div>
              <div class="text-[10px] text-slate-500 font-mono">{{ (selectedFollowUpFile.size / (1024 * 1024)).toFixed(2) }} MB</div>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
          <button 
            type="button" 
            @click="showUploadDocModal = false" 
            class="px-4 py-2.5 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer"
          >
            Cancel
          </button>
          <button 
            type="button" 
            @click="submitFollowUpUpload" 
            :disabled="!selectedFollowUpFile || isSubmittingUpload" 
            class="px-5 py-2.5 rounded-xl font-bold bg-[#0c2340] hover:bg-[#163a66] disabled:opacity-50 text-white shadow-xs transition flex items-center space-x-2 cursor-pointer"
          >
            <RefreshCw v-if="isSubmittingUpload" class="w-4 h-4 animate-spin" />
            <UploadCloud v-else class="w-4 h-4 text-emerald-400" />
            <span>{{ isSubmittingUpload ? 'Uploading...' : 'Submit Requirement' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: DOCUMENT PREVIEW (PDF / IMAGE)                   -->
    <!-- ======================================================== -->
    <!-- MODAL: DOCUMENT PREVIEW (PDF / WORD DOCX / IMAGE / FILES)-->
    <!-- ======================================================== -->
    <div v-if="previewDocModal" class="no-print fixed inset-0 z-[60] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-4xl w-full max-h-[92vh] flex flex-col shadow-2xl overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
        <!-- Header -->
        <div class="p-4 sm:px-6 border-b border-slate-200 flex items-center justify-between bg-slate-900 text-white">
          <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-blue-500/20 border border-blue-400/30 flex items-center justify-center text-blue-400 font-bold">
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
              class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold flex items-center space-x-1.5 shadow-sm transition"
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
                <h4 class="font-bold text-slate-800 text-sm truncate max-w-xs mx-auto">{{ previewDocModal.title || 'document.docx' }}</h4>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                  This Word file format requires download to view on your device.
                </p>
              </div>
              <a 
                :href="getFileUrl(previewDocModal.file_path)" 
                target="_blank" 
                download
                class="inline-flex items-center space-x-2 px-6 py-2.5 rounded-xl font-bold bg-blue-700 hover:bg-blue-600 text-white text-xs shadow-md shadow-blue-700/20 transition cursor-pointer"
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
              class="inline-flex items-center space-x-2 px-6 py-2.5 rounded-xl font-bold bg-blue-900 hover:bg-blue-800 text-white text-xs shadow-md transition cursor-pointer"
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
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { 
  Calendar, MapPin, FileText, Clock, User, BookOpen, Layers, 
  Sparkles, CreditCard, LayoutGrid, List, Coffee, Utensils, 
  ChevronRight, ChevronLeft, Download, ExternalLink, Paperclip, CheckCircle, 
  AlertCircle, AlertTriangle, Send, UploadCloud, MessageSquare, CheckCircle2,
  ShieldCheck, RefreshCw, Search, Filter, CalendarDays, Tag, Info, Bell, Users, Eye, X, Lock, Pin,
  ListChecks, HelpCircle, Award, Loader2
} from 'lucide-vue-next';
import api, { getFileUrl } from '../../services/api';

const route = useRoute();
const router = useRouter();
const activeTab = ref('all');

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

watch(() => route.query.tab, (tab) => {
  if (tab && ['all', 'schedule', 'account', 'events', 'records', 'lms'].includes(tab)) {
    activeTab.value = tab;
  }
}, { immediate: true });

const dashboardData = ref({
  user: null,
  enrollment: null,
  subjects: [],
  payments: [],
  events: []
});

const eventsList = ref([]);
const myDocRequests = ref([]);
const showStudentDocModal = ref(false);

// Admission Requirements & Follow-up Compliance State
const requirementsData = ref({ documents: [], stats: { total: 0, verified: 0, to_follow: 0, deficient: 0, compliance_percentage: 100 } });
const isLoadingRequirements = ref(false);
const showUploadDocModal = ref(false);
const selectedDocToUpload = ref(null);
const selectedFollowUpFile = ref(null);
const isSubmittingUpload = ref(false);
const uploadDocError = ref('');
const previewDocModal = ref(null);
const showAllRequirements = ref(false);

const pendingFollowUpDocuments = computed(() => {
  return (requirementsData.value.documents || []).filter(doc => doc.status !== 'Verified');
});

const verifiedDocuments = computed(() => {
  return (requirementsData.value.documents || []).filter(doc => doc.status === 'Verified');
});

const loadStudentRequirements = async () => {
  isLoadingRequirements.value = true;
  try {
    const res = await api.getStudentRequirements();
    if (res.data) {
      requirementsData.value = res.data;
    }
  } catch (err) {
    console.error('Failed to load student requirements:', err);
  } finally {
    isLoadingRequirements.value = false;
  }
};

const openUploadDocModal = (req) => {
  selectedDocToUpload.value = req;
  selectedFollowUpFile.value = null;
  uploadDocError.value = '';
  showUploadDocModal.value = true;
};

const handleFollowUpFileSelect = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  if (file.size > 10 * 1024 * 1024) {
    uploadDocError.value = 'File is too large. Maximum file size allowed is 10MB.';
    selectedFollowUpFile.value = null;
    return;
  }

  const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
  if (!allowedTypes.includes(file.type)) {
    uploadDocError.value = 'Invalid file format. Only PDF, JPG, and PNG documents are allowed.';
    selectedFollowUpFile.value = null;
    return;
  }

  uploadDocError.value = '';
  selectedFollowUpFile.value = file;
};

const submitFollowUpUpload = async () => {
  if (!selectedDocToUpload.value || !selectedFollowUpFile.value) return;

  isSubmittingUpload.value = true;
  uploadDocError.value = '';

  try {
    const formData = new FormData();
    formData.append('document_type', selectedDocToUpload.value.document_type);
    formData.append('document', selectedFollowUpFile.value);

    const res = await api.uploadStudentFollowUpDoc(formData);
    showUploadDocModal.value = false;
    showNotice('Requirement Submitted', res.message || 'Your document has been submitted and is now pending verification by school staff.', 'success');
    await loadStudentRequirements();
  } catch (err) {
    uploadDocError.value = err.message || 'Failed to upload document. Please try again.';
  } finally {
    isSubmittingUpload.value = false;
  }
};

const openPreviewDoc = (filePath, title) => {
  previewDocModal.value = { file_path: filePath, title: title || 'Requirement Document Preview' };
};

const getRequirementBadgeClass = (status) => {
  switch (status) {
    case 'Verified':
      return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
    case 'Pending':
    case 'Under Review':
      return 'bg-amber-50 text-amber-700 border border-amber-300';
    case 'Deficient':
    case 'Rejected':
      return 'bg-rose-50 text-rose-700 border border-rose-300';
    case 'To Follow Up':
      return 'bg-yellow-50 text-yellow-800 border border-yellow-300';
    default:
      return 'bg-slate-100 text-slate-700 border border-slate-200';
  }
};

const selectedSemesterFilter = ref('1st Semester');
const scheduleViewMode = ref('timetable'); // default to 'timetable' or 'cards'
const timetableSubView = ref('matrix'); // 'matrix' | 'daily'
const selectedDayTab = ref('Monday');
const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

const getDaySchedules = (day) => {
  const all = dashboardData.value.section_schedules || [];
  return all.filter(s => {
    if (s.day_of_week !== day) return false;
    if (isSHSStudent.value && selectedSemesterFilter.value !== 'All') {
      const sem = s.semester || s.subject_semester;
      if (sem && sem !== selectedSemesterFilter.value && sem !== 'Full Year') return false;
    }
    return true;
  }).sort((a, b) => (a.time_start || '').localeCompare(b.time_start || ''));
};

const getScheduleAt = (day, timeStart) => {
  const all = dashboardData.value.section_schedules || [];
  return all.find(s => {
    if (s.day_of_week !== day || s.time_start !== timeStart) return false;
    if (isSHSStudent.value && selectedSemesterFilter.value !== 'All') {
      const sem = s.semester || s.subject_semester;
      if (sem && sem !== selectedSemesterFilter.value && sem !== 'Full Year') return false;
    }
    return true;
  });
};

const hoveredSubjectCode = ref(null);

const isSameSubject = (codeA, codeB) => {
  if (!codeA || !codeB) return false;
  return codeA.trim().toUpperCase() === codeB.trim().toUpperCase();
};

const getSubjectTheme = (code) => {
  if (!code) return { accentBorder: 'border-l-4 border-l-slate-400', badge: 'bg-slate-100 text-slate-700 border border-slate-200', text: 'text-slate-800' };
  const c = code.toUpperCase();
  if (c.includes('SCI')) return { accentBorder: 'border-l-4 border-l-emerald-500', badge: 'bg-emerald-50 text-emerald-700 border border-emerald-200/80', text: 'text-emerald-950' };
  if (c.includes('MATH')) return { accentBorder: 'border-l-4 border-l-blue-600', badge: 'bg-blue-50 text-blue-700 border border-blue-200/80', text: 'text-blue-950' };
  if (c.includes('ENG')) return { accentBorder: 'border-l-4 border-l-indigo-600', badge: 'bg-indigo-50 text-indigo-700 border border-indigo-200/80', text: 'text-indigo-950' };
  if (c.includes('FIL')) return { accentBorder: 'border-l-4 border-l-amber-500', badge: 'bg-amber-50 text-amber-700 border border-amber-200/80', text: 'text-amber-950' };
  if (c.includes('AP')) return { accentBorder: 'border-l-4 border-l-rose-500', badge: 'bg-rose-50 text-rose-700 border border-rose-200/80', text: 'text-rose-950' };
  if (c.includes('MAPEH')) return { accentBorder: 'border-l-4 border-l-purple-500', badge: 'bg-purple-50 text-purple-700 border border-purple-200/80', text: 'text-purple-950' };
  if (c.includes('TLE') || c.includes('ICT') || c.includes('TVL') || c.includes('HE')) return { accentBorder: 'border-l-4 border-l-teal-600', badge: 'bg-teal-50 text-teal-700 border border-teal-200/80', text: 'text-teal-950' };
  if (c.includes('ESP')) return { accentBorder: 'border-l-4 border-l-cyan-600', badge: 'bg-cyan-50 text-cyan-700 border border-cyan-200/80', text: 'text-cyan-950' };
  return { accentBorder: 'border-l-4 border-l-slate-400', badge: 'bg-slate-100 text-slate-800 border border-slate-200', text: 'text-slate-900' };
};

const timeSlots = computed(() => {
  const all = dashboardData.value.section_schedules || [];
  const map = new Map();
  all.forEach(s => {
    if (s.time_start && s.time_end) {
      const key = `${s.time_start}-${s.time_end}`;
      if (!map.has(key)) {
        map.set(key, { start: s.time_start, end: s.time_end });
      }
    }
  });
  const sorted = Array.from(map.values()).sort((a, b) => a.start.localeCompare(b.start));
  
  const result = [];
  sorted.forEach((slot, index) => {
    if (index > 0) {
      const prev = sorted[index - 1];
      if (prev.end === '09:30:00' && slot.start === '09:50:00') {
        result.push({ isBreak: true, label: 'Morning Recess', time: '9:30 AM - 9:50 AM', icon: '☕' });
      } else if (prev.end === '11:50:00' && slot.start === '12:50:00') {
        result.push({ isBreak: true, label: 'Institutional Lunch Break', time: '11:50 AM - 12:50 PM', icon: '🍱' });
      }
    }
    result.push({
      isBreak: false,
      periodNumber: result.filter(r => !r.isBreak).length + 1,
      start: slot.start,
      end: slot.end,
      label: `Period ${result.filter(r => !r.isBreak).length + 1}`,
      time: `${formatTime(slot.start)} - ${formatTime(slot.end)}`
    });
  });
  return result;
});

const studentDocForm = ref({
  document_type: 'Certificate of Enrollment',
  purpose: '',
  copies: 1
});

const studentDisplayName = computed(() => {
  const u = dashboardData.value.user;
  const e = dashboardData.value.enrollment;
  if (u?.first_name || u?.last_name) {
    return `${u.first_name || ''} ${u.last_name || ''}`.trim();
  }
  if (e?.student_first_name || e?.student_last_name) {
    return `${e.student_first_name || ''} ${e.student_last_name || ''}`.trim();
  }
  return 'Student';
});

const studentDisplayId = computed(() => {
  return dashboardData.value.user?.student_id || dashboardData.value.enrollment?.student_no || dashboardData.value.enrollment?.official_student_no || 'Pending';
});

const studentDisplayLrn = computed(() => {
  return dashboardData.value.enrollment?.lrn || 'N/A';
});

const isSHSStudent = computed(() => {
  const gl = dashboardData.value.enrollment?.grade_level_id;
  const cat = dashboardData.value.enrollment?.grade_category;
  return cat === 'SHS' || gl >= 5;
});

const currentSemesterSubjects = computed(() => {
  const all = dashboardData.value.subjects || [];
  if (!isSHSStudent.value || selectedSemesterFilter.value === 'All') {
    return all;
  }
  return all.filter(s => !s.semester || s.semester === selectedSemesterFilter.value);
});

const formatTime = (t) => {
  if (!t) return '8:00 AM';
  const parts = t.split(':');
  if (parts.length < 2) return t;
  let h = parseInt(parts[0], 10);
  const m = parts[1];
  const ampm = h >= 12 ? 'PM' : 'AM';
  h = h % 12 || 12;
  return `${h}:${m} ${ampm}`;
};

// --- SCHOOL EVENTS CALENDAR STATE & METHODS ---
const calendarViewMode = ref('calendar'); // 'calendar' | 'timeline'
const calendarCursorDate = ref(new Date());
const selectedCalendarDay = ref(null);
const eventCategoryFilter = ref('All');
const eventSearchQuery = ref('');
const eventTimelineFilter = ref('all'); // 'all' | 'upcoming' | 'past'
const selectedEventDetail = ref(null);
const showEventDetailModal = ref(false);

const availableEventCategories = ['Academic', 'Holiday', 'Sports', 'Cultural', 'Institutional', 'Examination'];

const parseDateParts = (str) => {
  if (!str) return new Date();
  const parts = str.split('T')[0].split('-');
  if (parts.length < 3) return new Date(str);
  return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
};

const formatDateYMD = (d) => {
  if (!d) return '';
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
};

const formatEvDateRange = (s, e) => {
  if (!s) return '';
  const d1 = parseDateParts(s).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  if (!e || e === s) return d1;
  const d2 = parseDateParts(e).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  return `${d1} – ${d2}`;
};

const formatSelectedDayLabel = (dateStr) => {
  if (!dateStr) return '';
  const d = parseDateParts(dateStr);
  return d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
};

const getEventCategoryMeta = (cat) => {
  switch (cat) {
    case 'Academic':
      return {
        badge: 'bg-purple-100 text-purple-800 border-purple-200',
        chip: 'bg-purple-600 text-white',
        border: 'border-l-purple-600',
        dot: 'bg-purple-500',
        bgSoft: 'bg-purple-50/50'
      };
    case 'Holiday':
      return {
        badge: 'bg-amber-100 text-amber-800 border-amber-200',
        chip: 'bg-amber-600 text-white',
        border: 'border-l-amber-500',
        dot: 'bg-amber-500',
        bgSoft: 'bg-amber-50/50'
      };
    case 'Sports':
      return {
        badge: 'bg-emerald-100 text-emerald-800 border-emerald-200',
        chip: 'bg-emerald-600 text-white',
        border: 'border-l-emerald-600',
        dot: 'bg-emerald-500',
        bgSoft: 'bg-emerald-50/50'
      };
    case 'Cultural':
      return {
        badge: 'bg-rose-100 text-rose-800 border-rose-200',
        chip: 'bg-rose-600 text-white',
        border: 'border-l-rose-500',
        dot: 'bg-rose-500',
        bgSoft: 'bg-rose-50/50'
      };
    case 'Institutional':
    case 'Administrative':
      return {
        badge: 'bg-blue-100 text-blue-800 border-blue-200',
        chip: 'bg-blue-600 text-white',
        border: 'border-l-blue-600',
        dot: 'bg-blue-500',
        bgSoft: 'bg-blue-50/50'
      };
    case 'Examination':
      return {
        badge: 'bg-red-100 text-red-800 border-red-200',
        chip: 'bg-red-600 text-white',
        border: 'border-l-red-600',
        dot: 'bg-red-500',
        bgSoft: 'bg-red-50/50'
      };
    default:
      return {
        badge: 'bg-slate-100 text-slate-800 border-slate-200',
        chip: 'bg-slate-700 text-white',
        border: 'border-l-slate-600',
        dot: 'bg-slate-500',
        bgSoft: 'bg-slate-50/50'
      };
  }
};

const getEventBadge = (cat) => {
  return getEventCategoryMeta(cat).badge;
};

const getEventDayNumber = (s, e) => {
  if (!s) return '—';
  const p1 = s.split('T')[0].split('-');
  const d1 = parseInt(p1[2], 10);
  if (!e || e === s) return `${d1}`;
  const p2 = e.split('T')[0].split('-');
  const d2 = parseInt(p2[2], 10);
  return `${d1}–${d2}`;
};

const getEventMonthShort = (s) => {
  if (!s) return '';
  const d = parseDateParts(s);
  return d.toLocaleDateString('en-US', { month: 'short' }).toUpperCase();
};

const getEventDayOfWeek = (s) => {
  if (!s) return '';
  const d = parseDateParts(s);
  return d.toLocaleDateString('en-US', { weekday: 'short' });
};

const getEventStatusText = (s, e) => {
  const today = formatDateYMD(new Date());
  const start = s?.split('T')[0] || '';
  const end = (e || s)?.split('T')[0] || '';
  if (today >= start && today <= end) {
    return { label: 'Happening Today', class: 'bg-emerald-100 text-emerald-800 border-emerald-300' };
  }
  if (start > today) {
    const diffDays = Math.ceil((parseDateParts(start) - parseDateParts(today)) / (1000 * 60 * 60 * 24));
    return { 
      label: diffDays === 1 ? 'Tomorrow' : `In ${diffDays} days`, 
      class: 'bg-blue-50 text-blue-800 border-blue-200' 
    };
  }
  return { label: 'Completed', class: 'bg-slate-100 text-slate-600 border-slate-200' };
};

const filteredEvents = computed(() => {
  const list = eventsList.value || [];
  const q = (eventSearchQuery.value || '').trim().toLowerCase();
  const cat = eventCategoryFilter.value;
  const timeMode = eventTimelineFilter.value;
  const todayStr = formatDateYMD(new Date());

  return list.filter(e => {
    if (cat !== 'All' && e.event_category !== cat) return false;

    if (q) {
      const matchTitle = (e.title || '').toLowerCase().includes(q);
      const matchDesc = (e.description || '').toLowerCase().includes(q);
      const matchLoc = (e.location || '').toLowerCase().includes(q);
      if (!matchTitle && !matchDesc && !matchLoc) return false;
    }

    if (calendarViewMode.value === 'timeline' && timeMode !== 'all') {
      const start = (e.start_date || '').split('T')[0];
      const end = (e.end_date || e.start_date || '').split('T')[0];
      if (timeMode === 'upcoming' && end < todayStr) return false;
      if (timeMode === 'past' && end >= todayStr) return false;
    }

    return true;
  });
});

const upcomingEventsList = computed(() => {
  const todayStr = formatDateYMD(new Date());
  return (eventsList.value || [])
    .filter(e => {
      const end = (e.end_date || e.start_date || '').split('T')[0];
      return end >= todayStr;
    })
    .sort((a, b) => (a.start_date || '').localeCompare(b.start_date || ''));
});

const upcomingEventsPreview = computed(() => {
  if (upcomingEventsList.value.length > 0) {
    return upcomingEventsList.value.slice(0, 4);
  }
  return (eventsList.value || []).slice(0, 4);
});

const nextMajorEvent = computed(() => {
  return upcomingEventsList.value[0] || eventsList.value[0] || null;
});

const holidayCount = computed(() => {
  return (eventsList.value || []).filter(e => 
    e.event_category === 'Holiday' || 
    (e.title && e.title.toLowerCase().includes('holiday')) ||
    (e.title && e.title.toLowerCase().includes('bayani')) ||
    (e.title && e.title.toLowerCase().includes('revolution'))
  ).length;
});

const academicMilestoneCount = computed(() => {
  return (eventsList.value || []).filter(e => 
    e.event_category === 'Academic' || 
    e.event_category === 'Examination' ||
    (e.title && (e.title.toLowerCase().includes('opening') || e.title.toLowerCase().includes('exam') || e.title.toLowerCase().includes('card')))
  ).length;
});

const availableEventMonths = computed(() => {
  const map = new Map();
  (eventsList.value || []).forEach(ev => {
    if (ev.start_date) {
      const d = parseDateParts(ev.start_date);
      const key = `${d.getFullYear()}-${String(d.getMonth()).padStart(2, '0')}`;
      if (!map.has(key)) {
        map.set(key, {
          key,
          year: d.getFullYear(),
          month: d.getMonth(),
          label: d.toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
        });
      }
    }
  });
  return Array.from(map.values()).sort((a, b) => a.key.localeCompare(b.key));
});

const currentMonthYearTitle = computed(() => {
  const cur = calendarCursorDate.value || new Date();
  return cur.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
});

const isMonthActive = (m) => {
  const cur = calendarCursorDate.value || new Date();
  return cur.getFullYear() === m.year && cur.getMonth() === m.month;
};

const getEventsForDate = (dateStr) => {
  return filteredEvents.value.filter(ev => {
    if (!ev.start_date) return false;
    const start = ev.start_date.split('T')[0];
    const end = (ev.end_date || ev.start_date).split('T')[0];
    return dateStr >= start && dateStr <= end;
  });
};

const calendarGridDays = computed(() => {
  const cur = calendarCursorDate.value || new Date();
  const year = cur.getFullYear();
  const month = cur.getMonth();
  
  const firstDay = new Date(year, month, 1);
  const startDow = firstDay.getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const prevMonthDays = new Date(year, month, 0).getDate();
  
  const todayStr = formatDateYMD(new Date());
  const cells = [];
  
  // Leading days
  for (let i = startDow - 1; i >= 0; i--) {
    const dayNum = prevMonthDays - i;
    const prevDate = new Date(year, month - 1, dayNum);
    const dateStr = formatDateYMD(prevDate);
    cells.push({
      dayNumber: dayNum,
      dateStr,
      isCurrentMonth: false,
      isToday: dateStr === todayStr,
      events: getEventsForDate(dateStr)
    });
  }
  
  // Current month days
  for (let d = 1; d <= daysInMonth; d++) {
    const dayDate = new Date(year, month, d);
    const dateStr = formatDateYMD(dayDate);
    cells.push({
      dayNumber: d,
      dateStr,
      isCurrentMonth: true,
      isToday: dateStr === todayStr,
      events: getEventsForDate(dateStr)
    });
  }
  
  // Trailing days
  const targetTotal = cells.length > 35 ? 42 : 35;
  const remaining = targetTotal - cells.length;
  for (let nextD = 1; nextD <= remaining; nextD++) {
    const nextDate = new Date(year, month + 1, nextD);
    const dateStr = formatDateYMD(nextDate);
    cells.push({
      dayNumber: nextD,
      dateStr,
      isCurrentMonth: false,
      isToday: dateStr === todayStr,
      events: getEventsForDate(dateStr)
    });
  }
  
  return cells;
});

const eventsOnSelectedDay = computed(() => {
  if (!selectedCalendarDay.value) return [];
  return getEventsForDate(selectedCalendarDay.value);
});

const eventsGroupedByMonth = computed(() => {
  const groups = {};
  filteredEvents.value.forEach(ev => {
    if (!ev.start_date) return;
    const d = parseDateParts(ev.start_date);
    const mLabel = d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    if (!groups[mLabel]) {
      groups[mLabel] = [];
    }
    groups[mLabel].push(ev);
  });
  return groups;
});

const prevMonth = () => {
  const d = new Date(calendarCursorDate.value || new Date());
  d.setMonth(d.getMonth() - 1);
  calendarCursorDate.value = d;
};

const nextMonth = () => {
  const d = new Date(calendarCursorDate.value || new Date());
  d.setMonth(d.getMonth() + 1);
  calendarCursorDate.value = d;
};

const goToToday = () => {
  calendarCursorDate.value = new Date();
  selectedCalendarDay.value = formatDateYMD(new Date());
};

const jumpToMonth = (m) => {
  calendarCursorDate.value = new Date(m.year, m.month, 1);
};

const selectDayCell = (cell) => {
  selectedCalendarDay.value = cell.dateStr;
};

const openEventModal = (ev) => {
  selectedEventDetail.value = ev;
  showEventDetailModal.value = true;
};

watch(eventsList, (list) => {
  if (list && list.length > 0) {
    const todayStr = formatDateYMD(new Date());
    const upcomingOrCurrent = list.find(e => (e.end_date || e.start_date) >= todayStr) || list[0];
    if (upcomingOrCurrent && upcomingOrCurrent.start_date) {
      const d = parseDateParts(upcomingOrCurrent.start_date);
      calendarCursorDate.value = new Date(d.getFullYear(), d.getMonth(), 1);
    }
  }
}, { immediate: true });

const getPaymentBadge = (status) => {
  if (status === 'Fully Paid') return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
  if (status === 'Partially Paid') return 'bg-blue-100 text-blue-800 border border-blue-200';
  return 'bg-amber-100 text-amber-800 border border-amber-200';
};

const getDRSBadge = (status) => {
  switch (status) {
    case 'Released': return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
    case 'Ready for Pickup': return 'bg-cyan-50 text-cyan-700 border border-cyan-200';
    case 'Processing': return 'bg-blue-50 text-blue-700 border border-blue-200';
    case 'Pending': return 'bg-amber-50 text-amber-700 border border-amber-200';
    default: return 'bg-slate-100 text-slate-700';
  }
};

const loadDashboard = async () => {
  try {
    const res = await api.getStudentDashboard();
    dashboardData.value = res.data;
    if (res.data?.enrollment?.active_semester && isSHSStudent.value) {
      selectedSemesterFilter.value = res.data.enrollment.active_semester;
    }
    if (res.data?.events && res.data.events.length > 0) {
      eventsList.value = res.data.events;
    }
  } catch (err) {
    console.error('Failed to load student dashboard:', err);
  }

  try {
    const evRes = await api.getEvents();
    if (evRes.data?.events && evRes.data.events.length > 0) {
      eventsList.value = evRes.data.events;
    }
  } catch (err) {
    console.error('Failed to load events:', err);
  }

  try {
    const docRes = await api.getDocumentRequests();
    myDocRequests.value = docRes.data || [];
  } catch (err) {
    console.error('Failed to load document requests:', err);
  }
};

const submitStudentDocRequest = async () => {
  try {
    await api.saveDocumentRequest(studentDocForm.value);
    showStudentDocModal.value = false;
    studentDocForm.value.purpose = '';
    const docRes = await api.getDocumentRequests();
    myDocRequests.value = docRes.data || [];
    showNotice('Official Document Request', 'Your request has been submitted and queued for the School Records Custodian.', 'success');
  } catch (err) {
    showNotice('Request Failed', err.message || 'Failed to submit document request.', 'error');
  }
};

// ========================================================
// STUDENT LMS STATE & METHODS
// ========================================================
const selectedLmsSubject = ref(null);
const lmsClassData = ref({
  class_info: null,
  announcements: [],
  modules: [],
  assignments: []
});
const isLoadingLms = ref(false);
const selectedLmsQuarter = ref('all');
const showSubmitModal = ref(false);
const activeSubmittingAssignment = ref(null);
const submissionForm = ref({ text: '', file: null });
const isSubmittingWork = ref(false);

const showQuizTakerModal = ref(false);
const activeQuizAssignment = ref(null);
const quizAnswersState = ref({});
const isQuizReadOnly = computed(() => !!activeQuizAssignment.value?.my_submission);
const answeredQuizCount = computed(() => {
  if (!activeQuizAssignment.value?.quiz_questions) return 0;
  return Object.values(quizAnswersState.value).filter(val => typeof val === 'string' && val.trim().length > 0).length;
});

const lmsMessage = ref('');
const lmsError = ref('');

const filteredStudentModules = computed(() => {
  if (selectedLmsQuarter.value === 'all') {
    return lmsClassData.value.modules || [];
  }
  return (lmsClassData.value.modules || []).filter(m => m.quarter === selectedLmsQuarter.value);
});

const selectedLmsSemesterFilter = ref('active'); // 'active' | 'archived' | 'all'

const activeLmsSubjects = computed(() => {
  const all = dashboardData.value.subjects || [];
  if (!isSHSStudent.value) return all;
  const activeSem = dashboardData.value.enrollment?.active_semester || '1st Semester';
  return all.filter(s => !s.semester || s.semester === activeSem || s.semester === 'Full Year');
});

const archivedLmsSubjects = computed(() => {
  const all = dashboardData.value.subjects || [];
  if (!isSHSStudent.value) return [];
  const activeSem = dashboardData.value.enrollment?.active_semester || '1st Semester';
  return all.filter(s => s.semester && s.semester !== activeSem && s.semester !== 'Full Year');
});

const displayedLmsSubjects = computed(() => {
  if (!isSHSStudent.value) return dashboardData.value.subjects || [];
  if (selectedLmsSemesterFilter.value === 'active') {
    return activeLmsSubjects.value;
  } else if (selectedLmsSemesterFilter.value === 'archived') {
    return archivedLmsSubjects.value;
  }
  return dashboardData.value.subjects || [];
});

const isSubjectTermArchived = (sub) => {
  if (!sub || !isSHSStudent.value) return false;
  const activeSem = dashboardData.value.enrollment?.active_semester || '1st Semester';
  return Boolean(sub.semester && sub.semester !== activeSem && sub.semester !== 'Full Year');
};

const isAssignmentGraded = (asg) => {
  if (!asg) return false;
  const sub = asg.my_submission;
  if (!sub) return false;
  return sub.status === 'Graded' || (sub.score !== null && sub.score !== undefined && sub.score !== '');
};

const isPastDeadline = (dueDate) => {
  if (!dueDate) return false;
  return new Date(dueDate).getTime() < Date.now();
};

const formatDeadline = (dueDate) => {
  if (!dueDate) return 'No due date';
  const d = new Date(dueDate);
  return d.toLocaleString([], {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const setLmsSemesterFilter = (mode) => {
  selectedLmsSemesterFilter.value = mode;
  const list = displayedLmsSubjects.value;
  if (list.length > 0 && (!selectedLmsSubject.value || !list.some(s => s.subject_id === selectedLmsSubject.value.subject_id))) {
    selectLmsSubject(list[0]);
  }
};

const selectLmsSubject = async (sub) => {
  selectedLmsSubject.value = sub;
  const secId = dashboardData.value.enrollment?.section_id;
  if (!secId || !sub.subject_id) return;
  await loadStudentLmsContent(secId, sub.subject_id);
};

const loadStudentLmsContent = async (sectionId, subjectId) => {
  isLoadingLms.value = true;
  lmsMessage.value = '';
  lmsError.value = '';
  try {
    const res = await api.getLmsClassContent(sectionId, subjectId);
    lmsClassData.value = res.data;
  } catch (err) {
    console.error('Failed to load LMS content:', err);
    lmsError.value = 'Failed to load classroom content: ' + err.message;
  } finally {
    isLoadingLms.value = false;
  }
};

watch([activeTab, displayedLmsSubjects], ([tab, list]) => {
  if (tab === 'lms' && !selectedLmsSubject.value && list && list.length > 0) {
    selectLmsSubject(list[0]);
  }
});

const navigateToLmsSubject = async (subjectId) => {
  activeTab.value = 'lms';
  router.push({ query: { tab: 'lms' } });
  const target = (dashboardData.value.subjects || []).find(s => s.subject_id === subjectId);
  if (target) {
    await selectLmsSubject(target);
  }
};

const openDirectAssignmentSubmission = async (item) => {
  if (item.submission_status === 'Graded' || (item.grade !== null && item.grade !== undefined && item.grade !== '')) {
    await navigateToLmsSubject(item.subject_id);
    return;
  }
  await navigateToLmsSubject(item.subject_id);
  const asg = (lmsClassData.value.assignments || []).find(a => a.id === item.id);
  if (asg) {
    if (isAssignmentGraded(asg)) {
      showNotice('Submissions Finalized', 'This assignment has already been evaluated and graded by the teacher. Submissions are finalized and cannot be modified.', 'warning');
      return;
    }
    openSubmitModal(asg);
  }
};

const openSubmitModal = (asg) => {
  if (isAssignmentGraded(asg)) {
    showNotice('Submissions Finalized', 'This assignment has already been evaluated and graded by your teacher. Submitted work is finalized and cannot be modified.', 'warning');
    return;
  }
  if (isSubjectTermArchived(selectedLmsSubject.value)) {
    showNotice('Academic Term Concluded', 'Submissions are closed for archived semester subjects. This academic term has concluded and is now in read-only mode.', 'info');
    return;
  }
  activeSubmittingAssignment.value = asg;
  submissionForm.value = {
    text: asg.my_submission?.submission_text || '',
    file: null
  };
  showSubmitModal.value = true;
};

const handleSubmissionFileSelect = (e) => {
  const file = e.target.files[0];
  if (file) {
    submissionForm.value.file = file;
  }
};

const submitStudentWork = async () => {
  if (!activeSubmittingAssignment.value) return;
  if (!submissionForm.value.text && !submissionForm.value.file && !activeSubmittingAssignment.value.my_submission?.submission_file) {
    showNotice('Submission Required', 'Please enter your written response or select a document file before turning in work.', 'warning');
    return;
  }

  const formData = new FormData();
  formData.append('assignment_id', activeSubmittingAssignment.value.id);
  formData.append('submission_text', submissionForm.value.text);
  if (submissionForm.value.file) {
    formData.append('file', submissionForm.value.file);
  }

  isSubmittingWork.value = true;
  try {
    const res = await api.submitLmsAssignment(formData);
    lmsMessage.value = res.message || 'Work submitted successfully!';
    showSubmitModal.value = false;
    const secId = dashboardData.value.enrollment?.section_id;
    if (secId && selectedLmsSubject.value) {
      await loadStudentLmsContent(secId, selectedLmsSubject.value.subject_id);
    }
    setTimeout(() => { lmsMessage.value = ''; }, 3500);
  } catch (err) {
    showNotice('Submission Error', 'Failed to submit work: ' + err.message, 'error');
  } finally {
    isSubmittingWork.value = false;
  }
};

const openQuizTakerModal = (asg) => {
  activeQuizAssignment.value = asg;
  quizAnswersState.value = {};
  
  if (asg.my_submission?.quiz_answers) {
    const saved = asg.my_submission.quiz_answers;
    for (const [qid, ansData] of Object.entries(saved)) {
      quizAnswersState.value[qid] = ansData.student_answer || '';
    }
  } else if (asg.quiz_questions) {
    asg.quiz_questions.forEach(q => {
      quizAnswersState.value[q.id] = '';
    });
  }
  showQuizTakerModal.value = true;
};

const submitStudentQuiz = async () => {
  if (!activeQuizAssignment.value) return;
  const questions = activeQuizAssignment.value.quiz_questions || [];
  if (questions.length === 0) {
    showNotice('Quiz Error', 'This quiz has no questions configured.', 'error');
    return;
  }

  const answered = answeredQuizCount.value;
  if (answered === 0) {
    showNotice('Quiz Incomplete', 'Please answer at least one question before submitting your quiz.', 'warning');
    return;
  }

  isSubmittingWork.value = true;
  try {
    const res = await api.submitLmsAssignment({
      assignment_id: activeQuizAssignment.value.id,
      quiz_answers: quizAnswersState.value
    });
    
    showQuizTakerModal.value = false;
    const secId = dashboardData.value.enrollment?.section_id;
    if (secId && selectedLmsSubject.value) {
      await loadStudentLmsContent(secId, selectedLmsSubject.value.subject_id);
    }

    if (res.data?.has_essay) {
      showNotice(
        'Quiz Submitted!',
        `Your objective responses have been auto-graded: ${res.data.score} / ${res.data.max_score} pts. Your essay response(s) have been submitted to your teacher for manual evaluation.`,
        'success'
      );
    } else {
      showNotice(
        'Quiz Auto-Graded!',
        `Your online quiz has been evaluated: You scored ${res.data?.score ?? 0} out of ${res.data?.max_score ?? 0} points!`,
        'success'
      );
    }
  } catch (err) {
    showNotice('Submission Error', 'Failed to submit quiz answers: ' + err.message, 'error');
  } finally {
    isSubmittingWork.value = false;
  }
};

// ========================================================
// PAYMONGO STUDENT BALANCE ONLINE PAYMENT
// ========================================================
const showPaymongoModal = ref(false);
const paymongoAmount = ref(0);
const paymongoIsSubmitting = ref(false);
const paymongoError = ref('');
const isVerifyingPaymongo = ref(false);
const paymongoVerifyResult = ref(null);
const paymongoVerifyError = ref('');

const remainingBalance = computed(() => Number(dashboardData.value.enrollment?.remaining_balance || 0));

const openStudentPaymongoModal = () => {
  paymongoError.value = '';
  paymongoAmount.value = remainingBalance.value;
  showPaymongoModal.value = true;
};

const setQuickAmount = (amt) => {
  if (amt === 'full') {
    paymongoAmount.value = remainingBalance.value;
  } else {
    paymongoAmount.value = Math.min(Number(amt), remainingBalance.value);
  }
};

const initiateStudentPaymongoCheckout = async () => {
  const amt = Number(paymongoAmount.value);
  if (!amt || amt < 20) {
    paymongoError.value = 'Please enter a valid amount of at least ₱20.00.';
    return;
  }
  if (amt > remainingBalance.value + 0.01) {
    paymongoError.value = `Amount cannot exceed your remaining balance of ₱${remainingBalance.value.toLocaleString('en-US', { minimumFractionDigits: 2 })}.`;
    return;
  }

  paymongoIsSubmitting.value = true;
  paymongoError.value = '';
  try {
    const res = await api.createStudentPaymongoCheckout({ amount: amt });
    if (res.data?.checkout_url) {
      window.location.href = res.data.checkout_url;
    } else {
      throw new Error('PayMongo checkout URL was not returned by server.');
    }
  } catch (err) {
    paymongoError.value = err.message || 'Failed to initiate PayMongo checkout. Please try again.';
    paymongoIsSubmitting.value = false;
  }
};

const handlePaymongoReturn = async () => {
  const status = route.query.paymongo_status;
  const sessionId = route.query.session_id;

  // Immediately strip the paymongo query parameters from the URL
  if (status || sessionId) {
    const cleanQuery = { ...route.query };
    delete cleanQuery.paymongo_status;
    delete cleanQuery.session_id;
    router.replace({ path: route.path, query: cleanQuery });
  }

  if (status === 'success' && sessionId && sessionId !== '{CHECKOUT_SESSION_ID}') {
    isVerifyingPaymongo.value = true;
    paymongoVerifyError.value = '';
    try {
      const res = await api.verifyStudentPaymongoSession(sessionId);
      paymongoVerifyResult.value = res.data;
      await loadDashboard();
    } catch (err) {
      console.error('Failed to verify PayMongo session:', err);
      paymongoVerifyError.value = err.message || 'Payment verification failed. If you have completed payment, please contact the Cashier.';
    } finally {
      isVerifyingPaymongo.value = false;
    }
  } else if (status === 'cancelled') {
    paymongoVerifyError.value = 'PayMongo transaction was cancelled. No charges were made.';
  }
};

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

onMounted(async () => {
  const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  const today = dayNames[new Date().getDay()];
  if (daysOfWeek.includes(today)) {
    selectedDayTab.value = today;
  }
  await loadDashboard();
  await loadStudentRequirements();
  await handlePaymongoReturn();
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

