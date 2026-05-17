<?php
declare(strict_types=1);

define('XANDER_AI_AUTOFILL_FORM', 'job');

if (session_status() === PHP_SESSION_NONE) {
    session_name('XGS_JOB_FORM');
}

require __DIR__ . '/student_ai_autofill.php';
