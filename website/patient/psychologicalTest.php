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

// Fetch categories from database
$categories = [];
$stmt = $database->prepare("SELECT DISTINCT test_category FROM test");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $categories[] = $row['test_category'];
}
$stmt->close();

// Define category icons - add more as needed
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Phychological Test</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/animations.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    .page-header {
      padding: 30px 0;
      text-align: center;
      margin-bottom: 40px;
      border-bottom: 1px solid #e3e3e3;
    }

    .page-title {
      font-size: 32px;
      color: #2c3e50;
      margin-bottom: 10px;
    }

    .page-description {
      color: #7f8c8d;
      font-size: 18px;
      max-width: 600px;
      margin: 0 auto;
    }

    .category-container {
      padding: 20px;
      animation: transitionIn-Y-bottom 0.5s;
      max-width: 1200px;
      margin: auto;
    }

    .category-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 30px;
    }

    .category-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
      padding: 25px;
      transition: all 0.3s ease;
      border-top: 5px solid #008f7a;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }

    .category-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
    }

    .category-icon {
      width: 80px;
      height: 80px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      background-color: #f1f9f8;
    }

    .category-icon i {
      font-size: 36px;
      color: #008f7a;
    }

    .category-icon img {
      max-width: 50px;
      max-height: 50px;
    }

    .category-title {
      font-size: 22px;
      font-weight: 600;
      color: #2c3e50;
      margin-bottom: 10px;
    }

    .category-btn {
      margin-top: 15px;
      padding: 10px 20px;
      background-color: #008f7a;
      color: white;
      border: none;
      border-radius: 30px;
      cursor: pointer;
      font-size: 16px;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }

    .category-btn:hover {
      background-color: #006e5f;
      transform: scale(1.05);
    }

    .category-description {
      color: #7f8c8d;
      font-size: 14px;
      margin-bottom: 15px;
      line-height: 1.5;
    }

    /* For smaller screens */
    @media screen and (max-width: 768px) {
      .category-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      }

      .page-title {
        font-size: 28px;
      }

      .category-title {
        font-size: 20px;
      }
    }

    /* For even smaller screens */
    @media screen and (max-width: 480px) {
      .category-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <?php include(__DIR__ . '/patientMenu.php'); ?>
    <div class="dash-body">
      <table border="0" width="100%" style="border-spacing:0;margin:0;padding:0;margin-top:25px;">
        <tr>
          <td width="13%">
            <a href="index.php">
              <button class="login-btn btn-primary-soft btn btn-icon-back"
                style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                <font class="tn-in-text">Back</font>
              </button>
            </a>
          </td>
          <td style="text-align: center;">
            <p style="font-size: 23px; font-weight: 600; margin: 0;"></p>
          </td>
          <td width="15%">
            <p style="font-size:14px;color:rgb(119,119,119);padding:0;margin:0;text-align:right;">
              Today's Date
            </p>
            <p class="heading-sub12" style="padding:0;margin:0;">
              <?php
              // Display date as dd-mm-yyyy
              echo date('d-m-Y');
              ?>
            </p>
          </td>
          <td width="10%">
            <button class="btn-label" style="display:flex;justify-content:center;align-items:center;">
              <img src="../img/calendar.svg" width="100%">
            </button>
          </td>
        </tr>
      </table>

      <div class="page-header">
        <h1 class="page-title"> Test Categories</h1>
        <p class="page-description">Browse through our comprehensive range of psychological tests by category. Select a category to view available tests.</p>
      </div>

      <div class="category-container">
        <div class="category-grid">
          <?php foreach ($categories as $category):
            // Get the appropriate icon for this category
            $iconFile = isset($categoryIcons[$category]) ? $categoryIcons[$category] : $defaultIcon;

            // Determine which Font Awesome icon to use as fallback
            $faIcon = 'fa-flask';
            if (stripos($category, 'blood') !== false) $faIcon = 'fa-tint';
            else if (stripos($category, 'heart') !== false || stripos($category, 'cardiac') !== false) $faIcon = 'fa-heartbeat';
            else if (stripos($category, 'imaging') !== false || stripos($category, 'scan') !== false) $faIcon = 'fa-x-ray';
            else if (stripos($category, 'urine') !== false) $faIcon = 'fa-vial';
          ?>
            <div class="category-card">
              <div class="category-icon">
                <?php if (file_exists("../img/icons/" . $iconFile)): ?>
                  <img src="../img/icons/<?php echo $iconFile; ?>" alt="<?php echo htmlspecialchars($category); ?>">
                <?php else: ?>
                  <i class="fas <?php echo $faIcon; ?>"></i>
                <?php endif; ?>
              </div>
              <h3 class="category-title"><?php echo htmlspecialchars($category); ?></h3>
              <p class="category-description">
                <?php
                // Generate simple description based on category name
                echo "View all available " . strtolower(htmlspecialchars($category)) . "  tests ";
                ?>
              </p>
              <a href="viewCategoryTests.php?category=<?php echo urlencode($category); ?>" class="category-btn">View Tests</a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Add some simple animation effects
    document.addEventListener('DOMContentLoaded', function() {
      const cards = document.querySelectorAll('.category-card');

      // Add a slight delay between each card animation
      cards.forEach((card, index) => {
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