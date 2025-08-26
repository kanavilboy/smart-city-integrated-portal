<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

require_once '../../database.php';

try {
    // Get all active products with merchant information
    $productQuery = "SELECT p.*, m.name as merchant_name 
                    FROM products p
                    JOIN merchants m ON p.merchant_id = m.id
                    WHERE p.status = 'active'
                    ORDER BY p.created_at DESC";
    
    $productStmt = $conn->prepare($productQuery);
    $productStmt->execute();
    $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Start output buffering
    ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Products Marketplace - Smart City</title>
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
    
    .product-card {
      border: 1px solid #eee;
      border-radius: 8px;
      overflow: hidden;
      transition: all 0.3s;
      margin-bottom: 30px;
      height: 100%;
    }
    
    .product-card:hover {
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      transform: translateY(-5px);
    }
    
    .product-img-container {
      height: 200px;
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
      padding: 20px;
    }
    
    .product-title {
      font-size: 1.1rem;
      margin-bottom: 10px;
      height: 50px;
      overflow: hidden;
    }
    
    .product-merchant {
      font-size: 0.9rem;
      color: #666;
      margin-bottom: 10px;
    }
    
    .product-price {
      font-size: 1.25rem;
      font-weight: bold;
      color: #00d289;
    }
    
    .product-stock {
      font-size: 0.85rem;
      margin-top: 5px;
    }
    
    .in-stock {
      color: #00d289;
    }
    
    .out-of-stock {
      color: #dc3545;
    }
    
    .filter-section {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      margin-bottom: 30px;
    }
    
    .pagination {
      justify-content: center;
      margin-top: 30px;
    }
    
    .no-products {
      text-align: center;
      padding: 50px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
            <li class="nav-item active"><a class="nav-link" href="marketplace.php">Marketplace</a></li>
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
      <div class="row align-items-center">
        <div class="col-md-6">
          <h1 class="mb-3">Marketplace Products</h1>
          <p class="mb-0">Browse through our wide selection of products from local merchants</p>
        </div>
        <div class="col-md-6 text-md-right">
          <p class="mb-0">Showing <?php echo count($products); ?> products</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Product Listing Section -->
  <section class="mb-5">
    <div class="container">
      
      <?php if (count($products) > 0): ?>
        <div class="row" id="products-container">
          <?php foreach ($products as $product): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
              <div class="product-card">
                <div class="product-img-container">
                  <img src="<?php echo htmlspecialchars($product['product_image']); ?>" 
                       alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                       class="product-img">
                </div>
                <div class="product-body">
                  <h5 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                  <p class="product-merchant">Sold by: <?php echo htmlspecialchars($product['merchant_name']); ?></p>
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
        
        <!-- Pagination would go here if implemented -->
        <!-- <nav aria-label="Page navigation">
          <ul class="pagination">
            <li class="page-item disabled">
              <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
            </li>
            <li class="page-item active"><a class="page-link" href="#">1</a></li>
            <li class="page-item"><a class="page-link" href="#">2</a></li>
            <li class="page-item"><a class="page-link" href="#">3</a></li>
            <li class="page-item">
              <a class="page-link" href="#">Next</a>
            </li>
          </ul>
        </nav> -->
      <?php else: ?>
        <div class="no-products">
          <span class="mai-cart-outline" style="font-size: 3rem; color: #ccc;"></span>
          <h3 class="mt-3">No Products Available</h3>
          <p>There are currently no products in the marketplace.</p>
          <a href="marketplace.php" class="btn btn-primary mt-2">Refresh</a>
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
        
        $('.product-card').each(function() {
          const productText = $(this).text().toLowerCase();
          if (productText.includes(searchText)) {
            $(this).parent().show();
          } else {
            $(this).parent().hide();
          }
        });
      });
      
      // Filter functionality
      $('#apply-filters').click(function() {
        const category = $('#category-filter').val().toLowerCase();
        const priceRange = $('#price-filter').val();
        const sortBy = $('#sort-by').val();
        
        // In a real application, this would be an AJAX call to your server
        // For now, we'll just show an alert
        alert(`Filters applied:\nCategory: ${category || 'All'}\nPrice: ${priceRange || 'All'}\nSort By: ${sortBy}`);
        
        // You would implement actual filtering and sorting here
        // This would typically involve an AJAX call to your server with the filter parameters
        // and then updating the products-container with the returned HTML
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
    echo '<div class="container mt-5"><div class="alert alert-danger">Error loading products: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
    exit();
}
?>