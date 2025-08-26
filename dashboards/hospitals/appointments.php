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

// Handle appointment delete action
if (isset($_GET['delete'])) {
    $appointment_id = $_GET['delete'];

    $delete_stmt = $conn->prepare("DELETE FROM appointment WHERE id = ? AND hospital_id = ?");
    if ($delete_stmt->execute(array($appointment_id, $hospital_id))) {
        $message = "Appointment deleted successfully.";
    } else {
        $message = "Failed to delete appointment.";
    }
}

// Fetch all appointments for this hospital
$appointments_stmt = $conn->prepare("SELECT * FROM appointment WHERE hospital_id = ? ORDER BY appointment_date ASC");
$appointments_stmt->execute(array($hospital_id));
$appointments = $appointments_stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['confirm_appointment'])) {
    $appointment_id = $_POST['appointment_id'];

    // Fetch appointment details
    $appt_stmt = $conn->prepare("SELECT * FROM appointment WHERE id = ? AND hospital_id = ?");
    $appt_stmt->execute(array($appointment_id, $hospital_id));
    $appointment = $appt_stmt->fetch(PDO::FETCH_ASSOC);

    if ($appointment) {
        $customer_id = $appointment['user_id'];
		$user_name = $appointment['fullname'];
        $user_email = $appointment['email'];
        $user_contact = $appointment['contact'];
        $doctor_assigned = $appointment['doctor_associated'];
        $token_no = $appointment['token_no'];

        $message = "Dear $user_name, your appointment is confirmed with Dr. $doctor_assigned. 
                    Your Token Number is $token_no. Please arrive on your appointment date: " . $appointment['appointment_date'];

        // Insert into hospital_notification
        $insert_stmt = $conn->prepare("INSERT INTO patient (hospital_id, customer_id, user_name, user_email, user_contact, doctor_assigned, token_no)
                                       VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insert_stmt->execute(array($hospital_id, $customer_id, $user_name, $user_email, $user_contact, $doctor_assigned, $token_no));
		
		$insert_stmt = $conn->prepare("INSERT INTO message (customer_id, sender, message) 
									  VALUES (?, ?, ?)");
		$insert_stmt->execute(array($customer_id, $hospital_name, $message));


        $message = "Notification sent successfully.";
    } else {
        $message = "Appointment not found.";
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Appointments List - Hospital Management Dashboard</title>
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

            <div class="row">
                <div class="col-lg-12">
                    <h2>APPOINTMENTS LIST</h2>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">All Booked Appointments</div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Patient Name</th>
                                            <th>Contact</th>
                                            <th>Email</th>
                                            <th>Specialization</th>
                                            <th>Doctor</th>
                                            <th>Appointment Date</th>
                                            <th>Token No</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($appointments) > 0): ?>
                                            <?php foreach ($appointments as $index => $appointment): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($appointment['fullname']); ?></td>
                                                    <td><?php echo htmlspecialchars($appointment['contact']); ?></td>
                                                    <td><?php echo htmlspecialchars($appointment['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($appointment['specialization']); ?></td>
                                                    <td><?php echo !empty($appointment['doctor_associated']) ? htmlspecialchars($appointment['doctor_associated']) : 'Not Assigned'; ?></td>
                                                    <td><?php echo htmlspecialchars($appointment['appointment_date']); ?></td>
													<td><?php echo !empty($appointment['token_no']) ? htmlspecialchars($appointment['token_no']) : 'Not Assigned'; ?></td>
                                                    <td>
                                                        <a href="edit_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                                    </td>
													<td>
                                                        <a href="?delete=<?php echo $appointment['id']; ?>" onclick="return confirm('Are you sure you want to delete this appointment?');" class="btn btn-danger btn-sm">Delete</a>
                                                    </td>
													<td>
													<form method="POST" style="display:inline;">
														<input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
														<button type="submit" name="confirm_appointment" class="btn btn-success btn-sm">Confirm</button>
													</form>
													</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center">No Appointments Found.</td>
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
                <strong>Tip:</strong> Use the delete button to remove an appointment from your hospital records.
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
