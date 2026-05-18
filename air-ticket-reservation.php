<?php
session_start();

if (!isset($_GET['id']) || trim((string) $_GET['id']) === '') {
    $newId = 'ticket-' . time() . '-' . random_int(1000, 9999);
    header('Location: air-ticket-reservation.php?id=' . rawurlencode($newId));
    exit;
}

$user_id = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $_GET['id']);
if ($user_id === '') {
    $user_id = 'ticket-' . time() . '-' . random_int(1000, 9999);
    header('Location: air-ticket-reservation.php?id=' . rawurlencode($user_id));
    exit;
}

$_SESSION['user_id'] = $user_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Air Ticket Reservation | Xander Global Scholars</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<!-- CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css" rel="stylesheet">

<style>
body{font-family:Inter;background:#f8fafc;color:#1e293b}
.form-wrap{max-width:1150px;margin:auto;padding:50px 18px 90px}
.form-section{
  background:#fff;border:1px solid #e2e8f0;border-radius:16px;
  padding:28px;margin-bottom:28px;
  box-shadow:0 6px 18px rgba(15,23,42,.05);
  position:relative
}
.form-section::before{
  content:"";position:absolute;left:0;top:0;bottom:0;width:4px;
  background:linear-gradient(180deg,#1e3a5f,#ff8c42);
  border-radius:16px 0 0 16px
}
.form-section h3{font-size:1.15rem;font-weight:800}
.section-help{font-size:.85rem;color:#64748b;margin-bottom:18px}
.form-control,.form-select{height:44px;border-radius:10px;font-size:14.5px}
.btn-submit{
  background:linear-gradient(135deg,#ff8c42,#e6732f);
  color:#fff;border:none;padding:14px 48px;
  font-weight:800;border-radius:16px
}
.payment-box{
  display:none;background:#f8fafc;border:1px dashed #cbd5e1;
  padding:14px;border-radius:12px;font-size:.9rem
}

/* Select2 – KEEP WORKING */
.select2-container{width:100%!important;font-size:14.5px}
.select2-selection--single,
.select2-selection--multiple{
  min-height:44px!important;border-radius:10px!important;
  border:1px solid #ced4da!important;
  display:flex!important;align-items:center!important
}
.select2-selection__rendered{
  padding-left:12px!important;
  padding-right:12px!important;
}

/* Overlay */
#uploadOverlay{
  position:fixed;inset:0;background:rgba(15,37,66,.88);
  display:none;align-items:center;justify-content:center;z-index:9999
}
.upload-card{
  background:#fff;padding:36px 44px;border-radius:22px;text-align:center
}
</style>
</head>

<body>
<?php include 'header.php'; ?>

<div class="form-wrap">
<h1 class="text-center fw-bold mb-2">Air Ticket Reservation</h1>
<p class="text-center text-muted mb-4">
Search by <strong>city name</strong>, select airports, and submit your request.
</p>

<form id="airForm" novalidate>

<!-- REQUIRED BACKEND FIELDS -->
<input type="hidden" name="user_id" value="<?= htmlspecialchars($_SESSION['user_id']) ?>">
<input type="hidden" name="departure_city" id="departure_city">
<input type="hidden" name="destination_city" id="destination_city">
<input type="hidden" name="departure_city_text" id="departure_city_text">
<input type="hidden" name="destination_city_text" id="destination_city_text">
<input type="hidden" name="airline_id" id="airline_id">
<input type="hidden" name="phone_area_code" id="phone_area_code">
<input type="hidden" name="phone_number" id="phone_number">

<!-- PASSENGER -->
<section class="form-section">
<h3>Passenger Information</h3>
<p class="section-help">Details must match passport exactly</p>

<div class="row g-3">
  <div class="col-md-6">
    <input class="form-control" name="full_name" placeholder="Full name (as on passport)" required>
  </div>

  <div class="col-md-3">
    <select class="form-select" name="gender" required>
      <option value="">Gender</option>
      <option value="Male">Male</option>
      <option value="Female">Female</option>
    </select>
  </div>

  <div class="col-md-3">
    <input type="date" class="form-control" name="date_of_birth" required>
  </div>

  <div class="col-md-4">
    <input class="form-control" name="nationality" placeholder="Nationality" required>
  </div>

  <div class="col-md-4">
    <input class="form-control" name="passport_number" placeholder="Passport number" required>
  </div>

  <div class="col-md-4">
    <input type="date" class="form-control" name="passport_expiry" required>
  </div>

  <div class="col-md-6">
    <input type="email" class="form-control" name="email" placeholder="Email address" required>
  </div>

  <div class="col-md-6">
    <input type="tel" id="phone" class="form-control" placeholder="Phone number" required>
  </div>
</div>
</section>

<!-- ROUTE -->
<section class="form-section">
<h3>Trip Route</h3>
<p class="section-help">Type city name and select the correct airport</p>

<div class="row g-3">
  <div class="col-md-4">
    <select class="form-select" name="trip_type" required>
      <option value="">Trip type</option>
      <option value="one_way">One Way</option>
      <option value="round_trip">Round Trip</option>
      <option value="multi_city">Multi City</option>
    </select>
  </div>

  <div class="col-md-4">
    <select id="departure_airport" required></select>
  </div>

  <div class="col-md-4">
    <select id="destination_airport" required></select>
  </div>
</div>
</section>

<!-- DATES -->
<section class="form-section">
<h3>Travel Dates & Cabin</h3>
<div class="row g-3">
  <div class="col-md-4">
    <input type="date" class="form-control" name="departure_date" required>
  </div>

  <div class="col-md-4">
    <input type="date" class="form-control" name="return_date">
  </div>

  <div class="col-md-4">
    <select class="form-select" name="cabin_class" required>
      <option value="">Cabin class</option>
      <option value="economy">Economy</option>
      <option value="business">Business</option>
      <option value="first">First Class</option>
    </select>
  </div>
</div>
</section>

<!-- PASSENGERS -->
<section class="form-section">
<h3>Passengers</h3>
<input type="number" min="1" value="1"
  class="form-control"
  name="passengers"
  placeholder="Total number of passengers"
  required>
</section>

<!-- AIRLINES -->
<section class="form-section">
<h3>Airline Preferences</h3>
<p class="section-help">Optional – select preferred airlines</p>
<select id="airlines" multiple></select>
</section>

<!-- PAYMENT -->
<section class="form-section">
<h3>Payment Method</h3>
<select class="form-select" name="payment_method" id="paymentMethod" required>
  <option value="">Choose payment method</option>
  <option value="mobile_money">Mobile Money</option>
  <option value="bank_transfer">Bank Transfer</option>
  <option value="cash">Cash</option>
</select>

<div class="payment-box mt-3" id="mobileBox">
<strong>Mobile Money</strong><br>
MTN: +250 788 000 000<br>
Airtel: +250 730 000 000
</div>
</section>

<section class="form-section">
<div class="form-check">
  <input class="form-check-input" type="checkbox" required>
  <label class="form-check-label">
    I confirm the information is accurate and consent to be contacted.
  </label>
</div>
</section>

<div class="text-center">
<button type="submit" class="btn-submit">Submit Reservation</button>
</div>

</form>
</div>

<?php include 'footer.php'; ?>

<div id="uploadOverlay">
  <div class="upload-card">
    <strong>Processing…</strong>
    <p>Please wait</p>
  </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>

<script>
$(function(){

  const iti = intlTelInput(phone,{separateDialCode:true});
  const syncPhone = ()=>{
    const d = iti.getSelectedCountryData();
    $("#phone_area_code").val("+"+d.dialCode);
    $("#phone_number").val(phone.value.trim());
  };
  syncPhone();
  phone.addEventListener("countrychange",syncPhone);
  phone.addEventListener("input",syncPhone);

  $("#departure_airport").select2({
    theme:"bootstrap-5",
    placeholder:"Type city name",
    minimumInputLength:2,
    ajax:{url:"getAirports.php",dataType:"json",delay:300,
      data:p=>({q:p.term}),
      processResults:d=>({results:d})}
  }).on("select2:select",e=>{
    $("#departure_city").val(e.params.data.id);
    $("#departure_city_text").val(e.params.data.text);
  });

  $("#destination_airport").select2({
    theme:"bootstrap-5",
    placeholder:"Type city name",
    minimumInputLength:2,
    ajax:{url:"getAirports.php",dataType:"json",delay:300,
      data:p=>({q:p.term}),
      processResults:d=>({results:d})}
  }).on("select2:select",e=>{
    $("#destination_city").val(e.params.data.id);
    $("#destination_city_text").val(e.params.data.text);
  });

  $("#airlines").select2({
    theme:"bootstrap-5",
    placeholder:"Search airlines",
    minimumInputLength:1,
    ajax:{url:"getAirlines.php",dataType:"json",delay:300,
      data:p=>({q:p.term}),
      processResults:d=>({results:d})}
  }).on("change",function(){
    const v=$(this).val();
    $("#airline_id").val(v && v.length ? v[0] : "");
  });

  $("#paymentMethod").on("change",()=>{
    $("#mobileBox").toggle(paymentMethod.value==="mobile_money");
  });

  $("#airForm").on("submit",async function(e){
    e.preventDefault();
    $("#uploadOverlay").show();
    try{
      const res = await fetch("submitAirReservation.php",{method:"POST",body:new FormData(this)});
      const data = await res.json();
      if(data.status==="success"){
        window.location.href="thank-you.php?id="+encodeURIComponent(data.user_id);
      }else{
        alert(data.message||"Submission failed");
      }
    }catch{
      alert("Network error");
    }finally{
      $("#uploadOverlay").hide();
    }
  });

});
</script>

</body>
</html>
