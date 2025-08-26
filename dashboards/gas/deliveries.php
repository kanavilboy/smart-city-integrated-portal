<?php
session_start();
require '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch gas agency details based on logged-in user
$stmt = $conn->query("SELECT * FROM gas_agencies WHERE user_id = $user_id");
$agency = $stmt->fetch(PDO::FETCH_ASSOC);
$agency_name = $agency['dealer_name'];
$agency_id = $agency['id'];

if (!$agency) {
    die("Gas agency not found for this user.");
}

// Process delivery confirmation if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delivery'])) {
    $delivery_id = $_POST['delivery_id'];
    
    // Update delivery status to 'delivered'
    $update_stmt = $conn->prepare("
        UPDATE gas_deliveries 
        SET status = 'delivered'
        WHERE id = ? AND EXISTS (
            SELECT 1 FROM gas_bookings b 
            WHERE b.id = gas_deliveries.booking_id 
            AND b.agency_id = ?
        )
    ");
    
    if ($update_stmt->execute(array($delivery_id, $agency_id))) {
        $_SESSION['success_message'] = "Delivery confirmed successfully!";
        header("Location: deliveries.php");
        exit();
    } else {
        $error_message = "Failed to confirm delivery. Please try again.";
    }
}

// Get all deliveries with booking, customer, and cylinder details
$deliveries = $conn->query("
    SELECT d.*, 
           b.id as booking_id,
           b.status as booking_status,
           b.quantity,
           b.total_amount,
           b.payment_status,
           c.id as customer_id,
           c.full_name as customer_name, 
           c.phone as customer_phone,
           c.address as customer_address,
           c.city as customer_city,
           c.state as customer_state,
           c.pincode as customer_pincode,
           gc.id as cylinder_id,
           gc.type as cylinder_type,
           gc.price as cylinder_price
    FROM gas_deliveries d
    JOIN gas_bookings b ON d.booking_id = b.id
    JOIN gas_customers c ON b.customer_id = c.id
    JOIN gas_cylinders gc ON b.cylinder_id = gc.id
    WHERE b.agency_id = {$agency['id']}
    ORDER BY d.delivery_date DESC, d.status ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Get delivery statistics
$totalDeliveries = $conn->query("
    SELECT COUNT(*) FROM gas_deliveries d
    JOIN gas_bookings b ON d.booking_id = b.id
    WHERE b.agency_id = {$agency['id']}
")->fetchColumn();

$scheduledDeliveries = $conn->query("
    SELECT COUNT(*) FROM gas_deliveries d
    JOIN gas_bookings b ON d.booking_id = b.id
    WHERE b.agency_id = {$agency['id']} AND d.status = 'scheduled'
")->fetchColumn();

$deliveredCount = $conn->query("
    SELECT COUNT(*) FROM gas_deliveries d
    JOIN gas_bookings b ON d.booking_id = b.id
    WHERE b.agency_id = {$agency['id']} AND d.status = 'delivered'
")->fetchColumn();

$inTransitCount = $conn->query("
    SELECT COUNT(*) FROM gas_deliveries d
    JOIN gas_bookings b ON d.booking_id = b.id
    WHERE b.agency_id = {$agency['id']} AND d.status = 'in_transit'
")->fetchColumn();
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Deliveries - Gas Agency</title>
    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES-->
    <link href="assets/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <!-- DataTables CSS -->
    <link href="assets/css/dataTables.bootstrap.css" rel="stylesheet" />
</head>
<body>
    <div id="wrapper">
        <div class="navbar navbar-inverse navbar-fixed-top">
            <div class="adjust-nav">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="#">
                        <?php echo htmlspecialchars($agency_name); ?> - Deliveries
                    </a>
                </div>
                <span class="logout-spn">
                    <a href="../../login.php" style="color:#fff;" onclick="return confirm('Are you sure you want to logout?')">
                        <i class="fa fa-sign-out"></i> LOGOUT
                    </a>
                </span>
            </div>
        </div>
        <!-- /. NAV TOP  -->
        <nav class="navbar-default navbar-side" role="navigation">
            <div class="sidebar-collapse">
                <ul class="nav" id="main-menu">
                    <li>
                        <a href="gas_dashboard.php"><i class="fa fa-tachometer"></i>Dashboard</a>
                    </li>
                    <li>
                        <a href="bookings.php"><i class="fa fa-calendar"></i>Bookings</a>
                    </li>
                    <li>
                        <a href="customers.php"><i class="fa fa-users"></i>Customers</a>
                    </li>
                    <li>
                        <a href="inventory.php"><i class="fa fa-database"></i>Inventory</a>
                    </li>
                    <li class="active-link">
                        <a href="deliveries.php"><i class="fa fa-truck"></i>Deliveries</a>
                    </li>
                    <li>
                        <a href="payments.php"><i class="fa fa-credit-card"></i>Payments</a>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- /. NAV SIDE  -->
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        <h2>DELIVERY MANAGEMENT</h2>
                        <h5>Manage your gas cylinder deliveries</h5>
                    </div>
                </div>
                <!-- /. ROW  -->
                <hr />
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-primary text-center no-boder">
                            <div class="panel-body">
                                <i class="fa fa-truck fa-3x"></i>
                                <h3><?php echo $totalDeliveries ? $totalDeliveries : '0'; ?></h3>
                            </div>
                            <div class="panel-footer">Total Deliveries</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-green text-center no-boder">
                            <div class="panel-body">
                                <i class="fa fa-check-circle fa-3x"></i>
                                <h3><?php echo $deliveredCount ? $deliveredCount : '0'; ?></h3>
                            </div>
                            <div class="panel-footer">Delivered</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Delivery Schedule
                                <div class="pull-right">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                                            Filter Status
                                            <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu pull-right" role="menu">
                                            <li><a href="?status=all">All Deliveries</a></li>
                                            <li><a href="?status=scheduled">Scheduled</a></li>
                                            <li><a href="?status=in_transit">In Transit</a></li>
                                            <li><a href="?status=delivered">Delivered</a></li>
                                            <li><a href="?status=failed">Failed</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover" id="deliveries-table">
                                        <thead>
                                            <tr>
                                                <th>Delivery ID</th>
                                                <th>Booking ID</th>
                                                <th>Customer</th>
                                                <th>Cylinder Type</th>
                                                <th>Delivery Date/Time</th>
                                                <th>Qty</th>
                                                <th>Amount</th>
                                                <th>Payment</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($deliveries as $delivery): 
                                                $statusClass = '';
                                                if ($delivery['status'] == 'delivered') {
                                                    $statusClass = 'success';
                                                } elseif ($delivery['status'] == 'scheduled') {
                                                    $statusClass = 'warning';
                                                } elseif ($delivery['status'] == 'in_transit') {
                                                    $statusClass = 'info';
                                                } elseif ($delivery['status'] == 'failed') {
                                                    $statusClass = 'danger';
                                                }
                                                
                                                $paymentClass = '';
                                                if ($delivery['payment_status'] == 'paid') {
                                                    $paymentClass = 'success';
                                                } elseif ($delivery['payment_status'] == 'pending') {
                                                    $paymentClass = 'warning';
                                                } elseif ($delivery['payment_status'] == 'failed') {
                                                    $paymentClass = 'danger';
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo $delivery['id']; ?></td>
                                                <td><?php echo $delivery['booking_id']; ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($delivery['customer_name']); ?>
                                                    <br><small><?php echo htmlspecialchars($delivery['customer_phone']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($delivery['cylinder_type']); ?></td>
                                                <td>
                                                    <?php echo date('d M Y', strtotime($delivery['delivery_date'])); ?>
                                                    <?php if (!empty($delivery['delivery_time'])): ?>
                                                    <br><small><?php echo date('h:i A', strtotime($delivery['delivery_time'])); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $delivery['quantity']; ?></td>
                                                <td>₹<?php echo number_format($delivery['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="label label-<?php echo $paymentClass; ?>">
                                                        <?php echo ucfirst($delivery['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="label label-<?php echo $statusClass; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $delivery['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($delivery['status'] != 'delivered' && $delivery['status'] != 'failed'): ?>
                                                    <form method="post" action="" style="display:inline;">
                                                        <input type="hidden" name="delivery_id" value="<?php echo $delivery['id']; ?>">
                                                        <button type="submit" name="confirm_delivery" class="btn btn-success btn-xs" 
                                                                onclick="return confirm('Are you sure you want to confirm this delivery?')">
                                                            <i class="fa fa-check"></i> Confirm
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /. ROW  -->
            </div>
            <!-- /. PAGE INNER  -->
        </div>
        <!-- /. PAGE WRAPPER  -->
    </div>
    <div class="footer">
        <div class="row">
            <div class="col-lg-12">
                &copy; <?php echo date('Y'); ?> Gas Agency Management System
            </div>
        </div>
    </div>

    <!-- SCRIPTS -AT THE BOTOM TO REDUCE THE LOAD TIME-->
    <!-- JQUERY SCRIPTS -->
    <script src="assets/js/jquery-1.10.2.js"></script>
    <!-- BOOTSTRAP SCRIPTS -->
    <script src="assets/js/bootstrap.min.js"></script>
    <!-- DATA TABLE SCRIPTS -->
    <script src="assets/js/jquery.dataTables.js"></script>
    <script src="assets/js/dataTables.bootstrap.js"></script>
    <!-- CUSTOM SCRIPTS -->
    <script src="assets/js/custom.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#deliveries-table').dataTable({
                "order": [[4, "asc"]], // Default sort by delivery date
                "pageLength": 25
            });
        });
    </script>
</body>
</html>