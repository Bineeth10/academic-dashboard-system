<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
include '../includes/header.php';

// Get all courses
$courses = getCourses($pdo);
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Faculty Dashboard</h2>
            <p>Manage your courses and assignments</p>
        </div>
        
        <?php if (empty($courses)): ?>
            <div class="alert alert-info">
                <p>No courses found. Please contact the administrator to add courses.</p>
            </div>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($courses as $course): ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                        <?php if (!empty($course['course_code'])): ?>
                            <p><strong>Code:</strong> <?php echo htmlspecialchars($course['course_code']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($course['description'])): ?>
                            <p><?php echo htmlspecialchars($course['description']); ?></p>
                        <?php endif; ?>
                        
                        <?php
                        // Get assignment count for this course
                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assignments WHERE course_id = ?");
                        $stmt->execute([$course['id']]);
                        $assignmentCount = $stmt->fetch()['count'];
                        
                        // Get submission count for this course
                        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT s.id) as count FROM submissions s 
                                             JOIN assignments a ON s.assignment_id = a.id 
                                             WHERE a.course_id = ?");
                        $stmt->execute([$course['id']]);
                        $submissionCount = $stmt->fetch()['count'];
                        ?>
                        
                        <div class="stats-grid" style="margin: 1rem 0;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $assignmentCount; ?></div>
                                <div class="stat-label">Assignments</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $submissionCount; ?></div>
                                <div class="stat-label">Submissions</div>
                            </div>
                        </div>
                        
                        <div class="action-group">
                            <a href="course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">
                                Manage Course
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
