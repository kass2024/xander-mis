<?php
declare(strict_types=1);

require_once __DIR__ . '/prescreening_options.php';

/**
 * Update work destination for a job application (superadmin).
 *
 * @return array{destination_label:string,work_country_id:int,prescreen_updated:bool}
 */
function xander_job_update_application_destination(
    mysqli $conn,
    int $applicationId,
    int $workCountryId,
    string $destinationsCsv = ''
): array {
    $chk = $conn->prepare('SELECT id, user_id FROM job_applications WHERE id = ? LIMIT 1');
    if (!$chk) {
        throw new RuntimeException('Database error');
    }
    $chk->bind_param('i', $applicationId);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();
    if (!$row) {
        throw new InvalidArgumentException('Application not found');
    }

    $userId = (string) ($row['user_id'] ?? '');
    $countryName = '';
    if ($workCountryId > 0) {
        $cn = $conn->prepare('SELECT name FROM countries WHERE id = ? LIMIT 1');
        if ($cn) {
            $cn->bind_param('i', $workCountryId);
            $cn->execute();
            $cRow = $cn->get_result()->fetch_assoc();
            $cn->close();
            $countryName = trim((string) ($cRow['name'] ?? ''));
        }
    }

    $destStore = '';
    $destinationsCsv = trim($destinationsCsv);
    if ($destinationsCsv !== '') {
        $parts = array_map('trim', explode(',', $destinationsCsv));
        $parts = array_values(array_filter($parts, static fn (string $p): bool => $p !== ''));
        if ($parts !== []) {
            $destStore = implode(', ', $parts);
        }
    } elseif ($countryName !== '') {
        $destStore = $countryName;
    }

    $wcId = $workCountryId > 0 ? $workCountryId : 0;
    $up = $conn->prepare('UPDATE job_applications SET work_country_id = ? WHERE id = ? LIMIT 1');
    if (!$up) {
        throw new RuntimeException('Update failed');
    }
    $up->bind_param('ii', $wcId, $applicationId);
    $up->execute();
    $up->close();

    $prescreenUpdated = false;
    if ($userId !== '' && $destStore !== '') {
        $psId = 0;
        $find = $conn->prepare(
            "SELECT id FROM prescreening_submissions
             WHERE user_id = ? AND service_type = 'work_abroad'
             ORDER BY id DESC LIMIT 1"
        );
        if ($find) {
            $find->bind_param('s', $userId);
            $find->execute();
            $psRow = $find->get_result()->fetch_assoc();
            $find->close();
            if ($psRow) {
                $psId = (int) $psRow['id'];
            }
        }
        if ($psId > 0) {
            $ps = $conn->prepare('UPDATE prescreening_submissions SET work_country_destination = ? WHERE id = ? LIMIT 1');
            if ($ps) {
                $ps->bind_param('si', $destStore, $psId);
                $ps->execute();
                $prescreenUpdated = $ps->affected_rows > 0;
                $ps->close();
            }
        }
    }

    $label = $destStore !== '' ? $destStore : ($countryName !== '' ? $countryName : '—');

    return [
        'destination_label' => $label,
        'work_country_id' => $wcId,
        'prescreen_updated' => $prescreenUpdated,
    ];
}
