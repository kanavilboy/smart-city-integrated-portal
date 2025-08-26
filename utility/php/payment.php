<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <meta name="copyright" content="MACode ID, https://macodeid.com/">
  <title>Payment Portal</title>

  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
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
              <a class="nav-link" href="utility.html">Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="kseb.php">KSEB bill</a>
            </li>
            <li class="nav-item active">
              <a class="nav-link" href="gas.php">LPG booking</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

<?php
$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;
$consumer = isset($_GET['consumer']) ? htmlspecialchars($_GET['consumer']) : '';
$paymentStatus = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cardNumber = isset($_POST['cardNumber']) ? $_POST['cardNumber'] : '';
    $expiry = isset($_POST['expiry']) ? $_POST['expiry'] : '';
    $cvv = isset($_POST['cvv']) ? $_POST['cvv'] : '';
    
    // Basic validation (in real application, you'd need more robust validation and security)
    if (strlen($cardNumber) >= 12 && strlen($cvv) >= 3 && !empty($expiry)) {
        $paymentStatus = "Payment of Rs. " . number_format($amount, 2) . " successful for Consumer Number: " . $consumer;
    } else {
        $paymentStatus = "Payment failed. Please check your card details.";
    }
}
?>

<div class="page-section">
  <div class="container">
    <h1 class="text-center">Payment Portal</h1>

    <div class="row justify-content-center mt-5">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-body">
            <h3 class="card-title text-center">Payment Details</h3>
            <p class="text-center"><strong>Amount to Pay: Rs. <?php echo number_format($amount, 2); ?></strong></p>
            <p class="text-center"><strong>Consumer Number: <?php echo $consumer; ?></strong></p>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?amount=$amount&consumer=$consumer"; ?>">
              <div class="form-group">
                <label for="cardNumber">Card Number</label>
                <input type="text" class="form-control" id="cardNumber" name="cardNumber" 
                       placeholder="Enter 12-16 digit card number" required>
              </div>
              
              <div class="row">
                <div class="col-6">
                  <div class="form-group">
                    <label for="expiry">Expiry Date</label>
                    <input type="text" class="form-control" id="expiry" name="expiry" 
                           placeholder="MM/YY" required>
                  </div>
                </div>
                <div class="col-6">
                  <div class="form-group">
                    <label for="cvv">CVV</label>
                    <input type="text" class="form-control" id="cvv" name="cvv" 
                           placeholder="3-4 digits" required>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label for="cardHolder">Card Holder Name</label>
                <input type="text" class="form-control" id="cardHolder" name="cardHolder" 
                       placeholder="Enter name on card" required>
              </div>

              <button type="submit" class="btn btn-primary btn-block mt-4">Make Payment</button>
            </form>

            <?php if (!empty($paymentStatus)): ?>
              <div class="alert <?php echo strpos($paymentStatus, 'successful') !== false ? 'alert-success' : 'alert-danger'; ?> mt-4">
                <?php echo $paymentStatus; ?>
              </div>
              <?php if (strpos($paymentStatus, 'successful') !== false): ?>
                <a href="gas.php" class="btn btn-secondary btn-block">Return to Booking</a>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
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
    </div> <!-- .container -->
  </footer> <!-- .page-footer -->

<script src="../assets/js/jquery-3.5.1.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/vendor/owl-carousel/js/owl.carousel.min.js"></script>
<script src="../assets/vendor/wow/wow.min.js"></script>
<script src="../assets/js/theme.js"></script>
  
</body>
</html>