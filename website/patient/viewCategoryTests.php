<?php
session_start();

// ---------------------------------------------------------------------
// 1) Check if user is logged in as patient
// ---------------------------------------------------------------------
if (isset($_SESSION["user"])) {
    if (($_SESSION["user"]) == "" || $_SESSION['usertype'] != 'p') {
        header("location: ../login.php");
        exit;
    } else {
        $useremail = $_SESSION["user"];
    }
} else {
    header("location: ../login.php");
    exit;
}

// ---------------------------------------------------------------------
// 2) Include database connection and fetch user details
// ---------------------------------------------------------------------
include("../connection.php");
$userrow   = $database->query("SELECT * FROM patient WHERE pemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid    = $userfetch["pid"];
$username  = $userfetch["pname"];

// ---------------------------------------------------------------------
// 2a) Prepare today's date (in YYYY-MM-DD) for DB
// ---------------------------------------------------------------------
date_default_timezone_set('Asia/Kolkata');
$mysqlDate = date('Y-m-d');  // e.g. "2025-03-08"

// Get category from URL parameter
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Fetch tests for the selected category
$tests = [];
if ($category !== '') {
    $stmt = $database->prepare("SELECT test_id, test_name FROM test WHERE test_category = ?");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $tests[] = $row;
    }
    $stmt->close();
}

// Define category icons - same as in the previous page for consistency
$categoryIcons = [
    'Blood Tests' => 'blood-drop.png',
    'Imaging' => 'imaging.png',
    'Urine Tests' => 'urine-test.png',
    'Cardiac Tests' => 'heart.png',
    'Allergy Tests' => 'allergy.png',
    'General Health' => 'health.png',
    'Diabetes' => 'diabetes.png',
    'Hormonal Tests' => 'hormone.png',
    'Cancer Screening' => 'cancer.png',
    'Genetic Tests' => 'dna.png'
];

// Default icon for categories without specific icon
$defaultIcon = 'lab-test.png';
$iconFile = isset($categoryIcons[$category]) ? $categoryIcons[$category] : $defaultIcon;

// Determine which Font Awesome icon to use as fallback
$faIcon = 'fa-flask';
if (stripos($category, 'blood') !== false) $faIcon = 'fa-tint';
else if (stripos($category, 'heart') !== false || stripos($category, 'cardiac') !== false) $faIcon = 'fa-heartbeat';
else if (stripos($category, 'imaging') !== false || stripos($category, 'scan') !== false) $faIcon = 'fa-x-ray';
else if (stripos($category, 'urine') !== false) $faIcon = 'fa-vial';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($category); ?> Tests - Medical Center</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/animations.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    .page-header {
      background-color: #f8f9fa;
      padding: 30px 0;
      text-align: center;
      border-bottom: 1px solid #e3e3e3;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    
    .category-icon-large {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      background-color: #f1f9f8;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    
    .category-icon-large i {
      font-size: 48px;
      color: #008f7a;
    }
    
    .category-icon-large img {
      max-width: 60px;
      max-height: 60px;
    }
    
    .page-title {
      font-size: 32px;
      color: #2c3e50;
      margin-bottom: 10px;
    }
    
    .page-description {
      color: #7f8c8d;
      font-size: 18px;
      max-width: 800px;
      margin: 0 auto;
    }
    
    .back-link {
      position: absolute;
      left: 30px;
      top: 30px;
      display: flex;
      align-items: center;
      color: #008f7a;
      text-decoration: none;
      font-weight: 500;
    }
    
    .back-link i {
      margin-right: 5px;
    }
    
    .tests-container {
      padding: 40px 20px;
      max-width: 1000px;
      margin: auto;
      animation: transitionIn-Y-bottom 0.5s;
    }
    
    .search-bar {
      max-width: 600px;
      margin: 0 auto 30px;
      position: relative;
    }
    
    .search-input {
      width: 100%;
      padding: 12px 20px;
      padding-left: 45px;
      border-radius: 30px;
      border: 2px solid #ddd;
      font-size: 16px;
      outline: none;
      transition: all 0.3s ease;
    }
    
    .search-input:focus {
      border-color: #008f7a;
    }
    
    .search-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #888;
    }
    
    .tests-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
    }
    
    .test-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.08);
      padding: 25px;
      transition: all 0.3s ease;
      display: flex;
      flex-direction: column;
      border-left: 5px solid #008f7a;
      height: 100%;
    }
    
    .test-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.12);
    }
    
    .test-icon {
      width: 60px;
      height: 60px;
      background-color: #f1f9f8;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 15px;
    }
    
    .test-icon i {
      font-size: 28px;
      color: #008f7a;
    }
    
    .test-name {
      font-size: 20px;
      color: #2c3e50;
      margin-bottom: 15px;
      font-weight: 600;
      line-height: 1.3;
      flex-grow: 1;
    }
    
    .view-btn {
      padding: 10px 20px;
      background-color: #008f7a;
      color: white;
      border: none;
      border-radius: 30px;
      cursor: pointer;
      font-size: 16px;
      transition: all 0.3s ease;
      text-decoration: none;
      text-align: center;
      display: block;
      margin-top: 15px;
    }
    
    .view-btn:hover {
      background-color: #006e5f;
      transform: scale(1.05);
    }
    
    .no-tests {
      text-align: center;
      padding: 40px 0;
      color: #7f8c8d;
      font-size: 18px;
    }
    
    .no-tests i {
      display: block;
      font-size: 50px;
      color: #ddd;
      margin-bottom: 15px;
    }
    
    @media screen and (max-width: 768px) {
      .back-link {
        position: static;
        margin-bottom: 20px;
        justify-content: center;
      }
      
      .page-header {
        padding-top: 20px;
      }
      
      .tests-grid {
        grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
      }
    }
    
    @media screen and (max-width: 480px) {
      .tests-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <?php include(__DIR__ . '/patientMenu.php'); ?>
    <div class="dash-body" style="position: relative;">
      <a href="psychologicalTest.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Categories</a>
      
      <div class="page-header">
        <div class="category-icon-large">
          <?php if (file_exists("../img/icons/" . $iconFile)): ?>
            <img src="../img/icons/<?php echo $iconFile; ?>" alt="<?php echo htmlspecialchars($category); ?>">
          <?php else: ?>
            <i class="fas <?php echo $faIcon; ?>"></i>
          <?php endif; ?>
        </div>
        <h1 class="page-title"><?php echo htmlspecialchars($category); ?></h1>
        <p class="page-description">
          Select from our range of <?php echo strtolower(htmlspecialchars($category)); ?> to view more details or book an appointment.
        </p>
      </div>
      
      <div class="tests-container">
        <?php if (count($tests) > 0): ?>
          <div class="search-bar">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" placeholder="Search tests..." class="search-input">
          </div>
          
          <div class="tests-grid" id="testsGrid">
            <?php foreach ($tests as $test): ?>
              <div class="test-card" data-name="<?php echo htmlspecialchars(strtolower($test['test_name'])); ?>">
                <div class="test-icon">
                  <?php
                    // Assign icon based on test name keywords
                    $testIcon = 'fa-vial';
                    $testName = strtolower($test['test_name']);
                    
                    if (strpos($testName, 'blood') !== false) $testIcon = 'fa-tint';
                    else if (strpos($testName, 'urine') !== false) $testIcon = 'fa-flask';
                    else if (strpos($testName, 'scan') !== false || strpos($testName, 'ray') !== false) $testIcon = 'fa-x-ray';
                    else if (strpos($testName, 'heart') !== false || strpos($testName, 'cardio') !== false) $testIcon = 'fa-heartbeat';
                    else if (strpos($testName, 'brain') !== false) $testIcon = 'fa-brain';
                    else if (strpos($testName, 'ultrasound') !== false) $testIcon = 'fa-wave-square';
                    else if (strpos($testName, 'pregnancy') !== false) $testIcon = 'fa-baby';
                    else if (strpos($testName, 'sugar') !== false || strpos($testName, 'glucose') !== false) $testIcon = 'fa-candy-cane';
                  ?>
                  <i class="fas <?php echo $testIcon; ?>"></i>
                </div>
                <h3 class="test-name"><?php echo htmlspecialchars($test['test_name']); ?></h3>
                <a href="doTestDetails.php?test_id=<?php echo urlencode($test['test_id']); ?>" class="view-btn">View Details</a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="no-tests">
            <i class="fas fa-clipboard-list"></i>
            <p>No tests are currently available for this category.</p>
            <p>Please check back later or select a different category.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Search functionality
      const searchInput = document.getElementById('searchInput');
      const testCards = document.querySelectorAll('.test-card');
      
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        testCards.forEach(card => {
          const testName = card.getAttribute('data-name');
          if (testName.includes(searchTerm)) {
            card.style.display = 'flex';
          } else {
            card.style.display = 'none';
          }
        });
      });
      
      // Add entrance animation for test cards
      testCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
          card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        }, 100 * index);
      });
    });
  </script>
</body>
</html>