<?php
session_start();
// Database connection settings
$host = "localhost";
$user = "root";      // XAMPP's default MySQL user is 'root'
$pass = "";          // Default is an empty password
$dbname = "elearning";  // The name of your database

// Create a connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check if parent is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Parent Info
$parent_sql = "
    SELECT p.id as parent_id, u.full_name, u.email, u.phone_number, u.address, u.profile_picture, p.occupation, p.notes
    FROM parents p
    JOIN users u ON p.user_id = u.id
    WHERE p.user_id = ?
";
$stmt = $conn->prepare($parent_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$parent_result = $stmt->get_result();
$parent = $parent_result->fetch_assoc();

if (!$parent) {
    echo "Parent profile not found!";
    exit();
}

$parent_id = $parent['parent_id'];

// Fetch Students
$students_sql = "
    SELECT s.id, u.full_name as student_name, psr.relationship, s.grade_level
    FROM parent_student_relation psr
    JOIN students s ON psr.student_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE psr.parent_id = ?
";
$stmt = $conn->prepare($students_sql);
$stmt->bind_param('i', $parent_id);
$stmt->execute();
$students_result = $stmt->get_result();
$students = [];
while ($row = $students_result->fetch_assoc()) $students[] = $row;

// Fetch Subscriptions
$subscriptions_sql = "
    SELECT subscription_type, amount, payment_method, payment_status, start_date, end_date
    FROM subscriptions
    WHERE parent_id = ?
    ORDER BY start_date DESC
";
$stmt = $conn->prepare($subscriptions_sql);
$stmt->bind_param('i', $parent_id);
$stmt->execute();
$subscriptions_result = $stmt->get_result();
$subscriptions = [];
while ($row = $subscriptions_result->fetch_assoc()) $subscriptions[] = $row;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parent Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; padding: 30px; color: #333; }

        h1, h2 { margin-bottom: 15px; color: #2c3e50; }

        .container { max-width: 1200px; margin: auto; }

        .logout-btn {
            padding: 10px 20px; background: #e74c3c; color: white; text-decoration: none;
            border-radius: 30px; position: absolute; right: 30px; top: 30px;
            transition: background 0.3s;
        }
        .logout-btn:hover { background: #c0392b; }

        .card { background: white; border-radius: 16px; padding: 20px; margin-bottom: 30px; box-shadow: 0 6px 18px rgba(0,0,0,0.1); }

        .profile { display: flex; gap: 20px; align-items: center; }
        .profile img { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid #ddd; }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }

        .student-card, .subscription-card {
            padding: 15px; border: 1px solid #ddd; border-radius: 12px;
            transition: box-shadow 0.3s, transform 0.3s;
            background: #fff;
        }
        .student-card:hover, .subscription-card:hover {
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            transform: translateY(-5px);
        }
        .label { font-weight: bold; color: #555; margin-bottom: 5px; }
        .status-paid { color: green; }
        .status-pending { color: orange; }
        .status-failed { color: red; }

        @media (max-width: 600px) {
            .profile { flex-direction: column; align-items: flex-start; }
            .logout-btn { position: static; margin-bottom: 20px; display: inline-block; }
        }
    </style>
</head>
<body>

<div class="container">

    <a href="logout.php" class="logout-btn">Logout</a>

    <h1>👋 Welcome, <?php echo htmlspecialchars($parent['full_name']); ?>!</h1>

    <!-- Parent Profile -->
    <div class="card profile">
        <img src="<?php echo htmlspecialchars($parent['profile_picture'] ?? 'default_profile.png'); ?>" alt="Profile Picture">
        <div>
            <p><span class="label">Email:</span> <?php echo htmlspecialchars($parent['email']); ?></p>
            <p><span class="label">Phone:</span> <?php echo htmlspecialchars($parent['phone_number']); ?></p>
            <p><span class="label">Address:</span> <?php echo htmlspecialchars($parent['address']); ?></p>
            <p><span class="label">Occupation:</span> <?php echo htmlspecialchars($parent['occupation']); ?></p>
            <p><span class="label">Notes:</span> <?php echo htmlspecialchars($parent['notes']); ?></p>
        </div>
    </div>

    <!-- Linked Students -->
    <div class="card">
        <h2>🎓 Your Children</h2>
        <div class="grid">
            <?php if ($students): foreach ($students as $student): ?>
                <div class="student-card">
                    <p><span class="label">Name:</span> <?php echo htmlspecialchars($student['student_name']); ?></p>
                    <p><span class="label">Relationship:</span> <?php echo htmlspecialchars($student['relationship']); ?></p>
                    <p><span class="label">Grade Level:</span> <?php echo htmlspecialchars($student['grade_level']); ?></p>
                </div>
            <?php endforeach; else: ?>
                <p>No students linked.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Subscriptions -->
    <div class="card">
        <h2>💳 Subscriptions</h2>
        <div class="grid">
            <?php if ($subscriptions): foreach ($subscriptions as $sub): ?>
                <div class="subscription-card">
                    <p><span class="label">Type:</span> <?php echo ucfirst($sub['subscription_type']); ?></p>
                    <p><span class="label">Amount:</span> $<?php echo htmlspecialchars($sub['amount']); ?></p>
                    <p><span class="label">Payment Method:</span> <?php echo ucfirst($sub['payment_method']); ?></p>
                    <p><span class="label">Status:</span> 
                        <span class="status-<?php echo strtolower($sub['payment_status']); ?>">
                            <?php echo ucfirst($sub['payment_status']); ?>
                        </span>
                    </p>
                    <p><span class="label">Valid From:</span> <?php echo htmlspecialchars($sub['start_date']); ?> to <?php echo htmlspecialchars($sub['end_date']); ?></p>
                </div>
            <?php endforeach; else: ?>
                <p>No subscriptions found.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

</body>
</html>
