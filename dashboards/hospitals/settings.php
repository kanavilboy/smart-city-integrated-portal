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

if (!$hospital) {
    die("Hospital not found for this user.");
}

$hospital_id = $hospital['id'];
$hospital_name = $hospital['hospital_name'];
$message = "";

// Handle hospital details update
if (isset($_POST['save_details'])) {
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $description = $_POST['description'];

    $check = $conn->query("SELECT id FROM hospital_details WHERE hospital_id = $hospital_id");
    if ($check->rowCount() > 0) {
        $conn->query("UPDATE hospital_details SET 
                        address = '$address', 
                        contact_number = '$contact', 
                        email = '$email', 
                        description = '$description', 
                        updated_at = NOW()
                      WHERE hospital_id = $hospital_id");
    } else {
        $conn->query("INSERT INTO hospital_details (hospital_id, address, contact_number, email, description) 
                      VALUES ($hospital_id, '$address', '$contact', '$email', '$description')");
    }
    $message = "Hospital details updated successfully!";
}


// Handle profile picture upload
if (isset($_POST['upload_pic']) && isset($_FILES['profile_pic'])) {
    $target_dir = "../../uploads/hospitals/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    $target_file = $target_dir . basename($_FILES["profile_pic"]["name"]);
    if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
        $conn->query("UPDATE hospital_details SET profile_picture = '$target_file', updated_at = NOW() WHERE hospital_id = $hospital_id");
        $message = "Profile picture updated successfully!";
    } else {
        $message = "Failed to upload profile picture.";
    }
}

// Fetch hospital details for form
$stmt = $conn->query("SELECT * FROM hospital_details WHERE hospital_id = $hospital_id");
$hospital_details = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>Settings - Hospital Management Dashboard</title>
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
                    <h2>HOSPITAL SETTINGS</h2>
                </div>
            </div>
            <hr />

            <div class="row">
                <!-- Hospital Details Form -->
                <div class="col-lg-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">Update Hospital Details</div>
                        <div class="panel-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label>Hospital Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($hospital['hospital_name']); ?>" readonly />
                                </div>
                                <div class="form-group">
                                    <label>Address</label>
                                    <textarea name="address" class="form-control" required><?php echo isset($hospital_details['address']) ? htmlspecialchars($hospital_details['address']) : ''; ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Contact Number</label>
                                    <input type="text" name="contact" class="form-control" value="<?php echo isset($hospital_details['contact_number']) ? htmlspecialchars($hospital_details['contact_number']) : ''; ?>" required />
                                </div>
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo isset($hospital_details['email']) ? htmlspecialchars($hospital_details['email']) : ''; ?>" required />
                                </div>
								<div class="form-group">
									<label>Description</label>
									<textarea name="description" class="form-control" required><?php echo isset($hospital_details['description']) ? htmlspecialchars($hospital_details['description']) : ''; ?></textarea>
								</div>
                                <button type="submit" name="save_details" class="btn btn-primary">Save Details</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Profile Picture Upload -->
                <div class="col-lg-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">Change Profile Picture</div>
                        <div class="panel-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label>Upload New Profile Picture</label>
                                    <input type="file" name="profile_pic" class="form-control" accept="image/*" required />
                                </div>
                                <button type="submit" name="upload_pic" class="btn btn-primary">Upload</button>
                            </form>
                            <hr>
                            <p><strong>Current Profile Picture:</strong></p>
                            <img src="<?php echo isset($hospital_details['profile_picture']) ? $hospital_details['profile_picture'] : 'assets/img/hospital-logo.png'; ?>" alt="Profile Picture" width="150px" class="img-thumbnail" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <strong>Note:</strong> Updating your hospital details and profile picture helps keep your information current.
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
