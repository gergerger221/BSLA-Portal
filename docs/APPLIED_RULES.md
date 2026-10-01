# SIA Project 2: Applied Rules & Standards

This document consolidates all the rules, guidelines, and constraints enforced across the codebase.

---

## 📋 1. Planning & Quality Assurance Rules
- **Mandatory Planning Before Execution**: All complex changes must have an implementation plan written and approved before touching source code.
- **Root-Cause Investigation**: Do not patch symptoms; investigate underlying database constraints, missing columns, or missing state.
- **Build Verification**: Every modification must pass `npm run build` with 0 compilation errors.

---

## 🎨 2. UI/UX & Navigation Rules
- **Modal Design Standards**:
  - Backdrop blur, clean close buttons, structured scrollable containers (`max-h-[85vh]`).
  - Student demographics tab is prioritized before submitted document credentials.
- **Sidebar Accordion Trees**:
  - Collapsible portal groups with CSS Grid height animation (`grid-rows-[0fr]` ⇋ `grid-rows-[1fr]`, 300ms transition).
  - Single-active exclusivity: opening a dropdown automatically closes other open dropdowns.
  - Rotating chevron indicators and glowing active route indicators.

---

## 📚 3. DepEd Curriculum & Academic Rules
- **Junior High School (Grades 7–10)**:
  - 8 core learning areas per grade with sequential prerequisites:
    - English 7 ➔ 8 ➔ 9 ➔ 10
    - Math 7 ➔ 8 ➔ 9 ➔ 10
    - Science 7 ➔ 8 ➔ 9 ➔ 10
    - Filipino 7 ➔ 8 ➔ 9 ➔ 10
    - AP 7 ➔ 8 ➔ 9 ➔ 10
    - EsP 7 ➔ 8 ➔ 9 ➔ 10
    - TLE 7 ➔ 8 ➔ 9 ➔ 10
    - MAPEH 7 ➔ 8 ➔ 9 ➔ 10
- **Senior High School (Grades 11–12)**:
  - 15 Core Subjects + 7 Applied Subjects + Specialized Track Subjects (STEM, ABM, HUMSS, GAS, TVL-ICT, TVL-HE).
  - Strict prerequisite dependency enforcement (e.g. Pre-Calculus ➔ Basic Calculus; FABM 1 ➔ FABM 2; CSS Modules 1 ➔ 2 ➔ 3 ➔ 4).

---

## ⏰ 4. Timetable Scheduling Constraints
- **Zero Teacher Overlap**: No teacher can be assigned to more than 1 class during overlapping hours.
- **Zero Section Overlap**: A section cannot have overlapping periods.
- **Zero Room Overlap**: Classrooms and laboratories must be uniquely occupied per time block.
- **Balanced Mon-Fri DepEd Schedule**: 6 periods daily (`07:30 AM` to `02:50 PM`) with 0 empty days and 0 duplicate subjects per day.
- **Dual-Semester Completeness**: All sections must have full schedules in both 1st Semester and 2nd Semester.

---

## 🔒 5. Database & Security Rules
- **Uniqueness & Deduplication**: Subjects must be unique by `(code, grade_level_id, IFNULL(strand_id, 0))`.
- **Institutional Audit Trail**: Every sensitive operation must be logged in `audit_logs`.
- **Relational Integrity**: Foreign key dependencies (`schedules`, `subjects.prerequisite_id`, `enrollments`) must always be remapped before cleanup.
