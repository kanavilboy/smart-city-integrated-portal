<?php
session_start();
require '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch hospital id based on logged-in user
$stmt = $conn->query("SELECT * FROM hospitals WHERE user_id = $user_id");
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);
$hospital_name = $hospital['hospital_name'];
$message = "Welcome $hospital_name";

if (!$hospital) {
    die("Hospital not found for this user.");
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Hospital Management Dashboard</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
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
                        <?php echo $hospital_name; ?>
                    </a>
                </div>
                <span class="logout-spn">
                    <a href="../../login.php" style="color:#fff;">LOGOUT</a>
                </span>
            </div>
        </div>

        <nav class="navbar-default navbar-side" role="navigation">
            <div class="sidebar-collapse">
                <ul class="nav" id="main-menu">
                    <li class="active-link">
                        <a href="#"><i class="fa fa-dashboard"></i> Dashboard</a>
                    </li>
                    <li>
                        <a href="doctors.php"><i class="fa fa-user-md"></i> View Doctors</a>
                    </li>
					<li><a href="regdoctor.php"><i class="fa fa-user-md"></i> Add Doctors</a></li>
                    <li>
                        <a href="patients.php"><i class="fa fa-users"></i> Patients</a>
                    </li>
                    <li>
                        <a href="appointments.php"><i class="fa fa-calendar"></i> Appointments</a>
                    </li>
                    <li>
                        <a href="settings.php"><i class="fa fa-cogs"></i> Settings</a>
                    </li>
                </ul>
            </div>
        </nav>

        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-lg-12">
                        <h2><?php echo $hospital_name; ?> DASHBOARD</h2>
                    </div>
                </div>
                <hr />

                <div class="row">
                    <div class="col-lg-12">
                        <div class="alert alert-info">
                            <strong><?php echo $message; ?></strong> All systems are running smoothly.
                        </div>
                    </div>
                </div>

                <div class="row text-center pad-top">
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-6">
                        <div class="div-square">
                            <a href="doctors.php">
                                <i class="fa fa-user-md fa-5x"></i>
                                <h4>View Doctors</h4>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-6">
                        <div class="div-square">
                            <a href="patients.php">
                                <i class="fa fa-users fa-5x"></i>
                                <h4>Manage Patients</h4>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-6">
                        <div class="div-square">
                            <a href="appointments.php">
                                <i class="fa fa-calendar fa-5x"></i>
                                <h4>Appointments</h4>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-6">
                        <div class="div-square">
                            <a href="settings.php">
                                <i class="fa fa-cogs fa-5x"></i>
                                <h4>Settings</h4>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row text-center pad-top">
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-6">
                        <div class="div-square">
                            <a href="regdoctor.php">
                                <i class="fa fa-user-md fa-5x"></i>
                                <h4>Add Doctors</h4>
                            </a>
                        </div>
                    </div>
                    
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