<?php
session_start();
require 'database.php';

// Handle user registration
if (isset($_POST['register'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = md5($_POST['password']);  // Hash password
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $account_type = $_POST['accountType'];

    // Insert into users table
    $stmt = $conn->prepare("INSERT INTO users (email, password_hash, account_type) VALUES (?, ?, ?)");
    $stmt->execute(array($email, $password, $account_type));

    $user_id = $conn->lastInsertId(); // Get the new user's ID

    // Insert into respective tables based on account type
    switch ($account_type) {
		case "Personal":
            $stmt = $conn->prepare("INSERT INTO personal_users (user_id, full_name, email, phone, address) VALUES (?, ?, ?, ?, ?)");
			$stmt->execute(array($user_id, $full_name, $email, $phone, $address));
            break;
        case "Merchant":
            $category = $_POST['merchantCategory'];
            $business_name = $_POST['business_name'];
            $stmt = $conn->prepare("INSERT INTO merchants (user_id, merchant_type, name, email) VALUES (?, ?, ?, ?)");
            $stmt->execute(array($user_id, $category, $business_name, $email));
            break;

        case "Hospital":
            $hospital_name = $_POST['hospital_name'];
            $stmt = $conn->prepare("INSERT INTO hospitals (user_id, hospital_name) VALUES (?, ?)");
            $stmt->execute(array($user_id, $hospital_name));
            break;
			
        case "Institution":
            $institution_name = $_POST['institution_name'];	
			$institution_type = $_POST['institution_type'];
            $stmt = $conn->prepare("INSERT INTO institutions (user_id, institution_name, type) VALUES (?, ?, ?)");
            $stmt->execute(array($user_id, $institution_name, $institution_type));
            break;

        case "Gas":
            $dealer_name = $_POST['dealer_name'];
            $stmt = $conn->prepare("INSERT INTO gas_agencies (user_id, dealer_name) VALUES (?, ?)");
            $stmt->execute(array($user_id, $dealer_name));
            break;
    }

    // Insert into activity log
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
    $stmt->execute(array($user_id, 'Registered an account'));

    header("Location: index.php");
    exit();
}

// Handle user login
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = md5($_POST['password']); // MD5 hash

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND password_hash = ?");
    $stmt->execute(array($email, $password));

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['account_type'] = $user['account_type'];

        // Log activity
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->execute(array($user['id'], 'Logged in'));

        // Redirect based on user type
        switch ($user['account_type']) {
            case 'Hospital':
                header("Location: dashboards/hospitals/hospital_dashboard.php");
                break;
            case 'Merchant':
                header("Location: dashboards/merchants/merchant_dashboard.php");
                break;
            case 'KSEB':
                header("Location: dashboards/kseb/kseb_dashboard.php");
                break;
            case 'Water Authority':
                header("Location: dashboards/water_dashboard.php");
                break;
            case 'Gas':
                header("Location: dashboards/gas/gas_dashboard.php");
                break;
            case 'Institution':
                header("Location: dashboards/institution/institution_dashboard.php");
                break;
			case 'admin':
                header("Location: dashboards/admin/admin_dashboard.php");
                break;
			case 'personal':
                header("Location: smartcity.php");
                break;
            default:
                header("Location: smartcity.php"); // home page
                break;
        }
        exit();
    } else {
        echo "Invalid email or password!";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login & Signup Form</title>
  <link rel="stylesheet" href="styles.css">
  <script>
    function toggleAccountType() {
      document.getElementById('merchantOptions').style.display = document.getElementById('accountType').value === 'Merchant' ? 'block' : 'none';
      document.getElementById('hospitalOptions').style.display = document.getElementById('accountType').value === 'Hospital' ? 'block' : 'none';
      document.getElementById('educationOptions').style.display = document.getElementById('accountType').value === 'Institution' ? 'block' : 'none';
      document.getElementById('gasOptions').style.display = document.getElementById('accountType').value === 'Gas' ? 'block' : 'none';
      document.getElementById('personalOptions').style.display = document.getElementById('accountType').value === 'Personal' ? 'block' : 'none';
    }
</script>
  <style>
    /* Import Google font - Poppins */
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      min-height: 100vh;
      width: 100%;
      background: #009579;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .container {
      max-width: 450px;
      width: 100%;
      background: #fff;
      border-radius: 7px;
      box-shadow: 0 5px 10px rgba(0, 0, 0, 0.3);
      padding: 2rem;
    }

    .registration {
      display: none;
    }

    #check:checked ~ .registration {
      display: block;
    }

    #check:checked ~ .login {
      display: none;
    }

    #check {
      display: none;
    }

    .form header {
      font-size: 1.8rem;
      font-weight: 500;
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .form input, .form select {
      height: 50px;
      width: 100%;
      padding: 10px;
      font-size: 16px;
      margin-bottom: 1rem;
      border: 1px solid #ddd;
      border-radius: 6px;
      outline: none;
    }

    .form input:focus, .form select:focus {
      box-shadow: 0 1px 0 rgba(0, 0, 0, 0.2);
    }

    .form a {
      font-size: 16px;
      color: #009579;
      text-decoration: none;
    }

    .form a:hover {
      text-decoration: underline;
    }

    .form input.button {
      color: #fff;
      background: #009579;
      font-size: 1.2rem;
      font-weight: 500;
      letter-spacing: 1px;
      margin-top: 1rem;
      cursor: pointer;
      transition: 0.3s;
      border: none;
    }

    .form input.button:hover {
      background: #006653;
    }

    .signup {
      font-size: 16px;
      text-align: center;
      margin-top: 1rem;
    }

    .signup label {
      color: #009579;
      cursor: pointer;
    }

    .signup label:hover {
      text-decoration: underline;
    }

    .hidden {
      display: none;
    }
  </style>
</head>
<body>
  <div class="container">
    <input type="checkbox" id="check">
    
    <div class="login form">
      <header>Login</header>
      <form action="" method="POST">
        <input type="email" name="email" placeholder="Enter your email" required>
        <input type="password" name="password" placeholder="Enter your password" required>
        <input type="submit" name="login" class="button" value="Login">
      </form>
      <div class="signup">
        <span>Don't have an account? <label for="check">Signup</label></span>
      </div>
    </div>
    
    <div class="registration form">
		<header>Signup</header>
		<form action="" method="POST">
			<label>Choose Account Type</label>
			<select name="accountType" id="accountType" onchange="toggleAccountType()" required>
				<option value="">--Select--</option>
				<option value="Personal">Personal Account</option>
				<option value="Merchant">Merchant</option>
				<option value="Hospital">Hospital Management</option>
				<option value="Institution">Education</option>
				<option value="Gas">Gas Management</option>
			</select>

			<input type="email" name="email" placeholder="Enter your email" required>
			<input type="password" name="password" placeholder="Create a password" required>
			<input type="password" name="confirm_password" placeholder="Confirm your password" required>

			<div id="personalOptions" class="hidden">
				<input type="text" name="full_name" placeholder="Full Name">
				<input type="text" name="phone" placeholder="Phone Number">
				<input type="text" name="address" placeholder="Address">
			</div>

			<div id="merchantOptions" class="hidden">
				<select name="merchantCategory">
					<option value="Hotels">Hotels</option>
					<option value="Restaurants">Restaurants</option>
					<option value="Stores">Stores</option>
					<option value="PetShop">Pet Shop</option>
					<option value="Fashion">Fashion</option>
					<option value="Electronics">Electronics</option>
				</select>
				<input type="text" name="business_name" placeholder="Business Name">
			</div>
			
			<div id="hospitalOptions" class="hidden">
				<input type="text" name="hospital_name" placeholder="Hospital Name">
				<!-- Add other hospital-specific fields if needed -->
			</div>
			
			<div id="educationOptions" class="hidden">
				<label>Institution Type</label>
				<select name="institution_type">
					<option value="school">School</option>
					<option value="College">College</option>
					<option value="Other Institution">Other Institution</option>
				</select>
				<label>Institution name</label>
				<input type="text" name="institution_name" placeholder="Institution Name">
			</div>

			<div id="gasOptions" class="hidden">
				<label>Dealer name</label>
				<input type="text" name="dealer_name" placeholder="dealer Name">
			</div>

			<input type="submit" name="register" class="button" value="Signup">
		</form>

		<div class="signup">
			<span>Already have an account? <label for="check">Login</label></span>
		</div>
	</div>
  </div>
</body>
</html>
