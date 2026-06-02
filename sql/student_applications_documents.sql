-- Student application document columns (admin upload / student portal)
-- Auto-migrate: helpers/student_applications_schema.php (runs this file + PHP column checks)
-- CLI: php -r "require 'db.php'; require 'helpers/student_applications_schema.php'; pcvc_student_applications_ensure_schema(\$conn);"

-- Column adds are handled idempotently in PHP (MySQL has no ADD COLUMN IF NOT EXISTS on all versions).
-- Keep this file for any future idempotent DDL (indexes, etc.).
