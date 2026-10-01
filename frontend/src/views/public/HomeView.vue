<template>
  <div class="min-h-screen bg-slate-50 text-slate-900 font-sans selection:bg-blue-900 selection:text-white">
    
    <!-- ========================================================================= -->
    <!-- 1. MINIMALIST HERO SECTION                                                -->
    <!-- ========================================================================= -->
    <section id="hero" class="relative overflow-hidden bg-gradient-to-b from-[#061322] via-[#0c2340] to-[#0a1b2f] text-white py-16 sm:py-24 lg:py-28 border-b border-slate-800">
      <!-- Subtle Ambient Pattern -->
      <div class="absolute inset-0 bg-[radial-gradient(#1e3a8a_1px,transparent_1px)] [background-size:32px_32px] opacity-20 pointer-events-none"></div>
      
      <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
        
        <!-- Institutional Pill Badge -->
        <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/15 text-blue-200 text-xs font-semibold backdrop-blur-md">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
          <span>DepEd School ID: 405621 • "Innovating for the Nation" • S.Y. 2026–2027</span>
        </div>

        <!-- Main Headline -->
        <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black font-serif tracking-tight text-white leading-tight">
          Biringan Science & Leadership Academy
          <span class="block text-blue-200 font-normal text-2xl sm:text-4xl lg:text-5xl mt-2 font-serif">
            Admissions & Student Portal (BSLA)
          </span>
        </h1>

        <!-- Subtitle -->
        <p class="text-sm sm:text-base text-slate-300 max-w-2xl mx-auto font-normal leading-relaxed">
          Official admissions and enrollment portal for Junior High School (Grades 7–10) and Senior High School (Grades 11–12). Submit credentials, track status, and enroll online.
        </p>

        <!-- Primary Action Buttons -->
        <div class="flex flex-wrap items-center justify-center gap-3.5 pt-4">
          <router-link 
            to="/register" 
            class="px-7 py-3.5 rounded-xl text-xs sm:text-sm font-semibold bg-blue-900 hover:bg-blue-800 text-white shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center space-x-2 border border-blue-700/50 cursor-pointer"
          >
            <UserPlus class="w-4 h-4" />
            <span>Apply for S.Y. 2026–2027</span>
            <ArrowRight class="w-4 h-4" />
          </router-link>

          <router-link 
            to="/login" 
            class="px-6 py-3.5 rounded-xl text-xs sm:text-sm font-semibold bg-white/10 hover:bg-white/15 text-white border border-white/20 backdrop-blur-md transition flex items-center space-x-2 cursor-pointer"
          >
            <GraduationCap class="w-4 h-4 text-blue-200" />
            <span>Student Portal</span>
          </router-link>
        </div>

        <!-- Minimalist Key Facts Strip -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-10 border-t border-slate-800/80 text-center max-w-3xl mx-auto text-xs">
          <div class="p-3">
            <span class="text-slate-400 block">Grade Levels</span>
            <strong class="text-white text-sm font-serif">Grades 7 to 12 Only</strong>
          </div>
          <div class="p-3 sm:border-x sm:border-slate-800">
            <span class="text-slate-400 block">DepEd Voucher Program</span>
            <strong class="text-emerald-400 text-sm font-serif">100% Subsidy Eligible</strong>
          </div>
          <div class="p-3">
            <span class="text-slate-400 block">Admission Process</span>
            <strong class="text-white text-sm font-serif">100% Online Paperless</strong>
          </div>
        </div>

      </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 2. STREAMLINED ACADEMIC PROGRAMS & STRANDS                                -->
    <!-- ========================================================================= -->
    <section id="programs" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-20 scroll-mt-24">
      
      <div class="text-center max-w-2xl mx-auto mb-10 space-y-2">
        <span class="text-xs font-bold uppercase tracking-wider text-blue-900">Curriculums & Offerings</span>
        <h2 class="text-2xl sm:text-3xl font-black font-serif text-[#0c2340] tracking-tight">
          Secondary Education Programs
        </h2>
        <p class="text-xs sm:text-sm text-slate-500">
          Philippine DepEd K-12 & MATATAG curriculum offerings for Junior & Senior High learners.
        </p>

        <!-- Clean Category Filter -->
        <div class="inline-flex items-center space-x-1 p-1 bg-slate-100 rounded-xl border border-slate-200 mt-2 max-w-full overflow-x-auto">
          <button 
            v-for="cat in ['all', 'JHS', 'ACAD', 'TVL']" 
            :key="cat"
            @click="selectedTrackCategory = cat"
            :class="selectedTrackCategory === cat ? 'bg-white text-blue-950 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
            class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer"
          >
            {{ cat === 'all' ? 'All Offerings' : cat }}
          </button>
        </div>
      </div>

      <!-- Clean Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="prog in filteredPrograms" 
          :key="prog.id"
          class="p-6 rounded-2xl bg-white border border-slate-200 hover:border-blue-500 hover:shadow-md transition-all flex flex-col justify-between space-y-4"
        >
          <div>
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-mono font-bold text-blue-900">{{ prog.code }}</span>
              <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-slate-100 text-slate-600">
                {{ prog.category }}
              </span>
            </div>
            <h3 class="font-bold text-base text-[#0c2340] font-serif leading-snug">{{ prog.name }}</h3>
            <p class="text-xs text-slate-500 mt-2 leading-relaxed">{{ prog.description }}</p>
          </div>

          <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="text-[11px] font-semibold text-emerald-700">100% Voucher Grant</span>
            <button 
              type="button" 
              @click="openStrandModal(prog)"
              class="text-xs font-semibold text-blue-900 hover:text-blue-700 flex items-center space-x-1 cursor-pointer"
            >
              <span>View Details</span>
              <ArrowRight class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>

    </section>

    <!-- ========================================================================= -->
    <!-- 3. 4-STEP STREAMLINED ADMISSION PATHWAY                                    -->
    <!-- ========================================================================= -->
    <section id="pathway" class="bg-white border-y border-slate-200 py-14 sm:py-20 scroll-mt-24">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-2xl mx-auto mb-10 space-y-2">
          <span class="text-xs font-bold uppercase tracking-wider text-blue-900">Simple Process</span>
          <h2 class="text-2xl sm:text-3xl font-black font-serif text-[#0c2340] tracking-tight">
            4-Stage Admission Pathway
          </h2>
          <p class="text-xs sm:text-sm text-slate-500">
            From online registration to official Certificate of Registration (COR) generation.
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 space-y-2.5">
            <div class="w-8 h-8 rounded-xl bg-blue-900 text-white font-mono font-bold text-xs flex items-center justify-center">
              01
            </div>
            <h3 class="font-bold text-sm text-[#0c2340] font-serif">Online Registration</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
              Create your applicant account, enter student information, and supply your 12-digit DepEd LRN.
            </p>
          </div>

          <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 space-y-2.5">
            <div class="w-8 h-8 rounded-xl bg-blue-900 text-white font-mono font-bold text-xs flex items-center justify-center">
              02
            </div>
            <h3 class="font-bold text-sm text-[#0c2340] font-serif">Document Upload</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
              Upload clear photos or scans of your PSA Birth Certificate, SF9 Report Card, and Good Moral.
            </p>
          </div>

          <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 space-y-2.5">
            <div class="w-8 h-8 rounded-xl bg-blue-900 text-white font-mono font-bold text-xs flex items-center justify-center">
              03
            </div>
            <h3 class="font-bold text-sm text-[#0c2340] font-serif">Registrar Evaluation</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
              Registrar verifies credentials, assigns official section seating, and approves assessment.
            </p>
          </div>

          <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 space-y-2.5">
            <div class="w-8 h-8 rounded-xl bg-blue-900 text-white font-mono font-bold text-xs flex items-center justify-center">
              04
            </div>
            <h3 class="font-bold text-sm text-[#0c2340] font-serif">Downpayment & COR</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
              Settle downpayment online or at Cashier to receive your official COR and permanent Student ID.
            </p>
          </div>
        </div>

      </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 4. MINIMALIST DEPED VOUCHER PROGRAM CALLOUT                               -->
    <!-- ========================================================================= -->
    <section id="vouchers" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-20 scroll-mt-24">
      <div class="p-8 sm:p-10 rounded-3xl bg-[#0c2340] text-white border border-slate-800 shadow-md">
        
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-8 border-b border-slate-700/80">
          <div class="space-y-2 max-w-2xl">
            <span class="px-3 py-1 rounded text-xs font-bold uppercase bg-blue-800 text-white inline-block">
              Government Tuition Subsidy
            </span>
            <h2 class="text-2xl sm:text-3xl font-black font-serif text-blue-100">
              Senior High School Voucher Program (SHS-VP)
            </h2>
            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
              Under the Department of Education (DepEd) & PEAC guidelines, eligible Junior High School completers receive substantial tuition voucher grants.
            </p>
          </div>

          <router-link 
            to="/register" 
            class="px-6 py-3 rounded-xl text-xs sm:text-sm font-semibold bg-white text-blue-950 hover:bg-blue-50 transition shrink-0 cursor-pointer shadow-sm text-center"
          >
            Apply for S.Y. 2026–2027
          </router-link>
        </div>

        <!-- 3-Column Subsidy Rate Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-8 text-center">
          <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-700 space-y-1.5">
            <span class="text-xs text-blue-300 font-bold">Public JHS Completers</span>
            <div class="text-2xl sm:text-3xl font-black font-mono text-white">100% Grant</div>
            <span class="text-[11px] text-slate-400 block">Automatic Voucher (No Code Needed)</span>
          </div>

          <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-700 space-y-1.5">
            <span class="text-xs text-blue-300 font-bold">Private ESC Grantees</span>
            <div class="text-2xl sm:text-3xl font-black font-mono text-white">80% Subsidy</div>
            <span class="text-[11px] text-slate-400 block">ESC Certificate Applied</span>
          </div>

          <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-700 space-y-1.5">
            <span class="text-xs text-blue-300 font-bold">Private Non-ESC / QVR</span>
            <div class="text-2xl sm:text-3xl font-black font-mono text-white">50% Subsidy</div>
            <span class="text-[11px] text-slate-400 block">QVR Voucher Grant</span>
          </div>
        </div>

      </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 5. ESSENTIAL FAQS & INQUIRIES                                             -->
    <!-- ========================================================================= -->
    <section id="faqs" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-16 sm:pb-24 scroll-mt-24">
      
      <div class="text-center mb-8 space-y-1.5">
        <span class="text-xs font-bold uppercase tracking-wider text-blue-900">Inquiries</span>
        <h2 class="text-2xl sm:text-3xl font-black font-serif text-[#0c2340] tracking-tight">
          Frequently Asked Questions
        </h2>
      </div>

      <div class="space-y-3">
        <div 
          v-for="(faq, fIdx) in faqs" 
          :key="fIdx"
          class="p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-1.5"
        >
          <h3 class="font-bold text-sm text-[#0c2340] font-serif">{{ faq.q }}</h3>
          <p class="text-xs text-slate-600 leading-relaxed">{{ faq.a }}</p>
        </div>
      </div>

    </section>

    <!-- ========================================================================= -->
    <!-- 6. DETAILED STRAND INFORMATION MODAL                                      -->
    <!-- ========================================================================= -->
    <div 
      v-if="showStrandModal && selectedModalStrand" 
      class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6 overflow-y-auto animate-in fade-in duration-200"
      @click.self="closeStrandModal"
    >
      <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden shadow-2xl border border-slate-200 my-auto text-slate-900">
        
        <!-- Header -->
        <div class="p-6 bg-[#0c2340] text-white flex items-center justify-between">
          <div>
            <span class="text-xs font-mono font-bold text-blue-300 uppercase">{{ selectedModalStrand.code }} • {{ selectedModalStrand.category }}</span>
            <h3 class="text-xl font-bold font-serif text-white mt-0.5">{{ selectedModalStrand.name }}</h3>
          </div>
          <button 
            @click="closeStrandModal" 
            class="w-8 h-8 rounded-full bg-white/10 hover:bg-rose-600 text-white flex items-center justify-center transition cursor-pointer"
            title="Close"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-4 sm:p-6 space-y-4 overflow-y-auto text-xs sm:text-sm flex-1">
          <div>
            <h4 class="font-bold text-slate-400 uppercase text-[10px] tracking-wider mb-1">Curriculum Overview</h4>
            <p class="text-slate-700 leading-relaxed">
              {{ selectedModalStrand.detailedDescription || selectedModalStrand.description }}
            </p>
          </div>

          <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
            <h4 class="font-bold text-[#0c2340] uppercase text-[11px]">Specialized Curriculum Subjects</h4>
            <p class="text-slate-600 text-xs leading-relaxed">
              {{ selectedModalStrand.specializedSubjects }}
            </p>
          </div>

          <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
            <h4 class="font-bold text-[#0c2340] uppercase text-[11px]">Career & Senior High Pathways</h4>
            <p class="text-slate-600 text-xs leading-relaxed">
              {{ selectedModalStrand.careerPathways }}
            </p>
          </div>
        </div>

        <!-- Footer -->
        <div class="p-4 sm:p-5 bg-slate-50 border-t border-slate-200 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3">
          <button 
            type="button" 
            @click="closeStrandModal" 
            class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-200 transition cursor-pointer text-center"
          >
            Close
          </button>

          <button 
            type="button" 
            @click="enrollInStrand(selectedModalStrand)" 
            class="w-full sm:w-auto px-6 py-2.5 rounded-xl text-xs font-semibold bg-blue-900 hover:bg-blue-800 text-white shadow-xs transition flex items-center justify-center space-x-2 cursor-pointer"
          >
            <GraduationCap class="w-4 h-4 text-white" />
            <span>Apply for {{ selectedModalStrand.code || selectedModalStrand.name }}</span>
            <ArrowRight class="w-4 h-4 text-white" />
          </button>
        </div>

      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { 
  UserPlus, 
  GraduationCap, 
  ArrowRight, 
  X 
} from 'lucide-vue-next';
import { getRoleRouteName } from '../../router';
import api from '../../services/api';

const router = useRouter();
const route = useRoute();

// Modal State
const showStrandModal = ref(false);
const selectedModalStrand = ref(null);

// ============================================================================
// ACADEMIC DATA (JHS & SHS ONLY)
// ============================================================================
const selectedTrackCategory = ref('all');

const academicPrograms = [
  {
    id: 'jhs',
    code: 'JHS',
    name: 'Junior High School',
    category: 'JHS (Grades 7–10)',
    type: 'JHS',
    grade_level_id: 1,
    track_id: null,
    strand_id: null,
    description: 'Comprehensive DepEd K-12 and MATATAG curriculum delivering strong foundations in English, Math, Integrated Science, TLE, AP, and MAPEH.',
    detailedDescription: 'Our Junior High School department provides an exemplary foundational education emphasizing scientific inquiry, mathematical rigor, linguistic fluency, and values-centered formation. Students gain comprehensive preparation for all Senior High School tracks.',
    specializedSubjects: 'English, Mathematics, Integrated Science, Araling Panlipunan, Filipino, Edukasyon sa Pagpapakatao (EsP), Technology and Livelihood Education (TLE), and MAPEH.',
    careerPathways: 'Prepares learners for smooth transition to all Senior High School tracks (STEM, ABM, HUMSS, GAS, TVL-ICT, and TVL-HE).'
  },
  {
    id: 'stem',
    code: 'STEM',
    name: 'Science, Tech, Engineering & Math',
    category: 'SHS Academic Track',
    type: 'ACAD',
    grade_level_id: 5,
    track_id: 1,
    strand_id: 1,
    description: 'Rigorous preparatory program for aspiring Doctors, Engineers, Architects, Scientists, and Developers with Pre-Calculus, Physics, and Biology.',
    detailedDescription: 'The STEM strand is designed for inquisitive learners who aim to pioneer scientific discovery and technical innovation. With advanced hands-on laboratory experiments and capstone research, students excel in national scientific investigations and technological innovations.',
    specializedSubjects: 'General Physics 1 & 2, Pre-Calculus, Basic Calculus, General Chemistry 1 & 2, General Biology 1 & 2, Capstone Scientific Research, and Disaster Readiness.',
    careerPathways: 'Medical Sciences, Civil & Software Engineering, Architecture, Computer Systems, Biotechnology, Nursing, and Applied Sciences.'
  },
  {
    id: 'abm',
    code: 'ABM',
    name: 'Accountancy, Business & Management',
    category: 'SHS Academic Track',
    type: 'ACAD',
    grade_level_id: 5,
    track_id: 1,
    strand_id: 2,
    description: 'Specialized for future Certified Public Accountants, Financial Analysts, and Business Owners with Accounting and Finance principles.',
    detailedDescription: 'The ABM strand develops the financial acumen, entrepreneurial mindset, and executive leadership required in modern business operations. Features business enterprise simulation, financial modeling, and marketing campaigns.',
    specializedSubjects: 'Fundamentals of Accountancy, Business & Management (FABM 1 & 2), Business Finance, Principles of Marketing, Business Mathematics, and Organization & Management.',
    careerPathways: 'Corporate Accountancy, Business Administration, Banking & Financial Management, Marketing Management, Corporate Economics, and Entrepreneurship.'
  },
  {
    id: 'humss',
    code: 'HUMSS',
    name: 'Humanities & Social Sciences',
    category: 'SHS Academic Track',
    type: 'ACAD',
    grade_level_id: 5,
    track_id: 1,
    strand_id: 3,
    description: 'Designed for future Lawyers, Diplomats, Journalists, Educators, and Psychologists with Creative Writing, Politics, and Sociology.',
    detailedDescription: 'The HUMSS strand immerses students in the study of human behavior, societal structures, governance, and communication. Students master persuasive speech, critical debate, and community leadership.',
    specializedSubjects: 'Creative Writing, Philippine Politics & Governance, Trends & Critical Thinking in the 21st Century, Disciplines and Ideas in the Social Sciences (DISS), and Community Engagement.',
    careerPathways: 'Legal Studies, Public Governance, Foreign Affairs, Journalism, Media Arts, Social Work, and Educational Leadership.'
  },
  {
    id: 'gas',
    code: 'GAS',
    name: 'General Academic Strand',
    category: 'SHS Academic Track',
    type: 'ACAD',
    grade_level_id: 5,
    track_id: 1,
    strand_id: 4,
    description: 'Flexible multidisciplinary academic strand combining social sciences, management, and humanities electives for diverse Senior High specializations.',
    detailedDescription: 'The GAS strand offers customizable academic pathways for students who wish to explore multidisciplinary subjects across social sciences, management, and arts during Senior High School.',
    specializedSubjects: 'Humanities Electives, Applied Economics, Organization & Management, Philippine Politics, Disaster Readiness, Contemporary Philippine Arts, and Social Inquiry.',
    careerPathways: 'Liberal Arts, Public Administration, Criminology, Interdisciplinary Studies, Communications, and Custom Senior High Career Tracks.'
  },
  {
    id: 'tvl-ict',
    code: 'TVL-ICT',
    name: 'Information & Communications Tech',
    category: 'SHS TVL Track',
    type: 'TVL',
    grade_level_id: 5,
    track_id: 2,
    strand_id: 5,
    description: 'Hands-on technical training in Web Development, Python/C# Programming, Computer System Servicing (CSS), and Networking.',
    detailedDescription: 'The TVL-ICT strand empowers students with hands-on software development, IT systems servicing, and network infrastructure skills. Prepares graduates for direct TESDA NC II certifications and rapid tech industry employment.',
    specializedSubjects: 'Computer Systems Servicing (CSS NC II), Web Development & UI/UX Design, Java & Python Programming, Database Administration, and Industry Tech Immersion.',
    careerPathways: 'TESDA CSS NC II Certified, Junior Full-Stack Developer, Computer Systems Technician, IT Support Specialist, and Network Administrator.'
  },
  {
    id: 'tvl-he',
    code: 'TVL-HE',
    name: 'Home Economics (Culinary & Tourism)',
    category: 'SHS TVL Track',
    type: 'TVL',
    grade_level_id: 5,
    track_id: 2,
    strand_id: 6,
    description: 'Master Commercial Cooking, Bread & Pastry Production, Food & Beverage Services, and Hotel & Tourism operations.',
    detailedDescription: 'The TVL-HE strand focuses on professional culinary arts, baking, hotel management, and hospitality services. Students train in industrial kitchens and complete luxury hospitality apprenticeships.',
    specializedSubjects: 'Commercial Cookery NC II, Bread & Pastry Production (BPP NC II), Food & Beverage Services (FBS), Front Office Operations, and Tourism Promotion Services.',
    careerPathways: 'TESDA Cookery NC II, TESDA BPP NC II, Professional Chef de Partie, Pastry Artisan, Hotel & Cruise Hospitality Staff, and Restaurant Manager.'
  }
];

const filteredPrograms = computed(() => {
  if (selectedTrackCategory.value === 'all') return academicPrograms;
  return academicPrograms.filter(p => p.type === selectedTrackCategory.value);
});

const faqs = [
  {
    q: 'How does the Senior High School DepEd Voucher Program work at Biringan Science and Leadership Academy (BSLA)?',
    a: 'Public Junior High School completers automatically qualify for a 100% DepEd voucher subsidy value (₱17,500 – ₱22,500/year). Private school ESC grantees receive an 80% subsidy. The voucher is automatically calculated and deducted from the gross assessment by our Treasury department.'
  },
  {
    q: 'Can I submit my admission application and documents online without visiting campus?',
    a: 'Yes! Our web portal allows you to complete registration, choose your track/strand, and upload clear digital photos or scans of your PSA Birth Certificate and SF9 Report Card. You will receive real-time review updates directly on your applicant dashboard.'
  },
  {
    q: 'What payment methods are supported for tuition downpayments?',
    a: 'We accept both Walk-in Cashier payments (with automated priority queue tickets) and Online Payments (GCash, Maya, and Bank Direct Transfer). For online payments, simply submit your reference number and receipt screenshot on the portal for instant verification.'
  },
  {
    q: 'When will I receive my permanent Student Number and Student Portal access?',
    a: 'As soon as your initial minimum downpayment (₱3,000) or voucher verification is finalized by the Treasury Cashier, the system automatically creates your permanent Student ID (e.g. 2026-SHS-0001) and sets up your official student portal credentials.'
  }
];

const openStrandModal = (prog) => {
  selectedModalStrand.value = prog;
  showStrandModal.value = true;
};

const closeStrandModal = () => {
  showStrandModal.value = false;
  selectedModalStrand.value = null;
};

const enrollInStrand = async (strand) => {
  const strandSelection = {
    id: strand.id,
    code: strand.code,
    name: strand.name,
    grade_level_id: strand.grade_level_id,
    track_id: strand.track_id,
    strand_id: strand.strand_id,
    type: strand.type
  };

  sessionStorage.setItem('selected_enroll_strand', JSON.stringify(strandSelection));
  localStorage.setItem('selected_enroll_strand', JSON.stringify(strandSelection));

  closeStrandModal();

  const token = sessionStorage.getItem('sia_auth_token') || localStorage.getItem('sia_auth_token');
  const userJson = sessionStorage.getItem('sia_auth_user') || localStorage.getItem('sia_auth_user');

  if (token && userJson) {
    try {
      const user = JSON.parse(userJson);
      if (user.role_slug === 'applicant') {
        try {
          await api.updateApplication({
            grade_level_id: strand.grade_level_id,
            track_id: strand.track_id,
            strand_id: strand.strand_id
          });
        } catch (e) {
          // Continue to admission portal
        }
        router.push('/admission');
        return;
      }
    } catch (e) {
      // Continue to register
    }
  }

  router.push({
    path: '/register',
    query: {
      strand: strand.code,
      track: strand.type
    }
  });
};

onMounted(() => {
  const token = sessionStorage.getItem('sia_auth_token') || localStorage.getItem('sia_auth_token');
  const userJson = sessionStorage.getItem('sia_auth_user') || localStorage.getItem('sia_auth_user');
  if (token && userJson) {
    try {
      const user = JSON.parse(userJson);
      const target = getRoleRouteName(user.role_slug);
      if (target !== 'Home') {
        router.replace({ name: target });
      }
    } catch (e) {
      // Ignore parse error
    }
  }
});
</script>
