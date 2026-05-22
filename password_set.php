<?php
require_once 'db.php';

// $username = 'admin';
// $password = 'admin123'; // Change this before going live!
// $fullName = 'Administrator';
// $username = 'yvette';
// $password = 'kigali@2025'; // Change this before going live!
// $fullName = 'Yvette';
$username = 'yvette';
$password = 'kigali@2025'; // Change this before going live!
$fullName = 'Yvette';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO admins (username, password_hash, full_name) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $passwordHash, $fullName);

if ($stmt->execute()) {
    $newAdminId = (int) $conn->insert_id;
    // Start with N/A menu access; superadmin grants permissions later via Menu Access.
    require_once __DIR__ . '/helpers/admin_menu_permissions.php';
    xander_admin_menu_init_empty_for_admin($conn, $newAdminId, $newAdminId);
    echo "Admin created successfully.";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
