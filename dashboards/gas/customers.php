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
$dealer_id = $agency['id'];

if (!$agency) {
    die("Gas agency not found for this user.");
}

// Handle delete action if requested
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // Verify customer belongs to this user before deleting
    $stmt = $conn->prepare("DELETE FROM gas_customers WHERE id = ? AND dealer_id = ?");
    $stmt->execute(array($delete_id, $dealer_id));
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message'] = "Customer deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete customer or customer not found";
    }
    
    header("Location: customers.php");
    exit();
}

// Get all customers with their booking counts
$customers = $conn->query("
    SELECT c.*, 
           COUNT(b.id) as total_bookings,
           MAX(b.booking_date) as last_booking_date
    FROM gas_customers c
    LEFT JOIN gas_bookings b ON c.id = b.customer_id AND b.agency_id = {$agency['id']}
    GROUP BY c.id
    ORDER BY c.full_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Get customer statistics
$totalCustomers = $conn->query("SELECT COUNT(*) FROM gas_customers")->fetchColumn();
$activeCustomers = $conn->query("
    SELECT COUNT(DISTINCT customer_id) 
    FROM gas_bookings 
    WHERE agency_id = {$agency['id']} AND 
          booking_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
")->fetchColumn();
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Customers Management - Gas Agency</title>
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
                        <?php echo htmlspecialchars($agency_name); ?> - Customers
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
                    <li class="active-link">
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
                        <h2>CUSTOMERS MANAGEMENT</h2>
                        <h5>Manage all gas agency customers</h5>
                    </div>
                </div>
                <!-- /. ROW  -->
                <hr />
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success_message']; ?>
                        <?php unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <?php echo $_SESSION['error_message']; ?>
                        <?php unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-primary text-center no-boder">
                            <div class="panel-body">
                                <i class="fa fa-users fa-3x"></i>
                                <h3><?php echo $totalCustomers; ?></h3>
                            </div>
                            <div class="panel-footer">Total Customers</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-green text-center no-boder">
                            <div class="panel-body">
                                <i class="fa fa-check-circle fa-3x"></i>
                                <h3><?php echo $activeCustomers; ?></h3>
                            </div>
                            <div class="panel-footer">Active Customers</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Customer List
                                <div class="pull-right">
                                    <a href="add_customer.php" class="btn btn-primary btn-xs">
                                        <i class="fa fa-plus"></i> Add Customer
                                    </a>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Consumer No</th>
                                                <th>Customer Name</th>
                                                <th>Phone</th>
                                                <th>Address</th>
                                                <th>City</th>
                                                <th>Total Bookings</th>
                                                <th>Last Booking</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($customers as $customer): 
                                                // Determine customer activity class
                                                $activityClass = ($customer['total_bookings'] > 0) ? 'success' : 'warning';
                                            ?>
                                            <tr>
                                                <td><?php echo $customer['consumer_no']; ?></td>
                                                <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($customer['address'], 0, 30)); ?></td>
                                                <td><?php echo htmlspecialchars($customer['city']); ?></td>
                                                <td><span class="label label-<?php echo $activityClass; ?>"><?php echo $customer['total_bookings']; ?></span></td>
                                                <td>
                                                    <?php if ($customer['last_booking_date']): ?>
                                                        <?php echo date('d M Y', strtotime($customer['last_booking_date'])); ?>
                                                    <?php else: ?>
                                                        Never
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="edit_customer.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary btn-xs" title="Edit">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </a>
                                                    <a href="customers.php?delete_id=<?php echo $customer['id']; ?>" class="btn btn-danger btn-xs" title="Delete" onclick="return confirm('Are you sure you want to delete this customer? All related bookings will also be deleted.')">
                                                        <i class="fa fa-trash"></i> Delete
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