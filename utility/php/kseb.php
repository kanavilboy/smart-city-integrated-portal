<?php
session_start();
require '../../database.php';

$consumerDetails = null;
$unpaidBills = array();
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['search_consumer'])) {
        $consumerNumber = $_POST['consumer_number'];
        
        // Fetch consumer details
        $stmt = $conn->prepare("SELECT * FROM kseb_consumers WHERE consumer_number = ? AND status = 'active'");
        $stmt->execute(array($consumerNumber));
        $consumerDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($consumerDetails) {
            // Fetch unpaid bills for this consumer
            $stmt = $conn->prepare("
                SELECT * FROM kseb_bills 
                WHERE consumer_id = ? AND payment_status = 'unpaid'
                ORDER BY due_date ASC
            ");
            $stmt->execute(array($consumerDetails['id']));
            $unpaidBills = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "Consumer not found or inactive. Please check the consumer number.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <meta name="copyright" content="MACode ID, https://macodeid.com/">
  <title>Utility Payment Portal</title>
  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    .bill-card {
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      overflow: hidden;
    }
    .bill-header {
      background-color: #0056b3;
      color: white;
      padding: 15px;
    }
    .bill-body {
      padding: 20px;
    }
    .status-unpaid {
      background-color: #ffc107;
      color: black;
    }
    .status-overdue {
      background-color: #dc3545;
      color: white;
    }
    .consumer-details {
      background-color: #f8f9fa;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
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
      <h1 class="text-center">Electricity Bill Payment</h1>
      
      <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
      <?php endif; ?>
      
      <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="main-form">
        <div class="row mt-5">
          <div class="col-12 col-sm-6 py-2">
            <label>Enter Consumer Number:</label> 
          </div>
          <div class="col-12 col-sm-6 py-2">
            <input type="text" name="consumer_number" class="form-control" 
                   placeholder="Consumer Number" required>
          </div>
        </div>
        <button type="submit" name="search_consumer" class="btn btn-primary mt-3">Search</button>
      </form>
      
      <?php if ($consumerDetails): ?>
        <div class="consumer-details mt-4">
          <h3>Consumer Details</h3>
          <div class="row">
            <div class="col-md-6">
              <p><strong>Name:</strong> <?php echo htmlspecialchars($consumerDetails['name']); ?></p>
              <p><strong>Consumer Number:</strong> <?php echo htmlspecialchars($consumerDetails['consumer_number']); ?></p>
              <p><strong>Connection Type:</strong> <?php echo ucfirst($consumerDetails['connection_type']); ?></p>
            </div>
            <div class="col-md-6">
              <p><strong>Phase Type:</strong> <?php echo str_replace('_', ' ', ucfirst($consumerDetails['phase_type'])); ?></p>
              <p><strong>Meter Number:</strong> <?php echo htmlspecialchars($consumerDetails['meter_number']); ?></p>
              <p><strong>Address:</strong> <?php echo htmlspecialchars($consumerDetails['address']); ?></p>
            </div>
          </div>
        </div>
      <?php endif; ?>
      
      <?php if (!empty($unpaidBills)): ?>
        <div class="mt-4">
          <h3>Unpaid Bills</h3>
          <?php foreach ($unpaidBills as $bill): 
            $isOverdue = strtotime($bill['due_date']) < time();
            $statusClass = $isOverdue ? 'status-overdue' : 'status-unpaid';
          ?>
            <div class="bill-card">
              <div class="bill-header">
                <h4>Bill #<?php echo htmlspecialchars($bill['bill_number']); ?></h4>
              </div>
              <div class="bill-body">
                <div class="row">
                  <div class="col-md-6">
                    <p><strong>Issue Date:</strong> <?php echo date('d M Y', strtotime($bill['issue_date'])); ?></p>
                    <p><strong>Due Date:</strong> 
                      <?php echo date('d M Y', strtotime($bill['due_date'])); ?>
                      <?php if ($isOverdue): ?>
                        <span class="badge <?php echo $statusClass; ?>">Overdue</span>
                      <?php else: ?>
                        <span class="badge <?php echo $statusClass; ?>">Unpaid</span>
                      <?php endif; ?>
                    </p>
                    <p><strong>Units Consumed:</strong> <?php echo $bill['units_consumed']; ?></p>
                  </div>
                  <div class="col-md-6">
                    <p><strong>Energy Charge:</strong> ₹<?php echo number_format($bill['energy_charge'], 2); ?></p>
                    <p><strong>Fixed Charge:</strong> ₹<?php echo number_format($bill['fixed_charge'], 2); ?></p>
                    <p><strong>Total Amount:</strong> ₹<?php echo number_format($bill['total_amount'], 2); ?></p>
                  </div>
                </div>
                <div class="text-right mt-3">
                  <a href="kseb_payment.php?bill_id=<?php echo $bill['id']; ?>&amount=<?php echo $bill['total_amount']; ?>" 
                     class="btn btn-success">Pay Now</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php elseif ($consumerDetails && empty($unpaidBills)): ?>
        <div class="alert alert-info mt-4">
          No unpaid bills found for this consumer.
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
    </div>
  </footer>

  <script src="../assets/js/jquery-3.5.1.min.js"></script>
  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/vendor/owl-carousel/js/owl.carousel.min.js"></script>
  <script src="../assets/vendor/wow/wow.min.js"></script>
  <script src="../assets/js/theme.js"></script>
</body>
</html>