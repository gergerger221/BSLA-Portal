<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Top Header & Actions -->
    <div class="no-print flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6 pb-5 border-b border-slate-200">
      <div>
        <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
          <Sparkles class="w-3.5 h-3.5 text-amber-600" />
          <span>Super Admin Control Center</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">System Administration & School Year Control</h1>
        <p class="text-xs text-slate-500 mt-0.5">Manage user roles, toggle enrollment lock status, and monitor system metrics.</p>
      </div>

      <div class="flex items-center space-x-2.5 shrink-0">
        <button 
          @click="openUserModal()"
          class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-xs transition flex items-center space-x-1.5 cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Add Staff Account</span>
        </button>
        <button 
          @click="loadStats(); loadUsers(); loadSchoolYears();"
          class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-medium shadow-2xs transition flex items-center space-x-1.5 cursor-pointer"
        >
          <RefreshCw class="w-3.5 h-3.5" />
          <span>Refresh</span>
        </button>
      </div>
    </div>

    <!-- Floating Toast Notifications (Always visible regardless of scroll position) -->
    <div class="fixed bottom-6 right-6 z-[9999] flex flex-col space-y-2.5 max-w-md w-full pointer-events-none px-4 sm:px-0">
      <transition-group
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-4 opacity-0 sm:translate-y-0 sm:translate-x-4 scale-95"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0 scale-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <!-- Success Toast -->
        <div 
          v-if="successMessage" 
          key="success-toast"
          class="pointer-events-auto p-4 rounded-2xl bg-slate-900/95 text-white border border-emerald-500/50 shadow-2xl backdrop-blur-md flex items-start space-x-3.5 animate-in slide-in-from-bottom-2"
        >
          <div class="w-7 h-7 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0 mt-0.5">
            <CheckCircle2 class="w-4 h-4 text-emerald-400" />
          </div>
          <div class="flex-1 min-w-0 pr-2">
            <p class="text-xs font-bold text-white tracking-tight">Operation Successful</p>
            <p class="text-[11px] text-slate-300 mt-0.5 leading-relaxed font-medium">{{ successMessage }}</p>
          </div>
          <button 
            @click="successMessage = ''" 
            class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-white/10 transition shrink-0 cursor-pointer"
          >
            ✕
          </button>
        </div>

        <!-- Error Toast -->
        <div 
          v-if="errorMessage" 
          key="error-toast"
          class="pointer-events-auto p-4 rounded-2xl bg-slate-900/95 text-white border border-rose-500/50 shadow-2xl backdrop-blur-md flex items-start space-x-3.5 animate-in slide-in-from-bottom-2"
        >
          <div class="w-7 h-7 rounded-xl bg-rose-500/20 text-rose-400 border border-rose-500/30 flex items-center justify-center shrink-0 mt-0.5">
            <AlertCircle class="w-4 h-4 text-rose-400" />
          </div>
          <div class="flex-1 min-w-0 pr-2">
            <p class="text-xs font-bold text-white tracking-tight">Notice / Action Error</p>
            <p class="text-[11px] text-rose-200 mt-0.5 leading-relaxed font-medium">{{ errorMessage }}</p>
          </div>
          <button 
            @click="errorMessage = ''" 
            class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-white/10 transition shrink-0 cursor-pointer"
          >
            ✕
          </button>
        </div>
      </transition-group>
    </div>

    <!-- TAB 1: OVERVIEW & STATS -->
    <div v-if="activeTab === 'stats'" class="space-y-6">
      <!-- 4 Stat Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm">
          <span class="text-xs font-bold uppercase text-slate-400">Total Applicants</span>
          <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ stats.total_applicants || 0 }}</div>
          <span class="text-[11px] text-amber-600 font-semibold">{{ stats.pending_review || 0 }} Under Review</span>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm">
          <span class="text-xs font-bold uppercase text-slate-400">Enrolled Students</span>
          <div class="text-2xl font-extrabold text-emerald-700 mt-1">{{ (stats.enrolled_jhs || 0) + (stats.enrolled_shs || 0) }}</div>
          <span class="text-[11px] text-slate-500 font-medium">JHS: {{ stats.enrolled_jhs || 0 }} • SHS: {{ stats.enrolled_shs || 0 }}</span>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm">
          <span class="text-xs font-bold uppercase text-slate-400">Tuition Collections</span>
          <div class="text-2xl font-extrabold text-slate-900 mt-1">
            ₱{{ Number(stats.total_revenue || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}
          </div>
          <span class="text-[11px] text-emerald-600 font-semibold">Treasury Verified</span>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs">
          <span class="text-xs font-bold uppercase text-slate-400">Active Staff</span>
          <div class="text-2xl font-extrabold text-blue-900 mt-1">{{ stats.total_staff || 0 }}</div>
          <span class="text-[11px] text-slate-500 font-medium">Registrar, Treasury, Coordinator</span>
        </div>
      </div>

      <!-- Audit Logs Trail -->
      <!-- Audit Logs Trail -->
      <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4">
          <div>
            <div class="flex items-center space-x-2">
              <h2 class="text-base font-bold text-slate-800">System Audit Trail & Security Logs</h2>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-900 border border-blue-200 font-mono">
                {{ filteredLogs.length }} Events
              </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Real-time immutable security events, role transactions, and user logins.</p>
          </div>

          <div class="flex items-center space-x-2 w-full sm:w-auto">
            <div class="relative w-full sm:w-64">
              <input 
                v-model="searchLogQuery" 
                type="text" 
                placeholder="Search action, user, details..." 
                class="w-full pl-8 pr-3 py-1.5 rounded-xl border border-slate-300 text-xs text-slate-800 focus:outline-none focus:border-blue-900"
              />
              <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" />
            </div>
            <button 
              type="button" 
              @click="loadStats()" 
              class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold flex items-center shrink-0 cursor-pointer"
              title="Refresh security audit logs"
            >
              <RefreshCw class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left border-collapse min-w-[650px]">
            <thead>
              <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
                <th class="p-3">Timestamp</th>
                <th class="p-3">User</th>
                <th class="p-3">Security Action</th>
                <th class="p-3">Activity Details</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="log in filteredLogs" :key="log.id" class="hover:bg-slate-50 transition">
                <td class="p-3 text-slate-500 font-mono text-[11px] whitespace-nowrap">{{ log.created_at }}</td>
                <td class="p-3 font-bold text-slate-800 whitespace-nowrap">
                  <div class="flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full" :class="log.username ? 'bg-blue-600' : 'bg-slate-400'"></span>
                    <span>{{ log.username ? '@' + log.username : 'System / Automated' }}</span>
                  </div>
                </td>
                <td class="p-3 whitespace-nowrap">
                  <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase font-mono tracking-wider border inline-block" :class="getAuditActionClass(log.action)">
                    {{ log.action }}
                  </span>
                </td>
                <td class="p-3 text-slate-700 font-medium max-w-md">{{ log.details }}</td>
              </tr>
              <tr v-if="filteredLogs.length === 0">
                <td colspan="4" class="p-8 text-center text-slate-400 text-xs">
                  <ShieldAlert class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                  <div>No matching system audit logs found.</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 2: STAFF & USER MANAGEMENT -->
    <div v-if="activeTab === 'users'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-5">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center space-x-2">
            <h2 class="text-base font-bold text-slate-800">Staff & System Accounts</h2>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-900 border border-blue-200 font-mono">
              {{ filteredUsers.length }} of {{ usersList.users?.length || 0 }} Accounts
            </span>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">Manage credentials, roles, and access for Administrator, Coordinator, Registrar, Treasury, Records, and Faculty.</p>
        </div>
        <button @click="openUserModal()" class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-900 hover:bg-blue-800 text-white shadow-xs transition flex items-center space-x-1.5 shrink-0 cursor-pointer">
          <Plus class="w-4 h-4" />
          <span>Create User Account</span>
        </button>
      </div>

      <!-- Search & Filters Toolbar -->
      <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 bg-slate-50/90 p-3.5 rounded-2xl border border-slate-200">
        <!-- Search Input -->
        <div class="relative flex-1 min-w-[240px]">
          <Search class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input 
            v-model="searchUserQuery" 
            type="text" 
            placeholder="Search by name, @username, email, or role..." 
            class="w-full pl-9 pr-8 py-2 rounded-xl bg-white border border-slate-300 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-900 focus:ring-1 focus:ring-blue-900"
          />
          <button 
            v-if="searchUserQuery" 
            @click="searchUserQuery = ''" 
            class="absolute right-2.5 top-1/2 -translate-y-1/2 p-0.5 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 cursor-pointer"
            title="Clear search"
          >
            <X class="w-3.5 h-3.5" />
          </button>
        </div>

        <!-- Role Filter Dropdown -->
        <div class="flex flex-wrap items-center gap-2 shrink-0">
          <div class="flex items-center space-x-1.5 bg-white px-3 py-1.5 rounded-xl border border-slate-300 text-xs text-slate-700">
            <Filter class="w-3.5 h-3.5 text-slate-400" />
            <select v-model="selectedRoleFilter" class="bg-transparent font-medium focus:outline-none cursor-pointer">
              <option value="">All Roles</option>
              <option v-for="r in usersList.roles" :key="r.id" :value="r.slug">
                {{ r.name }}
              </option>
            </select>
          </div>

          <!-- Status Filter Dropdown -->
          <div class="flex items-center space-x-1.5 bg-white px-3 py-1.5 rounded-xl border border-slate-300 text-xs text-slate-700">
            <select v-model="selectedStatusFilter" class="bg-transparent font-medium focus:outline-none cursor-pointer">
              <option value="">All Statuses</option>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>

          <!-- Sort Selector -->
          <div class="flex items-center space-x-1.5 bg-white px-3 py-1.5 rounded-xl border border-slate-300 text-xs text-slate-700">
            <span class="text-slate-400 font-normal">Sort:</span>
            <select v-model="userSortField" class="bg-transparent font-medium focus:outline-none cursor-pointer">
              <option value="name">Name (A-Z)</option>
              <option value="username">Username (@)</option>
              <option value="role">Role</option>
              <option value="status">Status</option>
              <option value="id_desc">Newest First</option>
              <option value="id_asc">Oldest First</option>
            </select>
            <button 
              type="button" 
              @click="toggleUserSortDirection" 
              class="p-1 rounded-md hover:bg-slate-100 text-slate-600 cursor-pointer"
              :title="userSortOrder === 'asc' ? 'Ascending (Click for Descending)' : 'Descending (Click for Ascending)'"
            >
              <ArrowUp v-if="userSortOrder === 'asc'" class="w-3.5 h-3.5 text-blue-900" />
              <ArrowDown v-else class="w-3.5 h-3.5 text-blue-900" />
            </button>
          </div>

          <!-- Refresh Button -->
          <button 
            type="button" 
            @click="loadUsers()" 
            class="p-2 rounded-xl bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 text-xs font-semibold flex items-center shrink-0 cursor-pointer shadow-2xs"
            title="Refresh user accounts"
          >
            <RefreshCw class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <!-- Accounts Table with Clickable Sort Headers -->
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse min-w-[700px]">
          <thead>
            <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
              <th @click="setUserSort('name')" class="p-3.5 cursor-pointer hover:text-blue-900 select-none transition">
                <div class="flex items-center space-x-1.5">
                  <span>Name / Username</span>
                  <ArrowUpDown v-if="userSortField !== 'name' && userSortField !== 'username'" class="w-3 h-3 text-slate-300" />
                  <ArrowUp v-else-if="userSortOrder === 'asc'" class="w-3 h-3 text-blue-900" />
                  <ArrowDown v-else class="w-3 h-3 text-blue-900" />
                </div>
              </th>
              <th @click="setUserSort('email')" class="p-3.5 cursor-pointer hover:text-blue-900 select-none transition">
                <div class="flex items-center space-x-1.5">
                  <span>Email</span>
                  <ArrowUpDown v-if="userSortField !== 'email'" class="w-3 h-3 text-slate-300" />
                  <ArrowUp v-else-if="userSortOrder === 'asc'" class="w-3 h-3 text-blue-900" />
                  <ArrowDown v-else class="w-3 h-3 text-blue-900" />
                </div>
              </th>
              <th @click="setUserSort('role')" class="p-3.5 cursor-pointer hover:text-blue-900 select-none transition">
                <div class="flex items-center space-x-1.5">
                  <span>Role</span>
                  <ArrowUpDown v-if="userSortField !== 'role'" class="w-3 h-3 text-slate-300" />
                  <ArrowUp v-else-if="userSortOrder === 'asc'" class="w-3 h-3 text-blue-900" />
                  <ArrowDown v-else class="w-3 h-3 text-blue-900" />
                </div>
              </th>
              <th @click="setUserSort('status')" class="p-3.5 cursor-pointer hover:text-blue-900 select-none transition">
                <div class="flex items-center space-x-1.5">
                  <span>Status</span>
                  <ArrowUpDown v-if="userSortField !== 'status'" class="w-3 h-3 text-slate-300" />
                  <ArrowUp v-else-if="userSortOrder === 'asc'" class="w-3 h-3 text-blue-900" />
                  <ArrowDown v-else class="w-3 h-3 text-blue-900" />
                </div>
              </th>
              <th class="p-3.5 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="u in filteredUsers" :key="u.id" class="hover:bg-slate-50 transition">
              <td class="p-3.5">
                <div class="font-bold text-slate-900">{{ u.first_name }} {{ u.last_name }}</div>
                <div class="text-[11px] font-mono text-slate-400">@{{ u.username }}</div>
              </td>
              <td class="p-3.5 text-slate-600 font-mono">{{ u.email }}</td>
              <td class="p-3.5">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="getRoleClass(u.role_slug)">
                  {{ u.role_name }}
                </span>
              </td>
              <td class="p-3.5">
                <span :class="u.status === 'Active' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-rose-100 text-rose-800 border border-rose-200'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider">
                  {{ u.status }}
                </span>
              </td>
              <td class="p-3.5 text-right">
                <button @click="openUserModal(u)" class="px-2.5 py-1 rounded-lg text-xs font-semibold text-blue-900 hover:bg-blue-50 cursor-pointer">
                  Edit
                </button>
              </td>
            </tr>
            <tr v-if="filteredUsers.length === 0">
              <td colspan="5" class="p-8 text-center text-slate-400 text-xs">
                <Users class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                <div class="font-semibold text-slate-600">No matching staff or system accounts found.</div>
                <button 
                  v-if="searchUserQuery || selectedRoleFilter || selectedStatusFilter" 
                  @click="searchUserQuery = ''; selectedRoleFilter = ''; selectedStatusFilter = ''" 
                  class="mt-2 text-blue-900 font-bold hover:underline cursor-pointer"
                >
                  Clear all filters
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 3: SCHOOL YEAR LOCK / UNLOCK -->
    <div v-if="activeTab === 'school_years'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
        <div>
          <h2 class="text-base font-bold text-slate-900">School Year Academic & Curriculum Governance</h2>
          <p class="text-xs text-slate-500">Manage 4-stage academic lifecycles, independent admission intake gates, and DepEd curriculum blueprints.</p>
        </div>
        <button 
          @click="openSchoolYearModal()" 
          class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-900 hover:bg-blue-800 text-white shadow-xs transition flex items-center space-x-1.5 shrink-0 cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Add School Year</span>
        </button>
      </div>

      <div class="space-y-4">
        <div v-for="sy in schoolYears" :key="sy.id" class="p-6 rounded-3xl border border-slate-200 bg-slate-50/60 space-y-4 shadow-2xs">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200/70 pb-3">
            <div>
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="font-extrabold text-base text-slate-900">{{ sy.name }} ({{ sy.code }})</h3>
                
                <!-- 4-STAGE ACADEMIC LIFECYCLE BADGES -->
                <span v-if="sy.is_active" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center space-x-1">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                  <span>ACTIVE ACADEMIC CYCLE</span>
                </span>
                <span v-else-if="sy.lifecycle_stage === 'Early Admission'" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-sky-100 text-sky-800 border border-sky-300 flex items-center space-x-1">
                  <span class="w-1.5 h-1.5 rounded-full bg-sky-600"></span>
                  <span>EARLY ADMISSION OPEN</span>
                </span>
                <span v-else-if="sy.lifecycle_stage === 'Planning'" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300 flex items-center space-x-1">
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                  <span>PLANNING & SETUP</span>
                </span>
                <span v-else class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-200 text-slate-700 border border-slate-300">
                  ARCHIVED / HISTORICAL
                </span>
              </div>
              <div class="flex flex-wrap items-center gap-2 mt-1.5">
                <p class="text-xs text-slate-500">Duration: {{ sy.start_date }} to {{ sy.end_date }}</p>
                <span class="text-slate-300 hidden sm:inline">•</span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-900 border border-blue-200">
                  {{ sy.active_semester || '1st Semester' }}
                </span>
                <button 
                  @click="toggleSemester(sy)"
                  class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 transition shadow-2xs cursor-pointer"
                  :title="`Switch term to ${sy.active_semester === '1st Semester' ? '2nd Semester' : '1st Semester'}`"
                >
                  <RotateCcw class="w-3 h-3 text-blue-600" />
                  <span>Switch to {{ sy.active_semester === '1st Semester' ? '2nd Sem' : '1st Sem' }}</span>
                </button>
              </div>
            </div>

            <div class="flex items-center space-x-2">
              <button 
                v-if="!sy.is_active"
                @click="openRolloverModal(sy)" 
                class="px-3.5 py-1.5 rounded-xl font-bold bg-blue-900 hover:bg-blue-800 text-white text-xs transition shadow-xs flex items-center space-x-1.5 cursor-pointer"
              >
                <Sparkles class="w-3.5 h-3.5 text-amber-300" />
                <span>Rollover / Set Active</span>
              </button>
              <button 
                @click="openSchoolYearModal(sy)" 
                class="px-3 py-1.5 rounded-xl font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs transition cursor-pointer"
              >
                Edit
              </button>
              <button 
                v-if="!sy.is_active"
                @click="openDeleteSyModal(sy)" 
                class="px-2.5 py-1.5 rounded-xl font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs transition cursor-pointer flex items-center space-x-1"
                title="Delete draft school year"
              >
                <Trash2 class="w-3.5 h-3.5" />
                <span class="hidden sm:inline">Delete</span>
              </button>
            </div>
          </div>

          <!-- DUAL CONTROLS GRID: INDEPENDENT ENROLLMENT GATE & CURRICULUM BLUEPRINT -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- 1. INDEPENDENT ENROLLMENT INTAKE GATE -->
            <div class="p-4 rounded-2xl bg-white border border-slate-200 flex items-center justify-between gap-3">
              <div>
                <div class="text-[10px] font-extrabold uppercase text-slate-400">Admission & Enrollment Gate</div>
                <div class="font-bold text-xs text-slate-800 mt-0.5">
                  <span v-if="!sy.is_active && !sy.is_locked" class="text-sky-700 font-extrabold flex items-center space-x-1">
                    <Unlock class="w-3.5 h-3.5 text-sky-600" />
                    <span>EARLY ADMISSION OPEN</span>
                  </span>
                  <span v-else-if="!sy.is_active && sy.is_locked" class="text-slate-500 font-extrabold flex items-center space-x-1">
                    <Lock class="w-3.5 h-3.5 text-slate-400" />
                    <span>INTAKE CLOSED (PLANNING)</span>
                  </span>
                  <span v-else-if="sy.is_active && !sy.is_locked" class="text-emerald-700 font-extrabold flex items-center space-x-1">
                    <Unlock class="w-3.5 h-3.5 text-emerald-600" />
                    <span>OPEN FOR ADMISSIONS & ENROLLMENT</span>
                  </span>
                  <span v-else class="text-rose-700 font-extrabold flex items-center space-x-1">
                    <Lock class="w-3.5 h-3.5 text-rose-600" />
                    <span>CURRENT INTAKE CLOSED</span>
                  </span>
                </div>
                <div class="text-[10px] text-slate-400 mt-0.5">
                  <template v-if="!sy.is_active">
                    {{ sy.is_locked ? 'Intake closed. Unlock to accept advance student applicant entries.' : 'Accepting advance student applications for upcoming school year.' }}
                  </template>
                  <template v-else>
                    {{ sy.is_locked ? 'New applications & payment assessments closed for current cycle.' : 'Accepting new admission & enrollment entries for current active cycle.' }}
                  </template>
                </div>
              </div>

              <!-- Independent Intake Toggle Button -->
              <button 
                @click="toggleLock(sy.id)"
                :class="sy.is_locked ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-rose-600 hover:bg-rose-500 text-white'"
                class="px-3.5 py-2 rounded-xl text-xs font-bold shadow-xs transition shrink-0 cursor-pointer"
                :title="sy.is_locked ? 'Open admission intake for this school year' : 'Close admission intake for this school year'"
              >
                {{ sy.is_locked ? (sy.is_active ? 'Unlock Intake' : 'Open Early Intake') : 'Lock Intake' }}
              </button>
            </div>

            <!-- 2. CURRICULUM BLUEPRINT LOCK -->
            <div class="p-4 rounded-2xl bg-white border border-slate-200 flex items-center justify-between gap-3">
              <div>
                <div class="text-[10px] font-extrabold uppercase text-slate-400">DepEd Curriculum Blueprint</div>
                <div class="font-bold text-xs text-slate-800 mt-0.5">
                  <span :class="sy.curriculum_locked ? 'text-emerald-700 font-extrabold' : 'text-amber-700 font-extrabold'">
                    {{ sy.curriculum_locked ? '🔒 DECLARED & LOCKED' : '🟡 DRAFT / SETUP MODE' }}
                  </span>
                </div>
                <div class="text-[10px] text-slate-400 mt-0.5">
                  {{ sy.curriculum_locked ? 'Subjects & Strands frozen (records protected).' : 'Academic offerings & curriculum subjects are editable.' }}
                </div>
              </div>

              <!-- Curriculum Lock Controls -->
              <button 
                @click="openCurriculumLockModal(sy)"
                :class="sy.curriculum_locked ? 'bg-slate-800 hover:bg-slate-700 text-slate-200' : 'bg-emerald-600 hover:bg-emerald-500 text-white'"
                class="px-3.5 py-2 rounded-xl text-xs font-bold shadow-xs transition shrink-0 cursor-pointer"
              >
                {{ sy.curriculum_locked ? 'Unlock Setup' : 'Declare & Lock' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- USER CREATE / EDIT MODAL -->
    <div v-if="showUserModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl border border-slate-200 text-xs">
        <h3 class="text-base font-bold text-slate-900 mb-4">{{ userForm.id ? 'Edit User Account' : 'Create User Account' }}</h3>
        <form @submit.prevent="saveUser" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">First Name *</label>
              <input v-model="userForm.first_name" type="text" required class="w-full px-3 py-2 rounded-xl border border-slate-300" />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Last Name *</label>
              <input v-model="userForm.last_name" type="text" required class="w-full px-3 py-2 rounded-xl border border-slate-300" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Username *</label>
              <input v-model="userForm.username" type="text" required class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono" />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Role *</label>
              <select v-model="userForm.role_id" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                <option v-for="r in usersList.roles" :key="r.id" :value="r.id">{{ r.name }}</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Email Address *</label>
            <input v-model="userForm.email" type="email" required class="w-full px-3 py-2 rounded-xl border border-slate-300" />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Password {{ userForm.id ? '(Leave blank to keep unchanged)' : '*' }}</label>
            <input v-model="userForm.password" type="password" :required="!userForm.id" class="w-full px-3 py-2 rounded-xl border border-slate-300" />
          </div>

          <div class="flex justify-end space-x-3 pt-3">
            <button type="button" @click="showUserModal = false" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer">Cancel</button>
            <button type="submit" class="px-5 py-2.5 rounded-xl font-semibold bg-blue-900 hover:bg-blue-800 text-white shadow-xs cursor-pointer">Save Account</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ADMIN CURRICULUM DECLARATION & LOCK CONFIRMATION POPUP MODAL -->
    <div v-if="curriculumLockModal.isOpen" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200 text-xs space-y-4 animate-in fade-in zoom-in duration-150">
        
        <!-- Header with Dynamic Icon and Alert Styling -->
        <div class="flex items-start space-x-3.5 border-b border-slate-100 pb-4">
          <div 
            class="p-3 rounded-2xl shrink-0"
            :class="curriculumLockModal.sy?.curriculum_locked ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200'"
          >
            <Unlock v-if="curriculumLockModal.sy?.curriculum_locked" class="w-6 h-6 text-amber-700" />
            <Lock v-else class="w-6 h-6 text-emerald-700" />
          </div>
          <div>
            <div 
              class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase font-mono tracking-wider mb-1"
              :class="curriculumLockModal.sy?.curriculum_locked ? 'bg-amber-100 text-amber-900 border border-amber-200' : 'bg-emerald-100 text-emerald-900 border border-emerald-200'"
            >
              <span>{{ curriculumLockModal.sy?.curriculum_locked ? 'Unlock for Drafting' : 'Official DepEd Academic Freeze' }}</span>
            </div>
            <h3 class="text-base font-extrabold text-slate-900 leading-snug">
              {{ curriculumLockModal.sy?.curriculum_locked ? 'Switch Curriculum to Draft / Setup Mode?' : 'Officially Declare & Lock SY Curriculum?' }}
            </h3>
            <p class="text-[11px] text-slate-500 mt-0.5">
              School Year: <strong>{{ curriculumLockModal.sy?.name }} ({{ curriculumLockModal.sy?.code }})</strong>
            </p>
          </div>
        </div>

        <!-- Explanatory Box -->
        <div 
          class="p-4 rounded-2xl border text-xs space-y-2 leading-relaxed"
          :class="curriculumLockModal.sy?.curriculum_locked ? 'bg-amber-50/80 border-amber-200 text-amber-950' : 'bg-emerald-50/80 border-emerald-200 text-emerald-950'"
        >
          <div class="font-bold text-slate-900 flex items-center space-x-1.5">
            <AlertCircle class="w-4 h-4" :class="curriculumLockModal.sy?.curriculum_locked ? 'text-amber-700' : 'text-emerald-700'" />
            <span>{{ curriculumLockModal.sy?.curriculum_locked ? 'Administrator Safeguard Notice:' : 'Curriculum Lock Consequences:' }}</span>
          </div>

          <template v-if="curriculumLockModal.sy?.curriculum_locked">
            <ul class="list-disc list-inside space-y-1.5 text-[11px] text-slate-700">
              <li>Re-enables editing, adding, and deleting learning area subjects and strands in Coordinator dashboard.</li>
              <li><strong class="text-amber-900">Warning:</strong> Only unlock if academic adjustments are genuinely required prior to student enrollment processing.</li>
              <li>Re-lock once edits are complete to protect permanent student records.</li>
            </ul>
          </template>

          <template v-else>
            <ul class="list-disc list-inside space-y-1.5 text-[11px] text-slate-700">
              <li>Freezes all 119 subjects and 8 strands from modification or deletion across all dashboards.</li>
              <li>Protects student permanent records (SF10), quarterly report cards (SF9), and section timetables.</li>
              <li>Any DepEd updates will take effect in subsequent school year cycles.</li>
            </ul>
          </template>
        </div>

        <!-- Footer Action Buttons -->
        <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
          <button 
            type="button" 
            @click="curriculumLockModal.isOpen = false" 
            :disabled="curriculumLockModal.isProcessing"
            class="px-4 py-2.5 rounded-xl font-bold text-slate-600 hover:bg-slate-100 transition text-xs"
          >
            Cancel
          </button>
          
          <button 
            type="button" 
            @click="executeAdminCurriculumLock()" 
            :disabled="curriculumLockModal.isProcessing"
            class="px-5 py-2.5 rounded-xl font-bold text-white shadow-md transition flex items-center space-x-2 text-xs"
            :class="curriculumLockModal.sy?.curriculum_locked ? 'bg-amber-600 hover:bg-amber-500' : 'bg-emerald-600 hover:bg-emerald-500'"
          >
            <span v-if="curriculumLockModal.isProcessing" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <Unlock v-else-if="curriculumLockModal.sy?.curriculum_locked" class="w-3.5 h-3.5 text-white" />
            <Lock v-else class="w-3.5 h-3.5 text-white" />
            <span>{{ curriculumLockModal.isProcessing ? 'Updating Status...' : (curriculumLockModal.sy?.curriculum_locked ? 'Confirm Switch to Draft Mode' : 'Confirm Declare & Lock') }}</span>
          </button>
        </div>

      </div>
    </div>

    <!-- SCHOOL YEAR CREATE / EDIT MODAL -->
    <div v-if="showSchoolYearModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl border border-slate-200 text-xs">
        <h3 class="text-base font-bold text-slate-900 mb-4">{{ schoolYearForm.id ? 'Edit School Year' : 'Create New School Year' }}</h3>
        <form @submit.prevent="saveSchoolYear" class="space-y-4">
          
          <!-- SPLIT ACADEMIC YEAR INPUTS (FULL WIDTH) -->
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Academic Year Span *</label>
            <div class="flex items-center space-x-3">
              <div class="relative flex-1">
                <input 
                  v-model="schoolYearForm.start_year" 
                  @input="onStartYearChange"
                  type="number" 
                  min="2000" 
                  max="2099" 
                  required 
                  placeholder="2027" 
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono font-bold text-center text-sm focus:border-blue-900 focus:ring-1 focus:ring-blue-900" 
                />
                <span class="block text-[10px] text-slate-400 text-center mt-1 font-medium">Opening Year</span>
              </div>

              <div class="px-1 text-slate-400 font-black text-base select-none mb-4">
                —
              </div>

              <div class="relative flex-1">
                <input 
                  v-model="schoolYearForm.end_year" 
                  @input="onEndYearChange"
                  type="number" 
                  min="2000" 
                  max="2099" 
                  required 
                  placeholder="2028" 
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono font-bold text-center text-sm focus:border-blue-900 focus:ring-1 focus:ring-blue-900" 
                />
                <span class="block text-[10px] text-slate-400 text-center mt-1 font-medium">Closing Year</span>
              </div>
            </div>
          </div>

          <!-- GENERATED CYCLE IDENTIFIER PREVIEW -->
          <div class="p-3 rounded-2xl bg-blue-50/70 border border-blue-100 flex items-center justify-between">
            <div>
              <span class="text-[10px] font-extrabold uppercase text-blue-800">Generated Cycle Identifier</span>
              <div class="font-extrabold text-xs text-blue-950 mt-0.5">
                {{ schoolYearForm.name || 'School Year' }}
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold bg-white text-blue-900 border border-blue-200">
              {{ schoolYearForm.code }}
            </span>
          </div>

          <!-- OPTIONAL DEPED CALENDAR SPAN -->
          <div class="p-3.5 bg-slate-50/80 rounded-2xl border border-slate-200 space-y-2">
            <div class="flex items-center justify-between">
              <label class="block font-bold text-slate-700">DepEd School Calendar Span (Optional)</label>
              <span class="text-[10px] text-slate-400 font-medium">For transcripts & SF10</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] font-medium text-slate-500 mb-1">Opening Date</label>
                <input v-model="schoolYearForm.start_date" type="date" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 bg-white text-xs" />
              </div>
              <div>
                <label class="block text-[11px] font-medium text-slate-500 mb-1">Closing Date</label>
                <input v-model="schoolYearForm.end_date" type="date" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 bg-white text-xs" />
              </div>
            </div>
            <p class="text-[10px] text-slate-400">Auto-filled based on standard DepEd calendar (August – May). Adjust only if DepEd releases an altered schedule.</p>
          </div>

          <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-900">
            <strong>Note:</strong> New school years are created with admission gate locked and curriculum in Setup / Draft mode so the academic coordinator can prepare offerings.
          </div>

          <div class="flex justify-end space-x-3 pt-3 border-t border-slate-100">
            <button type="button" @click="showSchoolYearModal = false" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer">Cancel</button>
            <button type="submit" :disabled="isSavingSy" class="px-5 py-2.5 rounded-xl font-semibold bg-blue-900 hover:bg-blue-800 text-white shadow-xs transition flex items-center space-x-1.5 cursor-pointer">
              <span v-if="isSavingSy" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span>{{ isSavingSy ? 'Saving...' : 'Save School Year' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- PRE-FLIGHT SCHOOL YEAR ROLLOVER WIZARD MODAL -->
    <div v-if="rolloverModal.isOpen" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-7 shadow-2xl border border-slate-200 text-xs space-y-5 animate-in fade-in zoom-in-95 duration-150 max-h-[90vh] overflow-y-auto">
        
        <!-- Header -->
        <div class="flex items-start space-x-3.5 border-b border-slate-100 pb-4">
          <div class="p-3 rounded-2xl bg-blue-100 text-blue-900 border border-blue-200 shrink-0">
            <Sparkles class="w-6 h-6 text-blue-700" />
          </div>
          <div>
            <div class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase font-mono tracking-wider bg-blue-100 text-blue-900 border border-blue-200 mb-1">
              <span>Academic Transition & Rollover Wizard</span>
            </div>
            <h3 class="text-lg font-extrabold text-slate-900 leading-snug">
              Execute School Year Rollover
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Target Academic Master: <strong class="text-slate-800">{{ rolloverModal.sy?.name }} ({{ rolloverModal.sy?.code }})</strong>
            </p>
          </div>
        </div>

        <!-- Pre-Flight Loading State -->
        <div v-if="rolloverModal.isLoadingPreflight" class="py-12 flex flex-col items-center justify-center space-y-3">
          <div class="w-8 h-8 border-3 border-blue-900 border-t-transparent rounded-full animate-spin"></div>
          <p class="text-xs font-semibold text-slate-600">Running real-time academic diagnostics...</p>
        </div>

        <!-- Pre-Flight Diagnostic Dashboard -->
        <div v-else class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- 1. Target SY Curriculum Readiness -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
              <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold uppercase text-slate-400">Target Curriculum</span>
                <CheckCircle2 v-if="rolloverModal.preflight?.target_sy?.approved_subjects_count > 0" class="w-4 h-4 text-emerald-600" />
                <AlertTriangle v-else class="w-4 h-4 text-amber-500" />
              </div>
              <div class="text-lg font-black text-slate-900">
                {{ rolloverModal.preflight?.target_sy?.approved_subjects_count ?? 0 }}
              </div>
              <p class="text-[10px] text-slate-500 font-medium">
                Approved subjects configured for {{ rolloverModal.sy?.code }}.
              </p>
            </div>

            <!-- 2. Active Enrolled Clearance -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
              <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold uppercase text-slate-400">Active Students</span>
                <Users class="w-4 h-4 text-blue-600" />
              </div>
              <div class="text-lg font-black text-slate-900">
                {{ rolloverModal.preflight?.current_active_sy?.active_enrolled_students ?? 0 }}
              </div>
              <p class="text-[10px] text-slate-500 font-medium">
                {{ rolloverModal.preflight?.current_active_sy?.unfinalized_grades_count > 0 
                    ? `${rolloverModal.preflight?.current_active_sy?.unfinalized_grades_count} pending unsubmitted grades`
                    : 'All grades submitted & cleared' }}
              </p>
            </div>

            <!-- 3. Mass-Progression Prospects -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
              <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold uppercase text-slate-400">Progression</span>
                <GraduationCap class="w-4 h-4 text-indigo-600" />
              </div>
              <div class="text-lg font-black text-slate-900">
                {{ (rolloverModal.preflight?.progression_summary?.promotable_students ?? 0) + (rolloverModal.preflight?.progression_summary?.graduating_seniors ?? 0) }}
              </div>
              <p class="text-[10px] text-slate-500 font-medium">
                {{ rolloverModal.preflight?.progression_summary?.promotable_students ?? 0 }} promotable • {{ rolloverModal.preflight?.progression_summary?.graduating_seniors ?? 0 }} graduating
              </p>
            </div>
          </div>

          <!-- Rollover Options Checkboxes -->
          <div class="p-4 rounded-2xl bg-blue-50/50 border border-blue-100 space-y-3">
            <h4 class="font-bold text-xs text-blue-950 flex items-center space-x-1.5">
              <Layers class="w-4 h-4 text-blue-800" />
              <span>Rollover Transition Actions:</span>
            </h4>

            <label class="flex items-start space-x-3 cursor-pointer select-none">
              <input 
                type="checkbox" 
                v-model="rolloverModal.autoPromote" 
                class="mt-0.5 rounded border-slate-300 text-blue-900 focus:ring-blue-900 h-4 w-4"
              />
              <div class="text-xs">
                <span class="font-bold text-slate-900">Automate Student Mass-Progression & Promotion</span>
                <p class="text-[11px] text-slate-500">Automatically advance passed Grade 7-11 students to the next grade level and transition Grade 12 students to Graduated.</p>
              </div>
            </label>

            <label class="flex items-start space-x-3 cursor-pointer select-none">
              <input 
                type="checkbox" 
                v-model="rolloverModal.archivePrevious" 
                class="mt-0.5 rounded border-slate-300 text-blue-900 focus:ring-blue-900 h-4 w-4"
              />
              <div class="text-xs">
                <span class="font-bold text-slate-900">Archive Previous Active School Year</span>
                <p class="text-[11px] text-slate-500">Set previous active school year to 'Archived' status to preserve historical grading records and SF10 permanent sheets.</p>
              </div>
            </label>
          </div>

          <!-- Transition Notice Safeguard -->
          <div class="p-3.5 bg-amber-50 rounded-2xl border border-amber-200 text-xs text-amber-950 flex items-start space-x-2.5">
            <AlertCircle class="w-4 h-4 text-amber-700 shrink-0 mt-0.5" />
            <div class="text-[11px] leading-relaxed">
              <strong>Master Switch Notice:</strong> Activating this cycle will immediately switch default dashboard filters, teacher rosters, and student enrollment sessions to <strong>{{ rolloverModal.sy?.name }}</strong>.
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
          <button 
            type="button" 
            @click="rolloverModal.isOpen = false" 
            :disabled="rolloverModal.isProcessing"
            class="px-4 py-2.5 rounded-xl font-bold text-slate-600 hover:bg-slate-100 transition text-xs cursor-pointer"
          >
            Cancel
          </button>
          
          <button 
            type="button" 
            @click="executeRollover()" 
            :disabled="rolloverModal.isProcessing || rolloverModal.isLoadingPreflight"
            class="px-5 py-2.5 rounded-xl font-bold bg-blue-900 hover:bg-blue-800 text-white shadow-md transition flex items-center space-x-2 text-xs cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="rolloverModal.isProcessing" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <Sparkles v-else class="w-3.5 h-3.5 text-amber-300" />
            <span>{{ rolloverModal.isProcessing ? 'Executing Rollover...' : 'Confirm & Execute Rollover' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- DELETE SCHOOL YEAR CONFIRMATION MODAL -->
    <div v-if="deleteSyModal.isOpen" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-slate-200 text-xs space-y-4 animate-in fade-in zoom-in-95 duration-150 text-slate-900">
        <div class="flex items-start space-x-3.5 border-b border-slate-100 pb-3.5">
          <div class="p-3 rounded-2xl bg-rose-100 text-rose-800 border border-rose-200 shrink-0">
            <Trash2 class="w-6 h-6 text-rose-600" />
          </div>
          <div>
            <div class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase font-mono tracking-wider bg-rose-100 text-rose-900 border border-rose-200 mb-1">
              <span>Safe Delete Verification</span>
            </div>
            <h3 class="text-base font-extrabold text-slate-900 leading-snug">
              Delete School Year?
            </h3>
            <p class="text-[11px] text-slate-500 mt-0.5">
              Target: <strong>{{ deleteSyModal.sy?.name }} ({{ deleteSyModal.sy?.code }})</strong>
            </p>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-rose-50/60 border border-rose-200 text-xs space-y-2 text-rose-950">
          <div class="font-bold flex items-center space-x-1.5">
            <AlertTriangle class="w-4 h-4 text-rose-600 shrink-0" />
            <span>Permanent Removal Notice:</span>
          </div>
          <p class="text-[11px] leading-relaxed">
            This will permanently remove <strong>{{ deleteSyModal.sy?.name }}</strong> and its unapproved draft offerings. This action cannot be undone.
          </p>
          <p class="text-[10px] text-rose-700 font-medium">
            * The system will automatically block deletion if this academic year contains active or historical student enrollments.
          </p>
        </div>

        <div class="flex items-center justify-end space-x-2.5 pt-2 border-t border-slate-100">
          <button 
            type="button" 
            @click="deleteSyModal.isOpen = false" 
            :disabled="deleteSyModal.isProcessing"
            class="px-4 py-2.5 rounded-xl font-bold text-slate-600 hover:bg-slate-100 transition text-xs cursor-pointer"
          >
            Cancel
          </button>
          
          <button 
            type="button" 
            @click="executeDeleteSy()" 
            :disabled="deleteSyModal.isProcessing"
            class="px-5 py-2.5 rounded-xl font-bold bg-rose-600 hover:bg-rose-500 text-white shadow-md transition flex items-center space-x-2 text-xs cursor-pointer"
          >
            <span v-if="deleteSyModal.isProcessing" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <Trash2 v-else class="w-3.5 h-3.5 text-white" />
            <span>{{ deleteSyModal.isProcessing ? 'Deleting...' : 'Confirm Delete' }}</span>
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
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { 
  Users, Key, ShieldCheck, Plus, CheckCircle2, Lock, Unlock, AlertCircle, AlertTriangle, Info,
  RotateCcw, Sparkles, RefreshCw, Eye, BookOpen, Layers, Check, Search, ShieldAlert,
  ArrowUpDown, ArrowUp, ArrowDown, Filter, X, GraduationCap, Trash2
} from 'lucide-vue-next';
import api from '../../services/api';

const route = useRoute();
const activeTab = ref('stats');

watch(() => route.query.tab, (newTab) => {
  if (newTab && ['stats', 'users', 'school_years'].includes(newTab)) {
    activeTab.value = newTab;
  }
}, { immediate: true });

const stats = ref({ recent_logs: [] });
const usersList = ref({ users: [], roles: [] });
const schoolYears = ref([]);
const successMessage = ref('');
const errorMessage = ref('');

let successTimer = null;
let errorTimer = null;

watch(successMessage, (newVal) => {
  if (newVal) {
    if (successTimer) clearTimeout(successTimer);
    successTimer = setTimeout(() => {
      successMessage.value = '';
    }, 5000);
  }
});

watch(errorMessage, (newVal) => {
  if (newVal) {
    if (errorTimer) clearTimeout(errorTimer);
    errorTimer = setTimeout(() => {
      errorMessage.value = '';
    }, 6000);
  }
});
const searchLogQuery = ref('');

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

// Staff & System Accounts Search, Filter & Sort State
const searchUserQuery = ref('');
const selectedRoleFilter = ref('');
const selectedStatusFilter = ref('');
const userSortField = ref('name');
const userSortOrder = ref('asc');

const setUserSort = (field) => {
  if (userSortField.value === field) {
    userSortOrder.value = userSortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    userSortField.value = field;
    userSortOrder.value = 'asc';
  }
};

const toggleUserSortDirection = () => {
  userSortOrder.value = userSortOrder.value === 'asc' ? 'desc' : 'asc';
};

const filteredUsers = computed(() => {
  let list = usersList.value?.users || [];

  // 1. Text Search Filter
  if (searchUserQuery.value.trim()) {
    const q = searchUserQuery.value.toLowerCase().trim();
    list = list.filter(u => {
      const fullName = `${u.first_name || ''} ${u.last_name || ''}`.toLowerCase();
      const username = (u.username || '').toLowerCase();
      const email = (u.email || '').toLowerCase();
      const roleName = (u.role_name || '').toLowerCase();
      const roleSlug = (u.role_slug || '').toLowerCase();
      const status = (u.status || '').toLowerCase();

      return fullName.includes(q) || 
             username.includes(q) || 
             email.includes(q) || 
             roleName.includes(q) || 
             roleSlug.includes(q) || 
             status.includes(q);
    });
  }

  // 2. Role Filter
  if (selectedRoleFilter.value) {
    list = list.filter(u => u.role_slug === selectedRoleFilter.value);
  }

  // 3. Status Filter
  if (selectedStatusFilter.value) {
    list = list.filter(u => u.status === selectedStatusFilter.value);
  }

  // 4. Multi-field Sorting
  const sorted = [...list].sort((a, b) => {
    let comparison = 0;
    if (userSortField.value === 'name') {
      const nameA = `${a.last_name || ''} ${a.first_name || ''}`.toLowerCase();
      const nameB = `${b.last_name || ''} ${b.first_name || ''}`.toLowerCase();
      comparison = nameA.localeCompare(nameB);
    } else if (userSortField.value === 'username') {
      comparison = (a.username || '').localeCompare(b.username || '');
    } else if (userSortField.value === 'email') {
      comparison = (a.email || '').localeCompare(b.email || '');
    } else if (userSortField.value === 'role') {
      comparison = (a.role_name || a.role_slug || '').localeCompare(b.role_name || b.role_slug || '');
    } else if (userSortField.value === 'status') {
      comparison = (a.status || '').localeCompare(b.status || '');
    } else if (userSortField.value === 'id_desc') {
      comparison = (b.id || 0) - (a.id || 0);
    } else if (userSortField.value === 'id_asc') {
      comparison = (a.id || 0) - (b.id || 0);
    }

    return userSortOrder.value === 'desc' ? -comparison : comparison;
  });

  return sorted;
});

const filteredLogs = computed(() => {
  const list = stats.value?.recent_logs || [];
  if (!searchLogQuery.value.trim()) return list;
  const q = searchLogQuery.value.toLowerCase();
  return list.filter(l => 
    (l.action && l.action.toLowerCase().includes(q)) ||
    (l.username && l.username.toLowerCase().includes(q)) ||
    (l.details && l.details.toLowerCase().includes(q)) ||
    (l.ip_address && l.ip_address.toLowerCase().includes(q))
  );
});

const getAuditActionClass = (action) => {
  if (!action) return 'bg-slate-100 text-slate-700 border-slate-200';
  const a = action.toUpperCase();
  if (a.includes('LOGIN') || a.includes('REGISTER')) return 'bg-blue-50 text-blue-900 border-blue-200';
  if (a.includes('APPROVE') || a.includes('ENROLL') || a.includes('VERIFIED')) return 'bg-emerald-50 text-emerald-800 border-emerald-300';
  if (a.includes('PAYMENT') || a.includes('OR_')) return 'bg-emerald-100 text-emerald-900 border-emerald-300';
  if (a.includes('LOCK') || a.includes('TOGGLE')) return 'bg-amber-50 text-amber-900 border-amber-300';
  if (a.includes('CREATE') || a.includes('USER')) return 'bg-purple-50 text-purple-900 border-purple-200';
  if (a.includes('REJECT') || a.includes('DELETE') || a.includes('REVOKE')) return 'bg-rose-50 text-rose-800 border-rose-300';
  return 'bg-slate-100 text-slate-800 border-slate-200';
};

const showUserModal = ref(false);
const userForm = ref({
  id: null,
  role_id: 2,
  username: '',
  email: '',
  password: '',
  first_name: '',
  last_name: '',
  status: 'Active'
});

const getRoleClass = (slug) => {
  if (slug === 'admin') return 'bg-amber-100 text-amber-800';
  if (slug === 'coordinator') return 'bg-purple-100 text-purple-800';
  if (slug === 'registrar') return 'bg-blue-100 text-blue-800';
  if (slug === 'treasury') return 'bg-emerald-100 text-emerald-800';
  if (slug === 'records') return 'bg-cyan-100 text-cyan-800';
  return 'bg-slate-100 text-slate-800';
};

const loadStats = async () => {
  try {
    const res = await api.getDashboardStats();
    stats.value = res.data;
  } catch (err) {
    console.error('Failed to load stats:', err);
  }
};

const loadUsers = async () => {
  try {
    const res = await api.getUsers();
    usersList.value = res.data;
  } catch (err) {
    console.error('Failed to load users:', err);
  }
};

const loadSchoolYears = async () => {
  try {
    const res = await api.getSchoolYears();
    schoolYears.value = res.data;
  } catch (err) {
    console.error('Failed to load school years:', err);
  }
};

const toggleLock = async (syId) => {
  try {
    const res = await api.toggleSchoolYearLock(syId);
    successMessage.value = res.message;
    await loadSchoolYears();
  } catch (err) {
    showNotice('School Year Lock Failed', err.message || 'Failed to toggle school year lock.', 'error');
  }
};

const toggleSemester = async (sy) => {
  try {
    const res = await api.toggleSchoolYearSemester(sy.id);
    successMessage.value = res.message || 'Active semester updated successfully!';
    await loadSchoolYears();
  } catch (err) {
    showNotice('Semester Switch Failed', err.message || 'Failed to switch semester.', 'error');
  }
};

const curriculumLockModal = ref({
  isOpen: false,
  sy: null,
  isProcessing: false
});

const openCurriculumLockModal = (sy) => {
  curriculumLockModal.value = {
    isOpen: true,
    sy: sy,
    isProcessing: false
  };
};

const executeAdminCurriculumLock = async () => {
  if (!curriculumLockModal.value.sy) return;
  curriculumLockModal.value.isProcessing = true;
  try {
    const res = await api.toggleAdminCurriculumLock(curriculumLockModal.value.sy.id);
    successMessage.value = res.message;
    curriculumLockModal.value.isOpen = false;
    await loadSchoolYears();
  } catch (err) {
    showNotice('Curriculum Lock Failed', err.message || 'Failed to toggle curriculum lock.', 'error');
  } finally {
    curriculumLockModal.value.isProcessing = false;
  }
};

// --- SCHOOL YEAR FORM & ACTIVE CYCLE HANDLERS ---
const showSchoolYearModal = ref(false);
const isSavingSy = ref(false);
const schoolYearForm = ref({
  id: null,
  start_year: '2028',
  end_year: '2029',
  code: '2028-2029',
  name: 'School Year 2028-2029',
  start_date: '2028-08-01',
  end_date: '2029-05-31',
  active_semester: '1st Semester',
  is_active: 0
});

const syncSchoolYearCodeAndDates = () => {
  const start = schoolYearForm.value.start_year;
  const end = schoolYearForm.value.end_year;
  if (start && end) {
    const code = `${start}-${end}`;
    schoolYearForm.value.code = code;
    schoolYearForm.value.name = `School Year ${code}`;
    if (!schoolYearForm.value.id) {
      schoolYearForm.value.start_date = `${start}-08-01`;
      schoolYearForm.value.end_date = `${end}-05-31`;
    }
  }
};

const onStartYearChange = () => {
  const sy = parseInt(schoolYearForm.value.start_year, 10);
  if (!isNaN(sy) && sy >= 2000 && sy <= 2099) {
    schoolYearForm.value.end_year = (sy + 1).toString();
  }
  syncSchoolYearCodeAndDates();
};

const onEndYearChange = () => {
  syncSchoolYearCodeAndDates();
};

const openSchoolYearModal = (sy = null) => {
  if (sy) {
    let startY = '2028';
    let endY = '2029';
    const match = (sy.code || '').match(/^(\d{4})-(\d{4})$/);
    if (match) {
      startY = match[1];
      endY = match[2];
    } else if (sy.start_date) {
      startY = sy.start_date.substring(0, 4);
      endY = (parseInt(startY, 10) + 1).toString();
    }

    schoolYearForm.value = {
      id: sy.id,
      start_year: startY,
      end_year: endY,
      code: sy.code,
      name: sy.name,
      start_date: sy.start_date,
      end_date: sy.end_date,
      active_semester: sy.active_semester || '1st Semester',
      is_active: sy.is_active
    };
  } else {
    // Propose the next upcoming school year
    const nextStart = 2028;
    const nextEnd = 2029;
    schoolYearForm.value = {
      id: null,
      start_year: nextStart.toString(),
      end_year: nextEnd.toString(),
      code: `${nextStart}-${nextEnd}`,
      name: `School Year ${nextStart}-${nextEnd}`,
      start_date: `${nextStart}-08-01`,
      end_date: `${nextEnd}-05-31`,
      active_semester: '1st Semester',
      is_active: 0
    };
  }
  showSchoolYearModal.value = true;
};

const saveSchoolYear = async () => {
  isSavingSy.value = true;
  try {
    const res = await api.saveSchoolYear(schoolYearForm.value);
    successMessage.value = res.message || 'School year saved successfully!';
    showSchoolYearModal.value = false;
    await loadSchoolYears();
  } catch (err) {
    showNotice('Save Failed', err.message || 'Failed to save school year.', 'error');
  } finally {
    isSavingSy.value = false;
  }
};

const rolloverModal = ref({
  isOpen: false,
  sy: null,
  isProcessing: false,
  isLoadingPreflight: false,
  preflight: null,
  autoPromote: true,
  archivePrevious: true
});

const openRolloverModal = async (sy) => {
  rolloverModal.value = {
    isOpen: true,
    sy: sy,
    isProcessing: false,
    isLoadingPreflight: true,
    preflight: null,
    autoPromote: true,
    archivePrevious: true
  };

  try {
    const res = await api.getRolloverPreflightCheck(sy.id);
    rolloverModal.value.preflight = res.data;
  } catch (err) {
    showNotice('Diagnostics Notice', err.message || 'Could not fetch pre-flight diagnostics for this school year.', 'warning');
  } finally {
    rolloverModal.value.isLoadingPreflight = false;
  }
};

const executeRollover = async () => {
  if (!rolloverModal.value.sy) return;
  rolloverModal.value.isProcessing = true;
  try {
    const res = await api.executeSchoolYearRollover({
      target_school_year_id: rolloverModal.value.sy.id,
      auto_promote_students: rolloverModal.value.autoPromote,
      archive_previous_sy: rolloverModal.value.archivePrevious
    });
    showNotice('Academic Rollover Completed', res.message || 'Active school year switched and records updated successfully!', 'success');
    rolloverModal.value.isOpen = false;
    await loadSchoolYears();
    await loadStats();
  } catch (err) {
    showNotice('Rollover Execution Failed', err.message || 'Failed to execute school year rollover.', 'error');
  } finally {
    rolloverModal.value.isProcessing = false;
  }
};

const deleteSyModal = ref({
  isOpen: false,
  sy: null,
  isProcessing: false
});

const openDeleteSyModal = (sy) => {
  deleteSyModal.value = {
    isOpen: true,
    sy: sy,
    isProcessing: false
  };
};

const executeDeleteSy = async () => {
  if (!deleteSyModal.value.sy) return;
  deleteSyModal.value.isProcessing = true;
  try {
    const res = await api.deleteSchoolYear(deleteSyModal.value.sy.id);
    showNotice('School Year Deleted', res.message || 'School year was deleted successfully.', 'success');
    deleteSyModal.value.isOpen = false;
    await loadSchoolYears();
    await loadStats();
  } catch (err) {
    showNotice('Deletion Blocked', err.message || 'Could not delete school year.', 'error');
  } finally {
    deleteSyModal.value.isProcessing = false;
  }
};

const openUserModal = (u = null) => {
  if (u) {
    userForm.value = {
      id: u.id,
      role_id: u.role_id,
      username: u.username,
      email: u.email,
      password: '',
      first_name: u.first_name,
      last_name: u.last_name,
      status: u.status
    };
  } else {
    userForm.value = {
      id: null,
      role_id: 2,
      username: '',
      email: '',
      password: 'password123',
      first_name: '',
      last_name: '',
      status: 'Active'
    };
  }
  showUserModal.value = true;
};

const saveUser = async () => {
  try {
    await api.saveUser(userForm.value);
    successMessage.value = 'User account saved successfully!';
    showUserModal.value = false;
    await loadUsers();
  } catch (err) {
    showNotice('Save Failed', err.message || 'Failed to save user.', 'error');
  }
};

onMounted(() => {
  loadStats();
  loadUsers();
  loadSchoolYears();
});
</script>
