<?php
// Database connection
session_start();
require '../../database.php';
// submit appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data
    $hospital_id = htmlspecialchars($_POST['hospital_id']);
    $fullname = htmlspecialchars($_POST['fullname']);
    $email = htmlspecialchars($_POST['email']);
    $date = htmlspecialchars($_POST['date']);
    $specialization = htmlspecialchars($_POST['specialization']);
    $message = htmlspecialchars($_POST['message']);

    try {
        // Prepare SQL query to insert appointment
        $sql = "INSERT INTO appointment (hospital_id, fullname, email, appointment_date, specialization, message)
                VALUES (:hospital_id, :fullname, :email, :appointment_date, :specialization, :message)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':hospital_id', $hospital_id);
        $stmt->bindParam(':fullname', $fullname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':appointment_date', $date);
        $stmt->bindParam(':specialization', $specialization);
        $stmt->bindParam(':message', $message);

        if ($stmt->execute()) {
            // Redirect or show success message
            header("Location: appointment.php");
            exit();
        } else {
            echo "Failed to book appointment.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid Request";
}
?>