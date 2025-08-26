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
$bill_id = isset($_GET['bill_id']) ? $_GET['bill_id'] : '';
$consumer = isset($_GET['consumer']) ? htmlspecialchars($_GET['consumer']) : '';
$paymentStatus = "";
$payment_success = false;

// Fetch bill details if bill_id is provided
if ($bill_id) {
    $stmt = $conn->prepare("
        SELECT b.*, c.consumer_number, c.name 
        FROM kseb_bills b
        JOIN kseb_consumers c ON b.consumer_id = c.id
        WHERE b.id = ? AND b.payment_status = 'unpaid'
    ");
    $stmt->execute(array($bill_id));
    $bill = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($bill) {
        $amount = $bill['total_amount'];
        $consumer = $bill['consumer_number'];
        $consumer_id = $bill['consumer_id'];
    } else {
        $paymentStatus = "Invalid bill ID or bill has already been paid.";
    }
}

// Process payment form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['make_payment'])) {
    $cardNumber = isset($_POST['cardNumber']) ? $_POST['cardNumber'] : '';
    $expiry = isset($_POST['expiry']) ? $_POST['expiry'] : '';
    $cvv = isset($_POST['cvv']) ? $_POST['cvv'] : '';
    $cardHolder = isset($_POST['cardHolder']) ? $_POST['cardHolder'] : '';
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'credit_card';
    
    // Basic validation
    if (strlen($cardNumber) >= 12 && strlen($cvv) >= 3 && !empty($expiry) && !empty($cardHolder)) {
        try {
            $conn->beginTransaction();
            
            // Update payment status in kseb_bills
            $stmt = $conn->prepare("
                UPDATE kseb_bills 
                SET payment_status = 'paid'
                WHERE id = ?
            ");
            $stmt->execute(array($bill_id));
            
            // Record payment transaction
            $stmt = $conn->prepare("
                INSERT INTO kseb_transactions 
                (consumer_id, bill_id, bill_number, payment_method, amount, payment_date, status)
                VALUES (?, ?, ?, ?, ?, NOW(), 'success')
            ");
            $stmt->execute(array(
                $consumer_id,
                $bill_id,
                $bill['bill_number'],
                $payment_method,
                $amount
            ));
            
            $conn->commit();
            
            $payment_success = true;
            $paymentStatus = "Payment of Rs. " . number_format($amount, 2) . " successful for Bill #" . $bill['bill_number'];
            
        } catch (Exception $e) {
            $conn->rollBack();
            $paymentStatus = "Payment failed. Error: " . $e->getMessage();
        }
    } else {
        $paymentStatus = "Payment failed. Please check your payment details.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>KSEB Bill Payment</title>
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
      background-color: #0056b3;
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
      color: #0056b3;
    }
    .payment-details {
      background-color: #f8f9fa;
      border-radius: 5px;
      padding: 15px;
      margin-bottom: 20px;
    }
    .btn-pay {
      background-color: #0056b3;
      border-color: #0056b3;
      font-weight: bold;
    }
    .btn-pay:hover {
      background-color: #004494;
      border-color: #004494;
    }
    .bill-details {
      margin-bottom: 20px;
    }
    .bill-details p {
      margin-bottom: 5px;
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
              <a class="nav-link active" href="kseb.php">KSEB bill</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="gas.php">LPG booking</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

<div class="page-section">
  <div class="container">
    <h1 class="text-center">KSEB Electricity Bill Payment</h1>

    <div class="row justify-content-center mt-5">
      <div class="col-lg-8">
        <?php if (!empty($paymentStatus)): ?>
          <div class="alert <?php echo $payment_success ? 'alert-success' : 'alert-danger'; ?>">
            <?php echo $paymentStatus; ?>
            <?php if ($payment_success): ?>
              <div class="mt-3">
                <a href="kseb.php" class="btn btn-primary">
                  Return to KSEB Bills
                </a>
                <a href="kseb_payment_receipt.php?bill_id=<?php echo $bill_id; ?>" class="btn btn-secondary ml-2">
                  View Receipt
                </a>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($bill_id && !$payment_success && isset($bill)): ?>
          <div class="card payment-card">
            <div class="card-header payment-header">
              <h3 class="card-title text-center mb-0">Complete Your Electricity Bill Payment</h3>
            </div>
            <div class="card-body payment-body">
              <div class="payment-details">
                <div class="row">
                  <div class="col-md-6">
                    <p><strong>Bill Number:</strong> <?php echo $bill['bill_number']; ?></p>
                    <p><strong>Consumer Number:</strong> <?php echo $consumer; ?></p>
                    <p><strong>Customer Name:</strong> <?php echo $bill['name']; ?></p>
                  </div>
                  <div class="col-md-6 text-right">
                    <p class="payment-amount">Rs. <?php echo number_format($amount, 2); ?></p>
                    <p><small>Amount to be paid</small></p>
                  </div>
                </div>
              </div>

              <div class="bill-details">
                <h5>Bill Details</h5>
                <div class="row">
                  <div class="col-md-6">
                    <p><strong>Issue Date:</strong> <?php echo date('d M Y', strtotime($bill['issue_date'])); ?></p>
                    <p><strong>Due Date:</strong> <?php echo date('d M Y', strtotime($bill['due_date'])); ?></p>
                  </div>
                  <div class="col-md-6">
                    <p><strong>Units Consumed:</strong> <?php echo $bill['units_consumed']; ?></p>
                  </div>
                </div>
              </div>

              <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?bill_id=<?php echo $bill_id; ?>">
                <h5 class="mb-4">Payment Information</h5>
                
                <div class="form-group">
                  <label>Payment Method</label>
                  <select name="payment_method" class="form-control" required>
                    <option value="credit_card" selected>Credit Card</option>
                    <option value="debit_card">Debit Card</option>
                  </select>
                </div>
                
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
        <?php elseif (!$bill_id): ?>
          <div class="alert alert-warning">
            <p>No bill specified for payment. Please go back to your KSEB bills page and click on the payment link.</p>
            <a href="kseb.php" class="btn btn-primary">Go to KSEB Bills</a>
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
          <h5>Contact</h5>
          <p class="footer-link mt-2">KSEB Bill Payment System</p>
          <a href="#" class="footer-link">support@kseb.com</a>
        </div>
      </div>
      <hr>
      <p id="copyright">Copyright &copy; 2025 KSEB Bill Payment System. All right reserved</p>
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