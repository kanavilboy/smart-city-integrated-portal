<?php
require '../../database.php';

// Pagination setup
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 9;
$offset = ($currentPage - 1) * $perPage;

// Fetch total number of schools
$totalSchools = $conn->query("SELECT COUNT(*) FROM institutions WHERE type = 'Other Institution'")->fetchColumn();

// Calculate total pages
$totalPages = ceil($totalSchools / $perPage);

// Fetch schools with pagination
$schools = $conn->query("SELECT * FROM institutions 
                        WHERE type = 'Other Institution' 
                        ORDER BY institution_name ASC 
                        LIMIT $perPage OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Schools | Education Services</title>
  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
  <style>
    .school-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      height: 100%;
    }
    .school-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .school-img {
      height: 180px;
      object-fit: cover;
      width: 100%;
    }
    .badge-school {
      background-color: #2d6cdf;
      color: white;
    }
    .pagination .page-item.active .page-link {
      background-color: #2d6cdf;
      border-color: #2d6cdf;
    }
    .pagination .page-link {
      color: #2d6cdf;
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm">
      <div class="container">
        <a class="navbar-brand" href="../../smartcity.php"><span class="text-primary">Smart</span>-City</a>
        <form action="search.php" method="GET">
          <div class="input-group input-navbar">
            <div class="input-group-prepend">
              <span class="input-group-text" id="icon-addon1"><span class="mai-search"></span></span>
            </div>
            <input type="text" name="query" class="form-control" placeholder="Search schools..." aria-label="Search">
          </div>
        </form>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupport" aria-controls="navbarSupport" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupport">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a class="nav-link" href="education.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="schools.php">Schools</a></li>
            <li class="nav-item"><a class="nav-link" href="colleges.php">Colleges</a></li>
            <li class="nav-item active"><a class="nav-link" href="otherinstitutions.php">Other Institutions</a></li>
            <li class="nav-item"><a class="nav-link" href="news.php">News</a></li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <!-- Page Header -->
  <div class="page-banner overlay-dark bg-image" style="background-image: url(../assets/img/school-banner.jpg);">
    <div class="banner-section">
      <div class="container text-center wow fadeInUp">
        <h1 class="display-4 mb-4">Education in Our City</h1>
        <p class="text-white">Discover quality education institutions for your children</p>
      </div>
    </div>
  </div>

  <!-- Schools Listing Section -->
  <div class="page-section">
    <div class="container">
      <div class="row mb-4">
        <div class="col-md-6">
          <h3 class="mb-0">Education Institutions</h3>
        </div>
      </div>

      <div class="row">
        <?php if (empty($schools)): ?>
          <div class="col-12">
            <div class="alert alert-info">No institutions found in our database.</div>
          </div>
        <?php else: ?>
          <?php foreach ($schools as $school): 
            $details = $conn->query("SELECT * FROM institution_details WHERE institution_id = ".$school['id'])->fetch(PDO::FETCH_ASSOC);
          ?>
          <div class="col-md-6 col-lg-4 mb-4">
            <div class="card school-card h-100 shadow-sm">
              <div class="position-relative">
                <?php if (isset($details['logo_path'])): ?>
                  <img src="<?php echo htmlspecialchars($details['logo_path']); ?>" class="card-img-top school-img" alt="<?php echo htmlspecialchars($school['institution_name']); ?>">
                <?php else: ?>
                  <div class="school-img bg-light d-flex align-items-center justify-content-center">
                    <i class="bi bi-building text-muted" style="font-size: 3rem;"></i>
                  </div>
                <?php endif; ?>
                <span class="badge badge-school position-absolute" style="top: 10px; right: 10px;">College</span>
              </div>
              <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($school['institution_name']); ?></h5>
                <p class="card-text text-muted">
                  <i class="bi bi-geo-alt-fill text-primary"></i> 
                  <?php echo isset($details['address']) ? htmlspecialchars($details['address']) : 'Address not available'; ?>
                </p>
                <?php if (isset($details['description'])): ?>
                  <p class="card-text"><?php echo substr(htmlspecialchars($details['description']), 0, 100); ?>...</p>
                <?php endif; ?>
              </div>
              <div class="card-footer bg-transparent">
                <a href="institution.php?id=<?php echo $school['id']; ?>" class="btn btn-primary btn-sm stretched-link">View Details</a>
                <?php if (isset($details['website'])): ?>
                  <a href="<?php echo htmlspecialchars($details['website']); ?>" class="btn btn-outline-primary btn-sm float-right" target="_blank">Website</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <nav aria-label="Schools pagination" class="mt-5">
        <ul class="pagination justify-content-center">
          <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>" aria-label="Previous">
              <span aria-hidden="true">&laquo;</span>
            </a>
          </li>
          
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
              <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>
          
          <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>" aria-label="Next">
              <span aria-hidden="true">&raquo;</span>
            </a>
          </li>
        </ul>
      </nav>
      <?php endif; ?>
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
    </div>
  </footer>

  <script src="../assets/js/jquery-3.5.1.min.js"></script>
  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/theme.js"></script>
</body>
</html>