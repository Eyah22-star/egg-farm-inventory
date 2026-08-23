<?php
require 'db.php';

// Start session to safely manage state across pages if needed
if (!isset($_SESSION)) {
    session_start();
}

// Security Enforcement: Kick back to logging panel if user identifier isn't tracked
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch session parameters synchronized within login_process.php
$user_id = $_SESSION['user_id'];
$customer_name = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Guest';
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Customer';

// Mark notifications as read when notification bell is opened
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_notifications'])) {

    $mark_stmt = $conn->prepare(
        "UPDATE notifications 
         SET is_read = 1 
         WHERE user_id = ? AND is_read = 0"
    );

    $mark_stmt->bind_param("i", $user_id);
    $mark_stmt->execute();
    $mark_stmt->close();

    exit();
}
// Dynamic greeting based on current Philippines time
date_default_timezone_set('Asia/Manila');

$current_hour = (int)date('H');

if ($current_hour < 12) {
    $greeting = 'Good morning';
} elseif ($current_hour < 17) {
    $greeting = 'Good afternoon';
} else {
    $greeting = 'Good evening';
}
// --- DATABASE METRICS AGGREGATION & GROUPING LOGIC (SYNCHRONIZED WITH VIEW.PHP) ---

// Pull all account-specific reservations to replicate grouping architecture accurately
$all_reservations = array();
$query_stmt = $conn->prepare("SELECT * FROM reservations WHERE user_id = ? ORDER BY id DESC");
$query_stmt->bind_param("i", $user_id);
if ($query_stmt->execute()) {
    $res_query = $query_stmt->get_result();
    while ($row = $res_query->fetch_assoc()) {
        $all_reservations[] = $row;
    }
}
$query_stmt->close();

// Process grouping structure matching view.php parameters exactly
$grouped_reservations = array();
$total_trays = 0;
$total_spent = 0;

foreach ($all_reservations as $row) {
    $timestamp_key = isset($row['created_at']) ? $row['created_at'] : $row['reservation_date'];
    $group_key = $row['customer_name'] . '_' . $row['contact_number'] . '_' . $timestamp_key . '_' . $row['delivery_method'];
    
    if (!isset($grouped_reservations[$group_key])) {
       $grouped_reservations[$group_key] = array(
    'id' => $row['id'],
    'egg_type' => $row['egg_type'],
    'reservation_date' => $row['reservation_date'],
    'delivery_method' => $row['delivery_method'],
    'total_price' => 0,
    'items' => array()
);
    }

  $grouped_reservations[$group_key]['items'][] = array(
    'egg_type' => $row['egg_type'],
    'quantity' => (int)$row['quantity'],
    'total_price' => (float)$row['total_price']
);
    
    $grouped_reservations[$group_key]['total_price'] += (float)$row['total_price'];
    $total_trays += (int)$row['quantity'];
    $total_spent += (float)$row['total_price'];
}

// Complete assignments reflecting grouped statistics
$total_reservations = count($grouped_reservations);
$recent_activities = array_slice($grouped_reservations, 0, 5); // Limit dashboard recent items list to top 5 bundles

// Fetch all persistent historical user notifications to render inside dropdown
$notifications = array();
$notif_stmt = $conn->prepare("SELECT id, title, description, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 15");
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$result = $notif_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$notif_stmt->close();

$unread_count = 0;
foreach ($notifications as $n) {
    if (!$n['is_read']) $unread_count++;
}

// Default avatar only
$avatar_src = "https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VDVC - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 font-sans text-gray-700 antialiased min-h-screen">

<?php include('customer_panel.php'); ?>

<div class="main-content">

          <!-- ========================================= -->
    <!-- ONE LARGE OUTER DASHBOARD CARD -->
    <!-- ========================================= -->
    <div class="max-w-7xl w-full mx-auto my-5
                bg-white rounded-2xl
                border border-gray-200
                shadow-sm overflow-hidden">

        <!-- ========================================= -->
        <!-- DASHBOARD HEADER -->
        <!-- ========================================= -->
        <header class="px-8 py-4 flex justify-between items-center relative
                       border-b border-gray-200">
    <div>
       <h1 class="text-lg font-bold text-slate-800">
    <?php echo $greeting; ?>,
    <?php echo htmlspecialchars($customer_name); ?>! 👋
</h1>
        <p class="text-xs text-gray-400 mt-1">
            Here's what's happening with your reservations today.
        </p>
    </div>

    <div class="flex items-center gap-4">

        <div class="text-right hidden sm:block">
            <div class="font-semibold text-sm text-gray-800">
                <?php echo htmlspecialchars($customer_name); ?>
            </div>
            <div class="text-xs text-gray-400">
                Customer
            </div>
        </div>

        <div class="w-10 h-10 rounded-full overflow-hidden border border-gray-200">
            <img src="<?php echo $avatar_src; ?>"
                 alt="Profile"
                 class="w-full h-full object-cover">
        </div>

        <div class="relative">
            <div onclick="toggleNotifications(event)"
                 class="cursor-pointer relative p-1 hover:bg-gray-100 rounded-full transition">

                <i class="fa-regular fa-bell text-gray-500 text-xl"></i>

               <?php if ($unread_count > 0): ?>
    <span id="bell_badge"
          class="absolute -top-1 -right-1 min-w-[17px] h-[17px]
                 bg-red-500 text-white text-[9px] font-bold
                 rounded-full flex items-center justify-center
                 border-2 border-white">
        <?php echo $unread_count; ?>
    </span>
<?php endif; ?>

            </div>

            <!-- KEEP YOUR EXISTING NOTIFICATION DROPDOWN HERE -->
            <div id="notification_dropdown"
                 class="hidden absolute right-0 mt-3 w-80 bg-white border border-gray-200 rounded-xl shadow-xl z-50 overflow-hidden">

                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <span class="font-bold text-sm text-slate-800">
                        Notifications
                    </span>

                    <span id="unread_count"
                          class="text-xs <?php echo $unread_count > 0 ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-400'; ?> px-2 py-0.5 rounded-full font-medium">
                        <?php echo $unread_count > 0 ? $unread_count . ' New' : '0 New'; ?>
                    </span>
                </div>

                <div id="notification_list"
                     class="divide-y divide-gray-100 max-h-60 overflow-y-auto">

                    <?php if (!empty($notifications)): ?>

                        <?php foreach ($notifications as $notif):

                            $bg_class = !$notif['is_read'] ? 'bg-blue-50/30' : '';
                            $icon_bg = 'bg-blue-100 text-blue-600';
                            $icon_fa = 'fa-solid fa-circle-info';

                            if ($notif['type'] === 'success') {
                                $icon_bg = 'bg-green-100 text-green-600';
                                $icon_fa = 'fa-solid fa-circle-check';
                            } elseif ($notif['type'] === 'alert') {
                                $icon_bg = 'bg-red-100 text-red-600';
                                $icon_fa = 'fa-solid fa-triangle-exclamation';
                            }
                        ?>

                            <div class="p-4 hover:bg-slate-50 transition flex space-x-3 <?php echo $bg_class; ?>">

                                <div class="<?php echo $icon_bg; ?> rounded-full w-8 h-8 flex items-center justify-center flex-shrink-0">
                                    <i class="<?php echo $icon_fa; ?> text-xs"></i>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-700 font-semibold mb-0.5 truncate">
                                        <?php echo htmlspecialchars($notif['title']); ?>
                                    </p>

                                    <p class="text-[11px] text-gray-500 break-words mb-1">
                                        <?php echo htmlspecialchars($notif['description']); ?>
                                    </p>

                                    <p class="text-[9px] text-gray-400">
                                        <?php echo date('M d, g:i a', strtotime($notif['created_at'])); ?>
                                    </p>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="p-8 text-center text-gray-400 text-xs">
                            <i class="fa-regular fa-bell-slash text-2xl mb-2 block text-gray-300"></i>
                            No new notifications
                        </div>

                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>
</header>

         <main class="p-5 space-y-5">

    <!-- ========================================= -->
    <!-- HERO / RESERVATION BANNER -->
    <!-- ========================================= -->

    <div class="bg-gradient-to-r from-sky-400 to-blue-600
                rounded-xl px-6 py-5 text-white shadow-md
                flex items-center justify-between gap-5">

        <div class="flex items-center gap-4">

            <div class="w-16 h-16 rounded-full bg-white/20
                        flex items-center justify-center text-4xl flex-shrink-0">
                🥚
            </div>

            <div>
                <h2 class="text-xl font-bold">
                    Reserve fresh eggs with ease!
                </h2>

                <p class="text-sky-100 text-xs mt-1 max-w-lg">
                    Check available stocks, make a reservation,
                    and track your orders in real-time.
                </p>
            </div>

        </div>

        <a href="customer_reservation.php"
           class="bg-white text-blue-600 font-semibold
                  px-5 py-2.5 rounded-lg shadow-sm
                  hover:bg-sky-50 transition text-xs
                  flex items-center gap-2 flex-shrink-0">

            <i class="fa-solid fa-circle-plus"></i>
            New Reservation

        </a>

    </div>


    <!-- ========================================= -->
    <!-- 4 STATISTICS CARDS -->
    <!-- ========================================= -->

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">

        <!-- ACTIVE RESERVATIONS -->
        <div class="bg-white rounded-xl border border-gray-100
                    shadow-sm p-4 flex items-center justify-between">

            <div>
                <p class="text-[11px] text-gray-400 font-medium">
                    Active Reservations
                </p>

                <p class="text-xl font-bold text-slate-800 mt-1">
                    <?php echo number_format($total_reservations); ?>
                </p>

             <?php if (!empty($recent_activities) && is_array($recent_activities)): ?>

    <?php

    // Get the first available reservation safely
    $active_activity = reset($recent_activities);

    // Make sure the reservation is actually an array
    if (is_array($active_activity)) {

        $active_total_trays = 0;

        // Make sure items exists and is an array
        if (isset($active_activity['items']) &&
            is_array($active_activity['items'])) {

            foreach ($active_activity['items'] as $active_item) {

                if (isset($active_item['quantity'])) {
                    $active_total_trays += (int)$active_item['quantity'];
                }

            }
        }

        $active_reservation_id =
            'RES-' . date('Y') . '-' .
            str_pad(1, 3, '0', STR_PAD_LEFT);

        $active_date = isset($active_activity['reservation_date'])
            ? $active_activity['reservation_date']
            : '';

        $active_delivery = isset($active_activity['delivery_method'])
            ? $active_activity['delivery_method']
            : '';

        $active_total_price = isset($active_activity['total_price'])
            ? (float)$active_activity['total_price']
            : 0;

        $active_items = isset($active_activity['items']) &&
                        is_array($active_activity['items'])
            ? $active_activity['items']
            : array();

    ?>

        <button type="button"
                onclick="openReservationModal(this)"
                data-res-id="<?php echo htmlspecialchars($active_reservation_id, ENT_QUOTES, 'UTF-8'); ?>"

                data-date="<?php echo htmlspecialchars(
                    $active_date,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"

                data-delivery="<?php echo htmlspecialchars(
                    $active_delivery,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"

                data-total-trays="<?php echo $active_total_trays; ?>"

                data-total-price="<?php echo number_format(
                    $active_total_price,
                    2,
                    '.',
                    ''
                ); ?>"

                data-items="<?php echo htmlspecialchars(
                    json_encode($active_items),
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"

                class="text-[10px] text-blue-500 font-semibold
                       mt-2 inline-block
                       hover:text-blue-700
                       transition">

            View details →

        </button>

    <?php } else { ?>

        <span class="text-[10px] text-gray-400 mt-2 inline-block">
            No active reservation
        </span>

    <?php } ?>

<?php else: ?>

    <span class="text-[10px] text-gray-400 mt-2 inline-block">
        No active reservation
    </span>

<?php endif; ?>
            </div>

            <div class="w-10 h-10 rounded-full bg-blue-50
                        text-blue-500 flex items-center justify-center">
                <i class="fa-regular fa-clipboard text-sm"></i>
            </div>

        </div>


        <!-- NEXT DELIVERY - PLACEHOLDER -->
        <div class="bg-white rounded-xl border border-gray-100
                    shadow-sm p-4 flex items-center justify-between">

            <div>
                <p class="text-[11px] text-gray-400 font-medium">
                    Next Delivery
                </p>

                <p class="text-xl font-bold text-slate-800 mt-1">
                    Tomorrow
                </p>

                <p class="text-[10px] text-gray-400 mt-1">
                    Aug 13, 2026
                </p>
            </div>

            <div class="w-10 h-10 rounded-full bg-green-50
                        text-green-500 flex items-center justify-center">
                <i class="fa-regular fa-calendar text-sm"></i>
            </div>

        </div>


        <!-- TRAYS ORDERED -->
        <div class="bg-white rounded-xl border border-gray-100
                    shadow-sm p-4 flex items-center justify-between">

            <div>
                <p class="text-[11px] text-gray-400 font-medium">
                    Trays Ordered
                </p>

                <p class="text-xl font-bold text-slate-800 mt-1">
                    <?php echo number_format($total_trays); ?>
                </p>

                <p class="text-[10px] text-gray-400 mt-1">
                    Total trays
                </p>
            </div>

            <div class="w-10 h-10 rounded-full bg-amber-50
                        text-amber-500 flex items-center justify-center">
                <i class="fa-solid fa-boxes-stacked text-sm"></i>
            </div>

        </div>


        <!-- TOTAL SPENT -->
        <div class="bg-white rounded-xl border border-gray-100
                    shadow-sm p-4 flex items-center justify-between">

            <div>
                <p class="text-[11px] text-gray-400 font-medium">
                    Total Spent
                </p>

                <p class="text-xl font-bold text-slate-800 mt-1">
                    ₱<?php echo number_format($total_spent, 2); ?>
                </p>

                <p class="text-[10px] text-gray-400 mt-1">
                    Lifetime total
                </p>
            </div>

            <div class="w-10 h-10 rounded-full bg-purple-50
                        text-purple-500 flex items-center justify-center">
                <i class="fa-solid fa-wallet text-sm"></i>
            </div>

        </div>

    </div>


    <!-- ========================================= -->
    <!-- MIDDLE SECTION -->
    <!-- ========================================= -->

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-3">

        <!-- ===================================== -->
        <!-- AVAILABLE EGG STOCKS - PLACEHOLDER -->
        <!-- ===================================== -->

        <div class="bg-white rounded-xl border border-gray-100
                    shadow-sm p-4">

            <div class="flex items-center justify-between mb-3">

                <h3 class="text-sm font-bold text-slate-800">
                    🥚 Available Egg Stocks
                </h3>

                <a href="reservation.php"
                   class="text-[10px] text-blue-600 font-semibold">
                    View All
                </a>

            </div>


            <div class="grid grid-cols-4 gap-2">

                <!-- EXTRA SMALL -->
                <div class="border border-gray-100 rounded-lg p-3 text-center">
                    <p class="text-[9px] text-gray-500">Extra Small</p>
                    <p class="text-sm font-bold text-slate-800 mt-1">
                        120 trays
                    </p>
                    <span class="inline-block mt-2 px-2 py-1
                                 rounded-full bg-green-50 text-green-600
                                 text-[8px] font-semibold">
                        Available
                    </span>
                </div>

                <!-- SMALL -->
                <div class="border border-gray-100 rounded-lg p-3 text-center">
                    <p class="text-[9px] text-gray-500">Small</p>
                    <p class="text-sm font-bold text-slate-800 mt-1">
                        80 trays
                    </p>
                    <span class="inline-block mt-2 px-2 py-1
                                 rounded-full bg-green-50 text-green-600
                                 text-[8px] font-semibold">
                        Available
                    </span>
                </div>

                <!-- MEDIUM -->
                <div class="border border-gray-100 rounded-lg p-3 text-center">
                    <p class="text-[9px] text-gray-500">Medium</p>
                    <p class="text-sm font-bold text-slate-800 mt-1">
                        150 trays
                    </p>
                    <span class="inline-block mt-2 px-2 py-1
                                 rounded-full bg-green-50 text-green-600
                                 text-[8px] font-semibold">
                        Available
                    </span>
                </div>

                <!-- LARGE -->
                <div class="border border-gray-100 rounded-lg p-3 text-center">
                    <p class="text-[9px] text-gray-500">Large</p>
                    <p class="text-sm font-bold text-slate-800 mt-1">
                        25 trays
                    </p>
                    <span class="inline-block mt-2 px-2 py-1
                                 rounded-full bg-amber-50 text-amber-600
                                 text-[8px] font-semibold">
                        Limited
                    </span>
                </div>


                <!-- XL -->
                <div class="border border-gray-100 rounded-lg p-3 text-center">
                    <p class="text-[9px] text-gray-500">XL</p>
                    <p class="text-sm font-bold text-slate-800 mt-1">
                        18 trays
                    </p>
                    <span class="inline-block mt-2 px-2 py-1
                                 rounded-full bg-amber-50 text-amber-600
                                 text-[8px] font-semibold">
                        Limited
                    </span>
                </div>

                <!-- JUMBO -->
                <div class="border border-gray-100 rounded-lg p-3 text-center">
                    <p class="text-[9px] text-gray-500">Jumbo</p>
                    <p class="text-sm font-bold text-slate-800 mt-1">
                        0 trays
                    </p>
                    <span class="inline-block mt-2 px-2 py-1
                                 rounded-full bg-red-50 text-red-500
                                 text-[8px] font-semibold">
                        Out of Stock
                    </span>
                </div>

                <!-- SUPER JUMBO -->
                <div class="border border-gray-100 rounded-lg p-3 text-center">
                    <p class="text-[9px] text-gray-500">Super Jumbo</p>
                    <p class="text-sm font-bold text-slate-800 mt-1">
                        0 trays
                    </p>
                    <span class="inline-block mt-2 px-2 py-1
                                 rounded-full bg-red-50 text-red-500
                                 text-[8px] font-semibold">
                        Out of Stock
                    </span>
                </div>

                <!-- DOUBLE YOLK -->
                <div class="border border-gray-100 rounded-lg p-3 text-center">
                    <p class="text-[9px] text-gray-500">Double Yolk</p>
                    <p class="text-sm font-bold text-slate-800 mt-1">
                        5 trays
                    </p>
                    <span class="inline-block mt-2 px-2 py-1
                                 rounded-full bg-amber-50 text-amber-600
                                 text-[8px] font-semibold">
                        Limited
                    </span>
                </div>

            </div>

        </div>


        <!-- ===================================== -->
        <!-- TRACK CURRENT RESERVATION -->
        <!-- PLACEHOLDER FOR NOW -->
        <!-- ===================================== -->

        <div class="bg-white rounded-xl border border-gray-100
                    shadow-sm p-4">

            <div class="flex items-center justify-between mb-4">

                <h3 class="text-sm font-bold text-slate-800">
                    📦 Track Your Current Reservation
                </h3>

                <a href="view.php"
                   class="text-[10px] text-blue-600 font-semibold">
                    View Details
                </a>

            </div>


            <!-- Reservation information -->

            <div class="bg-slate-50 rounded-lg px-4 py-3
                        flex items-center justify-between">

                <div>
                    <span class="text-xs font-bold text-blue-700">
                        RES-2026-001
                    </span>

                    <span class="text-[10px] text-gray-500 ml-3">
                        Extra Small • 50 Trays
                    </span>
                </div>

                <span class="text-sm font-bold text-blue-600">
                    ₱7,000.00
                </span>

            </div>


            <!-- Tracking line -->

            <div class="flex items-start justify-between mt-5">

                <div class="text-center flex-1">

                    <div class="w-6 h-6 mx-auto rounded-full
                                bg-blue-500 text-white
                                flex items-center justify-center text-[10px]">
                        <i class="fa-solid fa-check"></i>
                    </div>

                    <p class="text-[9px] font-semibold text-gray-700 mt-2">
                        Reserved
                    </p>

                    <p class="text-[8px] text-gray-400">
                        Aug 11
                    </p>

                </div>


                <div class="h-[2px] bg-blue-500 flex-1 mt-3"></div>


                <div class="text-center flex-1">

                    <div class="w-6 h-6 mx-auto rounded-full
                                bg-blue-500 text-white
                                flex items-center justify-center text-[10px]">
                        <i class="fa-solid fa-check"></i>
                    </div>

                    <p class="text-[9px] font-semibold text-gray-700 mt-2">
                        Confirmed
                    </p>

                </div>


                <div class="h-[2px] bg-blue-500 flex-1 mt-3"></div>


                <div class="text-center flex-1">

                    <div class="w-6 h-6 mx-auto rounded-full
                                bg-blue-500 text-white
                                flex items-center justify-center text-[10px]">
                        <i class="fa-solid fa-truck"></i>
                    </div>

                    <p class="text-[9px] font-semibold text-gray-700 mt-2">
                        Out for Delivery
                    </p>

                </div>


                <div class="h-[2px] bg-gray-200 flex-1 mt-3"></div>


                <div class="text-center flex-1">

                    <div class="w-6 h-6 mx-auto rounded-full
                                bg-gray-100 text-gray-400
                                flex items-center justify-center text-[10px]">
                        <i class="fa-solid fa-house"></i>
                    </div>

                    <p class="text-[9px] font-semibold text-gray-400 mt-2">
                        Delivered
                    </p>

                </div>

            </div>


            <div class="bg-blue-50 rounded-lg p-3 mt-5 text-center">
                <p class="text-[9px] text-blue-600">
                    <i class="fa-regular fa-calendar mr-1"></i>
                    Expected Delivery Date: August 13, 2026
                </p>
            </div>

        </div>

    </div>


    <!-- ========================================= -->
    <!-- BOTTOM SECTION -->
    <!-- ========================================= -->

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-3">


        <!-- ===================================== -->
        <!-- RECENT RESERVATIONS -->
        <!-- WORKING -->
        <!-- ===================================== -->

        <div class="xl:col-span-2 bg-white rounded-xl
                    border border-gray-100 shadow-sm p-4">

            <div class="flex items-center justify-between mb-3">

                <h3 class="text-sm font-bold text-slate-800">
                    Recent Reservations
                </h3>

                <a href="view.php"
                   class="text-[10px] text-blue-600 font-semibold">
                    View All
                </a>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full text-left">

                    <thead>
                        <tr class="border-b border-gray-100">

                            <th class="py-2 px-2 text-[9px]
                                       text-gray-400 font-semibold">
                                Reservation
                            </th>

                            <th class="py-2 px-2 text-[9px]
                                       text-gray-400 font-semibold">
                                Item
                            </th>

                            <th class="py-2 px-2 text-[9px]
                                       text-gray-400 font-semibold">
                                Date
                            </th>

                            <th class="py-2 px-2 text-[9px]
                                       text-gray-400 font-semibold">
                                Status
                            </th>

                            <th class="py-2 px-2 text-[9px]
                                       text-gray-400 font-semibold">
                                Total
                            </th>

                            <th class="py-2 px-2"></th>

                        </tr>
                    </thead>


                    <tbody class="divide-y divide-gray-100">

                        <?php if (!empty($recent_activities)): ?>

                            <?php
                            $reservation_counter = 1;
                            ?>

                            <?php foreach ($recent_activities as $activity): ?>

                                <?php

                                $total_item_qty = 0;

                                foreach ($activity['items'] as $item) {
                                    $total_item_qty += (int)$item['quantity'];
                                }

                              $first_item = '';

if (isset($activity['items']) &&
    is_array($activity['items']) &&
    count($activity['items']) > 0 &&
    isset($activity['items'][0]['egg_type'])) {

    $first_item = $activity['items'][0]['egg_type'];

}

                                ?>

                                <tr class="hover:bg-slate-50 transition">

                                    <!-- RESERVATION ID -->

                                    <td class="py-3 px-2">

                                        <span class="text-[10px]
                                                     font-bold text-slate-700">

                                            RES-<?php echo date('Y'); ?>-
                                            <?php echo str_pad(
                                                $reservation_counter,
                                                3,
                                                '0',
                                                STR_PAD_LEFT
                                            ); ?>

                                        </span>

                                    </td>


                                    <!-- ITEM -->

                                    <td class="py-3 px-2">

                                        <div class="flex items-center gap-2">

                                            <div class="w-6 h-6 rounded-full
                                                        bg-orange-50
                                                        flex items-center
                                                        justify-center">

                                                🥚

                                            </div>

                                            <div>

                                                <p class="text-[10px]
                                                          font-semibold
                                                          text-slate-700">

                                                    <?php
                                                    echo htmlspecialchars($first_item);
                                                    ?>

                                                </p>

                                                <p class="text-[8px] text-gray-400">
                                                    (<?php echo $total_item_qty; ?> trays)
                                                </p>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- DATE -->

                                    <td class="py-3 px-2 text-[9px]
                                               text-gray-500">

                                        <?php
                                        echo htmlspecialchars(
                                            $activity['reservation_date']
                                        );
                                        ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td class="py-3 px-2">

                                        <span class="inline-block
                                                     px-2 py-1 rounded-full
                                                     bg-green-50 text-green-600
                                                     text-[8px] font-semibold">

                                            Confirmed

                                        </span>

                                    </td>


                                    <!-- TOTAL -->

                                    <td class="py-3 px-2">

                                        <span class="text-[10px]
                                                     font-bold text-slate-800">

                                            ₱<?php echo number_format(
                                                (float)$activity['total_price'],
                                                2
                                            ); ?>

                                        </span>

                                    </td>


                                    <!-- VIEW -->

                                    <td class="py-3 px-2 text-right">

                                     <button type="button"
        onclick="openReservationModal(this)"
        data-res-id="RES-<?php echo date('Y'); ?>-<?php echo str_pad($reservation_counter, 3, '0', STR_PAD_LEFT); ?>"
        data-date="<?php echo htmlspecialchars($activity['reservation_date'], ENT_QUOTES, 'UTF-8'); ?>"
        data-delivery="<?php echo htmlspecialchars($activity['delivery_method'], ENT_QUOTES, 'UTF-8'); ?>"
        data-total-trays="<?php echo $total_item_qty; ?>"
        data-total-price="<?php echo number_format((float)$activity['total_price'], 2, '.', ''); ?>"
        data-items="<?php echo htmlspecialchars(json_encode($activity['items']), ENT_QUOTES, 'UTF-8'); ?>"
        class="px-2 py-1 rounded-md
               border border-gray-200
               text-[8px] font-semibold
               text-blue-600
               hover:bg-blue-50
               transition">

    View

</button>

                                    </td>

                                </tr>

                                <?php
                                $reservation_counter++;
                                ?>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="6"
                                    class="py-8 text-center text-gray-400">

                                    <i class="fa-regular fa-folder-open
                                              text-xl mb-2 block"></i>

                                    <span class="text-xs">
                                        No reservation history found.
                                    </span>

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

<!-- ===================================== -->
<!-- FARM ANNOUNCEMENTS -->
<!-- ===================================== -->

<div class="bg-white rounded-xl border border-gray-100
            shadow-sm p-4">

    <div class="flex items-center justify-between mb-4">

        <div class="flex items-center gap-2">

            <div class="w-8 h-8 rounded-lg bg-amber-50
                        text-amber-500 flex items-center justify-center">

                <i class="fa-solid fa-bullhorn text-xs"></i>

            </div>

            <h3 class="text-sm font-bold text-slate-800">
                Farm Announcements
            </h3>

        </div>

        <span class="text-[9px] text-gray-400">
            Latest Updates
        </span>

    </div>


    <!-- Announcement 1 -->

    <div class="border border-gray-100 rounded-lg p-3 mb-3
                hover:bg-slate-50 transition">

        <div class="flex items-start gap-3">

            <div class="w-8 h-8 rounded-full bg-red-50
                        text-red-500 flex items-center justify-center
                        flex-shrink-0">

                <i class="fa-solid fa-triangle-exclamation text-xs"></i>

            </div>

            <div class="flex-1">

                <div class="flex justify-between items-start gap-2">

                    <p class="text-[10px] font-bold text-slate-800">
                        Large Eggs Limited
                    </p>

                    <span class="text-[8px] text-gray-400 whitespace-nowrap">
                        Aug 12
                    </span>

                </div>

                <p class="text-[9px] text-gray-500 mt-1 leading-relaxed">
                    Large egg stocks are currently limited.
                    Customers are encouraged to reserve early.
                </p>

            </div>

        </div>

    </div>


    <!-- Announcement 2 -->

    <div class="border border-gray-100 rounded-lg p-3 mb-3
                hover:bg-slate-50 transition">

        <div class="flex items-start gap-3">

            <div class="w-8 h-8 rounded-full bg-blue-50
                        text-blue-500 flex items-center justify-center
                        flex-shrink-0">

                <i class="fa-solid fa-truck text-xs"></i>

            </div>

            <div class="flex-1">

                <div class="flex justify-between items-start gap-2">

                    <p class="text-[10px] font-bold text-slate-800">
                        Delivery Schedule Update
                    </p>

                    <span class="text-[8px] text-gray-400 whitespace-nowrap">
                        Aug 11
                    </span>

                </div>

                <p class="text-[9px] text-gray-500 mt-1 leading-relaxed">
                    Please make sure your delivery details are
                    complete before your scheduled delivery date.
                </p>

            </div>

        </div>

    </div>


    <!-- Announcement 3 -->

    <div class="border border-gray-100 rounded-lg p-3
                hover:bg-slate-50 transition">

        <div class="flex items-start gap-3">

            <div class="w-8 h-8 rounded-full bg-green-50
                        text-green-500 flex items-center justify-center
                        flex-shrink-0">

                <i class="fa-solid fa-circle-check text-xs"></i>

            </div>

            <div class="flex-1">

                <div class="flex justify-between items-start gap-2">

                    <p class="text-[10px] font-bold text-slate-800">
                        Fresh Egg Availability
                    </p>

                    <span class="text-[8px] text-gray-400 whitespace-nowrap">
                        Aug 10
                    </span>

                </div>

                <p class="text-[9px] text-gray-500 mt-1 leading-relaxed">
                    Fresh egg stocks are regularly updated.
                    Check available sizes before making a reservation.
                </p>

            </div>

        </div>

    </div>


    <!-- View All -->

    <div class="text-center mt-4">

        <a href="#"
           class="text-[10px] text-blue-600 font-semibold
                  hover:text-blue-700">

            View All Announcements →

        </a>

    </div>

</div>

    </div>

</main>
</div>
         <!-- ========================================= -->
<!-- RESERVATION DETAILS MODAL -->
<!-- ========================================= -->

<div id="reservation_modal"
     class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm
            flex items-center justify-center z-[90] p-4">

    <div class="bg-white rounded-2xl shadow-2xl
                border border-gray-100
                max-w-md w-full
                max-h-[90vh] overflow-y-auto">

        <!-- HEADER -->
        <div class="px-6 pt-6 pb-4 border-b border-gray-100">

            <div class="flex items-start justify-between">

                <div class="flex items-center gap-3">

                    <div class="w-11 h-11 rounded-full
                                bg-blue-50 text-blue-600
                                flex items-center justify-center">

                        <i class="fa-solid fa-receipt"></i>

                    </div>

                    <div>
                        <h2 class="text-base font-bold text-slate-800">
                            Reservation Details
                        </h2>

                        <p id="modal_reservation_id"
                           class="text-[10px] text-blue-500 font-semibold mt-0.5">
                            RES-2026-001
                        </p>
                    </div>

                </div>

                <button type="button"
                        onclick="closeReservationModal()"
                        class="w-8 h-8 rounded-full
                               bg-gray-100 text-gray-400
                               hover:bg-gray-200
                               hover:text-gray-600
                               transition">

                    <i class="fa-solid fa-xmark text-xs"></i>

                </button>

            </div>

        </div>


        <!-- BODY -->
        <div class="p-6 space-y-4">

            <!-- STATUS -->
            <div class="bg-green-50 border border-green-100
                        rounded-xl p-3
                        flex items-center justify-between">

                <div class="flex items-center gap-2">

                    <div class="w-8 h-8 rounded-full
                                bg-green-100 text-green-600
                                flex items-center justify-center">

                        <i class="fa-solid fa-check text-xs"></i>

                    </div>

                    <div>
                        <p class="text-[9px] text-gray-400">
                            Reservation Status
                        </p>

                        <p class="text-xs font-bold text-green-600">
                            Confirmed
                        </p>
                    </div>

                </div>

            </div>


            <!-- BASIC INFORMATION -->
            <div class="grid grid-cols-2 gap-3">

                <div class="bg-slate-50 rounded-lg p-3">

                    <p class="text-[9px] text-gray-400">
                        Reservation Date
                    </p>

                    <p id="modal_date"
                       class="text-xs font-semibold text-slate-700 mt-1">
                        —
                    </p>

                </div>


                <div class="bg-slate-50 rounded-lg p-3">

                    <p class="text-[9px] text-gray-400">
                        Delivery Method
                    </p>

                    <p id="modal_delivery"
                       class="text-xs font-semibold text-slate-700 mt-1">
                        —
                    </p>

                </div>

            </div>


            <!-- ITEMS -->
            <div>

                <div class="flex items-center justify-between mb-2">

                    <h3 class="text-xs font-bold text-slate-800">
                        🥚 Reserved Items
                    </h3>

                    <span id="modal_total_trays"
                          class="text-[9px] text-gray-400">
                        0 trays
                    </span>

                </div>


                <div id="modal_items"
                     class="border border-gray-100
                            rounded-xl overflow-hidden">

                    <!-- Items will be inserted here -->

                </div>

            </div>


            <!-- TOTAL -->
            <div class="bg-blue-50 border border-blue-100
                        rounded-xl p-4">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-[9px] text-blue-400">
                            Total Amount
                        </p>

                        <p id="modal_total_price"
                           class="text-xl font-bold text-blue-600 mt-1">
                            ₱0.00
                        </p>

                    </div>

                    <div class="w-10 h-10 rounded-full
                                bg-white text-blue-500
                                flex items-center justify-center">

                        <i class="fa-solid fa-peso-sign text-sm"></i>

                    </div>

                </div>

            </div>

        </div>


        <!-- FOOTER -->
        <div class="px-6 pb-6">

            <button type="button"
                    onclick="closeReservationModal()"
                    class="w-full bg-slate-800
                           hover:bg-slate-900
                           text-white
                           py-2.5 rounded-lg
                           text-xs font-semibold
                           transition">

                Close

            </button>

        </div>

    </div>

</div>   
        </div>
   

    <!-- Interactive Logout Confirmation Modal -->
    <div id="logout_modal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
        <div class="bg-white rounded-xl shadow-2xl border border-gray-100 max-w-sm w-full p-6 space-y-4 transform transition-all">
            <div class="flex items-center space-x-3 text-red-500">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center text-red-600">
                    <i class="fa-solid fa-right-from-bracket text-base"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800">Confirm Logout</h3>
            </div>
            <p class="text-sm text-gray-500">Are you sure you want to end your session? You will need to log back in to manage reservations.</p>
            <div class="flex justify-end space-x-3 pt-2">
                <button type="button" onclick="closeLogoutModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition">
                    Stay Logged In
                </button>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold text-center transition">
                    Yes, Logout
                </a>
            </div>
        </div>
    </div>

    <script> 
    function openLogoutModal() {
        document.getElementById('logout_modal').classList.remove('hidden');
    }

    function closeLogoutModal() {
        document.getElementById('logout_modal').classList.add('hidden');
    }

    window.addEventListener('click', function(event) {
        const logoutModal = document.getElementById('logout_modal');
        if (event.target === logoutModal) {
            closeLogoutModal();
        }
    });
    
    function toggleNotifications(event) {
    event.stopPropagation();

    const dropdown = document.getElementById('notification_dropdown');
    const badge = document.getElementById('bell_badge');
    const unreadCount = document.getElementById('unread_count');

    // Open / close notification dropdown
    dropdown.classList.toggle('hidden');

    // Only mark notifications as read when opening the dropdown
    if (!dropdown.classList.contains('hidden')) {

        // Immediately remove the red notification badge
        if (badge) {
            badge.remove();
        }

        // Change "X New" to "0 New"
        if (unreadCount) {
            unreadCount.innerText = "0 New";
            unreadCount.className =
                "text-xs bg-gray-100 text-gray-400 px-2 py-0.5 rounded-full font-medium";
        }

        // Save the read status into the database
        var xhr = new XMLHttpRequest();

        xhr.open("POST", "customer_dashboard.php", true);

        xhr.setRequestHeader(
            "Content-Type",
            "application/x-www-form-urlencoded"
        );

        xhr.send("mark_notifications=1");
    }
}






function openReservationModal(button) {

    var modal = document.getElementById('reservation_modal');

    var reservationId = button.getAttribute('data-res-id');
    var reservationDate = button.getAttribute('data-date');
    var deliveryMethod = button.getAttribute('data-delivery');
    var totalTrays = button.getAttribute('data-total-trays');
    var totalPrice = button.getAttribute('data-total-price');
    var itemsJson = button.getAttribute('data-items');

    document.getElementById('modal_reservation_id').innerText =
        reservationId;

    document.getElementById('modal_date').innerText =
        reservationDate;

    document.getElementById('modal_delivery').innerText =
        deliveryMethod;

    document.getElementById('modal_total_trays').innerText =
        totalTrays + ' trays';

    document.getElementById('modal_total_price').innerText =
        '₱' + parseFloat(totalPrice).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });


    /* BUILD ITEMS */

    var itemsContainer = document.getElementById('modal_items');

    itemsContainer.innerHTML = '';

    try {

        var items = JSON.parse(itemsJson);

        for (var i = 0; i < items.length; i++) {

            var item = items[i];

            var itemRow = document.createElement('div');

            itemRow.className =
                'px-4 py-3 flex items-center justify-between border-b border-gray-100 last:border-b-0';

            itemRow.innerHTML =
                '<div class="flex items-center gap-3">' +

                    '<div class="w-8 h-8 rounded-full bg-orange-50 ' +
                    'flex items-center justify-center">' +
                        '🥚' +
                    '</div>' +

                    '<div>' +

                        '<p class="text-xs font-semibold text-slate-700">' +
                            escapeHtml(item.egg_type) +
                        '</p>' +

                        '<p class="text-[9px] text-gray-400">' +
                            'Eggs' +
                        '</p>' +

                    '</div>' +

                '</div>' +

                '<div class="text-right">' +

                    '<p class="text-xs font-bold text-slate-700">' +
                        item.quantity + ' trays' +
                    '</p>' +

                    '<p class="text-[9px] text-gray-400">' +
                        '₱' +
                        parseFloat(item.total_price).toLocaleString('en-PH', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }) +
                    '</p>' +

                '</div>';

            itemsContainer.appendChild(itemRow);
        }

    } catch (error) {

        itemsContainer.innerHTML =
            '<div class="p-4 text-center text-xs text-red-400">' +
            'Unable to load reservation items.' +
            '</div>';

    }


    /* SHOW MODAL */

    modal.classList.remove('hidden');

    document.body.classList.add('overflow-hidden');
}


/* CLOSE MODAL */

function closeReservationModal() {

    var modal = document.getElementById('reservation_modal');

    modal.classList.add('hidden');

    document.body.classList.remove('overflow-hidden');
}


/* CLICK OUTSIDE MODAL */

window.addEventListener('click', function(event) {

    var modal = document.getElementById('reservation_modal');

    if (event.target === modal) {

        closeReservationModal();

    }

});


/* ESC KEY */

document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {

        closeReservationModal();

    }

});


/* BASIC HTML ESCAPE */

function escapeHtml(text) {

    var div = document.createElement('div');

    div.appendChild(document.createTextNode(text));

    return div.innerHTML;

}
    </script>
</body>
</html>