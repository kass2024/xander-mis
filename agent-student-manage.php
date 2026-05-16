<?php
session_start();
require 'db.php';

// Check login
$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header("Location: admin-login.php");
    exit;
}

// Load agent info
$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM admins WHERE id = " . intval($admin_id)));
$agent_email = $admin['email'] ?? '';
$agent_full_name = $admin['full_name'] ?? '';

// Handle new record submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_new'])) {
    $stmt = $conn->prepare("INSERT INTO student_applications (
        first_name, last_name, email, phone_number, gender, dob, nationality, city, address_line1,
        masters_program, destination, application_date, agent_email
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("sssssssssssss",
        $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone_number'], $_POST['gender'],
        $_POST['dob'], $_POST['nationality'], $_POST['city'], $_POST['address_line1'],
        $_POST['masters_program'], $_POST['destination'], $_POST['application_date'],
        $agent_email
    );
    
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Get this agent’s records only
$query = $conn->prepare("SELECT id, first_name, last_name, email, phone_number, gender, dob, nationality, city, address_line1,
    masters_program, destination, application_date, application_id, application_remarks,
    incomplete_app, submitted,
    admit, i20_sent, sevis_paid, visa_scheduled, visa_approved,
    enrolled, addn_doc, deny, app_start 
    FROM student_applications 
    WHERE agent_email = ?
    ORDER BY 
        visa_approved DESC,
        admit DESC,
        deny DESC,
        submitted DESC,
        id DESC");

$query->bind_param("s", $agent_email);
$query->execute();
$result = $query->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  <title>All Applicants Management Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>
html, body {
  height: 100%; /* 🔥 KEY ADDITION */
}

body {
  background: #eef2f7;
  font-family: 'Segoe UI', 'Helvetica Neue', sans-serif;
  font-size: 16px;
  color: #212529;
  margin: 0;
  padding: 20px;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

h3 {
  font-weight: 700;
  font-size: 28px;
  color: #003366;
  display: flex;
  align-items: center;
  margin-bottom: 20px;
}

h3::before {
  content: '🎓';
  margin-right: 12px;
  font-size: 30px;
}

.search-box {
  max-width: 450px;
  margin-bottom: 20px;
  padding: 10px 15px;
  border-left: 5px solid #0d6efd;
  border-radius: 5px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  font-size: 16px;
}

.scrollable-table-wrapper {
  flex: 1 1 auto;
  overflow-y: auto;
  overflow-x: auto;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  min-height: 0; /* 🔥 KEY ADDITION */
}

.scrollable-table-wrapper table {
  width: max-content;
  min-width: 100%; /* 🔥 KEY ADDITION */
  table-layout: auto;
}

.scrollable-table-wrapper thead th {
  position: sticky;
  top: 0;
  background-color: #003366 !important;
  color: #fff !important;
  z-index: 11;
  text-align: center;
  font-size: 14px;
  text-transform: uppercase;
}

/* === ADD COLUMN WIDTHS TO PREVENT CUTTING === */
.scrollable-table-wrapper table th:nth-child(9),
.scrollable-table-wrapper table th:nth-child(10),
.scrollable-table-wrapper table th:nth-child(11),
.scrollable-table-wrapper table th:nth-child(12),
.scrollable-table-wrapper table th:nth-child(13),
.scrollable-table-wrapper table th:nth-child(14),
.scrollable-table-wrapper table th:nth-child(15) {
  min-width: 240px;
  max-width: 400px;
}

table {
  margin: 0;
  background: #fff;
  border-collapse: collapse;
}

.table td {
  text-align: center;
  vertical-align: middle;
  font-size: 15px;
  padding: 12px 14px;
  white-space: normal;
  word-wrap: break-word;
}

.editable-cell {
  cursor: pointer;
}

.btn-flag {
  margin: 2px 0;
  width: 100%;
  font-size: 12px;
  font-weight: 500;
  padding: 5px 8px;
  border-radius: 5px;
  white-space: nowrap;
}

.btn-flag:disabled {
  font-weight: bold;
  opacity: 0.9;
  color: #fff;
}

.flag-column {
  min-width: 110px;
  max-width: 115px;
}

.form-control-sm {
  font-size: 14px;
  padding: 6px 10px;
  border-radius: 6px;
}

textarea.form-control-sm {
  min-height: 50px;
}

.saving-text {
  font-size: 12px;
  color: #6c757d;
  font-style: italic;
  margin-top: 4px;
}

.nowrap {
  white-space: nowrap;
}

.btn-outline-light {
  border: 1px solid #ccc;
  color: #555;
  background-color: #f8f9fa;
}

.btn-light:disabled {
  color: #000 !important;
  background-color: #f0f0f0 !important;
  border: 1px solid #bbb !important;
}

/* Responsive */
@media (max-width: 1200px) {
  .table td, .scrollable-table-wrapper thead th { font-size: 14px; padding: 10px 10px; }
}

@media (max-width: 992px) {
  .table td, .scrollable-table-wrapper thead th { font-size: 13px; padding: 8px 8px; }
  .btn-flag { font-size: 11px; }
  h3 { font-size: 22px; }
  .form-control-sm { font-size: 13px; }
}

@media (max-width: 768px) {
  .table td, .scrollable-table-wrapper thead th { font-size: 12px; padding: 6px 6px; }
  .btn-flag { font-size: 10px; padding: 4px 6px; }
  h3 { font-size: 20px; }
  .search-box { max-width: 100%; font-size: 14px; padding: 8px 12px; }
}

@media (max-width: 576px) {
  body { padding: 10px; }
  h3 { font-size: 18px; margin-bottom: 15px; }
  .search-box { margin-bottom: 15px; }
  .btn { font-size: 14px; padding: 6px 10px; }
}
</style>


</head>

<body>
<h3>All Applicants Management Portal</h3>
<div class="d-flex mb-3">
  <a href="admin-dashboard.php" class="btn btn-warning me-2">🏠 Back to Admin Dashboard</a>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRecordModal">Add New Record</button>
</div>
<!-- Add Record Modal -->
<div class="modal fade" id="addRecordModal" tabindex="-1" aria-labelledby="addRecordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content rounded-4 shadow-lg">
      <form method="post">
        <div class="modal-header bg-primary text-white rounded-top-4">
          <h5 class="modal-title" id="addRecordModalLabel">Add New Applicant</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6"><input type="text" name="first_name" class="form-control" placeholder="Enter First Name" required></div>
            <div class="col-md-6"><input type="text" name="last_name" class="form-control" placeholder="Enter Last Name" required></div>
            <div class="col-md-6"><input type="email" name="email" class="form-control" placeholder="Enter Email Address" required></div>
            <div class="col-md-6"><input type="text" name="phone_number" class="form-control" placeholder="Enter Phone Number" required></div>
            <div class="col-md-6">
              <select name="gender" class="form-select" required>
                <option value="" selected disabled>Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="col-md-6"><input type="text" name="dob" class="form-control datepicker" placeholder="Date of Birth" required></div>
            <div class="col-md-6"><input type="text" name="nationality" class="form-control" placeholder="Enter Nationality" required></div>
            <div class="col-md-6"><input type="text" name="city" class="form-control" placeholder="Enter City" required></div>
            <div class="col-12"><input type="text" name="address_line1" class="form-control" placeholder="Enter Full Address" required></div>
            <div class="col-md-6"><input type="text" name="masters_program" class="form-control" placeholder="Intended Master's Program" required></div>
            <div class="col-md-6"><input type="text" name="destination" class="form-control" placeholder="Study Destination (e.g., Canada, USA)" required></div>
            <div class="col-12"><input type="text" name="application_date" class="form-control datepicker" placeholder="Application Submission Date" required></div>
          </div>
        </div>
        <div class="modal-footer bg-light rounded-bottom-4">
          <button type="submit" name="add_new" class="btn btn-success px-4">💾 Save</button>
          <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="table-responsive scrollable-table-wrapper">
  <table class="table table-bordered table-hover table-striped bg-white" id="applicantTable">
    <thead class="text-center">
      <tr>
        <th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Gender</th><th>DOB</th>
        <th>Nationality</th><th>City</th><th>Address</th><th>Program</th><th>Destination</th>
        <th>Applied On</th><th>Flags</th><th>App ID</th><th>Remarks</th>
      </tr>
    </thead>
    <tbody>
    <?php $counter = 1; foreach ($students as $s): ?>
      <tr data-row-id="<?= $s['id'] ?>">
        <td><?= $counter++ ?></td>
        <td contenteditable="true" class="editable-cell" data-id="<?= $s['id'] ?>" data-field="first_name"><?= ucfirst($s['first_name']) . ' ' . ucfirst($s['last_name']) ?></td>
        <td contenteditable="true" class="editable-cell" data-id="<?= $s['id'] ?>" data-field="email"><?= $s['email'] ?></td>
        <td contenteditable="true" class="editable-cell" data-id="<?= $s['id'] ?>" data-field="phone_number"><?= $s['phone_number'] ?></td>
        <td contenteditable="true" class="editable-cell" data-id="<?= $s['id'] ?>" data-field="gender"><?= $s['gender'] ?></td>
        <td contenteditable="true" class="editable-cell" data-id="<?= $s['id'] ?>" data-field="dob"><?= $s['dob'] ?></td>
        <td contenteditable="true" class="editable-cell" data-id="<?= $s['id'] ?>" data-field="nationality"><?= $s['nationality'] ?></td>
        <td contenteditable="true" class="editable-cell" data-id="<?= $s['id'] ?>" data-field="city"><?= $s['city'] ?></td>
        <td contenteditable="true" class="editable-cell" data-id="<?= $s['id'] ?>" data-field="address_line1"><?= $s['address_line1'] ?></td>
        <td contenteditable="true" class="editable-cell" data-id="<?= $s['id'] ?>" data-field="masters_program"><?= $s['masters_program'] ?></td>
        <td contenteditable="true" class="editable-cell" data-id="<?= $s['id'] ?>" data-field="destination"><?= $s['destination'] ?></td>
        <td contenteditable="true" class="editable-cell" data-id="<?= $s['id'] ?>" data-field="application_date"><?= $s['application_date'] ?></td>
        <td class="flag-column text-center">
          <div class="flag-wrapper" data-id="<?= $s['id'] ?>">
            <?php
              $flags = [
                'incomplete_app' => ['Incomplete App', 'light'], 'submitted' => ['Submitted', 'secondary'],
                'admit' => ['Admit', 'primary'], 'i20_sent' => ['I-20 Sent', 'info'],
                'sevis_paid' => ['Sevis Paid', 'secondary'], 'visa_scheduled' => ['Visa Sch.', 'warning'],
                'visa_approved' => ['Visa OK', 'success'], 'enrolled' => ['Enrolled', 'success'],
                'addn_doc' => ['Add Doc', 'dark'], 'deny' => ['Rejected', 'danger'],
                'app_start' => ['App Start', 'secondary']
              ];
              foreach ($flags as $key => [$label, $color]) {
                $status = $s[$key];
                $btnClass = $status ? "btn-$color" : "btn-outline-$color";
                $text = $status ? '✔ ' . $label : $label;
                $disabled = $status ? 'disabled' : '';
                echo "<button class='btn btn-sm $btnClass btn-flag' data-id='{$s['id']}' data-flag='$key' $disabled>$text</button>";
              }
            ?>
          </div>
        </td>
        <td><input type="text" class="form-control form-control-sm live-app-id" data-id="<?= $s['id'] ?>" value="<?= htmlspecialchars($s['application_id'] ?? '') ?>"></td>
        <td><textarea class="form-control form-control-sm live-app-remarks" data-id="<?= $s['id'] ?>"><?= htmlspecialchars($s['application_remarks'] ?? '') ?></textarea></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
$(function() {
  // SEARCH
  $('#searchInput').on('keyup', function(){
    const value = $(this).val().toLowerCase();
    $('#applicantTable tbody tr').filter(function(){
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
  });

  // FLAGS CLICK — this is your original working logic!
  $(document).on('click', '.btn-flag', function(){
    const btn = $(this);
    const id = btn.data('id');
    const flag = btn.data('flag');

    $.post("update-flag.php", { id, flag }, function(resp){
      if(resp === 'ok'){
        // After update ok, re-render full flag-wrapper via Ajax
        $.get('render-flags.php', { id }, function(html){
          $('.flag-wrapper[data-id="' + id + '"]').html(html);
        });
      } else {
        alert('Failed to update flag');
      }
    });
  });

  // Application ID live update
  $('.live-app-id').on('input', function(){
    const id = $(this).data('id');
    const value = $(this).val();
    $.post("update-static.php", { id, application_id: value }, function(resp){
      console.log(resp);
    });
  });

  // Remarks live update
  $('.live-app-remarks').on('input', function(){
    const id = $(this).data('id');
    const value = $(this).val();
    $.post("update-static.php", { id, application_remarks: value }, function(resp){
      console.log(resp);
    });
  });

  // Editable fields
  $(document).on('blur', '.editable-cell', function() {
    const cell = $(this);
    const id = cell.data('id');
    const field = cell.data('field');
    const value = cell.text().trim();

    $.post('update-field.php', { id, field, value }, function(resp) {
      if (resp !== 'ok') {
        alert('Failed to save field');
      }
    });
  });

  // DATE PICKER
  flatpickr(".datepicker", {
    altInput: true,
    altFormat: "F j, Y",
    dateFormat: "Y-m-d",
    maxDate: "today"
  });
});
</script>
</body>
</html>
