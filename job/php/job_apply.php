<?php
session_start();
require '../../database.php';


$job_id = $_GET['id'];

// Get job details
$stmt = $conn->prepare("
    SELECT jv.*, m.name as company_name, m.profile_image 
    FROM job_vacancies jv
    JOIN merchants m ON jv.merchant_id = m.id
    WHERE jv.id = ? AND jv.application_deadline >= CURDATE()
");
$stmt->execute(array($job_id));
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    $_SESSION['error'] = "Job not found or application deadline has passed.";
    header("Location: jobslist.php");
    exit();
}

// Check if user is logged in and has a seeker profile
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$seeker_profile = null;

if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM personal_users WHERE user_id = ?");
    $stmt->execute(array($user_id));
    $personal_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM job_seeker_profiles WHERE user_id = ?");
    $stmt->execute(array($user_id));
    $seeker_profile = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Process form submission
$errors = array();
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $applicant_name = trim($_POST['applicant_name']);
    $applicant_email = trim($_POST['applicant_email']);
    $applicant_phone = trim($_POST['applicant_phone']);
    $cover_letter = trim($_POST['cover_letter']);
    
    if (empty($applicant_name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($applicant_email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($applicant_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($applicant_phone)) {
        $errors[] = "Phone number is required";
    }
    
    // Handle file upload
    $resume_path = null;
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = array('application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['resume']['type'], $allowed_types)) {
            $errors[] = "Only PDF and Word documents are allowed";
        } elseif ($_FILES['resume']['size'] > $max_size) {
            $errors[] = "File size must be less than 5MB";
        } else {
            $upload_dir = '../../uploads/resumes/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('resume_') . '.' . $file_ext;
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $target_path)) {
                $resume_path = $filename;
            } else {
                $errors[] = "Failed to upload resume";
            }
        }
    } elseif ($_FILES['resume']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = "Error uploading resume";
    }
    
    // If no errors, save application
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Insert application
            $stmt = $conn->prepare("
                INSERT INTO job_applications 
                (job_id, personal_id, applicant_name, applicant_email, applicant_phone, applicant_resume, application_date, status)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), 'pending')
            ");
            $stmt->execute(array(
                $job_id,
				$user_id,
                $applicant_name,
                $applicant_email,
                $applicant_phone,
                $resume_path,
            ));
            
            $conn->commit();
            $success = true;
            $_SESSION['success'] = "Your application has been submitted successfully!";
            
            // Redirect to prevent form resubmission
            header("Location: job_apply.php?id=" . $job_id);
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Check if user has already applied for this job
$has_applied = false;
if ($user_id && $seeker_profile) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM job_applications 
        WHERE job_id = ?
    ");
    $stmt->execute(array($job_id));
    $has_applied = $stmt->fetchColumn() > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Apply for <?php echo  htmlspecialchars($job['job_title']); ?> - Smart City</title>
  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    .job-header {
      background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url(../assets/img/job-hero-bg.jpg) no-repeat center center;
      background-size: cover;
      padding: 80px 0 60px;
      color: white;
      margin-bottom: 40px;
    }
    
    .job-apply-card {
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }
    
    .company-logo {
      width: 80px;
      height: 80px;
      object-fit: contain;
      border-radius: 8px;
    }
    
    .job-meta span {
      margin-right: 15px;
    }
    
    .job-meta i {
      color: #00d289;
      margin-right: 5px;
    }
    
    .form-group label {
      font-weight: 600;
    }
    
    .required-field::after {
      content: " *";
      color: red;
    }
    
    .alert-success {
      background-color: #d4edda;
      border-color: #c3e6cb;
      color: #155724;
    }
    
    .alert-danger {
      background-color: #f8d7da;
      border-color: #f5c6cb;
      color: #721c24;
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
        
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupport">
          <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarSupport">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a class="nav-link" href="job.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="jobslist.php">Browse Jobs</a></li>
            <li class="nav-item"><a class="nav-link" href="employers.php">Employers</a></li>
            <?php if(isset($_SESSION['user_id'])): ?>
              <?php if($seeker_profile): ?>
                <li class="nav-item"><a class="nav-link" href="seeker-dashboard.php">Profile</a></li>
              <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="create-seeker-profile.php">Create Profile</a></li>
              <?php endif; ?>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <!-- Page Header -->
  <div class="job-header text-center">
    <div class="container">
      <h1 class="display-4"><?php echo htmlspecialchars($job['job_title']) ;?></h1>
      <p class="lead"><?php echo htmlspecialchars($job['company_name']) ;?></p>
    </div>
  </div>

  <!-- Page Content -->
  <div class="page-section">
    <div class="container">
      <div class="row">
        <div class="col-lg-8">
          <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <?php echo $_SESSION['success'] ;?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <?php unset($_SESSION['success']); ?>
          <?php endif; ?>
          
          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                  <li><?php echo htmlspecialchars($error) ;?></li>
                <?php endforeach; ?>
              </ul>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          <?php endif; ?>
          
          <?php if ($has_applied): ?>
            <div class="alert alert-info">
              You have already applied for this position. We'll review your application and get back to you soon.
            </div>
          <?php elseif ($job['application_deadline'] < date('Y-m-d')): ?>
            <div class="alert alert-warning">
              The application deadline for this position has passed.
            </div>
          <?php else: ?>
            <div class="card job-apply-card">
              <div class="card-body">
                <h3 class="card-title mb-4">Apply for this Position</h3>
                
                <form method="POST" enctype="multipart/form-data">
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="applicant_name" class="required-field">Full Name</label>
                      <input type="text" class="form-control" id="applicant_name" name="applicant_name" 
                             value="<?php echo isset($_POST['applicant_name']) ? htmlspecialchars($_POST['applicant_name']) : 
                                    ($seeker_profile ? htmlspecialchars($seeker_profile['full_name']) : '') ;?>" required>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="applicant_email" class="required-field">Email</label>
                      <input type="email" class="form-control" id="applicant_email" name="applicant_email" 
                             value="<?php echo isset($_POST['applicant_email']) ? htmlspecialchars($_POST['applicant_email']) : 
                                    ($seeker_profile ? htmlspecialchars($personal_user['email']) : '') ;?>" required>
                    </div>
                  </div>
                  
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="applicant_phone" class="required-field">Phone Number</label>
                      <input type="tel" class="form-control" id="applicant_phone" name="applicant_phone" 
                             value="<?php echo isset($_POST['applicant_phone']) ? htmlspecialchars($_POST['applicant_phone']) : 
                                    ($seeker_profile ? htmlspecialchars($seeker_profile['phone']) : '') ;?>" required>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="resume" class="required-field">Resume/CV</label>
                      <div class="custom-file">
                        <input type="file" class="custom-file-input" id="resume" name="resume" accept=".pdf,.doc,.docx" required>
                        <label class="custom-file-label" for="resume">Choose file (PDF or Word)</label>
                      </div>
                      <small class="form-text text-muted">Max file size: 5MB</small>
                    </div>
                  </div>
                  
                  <button type="submit" class="btn btn-primary">Submit Application</button>
                  <a href="jobslist.php?id=<?= $job_id ?>" class="btn btn-outline-secondary">Back to Job Details</a>
                </form>
              </div>
            </div>
          <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
          <div class="card mb-4">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <img src="<?php echo htmlspecialchars($job['profile_image']) ;?>" alt="<?php echo htmlspecialchars($job['company_name']) ;?>" class="company-logo mr-3">
                <h4 class="mb-0"><?php echo htmlspecialchars($job['company_name']) ;?></h4>
              </div>
              
              <h5 class="mb-3">Job Details</h5>
              <div class="job-meta mb-3">
                <?php if (!empty($job['salary'])): ?>
                  <span><i class="mai-rupee">Rs</i> $<?php echo htmlspecialchars($job['salary']) ;?></span>
                <?php endif; ?>
                <span><i class="mai-location"></i> <?php htmlspecialchars($job['job_location']) ;?></span>
                <span><i class="mai-calendar"></i> Deadline: <?php echo date('M j, Y', strtotime($job['application_deadline'])) ;?></span>
              </div>
              
              <hr>
              
              <h5 class="mb-3">Job Requirements</h5>
              <div class="mb-3">
                <?php echo nl2br(htmlspecialchars($job['job_requirements'])) ;?>
              </div>
            </div>
          </div>
          
          <div class="card">
            <div class="card-body">
              <h5 class="mb-3">How to Apply</h5>
              <ol>
                <li>Fill out the application form</li>
                <li>Upload your resume/CV</li>
                <li>Optionally include a cover letter</li>
                <li>Submit your application</li>
              </ol>
              <p>We'll review your application and contact you if you're selected for an interview.</p>
            </div>
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
    // Show the file name when a file is selected
    $('.custom-file-input').on('change', function() {
      let fileName = $(this).val().split('\\').pop();
      $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
  </script>
</body>
</html>