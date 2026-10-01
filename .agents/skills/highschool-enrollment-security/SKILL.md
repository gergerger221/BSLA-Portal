---
name: highschool-enrollment-security
description: >-
  Institutional security, transaction queuing, DepEd voucher discount calculations,
  document verification workflows, and audit trail logging for enrollment systems.
---

# High School Enrollment Security & Operations Skill

This skill documents the institutional security workflows, financial assessment pipelines, and administrative logging in the SIA High School Enrollment Management System.

## 1. Admission & Voucher Categorization

- **Voucher Classifications**:
  - `Public JHS Completer (100% Voucher)`: 100% discount on base tuition.
  - `Private JHS ESC Grantee (80% Voucher)`: 80% discount on base tuition.
  - `Non-Voucher / Self-Paying`: No subsidy.
- **Grade-Level Conditionality**:
  - Senior High School (Grades 11 & 12) applicants must select a Voucher category and a Strand.
  - Junior High School (Grades 7–10) applicants bypass the voucher category and strand selection.

## 2. Institutional Audit Trail Logging

Every sensitive database change must log to `audit_logs`:
- Table structure: `(id, user_id, action, details, ip_address, created_at)`
- Standard actions:
  - `SYSTEM_INIT`: Initial database and schema setup.
  - `APPLICANT_REGISTER`: New student online application.
  - `ACTIVE_SCHOOL_YEAR_CHANGED`: Academic year switch.
  - `SECTION_CREATED`: Section creation and adviser allocation.
  - `CURRICULUM_LOCK_TOGGLED`: Curriculum locking and publishing.
  - `ONLINE_PAYMENT_VERIFIED`: GCash/Bank online downpayment verification.
  - `PAYMENT_PROCESSED`: Cash cashier receipt issuance.
  - `DOCUMENT_VERIFIED`: PSA/SF9 admission document verification.
  - `APPROVE_AND_QUEUE`: Application approval, section assignment, and queue number generation.
  - `LOGIN`: User authentication event.
