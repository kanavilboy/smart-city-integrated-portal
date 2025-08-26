<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../database.php';

// Check if product ID is provided and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: marketplace.php");
    exit();
}

$product_id = (int)$_GET['id'];

try {
    // Get product details
    $productQuery = "SELECT p.*, m.name as merchant_name, m.merchant_type 
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
    
    // Get related products from same merchant
    $relatedQuery = "SELECT * FROM products 
                    WHERE merchant_id = ? AND id != ? AND status = 'active' 
                    ORDER BY RAND() LIMIT 4";
    $relatedStmt = $conn->prepare($relatedQuery);
    $relatedStmt->execute(array($product['merchant_id'], $product_id));
    $relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Start output buffering
    ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $product['product_name']; ?> - Smart City</title>
  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    .product-hero {
      background-color: #f8f9fa;
      padding: 3rem 0;
      margin-bottom: 3rem;
    }
    
    .product-image-main {
      width: 100%;
      height: 400px;
      object-fit: contain;
      background: white;
      border-radius: 8px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .product-thumbnail {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 4px;
      cursor: pointer;
      border: 2px solid transparent;
      transition: all 0.3s;
    }
    
    .product-thumbnail:hover, .product-thumbnail.active {
      border-color: #00d289;
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
    
    .product-price {
      font-size: 1.8rem;
      font-weight: 700;
      color: #00d289;
    }
    
    .product-old-price {
      text-decoration: line-through;
      color: #6c757d;
      margin-left: 0.5rem;
    }
    
    .product-actions .btn {
      padding: 0.75rem 1.5rem;
      font-weight: 600;
    }
    
    .product-details-section {
      padding: 2rem 0;
    }
    
    .related-products .card {
      transition: transform 0.3s;
    }
    
    .related-products .card:hover {
      transform: translateY(-5px);
    }
    
    .product-tabs .nav-link {
      color: #495057;
      font-weight: 600;
    }
    
    .product-tabs .nav-link.active {
      color: #00d289;
      border-bottom: 3px solid #00d289;
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

  <!-- Product Hero Section -->
  <section class="product-hero">
    <div class="container">
      <div class="row">
        <div class="col-md-5">
		<?php echo '<img src="'.htmlspecialchars($product['product_image']).'" alt="'.htmlspecialchars($product['product_name']).'" id="mainProductImage"  class="product-image-main">'; ?> 
        </div>
        <div class="col-md-6">
          <span class="merchant-badge"><?php echo $product['merchant_type']; ?></span>
          <h1 class="mb-3"><?php echo $product['product_name']; ?></h1>
          <p class="text-muted">Sold by: <a href="profile.php?id=<?php echo $product['merchant_id']; ?>"><?php echo $product['merchant_name']; ?></a></p>
          
          <div class="d-flex align-items-center mb-4">
            <span class="product-price">₹<?php echo number_format($product['price'], 2); ?></span>
          </div>
          
          <div class="product-actions mb-5">
            <div class="d-flex">
              <a href="order.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">Buy Now</a>
            </div>
          </div>
          
          <div class="border-top pt-3">
            <div class="d-flex mb-2">
              <span class="text-muted mr-3" style="width: 100px;">Category:</span>
              <span><?php echo $product['category']; ?></span>
            </div>
            <div class="d-flex mb-2">
              <span class="text-muted mr-3" style="width: 100px;">Stock:</span>
              <span><?php echo $product['stock'] > 0 ? 'In Stock ('.$product['stock'].' available)' : 'Out of Stock'; ?></span>
            </div>
            <div class="d-flex">
              <span class="text-muted mr-3" style="width: 100px;">Delivery:</span>
              <span>Free delivery within 5km</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Product Details Section -->
  <section class="product-details-section">
    <div class="container">
      <ul class="nav product-tabs mb-4">
        <li class="nav-item">
          <a class="nav-link active" data-toggle="tab" href="#description">Description</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-toggle="tab" href="#specifications">Specifications</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-toggle="tab" href="#reviews">Reviews</a>
        </li>
      </ul>
      
      <div class="tab-content">
        <div class="tab-pane fade show active" id="description">
          <h4 class="mb-3">Product Description</h4>
          <p><?php echo nl2br($product['description']); ?></p>
        </div>
        
        <div class="tab-pane fade" id="reviews">
          <h4 class="mb-3">Customer Reviews</h4>
          <div class="alert alert-info">
            No reviews yet. Be the first to review this product!
          </div>
          <button class="btn btn-primary">Write a Review</button>
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
  <script src="../assets/vendor/wow/wow.min.js"></script>
  <script src="../assets/js/theme.js"></script>
  <script>
    // Product image gallery functionality
    function changeMainImage(thumb) {
      document.getElementById('mainProductImage').src = thumb.src;
      // Update active thumbnail
      document.querySelectorAll('.product-thumbnail').forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
    }
    
    // Quantity controls
    function incrementQuantity() {
      const input = document.getElementById('productQuantity');
      input.value = parseInt(input.value) + 1;
    }
    
    function decrementQuantity() {
      const input = document.getElementById('productQuantity');
      if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
      }
    }
    
    // Add to cart functionality
    function addToCart() {
      const quantity = document.getElementById('productQuantity').value;
      // In a real app, you would make an AJAX call here
      alert('Added ' + quantity + ' of <?php echo $product["product_name"]; ?> to cart!');
      // window.location.href = 'add_to_cart.php?product_id=<?php echo $product_id; ?>&quantity=' + quantity;
    }
    
    new WOW().init();
  </script>
</body>
</html>
<?php
    // End output buffering and send output
    $output = ob_get_clean();
    echo $output;

} catch (PDOException $e) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Error loading product: ' . $e->getMessage() . '</div></div>';
    exit();
}
?>