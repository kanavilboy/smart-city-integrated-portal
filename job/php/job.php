<?php
session_start();
require '../../database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Job Portal - Smart City</title>
  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    .job-hero {
      background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url(../assets/img/job-hero-bg.jpg) no-repeat center center;
      background-size: cover;
      padding: 120px 0 100px;
      color: white;
    }
    
    .job-card {
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      transition: transform 0.3s, box-shadow 0.3s;
      margin-bottom: 30px;
      border: none;
    }
    
    .job-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0,0,0,0.2);
    }
    
    .job-card .card-body {
      padding: 25px;
    }
    
    .job-card .company-logo {
      width: 60px;
      height: 60px;
      object-fit: contain;
      border-radius: 8px;
      margin-bottom: 15px;
    }
    
    .job-card .job-title {
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 5px;
    }
    
    .job-card .company-name {
      color: #6c757d;
      margin-bottom: 15px;
    }
    
    .job-card .job-meta {
      display: flex;
      flex-wrap: wrap;
      margin-bottom: 15px;
    }
    
    .job-card .job-meta span {
      margin-right: 15px;
      margin-bottom: 5px;
    }
    
    .job-card .job-meta i {
      color: #00d289;
      margin-right: 5px;
    }
    
    .job-type {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 50px;
      font-size: 0.75rem;
      font-weight: 600;
      margin-right: 5px;
    }
    
    .job-type.fulltime {
      background-color: #d4edda;
      color: #155724;
    }
    
    .job-type.parttime {
      background-color: #fff3cd;
      color: #856404;
    }
    
    .job-type.remote {
      background-color: #cce5ff;
      color: #004085;
    }
    
    .job-categories {
      margin-bottom: 40px;
    }
    
    .category-card {
      border-radius: 8px;
      padding: 30px 20px;
      text-align: center;
      margin-bottom: 30px;
      transition: transform 0.3s;
      background-color: #f8f9fa;
    }
    
    .category-card:hover {
      transform: translateY(-5px);
    }
    
    .category-card i {
      font-size: 2.5rem;
      color: #00d289;
      margin-bottom: 15px;
    }
    
    .job-stats {
      background-color: #f8f9fa;
      padding: 60px 0;
    }
    
    .stat-item {
      text-align: center;
      padding: 20px;
    }
    
    .stat-item .number {
      font-size: 2.5rem;
      font-weight: 700;
      color: #00d289;
      margin-bottom: 10px;
    }
    
    .search-filters {
      background-color: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      margin-bottom: 30px;
    }
  </style>
</head>
<body>
  <!-- Back to top button -->
  <div class="back-to-top"></div>

  <!-- Header -->
  <header>
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm">
      <div class="container">
        <a class="navbar-brand" href="../../smartcity.php"><span class="text-primary">Smart</span>-City</a>
        
        <form action="jobslist.php" method="GET" class="search-form">
          <div class="input-group input-navbar">
            <div class="input-group-prepend">
              <span class="input-group-text" id="icon-addon1"><span class="mai-search"></span></span>
            </div>
            <input type="text" class="form-control" name="search" placeholder="Job title, keywords..." aria-label="Search jobs">
          </div>
        </form>
        
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupport">
          <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarSupport">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item active"><a class="nav-link" href="job.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="jobslist.php">Browse Jobs</a></li>
            <li class="nav-item"><a class="nav-link" href="employers.php">Employers</a></li>
            <?php if(isset($_SESSION['user_id'])): ?>
              <?php 
              // Check if user already has a seeker profile
              $stmt = $conn->prepare("SELECT id FROM job_seeker_profiles WHERE user_id = ?");
              $stmt->execute(array($_SESSION['user_id']));
              $has_profile = $stmt->fetch();
              ?>
              
              <?php if($has_profile): ?>
                <li class="nav-item"><a class="nav-link" href="seeker-dashboard.php">Profile</a></li>
              <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="create-seeker-profile.php">Create Profile</a></li>
              <?php endif; ?>
            <?php else: ?>
            
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <!-- Job Categories -->
  <div class="page-section">
    <div class="container">
      <div class="text-center mb-5">
        <h2 class="title-section">Popular Job Categories</h2>
        <div class="divider mx-auto"></div>
      </div>
      
      <div class="row job-categories">
        <div class="col-md-4">
          <div class="category-card">
            <span class="mai-laptop"></span>
            <h5>Technology</h5>
            <p class="text-muted">1,200+ Jobs</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="category-card">
            <span class="mai-medkit"></span>
            <h5>Healthcare</h5>
            <p class="text-muted">850+ Jobs</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="category-card">
            <span class="mai-calculator"></span>
            <h5>Finance</h5>
            <p class="text-muted">1,000+ Jobs</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="category-card">
            <span class="mai-school"></span>
            <h5>Education</h5>
            <p class="text-muted">750+ Jobs</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="category-card">
            <span class="mai-construct"></span>
            <h5>Construction</h5>
            <p class="text-muted">650+ Jobs</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="category-card">
            <span class="mai-restaurant"></span>
            <h5>Hospitality</h5>
            <p class="text-muted">900+ Jobs</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Featured Jobs -->
  <div class="page-section bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="title-section">Featured Job Listings</h2>
            <div class="divider mx-auto"></div>
        </div>
        
        <div class="row">
            <?php
            // Fetch featured job listings from database
            $stmt = $conn->prepare("
                SELECT jv.*, m.name ,m.profile_image 
                FROM job_vacancies jv
                JOIN merchants m ON jv.merchant_id = m.id
                WHERE jv.application_deadline >= CURDATE()
                ORDER BY jv.created_at DESC
                LIMIT 2
            ");
            $stmt->execute();
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($jobs) > 0) {
                foreach ($jobs as $job) {
                    // Format salary if it exists
                    $salary = '';
                    if (!empty($job['salary'])) {
                        $salary = '<span><i class="mai-cash"></i> $' . ($job['salary']) . '</span>';
                    }
                    
                    // Calculate time since posting
                    $created_at = new DateTime($job['created_at']);
                    $now = new DateTime();
                    $interval = $created_at->diff($now);
                    
                    if ($interval->d == 0) {
                        $time_ago = 'Today';
                    } elseif ($interval->d == 1) {
                        $time_ago = '1 day ago';
                    } else {
                        $time_ago = $interval->d . ' days ago';
                    }
                    
                    // Determine job type (simplified for this example)
                    $job_type = 'fulltime'; // You could add a field in your table for this
                    ?>
					<?php
				
					echo '
					<div class="col-lg-6">
						<div class="card job-card">
							<div class="card-body">
								<img src="' . htmlspecialchars($job['profile_image']) . '" alt="' . htmlspecialchars($job['name']) . '" class="company-logo">
								<h3 class="job-title"><a href="job_apply.php?id=' . htmlspecialchars($job['id']) . '">' . htmlspecialchars($job['job_title']) . '</a></h3>
								<p class="company-name">' . htmlspecialchars($job['name']) . ' • ' . htmlspecialchars($job['job_location']) . '</p>
								
								<div class="job-meta">
									' . $salary . '
									<span><i class="mai-time"></i> Full Time</span>
									<span><i class="mai-calendar"></i> ' . htmlspecialchars($time_ago) . '</span>
								</div>
								
								<p class="card-text">' . htmlspecialchars(substr($job['job_description'], 0, 150)) . '...</p>
								
								<div class="d-flex justify-content-between align-items-center">
									<span class="job-type ' . htmlspecialchars($job_type) . '">' . ucfirst(htmlspecialchars($job_type)) . '</span>
									<a href="job_apply.php?id=' . htmlspecialchars($job['id']) . '" class="btn btn-primary">Apply Now</a>
								</div>
							</div>
						</div>
					</div>
					';
					?>
                    <?php
                }
            } else {
                echo '<div class="col-12 text-center"><p>No current job openings available.</p></div>';
            }
            ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="jobslist.php" class="btn btn-outline-primary">View All Jobs</a>
        </div>
    </div>
</div>

  <!-- Call to Action -->
  <div class="page-section bg-light">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6 wow fadeInLeft" data-wow-delay="400ms">
          <img src="../../assets/img/freelancer.png" alt="Freelancer showcasing skills" class="img-fluid rounded">
        </div>
        <div class="col-lg-6 py-3 wow fadeInUp">
          <h2 class="mb-4">Looking for Work?</h2>
          <p class="text-grey mb-4">Showcase your skills to thousands of employers. Create a standout profile and get discovered for opportunities that match your expertise.</p>
          
          <div class="d-flex flex-wrap mb-4">
            <div class="mr-4 mb-2">
              <span class="mai-checkmark-circle text-primary"></span>
              <span class="ml-2">Highlight your unique skills</span>
            </div>
            <div class="mr-4 mb-2">
              <span class="mai-checkmark-circle text-primary"></span>
              <span class="ml-2">Get discovered by employers</span>
            </div>
            <div class="mr-4 mb-2">
              <span class="mai-checkmark-circle text-primary"></span>
              <span class="ml-2">Receive direct job offers</span>
            </div>
          </div>
          
          <?php if(isset($_SESSION['user_id'])): ?>
            <?php 
            // Check if user already has a seeker profile
            $stmt = $conn->prepare("SELECT id FROM job_seeker_profiles WHERE user_id = ?");
            $stmt->execute(array($_SESSION['user_id']));
            $has_profile = $stmt->fetch();
            ?>
            
            <?php if($has_profile): ?>
              <a href="seeker-dashboard.php" class="btn btn-primary mr-2">View My Profile</a>
              <a href="edit-seeker-profile.php" class="btn btn-outline-primary">Update Profile</a>
            <?php else: ?>
              <a href="create-seeker-profile.php" class="btn btn-primary">Create My Profile</a>
            <?php endif; ?>
          <?php else: ?>
            <div class="d-flex">
              <a href="register.php?type=seeker" class="btn btn-primary mr-2">Register Now</a>
            </div>
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
          <h5>Quick Links</h5>
          <ul class="footer-menu">
            <li><a href="job.php">Home</a></li>
            <li><a href="about.php">About Us</a></li>
            <li><a href="jobslist.php">Browse Jobs</a></li>
            <li><a href="contact.php">Contact</a></li>
          </ul>
        </div>
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>Job Seekers</h5>
          <ul class="footer-menu">
            <li><a href="register.php">Create Account</a></li>
            <li><a href="jobslist.php">Job Listings</a></li>
            <li><a href="#">Career Advice</a></li>
            <li><a href="#">FAQ</a></li>
          </ul>
        </div>
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>Employers</h5>
          <ul class="footer-menu">
            <li><a href="employer-register.php">Post a Job</a></li>
            <li><a href="#">Browse Candidates</a></li>
            <li><a href="#">Pricing Plans</a></li>
            <li><a href="#">Recruitment Tips</a></li>
          </ul>
        </div>
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>Contact</h5>
          <p class="footer-link mt-2">JK Smart City</p>
          <a href="#" class="footer-link">contact@jksmartcity.com</a>
          <a href="#" class="footer-link">+1 (555) 123-4567</a>

          <h5 class="mt-3">Social Media</h5>
          <div class="footer-sosmed mt-3">
            <a href="#" target="_blank"><span class="mai-logo-facebook-f"></span></a>
            <a href="#" target="_blank"><span class="mai-logo-twitter"></span></a>
            <a href="#" target="_blank"><span class="mai-logo-linkedin"></span></a>
            <a href="#" target="_blank"><span class="mai-logo-instagram"></span></a>
          </div>
        </div>
      </div>

      <hr>

      <p id="copyright">Copyright &copy; 2025 <a href="job.php">JK Smart City</a>. All rights reserved</p>
    </div>
  </footer>

  <script src="../assets/js/jquery-3.5.1.min.js"></script>
  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/vendor/owl-carousel/js/owl.carousel.min.js"></script>
  <script src="../assets/vendor/wow/wow.min.js"></script>
  <script src="../assets/js/theme.js"></script>
  
  <script>
    // Initialize Owl Carousel for featured jobs
    $(document).ready(function(){
      $('.owl-carousel').owlCarousel({
        loop: true,
        margin: 20,
        nav: true,
        dots: false,
        responsive: {
          0: {
            items: 1
          },
          600: {
            items: 2
          },
          1000: {
            items: 3
          }
        }
      });
      
      // Animation for stats counter
      $('.number').each(function () {
        $(this).prop('Counter', 0).animate({
          Counter: $(this).text()
        }, {
          duration: 2000,
          easing: 'swing',
          step: function (now) {
            $(this).text(Math.ceil(now));
          }
        });
      });
    });
  </script>
</body>
</html>