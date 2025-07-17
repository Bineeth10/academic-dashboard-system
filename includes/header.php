<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Dashboard</title>
    <link rel="stylesheet" href="<?php echo isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/faculty/') !== false || strpos($_SERVER['REQUEST_URI'], '/student/') !== false ? '../css/style.css' : 'css/style.css'; ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="<?php echo isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/faculty/') !== false || strpos($_SERVER['REQUEST_URI'], '/student/') !== false ? '../index.php' : 'index.php'; ?>">
                    Academic Dashboard
                </a>
            </div>
            
            <?php if (isset($_GET['back']) || basename($_SERVER['PHP_SELF']) !== 'index.php'): ?>
            <div class="nav-actions">
                <?php
                $back_url = '';
                $current_page = basename($_SERVER['PHP_SELF']);
                $current_dir = basename(dirname($_SERVER['PHP_SELF']));
                
                if ($current_dir === 'faculty') {
                    switch ($current_page) {
                        case 'dashboard.php':
                            $back_url = '../index.php';
                            break;
                        case 'course.php':
                            $back_url = 'dashboard.php';
                            break;
                        case 'assignment.php':
                            $back_url = isset($_GET['course_id']) ? 'course.php?id=' . $_GET['course_id'] : 'dashboard.php';
                            break;
                        case 'publish_assignment.php':
                            $back_url = isset($_GET['course_id']) ? 'course.php?id=' . $_GET['course_id'] : 'dashboard.php';
                            break;
                        case 'verify_submissions.php':
                            $back_url = isset($_GET['course_id']) ? 'course.php?id=' . $_GET['course_id'] : 'dashboard.php';
                            break;
                        default:
                            $back_url = 'dashboard.php';
                    }
                } elseif ($current_dir === 'student') {
                    switch ($current_page) {
                        case 'dashboard.php':
                            $back_url = '../index.php';
                            break;
                        case 'assignment_detail.php':
                            $back_url = 'dashboard.php';
                            break;
                        default:
                            $back_url = 'dashboard.php';
                    }
                } else {
                    $back_url = 'index.php';
                }
                ?>
                <a href="<?php echo $back_url; ?>" class="btn-back">← Back</a>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    
    <main class="main-content">
