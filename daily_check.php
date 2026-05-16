<?php
require_once 'db.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
require_once 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$dateToday = date('Y-m-d');

// For summary report
$checkedInOutList = '';
$onlyCheckInList = '';
$noCheckInList = '';

// Fetch all admins
$admins = $conn->query("SELECT id, full_name, email, role, salary_per_minute FROM admins WHERE role IN ('staff', 'superadmin')");

while ($admin = $admins->fetch_assoc()) {
    $adminId = $admin['id'];
    $name = $admin['full_name'];
    $email = $admin['email'];
    $role = $admin['role'];
    $salaryPerMinute = $admin['salary_per_minute'];

    // Get today's attendance
    $stmt = $conn->prepare("SELECT check_in_time, check_out_time, total_work_minutes, daily_salary_rwf FROM attendance WHERE admin_id = ? AND date = ?");
    $stmt->bind_param("is", $adminId, $dateToday);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        // No attendance at all
        $insert = $conn->prepare("INSERT IGNORE INTO job_summary (admin_id, summary_date, total_jobs, total_hours, avg_productivity_score) VALUES (?, ?, 0, 0, 0)");
        $insert->bind_param("is", $adminId, $dateToday);
        $insert->execute();
        $insert->close();

        $noCheckInList .= "<li><strong>$name</strong> (No Check-in)</li>";

        // Send warning only to staff
        if ($role === 'staff' && !empty($email)) {
            try {
                $mail = new PHPMailer(true);
                $mail->CharSet = 'UTF-8';
                $mail->Encoding = 'base64';
                $mail->isSMTP();
                $mail->Host = 'visaconsultantcanada.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'admission@visaconsultantcanada.com';
                $mail->Password = getenv('SMTP_PASSWORD') ?: 'Petero@1981';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;
                $mail->setFrom('admission@visaconsultantcanada.com', 'Parrot-Canada');
                $mail->addAddress($email, $name);
                $mail->isHTML(true);
                $mail->Subject = "⚠️ No Check-in Detected on $dateToday";
                $mail->Body = "
                    <p>Dear $name,</p>
                    <p>Our system shows that you did <strong>not check in or check out</strong> on <strong>$dateToday</strong>.</p>
                    <p>This means you earned <strong>nothing today</strong>. Please remember to check in daily.</p>
                    <p>Best regards,<br>Parrot Canada Admin Team</p>
                ";
                $mail->send();
            } catch (Exception $e) {
                error_log("Warning Email Error to $email: " . $e->getMessage());
            }
        }
    } else {
        // Attendance exists — fetch values
        $stmt->bind_result($checkIn, $checkOut, $minutesWorked, $salary);
        $stmt->fetch();

        if (!empty($checkIn) && empty($checkOut)) {
            // Checked in only
            $onlyCheckInList .= "<li><strong>$name</strong> (Checked-in only)</li>";

            if ($role === 'staff' && !empty($email)) {
                try {
                    $mail = new PHPMailer(true);
                    $mail->CharSet = 'UTF-8';
                    $mail->Encoding = 'base64';
                    $mail->isSMTP();
                    $mail->Host = 'visaconsultantcanada.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'admission@visaconsultantcanada.com';
                    $mail->Password = getenv('SMTP_PASSWORD') ?: 'Petero@1981';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port = 465;
                    $mail->setFrom('admission@visaconsultantcanada.com', 'Parrot-Canada');
                    $mail->addAddress($email, $name);
                    $mail->isHTML(true);
                    $mail->Subject = "⚠️ Check-in without Check-out – $dateToday";
                    $mail->Body = "
                        <p>Dear $name,</p>
                        <p>Our system shows that you <strong>checked in</strong> but did <strong>not check out</strong> on <strong>$dateToday</strong>.</p>
                        <p>Please remember to check out to ensure proper salary calculation.</p>
                        <p>Best regards,<br>Parrot Canada Admin Team</p>
                    ";
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Half Attendance Email Error to $email: " . $e->getMessage());
                }
            }

        } elseif (!empty($checkIn) && !empty($checkOut)) {
            // Full attendance
            $checkedInOutList .= "<li><strong>$name</strong> – $minutesWorked mins – RWF $salary</li>";

            if ($role === 'staff' && !empty($email)) {
                try {
                    $mail = new PHPMailer(true);
                    $mail->CharSet = 'UTF-8';
                    $mail->Encoding = 'base64';
                    $mail->isSMTP();
                    $mail->Host = 'visaconsultantcanada.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'admission@visaconsultantcanada.com';
                    $mail->Password = getenv('SMTP_PASSWORD') ?: 'Petero@1981';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port = 465;
                    $mail->setFrom('admission@visaconsultantcanada.com', 'Parrot-Canada');
                    $mail->addAddress($email, $name);
                    $mail->isHTML(true);
                    $mail->Subject = "✅ Salary Summary for $dateToday";
                    $mail->Body = "
                        <p>Dear $name,</p>
                        <p>You have successfully checked in and out today <strong>($dateToday)</strong>.</p>
                        <p><strong>Total Minutes Worked:</strong> $minutesWorked minutes<br>
                           <strong>Salary Earned:</strong> RWF $salary</p>
                        <p>Keep up the good work!</p>
                        <p>Best regards,<br>Parrot Canada Admin Team</p>
                    ";
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Salary Email Error to $email: " . $e->getMessage());
                }
            }
        }
    }

    $stmt->close();
}

// Send Summary to Superadmins only
$superadmins = $conn->query("SELECT full_name, email FROM admins WHERE role = 'superadmin'");
if ($superadmins->num_rows > 0) {
    try {
        $reportMail = new PHPMailer(true);
        $reportMail->CharSet = 'UTF-8';
        $reportMail->Encoding = 'base64';
        $reportMail->isSMTP();
        $reportMail->Host = 'visaconsultantcanada.com';
        $reportMail->SMTPAuth = true;
        $reportMail->Username = 'admission@visaconsultantcanada.com';
        $reportMail->Password = getenv('SMTP_PASSWORD') ?: 'Petero@1981';
        $reportMail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $reportMail->Port = 465;
        $reportMail->setFrom('admission@visaconsultantcanada.com', 'Parrot-Canada');

        while ($super = $superadmins->fetch_assoc()) {
            $reportMail->addAddress($super['email'], $super['full_name']);
        }

        $reportMail->isHTML(true);
        $reportMail->Subject = "📊 Daily Attendance Summary – $dateToday";
        $reportMail->Body = "
            <div style='font-family: Arial, sans-serif; font-size:14px;'>
                <h2>📋 Attendance Report for $dateToday</h2>
                <h3>✅ Full Attendance:</h3>
                <ul>$checkedInOutList</ul>
                <h3>⏳ Check-in Only:</h3>
                <ul>$onlyCheckInList</ul>
                <h3>❌ No Check-in:</h3>
                <ul>$noCheckInList</ul>
                <p style='margin-top:20px; font-size:12px;'>Generated by Parrot-Canada Attendance System</p>
            </div>
        ";
        $reportMail->send();
    } catch (Exception $e) {
        error_log("Summary Email Error: " . $e->getMessage());
    }
}

$conn->close();
echo "✅ Attendance script executed for $dateToday.";
?>
