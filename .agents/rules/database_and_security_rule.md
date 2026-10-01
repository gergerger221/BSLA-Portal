---
title: Database & Security Rules
description: Data integrity, foreign keys, deduplication, and institutional audit logging constraints.
---

# Database & Security Rules

## 1. Data Integrity & Deduplication
- **Subject Uniqueness**: Subjects are identified by `(code, grade_level_id, IFNULL(strand_id, 0))`. Never insert duplicate subjects.
- **Foreign Key Remapping**: Before deleting or updating any records (e.g. subjects, teachers, sections), always remap dependent tables (`schedules.subject_id`, `subjects.prerequisite_id`, `enrollments`, `student_grades`).

## 2. Institutional Security & Audit Trail
- **Audit Logging**: Every sensitive action (`APPLICANT_REGISTER`, `DOCUMENT_VERIFIED`, `APPROVE_AND_QUEUE`, `PAYMENT_PROCESSED`, `CURRICULUM_LOCK_TOGGLED`, `SECTION_CREATED`) must log `user_id`, `action`, `details`, `ip_address`, and `created_at` in the `audit_logs` table.
- **Live Search & Filter**: The Admin Dashboard audit trail must support instantaneous live text search and colored category badges.

## 3. UI/UX & Accordion Sidenav
- **Dropdown Sidenav**: Portal navigation items with sub-routes must use collapsible accordion menus with smooth height transitions (`grid-rows-[0fr]` to `grid-rows-[1fr]`, 300ms duration) and single-open auto-closing behavior.
