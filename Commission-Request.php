<?php
session_start();
// Main database (e.g. student_applications)
require_once 'db.php';

// Secondary database (e.g. applications from Cyprus system)
require_once 'database.php';  // This connects to visaeofi_cyprus

// Get current agent info from session
$agentUsername = $_SESSION['username'] ?? '';
$agentInfo = null;

if ($agentUsername) {
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, phone_number FROM admins WHERE username = ?");
    $stmt->bind_param("s", $agentUsername);
    $stmt->execute();
    $stmt->bind_result($id, $first, $last, $email, $phone);

    if ($stmt->fetch()) {
        $_SESSION['user_id'] = $id;
        $_SESSION['agent_email'] = $email;

        $agentInfo = [
            'first_name' => trim($first),
            'last_name'  => trim($last),
            'email'      => trim($email),
            'phone'      => trim($phone)
        ];
    }

    $stmt->close();
}

$students = [];
$agentEmail = $_SESSION['agent_email'] ?? '';

if ($agentEmail) {
    // From student_applications in the main DB
    $stmt1 = $conn->prepare("SELECT id, first_name, last_name, email FROM student_applications WHERE agent_email = ? ORDER BY created_at DESC");
    $stmt1->bind_param("s", $agentEmail);
    $stmt1->execute();
    $result1 = $stmt1->get_result();

    while ($row = $result1->fetch_assoc()) {
        $students[] = [
            'id'    => 's_' . $row['id'],
            'name'  => trim($row['first_name'] . ' ' . $row['last_name']),
            'email' => $row['email']
        ];
    }

    $stmt1->close();

    // From applications in the Cyprus DB using $conn2
    $stmt2 = $conn2->prepare("SELECT id, name, email FROM applications WHERE agent_email = ? ORDER BY created_at DESC");
    $stmt2->bind_param("s", $agentEmail);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    while ($row = $result2->fetch_assoc()) {
        $students[] = [
            'id'    => 'a_' . $row['id'],
            'name'  => $row['name'],
            'email' => $row['email']
        ];
    }

    $stmt2->close();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Commission Request | Xander Global Scholars</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/x-icon" href="https://xanderglobalscholars.com/favicon.ico">

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5.min.css" rel="stylesheet">

<style>
/* ===== GLOBAL ===== */
body {
  font-family: 'Inter', system-ui, sans-serif;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  color: #1e293b;
  min-height: 100vh;
  padding: 20px;
  margin: 0;
}

/* ===== LOGO & HEADER ===== */
.xander-header {
  text-align: center;
  margin-bottom: 40px;
  padding-top: 20px;
}

.xander-logo {
  font-size: 2.8rem;
  font-weight: 900;
  margin: 0;
  background: linear-gradient(135deg, #1e3a5f 0%, #ff8c42 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -1px;
}

.xander-tagline {
  color: #64748b;
  font-size: 1.1rem;
  margin-top: 8px;
  font-weight: 500;
}

/* ===== FORM CONTAINER ===== */
.form-container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 20px 60px;
}

/* ===== PAGE HEADER ===== */
.page-header {
  text-align: center;
  margin-bottom: 50px;
}

.page-header h1 {
  font-size: 2.2rem;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 12px;
}

.page-header p {
  font-size: 1.1rem;
  color: #64748b;
  max-width: 700px;
  margin: 0 auto 25px;
  line-height: 1.6;
}

/* ===== SECTION ===== */
.form-section {
  position: relative;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  padding: 32px;
  margin-bottom: 32px;
  box-shadow: 0 8px 25px rgba(15, 23, 42, 0.06);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.form-section:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.1);
}

.form-section::before {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 5px;
  background: linear-gradient(180deg, #1e3a5f, #ff8c42);
  border-radius: 20px 0 0 20px;
}

.form-section h3 {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 6px;
  color: #1e293b;
}

.section-help {
  font-size: 0.9rem;
  color: #64748b;
  margin-bottom: 24px;
  line-height: 1.5;
}

/* ===== INPUTS ===== */
.form-control, .form-select {
  height: 48px;
  font-size: 15px;
  border-radius: 12px;
  border: 1px solid #dbe2ea;
  background-color: #fff;
  transition: all 0.3s ease;
  padding: 0 16px;
}

.form-control:focus, .form-select:focus {
  border-color: #1e3a5f;
  box-shadow: 0 0 0 4px rgba(30, 58, 95, 0.15);
  outline: none;
}

.form-control:read-only {
  background-color: #f8fafc;
  cursor: not-allowed;
  color: #64748b;
}

.select2-container--bootstrap-5 .select2-selection {
  min-height: 48px;
  font-size: 15px;
  border-radius: 12px;
  border: 1px solid #dbe2ea;
}

/* ===== RADIO BUTTONS ===== */
.radio-group {
  display: flex;
  gap: 28px;
  margin-top: 15px;
  flex-wrap: wrap;
}

.radio-item {
  display: flex;
  align-items: center;
  background: #f8fafc;
  padding: 12px 20px;
  border-radius: 10px;
  border: 2px solid #e2e8f0;
  transition: all 0.3s ease;
  cursor: pointer;
}

.radio-item:hover {
  border-color: #cbd5e1;
  background: #f1f5f9;
}

.radio-item input[type="radio"] {
  width: 20px;
  height: 20px;
  margin-right: 12px;
  accent-color: #1e3a5f;
  cursor: pointer;
}

.radio-item label {
  margin: 0;
  font-weight: 600;
  color: #334155;
  cursor: pointer;
  font-size: 15px;
}

/* ===== TEXTAREA ===== */
textarea.form-control {
  min-height: 120px;
  padding: 12px 16px;
  line-height: 1.5;
}

/* ===== SUBMIT BUTTON ===== */
.submit-wrap {
  text-align: center;
  margin-top: 50px;
  padding-top: 30px;
  border-top: 1px solid #e2e8f0;
}

.btn-submit {
  background: linear-gradient(135deg, #1e3a5f 0%, #2d4f7c 100%);
  color: #fff;
  border: none;
  padding: 18px 50px;
  font-weight: 700;
  border-radius: 14px;
  font-size: 1.1rem;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  box-shadow: 0 8px 20px rgba(30, 58, 95, 0.2);
}

.btn-submit:hover {
  background: linear-gradient(135deg, #2d4f7c 0%, #3c6499 100%);
  transform: translateY(-3px);
  box-shadow: 0 12px 25px rgba(30, 58, 95, 0.3);
}

.btn-submit:active {
  transform: translateY(-1px);
}

.btn-submit:disabled {
  background: #94a3b8;
  transform: none;
  box-shadow: none;
  cursor: not-allowed;
}

/* ===== UPLOAD OVERLAY ===== */
#uploadOverlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 37, 66, 0.92);
  backdrop-filter: blur(10px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}

.upload-card {
  background: #fff;
  padding: 48px;
  border-radius: 24px;
  text-align: center;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
  max-width: 450px;
  width: 90%;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

.progress-ring {
  transform: rotate(-90deg);
  margin: 0 auto 30px;
}

.progress-ring-bg {
  fill: none;
  stroke: #e5e7eb;
  stroke-width: 10;
}

.progress-ring-circle {
  fill: none;
  stroke: #1e3a5f;
  stroke-width: 10;
  stroke-linecap: round;
  stroke-dasharray: 326;
  stroke-dashoffset: 326;
  transition: stroke-dashoffset 0.3s ease;
}

.progress-text {
  position: absolute;
  margin-top: -100px;
  width: 100%;
  font-size: 1.8rem;
  font-weight: 800;
  color: #1e3a5f;
}

.progress-label {
  margin-top: 20px;
  font-size: 1.05rem;
  color: #64748b;
  font-weight: 500;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
  body {
    padding: 15px;
  }
  
  .form-container {
    padding: 0 15px 40px;
  }
  
  .xander-logo {
    font-size: 2.2rem;
  }
  
  .page-header h1 {
    font-size: 1.8rem;
  }
  
  .form-section {
    padding: 24px;
    border-radius: 16px;
  }
  
  .radio-group {
    gap: 15px;
  }
  
  .radio-item {
    padding: 10px 16px;
    flex: 1;
    min-width: 120px;
  }
  
  .btn-submit {
    padding: 16px 30px;
    width: 100%;
  }
}

/* ===== FOOTER ===== */
.xander-footer {
  text-align: center;
  margin-top: 60px;
  padding-top: 30px;
  border-top: 1px solid #e2e8f0;
  color: #94a3b8;
  font-size: 0.9rem;
}

.xander-footer p {
  margin: 5px 0;
}
</style>
</head>

<body>

<div class="xander-header">
  <h1 class="xander-logo">Xander Global Scholars</h1>
  <p class="xander-tagline">Empowering Global Education Opportunities</p>
</div>

<div class="form-container">
  <div class="page-header">
    <h1>Commission Request Form</h1>
    <p>Submit your commission requests for recruited students through Xander Global Scholars. Please ensure all information is accurate and complete before submission.</p>
  </div>

  <form id="commissionForm" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <!-- AGENT INFORMATION -->
    <section class="form-section">
      <h3>Agent Information</h3>
      <p class="section-help">Your details are pre-filled from your profile and cannot be modified.</p>
      <div class="row g-4">
        <div class="col-md-6">
          <label class="form-label fw-semibold">First Name</label>
          <input class="form-control" name="first_name" value="<?= htmlspecialchars($agentInfo['first_name'] ?? '') ?>" readonly placeholder="First Name" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Last Name</label>
          <input class="form-control" name="last_name" value="<?= htmlspecialchars($agentInfo['last_name'] ?? '') ?>" readonly placeholder="Last Name" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Email Address</label>
          <input class="form-control" type="email" name="email" value="<?= htmlspecialchars($agentInfo['email'] ?? '') ?>" readonly placeholder="Email" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Phone Number</label>
          <input class="form-control" name="phone" value="<?= htmlspecialchars($agentInfo['phone'] ?? '') ?>" readonly placeholder="Phone Number" required>
        </div>
      </div>
    </section>

    <!-- STUDENT SELECTION -->
    <section class="form-section">
      <h3>Student Selection</h3>
      <p class="section-help">Select the student for whom you're requesting commission from the list below.</p>
      <div class="row g-4">
        <div class="col-12">
          <label class="form-label fw-semibold">Select Student *</label>
          <select id="studentSelect" class="form-select" name="recruited_student_id" required>
            <option value="">-- Select Student --</option>
            <?php foreach ($students as $s): ?>
              <option value="<?= htmlspecialchars($s['id']) ?>">
                <?= htmlspecialchars($s['name']) ?> - <?= htmlspecialchars($s['email']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </section>

    <!-- PAYMENT ADDRESS -->
    <section class="form-section">
      <h3>Payment Address</h3>
      <p class="section-help">Address where commission payment should be sent (optional but recommended for faster processing).</p>
      <div class="row g-4">
        <div class="col-12">
          <label class="form-label fw-semibold">Street Address</label>
          <input class="form-control" name="street_address" placeholder="Street Address">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Address Line 2</label>
          <input class="form-control" name="address_line_2" placeholder="Apartment, Suite, Unit, etc. (Optional)">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">City</label>
          <input class="form-control" name="city" placeholder="City">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">State / Province</label>
          <input class="form-control" name="state" placeholder="State / Province">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Postal Code</label>
          <input class="form-control" name="postal_code" placeholder="Postal Code">
        </div>
      </div>
    </section>

    <!-- APPLICATION DETAILS -->
    <section class="form-section">
      <h3>Application Details</h3>
      <p class="section-help">Provide details about the student's application.</p>
      <div class="row g-4">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Country Applied For</label>
          <input class="form-control" name="country_applied" placeholder="e.g., Canada, Australia, UK">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Submission Date *</label>
          <input type="date" class="form-control" name="date" required>
        </div>
      </div>
    </section>

    <!-- STATUS INFORMATION -->
    <section class="form-section">
      <h3>Status Information</h3>
      <p class="section-help">Please provide the current status of the student's application.</p>

      <div class="row g-4">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Loan Status *</label>
          <div class="radio-group">
            <div class="radio-item">
              <input type="radio" id="loan_approved" name="loan_status" value="APPROVED" required>
              <label for="loan_approved">APPROVED</label>
            </div>
            <div class="radio-item">
              <input type="radio" id="loan_denied" name="loan_status" value="DENIED">
              <label for="loan_denied">DENIED</label>
            </div>
            <div class="radio-item">
              <input type="radio" id="loan_na" name="loan_status" value="NOT APPLICABLE">
              <label for="loan_na">NOT APPLICABLE</label>
            </div>
          </div>
        </div>
        
        <div class="col-md-6">
          <label class="form-label fw-semibold">Visa Status *</label>
          <div class="radio-group">
            <div class="radio-item">
              <input type="radio" id="visa_approved" name="visa_status" value="APPROVED" required>
              <label for="visa_approved">APPROVED</label>
            </div>
            <div class="radio-item">
              <input type="radio" id="visa_denied" name="visa_status" value="DENIED">
              <label for="visa_denied">DENIED</label>
            </div>
            <div class="radio-item">
              <input type="radio" id="visa_na" name="visa_status" value="NOT APPLICABLE">
              <label for="visa_na">NOT APPLICABLE</label>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-5">
        <label class="form-label fw-semibold">Contract Status *</label>
        <p class="section-help">Have you signed the recruitment contract with Xander Global Scholars?</p>
        <div class="radio-group">
          <div class="radio-item">
            <input type="radio" id="contract_yes" name="contract_signed" value="YES" required>
            <label for="contract_yes">YES</label>
          </div>
          <div class="radio-item">
            <input type="radio" id="contract_no" name="contract_signed" value="NO">
            <label for="contract_no">NO</label>
          </div>
        </div>
      </div>
    </section>

    <!-- COMMENTS & SIGNATURE -->
    <section class="form-section">
      <h3>Additional Information</h3>
      <p class="section-help">Add any comments and provide your electronic signature.</p>
      <div class="row g-4">
        <div class="col-12">
          <label class="form-label fw-semibold">Comments</label>
          <textarea class="form-control" name="comments" rows="4" placeholder="Any additional notes, special instructions, or comments regarding this commission request..."></textarea>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Electronic Signature *</label>
          <input class="form-control" name="signature" placeholder="Type your full name as electronic signature" required>
          <div class="form-text mt-2">
            By typing your name, you agree to the terms and confirm the accuracy of this request.
          </div>
        </div>
      </div>
    </section>

    <div class="submit-wrap">
      <button type="submit" class="btn-submit">Submit Commission Request</button>
    </div>
  </form>
</div>

<div class="xander-footer">
  <p>© <?= date('Y') ?> Xander Global Scholars. All rights reserved.</p>
  <p>For support, contact: support@xanderglobalscholars.com</p>
</div>

<!-- Progress Overlay -->
<div id="uploadOverlay" style="display:none;">
  <div class="upload-card">
    <svg class="progress-ring" width="140" height="140">
      <circle class="progress-ring-bg" cx="70" cy="70" r="60"></circle>
      <circle class="progress-ring-circle" cx="70" cy="70" r="60"></circle>
    </svg>
    <div class="progress-text" id="progressText">0%</div>
    <p class="progress-label">Submitting commission request…</p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("commissionForm");

  // Initialize Select2
  $("#studentSelect").select2({
    theme: "bootstrap-5",
    placeholder: "Search for a student...",
    width: "100%",
    allowClear: true
  });

  // Set today's date as default
  const today = new Date().toISOString().split('T')[0];
  document.querySelector('input[name="date"]').value = today;
  document.querySelector('input[name="date"]').max = today; // Cannot select future dates

  /* ===============================
     FORM SUBMISSION WITH PROGRESS
  =============================== */
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    // Basic validation
    const studentSelect = document.getElementById('studentSelect');
    if (!studentSelect.value) {
      alert("Please select a student.");
      $("#studentSelect").select2('open');
      return;
    }

    // Validate required radio buttons
    const loanStatus = form.querySelector('input[name="loan_status"]:checked');
    const visaStatus = form.querySelector('input[name="visa_status"]:checked');
    const contractStatus = form.querySelector('input[name="contract_signed"]:checked');
    
    const errorMessages = [];
    if (!loanStatus) errorMessages.push("Loan Status");
    if (!visaStatus) errorMessages.push("Visa Status");
    if (!contractStatus) errorMessages.push("Contract Status");
    
    if (errorMessages.length > 0) {
      alert("Please fill in all required fields:\n" + errorMessages.join("\n"));
      return;
    }

    // Validate signature
    const signatureInput = document.querySelector('input[name="signature"]');
    const signature = signatureInput.value.trim();
    if (!signature || signature.length < 2) {
      alert("Please provide a valid electronic signature (minimum 2 characters).");
      signatureInput.focus();
      return;
    }

    const formData = new FormData(form);

    // UI LOCK
    const submitBtn = form.querySelector("button[type='submit']");
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
      <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
      Processing...
    `;

    // SHOW OVERLAY
    const overlay = document.getElementById("uploadOverlay");
    const circle = document.querySelector(".progress-ring-circle");
    const text = document.getElementById("progressText");

    const radius = 60;
    const circumference = 2 * Math.PI * radius;

    circle.style.strokeDasharray = circumference;
    circle.style.strokeDashoffset = circumference;

    function setProgress(percent) {
      const offset = circumference - (percent / 100) * circumference;
      circle.style.strokeDashoffset = offset;
      text.textContent = percent + "%";
    }

    overlay.style.display = "flex";
    setProgress(0);

    // USE XHR for upload progress tracking
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "save_commission.php", true);

    // UPLOAD PROGRESS
    xhr.upload.onprogress = (e) => {
      if (e.lengthComputable) {
        const percent = Math.round((e.loaded / e.total) * 100);
        setProgress(percent);
      }
    };

    // RESPONSE HANDLING
    xhr.onload = () => {
      submitBtn.disabled = false;
      submitBtn.innerHTML = 'Submit Commission Request';

      if (xhr.status !== 200) {
        overlay.style.display = "none";
        alert("Server error. Please try again.\nError: " + xhr.statusText);
        console.error(xhr.responseText);
        return;
      }

      let data;
      try {
        data = JSON.parse(xhr.responseText);
      } catch (err) {
        overlay.style.display = "none";
        alert("Invalid server response. Please contact support.");
        console.error(xhr.responseText);
        return;
      }

      if (data.status === "success") {
        setProgress(100);
        setTimeout(() => {
          overlay.style.display = "none";
          const successMsg = "✅ Commission request submitted successfully!\n\n" +
                           "Request ID: " + (data.request_id || 'N/A') + "\n" +
                           "Submitted on: " + new Date().toLocaleDateString() + "\n\n" +
                           "You will be redirected to your dashboard.";
          alert(successMsg);
          window.location.href = "agent-dashboard.php";
        }, 800);
      } else {
        overlay.style.display = "none";
        alert("❌ " + (data.message || "Submission failed. Please try again."));
      }
    };

    // NETWORK ERROR
    xhr.onerror = () => {
      submitBtn.disabled = false;
      submitBtn.innerHTML = 'Submit Commission Request';
      overlay.style.display = "none";
      alert("Network error. Please check your internet connection and try again.");
    };

    // SEND REQUEST
    xhr.send(formData);
  });

  // Add input validation for signature
  const signatureInput = document.querySelector('input[name="signature"]');
  signatureInput.addEventListener('blur', () => {
    const value = signatureInput.value.trim();
    if (value && !/^[A-Za-z\s.,'-]{2,}$/.test(value)) {
      alert("Please enter a valid name (letters, spaces, and common punctuation only, minimum 2 characters).");
      signatureInput.focus();
    }
  });

  // Add visual feedback for radio buttons
  document.querySelectorAll('.radio-item').forEach(item => {
    item.addEventListener('click', function() {
      const radio = this.querySelector('input[type="radio"]');
      radio.checked = true;
      
      // Remove active class from all items in the same group
      const groupName = radio.name;
      document.querySelectorAll(`.radio-item input[name="${groupName}"]`).forEach(r => {
        r.closest('.radio-item').style.borderColor = '#e2e8f0';
        r.closest('.radio-item').style.background = '#f8fafc';
      });
      
      // Add active class to selected item
      this.style.borderColor = '#1e3a5f';
      this.style.background = '#f0f4ff';
    });
  });
});
</script>

</body>
</html>