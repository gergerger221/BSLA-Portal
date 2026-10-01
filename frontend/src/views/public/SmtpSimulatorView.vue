<template>
  <div class="min-h-screen bg-slate-100 text-slate-900 font-sans pb-16">
    
    <!-- Top Header Banner -->
    <div class="bg-gradient-to-r from-[#061322] via-[#0c2340] to-[#0a1b2f] text-white border-b border-[#16273f] py-6 px-4 sm:px-8">
      <div class="max-w-[1600px] w-full mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
        
        <div class="flex items-center space-x-3.5">
          <div class="w-12 h-12 rounded-2xl bg-blue-500/20 border border-blue-400/40 flex items-center justify-center text-blue-300">
            <Mail class="w-6 h-6" />
          </div>
          <div>
            <div class="flex items-center space-x-2">
              <h1 class="text-xl sm:text-2xl font-black font-serif tracking-tight text-white">SMTP & Email Testing Simulator</h1>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-blue-900 text-white border border-blue-700">Test Sandbox</span>
            </div>
            <p class="text-xs text-slate-300">Test live or simulated outbound email notifications across the student admission lifecycle</p>
          </div>
        </div>

        <router-link 
          to="/" 
          class="px-5 py-2.5 rounded-xl text-xs font-semibold bg-white/10 hover:bg-white/20 text-white border border-white/20 transition flex items-center space-x-2 cursor-pointer shrink-0"
        >
          <ArrowLeft class="w-4 h-4" />
          <span>Return to Home</span>
        </router-link>

      </div>
    </div>

    <!-- Main Content Container -->
    <div class="max-w-[1600px] w-full mx-auto px-4 sm:px-8 mt-8 space-y-8">
      
      <!-- 1. SMTP Server Status & Configuration Inspector -->
      <div class="p-6 rounded-3xl bg-white border border-slate-200 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100">
          <div>
            <span class="text-xs font-bold uppercase tracking-wider text-blue-900">Server Diagnostics</span>
            <h2 class="text-lg font-black text-[#0c2340] font-serif mt-0.5">Current SMTP Configuration</h2>
          </div>

          <div class="flex items-center space-x-2.5">
            <span 
              :class="smtpConfig.enabled ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-blue-50 text-blue-950 border-blue-200'"
              class="px-3.5 py-1.5 rounded-xl text-xs font-bold uppercase border flex items-center space-x-1.5 shadow-2xs"
            >
              <span :class="smtpConfig.enabled ? 'bg-emerald-500' : 'bg-blue-500'" class="w-2 h-2 rounded-full animate-pulse"></span>
              <span>{{ smtpConfig.enabled ? 'Live Outbound Mode (Active)' : 'Simulation Mode (Safe Preview & Audit)' }}</span>
            </span>

            <button 
              @click="fetchConfig" 
              :disabled="loadingConfig"
              class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center space-x-1.5 cursor-pointer"
            >
              <RefreshCw :class="{'animate-spin': loadingConfig}" class="w-3.5 h-3.5" />
              <span>Refresh</span>
            </button>
          </div>
        </div>

        <!-- Configuration Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mt-4 text-xs">
          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
            <span class="text-[10px] font-bold text-slate-400 uppercase">SMTP Host</span>
            <div class="font-mono font-bold text-slate-800 text-xs truncate mt-0.5">{{ smtpConfig.host || 'smtp.gmail.com' }}</div>
          </div>
          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Port / Security</span>
            <div class="font-mono font-bold text-slate-800 text-xs mt-0.5">{{ smtpConfig.port }} ({{ (smtpConfig.encryption || 'tls').toUpperCase() }})</div>
          </div>
          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Username</span>
            <div class="font-mono font-bold text-slate-800 text-xs truncate mt-0.5">{{ smtpConfig.username || 'Not set' }}</div>
          </div>
          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Password</span>
            <div class="font-mono font-bold text-slate-800 text-xs mt-0.5">{{ smtpConfig.password_set ? '••••••••••••••••' : 'Not configured' }}</div>
          </div>
          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Sender Email</span>
            <div class="font-mono font-bold text-slate-800 text-xs truncate mt-0.5">{{ smtpConfig.from_email }}</div>
          </div>
          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Config File</span>
            <div class="font-mono text-blue-900 font-bold text-xs truncate mt-0.5" title="backend/config/MailConfig.php">MailConfig.php</div>
          </div>
        </div>
      </div>

      <!-- 2. Interactive Testing Simulator & Live Preview -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: Dispatch Controller (5 cols) -->
        <div class="lg:col-span-5 space-y-6">
          <div class="p-6 sm:p-7 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-5">
            <div>
              <span class="text-xs font-bold uppercase tracking-wider text-blue-900">Dispatch Controls</span>
              <h2 class="text-lg font-black text-[#0c2340] font-serif mt-0.5">Trigger Email Dispatch</h2>
              <p class="text-xs text-slate-500">Select an official institutional email template to test</p>
            </div>

            <!-- Template Type Picker -->
            <div class="space-y-2">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Email Template Type</label>
              <div class="grid grid-cols-1 gap-2">
                
                <button 
                  type="button" 
                  @click="testForm.type = 'registration'"
                  :class="testForm.type === 'registration' ? 'bg-blue-900 text-white border-blue-900 shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                  class="p-3.5 rounded-2xl border text-left text-xs font-semibold transition flex items-center justify-between cursor-pointer"
                >
                  <div class="flex items-center space-x-2.5">
                    <UserPlus class="w-4 h-4" :class="testForm.type === 'registration' ? 'text-blue-200' : 'text-blue-900'" />
                    <div>
                      <div class="font-bold">1. Applicant Registration Received</div>
                      <div class="text-[11px] opacity-80 font-normal">Application number, username & upload steps</div>
                    </div>
                  </div>
                  <CheckCircle v-if="testForm.type === 'registration'" class="w-4 h-4 text-blue-200" />
                </button>

                <button 
                  type="button" 
                  @click="testForm.type = 'approval'"
                  :class="testForm.type === 'approval' ? 'bg-blue-900 text-white border-blue-900 shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                  class="p-3.5 rounded-2xl border text-left text-xs font-semibold transition flex items-center justify-between cursor-pointer"
                >
                  <div class="flex items-center space-x-2.5">
                    <CheckCircle class="w-4 h-4" :class="testForm.type === 'approval' ? 'text-emerald-300' : 'text-emerald-600'" />
                    <div>
                      <div class="font-bold">2. Registrar Evaluation & Approval</div>
                      <div class="text-[11px] opacity-80 font-normal">Approved status, Student ID & assessment slip</div>
                    </div>
                  </div>
                  <CheckCircle v-if="testForm.type === 'approval'" class="w-4 h-4 text-blue-200" />
                </button>

                <button 
                  type="button" 
                  @click="testForm.type = 'enrollment'"
                  :class="testForm.type === 'enrollment' ? 'bg-blue-900 text-white border-blue-900 shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                  class="p-3.5 rounded-2xl border text-left text-xs font-semibold transition flex items-center justify-between cursor-pointer"
                >
                  <div class="flex items-center space-x-2.5">
                    <GraduationCap class="w-4 h-4" :class="testForm.type === 'enrollment' ? 'text-blue-200' : 'text-blue-900'" />
                    <div>
                      <div class="font-bold">3. Treasury Official Enrollment & COR</div>
                      <div class="text-[11px] opacity-80 font-normal">Official COR, OR number & permanent portal login</div>
                    </div>
                  </div>
                  <CheckCircle v-if="testForm.type === 'enrollment'" class="w-4 h-4 text-blue-200" />
                </button>

              </div>
            </div>

            <!-- Recipient Input Fields -->
            <div class="space-y-3 pt-2">
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Recipient Email Address *</label>
                <input 
                  v-model="testForm.recipient_email" 
                  type="email" 
                  placeholder="e.g. your_email@gmail.com, user@yahoo.com" 
                  class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:border-blue-900 transition"
                />
                <span class="text-[10px] text-slate-500 mt-0.5 block">Accepts any email provider (Gmail, Yahoo, Outlook, custom school domain)</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">First Name</label>
                  <input 
                    v-model="testForm.first_name" 
                    type="text" 
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:border-blue-900 transition"
                  />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Last Name</label>
                  <input 
                    v-model="testForm.last_name" 
                    type="text" 
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:border-blue-900 transition"
                  />
                </div>
              </div>
            </div>

            <!-- Trigger Button -->
            <button 
              type="button" 
              @click="triggerDispatch" 
              :disabled="sendingMail"
              class="w-full py-3.5 px-4 rounded-xl text-xs sm:text-sm font-semibold bg-blue-900 hover:bg-blue-800 disabled:opacity-50 text-white shadow-md transition flex items-center justify-center space-x-2 cursor-pointer"
            >
              <span v-if="sendingMail" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span v-else class="flex items-center space-x-2">
                <Send class="w-4 h-4 text-white" />
                <span>Dispatch Email Test</span>
              </span>
            </button>

            <!-- Dispatch Result Toast Alert -->
            <div v-if="dispatchResult" :class="dispatchResult.success ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : 'bg-rose-50 border-rose-300 text-rose-900'" class="p-4 rounded-2xl border-2 text-xs space-y-1 animate-in fade-in duration-200 shadow-xs">
              <div class="font-bold flex items-center space-x-1.5">
                <CheckCircle v-if="dispatchResult.success" class="w-4 h-4 text-emerald-600 shrink-0" />
                <AlertCircle v-else class="w-4 h-4 text-rose-600 shrink-0" />
                <span>{{ dispatchResult.success ? 'Email Dispatch Completed' : 'Dispatch Returned Error' }}</span>
              </div>
              <p class="text-[11px] leading-relaxed">{{ dispatchResult.message }}</p>
            </div>

          </div>
        </div>

        <!-- Right: Live Email Visual Preview (7 cols) -->
        <div class="lg:col-span-7 space-y-6">
          <div class="p-6 sm:p-7 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <div>
                <span class="text-xs font-bold uppercase tracking-wider text-blue-900">Email Canvas</span>
                <h2 class="text-lg font-black text-[#0c2340] font-serif mt-0.5">Live Rendered Email Preview</h2>
              </div>
              <span class="text-xs font-semibold text-slate-600 bg-slate-100 px-3 py-1 rounded-full border border-slate-200">
                To: {{ testForm.recipient_email || 'recipient@domain.com' }}
              </span>
            </div>

            <!-- Email Mock Browser Frame -->
            <div class="rounded-2xl border border-slate-300 overflow-hidden shadow-inner bg-slate-50">
              
              <!-- Mock Email Client Bar -->
              <div class="bg-slate-200 px-4 py-2.5 border-b border-slate-300 flex items-center justify-between text-xs text-slate-700 font-medium">
                <div class="flex items-center space-x-2">
                  <div class="flex space-x-1">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-400 inline-block"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-400 inline-block"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 inline-block"></span>
                  </div>
                  <span class="font-bold ml-2">Subject:</span>
                  <span class="font-serif text-[#0c2340] font-bold truncate max-w-xs sm:max-w-md">{{ previewSubject }}</span>
                </div>
                <span class="text-[10px] text-slate-500">HTML Template</span>
              </div>

              <!-- Rendered Email Body -->
              <div class="p-4 sm:p-6 bg-slate-100 max-h-[560px] overflow-y-auto">
                <div class="max-w-[560px] mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden text-slate-800">
                  
                  <!-- Email Header Banner -->
                  <div class="bg-gradient-to-br from-[#0c2340] to-[#163860] p-6 text-center text-white border-b-4 border-blue-900">
                    <h3 class="font-serif font-black text-lg sm:text-xl tracking-wide">BIRINGAN SCIENCE & LEADERSHIP ACADEMY</h3>
                    <p class="text-blue-200 text-[10px] font-bold uppercase tracking-wider mt-1">Junior & Senior High School • "Innovating for the Nation" (DepEd ID: 405621)</p>
                  </div>

                  <!-- Email Content Container -->
                  <div class="p-6 sm:p-8 space-y-4 text-xs sm:text-sm leading-relaxed">
                    
                    <!-- Template 1: Registration -->
                    <template v-if="testForm.type === 'registration'">
                      <h4 class="text-base font-black text-[#0c2340] font-serif">Welcome to Biringan Science and Leadership Academy!</h4>
                      <p>Dear <strong>{{ testForm.first_name }} {{ testForm.last_name }}</strong>,</p>
                      <p>Your temporary admission account has been successfully created for <strong>School Year 2026–2027</strong>.</p>
                      
                      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5 font-sans">
                        <div class="flex justify-between">
                          <span class="text-slate-500">Application Reference No:</span>
                          <span class="font-mono font-bold text-[#0c2340]">ADM-2026-9999</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-slate-500">Registered Email:</span>
                          <span class="font-medium text-slate-800">{{ testForm.recipient_email }}</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-slate-500">Temporary Username:</span>
                          <span class="font-mono font-bold text-blue-900">juandelacruz123</span>
                        </div>
                      </div>

                      <h5 class="font-bold text-[#0c2340] text-xs uppercase tracking-wider mt-4">Next Steps to Complete Admission:</h5>
                      <ol class="list-decimal list-inside space-y-1 text-xs text-slate-600 pl-1">
                        <li>Log in to your <strong>Online Admission Portal</strong>.</li>
                        <li>Enter your 12-digit DepEd LRN and personal information.</li>
                        <li>Upload digital copies of your <strong>PSA Birth Certificate</strong> and <strong>SF9 Report Card</strong>.</li>
                        <li>Await Registrar document verification and Section assignment.</li>
                      </ol>

                      <div class="pt-4">
                        <div class="inline-block px-5 py-3 rounded-xl bg-blue-900 text-white font-bold text-xs shadow-xs">
                          Access Admission Dashboard &rarr;
                        </div>
                      </div>
                    </template>

                    <!-- Template 2: Registrar Approval -->
                    <template v-if="testForm.type === 'approval'">
                      <h4 class="text-base font-black text-emerald-800 font-serif">✔ Your Admission Has Been Approved!</h4>
                      <p>Dear <strong>{{ testForm.first_name }} {{ testForm.last_name }}</strong>,</p>
                      <p>Congratulations! The Registrar has reviewed and authenticated your submitted credentials. You have been officially approved for enrollment.</p>
                      
                      <div class="p-4 rounded-xl bg-emerald-50 border-2 border-emerald-200 space-y-1.5 font-sans text-xs">
                        <div class="flex justify-between">
                          <span class="text-emerald-700">Assigned Student ID:</span>
                          <span class="font-mono font-bold text-emerald-950">2026-SHS-0099</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-emerald-700">Assigned Section:</span>
                          <span class="font-bold text-emerald-950">11 - STEM Einstein</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-emerald-700">Assessment Form No:</span>
                          <span class="font-mono text-emerald-950">ASS-2026-0099</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-emerald-700">Total Net Tuition:</span>
                          <span class="font-bold text-emerald-950">₱12,500.00</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-emerald-700">Minimum Required Downpayment:</span>
                          <span class="font-black text-emerald-950 text-sm">₱3,000.00</span>
                        </div>
                      </div>

                      <div class="pt-4">
                        <div class="inline-block px-5 py-3 rounded-xl bg-emerald-700 text-white font-black text-xs shadow-md">
                          Complete Downpayment &rarr;
                        </div>
                      </div>
                    </template>

                    <!-- Template 3: Treasury Official Enrollment -->
                    <template v-if="testForm.type === 'enrollment'">
                      <h4 class="text-base font-black text-[#0c2340] font-serif">🎓 You Are Officially Enrolled!</h4>
                      <p>Dear <strong>{{ testForm.first_name }} {{ testForm.last_name }}</strong>,</p>
                      <p>Your tuition payment of <strong>₱3,000.00</strong> has been verified by the Treasury Department. You are now officially enrolled for <strong>S.Y. 2026–2027</strong>.</p>
                      
                      <div class="p-4 rounded-xl bg-blue-50 border-2 border-blue-200 space-y-1.5 font-sans text-xs">
                        <div class="flex justify-between">
                          <span class="text-blue-700">Official Student ID:</span>
                          <span class="font-mono font-bold text-blue-950">2026-SHS-0099</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-blue-700">Official Receipt (OR) No:</span>
                          <span class="font-mono text-blue-950">OR-2026-0099</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-blue-700">Enrollment Status:</span>
                          <span class="font-bold text-emerald-700">OFFICIALLY ENROLLED</span>
                        </div>
                      </div>

                      <div class="pt-4">
                        <div class="inline-block px-5 py-3 rounded-xl bg-blue-900 text-white font-bold text-xs shadow-xs">
                          Sign In to Student Portal &rarr;
                        </div>
                      </div>
                    </template>

                  </div>

                  <!-- Email Footer -->
                  <div class="bg-[#0c2340] p-4 text-center text-[10px] text-slate-400 space-y-1">
                    <p class="font-bold text-slate-300">Biringan Science and Leadership Academy (BSLA) Main Campus</p>
                    <p>Academic Boulevard, Biringan City, Samar • Tel: (055) 888-7766</p>
                  </div>

                </div>
              </div>

            </div>
          </div>
        </div>

      </div>

      <!-- 3. Recent SMTP Audit & Dispatch Logs -->
      <div class="p-6 sm:p-7 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <span class="text-xs font-bold uppercase tracking-wider text-blue-900">Delivery Audit</span>
            <h2 class="text-lg font-black text-[#0c2340] font-serif mt-0.5">Recent Email Dispatch Activity Logs</h2>
          </div>
          <button 
            @click="fetchConfig" 
            class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer"
          >
            Refresh Logs
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs min-w-[550px]">
            <thead>
              <tr class="border-b-2 border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                <th class="py-3 px-4">Log ID</th>
                <th class="py-3 px-4">Timestamp</th>
                <th class="py-3 px-4">Action</th>
                <th class="py-3 px-4">Details</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="log in recentLogs" :key="log.id" class="hover:bg-slate-50">
                <td class="py-3 px-4 font-mono font-bold text-slate-500">#{{ log.id }}</td>
                <td class="py-3 px-4 text-slate-600 whitespace-nowrap">{{ log.created_at }}</td>
                <td class="py-3 px-4">
                  <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase bg-blue-100 text-blue-900 border border-blue-200">
                    {{ log.action }}
                  </span>
                </td>
                <td class="py-3 px-4 font-mono text-slate-800 text-[11px]">{{ log.details }}</td>
              </tr>
              <tr v-if="recentLogs.length === 0">
                <td colspan="4" class="py-8 text-center text-slate-400">
                  No email dispatch logs found. Click "Dispatch Email Test" above to generate a test log!
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { 
  Mail, 
  Send, 
  RefreshCw, 
  ArrowLeft, 
  CheckCircle, 
  AlertCircle, 
  UserPlus, 
  GraduationCap 
} from 'lucide-vue-next';
import api from '../../services/api';

const smtpConfig = ref({
  enabled: false,
  host: 'smtp.gmail.com',
  port: 587,
  encryption: 'tls',
  username: '',
  password_set: false,
  from_email: 'admissions@bsla.edu.ph'
});

const recentLogs = ref([]);
const loadingConfig = ref(false);
const sendingMail = ref(false);
const dispatchResult = ref(null);

const testForm = ref({
  type: 'registration', // registration, approval, enrollment
  recipient_email: 'applicant.test@gmail.com',
  first_name: 'Juan',
  last_name: 'Dela Cruz'
});

const previewSubject = computed(() => {
  switch (testForm.value.type) {
    case 'registration':
      return 'Admission Application Received: ADM-2026-9999 - Biringan Science & Leadership Academy';
    case 'approval':
      return 'Admission Approved & Assessment Ready - BSLA Admissions';
    case 'enrollment':
      return 'Official Certificate of Registration (COR) - BSLA Admissions';
    default:
      return 'SMTP Test Notification - BSLA Admissions';
  }
});

const fetchConfig = async () => {
  loadingConfig.value = true;
  try {
    const res = await api.getSmtpConfig();
    if (res.data) {
      smtpConfig.value = res.data.config || smtpConfig.value;
      recentLogs.value = res.data.recent_logs || [];
    }
  } catch (e) {
    console.error('Failed to load SMTP config:', e);
  } finally {
    loadingConfig.value = false;
  }
};

const triggerDispatch = async () => {
  if (!testForm.value.recipient_email) {
    dispatchResult.value = {
      success: false,
      message: 'Please enter a recipient email address before dispatching the test email.'
    };
    return;
  }

  sendingMail.value = true;
  dispatchResult.value = null;

  try {
    const res = await api.testSmtp(testForm.value);
    dispatchResult.value = res.data;
    await fetchConfig();
  } catch (e) {
    dispatchResult.value = {
      success: false,
      message: e.message || 'Failed to dispatch email test.'
    };
  } finally {
    sendingMail.value = false;
  }
};

onMounted(() => {
  fetchConfig();
});
</script>
