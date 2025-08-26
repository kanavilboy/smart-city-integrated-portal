<?php
session_start();
require '../../database.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Fetch admin details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND account_type = 'admin'");
$stmt->execute(array($admin_id));
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    die("Admin not found or invalid permissions.");
}

$admin_name = $admin['email'];
$admin_role = $admin['account_type'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_type = $_POST['account_type'];
    
    try {
        if ($account_type === 'hospital') {
            // Handle hospital form submission
            $stmt = $conn->prepare("INSERT INTO listed_hospitals (
                hospital_name, address, contact_number, email, 
                profile_picture, description
            ) VALUES (?, ?, ?, ?, ?, ?)");
            
            $stmt->execute(array(
                $_POST['hospital_name'],
                $_POST['address'],
                $_POST['contact_number'],
                $_POST['email'],
                '', // You'll need to handle file upload separately
                $_POST['description']
            ));
            
            $message = "Hospital added successfully!";
            
        } elseif ($account_type === 'institution') {
            // Handle institution form submission
            $stmt = $conn->prepare("INSERT INTO listed_institutions (
                institution_name, contact_email, phone, alternate_phone, 
                address, city, state, country, postal_code, 
                website, established_year, logo_path, description, 
                institution_category, institution_level
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute(array(
                $_POST['institution_name'],
                $_POST['contact_email'],
                $_POST['phone'],
                $_POST['alternate_phone'],
                $_POST['address'],
                $_POST['city'],
                $_POST['state'],
                $_POST['country'],
                $_POST['postal_code'],
                $_POST['website'],
                $_POST['established_year'],
                '', // You'll need to handle file upload separately
                $_POST['description'],
                $_POST['institution_category'],
                $_POST['institution_level']
            ));
            
            $message = "Institution added successfully!";
            
        } elseif ($account_type === 'merchant') {
            // Handle merchant form submission
            $stmt = $conn->prepare("INSERT INTO listed_merchants (
                merchant_name, merchant_type, address, 
                contact, email, description, profile_image
            ) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute(array(
                $_POST['merchant_name'],
                $_POST['merchant_type'],
                $_POST['address'],
                $_POST['contact'],
                $_POST['email'],
                $_POST['description'],
                '' // You'll need to handle file upload separately
            ));
            
            $message = "Merchant added successfully!";
        }
        
        // Log the activity
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, timestamp) VALUES (?, ?, NOW())");
        $stmt->execute(array($admin_id, "added new $account_type profile"));
        
        $success = true;
    } catch (PDOException $e) {
        $error = "Failed to add profile: " . $e->getMessage();
    }
}

// Set default account type
$account_type = isset($_GET['type']) ? $_GET['type'] : 'hospital';
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Smart City Admin - Add Profiles</title>
    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES-->
    <link href="assets/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='https://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .account-badge {
            font-size: 0.8em;
            padding: 3px 6px;
            border-radius: 3px;
        }
        .badge-merchant { background-color: #5cb85c; }
        .badge-institution { background-color: #f0ad4e; }
        .badge-hospital { background-color: #d9534f; }
        .filter-buttons .btn {
            margin-bottom: 10px;
            margin-right: 5px;
        }
        .form-section {
            margin-top: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
    </style>
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
                        <i class="fa fa-city"></i> Smart City Portal
                    </a>
                </div>
                <span class="logout-spn">
                    <span class="admin-info"><?php echo htmlspecialchars($admin_name); ?></span>
                    <a href="../../login.php" style="color:#fff;" onclick="return confirm('Are you sure you want to logout?')">
                        <i class="fa fa-sign-out"></i> LOGOUT
                    </a>
                </span>
            </div>
        </div>
        <!-- /. NAV TOP  -->
        <nav class="navbar-default navbar-side" role="navigation">
            <div class="sidebar-collapse">
                <ul class="nav" id="main-menu">
                    <li>
                        <a href="admin_dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a>
                    </li>
                    <li>
                        <a href="usermanagement.php"><i class="fa fa-user"></i> User Management</a>
                    </li>
                    <li>
                        <a href="registered_accounts.php"><i class="fa fa-users"></i> Account Types</a>
                    </li>
                    <li>
                        <a href="activity_logs.php"><i class="fa fa-history"></i> Activity Logs</a>
                    </li>
                    <li class="active-link">
                        <a href="add_profiles.php"><i class="fa fa-user"></i> Add Profiles</a>
                    </li>
					<li>
                        <a href="view_profiles.php"><i class="fa fa-list"></i> view Profiles</a>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- /. NAV SIDE  -->
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        <h2>ADD PROFILES</h2>
                        <h5>Add new institution, hospital or merchant profiles</h5>
                    </div>
                </div>
                <!-- /. ROW  -->
                <hr />
                
                <?php if (isset($success) && $success): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php elseif (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-filter"></i> Select Profile Type
                            </div>
                            <div class="panel-body filter-buttons">
                                <a href="add_profiles.php?type=hospital" class="btn btn-<?php echo $account_type === 'hospital' ? 'primary' : 'default'; ?>">
                                    <i class="fa fa-hospital"></i> Hospital
                                </a>
                                <a href="add_profiles.php?type=institution" class="btn btn-<?php echo $account_type === 'institution' ? 'primary' : 'default'; ?>">
                                    <i class="fa fa-school"></i> Institution
                                </a>
                                <a href="add_profiles.php?type=merchant" class="btn btn-<?php echo $account_type === 'merchant' ? 'primary' : 'default'; ?>">
                                    <i class="fa fa-store"></i> Merchant
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-plus-circle"></i> Add New <?php echo ucfirst($account_type); ?> Profile
                            </div>
                            <div class="panel-body">
                                <form method="POST" action="add_profiles.php" enctype="multipart/form-data">
                                    <input type="hidden" name="account_type" value="<?php echo $account_type; ?>">
                                    
                                    <?php if ($account_type === 'hospital'): ?>
                                        <!-- Hospital Form -->
                                        <div class="form-group">
                                            <label class="required-field">Hospital Name</label>
                                            <input type="text" name="hospital_name" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Address</label>
                                            <textarea name="address" class="form-control" rows="3"></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="required-field">Contact Number</label>
                                                    <input type="text" name="contact_number" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Email</label>
                                                    <input type="email" name="email" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Profile Picture</label>
                                            <input type="file" name="profile_picture" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea name="description" class="form-control" rows="5"></textarea>
                                        </div>
                                        
                                    <?php elseif ($account_type === 'institution'): ?>
                                        <!-- Institution Form -->
                                        <div class="form-group">
                                            <label class="required-field">Institution Name</label>
                                            <input type="text" name="institution_name" class="form-control" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="required-field">Contact Email</label>
                                                    <input type="email" name="contact_email" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="required-field">Phone</label>
                                                    <input type="text" name="phone" class="form-control" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Alternate Phone</label>
                                                    <input type="text" name="alternate_phone" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Established Year</label>
                                                    <input type="number" name="established_year" min="1900" max="<?php echo date('Y'); ?>" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Address</label>
                                            <textarea name="address" class="form-control" rows="2"></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>City</label>
                                                    <input type="text" name="city" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>State</label>
                                                    <input type="text" name="state" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Country</label>
                                                    <input type="text" name="country" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Postal Code</label>
                                                    <input type="text" name="postal_code" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Website</label>
                                                    <input type="url" name="website" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="required-field">Institution Category</label>
                                                    <select name="institution_category" class="form-control" required>
                                                        <option value="">Select Category</option>
                                                        <option value="college">College</option>
                                                        <option value="school">School</option>
                                                        <option value="Other institutions">Other institutions</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="required-field">Institution Level</label>
                                                    <select name="institution_level" class="form-control" required>
                                                        <option value="">Select Level</option>
                                                        <option value="primary">Primary</option>
                                                        <option value="secondary">Secondary</option>
                                                        <option value="higher_secondary">Higher Secondary</option>
                                                        <option value="college">College</option>
                                                        <option value="university">University</option>
                                                        <option value="vocational">Vocational</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Logo</label>
                                            <input type="file" name="logo" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea name="description" class="form-control" rows="5"></textarea>
                                        </div>
                                        
                                    <?php elseif ($account_type === 'merchant'): ?>
                                        <!-- Merchant Form -->
                                        <div class="form-group">
                                            <label class="required-field">Merchant Name</label>
                                            <input type="text" name="merchant_name" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="required-field">Merchant Type</label>
                                            <select name="merchant_type" class="form-control" required>
                                                <option value="">Select Type</option>
                                                <option value="hotels">Hotels</option>
                                                <option value="restaurants">Restaurants</option>
                                                <option value="stores">Stores</option>
                                                <option value="petshop">Petshop</option>
                                                <option value="fashion">Fashion</option>
                                                <option value="electronics">Electronics</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Address</label>
                                            <textarea name="address" class="form-control" rows="3"></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="required-field">Contact</label>
                                                    <input type="text" name="contact" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Email</label>
                                                    <input type="email" name="email" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Profile Image</label>
                                            <input type="file" name="profile_image" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea name="description" class="form-control" rows="5"></textarea>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Save Profile
                                        </button>
                                        <button type="reset" class="btn btn-default">
                                            <i class="fa fa-undo"></i> Reset
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /. PAGE INNER  -->
        </div>
        <!-- /. PAGE WRAPPER  -->
    </div>
    <div class="footer">
        <div class="row">
            <div class="col-lg-12">
                &copy; <?php echo date('Y'); ?> Smart City Portal | User Management System
            </div>
        </div>
    </div>

    <!-- SCRIPTS -AT THE BOTOM TO REDUCE THE LOAD TIME-->
    <!-- JQUERY SCRIPTS -->
    <script src="assets/js/jquery-1.10.2.js"></script>
    <!-- BOOTSTRAP SCRIPTS -->
    <script src="assets/js/bootstrap.min.js"></script>
    <!-- CUSTOM SCRIPTS -->
    <script src="assets/js/custom.js"></script>
</body>
</html>