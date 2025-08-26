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

// Process form submission if this is an AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'accept_application') {
        $application_id = $_POST['application_id'];
        $message = $_POST['message'];
        
        try {
            // Update application status
            $stmt = $conn->prepare("UPDATE job_applications SET status = 'Accepted' WHERE id = ?");
            $stmt->execute(array($application_id));
            
            // Get applicant user_id
            $stmt = $conn->prepare("
                SELECT u.id AS user_id 
                FROM job_applications ja
                JOIN job_seeker_profiles jsp ON ja.applicant_name = jsp.full_name
                JOIN users u ON jsp.user_id = u.id
                WHERE ja.id = ?
            ");
            $stmt->execute(array($application_id));
            $applicant = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($applicant) {
                // Save message
                $stmt = $conn->prepare("
                    INSERT INTO message (customer_id, sender, message, sent_date)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute(array(
                    $applicant['user_id'],
                    $merchant_name,
                    $message
                ));
            }
            
            echo json_encode(array('success' => true));
            exit();
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
            exit();
        }
    }
}

// Fetch job applications for the merchant's job vacancies
$stmt = $conn->query("
    SELECT ja.*, jv.job_title 
    FROM job_applications ja
    JOIN job_vacancies jv ON ja.job_id = jv.id
    WHERE jv.merchant_id = {$merchant['id']}
");
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Job Requests</title>
    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES-->
    <link href="assets/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .accept-modal textarea {
            min-height: 120px;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <!-- [Navigation code remains the same] -->
			<div class="navbar navbar-inverse navbar-fixed-top">
				<div class="adjust-nav">
					<div class="navbar-header">
						<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>
						<a class="navbar-brand" href="#">
							<?php echo $merchant_name?>
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
						<li class="active-link">
							<a href="merchant_dashboard.php"><i class="fa fa-desktop "></i>Dashboard</a>
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
						<li>
							<a href="add_job.php"><i class="fa fa-briefcase"></i>Add Job</a>
						</li>
						<li>
							<a href="job_requests.php">
								<i class="fa fa-tasks"></i> <!-- Use the tasks icon -->
								Job Requests
							</a>
						</li>
					</ul>
				</div>
			</nav>
			<!-- /. NAV SIDE  -->
        
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-lg-12">
                        <h2>Job Requests</h2>
                    </div>
                </div>
                <hr />
                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Job Applications
                            </div>
                            <div class="panel-body">
                                <?php if (empty($applications)): ?>
                                    <div class="alert alert-info">
                                        No job applications found.
                                    </div>
                                <?php else: ?>
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Job Title</th>
                                                <th>Applicant Name</th>
                                                <th>Applicant Email</th>
                                                <th>Applicant Phone</th>
                                                <th>Resume</th>
                                                <th>Application Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($applications as $application): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($application['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($application['job_title']); ?></td>
                                                    <td><?php echo htmlspecialchars($application['applicant_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($application['applicant_email']); ?></td>
                                                    <td><?php echo htmlspecialchars($application['applicant_phone']); ?></td>
                                                    <td>
                                                        <a href="<?php echo htmlspecialchars($application['applicant_resume']); ?>" target="_blank">Download Resume</a>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($application['application_date']); ?></td>
                                                    <td><?php echo htmlspecialchars($application['status']); ?></td>
                                                    <td>
                                                        <button class="btn btn-success btn-sm accept-btn" 
                                                                data-application-id="<?php echo $application['id']; ?>"
                                                                data-applicant-name="<?php echo htmlspecialchars($application['applicant_name']); ?>"
                                                                data-job-title="<?php echo htmlspecialchars($application['job_title']); ?>">
                                                            Accept
                                                        </button>
                                                        <a href="update_application_status.php?id=<?php echo $application['id']; ?>&status=Rejected" class="btn btn-danger btn-sm">Reject</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Accept Application Modal -->
    <div class="modal fade accept-modal" id="acceptModal" tabindex="-1" role="dialog" aria-labelledby="acceptModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="acceptModalLabel">Accept Job Application</h4>
                </div>
                <form id="acceptForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="accept_application">
                        <input type="hidden" name="application_id" id="application_id">
                        
                        <div class="form-group">
                            <label>Applicant:</label>
                            <p class="form-control-static" id="applicantName"></p>
                        </div>
                        <div class="form-group">
                            <label>Job Title:</label>
                            <p class="form-control-static" id="jobTitle"></p>
                        </div>
                        <div class="form-group">
                            <label for="message">Message to Applicant:</label>
                            <textarea class="form-control" id="message" name="message" required></textarea>
                            <small class="help-block">This message will be sent to the applicant.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Acceptance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="row">
            <div class="col-lg-12">
                &copy; 2023 yourdomain.com | Design by: <a href="http://binarytheme.com" style="color:#fff;" target="_blank">www.binarytheme.com</a>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/custom.js"></script>
    
    <script>
    $(document).ready(function() {
        // Handle accept button click
        $('.accept-btn').click(function() {
            var applicationId = $(this).data('application-id');
            var applicantName = $(this).data('applicant-name');
            var jobTitle = $(this).data('job-title');
            
            $('#application_id').val(applicationId);
            $('#applicantName').text(applicantName);
            $('#jobTitle').text(jobTitle);
            $('#message').val('Dear ' + applicantName + ',\n\nWe are pleased to inform you that your application for "' + jobTitle + '" has been accepted.\n\n');
            
            $('#acceptModal').modal('show');
        });
        
        // Handle form submission
        $('#acceptForm').submit(function(e) {
            e.preventDefault();
            
            $.ajax({
                type: 'POST',
                url: window.location.href,
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#acceptModal').modal('hide');
                        location.reload(); // Refresh the page to show updated status
                    } else {
                        alert('Error: ' + (response.error || 'Unknown error occurred'));
                    }
                },
                error: function() {
                    alert('An error occurred while processing your request.');
                }
            });
        });
    });
    </script>
</body>
</html>