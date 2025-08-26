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

// Get post ID from URL
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No post specified";
    header("Location: post.php");
    exit();
}

$post_id = $_GET['id'];

// Fetch post data
$stmt = $conn->prepare("SELECT * FROM institution_news WHERE id = ? AND institution_id = ?");
$stmt->execute(array($post_id, $institution_id));
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    $_SESSION['error'] = "Post not found or you don't have permission to edit";
    header("Location: post.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $news = isset($_POST['news']) ? trim($_POST['news']) : '';
    $deadline_date = isset($_POST['deadline_date']) ? $_POST['deadline_date'] : null;

    // Validate inputs
    $errors = array();
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($news)) {
        $errors[] = "News content is required";
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("UPDATE institution_news SET 
                title = ?, 
                news = ?, 
                deadline_date = ?
                WHERE id = ? AND institution_id = ?");
            
            $stmt->execute(array(
                $title,
                $news,
                $deadline_date ? $deadline_date : null,
                $post_id,
                $institution_id
            ));
            
            $_SESSION['success'] = "Post updated successfully!";
            header("Location: post.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error updating post: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Post - <?php echo htmlspecialchars($institution_name); ?></title>
    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES-->
    <link href="assets/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <!-- Datepicker CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" rel="stylesheet" />
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
                    <li>
                        <a href="add_course.php"><i class="fa fa-book"></i>Add Course</a>
                    </li>
                    <li>
                        <a href="view_courses.php"><i class="fa fa-book"></i>View Courses</a>
                    </li>
                    <li class="active-link">
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
                        <h2>Edit Post</h2>
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
                                Post Information
                            </div>
                            <div class="panel-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Title *</label>
                                                <input type="text" name="title" class="form-control" 
                                                    value="<?php echo htmlspecialchars(isset($_POST['title']) ? $_POST['title'] : $post['title']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Deadline Date (optional)</label>
                                                <input type="text" name="deadline_date" class="form-control datepicker" 
                                                    value="<?php echo htmlspecialchars(isset($_POST['deadline_date']) ? $_POST['deadline_date'] : ($post['deadline_date'] ? $post['deadline_date'] : '')); ?>">
                                                <small class="form-text text-muted">Leave blank if post should stay indefinitely</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>News Content *</label>
                                        <textarea name="news" class="form-control" rows="10" required><?php 
                                            echo htmlspecialchars(isset($_POST['news']) ? $_POST['news'] : $post['news']); 
                                        ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Posted Date</label>
                                        <input type="text" class="form-control" 
                                            value="<?php echo date('M j, Y h:i A', strtotime($post['posted_date'])); ?>" readonly>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Update Post
                                        </button>
                                        <a href="post.php" class="btn btn-default">
                                            <i class="fa fa-arrow-left"></i> Back to Posts
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
    <!-- Datepicker JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize datepicker
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
    });
    </script>
</body>
</html>