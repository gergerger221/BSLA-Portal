---
name: deped-curriculum-management
description: >-
  Expert guide for managing Philippine DepEd K to 12 and MATATAG curriculum,
  subject classifications (Core, Applied, Specialized), Junior High progressions,
  Senior High academic tracks/strands, and prerequisite dependency resolver graphs.
---

# DepEd Curriculum Management Skill

This skill provides procedures and reference standards for handling Philippine basic education curriculum data, academic levels, tracks, strands, and prerequisite dependency chains.

## 1. Grade Level & Track Classifications

- **Junior High School (JHS)**:
  - Grade 7, Grade 8, Grade 9, Grade 10
  - Academic subjects: English, Mathematics, Science, Filipino, Araling Panlipunan, EsP, TLE, MAPEH
  - Full-year academic courses with sequential annual prerequisites.

- **Senior High School (SHS)**:
  - Grade 11 and Grade 12
  - Divided into 1st Semester and 2nd Semester.
  - **15 Common Core Subjects**: Oral Communication, Reading and Writing, Komunikasyon, Pagbasa at Pagsusuri, 21st Century Literature, Contemporary Arts, Media and Information Literacy, General Mathematics, Statistics & Probability, Earth & Life Science, Physical Science, Introduction to Philosophy, Physical Education and Health 1–4, Personal Development, UCSP.
  - **7 Applied Track Subjects**: EAPP, Practical Research 1 (Qualitative), Practical Research 2 (Quantitative), 3Is (Inquiries, Investigations & Immersion), Empowerment Technologies, Entrepreneurship, Filipino sa Piling Larang.
  - **Official Strands**:
    - **STEM**: Pre-Calculus, Basic Calculus, Gen Physics 1 & 2, Gen Biology 1 & 2, Gen Chemistry 1 & 2, STEM Capstone.
    - **ABM**: Business Math, Org & Management, FABM 1 & 2, Principles of Marketing, Business Finance, Applied Economics, Business Ethics, Enterprise Simulation.
    - **HUMSS**: Creative Writing, Creative Nonfiction, DISS, DIASS, Philippine Politics, Trends & Networks, Community Engagement (CESC), World Religions.
    - **GAS**: Humanities 1 & 2, Social Sciences, Organization & Management, Applied Economics, Electives 1 & 2, GAS Culminating.
    - **TVL-ICT**: CSS NC II Modules 1–4, Technical Drafting NC II, Web Development Foundations, Work Immersion.
    - **TVL-HE**: Bread & Pastry NC II, Cookery NC II Parts 1 & 2, Food and Beverage Services NC II, Tourism Promotion NC II, Work Immersion.

## 2. Prerequisite Linkage Resolution

Prerequisites are maintained using self-referencing foreign keys:
```sql
ALTER TABLE subjects ADD CONSTRAINT fk_subject_prerequisite
FOREIGN KEY (prerequisite_id) REFERENCES subjects(id) ON DELETE SET NULL;
```
When querying subjects for enrollment eligibility or display, join on the prerequisite:
```sql
SELECT sub.*, pre.code as prerequisite_code, pre.title as prerequisite_title
FROM subjects sub
LEFT JOIN subjects pre ON sub.prerequisite_id = pre.id;
```
