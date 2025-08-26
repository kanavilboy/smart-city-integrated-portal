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

// Fetch all courses for this institution
$stmt = $conn->prepare("SELECT * FROM institution_courses WHERE institution_id = ? ORDER BY course_name");
$stmt->execute(array($institution_id));
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM institution_courses WHERE id = ? AND institution_id = ?");
        $stmt->execute(array($delete_id, $institution_id));
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Course deleted successfully";
        } else {
            $_SESSION['error'] = "Course not found or you don't have permission to delete";
        }
        header("Location: view_courses.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting course: " . $e->getMessage();
        header("Location: view_courses.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>View Courses - <?php echo htmlspecialchars($institution_name); ?></title>
    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES-->
    <link href="assets/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <style>
        .action-buttons .btn {
            margin-right: 5px;
        }
        .course-description {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
                    <li class="active-link">
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
                        <h2>Course Management<span class="logout-spn">
                            <a href="add_course.php" class="btn btn-primary"><i class="fa fa-plus"></i> Add New Course</a>
                        </span></h2>
                    </div>
                </div>
                <hr />
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                All Courses
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table id="coursesTable" class="table table-striped table-bordered" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Course Name</th>
                                                <th>Department</th>
                                                <th>Duration</th>
                                                <th>Semesters</th>
                                                <th>Description</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($courses as $course): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($course['id']); ?></td>
                                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                                <td><?php echo htmlspecialchars($course['department']); ?></td>
                                                <td><?php echo htmlspecialchars($course['duration_years']); ?> years</td>
                                                <td><?php echo htmlspecialchars($course['total_semesters']); ?></td>
                                                <td class="course-description" title="<?php echo htmlspecialchars($course['description']); ?>">
                                                    <?php echo htmlspecialchars($course['description']); ?>
                                                </td>
                                                <td class="action-buttons">
                                                    <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </a>
                                                    <button class="btn btn-danger btn-sm delete-course" data-id="<?php echo $course['id']; ?>">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
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
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#coursesTable').DataTable({
            responsive: true,
            columnDefs: [
                { responsivePriority: 1, targets: 1 }, // Course name
                { responsivePriority: 2, targets: -1 }, // Actions
                { responsivePriority: 3, targets: 2 }  // Department
            ]
        });
        
        // Delete confirmation
        $('.delete-course').click(function() {
            var courseId = $(this).data('id');
            if (confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
                window.location.href = 'view_courses.php?delete_id=' + courseId;
            }
        });
    });
    </script>
</body>
</html>