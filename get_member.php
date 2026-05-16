<?php
include 'db.php';
$id = intval($_GET['id']);
$res = $conn->query("SELECT * FROM members WHERE id=$id");
echo json_encode($res->fetch_assoc());
?>
