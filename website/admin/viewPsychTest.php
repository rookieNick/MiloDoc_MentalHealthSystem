<?php
session_start();

// Restrict access to admins only
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION["usertype"] != "a") {
  header("location: ../login.php");
  exit();
}

include("../connection.php");

// Initialize variables to prevent undefined variable errors
$test_id = isset($_GET['test_id']) ? $_GET['test_id'] : '';
$test = null;
$questions = [];

if ($test_id !== '') {
  // Fetch test details including category, name and description.
  $stmt = $database->prepare("SELECT test_category, test_name, test_description FROM test WHERE test_id = ?");
  $stmt->bind_param("s", $test_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $test = $result->fetch_assoc();
  $stmt->close();

  // Fetch related questions for this test.
  $stmt = $database->prepare("SELECT question_id, question_text FROM test_question WHERE test_id = ? ORDER BY question_id");
  $stmt->bind_param("s", $test_id);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>View Psychological Test</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- External CSS files -->
  <link rel="stylesheet" href="../css/animations.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/admin.css">
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* Header styles */
    .dash-body {
      padding: 20px;
    }

    .dash-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 25px;
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      margin-bottom: 30px;
    }

    .dash-header-left {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .btn-back {
      background-color: #f0f0f0;
      color: #555;
      border: none;
      border-radius: 6px;
      padding: 10px 16px;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-back:hover {
      background-color: #e0e0e0;
      color: #333;
    }

    .page-title {
      font-size: 24px;
      margin: 0;
      color: #333;
      font-weight: 600;
    }

    .dash-header-right {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .date-display {
      text-align: right;
    }

    .date-label {
      font-size: 14px;
      color: #888;
      margin: 0 0 4px 0;
    }

    .date-value {
      font-size: 16px;
      font-weight: 600;
      color: #333;
      margin: 0;
    }

    .calendar-icon {
      background-color: #f0f0f0;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .calendar-icon img {
      width: 24px;
      height: 24px;
    }

    /* Main container */
    .test-container {
      padding: 30px;
      animation: fadeIn 0.5s ease;
      max-width: 1100px;
      margin: 0 auto;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Card styling for test details */
    .test-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      padding: 30px;
      margin-bottom: 40px;
      border-top: 4px solid #008f7a;
    }

    .test-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 1px solid #eee;
    }

    .test-header h1 {
      font-size: 28px;
      color: #008f7a;
      margin: 0;
      font-weight: 600;
    }

    .action-buttons {
      display: flex;
      gap: 10px;
    }

    .edit-btn {
      padding: 10px 18px;
      background: #008f7a;
      color: #fff;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      align-items: center;
      gap: 8px;
    }

    .edit-btn:hover {
      background: #007066;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 139, 122, 0.2);
    }

    .test-meta {
      display: flex;
      margin-bottom: 25px;
      align-items: center;
    }

    .test-badge {
      background: #e6f7f4;
      color: #008f7a;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 16px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .test-badge i {
      font-size: 14px;
    }

    .test-description-container {
      margin-top: 20px;
    }

    .section-title {
      font-size: 20px;
      color: #444;
      margin-bottom: 15px;
      font-weight: 600;
    }

    .test-description {
      font-size: 16px;
      color: #555;
      line-height: 1.6;
      white-space: pre-line;
      background: #f9f9f9;
      border-left: 4px solid #008f7a;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 30px;
    }

    /* Questions Section */
    .questions-section {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      padding: 30px;
    }

    .questions-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .questions-header h2 {
      font-size: 24px;
      color: #008f7a;
      margin: 0;
      font-weight: 600;
    }

    .questions-count {
      background: #e6f7f4;
      color: #008f7a;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 16px;
      font-weight: 500;
    }

    /* Questions Table */
    .question-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      margin-bottom: 20px;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .question-table th,
    .question-table td {
      padding: 16px;
      text-align: left;
      font-size: 16px;
    }

    .question-table th {
      background: #f0f7f6;
      color: #008f7a;
      font-weight: 600;
      border-bottom: 2px solid #ddd;
    }

    .question-table td {
      border-bottom: 1px solid #eee;
      background: #fff;
    }

    .question-table tr:last-child td {
      border-bottom: none;
    }

    .question-table tr:hover td {
      background-color: #f5f9f8;
    }

    .question-number {
      font-weight: 600;
      color: #008f7a;
      width: 60px;
      text-align: center;
    }

    .empty-state {
      text-align: center;
      padding: 40px 20px;
      color: #888;
    }

    .empty-state i {
      font-size: 48px;
      color: #ccc;
      margin-bottom: 15px;
    }

    .empty-state p {
      font-size: 18px;
      margin: 0;
    }

    /* Responsive design */
    @media (max-width: 768px) {
      .dash-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
      }

      .dash-header-right {
        width: 100%;
        justify-content: space-between;
      }

      .test-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
      }

      .action-buttons {
        width: 100%;
      }

      .edit-btn {
        width: 100%;
        justify-content: center;
      }

      .questions-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }

      .test-container {
        padding: 15px;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <?php include("adminMenu.php"); ?>
    <div class="dash-body">
      <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;margin-top:25px; ">
        <tr>
          <td width="13%">
            <a href="tests.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                <font class="tn-in-text">Back</font>
              </button></a>
          </td>
          <td>
            <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Psychological Test Manager</p>

          </td>
          <td width="15%">
            <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
              Today's Date
            </p>
            <p class="heading-sub12" style="padding: 0;margin: 0;">
              <?php
              date_default_timezone_set('Asia/Kolkata');
              $today = date('Y-m-d');
              echo $today;
              ?>
            </p>
          </td>
          <td width="10%">
            <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
          </td>


        </tr>
      </table>

      <div class="test-container">
        <?php if ($test): ?>
          <div class="test-card">
            <div class="test-header">
              <h1><?php echo htmlspecialchars($test['test_name']); ?></h1>
              <div class="action-buttons">
                <a href="editPsychTest.php?test_id=<?php echo urlencode($test_id); ?>">
                  <button class="edit-btn">
                    <i class="fas fa-edit"></i> Edit Test
                  </button>
                </a>
              </div>
            </div>

            <div class="test-meta">
              <div class="test-badge">
                <i class="fas fa-folder"></i> <?php echo htmlspecialchars($test['test_category']); ?>
              </div>
            </div>

            <div class="test-description-container">
              <h3 class="section-title">Description</h3>
              <div class="test-description">
                <?php echo nl2br(htmlspecialchars($test['test_description'])); ?>
              </div>
            </div>
          </div>

          <div class="questions-section">
            <div class="questions-header">
              <h2>Test Questions</h2>
              <div class="questions-count">
                <?php echo count($questions); ?> Question<?php echo count($questions) != 1 ? 's' : ''; ?>
              </div>
            </div>

            <?php if (count($questions) > 0): ?>
              <table class="question-table">
                <thead>
                  <tr>
                    <th class="question-number">No.</th>
                    <th>Question Text</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($questions as $index => $q): ?>
                    <tr>
                      <td class="question-number"><?php echo $index + 1; ?></td>
                      <td><?php echo nl2br(htmlspecialchars($q['question_text'])); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php else: ?>
              <div class="empty-state">
                <i class="fas fa-clipboard-question"></i>
                <p>No questions available for this test. Add questions by editing the test.</p>
              </div>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <i class="fas fa-exclamation-circle"></i>
            <p>Test not found or invalid test ID.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>

</html>