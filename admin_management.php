<?php
session_start();
include_once 'db_conn.php';

function generateSecurePassword()
{
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $specialChars = '!@#$%^&*()_+-={}[]|;:,.<>?';

    $password = '';
    $password .= substr(str_shuffle($uppercase), 0, 1); // At least one uppercase letter
    $password .= substr(str_shuffle($lowercase), 0, 1); // At least one lowercase letter
    $password .= substr(str_shuffle($numbers), 0, 1); // At least one number
    $password .= substr(str_shuffle($specialChars), 0, 2); // At least two special characters
    $password .= substr(str_shuffle($uppercase . $lowercase . $numbers . $specialChars), 0, 5); // Remaining random characters

    $salt = uniqid(mt_rand(), true); // Generate a unique salt
    $hash = password_hash($password . $salt, PASSWORD_DEFAULT); // Hash password with salt

    return array($hash, $salt, $password); // Return hash, salt, and plain password
}

// Handle form submission (Add, Update, Delete Admin)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'addAdmin') {
            // Count existing admins
            $countAdminsSql = "SELECT COUNT(*) AS adminCount FROM Admin";
            $countResult = $conn->query($countAdminsSql);

            if ($countResult && $countResult->num_rows > 0) {
                $row = $countResult->fetch_assoc();
                $adminCount = intval($row['adminCount']);

                if ($adminCount >= 2) {
                    $_SESSION['error_message'] = 'You can only have a maximum of two admins.';
                    header("Location: admin_management.php");
                    exit();
                }
            }

            // Add new admin
            $adminName = isset($_POST['adminName']) ? trim($_POST['adminName']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
            $address = isset($_POST['address']) ? trim($_POST['address']) : '';

            if (empty($adminName) || empty($email) || empty($phone) || empty($address) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error_message'] = 'Please fill all required fields correctly.';
            } else {
                // Generate a unique username based on admin name
                $adminNameWithoutSpaces = str_replace(' ', '.', $adminName);
                $uniqueId = substr(uniqid(), -2); // Extract last two characters of uniqid for two numbers
                $username = $adminNameWithoutSpaces . '@' . $uniqueId;

                // Generate a secure password and retrieve hash, salt, and plain password
                list($passwordHash, $salt, $password) = generateSecurePassword();

                // Insert into Admin table
                $insertAdminSql = "INSERT INTO Admin (Name, Email, Phone, Address) VALUES (?, ?, ?, ?)";
                $insertAdminStmt = $conn->prepare($insertAdminSql);

                if ($insertAdminStmt) {
                    $insertAdminStmt->bind_param("ssss", $adminName, $email, $phone, $address);

                    if ($insertAdminStmt->execute()) {
                        // Get the inserted admin's ID
                        $adminId = $insertAdminStmt->insert_id;

                        // Insert into Users table
                        $userType = 'Admin';
                        $insertUserSql = "INSERT INTO Users (UserName, PasswordHash, Salt, AdminID, UserType) VALUES (?, ?, ?, ?, ?)";
                        $insertUserStmt = $conn->prepare($insertUserSql);

                        if ($insertUserStmt) {
                            $insertUserStmt->bind_param("sssis", $username, $passwordHash, $salt, $adminId, $userType);

                            if ($insertUserStmt->execute()) {
                                // Save admin details to admins.txt
                                $adminDetails = "Admin Name: $adminName\nUsername: $username\nPassword: $password\n";
                                file_put_contents("etc/admins.txt", $adminDetails . PHP_EOL, FILE_APPEND);

                                $_SESSION['success_message'] = 'Admin added successfully. ' . $adminName . ', this '.$password.' is your password. Keep it safely.';
                            } else {
                                $_SESSION['error_message'] = 'Failed to add admin user.';
                            }

                            $insertUserStmt->close();
                        } else {
                            $_SESSION['error_message'] = 'Failed to prepare user insertion.';
                        }
                    } else {
                        $_SESSION['error_message'] = 'Failed to add admin.';
                    }

                    $insertAdminStmt->close();
                } else {
                    $_SESSION['error_message'] = 'Failed to prepare admin insertion.';
                }
            }
        } elseif ($_POST['action'] == 'updateAdmin') {
            // Update existing admin
            $adminId = isset($_POST['adminId']) ? intval($_POST['adminId']) : 0;
            $adminName = isset($_POST['adminName']) ? trim($_POST['adminName']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
            $address = isset($_POST['address']) ? trim($_POST['address']) : '';

            if ($adminId > 0 && !empty($adminName) && !empty($email) && !empty($phone) && !empty($address) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $updateAdminSql = "UPDATE Admin SET Name=?, Email=?, Phone=?, Address=? WHERE ID=?";
                $updateAdminStmt = $conn->prepare($updateAdminSql);

                if ($updateAdminStmt) {
                    $updateAdminStmt->bind_param("ssssi", $adminName, $email, $phone, $address, $adminId);

                    if ($updateAdminStmt->execute()) {
                        $_SESSION['success_message'] = 'Admin updated successfully.';
                    } else {
                        $_SESSION['error_message'] = 'Failed to update admin.';
                    }

                    $updateAdminStmt->close();
                } else {
                    $_SESSION['error_message'] = 'Failed to prepare admin update.';
                }
            } else {
                $_SESSION['error_message'] = 'Invalid input for admin update.';
            }
        } elseif ($_POST['action'] == 'deleteAdmin') {
            // Delete admin
            $adminId = isset($_POST['adminId']) ? intval($_POST['adminId']) : 0;

            if ($adminId > 0) {
                $deleteUserSql = "DELETE FROM Users WHERE AdminID=?";
                $deleteUserStmt = $conn->prepare($deleteUserSql);

                if ($deleteUserStmt) {
                    $deleteUserStmt->bind_param("i", $adminId);

                    if ($deleteUserStmt->execute()) {
                        // Now delete from Admin table
                        $deleteAdminSql = "DELETE FROM Admin WHERE ID=?";
                        $deleteAdminStmt = $conn->prepare($deleteAdminSql);

                        if ($deleteAdminStmt) {
                            $deleteAdminStmt->bind_param("i", $adminId);

                            if ($deleteAdminStmt->execute()) {
                                $_SESSION['success_message'] = 'Admin deleted successfully.';
                            } else {
                                $_SESSION['error_message'] = 'Failed to delete admin.';
                            }

                            $deleteAdminStmt->close();
                        } else {
                            $_SESSION['error_message'] = 'Failed to prepare admin deletion.';
                        }
                    } else {
                        $_SESSION['error_message'] = 'Failed to delete admin user.';
                    }

                    $deleteUserStmt->close();
                } else {
                    $_SESSION['error_message'] = 'Failed to prepare user deletion.';
                }
            } else {
                $_SESSION['error_message'] = 'Invalid admin ID for deletion.';
            }
        }
        header("Location: admin_management.php");
        exit();
    } else {
        $_SESSION['error_message'] = 'Invalid action.';
        header("Location: admin_management.php");
        exit();
    }
}

// Fetch all admins from Admin table
$admins = [];
$selectAdminsSql = "SELECT ID, Name, Email, Phone, Address FROM Admin";
$result = $conn->query($selectAdminsSql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <style>
        /* Base styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            padding: 20px;
        }

        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            max-width: 800px; /* Limit maximum width */
            margin: 0 auto; /* Center container */
        }

        .container h2 {
            color: #333;
        }

        .table-container {
            margin: 0 auto;
            overflow-x: auto; /* Enable horizontal scrolling on small screens */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th,
        table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .btn {
            display:inline;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-primary {
            background-color: #007bff;
            color: #fff;
        }

        .btn-danger {
            background-color: #dc3545;
            color: #fff;
        }

        .form-group {
            margin-bottom: 10px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box; /* Ensure padding is included in width */
        }

        .form-group .btn {
            width: auto;
            display: inline-block;
        }

        .error-message,
        .success-message {
            color: green;
            margin-bottom: 10px;
        }

        @media screen and (max-width: 768px) {
            /* Adjust table layout for smaller screens */
            .container {
                padding: 10px;
            }

            .table-container {
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
            }

            table {
                min-width: 400px; /* Ensure a minimum width for readability */
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Admin Management</h2>
        <?php
        if (isset($_SESSION['error_message'])) {
            echo '<div class="error-message">' . $_SESSION['error_message'] . '</div>';
            unset($_SESSION['error_message']);
        }
        if (isset($_SESSION['success_message'])) {
            echo '<div class="success-message">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']);
        }
        ?>

        <!-- Add Admin Form -->
        <div class="form-container">
            <h3>Add New Admin</h3>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="action" value="addAdmin">
                <div class="form-group">
                    <input type="text" name="adminName" placeholder="Admin Name" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="text" name="phone" placeholder="Phone" required>
                </div>
                <div class="form-group">
                    <input type="text" name="address" placeholder="Address" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Add Admin</button>
                </div>
            </form>
        </div>

        <!-- Admin Table -->
        <div class="table-container">
            <h3>Admin List</h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($admins as $admin) {
                        echo '<tr>';
                        echo '<td>' . $admin['Name'] . '</td>';
                        echo '<td>' . $admin['Email'] . '</td>';
                        echo '<td>' . $admin['Phone'] . '</td>';
                        echo '<td>' . $admin['Address'] . '</td>';
                        echo '<td style="display:flex; padding-bottom:15px">';
                        echo '<form action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '" method="post">';
                        echo '<input type="hidden" name="action" value="deleteAdmin">';
                        echo '<input type="hidden" name="adminId" value="' . $admin['ID'] . '">';
                        echo '<button type="submit" class="btn btn-danger" onclick="return confirm(\'Are you sure you want to delete this admin?\')">Delete</button>';
                        echo '</form>';
                        echo '<button class="btn btn-primary" style="margin-left:5px" onclick="showUpdateForm(' . $admin['ID'] . ', \'' . $admin['Name'] . '\', \'' . $admin['Email'] . '\', \'' . $admin['Phone'] . '\', \'' . $admin['Address'] . '\')">Update</button>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Update Admin Form (Initially hidden) -->
        <div id="updateFormContainer" style="display: none;">
            <h3>Update Admin</h3>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="action" value="updateAdmin">
                <input type="hidden" id="adminId" name="adminId">
                <div class="form-group">
                    <input type="text" id="updateName" name="adminName" placeholder="Admin Name" required>
                </div>
                <div class="form-group">
                    <input type="email" id="updateEmail" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="text" id="updatePhone" name="phone" placeholder="Phone" required>
                </div>
                <div class="form-group">
                    <input type="text" id="updateAddress" name="address" placeholder="Address" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Admin</button>
                    <button type="button" onclick="hideUpdateForm()" class="btn btn-danger">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showUpdateForm(adminId, name, email, phone, address) {
            document.getElementById('adminId').value = adminId;
            document.getElementById('updateName').value = name;
            document.getElementById('updateEmail').value = email;
            document.getElementById('updatePhone').value = phone;
            document.getElementById('updateAddress').value = address;
            document.getElementById('updateFormContainer').style.display = 'block';
        }

        function hideUpdateForm() {
            document.getElementById('updateFormContainer').style.display = 'none';
        }
    </script>
</body>

</html>
