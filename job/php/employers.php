<?php
session_start();
require '../../database.php';

// Redirect if not logged in as employer
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch all job seeker profiles
try {
    $stmt = $conn->prepare("SELECT * FROM job_seeker_profiles ORDER BY created_at DESC");
    $stmt->execute();
    $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Seekers - Smart City</title>
    <link rel="stylesheet" href="../assets/css/maicons.css">
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        .profile-section {
            padding: 40px 0;
        }
        .profile-card {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
            transition: transform 0.3s;
        }
        .profile-card:hover {
            transform: translateY(-5px);
        }
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        .skill-tag {
            background-color: #e0f7fa;
            color: #00838f;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
        }
        .search-container {
            margin-bottom: 30px;
        }
        .pagination {
            justify-content: center;
            margin-top: 30px;
        }
        .empty-state {
            text-align: center;
            padding: 50px 0;
        }
        .empty-state i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
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
            <li class="nav-item"><a class="nav-link" href="job.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="jobslist.php">Browse Jobs</a></li>
            <li class="nav-item active"><a class="nav-link" href="employers.php">Employers</a></li>
            <?php if(isset($_SESSION['user_id'])): ?>
              <?php 
              // Check if user already has a seeker profile
              $stmt = $conn->prepare("SELECT * FROM job_seeker_profiles WHERE user_id = ?");
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

    <!-- Job Seekers List -->
    <div class="page-section profile-section">
        <div class="container">
            <h2 class="text-center mb-5">Browse Job Seekers</h2>
            
            <div class="row">
                <div class="col-md-12">
                    <?php if (!empty($search)): ?>
                        <div class="alert alert-info">
                            Showing results for: <strong><?php echo htmlspecialchars($search); ?></strong>
                            <a href="employers.php" class="float-right">Clear search</a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($profiles)): ?>
                        <div class="empty-state">
                            <span class="mai-people-outline"></span>
                            <h3>No job seekers found</h3>
                            <p>There are currently no job seeker profiles matching your criteria.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($profiles as $profile): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="profile-card">
                                        <div class="text-center mb-4">
                                            <img src="<?php echo htmlspecialchars($profile['profile_photo'] ? $profile['profile_photo'] : '../assets/img/default-profile.jpg'); ?>" 
												class="profile-img" alt="Profile Photo">
                                        </div>
                                        <h4 class="text-center"><?php echo htmlspecialchars($profile['full_name']); ?></h4>
                                        <p class="text-center text-primary"><?php echo htmlspecialchars($profile['headline']); ?></p>
                                        <p class="text-muted text-center mb-4">
                                            <i class="mai-location-outline"></i> <?php echo htmlspecialchars($profile['location']); ?>
                                            <span class="mx-2">|</span>
                                            <?php echo htmlspecialchars($profile['experience_years']); ?>+ years experience
                                        </p>
                                        
                                        <div class="mb-3">
                                            <h6>Skills</h6>
                                            <div class="skills-list">
                                                <?php 
                                                $skills = explode(',', $profile['skills']);
                                                foreach ($skills as $skill): 
                                                    if (trim($skill)): ?>
                                                        <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                                    <?php endif; 
                                                endforeach; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <h6>Availability</h6>
                                            <p><?php echo ucfirst(htmlspecialchars($profile['availability'])); ?></p>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between">
                                            <?php if ($profile['phone']): ?>
                                                <a href="tel:<?php echo htmlspecialchars($profile['phone']); ?>" class="btn btn-primary btn-sm">
                                                    <i class="mai-call-outline"></i> Contact: <?php echo htmlspecialchars($profile['phone']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="btn btn-outline-secondary btn-sm disabled">No contact number</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($profile['resume_path']): ?>
                                                <a href="../uploads/<?php echo basename($profile['resume_path']); ?>" 
                                                   class="btn btn-outline-primary btn-sm" download>
                                                    <i class="mai-download-outline"></i> Resume
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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
    <script src="../assets/vendor/wow/wow.min.js"></script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>