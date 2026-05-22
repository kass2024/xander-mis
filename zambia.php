
<style>
/* ===== MODERN FLOATING WHATSAPP BUTTON ===== */
.xander-whatsapp-float {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 
        0 8px 32px rgba(37, 211, 102, 0.3),
        0 4px 16px rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
    cursor: pointer;
    text-decoration: none;
    z-index: 9999;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    animation: xanderFloat 3s ease-in-out infinite, xanderPulse 2s ease-in-out infinite;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

@keyframes xanderFloat {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-8px); }
}

@keyframes xanderPulse {
    0%, 100% { 
        box-shadow: 
            0 8px 32px rgba(37, 211, 102, 0.3),
            0 4px 16px rgba(0, 0, 0, 0.1),
            inset 0 1px 0 rgba(255, 255, 255, 0.2),
            0 0 0 0 rgba(37, 211, 102, 0.4);
    }
    50% { 
        box-shadow: 
            0 12px 40px rgba(37, 211, 102, 0.4),
            0 6px 20px rgba(0, 0, 0, 0.15),
            inset 0 1px 0 rgba(255, 255, 255, 0.3),
            0 0 0 8px rgba(37, 211, 102, 0);
    }
}

.xander-whatsapp-float:hover {
    transform: scale(1.1) translateY(-4px);
    box-shadow: 
        0 12px 40px rgba(37, 211, 102, 0.4),
        0 6px 20px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    background: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
    animation: none;
}

.xander-whatsapp-float svg {
    width: 32px;
    height: 32px;
    color: white;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
    transition: transform 0.3s ease;
}

.xander-whatsapp-float:hover svg {
    transform: scale(1.1);
}


/* ===== ENHANCED WHATSAPP TOOLTIP - ALWAYS VISIBLE ===== */
.xander-whatsapp-tooltip {
    position: absolute;
    bottom: 80px;
    right: 0;
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: white;
    padding: 16px 24px;
    border-radius: 20px;
    font-size: 15px;
    font-weight: 600;
    white-space: nowrap;
    box-shadow: 
        0 12px 40px rgba(37, 211, 102, 0.4),
        0 6px 20px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.2);
    pointer-events: none;
    animation: xanderTooltipFloat 3s ease-in-out infinite;
    z-index: 10000;
}

.xander-whatsapp-tooltip::before {
    content: "👉";
    margin-right: 8px;
    font-size: 18px;
    animation: xanderPointingFinger 1.5s ease-in-out infinite;
}

@keyframes xanderTooltipFloat {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-5px); }
}

@keyframes xanderPointingFinger {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

.xander-whatsapp-tooltip::after {
    content: "";
    position: absolute;
    top: 100%;
    right: 24px;
    border: 10px solid transparent;
    border-top-color: #128C7E;
    transform: translateX(50%);
}

/* Enhanced hover effect */
.xander-whatsapp-float:hover .xander-whatsapp-tooltip {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 
        0 16px 50px rgba(37, 211, 102, 0.5),
        0 8px 25px rgba(0, 0, 0, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.4);
    background: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .xander-whatsapp-tooltip {
        bottom: 70px;
        right: -80px;
        font-size: 14px;
        padding: 12px 18px;
        max-width: 200px;
        white-space: normal;
        text-align: center;
        line-height: 1.4;
    }
    
    .xander-whatsapp-tooltip::after {
        right: 90px;
    }
}

@media (max-width: 480px) {
    .xander-whatsapp-tooltip {
        bottom: 65px;
        right: -70px;
        font-size: 13px;
        padding: 10px 16px;
        max-width: 180px;
    }
    
    .xander-whatsapp-tooltip::after {
        right: 80px;
    }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .xander-whatsapp-float {
        width: 56px;
        height: 56px;
        bottom: 20px;
        right: 20px;
    }
    
    .xander-whatsapp-float svg {
        width: 28px;
        height: 28px;
    }
    
    .xander-whatsapp-tooltip {
        bottom: 70px;
        right: -60px;
        font-size: 13px;
        padding: 10px 16px;
    }
    
    .xander-whatsapp-tooltip::after {
        right: 70px;
    }
}

@media (max-width: 480px) {
    .xander-whatsapp-float {
        width: 52px;
        height: 52px;
        bottom: 16px;
        right: 16px;
    }
    
    .xander-whatsapp-float svg {
        width: 26px;
        height: 26px;
    }
    
    .xander-whatsapp-tooltip {
        display: none;
    }
}

/* Entrance Animation */
@keyframes xanderEntrance {
    0% {
        opacity: 0;
        transform: scale(0) translateY(100px);
    }
    50% {
        opacity: 0;
        transform: scale(0.5) translateY(50px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.xander-whatsapp-float {
    animation: xanderEntrance 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards, 
               xanderFloat 3s ease-in-out 2s infinite, 
               xanderPulse 2s ease-in-out 2s infinite;
}
</style><?php
session_start();
require_once 'db.php';

// Handle user_id from URL or session (MUST be first!)
if (isset($_GET['id']) && strpos($_GET['id'], 'user-') === 0) {
    $_SESSION['user_id'] = $_GET['id'];

    // Clean URL
    $cleanUrl = strtok($_SERVER["REQUEST_URI"], '?');
    $query = $_GET;
    unset($query['id']);
    if (!empty($query)) {
        $cleanUrl .= '?' . http_build_query($query);
    }
    header("Location: $cleanUrl");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'user-' . time() . '-' . rand(1000, 9999);
}

$userId = $_SESSION['user_id'];

// Define accepted agent IDs
$agent_ids = [1, 2, 10,12,13,14,15,17,18,27,28,34,35,54,55,56,57,58,62,63,64,65,66];

// Query agents from admins table
$agent_query = "SELECT id, first_name, last_name, email FROM admins WHERE id IN (" . implode(',', $agent_ids) . ")";
$agent_result = mysqli_query($conn, $agent_query);

$agents = [];
while ($row = mysqli_fetch_assoc($agent_result)) {
    $agents[] = $row;
}

// Defaults
$universityName = 'Unknown University';
$regionName = 'Unknown Region';
$universityId = null;
$regionId = null;

// Capture university_id from URL
if (isset($_GET['university_id']) && is_numeric($_GET['university_id'])) {
    $universityId = (int) $_GET['university_id'];

    $stmt = $conn->prepare("
        SELECT u.name AS university_name, r.name AS region_name, r.id AS region_id
        FROM universities u
        JOIN regions r ON u.region_id = r.id
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $universityId);
    $stmt->execute();
    $stmt->bind_result($universityName, $regionName, $regionId);
    $stmt->fetch();
    $stmt->close();

    // Store in session
    $_SESSION['university_id'] = $universityId;
    $_SESSION['region_id'] = $regionId;
}

// Retrieve saved data SAFELY
$stmt = $conn->prepare("SELECT * FROM student_applications WHERE user_id = ?");
$stmt->bind_param("s", $userId);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $studentData = $result->fetch_assoc() ?? [];
    $stmt->close();
} else {
    // Log error if needed
    error_log("Failed to load student_applications for user_id=$userId: " . $stmt->error);
    $studentData = []; // Fallback to empty array
}

// ✅ FIXED: If reloading saved application, but no university_id in URL — use saved values
if (!isset($_GET['university_id']) && isset($studentData['university_id']) && $studentData['university_id'] > 0) {
    $universityId = (int)$studentData['university_id'];
    $regionId = (int)$studentData['region_id'];

    $stmt = $conn->prepare("
        SELECT u.name AS university_name, r.name AS region_name, r.id AS region_id
        FROM universities u
        JOIN regions r ON u.region_id = r.id
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $universityId);
    $stmt->execute();
    $stmt->bind_result($universityName, $regionName, $regionId);
    $stmt->fetch();
    $stmt->close();
}

// Helper functions
function checked($field, $value) {
    global $studentData;
    return (isset($studentData[$field]) && $studentData[$field] == $value) ? 'checked' : '';
}

function selected($field, $value) {
    global $studentData;
    return (isset($studentData[$field]) && $studentData[$field] == $value) ? 'selected' : '';
}

function isChecked($field, $value) {
    global $studentData;
    return (isset($studentData[$field]) && strpos($studentData[$field], $value) !== false) ? 'checked' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Application  - University of Zambia </title>

  <!-- Flatpickr and Select2 Styles -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@17.0.19/build/css/intlTelInput.css" />

  <style>
  body {
    font-family: 'Segoe UI', sans-serif;
    background-color: #f5f7fb;
    padding: 30px 15px;
    color: #333;
  }

  .form-container {
    background-color: #fff;
    max-width: 950px;
    width: 100%;
    margin: auto;
    padding: 30px 40px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
  }

  h2, h3 {
    text-align: center;
    color: #0c3c78;
    margin-bottom: 20px;
    font-size: 1.5rem;
  }

  label {
    font-weight: 500;
    margin-top: 20px;
    display: block;
  }

  input[type="text"],
  input[type="email"],
  input[type="date"],
  select,
  textarea {
    width: 100%;
    padding: 10px 15px;
    margin-top: 5px;
    border-radius: 5px;
    border: 1px solid #ccc;
    font-size: 1rem;
    box-sizing: border-box;
  }

  input[type="file"] {
    display: block;
    margin-top: 5px;
    padding: 10px;
    border-radius: 8px;
    border: 2px dashed #bbb;
    background: #fafafa;
    color: #555;
    width: 100%;
    font-size: 0.95rem;
    box-sizing: border-box;
    overflow-wrap: break-word;
    word-break: break-word;
  }

  textarea {
    resize: vertical;
  }

  .form-step {
    display: none;
  }

  .form-step.active {
    display: block;
  }

  .form-buttons {
    margin-top: 30px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: space-between;
  }

  .form-buttons button {
    background-color: #0c3c78;
    color: #fff;
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    cursor: pointer;
    flex: 1 1 48%;
  }

  .form-buttons button:hover {
    background-color: #092a5c;
  }

  .radio-group,
  .checkbox-group {
    margin-top: 10px;
  }

  .radio-group label,
  .checkbox-group label {
    display: inline-block;
    margin-right: 20px;
  }

  /* Program selection horizontal layout (default) */
  .inline-inputs {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
  }

  .inline-inputs > div {
    flex: 1 1 30%;
    min-width: 200px;
  }

  .inline-inputs select {
    width: 100%;
  }

  /* Mobile Optimization */
  @media (max-width: 600px) {
    body {
      padding: 15px;
    }

    .form-container {
      padding: 20px;
    }

    h2, h3 {
      font-size: 1.3rem;
    }

    .inline-inputs {
      flex-direction: column;
    }

    .inline-inputs > div {
      flex: 1 1 100%;
      min-width: 100%;
    }

    .form-buttons {
      flex-direction: column;
      gap: 10px;
    }

    .form-buttons button {
      width: 100%;
      flex: 1 1 100%;
    }
  }
.intl-tel-input {
  width: 100%;
  font-family: 'Segoe UI', sans-serif;
}

.intl-tel-input .iti__flag-container {
  height: 100%;
}

.intl-tel-input .iti__selected-flag {
  background-color: #fff;
  border: 1px solid #ccc;
  border-right: none;
  height: 45px;
  border-radius: 5px 0 0 5px;
  padding: 0 12px;
  display: flex;
  align-items: center;
}

.intl-tel-input input[type="tel"] {
  height: 45px;
  border: 1px solid #ccc;
  border-left: none;
  font-size: 1rem;
  border-radius: 0 5px 5px 0;
  padding: 10px 15px;
  width: 100%;
  box-sizing: border-box;
}

.intl-tel-input input[type="tel"]:focus {
  outline: none;
  border-color: #0c3c78;
  box-shadow: 0 0 4px rgba(12, 60, 120, 0.2);
}

.iti--separate-dial-code .iti__selected-dial-code {
  margin-left: 8px;
  font-weight: 500;
  color: #333;
}

  phoneInput.addEventListener("blur", updatePhoneFields);
  phoneInput.addEventListener("change", updatePhoneFields);
  phoneInput.addEventListener("keyup", updatePhoneFields);

  const form = phoneInput.closest("form");
  if (form) {
    form.addEventListener("submit", function () {
      updatePhoneFields();
    });
  }

  setTimeout(updatePhoneFields, 500);
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const emergencyPhoneInput = document.querySelector("#emergency_phone_number_input");
  const emergencyAreaCode = document.querySelector("#emergency_area_code");
  const emergencyPhoneClean = document.querySelector("#emergency_phone_number");

  if (!emergencyPhoneInput) return;

  const itiEmergency = window.intlTelInput(emergencyPhoneInput, {
    initialCountry: "auto",
    separateDialCode: true,
    preferredCountries: ["rw", "ke", "ug", "us"],
    geoIpLookup: function (callback) {
      fetch("https://ipapi.co/json")
        .then((res) => res.json())
        .then((data) => callback(data.country_code || "rw"))
        .catch(() => callback("rw"));
    },
    utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@17.0.19/build/js/utils.js"
  });

  function updateEmergencyPhoneFields() {
    if (itiEmergency && typeof intlTelInputUtils !== "undefined" && itiEmergency.isValidNumber()) {
      const fullNumber = itiEmergency.getNumber();
      const countryData = itiEmergency.getSelectedCountryData();
      emergencyAreaCode.value = `+${countryData.dialCode}`;
      emergencyPhoneClean.value = fullNumber.replace(`+${countryData.dialCode}`, '').trim();
    }
  }

  emergencyPhoneInput.addEventListener("blur", updateEmergencyPhoneFields);
  emergencyPhoneInput.addEventListener("change", updateEmergencyPhoneFields);
  emergencyPhoneInput.addEventListener("keyup", updateEmergencyPhoneFields);

  const form = emergencyPhoneInput.closest("form");
  if (form) {
    form.addEventListener("submit", function () {
      updateEmergencyPhoneFields();
    });
  }

  setTimeout(updateEmergencyPhoneFields, 500);
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const agentSelect = document.getElementById('agent_select');
  const agentFirstName = document.getElementById('agent_first_name');
  const agentLastName = document.getElementById('agent_last_name');
  const agentEmail = document.getElementById('agent_email');

  if (agentSelect) {
    agentSelect.addEventListener('change', function() {
      const parts = this.value.split('|');
      agentFirstName.value = parts[0] || '';
      agentLastName.value = parts[1] || '';
      agentEmail.value = parts[2] || '';
    });
  }
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {

  function setupLiveUpload(fieldName) {
    const fileInput = document.querySelector(`#${fieldName}_file`);
    const hiddenInput = document.querySelector(`input[name="${fieldName}"]`);
    const viewLink = document.getElementById(`${fieldName}_view`);

    if (!fileInput || !hiddenInput) return;

    fileInput.addEventListener("change", function () {
      const file = fileInput.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append("file", file);
      formData.append("field", fieldName);

      fileInput.disabled = true;
      fileInput.style.opacity = "0.6";

      fetch("upload_file.php", {
        method: "POST",
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        fileInput.disabled = false;
        fileInput.style.opacity = "1";

        if (data.status === "success") {
          hiddenInput.value = data.file_path;

          if (viewLink) {
            viewLink.innerHTML = `<a href="${data.file_path}" target="_blank">View File</a>`;
          }

        } else {
          alert("Upload failed: " + (data.message || "Unknown error"));
        }
      })
      .catch(error => {
        fileInput.disabled = false;
        fileInput.style.opacity = "1";
        alert("Upload error: " + error);
      });
    });
  }

  // STEP 5 uploads
  setupLiveUpload('degree_transcripts');
  setupLiveUpload('high_school_degree');
  setupLiveUpload('valid_passport');

  // STEP 6 uploads
  setupLiveUpload('recommendation_letters');
  setupLiveUpload('personal_statement');
  setupLiveUpload('cv_resume');
  setupLiveUpload('english_certificate');
  setupLiveUpload('birth_certificate');
  setupLiveUpload('payment_proof');

});
</script>
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('select[name="bachelor_program"], select[name="masters_program"], select[name="phd_program"]').select2({
    placeholder: "Please Select Program",
    width: '100%'
});

});
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(');

, function(data) {
    let oldContent = 
    
    if (
  });
}

, function(response) {
    
  });
}

// Auto-refresh every 5 seconds
setInterval(function() {
}, 5000);
</script>

<!-- Modern Floating WhatsApp Button -->
<div class="xander-whatsapp-container">
    <a href="https://wa.me/14389009784" 
       target="_blank" 
       rel="noopener noreferrer"
       class="xander-whatsapp-float"
       aria-label="👉 Chat with us on WhatsApp!"
       title="👉 Chat with us on WhatsApp!">
        
        <!-- WhatsApp SVG Icon -->
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.149-.67.149-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414-.074-.123-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
        </svg>
        
        <!-- Tooltip -->
        <div class="xander-whatsapp-tooltip">
            👉 Chat with us on WhatsApp!
        </div>
    </a>
</div></body>
</html>
