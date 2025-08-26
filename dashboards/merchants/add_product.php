<?php
session_start();
require '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch merchant details based on logged-in user
$stmt = $conn->query("SELECT * FROM merchants WHERE user_id = $user_id");
$merchant = $stmt->fetch(PDO::FETCH_ASSOC);
$merchant_name = $merchant['name'];

if (!$merchant) {
    die("Merchant not found for this user.");
}

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$category = $_POST['category'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $merchant_id = $merchant['id'];

    // Handle image upload
    $image_path = null;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = basename($_FILES['product_image']['name']);
        $file_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $file_path)) {
            $image_path = $file_path;
        } else {
            $message = "Failed to upload image.";
        }
    }

    // Insert product into the database
    $sql = "INSERT INTO products (merchant_id, category, product_name, description, price, product_image) VALUES (:merchant_id, :category, :name, :description, :price, :image_path)";
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(
        ':merchant_id' => $merchant_id,
		':category' => $category,
        ':name' => $name,
        ':description' => $description,
        ':price' => $price,
        ':image_path' => $image_path,
    ));

    if ($stmt->rowCount() > 0) {
        $message = "Product added successfully!";
    } else {
        $message = "Failed to add product.";
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add Product</title>
    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES-->
    <link href="assets/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
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
                        <?php echo $merchant_name; ?>
                    </a>
                </div>
                <span class="logout-spn">
                    <a href="../../login.php" style="color:#fff;">LOGOUT</a>
                </span>
            </div>
        </div>
        <!-- /. NAV TOP  -->
        <nav class="navbar-default navbar-side" role="navigation">
            <div class="sidebar-collapse">
                <ul class="nav" id="main-menu">
                    <li>
                        <a href="merchant_dashboard.php"><i class="fa fa-desktop"></i>Dashboard</a>
                    </li>
                    <li>
                        <a href="edit_profile.php"><i class="fa fa-user"></i>Edit Profile</a>
                    </li>
                    <li>
                        <a href="view_booking.php"><i class="fa fa-calendar"></i>View Bookings</a>
                    </li>
                    <li class="active-link">
                        <a href="add_product.php"><i class="fa fa-plus"></i>Add Product</a>
                    </li>
                    <li>
                        <a href="view_products.php"><i class="fa fa-list"></i>View Products</a>
                    </li>
                    <li>
                        <a href="add_job.php"><i class="fa fa-briefcase"></i>Add Job</a>
                    </li>
                    <li>
                        <a href="job_requests.php"><i class="fa fa-tasks"></i>Job Requests</a>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- /. NAV SIDE  -->
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-lg-12">
                        <h2>Add Product</h2>
                    </div>
                </div>
                <hr />
                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Product Details
                            </div>
                            <div class="panel-body">
                                <?php if (!empty($message)): ?>
                                    <div class="alert alert-info">
                                        <?php echo $message; ?>
                                    </div>
                                <?php endif; ?>
                                <form action="add_product.php" method="POST" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="name">Product Name</label>
                                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter product name" required>
                                    </div>
									<div class="form-group">
                                        <label for="category">Category</label>
                                        <select name="category">
										<option value="random">select</option>
										<option value="food">Food</option>
										<option value="pet">Pet</option>
										<option value="fashion">Fashion</option>
										<option value="gadget">Gadget</option>
									  </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea class="form-control" id="description" name="description" placeholder="Enter product description" rows="3" required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="price">Price</label>
                                        <input type="number" class="form-control" id="price" name="price" placeholder="Enter product price" step="0.01" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="product_image">Product Image</label>
                                        <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                                        <small class="form-text text-muted">Upload a product image (optional).</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Add Product</button>
                                </form>
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
    <div class="footer">
        <div class="row">
            <div class="col-lg-12">
                &copy; 2023 yourdomain.com | Design by: <a href="http://binarytheme.com" style="color:#fff;" target="_blank">www.binarytheme.com</a>
            </div>
        </div>
    </div>
    <!-- /. WRAPPER  -->
    <!-- SCRIPTS -AT THE BOTOM TO REDUCE THE LOAD TIME-->
    <!-- JQUERY SCRIPTS -->
    <script src="assets/js/jquery-1.10.2.js"></script>
    <!-- BOOTSTRAP SCRIPTS -->
    <script src="assets/js/bootstrap.min.js"></script>
    <!-- CUSTOM SCRIPTS -->
    <script src="assets/js/custom.js"></script>
</body>
</html>