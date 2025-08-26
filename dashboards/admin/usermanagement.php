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

// Handle user deletion
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    try {
        $conn->beginTransaction();
        
        // First delete from personal_users
        $stmt = $conn->prepare("DELETE FROM personal_users WHERE user_id = ?");
        $stmt->execute(array($user_id));
        
        // Then delete from users
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute(array($user_id));
        
        $conn->commit();
        
        // Log the activity
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, timestamp) VALUES (?, ?, NOW())");
        $stmt->execute(array($admin_id, "deleted user $user_id"));
        
        header("Location: usermanagement.php?success=User+deleted+successfully");
        exit();
    } catch (PDOException $e) {
        $conn->rollBack();
        header("Location: usermanagement.php?error=Failed+to+delete+user");
        exit();
    }
}

// Fetch all personal users with their details
$users = $conn->query("
    SELECT u.id, u.email, u.account_type, u.created_at, 
           p.full_name, p.phone, p.address
    FROM users u
    JOIN personal_users p ON u.id = p.user_id
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Smart City Admin - User Management</title>
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
        .badge-user { background-color: #5bc0de; }
        .badge-admin { background-color: #d9534f; }
        .badge-merchant { background-color: #5cb85c; }
        .badge-institution { background-color: #f0ad4e; }
        .badge-gas { background-color: #337ab7; }
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
                        <a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a>
                    </li>
                    <li class="active-link">
                        <a href="usermanagement.php"><i class="fa fa-user"></i> User Management</a>
                    </li>
                    <li>
                        <a href="registered_accounts.php"><i class="fa fa-users"></i> Account Types <span class="fa arrow"></span></a>
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
                        <h2>USER MANAGEMENT</h2>
                        <h5>Manage all personal users in the system</h5>
                    </div>
                </div>
                <!-- /. ROW  -->
                <hr />
                
                <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-users"></i> Personal Users
                                <div class="pull-right">
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Full Name</th>
                                                <th>Email</th>
                                                <th>Account Type</th>
                                                <th>Phone</th>
                                                <th>Address</th>
                                                <th>Registered</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): 
                                                $badgeClass = 'badge-' . str_replace(array('admin', 'merchant', 'institution', 'gas_agency'), 
                                                array('admin', 'merchant', 'institution', 'gas'), $user['account_type']);
                                            ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <span class="account-badge <?php echo $badgeClass; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $user['account_type'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($user['address']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                <td class="action-btns">
                                                    <a href="usermanagement.php?delete=<?php echo $user['id']; ?>" 
                                                       class="btn btn-danger btn-xs" 
                                                       onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
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