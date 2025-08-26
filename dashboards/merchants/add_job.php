<?php
session_start();
require '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch merchant details based on logged-in user
$stmt = $conn->query("SELECT * FROM merchants WHERE user_id = $user_id");
$merchant = $stmt->fetch(PDO::FETCH_ASSOC);
$merchant_name = $merchant['name'];

if (!$merchant) {
    die("Merchant not found for this user.");
}

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_title = $_POST['job_title'];
    $job_description = $_POST['job_description'];
    $job_requirements = $_POST['job_requirements'];
    $job_location = $_POST['job_location'];
    $salary = $_POST['salary'];
    $application_deadline = $_POST['application_deadline'];
    $merchant_id = $merchant['id'];

    // Insert job vacancy into the database
    $sql = "INSERT INTO job_vacancies (merchant_id, job_title, job_description, job_requirements, job_location, salary, application_deadline) VALUES (:merchant_id, :job_title, :job_description, :job_requirements, :job_location, :salary, :application_deadline)";
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(
        ':merchant_id' => $merchant_id,
        ':job_title' => $job_title,
        ':job_description' => $job_description,
        ':job_requirements' => $job_requirements,
        ':job_location' => $job_location,
        ':salary' => $salary,
        ':application_deadline' => $application_deadline,
    ));

    if ($stmt->rowCount() > 0) {
        $message = "Job vacancy posted successfully!";
    } else {
        $message = "Failed to post job vacancy.";
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add Job Vacancy</title>
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
                        <?php echo $merchant_name; ?>
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
                        <a href="merchant_dashboard.php"><i class="fa fa-desktop"></i>Dashboard</a>
                    </li>
                    <li>
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
                    <li class="active-link">
                        <a href="add_vacancy.php"><i class="fa fa-briefcase"></i>Add Job</a>
                    </li>
                    <li>
                        <a href="job_requests.php"><i class="fa fa-tasks"></i>Job Requests</a>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- /. NAV SIDE  -->
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-lg-12">
                        <h2>Add Job Vacancy <span class="logout-spn">
							<a href="view_job.php" class="btn btn-primary">View</a>
						</span></h2>
                    </div>
                </div>
                <hr />
                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Job Vacancy Details
                            </div>
                            <div class="panel-body">
                                <?php if (!empty($message)): ?>
                                    <div class="alert alert-info">
                                        <?php echo $message; ?>
                                    </div>
                                <?php endif; ?>
                                <form action="" method="POST">
                                    <div class="form-group">
                                        <label for="job_title">Job Title</label>
                                        <input type="text" class="form-control" id="job_title" name="job_title" placeholder="Enter job title" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="job_description">Job Description</label>
                                        <textarea class="form-control" id="job_description" name="job_description" placeholder="Enter job description" rows="3" required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="job_requirements">Job Requirements</label>
                                        <textarea class="form-control" id="job_requirements" name="job_requirements" placeholder="Enter job requirements" rows="3" required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="job_location">Job Location</label>
                                        <input type="text" class="form-control" id="job_location" name="job_location" placeholder="Enter job location" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="salary">Salary</label>
                                        <input type="text" class="form-control" id="salary" name="salary" placeholder="Enter salary">
                                    </div>
                                    <div class="form-group">
                                        <label for="application_deadline">Application Deadline</label>
                                        <input type="date" class="form-control" id="application_deadline" name="application_deadline" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Post Job Vacancy</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /. ROW  -->
            </div>
            <!-- /. PAGE INNER  -->
        </div>
        <!-- /. PAGE WRAPPER  -->
    </div>
    <div class="footer">
        <div class="row">
            <div class="col-lg-12">
                &copy; 2023 yourdomain.com | Design by: <a href="http://binarytheme.com" style="color:#fff;" target="_blank">www.binarytheme.com</a>
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