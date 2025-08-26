<?php
require '../../database.php';

// Get search query
$searchQuery = isset($_GET['query']) ? trim($_GET['query']) : '';

// Initialize results arrays
$products = array();
$merchants = array();

if (!empty($searchQuery)) {
    // Search products
    $productStmt = $conn->prepare("
        SELECT p.*, m.name as merchant_name 
        FROM products p
        JOIN merchants m ON p.merchant_id = m.id
        WHERE (p.product_name LIKE :query 
               OR p.description LIKE :query 
               OR p.category LIKE :query)
          AND p.status = 'active'
        ORDER BY p.created_at DESC
    ");
    $productStmt->execute(array('query' => "%$searchQuery%"));
    $products = $productStmt->fetchAll();

    // Search merchants
    $merchantStmt = $conn->prepare("
        SELECT * FROM merchants 
        WHERE name LIKE :query 
           OR merchant_type LIKE :query 
           OR description LIKE :query
        ORDER BY created_at DESC
    ");
    $merchantStmt->execute(array('query' => "%$searchQuery%"));
    $merchants = $merchantStmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search Results - Smart City Marketplace</title>

  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    .search-results-header {
      background-color: #f8f9fa;
      padding: 30px 0;
      margin-bottom: 30px;
    }
    .result-card {
      transition: transform 0.3s;
      margin-bottom: 20px;
    }
    .result-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .no-results {
      padding: 50px 0;
      text-align: center;
    }
  </style>
</head>

<body>

  <div class="back-to-top"></div>

  <header>
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm">
      <div class="container">
        <a class="navbar-brand" href="../../smartcity.php"><span class="text-primary">Smart</span>-City</a>

        <form action="search_results.php" method="GET" class="ml-auto mr-3">
          <div class="input-group input-navbar">
            <div class="input-group-prepend">
              <span class="input-group-text" id="icon-addon1"><span class="mai-search"></span></span>
            </div>
            <input type="text" name="query" class="form-control" placeholder="Search products or merchants..." value="<?php echo htmlspecialchars($searchQuery); ?>" required>
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

  <div class="search-results-header">
    <div class="container">
      <h1 class="text-center">Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"</h1>
      <p class="text-center text-muted">Found <?php echo count($products) + count($merchants); ?> results</p>
    </div>
  </div>

  <div class="page-section">
    <div class="container">
      <!-- Products Results -->
      <div class="mb-5 wow fadeInUp">
        <h3 class="mb-4">Products</h3>
        <?php if (!empty($products)): ?>
          <div class="row">
            <?php foreach ($products as $product): 
              $imagePath = !empty($product['product_image']) ? $product['product_image'] : '../assets/img/product.png';
            ?>
              <div class="col-md-6 col-lg-3 py-3">
                <div class="card-doctor result-card">
                  <div class="header">
                    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                    <div class="meta">
                      <a href="order.php?id=<?php echo $product['id']; ?>"><span class="mai-cart"></span></a>
                    </div>
                  </div>
                  <div class="body">
                    <p class="text-xl mb-0"><?php echo htmlspecialchars($product['product_name']); ?></p>
                    <span class="text-sm text-grey"><?php echo htmlspecialchars($product['merchant_name']); ?></span>
                    <p class="text-primary">₹<?php echo number_format($product['price'], 2); ?></p>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="no-results">
            <p class="text-muted">No products found matching your search.</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Merchants Results -->
      <div class="mb-5 wow fadeInUp">
        <h3 class="mb-4">Merchants</h3>
        <?php if (!empty($merchants)): ?>
          <div class="row">
            <?php foreach ($merchants as $merchant): 
              $profileImage = !empty($merchant['profile_image']) ? $merchant['profile_image'] : '../assets/img/merchants/default.jpg';
            ?>
              <div class="col-md-6 col-lg-3 py-3">
                <div class="card-doctor result-card">
                  <div class="header">
                    <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="<?php echo htmlspecialchars($merchant['name']); ?>">
                  </div>
                  <div class="body">
                    <p class="text-xl mb-0"><?php echo htmlspecialchars($merchant['name']); ?></p>
                    <span class="text-sm text-grey"><?php echo htmlspecialchars(ucfirst($merchant['merchant_type'])); ?></span>
                    <a href="merchant.php?id=<?php echo $merchant['id']; ?>" class="btn btn-primary btn-sm mt-2">View Profile</a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="no-results">
            <p class="text-muted">No merchants found matching your search.</p>
          </div>
        <?php endif; ?>
      </div>

      <div class="text-center mt-5">
        <a href="marketplace.php" class="btn btn-primary">Back to Marketplace</a>
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
  <script src="../assets/vendor/owl-carousel/js/owl.carousel.min.js"></script>
  <script src="../assets/vendor/wow/wow.min.js"></script>
  <script src="../assets/js/theme.js"></script>
  
  <script>
    // Initialize animations
    new WOW().init();
  </script>

</body>
</html>