<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
include '../includes/header.php';

// Get assignment ID and student ID from URL
$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 1;

if (!$assignment_id) {
    header('Location: dashboard.php');
    exit();
}

// Get assignment details
$assignment = getAssignmentById($pdo, $assignment_id);
if (!$assignment || $assignment['status'] !== 'published') {
    header('Location: dashboard.php');
    exit();
}

// Get student details
$student = getStudentById($pdo, $student_id);
if (!$student) {
    header('Location: dashboard.php');
    exit();
}

// Check if assignment is published to this student
$stmt = $pdo->prepare("SELECT * FROM assignment_publications WHERE assignment_id = ? AND (student_id = ? OR student_id IS NULL)");
$stmt->execute([$assignment_id, $student_id]);
$publication = $stmt->fetch();

if (!$publication) {
    header('Location: dashboard.php');
    exit();
}

// Get questions for this assignment
$questions = getQuestionsByAssignment($pdo, $assignment_id);

// Get existing submission if any
$stmt = $pdo->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?");
$stmt->execute([$assignment_id, $student_id]);
$existing_submission = $stmt->fetch();

// Get verification status if submission exists
$verification = null;
if ($existing_submission) {
    $stmt = $pdo->prepare("SELECT * FROM verification WHERE submission_id = ?");
    $stmt->execute([$existing_submission['id']]);
    $verification = $stmt->fetch();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_assignment'])) {
        $submission_text = sanitize($_POST['submission_text']);
        
        if (!empty($submission_text)) {
            if (submitAssignment($pdo, $assignment_id, $student_id, $submission_text)) {
                $success_message = "Assignment submitted successfully!";
                
                // Refresh submission data
                $stmt = $pdo->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?");
                $stmt->execute([$assignment_id, $student_id]);
                $existing_submission = $stmt->fetch();
                
                // Clear verification data as it's a new submission
                $verification = null;
            } else {
                $error_message = "Failed to submit assignment. Please try again.";
            }
        } else {
            $error_message = "Please provide your solution before submitting.";
        }
    }
}
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><?php echo htmlspecialchars($assignment['title']); ?></h2>
            <p><strong>Course:</strong> <?php echo htmlspecialchars($assignment['course_name']); ?></p>
            <p><strong>Student:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- Assignment Description -->
        <?php if (!empty($assignment['description'])): ?>
            <div class="card" style="margin-bottom: 2rem;">
                <h3>Assignment Description</h3>
                <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Questions -->
        <div class="card" style="margin-bottom: 2rem;">
            <h3>Questions (<?php echo count($questions); ?>)</h3>
            
            <?php if (empty($questions)): ?>
                <div class="alert alert-info">
                    <p>No questions available for this assignment.</p>
                </div>
            <?php else: ?>
                <div class="question-list">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question-item">
                            <div class="question-text">
                                <strong>Question <?php echo $index + 1; ?>:</strong><br>
                                <?php echo nl2br(htmlspecialchars($question['question_text'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Submission Status -->
        <?php if ($existing_submission): ?>
            <div class="card" style="margin-bottom: 2rem;">
                <h3>Submission Status</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <strong>Submitted:</strong><br>
                        <?php echo date('M j, Y g:i A', strtotime($existing_submission['submitted_at'])); ?>
                    </div>
                    <div>
                        <strong>Submission Count:</strong><br>
                        <span class="status-badge status-draft">
                            <?php echo $existing_submission['submission_count']; ?>x
                        </span>
                    </div>
                    <div>
                        <strong>Status:</strong><br>
                        <?php if ($verification): ?>
                            <span class="status-badge status-<?php echo $verification['status']; ?>">
                                <?php echo ucfirst($verification['status']); ?>
                            </span>
                        <?php else: ?>
                            <span class="status-badge status-draft">Pending Review</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($verification && !empty($verification['feedback'])): ?>
                    <div style="margin-top: 1rem; padding: 1rem; background: #f9fafb; border-radius: 0.5rem;">
                        <strong>Faculty Feedback:</strong><br>
                        <?php echo nl2br(htmlspecialchars($verification['feedback'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Submission Form -->
        <div class="card">
            <h3><?php echo $existing_submission ? 'Update Your Submission' : 'Submit Your Solution'; ?></h3>
            
            <?php if ($existing_submission): ?>
                <div class="alert alert-info">
                    <p>You have already submitted this assignment <?php echo $existing_submission['submission_count']; ?> time(s). 
                    You can submit again to update your solution.</p>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="submission_text" class="form-label">Your Solution *</label>
                    <textarea id="submission_text" name="submission_text" class="form-textarea" 
                              style="min-height: 300px; font-family: 'Courier New', monospace;" 
                              placeholder="Enter your code/solution here..." required><?php echo $existing_submission ? htmlspecialchars($existing_submission['submission_text']) : ''; ?></textarea>
                    <small style="color: #6b7280;">
                        Tip: You can write code, explanations, or any text-based solution here.
                    </small>
                </div>
                
                <div class="action-group">
                    <button type="submit" name="submit_assignment" class="btn btn-success">
                        <?php echo $existing_submission ? 'Update Submission' : 'Submit Assignment'; ?>
                    </button>
                    <a href="dashboard.php?student_id=<?php echo $student_id; ?>" class="btn btn-outline">
                        Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Previous Submission (if exists) -->
        <?php if ($existing_submission): ?>
            <div class="card" style="margin-top: 2rem;">
                <h3>Current Submission</h3>
                <div style="background: #f9fafb; padding: 1rem; border-radius: 0.5rem; 
                           font-family: 'Courier New', monospace; white-space: pre-wrap; 
                           max-height: 300px; overflow-y: auto;">
                    <?php echo htmlspecialchars($existing_submission['submission_text']); ?>
                </div>
                <small style="color: #6b7280; margin-top: 0.5rem; display: block;">
                    Last updated: <?php echo date('M j, Y g:i A', strtotime($existing_submission['submitted_at'])); ?>
                </small>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.question-item {
    border-left: 4px solid #3b82f6;
    margin-bottom: 1rem;
}

.question-item:last-child {
    margin-bottom: 0;
}

.form-textarea {
    resize: vertical;
}

@media (max-width: 768px) {
    .form-textarea {
        min-height: 200px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
