<?php
include 'db.php';

$id = $_POST['id'];

$sql = "DELETE FROM schools WHERE id='$id'";

if ($conn->query($sql) === TRUE) {
    echo "success";
} else {
    echo "error";
}
?>
