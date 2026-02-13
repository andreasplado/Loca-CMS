<?php
require_once 'core/config.php';

// This will create a secure hash for the password "admin123"
$new_password = password_hash('admin123', PASSWORD_DEFAULT);

// Update the user 'admin'
$stmt = $db->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
if($stmt->execute([$new_password])) {
    echo "Password successfully reset to: admin123";
    echo "<br>Please delete this file (reset.php) immediately for security!";
} else {
    echo "Error updating password.";
}
?>