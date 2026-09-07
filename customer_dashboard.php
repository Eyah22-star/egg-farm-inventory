<?php
ob_start();

if (session_id() == '') {
    session_start();
}

require 'db.php';

/* =========================================================
   SECURITY CHECK
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}


/* =========================================================
   CUSTOMER INFORMATION
========================================================= */

$customer_id = $_SESSION['user_id'];

$customer_name = isset($_SESSION['customer_name'])
    ? $_SESSION['customer_name']
    : (isset($_SESSION['name']) ? $_SESSION['name'] : 'Customer');


/* =========================================================
   TOTAL RESERVATIONS
========================================================= */

$total_reservations = 0;

$reservation_count_sql = "
    SELECT COUNT(DISTINCT reservation_code) AS total
    FROM reservations
    WHERE customer_id = ?
";

$stmt = $conn->prepare($reservation_count_sql);

if ($stmt) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $total_reservations = $row['total'];
    }

    $stmt->close();
}


/* =========================================================
   TOTAL TRAYS ORDERED
========================================================= */

$total_trays = 0;

$total_trays_sql = "
    SELECT SUM(quantity) AS total
    FROM reservations
    WHERE customer_id = ?
";

$stmt = $conn->prepare($total_trays_sql);

if ($stmt) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $total_trays = $row['total'] ? $row['total'] : 0;
    }

    $stmt->close();
}


/* =========================================================
   AVAILABLE EGG STOCKS
========================================================= */

$egg_stocks = array();

$stock_sql = "
    SELECT 
        egg_size,
        available_stock
    FROM egg_inventory
    ORDER BY 
        FIELD(
            egg_size,
            'XS',
            'Small',
            'Medium',
            'Large',
            'XL',
            'Jumbo',
            'Super Jumbo',
            'Double Yolk'
        )
";

$stock_result = $conn->query($stock_sql);

if ($stock_result) {
    while ($stock_row = $stock_result->fetch_assoc()) {
        $egg_stocks[] = $stock_row;
    }
}


/* =========================================================
   RECENT CUSTOMER ACTIVITY
========================================================= */

$recent_activities = array();

$activity_sql = "
    SELECT
        reservation_code,
        egg_size,
        quantity,
        status,
        reserved_at
    FROM reservations
    WHERE customer_id = ?
    ORDER BY reserved_at DESC
    LIMIT 5
";

$stmt = $conn->prepare($activity_sql);

if ($stmt) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result) {
        while ($activity_row = $result->fetch_assoc()) {
            $recent_activities[] = $activity_row;
        }
    }

    $stmt->close();
}


/* =========================================================
   GREETING
========================================================= */

$current_hour = date('H');

if ($current_hour < 12) {
    $greeting = "Good morning";
} elseif ($current_hour < 18) {
    $greeting = "Good afternoon";
} else {
    $greeting = "Good evening";
}


/* =========================================================
   GET FIRST NAME
========================================================= */

$name_parts = explode(" ", trim($customer_name));
$first_name = $name_parts[0];


/* =========================================================
   STATUS CLASS
========================================================= */

function getActivityIcon($status)
{
    $status = strtolower($status);

    if ($status == 'confirmed') {
        return array(
            'icon' => 'fa-calendar-check',
            'class' => 'confirmed'
        );
    }

    if ($status == 'completed') {
        return array(
            'icon' => 'fa-circle-check',
            'class' => 'completed'
        );
    }

    if (
        $status == 'cancelled' ||
        $status == 'canceled' ||
        $status == 'rejected'
    ) {
        return array(
            'icon' => 'fa-circle-xmark',
            'class' => 'cancelled'
        );
    }

    if ($status == 'pending') {
        return array(
            'icon' => 'fa-clock',
            'class' => 'pending'
        );
    }

    return array(
        'icon' => 'fa-calendar',
        'class' => 'default'
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Customer Dashboard</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>
html,
body {
    margin: 0;
    padding: 0;
    width: 100%;
    min-height: 100%;
}
        /* =====================================================
           MAIN CONTENT
        ===================================================== */

     .main-content {
    min-height: 100vh;
    padding: 30px;
    box-sizing: border-box;
    background: #f4f2eb;
}


        /* =====================================================
           DASHBOARD CONTAINER
        ===================================================== */
.customer-dashboard-wrapper {
    max-width: 1600px;
    margin: 0 auto;

    /* Same style as Manager Dashboard outer card */
    background: #f7f6f1;

    border: 1px solid #e2e1da;

    border-radius: 28px;

    padding: 28px;

    box-shadow:
        0 8px 30px rgba(70, 80, 73, 0.05);
}


        /* =====================================================
           HEADER
        ===================================================== */

        .dashboard-header {
            margin-bottom: 28px;
        }

        .dashboard-header h1 {
            margin: 0;
            font-size: 30px;
            font-weight: 700;
            color: #3f342c;
            letter-spacing: -0.5px;
        }

        .dashboard-header h1 span {
            font-size: 28px;
        }

        .dashboard-header p {
            margin: 7px 0 0;
            font-size: 14px;
            color: #786f67;
        }


        /* =====================================================
           SUMMARY CARDS
        ===================================================== */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 22px;
        }


       .summary-card {
    min-height: 108px;
    background: #fffdf8;
    border: 1px solid #e8e0d2;
    border-radius: 14px;
    padding: 20px 22px;
    box-sizing: border-box;

    display: flex;
    align-items: center;

    box-shadow:
        0 5px 18px rgba(89, 68, 46, 0.06);

    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

        .summary-card:hover {
            transform: translateY(-3px);

            box-shadow:
                0 10px 25px rgba(89, 68, 46, 0.10);
        }


        /* =====================================================
           SUMMARY ICON
        ===================================================== */

        .summary-icon {
            width: 56px;
            height: 56px;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 22px;
            margin-right: 18px;

            flex-shrink: 0;
        }


        .summary-icon.account {
            background: #f4eee5;
            color: #735334;
        }

        .summary-icon.reservation {
            background: #eee5f4;
            color: #76548b;
        }

        .summary-icon.trays {
            background: #e4edf2;
            color: #52758b;
        }


        .summary-content span {
            display: block;
            font-size: 12px;
            color: #746b63;
            margin-bottom: 5px;
        }

        .summary-content h2 {
            margin: 0;
            font-size: 25px;
            color: #302923;
            font-weight: 700;
        }

        .summary-content small {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: #827970;
        }


        /* =====================================================
           MAIN DASHBOARD GRID
        ===================================================== */

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.9fr;
            gap: 18px;
        }


        /* =====================================================
           DASHBOARD CARD
        ===================================================== */
.dashboard-card {
    background: #fffdf8;

    border: 1px solid #e8e0d2;

    border-radius: 16px;

    box-shadow:
        0 5px 18px rgba(89, 68, 46, 0.06);

    overflow: hidden;
}

        /* =====================================================
           CARD HEADER
        ===================================================== */

        .card-header {
            padding: 20px 22px 14px;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }


        .card-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }


        .card-title-icon {
            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #f4eee5;

            color: #795a3d;

            font-size: 17px;
        }


        .card-title-text h2 {
            margin: 0;

            font-size: 17px;

            font-weight: 700;

            color: #443931;
        }


        .card-title-text p {
            margin: 4px 0 0;

            font-size: 11px;

            color: #80776f;
        }


        .view-all-btn {
            color: #5d4633;

            text-decoration: none;

            font-size: 12px;

            font-weight: 600;

            transition: color 0.2s ease;
        }

        .view-all-btn:hover {
            color: #9b6a38;
        }


        /* =====================================================
           STOCK TABLE
        ===================================================== */

        .stock-table-container {
            padding: 0 18px;
        }


        .stock-table {
            width: 100%;

            border-collapse: collapse;

            font-size: 13px;
        }


        .stock-table thead {
            background: #f5f1eb;
        }


        .stock-table th {
            padding: 13px 14px;

            text-align: left;

            font-size: 11px;

            font-weight: 600;

            color: #625950;
        }


        .stock-table td {
            padding: 14px;

            color: #554b43;

            border-bottom:
                1px solid #eee9e2;
        }


        .stock-table tbody tr:last-child td {
            border-bottom: none;
        }


        .stock-table tbody tr:hover {
            background: #faf8f4;
        }


        .stock-number {
            font-size: 17px;

            font-weight: 700;

            color: #312a25;
        }


        .stock-status {
            display: inline-flex;

            align-items: center;

            gap: 7px;

            font-size: 11px;

            color: #4d7b47;

            font-weight: 500;
        }


        .status-dot {
            width: 7px;

            height: 7px;

            border-radius: 50%;

            background: #4c8748;
        }


        /* =====================================================
           STOCK FOOTER
        ===================================================== */

        .stock-footer {
            padding: 16px 22px 18px;
        }


        .stock-info {
            display: flex;

            align-items: center;

            gap: 8px;

            color: #756c64;

            font-size: 10px;

            margin-bottom: 16px;
        }


        .stock-info i {
            color: #8b6a48;
        }


        .reservation-button {
            width: 100%;

            max-width: 220px;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 9px;

            padding: 12px 18px;

            background:
                linear-gradient(
                    135deg,
                    #5b3a22,
                    #3d2617
                );

            color: white;

            border-radius: 8px;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

            box-shadow:
                0 5px 12px rgba(77, 47, 25, 0.18);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }


        .reservation-button:hover {
            transform: translateY(-2px);

            box-shadow:
                0 8px 18px rgba(77, 47, 25, 0.25);
        }


        /* =====================================================
           RECENT ACTIVITY
        ===================================================== */

        .activity-list {
            padding: 0 22px 15px;

            max-height: 390px;

            overflow-y: auto;
        }


        .activity-list::-webkit-scrollbar {
            width: 5px;
        }


        .activity-list::-webkit-scrollbar-thumb {
            background: #d7cec3;

            border-radius: 10px;
        }


        .activity-item {
            display: flex;

            align-items: center;

            gap: 13px;

            padding: 16px 0;

            border-bottom:
                1px solid #eee9e2;
        }


        .activity-item:last-child {
            border-bottom: none;
        }


        .activity-icon {
            width: 38px;
            height: 38px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            font-size: 15px;
        }


        .activity-icon.confirmed {
            background: #e9f1e7;
            color: #55834d;
        }


        .activity-icon.completed {
            background: #e8f0f5;
            color: #547c95;
        }


        .activity-icon.cancelled {
            background: #fbe9e6;
            color: #c65b4e;
        }


        .activity-icon.pending {
            background: #fbf0dd;
            color: #b77a2c;
        }


        .activity-icon.default {
            background: #eeeeee;
            color: #777777;
        }


        .activity-content {
            flex: 1;

            min-width: 0;
        }


        .activity-content h4 {
            margin: 0 0 5px;

            font-size: 12px;

            color: #40372f;

            font-weight: 600;
        }


        .activity-content p {
            margin: 0;

            font-size: 10px;

            color: #817870;
        }


        .activity-time {
            font-size: 10px;

            color: #756c64;

            white-space: nowrap;
        }


        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .empty-state {
            padding: 55px 20px;

            text-align: center;

            color: #938a82;
        }


        .empty-state i {
            font-size: 35px;

            margin-bottom: 12px;

            color: #c4b8aa;
        }


        .empty-state p {
            margin: 0;

            font-size: 13px;
        }


        /* =====================================================
           SUPPORT SECTION
        ===================================================== */

        .support-card {
            margin-top: 18px;

            min-height: 74px;

            position: relative;

            overflow: hidden;

            display: flex;

            align-items: center;

            padding: 0 24px;

            background:
                linear-gradient(
                    90deg,
                    #fbfaf7,
                    #f4f1eb
                );

            border: 1px solid rgba(120, 100, 80, 0.12);

            border-radius: 15px;

            box-shadow:
                0 5px 18px rgba(89, 68, 46, 0.04);
        }


     .support-icon {
    width: 48px;
    height: 48px;

    border-radius: 50%;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #f2ede5;
    color: #705337;

    margin-right: 16px;

    font-size: 20px;

    position: relative;
    z-index: 2;

    flex-shrink: 0;
}
.support-content {
    position: relative;
    z-index: 2;
}


        .support-content h3 {
            margin: 0 0 5px;

            font-size: 13px;

            color: #51443a;
        }


        .support-content p {
            margin: 0;

            font-size: 11px;

            color: #7c726a;
        }


        /* =====================================================
           DECORATION
        ===================================================== */

    .farm-decoration {
    position: absolute;
    right: 0;
    bottom: 0;

    height: 100%;
    width: 55%;

    display: flex;
    justify-content: flex-end;
    align-items: flex-end;

    pointer-events: none;
    z-index: 1;

    overflow: hidden;
}

.farm-decoration img {
    width: 100%;
    height: auto;

    max-height: 100%;
    object-fit: contain;
    object-position: right bottom;

    display: block;
}

        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1100px) {

            .summary-grid {
                grid-template-columns: 1fr;
            }


            .dashboard-grid {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 768px) {

            .main-content {
                padding: 20px 15px;
            }


            .dashboard-header h1 {
                font-size: 24px;
            }


            .dashboard-header h1 span {
                font-size: 22px;
            }


            .summary-card {
                padding: 17px;
            }


            .stock-table-container {
                overflow-x: auto;
            }


            .stock-table {
                min-width: 550px;
            }


            .farm-decoration {
                display: none;
            }

        }

    </style>

</head>


<body class="bg-slate-100 font-sans text-gray-700 antialiased min-h-screen">

<?php include('customer_panel.php'); ?>


<!-- =====================================================
     MAIN CONTENT
===================================================== -->
<div class="main-content">
 

    <div class="customer-dashboard-wrapper">


        <!-- =====================================================
             DASHBOARD HEADER
        ===================================================== -->

        <div class="dashboard-header">

            <h1>
                <?php echo $greeting; ?>,
                <?php echo htmlspecialchars($first_name); ?>!
                <span>👋</span>
            </h1>

            <p>
                Here's a quick overview of your account.
            </p>

        </div>


        <!-- =====================================================
             SUMMARY CARDS
        ===================================================== -->

        <div class="summary-grid">


            <!-- ACCOUNT TYPE -->

            <div class="summary-card">

                <div class="summary-icon account">

                    <i class="fa-regular fa-user"></i>

                </div>


                <div class="summary-content">

                    <span>Account Type</span>

                    <h2>Customer</h2>

                    <small>Regular Customer</small>

                </div>

            </div>


            <!-- TOTAL RESERVATIONS -->

            <div class="summary-card">

                <div class="summary-icon reservation">

                    <i class="fa-regular fa-calendar-check"></i>

                </div>


                <div class="summary-content">

                    <span>Total Reservations</span>

                    <h2>
                        <?php echo $total_reservations; ?>
                    </h2>

                    <small>All time</small>

                </div>

            </div>


            <!-- TRAYS ORDERED -->

            <div class="summary-card">

                <div class="summary-icon trays">

                    <i class="fa-solid fa-cube"></i>

                </div>


                <div class="summary-content">

                    <span>Trays Ordered</span>

                    <h2>
                        <?php echo $total_trays; ?>
                    </h2>

                    <small>All time</small>

                </div>

            </div>


        </div>


        <!-- =====================================================
             MAIN DASHBOARD CONTENT
        ===================================================== -->

        <div class="dashboard-grid">


            <!-- =====================================================
                 AVAILABLE EGG STOCKS
            ===================================================== -->

            <div class="dashboard-card">


                <div class="card-header">

                    <div class="card-title">

                        <div class="card-title-icon">

                            <i class="fa-solid fa-egg"></i>

                        </div>


                        <div class="card-title-text">

                            <h2>
                                Available Egg Stocks
                            </h2>

                            <p>
                                Real-time availability for all egg sizes.
                            </p>

                        </div>

                    </div>

                </div>


                <!-- STOCK TABLE -->

                <div class="stock-table-container">


                    <?php if (count($egg_stocks) > 0) { ?>


                        <table class="stock-table">


                            <thead>

                                <tr>

                                    <th>
                                        Egg Size
                                    </th>

                                    <th>
                                        Available Stock
                                    </th>

                                    <th>
                                        Unit
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach ($egg_stocks as $stock) { ?>


                                    <?php

                                    $available_stock =
                                        isset($stock['available_stock'])
                                        ? $stock['available_stock']
                                        : 0;

                                    ?>


                                    <tr>

                                        <td>

                                            <strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    $stock['egg_size']
                                                );
                                                ?>
                                            </strong>

                                        </td>


                                        <td>

                                            <span class="stock-number">

                                                <?php
                                                echo $available_stock;
                                                ?>

                                            </span>

                                        </td>


                                        <td>
                                            trays
                                        </td>


                                        <td>

                                            <?php if ($available_stock > 0) { ?>

                                                <span class="stock-status">

                                                    <span
                                                        class="status-dot"
                                                    ></span>

                                                    Available

                                                </span>

                                            <?php } else { ?>

                                                <span
                                                    style="
                                                        color:#c65b4e;
                                                        font-size:11px;
                                                        font-weight:500;
                                                    "
                                                >

                                                    <i
                                                        class="
                                                        fa-solid
                                                        fa-circle
                                                        "
                                                        style="
                                                            font-size:7px;
                                                        "
                                                    ></i>

                                                    Out of Stock

                                                </span>

                                            <?php } ?>

                                        </td>

                                    </tr>


                                <?php } ?>


                            </tbody>


                        </table>


                    <?php } else { ?>


                        <div class="empty-state">

                            <i
                                class="
                                fa-solid
                                fa-box-open
                                "
                            ></i>

                            <p>
                                No egg stocks are currently available.
                            </p>

                        </div>


                    <?php } ?>


                </div>


                <!-- STOCK FOOTER -->

                <div class="stock-footer">


                    <div class="stock-info">

                        <i
                            class="
                            fa-solid
                            fa-circle-info
                            "
                        ></i>

                        <span>
                            Stocks are updated in real-time.
                            Reserve now to secure your preferred stock.
                        </span>

                    </div>


                    <a
                        href="customer_reservation.php"
                        class="reservation-button"
                    >

                        <i
                            class="
                            fa-regular
                            fa-calendar-plus
                            "
                        ></i>

                        Make a Reservation

                    </a>


                </div>


            </div>


            <!-- =====================================================
                 RECENT ACTIVITY
            ===================================================== -->

            <div class="dashboard-card">


                <div class="card-header">


                    <div class="card-title">


                        <div class="card-title-icon">

                            <i
                                class="
                                fa-solid
                                fa-clock-rotate-left
                                "
                            ></i>

                        </div>


                        <div class="card-title-text">

                            <h2>
                                Recent Activity
                            </h2>

                        </div>


                    </div>


                    <a
                        href="customer_history.php"
                        class="view-all-btn"
                    >

                        View All

                    </a>


                </div>


                <div class="activity-list">


                    <?php if (count($recent_activities) > 0) { ?>


                        <?php
                        foreach (
                            $recent_activities
                            as $activity
                        ) {
                        ?>


                            <?php

                            $icon_data =
                                getActivityIcon(
                                    $activity['status']
                                );

                            ?>


                            <div class="activity-item">


                                <!-- ACTIVITY ICON -->

                                <div
                                    class="
                                    activity-icon
                                    <?php
                                    echo $icon_data['class'];
                                    ?>
                                    "
                                >

                                    <i
                                        class="
                                        fa-solid
                                        <?php
                                        echo $icon_data['icon'];
                                        ?>
                                        "
                                    ></i>

                                </div>


                                <!-- ACTIVITY CONTENT -->

                                <div class="activity-content">


                                    <h4>

                                        Reservation
                                        <?php
                                        echo htmlspecialchars(
                                            $activity[
                                                'reservation_code'
                                            ]
                                        );
                                        ?>

                                        <?php
                                        echo htmlspecialchars(
                                            strtolower(
                                                $activity['status']
                                            )
                                        );
                                        ?>

                                    </h4>


                                    <p>

                                        <?php

                                        if (
                                            !empty(
                                                $activity[
                                                    'reserved_at'
                                                ]
                                            )
                                        ) {

                                            echo date(
                                                "M d, Y",
                                                strtotime(
                                                    $activity[
                                                        'reserved_at'
                                                    ]
                                                )
                                            );

                                        }

                                        ?>

                                        &nbsp;•&nbsp;

                                        <?php
                                        echo $activity['quantity'];
                                        ?>

                                        trays

                                        (
                                        <?php
                                        echo htmlspecialchars(
                                            $activity['egg_size']
                                        );
                                        ?>
                                        )

                                    </p>


                                </div>


                                <!-- TIME -->

                                <div class="activity-time">


                                    <?php

                                    if (
                                        !empty(
                                            $activity[
                                                'reserved_at'
                                            ]
                                        )
                                    ) {

                                        echo date(
                                            "g:i A",
                                            strtotime(
                                                $activity[
                                                    'reserved_at'
                                                ]
                                            )
                                        );

                                    }

                                    ?>


                                </div>


                            </div>


                        <?php } ?>


                    <?php } else { ?>


                        <div class="empty-state">

                            <i
                                class="
                                fa-regular
                                fa-calendar
                                "
                            ></i>

                            <p>
                                No recent reservation activity.
                            </p>

                        </div>


                    <?php } ?>


                </div>


            </div>


        </div>


        <!-- =====================================================
             SUPPORT CARD
        ===================================================== -->

        <div class="support-card">


            <div class="support-icon">

                <i
                    class="
                    fa-solid
                    fa-house
                    "
                ></i>

            </div>


            <div class="support-content">

                <h3>
                    Thank you for supporting local farms!
                </h3>

                <p>
                    We appreciate your trust and support.
                </p>

            </div>


         <!-- FARM IMAGE DECORATION -->

<div class="farm-decoration">

    <img
        src="/EggFarm/dashboard_footer.png"
        alt="Egg Farm Landscape"
    >

</div>

        </div>


    </div>


</div>


</body>

</html>