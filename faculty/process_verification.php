<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

// Get form data
$submission_id = isset($_POST['submission_id']) ? (int)$_POST['submission_id'] : 0;
$assignment_id = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
$course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
$status = isset($_POST['status']) ? sanitize($_POST['status']) : '';
$feedback = isset($_POST['feedback']) ? sanitize($_POST['feedback']) : '';

// Validate required fields
if (!$submission_id || !$assignment_id || !$course_id || !$status) {
    header("Location: verify_submissions.php?assignment_id=$assignment_id&course_id=$course_id&error=missing_data");
    exit();
}

// Validate status
if (!in_array($status, ['verified', 'rejected', 'returned'])) {
    header("Location: verify_submissions.php?assignment_id=$assignment_id&course_id=$course_id&error=invalid_status");
    exit();
}

try {
    // Verify the submission
    if (verifySubmission($pdo, $submission_id, $status, $feedback)) {
        $success_message = "Submission has been " . $status . " successfully!";
        header("Location: verify_submissions.php?assignment_id=$assignment_id&course_id=$course_id&success=" . urlencode($success_message));
    } else {
        header("Location: verify_submissions.php?assignment_id=$assignment_id&course_id=$course_id&error=verification_failed");
    }
} catch (Exception $e) {
    header("Location: verify_submissions.php?assignment_id=$assignment_id&course_id=$course_id&error=" . urlencode($e->getMessage()));
}

exit();
?>
