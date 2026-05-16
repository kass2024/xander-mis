<?php
define('REMINDERS_CRON', true);
require_once __DIR__ . '/../db.php';

require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

mysqli_set_charset($conn,'utf8mb4');

function make_mailer(): PHPMailer {
  $m = new PHPMailer(true);
  $m->isSMTP();
  $m->Host       = 'visaconsultantcanada.com';
  $m->SMTPAuth   = true;
  $m->Username   = 'infos@visaconsultantcanada.com';
  $m->Password   = getenv('SMTP_PASSWORD') ?: 'Petero@1981';
  $m->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
  $m->Port       = 465;
  $m->setFrom('infos@visaconsultantcanada.com', 'Event Reminder');
  return $m;
}

$processed = 0;

/* Grab small batch of unsent, due emails and lock them */
$sql = "SELECT * FROM reminder_emails_queue
        WHERE sent_at IS NULL
          AND scheduled_at_utc <= UTC_TIMESTAMP()
        ORDER BY id ASC
        LIMIT 50
        FOR UPDATE";
$conn->begin_transaction();
$rs = $conn->query($sql);
if (!$rs) { $conn->rollback(); exit("query failed: ".$conn->error.PHP_EOL); }

$items = $rs->fetch_all(MYSQLI_ASSOC);
$conn->commit();

foreach ($items as $row) {
  $id  = (int)$row['id'];
  $to  = trim($row['send_to']);
  if ($to==='') continue;

  try {
    $mail = make_mailer();
    $mail->addAddress($to);
    $mail->isHTML(true);
    $mail->Subject = $row['subject'];
    $mail->Body    = nl2br($row['body']);
    $mail->AltBody = $row['body'];
    $mail->send();

    $stmt = $conn->prepare("UPDATE reminder_emails_queue SET sent_at=UTC_TIMESTAMP(), attempts=attempts+1, last_error=NULL WHERE id=? AND sent_at IS NULL");
    $stmt->bind_param('i',$id);
    $stmt->execute();
    $processed++;
  } catch(Exception $e) {
    $stmt = $conn->prepare("UPDATE reminder_emails_queue SET attempts=attempts+1, last_error=? WHERE id=?");
    $err = $e->getMessage();
    $stmt->bind_param('si',$err,$id);
    $stmt->execute();
  }
}

if (PHP_SAPI === 'cli') echo "[email-sender] processed={$processed} @ ".gmdate('c').PHP_EOL;
