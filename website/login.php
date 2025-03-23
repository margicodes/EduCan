<?php
// Start the session
session_start();

// Database connection settings
$host = "localhost";
$user = "root";      // XAMPP's default MySQL user is 'root'
$pass = "";          // Default is an empty password
$dbname = "elearning";  // The name of your database

// Create a connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check the connection
if ($conn->connect_error) {
    $_SESSION['error_message'] = "Invalid Connection.";
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    // Prepare the SQL query to fetch the user from the database
    $sql = "SELECT * FROM users WHERE email = '$username'";

    // Execute the query
    $result = $conn->query($sql);

    // Check if the user exists
    if ($result->num_rows > 0) {
        // Fetch the user's data
        $user = $result->fetch_assoc();

        if ($password === $user['password_hash']) {
            // Password is correct, store user data in the session
            $_SESSION['user_id'] = $user['id'];
            
            if ($user['role'] == "STUDENT") {
                header("Location: studentview.php");
                exit;
            } elseif ($user['role'] == "PARENT") {
                header("Location: parentview.php");
                exit;
            } elseif ($user['role'] == "ADMIN") {
                header("Location: adminview.php");
                exit;
            } else {
                header("Location: dashboard.php");
                exit;
            }
        } else {
            // Invalid password
            $_SESSION['error_message'] = "Invalid username or password.";
            header("Location: index.php");
            exit;
        }
    } else {
        // Username doesn't exist
        $_SESSION['error_message'] = "Invalid username or password.";
        header("Location: index.php");
        exit;
    }
}

$conn->close();
?>