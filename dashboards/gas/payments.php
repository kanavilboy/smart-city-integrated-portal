<?php
session_start();
require '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch gas agency details
$stmt = $conn->query("SELECT * FROM gas_agencies WHERE user_id = $user_id");
$agency = $stmt->fetch(PDO::FETCH_ASSOC);
$agency_name = $agency['dealer_name'];
$agency_id = $agency['id'];

if (!$agency) {
    die("Gas agency not found for this user.");
}

// Get payment transactions with booking and customer details
$payments = $conn->query("
    SELECT pt.*,
           b.id as booking_id,
           b.quantity,
           b.total_amount as booking_amount,
           b.status as booking_status,
           c.full_name as customer_name,
           c.phone as customer_phone
    FROM gas_payment_transactions pt
    JOIN gas_bookings b ON pt.booking_id = b.id
    JOIN gas_customers c ON b.customer_id = c.id
    WHERE b.agency_id = {$agency['id']}
    ORDER BY pt.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get payment statistics
$totalPayments = $conn->query("
    SELECT COUNT(*) FROM gas_payment_transactions pt
    JOIN gas_bookings b ON pt.booking_id = b.id
    WHERE b.agency_id = {$agency['id']}
")->fetchColumn();

$successfulPayments = $conn->query("
    SELECT COUNT(*) FROM gas_payment_transactions pt
    JOIN gas_bookings b ON pt.booking_id = b.id
    WHERE b.agency_id = {$agency['id']} AND pt.status = 'success'
")->fetchColumn();

$totalRevenue = $conn->query("
    SELECT SUM(pt.amount) FROM gas_payment_transactions pt
    JOIN gas_bookings b ON pt.booking_id = b.id
    WHERE b.agency_id = {$agency['id']} AND pt.status = 'success'
")->fetchColumn();

$pendingPayments = $conn->query("
    SELECT COUNT(*) FROM gas_payment_transactions pt
    JOIN gas_bookings b ON pt.booking_id = b.id
    WHERE b.agency_id = {$agency['id']} AND pt.status IN ('initiated', 'processing')
")->fetchColumn();
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Payments - Gas Agency</title>
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
                        <?php echo htmlspecialchars($agency_name); ?> - Payments
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
                    <li>
                        <a href="deliveries.php"><i class="fa fa-truck"></i>Deliveries</a>
                    </li>
                    <li class="active-link">
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
                        <h2>PAYMENT MANAGEMENT</h2>
                        <h5>View and manage payment transactions</h5>
                    </div>
                </div>
                <!-- /. ROW  -->
                <hr />
                
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-primary text-center no-boder">
                            <div class="panel-body">
                                <i class="fa fa-credit-card fa-3x"></i>
                                <h3><?php echo $totalPayments ? $totalPayments : '0'; ?></h3>
                            </div>
                            <div class="panel-footer">Total Transactions</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-green text-center no-boder">
                            <div class="panel-body">
                                <i class="fa fa-check-circle fa-3x"></i>
                                <h3><?php echo $successfulPayments ? $successfulPayments : '0'; ?></h3>
                            </div>
                            <div class="panel-footer">Successful</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-red text-center no-boder">
                            <div class="panel-body">
                                <i class="fa fa-rupee fa-3x"></i>
                                <h3>₹<?php echo $totalRevenue ? number_format($totalRevenue, 2) : '0.00'; ?></h3>
                            </div>
                            <div class="panel-footer">Total Revenue</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Payment Transactions
                                <div class="pull-right">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                                            Filter Status
                                            <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu pull-right" role="menu">
                                            <li><a href="?status=all">All Transactions</a></li>
                                            <li><a href="?status=success">Successful</a></li>
                                            <li><a href="?status=pending">Pending</a></li>
                                            <li><a href="?status=failed">Failed</a></li>
                                            <li><a href="?status=refunded">Refunded</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover" id="payments-table">
                                        <thead>
                                            <tr>
                                                <th>Customer</th>
                                                <th>Payment Method</th>
                                                <th>Amount</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payments as $payment): 
                                                $statusClass = '';
                                                if ($payment['status'] == 'success') {
                                                    $statusClass = 'success';
                                                } elseif ($payment['status'] == 'initiated' || $payment['status'] == 'processing') {
                                                    $statusClass = 'warning';
                                                } elseif ($payment['status'] == 'failed') {
                                                    $statusClass = 'danger';
                                                } elseif ($payment['status'] == 'refunded') {
                                                    $statusClass = 'info';
                                                }
                                            ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($payment['customer_name']); ?>
                                                    <br><small><?php echo htmlspecialchars($payment['customer_phone']); ?></small>
                                                </td>
                                                <td><?php echo $payment['payment_method'] ? htmlspecialchars($payment['payment_method']) : 'Cash'; ?></td>
                                                <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                                <td><?php echo date('d M Y h:i A', strtotime($payment['created_at'])); ?></td>
                                                <td>
                                                    <span class="label label-<?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($payment['status']); ?>
                                                    </span>
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
            $('#payments-table').dataTable({
                "order": [[5, "desc"]], // Default sort by date
                "pageLength": 25
            });
        });
    </script>
</body>
</html>