<template>
  <div>
    <!-- Mobile Backdrop Overlay -->
    <div 
      v-if="isOpen" 
      @click="$emit('close')" 
      class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-40 lg:hidden transition-opacity"
    ></div>

    <!-- Sidenav Sidebar Container (Dark Navy Theme with Greenfield-style Clean Layout) -->
    <aside 
      :class="[
        'fixed top-0 bottom-0 left-0 z-50 flex flex-col bg-[#071322] border-r border-[#16273f] text-slate-200 transition-all duration-300 ease-in-out shadow-2xl no-print select-none',
        // Width handling
        isCollapsed ? 'w-[78px]' : 'w-[270px]',
        // Mobile visibility handling
        isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      ]"
    >
      <!-- 1. Sidenav Brand Header (Logo + School Name & Subtitle + Collapse Button on Right) -->
      <div 
        :class="[
          'h-20 flex items-center border-b border-[#16273f] shrink-0 bg-[#06101c] transition-all',
          isCollapsed ? 'justify-center px-2' : 'justify-between px-4'
        ]"
      >
        <!-- Expanded Mode: Brand Title & Crest Logo (Hidden when collapsed) -->
        <div v-if="!isCollapsed" class="flex items-center space-x-3 overflow-hidden min-w-0">
          <!-- School Crest Badge -->
          <router-link to="/" class="shrink-0 group">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-900/80 via-[#0e2748] to-emerald-900/40 p-0.5 border border-blue-400/30 group-hover:border-blue-300 flex items-center justify-center shadow-md transition-all">
              <GraduationCap class="w-5 h-5 text-blue-300 group-hover:scale-110 transition-transform" />
            </div>
          </router-link>

          <!-- Text Information (School Name + Portal Subtitle) -->
          <router-link to="/" class="min-w-0 transition-opacity duration-200 group">
            <div class="font-serif font-black text-sm text-white tracking-tight leading-tight truncate group-hover:text-blue-300 transition">
              BSLA Biringan
            </div>
            <div class="text-[10px] font-semibold text-slate-400 truncate mt-0.5">
              {{ currentRoleTitle }}
            </div>
          </router-link>
        </div>

        <!-- Expanded Mode Right: Burger Toggle & Mobile Close -->
        <div v-if="!isCollapsed" class="flex items-center space-x-1 shrink-0">
          <button 
            @click="$emit('toggle-collapse')" 
            type="button"
            class="hidden lg:flex w-8 h-8 rounded-lg bg-slate-800/80 hover:bg-slate-700/90 border border-slate-700/60 text-slate-300 hover:text-white items-center justify-center transition cursor-pointer"
            title="Collapse Sidebar"
          >
            <Menu class="w-4 h-4" />
          </button>

          <!-- Mobile Close Drawer Button -->
          <button 
            @click="$emit('close')" 
            type="button"
            class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer"
            title="Close Sidebar"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Collapsed Mode: Only Centered Expand Toggle Button -->
        <button 
          v-if="isCollapsed"
          @click="$emit('toggle-collapse')" 
          type="button"
          class="w-10 h-10 rounded-xl bg-slate-800/90 hover:bg-slate-700 border border-slate-700/70 text-slate-300 hover:text-white flex items-center justify-center transition shadow-md cursor-pointer group"
          title="Expand Sidebar"
        >
          <Menu class="w-5 h-5 group-hover:scale-110 transition-transform" />
        </button>
      </div>

      <!-- 2. Sidenav Scrollable Navigation Section -->
      <div class="flex-1 overflow-y-auto px-3 py-4 space-y-5 custom-scrollbar">
        
        <!-- ==================================================== -->
        <!-- CASE A: SUPER ADMIN NAVIGATION (GROUPED & ACCORDION) -->
        <!-- ==================================================== -->
        <template v-if="isAdmin">
          <!-- Section 1: Admin Core Overview -->
          <div class="space-y-1">
            <div v-if="!isCollapsed" class="px-3.5 text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">
              OVERVIEW
            </div>

            <button 
              v-for="item in adminCoreNavItems"
              :key="item.id"
              @click="handleAdminCoreClick(item)"
              type="button"
              :title="isCollapsed ? item.label : ''"
              :class="[
                'w-full flex items-center rounded-xl font-semibold text-xs transition cursor-pointer text-left group relative',
                isCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5 space-x-3',
                isAdminCoreItemActive(item) 
                  ? 'bg-slate-800/80 text-white font-bold shadow-xs' 
                  : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/40'
              ]"
            >
              <!-- Signature Active Left Indicator Bar (from Reference Design) -->
              <span 
                v-if="isAdminCoreItemActive(item)" 
                class="absolute left-0 top-1.5 bottom-1.5 w-1 bg-emerald-500 rounded-r-md"
              ></span>

              <component 
                :is="item.icon" 
                :class="[
                  'shrink-0 transition-transform group-hover:scale-110',
                  isCollapsed ? 'w-5 h-5' : 'w-4 h-4',
                  isAdminCoreItemActive(item) ? 'text-emerald-400' : 'text-slate-400 group-hover:text-slate-200'
                ]" 
              />

              <span v-if="!isCollapsed" class="truncate flex-1">
                {{ item.label }}
              </span>
            </button>
          </div>

          <!-- Section 2: Department Portals & Modules Accordion -->
          <div class="space-y-1 pt-1">
            <div v-if="!isCollapsed" class="px-3.5 text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2 flex items-center justify-between">
              <span>PORTALS & MODULES</span>
              <ShieldCheck class="w-3 h-3 text-blue-400/80" />
            </div>

            <div v-for="group in adminPortalGroups" :key="group.id" class="space-y-1">
              <!-- Parent Portal Accordion Button -->
              <button 
                type="button"
                @click="toggleGroup(group)"
                :title="isCollapsed ? group.label : ''"
                :class="[
                  'w-full flex items-center rounded-xl font-semibold text-xs transition cursor-pointer text-left group select-none relative',
                  isCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5 space-x-3',
                  isGroupActive(group) 
                    ? 'bg-blue-950/60 text-blue-200 shadow-xs' 
                    : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/40'
                ]"
              >
                <!-- Active Left Border Indicator on Parent -->
                <span 
                  v-if="isGroupActive(group) && isCollapsed" 
                  class="absolute left-0 top-1.5 bottom-1.5 w-1 bg-emerald-500 rounded-r-md"
                ></span>

                <component 
                  :is="group.icon" 
                  :class="[
                    'shrink-0 transition-transform group-hover:scale-110',
                    isCollapsed ? 'w-5 h-5' : 'w-4 h-4',
                    isGroupActive(group) ? 'text-blue-400' : 'text-slate-400 group-hover:text-slate-200'
                  ]" 
                />

                <span v-if="!isCollapsed" class="truncate flex-1">
                  {{ group.label }}
                </span>
                
                <!-- Chevron Accordion Arrow -->
                <span 
                  v-if="!isCollapsed" 
                  :class="[
                    'shrink-0 text-slate-400 group-hover:text-slate-200 transition-transform duration-300 ease-in-out',
                    activeOpenGroupId === group.id ? 'rotate-180 text-blue-400' : 'rotate-0'
                  ]"
                >
                  <ChevronDown class="w-3.5 h-3.5" />
                </span>
              </button>

              <!-- Collapsible Child Sub-Items -->
              <div 
                :class="[
                  'grid transition-all duration-300 ease-in-out overflow-hidden',
                  !isCollapsed && activeOpenGroupId === group.id 
                    ? 'grid-rows-[1fr] opacity-100' 
                    : 'grid-rows-[0fr] opacity-0 pointer-events-none'
                ]"
              >
                <div class="min-h-0 ml-3 pl-2.5 border-l border-slate-800/80 space-y-1 py-1">
                  <button
                    v-for="child in group.children"
                    :key="child.id"
                    @click="handleChildNavClick(group.basePath, child)"
                    type="button"
                    :class="[
                      'w-full flex items-center rounded-lg text-xs transition-all duration-150 cursor-pointer text-left px-3 py-2 space-x-2.5 group relative',
                      isChildActive(group.basePath, child)
                        ? 'bg-slate-800/90 text-white font-bold shadow-2xs'
                        : 'text-slate-400 hover:text-white hover:bg-slate-800/40'
                    ]"
                  >
                    <!-- Active Left Bar on Child Item -->
                    <span 
                      v-if="isChildActive(group.basePath, child)" 
                      class="absolute left-0 top-1 bottom-1 w-0.5 bg-emerald-500 rounded-r-md"
                    ></span>

                    <component 
                      :is="child.icon" 
                      :class="[
                        'w-3.5 h-3.5 shrink-0 transition-colors',
                        isChildActive(group.basePath, child) ? 'text-emerald-400' : 'text-slate-400 group-hover:text-slate-300'
                      ]" 
                    />
                    <span class="truncate flex-1">{{ child.label }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- ==================================================== -->
        <!-- CASE B: ROLE PORTALS WITH CATEGORIZED SECTION DESIGN -->
        <!-- ==================================================== -->
        <template v-else>
          <div v-for="section in categorizedRoleSections" :key="section.title" class="space-y-1">
            <!-- Section Header (Clean uppercase, e.g. OVERVIEW, SCHEDULER) -->
            <div v-if="!isCollapsed" class="px-3.5 text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">
              {{ section.title }}
            </div>

            <!-- Section Items with Signature Left Indicator -->
            <div class="space-y-1">
              <button 
                v-for="item in section.items" 
                :key="item.id"
                @click="handleNavClick(item)"
                type="button"
                :title="isCollapsed ? item.label : ''"
                :class="[
                  'w-full flex items-center rounded-xl font-semibold text-xs transition cursor-pointer text-left group relative',
                  isCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5 space-x-3',
                  isItemActive(item) 
                    ? 'bg-slate-800/80 text-white font-bold shadow-xs' 
                    : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/40'
                ]"
              >
                <!-- Active Left Vertical Indicator (Reference signature) -->
                <span 
                  v-if="isItemActive(item)" 
                  class="absolute left-0 top-1.5 bottom-1.5 w-1 bg-emerald-500 rounded-r-md"
                ></span>

                <!-- Item Icon -->
                <component 
                  :is="item.icon" 
                  :class="[
                    'shrink-0 transition-transform group-hover:scale-110',
                    isCollapsed ? 'w-5 h-5' : 'w-4 h-4',
                    isItemActive(item) ? 'text-emerald-400' : 'text-slate-400 group-hover:text-slate-200'
                  ]" 
                />

                <!-- Item Label -->
                <span v-if="!isCollapsed" class="truncate flex-1">
                  {{ item.label }}
                </span>

                <!-- Optional Badge -->
                <span 
                  v-if="!isCollapsed && item.badge !== undefined" 
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700"
                >
                  {{ item.badge }}
                </span>
              </button>
            </div>
          </div>
        </template>

      </div>

      <!-- 3. Sidenav User Profile & Sign Out Footer -->
      <div class="p-3 border-t border-[#16273f] bg-[#06101c] shrink-0">
        <div 
          :class="[
            'flex items-center rounded-2xl bg-slate-900/90 border border-slate-800 transition shadow-2xs',
            isCollapsed ? 'p-2 justify-center flex-col space-y-2' : 'p-3 justify-between'
          ]"
        >
          <!-- User Avatar & Info -->
          <div class="flex items-center space-x-2.5 overflow-hidden">
            <div class="relative shrink-0">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-700 to-indigo-900 text-white font-bold text-xs flex items-center justify-center shadow-md">
                {{ userInitials }}
              </div>
              <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-slate-900" title="Active"></span>
            </div>

            <div v-if="!isCollapsed" class="min-w-0">
              <div class="text-xs font-bold text-white truncate">
                {{ userDisplayName }}
              </div>
              <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider truncate">
                {{ userRoleName }}
              </div>
            </div>
          </div>

          <!-- Sign Out Button -->
          <button 
            @click="$emit('trigger-logout')" 
            type="button"
            :title="isCollapsed ? 'Sign Out' : ''"
            class="p-2 rounded-xl text-slate-400 hover:text-rose-400 hover:bg-rose-950/50 border border-transparent hover:border-rose-800/60 transition cursor-pointer"
          >
            <LogOut class="w-4 h-4 shrink-0" />
          </button>
        </div>
      </div>

    </aside>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { 
  Menu, X, LogOut,
  ShieldCheck, Activity, Users, Lock, BookOpen, Layers, Clock, Calendar,
  FileCheck, ListOrdered, FolderArchive, Receipt, CreditCard, Percent,
  Table, FileSpreadsheet, Award, Compass,
  FileText, ChevronDown, GraduationCap, LayoutDashboard
} from 'lucide-vue-next';

const props = defineProps({
  currentUser: {
    type: Object,
    default: null
  },
  isCollapsed: {
    type: Boolean,
    default: false
  },
  isOpen: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'toggle-collapse', 'trigger-logout']);

const route = useRoute();
const router = useRouter();

// Check if current user is Super Admin
const isAdmin = computed(() => {
  return props.currentUser?.role_slug === 'admin';
});

// Single active open group for exclusive accordion expansion
const activeOpenGroupId = ref('coordinator');

// Admin Core Overview Navigation Items
const adminCoreNavItems = [
  { id: 'stats', label: 'Dashboard & Security', icon: LayoutDashboard, path: '/admin', tab: 'stats' },
  { id: 'users', label: 'Staff & User Accounts', icon: Users, path: '/admin', tab: 'users' },
  { id: 'school_years', label: 'School Year Lock', icon: Lock, path: '/admin', tab: 'school_years' }
];

const isAdminCoreItemActive = (item) => {
  if (route.path !== item.path) return false;
  const currentTab = route.query.tab || 'stats';
  return currentTab === item.tab;
};

const handleAdminCoreClick = (item) => {
  emit('close');
  router.push({
    path: item.path,
    query: { tab: item.tab }
  });
  window.dispatchEvent(new CustomEvent('portal-tab-selected', { 
    detail: { tab: item.tab, path: item.path } 
  }));
};

// Admin Structured Portal Groups with Child Tabs
const adminPortalGroups = [
  {
    id: 'coordinator',
    label: 'Curriculum & Coordinator',
    basePath: '/coordinator',
    icon: BookOpen,
    children: [
      { id: 'curriculum', label: 'DepEd Curriculum', icon: BookOpen, tab: 'curriculum' },
      { id: 'strands', label: 'SHS Strands', icon: Layers, tab: 'strands' },
      { id: 'sections', label: 'Class Sections', icon: Users, tab: 'sections' },
      { id: 'schedules', label: 'Timetable Scheduler', icon: Clock, tab: 'schedules' },
      { id: 'events', label: 'Events Calendar', icon: Calendar, tab: 'events' }
    ]
  },
  {
    id: 'registrar',
    label: 'Registrar & Admissions',
    basePath: '/registrar',
    icon: FileCheck,
    children: [
      { id: 'applications', label: 'Admission Review', icon: FileCheck, tab: 'applications' },
      { id: 'queue', label: 'Enrollment Queue', icon: ListOrdered, tab: 'queue' }
    ]
  },
  {
    id: 'treasury',
    label: 'Treasury & Cashier',
    basePath: '/treasury',
    icon: Receipt,
    children: [
      { id: 'assessments', label: 'Billing Assessments & OR', icon: Receipt, tab: 'assessments' },
      { id: 'online_payments', label: 'Online Payment Queue', icon: CreditCard, tab: 'online_payments' },
      { id: 'fees', label: 'Fee Structures & Vouchers', icon: Percent, tab: 'fees' }
    ]
  },
  {
    id: 'records',
    label: 'Records & DepEd Forms',
    basePath: '/records',
    icon: FolderArchive,
    children: [
      { id: 'records', label: 'Permanent Records & SF10', icon: FolderArchive, tab: 'records' },
      { id: 'drs', label: 'Document Requests (DRS)', icon: FileText, tab: 'drs' },
      { id: 'school_forms', label: 'DepEd Forms (SF1 & SF5)', icon: Table, tab: 'school_forms' },
      { id: 'honors', label: 'Honors & Ranking Engine', icon: Award, tab: 'honors' },
      { id: 'transferees', label: 'Transferee F137 Tracker', icon: FileSpreadsheet, tab: 'transferees' }
    ]
  }
];

// Auto-expand group matching active path
watch(() => route.path, (newPath) => {
  if (isAdmin.value) {
    const matchedGroup = adminPortalGroups.find(g => newPath.startsWith(g.basePath));
    if (matchedGroup) {
      activeOpenGroupId.value = matchedGroup.id;
    }
  }
}, { immediate: true });

// Toggle Accordion Group
const toggleGroup = (group) => {
  if (props.isCollapsed) {
    emit('toggle-collapse');
    activeOpenGroupId.value = group.id;
    if (!route.path.startsWith(group.basePath)) {
      router.push(group.basePath);
    }
    return;
  }
  
  if (activeOpenGroupId.value === group.id) {
    activeOpenGroupId.value = '';
  } else {
    activeOpenGroupId.value = group.id;
    if (!route.path.startsWith(group.basePath)) {
      router.push({
        path: group.basePath,
        query: group.children[0]?.tab ? { tab: group.children[0].tab } : {}
      });
    }
  }
};

const isGroupActive = (group) => {
  return route.path.startsWith(group.basePath);
};

const isChildActive = (basePath, child) => {
  if (route.path !== basePath) return false;
  const currentTab = route.query.tab;
  if (!currentTab) {
    return child.id === adminPortalGroups.find(g => g.basePath === basePath)?.children[0]?.id;
  }
  return currentTab === child.tab;
};

// 1-Click Child Nav Click Handler
const handleChildNavClick = (basePath, child) => {
  emit('close');
  router.push({
    path: basePath,
    query: child.tab ? { tab: child.tab } : {}
  });

  window.dispatchEvent(new CustomEvent('portal-tab-selected', { 
    detail: { tab: child.tab, path: basePath } 
  }));
};

// Computed User Info
const userDisplayName = computed(() => {
  if (!props.currentUser) return 'Authorized User';
  if (props.currentUser.first_name) {
    return `${props.currentUser.first_name} ${props.currentUser.last_name || ''}`.trim();
  }
  return props.currentUser.username || 'User';
});

const userRoleName = computed(() => {
  if (!props.currentUser) return '';
  return props.currentUser.role_name || props.currentUser.role_slug || '';
});

const userInitials = computed(() => {
  if (!props.currentUser) return 'JJ';
  const name = props.currentUser.first_name || props.currentUser.username || 'User';
  return name.slice(0, 2).toUpperCase();
});

const currentRoleTitle = computed(() => {
  const slug = props.currentUser?.role_slug;
  switch (slug) {
    case 'admin': return 'Super Admin Portal';
    case 'coordinator': return 'Scheduler Portal';
    case 'registrar': return 'Registrar Portal';
    case 'treasury': return 'Treasury & Cashier';
    case 'records': return 'Records Custodian';
    case 'teacher': return 'Faculty Portal';
    case 'student': return 'Student Portal';
    case 'applicant': return 'Admission Portal';
    default: return 'Institutional Portal';
  }
});

// Non-Admin Role-Specific Categorized Navigation Sections
const categorizedRoleSections = computed(() => {
  const slug = props.currentUser?.role_slug;
  const currentPath = route.path;

  // 1. Coordinator Navigation (Matching the Reference layout structure: OVERVIEW & SCHEDULER)
  if (currentPath === '/coordinator' || slug === 'coordinator') {
    return [
      {
        title: 'OVERVIEW',
        items: [
          { id: 'curriculum', label: 'Curriculum Management', icon: BookOpen, path: '/coordinator', tab: 'curriculum' }
        ]
      },
      {
        title: 'SCHEDULER & OFFERINGS',
        items: [
          { id: 'strands', label: 'SHS Strands', icon: Layers, path: '/coordinator', tab: 'strands' },
          { id: 'sections', label: 'Sections', icon: Users, path: '/coordinator', tab: 'sections' },
          { id: 'schedules', label: 'Class Scheduling', icon: Clock, path: '/coordinator', tab: 'schedules' },
          { id: 'events', label: 'Events Calendar', icon: Calendar, path: '/coordinator', tab: 'events' }
        ]
      }
    ];
  }

  // 2. Teacher / Faculty Navigation
  if (currentPath === '/teacher' || slug === 'teacher') {
    return [
      {
        title: 'OVERVIEW & SCHEDULE',
        items: [
          { id: 'schedule', label: 'Weekly Schedule & Classes', icon: Clock, path: '/teacher', tab: 'schedule' },
          { id: 'lms', label: 'Classroom & LMS Modules', icon: BookOpen, path: '/teacher', tab: 'lms' }
        ]
      },
      {
        title: 'GRADING & MASTERLISTS',
        items: [
          { id: 'grading', label: 'Electronic Class Record', icon: FileSpreadsheet, path: '/teacher', tab: 'grading' },
          { id: 'roster', label: 'Class Masterlists', icon: Users, path: '/teacher', tab: 'roster' },
          { id: 'advisory', label: 'Advisory Section (SF9)', icon: Award, path: '/teacher', tab: 'advisory' },
          { id: 'attendance', label: 'Attendance Sheet (SF2)', icon: Calendar, path: '/teacher', tab: 'attendance' }
        ]
      }
    ];
  }

  // 3. Registrar Navigation
  if (currentPath === '/registrar' || slug === 'registrar') {
    return [
      {
        title: 'ADMISSION & ENROLLMENT',
        items: [
          { id: 'applications', label: 'Admission Review', icon: FileCheck, path: '/registrar', tab: 'applications' },
          { id: 'queue', label: 'Enrollment Queue', icon: ListOrdered, path: '/registrar', tab: 'queue' }
        ]
      }
    ];
  }

  // 4. Treasury Navigation
  if (currentPath === '/treasury' || slug === 'treasury') {
    return [
      {
        title: 'CASHIER & BILLING',
        items: [
          { id: 'assessments', label: 'Billing Assessments & OR', icon: Receipt, path: '/treasury', tab: 'assessments' },
          { id: 'online_payments', label: 'Online Payment Queue', icon: CreditCard, path: '/treasury', tab: 'online_payments' },
          { id: 'fees', label: 'Fee Structures & Vouchers', icon: Percent, path: '/treasury', tab: 'fees' }
        ]
      }
    ];
  }

  // 5. Records Custodian Navigation
  if (currentPath === '/records' || slug === 'records') {
    return [
      {
        title: 'PERMANENT RECORDS & DRS',
        items: [
          { id: 'records', label: 'Permanent Records & SF10', icon: FolderArchive, path: '/records', tab: 'records' },
          { id: 'drs', label: 'Document Requests (DRS)', icon: FileText, path: '/records', tab: 'drs' },
          { id: 'school_forms', label: 'DepEd Forms (SF1 & SF5)', icon: Table, path: '/records', tab: 'school_forms' },
          { id: 'honors', label: 'Honors & Ranking Engine', icon: Award, path: '/records', tab: 'honors' },
          { id: 'transferees', label: 'Transferee F137 Tracker', icon: FileSpreadsheet, path: '/records', tab: 'transferees' }
        ]
      }
    ];
  }

  // 6. Student Navigation
  if (slug === 'student' || currentPath === '/student') {
    return [
      {
        title: 'ACADEMICS & SCHEDULE',
        items: [
          { id: 'schedule', label: 'My Timetable & Schedule', icon: BookOpen, path: '/student', tab: 'schedule' },
          { id: 'lms', label: 'Classroom & Learning Modules', icon: Layers, path: '/student', tab: 'lms' }
        ]
      },
      {
        title: 'STUDENT SERVICES',
        items: [
          { id: 'account', label: 'Statement of Account (SOA)', icon: CreditCard, path: '/student', tab: 'account' },
          { id: 'events', label: 'School Events Calendar', icon: Calendar, path: '/student', tab: 'events' },
          { id: 'records', label: 'Academic Permanent Records', icon: FileText, path: '/student', tab: 'records' }
        ]
      }
    ];
  }

  // 7. Applicant Admission Services
  if (slug === 'applicant' || currentPath === '/admission') {
    return [
      {
        title: 'ADMISSION SERVICES',
        items: [
          { id: 'wizard', label: 'Admission Application', icon: FileText, path: '/admission', tab: 'wizard' },
          { id: 'checklist', label: 'Document Requirements', icon: FileCheck, path: '/admission', tab: 'checklist' },
          { id: 'process', label: 'Enrollment Guide & FAQs', icon: Compass, path: '/admission', tab: 'process' }
        ]
      }
    ];
  }

  return [];
});

// Non-admin Active Item Checker
const isItemActive = (item) => {
  if (route.path !== item.path) return false;
  if (!item.tab) return true;
  const currentTab = route.query.tab;
  if (!currentTab) {
    const firstSection = categorizedRoleSections.value[0];
    return firstSection?.items[0]?.id === item.id;
  }
  return currentTab === item.tab;
};

// Non-admin Item Click Handler
const handleNavClick = (item) => {
  emit('close');
  if (route.path === item.path && route.query.tab === item.tab) return;
  
  router.push({
    path: item.path,
    query: item.tab ? { tab: item.tab } : {}
  });

  window.dispatchEvent(new CustomEvent('portal-tab-selected', { 
    detail: { tab: item.tab, path: item.path } 
  }));
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #16273f;
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #253e63;
}
</style>
