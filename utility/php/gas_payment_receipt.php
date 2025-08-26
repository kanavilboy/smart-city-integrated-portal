<?php
session_start();
require '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Get booking ID from URL
$booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : null;

if (!$booking_id) {
    die("No booking specified for receipt.");
}

// Fetch payment details with all related information
$stmt = $conn->prepare("
    SELECT 
        t.*, 
        b.*, 
        c.consumer_no, 
        c.full_name, 
        c.address, 
        c.city, 
        c.state, 
        c.pincode, 
        c.phone,
        cy.type as cylinder_type,
        cy.price as cylinder_price,
        a.dealer_name as agency_name
    FROM gas_payment_transactions t
    JOIN gas_bookings b ON t.booking_id = b.id
    JOIN gas_customers c ON t.customer_id = c.id
    JOIN gas_cylinders cy ON b.cylinder_id = cy.id
    JOIN gas_agencies a ON b.agency_id = a.id
    WHERE t.booking_id = ? AND t.status = 'success'
    ORDER BY t.payment_date DESC LIMIT 1
");
$stmt->execute(array($booking_id));
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    die("Payment record not found or payment was not successful.");
}

// Format dates
$booking_date = date('d M Y H:i:s', strtotime($payment['booking_date']));
$payment_date = date('d M Y H:i:s', strtotime($payment['payment_date']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gas Booking Payment Receipt</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: Arial, sans-serif;
    }
    .receipt-container {
      max-width: 800px;
      margin: 30px auto;
      background: white;
      padding: 30px;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
      border-radius: 10px;
    }
    .receipt-header {
      text-align: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 2px solid #00D9A5;
    }
    .receipt-header h2 {
      color: #00D9A5;
      margin-bottom: 5px;
    }
    .receipt-header p {
      color: #666;
      margin-bottom: 0;
    }
    .receipt-details {
      margin-bottom: 30px;
    }
    .receipt-details h4 {
      color: #00D9A5;
      margin-bottom: 15px;
    }
    .detail-row {
      display: flex;
      margin-bottom: 10px;
    }
    .detail-label {
      font-weight: bold;
      width: 200px;
      color: #555;
    }
    .detail-value {
      flex: 1;
    }
    .bill-breakdown {
      margin-top: 20px;
      border-top: 1px dashed #ddd;
      padding-top: 20px;
    }
    .bill-breakdown h5 {
      color: #00D9A5;
      margin-bottom: 15px;
    }
    .breakdown-row {
      display: flex;
      margin-bottom: 8px;
    }
    .breakdown-label {
      width: 200px;
    }
    .breakdown-value {
      flex: 1;
      text-align: right;
      font-weight: bold;
    }
    .total-row {
      border-top: 2px solid #00D9A5;
      padding-top: 10px;
      margin-top: 10px;
      font-size: 1.1em;
    }
    .receipt-footer {
      margin-top: 30px;
      padding-top: 20px;
      border-top: 2px solid #00D9A5;
      text-align: center;
      color: #666;
      font-size: 0.9em;
    }
    .print-btn {
      display: block;
      margin: 30px auto;
      padding: 10px 30px;
      background-color: #00D9A5;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    .print-btn:hover {
      background-color: #00C095;
    }
    @media print {
      .no-print {
        display: none;
      }
      body {
        background-color: white;
      }
      .receipt-container {
        box-shadow: none;
        margin: 0;
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="receipt-container">
    <div class="receipt-header">
      <h2>GAS BOOKING PAYMENT RECEIPT</h2>
      <p>Official payment confirmation</p>
    </div>
    
    <div class="receipt-details">
      <h4>Payment Information</h4>
      <div class="detail-row">
        <div class="detail-label">Transaction ID:</div>
        <div class="detail-value"><?php echo $payment['id']; ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Booking Date:</div>
        <div class="detail-value"><?php echo $booking_date; ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Payment Date:</div>
        <div class="detail-value"><?php echo $payment_date; ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Payment Method:</div>
        <div class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Payment Status:</div>
        <div class="detail-value"><span style="color: green; font-weight: bold;">Success</span></div>
      </div>
    </div>
    
    <div class="receipt-details">
      <h4>Customer Information</h4>
      <div class="detail-row">
        <div class="detail-label">Consumer Number:</div>
        <div class="detail-value"><?php echo $payment['consumer_no']; ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Name:</div>
        <div class="detail-value"><?php echo $payment['full_name']; ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Phone:</div>
        <div class="detail-value"><?php echo $payment['phone']; ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Address:</div>
        <div class="detail-value">
          <?php echo $payment['address']; ?>, 
          <?php echo $payment['city']; ?>, 
          <?php echo $payment['state']; ?> - 
          <?php echo $payment['pincode']; ?>
        </div>
      </div>
    </div>
    
    <div class="receipt-details">
      <h4>Booking Information</h4>
      <div class="detail-row">
        <div class="detail-label">Booking ID:</div>
        <div class="detail-value"><?php echo $payment['booking_id']; ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Agency:</div>
        <div class="detail-value"><?php echo $payment['agency_name']; ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Booking Status:</div>
        <div class="detail-value"><?php echo ucfirst($payment['status']); ?></div>
      </div>
      
      <div class="bill-breakdown">
        <h5>Order Details</h5>
        <div class="breakdown-row">
          <div class="breakdown-label">Cylinder Type:</div>
          <div class="breakdown-value"><?php echo ucfirst($payment['cylinder_type']); ?></div>
        </div>
        <div class="breakdown-row">
          <div class="breakdown-label">Quantity:</div>
          <div class="breakdown-value"><?php echo $payment['quantity']; ?></div>
        </div>
        <div class="breakdown-row">
          <div class="breakdown-label">Price per Cylinder:</div>
          <div class="breakdown-value">₹<?php echo number_format($payment['cylinder_price'], 2); ?></div>
        </div>
        <div class="breakdown-row total-row">
          <div class="breakdown-label">Total Amount Paid:</div>
          <div class="breakdown-value">₹<?php echo number_format($payment['amount'], 2); ?></div>
        </div>
      </div>
    </div>
    
    <div class="receipt-footer">
      <p>Thank you for your payment. This is an electronically generated receipt, no signature required.</p>
      <p>For any queries, please contact our customer care at 1800-123-4567 or email support@gasbooking.com</p>
    </div>
    
    <button class="print-btn no-print" onclick="window.print()">Print Receipt</button>
    <a href="gas.php" class="no-print" style="display: block; text-align: center; margin-top: 10px;">Back to Gas Booking</a>
  </div>

  <script>
    // Automatically trigger print dialog when page loads (optional)
    // window.onload = function() {
    //   setTimeout(function() {
    //     window.print();
    //   }, 1000);
    // };
  </script>
</body>
</html>