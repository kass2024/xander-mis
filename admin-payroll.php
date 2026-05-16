<?php
session_start();
require_once 'db.php';

date_default_timezone_set('Africa/Kigali');

/* ----------------------- Auth / Role ----------------------- */
$admin_id = $_SESSION['admin_id'] ?? $_SESSION['id'] ?? null;
if (!$admin_id) {
    http_response_code(403);
    exit("🔐 Access denied. Login required.");
}

$stmt = $conn->prepare("SELECT role, full_name FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($user_role, $user_name);
$stmt->fetch();
$stmt->close();

if (!in_array($user_role, ['staff', 'superadmin'])) {
    http_response_code(403);
    exit("⛔ Access denied. Staff or Superadmin only.");
}

/* ----------------------- Date Filters ----------------------- */
$selected_month = $_GET['month'] ?? date('Y-m');              // format: YYYY-MM
$start_month    = date('Y-m-01', strtotime($selected_month));
$end_month      = date('Y-m-t',  strtotime($selected_month));

/* Build an array of all *weekdays* in the month (exclude Sat=6, Sun=7 by ISO) */
$all_dates = [];
$cursor = strtotime($start_month);
$endTs  = strtotime($end_month);
while ($cursor <= $endTs) {
    $isoDow = (int)date('N', $cursor); // 1=Mon ... 7=Sun
    if ($isoDow <= 5) {                // Mon-Fri only
        $all_dates[] = date('Y-m-d', $cursor);
    }
    $cursor = strtotime('+1 day', $cursor);
}

/* ----------------------- Load Admins ----------------------- */
$admins = []; // id => meta
$res = $conn->query("SELECT id, full_name, role, salary_per_minute FROM admins WHERE role IN ('staff','superadmin')");
while ($row = $res->fetch_assoc()) {
    $admins[(int)$row['id']] = [
        'id'    => (int)$row['id'],
        'name'  => $row['full_name'],
        'role'  => $row['role'],
        'rate'  => (float)$row['salary_per_minute'],
        'total_minutes' => 0,
        'total_salary'  => 0.0,
        'days'  => array_fill_keys($all_dates, ['minutes' => 0, 'salary' => 0.0]),
    ];
}
$res->free();

/* Early exit if empty */
if (empty($admins)) {
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Smart Payroll Report</title><style> body{font-family:Arial;padding:30px}</style></head>
    <body>
        <h1>🧾 Admin Payroll Report</h1>
        <p>👤 Logged in as: <strong><?= htmlspecialchars($user_name) ?> (<?= htmlspecialchars($user_role) ?>)</strong></p>
        <p>No staff/superadmin users found.</p>
    </body>
    </html>
    <?php exit;
}

/* ----------------------- Load Attendance (month) ----------------------- */
$sql = "
    SELECT admin_id, date, total_work_minutes
    FROM attendance
    WHERE date BETWEEN ? AND ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_month, $end_month);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $aid   = (int)$row['admin_id'];
    $date  = $row['date'];
    $mins  = (int)$row['total_work_minutes'];

    // Skip weekends
    $isoDow = (int)date('N', strtotime($date));
    if ($isoDow >= 6) continue;

    if (!isset($admins[$aid])) continue;
    if (!isset($admins[$aid]['days'][$date])) continue; // only weekdays in map

    $rate   = $admins[$aid]['rate'];
    $salary = round($rate * $mins);

    $admins[$aid]['days'][$date]['minutes'] = $mins;
    $admins[$aid]['days'][$date]['salary']  = $salary;

    $admins[$aid]['total_minutes'] += $mins;
    $admins[$aid]['total_salary']  += $salary;
}
$stmt->close();

/* ----------------------- Helpers ----------------------- */
function rwf($n) { return number_format((float)$n, 0); }
function ratefmt($n) { return number_format((float)$n, 2); }

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Smart Payroll Report</title>
<style>
:root{
    --ink:#0b1220; --muted:#6b7280;
    --line:#e5e7eb; --surface:#ffffff; --bg:#f7f9fc;
    --accent:#0a5cff; --ok:#16a34a;
    --radius:12px; --shadow:0 10px 24px rgba(10,20,40,.06);
}
*{box-sizing:border-box}
body { font-family: Arial, sans-serif; background:var(--bg); color:var(--ink); padding: 28px; }
h1 { margin: 0 0 8px; }
.subtle { color: var(--muted); margin: 2px 0 18px; }

.toolbar { display:flex; gap:.75rem; align-items:center; margin: 12px 0 24px; flex-wrap: wrap; }
.toolbar input[type="month"]{ padding:.5rem .6rem; border:1px solid var(--line); border-radius:8px; background:#fff; }
.toolbar button{ padding:.5rem .9rem; border:none; border-radius:8px; background:var(--accent); color:#fff; cursor:pointer; }
.toolbar .ghost{ background:#fff; color:#111; border:1px solid var(--line); }

.card { background:var(--surface); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow); }
.card + .card { margin-top:16px; }

table { border-collapse: collapse; width: 100%; }
th, td { border-bottom:1px solid var(--line); padding:10px 12px; text-align:left; }
thead th { background:#f3f6fb; font-weight:700; }
tfoot td { background:#fafbff; font-weight:700; }

.admin-header { display:flex; align-items:center; justify-content:space-between; padding:14px 16px; }
.admin-name { font-weight:800; }
.pill { font-size:.85rem; color:#0a5c27; background:#e8f7ed; padding:.15rem .5rem; border-radius:999px; border:1px solid #cfeeda; }
.meta { color:var(--muted); font-size:.9rem; }
.actions { display:flex; gap:.5rem; align-items:center; }
.actions .ghost{cursor:pointer}

.collapse { display:none; }
.collapse.open { display:block; }

.right { text-align:right; }
.ok { color: var(--ok); font-weight:700; }

/* ---------- Print styles ---------- */
@media print {
    body { background:#fff; }
    .toolbar { display:none !important; }
    .card { box-shadow:none; border:1px solid #ddd; }
    /* Only print the chosen admin block when using “Print details” */
    body.printing .card,
    body.printing .summary { display:none !important; }
    body.printing .print-target { display:block !important; }
}
</style>
</head>
<body>
    <h1>🧾 Admin Payroll Report</h1>
    <p class="subtle">👤 Logged in as: <strong><?= htmlspecialchars($user_name) ?></strong> (<?= htmlspecialchars($user_role) ?>)</p>

    <form method="get" class="toolbar">
        <label for="m">Select Month:</label>
        <input id="m" type="month" name="month" value="<?= htmlspecialchars($selected_month) ?>" />
        <button type="submit">Filter</button>
        <button type="button" class="ghost" onclick="window.print()">🖨️ Print Page</button>
        <button type="button" class="ghost" onclick="toggleAll(true)">Expand all</button>
        <button type="button" class="ghost" onclick="toggleAll(false)">Collapse all</button>
    </form>

    <!-- Summary table (no Actions column, per your request) -->
    <div class="card summary">
        <table>
            <thead>
                <tr>
                    <th>Admin</th>
                    <th>Role</th>
                    <th class="right">Rate (RWF/min)</th>
                    <th class="right">Total Minutes (Mon–Fri, <?= date('F Y', strtotime($selected_month)) ?>)</th>
                    <th class="right">Total Salary (RWF)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($admins as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['name']) ?></td>
                    <td><?= htmlspecialchars($a['role']) ?></td>
                    <td class="right"><?= ratefmt($a['rate']) ?></td>
                    <td class="right"><?= number_format($a['total_minutes']) ?></td>
                    <td class="right ok"><?= rwf($a['total_salary']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Per-admin detailed daily breakdown (weekdays only) -->
    <?php foreach ($admins as $a): ?>
        <div
          class="card"
          id="card-<?= $a['id'] ?>"
          data-admin-name="<?= htmlspecialchars($a['name']) ?>"
          data-admin-role="<?= htmlspecialchars($a['role']) ?>"
          data-admin-rate="<?= ratefmt($a['rate']) ?>"
          data-total-mins="<?= number_format($a['total_minutes']) ?>"
          data-total-salary="<?= rwf($a['total_salary']) ?>"
          data-month-label="<?= htmlspecialchars(date('F Y', strtotime($selected_month))) ?>"
        >
            <div class="admin-header">
                <div>
                    <div class="admin-name"><?= htmlspecialchars($a['name']) ?></div>
                    <div class="meta">Role: <?= htmlspecialchars($a['role']) ?> • Rate: <?= ratefmt($a['rate']) ?> RWF/min</div>
                </div>
                <div class="actions">
                    <button class="ghost" onclick="toggleDetails(<?= $a['id'] ?>)" type="button">Toggle details</button>
                    <button class="ghost" onclick="printDetails(<?= $a['id'] ?>)" type="button" title="Print only this admin">🖨️ Print details</button>
                    <span class="pill" title="Total Salary (RWF)">RWF <?= rwf($a['total_salary']) ?></span>
                </div>
            </div>

            <!-- This block becomes the print target when “Print details” is clicked -->
            <div class="collapse" id="details-<?= $a['id'] ?>">
                <div class="print-scope">
                    <h2 style="margin:10px 16px 0 16px;">Admin Payroll (Weekdays Only) — <span class="admin-title"><?= htmlspecialchars($a['name']) ?></span></h2>
                    <p style="margin:4px 16px 12px 16px;color:#6b7280;">
                        <span class="month-label"><?= htmlspecialchars(date('F Y', strtotime($selected_month))) ?></span> •
                        Role: <?= htmlspecialchars($a['role']) ?> •
                        Rate: <?= ratefmt($a['rate']) ?> RWF/min
                    </p>
                    <table>
                        <thead>
                            <tr>
                                <th style="width:25%">Date (Mon–Fri)</th>
                                <th style="width:25%" class="right">Work Minutes</th>
                                <th style="width:25%" class="right">Salary (RWF)</th>
                                <th style="width:25%">Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_dates as $d):
                                $mins   = $a['days'][$d]['minutes'] ?? 0;
                                $salary = $a['days'][$d]['salary']  ?? 0;
                                $note   = $mins > 0 ? 'Present' : '—';
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($d) ?></td>
                                    <td class="right"><?= number_format($mins) ?></td>
                                    <td class="right"><?= rwf($salary) ?></td>
                                    <td><?= $note ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total</td>
                                <td class="right"><?= number_format($a['total_minutes']) ?></td>
                                <td class="right"><?= rwf($a['total_salary']) ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

<script>
function toggleDetails(id){
    var el = document.getElementById('details-' + id);
    if (!el) return;
    el.classList.toggle('open');
}

/* Print only ONE admin’s details using Chrome’s native Print→Save as PDF */
function printDetails(id){
    const card = document.getElementById('card-' + id);
    const details = document.getElementById('details-' + id);
    if (!card || !details) return;

    // Ensure details are visible for printing
    if (!details.classList.contains('open')) details.classList.add('open');

    // Mark this card as the only print target
    document.body.classList.add('printing');
    card.classList.add('print-target');

    // Trigger print
    window.print();

    // Cleanup after printing
    setTimeout(() => {
        document.body.classList.remove('printing');
        card.classList.remove('print-target');
    }, 300);
}

function toggleAll(open){
    document.querySelectorAll('.collapse').forEach(n => {
        if (open) n.classList.add('open'); else n.classList.remove('open');
    });
}
</script>
</body>
</html>
