<?php
session_start();

if (!isset($_GET['id'])) {
  header('Location: index.php');
  exit;
}

/* Destroy old application session */
unset($_SESSION['user_id']);

/* Generate NEW ID for next application */
$newId = 'user-' . time() . '-' . random_int(1000,9999);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Application Submitted | Xander Global Scholars</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body{
  font-family:Inter,system-ui,sans-serif;
  background:#f8fafc;
  text-align:center;
  padding:80px 20px;
}
.box{
  max-width:600px;
  margin:auto;
  background:#fff;
  border-radius:18px;
  padding:40px;
  box-shadow:0 20px 40px rgba(0,0,0,.08);
}
h1{color:#1e3a5f}
.btn{
  display:inline-block;
  margin-top:30px;
  background:#ff8c42;
  color:#fff;
  padding:14px 28px;
  border-radius:10px;
  text-decoration:none;
  font-weight:700;
}
</style>
</head>
<body>

<div class="box">
  <h1>🎉 Application Submitted Successfully</h1>
  <p>Your application has been received and is under review.</p>
  <p><strong>Reference ID:</strong> <?=htmlspecialchars($_GET['id'])?></p>

  <a class="btn" href="job-application.php?id=<?=$newId?>">
    Submit Another Application
  </a>
</div>

<script>
/* Reset frontend ID */
sessionStorage.removeItem('user_id');
sessionStorage.setItem('user_id', "<?=$newId?>");
</script>

</body>
</html>
