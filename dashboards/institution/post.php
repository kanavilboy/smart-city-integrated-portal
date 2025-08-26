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

// Handle form submission for new post
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_post'])) {
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
            $stmt = $conn->prepare("INSERT INTO institution_news 
                (institution_id, title, news, deadline_date) 
                VALUES (?, ?, ?, ?)");
            
            $stmt->execute(array(
                $institution_id,
                $title,
                $news,
                $deadline_date ? $deadline_date : null
            ));
            
            $_SESSION['success'] = "News post added successfully!";
            header("Location: post.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error adding news post: " . $e->getMessage();
        }
    }
}

// Handle post deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM institution_news WHERE id = ? AND institution_id = ?");
        $stmt->execute(array($delete_id, $institution_id));
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Post deleted successfully";
        } else {
            $_SESSION['error'] = "Post not found or you don't have permission to delete";
        }
        header("Location: post.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting post: " . $e->getMessage();
        header("Location: post.php");
        exit();
    }
}

// Fetch all news posts for this institution
$stmt = $conn->prepare("SELECT * FROM institution_news WHERE institution_id = ? ORDER BY posted_date DESC");
$stmt->execute(array($institution_id));
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>News Posts - <?php echo htmlspecialchars($institution_name); ?></title>
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
    <!-- Datepicker CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" rel="stylesheet" />
    <style>
        .post-content {
            max-height: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        .action-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
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
                        <h2>News Posts Management<span class="logout-spn">
                            <button class="btn btn-primary" data-toggle="modal" data-target="#addPostModal">
                                <i class="fa fa-plus"></i> Add New Post
                            </button>
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
                                Current News Posts
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table id="postsTable" class="table table-striped table-bordered" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Title</th>
                                                <th>Posted Date</th>
                                                <th>Deadline Date</th>
                                                <th>Content Preview</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($posts as $post): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($post['id']); ?></td>
                                                <td><?php echo htmlspecialchars($post['title']); ?></td>
                                                <td><?php echo date('M j, Y h:i A', strtotime($post['posted_date'])); ?></td>
                                                <td>
                                                    <?php echo $post['deadline_date'] ? 
                                                        date('M j, Y', strtotime($post['deadline_date'])) : 
                                                        'No deadline'; ?>
                                                </td>
                                                <td class="post-content" title="<?php echo htmlspecialchars($post['news']); ?>">
                                                    <?php echo htmlspecialchars($post['news']); ?>
                                                </td>
                                                <td class="action-buttons">
                                                    <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </a>
                                                    <button class="btn btn-danger btn-sm delete-post" data-id="<?php echo $post['id']; ?>">
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

    <!-- Add Post Modal -->
    <div class="modal fade" id="addPostModal" tabindex="-1" role="dialog" aria-labelledby="addPostModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPostModalLabel">Add New News Post</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>Title *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>News Content *</label>
                            <textarea name="news" class="form-control" rows="8" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Deadline Date (optional)</label>
                            <input type="text" name="deadline_date" class="form-control datepicker">
                            <small class="form-text text-muted">Leave blank if post should stay indefinitely</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="add_post" class="btn btn-primary">Save Post</button>
                    </div>
                </form>
            </div>
        </div>
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
    <!-- Datepicker JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#postsTable').DataTable({
            responsive: true,
            order: [[2, 'desc']] // Sort by posted_date descending
        });
        
        // Initialize datepicker
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
        
        // Delete confirmation
        $('.delete-post').click(function() {
            var postId = $(this).data('id');
            if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
                window.location.href = 'post.php?delete_id=' + postId;
            }
        });
    });
    </script>
</body>
</html>