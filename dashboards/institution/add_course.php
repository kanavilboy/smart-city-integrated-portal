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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_name = isset($_POST['course_name']) ? trim($_POST['course_name']) : '';
    $department = isset($_POST['department']) ? trim($_POST['department']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $duration_years = isset($_POST['duration_years']) ? (float)$_POST['duration_years'] : 1.0;
    $total_semesters = isset($_POST['total_semesters']) ? (int)$_POST['total_semesters'] : 2;
    $eligibility_criteria = isset($_POST['eligibility_criteria']) ? trim($_POST['eligibility_criteria']) : '';

    // Validate inputs
    $errors = array();
    
    if (empty($course_name)) {
        $errors[] = "Course name is required";
    }
    
    if ($duration_years <= 0) {
        $errors[] = "Duration must be greater than 0";
    }
    
    if ($total_semesters <= 0) {
        $errors[] = "Total semesters must be greater than 0";
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT INTO institution_courses 
                (institution_id, course_name, department, description, duration_years, total_semesters, eligibility_criteria) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute(array(
                $institution_id,
                $course_name,
                $department,
                $description,
                $duration_years,
                $total_semesters,
                $eligibility_criteria
            ));
            
            $_SESSION['success'] = "Course added successfully!";
            header("Location: view_courses.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error adding course: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add Course - <?php echo htmlspecialchars($institution_name); ?></title>
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
        .error {
            color: #a94442;
            margin-top: 5px;
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
                        <?php echo htmlspecialchars($institution_name); ?>
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
                    <li>
                        <a href="edit_profile.php"><i class="fa fa-user"></i>Edit Profile</a>
                    </li>
                    <li>
                        <a href="faculty.php"><i class="fa fa-users"></i>Faculty</a>
                    </li>
                    <li class="active-link">
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
                        <h2>Add New Course</h2>
                    </div>
                </div>
                <hr />
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Course Information
                            </div>
                            <div class="panel-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Course Name *</label>
                                                <input type="text" name="course_name" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($_POST['course_name']) ? $_POST['course_name'] : ''); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Department</label>
                                                <input type="text" name="department" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($_POST['department']) ? $_POST['department'] : ''); ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Duration (Years) *</label>
                                                <input type="number" name="duration_years" class="form-control" step="0.1" min="0.1"
                                                    value="<?php echo htmlspecialchars(isset($_POST['duration_years']) ? $_POST['duration_years'] : '1.0'); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Total Semesters *</label>
                                                <input type="number" name="total_semesters" class="form-control" min="1"
                                                    value="<?php echo htmlspecialchars(isset($_POST['total_semesters']) ? $_POST['total_semesters'] : '2'); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Eligibility Criteria</label>
                                                <textarea name="eligibility_criteria" class="form-control" rows="3"><?php 
                                                    echo htmlspecialchars(isset($_POST['eligibility_criteria']) ? $_POST['eligibility_criteria'] : ''); 
                                                ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="description" class="form-control" rows="5"><?php 
                                            echo htmlspecialchars(isset($_POST['description']) ? $_POST['description'] : ''); 
                                        ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Save Course
                                        </button>
                                        <a href="view_courses.php" class="btn btn-default">
                                            <i class="fa fa-arrow-left"></i> Back to Courses
                                        </a>
                                    </div>
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