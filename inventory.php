<?php
ob_start();

include 'db.php';

// -------------------------------------------------------------------------
// 1. HANDLE CRUD FORM SUBMISSIONS (Egg & Supplies Action Handlers)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_date = date('Y-m-d H:i:s');
    $user = "Sarah L."; // Default active manager base sa mockup
    $status = "fan";    // Default status indicator badge

    // ===================== EGG INVENTORY CRUD =====================
if (isset($_POST['action_egg'])) {

    $movement = mysqli_real_escape_string($conn, $_POST['action_egg']);

    // Kunin ang batch_id na galing sa input field ng modal
    $batch_id = mysqli_real_escape_string($conn, $_POST['batch_id']);

    $harvest_date = $_POST['harvest_date'];

    $harvest_time = $_POST['harvest_time'];

    $egg_size = mysqli_real_escape_string($conn, $_POST['egg_size']);

    $quantity = intval($_POST['quantity']);

    $reason = mysqli_real_escape_string($conn, $_POST['reason']);

    // Kunin ang latest current stock ng napiling egg size
    $stock_query = mysqli_query($conn,
        "SELECT current_stock
         FROM egg_inventory
         WHERE egg_size='$egg_size'
         ORDER BY id DESC
         LIMIT 1");

    if(mysqli_num_rows($stock_query) > 0){

        $stock_row = mysqli_fetch_assoc($stock_query);

        $current_stock = $stock_row['current_stock'];

    }else{

        $current_stock = 0;

    }

    // Compute ng bagong stock
    if($movement == "Stock In"){

        $new_stock = $current_stock + $quantity;

    }elseif($movement == "Stock Out"){

        $new_stock = $current_stock - $quantity;

    }else{

        // Adjustment
        $new_stock = $quantity;

    }
$sql = "INSERT INTO egg_inventory
(
    batch_id,
    harvest_date,
    harvest_time,
    egg_size,
    quantity,
    current_stock,
    movement_type,
    reason,
    date_logged
)

VALUES

(
    '$batch_id',
    '$harvest_date',
    '$harvest_time',
    '$egg_size',
    '$quantity',
    '$new_stock',
    '$movement',
    '$reason',
    '$current_date'
)";

    mysqli_query($conn,$sql);

}
    
    // ===================== SUPPLY INVENTORY CRUD =====================
if (isset($_POST['action_supply'])) {

    $movement = mysqli_real_escape_string($conn, $_POST['action_supply']);

    $category = mysqli_real_escape_string($conn, $_POST['category']);

    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);

    $quantity = intval($_POST['quantity']);

    $reason = mysqli_real_escape_string($conn, $_POST['reason']);

    // =========================
    // Batch ID Generator
    // =========================

    if($category == "Feeds"){
        $code = "FD";
    }
    elseif($category == "Trays"){
        $code = "TR";
    }
    else{
        $code = "MD";
    }

    $today = date("Ymd");

    $prefix = $code.$today."-";

    $batch_query = mysqli_query($conn,"
        SELECT batch_id
        FROM supply_inventory
        WHERE batch_id LIKE '$prefix%'
        ORDER BY id DESC
        LIMIT 1
    ");

    if(mysqli_num_rows($batch_query)>0){

        $row = mysqli_fetch_assoc($batch_query);

        $last = (int)substr($row['batch_id'],-3);

        $next = str_pad($last+1,3,'0',STR_PAD_LEFT);

    }else{

        $next = "001";

    }

    $batch_id = $prefix.$next;

    // =========================
    // Get Current Stock
    // =========================

    $stock_query = mysqli_query($conn,"
        SELECT current_stock
        FROM supply_inventory
        WHERE item_category='$category'
        AND item_name='$item_name'
        ORDER BY id DESC
        LIMIT 1
    ");

    if(mysqli_num_rows($stock_query)>0){

        $stock = mysqli_fetch_assoc($stock_query);

        $current_stock = $stock['current_stock'];

    }else{

        $current_stock = 0;

    }

    // =========================
    // Compute New Stock
    // =========================

    if($movement=="Stock In"){

        $new_stock = $current_stock + $quantity;

    }
    elseif($movement=="Stock Out"){

        $new_stock = $current_stock - $quantity;

    }
    else{

        $new_stock = $quantity;

    }

   mysqli_query($conn,"
    INSERT INTO supply_inventory
    (
        batch_id,
        item_category,
        item_name,
        quantity,
        current_stock,
        action_type,
        reason,
        date_logged
    )

    VALUES
    (
        '$batch_id',
        '$category',
        '$item_name',
        '$quantity',
        '$new_stock',
        '$movement',
        '$reason',
        '$current_date'
    )
");

}
    // I-refresh ang page para maiwasan ang double form submission sa resubmit
    header("Location: inventory.php");
    exit();
}




// -------------------------------------------------------------------------
// 2. GET ACTIVE CATEGORY FILTER FOR REPORTS
// -------------------------------------------------------------------------
$filter_category = isset($_GET['filter_cat']) ? mysqli_real_escape_string($conn, $_GET['filter_cat']) : 'All';

$egg_search = isset($_GET['egg_search']) ? mysqli_real_escape_string($conn,$_GET['egg_search']) : "";

$supply_search = isset($_GET['supply_search']) ? mysqli_real_escape_string($conn,$_GET['supply_search']) : "";

$history_search = isset($_GET['history_search']) ? mysqli_real_escape_string($conn,$_GET['history_search']) : "";

// --- GET NEXT BATCH ID FOR MODAL DISPLAY ---
$today_str = date('Ymd');
$prefix = "EG" . $today_str . "-";

$seq_query = mysqli_query($conn, "SELECT batch_id FROM egg_inventory WHERE batch_id LIKE '$prefix%' ORDER BY id DESC LIMIT 1");

if (mysqli_num_rows($seq_query) > 0) {
    $last_row = mysqli_fetch_assoc($seq_query);
    $last_num = (int)substr($last_row['batch_id'], -3);
    $next_num = str_pad($last_num + 1, 3, '0', STR_PAD_LEFT);
} else {
    $next_num = '001';
}

$next_batch_id = $prefix . $next_num; // Lalabas halimbawa: EG20260728-008


// ---------- SUPPLY NEXT BATCH IDS ----------

function getNextSupplyBatch($conn, $code){

    $today = date("Ymd");

    $prefix = $code.$today."-";

    $query = mysqli_query($conn,"
        SELECT batch_id
        FROM supply_inventory
        WHERE batch_id LIKE '$prefix%'
        ORDER BY id DESC
        LIMIT 1
    ");

    if(mysqli_num_rows($query)>0){

        $row = mysqli_fetch_assoc($query);

        $last = (int)substr($row['batch_id'],-3);

        $next = str_pad($last+1,3,'0',STR_PAD_LEFT);

    }else{

        $next="001";

    }

    return $prefix.$next;

}

$next_feed_batch = getNextSupplyBatch($conn,"FD");

$next_tray_batch = getNextSupplyBatch($conn,"TR");

$next_medicine_batch = getNextSupplyBatch($conn,"MD");


// -------------------------------------------------------------------------
// 3. INVENTORY NOTIFICATIONS - LOW STOCK ALERTS
// -------------------------------------------------------------------------

$low_stock_threshold = 10;
$inventory_notifications = array();


// =========================
// LOW EGG STOCK
// =========================

$egg_notification_query = mysqli_query($conn,"
    SELECT egg_size, current_stock
    FROM egg_inventory
    WHERE id IN (
        SELECT MAX(id)
        FROM egg_inventory
        GROUP BY egg_size
    )
    AND current_stock <= $low_stock_threshold
    ORDER BY current_stock ASC
");

if($egg_notification_query){

    while($row = mysqli_fetch_assoc($egg_notification_query)){

        $inventory_notifications[] = array(
            'type' => 'Eggs',
            'item' => $row['egg_size'],
            'stock' => $row['current_stock']
        );

    }

}


// =========================
// LOW SUPPLY STOCK
// =========================

$supply_notification_query = mysqli_query($conn,"
    SELECT item_category, item_name, current_stock
    FROM supply_inventory
    WHERE id IN (
        SELECT MAX(id)
        FROM supply_inventory
        GROUP BY item_category, item_name
    )
    AND current_stock <= $low_stock_threshold
    ORDER BY current_stock ASC
");

if($supply_notification_query){

    while($row = mysqli_fetch_assoc($supply_notification_query)){

        $inventory_notifications[] = array(
            'type' => $row['item_category'],
            'item' => $row['item_name'],
            'stock' => $row['current_stock']
        );

    }

}

$notification_count = count($inventory_notifications);
?>
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VDVC Manager Access - Full Inventory Control & Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
       body {
    background-color: #f3f4f6;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* =========================
   INVENTORY OUTER CARD
   ========================= */
.inventory-outer-card {
    background-color: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    padding: 0;
    margin-bottom: 25px;
    overflow: hidden;
}
.inventory-outer-card > .container-fluid {
    padding-top: 18px;
    padding-bottom: 18px;
}
/* =========================
   MAIN CARDS INSIDE INVENTORY
   ========================= */
.row > .col-lg-6 {
    display: flex;
}

.row > .col-lg-6 > .card {
    width: 100%;
    height: 100%;
    min-height: 500px;
    display: flex;
    flex-direction: column;

    border: 1px solid #e5e7eb;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.04);
    margin-bottom: 18px;
}

.row > .col-lg-6 > .card .card-body {
    flex: 1;
}

.card-header {
    background-color: #ffffff;
    border-bottom: 1px solid #f0f1f3;
    font-weight: 600;
    font-size: 0.95rem;
    padding: 12px 14px;
    border-top-left-radius: 10px !important;
    border-top-right-radius: 10px !important;
}

.card-header small {
    font-size: 0.75rem;
    font-weight: 400;
}

.card-body {
    padding: 14px;
}

/* =========================
   GENERAL TEXT
   ========================= */
body,
.form-control,
.form-select,
.btn {
    font-size: 0.82rem;
}

h6 {
    font-size: 0.88rem;
}

.small {
    font-size: 0.75rem !important;
}

/* =========================
   TABLE TEXT
   ========================= */
.table {
    margin-bottom: 0;
}

.table th {
    background-color: #f9fafb;
    color: #4b5563;
    font-weight: 600;
    font-size: 0.72rem;
    text-transform: uppercase;
    white-space: nowrap;
    padding: 8px 9px;
}

.table td {
    vertical-align: middle;
    font-size: 0.78rem;
    padding: 8px 9px;
}

/* =========================
   SEARCH BOX
   ========================= */
.input-group .form-control {
    font-size: 0.8rem;
    padding: 7px 10px;
}

.input-group .btn {
    font-size: 0.78rem;
    padding: 7px 11px;
}

/* =========================
   BUTTONS
   ========================= */
.btn-sm {
    font-size: 0.76rem !important;
    padding: 6px 10px;
}

.btn-custom-blue {
    background-color: #4682b4;
    color: white;
}

.btn-custom-blue:hover {
    background-color: #356a93;
    color: white;
}

.btn-custom-danger {
    background-color: #cd5c5c;
    color: white;
}

.btn-custom-danger:hover {
    background-color: #b04f4f;
    color: white;
}

/* =========================
   SUPPLY ICON CARDS
   ========================= */
.supply-icon-card {
    text-align: center;
    padding: 10px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fafafa;
}

.supply-icon-card i {
    font-size: 1.45rem;
    color: #4b5563;
}

.supply-icon-card .small {
    font-size: 0.74rem !important;
}

/* =========================
   BADGES
   ========================= */
.badge {
    font-size: 0.68rem;
    font-weight: 500;
    padding: 4px 7px;
}

.status-badge-fan {
    background-color: #e6f7ed;
    color: #1f9254;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 600;
}

/* =========================
   CATEGORY REPORT BUTTONS
   ========================= */
.category-report-btn {
    font-size: 0.78rem;
}

.category-report-btn strong {
    font-size: 0.95rem !important;
}

.category-report-btn i {
    font-size: 1.5rem !important;
}

/* =========================
   MODAL TEXT
   ========================= */
.modal-title {
    font-size: 0.95rem;
}

.modal-body,
.modal-footer {
    font-size: 0.82rem;
}

.form-label {
    font-size: 0.78rem;
    font-weight: 600;
}


/* =========================
   INVENTORY PAGE HEADER
   ========================= */
.inventory-page-header {
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    padding: 25px 30px;
    margin-bottom: 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.inventory-header-left h1 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: #172033;
}

.inventory-header-left p {
    margin: 8px 0 0;
    font-size: 0.9rem;
    color: #64748b;
}

.inventory-header-right {
    display: flex;
    align-items: center;
    gap: 14px;
}
/* =========================
   NOTIFICATION BELL
   ========================= */

.notification-wrapper {
    position: relative;
}

.notification-icon {
    position: relative;
    width: 48px;
    height: 48px;
    border: 1px solid #e5e7eb;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #f0a53a;
    background: #ffffff;
    font-size: 1.2rem;
    cursor: pointer;
    transition: 0.2s ease;
}

.notification-icon:hover {
    background: #fffaf2;
    border-color: #f0a53a;
}

.notification-icon:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(240,165,58,0.15);
}


/* =========================
   NOTIFICATION BADGE
   ========================= */

.notification-badge {
    position: absolute;
    top: -4px;
    right: -2px;
    min-width: 18px;
    height: 18px;
    padding: 1px 5px;
    border-radius: 20px;
    background: #ef4444;
    color: #ffffff;
    font-size: 0.65rem;
    font-weight: 700;
    text-align: center;
    line-height: 16px;
}


/* =========================
   NOTIFICATION DROPDOWN
   ========================= */

.notification-dropdown {
    display: none;
    position: absolute;
    top: 58px;
    right: 0;
    width: 350px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.12);
    z-index: 9999;
    overflow: hidden;
}

.notification-dropdown.show {
    display: block;
}


/* =========================
   NOTIFICATION HEADER
   ========================= */

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 16px;
    border-bottom: 1px solid #f0f1f3;
    background: #ffffff;
}

.notification-header strong {
    display: block;
    color: #172033;
    font-size: 0.85rem;
}

.notification-header small {
    display: block;
    color: #64748b;
    font-size: 0.7rem;
    margin-top: 2px;
}

.notification-total {
    min-width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #fee2e2;
    color: #dc2626;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
}


/* =========================
   NOTIFICATION LIST
   ========================= */

.notification-list {
    max-height: 320px;
    overflow-y: auto;
}


/* =========================
   NOTIFICATION ITEM
   ========================= */

.notification-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
    transition: 0.2s ease;
}

.notification-item:hover {
    background: #f8fafc;
}

.notification-item:last-child {
    border-bottom: none;
}


/* =========================
   NOTIFICATION ICON
   ========================= */

.notification-item-icon {
    width: 34px;
    height: 34px;
    min-width: 34px;
    border-radius: 50%;
    background: #fff7ed;
    color: #f97316;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
}


/* =========================
   NOTIFICATION CONTENT
   ========================= */

.notification-item-content {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
}

.notification-item-content strong {
    color: #172033;
    font-size: 0.78rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.notification-item-content span {
    color: #64748b;
    font-size: 0.68rem;
    margin-top: 1px;
}

.notification-item-content small {
    color: #475569;
    font-size: 0.68rem;
    margin-top: 2px;
}


/* =========================
   LOW STOCK LABEL
   ========================= */

.low-stock-label {
    font-size: 0.58rem;
    font-weight: 700;
    color: #dc2626;
    background: #fee2e2;
    padding: 4px 6px;
    border-radius: 6px;
    white-space: nowrap;
}


/* =========================
   EMPTY NOTIFICATION
   ========================= */

.notification-empty {
    padding: 28px 18px;
    text-align: center;
}

.notification-empty i {
    display: block;
    font-size: 1.8rem;
    color: #22c55e;
    margin-bottom: 8px;
}

.notification-empty strong {
    display: block;
    font-size: 0.8rem;
    color: #172033;
}

.notification-empty span {
    display: block;
    font-size: 0.68rem;
    color: #64748b;
    margin-top: 4px;
}


/* =========================
   NOTIFICATION FOOTER
   ========================= */

.notification-footer {
    padding: 9px 14px;
    border-top: 1px solid #f0f1f3;
    background: #f9fafb;
    color: #64748b;
    font-size: 0.65rem;
}

.notification-badge {
    position: absolute;
    top: -4px;
    right: -2px;
    min-width: 18px;
    height: 18px;
    padding: 1px 5px;
    border-radius: 20px;
    background: #ef4444;
    color: #ffffff;
    font-size: 0.65rem;
    font-weight: 700;
    text-align: center;
    line-height: 16px;
}

.manager-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #e6f4ea;
    color: #2f8f57;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
}

.manager-account {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
    min-width: 125px;
}

.manager-account strong {
    font-size: 0.85rem;
    color: #172033;
}

.manager-account span {
    font-size: 0.72rem;
    color: #64748b;
    margin-top: 2px;
}

.manager-account small {
    font-size: 0.72rem;
    font-weight: 700;
    color: #198754;
    margin-top: 2px;
}

.account-arrow {
    color: #475569;
    font-size: 0.75rem;
    margin-left: 2px;
}
    </style>
</head>
<body>

<!-- TOP NAVBAR HEADER -->
<?php include('manager_panel.php'); ?>

<div class="main-content">

    <!-- ==================== INVENTORY OUTER CARD ==================== -->
    <div class="inventory-outer-card">

        <!-- ==================== INVENTORY PAGE HEADER ==================== -->
        <div class="inventory-page-header">

            <div class="inventory-header-left">
                <h1>Inventory Management</h1>
                <p>Manage egg and supply inventory, stock movements, and inventory records</p>
            </div>

            <div class="inventory-header-right">

              <div class="notification-wrapper">

    <button
        type="button"
        class="notification-icon"
        id="notificationBell"
        aria-label="Inventory Notifications">

        <i class="fa-solid fa-bell"></i>

        <?php if($notification_count > 0){ ?>

            <span class="notification-badge">
                <?php echo $notification_count; ?>
            </span>

        <?php } ?>

    </button>


    <!-- =========================
         NOTIFICATION DROPDOWN
         ========================= -->

    <div class="notification-dropdown" id="notificationDropdown">

        <div class="notification-header">

            <div>
                <strong>Inventory Notifications</strong>
                <small>Low stock alerts</small>
            </div>

            <span class="notification-total">
                <?php echo $notification_count; ?>
            </span>

        </div>


        <div class="notification-list">

            <?php if($notification_count > 0){ ?>

                <?php foreach($inventory_notifications as $notification){ ?>

                    <div class="notification-item">

                        <div class="notification-item-icon">

                            <?php
                            if($notification['type'] == 'Eggs'){
                                echo '<i class="fa-solid fa-egg"></i>';
                            }
                            elseif($notification['type'] == 'Feeds'){
                                echo '<i class="fa-solid fa-wheat-awn"></i>';
                            }
                            elseif($notification['type'] == 'Trays'){
                                echo '<i class="fa-solid fa-boxes-stacked"></i>';
                            }
                            else{
                                echo '<i class="fa-solid fa-prescription-bottle-medical"></i>';
                            }
                            ?>

                        </div>


                        <div class="notification-item-content">

                            <strong>
                                <?php echo htmlspecialchars($notification['item']); ?>
                            </strong>

                            <span>
                                <?php echo htmlspecialchars($notification['type']); ?>
                            </span>

                            <small>
                                Current stock:
                                <b><?php echo number_format($notification['stock']); ?></b>
                            </small>

                        </div>


                        <div class="low-stock-label">
                            Low Stock
                        </div>

                    </div>

                <?php } ?>

            <?php } else { ?>

                <div class="notification-empty">

                    <i class="fa-solid fa-circle-check"></i>

                    <strong>No low stock alerts</strong>

                    <span>
                        All inventory stocks are currently sufficient.
                    </span>

                </div>

            <?php } ?>

        </div>


        <div class="notification-footer">

            <span>
                Threshold: <?php echo $low_stock_threshold; ?> or below
            </span>

        </div>

    </div>

</div>

                <div class="manager-avatar">
                    MA
                </div>

                <div class="manager-account">
                    <strong>Manager Account</strong>
                    <span>Manager</span>
                    <span><?php echo date('F d, Y'); ?></span>
                    <small id="inventoryClock"></small>
                </div>

                <div class="account-arrow">
                    <i class="fa-solid fa-chevron-down"></i>
                </div>

            </div>

        </div>

        <!-- ==================== INVENTORY CONTENT ==================== -->
        <div class="container-fluid px-4">

        <div class="row">
        <!-- ==================== LEFT COLUMN: EGGS COMPREHENSIVE CONTROL ==================== -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>EGGS <small class="text-muted font-weight-normal">Comprehensive Egg Inventory & CRUD Logs</small></span>
                    <i class="fa-solid fa-chevron-up text-muted"></i>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Detailed Egg Stock Ledger</h6>
                <form id="eggSearchForm" class="mb-3">
<div class="input-group">

<input
type="search"
id="egg_search"
name="egg_search"
class="form-control"
placeholder="Search Batch ID, Egg Size, Movement, Harvest Date or Reason..."
value="<?php echo htmlspecialchars($egg_search); ?>">

 <button type="button" id="eggSearchBtn" class="btn btn-primary">
    <i class="fa-solid fa-search"></i>
</button>

</div>

</form>
                   <div class="table-responsive" style="height: 375px; overflow-y: auto;">
                      <table id="eggTable" class="table table-hover align-middle">
                           <thead>

<tr>

<th>Batch ID</th>

<th>Harvest Date</th>

<th>Harvest Time</th>

<th>Egg Size</th>

<th>Movement</th>

<th>Quantity</th>

<th>Reason</th>

</tr>

</thead>
                            <tbody>
                                <?php
                                $egg_res = mysqli_query($conn,"
SELECT *
FROM egg_inventory
ORDER BY id DESC
");
                               while($row = mysqli_fetch_assoc($egg_res)){

echo "

<tr>

<td>{$row['batch_id']}</td>

<td>{$row['harvest_date']}</td>

<td>".date('h:i A',strtotime($row['harvest_time']))."</td>

<td>

<span class='badge bg-secondary'>

{$row['egg_size']}

</span>

</td>

<td>{$row['movement_type']}</td>

<td>".number_format($row['quantity'])."</td>

<td>{$row['reason']}</td>

</tr>

";

}
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                   <div class="mt-3 d-flex flex-wrap gap-2">

    <button
        class="btn btn-success btn-sm"
        data-bs-toggle="modal"
        data-bs-target="#eggModal"
        onclick="setupEggModal('Stock In')">

        Harvest Eggs

    </button>

    <button
        class="btn btn-warning btn-sm"
        data-bs-toggle="modal"
        data-bs-target="#eggModal"
        onclick="setupEggModal('Stock Out')">

        Stock Out

    </button>

    <button
        class="btn btn-primary btn-sm"
        data-bs-toggle="modal"
        data-bs-target="#eggModal"
        onclick="setupEggModal('Adjustment')">

        Stock Adjustment

    </button>

</div>
                </div>
            </div>

          
        </div>

        <!-- ==================== RIGHT COLUMN: SUPPLIES CONTROL ==================== -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>SUPPLIES <small class="text-muted font-weight-normal">Detailed Supply Inventory & CRUD Logs</small></span>
                    <i class="fa-solid fa-chevron-up text-muted"></i>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3 text-center">
                        <div class="col-4">
                            <div class="supply-icon-card">
                                <i class="fa-solid fa-wheat-awn mb-1 text-warning"></i>
                                <div class="small fw-bold">Feeds</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="supply-icon-card">
                                <i class="fa-solid fa-boxes-stacked mb-1 text-secondary"></i>
                                <div class="small fw-bold">Trays</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="supply-icon-card">
                                <i class="fa-solid fa-prescription-bottle-medical mb-1 text-danger"></i>
                                <div class="small fw-bold">Medicine</div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-2">Unified Supply Stock Logs</h6>
                    <form class="mb-3">

<div class="input-group">

<input
type="search"
id="supply_search"
class="form-control"
placeholder="Search Batch ID, Category, Item Name, Movement or Reason...">

<button
type="button"
class="btn btn-primary">

<i class="fa-solid fa-search"></i>

</button>

</div>

</form>
                   <div class="table-responsive" style="height: 300px; overflow-y: auto;">
                      <table id="supplyTable" class="table table-hover align-middle">
                            <thead>
    <tr>
        <th>Batch ID</th>
        <th>Category</th>
        <th>Item Name</th>
        <th>Movement</th>
        <th>Quantity</th>
        <th>Current Stock</th>
        <th>Reason</th>
    </tr>
</thead>
                            <tbody>
                               <?php

$supply_res = mysqli_query($conn,"
SELECT *
FROM supply_inventory
ORDER BY id DESC
");

while($row=mysqli_fetch_assoc($supply_res)){

echo "

<tr>

<td>{$row['batch_id']}</td>

<td>

<span class='badge bg-secondary'>

{$row['item_category']}

</span>

</td>

<td>{$row['item_name']}</td>

<td>{$row['action_type']}</td>

<td>".number_format($row['quantity'])."</td>

<td>".number_format($row['current_stock'])."</td>

<td>{$row['reason']}</td>

</tr>

";

}

?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex flex-wrap gap-2">

    <button
        class="btn btn-success btn-sm"
        data-bs-toggle="modal"
        data-bs-target="#supplyModal"
        onclick="setupSupplyModal('Stock In')">

        Stock In

    </button>

    <button
        class="btn btn-warning btn-sm"
        data-bs-toggle="modal"
        data-bs-target="#supplyModal"
        onclick="setupSupplyModal('Stock Out')">

        Stock Out

    </button>

    <button
        class="btn btn-primary btn-sm"
        data-bs-toggle="modal"
        data-bs-target="#supplyModal"
        onclick="setupSupplyModal('Adjustment')">

        Stock Adjustment

    </button>

</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== LOWER SECTION: INVENTORY HISTORY & REPORTS (CONNECTED GENERAL LEDGER) ==================== -->
    <div class="card mt-2">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>INVENTORY HISTORY & REPORTS <small class="text-muted font-weight-normal">(Live General Ledger Consolidated Timeline)</small></span>
            <i class="fa-solid fa-chevron-up text-muted"></i>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- MASTER REPORT TABLE -->
                <div class="col-lg-8">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="inventory.php?filter_cat=All" class="btn btn-outline-secondary <?php echo $filter_category == 'All' ? 'active' : ''; ?>">All Categories</a>
                            
                        </div>
                    </div>
                    <form class="mb-3">

<div class="input-group">

<input
type="search"
id="history_search"
class="form-control"
placeholder="Search Date, Batch ID, Category, Egg Size, Item Name, Movement or Reason...">

<button
type="button"
class="btn btn-primary">

<i class="fa-solid fa-search"></i>

</button>

</div>

</form>
                    <div class="table-responsive" style="max-height: 300px;">
                       <table id="historyTable" class="table table-bordered table-sm align-middle text-start">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Action Type</th>
                                    <th>Egg/Size/Medicine</th>
                                    <th>Batch ID</th>
                                    <th>Quantity</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // UNIFIED QUERY: Kinokonekta at pinagsasama ang logs ng Eggs at Supplies sa iisang view gamit ang UNION ALL
                             $union_query = "

SELECT
date_logged,
'Eggs' AS category,
movement_type AS action_type,
egg_size AS item_detail,
batch_id,
quantity,
reason
FROM egg_inventory

UNION ALL

SELECT
date_logged,
item_category AS category,
action_type,
item_name AS item_detail,
batch_id,
quantity,
reason
FROM supply_inventory

";

                                // I-apply ang filter kapag pinindot ang tabs/cards
                         if($filter_category=="All"){

    $query="SELECT *
    FROM ($union_query) AS combined_ledger";

}
else{

    $query="SELECT *
    FROM ($union_query) AS combined_ledger
    WHERE category='$filter_category'";

}

$query.=" ORDER BY date_logged DESC";
                                
                                $report_res = mysqli_query($conn, $query);
                                
                                if (mysqli_num_rows($report_res) > 0) {
                                    while($row = mysqli_fetch_assoc($report_res)) {
                                        echo "<tr>
                                               <td>".date('M d, Y h:i A', strtotime($row['date_logged']))."</td>
                                                <td><span class='fw-semibold text-dark'>{$row['category']}</span></td>
                                                <td>{$row['action_type']}</td>
                                                <td>{$row['item_detail']}</td>
                                                <td><span class='text-muted'>{$row['batch_id']}</span></td>
                                                <td><strong>".number_format($row['quantity'])."</strong></td>
                                               <td>{$row['reason']}</td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center text-muted py-3'>No dynamic history logs recorded yet for this category filter.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- QUICK CLICK CATEGORY REPORT FILTERS (Malalaking Buttons sa Kanan) -->
                <div class="col-lg-4">
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="inventory.php?filter_cat=Eggs" class="btn btn-light w-100 py-3 border text-start d-flex align-items-center justify-content-between decoration-none">
                                <div><small class="text-muted d-block">View Category</small><strong class="fs-5 text-dark">Eggs</strong></div>
                                <i class="fa-solid fa-egg text-warning fs-3"></i>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="inventory.php?filter_cat=Feeds" class="btn btn-light w-100 py-3 border text-start d-flex align-items-center justify-content-between">
                                <div><small class="text-muted d-block">View Category</small><strong class="fs-5 text-dark">Feeds</strong></div>
                                <i class="fa-solid fa-wheat-awn text-success fs-3"></i>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="inventory.php?filter_cat=Trays" class="btn btn-light w-100 py-3 border text-start d-flex align-items-center justify-content-between">
                                <div><small class="text-muted d-block">View Category</small><strong class="fs-5 text-dark">Trays</strong></div>
                                <i class="fa-solid fa-boxes-stacked text-secondary fs-3"></i>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="inventory.php?filter_cat=Medicine" class="btn btn-light w-100 py-3 border text-start d-flex align-items-center justify-content-between">
                                <div><small class="text-muted d-block">View Category</small><strong class="fs-5 text-dark">Medicine</strong></div>
                                <i class="fa-solid fa-prescription-bottle-medical text-danger fs-3"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<div class="modal fade" id="eggModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="eggModalTitle">Egg Inventory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <!-- Movement -->
                <input type="hidden" name="action_egg" id="egg_action_type">

                <!-- Batch ID -->
                <div class="mb-3">
                    <label class="form-label">Batch ID</label>
                    <input
                        type="text"
                        name="batch_id"
                        id="batch_id"
                        class="form-control"
                        readonly>
                </div>

              <!-- Harvest Date -->
<div class="mb-3">
    <label class="form-label">Harvest Date</label>
    <input
        type="date"
        name="harvest_date"
        class="form-control"
        value="<?php echo date('Y-m-d'); ?>"
        min="<?php echo date('Y-m-d'); ?>"
        required>
</div>

                <!-- Harvest Time -->
                <div class="mb-3">
                    <label class="form-label">Harvest Time</label>
                    <input
                        type="time"
                        name="harvest_time"
                        class="form-control"
                        value="<?php echo date('H:i'); ?>"
                        required>
                </div>

                <!-- Egg Size -->
                <div class="mb-3">
                    <label class="form-label">Egg Size</label>

                    <select
                        name="egg_size"
                        class="form-select"
                        required>

                        <option value="XS">XS</option>
                        <option value="Small">Small</option>
                        <option value="Medium">Medium</option>
                        <option value="Large">Large</option>
                        <option value="XL">XL</option>
                        <option value="Jumbo">Jumbo</option>
                        <option value="Super Jumbo">Super Jumbo</option>
                        <option value="Double Yolk">Double Yolk</option>

                    </select>

                </div>

                <!-- Quantity -->
                <div class="mb-3">

                    <label class="form-label">Quantity</label>

                    <input
                        type="number"
                        name="quantity"
                        class="form-control"
                        required>

                </div>

                <!-- Reason -->

                <div class="mb-3">

                    <label class="form-label">Reason</label>

                    <select
                        name="reason"
                        id="reason"
                        class="form-select">

                        <option value="Harvest">Harvest</option>
                        <option value="Reservation">Reservation</option>
                        <option value="Adjustment">Adjustment</option>

                    </select>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal">

                    Cancel

                </button>

                <button
                    type="submit"
                    id="eggSubmitBtn"
                    class="btn btn-primary">

                    Save

                </button>

            </div>

        </form>
    </div>
</div>
</div>
<!-- ==================== SUPPLY INVENTORY MODAL ==================== -->
<div class="modal fade" id="supplyModal" tabindex="-1">

<div class="modal-dialog">

<form method="POST" class="modal-content">

<div class="modal-header">

<h5 class="modal-title" id="supplyModalTitle">

Supply Inventory

</h5>

<button type="button" class="btn-close" data-bs-dismiss="modal"></button>

</div>

<div class="modal-body">

<input
type="hidden"
name="action_supply"
id="supply_action_type">

<!-- Category -->
<div class="mb-3">

<label class="form-label">

Batch ID

</label>

<input
type="text"
id="supply_batch_id"
class="form-control"
readonly>

</div>

<div class="mb-3">

<label class="form-label">

Item Category

</label>

<select
name="category"
id="supplyCategorySelect"
class="form-select"
required>

<option value="Feeds">Feeds</option>

<option value="Trays">Trays</option>

<option value="Medicine">Medicine</option>

</select>

</div>

<!-- Item Name -->

<div class="mb-3">

<label class="form-label">

Item Name

</label>

<input
type="text"
name="item_name"
class="form-control"
placeholder="Enter item name"
required>

</div>

<!-- Quantity -->

<div class="mb-3">

<label class="form-label">

Quantity

</label>

<input
type="number"
name="quantity"
class="form-control"
required>

</div>

<!-- Reason -->

<div class="mb-3">

<label class="form-label">

Reason

</label>

<select
name="reason"
id="supplyReason"
class="form-select">

<option value="Purchase">

Purchase

</option>

<option value="Usage">

Usage

</option>

<option value="Adjustment">

Adjustment

</option>

</select>

</div>

</div>

<div class="modal-footer">

<button
type="button"
class="btn btn-secondary"
data-bs-dismiss="modal">

Cancel

</button>

<button
type="submit"
id="supplySubmitBtn"
class="btn btn-primary">

Save

</button>

</div>

</form>

</div>

</div>

<!-- JAVASCRIPT CONTROLS FOR SWITCHING MODAL TEXTS DYNAMICALLY -->
<script>
function setupEggModal(action){

    document.getElementById("egg_action_type").value = action;

    document.getElementById("eggModalTitle").innerHTML = action;

    document.getElementById("eggSubmitBtn").innerHTML = action;

    // I-set sa field ang na-calculate na next Batch ID galing PHP
    document.getElementById("batch_id").value = "<?php echo $next_batch_id; ?>";

}

function setupSupplyModal(action){

    document.getElementById("supply_action_type").value = action;

    document.getElementById("supplyModalTitle").innerHTML = action;

    document.getElementById("supplySubmitBtn").innerHTML = action;

    let reason=document.getElementById("supplyReason");

    if(action=="Stock In"){

        reason.value="Purchase";

    }
    else if(action=="Stock Out"){

        reason.value="Usage";

    }
    else{

        reason.value="Adjustment";

    }

    updateSupplyBatchID();

}

function updateSupplyBatchID(){

    let category=document.getElementById("supplyCategorySelect").value;

    let batch="";

    if(category=="Feeds"){

        batch="<?php echo $next_feed_batch; ?>";

    }
    else if(category=="Trays"){

        batch="<?php echo $next_tray_batch; ?>";

    }
    else{

        batch="<?php echo $next_medicine_batch; ?>";

    }

    document.getElementById("supply_batch_id").value=batch;

}

document.getElementById("supplyCategorySelect").addEventListener("change",updateSupplyBatchID);

const supplySearch=document.getElementById("supply_search");

supplySearch.addEventListener("input",function(){

let keyword=this.value.toLowerCase().trim();

let rows=document.querySelectorAll("#supplyTable tbody tr");

rows.forEach(function(row){

let text=row.textContent.toLowerCase();

row.style.display=text.includes(keyword)?"":"none";

});

});

const historySearch=document.getElementById("history_search");

historySearch.addEventListener("input",function(){

let keyword=this.value.toLowerCase().trim();

let rows=document.querySelectorAll("#historyTable tbody tr");

rows.forEach(function(row){

let text=row.textContent.toLowerCase();

row.style.display=text.includes(keyword)?"":"none";

});

});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

const eggSearch = document.getElementById("egg_search");
const eggSearchBtn = document.getElementById("eggSearchBtn");

function filterEggTable(){

    let keyword = eggSearch.value.toLowerCase().trim();

    let rows = document.querySelectorAll("#eggTable tbody tr");

    rows.forEach(function(row){

        let text = row.textContent.toLowerCase();

        row.style.display = text.includes(keyword) ? "" : "none";

    });

}

// Live Search
eggSearch.addEventListener("input", filterEggTable);

// Search Button
eggSearchBtn.addEventListener("click", filterEggTable);

// Enter Key
eggSearch.addEventListener("keypress", function(e){

    if(e.key === "Enter"){

        e.preventDefault();

        filterEggTable();

    }

});



function updateInventoryClock() {

    var now = new Date();

    var time = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });

    document.getElementById('inventoryClock').innerHTML = time;
}

updateInventoryClock();

setInterval(updateInventoryClock, 1000);




/* =========================================================
   INVENTORY NOTIFICATION BELL
   ========================================================= */

var notificationBell = document.getElementById("notificationBell");
var notificationDropdown = document.getElementById("notificationDropdown");


if(notificationBell && notificationDropdown){

    notificationBell.addEventListener("click", function(event){

        event.stopPropagation();

        notificationDropdown.classList.toggle("show");

    });


    document.addEventListener("click", function(event){

        if(
            !notificationDropdown.contains(event.target) &&
            !notificationBell.contains(event.target)
        ){

            notificationDropdown.classList.remove("show");

        }

    });

}



</script>
</body>
</html>