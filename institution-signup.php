<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/company_branding.php';
require_once __DIR__ . '/helpers/csrf.php';
require_once __DIR__ . '/helpers/institution_portal.php';
require_once __DIR__ . '/helpers/urls.php';

xander_institution_portal_ensure_schema($conn);

if (!empty($_SESSION['institution_account_id'])) {
    header('Location: ' . pcvc_url('/institution/index.php'));
    exit;
}

$regions = [];
$resR = $conn->query('SELECT id, name FROM regions ORDER BY name');
if ($resR) {
    while ($row = $resR->fetch_assoc()) {
        $regions[] = $row;
    }
}

$countries = [];
$resC = $conn->query('SELECT id, name FROM countries ORDER BY name');
if ($resC) {
    while ($row = $resC->fetch_assoc()) {
        $countries[] = $row;
    }
}

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!pcvc_csrf_validate_post()) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $universityId = (int) ($_POST['university_id'] ?? 0);
        $name = trim((string) ($_POST['institution_name'] ?? ''));
        $regionId = (int) ($_POST['region_id'] ?? 0);
        $countryId = (int) ($_POST['country_id'] ?? 0);
        $website = trim((string) ($_POST['website'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $institutionPhone = trim((string) ($_POST['institution_phone'] ?? ''));
        $institutionKind = trim((string) ($_POST['institution_kind'] ?? ''));
        $contactName = trim((string) ($_POST['contact_name'] ?? ''));
        $contactTitle = trim((string) ($_POST['contact_title'] ?? ''));
        $email = xander_institution_email_norm((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));

        $universityId = xander_institution_upsert_university(
            $conn,
            $universityId,
            $name,
            $regionId,
            $countryId,
            $website,
            $city,
            $institutionPhone,
            $institutionKind
        );

        if ($universityId <= 0) {
            $error = 'Please enter institution name, region, and country.';
        } else {
            $reg = xander_institution_register_portal_account(
                $conn,
                $universityId,
                $contactName,
                $contactTitle,
                $email,
                null,
                $phone
            );
            if (!$reg['ok']) {
                $error = $reg['message'];
            } else {
                $tempPassword = (string) ($reg['temp_password'] ?? '');
                $auth = xander_institution_authenticate($conn, $email, $tempPassword);
                if ($auth['ok']) {
                    $acc = $auth['account'];
                    session_regenerate_id(true);
                    $_SESSION['institution_account_id'] = (int) $acc['id'];
                    $_SESSION['institution_university_id'] = (int) $acc['university_id'];
                    $_SESSION['institution_email'] = $email;
                    $_SESSION['institution_name'] = trim((string) $acc['contact_name']);
                    $_SESSION['institution_university_name'] = trim((string) ($acc['university_name'] ?? $name));
                    header('Location: ' . pcvc_url('/institution/index.php?welcome=1'));
                    exit;
                }
                header('Location: ' . pcvc_url('/institution-login.php?email=' . rawurlencode($email) . '&registered=1'));
                exit;
            }
        }
    }
}

$appRoot = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$companyName = htmlspecialchars(PCVC_COMPANY_DISPLAY_NAME, ENT_QUOTES, 'UTF-8');
$fontsCssUrl = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Institution registration | <?php echo $companyName; ?></title>
  <link href="<?php echo htmlspecialchars($fontsCssUrl, ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root{--navy:#012F6B;--navy2:#254D81;--gold:#F2A65A;--bg:#eef2f7;}
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,system-ui,sans-serif;background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px 16px}
    .card{width:100%;max-width:720px;border:0;border-radius:20px;box-shadow:0 24px 60px rgba(1,47,107,.14);overflow:visible;background:#fff}
    .card-head{background:linear-gradient(135deg,var(--navy),var(--navy2));color:#fff;padding:26px 28px;text-align:center}
    .card-head h1{margin:0;font-size:1.35rem;font-weight:800}
    .card-body{padding:28px 28px 32px}
    .form-control,.form-select{border-radius:12px;border-color:#e2e8f0;padding:12px 14px;font-size:.95rem}
    .form-control:focus,.form-select:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(242,166,90,.28)}
    .form-control::placeholder,.form-select option[value=""]{color:#94a3b8}
    .lookup-wrap{position:relative}
    .lookup-results{position:absolute;left:0;right:0;top:100%;z-index:50;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 12px 32px rgba(0,0,0,.12);max-height:220px;overflow-y:auto;display:none;margin-top:4px}
    .lookup-results.show{display:block}
    .lookup-item{padding:11px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9;font-size:.9rem}
    .lookup-item:hover{background:#f8fafc}
    .lookup-item:last-child{border-bottom:0}
    .lookup-item small{display:block;color:#64748b;font-size:.78rem;margin-top:2px}
    .lookup-badge{font-size:.65rem;background:#fee2e2;color:#b91c1c;padding:2px 8px;border-radius:999px;margin-left:6px}
    .btn-primary-custom{background:linear-gradient(135deg,var(--navy),var(--navy2));color:#fff;font-weight:700;border:0;border-radius:12px;padding:14px 20px;width:100%}
    .btn-primary-custom:hover{filter:brightness(1.06);color:#fff}
    .btn-link-custom{color:var(--navy);font-weight:600;text-decoration:none;font-size:.9rem}
    .btn-link-custom:hover{text-decoration:underline}
    .divider{height:1px;background:#e2e8f0;margin:20px 0}
  </style>
</head>
<body>
  <div class="card">
    <div class="card-head">
      <h1><i class="fas fa-building-columns me-2"></i>Register your institution</h1>
    </div>
    <div class="card-body">
      <?php if ($error !== ''): ?>
      <div class="alert alert-danger py-2 mb-3"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <form method="post" id="instSignupForm" autocomplete="off" novalidate>
        <?php echo pcvc_csrf_input(); ?>
        <input type="hidden" name="university_id" id="university_id" value="0">
        <input type="hidden" name="has_portal_block" id="has_portal_block" value="0">

        <div class="lookup-wrap mb-3">
          <input type="text" class="form-control form-control-lg" name="institution_name" id="institution_name" required placeholder="Institution name *">
          <div class="lookup-results" id="lookupResults" role="listbox"></div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <select class="form-select" name="region_id" id="region_id" required>
              <option value="">Region *</option>
              <?php foreach ($regions as $r): ?>
              <option value="<?php echo (int) $r['id']; ?>"><?php echo htmlspecialchars((string) $r['name'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <select class="form-select" name="country_id" id="country_id" required>
              <option value="">Country *</option>
              <?php foreach ($countries as $c): ?>
              <option value="<?php echo (int) $c['id']; ?>"><?php echo htmlspecialchars((string) $c['name'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <select class="form-select" name="institution_kind" id="institution_kind">
              <option value="">Institution type</option>
              <option value="university">University</option>
              <option value="college">College</option>
              <option value="school">School</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="col-md-4">
            <input class="form-control" name="city" id="city" placeholder="City">
          </div>
          <div class="col-md-4">
            <input class="form-control" name="website" id="website" placeholder="Website (https://)">
          </div>
          <div class="col-12">
            <input class="form-control" name="institution_phone" id="institution_phone" placeholder="Institution phone">
          </div>
        </div>

        <div class="divider"></div>

        <div class="row g-3">
          <div class="col-md-6">
            <input class="form-control" name="contact_name" required placeholder="Your full name *">
          </div>
          <div class="col-md-6">
            <input class="form-control" name="contact_title" placeholder="Job title">
          </div>
          <div class="col-md-6">
            <input class="form-control" type="email" name="email" required placeholder="Email (login) *">
          </div>
          <div class="col-md-6">
            <input class="form-control" name="phone" placeholder="Your phone">
          </div>
        </div>

        <button type="submit" class="btn btn-primary-custom mt-4" id="submitBtn">
          <i class="fas fa-check me-2"></i>Create portal access
        </button>
        <p class="text-center mt-3 mb-0">
          <a href="institution-login.php" class="btn-link-custom">Already registered? Sign in</a>
        </p>
      </form>
    </div>
  </div>

  <script type="application/json" id="inst-signup-config"><?php echo json_encode(['apiBase' => $appRoot], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
  <script src="assets/js/institution-signup.js"></script>
</body>
</html>
