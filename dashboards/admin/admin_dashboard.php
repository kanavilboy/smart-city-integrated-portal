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

$admin_name = $admin['email']; // Using email as identifier
$admin_role = $admin['account_type'];
$message = "Welcome " . htmlspecialchars($admin_name) . " (" . ucfirst($admin_role) . ")";

// Get dashboard statistics
$stats = array(
    'total_users' => $conn->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'active_users' => $conn->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'today_logins' => $conn->query("SELECT COUNT(DISTINCT user_id) FROM activity_logs WHERE action = 'login' AND DATE(timestamp) = CURDATE()")->fetchColumn(),
    'recent_activities' => $conn->query("SELECT COUNT(*) FROM activity_logs WHERE DATE(timestamp) = CURDATE()")->fetchColumn(),
    'admins' => $conn->query("SELECT COUNT(*) FROM users WHERE account_type = 'admin'")->fetchColumn(),
    'merchants' => $conn->query("SELECT COUNT(*) FROM users WHERE account_type = 'merchant'")->fetchColumn(),
    'institutions' => $conn->query("SELECT COUNT(*) FROM users WHERE account_type = 'institution'")->fetchColumn(),
    'gas_agencies' => $conn->query("SELECT COUNT(*) FROM users WHERE account_type = 'gas_agency'")->fetchColumn()
);

// Get recent user activities
$recentActivities = $conn->query("
    SELECT a.id, a.action, a.timestamp, u.email, u.account_type 
    FROM activity_logs a
    JOIN users u ON a.user_id = u.id
    ORDER BY a.timestamp DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Get recent user registrations
$recentUsers = $conn->query("
    SELECT id, email, account_type, created_at 
    FROM users 
    ORDER BY created_at DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Smart City Admin Dashboard</title>
    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES-->
    <link href="assets/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='https://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .stat-card {
            transition: all 0.3s ease;
            min-height: 120px;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .entity-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
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
                    <li class="active-link">
                        <a href="admin_dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a>
                    </li>
                    <li>
                        <a href="usermanagement.php"><i class="fa fa-user"></i> User Management <span class="fa arrow"></span></a>
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
                        <h2>USER MANAGEMENT DASHBOARD</h2>
                        <h5><?php echo $message; ?></h5>
                    </div>
                </div>
                <!-- /. ROW  -->
                <hr />
                
                <div class="row">
                    <!-- User Statistics Cards -->
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-primary text-center no-boder stat-card">
                            <div class="panel-body">
                                <i class="fa fa-users entity-icon"></i>
                                <h3><?php echo $stats['total_users']; ?></h3>
                            </div>
                            <div class="panel-footer">Total Users</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-green text-center no-boder stat-card">
                            <div class="panel-body">
                                <i class="fa fa-user-check entity-icon"></i>
                                <h3><?php echo $stats['active_users']; ?></h3>
                            </div>
                            <div class="panel-footer">Active Users</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-yellow text-center no-boder stat-card">
                            <div class="panel-body">
                                <i class="fa fa-sign-in-alt entity-icon"></i>
                                <h3><?php echo $stats['today_logins']; ?></h3>
                            </div>
                            <div class="panel-footer">Today's Logins</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-red text-center no-boder stat-card">
                            <div class="panel-body">
                                <i class="fa fa-history entity-icon"></i>
                                <h3><?php echo $stats['recent_activities']; ?></h3>
                            </div>
                            <div class="panel-footer">Today's Activities</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Account Type Statistics -->
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-info text-center no-boder stat-card">
                            <div class="panel-body">
                                <i class="fa fa-user-shield entity-icon"></i>
                                <h3><?php echo $stats['admins']; ?></h3>
                            </div>
                            <div class="panel-footer">Administrators</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-warning text-center no-boder stat-card">
                            <div class="panel-body">
                                <i class="fa fa-store entity-icon"></i>
                                <h3><?php echo $stats['merchants']; ?></h3>
                            </div>
                            <div class="panel-footer">Merchants</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-success text-center no-boder stat-card">
                            <div class="panel-body">
                                <i class="fa fa-school entity-icon"></i>
                                <h3><?php echo $stats['institutions']; ?></h3>
                            </div>
                            <div class="panel-footer">Institutions</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-danger text-center no-boder stat-card">
                            <div class="panel-body">
                                <i class="fa fa-fire entity-icon"></i>
                                <h3><?php echo $stats['gas_agencies']; ?></h3>
                            </div>
                            <div class="panel-footer">Gas Agencies</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-history"></i> Recent User Activities
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Account Type</th>
                                                <th>Action</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentActivities as $activity): 
                                                $badgeClass = 'badge-' . str_replace(array('admin', 'merchant', 'institution', 'gas_agency'), 
                                                array('admin', 'merchant', 'institution', 'gas'), $activity['account_type']);
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($activity['email']); ?></td>
                                                <td>
                                                    <span class="account-badge <?php echo $badgeClass; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $activity['account_type'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo ucfirst($activity['action']); ?></td>
                                                <td><?php echo date('H:i', strtotime($activity['timestamp'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-user-plus"></i> Recently Registered Users
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Email</th>
                                                <th>Account Type</th>
                                                <th>Registered</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentUsers as $user): 
                                                $badgeClass = 'badge-' . str_replace(array('admin', 'merchant', 'institution', 'gas_agency'), 
                                                array('admin', 'merchant', 'institution', 'gas'), $user['account_type']);
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <span class="account-badge <?php echo $badgeClass; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $user['account_type'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                <td>
                                                    <a href="users/view.php?id=<?php echo $user['id']; ?>" class="btn btn-xs btn-primary">View</a>
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
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-tasks"></i> Quick Actions
                            </div>
                            <div class="panel-body text-center">
                                <div class="btn-group">
                                    <a href="createprofiles.php" class="btn btn-primary"><i class="fa fa-user-plus"></i> Create Profiles</a>
                                    <a href="#" class="btn btn-danger"><i class="fa fa-user-shield"></i> View Users</a>
                                    <a href="#" class="btn btn-success"><i class="fa fa-store"></i> View Accounts</a>
                                    <a href="activity_logs.php" class="btn btn-info"><i class="fa fa-history"></i> View All Activities</a>
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
    <script>
        // Auto-refresh dashboard every 2 minutes
        setTimeout(function(){
            location.reload();
        }, 120000);
    </script>
</body>
</html>