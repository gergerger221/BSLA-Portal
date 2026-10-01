<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Top Header & Actions -->
    <div class="no-print flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6 pb-5 border-b border-slate-200">
      <div>
        <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
          <CreditCard class="w-3.5 h-3.5 text-emerald-600" />
          <span>Treasury & Finance Management</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Enrollment Billing & Official Receipts</h1>
        <p class="text-xs text-slate-500 mt-0.5">Collect tuition downpayments, apply DepEd voucher subsidies, and issue Official Receipts.</p>
      </div>

      <div class="flex items-center space-x-2.5 shrink-0">
        <div class="hidden sm:flex items-center space-x-2 bg-emerald-50 text-emerald-800 border border-emerald-200 px-3.5 py-1.5 rounded-xl text-xs font-medium font-mono">
          <span>Assessments:</span>
          <strong class="text-emerald-900 font-bold">{{ assessments.length }}</strong>
          <span class="text-emerald-300">•</span>
          <span>Pending Online:</span>
          <strong :class="pendingOnlineCount > 0 ? 'text-amber-700 font-bold' : 'text-emerald-800'">{{ pendingOnlineCount }}</strong>
        </div>
        <button 
          @click="loadAssessments(); loadOnlinePayments(); loadFeeStructures();"
          class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-medium shadow-2xs transition flex items-center space-x-1.5 cursor-pointer"
        >
          <RefreshCw class="w-3.5 h-3.5" />
          <span>Refresh</span>
        </button>
      </div>
    </div>

    <!-- Alert Notification -->
    <div v-if="successMessage" class="no-print p-4 rounded-2xl bg-emerald-950/80 border border-emerald-500 text-emerald-300 text-xs mb-6 flex items-center justify-between">
      <span>{{ successMessage }}</span>
      <button @click="successMessage = ''" class="font-bold">✕</button>
    </div>

    <!-- TAB 1: BILLING ASSESSMENTS & CASHIER POS -->
    <div v-if="activeTab === 'assessments'" class="no-print bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
        <div class="relative w-full sm:w-80">
          <input 
            v-model="searchQuery" 
            @input="loadAssessments"
            type="text" 
            placeholder="Search by student name, Ass #, or Enr #..."
            class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500"
          />
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
        </div>

        <div class="flex items-center space-x-2 w-full sm:w-auto">
          <select v-model="filterStatus" @change="loadAssessments" class="px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white">
            <option value="">All Payment Statuses</option>
            <option value="Unpaid">Unpaid</option>
            <option value="Partially Paid">Partially Paid</option>
            <option value="Fully Paid">Fully Paid</option>
          </select>
          <button @click="loadAssessments" class="p-2 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-600">
            <RefreshCw class="w-4 h-4" />
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse min-w-[750px]">
          <thead>
            <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
              <th class="p-3.5">Ass # / Enr #</th>
              <th class="p-3.5">Student / Applicant</th>
              <th class="p-3.5">Grade & Strand</th>
              <th class="p-3.5">Voucher Subsidy</th>
              <th class="p-3.5">Net Payable</th>
              <th class="p-3.5">Total Paid</th>
              <th class="p-3.5">Status</th>
              <th class="p-3.5 text-right">Cashier Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="ass in assessments" :key="ass.id" class="hover:bg-slate-50 transition">
              <td class="p-3.5">
                <div class="font-bold font-mono text-slate-900">{{ ass.assessment_no }}</div>
                <div class="text-[11px] font-mono text-slate-400">{{ ass.enrollment_no }}</div>
              </td>
              <td class="p-3.5">
                <div class="font-bold text-slate-800">{{ ass.last_name }}, {{ ass.first_name }} {{ ass.middle_name || '' }}</div>
                <div v-if="ass.permanent_student_no" class="text-[11px] font-mono text-emerald-700 font-bold">
                  ID: {{ ass.permanent_student_no }}
                </div>
              </td>
              <td class="p-3.5">{{ ass.grade_level_name }} {{ ass.strand_code ? '(' + ass.strand_code + ')' : '' }}</td>
              <td class="p-3.5">
                <span v-if="ass.voucher_discount > 0" class="text-emerald-700 font-bold">
                  - ₱{{ Number(ass.voucher_discount).toLocaleString('en-US', {minimumFractionDigits: 2}) }}
                </span>
                <span v-else class="text-slate-400">None</span>
              </td>
              <td class="p-3.5 font-mono font-bold text-slate-900">
                ₱{{ Number(ass.net_payable).toLocaleString('en-US', {minimumFractionDigits: 2}) }}
              </td>
              <td class="p-3.5 font-mono font-bold text-emerald-600">
                ₱{{ Number(ass.total_paid).toLocaleString('en-US', {minimumFractionDigits: 2}) }}
              </td>
              <td class="p-3.5">
                <span :class="getStatusBadgeClass(ass.status)" class="px-2.5 py-1 rounded-full text-[10px] uppercase tracking-wider inline-flex items-center shadow-2xs whitespace-nowrap">
                  {{ ass.status }}
                </span>
              </td>
              <td class="p-3.5 text-right">
                <button 
                  v-if="ass.status === 'Payment Submitted – Awaiting Verification' || ass.online_submission_status === 'Pending Verification'"
                  @click="handleReviewFromBilling(ass)" 
                  class="inline-flex items-center justify-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 shadow-xs hover:shadow-sm transition whitespace-nowrap cursor-pointer ml-auto"
                >
                  <CreditCard class="w-3.5 h-3.5" />
                  <span>Verify PayMongo (₱{{ Number(ass.online_amount_submitted || ass.net_payable).toLocaleString('en-US', {minimumFractionDigits: 2}) }})</span>
                </button>
                <button 
                  v-else
                  @click="openPaymentModal(ass.id)" 
                  :class="ass.status === 'Fully Paid' || ass.status === 'Paid'
                    ? 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 hover:border-slate-300 shadow-2xs' 
                    : 'bg-[#0c2340] hover:bg-[#163a66] text-white shadow-xs hover:shadow-sm'"
                  class="inline-flex items-center justify-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer"
                >
                  <Printer v-if="ass.status === 'Fully Paid' || ass.status === 'Paid'" class="w-3.5 h-3.5 text-slate-400" />
                  <CreditCard v-else class="w-3.5 h-3.5 text-emerald-400" />
                  <span>{{ (ass.status === 'Fully Paid' || ass.status === 'Paid') ? 'View OR & History' : 'Process Payment' }}</span>
                </button>
              </td>
            </tr>
            <tr v-if="assessments.length === 0">
              <td colspan="8" class="p-8 text-center text-slate-400 text-xs">
                No assessments found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 2: FEE STRUCTURES CATALOG -->
    <div v-if="activeTab === 'fees'" class="no-print bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
      <div class="mb-4">
        <h2 class="text-base font-bold text-slate-800">DepEd Compliant Standard Fee Matrix</h2>
        <p class="text-xs text-slate-500">Configured tuition, miscellaneous, laboratory, and campus development fees for SY 2026-2027.</p>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse min-w-[650px]">
          <thead>
            <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
              <th class="p-3.5">Grade Level / Strand</th>
              <th class="p-3.5">Fee Category</th>
              <th class="p-3.5">Fee Description</th>
              <th class="p-3.5 text-right">Amount (PHP)</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="fee in feeStructuresList" :key="fee.id" class="hover:bg-slate-50 transition">
              <td class="p-3.5 font-semibold text-slate-800">
                {{ fee.grade_level_name }} {{ fee.strand_code ? '(' + fee.strand_code + ')' : '' }}
              </td>
              <td class="p-3.5">
                <span class="px-2 py-0.5 rounded bg-slate-100 font-bold text-[10px]">{{ fee.category_name }}</span>
              </td>
              <td class="p-3.5">{{ fee.name }}</td>
              <td class="p-3.5 text-right font-mono font-bold text-slate-900">
                ₱{{ Number(fee.amount).toLocaleString('en-US', {minimumFractionDigits: 2}) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 3: ONLINE PAYMENT VERIFICATION QUEUE -->
    <div v-if="activeTab === 'online_payments'" class="no-print bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-base font-bold text-slate-900">PayMongo & E-Wallet Verification Queue</h2>
          <p class="text-xs text-slate-500 mt-0.5">Verify online transaction reference numbers, validate merchant receipts, and release official enrollment.</p>
        </div>

        <div class="flex items-center space-x-2 w-full sm:w-auto">
          <div class="relative w-full sm:w-64">
            <input 
              v-model="onlineSearchQuery" 
              @input="loadOnlinePayments"
              type="text" 
              placeholder="Search by student, Ref #, App #..."
              class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500"
            />
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
          </div>

          <select v-model="onlineFilterStatus" @change="loadOnlinePayments" class="px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white">
            <option value="">All Statuses</option>
            <option value="Pending Verification">Pending Verification</option>
            <option value="Verified">Verified</option>
            <option value="Rejected">Rejected</option>
          </select>

          <button @click="loadOnlinePayments" class="p-2 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-600 cursor-pointer">
            <RefreshCw class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Table of Online Submissions -->
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse min-w-[750px]">
          <thead>
            <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
              <th class="p-3.5">Submission & Date</th>
              <th class="p-3.5">Enrollee Student</th>
              <th class="p-3.5">Program</th>
              <th class="p-3.5">Channel</th>
              <th class="p-3.5">Reference / Txn ID</th>
              <th class="p-3.5 text-right">Amount (PHP)</th>
              <th class="p-3.5 text-center">Receipt Proof</th>
              <th class="p-3.5">Status</th>
              <th class="p-3.5 text-right">Treasury Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="sub in onlinePayments" :key="sub.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="p-3.5">
                <div class="font-bold text-slate-900 font-mono">#SUB-{{ String(sub.id).padStart(4, '0') }}</div>
                <div class="text-[10px] text-slate-400 font-mono">{{ sub.created_at }}</div>
              </td>
              <td class="p-3.5">
                <div class="font-bold text-slate-900">{{ sub.last_name }}, {{ sub.first_name }} {{ sub.middle_name || '' }}</div>
                <div class="text-[11px] font-mono text-slate-500">App: {{ sub.application_no }}</div>
              </td>
              <td class="p-3.5">
                <div class="font-medium text-slate-800">{{ sub.grade_level_name }}</div>
                <div class="text-[10px] text-slate-500">{{ sub.strand_code ? '(' + sub.strand_code + ')' : '' }}</div>
              </td>
              <td class="p-3.5">
                <span :class="getChannelBadgeClass(sub.payment_channel)" class="px-2.5 py-1 rounded-lg text-[10px] tracking-wider uppercase inline-flex items-center">
                  {{ sub.payment_channel || 'PAYMONGO' }}
                </span>
              </td>
              <td class="p-3.5 font-mono font-bold text-slate-800 text-[11px]">
                {{ sub.reference_no }}
              </td>
              <td class="p-3.5 text-right font-mono font-bold text-emerald-700 text-sm">
                ₱{{ Number(sub.amount_submitted).toLocaleString('en-US', {minimumFractionDigits: 2}) }}
              </td>
              <td class="p-3.5 text-center">
                <button 
                  v-if="sub.receipt_file_path" 
                  @click="openReviewModal(sub)" 
                  class="px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200/80 font-semibold text-[11px] inline-flex items-center space-x-1.5 transition cursor-pointer shadow-2xs"
                >
                  <Eye class="w-3.5 h-3.5 text-indigo-500" />
                  <span>View Proof</span>
                </button>
                <span v-else class="text-slate-400 text-[11px] font-mono italic">No File</span>
              </td>
              <td class="p-3.5">
                <span :class="getVerificationBadgeClass(sub.status)" class="px-2.5 py-1 rounded-full text-[10px] tracking-wider uppercase inline-flex items-center space-x-1.5 shadow-2xs whitespace-nowrap">
                  <span v-if="sub.status === 'Pending Verification' || !sub.status" class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                  <span v-else-if="sub.status === 'Verified' || sub.status === 'Approved'" class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                  <span v-else-if="sub.status === 'Rejected'" class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                  <span>{{ sub.status || 'Pending Verification' }}</span>
                </span>
              </td>
              <td class="p-3.5 text-right">
                <button 
                  @click="openReviewModal(sub)"
                  :class="sub.status === 'Pending Verification' || !sub.status 
                    ? 'bg-[#0c2340] hover:bg-[#163a66] text-white shadow-xs hover:shadow-sm' 
                    : 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 hover:border-slate-300 shadow-2xs'"
                  class="inline-flex items-center justify-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer"
                >
                  <ShieldCheck v-if="sub.status === 'Pending Verification' || !sub.status" class="w-3.5 h-3.5 text-amber-400" />
                  <Eye v-else class="w-3.5 h-3.5 text-slate-400" />
                  <span>{{ (sub.status === 'Pending Verification' || !sub.status) ? 'Review & Verify' : 'View Details' }}</span>
                </button>
              </td>
            </tr>
            <tr v-if="onlinePayments.length === 0">
              <td colspan="9" class="p-8 text-center text-slate-400 text-xs">
                No online payment submissions found in queue.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- PAYMENT COLLECTION & OR ISSUANCE MODAL (CASHIER WALK-IN & BILLING) -->
    <div v-if="selectedAssessment" class="no-print fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6 sm:p-8 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
          <div>
            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
              {{ selectedAssessment.assessment_no }}
            </span>
            <h3 class="text-xl font-bold text-slate-900 mt-1">
              {{ selectedAssessment.first_name }} {{ selectedAssessment.last_name }}
            </h3>
          </div>
          <button @click="selectedAssessment = null" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold">✕</button>
        </div>

        <!-- Walk-in Cashier Ticket Banner (If scheduled) -->
        <div v-if="selectedAssessment.walkin_ticket_no || selectedAssessment.payment_ticket" class="p-3.5 rounded-2xl bg-indigo-50 border border-indigo-200 text-xs text-indigo-900 mb-4 flex items-center justify-between">
          <div>
            <span class="font-extrabold text-indigo-950 block">Walk-in Payment Ticket: {{ selectedAssessment.walkin_ticket_no || selectedAssessment.payment_ticket }}</span>
            <span class="text-[11px] text-indigo-700 font-mono">Scheduled: {{ selectedAssessment.walkin_scheduled_date || 'Appointment' }} • {{ selectedAssessment.walkin_time_slot || 'Morning Batch' }}</span>
          </div>
          <span class="px-2.5 py-1 rounded-full bg-indigo-100 text-indigo-800 font-bold text-[10px] uppercase border border-indigo-300">Window 1 / 2</span>
        </div>

        <!-- Assessment Financial Breakdown -->
        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-xs font-mono mb-6 space-y-1.5">
          <div class="flex justify-between text-slate-600">
            <span>Tuition Base:</span>
            <span>₱{{ Number(selectedAssessment.total_tuition || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
          </div>
          <div class="flex justify-between text-slate-600">
            <span>Misc & Lab Fees:</span>
            <span>₱{{ ((Number(selectedAssessment.total_miscellaneous) || 0) + (Number(selectedAssessment.total_laboratory) || 0) + (Number(selectedAssessment.total_other_fees) || 0)).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
          </div>
          <div class="flex justify-between text-emerald-700 font-bold">
            <span>DepEd Voucher / ESC Subsidy:</span>
            <span>- ₱{{ Number(selectedAssessment.voucher_discount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
          </div>
          <div class="flex justify-between font-bold text-slate-900 border-t border-slate-200 pt-1 text-sm">
            <span>Total Net Payable:</span>
            <span>₱{{ Number(selectedAssessment.net_payable || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
          </div>
          <div class="flex justify-between text-emerald-600 font-bold">
            <span>Total Paid to Date:</span>
            <span>₱{{ Number(selectedAssessment.total_paid || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
          </div>
          <div class="flex justify-between text-rose-600 font-bold border-t border-slate-200 pt-1">
            <span>Remaining Balance:</span>
            <span>₱{{ Number(selectedAssessment.remaining_balance || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
          </div>
        </div>

        <!-- Record Payment Form (if balance remains) -->
        <form v-if="selectedAssessment.remaining_balance > 0" @submit.prevent="openPaymentConfirmModal" class="p-5 rounded-2xl bg-emerald-50/70 border border-emerald-200 text-xs space-y-4 mb-6">
          <h4 class="font-bold text-emerald-950 uppercase">Accept Cashier Payment</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="block font-semibold text-slate-700">Amount to Pay (PHP) *</label>
                <button 
                  type="button" 
                  @click="paymentForm.amount_paid = Number(selectedAssessment.remaining_balance)" 
                  class="text-[10px] text-emerald-700 hover:text-emerald-900 font-bold underline cursor-pointer"
                >
                  Pay Full Balance
                </button>
              </div>
              <input 
                v-model.number="paymentForm.amount_paid" 
                type="number" 
                step="0.01" 
                min="1"
                :max="Number(selectedAssessment.remaining_balance)"
                @input="handleAmountInput"
                required 
                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-mono font-bold focus:ring-2 focus:ring-emerald-500"
              />
              <div class="flex items-center justify-between text-[10px] text-slate-500 mt-1">
                <span>Min: ₱{{ (Number(selectedAssessment.total_paid) > 0 ? 1 : Math.min(3000, Number(selectedAssessment.remaining_balance))).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
                <span class="font-bold text-rose-600">Max Limit: ₱{{ Number(selectedAssessment.remaining_balance).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
              </div>
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Payment Method *</label>
              <select v-model="paymentForm.payment_method" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-xs">
                <option value="Cash">Cash</option>
                <option value="Credit / Debit Card">Credit / Debit Card</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="GCash / Maya">GCash / Maya</option>
                <option value="Over-The-Counter">Over-The-Counter</option>
              </select>
            </div>
            <div v-if="paymentForm.payment_method !== 'Cash'" class="sm:col-span-2">
              <label class="block font-semibold text-slate-700 mb-1">
                {{ paymentForm.payment_method === 'Credit / Debit Card' ? 'Card Approval Code / POS Reference *' : 'Transaction Reference / Trace No. *' }}
              </label>
              <input 
                v-model="paymentForm.reference_no" 
                type="text" 
                :placeholder="paymentForm.payment_method === 'Credit / Debit Card' ? 'e.g. AUTH-948201 / VISA-4242' : 'e.g. REF-12345678'"
                required
                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-mono"
              />
            </div>
          </div>

          <div class="flex justify-end space-x-3 pt-2">
            <button 
              type="button" 
              @click="openPaymentConfirmModal"
              :disabled="isPaying"
              class="px-5 py-2.5 rounded-xl font-bold bg-[#0c2340] hover:bg-[#163a66] text-white shadow-xs transition flex items-center space-x-1.5 cursor-pointer"
            >
              <span v-if="isPaying" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span>Process Payment & Issue OR</span>
            </button>
          </div>
        </form>

        <!-- Previous Payment Receipts History -->
        <div>
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">Issued Official Receipts (OR)</h4>
          <div class="space-y-2">
            <div 
              v-for="p in selectedAssessment.payments" 
              :key="p.id"
              class="p-3 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-between text-xs font-mono"
            >
              <div>
                <strong class="text-slate-900">{{ p.or_number }}</strong>
                <div class="text-[11px] text-slate-500">{{ p.payment_date }} • {{ p.payment_method }}</div>
              </div>
              <div class="flex items-center space-x-3 text-right">
                <div>
                  <strong class="text-emerald-700 text-sm block">₱{{ Number(p.amount_paid).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</strong>
                  <div class="text-[10px] text-slate-400">Cashier: {{ p.received_by_user }}</div>
                </div>
                <button 
                  type="button" 
                  @click="openReceipt(p, selectedAssessment)" 
                  class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white shadow-sm transition flex items-center space-x-1"
                  title="Print Official Receipt"
                >
                  <Printer class="w-3.5 h-3.5" />
                  <span>Print OR</span>
                </button>
              </div>
            </div>
            <div v-if="!selectedAssessment.payments || selectedAssessment.payments.length === 0" class="text-xs text-slate-400 text-center py-4">
              No payments recorded yet.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- PAYMENT CONFIRMATION MODAL -->
    <div v-if="showPaymentConfirmModal" class="no-print fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150 text-slate-900">
        <div class="w-12 h-12 rounded-2xl bg-emerald-100 border border-emerald-200 text-emerald-600 flex items-center justify-center mx-auto mb-4 shadow-sm">
          <CreditCard class="w-6 h-6" />
        </div>
        <div class="text-center">
          <h3 class="text-base font-extrabold text-slate-900">Confirm Payment Collection</h3>
          <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
            Please verify the payment details below before finalizing the transaction and issuing the Official Receipt (OR).
          </p>
        </div>

        <div class="my-4 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-1.5 font-mono">
          <div class="flex justify-between">
            <span class="text-slate-500">Student:</span>
            <strong class="text-slate-900 font-sans">{{ selectedAssessment?.first_name }} {{ selectedAssessment?.last_name }}</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Assessment No:</span>
            <span class="font-bold text-slate-800">{{ selectedAssessment?.assessment_no }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Payment Channel:</span>
            <strong class="text-blue-700 font-sans">{{ paymentForm.payment_method }}</strong>
          </div>
          <div class="flex justify-between border-t border-slate-200 pt-1.5 text-sm">
            <span class="text-slate-600 font-sans font-bold">Amount to Collect:</span>
            <strong class="text-emerald-700 font-black">₱{{ Number(paymentForm.amount_paid).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</strong>
          </div>
        </div>

        <div class="flex items-center space-x-2.5 mt-6">
          <button 
            type="button" 
            @click="showPaymentConfirmModal = false" 
            class="w-1/2 py-2.5 px-4 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer"
          >
            Cancel
          </button>
          <button 
            type="button" 
            @click="executePayment" 
            :disabled="isPaying"
            class="w-1/2 py-2.5 px-4 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white shadow-md transition flex items-center justify-center space-x-1.5 cursor-pointer"
          >
            <span v-if="isPaying" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span v-else>Confirm & Issue OR</span>
          </button>
        </div>
      </div>
    </div>

    <!-- OFFICIAL RECEIPT PRINTABLE VIEW -->
    <div v-if="selectedReceipt" class="fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm overflow-y-auto p-4 sm:p-6 flex flex-col items-center">
      <!-- Print Action Bar (Hidden in Print) -->
      <div class="no-print w-full max-w-3xl flex items-center justify-between mb-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-lg">
        <button 
          @click="selectedReceipt = null" 
          class="px-4 py-2 rounded-xl text-slate-700 hover:bg-slate-100 font-semibold text-xs transition flex items-center space-x-2"
        >
          <ArrowLeft class="w-4 h-4" />
          <span>Back to Dashboard</span>
        </button>

        <div class="flex items-center space-x-3">
          <button 
            @click="printReceipt" 
            class="px-6 py-2.5 rounded-xl font-bold bg-emerald-600 hover:bg-emerald-500 text-white text-xs shadow-md transition flex items-center space-x-2"
          >
            <Printer class="w-4 h-4" />
            <span>Print Official Receipt</span>
          </button>
        </div>
      </div>

      <!-- Printable Document Sheet -->
      <div class="bg-white text-slate-900 rounded-3xl p-8 sm:p-10 border border-slate-200 shadow-2xl max-w-3xl w-full text-xs print:p-0 print:border-none print:shadow-none print:rounded-none">
        <!-- School Header -->
        <div class="text-center border-b-2 border-slate-900 pb-4 mb-6">
          <div class="flex items-center justify-center space-x-3 mb-2">
            <div class="w-10 h-10 rounded-xl bg-emerald-700 flex items-center justify-center text-white font-bold text-lg">
              SIA
            </div>
            <div>
              <h1 class="text-xl font-extrabold tracking-wide uppercase text-slate-900">SIA HIGH SCHOOL</h1>
              <p class="text-[11px] text-slate-600 font-medium">Junior High School & Senior High School Department</p>
            </div>
          </div>
          <p class="text-[10px] text-slate-500 font-mono">
            DepEd School ID: 405621 • Recognized K-12 Educational Institution • Finance & Treasury Division
          </p>
          <div class="inline-block mt-3 px-4 py-1 rounded-full bg-slate-900 text-white font-extrabold text-xs tracking-widest uppercase">
            OFFICIAL RECEIPT (DUPLICATE COPY)
          </div>
        </div>

        <!-- Receipt Metadata Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200 mb-6 font-mono text-[11px]">
          <div class="space-y-1.5">
            <div><span class="text-slate-500">OR Number:</span> <strong class="text-emerald-800 text-sm">{{ selectedReceipt.or_number }}</strong></div>
            <div><span class="text-slate-500">Assessment No:</span> <strong>{{ selectedReceipt.assessment_no }}</strong></div>
            <div><span class="text-slate-500">Student Name:</span> <strong class="text-slate-900 uppercase">{{ selectedReceipt.student_name }}</strong></div>
            <div><span class="text-slate-500">Student ID / LRN:</span> <strong>{{ selectedReceipt.permanent_student_no || 'Pending' }} / {{ selectedReceipt.lrn || 'N/A' }}</strong></div>
          </div>
          <div class="space-y-1.5 text-right sm:text-left">
            <div><span class="text-slate-500">Date Issued:</span> <strong>{{ selectedReceipt.payment_date }}</strong></div>
            <div><span class="text-slate-500">Grade & Section:</span> <strong>{{ selectedReceipt.grade_level_name }} - {{ selectedReceipt.section_name || 'Assigned' }}</strong></div>
            <div><span class="text-slate-500">Track & Strand:</span> <strong>{{ selectedReceipt.strand_name || 'JHS General' }}</strong></div>
            <div><span class="text-slate-500">Payment Method:</span> <strong class="uppercase text-emerald-800">{{ selectedReceipt.payment_method }}</strong></div>
          </div>
        </div>

        <!-- Particulars & Assessment Breakdown Table -->
        <div class="border border-slate-300 rounded-xl overflow-hidden mb-6">
          <table class="w-full text-xs text-left border-collapse">
            <thead>
              <tr class="bg-slate-100 text-slate-700 font-bold uppercase border-b border-slate-300">
                <th class="p-3">Payment Particulars / Assessment Items</th>
                <th class="p-3 text-right">Amount (PHP)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 font-mono">
              <tr>
                <td class="p-2.5">Tuition Fee Base</td>
                <td class="p-2.5 text-right">₱{{ Number(selectedReceipt.total_tuition || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</td>
              </tr>
              <tr>
                <td class="p-2.5">Miscellaneous & Laboratory Fees</td>
                <td class="p-2.5 text-right">₱{{ ((Number(selectedReceipt.total_miscellaneous) || 0) + (Number(selectedReceipt.total_laboratory) || 0)).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</td>
              </tr>
              <tr v-if="selectedReceipt.voucher_discount > 0" class="text-emerald-700">
                <td class="p-2.5">Less: DepEd Voucher Subsidy ({{ selectedReceipt.voucher_status }})</td>
                <td class="p-2.5 text-right">- ₱{{ Number(selectedReceipt.voucher_discount).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</td>
              </tr>
              <tr class="bg-slate-50 font-bold">
                <td class="p-2.5">Total Net Payable Assessment</td>
                <td class="p-2.5 text-right">₱{{ Number(selectedReceipt.net_payable || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</td>
              </tr>
              <tr class="bg-emerald-50 text-emerald-950 font-bold border-t-2 border-b-2 border-emerald-600">
                <td class="p-3 uppercase text-xs tracking-wider">AMOUNT PAID THIS RECEIPT</td>
                <td class="p-3 text-right text-sm">₱{{ Number(selectedReceipt.amount_paid).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</td>
              </tr>
              <tr>
                <td class="p-2.5 text-slate-600">Remaining Balance After This Payment</td>
                <td class="p-2.5 text-right font-bold text-slate-900">₱{{ Number(selectedReceipt.remaining_balance || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Signatures & Authority Section -->
        <div class="grid grid-cols-1 sm:grid-cols-2 print:grid-cols-2 gap-8 pt-6 border-t border-slate-200 mt-8">
          <div class="text-center">
            <div class="border-b border-slate-900 w-48 mx-auto mb-1"></div>
            <p class="font-bold text-slate-800 uppercase">{{ selectedReceipt.received_by_user || 'Cashier' }}</p>
            <p class="text-[10px] text-slate-500">Authorized Collecting Officer / Cashier</p>
          </div>
          <div class="text-center">
            <div class="border-b border-slate-900 w-48 mx-auto mb-1"></div>
            <p class="font-bold text-slate-800 uppercase">{{ selectedReceipt.student_name }}</p>
            <p class="text-[10px] text-slate-500">Student / Authorized Payor Signature</p>
          </div>
        </div>

        <!-- Official Notice -->
        <div class="mt-8 text-center text-[10px] text-slate-400 border-t border-slate-100 pt-4">
          <p>This Official Receipt is system-generated by SIA High School Information & Accounting System.</p>
          <p>Please present this receipt during enrollment consultations, ID processing, and credential releasing.</p>
        </div>
      </div>
    </div>

    <!-- ONLINE PAYMENT VERIFICATION REVIEW MODAL -->
    <div v-if="selectedOnlineSubmission" class="no-print fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[92vh] overflow-y-auto p-6 sm:p-8 shadow-2xl border border-slate-200 text-slate-900 animate-in fade-in zoom-in-95 duration-200">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-200 pb-4 mb-5">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-black shadow-md shadow-emerald-600/30">
              PM
            </div>
            <div>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" :class="getVerificationBadgeClass(selectedOnlineSubmission.status)">
                {{ selectedOnlineSubmission.status }}
              </span>
              <h3 class="text-base sm:text-lg font-black text-slate-900 tracking-tight mt-0.5">
                ONLINE PAYMENT VERIFICATION
              </h3>
            </div>
          </div>
          <button @click="selectedOnlineSubmission = null" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center font-bold text-slate-500 transition cursor-pointer">
            ✕
          </button>
        </div>

        <!-- Details Grid -->
        <div class="space-y-5 text-xs">
          <!-- Student & Assessment Matrix -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-slate-50 p-4 rounded-2xl border border-slate-200 font-mono text-[11px]">
            <div>
              <span class="block text-[10px] text-slate-500 uppercase font-sans font-bold">Applicant Name:</span>
              <strong class="text-slate-900 uppercase">{{ selectedOnlineSubmission.first_name }} {{ selectedOnlineSubmission.last_name }}</strong>
            </div>
            <div>
              <span class="block text-[10px] text-slate-500 uppercase font-sans font-bold">Application No:</span>
              <strong class="text-slate-900">{{ selectedOnlineSubmission.application_no }}</strong>
            </div>
            <div>
              <span class="block text-[10px] text-slate-500 uppercase font-sans font-bold">Assessment No:</span>
              <strong class="text-slate-900">{{ selectedOnlineSubmission.assessment_no }}</strong>
            </div>
            <div>
              <span class="block text-[10px] text-slate-500 uppercase font-sans font-bold">Grade & Strand:</span>
              <strong class="text-slate-900">{{ selectedOnlineSubmission.grade_level_name }} {{ selectedOnlineSubmission.strand_code ? '(' + selectedOnlineSubmission.strand_code + ')' : '' }}</strong>
            </div>
            <div>
              <span class="block text-[10px] text-slate-500 uppercase font-sans font-bold">Payment Method:</span>
              <strong class="text-emerald-700 font-bold">PayMongo ({{ selectedOnlineSubmission.payment_channel }})</strong>
            </div>
            <div>
              <span class="block text-[10px] text-slate-500 uppercase font-sans font-bold">Date Submitted:</span>
              <strong class="text-slate-900">{{ selectedOnlineSubmission.created_at }}</strong>
            </div>
          </div>

          <!-- Transaction Summary Banner -->
          <div class="p-4 rounded-2xl bg-emerald-50/80 border-2 border-emerald-300 space-y-2">
            <div class="flex items-center justify-between">
              <h4 class="font-black text-emerald-950 uppercase text-[11px] tracking-wider">Submitted PayMongo Details</h4>
              <span class="text-[10px] font-mono font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded border border-emerald-300">
                Channel: {{ selectedOnlineSubmission.payment_channel }}
              </span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 font-mono">
              <div class="bg-white p-3 rounded-xl border border-emerald-200">
                <span class="block text-[10px] text-slate-500 uppercase font-sans font-bold">Reference / Txn ID:</span>
                <span class="font-black text-slate-900 text-sm tracking-wide">
                  {{ selectedOnlineSubmission.reference_no }}
                </span>
              </div>
              <div class="bg-white p-3 rounded-xl border border-emerald-200">
                <span class="block text-[10px] text-slate-500 uppercase font-sans font-bold">Amount Paid (PHP):</span>
                <span class="font-black text-emerald-700 text-base">
                  ₱{{ Number(selectedOnlineSubmission.amount_submitted).toLocaleString('en-US', {minimumFractionDigits: 2}) }}
                </span>
              </div>
            </div>
          </div>

          <!-- PAYMENT PROOF / RECEIPT SECTION -->
          <div class="p-4 rounded-2xl border bg-slate-50 space-y-3" :class="selectedOnlineSubmission.receipt_file_path ? 'border-slate-200' : 'border-rose-300 bg-rose-50/50'">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <FileText class="w-4 h-4 text-slate-700" />
                <h4 class="font-black text-slate-900 uppercase text-[11px] tracking-wider">Payment Proof / Receipt</h4>
              </div>
              <span v-if="selectedOnlineSubmission.receipt_file_path" class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase">
                Attachment Present
              </span>
              <span v-else class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 text-[10px] font-bold uppercase">
                Proof Missing
              </span>
            </div>

            <!-- If proof is present -->
            <div v-if="selectedOnlineSubmission.receipt_file_path" class="space-y-3">
              <div class="flex items-center justify-between bg-white p-3 rounded-xl border border-slate-200">
                <div class="flex items-center space-x-2.5 overflow-hidden">
                  <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">
                    {{ selectedOnlineSubmission.receipt_file_path.toLowerCase().endsWith('.pdf') ? 'PDF' : 'IMG' }}
                  </div>
                  <div class="truncate">
                    <p class="font-bold text-slate-800 text-xs truncate">
                      {{ selectedOnlineSubmission.receipt_original_name || 'Payment Receipt Proof' }}
                    </p>
                    <p class="text-[10px] text-slate-400 font-mono">Format: {{ selectedOnlineSubmission.receipt_file_path.split('.').pop().toUpperCase() }}</p>
                  </div>
                </div>

                <button 
                  type="button" 
                  @click="openPreviewProof(selectedOnlineSubmission.receipt_file_path, selectedOnlineSubmission.receipt_original_name)" 
                  class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center space-x-1.5 shadow-sm transition cursor-pointer shrink-0"
                >
                  <Eye class="w-3.5 h-3.5" />
                  <span>View Uploaded Receipt</span>
                </button>
              </div>

              <!-- Inline Visual Preview Box -->
              <div class="max-h-56 overflow-auto rounded-xl border border-slate-200 bg-white p-2 flex items-center justify-center">
                <iframe 
                  v-if="selectedOnlineSubmission.receipt_file_path.toLowerCase().endsWith('.pdf')" 
                  :src="getFileUrl(selectedOnlineSubmission.receipt_file_path)" 
                  class="w-full h-48 rounded border border-slate-200"
                ></iframe>
                <img 
                  v-else 
                  :src="getFileUrl(selectedOnlineSubmission.receipt_file_path)" 
                  alt="Receipt Proof Preview" 
                  @click="openPreviewProof(selectedOnlineSubmission.receipt_file_path, selectedOnlineSubmission.receipt_original_name)"
                  class="max-h-48 max-w-full rounded object-contain cursor-pointer hover:opacity-95 transition"
                />
              </div>
            </div>

            <!-- If proof is missing -->
            <div v-else class="p-3.5 rounded-xl bg-rose-100/90 border border-rose-300 text-rose-900 text-xs flex items-start space-x-2">
              <AlertTriangle class="w-4 h-4 text-rose-700 shrink-0 mt-0.5" />
              <div>
                <p class="font-bold">Payment proof is missing. Please review the submission.</p>
                <p class="text-[11px] text-rose-800 mt-0.5">
                  Treasury policy strictly requires an uploaded payment receipt screenshot or PDF before verification can proceed.
                </p>
              </div>
            </div>
          </div>

          <!-- TREASURY VERIFICATION CHECKLIST (MANDATORY BEFORE APPROVAL) -->
          <div v-if="selectedOnlineSubmission.status === 'Pending Verification'" class="p-4 rounded-2xl bg-slate-900 text-white border border-slate-800 space-y-3 shadow-inner">
            <div class="flex items-center justify-between">
              <span class="font-extrabold text-[11px] uppercase tracking-wider text-amber-400 flex items-center space-x-1.5">
                <ShieldCheck class="w-4 h-4 text-amber-400" />
                <span>Treasury Verification Checklist</span>
              </span>
              <button 
                type="button" 
                @click="checkAllVerifications"
                class="text-[10px] text-emerald-400 hover:text-emerald-300 font-bold underline cursor-pointer"
              >
                Select All Checklist Items
              </button>
            </div>
            <div class="space-y-2 text-xs">
              <label class="flex items-center space-x-2.5 cursor-pointer text-slate-200 hover:text-white transition">
                <input type="checkbox" v-model="verificationChecklist.refChecked" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer" />
                <span>PayMongo Reference/Transaction Number checked against merchant statement</span>
              </label>
              <label class="flex items-center space-x-2.5 cursor-pointer text-slate-200 hover:text-white transition">
                <input type="checkbox" v-model="verificationChecklist.amountChecked" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer" />
                <span>Amount Paid checked (matches required assessment downpayment)</span>
              </label>
              <label class="flex items-center space-x-2.5 cursor-pointer text-slate-200 hover:text-white transition">
                <input type="checkbox" v-model="verificationChecklist.dateChecked" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer" />
                <span>Payment Date checked & confirmed current</span>
              </label>
              <label class="flex items-center space-x-2.5 cursor-pointer text-slate-200 hover:text-white transition">
                <input type="checkbox" v-model="verificationChecklist.proofChecked" :disabled="!selectedOnlineSubmission.receipt_file_path" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 disabled:opacity-30 cursor-pointer" />
                <span>Payment Receipt/Proof checked & verified clear</span>
              </label>
              <label class="flex items-center space-x-2.5 cursor-pointer text-slate-200 hover:text-white transition">
                <input type="checkbox" v-model="verificationChecklist.matchChecked" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer" />
                <span>Payment details match the submitted transaction & enrollee record</span>
              </label>
            </div>
          </div>

          <!-- If already verified, show Official Receipt info -->
          <div v-if="selectedOnlineSubmission.status === 'Verified'" class="p-3.5 rounded-2xl bg-emerald-100 text-emerald-900 border border-emerald-300 text-xs font-mono">
            <span class="font-bold block mb-0.5 font-sans">Official Receipt Generated: {{ selectedOnlineSubmission.or_number }}</span>
            <p class="text-[11px] font-sans">Verified at {{ selectedOnlineSubmission.verified_at }} by {{ selectedOnlineSubmission.verified_by_username || 'Treasury Staff' }}. Student is Officially Enrolled.</p>
          </div>

          <!-- If already rejected, show Rejection Remarks -->
          <div v-if="selectedOnlineSubmission.status === 'Rejected'" class="p-3.5 rounded-2xl bg-rose-100 text-rose-900 border border-rose-300 text-xs">
            <span class="font-bold block mb-0.5">Rejected / Flagged Remarks:</span>
            <p class="text-[11px]">{{ selectedOnlineSubmission.rejection_reason }}</p>
          </div>

          <!-- Inline Error / Notice Banner in Review Modal -->
          <div v-if="checklistErrorMessage" class="p-3.5 rounded-2xl bg-amber-50 border-2 border-amber-400 text-amber-950 text-xs flex items-center space-x-2 animate-in fade-in duration-150">
            <AlertTriangle class="w-4 h-4 text-amber-600 shrink-0" />
            <span class="font-bold">{{ checklistErrorMessage }}</span>
          </div>

          <div v-if="reviewModalError" class="p-3.5 rounded-2xl bg-rose-50 border-2 border-rose-300 text-rose-950 text-xs flex items-center space-x-2 animate-in fade-in duration-150">
            <AlertTriangle class="w-4 h-4 text-rose-600 shrink-0" />
            <span class="font-bold">{{ reviewModalError }}</span>
          </div>

          <!-- Rejection Reason Input Box (Shows when clicking Reject) -->
          <div v-if="showRejectInput" class="p-4 rounded-2xl bg-rose-50 border-2 border-rose-300 space-y-2 animate-in fade-in duration-150">
            <label class="block font-bold text-rose-900">Specify Reason for Rejection / Review *</label>
            <textarea 
              v-model="rejectionReason" 
              rows="3" 
              placeholder="e.g. Transaction ID not found in PayMongo records or receipt screenshot is unreadable. Please upload a clear copy."
              class="w-full p-2.5 rounded-xl border border-rose-300 text-xs focus:ring-2 focus:ring-rose-500 bg-white"
            ></textarea>
            <div class="flex justify-end space-x-2">
              <button 
                type="button" 
                @click="showRejectInput = false" 
                class="px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 font-bold hover:bg-slate-100 cursor-pointer"
              >
                Cancel
              </button>
              <button 
                type="button" 
                @click="confirmRejectOnlinePayment" 
                :disabled="isVerifying || !rejectionReason.trim()"
                class="px-4 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-bold disabled:opacity-50 cursor-pointer"
              >
                Submit Rejection
              </button>
            </div>
          </div>

          <!-- Action Buttons for Pending Verification -->
          <div v-if="selectedOnlineSubmission.status === 'Pending Verification' && !showRejectInput" class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button 
              type="button" 
              @click="showRejectInput = true" 
              class="w-full sm:w-auto px-4 py-2.5 rounded-xl font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-300 transition flex items-center justify-center space-x-1.5 cursor-pointer"
            >
              <AlertTriangle class="w-4 h-4" />
              <span>Reject Payment</span>
            </button>

            <button 
              type="button" 
              @click="approveOnlinePayment" 
              :disabled="isVerifying || !selectedOnlineSubmission.receipt_file_path"
              class="w-full sm:w-auto px-6 py-2.5 rounded-xl font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-600/30 transition flex items-center justify-center space-x-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="isVerifying" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span v-else class="flex items-center space-x-2">
                <CheckCircle class="w-4 h-4" />
                <span>Verify Payment</span>
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ONLINE PAYMENT APPROVE CONFIRMATION MODAL (CUSTOM MODERN DIALOG) -->
    <div v-if="showOnlineApproveConfirmModal && selectedOnlineSubmission" class="no-print fixed inset-0 z-[70] bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150 text-slate-900">
        <div class="w-14 h-14 rounded-2xl bg-emerald-100 border border-emerald-200 text-emerald-600 flex items-center justify-center mx-auto mb-4 shadow-sm">
          <CheckCircle class="w-7 h-7" />
        </div>
        <div class="text-center">
          <h3 class="text-lg font-black text-slate-900">Confirm Payment Verification</h3>
          <p class="text-xs text-slate-500 mt-1 leading-relaxed">
            Verify this online transaction and officially enroll the student into their assigned section.
          </p>
        </div>

        <div class="my-4 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-2 font-mono">
          <div class="flex justify-between">
            <span class="text-slate-500 font-sans">Enrollee Student:</span>
            <strong class="text-slate-900 font-sans uppercase">{{ selectedOnlineSubmission.first_name }} {{ selectedOnlineSubmission.last_name }}</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-sans">Application No:</span>
            <strong class="text-slate-800">{{ selectedOnlineSubmission.application_no }}</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-sans">Channel & Ref No:</span>
            <strong class="text-emerald-800">{{ selectedOnlineSubmission.payment_channel }} • {{ selectedOnlineSubmission.reference_no }}</strong>
          </div>
          <div class="flex justify-between border-t border-slate-200 pt-2 text-sm">
            <span class="text-slate-700 font-sans font-bold">Verified Amount:</span>
            <strong class="text-emerald-700 font-black">₱{{ Number(selectedOnlineSubmission.amount_submitted).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</strong>
          </div>
        </div>

        <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-[11px] text-emerald-900 space-y-1 mb-5">
          <div class="font-bold flex items-center space-x-1 text-emerald-800">
            <ShieldCheck class="w-3.5 h-3.5" />
            <span>Automatic Post-Verification Actions:</span>
          </div>
          <ul class="list-disc list-inside space-y-0.5 text-[10px] text-emerald-800/90 pl-1">
            <li>Generate Official Receipt (OR-2026-XXXXXX)</li>
            <li>Assign Permanent Student ID & create login account</li>
            <li>Change status to <strong>Officially Enrolled</strong></li>
            <li>Unlock Step 5 (COR & Schedule) in Student Portal</li>
          </ul>
        </div>

        <div class="flex items-center space-x-2.5">
          <button 
            type="button" 
            @click="showOnlineApproveConfirmModal = false" 
            class="w-1/2 py-2.5 px-4 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer"
          >
            Cancel
          </button>
          <button 
            type="button" 
            @click="executeApproveOnlinePayment" 
            :disabled="isVerifying"
            class="w-1/2 py-2.5 px-4 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white shadow-lg shadow-emerald-600/30 transition flex items-center justify-center space-x-1.5 cursor-pointer"
          >
            <span v-if="isVerifying" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span v-else>Confirm & Verify →</span>
          </button>
        </div>
      </div>
    </div>

    <!-- PAYMENT VERIFICATION SUCCESS DIALOG POPUP (REPLACES PLAIN TOAST) -->
    <div v-if="verifiedSuccessModalData" class="no-print fixed inset-0 z-[80] bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-200 text-slate-900 text-center">
        <div class="w-16 h-16 rounded-full bg-emerald-100 border-2 border-emerald-300 text-emerald-600 flex items-center justify-center mx-auto mb-4 shadow-lg shadow-emerald-500/20">
          <CheckCircle class="w-9 h-9" />
        </div>
        
        <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-black uppercase tracking-widest inline-block mb-2">
          Payment Verified & Approved
        </span>
        <h3 class="text-xl sm:text-2xl font-black text-slate-900">
          Student Officially Enrolled!
        </h3>
        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
          The transaction has been approved. The Official Receipt and Student Account are now active.
        </p>

        <!-- Details Card -->
        <div class="my-5 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-mono text-left space-y-2">
          <div class="flex justify-between items-center bg-white p-2.5 rounded-xl border border-slate-200">
            <span class="text-slate-500 font-sans text-[11px]">Official Receipt No:</span>
            <strong class="text-emerald-700 font-bold text-sm tracking-wide">{{ verifiedSuccessModalData.or_number }}</strong>
          </div>
          <div class="flex justify-between items-center bg-white p-2.5 rounded-xl border border-slate-200">
            <span class="text-slate-500 font-sans text-[11px]">Permanent Student ID:</span>
            <strong class="text-blue-700 font-bold text-sm tracking-wide">{{ verifiedSuccessModalData.student_no }}</strong>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] pt-1">
            <div>
              <span class="text-slate-400 font-sans block text-[10px]">Student Name:</span>
              <strong class="text-slate-800 font-sans uppercase">{{ verifiedSuccessModalData.student_name }}</strong>
            </div>
            <div>
              <span class="text-slate-400 font-sans block text-[10px]">Amount Settled:</span>
              <strong class="text-emerald-700 font-bold">₱{{ Number(verifiedSuccessModalData.amount_paid).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</strong>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-2">
          <button 
            type="button" 
            @click="openReceiptFromSuccessModal" 
            class="w-full py-3 px-4 rounded-xl font-bold bg-emerald-600 hover:bg-emerald-500 text-white text-xs shadow-lg shadow-emerald-600/30 transition flex items-center justify-center space-x-2 cursor-pointer"
          >
            <Printer class="w-4 h-4" />
            <span>View & Print Official Receipt (OR)</span>
          </button>
          
          <button 
            type="button" 
            @click="verifiedSuccessModalData = null" 
            class="w-full py-2.5 px-4 rounded-xl font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs transition cursor-pointer"
          >
            Done / Return to Queue
          </button>
        </div>
      </div>
    </div>

    <!-- DEDICATED FULLSCREEN PAYMENT PROOF VIEWER MODAL -->
    <div v-if="previewProofDoc" class="no-print fixed inset-0 z-[60] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-4xl w-full max-h-[92vh] flex flex-col shadow-2xl overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
        <!-- Modal Header -->
        <div class="p-4 sm:px-6 border-b border-slate-200 flex items-center justify-between bg-slate-900 text-white">
          <div class="flex items-center space-x-3">
            <FileText class="w-5 h-5 text-emerald-400" />
            <div>
              <h3 class="font-bold text-sm text-white">Payment Receipt Proof Preview</h3>
              <p class="text-[11px] text-slate-400 font-mono">{{ previewProofDoc.original_filename || 'Uploaded Receipt' }}</p>
            </div>
          </div>
          <div class="flex items-center space-x-2">
            <a 
              :href="getFileUrl(previewProofDoc.file_path)" 
              target="_blank" 
              download
              class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center space-x-1.5 transition"
            >
              <Download class="w-3.5 h-3.5" />
              <span>Download</span>
            </a>
            <button 
              @click="previewProofDoc = null" 
              class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center font-bold transition cursor-pointer"
            >
              ✕
            </button>
          </div>
        </div>

        <!-- Modal Body -->
        <div class="flex-1 p-4 sm:p-6 overflow-y-auto bg-slate-100 flex items-center justify-center min-h-[420px]">
          <iframe 
            v-if="previewProofDoc.file_path.toLowerCase().endsWith('.pdf')" 
            :src="getFileUrl(previewProofDoc.file_path)" 
            class="w-full h-[70vh] rounded-xl border border-slate-300 bg-white shadow-inner"
          ></iframe>
          <div v-else class="max-h-[70vh] overflow-auto flex items-center justify-center">
            <img 
              :src="getFileUrl(previewProofDoc.file_path)" 
              alt="Payment Receipt Proof" 
              class="max-w-full max-h-[68vh] rounded-xl shadow-lg object-contain border border-slate-300 bg-white"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { 
  Search, RefreshCw, Printer, ArrowLeft, CheckCircle, AlertTriangle, Eye, Clock, 
  CreditCard, FileText, Download, ShieldCheck 
} from 'lucide-vue-next';
import api, { getFileUrl } from '../../services/api';

const route = useRoute();
const activeTab = ref('assessments');

watch(() => route.query.tab, (newTab) => {
  if (newTab) {
    if (newTab === 'online_payments' || newTab === 'online-payments') {
      activeTab.value = 'online_payments';
    } else if (newTab === 'fees' || newTab === 'fee-structures') {
      activeTab.value = 'fees';
    } else if (newTab === 'assessments') {
      activeTab.value = 'assessments';
    }
  }
}, { immediate: true });

const assessments = ref([]);
const feeStructuresList = ref([]);
const onlinePayments = ref([]);
const onlineSearchQuery = ref('');
const onlineFilterStatus = ref('');
const selectedAssessment = ref(null);
const selectedReceipt = ref(null);
const selectedOnlineSubmission = ref(null);
const previewProofDoc = ref(null);
const showRejectInput = ref(false);
const rejectionReason = ref('');
const isVerifying = ref(false);
const searchQuery = ref('');
const filterStatus = ref('');
const isPaying = ref(false);
const successMessage = ref('');
const showPaymentConfirmModal = ref(false);
const showOnlineApproveConfirmModal = ref(false);
const verifiedSuccessModalData = ref(null);
const checklistErrorMessage = ref('');
const reviewModalError = ref('');

const verificationChecklist = ref({
  refChecked: false,
  amountChecked: false,
  dateChecked: false,
  proofChecked: false,
  matchChecked: false
});

const checkAllVerifications = () => {
  checklistErrorMessage.value = '';
  verificationChecklist.value = {
    refChecked: true,
    amountChecked: true,
    dateChecked: true,
    proofChecked: !!selectedOnlineSubmission.value?.receipt_file_path,
    matchChecked: true
  };
};

const openPreviewProof = (file_path, original_filename) => {
  previewProofDoc.value = { file_path, original_filename };
};

const pendingOnlineCount = computed(() => {
  return onlinePayments.value.filter(p => p.status === 'Pending Verification').length;
});

const paymentForm = ref({
  amount_paid: 3000,
  payment_method: 'Cash',
  reference_no: '',
  remarks: 'Enrollment Initial Payment'
});

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'Fully Paid':
    case 'Paid':
      return 'bg-emerald-50 text-emerald-700 border border-emerald-200/90 font-bold';
    case 'Partially Paid':
      return 'bg-blue-50 text-blue-700 border border-blue-200/90 font-bold';
    case 'Payment Submitted – Awaiting Verification':
    case 'Pending Verification':
      return 'bg-amber-50 text-amber-700 border border-amber-200/90 font-bold';
    case 'Unpaid':
      return 'bg-slate-100 text-slate-700 border border-slate-200/90 font-bold';
    default:
      return 'bg-slate-100 text-slate-600 border border-slate-200/90 font-medium';
  }
};

const getVerificationBadgeClass = (status) => {
  const s = String(status || 'Pending Verification').trim();
  switch (s) {
    case 'Pending Verification':
      return 'bg-amber-50 text-amber-700 border border-amber-300/80 font-bold';
    case 'Verified':
    case 'Approved':
      return 'bg-emerald-50 text-emerald-700 border border-emerald-300/80 font-bold';
    case 'Rejected':
      return 'bg-rose-50 text-rose-700 border border-rose-300/80 font-bold';
    default:
      return 'bg-slate-100 text-slate-700 border border-slate-200 font-semibold';
  }
};

const getChannelBadgeClass = (channel) => {
  const c = String(channel || '').toUpperCase();
  if (c.includes('GCASH')) {
    return 'bg-sky-50 text-sky-700 border border-sky-200/90 font-bold';
  } else if (c.includes('MAYA')) {
    return 'bg-emerald-50 text-emerald-700 border border-emerald-200/90 font-bold';
  } else if (c.includes('PAYMONGO')) {
    return 'bg-indigo-50 text-indigo-700 border border-indigo-200/90 font-bold';
  } else if (c.includes('BANK')) {
    return 'bg-purple-50 text-purple-700 border border-purple-200/90 font-bold';
  }
  return 'bg-slate-100 text-slate-700 border border-slate-200 font-bold';
};


const loadAssessments = async () => {
  try {
    let params = '';
    if (filterStatus.value) params += `status=${filterStatus.value}&`;
    if (searchQuery.value) params += `search=${encodeURIComponent(searchQuery.value)}`;
    const res = await api.getAssessments(params);
    assessments.value = res.data;
  } catch (err) {
    console.error('Failed to load assessments:', err);
  }
};

const loadOnlinePayments = async () => {
  try {
    let params = '';
    if (onlineFilterStatus.value) params += `status=${onlineFilterStatus.value}&`;
    if (onlineSearchQuery.value) params += `search=${encodeURIComponent(onlineSearchQuery.value)}`;
    const res = await api.getOnlinePaymentVerifications(params);
    onlinePayments.value = res.data;
  } catch (err) {
    console.error('Failed to load online payment queue:', err);
  }
};

const loadFeeStructures = async () => {
  try {
    const res = await api.getFeeStructures();
    feeStructuresList.value = res.data.fees;
  } catch (err) {
    console.error('Failed to load fee structures:', err);
  }
};

const openPaymentModal = async (id) => {
  try {
    const res = await api.getAssessmentDetails(id);
    selectedAssessment.value = res.data;
    const rem = Number(res.data.remaining_balance || 0);
    paymentForm.value.amount_paid = rem > 0 ? Math.min(3000, rem) : 0;
  } catch (err) {
    console.error('Failed to load assessment details:', err);
  }
};

const handleAmountInput = () => {
  if (!selectedAssessment.value) return;
  const max = Number(selectedAssessment.value.remaining_balance || 0);
  if (paymentForm.value.amount_paid > max) {
    paymentForm.value.amount_paid = max;
  }
};

const handleReviewFromBilling = async (ass) => {
  if (onlinePayments.value.length === 0) {
    await loadOnlinePayments();
  }
  const match = onlinePayments.value.find(p => p.assessment_id === ass.id || p.id === ass.online_submission_id);
  if (match) {
    openReviewModal(match);
  } else {
    activeTab.value = 'online_payments';
    await loadOnlinePayments();
  }
};

const openReviewModal = (sub) => {
  selectedOnlineSubmission.value = sub;
  showRejectInput.value = false;
  rejectionReason.value = '';
  checklistErrorMessage.value = '';
  reviewModalError.value = '';
  showOnlineApproveConfirmModal.value = false;
  verificationChecklist.value = {
    refChecked: false,
    amountChecked: false,
    dateChecked: false,
    proofChecked: !!sub.receipt_file_path,
    matchChecked: false
  };
};

const approveOnlinePayment = () => {
  if (!selectedOnlineSubmission.value) return;
  checklistErrorMessage.value = '';
  reviewModalError.value = '';

  // 1. Strict Payment Proof Validation
  if (!selectedOnlineSubmission.value.receipt_file_path) {
    checklistErrorMessage.value = 'Payment proof is missing. Treasury policy strictly requires an uploaded payment receipt proof before verification.';
    return;
  }

  // 2. Checklist Validation
  const chk = verificationChecklist.value;
  if (!chk.refChecked || !chk.amountChecked || !chk.dateChecked || !chk.proofChecked || !chk.matchChecked) {
    checklistErrorMessage.value = 'Please check and confirm all 5 items in the Treasury Verification Checklist before completing verification.';
    return;
  }

  // Open custom confirmation modal instead of browser confirm()
  showOnlineApproveConfirmModal.value = true;
};

const executeApproveOnlinePayment = async () => {
  if (!selectedOnlineSubmission.value) return;
  isVerifying.value = true;
  reviewModalError.value = '';

  try {
    const subCopy = { ...selectedOnlineSubmission.value };
    const res = await api.verifyOnlinePayment({
      submission_id: subCopy.id,
      action: 'approve'
    });

    showOnlineApproveConfirmModal.value = false;
    selectedOnlineSubmission.value = null;

    // Trigger celebratory popup dialog
    verifiedSuccessModalData.value = {
      ...res.data,
      student_name: `${subCopy.first_name} ${subCopy.last_name}`,
      amount_paid: subCopy.amount_submitted,
      grade_level_name: subCopy.grade_level_name,
      strand_code: subCopy.strand_code,
      assessment_id: subCopy.assessment_id
    };

    await loadOnlinePayments();
    await loadAssessments();
  } catch (err) {
    showOnlineApproveConfirmModal.value = false;
    reviewModalError.value = err.message || 'Payment verification failed.';
  } finally {
    isVerifying.value = false;
  }
};

const openReceiptFromSuccessModal = async () => {
  if (!verifiedSuccessModalData.value) return;
  const assId = verifiedSuccessModalData.value.assessment_id;
  try {
    const res = await api.getAssessmentDetails(assId);
    const ass = res.data;
    const latestPay = ass.payments?.[0] || {
      or_number: verifiedSuccessModalData.value.or_number,
      payment_date: new Date().toISOString().split('T')[0],
      payment_method: 'Online PayMongo',
      reference_no: verifiedSuccessModalData.value.student_no,
      amount_paid: verifiedSuccessModalData.value.amount_paid,
      received_by_user: 'Treasury Officer'
    };
    verifiedSuccessModalData.value = null;
    openReceipt(latestPay, ass);
  } catch (err) {
    console.error('Failed to load receipt details:', err);
  }
};

const confirmRejectOnlinePayment = async () => {
  if (!selectedOnlineSubmission.value || !rejectionReason.value.trim()) return;

  isVerifying.value = true;
  reviewModalError.value = '';
  try {
    await api.verifyOnlinePayment({
      submission_id: selectedOnlineSubmission.value.id,
      action: 'reject',
      rejection_reason: rejectionReason.value.trim()
    });

    successMessage.value = 'Payment flagged / rejected. The applicant has been notified to provide corrected payment details.';
    selectedOnlineSubmission.value = null;
    showRejectInput.value = false;
    await loadOnlinePayments();
    await loadAssessments();
  } catch (err) {
    reviewModalError.value = err.message || 'Payment rejection failed.';
  } finally {
    isVerifying.value = false;
  }
};

const openReceipt = (payment, assessment) => {
  selectedReceipt.value = {
    or_number: payment.or_number,
    payment_date: payment.payment_date,
    payment_method: payment.payment_method,
    reference_no: payment.reference_no,
    amount_paid: payment.amount_paid,
    received_by_user: payment.received_by_user,
    assessment_no: assessment.assessment_no,
    enrollment_no: assessment.enrollment_no,
    student_name: `${assessment.first_name} ${assessment.middle_name || ''} ${assessment.last_name}`.trim(),
    permanent_student_no: assessment.permanent_student_no,
    lrn: assessment.lrn,
    grade_level_name: assessment.grade_level_name,
    section_name: assessment.section_name,
    strand_name: assessment.strand_name,
    voucher_status: assessment.voucher_status,
    total_tuition: assessment.total_tuition,
    total_miscellaneous: assessment.total_miscellaneous,
    total_laboratory: assessment.total_laboratory,
    voucher_discount: assessment.voucher_discount,
    net_payable: assessment.net_payable,
    remaining_balance: assessment.remaining_balance
  };
};

const printReceipt = () => {
  window.print();
};

const openPaymentConfirmModal = () => {
  if (!selectedAssessment.value) return;

  const max = Number(selectedAssessment.value.remaining_balance || 0);
  if (Number(paymentForm.value.amount_paid) > max) {
    paymentForm.value.amount_paid = max;
    return;
  }
  if (Number(paymentForm.value.amount_paid) <= 0) {
    return;
  }

  showPaymentConfirmModal.value = true;
};

const executePayment = async () => {
  if (!selectedAssessment.value) return;

  isPaying.value = true;
  try {
    const res = await api.processPayment({
      assessment_id: selectedAssessment.value.id,
      amount_paid: paymentForm.value.amount_paid,
      payment_method: paymentForm.value.payment_method,
      reference_no: paymentForm.value.reference_no,
      remarks: paymentForm.value.remarks
    });

    showPaymentConfirmModal.value = false;
    const currentAss = { ...selectedAssessment.value };
    await openPaymentModal(currentAss.id);
    await loadAssessments();

    // Trigger celebratory success modal
    verifiedSuccessModalData.value = {
      ...res.data,
      student_name: `${currentAss.first_name} ${currentAss.last_name}`,
      amount_paid: paymentForm.value.amount_paid,
      grade_level_name: currentAss.grade_level_name,
      strand_code: currentAss.strand_code,
      assessment_id: currentAss.id
    };
  } catch (err) {
    successMessage.value = err.message || 'Payment processing failed.';
  } finally {
    isPaying.value = false;
  }
};

onMounted(() => {
  loadAssessments();
  loadFeeStructures();
  loadOnlinePayments();
});
</script>
