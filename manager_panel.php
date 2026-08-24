<?php
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header("Location: login.php");
    exit();
}

$current = basename($_SERVER['PHP_SELF']);
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>


*{
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

.sidebar{
    width:260px;
    height:100vh;
    background:#e3edf7;
    display:flex;
    flex-direction:column;
    padding:30px 15px;
    border-right:1px solid #d0dfeb;
    position:fixed;
    left:0;
    top:0;
    overflow:hidden;
}
.logo-section{
    text-align:center;
    margin-bottom:35px;
    padding-bottom:20px;
    border-bottom:1px solid #c2d5e7;
}

.logo-img{
    width:85px;
    display:block;
    margin:auto;
}

.panel-title{
    color:#1e4473;
    font-size:15px;
    font-weight:700;
    text-transform:uppercase;
}

.menu-list{
    list-style:none;
    display:flex;
    flex-direction:column;
    gap:8px;
   flex:1;
    padding:0;
}
.menu-item{
    display:flex;
    align-items:center;
    gap:15px;
    padding:12px 20px;
    text-decoration:none;
    color:#4a5568;
    border-radius:10px;
    font-size:14px;
    font-weight:500;
    transition:.2s;
}

.menu-item i{
    width:25px;
    text-align:center;
    font-size:18px;
}

.menu-item:hover{
    background:#d7e5f2;
    color:#1e4473;
}

.menu-item.active{
    background:#cddceb;
    color:#1e4473;
    font-weight:600;
}

.logout-section {
    margin-top: auto;
    margin-bottom: 190px;
    position: relative;
    z-index: 2;
}

.btn-logout{
    color:#e53e3e;
}

.btn-logout:hover{
    background:#fed7d7;
}

.main-content{
    margin-left:260px;
    padding:30px;
}


</style>

<nav class="sidebar">

    <div class="logo-section">
        <img src="/EggFarm/vdvc.png" class="logo-img">
        <div class="panel-title">
            Manager Panel
        </div>
    </div>

    <ul class="menu-list">

        <a href="manager_dashboard.php"
        class="menu-item <?=($current=='register.php')?'active':'';?>">

            <i class="fa-solid fa-chart-pie"></i>
            Dashboard

        </a>

        <a href="inventory.php"
        class="menu-item <?=($current=='inventory.php')?'active':'';?>">

            <i class="fa-solid fa-boxes-stacked"></i>
            Inventory Management

        </a>

        <a href="manager_reservation.php"
        class="menu-item <?=($current=='manager_reservation.php')?'active':'';?>">

            <i class="fa-solid fa-calendar-check"></i>
            Reservation Management

        </a>

        <a href="manager_delivery.php"
        class="menu-item <?=($current=='manager_delivery.php')?'active':'';?>">

            <i class="fa-solid fa-truck-ramp-box"></i>
            Delivery Management

        </a>

        <a href="profile.php"
        class="menu-item <?=($current=='profile.php')?'active':'';?>">

            <i class="fa-solid fa-user-gear"></i>
            Profile Management

        </a>

        <a href="manager_reports.php"
        class="menu-item <?=($current=='manager_reports.php')?'active':'';?>">

            <i class="fa-solid fa-file-invoice-dollar"></i>
            Reports

        </a>

        <div class="logout-section">

            <a href="logout.php" class="menu-item btn-logout">

                <i class="fa-solid fa-right-from-bracket"></i>

                Log Out

            </a>

        </div>

    </ul>

</nav>