<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <meta name="copyright" content="MACode ID, https://macodeid.com/">
  <title>Water Bill Payment Portal</title>

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

        <form action="#">
          <div class="input-group input-navbar">
            <div class="input-group-prepend">
              <span class="input-group-text" id="icon-addon1"><span class="mai-search"></span></span>
            </div>
            <input type="text" class="form-control" placeholder="Enter keyword.." aria-label="Username" aria-describedby="icon-addon1">
          </div>
        </form>

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
              <a class="nav-link active" href="water.php">Water bill</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="gas.php">LPG booking</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

<?php
$customerName = "";
$consumerNumber = "";
$waterUsage = "";
$billAmount = "";
$calculationDetails = ""; // For detailed breakdown

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customerName = isset($_POST['customerName']) ? $_POST['customerName'] : '';
    $consumerNumber = isset($_POST['consumerNumber']) ? $_POST['consumerNumber'] : '';
    $waterUsage = isset($_POST['waterUsage']) ? (float)$_POST['waterUsage'] : 0;

    if ($waterUsage >= 0) {
        // Water usage is already in liters, no conversion needed
        $waterUsageLiters = $waterUsage;

        // Domestic Tariff Calculation Logic
        if ($waterUsageLiters <= 5000) {
            $billAmount = max(72.05, ($waterUsageLiters / 1000) * 14.41);
            $calculationDetails = "Up to 5,000 liters: Rs. 14.41 per 1,000 liters (minimum Rs. 72.05)";
        } elseif ($waterUsageLiters <= 10000) {
            $billAmount = 72.05 + (($waterUsageLiters - 5000) / 1000) * 14.41;
            $calculationDetails = "5,001 to 10,000 liters: Rs. 72.05 + Rs. 14.41 per 1,000 liters above 5,000 liters";
        } elseif ($waterUsageLiters <= 15000) {
            $billAmount = 144.10 + (($waterUsageLiters - 10000) / 1000) * 15.51;
            $calculationDetails = "10,001 to 15,000 liters: Rs. 144.10 + Rs. 15.51 per 1,000 liters above 10,000 liters";
        } elseif ($waterUsageLiters <= 20000) {
            $billAmount = ($waterUsageLiters / 1000) * 16.62;
            $calculationDetails = "15,001 to 20,000 liters: Rs. 16.62 per 1,000 liters for entire consumption";
        } elseif ($waterUsageLiters <= 25000) {
            $billAmount = ($waterUsageLiters / 1000) * 17.72;
            $calculationDetails = "20,001 to 25,000 liters: Rs. 17.72 per 1,000 liters for entire consumption";
        } elseif ($waterUsageLiters <= 30000) {
            $billAmount = ($waterUsageLiters / 1000) * 19.92;
            $calculationDetails = "25,001 to 30,000 liters: Rs. 19.92 per 1,000 liters for entire consumption";
        } elseif ($waterUsageLiters <= 40000) {
            $billAmount = ($waterUsageLiters / 1000) * 23.23;
            $calculationDetails = "30,001 to 40,000 liters: Rs. 23.23 per 1,000 liters for entire consumption";
        } elseif ($waterUsageLiters <= 50000) {
            $billAmount = ($waterUsageLiters / 1000) * 25.44;
            $calculationDetails = "40,001 to 50,000 liters: Rs. 25.44 per 1,000 liters for entire consumption";
        } else {
            $billAmount = 1272.00 + (($waterUsageLiters - 50000) / 1000) * 54.10;
            $calculationDetails = "Above 50,000 liters: Rs. 1,272.00 + Rs. 54.10 per 1,000 liters above 50,000 liters";
        }
    } else {
        $billAmount = "Invalid water usage entry. Please enter a positive number.";
        $calculationDetails = "Error in calculation";
    }
}
?>

<div class="page-section">
  <div class="container">
    <h1 class="text-center">Water Bill Payment</h1>

    <form class="main-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <div class="row mt-5">
        <div class="col-12 col-sm-6 py-2">
          <label>Enter your name:</label> 
        </div>
        <div class="col-12 col-sm-6 py-2">
          <input type="text" name="customerName" class="form-control" 
                 placeholder="Full Name" value="<?php echo htmlspecialchars($customerName); ?>" required>
        </div>
        <div class="col-12 col-sm-6 py-2">
          <label>Enter consumer number:</label> 
        </div>
        <div class="col-12 col-sm-6 py-2">
          <input type="text" name="consumerNumber" class="form-control" 
                 placeholder="Consumer Number" value="<?php echo htmlspecialchars($consumerNumber); ?>" required>
        </div>
        <div class="col-12 col-sm-6 py-2">
          <label>Enter water usage (liters):</label> 
        </div>
        <div class="col-12 col-sm-6 py-2">
          <input type="number" name="waterUsage" class="form-control" 
                 placeholder="Enter water usage in liters" value="<?php echo htmlspecialchars($waterUsage); ?>" 
                 min="0" step="1" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary mt-3">Generate Bill</button>
    </form>

    <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($customerName)): ?>
      <div class="bill-result mt-4">
        <h3>Bill Summary</h3>
        <p><strong>Customer Name:</strong> <?php echo htmlspecialchars($customerName); ?></p>
        <p><strong>Consumer Number:</strong> <?php echo htmlspecialchars($consumerNumber); ?></p>
        <p><strong>Water Usage:</strong> <?php echo htmlspecialchars($waterUsage); ?> liters</p>
        <p><strong>Calculation Details:</strong> <?php echo $calculationDetails; ?></p>
        <p><strong>Total Bill Amount:</strong> 
          <?php 
          if (is_numeric($billAmount)) {
              echo 'Rs. ' . number_format($billAmount, 2);
          } else {
              echo $billAmount;
          }
          ?>
        </p>
        <?php if (is_numeric($billAmount)): ?>
          <a href="payment.php?amount=<?php echo $billAmount; ?>&consumer=<?php echo urlencode($consumerNumber); ?>" 
             class="btn btn-success">Pay Now</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
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