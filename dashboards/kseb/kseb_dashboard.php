<?php
session_start();
require '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch KSEB office details based on logged-in user
$stmt = $conn->query("SELECT * FROM kseb WHERE user_id = $user_id");
$kseb_office = $stmt->fetch(PDO::FETCH_ASSOC);
$office_name = $kseb_office['kseb_office_name'];
$message = "Welcome $office_name";

if (!$kseb_office) {
    die("KSEB office not found for this user.");
}

// Get dashboard statistics
$totalCustomers = $conn->query("SELECT COUNT(*) FROM kseb_consumers WHERE status = 'active'")->fetchColumn();
$pendingBills = $conn->query("SELECT COUNT(*) FROM kseb_bills WHERE payment_status = 'unpaid'")->fetchColumn();
$todayRevenue = $conn->query("SELECT SUM(total_amount) FROM kseb_bills WHERE payment_status = 'paid' AND DATE(issue_date) = CURDATE()")->fetchColumn();
$overdueBills = $conn->query("SELECT COUNT(*) FROM kseb_bills WHERE payment_status = 'unpaid' AND due_date < CURDATE()")->fetchColumn();

// Get recent bills with consumer details
$recentBills = $conn->query("
    SELECT b.id, b.bill_number, c.consumer_number, c.name, b.total_amount, b.payment_status, b.due_date,
           CASE 
               WHEN b.payment_status = 'unpaid' AND b.due_date < CURDATE() THEN 'overdue'
               ELSE b.payment_status
           END AS display_status
    FROM kseb_bills b
    JOIN kseb_consumers c ON b.consumer_id = c.id
    ORDER BY b.issue_date DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get consumption distribution by connection type
$consumptionStats = $conn->query("
    SELECT c.connection_type, COUNT(*) as count, 
           SUM(b.units_consumed) as total_units, 
           SUM(b.total_amount) as total_revenue
    FROM kseb_bills b
    JOIN kseb_consumers c ON b.consumer_id = c.id
    WHERE b.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
    GROUP BY c.connection_type
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>KSEB Admin Dashboard</title>
    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES-->
    <link href="assets/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .kseb-primary {
            background-color: #0056b3 !important;
            border-left: 4px solid #003366 !important;
        }
        .kseb-secondary {
            background-color: #003366 !important;
        }
        .div-square {
            transition: all 0.3s ease;
        }
        .div-square:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .domestic-card {
            border-left: 4px solid #28a745;
        }
        .commercial-card {
            border-left: 4px solid #ffc107;
        }
        .industrial-card {
            border-left: 4px solid #dc3545;
        }
        .status-paid {
            background-color: #28a745;
            color: white;
        }
        .status-unpaid {
            background-color: #ffc107;
            color: black;
        }
        .status-overdue {
            background-color: #dc3545;
            color: white;
        }
        .consumption-chart {
            height: 250px;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <div class="navbar navbar-inverse navbar-fixed-top kseb-secondary">
            <div class="adjust-nav">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="#">
                        <?php echo htmlspecialchars($office_name); ?>
                    </a>
                </div>
                <span class="logout-spn">
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
                        <a href="kseb_dashboard.php"><i class="fa fa-tachometer"></i>Dashboard</a>
                    </li>
                    <li>
                        <a href="consumers.php"><i class="fa fa-users"></i>Consumers</a>
                    </li>
                    <li>
                        <a href="billing.php"><i class="fa fa-file-text"></i> Billing</a>
                    </li>
                    <li>
                        <a href="payments.php"><i class="fa fa-credit-card"></i>Payments</a>
                    </li>
                    <li>
                        <a href="tariffs.php"><i class="fa fa-list-alt"></i>Tariff Management</a>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- /. NAV SIDE  -->
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        <h2>KSEB ADMIN DASHBOARD</h2>
                        <h5><?php echo htmlspecialchars($message); ?></h5>
                    </div>
                </div>
                <!-- /. ROW  -->
                <hr />
                
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-primary text-center no-boder div-square">
                            <div class="panel-body">
                                <i class="fa fa-users fa-3x"></i>
                                <h3><?php echo $totalCustomers; ?></h3>
                            </div>
                            <div class="panel-footer">Active Consumers</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-green text-center no-boder div-square">
                            <div class="panel-body">
                                <i class="fa fa-file-text fa-3x"></i>
                                <h3><?php echo $pendingBills; ?></h3>
                            </div>
                            <div class="panel-footer">Pending Bills</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-yellow text-center no-boder div-square">
                            <div class="panel-body">
                                <i class="fa fa-rupee fa-3x"></i>
                                <h3>₹<?php echo number_format($todayRevenue ?: 0, 2); ?></h3>
                            </div>
                            <div class="panel-footer">Today's Revenue</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-red text-center no-boder div-square">
                            <div class="panel-body">
                                <i class="fa fa-exclamation-triangle fa-3x"></i>
                                <h3><?php echo $overdueBills; ?></h3>
                            </div>
                            <div class="panel-footer">Overdue Bills</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-file-text"></i> Recent Bills
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Bill #</th>
                                                <th>Consumer No.</th>
                                                <th>Name</th>
                                                <th>Amount</th>
                                                <th>Due Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentBills as $bill): 
                                                $statusClass = 'status-' . $bill['display_status'];
                                                $dueDate = new DateTime($bill['due_date']);
                                                $isOverdue = ($bill['display_status'] == 'overdue');
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($bill['bill_number']); ?></td>
                                                <td><?php echo htmlspecialchars($bill['consumer_number']); ?></td>
                                                <td><?php echo htmlspecialchars($bill['name']); ?></td>
                                                <td>₹<?php echo number_format($bill['total_amount'], 2); ?></td>
                                                <td <?php echo $isOverdue ? 'class="text-danger"' : ''; ?>>
                                                    <?php echo $dueDate->format('d M Y'); ?>
                                                    <?php if ($isOverdue): ?>
                                                        <i class="fa fa-warning"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="label <?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($bill['display_status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-bolt"></i> Consumption Distribution (Last Month)
                            </div>
                            <div class="panel-body">
                                <?php if (!empty($consumptionStats)): ?>
                                    <div class="list-group">
                                        <?php foreach ($consumptionStats as $stat): 
                                            $cardClass = strtolower($stat['connection_type']) . '-card';
                                        ?>
                                        <div class="list-group-item <?php echo $cardClass; ?> div-square">
                                            <h4 class="list-group-item-heading">
                                                <?php echo ucfirst($stat['connection_type']); ?> Consumers
                                            </h4>
                                            <p class="list-group-item-text">
                                                <strong><?php echo $stat['count']; ?></strong> consumers<br>
                                                <strong><?php echo number_format($stat['total_units']); ?></strong> units<br>
                                                <strong>₹<?php echo number_format($stat['total_revenue'], 2); ?></strong> revenue
                                            </p>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">No consumption data available for the last month.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-area-chart"></i> Quick Actions
                            </div>
                            <div class="panel-body text-center">
                                <div class="btn-group">
                                    <a href="consumers.php?action=add" class="btn btn-primary">
                                        <i class="fa fa-user-plus"></i> Add Consumer
                                    </a>
                                    <a href="billing.php" class="btn btn-success">
                                        <i class="fa fa-file-text"></i> Generate Bill
                                    </a>
                                    <a href="payments.php" class="btn btn-info">
                                        <i class="fa fa-credit-card"></i> Record Payment
                                    </a>
                                    <a href="reports.php" class="btn btn-warning">
                                        <i class="fa fa-bar-chart"></i> View Reports
                                    </a>
                                    <a href="tariffs.php" class="btn btn-default">
                                        <i class="fa fa-cog"></i> Manage Tariffs
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /. ROW  -->
            </div>
            <!-- /. PAGE INNER  -->
        </div>
        <!-- /. PAGE WRAPPER  -->
    </div>
    <div class="footer kseb-secondary">
        <div class="row">
            <div class="col-lg-12">
                &copy; <?php echo date('Y'); ?> Kerala State Electricity Board | 
                <a href="https://kseb.in" style="color:#fff;" target="_blank">www.kseb.in</a> | 
                Admin Helpdesk: 0471-2334567
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