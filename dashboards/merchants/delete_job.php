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

// Check if job ID is provided
if (!isset($_POST['job_id'])) {
    die("Job ID not provided.");
}

$job_id = $_POST['job_id'];

// Delete the job vacancy from the database
$stmt = $conn->prepare("DELETE FROM job_vacancies WHERE id = :id AND merchant_id = :merchant_id");
$stmt->execute(array(
    ':id' => $job_id,
    ':merchant_id' => $merchant['id'],
));

if ($stmt->rowCount() > 0) {
    echo 'success';
} else {
    echo 'failed';
}
?>