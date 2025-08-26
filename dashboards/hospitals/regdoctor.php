<?php
session_start();
require '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch hospital ID based on the logged-in user
$stmt = $conn->query("SELECT * FROM hospitals WHERE user_id = $user_id");
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hospital) {
    die("Hospital not found for this user.");
}

$hospital_id = $hospital['id'];
$hospital_name = $hospital['hospital_name'];
$message = "";

// Handle Doctor Registration
if (isset($_POST['register_doctor'])) {
    $name = htmlspecialchars($_POST['name']);
    $specialization = htmlspecialchars($_POST['specialization']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);
    $profile_picture = 'assets/img/doctor-default.png'; // Default

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['name'] != '') {
        $target_dir = "../../uploads/doctors/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $file_name = basename($_FILES["profile_picture"]["name"]);
        $target_file = $target_dir . time() . "_" . $file_name;

        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            $profile_picture = $target_file;
        }
    }

    // Insert doctor data
    $sql = "INSERT INTO doctors (hospital_id, name, specialization, email, phone, profile_picture) 
            VALUES (:hospital_id, :name, :specialization, :email, :phone, :profile_picture)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':hospital_id', $hospital_id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':specialization', $specialization);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':profile_picture', $profile_picture);

    if ($stmt->execute()) {
        $message = "Doctor registered successfully!";
    } else {
        $message = "Failed to register doctor. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>Register Doctor - Hospital Management Dashboard</title>
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
                    <h2>REGISTER NEW DOCTOR</h2>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-lg-8">
                    <div class="panel panel-default">
                        <div class="panel-heading">Doctor Information</div>
                        <div class="panel-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label>Doctor Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Specialization</label>
									<select name="specialization" class="form-control" required>
										<option value="general">General Health</option>
										<option value="cardiology">Cardiology</option>
										<option value="dental">Dental</option>
										<option value="neurology">Neurology</option>
										<option value="orthopaedics">Orthopaedics</option>
									</select>
                                </div>
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="text" name="phone" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Profile Picture (Optional)</label>
                                    <input type="file" name="profile_picture" class="form-control" accept="image/*">
                                </div>
                                <button type="submit" name="register_doctor" class="btn btn-primary">Register Doctor</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <strong>Tip:</strong> Fill all required fields and upload a doctor picture for better identification.
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
