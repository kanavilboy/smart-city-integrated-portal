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

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    $target_dir = "../../uploads/institution_logos/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $filename = 'institution_' . $institution_id . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $filename;
    
    // Check if image file is actual image
    $check = getimagesize($_FILES['profile_picture']['tmp_name']);
    if ($check === false) {
        $error = "File is not an image.";
    }
    // Check file size (max 5MB)
    elseif ($_FILES['profile_picture']['size'] > 5000000) {
        $error = "Sorry, your file is too large.";
    }
    // Allow certain file formats
    elseif (!in_array($file_extension, array('jpg', 'jpeg', 'png', 'gif'))) {
        $error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
    }
    // Try to upload file
    elseif (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
        // Update database with new logo path
        try {
            $conn->beginTransaction();
            
            // Check if institution_details exists
            $stmt = $conn->prepare("SELECT * FROM institution_details WHERE institution_id = ?");
            $stmt->execute(array($institution_id));
            $details_exist = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($details_exist) {
                // Update existing record
                $stmt = $conn->prepare("UPDATE institution_details SET logo_path = ? WHERE institution_id = ?");
                $stmt->execute(array($target_file, $institution_id));
            } else {
                // Insert new record
                $stmt = $conn->prepare("INSERT INTO institution_details (institution_id, logo_path) VALUES (?, ?)");
                $stmt->execute(array($institution_id, $target_file));
            }
            
            $conn->commit();
            $success = "Profile picture updated successfully!";
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = "Error updating profile picture: " . $e->getMessage();
            // Delete the uploaded file if database update failed
            if (file_exists($target_file)) {
                unlink($target_file);
            }
        }
    } else {
        $error = "Sorry, there was an error uploading your file.";
    }
}

// Get current logo path
$stmt = $conn->prepare("SELECT logo_path FROM institution_details WHERE institution_id = ?");
$stmt->execute(array($institution_id));
$current_logo = $stmt->fetch(PDO::FETCH_ASSOC);
$logo_path = isset($current_logo['logo_path']) ? $current_logo['logo_path'] : '';
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
                        <a href="index.php"><i class="fa fa-desktop "></i>Dashboard</a>
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
                        <h2>Update Profile Picture</h2>
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
                                Current Profile Picture
                            </div>
                            <div class="panel-body">
                                <div class="profile-picture-container">
                                    <?php if (!empty($logo_path) && file_exists($logo_path)): ?>
                                        <img src="<?php echo htmlspecialchars($logo_path); ?>" class="profile-picture" alt="Institution Logo">
                                    <?php else: ?>
                                        <i class="fa fa-institution fa-5x" style="color: #ddd;"></i>
                                        <p>No profile picture uploaded</p>
                                    <?php endif; ?>
                                </div>
                                
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label>Upload New Profile Picture</label>
                                        <div class="upload-btn-wrapper">
                                            <button class="btn-upload">Choose a file</button>
                                            <input type="file" name="profile_picture" accept="image/*" required />
                                        </div>
                                        <p class="help-block">Max file size: 5MB. Allowed formats: JPG, JPEG, PNG, GIF</p>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Upload Picture</button>
                                    <a href="edit_profile.php" class="btn btn-default">Back to Profile</a>
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