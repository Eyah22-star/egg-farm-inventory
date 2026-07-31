<?php
session_start();

if($_SESSION['role']!="customer"){
    header("Location: ../login.php");
}
?>

<h1>CUSTOMER DASHBOARD</h1>

<a href="logout.php">Logout</a>
Logout
</a>