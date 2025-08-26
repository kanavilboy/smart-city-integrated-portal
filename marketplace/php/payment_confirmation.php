<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

require_once '../../database.php';

// Check if booking ID is provided and valid
if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    header("Location: marketplace.php");
    exit();
}

$booking_id = (int)$_GET['booking_id'];

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get booking details with product and merchant information
    $bookingQuery = "SELECT pb.*, p.product_name, p.price, 
                    m.name as merchant_name, pp.payment_method, pp.payment_amount,
                    pp.transaction_id, pp.created_at as payment_date
                    FROM product_booking pb
                    JOIN products p ON pb.product_id = p.id
                    JOIN merchants m ON pb.merchant_id = m.id
                    JOIN product_payments pp ON pp.booking_id = pb.id
                    WHERE pb.id = ? AND pb.customer_id = ?";
    
    $bookingStmt = $conn->prepare($bookingQuery);
    $bookingStmt->execute(array($booking_id, $user_id));
    $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        header("Location: marketplace.php");
        exit();
    }
    
    // Format dates
    $booking_date = date('F j, Y', strtotime($booking['created_at']));
    $payment_date = date('F j, Y', strtotime($booking['payment_date']));
    
    // Start output buffering
    ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Confirmation - Smart City</title>
  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    .confirmation-hero {
      background-color: #f8f9fa;
      padding: 3rem 0;
      margin-bottom: 3rem;
    }
    
    .confirmation-container {
      background: white;
      border-radius: 8px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      padding: 2rem;
      margin-bottom: 2rem;
    }
    
    .confirmation-success {
      text-align: center;
      padding: 2rem;
      background: #e8f5e9;
      border-radius: 8px;
      margin-bottom: 2rem;
    }
    
    .confirmation-success .icon {
      font-size: 4rem;
      color: #00d289;
      margin-bottom: 1rem;
    }
    
    .order-summary {
      border: 1px solid #eee;
      border-radius: 8px;
      padding: 1.5rem;
    }
    
    .order-details {
      margin-bottom: 2rem;
    }
    
    .order-details .row {
      margin-bottom: 1rem;
    }
    
    .order-details .col-md-6 {
      margin-bottom: 0.5rem;
    }
    
    .product-image {
      max-height: 120px;
      width: auto;
      margin-right: 1rem;
    }
    
    .btn-print {
      margin-right: 1rem;
    }
  </style>
</head>
<body>

  <!-- Header -->
  <header>
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm">
      <div class="container">
        <a class="navbar-brand" href="../../smartcity.php"><span class="text-primary">Smart</span>-City</a>

        <form action="search_results.php" method="GET">
		  <div class="input-group input-navbar">
			<div class="input-group-prepend">
			  <span class="input-group-text" id="icon-addon1"><span class="mai-search"></span></span>
			</div>
			<input type="text" name="query" class="form-control" placeholder="Search products or merchants..." required>
			<div class="input-group-append">
			  <button type="submit" class="btn btn-primary">Search</button>
			</div>
		  </div>
		</form>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupport">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupport">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a class="nav-link" href="marketplace.php">Marketplace</a></li>
            <li class="nav-item"><a class="nav-link" href="merchants.php">Merchants</a></li>
            <li class="nav-item"><a class="nav-link" href="categories.php">Categories</a></li>
            <li class="nav-item"><a class="nav-link" href="history.php">Cart</a></li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <!-- Confirmation Hero Section -->
  <section class="confirmation-hero">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-12">
          <h1 class="mb-0">Order Confirmation</h1>
        </div>
      </div>
    </div>
  </section>

  <!-- Confirmation Section -->
  <section class="mb-5">
    <div class="container">
      <div class="confirmation-success">
        <div class="icon">
          <span class="mai-checkmark-circle"></span>
        </div>
        <h2>Thank You for Your Order!</h2>
        <p class="lead">Your payment was successful and your order has been placed.</p>
        <p>Your order confirmation number is: <strong>#<?php echo $booking_id; ?></strong></p>
        <p>A confirmation email has been sent to <strong><?php echo htmlspecialchars($booking['customer_email']); ?></strong></p>
      </div>
      
      <div class="confirmation-container">
        <h3 class="mb-4">Order Summary</h3>
        
        <div class="order-summary">
          <div class="d-flex align-items-center mb-4">
            <img src="<?php echo htmlspecialchars($booking['product_image']); ?>" alt="<?php echo htmlspecialchars($booking['product_name']); ?>" class="product-image">
            <div>
              <h4><?php echo htmlspecialchars($booking['product_name']); ?></h4>
              <p class="text-muted">Sold by: <?php echo htmlspecialchars($booking['merchant_name']); ?></p>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="d-flex justify-content-between">
                <span>Order Date:</span>
                <span><?php echo $booking_date; ?></span>
              </div>
              <div class="d-flex justify-content-between">
                <span>Order Status:</span>
                <span class="text-capitalize"><?php echo htmlspecialchars($booking['status']); ?></span>
              </div>
            </div>
            <div class="col-md-6">
              <div class="d-flex justify-content-between">
                <span>Payment Method:</span>
                <span><?php echo htmlspecialchars($booking['payment_method']); ?></span>
              </div>
              <div class="d-flex justify-content-between">
                <span>Payment Status:</span>
                <span class="text-capitalize"><?php echo htmlspecialchars($booking['payment_status']); ?></span>
              </div>
            </div>
          </div>
          
          <hr>
          
          <div class="row">
            <div class="col-md-6">
              <h5>Delivery Address</h5>
              <p><?php echo nl2br(htmlspecialchars($booking['customer_address'])); ?></p>
            </div>
            <div class="col-md-6">
              <h5>Payment Details</h5>
              <div class="d-flex justify-content-between">
                <span>Transaction ID:</span>
                <span><?php echo htmlspecialchars($booking['transaction_id']); ?></span>
              </div>
              <div class="d-flex justify-content-between">
                <span>Payment Date:</span>
                <span><?php echo $payment_date; ?></span>
              </div>
              <div class="d-flex justify-content-between">
                <span>Amount Paid:</span>
                <span>₹<?php echo number_format($booking['price'], 2); ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="text-center">
        <button onclick="window.print()" class="btn btn-outline-primary btn-print">
          <span class="mai-print"></span> Print Receipt
        </button>
        <a href="marketplace.php" class="btn btn-primary">
          <span class="mai-cart"></span> Continue Shopping
        </a>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="page-footer">
    <div class="container">
      <div class="row px-md-3">
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>Company</h5>
          <ul class="footer-menu">
            <li><a href="#">About Us</a></li>
            <li><a href="#">Career</a></li>
            <li><a href="#">Merchants</a></li>
            <li><a href="#">Protection</a></li>
          </ul>
        </div>
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>More</h5>
          <ul class="footer-menu">
            <li><a href="#">Terms & Conditions</a></li>
            <li><a href="#">Privacy Policy</a></li>
            <li><a href="#">Advertise</a></li>
            <li><a href="#">Join Us</a></li>
          </ul>
        </div>
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>Our Services</h5>
          <ul class="footer-menu">
            <li><a href="#">Marketplace</a></li>
            <li><a href="#">Health Services</a></li>
            <li><a href="#">Public Services</a></li>
          </ul>
        </div>
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>Contact</h5>
          <p class="footer-link mt-2">JK Smart City</p>
          <a href="#" class="footer-link">jkmarket@gmail.com</a>
          <h5 class="mt-3">Social Media</h5>
          <div class="footer-sosmed mt-3">
            <a href="#"><span class="mai-logo-facebook-f"></span></a>
            <a href="#"><span class="mai-logo-twitter"></span></a>
            <a href="#"><span class="mai-logo-google-plus-g"></span></a>
            <a href="#"><span class="mai-logo-instagram"></span></a>
            <a href="#"><span class="mai-logo-linkedin"></span></a>
          </div>
        </div>
      </div>
      <hr>
      <p id="copyright">Copyright &copy; 2025 <a href="#">JK Smart City</a>. All rights reserved.</p>
    </div>
  </footer>

  <script src="../assets/js/jquery-3.5.1.min.js"></script>
  <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
    // End output buffering and send output
    $output = ob_get_clean();
    echo $output;

} catch (PDOException $e) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Error loading order confirmation: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
    exit();
}
?>