<?php
// Database connection
session_start();
require '../../database.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$log_id = $_SESSION['user_id'];
try {
    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT id FROM personal_users WHERE user_id = ?");
    $stmt->execute(array($log_id));

    // Fetch the result
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
	
    if ($user) {
        $user_id = $user['id'];
		
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}


$hospital_id = isset($_GET['hospital_id']) ? intval($_GET['hospital_id']) : 0;
$hospital_name = "Hospital Not Found";

// Fetch hospital details
if ($hospital_id > 0) {
    $sql = "SELECT * FROM hospitals WHERE id = :hospital_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':hospital_id', $hospital_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $hospital = $stmt->fetch(PDO::FETCH_ASSOC);
        $hospital_name = htmlspecialchars($hospital['hospital_name']);
    } else {
        $hospital_name = "Hospital Not Found";
    }
}

if ($hospital_id > 0) {
    $sql = "SELECT description FROM hospital_details WHERE hospital_id = :hospital_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':hospital_id', $hospital_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $hospital_description = htmlspecialchars($row['description']);
    } else {
        $hospital_description = "Description not available.";
    }
}

// Fetch doctors data into $doctors array
$doctors = array();
$sql = "SELECT * FROM doctors WHERE hospital_id = :hospital_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':hospital_id', $hospital_id, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// submit appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data
    $hospital_id = htmlspecialchars($_POST['hospital_id']);
    $fullname = htmlspecialchars($_POST['fullname']);
    $email = htmlspecialchars($_POST['email']);
	$contact = htmlspecialchars($_POST['contact']);
    $date = htmlspecialchars($_POST['date']);
    $specialization = htmlspecialchars($_POST['specialization']);
    $message = htmlspecialchars($_POST['message']);
	
    try {
        // Prepare SQL query to insert appointment
        $sql = "INSERT INTO appointment (hospital_id, user_id, fullname, email, contact, appointment_date, specialization, message)
				VALUES (:hospital_id, :user_id, :fullname, :email, :contact, :appointment_date, :specialization, :message)";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':hospital_id', $hospital_id);
		$stmt->bindParam(':user_id', $user_id);
		$stmt->bindParam(':fullname', $fullname);
		$stmt->bindParam(':email', $email);
		$stmt->bindParam(':contact', $contact);
		$stmt->bindParam(':appointment_date', $date);
		$stmt->bindParam(':specialization', $specialization);
		$stmt->bindParam(':message', $message_text);

        if ($stmt->execute()) {
            $message = "Appointment booked successfully!";
        } else {
            $message = "Failed to book appointment.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} 

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $hospital_name; ?> - Health Services</title>

  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
</head>
<body>

<!-- Back to top button -->
<div class="back-to-top"></div>

<header>
  <nav class="navbar navbar-expand-lg navbar-light shadow-sm">
    <div class="container">
      <a class="navbar-brand" href="../../smartcity.php"><span class="text-primary">Smart</span>-City</a>
      <form action="#">
        <div class="input-group input-navbar">
          <div class="input-group-prepend">
            <span class="input-group-text" id="icon-addon1"><span class="mai-search"></span></span>
          </div>
          <input type="text" class="form-control" placeholder="Enter keyword.." aria-label="Search">
        </div>
      </form>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupport">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupport">
        <ul class="navbar-nav ml-auto">
          <li class="nav-item"><a class="nav-link" href="health.php">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
          <li class="nav-item active"><a class="nav-link" href="hospitals.php">Hospital</a></li>
          <li class="nav-item"><a class="nav-link" href="doctors.php">Doctors</a></li>
          <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
        </ul>
      </div>
    </div>
  </nav>
</header>

<div class="page-banner overlay-dark bg-image" style="<?php echo !empty($hospital['profile_picture']) ? $hospital['profile_picture'] : '../assets/img/blog/blog_1.jpg'; ?>">
  <div class="banner-section">
    <div class="container text-center wow fadeInUp">
      <h1 class="font-weight-normal"><?php echo $hospital_name; ?></h1>
    </div>
  </div>
</div>

<div class="page-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 wow fadeInUp">
        <h1 class="text-center mb-3">Welcome to <?php echo $hospital_name; ?></h1>
        <div class="text-lg">
          <p><?php echo $hospital_description; ?></p>
        </div>
      </div>

      <div class="col-lg-10 mt-5">
    <h1 class="text-center mb-5 wow fadeInUp">Our Doctors</h1>
    <div class="row justify-content-center">

        <?php if (!empty($doctors)): ?>
            <?php foreach ($doctors as $doctor): 
                $doctor_name = htmlspecialchars($doctor['name']);
                $specialty = htmlspecialchars($doctor['specialization']);
                $profile_picture = !empty($doctor['profile_picture']) ? $doctor['profile_picture'] : '../assets/img/doctors/default.jpg';
                $phone = htmlspecialchars($doctor['phone']);
            ?>
                <div class="col-md-6 col-lg-4 wow zoomIn">
                    <div class="card-doctor">
                        <div class="header">
                            <img src="<?php echo $profile_picture; ?>" alt="Doctor Image">
                            <div class="meta">
                                <a href="tel:<?php echo $phone; ?>"><span class="mai-call"></span></a>
                                <a href="https://wa.me/<?php echo $phone; ?>" target="_blank"><span class="mai-logo-whatsapp"></span></a>
                            </div>
                        </div>
                        <div class="body">
                            <p class="text-xl mb-0"><?php echo $doctor_name; ?></p>
                            <span class="text-sm text-grey"><?php echo $specialty; ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center">No doctors available for this hospital.</p>
        <?php endif; ?> 

    </div>
</div>


    </div>
  </div>
</div>

<!-- Appointment Form -->
<div class="page-section">
  <div class="container">
    <h1 class="text-center wow fadeInUp">Make an Appointment</h1>
	<?php if (!empty($message)) : ?>
		<script>
			alert('<?php echo $message; ?>');
		</script>
	<?php endif; ?>

    <form class="main-form" method="POST" action="">
      <input type="hidden" name="hospital_id" value="<?php echo $hospital_id; ?>">
      <div class="row mt-5">
        <div class="col-12 col-sm-6 py-2 wow fadeInLeft">
          <input type="text" name="fullname" class="form-control" placeholder="Full name" required>
        </div>
        <div class="col-12 col-sm-6 py-2 wow fadeInRight">
          <input type="email" name="email" class="form-control" placeholder="Email address.." required>
        </div>
		<div class="col-12 col-sm-6 py-2 wow fadeInLeft">
		  <input type="text" name="contact" class="form-control" placeholder="Contact Number" required>
		</div>
        <div class="col-12 col-sm-6 py-2 wow fadeInLeft" data-wow-delay="300ms">
          <input type="date" name="date" class="form-control" required>
        </div>
        <div class="col-12 col-sm-6 py-2 wow fadeInRight" data-wow-delay="300ms">
          <select name="specialization" class="custom-select">
            <option value="general">General Health</option>
            <option value="cardiology">Cardiology</option>
            <option value="dental">Dental</option>
            <option value="neurology">Neurology</option>
            <option value="orthopaedics">Orthopaedics</option>
          </select>
        </div>
        <div class="col-12 py-2 wow fadeInUp" data-wow-delay="300ms">
          <textarea name="message" class="form-control" rows="6" placeholder="Enter message.."></textarea>
        </div>
      </div>
      <button type="submit" class="btn btn-primary mt-3 wow zoomIn">Submit Request</button>
    </form>
  </div>
</div>

<footer class="page-footer">
    <div class="container">
      <div class="row px-md-3">
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>Company</h5>
          <ul class="footer-menu">
            <li><a href="#">About Us</a></li>
            <li><a href="#">Career</a></li>
            <li><a href="#">Editorial Team</a></li>
            <li><a href="#">Protection</a></li>
          </ul>
        </div>
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>More</h5>
          <ul class="footer-menu">
            <li><a href="#">Terms & Condition</a></li>
            <li><a href="#">Privacy</a></li>
            <li><a href="#">Advertise</a></li>
            <li><a href="#">Join us</a></li>
          </ul>
        </div>
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>Our services</h5>
          <ul class="footer-menu">
            <li><a href="#">Marketplace</a></li>
            <li><a href="#">Health services</a></li>
            <li><a href="#">Public services</a></li>
          </ul>
        </div>
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>Contact</h5>
          <p class="footer-link mt-2">JK</p>
          <a href="#" class="footer-link">jk@gmail.com</a>

          <h5 class="mt-3">Social Media</h5>
          <div class="footer-sosmed mt-3">
            <a href="#" target="_blank"><span class="mai-logo-facebook-f"></span></a>
            <a href="#" target="_blank"><span class="mai-logo-twitter"></span></a>
            <a href="#" target="_blank"><span class="mai-logo-google-plus-g"></span></a>
            <a href="#" target="_blank"><span class="mai-logo-instagram"></span></a>
            <a href="#" target="_blank"><span class="mai-logo-linkedin"></span></a>
          </div>
        </div>
      </div>

      <hr>

      <p id="copyright">Copyright &copy; 2025 <a href="" target="_blank">JK</a>. All right reserved</p>
    </div> <!-- .container -->
  </footer> <!-- .page-footer -->

<script src="../assets/js/jquery-3.5.1.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/vendor/owl-carousel/js/owl.carousel.min.js"></script>
<script src="../assets/vendor/wow/wow.min.js"></script>
<script src="../assets/js/theme.js"></script>

</body>
</html>