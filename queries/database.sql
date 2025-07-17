-- Academic Dashboard Database Schema
-- Run these queries to create the required database structure

-- Create database
CREATE DATABASE IF NOT EXISTS academic_dashboard;
USE academic_dashboard;

-- Courses table: Faculty taught courses
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL,
    course_code VARCHAR(20),
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Assignments table: Each assignment linked to a course
CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    status ENUM('draft', 'published', 'held') DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Questions table: Multiple questions per assignment
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_order INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE
);

-- Students table: Student information
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    student_id VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Submissions table: Student submissions for assignments
CREATE TABLE submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_text TEXT,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    submission_count INT DEFAULT 1,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment_student (assignment_id, student_id)
);

-- Verification table: Records when a submission is verified, rejected, or returned
CREATE TABLE verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT NOT NULL,
    status ENUM('verified', 'rejected', 'returned') NOT NULL,
    verified_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    feedback TEXT,
    FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE
);

-- Notifications table: For sending messages to students
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Assignment Publications table: Track which students can see which assignments
CREATE TABLE assignment_publications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NULL, -- NULL means published to all students
    published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Insert sample data for testing

-- Sample courses
INSERT INTO courses (course_name, course_code, description) VALUES
('Introduction to Programming', 'CS101', 'Basic programming concepts and problem solving'),
('Data Structures', 'CS201', 'Fundamental data structures and algorithms'),
('Web Development', 'CS301', 'Modern web development technologies'),
('Database Systems', 'CS401', 'Database design and management systems'),
('Software Engineering', 'CS501', 'Software development methodologies and practices');

-- Sample students
INSERT INTO students (name, email, student_id) VALUES
('John Smith', 'john.smith@email.com', 'STU001'),
('Emily Johnson', 'emily.johnson@email.com', 'STU002'),
('Michael Brown', 'michael.brown@email.com', 'STU003'),
('Sarah Davis', 'sarah.davis@email.com', 'STU004'),
('David Wilson', 'david.wilson@email.com', 'STU005'),
('Lisa Anderson', 'lisa.anderson@email.com', 'STU006'),
('James Taylor', 'james.taylor@email.com', 'STU007'),
('Jennifer Martinez', 'jennifer.martinez@email.com', 'STU008'),
('Robert Garcia', 'robert.garcia@email.com', 'STU009'),
('Maria Rodriguez', 'maria.rodriguez@email.com', 'STU010');

-- Sample assignments
INSERT INTO assignments (course_id, title, description, status) VALUES
(1, 'Hello World Program', 'Create your first program that displays "Hello World"', 'published'),
(1, 'Variables and Data Types', 'Write a program demonstrating different data types', 'published'),
(2, 'Array Implementation', 'Implement basic array operations', 'draft'),
(2, 'Linked List Operations', 'Create a linked list with insert, delete operations', 'published'),
(3, 'HTML/CSS Layout', 'Create a responsive webpage layout', 'published'),
(3, 'JavaScript Functions', 'Implement various JavaScript functions', 'draft'),
(4, 'Database Design', 'Design a database schema for a library system', 'held'),
(5, 'Project Planning', 'Create a software project plan', 'published');

-- Sample questions for assignments
INSERT INTO questions (assignment_id, question_text, question_order) VALUES
(1, 'Write a program that prints "Hello World" to the console.', 1),
(1, 'Modify your program to also print your name.', 2),
(2, 'Declare variables of different data types (int, float, string, boolean).', 1),
(2, 'Perform arithmetic operations and display the results.', 2),
(2, 'Demonstrate type conversion between different data types.', 3),
(4, 'Implement a function to create a new linked list.', 1),
(4, 'Add a function to insert elements at the beginning of the list.', 2),
(4, 'Add a function to delete elements from the list.', 3),
(4, 'Implement a function to display all elements in the list.', 4),
(5, 'Create an HTML structure with header, navigation, main content, and footer.', 1),
(5, 'Style the webpage using CSS with a responsive design.', 2),
(5, 'Ensure the layout works on both desktop and mobile devices.', 3),
(8, 'Define the project scope and objectives.', 1),
(8, 'Create a timeline with major milestones.', 2),
(8, 'Identify potential risks and mitigation strategies.', 3);

-- Sample assignment publications (publish to all students)
INSERT INTO assignment_publications (assignment_id, student_id) VALUES
(1, NULL), -- Published to all students
(2, NULL), -- Published to all students
(4, NULL), -- Published to all students
(5, NULL), -- Published to all students
(8, NULL); -- Published to all students

-- Sample submissions
INSERT INTO submissions (assignment_id, student_id, submission_text, submission_count) VALUES
(1, 1, 'print("Hello World")\nprint("John Smith")', 1),
(1, 2, 'console.log("Hello World");\nconsole.log("Emily Johnson");', 1),
(2, 1, 'int age = 25;\nfloat height = 5.8;\nstring name = "John";\nbool isStudent = true;', 2),
(4, 3, 'class Node:\n    def __init__(self, data):\n        self.data = data\n        self.next = None\n\nclass LinkedList:\n    def __init__(self):\n        self.head = None', 1),
(5, 4, '<!DOCTYPE html>\n<html>\n<head>\n    <title>My Website</title>\n    <style>\n        body { margin: 0; font-family: Arial; }\n        .header { background: #333; color: white; padding: 1rem; }\n    </style>\n</head>\n<body>\n    <header class="header">My Website</header>\n</body>\n</html>', 1);

-- Sample verifications
INSERT INTO verification (submission_id, status, feedback) VALUES
(1, 'verified', 'Excellent work! Code is clean and follows best practices.'),
(2, 'verified', 'Good job! Consider adding comments to your code.'),
(3, 'returned', 'Please add more examples of type conversion as requested in question 3.'),
(4, 'verified', 'Great implementation of linked list! Well structured code.'),
(5, 'rejected', 'The responsive design requirement is not met. Please add media queries for mobile devices.');

-- Sample notifications
INSERT INTO notifications (student_id, message, is_read) VALUES
(1, 'Your assignment "Hello World Program" has been verified. Feedback: Excellent work! Code is clean and follows best practices.', FALSE),
(1, 'Your assignment "Variables and Data Types" has been returned. Feedback: Please add more examples of type conversion as requested in question 3.', FALSE),
(2, 'Your assignment "Hello World Program" has been verified. Feedback: Good job! Consider adding comments to your code.', TRUE),
(3, 'Your assignment "Linked List Operations" has been verified. Feedback: Great implementation of linked list! Well structured code.', FALSE),
(4, 'Your assignment "HTML/CSS Layout" has been rejected. Feedback: The responsive design requirement is not met. Please add media queries for mobile devices.', FALSE);

-- Create indexes for better performance
CREATE INDEX idx_assignments_course_id ON assignments(course_id);
CREATE INDEX idx_assignments_status ON assignments(status);
CREATE INDEX idx_questions_assignment_id ON questions(assignment_id);
CREATE INDEX idx_submissions_assignment_id ON submissions(assignment_id);
CREATE INDEX idx_submissions_student_id ON submissions(student_id);
CREATE INDEX idx_verification_submission_id ON verification(submission_id);
CREATE INDEX idx_notifications_student_id ON notifications(student_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
CREATE INDEX idx_assignment_publications_assignment_id ON assignment_publications(assignment_id);
CREATE INDEX idx_assignment_publications_student_id ON assignment_publications(student_id);
