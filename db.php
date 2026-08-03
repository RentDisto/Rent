<?php
$conn = mysqli_connect("localhost", "root", "", "device-rent");
//                                          ↑↑↑↑↑↑↑↑↑↑↑
// must be the same database where admins table exists

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}
?>