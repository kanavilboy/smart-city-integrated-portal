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

// Check if job ID is provided in the URL
if (!isset($_GET['id'])) {
    die("Job ID not provided.");
}

$job_id = $_GET['id'];

// Fetch job details
$stmt = $conn->prepare("SELECT * FROM job_vacancies WHERE id = :id AND merchant_id = :merchant_id");
$stmt->execute(array(
    ':id' => $job_id,
    ':merchant_id' => $merchant['id'],
));
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    die("Job not found or you do not have permission to edit this job.");
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

    // Update job vacancy in the database
    $sql = "UPDATE job_vacancies SET job_title = :job_title, job_description = :job_description, job_requirements = :job_requirements, job_location = :job_location, salary = :salary, application_deadline = :application_deadline WHERE id = :id AND merchant_id = :merchant_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(
        ':job_title' => $job_title,
        ':job_description' => $job_description,
        ':job_requirements' => $job_requirements,
        ':job_location' => $job_location,
        ':salary' => $salary,
        ':application_deadline' => $application_deadline,
        ':id' => $job_id,
        ':merchant_id' => $merchant['id'],
    ));

    if ($stmt->rowCount() > 0) {
        $message = "Job vacancy updated successfully!";
    } else {
        $message = "Failed to update job vacancy.";
    }

    // Refresh the job data after update
    $stmt = $conn->prepare("SELECT * FROM job_vacancies WHERE id = :id AND merchant_id = :merchant_id");
    $stmt->execute(array(
        ':id' => $job_id,
        ':merchant_id' => $merchant['id'],
    ));
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Job Vacancy</title>
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
                        <a href="add_vacancy.php"><i class="fa fa-briefcase"></i>Add Job Vacancy</a>
                    </li>
                    <li>
                        <a href="job_requests.php"><i class="fa fa-tasks"></i>Jobs Requests</a>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- /. NAV SIDE  -->
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-lg-12">
                        <h2>Edit Job Vacancy <span class="logout-spn">
							<a href="view_job.php" class="btn btn-primary">Back</a>
						</span></h2>
                    </div>
                </div>
                <hr />
                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Edit Job Details
                            </div>
                            <div class="panel-body">
                                <?php if (!empty($message)): ?>
                                    <div class="alert alert-info">
                                        <?php echo $message; ?>
                                    </div>
                                <?php endif; ?>
                                <form action="edit_job.php?id=<?php echo $job_id; ?>" method="POST">
                                    <div class="form-group">
                                        <label for="job_title">Job Title</label>
                                        <input type="text" class="form-control" id="job_title" name="job_title" value="<?php echo htmlspecialchars($job['job_title']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="job_description">Job Description</label>
                                        <textarea class="form-control" id="job_description" name="job_description" rows="3" required><?php echo htmlspecialchars($job['job_description']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="job_requirements">Job Requirements</label>
                                        <textarea class="form-control" id="job_requirements" name="job_requirements" rows="3" required><?php echo htmlspecialchars($job['job_requirements']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="job_location">Job Location</label>
                                        <input type="text" class="form-control" id="job_location" name="job_location" value="<?php echo htmlspecialchars($job['job_location']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="salary">Salary</label>
                                        <input type="text" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($job['salary']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="application_deadline">Application Deadline</label>
                                        <input type="date" class="form-control" id="application_deadline" name="application_deadline" value="<?php echo htmlspecialchars($job['application_deadline']); ?>" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update Job</button>
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