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

// Get all cylinders in inventory for this agency
$cylinders = $conn->query("
    SELECT * FROM gas_cylinders 
    WHERE agency_id = {$agency['id']}
    ORDER BY type ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Get inventory statistics
$totalCylinders = $conn->query("
    SELECT SUM(stock_quantity) FROM gas_cylinders 
    WHERE agency_id = {$agency['id']}
")->fetchColumn();

$availableCylinders = $conn->query("
    SELECT SUM(stock_quantity) FROM gas_cylinders 
    WHERE agency_id = {$agency['id']} AND is_available = 1
")->fetchColumn();

$cylinderTypes = $conn->query("
    SELECT COUNT(*) FROM gas_cylinders 
    WHERE agency_id = {$agency['id']}
")->fetchColumn();
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Inventory Management - Gas Agency</title>
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
                        <?php echo htmlspecialchars($agency_name); ?> - Inventory
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
                    <li class="active-link">
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
                        <h2>INVENTORY MANAGEMENT</h2>
                        <h5>Manage your gas cylinder inventory</h5>
                    </div>
                </div>
                <!-- /. ROW  -->
                <hr />
                
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-primary text-center no-boder">
                            <div class="panel-body">
                                <i class="fa fa-database fa-3x"></i>
                                <h3><?php echo $totalCylinders ? $totalCylinders : '0'; ?></h3>
                            </div>
                            <div class="panel-footer">Total Cylinders</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-green text-center no-boder">
                            <div class="panel-body">
                                <i class="fa fa-check-circle fa-3x"></i>
                                <h3><?php echo $availableCylinders ? $availableCylinders : '0'; ?></h3>
                            </div>
                            <div class="panel-footer">Available</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-yellow text-center no-boder">
                            <div class="panel-body">
                                <i class="fa fa-list fa-3x"></i>
                                <h3><?php echo $cylinderTypes; ?></h3>
                            </div>
                            <div class="panel-footer">Cylinder Types</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Cylinder Inventory
                                <div class="pull-right">
                                    <a href="add_cylinder.php" class="btn btn-primary btn-xs">
                                        <i class="fa fa-plus"></i> Add Cylinder Type
                                    </a>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Cylinder Type</th>
                                                <th>Price (₹)</th>
                                                <th>In Stock</th>
                                                <th>Availability</th>
                                                <th>Description</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($cylinders as $cylinder): 
                                                $availabilityClass = $cylinder['is_available'] ? 'success' : 'danger';
                                                $availabilityText = $cylinder['is_available'] ? 'Available' : 'Not Available';
                                            ?>
                                            <tr>
                                                <td><?php echo $cylinder['id']; ?></td>
                                                <td><?php echo htmlspecialchars($cylinder['type']); ?></td>
                                                <td>₹<?php echo number_format($cylinder['price'], 2); ?></td>
                                                <td><?php echo $cylinder['stock_quantity']; ?></td>
                                                <td>
                                                    <span class="label label-<?php echo $availabilityClass; ?>">
                                                        <?php echo $availabilityText; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars(substr($cylinder['description'], 0, 30)); ?>...</td>
                                                <td>
                                                    <a href="edit_cylinder.php?id=<?php echo $cylinder['id']; ?>" class="btn btn-primary btn-xs" title="Edit">
                                                        <i class="fa fa-edit">edit</i>
                                                    </a>
                                                    <a href="delete_cylinder.php?id=<?php echo $cylinder['id']; ?>" class="btn btn-danger btn-xs" title="Delete" onclick="return confirm('Are you sure you want to delete this cylinder type?')">
                                                        <i class="fa fa-trash">Delete</i>
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