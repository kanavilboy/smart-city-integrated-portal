<?php
session_start();
require '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch KSEB office details
$stmt = $conn->query("SELECT * FROM kseb WHERE user_id = $user_id");
$kseb_office = $stmt->fetch(PDO::FETCH_ASSOC);
$office_name = $kseb_office['kseb_office_name'];

if (!$kseb_office) {
    die("KSEB office not found for this user.");
}

// Initialize variables
$consumer_number = '';
$name = '';
$address = '';
$contact = '';
$email = '';
$connection_type = 'domestic';
$connected_load = '';
$meter_number = '';
$status = 'active';
$errors = array();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize input
    $consumer_number = isset($_POST['consumer_number']) ? trim($_POST['consumer_number']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $connection_type = isset($_POST['connection_type']) ? trim($_POST['connection_type']) : 'domestic';
    $connected_load = isset($_POST['connected_load']) ? trim($_POST['connected_load']) : '';
    $meter_number = isset($_POST['meter_number']) ? trim($_POST['meter_number']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'active';

    // Validate inputs
    if (empty($consumer_number)) {
        $errors['consumer_number'] = 'Consumer number is required';
    } elseif (!preg_match('/^[A-Z0-9]{8,20}$/', $consumer_number)) {
        $errors['consumer_number'] = 'Invalid consumer number format (8-20 alphanumeric characters)';
    }

    if (empty($name)) {
        $errors['name'] = 'Name is required';
    }

    if (empty($address)) {
        $errors['address'] = 'Address is required';
    }

    if (empty($contact)) {
        $errors['contact'] = 'Contact number is required';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $contact)) {
        $errors['contact'] = 'Invalid phone number format';
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    if (empty($connection_type)) {
        $errors['connection_type'] = 'Connection type is required';
    }

    if (empty($connected_load)) {
        $errors['connected_load'] = 'Connected load is required';
    } elseif (!is_numeric($connected_load) || $connected_load <= 0) {
        $errors['connected_load'] = 'Connected load must be a positive number';
    }

    if (empty($meter_number)) {
        $errors['meter_number'] = 'Meter number is required';
    }

    // Check if consumer number already exists
    $stmt = $conn->prepare("SELECT id FROM kseb_customers WHERE consumer_number = ?");
    $stmt->execute(array($consumer_number));
    if ($stmt->fetch()) {
        $errors['consumer_number'] = 'Consumer number already exists';
    }

    // Check if meter number already exists
    $stmt = $conn->prepare("SELECT id FROM kseb_customers WHERE meter_number = ?");
    $stmt->execute(array($meter_number));
    if ($stmt->fetch()) {
        $errors['meter_number'] = 'Meter number already exists';
    }

    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO kseb_customers 
                (consumer_number, name, address, contact, email, connection_type, connected_load, meter_number, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute(array(
                $consumer_number,
                $name,
                $address,
                $contact,
                $email,
                $connection_type,
                $connected_load,
                $meter_number,
                $status
            ));

            // Set success message and redirect
            $_SESSION['success_message'] = 'Consumer added successfully!';
            header("Location: consumers.php");
            exit();
        } catch (PDOException $e) {
            $errors['database'] = 'Error adding consumer: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add Consumer - KSEB</title>
    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES-->
    <link href="assets/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .kseb-primary {
            background-color: #0056b3 !important;
        }
        .kseb-secondary {
            background-color: #003366 !important;
        }
        .has-error .form-control {
            border-color: #dc3545;
        }
        .help-block {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <div class="navbar navbar-inverse navbar-fixed-top kseb-secondary">
            <div class="adjust-nav">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="#">
                        <?php echo htmlspecialchars($office_name); ?> - Add Consumer
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
                        <a href="kseb_dashboard.php"><i class="fa fa-tachometer"></i>Dashboard</a>
                    </li>
                    <li class="active-link">
                        <a href="consumers.php"><i class="fa fa-users"></i>Consumers</a>
                    </li>
                    <li>
                        <a href="billing.php"><i class="fa fa-rupee"></i>Billing</a>
                    </li>
                    <li>
                        <a href="payments.php"><i class="fa fa-credit-card"></i>Payments</a>
                    </li>
                    <li>
                        <a href="reports.php"><i class="fa fa-file"></i>Reports</a>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- /. NAV SIDE  -->
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        <h2>ADD NEW ELECTRICITY CONSUMER</h2>
                        <h5>Add a new consumer to KSEB database</h5>
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
                                <i class="fa fa-user-plus"></i> Consumer Details
                            </div>
                            <div class="panel-body">
                                <form method="POST" action="add_consumer.php">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group <?php echo isset($errors['consumer_number']) ? 'has-error' : ''; ?>">
                                                <label for="consumer_number">Consumer Number *</label>
                                                <input type="text" class="form-control" id="consumer_number" name="consumer_number" 
                                                       value="<?php echo htmlspecialchars($consumer_number); ?>" required
                                                       placeholder="13 digits" pattern="[A-Z0-9]{8,20}" title="8-20 alphanumeric characters">
                                                <?php if (isset($errors['consumer_number'])): ?>
                                                    <span class="help-block"><?php echo htmlspecialchars($errors['consumer_number']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group <?php echo isset($errors['meter_number']) ? 'has-error' : ''; ?>">
                                                <label for="meter_number">Meter Number *</label>
                                                <input type="text" class="form-control" id="meter_number" name="meter_number" 
                                                       value="<?php echo htmlspecialchars($meter_number); ?>" required
                                                       placeholder="8 digits">
                                                <?php if (isset($errors['meter_number'])): ?>
                                                    <span class="help-block"><?php echo htmlspecialchars($errors['meter_number']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group <?php echo isset($errors['name']) ? 'has-error' : ''; ?>">
                                        <label for="name">Full Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($name); ?>" required
                                               placeholder="Enter consumer's full name">
                                        <?php if (isset($errors['name'])): ?>
                                            <span class="help-block"><?php echo htmlspecialchars($errors['name']); ?></span>
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
                                            <div class="form-group <?php echo isset($errors['contact']) ? 'has-error' : ''; ?>">
                                                <label for="contact">Contact Number *</label>
                                                <input type="tel" class="form-control" id="contact" name="contact" 
                                                       value="<?php echo htmlspecialchars($contact); ?>" required
                                                       placeholder="e.g., 9876543210" pattern="[0-9]{10,15}">
                                                <?php if (isset($errors['contact'])): ?>
                                                    <span class="help-block"><?php echo htmlspecialchars($errors['contact']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group <?php echo isset($errors['email']) ? 'has-error' : ''; ?>">
                                                <label for="email">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($email); ?>"
                                                       placeholder="e.g., consumer@example.com">
                                                <?php if (isset($errors['email'])): ?>
                                                    <span class="help-block"><?php echo htmlspecialchars($errors['email']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group <?php echo isset($errors['connection_type']) ? 'has-error' : ''; ?>">
                                                <label for="connection_type">Connection Type *</label>
                                                <select class="form-control" id="connection_type" name="connection_type" required>
                                                    <option value="domestic" <?php echo $connection_type === 'domestic' ? 'selected' : ''; ?>>Domestic</option>
                                                    <option value="commercial" <?php echo $connection_type === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                                                    <option value="industrial" <?php echo $connection_type === 'industrial' ? 'selected' : ''; ?>>Industrial</option>
                                                </select>
                                                <?php if (isset($errors['connection_type'])): ?>
                                                    <span class="help-block"><?php echo htmlspecialchars($errors['connection_type']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group <?php echo isset($errors['connected_load']) ? 'has-error' : ''; ?>">
                                                <label for="connected_load">Connected Load (kW) *</label>
                                                <input type="number" step="0.01" class="form-control" id="connected_load" name="connected_load" 
                                                       value="<?php echo htmlspecialchars($connected_load); ?>" required
                                                       placeholder="e.g., 5.00" min="0.01">
                                                <?php if (isset($errors['connected_load'])): ?>
                                                    <span class="help-block"><?php echo htmlspecialchars($errors['connected_load']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="status">Status *</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Save Consumer
                                        </button>
                                        <a href="consumers.php" class="btn btn-default">
                                            <i class="fa fa-arrow-left"></i> Back to Consumers
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
    <div class="footer kseb-secondary">
        <div class="row">
            <div class="col-lg-12">
                &copy; <?php echo date('Y'); ?> Kerala State Electricity Board | 
                <a href="https://kseb.in" style="color:#fff;" target="_blank">www.kseb.in</a>
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