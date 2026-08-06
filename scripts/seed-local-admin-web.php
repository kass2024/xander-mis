<?php
/**
 * One-time local admin bootstrap (also runs automatically from db.php on localhost).
 * Open in browser once if login still fails: http://localhost/xander/scripts/seed-local-admin-web.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

$res = $conn->query('SELECT id, username, email, role, status FROM admins ORDER BY id ASC');
if (!$res) {
    echo 'Error reading admins: ' . $conn->error;
    exit(1);
}

echo "Local admin bootstrap complete.\n\n";
if ($res->num_rows === 0) {
    echo "No admin accounts found. Check PHP error log for seed failures.\n";
    exit(1);
}

while ($row = $res->fetch_assoc()) {
    echo sprintf(
        "id=%s username=%s email=%s role=%s status=%s\n",
        $row['id'],
        $row['username'],
        $row['email'],
        $row['role'],
        $row['status']
    );
}

echo "\nYou can sign in at admin-login.php with your local superadmin credentials.\n";
