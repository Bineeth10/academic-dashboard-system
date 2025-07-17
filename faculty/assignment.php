<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
include '../includes/header.php';

// Get assignment ID and course ID from URL
$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (!$assignment_id || !$course_id) {
    header('Location: dashboard.php');
    exit();
}

// Get assignment details
$assignment = getAssignmentById($pdo, $assignment_id);
if (!$assignment) {
    header('Location: dashboard.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_assignment'])) {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        
        if (!empty($title)) {
            $stmt = $pdo->prepare("UPDATE assignments SET title = ?, description = ? WHERE id = ?");
            if ($stmt->execute([$title, $description, $assignment_id])) {
                $success_message = "Assignment updated successfully!";
                // Refresh assignment data
                $assignment = getAssignmentById($pdo, $assignment_id);
            } else {
                $error_message = "Failed to update assignment.";
            }
        } else {
            $error_message = "Assignment title is required.";
        }
    }
    
    if (isset($_POST['add_question'])) {
        $question_text = sanitize($_POST['question_text']);
        
        if (!empty($question_text)) {
            if (addQuestion($pdo, $assignment_id, $question_text)) {
                $success_message = "Question added successfully!";
            } else {
                $error_message = "Failed to add question.";
            }
        } else {
            $error_message = "Question text is required.";
        }
    }
}

// Handle question deletion
if (isset($_GET['delete_question']) && is_numeric($_GET['delete_question'])) {
    if (deleteQuestion($pdo, $_GET['delete_question'])) {
        header("Location: assignment.php?id=$assignment_id&course_id=$course_id");
        exit();
    }
}

// Get questions for this assignment
$questions = getQuestionsByAssignment($pdo, $assignment_id);
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Edit Assignment</h2>
            <p>Course: <?php echo htmlspecialchars($assignment['course_name']); ?></p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- Assignment Details Form -->
        <div class="card" style="margin-bottom: 2rem;">
            <h3>Assignment Details</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="title" class="form-label">Assignment Title *</label>
                    <input type="text" id="title" name="title" class="form-input" 
                           value="<?php echo htmlspecialchars($assignment['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-textarea"><?php echo htmlspecialchars($assignment['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <span class="status-badge status-<?php echo $assignment['status']; ?>">
                        <?php echo ucfirst($assignment['status']); ?>
                    </span>
                </div>
                
                <button type="submit" name="update_assignment" class="btn btn-primary">
                    Update Assignment
                </button>
            </form>
        </div>
        
        <!-- Questions Section -->
        <div class="card">
            <h3>Questions (<?php echo count($questions); ?>)</h3>
            
            <!-- Add New Question Form -->
            <div style="background: #f9fafb; padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                <h4>Add New Question</h4>
                <form method="POST">
                    <div class="form-group">
                        <label for="question_text" class="form-label">Question Text *</label>
                        <textarea id="question_text" name="question_text" class="form-textarea" 
                                  placeholder="Enter your question here..." required></textarea>
                    </div>
                    
                    <button type="submit" name="add_question" class="btn btn-success">
                        Add Question
                    </button>
                </form>
            </div>
            
            <!-- Questions List -->
            <?php if (empty($questions)): ?>
                <div class="alert alert-info">
                    <p>No questions added yet. Add your first question using the form above.</p>
                </div>
            <?php else: ?>
                <div class="question-list">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question-item">
                            <div class="question-text">
                                <strong>Question <?php echo $index + 1; ?>:</strong><br>
                                <?php echo nl2br(htmlspecialchars($question['question_text'])); ?>
                            </div>
                            <div>
                                <a href="?id=<?php echo $assignment_id; ?>&course_id=<?php echo $course_id; ?>&delete_question=<?php echo $question['id']; ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to delete this question?')">
                                   Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-group" style="margin-top: 2rem;">
            <?php if ($assignment['status'] === 'draft' && count($questions) > 0): ?>
                <a href="publish_assignment.php?id=<?php echo $assignment_id; ?>&course_id=<?php echo $course_id; ?>" 
                   class="btn btn-success">Publish Assignment</a>
            <?php endif; ?>
            
            <?php if ($assignment['status'] === 'published'): ?>
                <a href="verify_submissions.php?assignment_id=<?php echo $assignment_id; ?>&course_id=<?php echo $course_id; ?>" 
                   class="btn btn-primary">View Submissions</a>
            <?php endif; ?>
            
            <a href="course.php?id=<?php echo $course_id; ?>" class="btn btn-outline">
                Back to Course
            </a>
        </div>
    </div>
</div>

<style>
.btn-sm {
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
}
</style>

<?php include '../includes/footer.php'; ?>
