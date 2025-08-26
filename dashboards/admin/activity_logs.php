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

// Pagination variables
$records_per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total number of logs
$total_logs = $conn->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
$total_pages = ceil($total_logs / $records_per_page);

// Get activity logs with user details
$logs = $conn->query("
    SELECT a.id, a.action, a.timestamp, u.id as user_id, u.email, u.account_type 
    FROM activity_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.timestamp DESC
    LIMIT $offset, $records_per_page
")->fetchAll(PDO::FETCH_ASSOC);

// Function to format timestamp
function formatTimestamp($timestamp) {
    return date('M d, Y H:i:s', strtotime($timestamp));
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Smart City Admin - Activity Logs</title>
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
        .pagination-info {
            margin-top: 20px;
            text-align: center;
        }
        .action-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
                    <li class="active-link">
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
                        <h2>ACTIVITY LOGS</h2>
                        <h5>View all user activities in the system</h5>
                    </div>
                </div>
                <!-- /. ROW  -->
                <hr />
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-history"></i> System Activities
                                <div class="pull-right">
                                    <span class="badge">Total: <?php echo $total_logs; ?></span>
                                </div>
                            </div>
                            <div class="panel-body">
                                <?php if (empty($logs)): ?>
                                    <div class="alert alert-info">No activity logs found.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>User</th>
                                                    <th>Account Type</th>
                                                    <th>Action</th>
                                                    <th>Timestamp</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($logs as $log): 
													$accountType = isset($log['account_type']) ? $log['account_type'] : 'system';
													$badgeClass = 'badge-' . str_replace(
														array('admin', 'merchant', 'institution', 'gas_agency', 'personal_user'), 
														array('admin', 'merchant', 'institution', 'gas', 'user'), 
														$accountType
													);
												?>
                                                <tr>
                                                    <td><?php echo $log['id']; ?></td>
                                                    <td>
                                                        <?php if ($log['user_id']): ?>
                                                            <a href="view_user.php?id=<?php echo $log['user_id']; ?>">
                                                                <?php echo htmlspecialchars(isset($log['email']) ? $log['email'] : 'System'); ?>
                                                            </a>
                                                        <?php else: ?>
                                                            System
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="account-badge <?php echo $badgeClass; ?>">
                                                            <?php echo isset($log['account_type']) ? 
                                                                ucfirst(str_replace('_', ' ', $log['account_type'])) : 
                                                                'System'; ?>
                                                        </span>
                                                    </td>
                                                    <td class="action-cell" title="<?php echo htmlspecialchars($log['action']); ?>">
                                                        <?php echo htmlspecialchars($log['action']); ?>
                                                    </td>
                                                    <td><?php echo formatTimestamp($log['timestamp']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Pagination -->
                                    <div class="pagination-info">
                                        <nav aria-label="Page navigation">
                                            <ul class="pagination">
                                                <?php if ($page > 1): ?>
                                                    <li>
                                                        <a href="activity_logs.php?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                                            <span aria-hidden="true">&laquo;</span>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                                
                                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                    <li class="<?php echo $i == $page ? 'active' : ''; ?>">
                                                        <a href="activity_logs.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                    </li>
                                                <?php endfor; ?>
                                                
                                                <?php if ($page < $total_pages): ?>
                                                    <li>
                                                        <a href="activity_logs.php?page=<?php echo $page + 1; ?>" aria-label="Next">
                                                            <span aria-hidden="true">&raquo;</span>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </nav>
                                        <p>Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $records_per_page, $total_logs); ?> of <?php echo $total_logs; ?> entries</p>
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
    <script>
        // Auto-refresh page every 5 minutes
        setTimeout(function(){
            location.reload();
        }, 300000);
    </script>
</body>
</html>