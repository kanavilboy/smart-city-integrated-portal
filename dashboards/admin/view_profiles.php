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

// Determine which profile type to display
$profile_type = isset($_GET['type']) ? $_GET['type'] : 'hospital';

// Function to get all profiles of a specific type
function getProfiles($conn, $type) {
    switch ($type) {
        case 'hospital':
            return $conn->query("SELECT * FROM listed_hospitals ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        case 'institution':
            return $conn->query("SELECT * FROM listed_institutions ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        case 'merchant':
            return $conn->query("SELECT * FROM listed_merchants ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        default:
            return array();
    }
}

$profiles = getProfiles($conn, $profile_type);

// Function to format timestamp
function formatTimestamp($timestamp) {
    return date('M d, Y', strtotime($timestamp));
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Smart City Admin - View Profiles</title>
    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES-->
    <link href="assets/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='https://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .profile-badge {
            font-size: 0.8em;
            padding: 3px 6px;
            border-radius: 3px;
        }
        .badge-hospital { background-color: #d9534f; }
        .badge-institution { background-color: #f0ad4e; }
        .badge-merchant { background-color: #5cb85c; }
        .filter-buttons .btn {
            margin-bottom: 10px;
            margin-right: 5px;
        }
        .profile-card {
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .profile-img {
            height: 150px;
            object-fit: cover;
            width: 100%;
        }
        .profile-details {
            padding: 15px;
        }
        .action-btns .btn {
            margin-right: 5px;
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
                    <li>
                        <a href="add_profiles.php"><i class="fa fa-user"></i> Add Profiles</a>
                    </li>
                    <li class="active-link">
                        <a href="view_profiles.php"><i class="fa fa-list"></i> View Profiles</a>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- /. NAV SIDE  -->
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        <h2>VIEW PROFILES</h2>
                        <h5>View and manage all registered profiles</h5>
                    </div>
                </div>
                <!-- /. ROW  -->
                <hr />
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-filter"></i> Filter Profiles
                                <span class="badge pull-right"><?php echo count($profiles); ?> profiles</span>
                            </div>
                            <div class="panel-body filter-buttons">
                                <a href="view_profiles.php?type=hospital" class="btn btn-<?php echo $profile_type === 'hospital' ? 'primary' : 'default'; ?>">
                                    <i class="fa fa-hospital"></i> Hospitals
                                </a>
                                <a href="view_profiles.php?type=institution" class="btn btn-<?php echo $profile_type === 'institution' ? 'primary' : 'default'; ?>">
                                    <i class="fa fa-school"></i> Institutions
                                </a>
                                <a href="view_profiles.php?type=merchant" class="btn btn-<?php echo $profile_type === 'merchant' ? 'primary' : 'default'; ?>">
                                    <i class="fa fa-store"></i> Merchants
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <?php if (empty($profiles)): ?>
                        <div class="col-md-12">
                            <div class="alert alert-info">No <?php echo $profile_type; ?> profiles found.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($profiles as $profile): ?>
                            <div class="col-md-4">
                                <div class="profile-card">
                                    <div class="panel panel-default">
                                        <div class="panel-heading">
                                            <?php echo htmlspecialchars(isset($profile[$profile_type . '_name']) ? $profile[$profile_type . '_name'] : $profile['merchant_name']); ?>
                                            <span class="profile-badge badge-<?php echo $profile_type; ?> pull-right">
                                                <?php echo ucfirst($profile_type); ?>
                                            </span>
                                        </div>
                                        <div class="panel-body">
                                            <?php if ($profile_type === 'hospital'): ?>
                                                <img src="<?php echo htmlspecialchars($profile['profile_picture'] ? 'uploads/hospitals/'.$profile['profile_picture'] : 'assets/img/hospital-default.jpg'); ?>" 
                                                     class="profile-img" alt="Hospital Image">
                                                <div class="profile-details">
                                                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($profile['contact_number']); ?></p>
                                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?></p>
                                                    <p><strong>Address:</strong> <?php echo htmlspecialchars(substr($profile['address'], 0, 50)); ?>...</p>
                                                    <p><strong>Added:</strong> <?php echo formatTimestamp($profile['created_at']); ?></p>
                                                </div>
                                                
                                            <?php elseif ($profile_type === 'institution'): ?>
                                                <img src="<?php echo htmlspecialchars($profile['logo_path'] ? 'uploads/institutions/'.$profile['logo_path'] : 'assets/img/institution-default.jpg'); ?>" 
                                                     class="profile-img" alt="Institution Logo">
                                                <div class="profile-details">
                                                    <p><strong>Category:</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $profile['institution_category']))); ?></p>
                                                    <p><strong>Level:</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $profile['institution_level']))); ?></p>
                                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($profile['phone']); ?></p>
                                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($profile['city'].', '.$profile['state']); ?></p>
                                                    <p><strong>Added:</strong> <?php echo formatTimestamp($profile['created_at']); ?></p>
                                                </div>
                                                
                                            <?php elseif ($profile_type === 'merchant'): ?>
                                                <img src="<?php echo htmlspecialchars($profile['profile_image'] ? 'uploads/merchants/'.$profile['profile_image'] : 'assets/img/merchant-default.jpg'); ?>" 
                                                     class="profile-img" alt="Merchant Image">
                                                <div class="profile-details">
                                                    <p><strong>Type:</strong> <?php echo htmlspecialchars(ucfirst($profile['merchant_type'])); ?></p>
                                                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($profile['contact']); ?></p>
                                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?></p>
                                                    <p><strong>Added:</strong> <?php echo formatTimestamp($profile['created_at']); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="panel-footer action-btns">
                                            <a href="view_profile_detail.php?type=<?php echo $profile_type; ?>&id=<?php echo $profile['id']; ?>" 
                                               class="btn btn-info btn-xs">
                                                <i class="fa fa-eye"></i> View
                                            </a>
                                            <a href="edit_profile.php?type=<?php echo $profile_type; ?>&id=<?php echo $profile['id']; ?>" 
                                               class="btn btn-warning btn-xs">
                                                <i class="fa fa-edit"></i> Edit
                                            </a>
                                            <a href="delete_profile.php?type=<?php echo $profile_type; ?>&id=<?php echo $profile['id']; ?>" 
                                               class="btn btn-danger btn-xs" 
                                               onclick="return confirm('Are you sure you want to delete this profile?')">
                                                <i class="fa fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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