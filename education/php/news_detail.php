<?php
require '../../database.php';

// Get the news ID from URL parameter
$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    header("Location: news.php");
    exit();
}

// Fetch the specific news item
$stmt = $conn->prepare("SELECT * FROM institution_news WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$newsItem = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$newsItem) {
    header("Location: news.php");
    exit();
}

// Fetch related news (excluding current item)
$currentDate = date('Y-m-d H:i:s');
$relatedNews = $conn->query("SELECT * FROM institution_news 
                            WHERE (deadline_date > '$currentDate' OR deadline_date IS NULL) 
                            AND id != $id 
                            ORDER BY posted_date DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($newsItem['title']); ?> | Education Services</title>
  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
  <style>
    .news-header {
      background-size: cover;
      background-position: center;
      min-height: 400px;
      position: relative;
    }
    .news-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.5);
    }
    .news-content img {
      max-width: 100%;
      height: auto;
      margin: 20px 0;
      border-radius: 8px;
    }
    .related-news-card {
      transition: transform 0.3s ease;
    }
    .related-news-card:hover {
      transform: translateY(-5px);
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

  <!-- News Detail Section -->
  <div class="page-section pt-5">
    <div class="container">
      <nav aria-label="Breadcrumb">
        <ol class="breadcrumb bg-transparent mb-4">
          <li class="breadcrumb-item"><a href="education.php">Home</a></li>
          <li class="breadcrumb-item"><a href="news.php">News</a></li>
          <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars(substr($newsItem['title'], 0, 30) . (strlen($newsItem['title']) > 30 ? '...' : '')); ?></li>
        </ol>
      </nav>
      
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <article class="news-detail">
            <header class="news-header mb-4 rounded" style="<?php echo isset($newsItem['image_path']) ? "background-image: url('" . htmlspecialchars($newsItem['image_path']) . "')" : "background-color: #f8f9fa"; ?>">
              <div class="container position-relative px-4 px-lg-5">
                <div class="row gx-4 gx-lg-5 justify-content-center">
                  <div class="col-md-10 col-lg-8 col-xl-7 text-center text-white py-5" style="position: relative; z-index: 1;">
                    <h1 class="display-5 fw-bold"><?php echo htmlspecialchars($newsItem['title']); ?></h1>
                    <div class="meta mb-3">
                      <span class="badge bg-primary me-2">News</span>
                      <span class="date">Posted on <?php echo date('F j, Y', strtotime($newsItem['posted_date'])); ?></span>
                      <?php if ($newsItem['deadline_date']): ?>
                        <span class="date ms-2">Visible until <?php echo date('F j, Y', strtotime($newsItem['deadline_date'])); ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </header>
            
            <div class="news-content mb-5">
              <?php if (isset($newsItem['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($newsItem['image_path']); ?>" alt="<?php echo htmlspecialchars($newsItem['title']); ?>" class="img-fluid rounded">
              <?php endif; ?>
              
              <div class="content">
                <?php echo nl2br(htmlspecialchars($newsItem['news'])); ?>
              </div>
              
              <?php if (!empty($newsItem['attachment_path'])): ?>
                <div class="mt-4">
                  <h5>Attachments:</h5>
                  <a href="<?php echo htmlspecialchars($newsItem['attachment_path']); ?>" class="btn btn-outline-primary" download>
                    <i class="bi bi-download me-2"></i>Download Attachment
                  </a>
                </div>
              <?php endif; ?>
            </div>
            
            <div class="news-footer border-top pt-4">
              <div class="d-flex justify-content-between align-items-center">
                <a href="news.php" class="btn btn-outline-secondary">
                  <i class="bi bi-arrow-left me-2"></i>Back to News
                </a>
                <div class="share-buttons">
                  <span class="me-2">Share:</span>
                  <a href="#" class="btn btn-sm btn-outline-primary me-2"><i class="bi bi-facebook"></i></a>
                  <a href="#" class="btn btn-sm btn-outline-primary me-2"><i class="bi bi-twitter"></i></a>
                  <a href="#" class="btn btn-sm btn-outline-primary"><i class="bi bi-linkedin"></i></a>
                </div>
              </div>
            </div>
          </article>
        </div>
      </div>
      
      <!-- Related News Section -->
      <?php if (!empty($relatedNews)): ?>
      <div class="row mt-5">
        <div class="col-12">
          <h3 class="mb-4">Related News</h3>
        </div>
        
        <?php foreach ($relatedNews as $relatedItem): ?>
        <div class="col-md-4 mb-4">
          <div class="card related-news-card h-100 shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between mb-2">
                <small class="text-muted"><?php echo date('M j, Y', strtotime($relatedItem['posted_date'])); ?></small>
              </div>
              <h5 class="card-title"><?php echo htmlspecialchars($relatedItem['title']); ?></h5>
              <p class="card-text"><?php echo substr(htmlspecialchars($relatedItem['news']), 0, 100); ?>...</p>
              <a href="news_detail.php?id=<?php echo $relatedItem['id']; ?>" class="btn btn-sm btn-primary stretched-link">Read More</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
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