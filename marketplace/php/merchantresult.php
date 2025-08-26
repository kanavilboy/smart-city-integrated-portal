<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Merchant Search Results - Smart City</title>
  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    .merchant-card {
      transition: transform 0.3s;
      height: 100%;
      margin-bottom: 20px;
    }
    .merchant-card:hover {
      transform: translateY(-5px);
    }
    .merchant-img {
      height: 200px;
      object-fit: cover;
    }
    .search-header {
      margin-bottom: 30px;
    }
  </style>
</head>
<body>

  <!-- Header (same as your marketplace page) -->
  <header>
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm">
      <div class="container">
        <a class="navbar-brand" href="../../smartcity.php"><span class="text-primary">Smart</span>-City</a>

        <form action="merchantresult.php" method="GET">
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

  <div class="page-section">
    <div class="container">
      <div class="search-header wow fadeInUp">
        <h1>Merchant Search Results</h1>
        <?php
        if (isset($_GET['query']) && !empty($_GET['query'])) {
          echo '<p class="text-muted">Showing results for: <strong>"' . htmlspecialchars($_GET['query']) . '"</strong></p>';
        } else {
          header("Location: merchants.php");
          exit();
        }
        ?>
      </div>

      <div class="row">
        <?php
        require_once '../../database.php';
        
        if (isset($_GET['query']) && !empty($_GET['query'])) {
          $searchQuery = '%' . $_GET['query'] . '%';
          
          try {
            // Search in both merchants and listed_merchants tables
            $sql = "(
              SELECT id, name, merchant_type, address, contact, email, description, profile_image, 'merchant' as source 
              FROM merchants 
              WHERE name LIKE :query OR description LIKE :query OR merchant_type LIKE :query
            ) UNION ALL (
              SELECT id, merchant_name as name, merchant_type, address, contact, email, description, profile_image, 'listed_merchant' as source 
              FROM listed_merchants 
              WHERE merchant_name LIKE :query OR description LIKE :query OR merchant_type LIKE :query
            )";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':query', $searchQuery);
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            if (count($results) > 0) {
              foreach ($results as $merchant) {
                echo '<div class="col-md-6 col-lg-4 wow fadeInUp">';
                echo '<div class="card merchant-card">';
                echo '<img src="' . htmlspecialchars($merchant['profile_image']) . '" class="card-img-top merchant-img" alt="' . htmlspecialchars($merchant['name']) . '">';
                echo '<div class="card-body">';
                echo '<h5 class="card-title">' . htmlspecialchars($merchant['name']) . '</h5>';
                echo '<p class="card-text text-muted">' . htmlspecialchars($merchant['description']) . '</p>';
                echo '<ul class="list-unstyled">';
                echo '<li><span class="mai-location"></span> ' . htmlspecialchars($merchant['address']) . '</li>';
                echo '<li><span class="mai-call"></span> ' . htmlspecialchars($merchant['contact']) . '</li>';
                echo '<li><span class="mai-mail"></span> ' . htmlspecialchars($merchant['email']) . '</li>';
                echo '</ul>';
                
                // Different profile links based on source
                if ($merchant['source'] == 'merchant') {
                  echo '<a href="profile.php?id=' . $merchant['id'] . '" class="btn btn-primary">View Products</a>';
                } else {
                  echo '<a href="listed_profile.php?id=' . $merchant['id'] . '" class="btn btn-primary">View Details</a>';
                }
                
                echo '</div></div></div>';
              }
            } else {
              echo '<div class="col-12"><div class="alert alert-info">No merchants found matching your search.</div></div>';
            }
          } catch (PDOException $e) {
            echo '<div class="col-12"><div class="alert alert-danger">Error searching merchants: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
          }
        }
        ?>
      </div>
    </div>
  </div>

  <!-- Footer (same as your marketplace page) -->
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