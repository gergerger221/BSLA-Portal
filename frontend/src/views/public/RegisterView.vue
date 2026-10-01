<template>
  <div class="min-h-screen flex flex-col items-center justify-center py-6 sm:py-12 px-3 sm:px-6 lg:px-8 bg-gradient-to-br from-slate-900 via-[#0c2340] to-slate-950 text-slate-900 selection:bg-blue-900 selection:text-white relative overflow-hidden">
    
    <!-- Academic Decorative Background Elements -->
    <div class="absolute inset-0 bg-[radial-gradient(#1e3a8a_1px,transparent_1px)] [background-size:24px_24px] opacity-20 pointer-events-none"></div>
    <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-blue-600/15 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-blue-500/15 blur-3xl pointer-events-none"></div>

    <!-- Quick Back to Website Trigger (Responsive) -->
    <div class="w-full max-w-lg mb-3 sm:mb-0 sm:absolute sm:top-6 sm:left-6 z-20">
      <router-link 
        to="/" 
        class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-xl bg-slate-900/80 hover:bg-slate-800 border border-slate-700 text-slate-300 hover:text-white text-xs font-semibold backdrop-blur-md transition-all shadow-md cursor-pointer"
      >
        <ArrowLeft class="w-4 h-4 text-blue-300" />
        <span>Back to Public Website</span>
      </router-link>
    </div>

    <div class="max-w-lg w-full space-y-5 sm:space-y-6 p-5 sm:p-9 rounded-3xl bg-white/95 backdrop-blur-xl border border-slate-200 shadow-2xl relative z-10 animate-in fade-in zoom-in-95 duration-200 my-auto">
      
      <!-- Top Navigation: Return to Home -->
      <div class="flex items-center justify-between pb-2 border-b border-slate-100">
        <router-link 
          to="/" 
          class="inline-flex items-center space-x-1.5 text-xs font-semibold text-slate-600 hover:text-blue-900 hover:bg-slate-100 px-3 py-1.5 rounded-xl transition cursor-pointer"
        >
          <ArrowLeft class="w-4 h-4" />
          <span>Return to Home</span>
        </router-link>

        <span class="text-[10px] font-bold text-blue-900 uppercase tracking-wider bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200">
          Admission Gateway
        </span>
      </div>

      <!-- Institutional Header -->
      <div class="text-center">
        <div class="w-16 h-16 rounded-2xl bg-[#091524] border border-blue-500/40 text-blue-300 flex items-center justify-center mx-auto mb-3.5 shadow-lg shadow-blue-950/20">
          <UserPlus class="w-8 h-8 text-blue-300" />
        </div>
        <div class="inline-flex items-center space-x-1.5 px-3 py-0.5 rounded-full bg-blue-50 border border-blue-200 text-blue-950 text-[10px] font-bold uppercase tracking-wider mb-2 shadow-2xs">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
          <span>S.Y. 2026-2027 Admission</span>
        </div>
        <h2 class="text-2xl sm:text-3xl font-black text-[#0c2340] tracking-tight font-serif">Temporary Admission Account</h2>
        <p class="mt-1 text-xs text-slate-600 font-medium">
          Create an applicant account to start your Junior or Senior High School admission procedure at Biringan Science and Leadership Academy (BSLA).
        </p>
      </div>

      <!-- Error Alert -->
      <div v-if="errorMessage" class="p-3.5 rounded-xl bg-rose-50 border-2 border-rose-300 text-rose-800 text-xs flex items-center space-x-2 animate-in fade-in duration-200 shadow-xs">
        <AlertCircle class="w-4 h-4 shrink-0 text-rose-600" />
        <span class="font-bold">{{ errorMessage }}</span>
      </div>

      <form class="space-y-4" @submit.prevent="handleRegister">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-1.5">First Name *</label>
            <input 
              v-model="form.first_name" 
              type="text" 
              required 
              @keydown="blockNonAlphabetic($event)"
              @input="handleAlphabeticInput('first_name', $event)"
              placeholder="e.g. Juan"
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-blue-900 focus:ring-2 focus:ring-blue-100 text-sm font-medium transition"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-1.5">Last Name *</label>
            <input 
              v-model="form.last_name" 
              type="text" 
              required 
              @keydown="blockNonAlphabetic($event)"
              @input="handleAlphabeticInput('last_name', $event)"
              placeholder="e.g. Dela Cruz"
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-blue-900 focus:ring-2 focus:ring-blue-100 text-sm font-medium transition"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-1.5">Middle Name</label>
          <input 
            v-model="form.middle_name" 
            type="text" 
            @keydown="blockNonAlphabetic($event)"
            @input="handleAlphabeticInput('middle_name', $event)"
            placeholder="e.g. Protacio (Optional)"
            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-blue-900 focus:ring-2 focus:ring-blue-100 text-sm font-medium transition"
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-1.5">Email Address *</label>
            <input 
              v-model="form.email" 
              type="email" 
              required 
              placeholder="juan@gmail.com"
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-blue-900 focus:ring-2 focus:ring-blue-100 text-sm font-medium transition"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-1.5">Contact Mobile No. *</label>
            <input 
              v-model="form.contact_number" 
              type="tel" 
              required 
              maxlength="11"
              @keydown="blockNonNumeric($event)"
              @input="handleNumericInput('contact_number', $event, 11)"
              placeholder="09171234567"
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-blue-900 focus:ring-2 focus:ring-blue-100 text-sm font-mono transition"
            />
            <span class="text-[10px] text-slate-500 font-medium mt-0.5 block">11-digit mobile number (e.g. 09171234567)</span>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-1.5">Set Password *</label>
            <div class="relative">
              <input 
                v-model="form.password" 
                :type="showPassword ? 'text' : 'password'" 
                required 
                placeholder="At least 6 characters"
                class="w-full px-3.5 py-2.5 pr-11 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-blue-900 focus:ring-2 focus:ring-blue-100 text-sm transition"
              />
              <button 
                type="button" 
                @click="showPassword = !showPassword" 
                class="absolute right-3.5 top-3 text-slate-400 hover:text-slate-700 transition focus:outline-none cursor-pointer"
                :title="showPassword ? 'Hide password' : 'Show password'"
              >
                <EyeOff v-if="showPassword" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-1.5">Re-enter Password *</label>
            <div class="relative">
              <input 
                v-model="form.password_confirmation" 
                :type="showConfirmPassword ? 'text' : 'password'" 
                required 
                placeholder="Confirm password"
                :class="[
                  'w-full px-3.5 py-2.5 pr-11 rounded-xl bg-slate-50 border text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white text-sm transition',
                  form.password_confirmation && form.password !== form.password_confirmation 
                    ? 'border-rose-300 focus:border-rose-600 focus:ring-2 focus:ring-rose-100' 
                    : form.password_confirmation && form.password === form.password_confirmation 
                      ? 'border-emerald-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100' 
                      : 'border-slate-200 focus:border-blue-900 focus:ring-2 focus:ring-blue-100'
                ]"
              />
              <button 
                type="button" 
                @click="showConfirmPassword = !showConfirmPassword" 
                class="absolute right-3.5 top-3 text-slate-400 hover:text-slate-700 transition focus:outline-none cursor-pointer"
                :title="showConfirmPassword ? 'Hide password' : 'Show password'"
              >
                <EyeOff v-if="showConfirmPassword" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
            <div v-if="form.password_confirmation && form.password !== form.password_confirmation" class="text-[10px] text-rose-600 font-bold mt-1">
              ✕ Passwords do not match
            </div>
            <div v-else-if="form.password_confirmation && form.password === form.password_confirmation" class="text-[10px] text-emerald-600 font-bold mt-1">
              ✓ Passwords match
            </div>
          </div>
        </div>

        <!-- Action Buttons: Register -->
        <div class="pt-2">
          <button 
            type="submit" 
            :disabled="isLoading"
            class="w-full py-3.5 px-4 rounded-xl text-xs sm:text-sm font-semibold bg-blue-900 hover:bg-blue-800 disabled:opacity-50 text-white shadow-md transition-all flex items-center justify-center space-x-2 cursor-pointer border border-blue-800"
          >
            <span v-if="isLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span v-else class="flex items-center space-x-2">
              <span>Register & Start Admission Procedure</span>
              <ArrowRight class="w-4 h-4 text-white" />
            </span>
          </button>
        </div>
      </form>

      <div class="text-center text-xs text-slate-600 pt-2 border-t border-slate-100">
        Already registered an admission application?
        <router-link to="/admission-login" class="font-bold text-blue-900 hover:text-blue-700 ml-1 inline-flex items-center space-x-0.5 underline">
          <span>Sign In to Admission Portal</span>
          <ArrowRight class="w-3.5 h-3.5" />
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { UserPlus, AlertCircle, Eye, EyeOff, ArrowRight, ArrowLeft } from 'lucide-vue-next';
import api from '../../services/api';

const router = useRouter();
const showPassword = ref(false);
const showConfirmPassword = ref(false);
const form = ref({
  first_name: '',
  last_name: '',
  middle_name: '',
  email: '',
  contact_number: '',
  password: '',
  password_confirmation: ''
});
const isLoading = ref(false);
const errorMessage = ref('');

// BULLETPROOF WATCHER: strips invalid chars reactively
watch(() => form.value.first_name, (v) => { if (v && /[^a-zA-ZñÑ\s\-\.\']/g.test(v)) form.value.first_name = v.replace(/[^a-zA-ZñÑ\s\-\.\']/g, ''); });
watch(() => form.value.last_name, (v) => { if (v && /[^a-zA-ZñÑ\s\-\.\']/g.test(v)) form.value.last_name = v.replace(/[^a-zA-ZñÑ\s\-\.\']/g, ''); });
watch(() => form.value.middle_name, (v) => { if (v && /[^a-zA-ZñÑ\s\-\.\']/g.test(v)) form.value.middle_name = v.replace(/[^a-zA-ZñÑ\s\-\.\']/g, ''); });
watch(() => form.value.contact_number, (v) => { if (v && /\D/.test(v)) form.value.contact_number = v.replace(/\D/g, '').slice(0, 11); });

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

const DRAFT_KEY = 'sia_registration_draft';

// Restore saved form draft on mount
onMounted(() => {
  try {
    const savedDraft = sessionStorage.getItem(DRAFT_KEY) || localStorage.getItem(DRAFT_KEY);
    if (savedDraft) {
      const parsed = JSON.parse(savedDraft);
      form.value.first_name = parsed.first_name || '';
      form.value.last_name = parsed.last_name || '';
      form.value.middle_name = parsed.middle_name || '';
      form.value.email = parsed.email || '';
      form.value.contact_number = parsed.contact_number || '';
      form.value.password = parsed.password || '';
      form.value.password_confirmation = parsed.password_confirmation || '';
    }
  } catch (e) {
    // Ignore parse error
  }
});

// Auto-save form inputs whenever user types
watch(form, (newVal) => {
  try {
    const draftData = {
      first_name: newVal.first_name,
      last_name: newVal.last_name,
      middle_name: newVal.middle_name,
      email: newVal.email,
      contact_number: newVal.contact_number,
      password: newVal.password,
      password_confirmation: newVal.password_confirmation
    };
    sessionStorage.setItem(DRAFT_KEY, JSON.stringify(draftData));
    localStorage.setItem(DRAFT_KEY, JSON.stringify(draftData));
  } catch (e) {
    // Ignore storage quota error
  }
}, { deep: true });

const handleRegister = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  const cleanContact = (form.value.contact_number || '').replace(/\D/g, '');
  if (!/^09\d{9}$/.test(cleanContact)) {
    errorMessage.value = 'Must be an 11-digit Philippine mobile number starting with 09 (e.g. 09123456789).';
    isLoading.value = false;
    return;
  }

  if ((form.value.password || '').length < 6) {
    errorMessage.value = 'Password must be at least 6 characters long.';
    isLoading.value = false;
    return;
  }

  if (form.value.password !== form.value.password_confirmation) {
    errorMessage.value = 'Passwords do not match. Please re-enter your password.';
    isLoading.value = false;
    return;
  }

  try {
    const res = await api.registerApplicant({
      ...form.value,
      contact_number: cleanContact
    });
    const user = res.data;

    // Clear saved draft on successful registration
    sessionStorage.removeItem(DRAFT_KEY);
    localStorage.removeItem(DRAFT_KEY);

    sessionStorage.setItem('sia_auth_token', user.token);
    sessionStorage.setItem('sia_auth_user', JSON.stringify(user));
    localStorage.removeItem('sia_auth_token');
    localStorage.removeItem('sia_auth_user');
    window.dispatchEvent(new Event('auth-changed'));

    router.push('/admission');
  } catch (err) {
    errorMessage.value = err.message || 'Registration failed.';
  } finally {
    isLoading.value = false;
  }
};
</script>
