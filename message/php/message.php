<?php
session_start();
require '../../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Database connection
$conn = new PDO("mysql:host=localhost;dbname=smartcity", "root", "");

$log_id = $_SESSION['user_id'];
$user_id = null; // Initialize user_id

try {
    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT id FROM personal_users WHERE user_id = ?");
    $stmt->execute(array($log_id));

    // Fetch the result
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header("Location: ../../login.php");
        exit();
    }
    
    $user_id = $user['id'];
    $customer_id = $user_id;

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Fetch all messages for the current user
$query = $conn->prepare("SELECT * FROM message WHERE customer_id = ? ORDER BY sent_date DESC");
$query->execute(array($user_id));
$messages = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages - Smart City</title>
  <link rel="stylesheet" href="../assets/css/maicons.css">
  <link rel="stylesheet" href="../assets/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="../assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    .messages-hero {
      background-color: #f8f9fa;
      padding: 3rem 0;
      margin-bottom: 3rem;
    }
    
    .inbox-container { 
      display: flex; 
      height: 70vh; 
      border-radius: 8px; 
      overflow: hidden; 
      box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
      background-color: #fff; 
      margin-bottom: 30px;
    }
    
    /* Left Side - Sender List */
    .sender-list { 
      width: 350px; 
      background-color: #343a40; 
      color: #fff; 
      overflow-y: auto; 
      padding: 20px; 
    }
    
    .sender-heading { 
      font-size: 1.25rem; 
      font-weight: 600; 
      color: #fff; 
      margin-bottom: 20px; 
      padding-bottom: 10px; 
      border-bottom: 1px solid #495057;
    }
    
    .sender-item { 
      padding: 15px; 
      border-bottom: 1px solid #495057; 
      cursor: pointer; 
      transition: background-color 0.3s; 
      border-radius: 4px; 
    }
    
    .sender-item:hover, 
    .sender-item.active { 
      background-color: #495057; 
    }
    
    .sender-name { 
      font-weight: 600; 
      color: #f8f9fa; 
    }
    
    /* Right Side - Chat Display */
    .chat-area { 
      flex: 3; 
      background-color: #fff; 
      display: flex; 
      flex-direction: column; 
    }
    
    .chat-header { 
      padding: 20px; 
      background-color: #00d289; 
      color: #fff; 
      font-size: 1.1rem; 
      font-weight: 600; 
    }
    
    .chat-content { 
      flex: 1; 
      padding: 20px; 
      overflow-y: auto;
    }
    
    .chat-time { 
      font-size: 0.875rem; 
      color: #6c757d; 
      margin-top: 15px; 
    }
    
    .empty-message { 
      padding: 30px; 
      color: #6c757d; 
      text-align: center;
    }
  </style>
</head>

<body>
  <!-- Header -->
  <header>
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm">
      <div class="container">
        <a class="navbar-brand" href="../../smartcity.php"><span class="text-primary">Smart</span>-City</a>  

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupport">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupport">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item">
              <a class="nav-link" href="../../smartcity.php">Home</a>
            </li>
			<li class="nav-item">
              <a class="nav-link active" href="message.php">Message</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../../marketplace/php/marketplace.php">Market</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../../health/php/health.php">Health</a>
            </li>
			<li class="nav-item">
              <a class="nav-link" href="../../job/php/job.php">Jobs</a>
            </li>
			<li class="nav-item">
              <a class="nav-link" href="../../education/php/education.php">Education</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../../utility/php/utility.php">Public services</a>
            </li>
            <li class="nav-item">
              <a class="btn btn-primary ml-lg-3" href="login.php">Logout</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

 

  <!-- Messages Section -->
  <section class="mb-5">
    <div class="container">
      <div class="inbox-container">
        <!-- Left Sidebar: Sender List -->
        <div class="sender-list" id="senderList">
          <div class="sender-heading">All Messages</div>
          <?php if (!empty($messages)) : ?>
            <?php foreach ($messages as $index => $msg) : ?>
              <div class="sender-item" 
                   data-message="<?php echo htmlspecialchars($msg['message']); ?>"
                   data-sender="<?php echo htmlspecialchars($msg['sender']); ?>"
                   data-time="<?php echo date('M d, Y h:i A', strtotime($msg['sent_date'])); ?>">
                <div class="sender-name"><?php echo htmlspecialchars($msg['sender']); ?></div>
                <small class="text-muted"><?php echo date('M d, h:i A', strtotime($msg['sent_date'])); ?></small>
              </div>
            <?php endforeach; ?>
          <?php else : ?>
            <div class="empty-message">No messages available.</div>
          <?php endif; ?>
        </div>

        <!-- Right Chat Area -->
        <div class="chat-area">
          <div class="chat-header">Message Details</div>
          <div class="chat-content" id="chatContent">
            <div class="empty-message">Select a message to view details</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="page-footer">
    <div class="container">
      <div class="row px-md-3">
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>Company</h5>
          <ul class="footer-menu">
            <li><a href="#">About Us</a></li>
            <li><a href="#">Career</a></li>
            <li><a href="#">Merchants</a></li>
            <li><a href="#">Protection</a></li>
          </ul>
        </div>
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>More</h5>
          <ul class="footer-menu">
            <li><a href="#">Terms & Conditions</a></li>
            <li><a href="#">Privacy Policy</a></li>
            <li><a href="#">Advertise</a></li>
            <li><a href="#">Join Us</a></li>
          </ul>
        </div>
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>Our Services</h5>
          <ul class="footer-menu">
            <li><a href="#">Marketplace</a></li>
            <li><a href="#">Health Services</a></li>
            <li><a href="#">Public Services</a></li>
          </ul>
        </div>
        <div class="col-sm-6 col-lg-3 py-3">
          <h5>Contact</h5>
          <p class="footer-link mt-2">JK Smart City</p>
          <a href="#" class="footer-link">jkmarket@gmail.com</a>
          <h5 class="mt-3">Social Media</h5>
          <div class="footer-sosmed mt-3">
            <a href="#"><span class="mai-logo-facebook-f"></span></a>
            <a href="#"><span class="mai-logo-twitter"></span></a>
            <a href="#"><span class="mai-logo-google-plus-g"></span></a>
            <a href="#"><span class="mai-logo-instagram"></span></a>
            <a href="#"><span class="mai-logo-linkedin"></span></a>
          </div>
        </div>
      </div>
      <hr>
      <p id="copyright">Copyright &copy; 2025 <a href="#">JK Smart City</a>. All rights reserved.</p>
    </div>
  </footer>

  <script src="../assets/js/jquery-3.5.1.min.js"></script>
  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  <script>
    $(document).ready(function() {
      // Handle sender click to load message
      $('.sender-item').click(function() {
        // Remove active class from all
        $('.sender-item').removeClass('active');
        // Add active class to clicked item
        $(this).addClass('active');
        
        // Get message details
        const message = $(this).data('message');
        const sender = $(this).data('sender');
        const time = $(this).data('time');
        
        // Update chat content
        $('#chatContent').html(`
          <h4>${sender}</h4>
          <p>${message}</p>
          <div class="chat-time">${time}</div>
        `);
      });
      
      // Auto-select first message if available
      if ($('.sender-item').length > 0) {
        $('.sender-item').first().click();
      }
    });
  </script>
</body>
</html>