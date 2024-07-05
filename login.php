<?php
session_start();
ini_set('session.cookie_httponly', true);

include 'db_conn.php'; // Include database connection details
require_once 'errors/error_handler.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $captcha = $_POST['captcha'];

    // Validate CAPTCHA
    if (empty($_SESSION['captcha']) || $_SESSION['captcha'] !== $captcha) {
        $_SESSION['error_message'] = "Captcha code is incorrect. Please try again.";
        header("Location: index.php?error=captcha");
        exit();
    }

    // Prepare and execute SQL query to fetch user
    $sql = "SELECT * FROM Users WHERE UserName = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // Error handling for prepare statement
        $_SESSION['error_message'] = "Error preparing SQL statement: " . $conn->error;
        header("Location: index.php?error=database");
        exit();
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    

    $result = $stmt->get_result();
    // Check for errors during execution
if (!$result) {
    // Error handling for execute statement
    $_SESSION['error_message'] = "Error executing SQL statement: " . $stmt->error;
    header("Location: index.php?error=database");
    exit();
}

    // Close the prepared statement after retrieving the result
    $stmt->close();

    // Check if a user with the provided username exists
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        // Verify the hashed password
        $hashedPassword = $row['PasswordHash'];
        $salt = $row['Salt'];
        if (password_verify($password. $salt, $hashedPassword)) {
            $session_id = uniqid();

            $update_sql = "UPDATE Users SET session_id = ? WHERE Id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $session_id, $row['Id']);
            $update_stmt->execute();

            // Redirect based on user type
            if ($row['UserType'] == 'Admin') {
                $_SESSION['admin_user_id'] = $row['Id'];
                $_SESSION['admin_username'] = $row['UserName'];
                $_SESSION['admin_type'] = $row['UserType'];
                $_SESSION['admin_session_id'] = $session_id;
                header("Location: Admin/dashboard.php");
                exit();
            } else if ($row['UserType'] == 'Teacher') {
                $_SESSION['teacher_user_id'] = $row['Id'];
                $_SESSION['teacher_username'] = $row['UserName'];
                $_SESSION['teacher_type'] = $row['UserType'];
                $_SESSION['teacher_session_id'] = $session_id;
                header("Location: Lecturer/dashboard.php");
                exit();
            }
        } else {
            // Incorrect password
            $_SESSION['error_message'] = "Invalid password. Please try again.";
            header("Location: index.php?error=invalid");
            exit();
        }
    } else {
        // User not found
        $_SESSION['error_message'] = "Invalid username. Please try again.";
        header("Location: index.php?error=invalid");
        exit();
    }

} else {
    // Redirect to the index page if not a POST request
    header("Location: index.php");
    exit();
}
?>