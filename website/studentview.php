<?php
session_start();

// Database connection settings
$host = "localhost";
$user = "root";      // XAMPP's default MySQL user is 'root'
$pass = "";          // Default is an empty password
$dbname = "elearning";  // The name of your database

// Create a connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch student ID and name
$stmt = $conn->prepare("
    SELECT s.id AS student_id, u.full_name 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Student profile not found.";
    exit();
}

$student = $result->fetch_assoc();
$student_id = $student['student_id'];
$student_name = $student['full_name'];

// Fetch all student courses
$courses_stmt = $conn->prepare("
    SELECT c.id, c.title, c.description, c.subject, c.start_date, c.end_date
    FROM course_enrollment sc
    JOIN courses c ON sc.course_id = c.id
    WHERE sc.student_id = ?
");
$courses_stmt->bind_param("i", $student_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>🎓 Student Dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        .header { text-align: center; margin-bottom: 30px; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .course-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.3s;
            position: relative;
        }
        .course-card:hover { transform: translateY(-5px); }
        .details {
            display: none;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        .class-item, .assignment-item {
            padding: 10px;
            margin: 8px 0;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .assignment-item { margin-left: 20px; }
        .expand-btn {
            position: absolute;
            right: 20px;
            top: 20px;
            background: #007bff;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            border: none;
        }
        .top-bar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 16px;
        }

        .logout-btn {
            padding: 6px 14px;
            background-color: #e63946;
            color: white;
            text-decoration: none;
            border-radius: 20px;
            font-size: 14px;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background-color: #d62828;
        }
    </style>
    <script>
        function toggleDetails(id) {
            var details = document.getElementById('details-' + id);
            details.style.display = details.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</head>
<body>

<div class="header">
    <h1>Welcome to E-Learning Platform</h1>
    <p> Classes and Assignments</p>
</div>
<div class="top-bar">
    <div class="user-info">
        <span>👋 Hi, <?php echo htmlspecialchars($student_name); ?></span>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>
<div class="grid">
<?php while ($course = $courses_result->fetch_assoc()): ?>
    <div class="course-card" onclick="toggleDetails(<?php echo $course['id']; ?>)">
        <h3><?php echo htmlspecialchars($course['title']); ?></h3>
        <p><?php echo htmlspecialchars($course['description']); ?></p>
        <small><strong>Subject:</strong> <?php echo htmlspecialchars($course['subject']); ?> | 
               <strong>Dates:</strong> <?php echo htmlspecialchars($course['start_date']) . " to " . htmlspecialchars($course['end_date']); ?></small>
        <button class="expand-btn">View</button>

        <div class="details" id="details-<?php echo $course['id']; ?>">
            <h4>📚 Classes:</h4>
            <?php
            // Fetch classes for the course
            $classes_stmt = $conn->prepare("
                SELECT c.id, c.class_name, c.schedule, c.mode, c.location, c.start_date, c.end_date, cs.id as class_student_id
                FROM class_section c
                JOIN section_enrollment cs ON cs.class_id = c.id
                WHERE c.course_id = ? AND cs.student_id = ?
            ");
            $classes_stmt->bind_param("ii", $course['id'], $student_id);
            $classes_stmt->execute();
            $classes_result = $classes_stmt->get_result();

            if ($classes_result->num_rows > 0):
                while ($class = $classes_result->fetch_assoc()):
            ?>
                <div class="class-item">
                    <strong><?php echo htmlspecialchars($class['class_name']); ?></strong> 
                    (<?php echo htmlspecialchars($class['mode']); ?> - <?php echo htmlspecialchars($class['location']); ?>)<br>
                    <small>Schedule: <?php echo htmlspecialchars($class['schedule']); ?> | Dates: <?php echo htmlspecialchars($class['start_date']) . ' to ' . htmlspecialchars($class['end_date']); ?></small>

                    <h5>📝 Assignments:</h5>
                    <?php
                    // Fetch assignments for each class_student
                    $assignments_stmt = $conn->prepare("
                        SELECT assignment_name, score, max_score, feedback
                        FROM class_assessments
                        WHERE class_student_id = ?
                    ");
                    $assignments_stmt->bind_param("i", $class['class_student_id']);
                    $assignments_stmt->execute();
                    $assignments_result = $assignments_stmt->get_result();

                    if ($assignments_result->num_rows > 0):
                        while ($assignment = $assignments_result->fetch_assoc()):
                            $percentage = ($assignment['max_score'] > 0) ? round(($assignment['score'] / $assignment['max_score']) * 100) : 0;
                    ?>
                        <div class="assignment-item">
                            <strong><?php echo htmlspecialchars($assignment['assignment_name']); ?></strong> - <?php echo $percentage; ?>%
                            <?php if (!empty($assignment['feedback'])): ?>
                                <br><small><em>Feedback: <?php echo htmlspecialchars($assignment['feedback']); ?></em></small>
                            <?php endif; ?>
                        </div>
                    <?php
                        endwhile;
                    else:
                        echo "<p>No assignments yet.</p>";
                    endif;
                    ?>
                </div>
            <?php endwhile; else: ?>
                <p>No classes enrolled in this course yet.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endwhile; ?>
</div>

</body>
</html>
