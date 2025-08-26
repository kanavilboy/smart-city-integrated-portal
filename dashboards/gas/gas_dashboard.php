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
$message = "Welcome $agency_name";

if (!$agency) {
    die("Gas agency not found for this user.");
}

// Get dashboard statistics
$todayBookings = $conn->query("SELECT COUNT(*) FROM gas_bookings WHERE DATE(booking_date) = CURDATE()")->fetchColumn();
$pendingDeliveries = $conn->query("SELECT COUNT(*) FROM gas_deliveries WHERE status IN ('scheduled', 'in_transit')")->fetchColumn();
$cylinderStock = $conn->query("SELECT SUM(stock_quantity) FROM gas_cylinders WHERE is_available = 1")->fetchColumn();
$todayRevenue = $conn->query("SELECT SUM(total_amount) FROM gas_bookings WHERE DATE(booking_date) = CURDATE() AND payment_status = 'paid'")->fetchColumn();

// Get recent bookings
$recentBookings = $conn->query("
    SELECT b.id, c.full_name, cy.type, b.status, b.total_amount 
    FROM gas_bookings b
    JOIN gas_customers c ON b.customer_id = c.id
    JOIN gas_cylinders cy ON b.cylinder_id = cy.id
    ORDER BY b.booking_date DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gas Agency Dashboard</title>
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
                        <?php echo htmlspecialchars($agency_name); ?>
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
                    <li class="active-link">
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
                        <h2>GAS AGENCY DASHBOARD</h2>
                        <h5><?php echo htmlspecialchars($message); ?></h5>
                    </div>
                </div>
                <!-- /. ROW  -->
                <hr />
                
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-primary text-center no-boder">
                            <div class="panel-body">
                                <i class="fa fa-calendar fa-3x"></i>
                                <h3><?php echo $todayBookings; ?></h3>
                            </div>
                            <div class="panel-footer">Today's Bookings</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-green text-center no-boder">
                            <div class="panel-body">
                                <i class="fa fa-truck fa-3x"></i>
                                <h3><?php echo $pendingDeliveries; ?></h3>
                            </div>
                            <div class="panel-footer">Pending Deliveries</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-yellow text-center no-boder">
                            <div class="panel-body">
                                <i class="fa fa-database fa-3x"></i>
                                <h3><?php echo $cylinderStock; ?></h3>
                            </div>
                            <div class="panel-footer">Cylinders in Stock</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-red text-center no-boder">
                            <div class="panel-body">
                                <i class="fa fa-rupee fa-3x"></i>
                                <h3>₹<?php echo number_format($todayRevenue, 2); ?></h3>
                            </div>
                            <div class="panel-footer">Today's Revenue</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Recent Bookings
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Customer</th>
                                                <th>Cylinder Type</th>
                                                <th>Status</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentBookings as $booking): 
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
											?>
                                            <tr>
                                                <td>GA-<?php echo $booking['id']; ?></td>
                                                <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['type']); ?></td>
                                                <td><span class="label label-<?php echo $statusClass; ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                                                <td>₹<?php echo number_format($booking['total_amount'], 2); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Quick Actions
                            </div>
                            <div class="panel-body">
                                <a href="bookings.php" class="btn btn-primary btn-block"><i class="fa fa-calendar"></i> Booking</a>
                                <a href="inventory.php" class="btn btn-success btn-block"><i class="fa fa-refresh"></i> Update Inventory</a>
                                <a href="deliveries.php" class="btn btn-info btn-block"><i class="fa fa-truck"></i> Schedule Delivery</a>
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
                &copy; <?php echo date('Y'); ?> Gas Agency Management System | Developed by: Your Name
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