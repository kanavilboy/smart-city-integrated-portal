<?php
session_start();
require '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Get bill ID from URL
$bill_id = isset($_GET['bill_id']) ? $_GET['bill_id'] : null;

if (!$bill_id) {
    die("No bill specified for receipt.");
}

// Fetch payment details
$stmt = $conn->prepare("
    SELECT t.*, b.*, c.consumer_number, c.name, c.address, c.connection_type, c.phase_type, c.meter_number
    FROM kseb_transactions t
    JOIN kseb_bills b ON t.bill_id = b.id
    JOIN kseb_consumers c ON t.consumer_id = c.id
    WHERE t.bill_id = ? AND t.status = 'success'
    ORDER BY t.payment_date DESC LIMIT 1
");
$stmt->execute(array($bill_id));
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    die("Payment record not found or payment was not successful.");
}

// Format dates
$issue_date = date('d M Y', strtotime($payment['issue_date']));
$due_date = date('d M Y', strtotime($payment['due_date']));
$payment_date = date('d M Y H:i:s', strtotime($payment['payment_date']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KSEB Payment Receipt</title>
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
      border-bottom: 2px solid #0056b3;
    }
    .receipt-header h2 {
      color: #0056b3;
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
      color: #0056b3;
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
      color: #0056b3;
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
      border-top: 2px solid #0056b3;
      padding-top: 10px;
      margin-top: 10px;
      font-size: 1.1em;
    }
    .receipt-footer {
      margin-top: 30px;
      padding-top: 20px;
      border-top: 2px solid #0056b3;
      text-align: center;
      color: #666;
      font-size: 0.9em;
    }
    .print-btn {
      display: block;
      margin: 30px auto;
      padding: 10px 30px;
      background-color: #0056b3;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    .print-btn:hover {
      background-color: #004494;
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
      <h2>KERALA STATE ELECTRICITY BOARD</h2>
      <p>Payment Receipt</p>
    </div>
    
    <div class="receipt-details">
      <h4>Payment Information</h4>
      <div class="detail-row">
        <div class="detail-label">Transaction ID:</div>
        <div class="detail-value"><?php echo $payment['id']; ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Payment Date & Time:</div>
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
      <h4>Consumer Information</h4>
      <div class="detail-row">
        <div class="detail-label">Consumer Number:</div>
        <div class="detail-value"><?php echo $payment['consumer_number']; ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Name:</div>
        <div class="detail-value"><?php echo $payment['name']; ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Address:</div>
        <div class="detail-value"><?php echo $payment['address']; ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Connection Type:</div>
        <div class="detail-value"><?php echo ucfirst($payment['connection_type']); ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Meter Number:</div>
        <div class="detail-value"><?php echo $payment['meter_number']; ?></div>
      </div>
    </div>
    
    <div class="receipt-details">
      <h4>Bill Information</h4>
      <div class="detail-row">
        <div class="detail-label">Bill Number:</div>
        <div class="detail-value"><?php echo $payment['bill_number']; ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Issue Date:</div>
        <div class="detail-value"><?php echo $issue_date; ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Due Date:</div>
        <div class="detail-value"><?php echo $due_date; ?></div>
      </div>
      
      <div class="bill-breakdown">
        <h5>Bill Breakdown</h5>
        <div class="breakdown-row">
          <div class="breakdown-label">Units Consumed:</div>
          <div class="breakdown-value"><?php echo $payment['units_consumed']; ?> units</div>
        </div>
        <div class="breakdown-row">
          <div class="breakdown-label">Energy Charge:</div>
          <div class="breakdown-value">₹<?php echo number_format($payment['energy_charge'], 2); ?></div>
        </div>
        <div class="breakdown-row">
          <div class="breakdown-label">Fixed Charge:</div>
          <div class="breakdown-value">₹<?php echo number_format($payment['fixed_charge'], 2); ?></div>
        </div>
        <div class="breakdown-row">
          <div class="breakdown-label">Electricity Duty:</div>
          <div class="breakdown-value">₹<?php echo number_format($payment['electricity_duty'], 2); ?></div>
        </div>
        <div class="breakdown-row">
          <div class="breakdown-label">Meter Rent:</div>
          <div class="breakdown-value">₹<?php echo number_format($payment['meter_rent'], 2); ?></div>
        </div>
        <div class="breakdown-row total-row">
          <div class="breakdown-label">Total Amount Paid:</div>
          <div class="breakdown-value">₹<?php echo number_format($payment['amount'], 2); ?></div>
        </div>
      </div>
    </div>
    
    <div class="receipt-footer">
      <p>Thank you for your payment. This is an electronically generated receipt, no signature required.</p>
      <p>For any queries, please contact KSEB customer care at 1912 or email support@kseb.in</p>
    </div>
    
    <button class="print-btn no-print" onclick="window.print()">Print Receipt</button>
    <a href="kseb.php" class="no-print" style="display: block; text-align: center; margin-top: 10px;">Back to KSEB Bills</a>
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