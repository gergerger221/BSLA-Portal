<template>
  <div class="min-h-screen bg-slate-50 text-slate-800 font-sans antialiased">
    
    <!-- ========================================================================= -->
    <!-- CASE A: AUTHENTICATED PORTAL WORKSPACE (Sidenav + Top Header + Workspace) -->
    <!-- ========================================================================= -->
    <template v-if="isPortalRoute && currentUser">
      <!-- Universal Sidenav Component -->
      <PortalSidebar 
        :current-user="currentUser"
        :is-collapsed="isSidebarCollapsed"
        :is-open="isMobileSidebarOpen"
        @close="isMobileSidebarOpen = false"
        @toggle-collapse="isSidebarCollapsed = !isSidebarCollapsed"
        @trigger-logout="showLogoutConfirm = true"
      />

      <!-- Main Portal Frame (Dynamic Left Padding based on Sidebar state) -->
      <div 
        :class="[
          'min-h-screen flex flex-col transition-all duration-300 ease-in-out',
          isSidebarCollapsed ? 'lg:pl-[78px]' : 'lg:pl-[270px]'
        ]"
      >
        <!-- Sticky Portal Top Bar -->
        <header class="no-print sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-xs h-16 flex items-center justify-between px-3 sm:px-6 lg:px-8">
          
          <!-- Left: Mobile Drawer Trigger & Dynamic Breadcrumbs -->
          <div class="flex items-center space-x-2 sm:space-x-4 min-w-0">
            <!-- Mobile Hamburger Menu Button -->
            <button 
              @click="isMobileSidebarOpen = true" 
              type="button" 
              class="lg:hidden p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition cursor-pointer shrink-0"
              title="Open Navigation Menu"
            >
              <Menu class="w-5 h-5" />
            </button>

            <!-- Dynamic Breadcrumbs -->
            <div class="flex items-center space-x-1.5 sm:space-x-2 text-xs font-bold text-slate-600 min-w-0">
              <span class="hidden sm:inline-block px-2.5 py-0.5 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 font-extrabold uppercase text-[10px] shrink-0">
                {{ breadcrumbPortalName }}
              </span>
              <ChevronRight class="hidden sm:inline-block w-3.5 h-3.5 text-slate-400 shrink-0" />
              <span class="text-slate-900 font-extrabold truncate text-xs sm:text-sm max-w-[150px] sm:max-w-xs md:max-w-none">
                {{ breadcrumbActiveTabName }}
              </span>
            </div>
          </div>

          <!-- Right: Live Academic Year Status & Quick User Actions -->
          <div class="flex items-center space-x-1.5 sm:space-x-3 shrink-0">
            
            <!-- Live Academic Year Badge -->
            <div class="hidden sm:inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-bold shadow-2xs">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              <span>S.Y. 2026-2027 Active</span>
            </div>

            <!-- User Quick Capsule -->
            <div class="flex items-center space-x-2 bg-slate-100/80 px-2 sm:px-2.5 py-1 rounded-xl border border-slate-200">
              <div class="w-7 h-7 rounded-lg bg-[#0c2340] text-blue-300 font-bold text-xs flex items-center justify-center shrink-0">
                {{ userInitials }}
              </div>
              <div class="hidden md:block text-left text-xs leading-tight">
                <div class="font-bold text-slate-900 truncate max-w-[120px]">{{ currentUser.first_name || currentUser.username }}</div>
                <div class="text-[9px] font-extrabold text-blue-800 uppercase">{{ currentUser.role_slug }}</div>
              </div>
            </div>

            <!-- Quick Sign Out Trigger -->
            <button 
              @click="showLogoutConfirm = true" 
              type="button" 
              class="p-2 rounded-xl text-slate-500 hover:text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition cursor-pointer" 
              title="Sign Out"
            >
              <LogOut class="w-4 h-4" />
            </button>
          </div>

        </header>

        <!-- Main Portal Content Workspace -->
        <main class="flex-1 pb-12">
          <router-view />
        </main>

        <!-- Minimal Portal Footer -->
        <footer class="no-print py-4 px-4 sm:px-6 border-t border-slate-200 bg-white text-center text-xs text-slate-400 flex flex-col sm:flex-row items-center justify-between gap-2">
          <span>© {{ currentYear }} Biringan Science & Leadership Academy • DepEd ID: 405621</span>
          <span class="text-[11px] text-slate-400">"Innovating for the Nation" • K-12 & MATATAG Aligned</span>
        </footer>
      </div>
    </template>

    <!-- ========================================================================= -->
    <!-- CASE B: PUBLIC GUEST WEBPAGE (Public Header + Full Landing + Full Footer)  -->
    <!-- ========================================================================= -->
    <template v-else>
      <!-- 1. TOP INSTITUTIONAL BAR (School Hotlines, DepEd ID & Portals) -->
      <div v-if="!isStandaloneAuthRoute" class="no-print bg-[#061322] text-slate-300 border-b border-[#142338] text-xs py-2 px-3 sm:px-8 lg:px-12 xl:px-16">
        <div class="max-w-[1680px] 2xl:max-w-[1780px] w-full mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
          
          <div class="flex items-center space-x-2 sm:space-x-3 text-[11px] flex-wrap justify-center sm:justify-start">
            <span class="inline-flex items-center space-x-1.5 text-blue-300 font-bold">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
              <span>DepEd School ID: 405621</span>
            </span>
            <span class="text-slate-600 hidden md:inline">•</span>
            <span class="text-slate-300 hidden md:inline">PEAC-ESC & DepEd Voucher Accredited</span>
            <span class="text-slate-600 hidden lg:inline">•</span>
            <span class="text-slate-400 hidden lg:inline">Biringan City, Samar, Philippines</span>
          </div>

          <div class="flex items-center space-x-3 sm:space-x-4 text-[11px]">
            <div class="hidden sm:flex items-center space-x-1.5 text-slate-300">
              <Phone class="w-3.5 h-3.5 text-blue-300" />
              <span>Admissions: <strong>(055) 888-7766</strong> / <strong>0917-111-0001</strong></span>
            </div>
            <span class="text-slate-700 hidden sm:inline">|</span>
            <router-link to="/smtp-simulator" class="text-blue-300 hover:text-white transition flex items-center space-x-1 font-semibold">
              <Mail class="w-3.5 h-3.5 text-blue-300" />
              <span>SMTP Simulator</span>
            </router-link>
          </div>

        </div>
      </div>

      <!-- 2. MAIN PUBLIC SCHOOL HEADER -->
      <header v-if="!isStandaloneAuthRoute" class="no-print bg-white text-slate-900 border-b border-slate-200 shadow-xs sticky top-0 z-50">
        <div class="max-w-[1680px] 2xl:max-w-[1780px] w-full mx-auto px-3 sm:px-8 lg:px-12 xl:px-16 h-16 sm:h-20 flex items-center justify-between gap-3 sm:gap-4">
          
          <!-- School Crest & Branding -->
          <router-link to="/" class="flex items-center space-x-2.5 sm:space-x-3.5 group cursor-pointer shrink min-w-0">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-[#091524] p-1 border border-blue-500/40 flex items-center justify-center shadow-md shadow-blue-950/15 group-hover:scale-105 transition-all shrink-0">
              <div class="w-full h-full rounded-lg sm:rounded-xl flex flex-col items-center justify-center text-center">
                <GraduationCap class="w-5 h-5 sm:w-6 sm:h-6 text-blue-300" />
                <span class="text-[6px] sm:text-[7px] font-bold text-blue-200 uppercase tracking-tighter leading-none mt-0.5">EST. 2012</span>
              </div>
            </div>

            <div class="space-y-0.5 min-w-0">
              <div class="flex items-center space-x-1.5 sm:space-x-2">
                <span class="font-black text-sm sm:text-lg md:text-xl tracking-tight text-[#0c2340] font-serif group-hover:text-blue-900 transition truncate">
                  BIRINGAN SCIENCE & LEADERSHIP ACADEMY
                </span>
                <span class="hidden sm:inline-block px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-blue-100 text-blue-900 border border-blue-200 font-mono shrink-0">
                  BSLA
                </span>
              </div>
              <span class="hidden sm:block text-[11px] text-slate-500 font-semibold uppercase tracking-wider truncate">
                Junior & Senior High School • "Innovating for the Nation"
              </span>
            </div>
          </router-link>

          <!-- Desktop Public Nav Links (Active Indicator & Scroll Spy) -->
          <nav class="hidden lg:flex items-center space-x-1 shrink-0">
            <button 
              type="button" 
              @click="navigateToSection('hero')"
              :class="activePublicSection === 'hero' && route.name === 'Home' 
                ? 'text-blue-950 font-bold bg-blue-50 border-blue-200/90 shadow-2xs' 
                : 'text-slate-600 hover:text-blue-950 hover:bg-slate-50 border-transparent font-medium'"
              class="px-3.5 py-1.5 rounded-xl text-xs border transition duration-150 cursor-pointer"
            >
              Home
            </button>

            <button 
              type="button" 
              @click="navigateToSection('programs')"
              :class="activePublicSection === 'programs' && route.name === 'Home' 
                ? 'text-blue-950 font-bold bg-blue-50 border-blue-200/90 shadow-2xs' 
                : 'text-slate-600 hover:text-blue-950 hover:bg-slate-50 border-transparent font-medium'"
              class="px-3.5 py-1.5 rounded-xl text-xs border transition duration-150 cursor-pointer"
            >
              Programs
            </button>

            <button 
              type="button" 
              @click="navigateToSection('pathway')"
              :class="activePublicSection === 'pathway' && route.name === 'Home' 
                ? 'text-blue-950 font-bold bg-blue-50 border-blue-200/90 shadow-2xs' 
                : 'text-slate-600 hover:text-blue-950 hover:bg-slate-50 border-transparent font-medium'"
              class="px-3.5 py-1.5 rounded-xl text-xs border transition duration-150 cursor-pointer"
            >
              Admission Steps
            </button>

            <button 
              type="button" 
              @click="navigateToSection('vouchers')"
              :class="activePublicSection === 'vouchers' && route.name === 'Home' 
                ? 'text-blue-950 font-bold bg-blue-50 border-blue-200/90 shadow-2xs' 
                : 'text-slate-600 hover:text-blue-950 hover:bg-slate-50 border-transparent font-medium'"
              class="px-3.5 py-1.5 rounded-xl text-xs border transition duration-150 cursor-pointer"
            >
              DepEd Vouchers
            </button>

            <button 
              type="button" 
              @click="navigateToSection('faqs')"
              :class="activePublicSection === 'faqs' && route.name === 'Home' 
                ? 'text-blue-950 font-bold bg-blue-50 border-blue-200/90 shadow-2xs' 
                : 'text-slate-600 hover:text-blue-950 hover:bg-slate-50 border-transparent font-medium'"
              class="px-3.5 py-1.5 rounded-xl text-xs border transition duration-150 cursor-pointer"
            >
              FAQs
            </button>
          </nav>

          <!-- Action Buttons Area -->
          <div class="flex items-center space-x-2 sm:space-x-3 shrink-0">
            <router-link 
              to="/admission-login" 
              class="hidden sm:inline-flex px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-300 transition items-center space-x-1.5 cursor-pointer shadow-2xs"
            >
              <FileText class="w-3.5 h-3.5 text-blue-900" />
              <span>Admission Portal</span>
            </router-link>

            <router-link 
              to="/login" 
              class="px-3.5 py-2 rounded-xl text-xs font-semibold text-blue-950 bg-blue-50/80 hover:bg-blue-100/80 border border-blue-200 hover:border-blue-300 transition flex items-center space-x-1.5 cursor-pointer shadow-2xs"
            >
              <UserCheck class="w-3.5 h-3.5 text-blue-900" />
              <span>Student Portal</span>
            </router-link>

            <router-link 
              to="/register" 
              class="hidden md:inline-flex px-4 py-2 rounded-xl text-xs font-semibold bg-blue-900 hover:bg-blue-800 text-white shadow-xs hover:scale-[1.01] active:scale-[0.99] transition items-center space-x-1.5 border border-blue-800 cursor-pointer"
            >
              <span>Apply Now</span>
              <ArrowRight class="w-3.5 h-3.5 text-white" />
            </router-link>

            <!-- Mobile Public Hamburger Button -->
            <button 
              type="button" 
              @click="isPublicMobileMenuOpen = !isPublicMobileMenuOpen"
              class="lg:hidden p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-blue-900 transition cursor-pointer"
              title="Open Navigation Menu"
            >
              <Menu v-if="!isPublicMobileMenuOpen" class="w-5 h-5" />
              <X v-else class="w-5 h-5" />
            </button>
          </div>

        </div>

        <!-- Mobile Public Dropdown Drawer -->
        <div 
          v-if="isPublicMobileMenuOpen" 
          class="lg:hidden border-t border-slate-200 bg-white px-4 py-5 space-y-3 animate-in slide-in-from-top-3 duration-200 shadow-xl"
        >
          <div class="grid grid-cols-2 gap-2 text-xs">
            <router-link 
              to="/" 
              @click="isPublicMobileMenuOpen = false; scrollToTop()"
              class="p-2.5 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 text-slate-800 font-semibold flex items-center space-x-2"
            >
              <Sparkles class="w-3.5 h-3.5 text-blue-900" />
              <span>Home</span>
            </router-link>

            <button 
              type="button" 
              @click="isPublicMobileMenuOpen = false; navigateToSection('programs')"
              class="p-2.5 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 text-slate-800 font-semibold flex items-center space-x-2 text-left cursor-pointer"
            >
              <BookOpen class="w-3.5 h-3.5 text-blue-900" />
              <span>Programs</span>
            </button>

            <button 
              type="button" 
              @click="isPublicMobileMenuOpen = false; navigateToSection('pathway')"
              class="p-2.5 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 text-slate-800 font-semibold flex items-center space-x-2 text-left cursor-pointer"
            >
              <Layers class="w-3.5 h-3.5 text-blue-900" />
              <span>Admission Steps</span>
            </button>

            <button 
              type="button" 
              @click="isPublicMobileMenuOpen = false; navigateToSection('vouchers')"
              class="p-2.5 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 text-slate-800 font-semibold flex items-center space-x-2 text-left cursor-pointer"
            >
              <Award class="w-3.5 h-3.5 text-blue-900" />
              <span>DepEd Vouchers</span>
            </button>

            <button 
              type="button" 
              @click="isPublicMobileMenuOpen = false; navigateToSection('faqs')"
              class="p-2.5 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 text-slate-800 font-semibold flex items-center space-x-2 text-left cursor-pointer col-span-2"
            >
              <HelpCircle class="w-3.5 h-3.5 text-blue-900" />
              <span>Frequently Asked Questions</span>
            </button>
          </div>

          <div class="pt-3 border-t border-slate-100 flex flex-col gap-2 text-xs">
            <router-link 
              to="/register" 
              @click="isPublicMobileMenuOpen = false"
              class="w-full py-3 rounded-xl bg-blue-900 text-white font-semibold text-center flex items-center justify-center space-x-2 shadow-xs"
            >
              <UserPlus class="w-4 h-4 text-white" />
              <span>Apply for S.Y. 2026-2027 Admission</span>
            </router-link>

            <div class="grid grid-cols-2 gap-2">
              <router-link 
                to="/admission-login" 
                @click="isPublicMobileMenuOpen = false"
                class="py-2.5 rounded-xl bg-slate-50 text-slate-800 font-semibold text-center border border-slate-200"
              >
                Admission Portal
              </router-link>
              <router-link 
                to="/login" 
                @click="isPublicMobileMenuOpen = false"
                class="py-2.5 rounded-xl bg-blue-50 text-blue-950 font-semibold text-center border border-blue-200"
              >
                Student Portal
              </router-link>
            </div>
          </div>
        </div>
      </header>

      <!-- Public Router Outlet -->
      <main class="flex-1">
        <router-view />
      </main>

      <!-- Full Institutional Footer -->
      <footer v-if="!isStandaloneAuthRoute" class="no-print bg-[#08182b] text-slate-300 border-t border-slate-800 py-12 text-xs">
        <div class="max-w-[1680px] 2xl:max-w-[1780px] w-full mx-auto px-4 sm:px-8 lg:px-12 xl:px-16">
          
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 pb-10 border-b border-slate-800">
            
            <div class="space-y-3.5">
              <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-[#0c2340] border border-blue-500/40 flex items-center justify-center text-blue-300 shrink-0">
                  <GraduationCap class="w-6 h-6" />
                </div>
                <div>
                  <h4 class="text-sm font-black text-white font-serif tracking-tight">BIRINGAN SCIENCE & LEADERSHIP ACADEMY</h4>
                  <p class="text-[10px] text-blue-300 font-bold uppercase">"Innovating for the Nation" (BSLA)</p>
                </div>
              </div>
              <p class="text-slate-400 text-xs leading-relaxed">
                A premier preparatory academic institution offering Philippine Department of Education (DepEd) K-12 Junior and Senior High School Academic & TVL tracks.
              </p>
              <div class="text-[11px] text-blue-200/90 font-medium">
                DepEd School ID: 405621<br />
                Accreditation: PEAC-ESC & DepEd Voucher Program
              </div>
            </div>

            <div class="space-y-2.5">
              <h5 class="text-xs font-black text-white uppercase tracking-wider font-mono">Academic Offerings (JHS & SHS)</h5>
              <ul class="space-y-1.5 text-slate-400 text-[11px]">
                <li>Junior High School (Grades 7–10)</li>
                <li>STEM (Science, Tech, Engineering & Math)</li>
                <li>ABM (Accountancy, Business & Management)</li>
                <li>HUMSS (Humanities & Social Sciences)</li>
                <li>GAS (General Academic Strand)</li>
                <li>TVL-ICT (Computer Systems & Programming)</li>
                <li>TVL-HE (Cookery, Pastry & Tourism)</li>
              </ul>
            </div>

            <div class="space-y-2.5">
              <h5 class="text-xs font-black text-white uppercase tracking-wider font-mono">Admissions & Portals</h5>
              <ul class="space-y-1.5 text-slate-400 text-[11px]">
                <li><router-link to="/register" class="hover:text-blue-300 transition">Online Admission Application</router-link></li>
                <li><router-link to="/admission-login" class="hover:text-blue-300 transition">Admission Applicant Portal</router-link></li>
                <li><router-link to="/login" class="hover:text-blue-300 transition">Enrolled Student Portal Access</router-link></li>
                <li><a href="#vouchers" @click="navigateToSection('vouchers')" class="hover:text-blue-300 transition">DepEd SHS Voucher Subsidy (100%)</a></li>
                <li><a href="#programs" @click="navigateToSection('programs')" class="hover:text-blue-300 transition">Junior & Senior High Programs</a></li>
              </ul>
            </div>

            <div class="space-y-2.5">
              <h5 class="text-xs font-black text-white uppercase tracking-wider font-mono">Campus Contact</h5>
              <div class="space-y-2 text-slate-400 text-[11px]">
                <div class="flex items-start space-x-2">
                  <MapPin class="w-4 h-4 text-blue-300 shrink-0 mt-0.5" />
                  <span>Academic Boulevard, Biringan City, Samar, Eastern Visayas 6700</span>
                </div>
                <div class="flex items-start space-x-2">
                  <Phone class="w-4 h-4 text-blue-300 shrink-0 mt-0.5" />
                  <span>Tel: (055) 888-7766 • Mobile: 0917-111-0001</span>
                </div>
                <div class="flex items-start space-x-2">
                  <Mail class="w-4 h-4 text-blue-300 shrink-0 mt-0.5" />
                  <span>admissions@bsla.edu.ph</span>
                </div>
                <div class="flex items-start space-x-2">
                  <Clock class="w-4 h-4 text-blue-300 shrink-0 mt-0.5" />
                  <span>Office: Mon–Sat, 8:00 AM – 5:00 PM</span>
                </div>
              </div>
            </div>

          </div>

          <!-- Bottom Copyright & DepEd Note -->
          <div class="pt-6 flex flex-col md:flex-row items-center justify-between gap-4 text-slate-400 text-[11px]">
            <div>
              © {{ currentYear }} Biringan Science and Leadership Academy (BSLA). All Rights Reserved.
            </div>
            <div class="flex items-center space-x-2 text-[10px] text-slate-400">
              <span>Republic of the Philippines</span>
              <span>•</span>
              <span>Department of Education (DepEd) K-12 & MATATAG Aligned</span>
            </div>
          </div>

        </div>
      </footer>
    </template>

    <!-- 4. LOGOUT CONFIRMATION MODAL -->
    <div v-if="showLogoutConfirm" class="no-print fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-200 text-slate-900">
        <div class="w-12 h-12 rounded-2xl bg-rose-100 border border-rose-200 text-rose-600 flex items-center justify-center mx-auto mb-4 shadow-sm">
          <LogOut class="w-6 h-6" />
        </div>
        <div class="text-center">
          <h3 class="text-base font-extrabold text-slate-900">Confirm Sign Out</h3>
          <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
            Are you sure you want to end your current session? Any unsaved form progress will be lost.
          </p>
        </div>

        <div class="flex items-center space-x-2.5 mt-6">
          <button 
            type="button" 
            @click="showLogoutConfirm = false" 
            class="w-1/2 py-2.5 px-4 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer"
          >
            Cancel
          </button>
          <button 
            type="button" 
            @click="confirmLogout" 
            class="w-1/2 py-2.5 px-4 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white shadow-md shadow-rose-600/20 transition flex items-center justify-center space-x-1.5 cursor-pointer"
          >
            <LogOut class="w-3.5 h-3.5" />
            <span>Sign Out</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { 
  GraduationCap, 
  LogOut, 
  ArrowRight, 
  UserCheck, 
  UserPlus, 
  ShieldCheck, 
  Phone, 
  Mail, 
  MapPin, 
  Clock, 
  Award,
  Menu,
  X,
  BookOpen,
  Layers,
  Building,
  Sparkles,
  FileText,
  PanelLeftClose,
  PanelLeftOpen,
  ChevronRight
} from 'lucide-vue-next';
import PortalSidebar from './components/navigation/PortalSidebar.vue';
import { getRoleRoutePath } from './router';
import api from './services/api';

const router = useRouter();
const route = useRoute();
const currentUser = ref(null);
const showLogoutConfirm = ref(false);
const currentYear = new Date().getFullYear();

// Sidenav Navigation States
const isSidebarCollapsed = ref(false);
const isMobileSidebarOpen = ref(false);
const isPublicMobileMenuOpen = ref(false);

const isPortalRoute = computed(() => {
  if (!currentUser.value) return false;
  const portalRoutes = [
    'AdmissionProcedure', 
    'RegistrarDashboard', 
    'TreasuryDashboard', 
    'CoordinatorDashboard', 
    'RecordsDashboard', 
    'StudentDashboard', 
    'AdminDashboard',
    'TeacherDashboard'
  ];
  return portalRoutes.includes(route.name) || ['/admission', '/registrar', '/treasury', '/coordinator', '/records', '/student', '/admin', '/teacher'].some(p => route.path.startsWith(p));
});

const isStandaloneAuthRoute = computed(() => {
  const authRoutes = ['Login', 'Register', 'StaffLogin', 'AdmissionLogin'];
  const authPaths = ['/login', '/register', '/staff-login', '/admission-login', '/applicant-login'];
  return authRoutes.includes(route.name) || authPaths.includes(route.path);
});

const breadcrumbPortalName = computed(() => {
  const path = route.path;
  if (path.startsWith('/admin')) return 'Super Admin Control';
  if (path.startsWith('/coordinator')) return 'Academic Coordinator';
  if (path.startsWith('/registrar')) return 'Registrar Admission & Queue';
  if (path.startsWith('/treasury')) return 'Treasury & Cashier';
  if (path.startsWith('/records')) return 'Records & DepEd Archives';
  if (path.startsWith('/student')) return 'Student Portal';
  if (path.startsWith('/teacher')) return 'Teacher & Faculty';
  if (path.startsWith('/admission')) return 'Admission Procedure';
  return 'School Portal';
});

const breadcrumbActiveTabName = computed(() => {
  const path = route.path;
  const tab = route.query.tab;

  if (path.startsWith('/admin')) {
    if (tab === 'users') return 'Staff & User Management';
    if (tab === 'school_years') return 'School Year Lock & Control';
    return 'Overview & System Logs';
  }
  if (path.startsWith('/coordinator')) {
    if (tab === 'strands') return 'SHS Academic Strands';
    if (tab === 'sections') return 'Class Sections & Matrix';
    if (tab === 'schedules') return 'Master Class Timetables';
    if (tab === 'events') return 'School Events Calendar';
    return 'DepEd Curriculum & Learning Areas';
  }
  if (path.startsWith('/registrar')) {
    if (tab === 'queue') return 'Enrollment Seating Queue';
    return 'Admission Applications Evaluation';
  }
  if (path.startsWith('/treasury')) {
    if (tab === 'online_payments' || tab === 'online-payments') return 'Online Payment Verifications';
    if (tab === 'fees' || tab === 'fee-structures') return 'Fee Structures & DepEd Vouchers';
    return 'Billing Assessments & Official Receipts';
  }
  if (path.startsWith('/records')) {
    if (tab === 'drs') return 'Document Request System (DRS)';
    if (tab === 'school_forms') return 'DepEd School Forms (SF1 & SF5)';
    if (tab === 'honors') return 'Academic Honors & GWA Engine';
    if (tab === 'transferees') return 'Transferee Compliance Tracker';
    return 'Student Permanent Records (SF10 / Form 137)';
  }
  if (path.startsWith('/student')) {
    if (tab === 'account') return 'Statement of Account & Receipts';
    if (tab === 'events') return 'School Events Calendar';
    if (tab === 'records') return 'Academic Records & Grades';
    return 'Class Schedule & Subject Timetable';
  }
  if (path.startsWith('/teacher')) {
    if (tab === 'grading') return 'Electronic Class Record (E-Class Record)';
    if (tab === 'roster') return 'Class Masterlists & Student Directory';
    if (tab === 'advisory') return 'Advisory Section (SF9 Core Values)';
    if (tab === 'attendance') return 'Attendance Sheet (SF2 Daily Log)';
    return 'Weekly Schedule & Teaching Load';
  }
  if (path.startsWith('/admission')) {
    if (tab === 'demographics') return 'Step 1: Personal & PSA Demographics';
    if (tab === 'strand') return 'Step 2: Grade Level & Strand';
    if (tab === 'documents') return 'Step 3: Document Uploads';
    if (tab === 'payment') return 'Step 4: Tuition Downpayment & COR';
    return 'Admission Status & Procedure';
  }
  return route.name || 'Dashboard';
});

const dashboardRoute = computed(() => {
  return currentUser.value ? getRoleRoutePath(currentUser.value.role_slug) : '/';
});

const userInitials = computed(() => {
  if (!currentUser.value) return 'U';
  const f = currentUser.value.first_name?.[0] || currentUser.value.username?.[0] || 'U';
  const l = currentUser.value.last_name?.[0] || '';
  return (f + l).toUpperCase();
});

const loadCurrentUser = () => {
  const userJson = sessionStorage.getItem('sia_auth_user') || localStorage.getItem('sia_auth_user');
  if (userJson) {
    try {
      currentUser.value = JSON.parse(userJson);
    } catch (e) {
      currentUser.value = null;
    }
  } else {
    currentUser.value = null;
  }
};

const confirmLogout = async () => {
  const isStaff = (currentUser.value && ['admin', 'registrar', 'treasury', 'coordinator', 'records', 'teacher'].includes(currentUser.value.role_slug)) || route.path.startsWith('/teacher');
  showLogoutConfirm.value = false;
  try {
    await api.logout();
  } catch (e) {
    // Ignore error on logout
  }
  sessionStorage.removeItem('sia_auth_token');
  sessionStorage.removeItem('sia_auth_user');
  localStorage.removeItem('sia_auth_token');
  localStorage.removeItem('sia_auth_user');
  currentUser.value = null;
  window.dispatchEvent(new Event('auth-changed'));
  
  if (isStaff) {
    router.push('/staff-login');
  } else {
    router.push('/login');
  }
};

watch(() => route.fullPath, () => {
  loadCurrentUser();
  isMobileSidebarOpen.value = false; // close mobile sidebar on navigation
}, { immediate: true });

const activePublicSection = ref('hero');

const scrollToTop = () => {
  activePublicSection.value = 'hero';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const currentActiveHomeTab = ref('academics');

const handleHomeTabSwitched = (e) => {
  if (e.detail) currentActiveHomeTab.value = e.detail;
};

const navigateToSection = (sectionId) => {
  activePublicSection.value = sectionId;
  if (route.name !== 'Home') {
    router.push({ path: '/' }).then(() => {
      setTimeout(() => {
        if (sectionId === 'hero') {
          window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
          const el = document.getElementById(sectionId);
          if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }, 100);
    });
  } else {
    if (sectionId === 'hero') {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
      const el = document.getElementById(sectionId);
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }
};

const handleScrollSpy = () => {
  if (route.name !== 'Home') return;
  const sectionIds = ['faqs', 'vouchers', 'pathway', 'programs', 'hero'];
  const scrollPos = window.scrollY + 140;

  for (const id of sectionIds) {
    const el = document.getElementById(id);
    if (el) {
      const top = el.offsetTop;
      if (scrollPos >= top) {
        activePublicSection.value = id;
        return;
      }
    }
  }
  activePublicSection.value = 'hero';
};

const navigateToHomeTab = (tabId) => {
  currentActiveHomeTab.value = tabId;
  window.dispatchEvent(new CustomEvent('switch-home-tab', { detail: tabId }));

  if (route.name !== 'Home') {
    router.push({ path: '/' }).then(() => {
      setTimeout(() => {
        window.dispatchEvent(new CustomEvent('switch-home-tab', { detail: tabId }));
        const el = document.getElementById('academic-hub');
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 100);
    });
  } else {
    const el = document.getElementById('academic-hub');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
};

const handleTriggerLogout = () => {
  showLogoutConfirm.value = true;
};

onMounted(() => {
  loadCurrentUser();
  window.addEventListener('storage', loadCurrentUser);
  window.addEventListener('auth-changed', loadCurrentUser);
  window.addEventListener('switch-home-tab', handleHomeTabSwitched);
  window.addEventListener('trigger-logout', handleTriggerLogout);
  window.addEventListener('scroll', handleScrollSpy, { passive: true });
});

onUnmounted(() => {
  window.removeEventListener('storage', loadCurrentUser);
  window.removeEventListener('auth-changed', loadCurrentUser);
  window.removeEventListener('switch-home-tab', handleHomeTabSwitched);
  window.removeEventListener('trigger-logout', handleTriggerLogout);
  window.removeEventListener('scroll', handleScrollSpy);
});
</script>
