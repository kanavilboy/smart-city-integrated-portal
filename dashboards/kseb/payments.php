<?php
session_start();
require '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch KSEB office details
$stmt = $conn->query("SELECT * FROM kseb WHERE user_id = $user_id");
$kseb_office = $stmt->fetch(PDO::FETCH_ASSOC);
$office_name = $kseb_office['kseb_office_name'];

if (!$kseb_office) {
    die("KSEB office not found for this user.");
}

// Initialize variables
$error = '';
$success = '';

// Handle search/filter form submission
$search_term = '';
$payment_status = '';
$payment_method = '';
$start_date = '';
$end_date = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $search_term = $_POST['search_term'];
    $payment_status = $_POST['payment_status'];
    $payment_method = $_POST['payment_method'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
}

// Build query for transactions with filters
$query = "
    SELECT t.*, c.consumer_number, c.name, b.total_amount as bill_amount
    FROM kseb_transactions t
    JOIN kseb_consumers c ON t.consumer_id = c.id
    JOIN kseb_bills b ON t.bill_id = b.id
    WHERE 1=1
";

$params = array();

if (!empty($search_term)) {
    $query .= " AND (c.consumer_number LIKE ? OR c.name LIKE ? OR t.bill_number LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
}

if (!empty($payment_status)) {
    $query .= " AND t.status = ?";
    $params[] = $payment_status;
}

if (!empty($payment_method)) {
    $query .= " AND t.payment_method = ?";
    $params[] = $payment_method;
}

if (!empty($start_date)) {
    $query .= " AND DATE(t.payment_date) >= ?";
    $params[] = $start_date;
}

if (!empty($end_date)) {
    $query .= " AND DATE(t.payment_date) <= ?";
    $params[] = $end_date;
}

$query .= " ORDER BY t.payment_date DESC";

// Get transactions
$stmt = $conn->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total payments for stats
$total_payments = $conn->query("SELECT COUNT(*) FROM kseb_transactions")->fetchColumn();
$today_payments = $conn->query("
    SELECT COUNT(*) FROM kseb_transactions 
    WHERE DATE(payment_date) = CURDATE()
")->fetchColumn();
$total_revenue = $conn->query("
    SELECT SUM(amount) FROM kseb_transactions 
    WHERE status = 'success'
")->fetchColumn();
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>KSEB - Payments Management</title>
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
        .payment-card {
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .payment-header {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            font-weight: bold;
        }
        .payment-body {
            padding: 15px;
        }
        .status-success {
            background-color: #28a745;
            color: white;
        }
        .status-pending {
            background-color: #ffc107;
            color: black;
        }
        .status-failed {
            background-color: #dc3545;
            color: white;
        }
        .status-refunded {
            background-color: #6c757d;
            color: white;
        }
        .filter-form {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
                    <li>
                        <a href="kseb_dashboard.php"><i class="fa fa-tachometer"></i>Dashboard</a>
                    </li>
                    <li>
                        <a href="consumers.php"><i class="fa fa-users"></i>Consumers</a>
                    </li>
                    <li>
                        <a href="billing.php"><i class="fa fa-file-text"></i> Billing</a>
                    </li>
                    <li class="active-link">
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
                        <h2>KSEB PAYMENTS MANAGEMENT</h2>
                        <h5>View and manage all payment transactions</h5>
                    </div>
                </div>
                <!-- /. ROW  -->
                <hr />
                
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-primary text-center no-boder div-square">
                            <div class="panel-body">
                                <i class="fa fa-credit-card fa-3x"></i>
                                <h3><?php echo $total_payments; ?></h3>
                            </div>
                            <div class="panel-footer">Total Payments</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-green text-center no-boder div-square">
                            <div class="panel-body">
                                <i class="fa fa-calendar fa-3x"></i>
                                <h3><?php echo $today_payments; ?></h3>
                            </div>
                            <div class="panel-footer">Today's Payments</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6">
                        <div class="panel panel-yellow text-center no-boder div-square">
                            <div class="panel-body">
                                <i class="fa fa-rupee fa-3x"></i>
                                <h3>₹<?php echo number_format($total_revenue ?: 0, 2); ?></h3>
                            </div>
                            <div class="panel-footer">Total Revenue</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="filter-form">
                            <form method="POST" action="" class="form-inline">
                                <div class="form-group">
                                    <input type="text" name="search_term" class="form-control" placeholder="Search..." 
                                        value="<?php echo htmlspecialchars($search_term); ?>">
                                </div>
                                
                                <div class="form-group" style="margin-left: 10px;">
                                    <select name="payment_status" class="form-control">
                                        <option value="">All Status</option>
                                        <option value="success" <?php echo ($payment_status == 'success') ? 'selected' : ''; ?>>Success</option>
                                        <option value="pending" <?php echo ($payment_status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="failed" <?php echo ($payment_status == 'failed') ? 'selected' : ''; ?>>Failed</option>
                                        <option value="refunded" <?php echo ($payment_status == 'refunded') ? 'selected' : ''; ?>>Refunded</option>
                                    </select>
                                </div>
                                
                                <div class="form-group" style="margin-left: 10px;">
                                    <select name="payment_method" class="form-control">
                                        <option value="">All Methods</option>
                                        <option value="cash" <?php echo ($payment_method == 'cash') ? 'selected' : ''; ?>>Cash</option>
                                        <option value="credit_card" <?php echo ($payment_method == 'credit_card') ? 'selected' : ''; ?>>Credit Card</option>
                                        <option value="debit_card" <?php echo ($payment_method == 'debit_card') ? 'selected' : ''; ?>>Debit Card</option>
                                        <option value="net_banking" <?php echo ($payment_method == 'net_banking') ? 'selected' : ''; ?>>Net Banking</option>
                                        <option value="upi" <?php echo ($payment_method == 'upi') ? 'selected' : ''; ?>>UPI</option>
                                        <option value="cheque" <?php echo ($payment_method == 'cheque') ? 'selected' : ''; ?>>Cheque</option>
                                        <option value="dd" <?php echo ($payment_method == 'dd') ? 'selected' : ''; ?>>Demand Draft</option>
                                    </select>
                                </div>
                                
                                <div class="form-group" style="margin-left: 10px;">
                                    <label>From:</label>
                                    <input type="date" name="start_date" class="form-control" 
                                        value="<?php echo htmlspecialchars($start_date); ?>">
                                </div>
                                
                                <div class="form-group" style="margin-left: 10px;">
                                    <label>To:</label>
                                    <input type="date" name="end_date" class="form-control" 
                                        value="<?php echo htmlspecialchars($end_date); ?>">
                                </div>
                                
                                <button type="submit" name="search" class="btn btn-primary" style="margin-left: 10px;">
                                    <i class="fa fa-search"></i> Filter
                                </button>
                                
                                <a href="payments.php" class="btn btn-default" style="margin-left: 10px;">
                                    <i class="fa fa-refresh"></i> Reset
                                </a>
                            </form>
                        </div>
                        
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-credit-card"></i> Payment Transactions
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date/Time</th>
                                                <th>Transaction ID</th>
                                                <th>Consumer</th>
                                                <th>Bill #</th>
                                                <th>Method</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transactions as $txn): 
                                                $statusClass = 'status-' . $txn['status'];
                                            ?>
                                            <tr>
                                                <td><?php echo date('d M Y H:i', strtotime($txn['payment_date'])); ?></td>
                                                <td>#<?php echo $txn['id']; ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($txn['consumer_number']); ?><br>
                                                    <?php echo htmlspecialchars($txn['name']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($txn['bill_number']); ?></td>
                                                <td><?php echo ucfirst(str_replace('_', ' ', $txn['payment_method'])); ?></td>
                                                <td>
                                                    ₹<?php echo number_format($txn['amount'], 2); ?>
                                                    <?php if ($txn['amount'] < $txn['bill_amount']): ?>
                                                        <br><small class="text-muted">(Partial)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="label <?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($txn['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($txn['status'] == 'success'): ?>
                                                    <a href="#" class="btn btn-warning btn-xs">
                                                        <i class="fa fa-undo"></i> Refund
                                                    </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($transactions)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No payment transactions found</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
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