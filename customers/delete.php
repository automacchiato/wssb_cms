<?php
include('../config/db.php');

$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM customers WHERE customer_id=$id");

header("Location: index.php");
