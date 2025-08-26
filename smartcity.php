<?php
session_start();
require 'database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <meta http-equiv="X-UA-Compatible" content="ie=edge">

  <meta name="copyright" content="MACode ID, https://macodeid.com/">

  <title>SMARTCITY</title>

  <link rel="stylesheet" href="assets/css/maicons.css">

  <link rel="stylesheet" href="assets/css/bootstrap.css">

  <link rel="stylesheet" href="assets/vendor/owl-carousel/css/owl.carousel.css">

  <link rel="stylesheet" href="assets/vendor/animate/animate.css">

  <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body>

  <!-- Back to top button -->
  <div class="back-to-top"></div>

  <header>
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm">
      <div class="container">
        <a class="navbar-brand" href="#"><span class="text-primary">Smart</span>-City</a>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupport" aria-controls="navbarSupport" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupport">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item">
              <a class="nav-link active" href="smartcity.php">Home</a>
            </li>
			<li class="nav-item">
              <a class="nav-link" href="message/php/message.php">Message</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="marketplace/php/marketplace.php">Market</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="health/php/health.php">Health</a>
            </li>
			<li class="nav-item">
              <a class="nav-link" href="job/php/job.php">Jobs</a>
            </li>
			<li class="nav-item">
              <a class="nav-link" href="education/php/education.php">Education</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="utility/php/utility.php">Public services</a>
            </li>
			<?php if(isset($_SESSION['user_id'])): ?>
              <li class="nav-item">
              <a class="btn btn-primary ml-lg-3" href="login.php">Logout</a>
            </li>
            <?php else: ?>
              <li class="nav-item"><a class="btn btn-primary ml-lg-2" href="register.php">Register</a></li>
            <?php endif; ?>
          </ul>
        </div> <!-- .navbar-collapse -->
      </div> <!-- .container -->
    </nav>
  </header>
  
  <div class="page-hero bg-image overlay-dark" style="background-image: url(assets/img/kottayam.jpg);">
    <div class="hero-section">
      <div class="container text-center wow zoomIn">
        <span class="subhead">Let's make your life happier</span>
        <h1 class="display-4">Welcome to Kottayam</h1>
      </div>
    </div>
  </div>

  <div class="page-section pt-5">
    <div class="container">
      <div class="row">
        <div class="col-lg-8">
          <article class="blog-details">
            <div class="post-thumb">
              <img src="assets/img/map.png" alt="">
            </div>
            <h2 class="post-title h1">Kottayam</h2>
            <div class="post-content">
              <p>Kottayam, a district in Kerala, is renowned for its literacy, scenic beauty, and rich cultural heritage. Often called the "Land of Letters, Lakes, and Latex," it was the first district in India to achieve 100% literacy and is home to prestigious institutions like Mahatma Gandhi University and CMS College. Geographically, it is bordered by the Western Ghats on the east and Vembanad Lake on the west, featuring a tropical climate with heavy monsoons.</p>
			  <p>Kottayam is famous for its backwaters, with Kumarakom being a top tourist destination known for houseboat cruises and a bird sanctuary. The district is also a major center for rubber plantations and the publishing industry, housing prominent newspapers like Malayala Manorama and Deepika. Cultural and religious landmarks such as the Ettumanoor and Vaikom Mahadeva Temples add to its significance. Festivals like Onam, Vishu, and boat races in the backwaters make Kottayam a vibrant and culturally rich destination in Kerala.</p>
            </div>
          </article> <!-- .blog-details -->

          <div class="comment-form-wrap pt-5">
            <h3 class="mb-5">Leave a comment</h3>
            <form action="#" class="">
              <div class="form-row form-group">
                <div class="col-md-6">
                  <label for="name">Name *</label>
                  <input type="text" class="form-control" id="name">
                </div>
                <div class="col-md-6">
                  <label for="email">Email *</label>
                  <input type="email" class="form-control" id="email">
                </div>
              </div>
              <div class="form-group">
                <label for="website">Website</label>
                <input type="url" class="form-control" id="website">
              </div>
  
              <div class="form-group">
                <label for="message">Message</label>
                <textarea name="msg" id="message" cols="30" rows="8" class="form-control"></textarea>
              </div>
              <div class="form-group">
                <input type="submit" value="Post Comment" class="btn btn-primary">
              </div>
  
            </form>
          </div>
        </div>
		
        <div class="col-lg-4">
          <div class="sidebar">
            <div class="sidebar-block">
              <h3 class="sidebar-title">Search</h3>
              <form action="#" class="search-form">
                <div class="form-group">
                  <input type="text" class="form-control" placeholder="Type a keyword and hit enter">
                  <button type="submit" class="btn"><span class="icon mai-search"></span></button>
                </div>
              </form>
            </div>
            <div class="sidebar-block">
              <h3 class="sidebar-title">Services</h3>
              <ul class="categories">
                <li><a href="marketplace/php/marketplace.php">Market place</a></li>
                <li><a href="health/php/health.php">Health services</a></li>
                <li><a href="utility/php/utility.php">Utility payment</a></li>
                <li><a href="job/php/job.php">Jobs</a></li>
                <li><a href="education/php/education.php">Education</a></li>
              </ul>
            </div>

            <div class="sidebar-block">
			  <h3 class="sidebar-title">Market</h3>
			  <?php
			  // Fetch active products from database
			  $products = $conn->query("SELECT p.*, m.name as merchant_name 
									  FROM products p 
									  JOIN merchants m ON p.merchant_id = m.id 
									  WHERE p.status = 'active' 
									  ORDER BY p.created_at DESC LIMIT 3")->fetchAll();
			  
			  if ($products): 
				foreach ($products as $product): 
				  $imagePath = !empty($product['product_image']) ? $product['product_image'] : 'assets/img/product.png';
			  ?>
				<div class="blog-item">
				  <a class="post-thumb" href="marketplace/php/marketplace.php">
					<img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
				  </a>
				  <div class="content">
					<h5 class="post-title">
					  <a href="marketplace/php/marketplace.php"><?php echo htmlspecialchars($product['product_name']); ?></a>
					</h5>
					<div class="meta">
					  <span class="text-primary">₹<?php echo htmlspecialchars($product['price']); ?></span>
					  <small class="text-muted d-block"><?php echo htmlspecialchars($product['merchant_name']); ?></small>
					  <small class="text-muted"><?php echo date('M d, Y', strtotime($product['created_at'])); ?></small>
					</div>
				  </div>
				</div>
			  <?php 
				endforeach; 
			  else: 
			  ?>
				<div class="alert alert-info">No products available at the moment.</div>
			  <?php endif; ?>
			  <div class="text-center mt-3">
				<a href="marketplace/php/marketplace.php" class="btn btn-sm btn-primary">View All Products</a>
			  </div>
			</div>

            <div class="sidebar-block">
              <h3 class="sidebar-title">Easy access</h3>
              <div class="tagcloud">
                <a href="#" class="tag-cloud-link">Hotels</a>
                <a href="#" class="tag-cloud-link">Restuarants</a>
                <a href="#" class="tag-cloud-link">Stores</a>
                <a href="#" class="tag-cloud-link">Pet shop</a>
                <a href="#" class="tag-cloud-link">Electronics</a>
                <a href="#" class="tag-cloud-link">Fashion</a>
                <a href="#" class="tag-cloud-link">Book store</a>
                <a href="#" class="tag-cloud-link">Furniture</a>
              </div>
            </div>
			
          </div>
        </div> 
      </div> <!-- .row -->
    </div> <!-- .container -->
  </div> <!-- .page-section -->



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
    </div> <!-- .container -->
  </footer> <!-- .page-footer -->

  <script src="../assets/js/jquery-3.5.1.min.js"></script>

  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  
  <script src="../assets/vendor/owl-carousel/js/owl.carousel.min.js"></script>
  
  <script src="../assets/vendor/wow/wow.min.js"></script>
  
  <script src="../assets/js/theme.js"></script>
  
</body>
</html>