<?php
require '../../database.php';

// Pagination setup
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 6;
$offset = ($currentPage - 1) * $perPage;

// Fetch total number of news items
$totalNews = $conn->query("SELECT COUNT(*) FROM institution_news 
                          WHERE deadline_date > NOW() OR deadline_date IS NULL")->fetchColumn();

// Calculate total pages
$totalPages = ceil($totalNews / $perPage);

// Fetch news for current page
$currentDate = date('Y-m-d H:i:s');
$news = $conn->query("SELECT * FROM institution_news 
                     WHERE (deadline_date > '$currentDate' OR deadline_date IS NULL)
                     ORDER BY posted_date DESC 
                     LIMIT $perPage OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>News & Updates | Education Services</title>
  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
  <style>
    .news-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      height: 100%;
    }
    .news-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .news-img {
      height: 200px;
      object-fit: cover;
      width: 100%;
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
  <!-- Header (same as your main page) -->
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
            <li class="nav-item"><a class="nav-link" href="education.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="schools.php">Schools</a></li>
            <li class="nav-item"><a class="nav-link" href="colleges.php">Colleges</a></li>
            <li class="nav-item"><a class="nav-link" href="otherinstitutions.php">Other Institutions</a></li>
            <li class="nav-item active"><a class="nav-link" href="news.php">News</a></li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <!-- Page Header -->
  <div class="page-banner overlay-dark bg-image" style="background-image: url(../assets/img/education-news.jpg);">
    <div class="banner-section">
      <div class="container text-center wow fadeInUp">
        <h1 class="display-4 mb-4">News & Announcements</h1>
        <p class="text-white">Stay updated with the latest from our educational institutions</p>
      </div>
    </div>
  </div>

  <!-- News Listing Section -->
  <div class="page-section">
    <div class="container">
      <div class="row">
        <div class="col-lg-8 mb-5 mb-lg-0">
          <div class="row">
            <?php if (empty($news)): ?>
              <div class="col-12">
                <div class="alert alert-info">No news articles found.</div>
              </div>
            <?php else: ?>
              <?php foreach ($news as $item): ?>
              <div class="col-md-6 mb-4">
                <div class="card news-card h-100 shadow-sm">
                  <?php if (isset($item['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($item['image_path']); ?>" class="card-img-top news-img" alt="<?php echo htmlspecialchars($item['title']); ?>">
                  <?php else: ?>
                    <div class="news-img bg-light d-flex align-items-center justify-content-center">
                      <i class="bi bi-newspaper text-muted" style="font-size: 3rem;"></i>
                    </div>
                  <?php endif; ?>
                  <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                      <span class="badge bg-primary">News</span>
                      <small class="text-muted"><?php echo date('M j, Y', strtotime($item['posted_date'])); ?></small>
                    </div>
                    <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                    <p class="card-text"><?php echo substr(htmlspecialchars($item['news']), 0, 120); ?>...</p>
                  </div>
                  <div class="card-footer bg-transparent border-top-0">
                    <a href="news_detail.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary stretched-link">Read More</a>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
          <nav aria-label="News pagination" class="mt-5">
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
        
        <!-- Sidebar -->
        <div class="col-lg-4">
          
          <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0">Recent News</h5>
            </div>
            <div class="card-body">
              <?php 
              $recentNews = $conn->query("SELECT id, title, posted_date FROM institution_news 
                                        WHERE (deadline_date > NOW() OR deadline_date IS NULL)
                                        ORDER BY posted_date DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
              foreach ($recentNews as $recent): ?>
              <div class="mb-3 pb-2 border-bottom">
                <h6 class="mb-1"><a href="news_detail.php?id=<?php echo $recent['id']; ?>" class="text-dark"><?php echo htmlspecialchars($recent['title']); ?></a></h6>
                <small class="text-muted"><?php echo date('M j, Y', strtotime($recent['posted_date'])); ?></small>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          
        </div>
      </div>
    </div>
  </div>

  <!-- Footer (same as your main page) -->
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