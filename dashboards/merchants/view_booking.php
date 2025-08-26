<?php
session_start();
require '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch merchant details based on logged-in user
$stmt = $conn->prepare("SELECT * FROM merchants WHERE user_id = ?");
$stmt->execute(array($user_id));
$merchant = $stmt->fetch(PDO::FETCH_ASSOC);
$merchant_name = $merchant['name'];

if (!$merchant) {
    die("Merchant not found for this user.");
}

// Handle booking confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    try {
        // Get booking details
        $stmt = $conn->prepare("SELECT * FROM product_booking WHERE id = ? AND merchant_id = ?");
        $stmt->execute(array($booking_id, $merchant['id']));
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($booking) {
            // Update booking status
            $updateStmt = $conn->prepare("UPDATE product_booking SET status = 'Confirmed' WHERE id = ?");
            $updateStmt->execute(array($booking_id));
            
            // Create confirmation message
            $message = "Your booking for product {$booking['product_name']} has been confirmed by $merchant_name. Booking date: {$booking['booking_date']} at {$booking['booking_time']}.";
            
            // Insert message into database
            $insertMsg = $conn->prepare("INSERT INTO message (customer_id, sender, message, sent_date) VALUES (?, ?, ?, NOW())");
            $insertMsg->execute(array($booking['customer_id'], $merchant_name, $message));
            
            $_SESSION['success'] = "Booking confirmed and customer notified!";
            header("Location: view_booking.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error confirming booking: " . $e->getMessage();
        header("Location: view_booking.php");
        exit();
    }
}

// Handle booking deletion
if (isset($_GET['delete'])) {
    $booking_id = (int)$_GET['delete'];
    
    try {
        // Get booking details before deletion
        $stmt = $conn->prepare("SELECT * FROM product_booking WHERE id = ? AND merchant_id = ?");
        $stmt->execute(array($booking_id, $merchant['id']));
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($booking) {
            // Create cancellation message
            $message = "We regret to inform you that your booking for product {$booking['product_name']} has been cancelled by $merchant_name. Please contact us for any queries.";
            
            // Insert message into database
            $insertMsg = $conn->prepare("INSERT INTO message (customer_id, sender, message, sent_date) VALUES (?, ?, ?, NOW())");
            $insertMsg->execute(array($booking['customer_id'], $merchant_name, $message));
            
            // Delete from product_payments first (if exists)
            $deletePayment = $conn->prepare("DELETE FROM product_payments WHERE booking_id = ?");
            $deletePayment->execute(array($booking_id));
            
            // Then delete from product_booking
            $deleteBooking = $conn->prepare("DELETE FROM product_booking WHERE id = ?");
            $deleteBooking->execute(array($booking_id));
            
            $_SESSION['success'] = "Booking deleted and customer notified!";
            header("Location: view_booking.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting booking: " . $e->getMessage();
        header("Location: view_booking.php");
        exit();
    }
}

// Fetch bookings for the merchant with product details
$stmt = $conn->prepare("
    SELECT 
        pb.id AS booking_id,
        pb.customer_id,
        pb.customer_name,
        pb.product_id,
        p.product_name AS product_name,
        pb.booking_date,
        pb.booking_time,
        pb.status
    FROM 
        product_booking pb
    JOIN 
        products p ON pb.product_id = p.id
    WHERE 
        pb.merchant_id = ?
");
$stmt->execute(array($merchant['id']));
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>View Bookings</title>
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
                        <?php echo htmlspecialchars($merchant_name); ?>
                    </a>
                </div>
                <span class="logout-spn">
                    <a href="../../login.php" style="color:#fff;">LOGOUT</a>
                </span>
            </div>
        </div>
        <!-- /. NAV TOP  -->
        <nav class="navbar-default navbar-side" role="navigation">
            <div class="sidebar-collapse">
                <ul class="nav" id="main-menu">
                    <li>
                        <a href="merchant_dashboard.php"><i class="fa fa-desktop"></i>Dashboard</a>
                    </li>
                    <li>
                        <a href="edit_profile.php"><i class="fa fa-user"></i>Edit Profile</a>
                    </li>
                    <li class="active-link">
                        <a href="view_booking.php"><i class="fa fa-calendar"></i>View Bookings</a>
                    </li>
                    <li>
                        <a href="add_product.php"><i class="fa fa-plus"></i>Add Product</a>
                    </li>
                    <li>
                        <a href="view_products.php"><i class="fa fa-list"></i>View Products</a>
                    </li>
                    <li>
                        <a href="add_job.php"><i class="fa fa-briefcase"></i>Add Job</a>
                    </li>
                    <li>
                        <a href="job_requests.php"><i class="fa fa-tasks"></i>Job Requests</a>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- /. NAV SIDE  -->
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-lg-12">
                        <h2>View Bookings</h2>
                    </div>
                </div>
                <hr />
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Booking Details
                            </div>
                            <div class="panel-body">
                                <?php if (empty($bookings)): ?>
                                    <div class="alert alert-info">
                                        No bookings found.
                                    </div>
                                <?php else: ?>
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Customer Name</th>
                                                <th>Product Name</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Confirm</th>
                                                <th>Delete</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($bookings as $booking): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($booking['product_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                                                    <td><?php echo htmlspecialchars($booking['status']); ?></td>
                                                    <td>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                            <button type="submit" name="confirm_booking" class="btn btn-success btn-sm" <?php echo $booking['status'] === 'Confirmed' ? 'disabled' : ''; ?>>
                                                                <?php echo $booking['status'] === 'Confirmed' ? 'Confirmed' : 'Confirm'; ?>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <td>
                                                        <a href="?delete=<?php echo $booking['booking_id']; ?>" onclick="return confirm('Are you sure you want to delete this booking? This will notify the customer.');" class="btn btn-danger btn-sm">Delete</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
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
                &copy; 2023 yourdomain.com | Design by: <a href="http://binarytheme.com" style="color:#fff;" target="_blank">www.binarytheme.com</a>
            </div>
        </div>
    </div>
    <!-- /. WRAPPER  -->
    <!-- SCRIPTS -AT THE BOTOM TO REDUCE THE LOAD TIME-->
    <!-- JQUERY SCRIPTS -->
    <script src="assets/js/jquery-1.10.2.js"></script>
    <!-- BOOTSTRAP SCRIPTS -->
    <script src="assets/js/bootstrap.min.js"></script>
    <!-- CUSTOM SCRIPTS -->
    <script src="assets/js/custom.js"></script>
</body>
</html>