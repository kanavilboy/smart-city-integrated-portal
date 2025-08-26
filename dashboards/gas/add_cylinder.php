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

// Initialize variables
$type = '';
$price = '';
$stock = '';
$description = '';
$is_available = 1;
$errors = array();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize input
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    $price = isset($_POST['price']) ? trim($_POST['price']) : '';
    $stock = isset($_POST['stock']) ? trim($_POST['stock']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    // Validate inputs
    if (empty($type)) {
        $errors['type'] = 'Cylinder type is required';
    }
    
    if (empty($price) || !is_numeric($price) || $price <= 0) {
        $errors['price'] = 'Valid price is required';
    }
    
    if (empty($stock) || !is_numeric($stock) || $stock < 0) {
        $errors['stock'] = 'Valid stock quantity is required';
    }

    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT INTO gas_cylinders 
                                   (type, price, description, agency_id, stock_quantity, is_available) 
                                   VALUES (:type, :price, :description, :agency_id, :stock, :available)");
            
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':agency_id', $agency_id);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':available', $is_available, PDO::PARAM_INT);
            
            $stmt->execute();

            // Set success message and redirect
            $_SESSION['success_message'] = 'Cylinder type added successfully!';
            header("Location: inventory.php");
            exit();
        } catch (PDOException $e) {
            $errors['database'] = 'Error adding cylinder: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add Cylinder - Gas Agency</title>
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
                        <?php echo htmlspecialchars($agency_name); ?> - Add Cylinder
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
                        <h2>ADD NEW CYLINDER TYPE</h2>
                        <h5>Add a new gas cylinder to your inventory</h5>
                    </div>
                </div>
                <!-- /. ROW  -->
                <hr />
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <strong>Error!</strong> Please fix the following issues:
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-8 col-md-offset-2">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-plus-circle"></i> Cylinder Details
                            </div>
                            <div class="panel-body">
                                <form method="POST" action="add_cylinder.php">
                                    <div class="form-group <?php echo isset($errors['type']) ? 'has-error' : ''; ?>">
                                        <label for="type">Cylinder Type *</label>
                                        <input type="text" class="form-control" id="type" name="type" 
                                               value="<?php echo htmlspecialchars($type); ?>" required
                                               placeholder="e.g., 14.2kg Domestic, 5kg Commercial">
                                        <?php if (isset($errors['type'])): ?>
                                            <span class="help-block"><?php echo htmlspecialchars($errors['type']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group <?php echo isset($errors['price']) ? 'has-error' : ''; ?>">
                                        <label for="price">Price (₹) *</label>
                                        <div class="input-group">
                                            <span class="input-group-addon">₹</span>
                                            <input type="number" step="0.01" min="0" class="form-control" id="price" 
                                                   name="price" value="<?php echo htmlspecialchars($price); ?>" required
                                                   placeholder="Enter price per cylinder">
                                        </div>
                                        <?php if (isset($errors['price'])): ?>
                                            <span class="help-block"><?php echo htmlspecialchars($errors['price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group <?php echo isset($errors['stock']) ? 'has-error' : ''; ?>">
                                        <label for="stock">Initial Stock Quantity *</label>
                                        <input type="number" min="0" class="form-control" id="stock" name="stock" 
                                               value="<?php echo htmlspecialchars($stock); ?>" required
                                               placeholder="Enter initial stock count">
                                        <?php if (isset($errors['stock'])): ?>
                                            <span class="help-block"><?php echo htmlspecialchars($errors['stock']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea class="form-control" id="description" name="description" 
                                                  rows="3" placeholder="Optional cylinder description"><?php echo htmlspecialchars($description); ?></textarea>
                                    </div>
                                    
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="is_available" <?php echo $is_available ? 'checked' : ''; ?>> 
                                            Available for immediate booking
                                        </label>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Save Cylinder
                                        </button>
                                        <a href="inventory.php" class="btn btn-default">
                                            <i class="fa fa-arrow-left"></i> Back to Inventory
                                        </a>
                                    </div>
                                </form>
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