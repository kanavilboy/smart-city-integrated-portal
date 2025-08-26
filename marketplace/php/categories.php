<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

require_once '../../database.php';

try {
    // Get all distinct categories with product counts
    $categoryQuery = "SELECT category, COUNT(*) as product_count 
                     FROM products 
                     WHERE status = 'active'
                     GROUP BY category 
                     ORDER BY category";
    $categoryStmt = $conn->prepare($categoryQuery);
    $categoryStmt->execute();
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all products grouped by category
    $productsQuery = "SELECT * FROM products WHERE status = 'active' ORDER BY category, product_name";
    $productsStmt = $conn->prepare($productsQuery);
    $productsStmt->execute();
    $allProducts = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Group products by category
    $productsByCategory = array();
    foreach ($allProducts as $product) {
        $category = $product['category'];
        if (!isset($productsByCategory[$category])) {
            $productsByCategory[$category] = array();
        }
        $productsByCategory[$category][] = $product;
    }

    // Start output buffering
    ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Categories - Smart City</title>
  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    .category-hero {
      background-color: #f8f9fa;
      padding: 3rem 0;
      margin-bottom: 3rem;
    }
    
    .category-section {
      margin-bottom: 4rem;
    }
    
    .category-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      padding-bottom: 0.5rem;
      border-bottom: 2px solid #00d289;
    }
    
    .category-title {
      font-size: 1.5rem;
      font-weight: 600;
      color: #333;
      margin: 0;
    }
    
    .product-count {
      background: #00d289;
      color: white;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.9rem;
    }
    
    .product-card {
      border: 1px solid #eee;
      border-radius: 8px;
      overflow: hidden;
      transition: all 0.3s;
      margin-bottom: 20px;
      height: 100%;
    }
    
    .product-card:hover {
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      transform: translateY(-5px);
    }
    
    .product-img-container {
      height: 180px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f9f9f9;
    }
    
    .product-img {
      max-height: 100%;
      width: auto;
      max-width: 100%;
      object-fit: contain;
    }
    
    .product-body {
      padding: 15px;
    }
    
    .product-title {
      font-size: 1rem;
      margin-bottom: 10px;
      height: 40px;
      overflow: hidden;
    }
    
    .product-price {
      font-size: 1.1rem;
      font-weight: bold;
      color: #00d289;
    }
    
    .product-stock {
      font-size: 0.8rem;
      margin-top: 5px;
    }
    
    .in-stock {
      color: #00d289;
    }
    
    .out-of-stock {
      color: #dc3545;
    }
    
    .view-all {
      text-align: right;
      margin-top: 1rem;
    }
    
    .no-products {
      text-align: center;
      padding: 50px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .alphabet-nav {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      margin-bottom: 2rem;
      background: white;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .alphabet-link {
      padding: 5px 10px;
      margin: 0 5px;
      color: #333;
      text-decoration: none;
      font-weight: 600;
    }
    
    .alphabet-link:hover, .alphabet-link.active {
      color: #00d289;
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
            <li class="nav-item active"><a class="nav-link" href="categories.php">Categories</a></li>
            <li class="nav-item"><a class="nav-link" href="history.php">My Orders</a></li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <!-- Category Hero Section -->
  <section class="category-hero">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-12">
          <h1 class="mb-3">Product Categories</h1>
          <p class="mb-0">Browse products by category</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Category Listing Section -->
  <section class="mb-5">
    <div class="container">
      <!-- Alphabet Navigation -->
      <div class="alphabet-nav">
        <?php 
        $letters = range('A', 'Z');
        foreach ($letters as $letter): ?>
          <a href="#category-<?php echo $letter; ?>" class="alphabet-link"><?php echo $letter; ?></a>
        <?php endforeach; ?>
      </div>
      
      <?php if (count($categories) > 0): ?>
        <?php foreach ($categories as $category): 
          $firstLetter = strtoupper(substr($category['category'], 0, 1));
          $categoryProducts = isset($productsByCategory[$category['category']]) 
    ? $productsByCategory[$category['category']] 
    : array();

        ?>
          <div class="category-section" id="category-<?php echo $firstLetter; ?>">
            <div class="category-header">
              <h2 class="category-title"><?php echo htmlspecialchars($category['category']); ?></h2>
              <span class="product-count"><?php echo $category['product_count']; ?> products</span>
            </div>
            
            <?php if (count($categoryProducts) > 0): ?>
              <div class="row">
                <?php foreach ($categoryProducts as $product): ?>
                  <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="product-card">
                      <div class="product-img-container">
                        <img src="<?php echo htmlspecialchars($product['product_image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                             class="product-img">
                      </div>
                      <div class="product-body">
                        <h5 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                        <div class="d-flex justify-content-between align-items-center">
                          <span class="product-price">₹<?php echo number_format($product['price'], 2); ?></span>
                          
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                          <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
              
              <div class="view-all">
                <a href="category_products.php?category=<?php echo urlencode($category['category']); ?>" class="btn btn-outline-primary">
                  View All <?php echo htmlspecialchars($category['category']); ?> Products
                </a>
              </div>
            <?php else: ?>
              <div class="no-products">
                <p>No products available in this category.</p>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-products">
          <span class="mai-cart-outline" style="font-size: 3rem; color: #ccc;"></span>
          <h3 class="mt-3">No Categories Available</h3>
          <p>There are currently no product categories in the marketplace.</p>
          <a href="marketplace.php" class="btn btn-primary mt-2">Browse Marketplace</a>
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
      // Add to cart functionality
      $('.add-to-cart').click(function() {
        const productId = $(this).data('product-id');
        const button = $(this);
        
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...');
        
        // In a real application, this would be an AJAX call to your server
        setTimeout(function() {
          button.html('Added to Cart').removeClass('btn-primary').addClass('btn-success');
          
          // Update cart count in navbar (you would need to implement this)
          // const cartCount = parseInt($('.cart-count').text()) || 0;
          // $('.cart-count').text(cartCount + 1);
          
          // Show success message
          alert('Product added to cart successfully!');
        }, 1000);
      });
      
      // Search functionality
      $('#search-input').on('keyup', function() {
        const searchText = $(this).val().toLowerCase();
        
        $('.category-section').each(function() {
          const categoryText = $(this).find('.category-title').text().toLowerCase();
          if (categoryText.includes(searchText)) {
            $(this).show();
          } else {
            $(this).hide();
          }
        });
      });
      
      // Highlight current letter in alphabet nav
      $(window).on('scroll', function() {
        const scrollPosition = $(window).scrollTop();
        
        $('.category-section').each(function() {
          const sectionTop = $(this).offset().top - 100;
          const sectionBottom = sectionTop + $(this).outerHeight();
          
          if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
            const sectionId = $(this).attr('id');
            const letter = sectionId.split('-')[1];
            
            $('.alphabet-link').removeClass('active');
            $(`.alphabet-link[href="#${sectionId}"]`).addClass('active');
          }
        });
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
    echo '<div class="container mt-5"><div class="alert alert-danger">Error loading categories: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
    exit();
}
?>