<template>
  <div class="min-h-screen flex flex-col items-center justify-center py-6 sm:py-12 px-3 sm:px-6 lg:px-8 bg-gradient-to-br from-slate-950 via-[#0a192f] to-slate-900 text-slate-100 selection:bg-blue-900 selection:text-white relative overflow-hidden">
    
    <!-- Decorative Ambient Glow Background -->
    <div class="absolute inset-0 bg-[radial-gradient(#1e3a8a_1px,transparent_1px)] [background-size:32px_32px] opacity-20 pointer-events-none"></div>
    <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full bg-blue-600/15 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -left-32 w-96 h-96 rounded-full bg-cyan-600/10 blur-3xl pointer-events-none"></div>

    <!-- Quick Back to Website Trigger (Responsive) -->
    <div class="w-full max-w-md mb-3 sm:mb-0 sm:absolute sm:top-6 sm:left-6 z-20">
      <router-link 
        to="/" 
        class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-xl bg-slate-900/80 hover:bg-slate-800 border border-slate-700 text-slate-300 hover:text-white text-xs font-semibold backdrop-blur-md transition-all shadow-md cursor-pointer"
      >
        <ArrowLeft class="w-4 h-4 text-blue-300" />
        <span>Back to Public Website</span>
      </router-link>
    </div>

    <div class="max-w-md w-full space-y-5 sm:space-y-6 p-5 sm:p-9 rounded-3xl bg-white/95 backdrop-blur-xl border border-slate-200 shadow-2xl relative z-10 text-slate-900 animate-in fade-in zoom-in-95 duration-200">
      
      <!-- Institutional Header Emblem & Titles -->
      <div class="text-center">
        <div class="w-16 h-16 rounded-2xl bg-[#091524] border border-blue-500/40 text-blue-300 flex items-center justify-center mx-auto mb-3.5 shadow-lg shadow-blue-950/30">
          <FileText class="w-8 h-8 text-blue-300" />
        </div>
        <div class="inline-flex items-center space-x-1.5 px-3 py-0.5 rounded-full bg-blue-50 border border-blue-200 text-blue-950 text-[10px] font-bold uppercase tracking-wider mb-2 shadow-2xs">
          <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
          <span>Applicant Gateway • S.Y. 2026–2027</span>
        </div>
        <h2 class="text-2xl sm:text-3xl font-black text-[#0c2340] tracking-tight font-serif">Admission Portal Login</h2>
        <p class="mt-1 text-xs text-slate-600 font-medium">For new applicants tracking or completing admission procedures</p>
      </div>

      <!-- Error Alert Banner -->
      <div v-if="errorMessage" class="p-4 rounded-2xl bg-rose-50 border-2 border-rose-400 text-rose-900 text-xs space-y-2 animate-in fade-in duration-200 shadow-sm">
        <div class="flex items-start space-x-2.5">
          <AlertCircle class="w-4 h-4 shrink-0 text-rose-600 mt-0.5" />
          <span class="font-bold leading-relaxed">{{ errorMessage }}</span>
        </div>
        <!-- If already enrolled student attempted to log in -->
        <div v-if="isEnrolledStudentAttempt" class="pt-1.5 border-t border-rose-200 flex items-center justify-between">
          <span class="text-[11px] text-rose-800">You are already enrolled!</span>
          <router-link to="/login" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition inline-flex items-center space-x-1">
            <span>Open Student Portal</span>
            <ArrowRight class="w-3 h-3" />
          </router-link>
        </div>
      </div>

      <!-- Login Form -->
      <form class="space-y-4" @submit.prevent="handleLogin">
        <div>
          <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-1.5">Application Ref / Username / Email</label>
          <div class="relative">
            <input 
              v-model="form.identity" 
              type="text" 
              required 
              placeholder="e.g. ADM-2026-0001, username, or email"
              class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-blue-900 focus:ring-2 focus:ring-blue-100 text-sm font-medium transition shadow-inner"
            />
            <User class="w-4 h-4 text-slate-400 absolute right-3.5 top-3.5" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-1.5">Password</label>
          <div class="relative">
            <input 
              v-model="form.password" 
              :type="showPassword ? 'text' : 'password'" 
              required 
              placeholder="••••••••"
              class="w-full px-4 py-3 pr-11 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-blue-900 focus:ring-2 focus:ring-blue-100 text-sm transition shadow-inner"
            />
            <button 
              type="button" 
              @click="showPassword = !showPassword" 
              class="absolute right-3.5 top-3.5 text-slate-400 hover:text-slate-700 transition focus:outline-none cursor-pointer"
              :title="showPassword ? 'Hide password' : 'Show password'"
            >
              <EyeOff v-if="showPassword" class="w-4 h-4" />
              <Eye v-else class="w-4 h-4" />
            </button>
          </div>
        </div>

        <button 
          type="submit" 
          :disabled="isLoading"
          class="w-full py-3.5 px-4 rounded-xl text-xs sm:text-sm font-semibold bg-blue-900 hover:bg-blue-800 disabled:opacity-50 text-white shadow-md transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] flex items-center justify-center space-x-2 cursor-pointer border border-blue-800"
        >
          <span v-if="isLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
          <span v-else class="flex items-center space-x-2">
            <span>Continue Admission Procedure</span>
            <ArrowRight class="w-4 h-4 text-white" />
          </span>
        </button>
      </form>

      <!-- Useful Navigation Links -->
      <div class="space-y-2.5 pt-4 border-t-2 border-slate-100 text-center text-xs text-slate-600">
        <div>
          Don't have an application account yet? 
          <router-link to="/register" class="font-bold text-blue-900 hover:text-blue-700 ml-1 inline-flex items-center space-x-0.5 underline">
            <span>Register & Apply Now</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </router-link>
        </div>
        <div class="pt-1">
          Officially Enrolled Student? 
          <router-link to="/login" class="font-semibold text-slate-700 hover:text-blue-900 ml-1 underline">
            <span>Student Portal Login →</span>
          </router-link>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { User, Eye, EyeOff, AlertCircle, FileText, ArrowRight, ArrowLeft } from 'lucide-vue-next';
import api from '../../services/api';
import { getRoleRouteName } from '../../router';

const router = useRouter();

const form = ref({
  identity: '',
  password: ''
});

const showPassword = ref(false);
const isLoading = ref(false);
const errorMessage = ref('');

const isEnrolledStudentAttempt = computed(() => {
  return errorMessage.value.toLowerCase().includes('already been approved') || 
         errorMessage.value.toLowerCase().includes('student portal');
});

const handleLogin = async () => {
  errorMessage.value = '';
  isLoading.value = true;

  try {
    const res = await api.login({
      username: form.value.identity.trim(),
      password: form.value.password,
      portal_type: 'applicant'
    });

    if (res.data && res.data.token) {
      sessionStorage.setItem('sia_auth_token', res.data.token);
      sessionStorage.setItem('sia_auth_user', JSON.stringify(res.data));
      localStorage.removeItem('sia_auth_token');
      localStorage.removeItem('sia_auth_user');
      window.dispatchEvent(new Event('auth-changed'));

      const targetRoute = getRoleRouteName(res.data.role_slug);
      router.push({ name: targetRoute });
    } else {
      errorMessage.value = 'Failed to authenticate admission applicant account.';
    }
  } catch (err) {
    errorMessage.value = err.message || 'Login failed. Please check your credentials.';
  } finally {
    isLoading.value = false;
  }
};
</script>
