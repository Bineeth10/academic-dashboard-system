<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
include '../includes/header.php';

// Get assignment ID and course ID from URL
$assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
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

// Get submissions for this assignment
$submissions = getSubmissionsByAssignment($pdo, $assignment_id);

// Get submission statistics
$stats = getSubmissionStats($pdo, $assignment_id);
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Verify Submissions</h2>
            <p><strong>Assignment:</strong> <?php echo htmlspecialchars($assignment['title']); ?></p>
            <p><strong>Course:</strong> <?php echo htmlspecialchars($assignment['course_name']); ?></p>
        </div>
        
        <!-- Statistics Overview -->
        <div class="stats-grid" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($submissions); ?></div>
                <div class="stat-label">Total Submissions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php echo count(array_filter($submissions, function($s) { return $s['verification_status'] === 'verified'; })); ?>
                </div>
                <div class="stat-label">Verified</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php echo count(array_filter($submissions, function($s) { return $s['verification_status'] === 'rejected'; })); ?>
                </div>
                <div class="stat-label">Rejected</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php echo count(array_filter($submissions, function($s) { return $s['verification_status'] === 'returned'; })); ?>
                </div>
                <div class="stat-label">Returned</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php echo count(array_filter($submissions, function($s) { return empty($s['verification_status']); })); ?>
                </div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        
        <?php if (empty($submissions)): ?>
            <div class="alert alert-info">
                <p>No submissions received yet for this assignment.</p>
            </div>
        <?php else: ?>
            <!-- Submissions Table -->
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Submission</th>
                            <th>Submitted At</th>
                            <th>Count</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($submission['student_name']); ?></strong>
                                </td>
                                <td>
                                    <div style="max-width: 300px; max-height: 100px; overflow-y: auto; 
                                                background: #f9fafb; padding: 0.5rem; border-radius: 0.25rem; 
                                                font-family: monospace; font-size: 0.875rem;">
                                        <?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?>
                                    </div>
                                </td>
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
                                        <?php if (!empty($submission['feedback'])): ?>
                                            <br><small style="color: #6b7280;">
                                                <?php echo htmlspecialchars(substr($submission['feedback'], 0, 50)); ?>
                                                <?php echo strlen($submission['feedback']) > 50 ? '...' : ''; ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="status-badge status-draft">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <button onclick="openVerificationModal(<?php echo $submission['id']; ?>, '<?php echo htmlspecialchars($submission['student_name'], ENT_QUOTES); ?>')" 
                                                class="btn btn-primary">
                                            <?php echo !empty($submission['verification_status']) ? 'Update' : 'Verify'; ?>
                                        </button>
                                        
                                        <button onclick="viewSubmissionModal(<?php echo $submission['id']; ?>)" 
                                                class="btn btn-outline">
                                            View Full
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- Submission Statistics by Student -->
        <?php if (!empty($stats)): ?>
            <div class="card" style="margin-top: 2rem;">
                <h3>Submission Statistics by Student</h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Submissions</th>
                                <th>Verified</th>
                                <th>Rejected</th>
                                <th>Returned</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats as $stat): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stat['student_name']); ?></td>
                                    <td><?php echo $stat['submission_count']; ?></td>
                                    <td><?php echo $stat['verified_count']; ?></td>
                                    <td><?php echo $stat['rejected_count']; ?></td>
                                    <td><?php echo $stat['returned_count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="action-group" style="margin-top: 2rem;">
            <a href="course.php?id=<?php echo $course_id; ?>" class="btn btn-outline">
                Back to Course
            </a>
        </div>
    </div>
</div>

<!-- Verification Modal -->
<div id="verificationModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                                   background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 2rem; border-radius: 0.75rem; max-width: 500px; width: 90%; max-height: 80%; overflow-y: auto;">
        <h3 id="modalTitle">Verify Submission</h3>
        <form id="verificationForm" method="POST" action="process_verification.php">
            <input type="hidden" name="submission_id" id="submissionId">
            <input type="hidden" name="assignment_id" value="<?php echo $assignment_id; ?>">
            <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
            
            <div class="form-group">
                <label class="form-label">Action *</label>
                <div style="margin-top: 0.5rem;">
                    <label style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                        <input type="radio" name="status" value="verified" style="margin-right: 0.5rem;" required>
                        Verify (Accept the submission)
                    </label>
                    <label style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                        <input type="radio" name="status" value="rejected" style="margin-right: 0.5rem;" required>
                        Reject (Submission is incorrect)
                    </label>
                    <label style="display: flex; align-items: center;">
                        <input type="radio" name="status" value="returned" style="margin-right: 0.5rem;" required>
                        Return (Needs revision)
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="feedback" class="form-label">Feedback (Optional)</label>
                <textarea id="feedback" name="feedback" class="form-textarea" 
                          placeholder="Provide feedback to the student..."></textarea>
            </div>
            
            <div class="action-group">
                <button type="submit" class="btn btn-primary">Submit Verification</button>
                <button type="button" onclick="closeVerificationModal()" class="btn btn-outline">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- View Submission Modal -->
<div id="viewModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                           background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 2rem; border-radius: 0.75rem; max-width: 800px; width: 90%; max-height: 80%; overflow-y: auto;">
        <h3>Full Submission</h3>
        <div id="fullSubmissionContent" style="background: #f9fafb; padding: 1rem; border-radius: 0.5rem; 
                                              font-family: monospace; white-space: pre-wrap; margin: 1rem 0;"></div>
        <button onclick="closeViewModal()" class="btn btn-outline">Close</button>
    </div>
</div>

<script>
function openVerificationModal(submissionId, studentName) {
    document.getElementById('submissionId').value = submissionId;
    document.getElementById('modalTitle').textContent = 'Verify Submission - ' + studentName;
    document.getElementById('verificationModal').style.display = 'flex';
}

function closeVerificationModal() {
    document.getElementById('verificationModal').style.display = 'none';
    document.getElementById('verificationForm').reset();
}

function viewSubmissionModal(submissionId) {
    // Get submission content via AJAX or from the existing data
    const submissionRow = document.querySelector(`button[onclick*="${submissionId}"]`).closest('tr');
    const submissionContent = submissionRow.querySelector('td:nth-child(2) div').textContent;
    
    document.getElementById('fullSubmissionContent').textContent = submissionContent;
    document.getElementById('viewModal').style.display = 'flex';
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

// Close modals when clicking outside
document.getElementById('verificationModal').addEventListener('click', function(e) {
    if (e.target === this) closeVerificationModal();
});

document.getElementById('viewModal').addEventListener('click', function(e) {
    if (e.target === this) closeViewModal();
});
</script>

<?php include '../includes/footer.php'; ?>
