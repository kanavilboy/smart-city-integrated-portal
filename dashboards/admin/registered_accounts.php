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

// Determine which account type to display
$account_type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Base query for all non-admin, non-personal accounts
$query = "SELECT u.id, u.email, u.account_type, u.created_at FROM users u WHERE u.account_type != 'admin' AND u.account_type != 'personal'";

// Modify query based on selected type
if ($account_type !== 'all') {
    $query .= " AND u.account_type = :account_type";
}

// Add ordering
$query .= " ORDER BY u.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if ($account_type !== 'all') {
    $stmt->bindParam(':account_type', $account_type);
}
$stmt->execute();
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get additional details for each account type
function getAccountDetails($conn, $user_id, $account_type) {
    $details = array();
    
    switch ($account_type) {
        case 'gas':
            $stmt = $conn->prepare("SELECT dealer_name FROM gas_agencies WHERE user_id = ?");
            $stmt->execute(array($user_id));
            $details = $stmt->fetch(PDO::FETCH_ASSOC);
            break;
            
        case 'hospital':
            $stmt = $conn->prepare("SELECT hospital_name FROM hospitals WHERE user_id = ?");
            $stmt->execute(array($user_id));
            $details = $stmt->fetch(PDO::FETCH_ASSOC);
            break;
            
        case 'institution':
            $stmt = $conn->prepare("SELECT institution_name, type FROM institutions WHERE user_id = ?");
            $stmt->execute(array($user_id));
            $details = $stmt->fetch(PDO::FETCH_ASSOC);
            break;
            
        case 'merchant':
            $stmt = $conn->prepare("SELECT name, address, contact, merchant_type FROM merchants WHERE user_id = ?");
            $stmt->execute(array($user_id));
            $details = $stmt->fetch(PDO::FETCH_ASSOC);
            break;
    }
    
    return $details ?: array('name' => 'N/A');
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Smart City Admin - Registered Accounts</title>
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
        .badge-gas { background-color: #337ab7; }
        .badge-hospital { background-color: #d9534f; }
        .filter-buttons .btn {
            margin-bottom: 10px;
            margin-right: 5px;
        }
        .account-details {
            margin-top: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 4px;
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
                    <li class="active-link">
                        <a href="registered_accounts.php"><i class="fa fa-users"></i> Account Types</a>
                    </li>
                    <li>
                        <a href="activity_logs.php"><i class="fa fa-history"></i> Activity Logs</a>
                    </li>
                    <li>
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
                        <h2>REGISTERED ACCOUNTS</h2>
                        <h5>Manage different types of registered accounts</h5>
                    </div>
                </div>
                <!-- /. ROW  -->
                <hr />
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-filter"></i> Filter Accounts
                            </div>
                            <div class="panel-body filter-buttons">
                                <a href="registered_accounts.php?type=all" class="btn btn-<?php echo $account_type === 'all' ? 'primary' : 'default'; ?>">
                                    <i class="fa fa-list"></i> All Accounts
                                </a>
                                <a href="registered_accounts.php?type=gas_agency" class="btn btn-<?php echo $account_type === 'gas' ? 'primary' : 'default'; ?>">
                                    <i class="fa fa-fire"></i> Gas Agencies
                                </a>
                                <a href="registered_accounts.php?type=hospital" class="btn btn-<?php echo $account_type === 'hospital' ? 'primary' : 'default'; ?>">
                                    <i class="fa fa-hospital"></i> Hospitals
                                </a>
                                <a href="registered_accounts.php?type=institution" class="btn btn-<?php echo $account_type === 'institution' ? 'primary' : 'default'; ?>">
                                    <i class="fa fa-school"></i> Institutions
                                </a>
                                <a href="registered_accounts.php?type=merchant" class="btn btn-<?php echo $account_type === 'merchant' ? 'primary' : 'default'; ?>">
                                    <i class="fa fa-store"></i> Merchants
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-users"></i> <?php echo ucfirst(str_replace('_', ' ', $account_type)); ?> Accounts
                                <span class="badge pull-right"><?php echo count($accounts); ?></span>
                            </div>
                            <div class="panel-body">
                                <?php if (empty($accounts)): ?>
                                    <div class="alert alert-info">No accounts found for this category.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Email</th>
                                                    <th>Account Type</th>
                                                    <th>Details</th>
                                                    <th>Registered</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($accounts as $account): 
                                                    $details = getAccountDetails($conn, $account['id'], $account['account_type']);
                                                    $badgeClass = 'badge-' . str_replace(array('merchant', 'institution', 'gas', 'hospital'), 
                                                    array('merchant', 'institution', 'gas', 'hospital'), $account['account_type']);
                                                ?>
                                                <tr>
                                                    <td><?php echo $account['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($account['email']); ?></td>
                                                    <td>
                                                        <span class="account-badge <?php echo $badgeClass; ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $account['account_type'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="account-details">
															<?php if ($account['account_type'] === 'gas') { ?>
																<strong>Dealer Name:</strong> <?php echo htmlspecialchars(isset($details['dealer_name']) ? $details['dealer_name'] : 'N/A'); ?>
															<?php } elseif ($account['account_type'] === 'hospital') { ?>
																<strong>Hospital Name:</strong> <?php echo htmlspecialchars(isset($details['hospital_name']) ? $details['hospital_name'] : 'N/A'); ?>
															<?php } elseif ($account['account_type'] === 'institution') { ?>
																<strong>Institution:</strong> <?php echo htmlspecialchars(isset($details['institution_name']) ? $details['institution_name'] : 'N/A'); ?><br>
																<strong>Type:</strong> <?php echo htmlspecialchars(isset($details['type']) ? $details['type'] : 'N/A'); ?>
															<?php } elseif ($account['account_type'] === 'merchant') { ?>
																<strong>Name:</strong> <?php echo htmlspecialchars(isset($details['name']) ? $details['name'] : 'N/A'); ?><br>
																<strong>Type:</strong> <?php echo htmlspecialchars(isset($details['merchant_type']) ? $details['merchant_type'] : 'N/A'); ?><br>
																<strong>Contact:</strong> <?php echo htmlspecialchars(isset($details['contact']) ? $details['contact'] : 'N/A'); ?>
															<?php } ?>
														</div>

                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($account['created_at'])); ?></td>
                                                    <td>
                                                        <a href="view_account.php?type=<?php echo $account['account_type']; ?>&id=<?php echo $account['id']; ?>" class="btn btn-info btn-xs">
                                                            <i class="fa fa-eye"></i> View
                                                        </a>
                                                        <a href="edit_account.php?type=<?php echo $account['account_type']; ?>&id=<?php echo $account['id']; ?>" class="btn btn-warning btn-xs">
                                                            <i class="fa fa-edit"></i> Edit
                                                        </a>
                                                        <a href="delete_account.php?type=<?php echo $account['account_type']; ?>&id=<?php echo $account['id']; ?>" class="btn btn-danger btn-xs" onclick="return confirm('Are you sure you want to delete this account?')">
                                                            <i class="fa fa-trash"></i> Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
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