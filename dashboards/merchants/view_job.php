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

// Fetch job vacancies for the merchant
$stmt = $conn->query("SELECT * FROM job_vacancies WHERE merchant_id = {$merchant['id']}");
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>View Jobs</title>
    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES-->
    <link href="assets/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <!-- JQUERY -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                        <a href="add_job.php"><i class="fa fa-briefcase"></i>Add Job</a>
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
                        <h2>View Jobs<span class="logout-spn">
							<a href="add_job.php" class="btn btn-primary">Back</a>
						</span></h2>
                    </div>
                </div>
                <hr />
                <div class="row">
                    <div class="col-lg-12">
							<!-- /. card  -->
							<?php if (empty($jobs)): ?>
							<div class="alert alert-info">
                                        No job found.
                                    </div>
							<?php else: ?>
								<?php foreach ($jobs as $job): ?>
								<div class="col-lg-6">
									<div class="panel panel-default">
											<div class="panel-heading">
										Job
									</div>
										<div class="panel-body">
											<table class="table table-bordered table-striped">
												<thead>
													<tr>
														<th>Job Title</th>
														<th>Deadline</th>
														<th>Actions</th>
													</tr>
												</thead>
												<tbody>
														<tr id="job-<?php echo $job['id']; ?>">
															<td><?php echo htmlspecialchars($job['job_title']); ?></td>
															<td><?php echo htmlspecialchars($job['application_deadline']); ?></td>
															<td>
																<a href="edit_job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
																<button type="button" class="btn btn-danger btn-sm delete-job" data-id="<?php echo $job['id']; ?>">Delete</button>
															</td>
														</tr>
												</tbody>
											</table>
										</div>
									</div>
								</div>
								<?php endforeach; ?>
								<?php endif; ?>
                    </div>
					<!-- /. card end  -->
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
    <script>
        // Handle delete button click
        $(document).ready(function() {
            $('.delete-job').click(function() {
                if (confirm('Are you sure you want to delete this job vacancy?')) {
                    var jobId = $(this).data('id');
                    $.ajax({
                        url: 'delete_job.php',
                        type: 'POST',
                        data: { job_id: jobId },
                        success: function(response) {
                            if (response === 'success') {
                                // Remove the deleted row from the table
                                $('#job-' + jobId).remove();
                                alert('Job vacancy deleted successfully!');
                            } else {
                                alert('Failed to delete job vacancy.');
                            }
                        },
                        error: function() {
                            alert('An error occurred while deleting the job vacancy.');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>