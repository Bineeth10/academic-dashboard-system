# Academic Dashboard System

A comprehensive PHP-based dashboard system for managing academic assignments between faculty and students.

## Features

### Faculty Dashboard
- **Course Management**: View and manage courses
- **Assignment Creation**: Create assignments with multiple questions
- **Assignment Publishing**: Publish assignments to all students or selected individuals
- **Submission Review**: View, verify, reject, or return student submissions
- **Statistics Tracking**: Monitor submission counts and verification status
- **Notification System**: Send automated notifications to students

### Student Dashboard
- **Assignment Viewing**: View all published assignments
- **Solution Submission**: Submit code/text solutions for assignments
- **Resubmission**: Update submissions multiple times
- **Status Tracking**: Monitor verification status and feedback
- **Notification Center**: Receive updates on assignment status

## Technology Stack
- **Backend**: PHP with PDO (MySQL)
- **Frontend**: HTML5, CSS3 (Modern responsive design)
- **Database**: MySQL
- **Styling**: Custom CSS with Google Fonts (Inter)

## Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or PHP built-in server

### Database Setup

1. Create a MySQL database named `academic_dashboard`
2. Import the database schema:
   ```bash
   mysql -u your_username -p academic_dashboard < queries/database.sql
   ```

### Configuration

1. Update database credentials in `includes/config.php`:
   ```php
   $host = 'localhost';
   $db   = 'academic_dashboard';
   $user = 'your_db_username';
   $pass = 'your_db_password';
   ```

### Running the Application

#### Option 1: PHP Built-in Server
```bash
php -S localhost:8000
```

#### Option 2: Apache/Nginx
Place files in your web server's document root and access via browser.

## Usage Guide

### Getting Started

1. **Access the Application**: Open `http://localhost:8000` in your browser
2. **Choose Dashboard**: Select either Faculty or Student dashboard
3. **No Login Required**: The system works without authentication for demo purposes

### Faculty Workflow

1. **View Courses**: Start from the Faculty Dashboard to see available courses
2. **Manage Course**: Click "Manage Course" to view assignments
3. **Create Assignment**: Use the form to create new assignments
4. **Add Questions**: Edit assignments to add multiple questions
5. **Publish Assignment**: Choose to publish to all students or selected individuals
6. **Review Submissions**: Monitor and verify student submissions
7. **Provide Feedback**: Verify, reject, or return submissions with feedback

### Student Workflow

1. **Select Student**: Choose a student from the dropdown (demo feature)
2. **View Assignments**: See all published assignments on the dashboard
3. **Start Assignment**: Click on an assignment to view details and questions
4. **Submit Solution**: Enter your code/solution and submit
5. **Track Status**: Monitor verification status and feedback
6. **Resubmit**: Update your solution if needed

## File Structure

```
/
├── index.php                 # Landing page
├── includes/
│   ├── config.php           # Database configuration
│   ├── functions.php        # Helper functions
│   ├── header.php           # Common header
│   └── footer.php           # Common footer
├── css/
│   └── style.css            # Styling
├── faculty/
│   ├── dashboard.php        # Faculty main dashboard
│   ├── course.php           # Course management
│   ├── assignment.php       # Assignment editing
│   ├── publish_assignment.php # Assignment publishing
│   ├── verify_submissions.php # Submission verification
│   └── process_verification.php # Verification processing
├── student/
│   ├── dashboard.php        # Student main dashboard
│   └── assignment_detail.php # Assignment submission
└── queries/
    └── database.sql         # Database schema and sample data
```

## Database Schema

### Core Tables
- **courses**: Course information
- **assignments**: Assignment details and status
- **questions**: Multiple questions per assignment
- **students**: Student information
- **submissions**: Student assignment submissions
- **verification**: Faculty verification records
- **notifications**: Student notifications
- **assignment_publications**: Assignment visibility control

## Features in Detail

### Assignment Management
- Create assignments with titles and descriptions
- Add multiple questions per assignment
- Draft, publish, and hold assignment states
- Edit and delete assignments

### Publication Control
- Publish to all students or selected individuals
- Track publication history
- Automatic notification sending

### Submission System
- Multiple submission attempts allowed
- Submission count tracking
- Full submission history

### Verification System
- Three verification states: Verified, Rejected, Returned
- Optional feedback for each verification
- Automatic notification to students

### Statistics & Reporting
- Submission counts per assignment
- Verification statistics
- Student performance tracking

## Sample Data

The system includes sample data for testing:
- 5 courses (CS101 to CS501)
- 10 students
- 8 sample assignments
- Multiple questions per assignment
- Sample submissions and verifications

## Customization

### Adding New Features
- Extend the database schema in `queries/database.sql`
- Add new functions in `includes/functions.php`
- Create new pages following the existing structure

### Styling
- Modify `css/style.css` for visual changes
- The design uses a modern, clean aesthetic with:
  - Inter font family
  - Responsive grid layouts
  - Card-based UI components
  - Status badges and alerts

### Security Considerations
- All user inputs are sanitized using `htmlspecialchars()`
- Database queries use prepared statements
- XSS protection implemented
- For production use, add proper authentication and authorization

## Browser Compatibility
- Modern browsers (Chrome, Firefox, Safari, Edge)
- Responsive design works on desktop, tablet, and mobile
- No external dependencies required

## Troubleshooting

### Common Issues
1. **Database Connection Error**: Check credentials in `config.php`
2. **Missing Tables**: Ensure `database.sql` was imported correctly
3. **Permission Issues**: Check file permissions for web server access
4. **Styling Issues**: Verify `css/style.css` is accessible

### Debug Mode
Enable error reporting by adding to `config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## License
This project is open source and available under the MIT License.

## Support
For issues or questions, please check the code comments and database schema for guidance.
