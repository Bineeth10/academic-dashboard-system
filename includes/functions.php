<?php
require_once 'config.php';

// Course functions
function getCourses($pdo) {
    $stmt = $pdo->query("SELECT * FROM courses ORDER BY course_name");
    return $stmt->fetchAll();
}

function getCourseById($pdo, $course_id) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    return $stmt->fetch();
}

// Assignment functions
function getAssignmentsByCourse($pdo, $course_id) {
    $stmt = $pdo->prepare("SELECT * FROM assignments WHERE course_id = ? ORDER BY created_at DESC");
    $stmt->execute([$course_id]);
    return $stmt->fetchAll();
}

function getAssignmentById($pdo, $assignment_id) {
    $stmt = $pdo->prepare("SELECT a.*, c.course_name FROM assignments a 
                          JOIN courses c ON a.course_id = c.id 
                          WHERE a.id = ?");
    $stmt->execute([$assignment_id]);
    return $stmt->fetch();
}

function getPublishedAssignments($pdo) {
    $stmt = $pdo->query("SELECT a.*, c.course_name FROM assignments a 
                         JOIN courses c ON a.course_id = c.id 
                         WHERE a.status = 'published' 
                         ORDER BY a.created_at DESC");
    return $stmt->fetchAll();
}

function createAssignment($pdo, $course_id, $title, $description) {
    $stmt = $pdo->prepare("INSERT INTO assignments (course_id, title, description, status) VALUES (?, ?, ?, 'draft')");
    $stmt->execute([$course_id, $title, $description]);
    return $pdo->lastInsertId();
}

function updateAssignmentStatus($pdo, $assignment_id, $status) {
    $stmt = $pdo->prepare("UPDATE assignments SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $assignment_id]);
}

function deleteAssignment($pdo, $assignment_id) {
    // Delete related questions first
    $stmt = $pdo->prepare("DELETE FROM questions WHERE assignment_id = ?");
    $stmt->execute([$assignment_id]);
    
    // Delete assignment
    $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ?");
    return $stmt->execute([$assignment_id]);
}

// Question functions
function getQuestionsByAssignment($pdo, $assignment_id) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE assignment_id = ? ORDER BY id");
    $stmt->execute([$assignment_id]);
    return $stmt->fetchAll();
}

function addQuestion($pdo, $assignment_id, $question_text) {
    $stmt = $pdo->prepare("INSERT INTO questions (assignment_id, question_text) VALUES (?, ?)");
    return $stmt->execute([$assignment_id, $question_text]);
}

function deleteQuestion($pdo, $question_id) {
    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
    return $stmt->execute([$question_id]);
}

// Student functions
function getStudents($pdo) {
    $stmt = $pdo->query("SELECT * FROM students ORDER BY name");
    return $stmt->fetchAll();
}

function getStudentById($pdo, $student_id) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    return $stmt->fetch();
}

// Submission functions
function submitAssignment($pdo, $assignment_id, $student_id, $submission_text) {
    // Check if student already submitted this assignment
    $stmt = $pdo->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?");
    $stmt->execute([$assignment_id, $student_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing submission and increment count
        $stmt = $pdo->prepare("UPDATE submissions SET submission_text = ?, submitted_at = NOW(), submission_count = submission_count + 1 WHERE assignment_id = ? AND student_id = ?");
        return $stmt->execute([$submission_text, $assignment_id, $student_id]);
    } else {
        // Create new submission
        $stmt = $pdo->prepare("INSERT INTO submissions (assignment_id, student_id, submission_text, submission_count) VALUES (?, ?, ?, 1)");
        return $stmt->execute([$assignment_id, $student_id, $submission_text]);
    }
}

function getSubmissionsByAssignment($pdo, $assignment_id) {
    $stmt = $pdo->prepare("SELECT s.*, st.name as student_name, v.status as verification_status, v.feedback 
                          FROM submissions s 
                          JOIN students st ON s.student_id = st.id 
                          LEFT JOIN verification v ON s.id = v.submission_id 
                          WHERE s.assignment_id = ? 
                          ORDER BY s.submitted_at DESC");
    $stmt->execute([$assignment_id]);
    return $stmt->fetchAll();
}

function getSubmissionById($pdo, $submission_id) {
    $stmt = $pdo->prepare("SELECT s.*, st.name as student_name, a.title as assignment_title 
                          FROM submissions s 
                          JOIN students st ON s.student_id = st.id 
                          JOIN assignments a ON s.assignment_id = a.id 
                          WHERE s.id = ?");
    $stmt->execute([$submission_id]);
    return $stmt->fetch();
}

// Verification functions
function verifySubmission($pdo, $submission_id, $status, $feedback = '') {
    // Check if verification already exists
    $stmt = $pdo->prepare("SELECT * FROM verification WHERE submission_id = ?");
    $stmt->execute([$submission_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing verification
        $stmt = $pdo->prepare("UPDATE verification SET status = ?, feedback = ?, verified_at = NOW() WHERE submission_id = ?");
        $result = $stmt->execute([$status, $feedback, $submission_id]);
    } else {
        // Create new verification
        $stmt = $pdo->prepare("INSERT INTO verification (submission_id, status, feedback) VALUES (?, ?, ?)");
        $result = $stmt->execute([$submission_id, $status, $feedback]);
    }
    
    // Send notification to student
    if ($result) {
        $submission = getSubmissionById($pdo, $submission_id);
        $message = "Your assignment '{$submission['assignment_title']}' has been {$status}.";
        if ($feedback) {
            $message .= " Feedback: {$feedback}";
        }
        addNotification($pdo, $submission['student_id'], $message);
    }
    
    return $result;
}

// Notification functions
function addNotification($pdo, $student_id, $message) {
    $stmt = $pdo->prepare("INSERT INTO notifications (student_id, message) VALUES (?, ?)");
    return $stmt->execute([$student_id, $message]);
}

function getNotificationsByStudent($pdo, $student_id) {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE student_id = ? ORDER BY created_at DESC");
    $stmt->execute([$student_id]);
    return $stmt->fetchAll();
}

// Statistics functions
function getSubmissionStats($pdo, $assignment_id) {
    $stmt = $pdo->prepare("SELECT 
                            s.student_id,
                            st.name as student_name,
                            s.submission_count,
                            COUNT(CASE WHEN v.status = 'verified' THEN 1 END) as verified_count,
                            COUNT(CASE WHEN v.status = 'rejected' THEN 1 END) as rejected_count,
                            COUNT(CASE WHEN v.status = 'returned' THEN 1 END) as returned_count
                          FROM submissions s 
                          JOIN students st ON s.student_id = st.id 
                          LEFT JOIN verification v ON s.id = v.submission_id 
                          WHERE s.assignment_id = ? 
                          GROUP BY s.student_id, st.name, s.submission_count");
    $stmt->execute([$assignment_id]);
    return $stmt->fetchAll();
}
?>
