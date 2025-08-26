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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_tariff_slab'])) {
        // Add new tariff slab
        $consumer_type = $_POST['consumer_type'];
        $min_units = (int)$_POST['min_units'];
        $max_units = (int)$_POST['max_units'];
        $rate_per_unit = (float)$_POST['rate_per_unit'];
        
        try {
            $stmt = $conn->prepare("INSERT INTO kseb_tariff_slabs 
                (consumer_type, min_units, max_units, rate_per_unit) 
                VALUES (?, ?, ?, ?)");
            $stmt->execute(array($consumer_type, $min_units, $max_units, $rate_per_unit));
            $success = "Tariff slab added successfully!";
        } catch (PDOException $e) {
            $error = "Error adding tariff slab: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_tariff_slab'])) {
        // Update existing tariff slab
        $id = (int)$_POST['id'];
        $rate_per_unit = (float)$_POST['rate_per_unit'];
        
        try {
            $stmt = $conn->prepare("UPDATE kseb_tariff_slabs SET rate_per_unit = ? WHERE id = ?");
            $stmt->execute(array($rate_per_unit, $id));
            $success = "Tariff slab updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating tariff slab: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_tariff_slab'])) {
        // Delete tariff slab
        $id = (int)$_POST['id'];
        
        try {
            $stmt = $conn->prepare("DELETE FROM kseb_tariff_slabs WHERE id = ?");
            $stmt->execute(array($id));
            $success = "Tariff slab deleted successfully!";
        } catch (PDOException $e) {
            $error = "Error deleting tariff slab: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_fixed_charge'])) {
        // Update fixed charge
        $id = (int)$_POST['id'];
        $fixed_amount = (float)$_POST['fixed_amount'];
        
        try {
            $stmt = $conn->prepare("UPDATE kseb_fixed_charges SET fixed_amount = ? WHERE id = ?");
            $stmt->execute(array($fixed_amount, $id));
            $success = "Fixed charge updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating fixed charge: " . $e->getMessage();
        }
    }
}

// Get all tariff slabs grouped by consumer type
$tariffSlabs = $conn->query("
    SELECT * FROM kseb_tariff_slabs 
    ORDER BY consumer_type, min_units
")->fetchAll(PDO::FETCH_ASSOC);

// Group slabs by consumer type for display
$groupedSlabs = array();
foreach ($tariffSlabs as $slab) {
    $groupedSlabs[$slab['consumer_type']][] = $slab;
}

// Get all fixed charges
$fixedCharges = $conn->query("
    SELECT * FROM kseb_fixed_charges 
    ORDER BY connection_type, phase_type
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>KSEB - Tariff Management</title>
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
        .tariff-card {
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .tariff-header {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            font-weight: bold;
        }
        .tariff-body {
            padding: 15px;
        }
        .slab-row {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #eee;
        }
        .no-bottom-border {
            border-bottom: none !important;
        }
        .domestic-bg {
            background-color: #e8f5e9;
        }
        .commercial-bg {
            background-color: #e3f2fd;
        }
        .industrial-bg {
            background-color: #ffebee;
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
                    <li>
                        <a href="payments.php"><i class="fa fa-credit-card"></i>Payments</a>
                    </li>
                    <li class="active-link">
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
                        <h2>KSEB TARIFF MANAGEMENT</h2>
                        <h5>Manage electricity tariff slabs and fixed charges</h5>
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
                                <i class="fa fa-list-ol"></i> Current Tariff Slabs
                            </div>
                            <div class="panel-body">
                                <?php foreach (array('domestic', 'commercial', 'industrial') as $type): 
                                    $bgClass = strtolower($type) . '-bg';
                                ?>
                                <div class="tariff-card <?php echo $bgClass; ?>">
                                    <div class="tariff-header">
                                        <?php echo ucfirst($type); ?> Tariff Slabs
                                    </div>
                                    <div class="tariff-body">
                                        <?php if (!empty($groupedSlabs[$type])): ?>
                                            <?php foreach ($groupedSlabs[$type] as $slab): ?>
                                            <div class="slab-row">
                                                <form method="POST" class="form-inline">
                                                    <input type="hidden" name="id" value="<?php echo $slab['id']; ?>">
                                                    <div class="form-group">
                                                        <label><?php echo $slab['min_units']; ?> - <?php echo $slab['max_units']; ?> units:</label>
                                                    </div>
                                                    <div class="form-group" style="margin: 0 10px;">
                                                        <div class="input-group">
                                                            <span class="input-group-addon">₹</span>
                                                            <input type="number" name="rate_per_unit" class="form-control" 
                                                                value="<?php echo $slab['rate_per_unit']; ?>" step="0.01" min="0" required>
                                                        </div>
                                                    </div>
                                                    <button type="submit" name="update_tariff_slab" class="btn btn-primary btn-xs">
                                                        <i class="fa fa-save"></i> Update
                                                    </button>
                                                    <button type="submit" name="delete_tariff_slab" class="btn btn-danger btn-xs" 
                                                        onclick="return confirm('Are you sure you want to delete this tariff slab?')">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="alert alert-info">No tariff slabs defined for <?php echo $type; ?> consumers</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-plus-circle"></i> Add New Tariff Slab
                            </div>
                            <div class="panel-body">
                                <form method="POST" action="">
                                    <div class="form-group">
                                        <label>Consumer Type</label>
                                        <select name="consumer_type" class="form-control" required>
                                            <option value="">-- Select Consumer Type --</option>
                                            <option value="domestic">Domestic</option>
                                            <option value="commercial">Commercial</option>
                                            <option value="industrial">Industrial</option>
                                        </select>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Minimum Units</label>
                                                <input type="number" name="min_units" class="form-control" min="0" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Maximum Units</label>
                                                <input type="number" name="max_units" class="form-control" min="1" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Rate Per Unit (₹)</label>
                                        <input type="number" name="rate_per_unit" class="form-control" step="0.01" min="0" required>
                                    </div>
                                    
                                    <button type="submit" name="add_tariff_slab" class="btn btn-success">
                                        <i class="fa fa-plus"></i> Add Tariff Slab
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-cog"></i> Fixed Charges
                            </div>
                            <div class="panel-body">
                                <?php if (!empty($fixedCharges)): ?>
                                    <?php foreach ($fixedCharges as $charge): ?>
                                    <div class="slab-row <?php echo empty($charge) ? 'no-bottom-border' : ''; ?>">
                                        <form method="POST" class="form-inline">
                                            <input type="hidden" name="id" value="<?php echo $charge['id']; ?>">
                                            <div class="form-group">
                                                <label>
                                                    <?php echo ucfirst($charge['connection_type']); ?> - 
                                                    <?php echo ucfirst(str_replace('_', ' ', $charge['phase_type'])); ?>:
                                                </label>
                                            </div>
                                            <div class="form-group" style="margin: 0 10px;">
                                                <div class="input-group">
                                                    <span class="input-group-addon">₹</span>
                                                    <input type="number" name="fixed_amount" class="form-control" 
                                                        value="<?php echo $charge['fixed_amount']; ?>" step="0.01" min="0" required>
                                                </div>
                                            </div>
                                            <button type="submit" name="update_fixed_charge" class="btn btn-primary">
                                                <i class="fa fa-save"></i> Update
                                            </button>
                                        </form>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">No fixed charges defined</div>
                                <?php endif; ?>
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