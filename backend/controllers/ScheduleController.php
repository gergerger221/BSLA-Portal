<?php
// backend/controllers/ScheduleController.php
namespace App\Controllers;

use App\Config\Database;
use App\Config\Response;
use App\Helpers\Auth;
use PDO;

class ScheduleController {

    /**
     * Get schedules for a specific section and semester.
     */
    public function getSectionSchedule(): void {
        Auth::requireAuth();
        $db = Database::getConnection();

        $sectionId = (int)($_GET['section_id'] ?? 0);
        $semester = trim($_GET['semester'] ?? '');

        if (!$sectionId) {
            Response::error('Section ID is required.');
        }

        $secStmt = $db->prepare("
            SELECT s.*, gl.name as grade_level_name, gl.category as grade_category,
                   st.name as strand_name, st.code as strand_code
            FROM sections s
            JOIN grade_levels gl ON s.grade_level_id = gl.id
            LEFT JOIN strands st ON s.strand_id = st.id
            WHERE s.id = :id
        ");
        $secStmt->execute(['id' => $sectionId]);
        $section = $secStmt->fetch();

        if (!$section) {
            Response::error('Section not found.');
        }

        $query = "
            SELECT sch.*, sub.code as subject_code, sub.title as subject_title, sub.category as subject_category,
                   sub.units as subject_units, sub.lecture_hours, sub.lab_hours,
                   u.username as teacher_username, p.first_name as teacher_first, p.last_name as teacher_last
            FROM schedules sch
            JOIN subjects sub ON sch.subject_id = sub.id
            LEFT JOIN users u ON sch.teacher_id = u.id
            LEFT JOIN user_profiles p ON u.id = p.user_id
            WHERE sch.section_id = :sec_id AND sch.is_active = 1
        ";

        $params = ['sec_id' => $sectionId];
        if ($semester) {
            $query .= " AND (sch.semester = :sem OR sch.semester = 'Full Year')";
            $params['sem'] = $semester;
        }

        $query .= " ORDER BY FIELD(sch.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Mon-Fri', 'Mon-Wed-Fri', 'Tue-Thu'), sch.time_start ASC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $schedules = $stmt->fetchAll();

        // Also fetch candidate subjects from curriculum for this section
        $glId = $section['grade_level_id'];
        $strandId = $section['strand_id'];

        $subQuery = "
            SELECT s.id, s.code, s.title, s.category, s.semester, s.units, s.lecture_hours, s.lab_hours
            FROM subjects s
            WHERE s.grade_level_id = :gl_id AND s.is_active = 1
              AND (s.strand_id IS NULL OR s.strand_id = :strand_id)
        ";
        $subParams = ['gl_id' => $glId, 'strand_id' => $strandId];
        if ($semester) {
            $subQuery .= " AND (s.semester = :sem OR s.semester = 'Full Year')";
            $subParams['sem'] = $semester;
        }
        $subQuery .= " ORDER BY s.code ASC";

        $subStmt = $db->prepare($subQuery);
        $subStmt->execute($subParams);
        $availableSubjects = $subStmt->fetchAll();

        // Fetch teachers
        $teachers = $db->query("
            SELECT u.id, u.username, p.first_name, p.last_name, p.contact_number
            FROM users u
            JOIN roles r ON u.role_id = r.id
            JOIN user_profiles p ON u.id = p.user_id
            WHERE r.slug IN ('teacher', 'coordinator') AND u.status = 'Active'
            ORDER BY p.last_name ASC, p.first_name ASC
        ")->fetchAll();

        Response::success('Section schedule loaded', [
            'section'            => $section,
            'schedules'          => $schedules,
            'available_subjects' => $availableSubjects,
            'teachers'           => $teachers
        ]);
    }

    /**
     * Add or update a schedule item with intelligent conflict detection.
     */
    public function saveSchedule(): void {
        $user = Auth::requireRole(['coordinator', 'admin', 'scheduler']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $id = !empty($input['id']) ? (int)$input['id'] : null;
        $sectionId = (int)($input['section_id'] ?? 0);
        $subjectId = (int)($input['subject_id'] ?? 0);
        $semester = trim($input['semester'] ?? '1st Semester');
        $teacherId = !empty($input['teacher_id']) ? (int)$input['teacher_id'] : null;
        $dayOfWeek = trim($input['day_of_week'] ?? 'Mon-Wed-Fri');
        $timeStart = trim($input['time_start'] ?? '');
        $timeEnd = trim($input['time_end'] ?? '');
        $room = trim($input['room'] ?? '');

        if (!$sectionId || !$subjectId || !$timeStart || !$timeEnd) {
            Response::error('Section, Subject, Day, Start Time, and End Time are required.');
        }

        if ($timeStart >= $timeEnd) {
            Response::error('Start time must be earlier than end time.');
        }

        $db = Database::getConnection();

        // Helper to check day overlap
        $dayOverlapClause = self::getDayOverlapSql($dayOfWeek);

        // 1. CONFLICT CHECK: Section Schedule Overlap (Cannot have 2 subjects in same section at overlapping times)
        $secOverlapSql = "
            SELECT sch.*, sub.code as subject_code, sub.title as subject_title
            FROM schedules sch
            JOIN subjects sub ON sch.subject_id = sub.id
            WHERE sch.section_id = :sec_id
              AND sch.is_active = 1
              AND (:id_null OR sch.id != :id)
              AND ({$dayOverlapClause})
              AND (sch.time_start < :time_end AND sch.time_end > :time_start)
            LIMIT 1
        ";
        $secStmt = $db->prepare($secOverlapSql);
        $secStmt->execute([
            'sec_id'     => $sectionId,
            'id_null'    => $id ? 0 : 1,
            'id'         => $id ?: 0,
            'time_start' => $timeStart,
            'time_end'   => $timeEnd
        ]);
        $secConflict = $secStmt->fetch();
        if ($secConflict) {
            Response::error("Section Time Conflict: This section already has [{$secConflict['subject_code']}] {$secConflict['subject_title']} scheduled on {$secConflict['day_of_week']} ({$secConflict['time_start']} - {$secConflict['time_end']}).");
        }

        // 2. CONFLICT CHECK: Teacher Double-Booking
        if ($teacherId) {
            $tOverlapSql = "
                SELECT sch.*, sec.name as section_name, sub.code as subject_code
                FROM schedules sch
                JOIN sections sec ON sch.section_id = sec.id
                JOIN subjects sub ON sch.subject_id = sub.id
                WHERE sch.teacher_id = :teacher_id
                  AND sch.is_active = 1
                  AND (:id_null OR sch.id != :id)
                  AND ({$dayOverlapClause})
                  AND (sch.time_start < :time_end AND sch.time_end > :time_start)
                LIMIT 1
            ";
            $tStmt = $db->prepare($tOverlapSql);
            $tStmt->execute([
                'teacher_id' => $teacherId,
                'id_null'    => $id ? 0 : 1,
                'id'         => $id ?: 0,
                'time_start' => $timeStart,
                'time_end'   => $timeEnd
            ]);
            $tConflict = $tStmt->fetch();
            if ($tConflict) {
                Response::error("Teacher Double-Booking Conflict: The selected faculty teacher is already assigned to {$tConflict['section_name']} ([{$tConflict['subject_code']}]) at {$tConflict['time_start']} - {$tConflict['time_end']} on {$tConflict['day_of_week']}.");
            }
        }

        // 3. CONFLICT CHECK: Room Double-Booking
        if ($room) {
            $rOverlapSql = "
                SELECT sch.*, sec.name as section_name, sub.code as subject_code
                FROM schedules sch
                JOIN sections sec ON sch.section_id = sec.id
                JOIN subjects sub ON sch.subject_id = sub.id
                WHERE LOWER(sch.room) = LOWER(:room)
                  AND sch.is_active = 1
                  AND (:id_null OR sch.id != :id)
                  AND ({$dayOverlapClause})
                  AND (sch.time_start < :time_end AND sch.time_end > :time_start)
                LIMIT 1
            ";
            $rStmt = $db->prepare($rOverlapSql);
            $rStmt->execute([
                'room'       => $room,
                'id_null'    => $id ? 0 : 1,
                'id'         => $id ?: 0,
                'time_start' => $timeStart,
                'time_end'   => $timeEnd
            ]);
            $rConflict = $rStmt->fetch();
            if ($rConflict) {
                Response::error("Room Conflict: {$room} is already booked by {$rConflict['section_name']} ([{$rConflict['subject_code']}]) at {$rConflict['time_start']} - {$rConflict['time_end']} on {$rConflict['day_of_week']}.");
            }
        }

        // Perform Save / Update
        if ($id) {
            $stmt = $db->prepare("
                UPDATE schedules SET
                    section_id = :sec_id,
                    subject_id = :sub_id,
                    semester = :sem,
                    teacher_id = :teacher_id,
                    day_of_week = :day,
                    time_start = :time_start,
                    time_end = :time_end,
                    room = :room
                WHERE id = :id
            ");
            $stmt->execute([
                'sec_id'     => $sectionId,
                'sub_id'     => $subjectId,
                'sem'        => $semester,
                'teacher_id' => $teacherId,
                'day'        => $dayOfWeek,
                'time_start' => $timeStart,
                'time_end'   => $timeEnd,
                'room'       => $room,
                'id'         => $id
            ]);
            Auth::logAudit('SCHEDULE_UPDATED', "Updated schedule #{$id} for Section #{$sectionId}", $user['id']);
            Response::success('Schedule updated successfully.', ['id' => $id]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO schedules (section_id, subject_id, semester, teacher_id, day_of_week, time_start, time_end, room, is_active)
                VALUES (:sec_id, :sub_id, :sem, :teacher_id, :day, :time_start, :time_end, :room, 1)
            ");
            $stmt->execute([
                'sec_id'     => $sectionId,
                'sub_id'     => $subjectId,
                'sem'        => $semester,
                'teacher_id' => $teacherId,
                'day'        => $dayOfWeek,
                'time_start' => $timeStart,
                'time_end'   => $timeEnd,
                'room'       => $room
            ]);
            $newId = (int)$db->lastInsertId();
            Auth::logAudit('SCHEDULE_CREATED', "Created schedule #{$newId} for Section #{$sectionId}", $user['id']);
            Response::success('Schedule created successfully.', ['id' => $newId]);
        }
    }

    /**
     * Delete schedule item.
     */
    public function deleteSchedule(): void {
        $user = Auth::requireRole(['coordinator', 'admin', 'scheduler']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            Response::error('Valid Schedule ID is required.');
        }

        $db = Database::getConnection();
        $del = $db->prepare("DELETE FROM schedules WHERE id = :id");
        $del->execute(['id' => $id]);

        Auth::logAudit('SCHEDULE_DELETED', "Deleted schedule #{$id}", $user['id']);
        Response::success('Schedule item removed successfully.');
    }

    /**
     * Get School Events & Academic Calendar.
     */
    public function getEvents(): void {
        Auth::requireAuth();
        $db = Database::getConnection();

        $month = !empty($_GET['month']) ? (int)$_GET['month'] : null;
        $year = !empty($_GET['year']) ? (int)$_GET['year'] : null;
        $audience = trim($_GET['audience'] ?? '');
        $category = trim($_GET['category'] ?? '');

        $query = "
            SELECT e.*, u.username as creator_username, p.first_name as creator_first, p.last_name as creator_last
            FROM school_events e
            LEFT JOIN users u ON e.created_by = u.id
            LEFT JOIN user_profiles p ON u.id = p.user_id
            WHERE e.is_published = 1
        ";

        $params = [];
        if ($month) {
            $query .= " AND (MONTH(e.start_date) = :m1 OR MONTH(e.end_date) = :m2)";
            $params['m1'] = $month;
            $params['m2'] = $month;
        }
        if ($year) {
            $query .= " AND (YEAR(e.start_date) = :y1 OR YEAR(e.end_date) = :y2)";
            $params['y1'] = $year;
            $params['y2'] = $year;
        }
        if ($audience && $audience !== 'All') {
            $query .= " AND (e.target_audience = 'All' OR e.target_audience = :aud)";
            $params['aud'] = $audience;
        }
        if ($category && $category !== 'All') {
            $query .= " AND (e.event_category = :cat1 OR e.category = :cat2 OR e.event_category LIKE :cat3)";
            $params['cat1'] = $category;
            $params['cat2'] = $category;
            $params['cat3'] = '%' . $category . '%';
        }

        $query .= " ORDER BY e.start_date ASC, e.start_time ASC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $events = $stmt->fetchAll();

        // Also fetch upcoming milestones
        $upcoming = $db->query("
            SELECT * FROM school_events 
            WHERE (end_date >= CURDATE() OR start_date >= CURDATE()) AND is_published = 1 
            ORDER BY start_date ASC LIMIT 5
        ")->fetchAll();

        Response::success('School events loaded', [
            'events'   => $events,
            'upcoming' => $upcoming
        ]);
    }

    /**
     * Save or update a School Event.
     */
    public function saveEvent(): void {
        $user = Auth::requireRole(['coordinator', 'admin', 'scheduler']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $id = !empty($input['id']) ? (int)$input['id'] : null;
        $title = trim($input['title'] ?? '');
        $desc = trim($input['description'] ?? '');
        $category = trim($input['event_category'] ?? 'Academic');
        $startDate = trim($input['start_date'] ?? '');
        $endDate = trim($input['end_date'] ?? $startDate);
        $startTime = !empty($input['start_time']) ? trim($input['start_time']) : null;
        $endTime = !empty($input['end_time']) ? trim($input['end_time']) : null;
        $location = trim($input['location'] ?? '');
        $audience = trim($input['target_audience'] ?? 'All');
        $isPublished = isset($input['is_published']) ? (int)$input['is_published'] : 1;

        if (!$title || !$startDate) {
            Response::error('Event title and start date are required.');
        }

        if ($endDate < $startDate) {
            Response::error('End date cannot be earlier than start date.');
        }

        $db = Database::getConnection();
        $sy = $db->query("SELECT id FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
        $syId = $sy ? (int)$sy['id'] : 1;

        if ($id) {
            $stmt = $db->prepare("
                UPDATE school_events SET
                    title = :title,
                    description = :desc,
                    event_category = :cat,
                    start_date = :start_date,
                    end_date = :end_date,
                    start_time = :start_time,
                    end_time = :end_time,
                    location = :loc,
                    target_audience = :aud,
                    is_published = :is_pub
                WHERE id = :id
            ");
            $stmt->execute([
                'title'      => $title,
                'desc'       => $desc,
                'cat'        => $category,
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'start_time' => $startTime,
                'end_time'   => $endTime,
                'loc'        => $location,
                'aud'        => $audience,
                'is_pub'     => $isPublished,
                'id'         => $id
            ]);
            Auth::logAudit('EVENT_UPDATED', "Updated school event '{$title}' (ID #{$id})", $user['id']);
            Response::success('Event updated successfully.', ['id' => $id]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO school_events (school_year_id, title, description, event_category, start_date, end_date, start_time, end_time, location, target_audience, is_published, created_by)
                VALUES (:sy_id, :title, :desc, :cat, :start_date, :end_date, :start_time, :end_time, :loc, :aud, :is_pub, :created_by)
            ");
            $stmt->execute([
                'sy_id'      => $syId,
                'title'      => $title,
                'desc'       => $desc,
                'cat'        => $category,
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'start_time' => $startTime,
                'end_time'   => $endTime,
                'loc'        => $location,
                'aud'        => $audience,
                'is_pub'     => $isPublished,
                'created_by' => $user['id']
            ]);
            $newId = (int)$db->lastInsertId();
            Auth::logAudit('EVENT_CREATED', "Created school event '{$title}' (ID #{$newId})", $user['id']);
            Response::success('Event created successfully.', ['id' => $newId]);
        }
    }

    /**
     * Delete a School Event.
     */
    public function deleteEvent(): void {
        $user = Auth::requireRole(['coordinator', 'admin', 'scheduler']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            Response::error('Valid Event ID is required.');
        }

        $db = Database::getConnection();
        $del = $db->prepare("DELETE FROM school_events WHERE id = :id");
        $del->execute(['id' => $id]);

        Auth::logAudit('EVENT_DELETED', "Deleted school event #{$id}", $user['id']);
        Response::success('School event deleted successfully.');
    }

    /**
     * Helper to construct SQL condition for overlapping days of week.
     */
    private static function getDayOverlapSql(string $day): string {
        switch ($day) {
            case 'Mon-Fri':
                return "sch.day_of_week IN ('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Mon-Fri', 'Mon-Wed-Fri', 'Tue-Thu')";
            case 'Mon-Wed-Fri':
                return "sch.day_of_week IN ('Monday', 'Wednesday', 'Friday', 'Mon-Fri', 'Mon-Wed-Fri')";
            case 'Tue-Thu':
                return "sch.day_of_week IN ('Tuesday', 'Thursday', 'Mon-Fri', 'Tue-Thu')";
            case 'Monday':
                return "sch.day_of_week IN ('Monday', 'Mon-Fri', 'Mon-Wed-Fri')";
            case 'Tuesday':
                return "sch.day_of_week IN ('Tuesday', 'Mon-Fri', 'Tue-Thu')";
            case 'Wednesday':
                return "sch.day_of_week IN ('Wednesday', 'Mon-Fri', 'Mon-Wed-Fri')";
            case 'Thursday':
                return "sch.day_of_week IN ('Thursday', 'Mon-Fri', 'Tue-Thu')";
            case 'Friday':
                return "sch.day_of_week IN ('Friday', 'Mon-Fri', 'Mon-Wed-Fri')";
            case 'Saturday':
                return "sch.day_of_week IN ('Saturday')";
            default:
                return "sch.day_of_week = '{$day}' OR sch.day_of_week = 'Mon-Fri'";
        }
    }
}
