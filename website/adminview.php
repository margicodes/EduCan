<?php
session_start();
// Database connection settings
$host = "localhost";
$user = "root";      // XAMPP's default MySQL user is 'root'
$pass = "";          // Default is an empty password
$dbname = "elearning";  // The name of your database

// Create a connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check if Admin is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Optional: check if user is admin
$user_id = $_SESSION['user_id'];
$check_role_sql = "SELECT role FROM users WHERE id = ?";
$stmt = $conn->prepare($check_role_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();
if ($role !== 'ADMIN') {
    echo "Access Denied. Admins only.";
    exit();
}

// Fetch counts
function getCount($conn, $table) {
    $sql = "SELECT COUNT(*) FROM $table";
    $result = $conn->query($sql);
    return $result->fetch_row()[0];
}

$total_students = getCount($conn, 'students');
$total_teachers = getCount($conn, 'teachers');
$total_parents = getCount($conn, 'parents');
$total_courses = getCount($conn, 'courses');
$total_classes = getCount($conn, 'class_section');
$total_admins = getCount($conn, "users WHERE role = 'ADMIN'");

// Fetch total earnings from subscriptions
$earnings_sql = "SELECT SUM(amount) FROM subscriptions WHERE payment_status = 'pending'";
$earnings_result = $conn->query($earnings_sql);
$total_earnings = $earnings_result->fetch_row()[0] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #34495e;
        }
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            text-align: center;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .number {
            font-size: 40px;
            margin: 15px 0;
            color: #2c3e50;
        }
        .label {
            font-size: 18px;
            color: #555;
        }
        .earnings {
            font-size: 30px;
            color: #27ae60;
            font-weight: bold;
        }
        .logout-btn {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 24px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 30px;
            text-decoration: none;
            transition: background 0.3s;
            text-align: center;
        }
        .logout-btn:hover {
            background: #c0392b;
        }
        .chart-container {
            width: 100%;
            max-width: 1200px;
            margin: 40px auto;
        }
        canvas {
            background: #fff;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<h1>📊 Admin Dashboard</h1>

<div class="dashboard">
    <div class="card">
        <div class="number"><?php echo $total_students; ?></div>
        <div class="label">Total Students</div>
    </div>
    <div class="card">
        <div class="number"><?php echo $total_teachers; ?></div>
        <div class="label">Total Teachers</div>
    </div>
    <div class="card">
        <div class="number"><?php echo $total_parents; ?></div>
        <div class="label">Total Parents</div>
    </div>
    <div class="card">
        <div class="number"><?php echo $total_courses; ?></div>
        <div class="label">Total Courses</div>
    </div>
    <div class="card">
        <div class="number"><?php echo $total_classes; ?></div>
        <div class="label">Total Classes</div>
    </div>
    <div class="card">
        <div class="number"><?php echo $total_admins; ?></div>
        <div class="label">Total Admins</div>
    </div>
    <div class="card">
        <div class="earnings">$<?php echo number_format($total_earnings, 2); ?></div>
        <div class="label">Total Earnings</div>
    </div>
</div>

<div style="text-align: center;">
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

</body>
</html>
