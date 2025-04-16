<?php
header('Content-Type: application/json');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$db_host = "localhost";
$db_name = "finalprj";  // Same as in db.php
$db_user = "root";
$db_pass = "";

// Initialize response
$response = ['success' => false, 'message' => ''];

try {
    // Connect to the database
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // No need for action here
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username)) throw new Exception("Username is required");
    if (empty($password)) throw new Exception("Password is required");

    $stmt = $conn->prepare("SELECT id, username, email, password FROM signup WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_active'] = time();

            $response['success'] = true;
            $response['message'] = "Login successful";
            $response['redirect'] = "dashboard.php";
        } else {
            throw new Exception("Invalid username or password");
        }
    } else {
        throw new Exception("Invalid username or password");
    }

} catch(Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    if (isset($conn)) $conn->close();
    echo json_encode($response);
}
