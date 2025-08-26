<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../database.php';

// Check if merchant ID is provided and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: merchants.php");
    exit();
}

$merchant_id = (int)$_GET['id'];

try {
    // Get merchant details
    $merchantQuery = "SELECT * FROM merchants WHERE id = ?";
    $merchantStmt = $conn->prepare($merchantQuery);
    $merchantStmt->execute(array($merchant_id));
    $merchant = $merchantStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$merchant) {
        header("Location: merchants.php");
        exit();
    }
    
    // Get merchant's products
    $productsQuery = "SELECT * FROM products WHERE merchant_id = ? AND status = 'active' ORDER BY created_at DESC";
    $productsStmt = $conn->prepare($productsQuery);
    $productsStmt->execute(array($merchant_id));
    $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Start output buffering
    ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Merchant Profile - Smart City</title>
  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    .merchant-profile-header {
      background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('../assets/img/merchant-profile-bg.jpg');
      background-size: cover;
      background-position: center;
      color: white;
      padding: 4rem 0;
      margin-bottom: 3rem;
    }
    .merchant-avatar {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid white;
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .merchant-badge {
      display: inline-block;
      background: #00d289;
      color: white;
      padding: 0.25rem 1rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-bottom: 1rem;
    }
    .product-card {
      transition: transform 0.3s;
      margin-bottom: 20px;
      height: 100%;
    }
    .product-card:hover {
      transform: translateY(-5px);
    }
    .product-img {
      height: 200px;
      object-fit: cover;
    }
    .contact-info-card {
      background: white;
      border-radius: 10px;
      padding: 1.5rem;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      margin-bottom: 2rem;
    }
    .contact-icon {
      color: #00d289;
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }
    .section-title {
      position: relative;
      padding-bottom: 10px;
      margin-bottom: 20px;
    }
    .section-title:after {
      content: "";
      position: absolute;
      bottom: 0;
      left: 0;
      width: 50px;
      height: 3px;
      background: #00d289;
    }
    .social-links a {
      display: inline-block;
      width: 36px;
      height: 36px;
      background: #f1f1f1;
      color: #333;
      border-radius: 50%;
      text-align: center;
      line-height: 36px;
      margin-right: 8px;
      transition: all 0.3s;
    }
    .social-links a:hover {
      background: #00d289;
      color: white;
      transform: translateY(-3px);
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
            <li class="nav-item active"><a class="nav-link" href="merchants.php">Merchants</a></li>
			<li class="nav-item"><a class="nav-link" href="categories.php">Categories</a></li>
            <li class="nav-item"><a class="nav-link" href="history.php">My Orders</a></li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <!-- Merchant Profile Header -->
  <div class="merchant-profile-header">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-2 text-center">
          <?php echo '<img src="'.htmlspecialchars($merchant['profile_image']).'" alt="'.htmlspecialchars($merchant['name']).'" class="merchant-avatar">';?>
        </div>
        <div class="col-md-8">
          <span class="merchant-badge"><?php echo ucwords($merchant['merchant_type']); ?></span>
          <h1 class="text-white mb-2"><?php echo $merchant['name']; ?></h1>
          <p class="text-white-50 mb-0"><?php echo $merchant['description']; ?></p>
        </div>
      </div>
    </div>
  </div>

  <div class="page-section">
    <div class="container">
      <div class="row">
        <!-- Left Column - Contact Info -->
        <div class="col-lg-4">
          <div class="contact-info-card wow fadeInUp">
            <h3 class="section-title">Contact Information</h3>
            
            <ul class="list-unstyled">
              <li class="mb-3">
                <span class="mai-location contact-icon"></span>
                <?php echo $merchant['address']; ?>
              </li>
              <li class="mb-3">
                <span class="mai-call contact-icon"></span>
                <?php echo $merchant['contact']; ?>
              </li>
              <li class="mb-3">
                <span class="mai-mail contact-icon"></span>
                <?php echo $merchant['email']; ?>
              </li>
            </ul>
            
            <hr>
            
            <h5 class="mb-3">Connect With Us</h5>
            <div class="social-links">
              <a href="#"><span class="mai-logo-facebook-f"></span></a>
              <a href="#"><span class="mai-logo-instagram"></span></a>
              <a href="#"><span class="mai-logo-twitter"></span></a>
              <a href="#"><span class="mai-logo-whatsapp"></span></a>
            </div>
          </div>
        </div>
        
        <!-- Right Column - Products -->
        <div class="col-lg-8">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title">Our Products</h2>
            <a href="products.php?merchant_id=<?php echo $merchant_id; ?>" class="btn btn-outline-primary">View All Products</a>
          </div>
          
          <?php if (count($products) > 0): ?>
            <div class="row">
              <?php foreach ($products as $product): ?>
                <div class="col-md-6 col-lg-6 mb-4 wow fadeInUp">
                  <div class="card product-card">
                    <div class="card-img-top">
                      <?php echo '<img src="'.htmlspecialchars($product['product_image']).'" alt="'.htmlspecialchars($product['product_name']).'" class="product-img w-100">'; ?> 
                    </div>
                    <div class="card-body">
                      <h5 class="card-title"><?php echo $product['product_name']; ?></h5>
                      <p class="text-primary font-weight-bold">₹<?php echo number_format($product['price'], 2); ?></p>
                      <p class="card-text text-muted"><?php echo substr($product['description'], 0, 100); ?>...</p>
                      <div class="d-flex justify-content-between">
                        <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                        <a href="order.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                          <span class="mai-cart"></span> Buy Now
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="alert alert-info wow fadeInUp">
              This merchant currently has no products available.
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

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
  <script src="../assets/vendor/wow/wow.min.js"></script>
  <script src="../assets/js/theme.js"></script>
  <script>
    new WOW().init();
  </script>
</body>
</html>
<?php
    // End output buffering and send output
    $output = ob_get_clean();
    echo $output;

} catch (PDOException $e) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Error loading merchant: ' . $e->getMessage() . '</div></div>';
    exit();
}
?>