<?php
session_start();
require '../../database.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch profile data
try {
    $stmt = $conn->prepare("SELECT * FROM job_seeker_profiles WHERE user_id = ?");
    $stmt->execute(array($_SESSION['user_id']));
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        header('Location: create-seeker-profile.php');
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Smart City</title>
    <link rel="stylesheet" href="../assets/css/maicons.css">
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        .profile-container {
            padding: 20px 0 40px;
        }
        .profile-header {
            background-color: #00d289;
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            position: relative;
        }
        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            margin-bottom: 15px;
        }
        .profile-section {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .section-title {
            color: #00d289;
            border-bottom: 2px solid #f1f1f1;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .skill-tag {
            display: inline-block;
            background: #f1f1f1;
            padding: 5px 12px;
            margin: 0 8px 8px 0;
            border-radius: 20px;
            font-size: 0.9em;
        }
        .edit-btn {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .contact-info p {
            margin-bottom: 10px;
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
            <li class="nav-item"><a class="nav-link" href="employers.php">Employers</a></li>
            <?php if(isset($_SESSION['user_id'])): ?>
              <?php 
              // Check if user already has a seeker profile
              $stmt = $conn->prepare("SELECT id FROM job_seeker_profiles WHERE user_id = ?");
              $stmt->execute(array($_SESSION['user_id']));
              $has_profile = $stmt->fetch();
              ?>
              
              <?php if($has_profile): ?>
                <li class="nav-item active"><a class="nav-link" href="seeker-dashboard.php">Profile</a></li>
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

    <!-- Profile Content -->
    <div class="page-section profile-container">
        <div class="container">
            <!-- Profile Header -->
            <div class="profile-header text-center">
                <a href="edit-seeker-profile.php?id=<?php echo $profile['id'];?>" class="btn btn-light edit-btn">
                    <span class="mai-create"></span> Edit Profile
                </a>
                <img src="<?php echo htmlspecialchars($profile['profile_photo'] ? $profile['profile_photo'] : '../assets/img/default-profile.jpg'); ?>" 
					class="profile-pic" alt="Profile Photo">
                <h2 class="mb-2"><?php echo htmlspecialchars($profile['full_name']) ;?></h2>
                <h4 class="font-weight-light mb-3"><?php echo htmlspecialchars($profile['headline']) ;?></h4>
                <span class="badge bg-white text-dark"><?php echo ucfirst($profile['availability']) ;?></span>
            </div>

            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-4">
                    <!-- Contact Info -->
                    <div class="profile-section">
                        <h4 class="section-title">Contact Information</h4>
                        <div class="contact-info">
                            <?php if ($profile['location']): ?>
                                <p><span class="mai-pin"></span> <?php echo htmlspecialchars($profile['location']) ;?></p>
                            <?php endif; ?>
                            <?php if ($profile['phone']): ?>
                                <p><span class="mai-call"></span> <?php echo htmlspecialchars($profile['phone']) ;?></p>
                            <?php endif; ?>
                            <?php if ($profile['portfolio_url']): ?>
                                <p><span class="mai-globe"></span> <a href="<?php echo htmlspecialchars($profile['portfolio_url']) ;?>" target="_blank">Portfolio</a></p>
                            <?php endif; ?>
                            <?php if ($profile['desired_salary']): ?>
                                <p><span class="mai-cash"></span> <?php echo htmlspecialchars($profile['desired_salary']) ;?></p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($profile['resume_path']): ?>
                            <a href="<?php echo basename($profile['resume_path']); ?>" 
                               class="btn btn-primary btn-block mt-3" download>
                                <span class="mai-download"></span> Download Resume
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Skills -->
                    <div class="profile-section">
                        <h4 class="section-title">Skills</h4>
                        <?php if ($profile['skills']): ?>
                            <?php foreach (explode(',', $profile['skills']) as $skill): ?>
                                <?php if (trim($skill)): ?>
                                    <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No skills listed</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-8">
                    <!-- About -->
                    <div class="profile-section">
                        <h4 class="section-title">About Me</h4>
                        <p><?php echo nl2br(htmlspecialchars($profile['bio'])) ; ?></p>
                    </div>

                    <!-- Experience -->
                    <div class="profile-section">
                        <h4 class="section-title">Experience</h4>
                        <?php if ($profile['experience_years']): ?>
                            <p><strong><?php echo htmlspecialchars($profile['experience_years']) ; ?> years</strong> of professional experience</p>
                        <?php endif; ?>
                        <?php if ($profile['experience']): ?>
                            <div><?php echo nl2br(htmlspecialchars($profile['experience'])) ; ?></div>
                        <?php else: ?>
                            <p class="text-muted">No experience details</p>
                        <?php endif; ?>
                    </div>

                    <!-- Education -->
                    <div class="profile-section">
                        <h4 class="section-title">Education</h4>
                        <?php if ($profile['education']): ?>
                            <div><?php echo nl2br(htmlspecialchars($profile['education'])) ; ?></div>
                        <?php else: ?>
                            <p class="text-muted">No education details</p>
                        <?php endif; ?>
                    </div>

                    <!-- Certifications -->
                    <div class="profile-section">
                        <h4 class="section-title">Certifications</h4>
                        <?php if ($profile['certifications']): ?>
                            <div><?php echo nl2br(htmlspecialchars($profile['certifications'])) ; ?></div>
                        <?php else: ?>
                            <p class="text-muted">No certifications</p>
                        <?php endif; ?>
                    </div>
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