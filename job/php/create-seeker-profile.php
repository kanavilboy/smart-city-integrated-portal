<?php
session_start();
require '../../database.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$errors = array();
$success = false;

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $headline = trim($_POST['headline']);
    $bio = trim($_POST['bio']);
    $skills = trim($_POST['skills']);
    $education = trim($_POST['education']);
    $experience = trim($_POST['experience']);
    $location = trim($_POST['location']);
    $phone = trim($_POST['phone']);
    $portfolio_url = trim($_POST['portfolio_url']);

    // Basic validation
    if (empty($full_name)) {
        $errors['full_name'] = 'Full name is required';
    }
    if (empty($headline)) {
        $errors['headline'] = 'Headline is required';
    }
    if (empty($bio)) {
        $errors['bio'] = 'Bio is required';
    }
    if (empty($skills)) {
        $errors['skills'] = 'Skills are required';
    }

    // Handle file upload
    $resume_path = null;
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = array('application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $file_type = $_FILES['resume']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors['resume'] = 'Only PDF and Word documents are allowed';
        } else {
            $upload_dir = '../../uploads/resumes/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
            $resume_filename = 'resume_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
            $resume_path = $upload_dir . $resume_filename;
            
            if (!move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path)) {
                $errors['resume'] = 'Failed to upload resume';
            }
        }
    } else {
        $errors['resume'] = 'Resume is required';
    }

    // If no errors, save to database
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Check if profile already exists
            $stmt = $conn->prepare("SELECT id FROM job_seeker_profiles WHERE user_id = ?");
            $stmt->execute(array($_SESSION['user_id']));
            
            if ($stmt->fetch()) {
                // Update existing profile
                $sql = "UPDATE job_seeker_profiles SET 
                        full_name = ?, headline = ?, bio = ?, skills = ?, education = ?, 
                        experience = ?, location = ?, phone = ?, portfolio_url = ?, 
                        linkedin_url = ?, resume_path = ?, updated_at = NOW()
                        WHERE user_id = ?";
            } else {
                // Create new profile
                $sql = "INSERT INTO job_seeker_profiles (
                        user_id, full_name, headline, bio, skills, education, 
                        experience, location, phone, portfolio_url, resume_path
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            }
            
            $stmt = $conn->prepare($sql);
            $params = array(
                $full_name, $headline, $bio, $skills, $education, 
                $experience, $location, $phone, $portfolio_url, $resume_path
            );
            
            if (isset($sql_update)) {
                $params[] = $_SESSION['user_id'];
            } else {
                array_unshift($params, $_SESSION['user_id']);
            }
            
            $stmt->execute($params);
            $conn->commit();
            
            $success = true;
            $_SESSION['profile_created'] = true;
            header('Location: seeker-dashboard.php');
            exit();
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors['database'] = 'Error saving profile: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Profile - Smart City</title>
  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    .profile-section {
      padding: 60px 0;
    }
    .profile-card {
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      padding: 30px;
      margin-bottom: 30px;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-control {
      height: 50px;
      border-radius: 8px;
    }
    textarea.form-control {
      height: auto;
    }
    .btn-primary {
      background-color: #00d289;
      border-color: #00d289;
      padding: 12px 30px;
      font-weight: 600;
    }
    .file-upload {
      position: relative;
      overflow: hidden;
      display: inline-block;
    }
    .file-upload-input {
      position: absolute;
      left: 0;
      top: 0;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }
    .file-upload-label {
      display: block;
      padding: 12px;
      border: 2px dashed #ddd;
      border-radius: 8px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
    }
    .file-upload-label:hover {
      border-color: #00d289;
    }
    .file-name {
      margin-top: 10px;
      font-size: 14px;
      color: #666;
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

  <!-- Profile Creation Form -->
  <div class="page-section profile-section">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="profile-card">
            <h2 class="title-section mb-4">Create Your Job Seeker Profile</h2>
            <p class="text-muted mb-5">Complete your profile to increase your visibility to employers.</p>
            
            <?php if (!empty($errors['database'])): ?>
              <div class="alert alert-danger"><?php echo htmlspecialchars($errors['database']); ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
					value="<?php echo htmlspecialchars(isset($_POST['full_name']) ? $_POST['full_name'] : ''); ?>" required>
                    <?php if (isset($errors['full_name'])): ?>
                      <div class="invalid-feedback"><?php echo htmlspecialchars($errors['full_name']) ;?></div>
                    <?php endif; ?>
                  </div>
                </div>
                
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="headline">Professional Headline *</label>
                    <input type="text" class="form-control" 
                           id="headline" name="headline" value="<?php echo htmlspecialchars(isset($_POST['headline']) ? $_POST['headline'] : ''); ?>" 
                           placeholder="e.g. Web Developer, Graphic Designer" required>
                    <?php if (isset($errors['headline'])): ?>
                      <div class="invalid-feedback"><?= htmlspecialchars($errors['headline']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              
              <div class="form-group">
                <label for="bio">Professional Summary *</label>
                <textarea class="form-control <?= isset($errors['bio']) ? 'is-invalid' : '' ?>" id="bio" name="bio" 
                          rows="4" required><?php echo htmlspecialchars(isset($_POST['bio']) ? $_POST['bio'] : ''); ?></textarea>
                <?php if (isset($errors['bio'])): ?>
                  <div class="invalid-feedback"><?= htmlspecialchars($errors['bio']) ?></div>
                <?php endif; ?>
                <small class="form-text text-muted">Describe your professional background and career objectives (200-300 words).</small>
              </div>
              
              <div class="form-group">
                <label for="skills">Skills *</label>
                <textarea class="form-control <?= isset($errors['skills']) ? 'is-invalid' : '' ?>" id="skills" name="skills" 
                          rows="3" required><?php echo htmlspecialchars(isset($_POST['skills']) ? $_POST['skills'] : ''); ?></textarea>
                <?php if (isset($errors['skills'])): ?>
                  <div class="invalid-feedback"><?= htmlspecialchars($errors['skills']) ?></div>
                <?php endif; ?>
                <small class="form-text text-muted">List your key skills separated by commas (e.g. HTML, CSS, JavaScript, Project Management).</small>
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="education">Education</label>
                    <textarea class="form-control" id="education" name="education" 
                              rows="3"><?php echo htmlspecialchars(isset($_POST['education']) ? $_POST['education'] : ''); ?></textarea>
                    <small class="form-text text-muted">List your educational background (Degree, Institution, Year).</small>
                  </div>
                </div>
                
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="experience">Work Experience</label>
                    <textarea class="form-control" id="experience" name="experience" 
                              rows="3"><?php echo htmlspecialchars(isset($_POST['experience']) ? $_POST['experience'] : ''); ?></textarea>
                    <small class="form-text text-muted">List your work experience (Job Title, Company, Duration).</small>
                  </div>
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" class="form-control" id="location" name="location" 
                           value="<?php echo htmlspecialchars(isset($_POST['location']) ? $_POST['location'] : ''); ?>" placeholder="City, Country">
                  </div>
                </div>
                
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars(isset($_POST['phone']) ? $_POST['phone'] : ''); ?>">
                  </div>
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="portfolio_url">Portfolio Website</label>
                    <input type="url" class="form-control" id="portfolio_url" name="portfolio_url" 
                           value="<?php echo htmlspecialchars(isset($_POST['portfolio_url']) ? $_POST['portfolio_url'] : ''); ?>" placeholder="https://">
                  </div>
                </div>
              </div>
              
              <div class="form-group">
                <label>Upload Resume *</label>
                <div class="file-upload">
                  <label for="resume" class="file-upload-label">
                    <span class="mai-document-text"></span>
                    <span>Click to upload your resume</span>
                    <span class="file-name" id="file-name">No file chosen</span>
                    <input type="file" class="file-upload-input <?php isset($errors['resume']) ? 'is-invalid' : '' ?>" 
                           id="resume" name="resume" accept=".pdf,.doc,.docx" required>
                  </label>
                  <?php if (isset($errors['resume'])): ?>
                    <div class="invalid-feedback d-block"><?php echo htmlspecialchars(isset($_POST['resume']) ? $_POST['resume'] : ''); ?></div>
                  <?php endif; ?>
                  <small class="form-text text-muted">PDF or Word documents only (Max 5MB).</small>
                </div>
              </div>
              
              <div class="form-group text-center mt-5">
                <button type="submit" class="btn btn-primary btn-lg">Complete Profile</button>
              </div>
            </form>
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
  <script src="../assets/vendor/owl-carousel/js/owl.carousel.min.js"></script>
  <script src="../assets/vendor/wow/wow.min.js"></script>
  <script src="../assets/js/theme.js"></script>
  
  <script>
    // Show selected file name
    document.getElementById('resume').addEventListener('change', function(e) {
      const fileName = e.target.files[0] ? e.target.files[0].name : 'No file chosen';
      document.getElementById('file-name').textContent = fileName;
    });
  </script>
</body>
</html>