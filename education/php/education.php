<?php
require '../../database.php';

// Fetch all active institutions
$institutions = $conn->query("SELECT * FROM institutions")->fetchAll(PDO::FETCH_ASSOC);

// Fetch current news (not past deadline)
$currentDate = date('Y-m-d H:i:s');
$news = $conn->query("SELECT * FROM institution_news WHERE deadline_date > '$currentDate' OR deadline_date IS NULL ORDER BY posted_date DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

// Fetch featured courses
$courses = $conn->query("SELECT * FROM institution_courses ORDER BY RAND() LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

// Fetch featured faculty
$faculty = $conn->query("SELECT * FROM institution_faculty ORDER BY RAND() LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <meta name="copyright" content="MACode ID, https://macodeid.com/">
  <title>Education Services</title>
  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
</head>
<body>
  <div class="back-to-top"></div>
  <header>
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm">
      <div class="container">
        <a class="navbar-brand" href="../../smartcity.php"><span class="text-primary">Smart</span>-City</a>
        <form action="search.php" method="GET">
          <div class="input-group input-navbar">
            <div class="input-group-prepend">
              <span class="input-group-text" id="icon-addon1"><span class="mai-search"></span></span>
            </div>
            <input type="text" name="query" class="form-control" placeholder="Search institutions..." aria-label="Search">
          </div>
        </form>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupport" aria-controls="navbarSupport" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupport">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item active"><a class="nav-link" href="education.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="schools.php">Schools</a></li>
            <li class="nav-item"><a class="nav-link" href="colleges.php">Colleges</a></li>
            <li class="nav-item"><a class="nav-link" href="otherinstitutions.php">Other Institutions</a></li>
			<li class="nav-item"><a class="nav-link" href="news.php">News</a></li>
          </ul>
        </div>
      </div>
    </nav>
  </header>
  <div class="page-hero bg-image overlay-dark" style="background-image: url(../assets/img/education.jpg);">
    <div class="hero-section">
      <div class="container text-center wow zoomIn">
        <span class="subhead">Empowering Future Generations</span>
        <h1 class="display-4">Quality Education</h1>
      </div>
    </div>
  </div>
  <div class="page-section pb-0">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6 py-3 wow fadeInUp">
          <h1>Excellence in Education</h1>
          <p class="text-grey mb-4">Our city is home to prestigious educational institutions, offering top-notch academic programs. We boast a mix of government and private schools, renowned colleges, and professional institutions that ensure quality education.</p>
          <a href="about.php" class="btn btn-primary">Learn More</a>
        </div>
        <div class="col-lg-6 wow fadeInRight" data-wow-delay="400ms">
          <div class="img-place custom-img-1">
            <img src="../assets/img/students.jpg" alt="">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- News Section -->
  <div class="news-section py-5 bg-light">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="fw-bold display-5">Latest News & Updates</h2>
            <p class="text-muted">Stay informed with our recent announcements and stories</p>
        </div>
        
        <div class="row g-4">
            <?php foreach ($news as $item): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card news-card h-100 border-0 shadow-sm overflow-hidden">
                    <div class="card-body p-4">
                        <div class="news-meta d-flex justify-content-between mb-3">
                            <span class="text-muted small"><?php echo date('M j, Y', strtotime($item['posted_date'])); ?></span>
                        </div>
                        <h3 class="h5 fw-bold mb-3"><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p class="text-muted mb-4"><?php echo substr(htmlspecialchars($item['news']), 0, 120); ?>...</p>
                        
                        <?php if ($item['deadline_date']): ?>
                        <div class="alert alert-warning small p-2 mb-3">
                            <i class="bi bi-clock me-2"></i>Visible until <?php echo date('M j, Y', strtotime($item['deadline_date'])); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex align-items-center">
                            <a href="news_detail.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-primary btn-sm stretched-link">
                                Read More <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="news.php" class="btn btn-primary px-4 py-2">
                View All News <i class="bi bi-newspaper ms-2"></i>
            </a>
        </div>
    </div>
</div>

  <!-- Institutions Section -->
  <div class="page-section bg-light">
    <div class="container">
      <h1 class="text-center mb-5 wow fadeInUp">Top Institutions</h1>
      <div class="owl-carousel wow fadeInUp" id="institutionSlideshow">
        <?php foreach ($institutions as $institution): 
          $details = $conn->query("SELECT * FROM institution_details WHERE institution_id = ".$institution['id'])->fetch(PDO::FETCH_ASSOC);
        ?>
        <div class="item">
          <div class="card-doctor">
            <div class="header">
              <img src="<?php echo isset($details['logo_path']) ? $details['logo_path'] : '../assets/img/school.jpg'; ?>" alt="<?php echo htmlspecialchars($institution['institution_name']); ?>">
            </div>
            <div class="body">
              <p class="text-xl mb-0"><?php echo htmlspecialchars($institution['institution_name']); ?></p>
              <p><span class="text-sm text-grey"><?php echo htmlspecialchars($institution['type']); ?></span></p>
              <a href="institution.php?id=<?php echo $institution['id']; ?>" class="btn btn-sm btn-primary">View Details</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
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

  <script>
    // Initialize institution carousel
    $('#institutionSlideshow').owlCarousel({
      center: true,
      items: 3,
      loop: true,
      margin: 10,
      responsive: {
        0: { items: 1 },
        600: { items: 2 },
        1000: { items: 3 }
      }
    });
  </script>
</body>
</html>