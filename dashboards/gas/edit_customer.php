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
$dealer_id = $agency['id'];

if (!$agency) {
    die("Gas agency not found for this user.");
}

// Get customer ID from URL
$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($customer_id <= 0) {
    header("Location: customers.php");
    exit();
}

// Fetch customer details
$stmt = $conn->prepare("SELECT * FROM gas_customers WHERE id = ? AND dealer_id = ?");
$stmt->execute(array($customer_id, $dealer_id));
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    $_SESSION['error_message'] = "Customer not found or you don't have permission";
    header("Location: customers.php");
    exit();
}

// Initialize variables with current customer data
$consumer_no = $customer['consumer_no'];
$full_name = $customer['full_name'];
$phone = $customer['phone'];
$address = $customer['address'];
$city = $customer['city'];
$state = $customer['state'];
$pincode = $customer['pincode'];
$errors = array();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize input
    $consumer_no = isset($_POST['consumer_no']) ? trim($_POST['consumer_no']) : '';
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';
    $state = isset($_POST['state']) ? trim($_POST['state']) : '';
    $pincode = isset($_POST['pincode']) ? trim($_POST['pincode']) : '';

    // Validate inputs
    if (empty($consumer_no)) {
        $errors['consumer_no'] = 'Consumer number is required';
    } elseif (!is_numeric($consumer_no)) {
        $errors['consumer_no'] = 'Consumer number must be numeric';
    }

    if (empty($full_name)) {
        $errors['full_name'] = 'Full name is required';
    }

    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors['phone'] = 'Invalid phone number format';
    }

    if (empty($address)) {
        $errors['address'] = 'Address is required';
    }

    if (empty($city)) {
        $errors['city'] = 'City is required';
    }

    if (empty($state)) {
        $errors['state'] = 'State is required';
    }

    if (empty($pincode)) {
        $errors['pincode'] = 'Pincode is required';
    } elseif (!preg_match('/^[0-9]{6}$/', $pincode)) {
        $errors['pincode'] = 'Invalid pincode (must be 6 digits)';
    }

    // Check if consumer number already exists for another customer
    $stmt = $conn->prepare("SELECT id FROM gas_customers WHERE consumer_no = ? AND id != ?");
    $stmt->execute(array($consumer_no, $customer_id));
    if ($stmt->fetch()) {
        $errors['consumer_no'] = 'Consumer number already exists for another customer';
    }

    // If no errors, update in database
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                UPDATE gas_customers 
                SET consumer_no = ?, 
                    full_name = ?, 
                    phone = ?, 
                    address = ?, 
                    city = ?, 
                    state = ?, 
                    pincode = ?
                WHERE id = ? AND dealer_id = ?
            ");
            
            $stmt->execute(array(
                $consumer_no,
                $full_name,
                $phone,
                $address,
                $city,
                $state,
                $pincode,
                $customer_id,
                $dealer_id
            ));

            // Set success message and redirect
            $_SESSION['success_message'] = 'Customer updated successfully!';
            header("Location: customers.php");
            exit();
        } catch (PDOException $e) {
            $errors['database'] = 'Error updating customer: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Customer - Gas Agency</title>
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
                        <?php echo htmlspecialchars($agency_name); ?> - Edit Customer
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
                        <h2>EDIT CUSTOMER</h2>
                        <h5>Update customer details</h5>
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
                                <i class="fa fa-edit"></i> Customer Details
                            </div>
                            <div class="panel-body">
                                <form method="POST" action="edit_customer.php?id=<?php echo $customer_id; ?>">
                                    <div class="form-group <?php echo isset($errors['consumer_no']) ? 'has-error' : ''; ?>">
                                        <label for="consumer_no">Consumer Number *</label>
                                        <input type="text" class="form-control" id="consumer_no" name="consumer_no" 
                                               value="<?php echo htmlspecialchars($consumer_no); ?>" required
                                               placeholder="Enter consumer number">
                                        <?php if (isset($errors['consumer_no'])): ?>
                                            <span class="help-block"><?php echo htmlspecialchars($errors['consumer_no']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group <?php echo isset($errors['full_name']) ? 'has-error' : ''; ?>">
                                        <label for="full_name">Full Name *</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo htmlspecialchars($full_name); ?>" required
                                               placeholder="Enter customer's full name">
                                        <?php if (isset($errors['full_name'])): ?>
                                            <span class="help-block"><?php echo htmlspecialchars($errors['full_name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group <?php echo isset($errors['phone']) ? 'has-error' : ''; ?>">
                                        <label for="phone">Phone Number *</label>
                                        <input type="text" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($phone); ?>" required
                                               placeholder="Enter 10-digit phone number">
                                        <?php if (isset($errors['phone'])): ?>
                                            <span class="help-block"><?php echo htmlspecialchars($errors['phone']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group <?php echo isset($errors['address']) ? 'has-error' : ''; ?>">
                                        <label for="address">Address *</label>
                                        <textarea class="form-control" id="address" name="address" rows="3" 
                                                  required placeholder="Enter full address"><?php echo htmlspecialchars($address); ?></textarea>
                                        <?php if (isset($errors['address'])): ?>
                                            <span class="help-block"><?php echo htmlspecialchars($errors['address']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group <?php echo isset($errors['city']) ? 'has-error' : ''; ?>">
                                                <label for="city">City *</label>
                                                <input type="text" class="form-control" id="city" name="city" 
                                                       value="<?php echo htmlspecialchars($city); ?>" required
                                                       placeholder="Enter city">
                                                <?php if (isset($errors['city'])): ?>
                                                    <span class="help-block"><?php echo htmlspecialchars($errors['city']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group <?php echo isset($errors['state']) ? 'has-error' : ''; ?>">
                                                <label for="state">State *</label>
                                                <input type="text" class="form-control" id="state" name="state" 
                                                       value="<?php echo htmlspecialchars($state); ?>" required
                                                       placeholder="Enter state">
                                                <?php if (isset($errors['state'])): ?>
                                                    <span class="help-block"><?php echo htmlspecialchars($errors['state']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group <?php echo isset($errors['pincode']) ? 'has-error' : ''; ?>">
                                        <label for="pincode">Pincode *</label>
                                        <input type="text" class="form-control" id="pincode" name="pincode" 
                                               value="<?php echo htmlspecialchars($pincode); ?>" required
                                               placeholder="Enter 6-digit pincode" maxlength="6">
                                        <?php if (isset($errors['pincode'])): ?>
                                            <span class="help-block"><?php echo htmlspecialchars($errors['pincode']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Update Customer
                                        </button>
                                        <a href="customers.php" class="btn btn-default">
                                            <i class="fa fa-arrow-left"></i> Back to Customers
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