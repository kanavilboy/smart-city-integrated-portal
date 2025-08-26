<?php
require '../../database.php';

// Get the institution ID from URL parameter
$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    header("Location: schools.php");
    exit();
}

// Fetch the institution details
$stmt = $conn->prepare("SELECT * FROM institutions WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$institution = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$institution) {
    header("Location: schools.php");
    exit();
}

// Fetch additional details
$details = $conn->query("SELECT * FROM institution_details WHERE institution_id = $id")->fetch(PDO::FETCH_ASSOC);

// Fetch news related to this institution
$currentDate = date('Y-m-d H:i:s');
$institutionNews = $conn->query("SELECT * FROM institution_news 
                                WHERE institution_id = $id 
                                AND (deadline_date > '$currentDate' OR deadline_date IS NULL)
                                ORDER BY posted_date DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

// Fetch courses offered by this institution
$courses = $conn->query("SELECT * FROM institution_courses WHERE institution_id = $id LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

// Fetch faculty members
$faculty = $conn->query("SELECT * FROM institution_faculty WHERE institution_id = $id LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);	

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($institution['institution_name']); ?> | Education Services</title>
  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
  <style>
    .institution-header {
      background-size: cover;
      background-position: center;
      min-height: 400px;
      position: relative;
    }
    .institution-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.5);
    }
    .badge-type {
      font-size: 1rem;
      padding: 0.5rem 1rem;
    }
    .feature-icon {
      font-size: 2.5rem;
      margin-bottom: 1rem;
      color: #2d6cdf;
    }
    .course-card, .faculty-card {
      transition: transform 0.3s ease;
    }
    .course-card:hover, .faculty-card:hover {
      transform: translateY(-5px);
    }
    .gallery-img {
      height: 200px;
      object-fit: cover;
      cursor: pointer;
    }
    #map {
      height: 300px;
      width: 100%;
      border-radius: 8px;
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
            <li class="nav-item"><a class="nav-link" href="news.php">News</a></li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <!-- Institution Header -->
  <div class="institution-header" style="<?php echo isset($details['image_path']) ? "background-image: url('" . htmlspecialchars($details['image_path']) . "')" : "background-color: #f8f9fa"; ?>">
    <div class="container position-relative px-4 px-lg-5 h-100">
      <div class="row gx-4 gx-lg-5 h-100 align-items-center justify-content-center">
        <div class="col-md-10 col-lg-8 col-xl-7 text-center text-white" style="position: relative; z-index: 1;">
          <h1 class="display-4 fw-bold mb-4"><?php echo htmlspecialchars($institution['institution_name']); ?></h1>
          <span class="badge badge-type bg-primary mb-3"><?php echo htmlspecialchars($institution['type']); ?></span>
          <?php if (isset($details['motto'])): ?>
            <p class="lead mb-4">"<?php echo htmlspecialchars($details['motto']); ?>"</p>
          <?php endif; ?>
          <?php if (isset($details['established_year'])): ?>
            <p class="mb-0">Established <?php echo htmlspecialchars($details['established_year']); ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Institution Details Section -->
  <div class="page-section">
    <div class="container">
      <div class="row">
        <div class="col-lg-8">
          <!-- About Section -->
          <section class="mb-5">
            <h2 class="mb-4">About <?php echo htmlspecialchars($institution['institution_name']); ?></h2>
            <div class="row mb-4">
              <?php if (isset($details['description'])): ?>
                <p><?php echo nl2br(htmlspecialchars($details['description'])); ?></p>
              <?php else: ?>
                <p class="text-muted">No description available for this institution.</p>
              <?php endif; ?>
            </div>
            
            <!-- Key Features -->
            <div class="row mb-4">
              <div class="col-md-4 mb-4">
                <div class="text-center">
                  <div class="feature-icon">
                    <i class="bi bi-people-fill"></i>
                  </div>
                  <h5>Student Body</h5>
                  <p class="text-muted">
                    <?php echo isset($details['total_students']) ? number_format($details['total_students']) : 'N/A'; ?> students
                  </p>
                </div>
              </div>
              <div class="col-md-4 mb-4">
                <div class="text-center">
                  <div class="feature-icon">
                    <i class="bi bi-award"></i>
                  </div>
                  <h5>Accreditation</h5>
                  <p class="text-muted">
                    <?php echo isset($details['accreditation']) ? htmlspecialchars($details['accreditation']) : 'Not specified'; ?>
                  </p>
                </div>
              </div>
            </div>
            
            <!-- Contact Information -->
            <div class="card shadow-sm mb-5">
              <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Contact Information</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <p><i class="bi bi-geo-alt-fill text-primary me-2"></i> 
                      <?php echo isset($details['address']) ? htmlspecialchars($details['address']) : 'Address not available'; ?>
                    </p>
                    <?php if (isset($details['phone'])): ?>
                      <p><i class="bi bi-telephone-fill text-primary me-2"></i> 
                        <?php echo htmlspecialchars($details['phone']); ?>
                      </p>
                    <?php endif; ?>
                    <?php if (isset($details['email'])): ?>
                      <p><i class="bi bi-envelope-fill text-primary me-2"></i> 
                        <a href="mailto:<?php echo htmlspecialchars($details['email']); ?>"><?php echo htmlspecialchars($details['email']); ?></a>
                      </p>
                    <?php endif; ?>
                  </div>
                  <div class="col-md-6">
                    <?php if (isset($details['website'])): ?>
                      <p><i class="bi bi-globe text-primary me-2"></i> 
                        <a href="<?php echo htmlspecialchars($details['website']); ?>" target="_blank">Visit Website</a>
                      </p>
                    <?php endif; ?>
                    <?php if (isset($details['facebook'])): ?>
                      <p><i class="bi bi-facebook text-primary me-2"></i> 
                        <a href="<?php echo htmlspecialchars($details['facebook']); ?>" target="_blank">Facebook Page</a>
                      </p>
                    <?php endif; ?>
                    <?php if (isset($details['twitter'])): ?>
                      <p><i class="bi bi-twitter text-primary me-2"></i> 
                        <a href="<?php echo htmlspecialchars($details['twitter']); ?>" target="_blank">Twitter Profile</a>
                      </p>
                    <?php endif; ?>
                  </div>
                </div>
                
                <!-- Map -->
                <?php if (isset($details['latitude']) && isset($details['longitude'])): ?>
                  <div class="mt-4">
                    <h6 class="mb-3">Location</h6>
                    <div id="map"></div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
            
            <!-- Courses Offered -->
            <?php if (!empty($courses)): ?>
              <section class="mb-5">
                <h2 class="mb-4">Courses Offered</h2>
                <div class="row">
                  <?php foreach ($courses as $course): ?>
                    <div class="col-md-6 mb-4">
                      <div class="card course-card h-100 shadow-sm">
                        <div class="card-body">
                          <h5 class="card-title"><?php echo htmlspecialchars($course['course_name']); ?></h5>
                          <p class="card-text text-muted small mb-2">
                            <?php if (isset($course['duration_years'])): ?>
                              <span class="me-3"><i class="bi bi-clock text-primary me-1"></i> <?php echo htmlspecialchars($course['duration_years']); ?> years</span>
                            <?php endif; ?>
                          </p>
                          <p class="card-text"><?php echo substr(htmlspecialchars($course['description']), 0, 120); ?>...</p>
                        </div>
                        <div class="card-footer bg-transparent">
                          <a href="#" class="btn btn-sm btn-outline-primary">Course Details</a>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <?php if (count($courses) >= 6): ?>
                  <div class="text-center mt-3">
                    <a href="#" class="btn btn-primary">View All Courses</a>
                  </div>
                <?php endif; ?>
              </section>
            <?php endif; ?>
            
            <!-- Faculty -->
            <?php if (!empty($faculty)): ?>
              <section class="mb-5">
                <h2 class="mb-4">Featured Faculty</h2>
                <div class="row">
                  <?php foreach ($faculty as $member): ?>
                    <div class="col-md-6 col-lg-3 mb-4">
                      <div class="card faculty-card h-100 shadow-sm">
                        <?php if (isset($member['photo_path'])): ?>
                          <img src="<?php echo htmlspecialchars($member['photo_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($member['name']); ?>">
                        <?php else: ?>
                          <div class="text-center py-4 bg-light">
                            <i class="bi bi-person-square" style="font-size: 3rem; color: #6c757d;"></i>
                          </div>
                        <?php endif; ?>
                        <div class="card-body text-center">
                          <h5 class="card-title mb-1"><?php echo htmlspecialchars($member['name']); ?></h5>
                          <p class="card-text text-muted small"><?php echo htmlspecialchars($member['position']); ?></p>
                          <?php if (isset($member['department'])): ?>
                            <p class="card-text small"><?php echo htmlspecialchars($member['department']); ?></p>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </section>
            <?php endif; ?>
          </section>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
          <!-- Quick Facts -->
          <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0">Quick Facts</h5>
            </div>
            <div class="card-body">
              <ul class="list-group list-group-flush">
                <?php if (isset($details['established_year'])): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>Established</span>
                    <span class="fw-bold"><?php echo htmlspecialchars($details['established_year']); ?></span>
                  </li>
                <?php endif; ?>
                <?php if (isset($details['student_count'])): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>Students</span>
                    <span class="fw-bold"><?php echo number_format($details['student_count']); ?></span>
                  </li>
                <?php endif; ?>
                <?php if (isset($details['faculty_count'])): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>Faculty</span>
                    <span class="fw-bold"><?php echo number_format($details['faculty_count']); ?></span>
                  </li>
                <?php endif; ?>
                <?php if (isset($institution['type'])): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>Institution Type</span>
                    <span class="fw-bold"><?php echo htmlspecialchars($institution['type']); ?></span>
                  </li>
                <?php endif; ?>
                <?php if (isset($details['campus_size'])): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>Campus Size</span>
                    <span class="fw-bold"><?php echo htmlspecialchars($details['campus_size']); ?> acres</span>
                  </li>
                <?php endif; ?>
              </ul>
            </div>
          </div>
          
          <!-- News & Updates -->
          <?php if (!empty($institutionNews)): ?>
            <div class="card shadow-sm mb-4">
              <div class="card-header bg-primary text-white">
                <h5 class="mb-0">News & Updates</h5>
              </div>
              <div class="card-body">
                <?php foreach ($institutionNews as $newsItem): ?>
                  <div class="mb-3 pb-2 border-bottom">
                    <h6 class="mb-1"><a href="news_detail.php?id=<?php echo $newsItem['id']; ?>" class="text-dark"><?php echo htmlspecialchars($newsItem['title']); ?></a></h6>
                    <small class="text-muted"><?php echo date('M j, Y', strtotime($newsItem['posted_date'])); ?></small>
                  </div>
                <?php endforeach; ?>
                <a href="news.php?institution=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary mt-2">View All News</a>
              </div>
            </div>
          <?php endif; ?>
          
          <!-- Gallery -->
          <?php if (isset($details['gallery_images'])): 
            $galleryImages = json_decode($details['gallery_images'], true);
            if (!empty($galleryImages)): ?>
              <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                  <h5 class="mb-0">Gallery</h5>
                </div>
                <div class="card-body">
                  <div class="row g-2">
                    <?php foreach (array_slice($galleryImages, 0, 4) as $image): ?>
                      <div class="col-6">
                        <img src="<?php echo htmlspecialchars($image); ?>" class="img-fluid rounded gallery-img" alt="Gallery image" data-bs-toggle="modal" data-bs-target="#galleryModal">
                      </div>
                    <?php endforeach; ?>
                  </div>
                  <?php if (count($galleryImages) > 4): ?>
                    <a href="#" class="btn btn-sm btn-outline-primary mt-3">View Full Gallery</a>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>
          <?php endif; ?>
          
        </div>
      </div>
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

  <!-- Gallery Modal -->
  <div class="modal fade" id="galleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><?php echo htmlspecialchars($institution['institution_name']); ?> Gallery</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <img src="" id="modalImage" class="img-fluid" alt="">
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/js/jquery-3.5.1.min.js"></script>
  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/theme.js"></script>
  
  <?php if (isset($details['latitude']) && isset($details['longitude'])): ?>
  <!-- Google Maps API -->
  <script>
    function initMap() {
      const location = { lat: <?php echo $details['latitude']; ?>, lng: <?php echo $details['longitude']; ?> };
      const map = new google.maps.Map(document.getElementById("map"), {
        zoom: 15,
        center: location,
      });
      new google.maps.Marker({
        position: location,
        map: map,
        title: "<?php echo addslashes($institution['institution_name']); ?>"
      });
    }
  </script>
  <script async defer src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap"></script>
  <?php endif; ?>
  
  <script>
    // Gallery modal functionality
    $(document).ready(function() {
      $('.gallery-img').click(function() {
        $('#modalImage').attr('src', $(this).attr('src'));
      });
    });
  </script>
</body>
</html>