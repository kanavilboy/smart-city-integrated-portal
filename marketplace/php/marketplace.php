<?php
// Database connection
      require '../../database.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Marketplace - Smart City</title>

  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    .deal-badge {
      position: absolute;
      top: 10px;
      right: 10px;
      background: #ff4757;
      color: white;
      padding: 5px 10px;
      border-radius: 3px;
      font-size: 12px;
      font-weight: bold;
    }
    .merchant-card {
      transition: transform 0.3s;
    }
    .merchant-card:hover {
      transform: translateY(-5px);
    }
    .category-card {
      border-radius: 10px;
      overflow: hidden;
      transition: all 0.3s;
      border: none;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .category-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0,0,0,0.2);
    }
    .testimonial-card {
      background: white;
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .stats-item {
      text-align: center;
      padding: 20px;
      background: #00008B;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .stats-item .count {
      font-size: 2.5rem;
      font-weight: bold;
      color: #00d289;
    }
  </style>
</head>

<body>

  <div class="back-to-top"></div>

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

  <div class="page-hero bg-image overlay-dark" style="background-image: url(../assets/img/marketplace_bg.jpg);">
    <div class="hero-section">
      <div class="container text-center wow zoomIn">
        <h1 class="display-4">Your Local Marketplace</h1>
        <p class="text-white mb-4">Shop from hundreds of local businesses and discover amazing deals</p>
        <a href="#categories" class="btn btn-primary">Explore Now</a>
      </div>
    </div>
  </div>

  <!-- Stats Section -->
  <div class="page-section py-5 bg-primary text-white">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-3 py-3 py-md-0">
          <div class="stats-item">
            <div class="count">500+</div>
            <p>Local Merchants</p>
          </div>
        </div>
        <div class="col-md-3 py-3 py-md-0">
          <div class="stats-item">
            <div class="count">10,000+</div>
            <p>Products</p>
          </div>
        </div>
        <div class="col-md-3 py-3 py-md-0">
          <div class="stats-item">
            <div class="count">50,000+</div>
            <p>Happy Customers</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Categories Section -->
  <div class="page-section" id="categories">
    <div class="container">
      <h1 class="text-center mb-5 wow fadeInUp">Shop By Category</h1>
      
      <div class="row">
        <div class="col-lg-3 col-md-6 py-3 wow fadeInUp">
          <div class="category-card">
            <div class="header">
              <img src="../assets/img/categories/food.jpg" alt="" class="img-fluid">
              <div class="body text-center p-3">
                <h5>Food & Dining</h5>
                <p class="text-grey">200+ Restaurants</p>
                <a href="#" class="btn btn-sm btn-outline-primary">Explore</a>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-3 col-md-6 py-3 wow fadeInUp" data-wow-delay="200ms">
          <div class="category-card">
            <div class="header">
              <img src="../assets/img/categories/fashion.jpg" alt="" class="img-fluid">
              <div class="body text-center p-3">
                <h5>Fashion</h5>
                <p class="text-grey">150+ Boutiques</p>
                <a href="#" class="btn btn-sm btn-outline-primary">Explore</a>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-3 col-md-6 py-3 wow fadeInUp" data-wow-delay="400ms">
          <div class="category-card">
            <div class="header">
              <img src="../assets/img/categories/electronics.jpg" alt="" class="img-fluid">
              <div class="body text-center p-3">
                <h5>Electronics</h5>
                <p class="text-grey">80+ Stores</p>
                <a href="#" class="btn btn-sm btn-outline-primary">Explore</a>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-3 col-md-6 py-3 wow fadeInUp" data-wow-delay="600ms">
          <div class="category-card">
            <div class="header">
              <img src="../assets/img/categories/groceries.jpg" alt="" class="img-fluid">
              <div class="body text-center p-3">
                <h5>Groceries</h5>
                <p class="text-grey">120+ Stores</p>
                <a href="#" class="btn btn-sm btn-outline-primary">Explore</a>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="text-center mt-4">
        <a href="categories.php" class="btn btn-primary">View All Categories</a>
      </div>
    </div>
  </div>
<!-- Daily Deals -->
<div class="page-section">
  <div class="container">
    <h1 class="text-center mb-5 wow fadeInUp">Today's Best Deals</h1>
    
    <div class="row">
      <?php
      
      
      try {
          // Fetch daily deals
          $query = "SELECT p.*, m.name 
                    FROM products p
                    JOIN merchants m ON p.merchant_id = m.id
                    WHERE p.status = 'active' 
                    ORDER BY p.created_at DESC
                    LIMIT 4"; //AND p.stock > 0
          
          $stmt = $conn->prepare($query);
          $stmt->execute();
          $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
          
          if(count($products) > 0) {
              $delay = 0;
              foreach($products as $product) {
                  
                  // Display product card
                  echo '
                  <div class="col-md-6 col-lg-3 py-3 wow fadeInUp" data-wow-delay="'.$delay.'ms">
                    <div class="card-doctor">
                      <div class="header">
                        <img src="'.$product['product_image'].'" alt="'.htmlspecialchars($product['product_name']).'">
                        <div class="meta">
                          <a href="order.php?id='.$product['id'].'"><span class="mai-cart"></span></a>
                        </div>
                      </div>
                      <div class="body">
                        <p class="text-xl mb-0">'.htmlspecialchars($product['product_name']).'</p>
                        <span class="text-sm text-grey">'.htmlspecialchars($product['name']).'</span>
                        <p class="text-sm">
						<span class="text-primary">₹'.number_format($product['price'], 2).'</span>
						</p>
                      </div>
                    </div>
                  </div>';
                  
                  $delay += 200;
              }
          } else {
              echo '<div class="col-12 text-center py-5">
                      <p class="text-muted">No deals available at the moment. Check back later!</p>
                    </div>';
          }
      } catch(PDOException $e) {
          echo '<div class="col-12 text-center py-5">
                  <p class="text-danger">Error loading products. Please try again later.</p>
                </div>';
          // You might want to log this error: error_log($e->getMessage());
      }
      ?>
    </div>
    
    <div class="text-center mt-4">
      <a href="products.php" class="btn btn-primary">View More Deals</a>
    </div>
  </div>
</div>
  <!-- Featured Merchants -->
<div class="page-section bg-light">
  <div class="container">
    <h1 class="text-center mb-5 wow fadeInUp">Featured Merchants</h1>
    <p class="text-center mb-5">Discover top-rated local businesses in your city</p>

    <div class="owl-carousel wow fadeInUp" id="merchantSlideshow">
      <?php
      
      try {
          // Prepare and execute query
          $stmt = $conn->prepare("SELECT * FROM merchants ORDER BY created_at DESC LIMIT 4");
          $stmt->execute();
          $merchants = $stmt->fetchAll(PDO::FETCH_ASSOC);
          
          if(count($merchants) > 0) {
              foreach($merchants as $merchant) {
                  // Determine profile image path
                  $profile_image = !empty($merchant['profile_image']) ? 
                      htmlspecialchars($merchant['profile_image']) : 
                      '../assets/img/merchants/default.jpg';
                  
                  echo '<div class="item">
                    <div class="card-doctor merchant-card">
                      <div class="header">
                        <img src="'.$profile_image.'" alt="'.htmlspecialchars($merchant['name']).'">
                      </div>
                      <div class="body">
                        <p class="text-xl mb-0"><a href="profile.php?id='.(int)$merchant['id'].'">'.htmlspecialchars($merchant['name']).'</a></p>
                        <p><span class="text-sm text-grey">'.htmlspecialchars(ucfirst($merchant['merchant_type'])).'</span></p>
                      </div>
                    </div>
                  </div>';
              }
          } else {
              echo '<div class="col-12 text-center"><p>No featured merchants found.</p></div>';
          }
      } catch(PDOException $e) {
          echo '<div class="col-12 text-center"><p>Error loading merchants: '.htmlspecialchars($e->getMessage()).'</p></div>';
      }
      ?>
    </div>
    
    <div class="text-center mt-4">
      <a href="merchants.php" class="btn btn-primary">View All Merchants</a>
    </div>
  </div>
</div>

  <!-- Testimonials -->
  <div class="page-section bg-light">
    <div class="container">
      <h1 class="text-center mb-5 wow fadeInUp">What Our Customers Say</h1>
      
      <div class="owl-carousel wow fadeInUp" id="testimonialCarousel">
        <div class="item">
          <div class="testimonial-card">
            <div class="text-center mb-4">
              <span class="mai-star text-warning"></span>
              <span class="mai-star text-warning"></span>
              <span class="mai-star text-warning"></span>
              <span class="mai-star text-warning"></span>
              <span class="mai-star text-warning"></span>
            </div>
            <p class="mb-4">"The Smart City marketplace has transformed how I shop locally. I can now discover amazing small businesses in my area that I never knew existed!"</p>
            <div class="d-flex align-items-center">
              <div class="rounded-circle overflow-hidden" style="width: 50px; height: 50px;">
                <img src="../assets/img/testimonials/person_1.jpg" alt="" class="img-fluid">
              </div>
              <div class="ml-3">
                <h6 class="mb-0">Aamir Khan</h6>
                <p class="text-sm text-grey mb-0">Srinagar</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="item">
          <div class="testimonial-card">
            <div class="text-center mb-4">
              <span class="mai-star text-warning"></span>
              <span class="mai-star text-warning"></span>
              <span class="mai-star text-warning"></span>
              <span class="mai-star text-warning"></span>
              <span class="mai-star-half text-warning"></span>
            </div>
            <p class="mb-4">"As a small restaurant owner, joining this platform has increased our visibility and sales by over 40%. The support team is fantastic!"</p>
            <div class="d-flex align-items-center">
              <div class="rounded-circle overflow-hidden" style="width: 50px; height: 50px;">
                <img src="../assets/img/testimonials/person_2.jpg" alt="" class="img-fluid">
              </div>
              <div class="ml-3">
                <h6 class="mb-0">Fatima Bhat</h6>
                <p class="text-sm text-grey mb-0">Restaurant Owner</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="item">
          <div class="testimonial-card">
            <div class="text-center mb-4">
              <span class="mai-star text-warning"></span>
              <span class="mai-star text-warning"></span>
              <span class="mai-star text-warning"></span>
              <span class="mai-star text-warning"></span>
              <span class="mai-star text-warning"></span>
            </div>
            <p class="mb-4">"I love the convenience of ordering from multiple local stores in one place. The delivery is always prompt and the products are high quality."</p>
            <div class="d-flex align-items-center">
              <div class="rounded-circle overflow-hidden" style="width: 50px; height: 50px;">
                <img src="../assets/img/testimonials/person_3.jpg" alt="" class="img-fluid">
              </div>
              <div class="ml-3">
                <h6 class="mb-0">Rajesh Kumar</h6>
                <p class="text-sm text-grey mb-0">Jammu</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Merchant Signup CTA -->
  <div class="page-section bg-primary text-white">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-8 py-3 wow fadeInUp">
          <h2>Are you a local business?</h2>
          <p class="mb-0">Join our marketplace and reach thousands of customers in your city. Grow your business with our powerful platform.</p>
        </div>
        <div class="col-lg-4 text-lg-right py-3 wow fadeInRight">
          <a href="merchant_signup.php" class="btn btn-light">Register Your Business</a>
        </div>
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
    // Initialize carousels
    $(document).ready(function(){
      $("#merchantSlideshow").owlCarousel({
        loop: true,
        margin: 20,
        nav: true,
        responsive: {
          0: { items: 1 },
          600: { items: 2 },
          1000: { items: 4 }
        }
      });
      
      $("#testimonialCarousel").owlCarousel({
        loop: true,
        margin: 30,
        nav: true,
        responsive: {
          0: { items: 1 },
          600: { items: 1 },
          1000: { items: 2 }
        }
      });
    });
  </script>

</body>

</html>