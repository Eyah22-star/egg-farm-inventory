<?php
session_start();

// SECURITY CHECK: Kung walang session o hindi manager, ibalik sa login form
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Panel - Egg Farm Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f0f7fc;
            display: flex;
            min-height: 100vh;
            color: #333;
        }

        /* Sidebar System Styling */
        .sidebar {
            width: 260px;
            background-color: #e3edf7;
            display: flex;
            flex-direction: column;
            padding: 30px 15px;
            border-right: 1px solid #d0dfeb;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 35px;
            padding-bottom: 20px;
            border-bottom: 1px solid #c2d5e7;
        }

        .logo-img {
            width: 85px;
            height: auto;
            margin-bottom: 10px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .panel-title {
            color: #1e4473;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .menu-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
            height: 100%;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #4a5568;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            gap: 15px;
        }

        .menu-item i {
            font-size: 18px;
            color: #637b97;
            width: 25px;
            text-align: center;
        }

        .menu-item:hover {
            background-color: rgba(30, 68, 115, 0.05);
            color: #1e4473;
        }

        .menu-item.active {
            background-color: #cddceb;
            color: #1e4473;
            font-weight: 600;
        }

        .menu-item.active i {
            color: #1e4473;
        }

        .logout-section {
            margin-top: auto;
        }
        
        .btn-logout {
            color: #e53e3e;
            text-decoration: none;
        }
        .btn-logout i {
            color: #e53e3e;
        }
        .btn-logout:hover {
            background-color: #fed7d7;
            color: #c53030;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 260px;
            flex-grow: 1;
            padding: 40px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
        }

        .header-content h2 {
            color: #1e4473;
            font-size: 24px;
            font-weight: 700;
        }

        .welcome-msg {
            font-size: 14px;
            color: #718096;
            font-weight: 500;
        }

        /* Tabs Visibility Rules */
        .tab-content {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .tab-content.active {
            display: block;
        }

        .content-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            border: 1px solid #e3edf7;
            min-height: 300px;
        }

        .content-card h3 {
            color: #1e4473;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .content-card p {
            color: #4a5568;
            font-size: 14px;
            line-height: 1.6;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="logo-section">
            <img src="/EggFarm/vdvc.png" alt="VDVC Logo" class="logo-img">
            <h1 class="panel-title">Manager Panel</h1>
        </div>

        <ul class="menu-list">
            <li class="menu-item active" data-tab="dashboard">
                <i class="fa-solid fa-chart-pie"></i> Dashboard
            </li>
            <li class="menu-item" data-tab="inventory">
                <i class="fa-solid fa-boxes-stacked"></i> Inventory Management
            </li>
            <li class="menu-item" data-tab="reservation">
                <i class="fa-solid fa-calendar-check"></i> Reservation Management
            </li>
            <li class="menu-item" data-tab="delivery">
                <i class="fa-solid fa-truck-ramp-box"></i> Delivery Management
            </li>
            <li class="menu-item" data-tab="profile">
                <i class="fa-solid fa-user-gear"></i> Profile Management
            </li>
            <li class="menu-item" data-tab="reports">
                <i class="fa-solid fa-file-invoice-dollar"></i> Reports
            </li>
            
            <div class="logout-section">
                <a href="logout.php" class="menu-item btn-logout">
                    <i class="fa-solid fa-right-from-bracket"></i> Log Out
                </a>
            </div>
        </ul>
    </nav>

    <main class="main-content">
        
        <div class="header-content">
            <h2 id="panelHeaderTitle">Dashboard</h2>
           <div class="welcome-msg">Welcome back, <?php echo htmlspecialchars(isset($_SESSION['username']) ? $_SESSION['username'] : 'Manager'); ?>!</div>
        </div>

        <div id="dashboard" class="tab-content active">
            <div class="content-card">
                <?php
                    if (file_exists('manager_dashboard.php')) {
                        include('manager_dashboard.php');
                    } else {
                        echo "<h3 style='color:#e53e3e;'>File Not Found</h3>";
                        echo "<p>manager_dashboard.php was not found.</p>";
                    }
                ?>
            </div>
        </div>

        <div id="inventory" class="tab-content">
            <div class="content-card">
                <?php 
                    if (file_exists('inventory.php')) {
                        include('inventory.php'); 
                    } else {
                        echo "<h3 style='color: #e53e3e;'>File Not Found</h3>";
                        echo "<p>Paalala: Siguraduhing magkasama sa iisang folder ang <strong>manager_panel.php</strong> at <strong>inventory.php</strong>.</p>";
                    }
                ?>
            </div>
        </div>

        <div id="reservation" class="tab-content">
            <div class="content-card">
                <h3>Customer Reservations</h3>
                <p>Subaybayan at i-approve ang mga order o reserbasyon ng mga kliyente bago i-iskedyul para sa pick-up o delivery.</p>
            </div>
        </div>

        <div id="delivery" class="tab-content">
            <div class="content-card">
                <h3>Delivery Logistics</h3>
                <p>I-manage ang mga sasakyan, dispatch status, at lokasyon ng mga orders na kasalukuyang hinahatid sa mga bumili.</p>
            </div>
        </div>

        <div id="profile" class="tab-content">
            <div class="content-card">
                <h3>Account Settings</h3>
                <p>Baguhin ang iyong pangalan, password, email address, o mga personal na detalye bilang manager ng system.</p>
            </div>
        </div>

        <div id="reports" class="tab-content">
            <div class="content-card">
                <h3>Data-Driven Analytics & Reports</h3>
                <p>Mag-generate ng mga ulat patungkol sa demand forecasting, sales statistics, at imbentaryo na pwedeng i-print.</p>
            </div>
        </div>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // Kunin lang ang mga menu items na MAY data-tab attribute
            const menuItems = document.querySelectorAll('.menu-list .menu-item[data-tab]');
            const tabContents = document.querySelectorAll('.tab-content');
            const panelHeaderTitle = document.getElementById('panelHeaderTitle');

            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    const targetTabId = this.getAttribute('data-tab');
                    const targetTabContent = document.getElementById(targetTabId);

                    if (!targetTabContent) return;

                    // Alisin ang active class sa lahat ng menu items at tab contents
                    menuItems.forEach(i => i.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));

                    // Lagyan ng active class ang napiling item at tab content
                    this.classList.add('active');
                    targetTabContent.classList.add('active');

                    // Palitan ang title sa header
                    panelHeaderTitle.innerText = this.textContent.trim();
                });
            });
        });
    </script>
</body>
</html>