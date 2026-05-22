<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/admin_menu_permissions.php';

// Check if admin is logged in
if (empty($_SESSION['admin_id'])) {
    die("Please log in first.");
}

$admin_id = $_SESSION['admin_id'];
$conn = mysqli_connect($localhost, $username, $password, $dbname);

if (!$conn) {
    die("Database connection failed.");
}

// Get current admin info
$result = mysqli_query($conn, "SELECT * FROM admins WHERE id = '$admin_id'");
if (!$result || mysqli_num_rows($result) === 0) {
    die("Admin not found.");
}
$admin = mysqli_fetch_assoc($result);

echo "<h2>Current Admin Info:</h2>";
echo "<p><strong>Username:</strong> " . htmlspecialchars($admin['username']) . "</p>";
echo "<p><strong>Role:</strong> " . htmlspecialchars($admin['role']) . "</p>";

// Get current menu access
xander_admin_menu_ensure_table($conn);
$menuAccess = xander_admin_menu_resolve($conn, $admin);

echo "<h3>Current Menu Access:</h3>";
echo "<pre>" . print_r($menuAccess, true) . "</pre>";

// Check if marketing is in the access list
$hasMarketing = xander_admin_menu_allowed($menuAccess, 'marketing');
echo "<h3>Marketing Menu Access:</h3>";
echo "<p><strong>Has Access:</strong> " . ($hasMarketing ? "YES" : "NO") . "</p>";

if (!$hasMarketing) {
    echo "<h3>Fixing Marketing Menu Access...</h3>";
    
    // Add marketing to custom permissions
    $currentMenus = $menuAccess['menus'] ?? [];
    $currentSubmenus = $menuAccess['submenus'] ?? [];
    
    // Add marketing if not present
    if (!in_array('marketing', $currentMenus)) {
        $currentMenus[] = 'marketing';
        $currentSubmenus['marketing'] = ['upload-materials.php', 'get-materials.php'];
        
        $permissions = [
            'menus' => $currentMenus,
            'submenus' => $currentSubmenus
        ];
        
        $json = json_encode($permissions);
        $updatedBy = $admin_id;
        
        $stmt = $conn->prepare(
            'INSERT INTO admin_menu_permissions (admin_id, permissions, updated_by)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE permissions = VALUES(permissions), updated_by = VALUES(updated_by)'
        );
        
        if ($stmt) {
            $stmt->bind_param('isi', $admin_id, $json, $updatedBy);
            $result = $stmt->execute();
            $stmt->close();
            
            if ($result) {
                echo "<p style='color: green;'><strong>✅ SUCCESS:</strong> Marketing menu access has been added!</p>";
                echo "<p><a href='admin-dashboard.php'>Go to Admin Dashboard</a></p>";
            } else {
                echo "<p style='color: red;'><strong>❌ ERROR:</strong> Could not update permissions.</p>";
            }
        } else {
            echo "<p style='color: red;'><strong>❌ ERROR:</strong> Database prepare failed.</p>";
        }
    } else {
        echo "<p style='color: orange;'>Marketing was already in the menu array but access check failed. This might be a caching issue.</p>";
    }
} else {
    echo "<p style='color: green;'>Marketing menu access is already enabled. The issue might be elsewhere.</p>";
}

echo "<hr>";
echo "<h3>Debug Info:</h3>";
echo "<p><strong>Session Admin ID:</strong> " . $admin_id . "</p>";
echo "<p><strong>Session Role:</strong> " . ($_SESSION['role'] ?? 'not set') . "</p>";

// Show role defaults for comparison
$roleDefaults = xander_admin_menu_role_defaults();
echo "<h4>Role Defaults for '" . htmlspecialchars($admin['role']) . "':</h4>";
echo "<pre>" . print_r($roleDefaults[$admin['role']] ?? [], true) . "</pre>";

mysqli_close($conn);
?>
