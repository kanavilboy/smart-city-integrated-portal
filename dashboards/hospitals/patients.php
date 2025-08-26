<?php
session_start();
require '../../database.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get hospital_id for the logged-in user
$stmt = $conn->prepare("SELECT * FROM hospitals WHERE user_id = ?");
$stmt->execute(array($user_id));
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hospital) {
    die("Hospital not found.");
}

$hospital_id = $hospital['id'];
$hospital_name = $hospital['hospital_name'];

// Fetch all notifications (patients) for this hospital
$notification_stmt = $conn->prepare("SELECT * FROM patient WHERE hospital_id = ? ORDER BY created_at DESC");
$notification_stmt->execute(array($hospital_id));
$patients = $notification_stmt->fetchAll(PDO::FETCH_ASSOC);

// handle delete
if (isset($_GET['delete'])) {
    $notification_id = intval($_GET['delete']);

    // Delete only if the notification belongs to this hospital
    $delete_stmt = $conn->prepare("DELETE FROM patient WHERE id = ? AND hospital_id = ?");
    if ($delete_stmt->execute(array($notification_id, $hospital_id))) {
        $message = "Notification deleted successfully.";
    } else {
        $message = "Failed to delete the notification.";
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="utf-8" />
    <title>Patients List - Hospital Management Dashboard</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
</head>

<body>
    <div id="wrapper">
        <div class="navbar navbar-inverse navbar-fixed-top">
            <div class="adjust-nav">
                <div class="navbar-header">
                    <a class="navbar-brand" href="#"><?php echo $hospital_name; ?></a>
                </div>
                <span class="logout-spn"><a href="../../login.php" style="color:#fff;">LOGOUT</a></span>
            </div>
        </div>

        <nav class="navbar-default navbar-side" role="navigation">
            <div class="sidebar-collapse">
                <ul class="nav" id="main-menu">
                    <li><a href="hospital_dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                    <li><a href="doctors.php"><i class="fa fa-user-md"></i> View Doctors</a></li>
                    <li><a href="regdoctor.php"><i class="fa fa-user-md"></i> Add Doctors</a></li>
                    <li class="active-link"><a href="#"><i class="fa fa-users"></i> Manage Patients</a></li>
                    <li><a href="appointments.php"><i class="fa fa-calendar"></i> Appointments</a></li>
                    <li><a href="settings.php"><i class="fa fa-cogs"></i> Settings</a></li>
                </ul>
            </div>
        </nav>

        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-lg-12">
                        <h2>PATIENTS (NOTIFICATIONS)</h2>
                    </div>
                </div>
                <hr />

                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">Patient Notifications</div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover" id="patientsTable">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Patient Name</th>
                                                <th>Email</th>
                                                <th>Contact</th>
                                                <th>Doctor</th>
                                                <th>Token No</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($patients) > 0): ?>
                                                <?php foreach ($patients as $index => $patient): ?>
                                                    <tr>
                                                        <td><?php echo $index + 1; ?></td>
                                                        <td><?php echo htmlspecialchars($patient['user_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($patient['user_email']); ?></td>
                                                        <td><?php echo htmlspecialchars($patient['user_contact']); ?></td>
                                                        <td><?php echo htmlspecialchars($patient['doctor_assigned']); ?></td>
                                                        <td><?php echo htmlspecialchars($patient['token_no']); ?></td>
                                                        <td>
															<a href="?delete=<?php echo $patient['id']; ?>" 
															   class="btn btn-danger btn-sm" 
															   onclick="return confirm('Are you sure you want to delete this notification?');">Delete</a>
														</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="9" class="text-center">No patient notifications found.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Optional Save Button -->
                                <button class="btn btn-primary" onclick="location.reload()">Refresh</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <strong>Tip:</strong> Use the delete option to remove a notification.
                </div>

            </div>
        </div>
    </div>

    <div class="footer">
        <div class="row">
            <div class="col-lg-12">
                &copy; 2025 hospitalmanagement.com | Design by: <a href="http://binarytheme.com" style="color:#fff;" target="_blank">BinaryTheme</a>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>
