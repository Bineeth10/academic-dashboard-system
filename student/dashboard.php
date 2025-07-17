<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
include '../includes/header.php';

// For demo purposes, we'll use a simple student selection
// In a real application, this would be handled by authentication
$selected_student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 1;

// Get student details
$student = getStudentById($pdo, $selected_student_id);
if (!$student) {
    // If student doesn't exist, use the first available student
    $students = getStudents($pdo);
    if (!empty($students)) {
        $student = $students[0];
        $selected_student_id = $student['id'];
    }
}

// Get all students for selection dropdown
$all_students = getStudents($pdo);

// Get published assignments for this student
$stmt = $pdo->prepare("
    SELECT DISTINCT a.*, c.course_name 
    FROM assignments a 
    JOIN courses c ON a.course_id = c.id 
    LEFT JOIN assignment_publications ap ON a.id = ap.assignment_id 
    WHERE a.status = 'published' 
    AND (ap.student_id = ? OR ap.student_id IS NULL)
    ORDER BY a.created_at DESC
");
$stmt->execute([$selected_student_id]);
$published_assignments = $stmt->fetchAll();

// Get student's submissions
$stmt = $pdo->prepare("
    SELECT s.*, a.title as assignment_title, c.course_name, v.status as verification_status, v.feedback
    FROM submissions s 
    JOIN assignments a ON s.assignment_id = a.id 
    JOIN courses c ON a.course_id = c.id 
    LEFT JOIN verification v ON s.id = v.submission_id 
    WHERE s.student_id = ? 
    ORDER BY s.submitted_at DESC
");
$stmt->execute([$selected_student_id]);
$my_submissions = $stmt->fetchAll();

// Get notifications for this student
$notifications = getNotificationsByStudent($pdo, $selected_student_id);
$unread_notifications = array_filter($notifications, function($n) { return !$n['is_read']; });
?>

<div class="container">
    <!-- Student Selection (Demo purposes) -->
    <div class="card" style="margin-bottom: 1rem;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <label for="student_select" class="form-label" style="margin: 0;">Select Student:</label>
            <select id="student_select" class="form-select" style="max-width: 300px;" 
                    onchange="window.location.href='dashboard.php?student_id=' + this.value">
                <?php foreach ($all_students as $s): ?>
                    <option value="<?php echo $s['id']; ?>" <?php echo $s['id'] == $selected_student_id ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s['name']); ?>
                        <?php if (!empty($s['student_id'])): ?>
                            (<?php echo htmlspecialchars($s['student_id']); ?>)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Student Dashboard</h2>
            <?php if ($student): ?>
                <p>Welcome, <strong><?php echo htmlspecialchars($student['name']); ?></strong>!</p>
            <?php endif; ?>
        </div>
        
        <!-- Notifications -->
        <?php if (!empty($unread_notifications)): ?>
            <div class="alert alert-info" style="margin-bottom: 2rem;">
                <h4>📢 New Notifications (<?php echo count($unread_notifications); ?>)</h4>
                <?php foreach (array_slice($unread_notifications, 0, 3) as $notification): ?>
                    <div style="margin: 0.5rem 0; padding: 0.5rem; background: rgba(255,255,255,0.7); border-radius: 0.25rem;">
                        <?php echo htmlspecialchars($notification['message']); ?>
                        <small style="color: #6b7280; display: block;">
                            <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                        </small>
                    </div>
                <?php endforeach; ?>
                <?php if (count($unread_notifications) > 3): ?>
                    <small>... and <?php echo count($unread_notifications) - 3; ?> more notifications</small>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Overview -->
        <div class="stats-grid" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($published_assignments); ?></div>
                <div class="stat-label">Available Assignments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($my_submissions); ?></div>
                <div class="stat-label">My Submissions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php echo count(array_filter($my_submissions, function($s) { return $s['verification_status'] === 'verified'; })); ?>
                </div>
                <div class="stat-label">Verified</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php echo count(array_filter($my_submissions, function($s) { return empty($s['verification_status']); })); ?>
                </div>
                <div class="stat-label">Pending Review</div>
            </div>
        </div>
        
        <!-- Available Assignments -->
        <h3>Available Assignments (<?php echo count($published_assignments); ?>)</h3>
        
        <?php if (empty($published_assignments)): ?>
            <div class="alert alert-info">
                <p>No assignments are currently available. Check back later for new assignments.</p>
            </div>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($published_assignments as $assignment): ?>
                    <?php
                    // Check if student has already submitted this assignment
                    $stmt = $pdo->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?");
                    $stmt->execute([$assignment['id'], $selected_student_id]);
                    $existing_submission = $stmt->fetch();
                    
                    // Get question count
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM questions WHERE assignment_id = ?");
                    $stmt->execute([$assignment['id']]);
                    $question_count = $stmt->fetch()['count'];
                    ?>
                    <div class="card">
                        <h4><?php echo htmlspecialchars($assignment['title']); ?></h4>
                        <p><strong>Course:</strong> <?php echo htmlspecialchars($assignment['course_name']); ?></p>
                        
                        <?php if (!empty($assignment['description'])): ?>
                            <p><?php echo htmlspecialchars(substr($assignment['description'], 0, 150)); ?>
                            <?php echo strlen($assignment['description']) > 150 ? '...' : ''; ?></p>
                        <?php endif; ?>
                        
                        <div style="margin: 1rem 0;">
                            <small style="color: #6b7280;">
                                📝 <?php echo $question_count; ?> questions • 
                                📅 Published <?php echo date('M j, Y', strtotime($assignment['created_at'])); ?>
                            </small>
                        </div>
                        
                        <?php if ($existing_submission): ?>
                            <div style="margin: 1rem 0;">
                                <span class="status-badge status-draft">
                                    Submitted <?php echo $existing_submission['submission_count']; ?>x
                                </span>
                                
                                <?php
                                $stmt = $pdo->prepare("SELECT * FROM verification WHERE submission_id = ?");
                                $stmt->execute([$existing_submission['id']]);
                                $verification = $stmt->fetch();
                                ?>
                                
                                <?php if ($verification): ?>
                                    <span class="status-badge status-<?php echo $verification['status']; ?>">
                                        <?php echo ucfirst($verification['status']); ?>
                                    </span>
                                    
                                    <?php if (!empty($verification['feedback'])): ?>
                                        <div style="margin-top: 0.5rem; padding: 0.5rem; background: #f9fafb; border-radius: 0.25rem; font-size: 0.875rem;">
                                            <strong>Feedback:</strong> <?php echo htmlspecialchars($verification['feedback']); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="status-badge status-draft">Pending Review</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="action-group">
                            <a href="assignment_detail.php?id=<?php echo $assignment['id']; ?>&student_id=<?php echo $selected_student_id; ?>" 
                               class="btn btn-primary">
                                <?php echo $existing_submission ? 'View & Resubmit' : 'Start Assignment'; ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Recent Submissions -->
        <?php if (!empty($my_submissions)): ?>
            <div class="card" style="margin-top: 2rem;">
                <h3>My Recent Submissions</h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Assignment</th>
                                <th>Course</th>
                                <th>Submitted</th>
                                <th>Count</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($my_submissions, 0, 10) as $submission): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($submission['assignment_title']); ?></td>
                                    <td><?php echo htmlspecialchars($submission['course_name']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?></td>
                                    <td>
                                        <span class="status-badge status-draft">
                                            <?php echo $submission['submission_count']; ?>x
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($submission['verification_status'])): ?>
                                            <span class="status-badge status-<?php echo $submission['verification_status']; ?>">
                                                <?php echo ucfirst($submission['verification_status']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-draft">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
