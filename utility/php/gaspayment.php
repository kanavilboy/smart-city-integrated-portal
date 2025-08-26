<?php
session_start();
require '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Initialize variables
$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;
$booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : '';
$consumer = isset($_GET['consumer']) ? htmlspecialchars($_GET['consumer']) : '';
$paymentStatus = "";
$payment_success = false;

// Fetch booking details if booking_id is provided
if ($booking_id) {
    $stmt = $conn->prepare("
        SELECT gb.*, gc.consumer_no, gc.full_name 
        FROM gas_bookings gb
        JOIN gas_customers gc ON gb.customer_id = gc.id
        WHERE gb.id = ?
    ");
    $stmt->execute(array($booking_id));
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($booking) {
        $amount = $booking['total_amount'];
        $consumer = $booking['consumer_no'];
		$customer_id = $booking['customer_id'];
    } else {
        $paymentStatus = "Invalid booking ID or you don't have permission to access this booking.";
    }
}

// Process payment form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['make_payment'])) {
    $cardNumber = isset($_POST['cardNumber']) ? $_POST['cardNumber'] : '';
    $expiry = isset($_POST['expiry']) ? $_POST['expiry'] : '';
    $cvv = isset($_POST['cvv']) ? $_POST['cvv'] : '';
    $cardHolder = isset($_POST['cardHolder']) ? $_POST['cardHolder'] : '';
    
    // Basic validation
    if (strlen($cardNumber) >= 12 && strlen($cvv) >= 3 && !empty($expiry) && !empty($cardHolder)) {
        try {
            $conn->beginTransaction();
            
            // Update payment status in gas_bookings
            $stmt = $conn->prepare("
                UPDATE gas_bookings 
                SET payment_status = 'paid', status = 'pending', booking_date = NOW() 
                WHERE id = ?
            ");
            $stmt->execute(array($booking_id));
            
            // Record payment transaction
            $stmt = $conn->prepare("
                INSERT INTO gas_payment_transactions 
                (booking_id, customer_id, amount, payment_method, status, payment_date)
                VALUES (?, ?, ?, 'credit_card', 'success', NOW())
            ");
            $stmt->execute(array(
                $booking_id,
				$customer_id,
                $amount
            ));
            
            $conn->commit();
            
            $payment_success = true;
            $paymentStatus = "Payment of Rs. " . number_format($amount, 2) . " successful for Booking ID: " . $booking_id;
            
        } catch (Exception $e) {
            $conn->rollBack();
            $paymentStatus = "Payment failed. Error: " . $e->getMessage();
        }
    } else {
        $paymentStatus = "Payment failed. Please check your card details.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Gas Booking Payment</title>
  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    .payment-card {
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }
    .payment-header {
      background-color: #00D9A5;
      color: white;
      border-radius: 10px 10px 0 0 !important;
      padding: 15px;
    }
    .payment-body {
      padding: 25px;
    }
    .payment-amount {
      font-size: 24px;
      font-weight: bold;
      color: #00D9A5;
    }
    .payment-details {
      background-color: #f8f9fa;
      border-radius: 5px;
      padding: 15px;
      margin-bottom: 20px;
    }
    .btn-pay {
      background-color: #00D9A5;
      border-color: #00D9A5;
      font-weight: bold;
    }
    .btn-pay:hover {
      background-color: #00C095;
      border-color: #00C095;
    }
  </style>
</head>
<body>
  <!-- Back to top button -->
  <div class="back-to-top"></div>

  <header>
    

    <nav class="navbar navbar-expand-lg navbar-light shadow-sm">
      <div class="container">
        <a class="navbar-brand" href="../../smartcity.php"><span class="text-primary">Smart</span>-City</a>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupport" aria-controls="navbarSupport" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupport">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item">
              <a class="nav-link" href="utility.php">Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="kseb.php">KSEB bill</a>
			</li>
            <li class="nav-item">
              <a class="nav-link" href="gas.php">LPG booking</a>
            </li>
           
          </ul>
        </div> <!-- .navbar-collapse -->
      </div> <!-- .container -->
    </nav>
  </header>

<div class="page-section">
  <div class="container">
    <h1 class="text-center">Gas Booking Payment</h1>

    <div class="row justify-content-center mt-5">
      <div class="col-lg-8">
        <?php if (!empty($paymentStatus)): ?>
          <div class="alert <?php echo $payment_success ? 'alert-success' : 'alert-danger'; ?>">
            <?php echo $paymentStatus; ?>
            <?php if ($payment_success): ?>
              <div class="mt-3">
                <a href="gas.php" class="btn btn-primary">
                  Return to Gas Booking
                </a>
                <a href="gas_payment_receipt.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-secondary ml-2">
                  View Receipt
                </a>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($booking_id && !$payment_success): ?>
          <div class="card payment-card">
            <div class="card-header payment-header">
              <h3 class="card-title text-center mb-0">Complete Your Gas Booking Payment</h3>
            </div>
            <div class="card-body payment-body">
              <div class="payment-details">
                <div class="row">
                  <div class="col-md-6">
                    <p><strong>Booking ID:</strong> <?php echo $booking_id; ?></p>
                    <p><strong>Consumer Number:</strong> <?php echo $consumer; ?></p>
                    <?php if (isset($booking['full_name'])): ?>
                      <p><strong>Customer Name:</strong> <?php echo $booking['full_name']; ?></p>
                    <?php endif; ?>
                  </div>
                  <div class="col-md-6 text-right">
                    <p class="payment-amount">Rs. <?php echo number_format($amount, 2); ?></p>
                    <p><small>Amount to be paid</small></p>
                  </div>
                </div>
              </div>

              <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?booking_id=<?php echo $booking_id; ?>">
                <h5 class="mb-4">Payment Information</h5>
                
                <div class="form-group">
                  <label for="cardHolder">Card Holder Name</label>
                  <input type="text" class="form-control" id="cardHolder" name="cardHolder" 
                         placeholder="Name as shown on card" required>
                </div>
                
                <div class="form-group">
                  <label for="cardNumber">Card Number</label>
                  <input type="text" class="form-control" id="cardNumber" name="cardNumber" 
                         placeholder="1234 5678 9012 3456" required>
                </div>
                
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="expiry">Expiry Date</label>
                      <input type="text" class="form-control" id="expiry" name="expiry" 
                             placeholder="MM/YY" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="cvv">CVV</label>
                      <input type="text" class="form-control" id="cvv" name="cvv" 
                             placeholder="123" required>
                    </div>
                  </div>
                </div>
                
                <button type="submit" name="make_payment" class="btn btn-pay btn-block mt-4">
                  Pay Rs. <?php echo number_format($amount, 2); ?>
                </button>
              </form>
            </div>
          </div>
        <?php elseif (!$booking_id): ?>
          <div class="alert alert-warning">
            <p>No booking specified for payment. Please go back to your gas booking page and click on the payment link.</p>
            <a href="gas.php" class="btn btn-primary">Go to Gas Booking</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<footer class="page-footer">
    <div class="container">
      <div class="row px-md-3">
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>Company</h5>
          <ul class="footer-menu">
            <li><a href="#">About Us</a></li>
            <li><a href="#">Career</a></li>
            <li><a href="#">Editorial Team</a></li>
            <li><a href="#">Protection</a></li>
          </ul>
        </div>
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>More</h5>
          <ul class="footer-menu">
            <li><a href="#">Terms & Condition</a></li>
            <li><a href="#">Privacy</a></li>
            <li><a href="#">Advertise</a></li>
            <li><a href="#">Join us</a></li>
          </ul>
        </div>
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>Our services</h5>
          <ul class="footer-menu">
            <li><a href="#">Marketplace</a></li>
            <li><a href="#">Health services</a></li>
            <li><a href="#">Public services</a></li>
          </ul>
        </div>
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>Contact</h5>
          <p class="footer-link mt-2">JK</p>
          <a href="#" class="footer-link">jk@gmail.com</a>

          <h5 class="mt-3">Social Media</h5>
          <div class="footer-sosmed mt-3">
            <a href="#" target="_blank"><span class="mai-logo-facebook-f"></span></a>
            <a href="#" target="_blank"><span class="mai-logo-twitter"></span></a>
            <a href="#" target="_blank"><span class="mai-logo-google-plus-g"></span></a>
            <a href="#" target="_blank"><span class="mai-logo-instagram"></span></a>
            <a href="#" target="_blank"><span class="mai-logo-linkedin"></span></a>
          </div>
        </div>
      </div>

      <hr>

      <p id="copyright">Copyright &copy; 2025 <a href="" target="_blank">JK</a>. All right reserved</p>
    </div>
  </footer>

<script src="../assets/js/jquery-3.5.1.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
  // Format card number input
  $('#cardNumber').on('input', function() {
    var value = $(this).val().replace(/\s+/g, '');
    if (value.length > 0) {
      value = value.match(new RegExp('.{1,4}', 'g')).join(' ');
    }
    $(this).val(value);
  });

  // Format expiry date input
  $('#expiry').on('input', function() {
    var value = $(this).val().replace(/\D/g, '');
    if (value.length > 2) {
      value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    $(this).val(value);
  });

  // Restrict CVV to numbers only
  $('#cvv').on('input', function() {
    $(this).val($(this).val().replace(/\D/g, ''));
  });
</script>
</body>
</html>