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
$consumer_id = '';
$consumer_details = null;
$units_consumed = '';
$error = '';
$success = '';
$calculated_charges = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['calculate'])) {
        // Calculate charges
        $consumer_id = $_POST['consumer_id'];
        $units_consumed = (float)$_POST['units_consumed'];
        
        // Validate inputs
        if (empty($consumer_id) || empty($units_consumed)) {
            $error = "Please select a consumer and enter units consumed";
        } else {
            // Get consumer details
            $stmt = $conn->prepare("SELECT * FROM kseb_consumers WHERE id = ? AND status = 'active'");
            $stmt->execute(array($consumer_id));
            $consumer_details = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$consumer_details) {
                $error = "Invalid consumer selected or consumer is inactive";
            } else {
                // Calculate energy charge based on tariff slabs
                $stmt = $conn->prepare("SELECT * FROM kseb_tariff_slabs WHERE consumer_type = ? AND ? BETWEEN min_units AND max_units");
                $stmt->execute(array($consumer_details['connection_type'], $units_consumed));
                $tariff_slab = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$tariff_slab) {
                    $error = "No tariff slab found for this consumption level";
                } else {
                    $energy_charge = $units_consumed * $tariff_slab['rate_per_unit'];
                    
                    // Get fixed charges
                    $stmt = $conn->prepare("SELECT * FROM kseb_fixed_charges WHERE connection_type = ? AND phase_type = ?");
                    $stmt->execute(array($consumer_details['connection_type'], $consumer_details['phase_type']));
                    $fixed_charge = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$fixed_charge) {
                        $error = "Fixed charges not defined for this connection type";
                    } else {
                        // Calculate electricity duty (5% of energy charge)
                        $electricity_duty = $energy_charge * 0.05;
                        
                        // Meter rent (fixed)
                        $meter_rent = 10.00;
                        
                        // Total amount
                        $total_amount = $energy_charge + $fixed_charge['fixed_amount'] + $electricity_duty + $meter_rent;
                        
                        $calculated_charges = array(
                            'units_consumed' => $units_consumed,
                            'energy_charge' => $energy_charge,
                            'fixed_charge' => $fixed_charge['fixed_amount'],
                            'electricity_duty' => $electricity_duty,
                            'meter_rent' => $meter_rent,
                            'total_amount' => $total_amount
                        );
                    }
                }
            }
        }
    } elseif (isset($_POST['generate_bill'])) {
        // Generate the bill and save to database
        $consumer_id = $_POST['consumer_id'];
        $units_consumed = (float)$_POST['units_consumed'];
        $energy_charge = (float)$_POST['energy_charge'];
        $fixed_charge = (float)$_POST['fixed_charge'];
        $electricity_duty = (float)$_POST['electricity_duty'];
        $meter_rent = (float)$_POST['meter_rent'];
        $total_amount = (float)$_POST['total_amount'];
        
        // Generate a unique bill number
        $bill_number = 'KSEB' . date('Ym') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Calculate dates (issue date = today, due date = 15 days from now)
        $issue_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime('+15 days'));
        
        try {
            $conn->beginTransaction();
            
            // Insert bill record
            $stmt = $conn->prepare("INSERT INTO kseb_bills 
                (consumer_id, bill_number, issue_date, due_date, units_consumed, energy_charge, 
                fixed_charge, electricity_duty, meter_rent, total_amount, payment_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'unpaid')");
            $stmt->execute(array(
                $consumer_id, $bill_number, $issue_date, $due_date, $units_consumed, 
                $energy_charge, $fixed_charge, $electricity_duty, $meter_rent, $total_amount
            ));
            
            $conn->commit();
            $success = "Bill generated successfully! Bill Number: $bill_number";
            
            // Reset form
            $consumer_id = '';
            $units_consumed = '';
            $calculated_charges = null;
            $consumer_details = null;
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = "Error generating bill: " . $e->getMessage();
        }
    }
}

// Get active consumers for dropdown
$consumers = $conn->query("SELECT id, consumer_number, name FROM kseb_consumers WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>KSEB - Billing</title>
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
        .charge-details {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .charge-row {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .total-row {
            font-weight: bold;
            font-size: 1.1em;
            color: #003366;
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
                    <li class="active-link">
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
                        <h2>KSEB BILL GENERATION</h2>
                        <h5>Generate electricity bills for consumers</h5>
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
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-file-text"></i> Bill Generator
                            </div>
                            <div class="panel-body">
                                <form method="POST" action="">
                                    <div class="form-group">
                                        <label>Select Consumer</label>
                                        <select name="consumer_id" class="form-control" required>
                                            <option value="">-- Select Consumer --</option>
                                            <?php foreach ($consumers as $consumer): ?>
                                            <option value="<?php echo $consumer['id']; ?>" <?php echo ($consumer_id == $consumer['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($consumer['consumer_number'] . ' - ' . $consumer['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <?php if ($consumer_details): ?>
                                    <div class="form-group">
                                        <label>Consumer Details</label>
                                        <div class="well">
                                            <strong>Name:</strong> <?php echo htmlspecialchars($consumer_details['name']); ?><br>
                                            <strong>Address:</strong> <?php echo htmlspecialchars($consumer_details['address']); ?><br>
                                            <strong>Connection Type:</strong> <?php echo ucfirst($consumer_details['connection_type']); ?><br>
                                            <strong>Phase Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $consumer_details['phase_type'])); ?><br>
                                            <strong>Meter No:</strong> <?php echo htmlspecialchars($consumer_details['meter_number']); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="form-group">
                                        <label>Units Consumed</label>
                                        <input type="number" name="units_consumed" class="form-control" 
                                            value="<?php echo htmlspecialchars($units_consumed); ?>" step="0.01" min="0" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <?php if (!$calculated_charges): ?>
                                        <button type="submit" name="calculate" class="btn btn-primary">
                                            <i class="fa fa-calculator"></i> Calculate Charges
                                        </button>
                                        <?php else: ?>
                                        <button type="submit" name="generate_bill" class="btn btn-success">
                                            <i class="fa fa-file-text"></i> Generate Bill
                                        </button>
                                        <button type="submit" name="calculate" class="btn btn-primary">
                                            <i class="fa fa-refresh"></i> Re-calculate
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($calculated_charges): ?>
                                    <input type="hidden" name="energy_charge" value="<?php echo $calculated_charges['energy_charge']; ?>">
                                    <input type="hidden" name="fixed_charge" value="<?php echo $calculated_charges['fixed_charge']; ?>">
                                    <input type="hidden" name="electricity_duty" value="<?php echo $calculated_charges['electricity_duty']; ?>">
                                    <input type="hidden" name="meter_rent" value="<?php echo $calculated_charges['meter_rent']; ?>">
                                    <input type="hidden" name="total_amount" value="<?php echo $calculated_charges['total_amount']; ?>">
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($calculated_charges): ?>
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-money"></i> Bill Details
                            </div>
                            <div class="panel-body">
                                <div class="charge-details">
                                    <div class="charge-row">
                                        <div class="row">
                                            <div class="col-sm-6">Units Consumed:</div>
                                            <div class="col-sm-6 text-right"><?php echo $calculated_charges['units_consumed']; ?> units</div>
                                        </div>
                                    </div>
                                    
                                    <div class="charge-row">
                                        <div class="row">
                                            <div class="col-sm-6">Energy Charge:</div>
                                            <div class="col-sm-6 text-right">₹<?php echo number_format($calculated_charges['energy_charge'], 2); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="charge-row">
                                        <div class="row">
                                            <div class="col-sm-6">Fixed Charge:</div>
                                            <div class="col-sm-6 text-right">₹<?php echo number_format($calculated_charges['fixed_charge'], 2); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="charge-row">
                                        <div class="row">
                                            <div class="col-sm-6">Electricity Duty (5%):</div>
                                            <div class="col-sm-6 text-right">₹<?php echo number_format($calculated_charges['electricity_duty'], 2); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="charge-row">
                                        <div class="row">
                                            <div class="col-sm-6">Meter Rent:</div>
                                            <div class="col-sm-6 text-right">₹<?php echo number_format($calculated_charges['meter_rent'], 2); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="charge-row total-row">
                                        <div class="row">
                                            <div class="col-sm-6">Total Amount:</div>
                                            <div class="col-sm-6 text-right">₹<?php echo number_format($calculated_charges['total_amount'], 2); ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> 
                                    Bill will be due 15 days from the date of generation.
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-history"></i> Recent Bills
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Bill #</th>
                                                <th>Date</th>
                                                <th>Consumer</th>
                                                <th>Units</th>
                                                <th>Amount</th>
                                                <th>Due Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Get recent bills
                                            $recent_bills = $conn->query("
                                                SELECT b.bill_number, b.issue_date, b.due_date, b.units_consumed, b.total_amount, 
                                                       b.payment_status, c.consumer_number, c.name
                                                FROM kseb_bills b
                                                JOIN kseb_consumers c ON b.consumer_id = c.id
                                                ORDER BY b.issue_date DESC LIMIT 10
                                            ")->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            foreach ($recent_bills as $bill):
                                                $status_class = '';
                                                if ($bill['payment_status'] == 'paid') {
                                                    $status_class = 'success';
                                                } elseif (strtotime($bill['due_date']) < time()) {
                                                    $status_class = 'danger';
                                                    $bill['payment_status'] = 'overdue';
                                                } else {
                                                    $status_class = 'warning';
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($bill['bill_number']); ?></td>
                                                <td><?php echo date('d M Y', strtotime($bill['issue_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($bill['consumer_number'] . ' - ' . $bill['name']); ?></td>
                                                <td><?php echo $bill['units_consumed']; ?></td>
                                                <td>₹<?php echo number_format($bill['total_amount'], 2); ?></td>
                                                <td><?php echo date('d M Y', strtotime($bill['due_date'])); ?></td>
                                                <td>
                                                    <span class="label label-<?php echo $status_class; ?>">
                                                        <?php echo ucfirst($bill['payment_status']); ?>
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