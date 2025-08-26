<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

require_once '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$log_id = $_SESSION['user_id'];

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
    // Get user's orders with product and payment information
    $ordersQuery = "SELECT 
                    pb.id as booking_id,
                    pb.product_name,
                    pb.booking_date,
                    pb.booking_time,
                    pb.status as order_status,
                    pb.payment_status,
                    pb.created_at as order_date,
                    p.id as product_id,
                    p.product_image,
                    p.price,
                    pp.payment_method,
                    pp.payment_amount,
                    pp.transaction_id,
                    pp.payment_date,
                    m.name as merchant_name
                FROM product_booking pb
                JOIN products p ON pb.product_id = p.id
                JOIN merchants m ON pb.merchant_id = m.id
                LEFT JOIN product_payments pp ON pp.booking_id = pb.id
                WHERE pb.customer_id = ?
                ORDER BY pb.created_at DESC";
    
    $ordersStmt = $conn->prepare($ordersQuery);
    $ordersStmt->execute(array($user_id));
    $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
	
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    try {
        // First verify the order belongs to the current user
        $verifyStmt = $conn->prepare("SELECT id FROM product_booking WHERE id = ? AND customer_id = ?");
        $verifyStmt->execute(array($booking_id, $user_id));
        $order = $verifyStmt->fetch();
        
        if (!$order) {
            echo json_encode(array('success' => false, 'message' => 'Order not found or unauthorized'));
            exit();
        }
        
        // Begin transaction
        $conn->beginTransaction();
        
        // First delete from product_payments (if exists)
        $deletePaymentStmt = $conn->prepare("DELETE FROM product_payments WHERE booking_id = ?");
        $deletePaymentStmt->execute(array($booking_id));
        
        // Then delete from product_booking
        $deleteBookingStmt = $conn->prepare("DELETE FROM product_booking WHERE id = ?");
        $deleteBookingStmt->execute(array($booking_id));
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(array('success' => true, 'message' => 'Order cancelled successfully'));
        exit();
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(array('success' => false, 'message' => 'Error cancelling order: ' . $e->getMessage()));
        exit();
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
  <title>My Orders - Smart City</title>
  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    .orders-hero {
      background-color: #f8f9fa;
      padding: 3rem 0;
      margin-bottom: 3rem;
    }
    
    .order-card {
      border: 1px solid #eee;
      border-radius: 8px;
      margin-bottom: 2rem;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .order-header {
      background-color: #f9f9f9;
      padding: 15px 20px;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
    }
    
    .order-body {
      padding: 20px;
    }
    
    .order-product {
      display: flex;
      margin-bottom: 20px;
      padding-bottom: 20px;
      border-bottom: 1px solid #f0f0f0;
    }
    
    .order-product:last-child {
      margin-bottom: 0;
      padding-bottom: 0;
      border-bottom: none;
    }
    
    .product-image {
      width: 100px;
      height: 100px;
      object-fit: contain;
      margin-right: 20px;
      border: 1px solid #eee;
      border-radius: 4px;
    }
    
    .product-details {
      flex: 1;
    }
    
    .product-title {
      font-weight: 600;
      margin-bottom: 5px;
    }
    
    .product-merchant {
      color: #666;
      font-size: 0.9rem;
      margin-bottom: 5px;
    }
    
    .product-price {
      font-weight: bold;
      color: #00d289;
    }
    
    .order-summary {
      background: #f9f9f9;
      padding: 15px;
      border-radius: 8px;
      margin-top: 20px;
    }
    
    .status-badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    
    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }
    
    .status-completed {
      background-color: #d4edda;
      color: #155724;
    }
    
    .status-cancelled {
      background-color: #f8d7da;
      color: #721c24;
    }
    
    .status-processing {
      background-color: #cce5ff;
      color: #004085;
    }
    
    .no-orders {
      text-align: center;
      padding: 50px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .order-actions {
      margin-top: 15px;
    }
    
    .order-filter {
      background: white;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      margin-bottom: 30px;
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
            <li class="nav-item"><a class="nav-link" href="categories.php">Categories</a></li>
            <li class="nav-item"><a class="nav-link" href="merchants.php">Merchants</a></li>
            <li class="nav-item active"><a class="nav-link" href="history.php">My Orders</a></li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <!-- Orders Hero Section -->
  <section class="orders-hero">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-12">
          <h1 class="mb-3">My Orders</h1>
          <p class="mb-0">View your order history and track current orders</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Orders Section -->
  <section class="mb-5">
    <div class="container">
      <!-- Order Filter -->
      <div class="order-filter">
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label for="status-filter">Order Status</label>
              <select class="form-control" id="status-filter">
                <option value="all">All Orders</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label for="date-filter">Date Range</label>
              <select class="form-control" id="date-filter">
                <option value="all">All Time</option>
                <option value="last30">Last 30 Days</option>
                <option value="last90">Last 90 Days</option>
                <option value="2024">2024</option>
                <option value="2023">2023</option>
              </select>
            </div>
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <button class="btn btn-primary mr-2" id="apply-filters">Apply Filters</button>
            <button class="btn btn-outline-secondary" id="reset-filters">Reset</button>
          </div>
        </div>
      </div>
      
      <?php if (count($orders) > 0): ?>
        <?php foreach ($orders as $order): 
          $orderDate = date('F j, Y', strtotime($order['order_date']));
          $bookingDate = date('F j, Y', strtotime($order['booking_date']));
          $paymentDate = $order['payment_date'] ? date('F j, Y', strtotime($order['payment_date'])) : 'N/A';
          
          // Determine status badge class
          $statusClass = '';
          switch(strtolower($order['order_status'])) {
            case 'pending':
              $statusClass = 'status-pending';
              break;
            case 'completed':
              $statusClass = 'status-completed';
              break;
            case 'cancelled':
              $statusClass = 'status-cancelled';
              break;
            case 'processing':
              $statusClass = 'status-processing';
              break;
            default:
              $statusClass = 'status-pending';
          }
        ?>
          <div class="order-card" data-status="<?php echo strtolower($order['order_status']); ?>" 
               data-date="<?php echo date('Y-m-d', strtotime($order['order_date'])); ?>">
            <div class="order-header">
              <div>
                <h5 class="mb-1">Order #<?php echo $order['booking_id']; ?></h5>
                <p class="mb-0 text-muted">Placed on <?php echo $orderDate; ?></p>
              </div>
              <div class="text-right">
                <span class="status-badge <?php echo $statusClass; ?>">
                  <?php echo ucfirst($order['order_status']); ?>
                </span>
                <?php if ($order['payment_status'] === 'Pending'): ?>
                  <span class="status-badge status-pending ml-2">Payment Pending</span>
                <?php elseif ($order['payment_status'] === 'Completed'): ?>
                  <span class="status-badge status-completed ml-2">Paid</span>
                <?php endif; ?>
              </div>
            </div>
            
            <div class="order-body">
              <div class="order-product">
                <img src="<?php echo htmlspecialchars($order['product_image']); ?>" 
                     alt="<?php echo htmlspecialchars($order['product_name']); ?>" 
                     class="product-image">
                <div class="product-details">
                  <h6 class="product-title"><?php echo htmlspecialchars($order['product_name']); ?></h6>
                  <p class="product-merchant">Sold by: <?php echo htmlspecialchars($order['merchant_name']); ?></p>
                  <p class="product-price">₹<?php echo number_format($order['price'], 2); ?></p>
                  
                  <div class="order-actions">
                    <a href="product_detail.php?id=<?php echo $order['product_id']; ?>" class="btn btn-sm btn-outline-primary">View Product</a>
                    <?php if (strtolower($order['order_status']) === 'completed'): ?>
                      <button class="btn btn-sm btn-outline-success">Buy Again</button>
                    <?php endif; ?>
                    <?php if (strtolower($order['order_status']) === 'pending' || strtolower($order['order_status']) === 'processing'): ?>
                      <button class="btn btn-sm btn-outline-danger cancel-order" data-order-id="<?php echo $order['booking_id']; ?>">Cancel Order</button>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              
              <div class="order-summary">
                <div class="row">
                  <div class="col-md-4">
                    <h6>Delivery Information</h6>
                    <p class="mb-1"><strong>Booking Date:</strong> <?php echo $bookingDate; ?></p>
                    <p class="mb-1"><strong>Booking Time:</strong> <?php echo $order['booking_time']; ?></p>
                  </div>
                  <div class="col-md-4">
                    <h6>Payment Information</h6>
                    <p class="mb-1"><strong>Payment Method:</strong> <?php echo htmlspecialchars(isset($order['payment_method']) ? $order['payment_method'] : 'N/A'); ?></p>
                    <p class="mb-1"><strong>Amount Paid:</strong> ₹<?php echo number_format($order['payment_amount'], 2); ?></p>
                  </div>
                  <div class="col-md-4">
                    <h6>Order Information</h6>
                    <p class="mb-1"><strong>Transaction ID:</strong> <?php echo htmlspecialchars(isset($order['transaction_id']) ? $order['transaction_id'] : 'N/A'); ?></p>
                    <p class="mb-1"><strong>Payment Date:</strong> <?php echo $paymentDate; ?></p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-orders">
          <span class="mai-cart-outline" style="font-size: 3rem; color: #ccc;"></span>
          <h3 class="mt-3">No Orders Found</h3>
          <p>You haven't placed any orders yet.</p>
          <a href="marketplace.php" class="btn btn-primary mt-2">Start Shopping</a>
        </div>
      <?php endif; ?>
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
    $(document).ready(function() {
      // Filter orders by status
      $('#apply-filters').click(function() {
        const statusFilter = $('#status-filter').val();
        const dateFilter = $('#date-filter').val();
        
        $('.order-card').each(function() {
          const orderStatus = $(this).data('status');
          const orderDate = $(this).data('date');
          let showOrder = true;
          
          // Apply status filter
          if (statusFilter !== 'all' && orderStatus !== statusFilter) {
            showOrder = false;
          }
          
          // Apply date filter
          if (dateFilter !== 'all') {
            const today = new Date();
            const orderDateObj = new Date(orderDate);
            
            if (dateFilter === 'last30') {
              const thirtyDaysAgo = new Date();
              thirtyDaysAgo.setDate(today.getDate() - 30);
              if (orderDateObj < thirtyDaysAgo) showOrder = false;
            } else if (dateFilter === 'last90') {
              const ninetyDaysAgo = new Date();
              ninetyDaysAgo.setDate(today.getDate() - 90);
              if (orderDateObj < ninetyDaysAgo) showOrder = false;
            } else if (dateFilter === '2024') {
              if (orderDateObj.getFullYear() !== 2024) showOrder = false;
            } else if (dateFilter === '2023') {
              if (orderDateObj.getFullYear() !== 2023) showOrder = false;
            }
          }
          
          if (showOrder) {
            $(this).show();
          } else {
            $(this).hide();
          }
        });
      });
      
      // Reset filters
      $('#reset-filters').click(function() {
        $('#status-filter').val('all');
        $('#date-filter').val('all');
        $('.order-card').show();
      });
      
      // Cancel order button
      $(document).ready(function() {
    // Cancel order button
    $('.cancel-order').click(function() {
        const orderId = $(this).data('order-id');
        const button = $(this);
        
        if (confirm('Are you sure you want to cancel this order?')) {
            button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
            
            // AJAX call to cancel order
            $.ajax({
                url: 'my_orders.php',
                type: 'POST',
                data: {
                    cancel_order: true,
                    booking_id: orderId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        // Remove the order card from view or update its status
                        button.closest('.order-card').fadeOut(300, function() {
                            $(this).remove();
                            // If no orders left, show empty state
                            if ($('.order-card').length === 0) {
                                $('<div class="no-orders"><span class="mai-cart-outline" style="font-size: 3rem; color: #ccc;"></span><h3 class="mt-3">No Orders Found</h3><p>You haven\'t placed any orders yet.</p><a href="marketplace.php" class="btn btn-primary mt-2">Start Shopping</a></div>')
                                    .appendTo('section.mb-5 .container');
                            }
                        });
                    } else {
                        alert(response.message);
                        button.prop('disabled', false).text('Cancel Order');
                    }
                },
                error: function() {
                    alert('Error communicating with server. Please try again.');
                    button.prop('disabled', false).text('Cancel Order');
                }
            });
        }
    });
});
      
      // Initialize with all orders showing
      $('#apply-filters').click();
    });
  </script>
</body>
</html>
<?php
    // End output buffering and send output
    $output = ob_get_clean();
    echo $output;

} catch (PDOException $e) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Error loading your orders: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
    exit();
}
?>