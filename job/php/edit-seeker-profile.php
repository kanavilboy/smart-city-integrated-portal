<?php
session_start();
require '../../database.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$errors = array();
$profile = array();
$success = false;

// Fetch existing profile data
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $full_name = trim($_POST['full_name']);
    $headline = trim($_POST['headline']);
    $bio = trim($_POST['bio']);
    $skills = trim($_POST['skills']);
    $experience = trim($_POST['experience']);
    $experience_years = !empty($_POST['experience_years']) ? (int)$_POST['experience_years'] : null;
    $education = trim($_POST['education']);
    $certifications = trim($_POST['certifications']);
    $location = trim($_POST['location']);
    $phone = trim($_POST['phone']);
    $portfolio_url = trim($_POST['portfolio_url']);
    $availability = $_POST['availability'];
    $desired_salary = trim($_POST['desired_salary']);

    // Basic validation
    if (empty($full_name)) $errors['full_name'] = 'Full name is required';
    if (empty($headline)) $errors['headline'] = 'Headline is required';
    if (empty($bio)) $errors['bio'] = 'Bio is required';
    if (empty($skills)) $errors['skills'] = 'Skills are required';

    // Handle file uploads
	$resume_path = isset($profile['resume_path']) ? $profile['resume_path'] : null;
	$profile_photo_path = isset($profile['profile_photo']) ? $profile['profile_photo'] : null;
    
    // Process resume upload
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = array('application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        if (!in_array($_FILES['resume']['type'], $allowed_types)) {
            $errors['resume'] = 'Only PDF and Word documents are allowed';
        } else {
            $upload_dir = '../../uploads/resumes/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $file_ext = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
            $resume_filename = 'resume_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
            $resume_path = $upload_dir . $resume_filename;
            if (!move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path)) {
                $errors['resume'] = 'Failed to upload resume';
            }
        }
    }

    // Process profile photo upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
        if (!in_array($_FILES['profile_photo']['type'], $allowed_types)) {
            $errors['profile_photo'] = 'Only JPG, PNG and GIF images are allowed';
        } else {
            $upload_dir = '../../uploads/profile_photos/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $file_ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $profile_photo_filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
            $profile_photo_path = $upload_dir . $profile_photo_filename;
            if (!move_uploaded_file($_FILES['profile_photo']['tmp_name'], $profile_photo_path)) {
                $errors['profile_photo'] = 'Failed to upload profile photo';
            }
        }
    }

    // Update database if no errors
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            $sql = "UPDATE job_seeker_profiles SET 
                    full_name = ?, headline = ?, bio = ?, skills = ?, 
                    experience = ?, experience_years = ?, education = ?, 
                    certifications = ?, location = ?, phone = ?, 
                    portfolio_url = ?, resume_path = ?, 
                    profile_photo = ?, availability = ?, desired_salary = ?, 
                    updated_at = NOW()
                    WHERE user_id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute(array(
                $full_name, $headline, $bio, $skills,
                $experience, $experience_years, $education,
                $certifications, $location, $phone,
                $portfolio_url, $resume_path,
                $profile_photo_path, $availability, $desired_salary,
                $_SESSION['user_id']
            ));
            
            $conn->commit();
            $success = true;
            $_SESSION['success_message'] = 'Profile updated successfully!';
            header('Location: seeker-dashboard.php');
            exit();
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors['database'] = 'Error updating profile: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Smart City</title>
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
        }
        .form-group {
            margin-bottom: 20px;
        }
        .file-upload {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
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
            padding: 15px;
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
        }
        .current-file {
            font-size: 14px;
            margin-top: 5px;
            color: #666;
        }
        .profile-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 4px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
              $stmt = $conn->prepare("SELECT * FROM job_seeker_profiles WHERE user_id = ?");
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

    <!-- Edit Profile Form -->
    <div class="page-section profile-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="profile-card">
                        <h2 class="text-center mb-4">Edit Your Profile</h2>
                        
                        <?php if (!empty($errors['database'])): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($errors['database']) ;?></div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <!-- Left Column -->
                                <div class="col-md-6">
                                    <!-- Profile Photo -->
                                    <div class="form-group text-center">
                                        <img id="profile-preview" src="<?php echo htmlspecialchars($profile['profile_photo'] ? '../uploads/'.basename($profile['profile_photo']) : '../assets/img/default-profile.jpg') ;?>" 
                                             class="profile-preview" alt="Profile Preview">
                                        <div class="file-upload mt-3">
                                            <label for="profile_photo" class="file-upload-label">
                                                <span class="mai-camera"></span> Change Profile Photo
                                                <span class="file-name" id="photo-file-name">No file chosen</span>
                                                <input type="file" class="file-upload-input" id="profile_photo" name="profile_photo" accept="image/*">
                                            </label>
                                            <?php if (isset($errors['profile_photo'])): ?>
                                                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['profile_photo']) ; ?></div>
                                            <?php endif; ?>
                                            <?php if ($profile['profile_photo']): ?>
                                                <div class="current-file">Current: <?php echo basename($profile['profile_photo']) ; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Basic Info -->
                                    <div class="form-group">
										<label for="full_name">Full Name *</label>
										<input type="text" class="form-control" id="full_name" name="full_name" 
										value="<?php echo htmlspecialchars($profile['full_name']); ?>" required>
										<?php if (isset($errors['full_name'])): ?>
										  <div class="invalid-feedback"><?php echo htmlspecialchars($errors['full_name']) ;?></div>
										<?php endif; ?>
									  </div>
                                    
                                    <div class="form-group">
                                        <label for="headline">Professional Headline *</label>
                                        <input type="text" class="form-control <?php echo isset($errors['headline']) ? 'is-invalid' : '' ; ?>" 
                                               id="headline" name="headline" 
                                               value="<?php echo htmlspecialchars($profile['headline']); ?>" required>
                                        <?php if (isset($errors['headline'])): ?>
                                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['headline']) ; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="location">Location</label>
                                        <input type="text" class="form-control" id="location" name="location" 
                                               value="<?php echo htmlspecialchars($profile['location']); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($profile['phone']); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="availability">Availability *</label>
                                        <select class="form-control" id="availability" name="availability" required>
                                            <?php
											$availability = isset($_POST['availability']) ? $_POST['availability'] : (isset($profile['availability']) ? $profile['availability'] : '');
											?>

											<option value="full-time" <?php echo $availability === 'full-time' ? 'selected' : ''; ?>>Full-time</option>
											<option value="part-time" <?php echo $availability === 'part-time' ? 'selected' : ''; ?>>Part-time</option>
											<option value="contract" <?php echo $availability === 'contract' ? 'selected' : ''; ?>>Contract</option>
											<option value="freelance" <?php echo $availability === 'freelance' ? 'selected' : ''; ?>>Freelance</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="desired_salary">Desired Salary</label>
                                        <input type="text" class="form-control" id="desired_salary" name="desired_salary" 
                                               value="<?php echo htmlspecialchars($profile['desired_salary']); ?>">
                                    </div>
                                </div>
                                
                                <!-- Right Column -->
                                <div class="col-md-6">
                                    <!-- Resume -->
                                    <div class="form-group">
                                        <label>Resume *</label>
                                        <div class="file-upload">
                                            <label for="resume" class="file-upload-label">
                                                <span class="mai-document"></span> Upload Resume
                                                <span class="file-name" id="resume-file-name">No file chosen</span>
                                                <input type="file" class="file-upload-input" id="resume" name="resume" accept=".pdf,.doc,.docx">
                                            </label>
                                            <?php if (isset($errors['resume'])): ?>
                                                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['resume']) ;?></div>
                                            <?php endif; ?>
                                            <?php if ($profile['resume_path']): ?>
                                                <div class="current-file">Current: <?php echo basename($profile['resume_path']) ; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Bio -->
                                    <div class="form-group">
                                        <label for="bio">Professional Summary *</label>
                                        <textarea class="form-control <?php echo isset($errors['bio']) ? 'is-invalid' : '' ;?>" 
                                                  id="bio" name="bio" rows="5" required><?php echo htmlspecialchars($profile['bio']); ?></textarea>
                                        <?php if (isset($errors['bio'])): ?>
                                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['bio']) ;?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Skills -->
                                    <div class="form-group">
                                        <label for="skills">Skills *</label>
                                        <textarea class="form-control <?php echo isset($errors['skills']) ? 'is-invalid' : '' ;?>" 
                                                  id="skills" name="skills" rows="3" required><?php echo htmlspecialchars($profile['skills']); ?></textarea>
                                        <?php if (isset($errors['skills'])): ?>
                                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['skills']) ;?></div>
                                        <?php endif; ?>
                                        <small class="form-text text-muted">Separate skills with commas (e.g., HTML, CSS, JavaScript)</small>
                                    </div>
                                    
                                    <!-- Experience -->
                                    <div class="form-group">
                                        <label for="experience_years">Years of Experience</label>
                                        <input type="number" class="form-control" id="experience_years" name="experience_years" 
                                               value="<?php echo htmlspecialchars($profile['experience_years']); ?>" min="0" max="50">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="experience">Work Experience</label>
                                        <textarea class="form-control" id="experience" name="experience" 
                                                  rows="4"><?php echo htmlspecialchars($profile['experience']); ?></textarea>
                                    </div>
                                    
                                    <!-- Education -->
                                    <div class="form-group">
                                        <label for="education">Education</label>
                                        <textarea class="form-control" id="education" name="education" 
                                                  rows="4"><?php echo htmlspecialchars($profile['education']); ?></textarea>
                                    </div>
                                    
                                    <!-- Certifications -->
                                    <div class="form-group">
                                        <label for="certifications">Certifications</label>
                                        <textarea class="form-control" id="certifications" name="certifications" 
                                                  rows="3"><?php echo htmlspecialchars($profile['certifications']); ?></textarea>
                                    </div>
                                    
                                    <!-- Links -->
                                    <div class="form-group">
                                        <label for="portfolio_url">Portfolio URL</label>
                                        <input type="url" class="form-control" id="portfolio_url" name="portfolio_url" 
                                               value="<?php echo htmlspecialchars($profile['portfolio_url']); ?>">
                                    </div>
                                    
                                </div>
                            </div>
                            
                            <div class="form-group text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">Update Profile</button>
                                <a href="seeker-profile.php" class="btn btn-outline-secondary btn-lg ml-2">Cancel</a>
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
    <script src="../assets/vendor/wow/wow.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    
    <script>
        // File upload name display
        document.getElementById('resume').addEventListener('change', function(e) {
            document.getElementById('resume-file-name').textContent = 
                e.target.files[0] ? e.target.files[0].name : 'No file chosen';
        });
        
        document.getElementById('profile_photo').addEventListener('change', function(e) {
            document.getElementById('photo-file-name').textContent = 
                e.target.files[0] ? e.target.files[0].name : 'No file chosen';
            
            // Preview the new image
            if (e.target.files && e.target.files[0]) {
                var reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('profile-preview').src = event.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>
</html>