<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session at the beginning
session_start();

require_once '../../database.php';

// Check if product ID is provided and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: marketplace.php");
    exit();
}

$product_id = (int)$_GET['id'];

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$log_id = $_SESSION['user_id'];
$user_id = null; // Initialize user_id

try {
    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT id FROM personal_users WHERE user_id = ?");
    $stmt->execute(array($log_id));

    // Fetch the result
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header("Location: ../../login.php");
        exit();
    }
    
    $user_id = $user['id'];
    $customer_id = $user_id;

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

try {
    // Get product details
    $productQuery = "SELECT p.*, m.name as merchant_name 
                    FROM products p 
                    JOIN merchants m ON p.merchant_id = m.id 
                    WHERE p.id = ?";
    $productStmt = $conn->prepare($productQuery);
    $productStmt->execute(array($product_id));
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header("Location: marketplace.php");
        exit();
    }
    
    // Initialize variables for form fields
    $customer_name = '';
    $customer_email = '';
    $customer_phone = '';
    $customer_address = '';
    $payment_method = 'Credit Card';
    $card_number = '';
    $card_expiry = '';
    $card_cvv = '';
    $upi_id = '';
    $errors = array();
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate and sanitize input
        $customer_name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
        $customer_email = isset($_POST['customer_email']) ? trim($_POST['customer_email']) : '';
		$customer_phone = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
		$customer_address = isset($_POST['customer_address']) ? trim($_POST['customer_address']) : '';
		$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'Credit Card';
        $card_number = isset($_POST['card_number']) ? trim($_POST['card_number']) : '';
        $card_expiry = isset($_POST['card_expiry']) ? trim($_POST['card_expiry']) : '';
        $card_cvv = isset($_POST['card_cvv']) ? trim($_POST['card_cvv']) : '';
        $upi_id = isset($_POST['upi_id']) ? trim($_POST['upi_id']) : '';
        
        // Basic validation
        if (empty($customer_name)) {
            $errors[] = "Customer name is required";
        }
        
        if (empty($customer_email) || !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required";
        }
        
        if (empty($customer_phone)) {
            $errors[] = "Phone number is required";
        }
        
        if (empty($customer_address)) {
            $errors[] = "Delivery address is required";
        }
        
        if ($payment_method === 'Credit Card' || $payment_method === 'Debit Card') {
            if (empty($card_number) || strlen(preg_replace('/\D/', '', $card_number)) !== 16) {
                $errors[] = "Valid 16-digit card number is required";
            }
            if (empty($card_expiry) || !preg_match('/^(0[1-9]|1[0-2])\/?([0-9]{2})$/', $card_expiry)) {
                $errors[] = "Valid expiry date (MM/YY) is required";
            }
            if (empty($card_cvv) || !preg_match('/^\d{3,4}$/', $card_cvv)) {
                $errors[] = "Valid CVV is required";
            }
        } elseif ($payment_method === 'UPI' && empty($upi_id)) {
            $errors[] = "UPI ID is required";
        }
        
        // If no errors, process booking and payment
        if (empty($errors)) {
            $conn->beginTransaction();
            
            try {
                // Insert booking
                $bookingQuery = "INSERT INTO product_booking (
                    merchant_id, 
                    product_id, 
                    customer_id,
                    customer_name,
                    customer_email,
                    customer_phone,
                    customer_address,
                    product_name, 
                    booking_date,
                    booking_time,
                    status,
                    payment_status,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Pending', NOW())";
				
				$booking_date = date('Y-m-d'); // Current Date
				$booking_time = date('H:i:s'); // Current Time

                
                $bookingStmt = $conn->prepare($bookingQuery);
                $bookingStmt->execute(array(
                    $product['merchant_id'],
                    $product_id,
                    $customer_id,
                    $customer_name,
                    $customer_email,
                    $customer_phone,
                    $customer_address,
                    $product['product_name'],
					$booking_date,
					$booking_time
                ));
				
				
                
                $booking_id = $conn->lastInsertId();
                
                // Insert payment
                $paymentQuery = "INSERT INTO product_payments (
                    booking_id,
                    payment_method,
                    payment_amount,
                    payment_status,
                    transaction_id,
                    card_last_four
                ) VALUES (?, ?, ?, 'Pending', ?, ?)";
                
                $paymentStmt = $conn->prepare($paymentQuery);
                
                // Generate a temporary transaction ID (in real app, this would come from payment gateway)
                $transaction_id = 'TXN' . time() . rand(100, 999);
                $card_last_four = ($payment_method === 'Credit Card' || $payment_method === 'Debit Card') ? substr(preg_replace('/\D/', '', $card_number), -4) : '';
                
                $paymentStmt->execute(array(
                    $booking_id,
                    $payment_method,
                    $product['price'],
                    $transaction_id,
                    $card_last_four
                ));
                
                $conn->commit();
                
                // Redirect to confirmation page
                header("Location: marketplace.php");
                exit();
                
            } catch (PDOException $e) {
                $conn->rollBack();
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
    
    // Start output buffering
    ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment - <?php echo htmlspecialchars($product['product_name']); ?> - Smart City</title>
  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    .payment-hero {
      background-color: #f8f9fa;
      padding: 3rem 0;
      margin-bottom: 3rem;
    }
    
    .payment-container {
      background: white;
      border-radius: 8px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      padding: 2rem;
    }
    
    .payment-method {
      border: 1px solid #ddd;
      border-radius: 5px;
      padding: 15px;
      margin-bottom: 15px;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .payment-method:hover {
      border-color: #00d289;
    }
    
    .payment-method.selected {
      border-color: #00d289;
      background-color: rgba(0, 210, 137, 0.05);
    }
    
    .payment-method input[type="radio"] {
      margin-right: 10px;
    }
    
    .payment-details {
      display: none;
      padding: 15px;
      background: #f9f9f9;
      border-radius: 5px;
      margin-top: 10px;
    }
    
    .product-summary {
      border-left: 1px solid #eee;
      padding-left: 20px;
    }
    
    .product-summary img {
      max-height: 150px;
      width: auto;
      margin-bottom: 15px;
    }
    
    .error-message {
      color: #dc3545;
      font-size: 0.875rem;
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
            <li class="nav-item"><a class="nav-link" href="history.php">My Orders</a></li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <!-- Payment Hero Section -->
  <section class="payment-hero">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-12">
          <h1 class="mb-0">Complete Your Purchase</h1>
        </div>
      </div>
    </div>
  </section>

  <!-- Payment Form Section -->
  <section class="mb-5">
    <div class="container">
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      
      <div class="row">
        <div class="col-md-8">
          <div class="payment-container">
            <h3 class="mb-4">Customer Information</h3>
            
            <form method="POST" action="">
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="customer_name">Full Name *</label>
                  <input type="text" class="form-control" id="customer_name" name="customer_name" 
                         value="<?php echo htmlspecialchars($customer_name); ?>" required>
                </div>
                <div class="form-group col-md-6">
                  <label for="customer_email">Email *</label>
                  <input type="email" class="form-control" id="customer_email" name="customer_email" 
                         value="<?php echo htmlspecialchars($customer_email); ?>" required>
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="customer_phone">Phone Number *</label>
                  <input type="tel" class="form-control" id="customer_phone" name="customer_phone" 
                         value="<?php echo htmlspecialchars($customer_phone); ?>" required>
                </div>
              </div>
              
              <div class="form-group">
                <label for="customer_address">Delivery Address *</label>
                <textarea class="form-control" id="customer_address" name="customer_address" rows="3" required><?php echo htmlspecialchars($customer_address); ?></textarea>
              </div>
              
              <h3 class="mb-4 mt-5">Payment Method</h3>
              
              <div class="payment-method selected" data-method="Credit Card">
                <input type="radio" name="payment_method" id="credit_card" value="Credit Card" <?php echo $payment_method === 'Credit Card' ? 'checked' : ''; ?>>
                <label for="credit_card">Credit Card</label>
                
                <div class="payment-details" id="credit_card_details" style="<?php echo $payment_method === 'Credit Card' ? 'display:block' : 'display:none'; ?>">
                  <div class="form-group">
                    <label for="card_number">Card Number</label>
                    <input type="text" class="form-control" id="card_number" name="card_number" 
                           placeholder="1234 5678 9012 3456" 
                           value="<?php echo htmlspecialchars($card_number); ?>">
                  </div>
                  
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="card_expiry">Expiry Date</label>
                      <input type="text" class="form-control" id="card_expiry" name="card_expiry" 
                             placeholder="MM/YY" 
                             value="<?php echo htmlspecialchars($card_expiry); ?>">
                    </div>
                    <div class="form-group col-md-6">
                      <label for="card_cvv">CVV</label>
                      <input type="text" class="form-control" id="card_cvv" name="card_cvv" 
                             placeholder="123" 
                             value="<?php echo htmlspecialchars($card_cvv); ?>">
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="payment-method" data-method="Debit Card">
                <input type="radio" name="payment_method" id="debit_card" value="Debit Card" <?php echo $payment_method === 'Debit Card' ? 'checked' : ''; ?>>
                <label for="debit_card">Debit Card</label>
                
                <div class="payment-details" id="debit_card_details" style="<?php echo $payment_method === 'Debit Card' ? 'display:block' : 'display:none'; ?>">
                  <div class="form-group">
                    <label for="debit_card_number">Card Number</label>
                    <input type="text" class="form-control" id="debit_card_number" name="card_number" 
                           placeholder="1234 5678 9012 3456" 
                           value="<?php echo htmlspecialchars($card_number); ?>">
                  </div>
                  
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="debit_card_expiry">Expiry Date</label>
                      <input type="text" class="form-control" id="debit_card_expiry" name="card_expiry" 
                             placeholder="MM/YY" 
                             value="<?php echo htmlspecialchars($card_expiry); ?>">
                    </div>
                    <div class="form-group col-md-6">
                      <label for="debit_card_cvv">CVV</label>
                      <input type="text" class="form-control" id="debit_card_cvv" name="card_cvv" 
                             placeholder="123" 
                             value="<?php echo htmlspecialchars($card_cvv); ?>">
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="payment-method" data-method="UPI">
                <input type="radio" name="payment_method" id="upi" value="UPI" <?php echo $payment_method === 'UPI' ? 'checked' : ''; ?>>
                <label for="upi">UPI</label>
                
                <div class="payment-details" id="upi_details" style="<?php echo $payment_method === 'UPI' ? 'display:block' : 'display:none'; ?>">
                  <div class="form-group">
                    <label for="upi_id">UPI ID</label>
                    <input type="text" class="form-control" id="upi_id" name="upi_id" 
                           placeholder="yourname@upi" 
                           value="<?php echo htmlspecialchars($upi_id); ?>">
                  </div>
                </div>
              </div>
              
              <div class="payment-method" data-method="Net Banking">
                <input type="radio" name="payment_method" id="net_banking" value="Net Banking" <?php echo $payment_method === 'Net Banking' ? 'checked' : ''; ?>>
                <label for="net_banking">Net Banking</label>
                
                <div class="payment-details" id="net_banking_details" style="<?php echo $payment_method === 'Net Banking' ? 'display:block' : 'display:none'; ?>">
                  <div class="form-group">
                    <label>You will be redirected to your bank's payment gateway</label>
                  </div>
                </div>
              </div>
              
              <div class="payment-method" data-method="Cash on Delivery">
                <input type="radio" name="payment_method" id="cod" value="Cash on Delivery" <?php echo $payment_method === 'Cash on Delivery' ? 'checked' : ''; ?>>
                <label for="cod">Cash on Delivery</label>
                
                <div class="payment-details" id="cod_details" style="<?php echo $payment_method === 'Cash on Delivery' ? 'display:block' : 'display:none'; ?>">
                  <div class="form-group">
                    <label>Pay when your order is delivered</label>
                  </div>
                </div>
              </div>
              
              <div class="form-group form-check mt-4">
                <input type="checkbox" class="form-check-input" id="terms" required>
                <label class="form-check-label" for="terms">I agree to the terms and conditions *</label>
              </div>
              
              <button type="submit" class="btn btn-primary btn-lg btn-block mt-3">Complete Payment</button>
            </form>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="product-summary">
            <h3 class="mb-4">Order Summary</h3>
            
            <img src="<?php echo htmlspecialchars($product['product_image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="img-fluid">
            <h4><?php echo htmlspecialchars($product['product_name']); ?></h4>
            <p class="text-muted">Sold by: <?php echo htmlspecialchars($product['merchant_name']); ?></p>
            
            <div class="d-flex justify-content-between mt-4">
              <span>Subtotal:</span>
              <span>₹<?php echo number_format($product['price'], 2); ?></span>
            </div>
            
            <div class="d-flex justify-content-between">
              <span>Delivery:</span>
              <span>Free</span>
            </div>
            
            <hr>
            
            <div class="d-flex justify-content-between font-weight-bold">
              <span>Total:</span>
              <span>₹<?php echo number_format($product['price'], 2); ?></span>
            </div>
          </div>
        </div>
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
  <script>
    // Payment method selection
    $(document).ready(function() {
      // Show details for selected payment method
      $('.payment-method').click(function() {
        $('.payment-method').removeClass('selected');
        $(this).addClass('selected');
        $('input[name="payment_method"]').prop('checked', false);
        $(this).find('input[type="radio"]').prop('checked', true);
        
        // Hide all payment details
        $('.payment-details').hide();
        
        // Show details for selected method
        const method = $(this).data('method');
        if (method === 'Credit Card') {
          $('#credit_card_details').show();
        } else if (method === 'Debit Card') {
          $('#debit_card_details').show();
        } else if (method === 'UPI') {
          $('#upi_details').show();
        } else if (method === 'Net Banking') {
          $('#net_banking_details').show();
        } else if (method === 'Cash on Delivery') {
          $('#cod_details').show();
        }
      });
      
      // Format card number
      $('#card_number, #debit_card_number').on('input', function() {
        let val = $(this).val().replace(/\D/g, '');
        val = val.replace(/(\d{4})(?=\d)/g, '$1 ');
        $(this).val(val);
      });
      
      // Format expiry date
      $('#card_expiry, #debit_card_expiry').on('input', function() {
        let val = $(this).val().replace(/\D/g, '');
        if (val.length > 2) {
          val = val.substring(0, 2) + '/' + val.substring(2, 4);
        }
        $(this).val(val);
      });
    });
  </script>
</body>
</html>
<?php
    // End output buffering and send output
    $output = ob_get_clean();
    echo $output;

} catch (PDOException $e) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Error processing payment: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
    exit();
}
?>