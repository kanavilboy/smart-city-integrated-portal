<?php
session_start();
require '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = array();
$success = false;
$booking_id = null;
$customer = null;
$agency_options = array();

// Initialize variables
$consumer_number = '';
$show_booking_form = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['search_customer'])) {
        // Handle customer search
        $consumer_number = trim($_POST['consumer_number']);
        
        if (empty($consumer_number)) {
            $errors[] = "Consumer number is required";
        } else {
            // Fetch customer details
            $stmt = $conn->prepare("SELECT * FROM gas_customers WHERE consumer_no = ?");
            $stmt->execute(array($consumer_number));
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                $errors[] = "Customer not found with this consumer number";
            } else {
                $show_booking_form = true;
                
                // Fetch the agency this customer is registered with
                $stmt = $conn->prepare("
                    SELECT ga.id as agency_id, ga.dealer_name, 
                           gc.id as cylinder_id, gc.type, gc.price, gc.stock_quantity
                    FROM gas_agencies ga
                    JOIN gas_cylinders gc ON ga.id = gc.agency_id
                    WHERE ga.id = ? AND gc.is_available = 1 AND gc.stock_quantity > 0
                    ORDER BY gc.type
                ");
                $stmt->execute(array($customer['dealer_id']));
                $agencies = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Organize agency and cylinders
                foreach ($agencies as $row) {
                    if (!isset($agency_options[$row['agency_id']])) {
                        $agency_options[$row['agency_id']] = array(
                            'name' => $row['dealer_name'],
                            'cylinders' => array()
                        );
                    }
                    $agency_options[$row['agency_id']]['cylinders'][] = array(
                        'id' => $row['cylinder_id'],
                        'type' => $row['type'],
                        'price' => $row['price'],
                        'stock' => $row['stock_quantity']
                    );
                }
            }
        }
    } 
    elseif (isset($_POST['book_cylinder'])) {
        // Handle booking submission
        $customer_id = $_POST['customer_id'];
        $agency_id = $_POST['agency_id'];
        $cylinder_id = $_POST['cylinder_id'];
        $quantity = (int)$_POST['quantity'];
        $delivery_address = trim($_POST['delivery_address']);
        //$delivery_date = $_POST['delivery_date'];
        
        // Validation
        if (empty($customer_id)) {
            $errors[] = "Customer information is required";
        }
        
        if (empty($agency_id)) {
            $errors[] = "Please select a gas agency";
        }
        
        if (empty($cylinder_id)) {
            $errors[] = "Please select a cylinder type";
        }
        
        if ($quantity <= 0) {
            $errors[] = "Please enter a valid quantity";
        }
        
        if (empty($delivery_address)) {
            $errors[] = "Delivery address is required";
        }
        
        
        
        // If no errors, process booking
        if (empty($errors)) {
            try {
                // Get cylinder details
                $stmt = $conn->prepare("SELECT * FROM gas_cylinders WHERE id = ? AND agency_id = ?");
                $stmt->execute(array($cylinder_id, $agency_id));
                $cylinder = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$cylinder) {
                    throw new Exception("Selected cylinder not found");
                }
                
                // Check stock availability
                if ($cylinder['stock_quantity'] < $quantity) {
                    throw new Exception("Insufficient stock for selected cylinder");
                }
                
                // Calculate total amount
                $total_amount = $cylinder['price'] * $quantity;
                
                // Start transaction
                $conn->beginTransaction();
                
                // 1. Create booking
                $stmt = $conn->prepare("
                    INSERT INTO gas_bookings 
                    (customer_id, cylinder_id, agency_id, quantity, total_amount, status, payment_status)
                    VALUES (?, ?, ?, ?, ?, 'pending', 'pending')
                ");
                $stmt->execute(array(
                    $customer_id,
                    $cylinder_id,
                    $agency_id,
                    $quantity,
                    $total_amount,
                ));
                $booking_id = $conn->lastInsertId();
                
                
                // 3. Update cylinder stock
                $stmt = $conn->prepare("
                    UPDATE gas_cylinders 
                    SET stock_quantity = stock_quantity - ? 
                    WHERE id = ?
                ");
                $stmt->execute(array(
                    $quantity,
                    $cylinder_id
                ));
                
                // Commit transaction
                $conn->commit();
                
                $success = true;
                
            } catch (Exception $e) {
                $conn->rollBack();
                $errors[] = "Error processing booking: " . $e->getMessage();
            }
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
  <title>Gas Booking Portal</title>

  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
            <li class="nav-item active">
              <a class="nav-link" href="gas.php">LPG booking</a>
            </li>
           
          </ul>
        </div> <!-- .navbar-collapse -->
      </div> <!-- .container -->
    </nav>
  </header>

<div class="page-section">
  <div class="container">
    <h1 class="text-center">LPG Gas Booking</h1>
    
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
      <div class="alert alert-success">
        <h4>Booking Successful!</h4>
        <p>Your booking ID is: <strong><?php echo $booking_id; ?></strong></p>
        <p>Total Amount: ₹<strong><?php echo number_format($total_amount, 2); ?></strong></p>
        <a href="gaspayment.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-primary">
          Proceed to Payment
        </a>
      </div>
    <?php elseif (!$show_booking_form): ?>
      <!-- Customer Search Form -->
      <form class="main-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="row mt-5">
          <div class="col-12 py-2">
            <label>Enter Consumer Number:</label>
            <input type="text" name="consumer_number" class="form-control" 
                   value="<?php echo htmlspecialchars($consumer_number); ?>" required>
          </div>
        </div>
        <button type="submit" name="search_customer" class="btn btn-primary mt-3">Search</button>
      </form>
    <?php else: ?>
      <!-- Booking Form -->
      <form class="main-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
        <input type="hidden" name="agency_id" value="<?php echo $customer['dealer_id']; ?>">
        
        <div class="row mt-5">
          <div class="col-12 col-sm-6 py-2">
            <label>Your Name:</label> 
            <input type="text" class="form-control" 
                   value="<?php echo htmlspecialchars($customer['full_name']); ?>" readonly>
          </div>
          <div class="col-12 col-sm-6 py-2">
            <label>Your Phone:</label>
            <input type="text" class="form-control" 
                   value="<?php echo htmlspecialchars($customer['phone']); ?>" readonly>
          </div>
          
          <div class="col-12 py-2">
            <label>Delivery Address:</label>
            <textarea name="delivery_address" class="form-control" required><?php 
              echo htmlspecialchars($customer['address']); 
            ?></textarea>
          </div>
          
          <div class="col-12 col-sm-6 py-2">
            <label>Gas Agency:</label>
            <input type="text" class="form-control" 
                   value="<?php echo htmlspecialchars($agency_options[$customer['dealer_id']]['name']); ?>" readonly>
          </div>
          
          <div class="col-12 col-sm-6 py-2">
            <label>Select Cylinder Type:</label>
            <select name="cylinder_id" class="form-control" required>
              <option value="">-- Select Cylinder --</option>
              <?php foreach ($agency_options[$customer['dealer_id']]['cylinders'] as $cylinder): ?>
                <option value="<?php echo $cylinder['id']; ?>">
                  <?php echo htmlspecialchars($cylinder['type']); ?> 
                  - ₹<?php echo number_format($cylinder['price'], 2); ?> 
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="col-12 col-sm-6 py-2">
            <label>Quantity:</label>
            <input type="number" name="quantity" class="form-control" 
                   min="1" value="<?php echo isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '1'; ?>" required>
          </div>
		  
        </div>

        <button type="submit" name="book_cylinder" class="btn btn-primary mt-3">Book Now</button>
      </form>
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
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
  // Initialize date picker
  flatpickr(".datepicker", {
    minDate: "today",
    dateFormat: "Y-m-d",
    disable: [
      function(date) {
        // Disable weekends
        return (date.getDay() === 0 || date.getDay() === 6);
      }
    ]
  });
</script>
  
</body>
</html>