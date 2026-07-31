<?php
session_start();

if($_SESSION['role']!="owner"){
    header("Location: ../login.php");
}
?>

<h1>OWNER DASHBOARD</h1>

<a href="logout.php">Logout</a>
Logout
</a>