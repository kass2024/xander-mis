<?php
session_start();
require_once 'db.php';

/* ===========================================================
   AUTH CHECK
=========================================================== */
if (!isset($_SESSION['id'])) {
    http_response_code(403);
    exit("Access denied. Please log in.");
}

$staff_id = (int) $_SESSION['id'];

/* ===========================================================
   GET STAFF NAME
=========================================================== */
$stmt = $conn->prepare("SELECT full_name FROM admins WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$stmt->bind_result($staff_name);
$stmt->fetch();
$stmt->close();

if (!$staff_name) {
    exit("Invalid session.");
}

/* ===========================================================
   CSRF TOKEN
=========================================================== */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Salary Request</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #f4f7fb;
}
.card-box {
    max-width: 720px;
    margin: 60px auto;
    background: #fff;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 12px 30px rgba(0,0,0,.08);
}
.btn-back {
    background: #6c63ff;
    color: #fff;
    font-weight: 600;
    border-radius: 10px;
}
.btn-back:hover {
    background: #5148ff;
}
.salary-box {
    background: #eaffea;
    border-left: 6px solid #28a745;
    padding: 15px;
    border-radius: 8px;
    display: none;
    font-size: 18px;
}
</style>
</head>

<body>

<div class="card-box">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0">💰 Salary Request</h4>
        <a href="admin-dashboard.php" class="btn btn-back">⬅ Back</a>
    </div>

    <!-- FLASH MESSAGES -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <p class="mb-3"><strong>Staff:</strong> <?= htmlspecialchars($staff_name); ?></p>

    <!-- MONTH PICKER -->
    <label class="form-label fw-bold">Select Month</label>
    <input type="month" id="monthPicker" class="form-control" value="<?= date('Y-m'); ?>">

    <!-- SALARY DISPLAY -->
    <div id="salaryBox" class="salary-box mt-4">
        Salary for <strong id="salaryMonth"></strong> :
        <span class="fw-bold text-success" id="salaryAmount"></span>
    </div>

    <hr id="divider" class="mt-4" style="display:none;">

    <!-- REQUEST FORM -->
    <form method="POST" action="submit-salary-request.php" id="salaryForm" style="display:none;">

        <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
        <input type="hidden" name="month" id="reqMonth">
        <input type="hidden" name="salary_rwf" id="reqSalary">

        <!-- PAYMENT METHOD -->
        <label class="form-label fw-bold">Payment Method</label>
        <select class="form-select" name="payment_method" id="payment_method" required>
            <option value="">Select option</option>
            <option value="bank">Bank Transfer</option>
            <option value="momo">Mobile Money</option>
        </select>

        <!-- BANK -->
        <div id="bank_fields" class="mt-3" style="display:none;">
            <label class="form-label">Bank Name</label>
            <input type="text" class="form-control" name="bank_name">

            <label class="form-label mt-2">Account Number</label>
            <input type="text" class="form-control" name="bank_account">

            <label class="form-label mt-2">Registered Names</label>
            <input type="text" class="form-control" name="bank_registered_names">
        </div>

        <!-- MOMO -->
        <div id="momo_fields" class="mt-3" style="display:none;">
            <label class="form-label">MoMo Number</label>
            <input type="text" class="form-control" name="momo_number">

            <label class="form-label mt-2">Registered Names</label>
            <input type="text" class="form-control" name="momo_registered_names">
        </div>

        <button class="btn btn-success w-100 mt-4 py-2 fw-bold">
            Submit Salary Request
        </button>

    </form>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
/* ===========================================================
   LOAD SALARY ON MONTH CHANGE
=========================================================== */
function loadSalary(month) {
    $.ajax({
        url: "calculate-salary.php",
        method: "POST",
        data: { month: month },
        dataType: "json",
        success: function(res) {

            if (res.error) {
                alert(res.error);
                return;
            }

            $("#salaryMonth").text(month);
            $("#salaryAmount").text(res.salary + " RWF");

            $("#reqMonth").val(month);
            $("#reqSalary").val(res.raw_salary);

            $("#salaryBox, #divider, #salaryForm").fadeIn();
        },
        error: function() {
            alert("Failed to load salary.");
        }
    });
}

$("#monthPicker").on("change", function () {
    let month = $(this).val();
    if (month) loadSalary(month);
});

// Auto-load current month on page load
$(document).ready(function () {
    loadSalary($("#monthPicker").val());
});

/* ===========================================================
   PAYMENT METHOD TOGGLE
=========================================================== */
$("#payment_method").on("change", function () {

    $("#bank_fields, #momo_fields").hide();
    $("#bank_fields input, #momo_fields input").prop("required", false);

    if (this.value === "bank") {
        $("#bank_fields").show();
        $("#bank_fields input").prop("required", true);
    }

    if (this.value === "momo") {
        $("#momo_fields").show();
        $("#momo_fields input").prop("required", true);
    }
});
</script>

</body>
</html>
