<?php
session_start();
require '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$user_id = $_SESSION['user_id'];

// Fetch merchant details based on logged-in user
$stmt = $conn->query("SELECT * FROM merchants WHERE user_id = $user_id");
$merchant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$merchant) {
    die("Merchant not found for this user.");
}

// Check if application ID and status are provided
if (!isset($_GET['id']) || !isset($_GET['status'])) {
    die("Application ID or status not provided.");
}

$application_id = $_GET['id'];
$status = $_GET['status'];

// Update the application status
$stmt = $conn->prepare("UPDATE job_applications SET status = :status WHERE id = :id");
$stmt->execute(array(
    ':status' => $status,
    ':id' => $application_id,
));

if ($stmt->rowCount() > 0) {
    header("Location: job_requests.php");
    exit();
} else {
    die("Failed to update application status.");
}
?>