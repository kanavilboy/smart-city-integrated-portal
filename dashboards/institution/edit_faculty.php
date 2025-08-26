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

// Get faculty ID from URL
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No faculty member specified";
    header("Location: faculty.php");
    exit();
}

$faculty_id = $_GET['id'];

// Fetch faculty data
$stmt = $conn->prepare("SELECT * FROM institution_faculty WHERE id = ? AND institution_id = ?");
$stmt->execute(array($faculty_id, $institution_id));
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$faculty) {
    $_SESSION['error'] = "Faculty member not found or you don't have permission to edit";
    header("Location: faculty.php");
    exit();
}



// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
    $department_id = isset($_POST['department']) ? $_POST['department'] : null;
    $position = isset($_POST['position']) ? trim($_POST['position']) : '';

    // Validate inputs
    $errors = array();
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($position)) {
        $errors[] = "Position is required";
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("UPDATE institution_faculty SET 
                name = ?, 
                email = ?, 
                phone_number = ?, 
                department = ?, 
                position = ?
                WHERE id = ? AND institution_id = ?");
            
            $stmt->execute(array(
                $name, 
                $email, 
                $phone_number, 
                $department, 
                $position,
                $faculty_id,
                $institution_id
            ));
            
            $_SESSION['success'] = "Faculty member updated successfully!";
            header("Location: faculty.php");
            exit();
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Duplicate entry (unique constraint)
                $errors[] = "Email already exists in our system";
            } else {
                $errors[] = "Error updating faculty member: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Faculty - <?php echo htmlspecialchars($institution_name); ?></title>
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
                    <li class="active-link">
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
                        <h2>Edit Faculty Member</h2>
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
                                Faculty Information
                            </div>
                            <div class="panel-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Full Name *</label>
                                                <input type="text" name="name" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($_POST['name']) ? $_POST['name'] : $faculty['name']); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Email *</label>
                                                <input type="email" name="email" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : $faculty['email']); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Phone Number</label>
                                                <input type="tel" name="phone_number" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($_POST['phone_number']) ? $_POST['phone_number'] : $faculty['phone_number']); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Department</label>
												<input type="text" name="department" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($_POST['department']) ? $_POST['department'] : $faculty['department']); ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Position *</label>
                                                <input type="text" name="position" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($_POST['position']) ? $_POST['position'] : $faculty['position']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Update Faculty Member
                                        </button>
                                        <a href="faculty.php" class="btn btn-default">
                                            <i class="fa fa-arrow-left"></i> Back to Faculty List
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