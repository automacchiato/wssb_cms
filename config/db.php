<?php
$host = "127.0.0.1:3306";
$user = "u647109978_admin";
$pass = "Mocha98@";
$db   = "u647109978_wssb";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
