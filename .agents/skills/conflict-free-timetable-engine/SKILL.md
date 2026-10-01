---
name: conflict-free-timetable-engine
description: >-
  Constraint-satisfaction scheduling engine for high schools. Enforces zero teacher
  double-booking, zero section collisions, zero room overlaps, and balanced Monday-to-Friday
  DepEd bell schedules for both 1st and 2nd Semesters.
---

# Conflict-Free Timetable Engine Skill

This skill documents the mathematical algorithms and constraint-satisfaction rules used to generate, validate, and manage realistic school schedules.

## 1. Constraint Satisfaction Rules

Every schedule entry `(section_id, subject_id, teacher_id, room, day_of_week, time_start, time_end, semester)` must satisfy:

1. **Teacher Constraint**:
   - `teacherOccupancy[teacher_id][day][time_slot] == false`
   - No faculty can teach multiple classes at overlapping times on the same day.
2. **Section Constraint**:
   - `sectionOccupancy[section_id][day][time_slot] == false`
   - No section can have multiple subjects scheduled at the same time.
3. **Room Constraint**:
   - `roomOccupancy[room][day][time_slot] == false`
   - No room/laboratory can host multiple class sections simultaneously.
4. **Subject Repetition Constraint**:
   - A single section cannot have the same subject twice on the same day (no back-to-back duplicate periods).
5. **Term Scope**:
   - JHS sections are marked `Full Year` to persist across both semesters.
   - SHS sections have distinct schedules generated for `1st Semester` and `2nd Semester`.

## 2. Standard DepEd Bell Schedule
- **Period 1**: 07:30:00 - 08:30:00
- **Period 2**: 08:30:00 - 09:30:00
- *(Morning Recess: 09:30:00 - 09:50:00)*
- **Period 3**: 09:50:00 - 10:50:00
- **Period 4**: 10:50:00 - 11:50:00
- *(Lunch Break: 11:50:00 - 12:50:00)*
- **Period 5**: 12:50:00 - 13:50:00
- **Period 6**: 13:50:00 - 14:50:00
- **Period 7**: 14:50:00 - 15:50:00

## 3. Conflict Verification Query
```sql
SELECT COUNT(*) FROM schedules s1
JOIN schedules s2 ON s1.teacher_id = s2.teacher_id AND s1.day_of_week = s2.day_of_week AND s1.id < s2.id
WHERE s1.is_active = 1 AND s2.is_active = 1
  AND (s1.semester = :sem OR s1.semester = 'Full Year') AND (s2.semester = :sem OR s2.semester = 'Full Year')
  AND (s1.time_start < s2.time_end AND s1.time_end > s2.time_start);
```
Result must always equal **0**.
