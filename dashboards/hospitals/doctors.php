<?php
session_start();
require '../../database.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get the hospital_id for the logged-in user
$stmt = $conn->prepare("SELECT * FROM hospitals WHERE user_id = ?");
$stmt->execute(array($user_id));
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hospital) {
    die("Hospital not found for this user.");
}

$hospital_id = $hospital['id'];
$hospital_name = $hospital['hospital_name'];
$message = "";

// Handle doctor delete action
if (isset($_GET['delete'])) {
    $doctor_id = $_GET['delete'];

    // Make sure $hospital_id is already defined earlier in your code
    $delete_stmt = $conn->prepare("DELETE FROM doctors WHERE id = ? AND hospital_id = ?");
    
    if ($delete_stmt->execute(array($doctor_id, $hospital_id))) {
        $message = "Doctor deleted successfully.";
    } else {
        $message = "Failed to delete doctor.";
    }
}


// Fetch all doctors for this hospital
$doctors_stmt = $conn->prepare("SELECT * FROM doctors WHERE hospital_id = ?");
$doctors_stmt->execute(array($hospital_id));
$doctors = $doctors_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Doctors List - Hospital Management Dashboard</title>
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
                <li class="active-link"><a href="#"><i class="fa fa-user-md"></i> View Doctors</a></li>
                <li><a href="regdoctor.php"><i class="fa fa-user-md"></i> Add Doctors</a></li>
                <li><a href="patients.php"><i class="fa fa-users"></i> Patients</a></li>
                <li><a href="appointments.php"><i class="fa fa-calendar"></i> Appointments</a></li>
                <li><a href="settings.php"><i class="fa fa-cogs"></i> Settings</a></li>
            </ul>
        </div>
    </nav>

    <div id="page-wrapper">
        <div id="page-inner">
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-12">
                    <h2>DOCTORS LIST</h2>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">All Registered Doctors</div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Profile Picture</th>
                                            <th>Name</th>
                                            <th>Specialization</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($doctors) > 0): ?>
                                            <?php foreach ($doctors as $index => $doctor): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <img src="<?php echo htmlspecialchars($doctor['profile_picture']); ?>" alt="Doctor Pic" width="50" height="50" class="img-thumbnail">
                                                    </td>
                                                    <td><?php echo htmlspecialchars($doctor['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                                                    <td><?php echo htmlspecialchars($doctor['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($doctor['phone']); ?></td>
                                                    <td>
                                                        <a href="?delete=<?php echo $doctor['id']; ?>" onclick="return confirm('Are you sure you want to delete this doctor?');" class="btn btn-danger btn-sm">Delete</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No doctors found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <strong>Tip:</strong> Use the delete button to remove a doctor from your hospital list.
            </div>

        </div>
    </div>

    <div class="footer">
        <div class="row">
            <div class="col-lg-12">
                &copy; 2025 hospitalmanagement.com | Design by: <a href="http://binarytheme.com" target="_blank">BinaryTheme</a>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/jquery-1.10.2.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>
