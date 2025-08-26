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

// Check if booking ID is provided
if (!isset($_GET['id'])) {
    header("Location: bookings.php");
    exit();
}

$booking_id = $_GET['id'];

// Fetch booking details
$booking_stmt = $conn->prepare("
    SELECT b.*, c.full_name, c.phone, c.address, cy.type as cylinder_type
    FROM gas_bookings b
    JOIN gas_customers c ON b.customer_id = c.id
    JOIN gas_cylinders cy ON b.cylinder_id = cy.id
    WHERE b.id = ? AND b.agency_id = ?
");
$booking_stmt->execute(array($booking_id, $agency['id']));
$booking = $booking_stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die("Booking not found or you don't have permission to access it.");
}

// Fetch all cylinder types for dropdown
$cylinders = $conn->query("SELECT * FROM gas_cylinders WHERE agency_id = {$agency['id']}")->fetchAll(PDO::FETCH_ASSOC);

// Check if delivery already exists for this booking
$delivery_stmt = $conn->prepare("SELECT * FROM gas_deliveries WHERE booking_id = ?");
$delivery_stmt->execute(array($booking_id));
$existing_delivery = $delivery_stmt->fetch(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_booking'])) {
        // Handle regular booking update
        $cylinder_id = $_POST['cylinder_id'];
        $quantity = $_POST['quantity'];
        $status = $_POST['status'];
        $payment_status = $_POST['payment_status'];
        //$notes = $_POST['notes'];
        
        // Get cylinder price
        $cylinder_stmt = $conn->prepare("SELECT price FROM gas_cylinders WHERE id = ?");
        $cylinder_stmt->execute(array($cylinder_id));
        $cylinder = $cylinder_stmt->fetch(PDO::FETCH_ASSOC);
        
        $total_amount = $cylinder['price'] * $quantity;
        
        // Update booking
        $update_stmt = $conn->prepare("
            UPDATE gas_bookings 
            SET cylinder_id = ?, quantity = ?, total_amount = ?, status = ?, 
                payment_status = ?
            WHERE id = ?
        ");
        
        if ($update_stmt->execute(array(
            $cylinder_id, $quantity, $total_amount, $status, 
            $payment_status, $booking_id
        ))) {
            $_SESSION['success_message'] = "Booking updated successfully!";
            header("Location: bookings.php");
            exit();
        } else {
            $error_message = "Failed to update booking. Please try again.";
        }
    } elseif (isset($_POST['schedule_delivery'])) {
        // Handle delivery scheduling
        $delivery_date = $_POST['delivery_date'];
        $delivery_time = $_POST['delivery_time'];
        $delivery_address = $_POST['delivery_address'];
        
        try {
            $conn->beginTransaction();
            
            // Update booking status to confirmed
            $update_booking_stmt = $conn->prepare("
                UPDATE gas_bookings 
                SET status = 'confirmed'
                WHERE id = ?
            ");
            $update_booking_stmt->execute(array($booking_id));
            
            // Insert or update delivery record
            if ($existing_delivery) {
                $delivery_stmt = $conn->prepare("
                    UPDATE gas_deliveries 
                    SET delivery_date = ?, delivery_time = ?, 
                        delivery_address = ?, status = 'scheduled'
                    WHERE booking_id = ?
                ");
                $delivery_stmt->execute(array(
                    $delivery_date, $delivery_time, 
                    $delivery_address, $booking_id
                ));
            } else {
                $delivery_stmt = $conn->prepare("
                    INSERT INTO gas_deliveries 
                    (booking_id, delivery_date, delivery_time, 
                     delivery_address, status)
                    VALUES (?, ?, ?, ?, 'scheduled')
                ");
                $delivery_stmt->execute(array(
                    $booking_id, $delivery_date, $delivery_time, 
                    $delivery_address
                ));
            }
            
            $conn->commit();
            $_SESSION['success_message'] = "Delivery scheduled successfully!";
            header("Location: bookings.php");
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $error_message = "Failed to schedule delivery: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Booking - Gas Agency</title>
    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES-->
    <link href="assets/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <!-- DATE PICKER CSS -->
    <link href="assets/css/bootstrap-datepicker.min.css" rel="stylesheet" />
    <!-- TIME PICKER CSS -->
    <link href="assets/css/bootstrap-timepicker.min.css" rel="stylesheet" />
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
                        <?php echo htmlspecialchars($agency_name); ?> - Edit Booking
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
                        <h2>EDIT BOOKING</h2>
                        <h5>Update booking details and status</h5>
                    </div>
                </div>
                <!-- /. ROW  -->
                <hr />
                
                <div class="row">
                    <div class="col-md-12">
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="panel panel-default">
							<div class="panel-heading text-center">
								<h3 class="m-0">Gas Booking Invoice</h3>
								<small class="text-muted">Booking #GA-<?php echo $booking['id']; ?></small>
							</div>

							<div class="panel-body">
								<div class="row">
									<div class="col-md-6">
										<h4>Customer Details</h4>
										<p><strong>Name:</strong> <?php echo htmlspecialchars($booking['full_name']); ?></p>
										<p><strong>Phone:</strong> <?php echo htmlspecialchars($booking['phone']); ?></p>
										<p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($booking['address'])); ?></p>
									</div>

									<div class="col-md-6 text-right">
										<h4>Booking Details</h4>
										<p><strong>Date:</strong> <?php echo date('d M Y h:i A', strtotime($booking['booking_date'])); ?></p>
										<p><strong>Status:</strong> <span class="label label-<?php echo ($booking['status'] == 'delivered') ? 'success' : 'warning'; ?>"><?php echo ucfirst($booking['status']); ?></span></p>
										<p><strong>Payment:</strong> <span class="label label-<?php echo ($booking['payment_status'] == 'paid') ? 'success' : 'danger'; ?>"><?php echo ucfirst($booking['payment_status']); ?></span></p>
									</div>
									
									<div class="col-md-6 text-right">
										<h4>Total Amount</h4>
										<h2 class="text-danger">₹<?php echo number_format($booking['total_amount'], 2); ?></h2>
										<p><small class="text-muted">Amount includes all taxes and charges.</small></p>
									</div>
								</div>
								<div class="row">
								</div>
							</div>
						</div>

                        
                        <!-- Delivery Scheduling Section -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Schedule Delivery
                                <?php if ($existing_delivery): ?>
                                    <span class="label label-info pull-right">
                                        Delivery <?php echo ucfirst(str_replace('_', ' ', $existing_delivery['status'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="panel-body">
                                <form method="post" action="">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Delivery Date *</label>
                                                <div class="input-group date" id="deliveryDatePicker">
													<input type="text" class="form-control" name="delivery_date" 
														   value="<?php echo $existing_delivery ? date('Y-m-d', strtotime($existing_delivery['delivery_date'])) : date('Y-m-d'); ?>" required>
													<span class="input-group-addon">
														<i class="fa fa-calendar"></i>
													</span>
												</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Delivery Time *</label>
                                                <div class="input-group bootstrap-timepicker timepicker">
                                                    <input type="text" class="form-control" name="delivery_time" 
                                                           value="<?php echo $existing_delivery ? $existing_delivery['delivery_time'] : '09:00'; ?>" required>
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-clock-o"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Delivery Address *</label>
                                                <textarea class="form-control" name="delivery_address" rows="1" required><?php 
                                                    echo $existing_delivery ? htmlspecialchars($existing_delivery['delivery_address']) : htmlspecialchars($booking['address']);
                                                ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="schedule_delivery" class="btn btn-success">
                                        <i class="fa fa-truck"></i> <?php echo $existing_delivery ? 'Update Delivery Schedule' : 'Schedule Delivery'; ?>
                                    </button>
                                    
                                    <?php if ($existing_delivery): ?>
                                        <a href="deliveries.php?id=<?php echo $existing_delivery['id']; ?>" class="btn btn-info">
                                            <i class="fa fa-eye"></i> View Delivery Details
                                        </a>
                                    <?php endif; ?>
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
    <!-- DATE PICKER SCRIPTS -->
    <script src="assets/js/bootstrap-datepicker.min.js"></script>
    <!-- TIME PICKER SCRIPTS -->
    <script src="assets/js/bootstrap-timepicker.min.js"></script>
    <!-- CUSTOM SCRIPTS -->
    <script src="assets/js/custom.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize date picker
        $('#deliveryDatePicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true,
            startDate: '0d'
        });
        
        // Initialize time picker
        $('input[name="delivery_time"]').timepicker({
            showMeridian: false,
            minuteStep: 15,
            defaultTime: '09:00'
        });
    });
    </script>
</body>
</html>