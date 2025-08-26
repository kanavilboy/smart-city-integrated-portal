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

if (!$agency) {
    die("Gas agency not found for this user.");
}

// Get all bookings for this agency
$bookings = $conn->query("
    SELECT b.*, c.full_name, c.phone, cy.type as cylinder_type
    FROM gas_bookings b
    JOIN gas_customers c ON b.customer_id = c.id
    JOIN gas_cylinders cy ON b.cylinder_id = cy.id
    WHERE b.agency_id = {$agency['id']}
    ORDER BY b.booking_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get counts for different statuses
$pendingCount = $conn->query("SELECT COUNT(*) FROM gas_bookings WHERE status = 'pending' AND agency_id = {$agency['id']}")->fetchColumn();
$confirmedCount = $conn->query("SELECT COUNT(*) FROM gas_bookings WHERE status = 'confirmed' AND agency_id = {$agency['id']}")->fetchColumn();
$dispatchedCount = $conn->query("SELECT COUNT(*) FROM gas_bookings WHERE status = 'dispatched' AND agency_id = {$agency['id']}")->fetchColumn();
$deliveredCount = $conn->query("SELECT COUNT(*) FROM gas_bookings WHERE status = 'delivered' AND agency_id = {$agency['id']}")->fetchColumn();

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bookings Management - Gas Agency</title>
    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES-->
    <link href="assets/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
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
                        <?php echo htmlspecialchars($agency_name); ?> - Bookings
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
                    <li class="active-link">
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
                        <h2>BOOKINGS MANAGEMENT</h2>
                        <h5>Manage all gas cylinder bookings</h5>
                    </div>
                </div>
                <!-- /. ROW  -->
                <hr />
                
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-primary text-center no-boder">
                            <div class="panel-body">
                                <i class="fa fa-clock-o fa-3x"></i>
                                <h3><?php echo $pendingCount; ?></h3>
                            </div>
                            <div class="panel-footer">Pending Bookings</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-info text-center no-boder">
                            <div class="panel-body">
                                <i class="fa fa-check fa-3x"></i>
                                <h3><?php echo $confirmedCount; ?></h3>
                            </div>
                            <div class="panel-footer">Confirmed</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-warning text-center no-boder">
                            <div class="panel-body">
                                <i class="fa fa-truck fa-3x"></i>
                                <h3><?php echo $dispatchedCount; ?></h3>
                            </div>
                            <div class="panel-footer">Dispatched</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-success text-center no-boder">
                            <div class="panel-body">
                                <i class="fa fa-home fa-3x"></i>
                                <h3><?php echo $deliveredCount; ?></h3>
                            </div>
                            <div class="panel-footer">Delivered</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                All Bookings
                                <div class="pull-right">
                                    <a href="new_booking.php" class="btn btn-primary btn-xs">
                                        <i class="fa fa-plus"></i> New Booking
                                    </a>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Booking ID</th>
                                                <th>Customer</th>
                                                <th>Contact</th>
                                                <th>Cylinder Type</th>
                                                <th>Quantity</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Payment</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($bookings as $booking): 
                                                // Determine status class
                                                switch($booking['status']) {
                                                    case 'pending':
                                                        $statusClass = 'default';
                                                        break;
                                                    case 'confirmed':
                                                        $statusClass = 'info';
                                                        break;
                                                    case 'dispatched':
                                                        $statusClass = 'warning';
                                                        break;
                                                    case 'delivered':
                                                        $statusClass = 'success';
                                                        break;
                                                    case 'cancelled':
                                                        $statusClass = 'danger';
                                                        break;
                                                    default:
                                                        $statusClass = 'default';
                                                }
                                                
                                                // Determine payment class
                                                if ($booking['payment_status'] == 'paid') {
                                                    $paymentClass = 'success';
                                                } elseif ($booking['payment_status'] == 'failed') {
                                                    $paymentClass = 'danger';
                                                } else {
                                                    $paymentClass = 'warning';
                                                }
                                            ?>
                                            <tr>
                                                <td>GA-<?php echo $booking['id']; ?></td>
                                                <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['cylinder_type']); ?></td>
                                                <td><?php echo $booking['quantity']; ?></td>
                                                <td>₹<?php echo number_format($booking['total_amount'], 2); ?></td>
                                                <td><span class="label label-<?php echo $statusClass; ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                                                <td><span class="label label-<?php echo $paymentClass; ?>"><?php echo ucfirst($booking['payment_status']); ?></span></td>
                                                <td><?php echo date('d M Y h:i A', strtotime($booking['booking_date'])); ?></td>
                                                <td>
                                                    <a href="edit_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary btn-xs" title="Edit">
                                                        <i class="fa fa-edit">Edit</i>
                                                    </a>
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
    <!-- CUSTOM SCRIPTS -->
    <script src="assets/js/custom.js"></script>
</body>
</html>