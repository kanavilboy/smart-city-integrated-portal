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

// Fetch all faculty members for this institution
$stmt = $conn->prepare("SELECT * FROM institution_faculty WHERE institution_id = ? ORDER BY name");
$stmt->execute(array($institution_id));
$faculty_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM institution_faculty WHERE id = ? AND institution_id = ?");
        $stmt->execute(array($delete_id, $institution_id));
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Faculty member deleted successfully";
        } else {
            $_SESSION['error'] = "Faculty member not found or you don't have permission to delete";
        }
        header("Location: faculty.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting faculty member: " . $e->getMessage();
        header("Location: faculty.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Faculty Management - <?php echo $institution_name; ?></title>
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
        table.dataTable thead th, table.dataTable thead td {
            padding: 10px 18px;
        }
        table.dataTable tbody th, table.dataTable tbody td {
            padding: 8px 10px;
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
                        <?php echo $institution_name; ?>
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
                        <h2>Faculty Management<span class="logout-spn">
                            <a href="add_faculty.php" class="btn btn-primary"><i class="fa fa-plus"></i> Add Faculty</a>
                        </span></h2>
                    </div>
                </div>
                <hr />
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Faculty Members List
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table id="facultyTable" class="table table-striped table-bordered" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Position</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($faculty_members as $faculty): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($faculty['id']); ?></td>
                                                <td><?php echo htmlspecialchars($faculty['name']); ?></td>
                                                <td><?php echo htmlspecialchars($faculty['email']); ?></td>
                                                <td><?php echo htmlspecialchars($faculty['phone_number']); ?></td>
                                                <td><?php echo htmlspecialchars($faculty['position']); ?></td>
                                                <td class="action-buttons">
                                                    <a href="edit_faculty.php?id=<?php echo $faculty['id']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </a>
                                                    <button class="btn btn-danger btn-sm delete-faculty" data-id="<?php echo $faculty['id']; ?>">
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
        $('#facultyTable').DataTable({
            responsive: true
        });
        
        // Delete confirmation
        $('.delete-faculty').click(function() {
            var facultyId = $(this).data('id');
            if (confirm('Are you sure you want to delete this faculty member?')) {
                window.location.href = 'faculty.php?delete_id=' + facultyId;
            }
        });
    });
    </script>
</body>
</html>