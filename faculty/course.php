<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
include '../includes/header.php';

// Get course ID from URL
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$course_id) {
    header('Location: dashboard.php');
    exit();
}

// Get course details
$course = getCourseById($pdo, $course_id);
if (!$course) {
    header('Location: dashboard.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_assignment'])) {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        
        if (!empty($title)) {
            $assignment_id = createAssignment($pdo, $course_id, $title, $description);
            if ($assignment_id) {
                $success_message = "Assignment created successfully!";
            } else {
                $error_message = "Failed to create assignment.";
            }
        } else {
            $error_message = "Assignment title is required.";
        }
    }
}

// Get assignments for this course
$assignments = getAssignmentsByCourse($pdo, $course_id);
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><?php echo htmlspecialchars($course['course_name']); ?></h2>
            <?php if (!empty($course['course_code'])): ?>
                <p><strong>Course Code:</strong> <?php echo htmlspecialchars($course['course_code']); ?></p>
            <?php endif; ?>
            <?php if (!empty($course['description'])): ?>
                <p><?php echo htmlspecialchars($course['description']); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- Create New Assignment Form -->
        <div class="card" style="margin-bottom: 2rem;">
            <h3>Create New Assignment</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="title" class="form-label">Assignment Title *</label>
                    <input type="text" id="title" name="title" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-textarea" 
                              placeholder="Enter assignment description..."></textarea>
                </div>
                
                <button type="submit" name="create_assignment" class="btn btn-primary">
                    Create Assignment
                </button>
            </form>
        </div>
        
        <!-- Assignments List -->
        <h3>Assignments (<?php echo count($assignments); ?>)</h3>
        
        <?php if (empty($assignments)): ?>
            <div class="alert alert-info">
                <p>No assignments created yet. Create your first assignment using the form above.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Questions</th>
                            <th>Submissions</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                            <?php
                            // Get question count
                            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM questions WHERE assignment_id = ?");
                            $stmt->execute([$assignment['id']]);
                            $questionCount = $stmt->fetch()['count'];
                            
                            // Get submission count
                            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM submissions WHERE assignment_id = ?");
                            $stmt->execute([$assignment['id']]);
                            $submissionCount = $stmt->fetch()['count'];
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($assignment['title']); ?></strong>
                                    <?php if (!empty($assignment['description'])): ?>
                                        <br><small><?php echo htmlspecialchars(substr($assignment['description'], 0, 100)); ?>
                                        <?php echo strlen($assignment['description']) > 100 ? '...' : ''; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $assignment['status']; ?>">
                                        <?php echo ucfirst($assignment['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $questionCount; ?></td>
                                <td><?php echo $submissionCount; ?></td>
                                <td><?php echo date('M j, Y', strtotime($assignment['created_at'])); ?></td>
                                <td>
                                    <div class="action-group">
                                        <a href="assignment.php?id=<?php echo $assignment['id']; ?>&course_id=<?php echo $course_id; ?>" 
                                           class="btn btn-outline">Edit</a>
                                        
                                        <?php if ($assignment['status'] === 'draft'): ?>
                                            <a href="publish_assignment.php?id=<?php echo $assignment['id']; ?>&course_id=<?php echo $course_id; ?>" 
                                               class="btn btn-success">Publish</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($assignment['status'] === 'published'): ?>
                                            <a href="?id=<?php echo $course_id; ?>&hold=<?php echo $assignment['id']; ?>" 
                                               class="btn btn-warning">Hold</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($assignment['status'] === 'held'): ?>
                                            <a href="?id=<?php echo $course_id; ?>&publish=<?php echo $assignment['id']; ?>" 
                                               class="btn btn-success">Publish</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($submissionCount > 0): ?>
                                            <a href="verify_submissions.php?assignment_id=<?php echo $assignment['id']; ?>&course_id=<?php echo $course_id; ?>" 
                                               class="btn btn-primary">
                                               Submissions (<?php echo $submissionCount; ?>)
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="?id=<?php echo $course_id; ?>&delete=<?php echo $assignment['id']; ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this assignment? This will also delete all questions and submissions.')">
                                           Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Handle status changes and deletions
if (isset($_GET['hold']) && is_numeric($_GET['hold'])) {
    updateAssignmentStatus($pdo, $_GET['hold'], 'held');
    header("Location: course.php?id=$course_id");
    exit();
}

if (isset($_GET['publish']) && is_numeric($_GET['publish'])) {
    updateAssignmentStatus($pdo, $_GET['publish'], 'published');
    header("Location: course.php?id=$course_id");
    exit();
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    deleteAssignment($pdo, $_GET['delete']);
    header("Location: course.php?id=$course_id");
    exit();
}
?>

<?php include '../includes/footer.php'; ?>
