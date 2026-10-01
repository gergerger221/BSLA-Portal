# SIA Project 2: Conversation History & Architectural Roadmap

This document serves as the chronological history of user directives, architectural decisions, and implemented features for the **SIA High School Enrollment & Academic Management System**.

---

## 📜 Chronological Log of User Directives & Implementations

### Phase 1: Registration, Demographics & Section Allocation
1. **Student Demographics Prioritization**:
   - *User Directive*: *"student demographics muna ang maveveiw bago submitted credentials."*
   - *Implementation*: Refactored Registrar and Admission review modals so Student Demographics (personal background, LRN, contact info, address, guardian details) is the first active tab before Document Credentials.
2. **Re-enter Password Verification**:
   - *User Directive*: *"add a re-enter password"*
   - *Implementation*: Added password confirmation field with real-time match validation in `RegisterView.vue`.
3. **Voucher Category Conditionality**:
   - *User Directive*: *"Check mo yung nasa screen, dapat hindi lalabas yung voucher category hanggat hindi selected yung senior high grade."*
   - *Implementation*: Conditioned Voucher Category & Strand fields to only appear when Grade 11 or 12 is chosen.
4. **Section Allocation Standards**:
   - *User Directive*: *"All grades and strands should have 2 or more sections"*
   - *Implementation*: Configured at least 2 sections per grade level in JHS (Grades 7–10) and per strand in SHS (STEM, ABM, HUMSS, GAS, TVL-ICT, TVL-HE), resulting in 33 active sections with assigned advisers and rooms.

---

### Phase 2: Security Audit Logs & Admin UX Navigation
5. **Institutional Audit Trail Restoration**:
   - *User Directive*: *"sys audit & security logs is not showing"*
   - *Implementation*: Connected backend `AdminController.php` to fetch live audit events; enhanced `AdminDashboardView.vue` with real-time search, action badges, and refresh triggers.
6. **Super Admin Sidenav Dropdown Accordion**:
   - *User Directive*: *"check mo yung sidenav ng admin, medyo di user friendly... dropdown menu... add a smooth animation... if mag oopen ng another dropdown dapat magcoclose yung previous na naka open."*
   - *Implementation*: Implemented collapsible accordion tree in `PortalSidebar.vue` with CSS Grid height transitions (`grid-rows-[0fr]` ⇋ `grid-rows-[1fr]`, 300ms) and single-active auto-closing behavior.
7. **School Year Schema Fix**:
   - *User Directive*: *"Internal Server Error: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'curriculum_locked' in 'field list'"*
   - *Implementation*: Added `curriculum_locked`, `curriculum_declared_at`, and `curriculum_declared_by` to the `school_years` table and setup scripts.

---

### Phase 3: Philippine DepEd Curriculum & Deduplication
8. **DepEd Curriculum Research & Prerequisite Linkage**:
   - *User Directive*: *"Reseach about the curren curriculum in the philippines, add subjects and add prerequisites(if needed). Pwede mo rin galawin yung mga strands based on what you researched."*
   - *Implementation*: Seeded authentic DepEd K to 12 & MATATAG curriculum across Grades 7–10 (English, Math, Science, Filipino, AP, EsP, TLE, MAPEH) and Grades 11–12 (15 Core, 7 Applied, and Specialized subjects for STEM, ABM, HUMSS, GAS, TVL-ICT, TVL-HE) with 60 prerequisite linkages.
9. **Subject Catalog Deduplication**:
   - *User Directive*: *"may mga duplicates sa subjects"*
   - *Implementation*: Cleaned up 116 redundant rows, remapped foreign keys in `schedules` and `prerequisite_id`, resulting in 104 distinct subjects with 0 duplicates.

---

### Phase 4: Conflict-Free Realistic Timetables
10. **Conflict-Free Timetable Scheduling**:
    - *User Directive*: *"Sa time table manager, mag add ka nga ng schedule para sa lahat ng sections tapos dapat walang nag ooverlap na time like bawal mag turo yung teacher kung occupied na sya in that time."*
    - *Implementation*: Built constraint-satisfaction scheduling engine ensuring zero teacher double-booking, zero section collisions, and zero room overlaps.
11. **Realistic DepEd Daily Schedule Restructuring**:
    - *User Directive*: *"Paki ayos ng schedules based on philippine curriculum, parang di reasonable yung ibang sched sa system."*
    - *Implementation*: Eliminated clustered back-to-back duplicates and empty days. Structured a balanced 6-period daily schedule across Monday to Friday (`07:30 AM` to `02:50 PM`).
12. **Complete Dual-Semester Coverage**:
    - *User Directive*: *"yung ibang shs sections is walng 2nd semester and some of sections is wala talagang schedule"*
    - *Implementation*: Expanded teaching faculty to 40 members across departments, generating 1,680 conflict-free class schedules covering all 33 sections across both 1st Semester and 2nd Semester.
