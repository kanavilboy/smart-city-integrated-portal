<?php
session_start();
require '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch institution basic info
$stmt = $conn->prepare("SELECT * FROM institutions WHERE user_id = ?");
$stmt->execute(array($user_id));
$institution = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$institution) {
    die("Institution not found for this user.");
}

$institution_id = $institution['id'];
$institution_name = $institution['institution_name'];
$institution_type = $institution['type'];
$message = "Welcome $institution_name ($institution_type)";

// Fetch institution details
$stmt = $conn->prepare("SELECT * FROM institution_details WHERE institution_id = ?");
$stmt->execute(array($institution_id));
$institution_details = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialize empty array if no details exist
if (!$institution_details) {
    $institution_details = array();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Basic info
    $institution_name = isset($_POST['institution_name']) ? $_POST['institution_name'] : '';
    $type = isset($_POST['type']) ? $_POST['type'] : '';
    
    // Contact info
    $contact_email = isset($_POST['contact_email']) ? $_POST['contact_email'] : '';
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $alternate_phone = isset($_POST['alternate_phone']) ? $_POST['alternate_phone'] : '';
    
    // Address info
    $address = isset($_POST['address']) ? $_POST['address'] : '';
    $city = isset($_POST['city']) ? $_POST['city'] : '';
    $state = isset($_POST['state']) ? $_POST['state'] : '';
    $country = isset($_POST['country']) ? $_POST['country'] : 'India';
    $postal_code = isset($_POST['postal_code']) ? $_POST['postal_code'] : '';
    
    // Institution details
    $website = isset($_POST['website']) ? $_POST['website'] : '';
    $principal_name = isset($_POST['principal_name']) ? $_POST['principal_name'] : '';
    $established_year = isset($_POST['established_year']) ? $_POST['established_year'] : '';
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $total_students = isset($_POST['total_students']) ? $_POST['total_students'] : 0;
    $total_staff = isset($_POST['total_staff']) ? $_POST['total_staff'] : 0;
    $institution_category = isset($_POST['institution_category']) ? $_POST['institution_category'] : '';
    $institution_level = isset($_POST['institution_level']) ? $_POST['institution_level'] : '';
	$accreditation = isset($_POST['accreditation']) ? $_POST['accreditation'] : '';
    
    try {
        $conn->beginTransaction();
        
        // Update institutions table
        $stmt = $conn->prepare("UPDATE institutions SET institution_name = ?, type = ? WHERE id = ?");
        $stmt->execute(array($institution_name, $type, $institution_id));
        
        // Update or insert institution_details
        if (!empty($institution_details)) {
            $stmt = $conn->prepare("UPDATE institution_details SET 
			contact_email = ?, phone = ?, alternate_phone = ?, address = ?, city = ?, 
			state = ?, country = ?, postal_code = ?, website = ?, principal_name = ?, 
			established_year = ?, description = ?, total_students = ?, total_staff = ?, 
			institution_category = ?, institution_level = ?, accreditation = ?
			WHERE institution_id = ?");
            $stmt->execute(array(
                $contact_email, $phone, $alternate_phone, $address, $city,
                $state, $country, $postal_code, $website, $principal_name,
                $established_year, $description, $total_students, $total_staff,
                $institution_category, $institution_level, $accreditation, $institution_id
            ));
        } else {
			$stmt = $conn->prepare("INSERT INTO institution_details (
				institution_id, contact_email, phone, alternate_phone, address, city, 
				state, country, postal_code, website, principal_name, established_year, 
				description, total_students, total_staff, institution_category, institution_level, accreditation
			) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$stmt->execute(array(
				$institution_id, $contact_email, $phone, $alternate_phone, $address, $city,
				$state, $country, $postal_code, $website, $principal_name,
				$established_year, $description, $total_students, $total_staff,
				$institution_category, $institution_level, $accreditation
			));
        }
        
        $conn->commit();
        $success = "Profile updated successfully!";
        
        // Refresh data after update
        $stmt = $conn->prepare("SELECT * FROM institutions WHERE user_id = ?");
        $stmt->execute(array($user_id));
        $institution = $stmt->fetch(PDO::FETCH_ASSOC);
        $institution_name = $institution['institution_name'];
        $institution_type = $institution['type'];
        $message = "Welcome $institution_name ($institution_type)";
        
        $stmt = $conn->prepare("SELECT * FROM institution_details WHERE institution_id = ?");
        $stmt->execute(array($institution_id));
        $institution_details = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$institution_details) {
            $institution_details = array();
        }
        
    } catch (PDOException $e) {
        $conn->rollBack();
        $error = "Error updating profile: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Institution Dashboard</title>
    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES-->
    <link href="assets/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .form-group {
            margin-bottom: 15px;
        }
        .form-control {
            height: 40px;
            border-radius: 4px;
        }
        textarea.form-control {
            height: auto;
        }
        .panel-heading {
            padding: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <div class="navbar navbar-inverse navbar-fixed-top">
            <div class="adjust-nav">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="#">
                        <?php echo $institution_name ?>
                    </a>
                </div>
                <span class="logout-spn">
                    <a href="../../login.php" style="color:#fff;" onclick="return confirm('Are you sure you want to logout?')">LOGOUT</a>
                </span>
            </div>
        </div>
        <!-- /. NAV TOP  -->
        <nav class="navbar-default navbar-side" role="navigation">
            <div class="sidebar-collapse">
                <ul class="nav" id="main-menu">
                    <li>
                        <a href="institution_dashboard.php"><i class="fa fa-desktop "></i>Dashboard</a>
                    </li>
                    <li class="active-link">
                        <a href="edit_profile.php"><i class="fa fa-user"></i>Edit Profile</a>
                    </li>
                    <li>
                        <a href="faculty.php"><i class="fa fa-users"></i>Faculty</a>
                    </li>
                    <li>
                        <a href="add_course.php"><i class="fa fa-book"></i>Add Course</a>
                    </li>
                    <li>
                        <a href="view_courses.php"><i class="fa fa-book"></i>View Courses</a>
                    </li>
                    <li>
                        <a href="post.php"><i class="fa fa-list"></i> Posts</a>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- /. NAV SIDE  -->
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-lg-12">
                        <h2>Edit Institution Profile<span class="logout-spn">
							<a href="update_profile_picture.php" class="btn btn-primary">Profile Picture</a>
						</span></h2>
                    </div>
                </div>
                <hr />
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Institution Information
                            </div>
                            <div class="panel-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Institution Name</label>
                                                <input type="text" name="institution_name" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($institution['institution_name']) ? $institution['institution_name'] : ''); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Institution Type</label>
                                                <select name="type" class="form-control" required>
                                                    <option value="school">School</option>
                                                    <option value="college">College</option>
                                                    <option value="university">University</option>
                                                </select>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Contact Email</label>
                                                <input type="email" name="contact_email" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($institution_details['contact_email']) ? $institution_details['contact_email'] : ''); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Phone Number</label>
                                                <input type="tel" name="phone" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($institution_details['phone']) ? $institution_details['phone'] : ''); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Alternate Phone</label>
                                                <input type="tel" name="alternate_phone" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($institution_details['alternate_phone']) ? $institution_details['alternate_phone'] : ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Principal Name</label>
                                                <input type="text" name="principal_name" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($institution_details['principal_name']) ? $institution_details['principal_name'] : ''); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Established Year</label>
                                                <input type="number" name="established_year" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($institution_details['established_year']) ? $institution_details['established_year'] : ''); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Institution Category</label>
                                                <select name="institution_category" class="form-control" required>
                                                    <option value="government">Government</option>
                                                    <option value="private">Private</option>
                                                    <option value="aided">Aided</option>
                                                    <option value="international">International</option>
                                                </select>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Institution Level</label>
                                                <select name="institution_level" class="form-control" required>
                                                    <option value="primary">Primary</option>
                                                    <option value="secondary">Secondary</option>
                                                    <option value="higher_secondary">Higher Secondary</option>
                                                    <option value="college">College</option>
                                                    <option value="university">University</option>
                                                    <option value="vocational">Vocational</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Address</label>
                                                <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars(isset($institution_details['address']) ? $institution_details['address'] : ''); ?></textarea>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>City</label>
                                                <input type="text" name="city" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($institution_details['city']) ? $institution_details['city'] : ''); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>State</label>
                                                <input type="text" name="state" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($institution_details['state']) ? $institution_details['state'] : ''); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Country</label>
                                                <input type="text" name="country" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($institution_details['country']) ? $institution_details['country'] : 'India'); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Postal Code</label>
                                                <input type="text" name="postal_code" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($institution_details['postal_code']) ? $institution_details['postal_code'] : ''); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Website</label>
                                                <input type="url" name="website" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($institution_details['website']) ? $institution_details['website'] : ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Total Students</label>
                                                <input type="number" name="total_students" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($institution_details['total_students']) ? $institution_details['total_students'] : 0); ?>" min="0">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Total Staff</label>
                                                <input type="number" name="total_staff" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($institution_details['total_staff']) ? $institution_details['total_staff'] : 0); ?>" min="0">
                                            </div>
                                        </div>
                                    </div>
									<div class="row">
										<div class="col-md-6">
											<!-- ... other fields ... -->
											
											<div class="form-group">
												<label>Accreditation</label>
												<input type="text" name="accreditation" class="form-control" 
													value="<?php echo htmlspecialchars(isset($institution_details['accreditation']) ? $institution_details['accreditation'] : ''); ?>"
													placeholder="e.g., NAAC, CBSE, ICSE, etc.">
											</div>
										</div>
									</div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="description" class="form-control" rows="5"><?php echo htmlspecialchars(isset($institution_details['description']) ? $institution_details['description'] : ''); ?></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /. PAGE INNER  -->
        </div>
        <!-- /. PAGE WRAPPER  -->
    </div>
    <div class="footer">
        <div class="row">
            <div class="col-lg-12">
                &copy; 2025 jk.com | Design by: <a href="" style="color:#fff;" target="_blank">www.jk.com</a>
            </div>
        </div>
    </div>
    <!-- /. WRAPPER  -->
    <!-- SCRIPTS -AT THE BOTOM TO REDUCE THE LOAD TIME-->
    <!-- JQUERY SCRIPTS -->
    <script src="assets/js/jquery-1.10.2.js"></script>
    <!-- BOOTSTRAP SCRIPTS -->
    <script src="assets/js/bootstrap.min.js"></script>
    <!-- CUSTOM SCRIPTS -->
    <script src="assets/js/custom.js"></script>
</body>
</html>