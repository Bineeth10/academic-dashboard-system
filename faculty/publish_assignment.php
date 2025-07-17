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

// Get all students
$students = getStudents($pdo);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['publish_assignment'])) {
        $publish_to = $_POST['publish_to'];
        $selected_students = isset($_POST['selected_students']) ? $_POST['selected_students'] : [];
        
        try {
            $pdo->beginTransaction();
            
            // Update assignment status to published
            updateAssignmentStatus($pdo, $assignment_id, 'published');
            
            // Clear existing publications for this assignment
            $stmt = $pdo->prepare("DELETE FROM assignment_publications WHERE assignment_id = ?");
            $stmt->execute([$assignment_id]);
            
            if ($publish_to === 'all') {
                // Publish to all students
                $stmt = $pdo->prepare("INSERT INTO assignment_publications (assignment_id, student_id) VALUES (?, NULL)");
                $stmt->execute([$assignment_id]);
                
                // Send notifications to all students
                foreach ($students as $student) {
                    $message = "New assignment '{$assignment['title']}' has been published in {$assignment['course_name']}.";
                    addNotification($pdo, $student['id'], $message);
                }
                
                $success_message = "Assignment published to all students successfully!";
            } else {
                // Publish to selected students
                if (!empty($selected_students)) {
                    $stmt = $pdo->prepare("INSERT INTO assignment_publications (assignment_id, student_id) VALUES (?, ?)");
                    
                    foreach ($selected_students as $student_id) {
                        $stmt->execute([$assignment_id, $student_id]);
                        
                        // Send notification to selected student
                        $message = "New assignment '{$assignment['title']}' has been published in {$assignment['course_name']}.";
                        addNotification($pdo, $student_id, $message);
                    }
                    
                    $success_message = "Assignment published to " . count($selected_students) . " selected students successfully!";
                } else {
                    throw new Exception("Please select at least one student.");
                }
            }
            
            $pdo->commit();
            
            // Redirect after successful publication
            header("Location: course.php?id=$course_id&published=1");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = $e->getMessage();
        }
    }
}

// Check if assignment has questions
$questions = getQuestionsByAssignment($pdo, $assignment_id);
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Publish Assignment</h2>
            <p>Course: <?php echo htmlspecialchars($assignment['course_name']); ?></p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- Assignment Preview -->
        <div class="card" style="margin-bottom: 2rem;">
            <h3>Assignment Preview</h3>
            <div style="background: #f9fafb; padding: 1.5rem; border-radius: 0.5rem;">
                <h4><?php echo htmlspecialchars($assignment['title']); ?></h4>
                <?php if (!empty($assignment['description'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                <?php endif; ?>
                
                <div style="margin-top: 1rem;">
                    <strong>Questions (<?php echo count($questions); ?>):</strong>
                    <?php if (empty($questions)): ?>
                        <div class="alert alert-error" style="margin-top: 0.5rem;">
                            <p>⚠️ This assignment has no questions. Please add questions before publishing.</p>
                        </div>
                    <?php else: ?>
                        <ol style="margin-top: 0.5rem;">
                            <?php foreach ($questions as $question): ?>
                                <li style="margin-bottom: 0.5rem;">
                                    <?php echo htmlspecialchars(substr($question['question_text'], 0, 100)); ?>
                                    <?php echo strlen($question['question_text']) > 100 ? '...' : ''; ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (empty($questions)): ?>
            <div class="alert alert-error">
                <p>Cannot publish assignment without questions. Please <a href="assignment.php?id=<?php echo $assignment_id; ?>&course_id=<?php echo $course_id; ?>">add questions</a> first.</p>
            </div>
        <?php elseif (empty($students)): ?>
            <div class="alert alert-error">
                <p>No students found in the system. Please contact the administrator to add students.</p>
            </div>
        <?php else: ?>
            <!-- Publication Form -->
            <form method="POST">
                <div class="card">
                    <h3>Publication Settings</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Publish To:</label>
                        <div style="margin-top: 0.5rem;">
                            <label style="display: flex; align-items: center; margin-bottom: 1rem;">
                                <input type="radio" name="publish_to" value="all" checked 
                                       onchange="toggleStudentSelection()" style="margin-right: 0.5rem;">
                                All Students (<?php echo count($students); ?> students)
                            </label>
                            
                            <label style="display: flex; align-items: center;">
                                <input type="radio" name="publish_to" value="selected" 
                                       onchange="toggleStudentSelection()" style="margin-right: 0.5rem;">
                                Selected Students
                            </label>
                        </div>
                    </div>
                    
                    <div id="student-selection" style="display: none; margin-top: 1.5rem;">
                        <label class="form-label">Select Students:</label>
                        <div style="max-height: 300px; overflow-y: auto; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 1rem; margin-top: 0.5rem;">
                            <?php foreach ($students as $student): ?>
                                <label style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                    <input type="checkbox" name="selected_students[]" 
                                           value="<?php echo $student['id']; ?>" style="margin-right: 0.5rem;">
                                    <?php echo htmlspecialchars($student['name']); ?>
                                    <?php if (!empty($student['student_id'])): ?>
                                        <span style="color: #6b7280; margin-left: 0.5rem;">(<?php echo htmlspecialchars($student['student_id']); ?>)</span>
                                    <?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="margin-top: 1rem;">
                            <button type="button" onclick="selectAllStudents()" class="btn btn-outline">Select All</button>
                            <button type="button" onclick="deselectAllStudents()" class="btn btn-outline">Deselect All</button>
                        </div>
                    </div>
                </div>
                
                <div class="action-group" style="margin-top: 2rem;">
                    <button type="submit" name="publish_assignment" class="btn btn-success">
                        Publish Assignment
                    </button>
                    <a href="assignment.php?id=<?php echo $assignment_id; ?>&course_id=<?php echo $course_id; ?>" 
                       class="btn btn-outline">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleStudentSelection() {
    const publishTo = document.querySelector('input[name="publish_to"]:checked').value;
    const studentSelection = document.getElementById('student-selection');
    
    if (publishTo === 'selected') {
        studentSelection.style.display = 'block';
    } else {
        studentSelection.style.display = 'none';
    }
}

function selectAllStudents() {
    const checkboxes = document.querySelectorAll('input[name="selected_students[]"]');
    checkboxes.forEach(checkbox => checkbox.checked = true);
}

function deselectAllStudents() {
    const checkboxes = document.querySelectorAll('input[name="selected_students[]"]');
    checkboxes.forEach(checkbox => checkbox.checked = false);
}
</script>

<?php include '../includes/footer.php'; ?>
