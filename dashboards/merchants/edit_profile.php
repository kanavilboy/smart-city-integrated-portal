<?php
session_start();
require '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Fetch merchant data
$stmt = $conn->prepare("SELECT * FROM merchants WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$merchant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$merchant) {
    die("Merchant not found for this user.");
}

$merchant_name = $merchant['name'];

// Handle profile details update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_details'])) {
    try {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $address = $_POST['address'];
        $contact = $_POST['contact'];
        
        $update_stmt = $conn->prepare("UPDATE merchants SET name = :name, email = :email, address = :address, contact = :contact WHERE user_id = :user_id");
        $update_stmt->bindParam(':name', $name);
        $update_stmt->bindParam(':email', $email);
        $update_stmt->bindParam(':address', $address);
        $update_stmt->bindParam(':contact', $contact);
        $update_stmt->bindParam(':user_id', $user_id);
        
        if ($update_stmt->execute()) {
            $message = "Profile updated successfully!";
            // Refresh merchant data
            $stmt->execute();
            $merchant = $stmt->fetch(PDO::FETCH_ASSOC);
            $merchant_name = $merchant['name'];
        } else {
            $message = "Error updating profile.";
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
    }
}

// Handle profile picture upload
if (isset($_POST['upload_pic']) && isset($_FILES['profile_pic'])) {
    $target_dir = "../../uploads/merchants/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    $target_file = $target_dir . basename($_FILES["profile_pic"]["name"]);
    if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
        $conn->query("UPDATE merchants SET profile_image = '$target_file' WHERE user_id = $user_id");
        $message = "Profile picture updated successfully!";
    } else {
        $message = "Failed to upload profile picture.";
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Profile</title>
    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES-->
    <link href="assets/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
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
                        <?php echo htmlspecialchars($merchant_name); ?>
                    </a>
                </div>
                <span class="logout-spn">
                    <a href="../../login.php" style="color:#fff;">LOGOUT</a>
                </span>
            </div>
        </div>
        <!-- /. NAV TOP  -->
        <nav class="navbar-default navbar-side" role="navigation">
            <div class="sidebar-collapse">
                <ul class="nav" id="main-menu">
                    <li>
                        <a href="merchant_dashboard.php"><i class="fa fa-desktop "></i>Dashboard</a>
                    </li>
                    <li class="active-link">
                        <a href="edit_profile.php"><i class="fa fa-user"></i>Edit Profile</a>
                    </li>
                    <li>
                        <a href="view_booking.php"><i class="fa fa-calendar"></i>View Bookings</a>
                    </li>
                    <li>
                        <a href="add_product.php"><i class="fa fa-plus"></i>Add Product</a>
                    </li>
                    <li>
                        <a href="view_products.php"><i class="fa fa-list"></i>View Products</a>
                    </li>
                    <li>
                        <a href="add_job.php"><i class="fa fa-briefcase"></i>Add Job</a>
                    </li>
                    <li>
                        <a href="job_requests.php">
                            <i class="fa fa-tasks"></i>
                            Job Requests
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- /. NAV SIDE  -->
        <div id="page-wrapper">
            <div id="page-inner">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-12">
                        <h2>Edit Profile</h2>
                    </div>
                </div>
                <hr />
                <div class="col-lg-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">Update Merchant Details</div>
                        <div class="panel-body">
                            <!-- Edit Profile Form -->
                            <form action="edit_profile.php" method="POST">
                                <div class="form-group">
                                    <label for="name">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                        value="<?php echo htmlspecialchars($merchant['name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                        value="<?php echo htmlspecialchars($merchant['email']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" 
                                        value="<?php echo htmlspecialchars($merchant['address']); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="contact">Contact Number</label>
                                    <input type="tel" class="form-control" id="contact" name="contact" 
                                        value="<?php echo htmlspecialchars($merchant['contact']); ?>">
                                </div>
                                <button type="submit" name="save_details" class="btn btn-primary">Save Details</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">Change Profile Picture</div>
                        <div class="panel-body">
                            <form method="POST" enctype="multipart/form-data" action="edit_profile.php">
                                <div class="form-group">
                                    <label>Upload New Profile Picture</label>
                                    <input type="file" name="profile_pic" class="form-control" accept="image/*" required />
                                </div>
                                <button type="submit" name="upload_pic" class="btn btn-primary">Upload</button>
                            </form>
                            <hr>
                            <p><strong>Current Profile Picture:</strong></p>
                            <img src="../../uploads/merchants/<?php echo !empty($merchant['profile_image']) ? htmlspecialchars($merchant['profile_image']) : 'default.jpg'; ?>" 
                                 alt="Profile Picture" width="150px" class="img-thumbnail" />
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
                &copy; <?php echo date('Y'); ?> yourdomain.com | Design by: <a href="http://binarytheme.com" style="color:#fff;" target="_blank">www.binarytheme.com</a>
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