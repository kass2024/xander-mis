<?php
declare(strict_types=1);

session_start();

unset(
    $_SESSION['institution_account_id'],
    $_SESSION['institution_university_id'],
    $_SESSION['institution_email'],
    $_SESSION['institution_name'],
    $_SESSION['institution_university_name']
);

require_once __DIR__ . '/../helpers/urls.php';
header('Location: ' . pcvc_url('/institution-login.php'));
exit;
