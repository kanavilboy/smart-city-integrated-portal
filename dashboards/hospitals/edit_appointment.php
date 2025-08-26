<?php
session_start();
require '../../database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM hospitals WHERE user_id = ?");
$stmt->execute(array($user_id));
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hospital) {
    die("Hospital not found.");
}
$hospital_id = $hospital['id'];
$hospital_name = $hospital['hospital_name'];
$message = "";

if (isset($_GET['id'])) {
    $appointment_id = $_GET['id'];
    $appt_stmt = $conn->prepare("SELECT * FROM appointment WHERE id = ? AND hospital_id = ?");
    $appt_stmt->execute(array($appointment_id, $hospital_id));
    $appointment = $appt_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$appointment) {
        die("Appointment not found.");
    }
} else {
    die("Invalid Request.");
}

// Doctor Assignment Form submission
if (isset($_POST['assign_doctor'])) {
    $doctor_associated = isset($_POST['doctor_associated']) ? $_POST['doctor_associated'] : null;
    if ($doctor_associated) {
        $update_stmt = $conn->prepare("UPDATE appointment SET doctor_associated = ? WHERE id = ?");
        if ($update_stmt->execute(array($doctor_associated, $appointment_id))) {
            $message = "Doctor assigned successfully.";
            $appointment['doctor_associated'] = $doctor_associated; // Update local variable for display
        } else {
            $message = "Failed to assign doctor.";
        }
    } else {
        $message = "Please select a doctor.";
    }
}

// Token Generation Form submission
if (isset($_POST['generate_token'])) {
    if ($appointment['doctor_associated']) {
        $doctor = $appointment['doctor_associated'];
        $appointment_date = $appointment['appointment_date'];

        $stmt = $conn->prepare("SELECT MAX(token_no) AS max_token FROM appointment WHERE doctor_associated = ? AND appointment_date = ?");
		$stmt->execute(array($doctor, $appointment_date));
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		$max_token = isset($result['max_token']) ? $result['max_token'] : 0;
		$new_token = $max_token + 1;


        $update_stmt = $conn->prepare("UPDATE appointment SET token_no = ? WHERE id = ?");
        if ($update_stmt->execute(array($new_token, $appointment_id))) {
            $message = "Token generated successfully.";
            $appointment['token_no'] = $new_token; // Update local variable for display
        } else {
            $message = "Failed to generate token.";
        }
    } else {
        $message = "Assign a doctor before generating the token.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Edit Appointment - Hospital Management Dashboard</title>
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
                <li><a href="patients.php"><i class="fa fa-users"></i> Patients</a></li>
                <li><a href="appointments.php"><i class="fa fa-calendar"></i> Appointments</a></li>
                <li class="active-link"><a href="settings.php"><i class="fa fa-cogs"></i> Settings</a></li>
            </ul>
        </div>
    </nav>

    <div id="page-wrapper">
        <div id="page-inner">
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>

            <h2>Edit Appointment</h2>
			<div class="row">
			<div class="col-lg-6">
            <div class="panel panel-default">
                        <div class="panel-heading">Token Generation</div>
                        <div class="panel-body">

            <p><strong>Patient Name:</strong> <?php echo htmlspecialchars($appointment['fullname']); ?></p>
            <p><strong>Specialization:</strong> <?php echo htmlspecialchars($appointment['specialization']); ?></p>
            <p><strong>Appointment Date:</strong> <?php echo htmlspecialchars($appointment['appointment_date']); ?></p>

            <!-- Doctor Assignment Form -->
            <form method="POST">
                <div class="form-group">
                    <label>Assign Doctor:</label>
                    <select name="doctor_associated" class="form-control">
                        <option value="">Select Doctor</option>
                        <?php
                        $specialization = htmlspecialchars($appointment['specialization']);
                        $doctor_stmt = $conn->prepare("SELECT name FROM doctors WHERE hospital_id = ? AND specialization = ?");
                        $doctor_stmt->execute(array($hospital_id, $specialization));
                        $doctors = $doctor_stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($doctors as $doctor) {
                            $selected = ($appointment['doctor_associated'] == $doctor['name']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($doctor['name']) . "' $selected>" . htmlspecialchars($doctor['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" name="assign_doctor" class="btn btn-primary">Assign Doctor</button>
            </form>
        </div>
		</div>
		</div>
		<!-- Token Generation Form -->
				<div class="col-lg-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">Token Generation</div>
                        <div class="panel-body">
                            <form method="POST">
								<div class="form-group">
									<label>Token Number:</label>
									<input type="text" class="form-control" value="<?php echo htmlspecialchars($appointment['token_no']); ?>" readonly>
								</div>
								<button type="submit" name="generate_token" class="btn btn-success">Generate Token</button>
							</form>
                            <hr>
                            </div>
                    </div>
                </div>
			</div>
			<a href="appointments.php" class="btn btn-secondary">Back to Appointments</a>
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
