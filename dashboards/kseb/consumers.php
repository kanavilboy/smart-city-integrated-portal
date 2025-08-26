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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_consumer'])) {
        // Add new consumer
        $consumer_number = $_POST['counsumer_no'];
        $name = $_POST['name'];
        $address = $_POST['address'];
        $connection_type = $_POST['connection_type'];
        $phase_type = $_POST['phase_type'];
        $meter_number = $_POST['meter_number'];
        
        $stmt = $conn->prepare("INSERT INTO kseb_consumers 
            (consumer_number, name, address, connection_type, phase_type, meter_number, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute(array($consumer_number, $name, $address, $connection_type, $phase_type, $meter_number));
        
        $_SESSION['success'] = "Consumer added successfully with Consumer Number: $consumer_number";
        header("Location: consumers.php");
        exit();
    } elseif (isset($_POST['update_consumer'])) {
        // Update existing consumer
        $id = $_POST['id'];
        $name = $_POST['name'];
        $address = $_POST['address'];
        $connection_type = $_POST['connection_type'];
        $phase_type = $_POST['phase_type'];
        $meter_number = $_POST['meter_number'];
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE kseb_consumers SET 
            name = ?, address = ?, connection_type = ?, phase_type = ?, 
            meter_number = ?, status = ? 
            WHERE id = ?");
        $stmt->execute(array($name, $address, $connection_type, $phase_type, $meter_number, $status, $id));
        
        $_SESSION['success'] = "Consumer updated successfully";
        header("Location: consumers.php");
        exit();
    } elseif (isset($_GET['delete'])) {
        // Delete consumer (soft delete by changing status)
        $id = $_GET['delete'];
        $stmt = $conn->prepare("UPDATE kseb_consumers SET status = 'inactive' WHERE id = ?");
        $stmt->execute(($id));
        
        $_SESSION['success'] = "Consumer deactivated successfully";
        header("Location: consumers.php");
        exit();
    }
}

// Handle search and filtering
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'active';
$connection_filter = isset($_GET['connection_type']) ? $_GET['connection_type'] : '';

$query = "SELECT * FROM kseb_consumers WHERE 1=1 ";
$params = array();

if (!empty($search)) {
    $query .= "AND (consumer_number LIKE ? OR name LIKE ? OR meter_number LIKE ?) ";
    $search_param = "%$search%";
    array_push($params, $search_param, $search_param, $search_param);
}

if (!empty($status_filter)) {
    $query .= "AND status = ? ";
    array_push($params, $status_filter);
}

if (!empty($connection_filter)) {
    $query .= "AND connection_type = ? ";
    array_push($params, $connection_filter);
}

$query .= "ORDER BY name ASC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$consumers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>KSEB - Consumer Management</title>
    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES-->
    <link href="assets/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <!-- DATA TABLE STYLES-->
    <link href="assets/js/dataTables/dataTables.bootstrap.css" rel="stylesheet" />
    <style>
        .kseb-primary {
            background-color: #0056b3 !important;
            border-left: 4px solid #003366 !important;
        }
        .kseb-secondary {
            background-color: #003366 !important;
        }
        .status-active {
            color: #28a745;
            font-weight: bold;
        }
        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }
        .connection-domestic {
            background-color: #e6f7ff;
        }
        .connection-commercial {
            background-color: #fff7e6;
        }
        .connection-industrial {
            background-color: #ffe6e6;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .filter-section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .phase-badge {
            font-size: 0.8em;
            padding: 3px 6px;
            border-radius: 3px;
        }
        .single-phase {
            background-color: #6c757d;
            color: white;
        }
        .three-phase {
            background-color: #17a2b8;
            color: white;
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
                    <li class="active-link">
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
                        <h2>Consumer Management</h2>
                        <h5>Manage electricity consumers for <?php echo htmlspecialchars($office_name); ?></h5>
                    </div>
                </div>
                <!-- /. ROW  -->
                <hr />
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <div class="row">
                                    <div class="col-md-6">
                                        <i class="fa fa-users"></i> Consumer List
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <a href="#addConsumerModal" data-toggle="modal" class="btn btn-primary btn-xs">
                                            <i class="fa fa-plus"></i> Add New Consumer
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="panel-body">
                                <!-- Search and Filter Section -->
                                <div class="filter-section">
                                    <form method="get" action="consumers.php">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <input type="text" class="form-control" name="search" placeholder="Search by name, number..." value="<?php echo htmlspecialchars($search); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <select class="form-control" name="status">
                                                        <option value="">All Statuses</option>
                                                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <select class="form-control" name="connection_type">
                                                        <option value="">All Connection Types</option>
                                                        <option value="domestic" <?php echo $connection_filter == 'domestic' ? 'selected' : ''; ?>>Domestic</option>
                                                        <option value="commercial" <?php echo $connection_filter == 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                                                        <option value="industrial" <?php echo $connection_filter == 'industrial' ? 'selected' : ''; ?>>Industrial</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary btn-block">Filter</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover" id="dataTables-consumers">
                                        <thead>
                                            <tr>
                                                <th>Consumer No.</th>
                                                <th>Name</th>
                                                <th>Address</th>
                                                <th>Meter No.</th>
                                                <th>Connection Type</th>
                                                <th>Phase</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($consumers as $consumer): 
                                                $connection_class = 'connection-' . $consumer['connection_type'];
                                                $phase_class = str_replace('_', '-', $consumer['phase_type']);
                                            ?>
                                            <tr class="<?php echo $connection_class; ?>">
                                                <td><?php echo htmlspecialchars($consumer['consumer_number']); ?></td>
                                                <td><?php echo htmlspecialchars($consumer['name']); ?></td>
                                                <td><?php echo htmlspecialchars($consumer['address']); ?></td>
                                                <td><?php echo htmlspecialchars($consumer['meter_number']); ?></td>
                                                <td><?php echo ucfirst($consumer['connection_type']); ?></td>
                                                <td>
                                                    <span class="phase-badge <?php echo $phase_class; ?>">
                                                        <?php echo str_replace('_', ' ', ucfirst($consumer['phase_type'])); ?>
                                                    </span>
                                                </td>
                                                <td class="status-<?php echo $consumer['status']; ?>">
                                                    <?php echo ucfirst($consumer['status']); ?>
                                                </td>
                                                <td>
                                                    <a href="#editConsumerModal<?php echo $consumer['id']; ?>" data-toggle="modal" class="btn btn-primary btn-xs">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </a>
                                                </td>
                                            </tr>
                                            
                                            <!-- Edit Consumer Modal -->
                                            <div class="modal fade" id="editConsumerModal<?php echo $consumer['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editConsumerModalLabel">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                            <h4 class="modal-title" id="editConsumerModalLabel">Edit Consumer</h4>
                                                        </div>
                                                        <form method="post" action="consumers.php">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="id" value="<?php echo $consumer['id']; ?>">
                                                                
                                                                <div class="form-group">
                                                                    <label>Consumer Number</label>
                                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($consumer['consumer_number']); ?>" readonly>
                                                                </div>
                                                                
                                                                <div class="form-group">
                                                                    <label>Name *</label>
                                                                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($consumer['name']); ?>" required>
                                                                </div>
                                                                
                                                                <div class="form-group">
                                                                    <label>Address *</label>
                                                                    <textarea class="form-control" name="address" required><?php echo htmlspecialchars($consumer['address']); ?></textarea>
                                                                </div>
                                                                
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label>Connection Type *</label>
                                                                            <select class="form-control" name="connection_type" required>
                                                                                <option value="domestic" <?php echo $consumer['connection_type'] == 'domestic' ? 'selected' : ''; ?>>Domestic</option>
                                                                                <option value="commercial" <?php echo $consumer['connection_type'] == 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                                                                                <option value="industrial" <?php echo $consumer['connection_type'] == 'industrial' ? 'selected' : ''; ?>>Industrial</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label>Phase Type *</label>
                                                                            <select class="form-control" name="phase_type" required>
                                                                                <option value="single_phase" <?php echo $consumer['phase_type'] == 'single_phase' ? 'selected' : ''; ?>>Single Phase</option>
                                                                                <option value="three_phase" <?php echo $consumer['phase_type'] == 'three_phase' ? 'selected' : ''; ?>>Three Phase</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="form-group">
                                                                    <label>Meter Number *</label>
                                                                    <input type="text" class="form-control" name="meter_number" value="<?php echo htmlspecialchars($consumer['meter_number']); ?>" required>
                                                                </div>
                                                                
                                                                <div class="form-group">
                                                                    <label>Status *</label>
                                                                    <select class="form-control" name="status" required>
                                                                        <option value="active" <?php echo $consumer['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                                        <option value="inactive" <?php echo $consumer['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                                                <button type="submit" name="update_consumer" class="btn btn-primary">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
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
    
    <!-- Add Consumer Modal -->
    <div class="modal fade" id="addConsumerModal" tabindex="-1" role="dialog" aria-labelledby="addConsumerModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="addConsumerModalLabel">Add New Consumer</h4>
                </div>
                <form method="post" action="consumers.php">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Consumer number *</label>
                            <input type="text" class="form-control" name="counsumer_no" required>
                        </div>
						<div class="form-group">
                            <label>Name *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Address *</label>
                            <textarea class="form-control" name="address" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Connection Type *</label>
                                    <select class="form-control" name="connection_type" required>
                                        <option value="domestic">Domestic</option>
                                        <option value="commercial">Commercial</option>
                                        <option value="industrial">Industrial</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phase Type *</label>
                                    <select class="form-control" name="phase_type" required>
                                        <option value="single_phase">Single Phase</option>
                                        <option value="three_phase">Three Phase</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Meter Number *</label>
                            <input type="text" class="form-control" name="meter_number" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" name="add_consumer" class="btn btn-primary">Add Consumer</button>
                    </div>
                </form>
            </div>
        </div>
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
    <!-- DATA TABLE SCRIPTS -->
    <script src="assets/js/dataTables/jquery.dataTables.js"></script>
    <script src="assets/js/dataTables/dataTables.bootstrap.js"></script>
    <!-- CUSTOM SCRIPTS -->
    <script src="assets/js/custom.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#dataTables-consumers').dataTable({
                "pageLength": 10,
                "order": [[1, 'asc']]
            });
        });
    </script>
</body>
</html>