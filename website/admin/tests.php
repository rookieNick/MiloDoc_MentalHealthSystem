<?php
session_start();

if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION["usertype"] != "a") {
  header("location: ../login.php");
  exit();
}

include("../connection.php");
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Psychological Test Manager</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- External CSS files -->
  <link rel="stylesheet" href="../css/animations.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/admin.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Do not change dash-body styling */
    .dash-body {
      background: #fff;
      height: 120%;
    }

    /* Modern color palette */
    :root {
      --primary: #2563eb;
      --primary-dark: #1d4ed8;
      --primary-light: #dbeafe;
      --secondary: #f3f4f6;
      --text-dark: #111827;
      --text-light: #6b7280;
      --danger: #ef4444;
      --danger-hover: #dc2626;
      --success: #10b981;
      --border: #e5e7eb;
      --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    /* Global styles */
    * {
      box-sizing: border-box;
    }

    /* Page layout */
    .page-container {
      padding: 1.5rem;
    }

    /* Header */
    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--border);
    }

    .header-left {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .back-button {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      background-color: var(--secondary);
      color: var(--text-dark);
      border-radius: 0.375rem;
      font-weight: 500;
      font-size: 0.875rem;
      text-decoration: none;
      transition: all 0.2s ease;
    }

    .back-button:hover {
      background-color: #e5e7eb;
    }

    .page-title {
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--text-dark);
      margin: 0;
      padding: 0;
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }

    .date-display {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .date-text {
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--text-light);
    }

    .calendar-icon {
      color: var(--primary);
      font-size: 1.25rem;
    }

    /* Action bar */
    .action-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }

    .category-filter {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .filter-label {
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--text-light);
    }

    .filter-select {
      padding: 0.5rem 1rem;
      border: 1px solid var(--border);
      border-radius: 0.375rem;
      font-size: 0.875rem;
      color: var(--text-dark);
      background-color: white;
    }

    .create-test-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.625rem 1.25rem;
      background-color: var(--primary);
      color: white;
      border-radius: 0.375rem;
      font-weight: 500;
      font-size: 0.875rem;
      text-decoration: none;
      transition: all 0.2s ease;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .create-test-btn:hover {
      background-color: var(--primary-dark);
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* Category tabs */
    .category-tabs {
      display: flex;
      gap: 0.5rem;
      border-bottom: 1px solid var(--border);
      margin-bottom: 1.5rem;
      overflow-x: auto;
      padding-bottom: 1px;
    }

    .category-tab {
      padding: 0.75rem 1.25rem;
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--text-light);
      cursor: pointer;
      border-bottom: 2px solid transparent;
      transition: all 0.2s ease;
      white-space: nowrap;
    }

    .category-tab.active {
      color: var(--primary);
      border-bottom: 2px solid var(--primary);
    }

    .category-tab:hover:not(.active) {
      color: var(--text-dark);
      border-bottom-color: var(--border);
    }

    .category-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 1.5rem;
      height: 1.5rem;
      border-radius: 9999px;
      background-color: var(--primary-light);
      color: var(--primary);
      font-size: 0.75rem;
      font-weight: 600;
      margin-left: 0.5rem;
    }

    /* Test cards */
    .tests-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 1.5rem;
    }

    .test-card {
      background-color: white;
      border-radius: 0.5rem;
      box-shadow: var(--card-shadow);
      overflow: hidden;
      transition: all 0.2s ease;
      border: 1px solid var(--border);
    }

    .test-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .test-card-header {
      padding: 1.25rem;
      border-bottom: 1px solid var(--border);
    }

    .test-name {
      font-size: 1rem;
      font-weight: 600;
      color: var(--text-dark);
      margin: 0;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .test-category {
      display: inline-block;
      margin-top: 0.5rem;
      padding: 0.25rem 0.625rem;
      background-color: var(--primary-light);
      color: var(--primary);
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 500;
    }

    .test-card-actions {
      display: flex;
      padding: 0.75rem;
      gap: 0.25rem;
      justify-content: space-between;
    }

    .test-action {
      flex: 1;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.375rem;
      padding: 0.5rem;
      border-radius: 0.375rem;
      font-size: 0.75rem;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.2s ease;
    }

    .view-action {
      color: var(--primary);
      background-color: var(--primary-light);
    }

    .view-action:hover {
      background-color: rgba(37, 99, 235, 0.2);
    }

    .edit-action {
      color: var(--text-dark);
      background-color: var(--secondary);
    }

    .edit-action:hover {
      background-color: #e5e7eb;
    }

    .delete-action {
      color: var(--danger);
      background-color: #fee2e2;
    }

    .delete-action:hover {
      background-color: #fecaca;
    }

    /* Empty state */
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      color: var(--text-light);
      grid-column: 1 / -1;
    }

    .empty-icon {
      font-size: 3rem;
      color: var(--border);
      margin-bottom: 1rem;
    }

    .empty-title {
      font-size: 1.125rem;
      font-weight: 500;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
    }

    .empty-message {
      font-size: 0.875rem;
      color: var(--text-light);
      max-width: 300px;
      margin: 0 auto;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .tests-container {
        grid-template-columns: 1fr;
      }

      .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
      }

      .header-right {
        width: 100%;
        justify-content: space-between;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <?php include(__DIR__ . '/adminMenu.php'); ?>
    <div class="dash-body">
      <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;margin-top:25px; ">
        <tr>
          <td width="13%">
            <a href="appointment.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                <font class="tn-in-text">Back</font>
              </button></a>
          </td>
          <td style="text-align: center;">
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
      <div class="page-container">
        <!-- Action Bar with functional filter -->
        <div class="action-bar">
          <div class="category-filter">
            <span class="filter-label">Filter by:</span>
            <select class="filter-select" id="categoryFilter">
              <option value="all">All Categories</option>
              <?php
              // Categories array
              $categories = ["Stress", "Anxiety", "Depression", "Aggression", "Self-Esteem"];
              foreach ($categories as $category) {
                echo "<option value=\"" . htmlspecialchars($category) . "\">" . htmlspecialchars($category) . "</option>";
              }
              ?>
            </select>
          </div>
          <a href="createPsychTest.php" class="create-test-btn">
            <i class="fas fa-plus"></i>
            <span>Create New Test</span>
          </a>
        </div>

        <!-- Category Tabs - now with data attributes for filtering -->
        <div class="category-tabs">
          <div class="category-tab active" data-category="all">All Tests
            <?php
            // Get total count of tests
            $totalStmt = $database->prepare("SELECT COUNT(*) FROM test");
            $totalStmt->execute();
            $totalResult = $totalStmt->get_result();
            $totalCount = $totalResult->fetch_row()[0];
            $totalStmt->close();
            echo "<span class='category-badge'>{$totalCount}</span>";
            ?>
          </div>
          <?php
          // Categories array with counts and data attributes
          $categories = ["Stress", "Anxiety", "Depression", "Aggression", "Self-Esteem"];

          foreach ($categories as $category) {
            $stmt = $database->prepare("SELECT COUNT(*) FROM test WHERE test_category = ?");
            $stmt->bind_param("s", $category);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_row()[0];
            $stmt->close();

            echo "<div class='category-tab' data-category='" . htmlspecialchars($category) . "'>{$category} <span class='category-badge'>{$count}</span></div>";
          }
          ?>
        </div>

        <!-- Test Cards -->
        <div class="tests-container" id="testsContainer">
          <?php
          // Get all tests
          $stmt = $database->prepare("SELECT test_id, test_name, test_category FROM test ORDER BY test_category, test_name");
          $stmt->execute();
          $result = $stmt->get_result();

          if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              echo "<div class='test-card' data-category='" . htmlspecialchars($row['test_category']) . "'>";
              echo "<div class='test-card-header'>";
              echo "<h3 class='test-name'>" . htmlspecialchars($row['test_name']) . "</h3>";
              echo "<span class='test-category'>" . htmlspecialchars($row['test_category']) . "</span>";
              echo "</div>";
              echo "<div class='test-card-actions'>";
              echo "<a href='viewPsychTest.php?test_id=" . urlencode($row['test_id']) . "' class='test-action view-action'>";
              echo "<i class='fas fa-eye'></i> <span>View</span>";
              echo "</a>";
              echo "<a href='editPsychTest.php?test_id=" . urlencode($row['test_id']) . "' class='test-action edit-action'>";
              echo "<i class='fas fa-edit'></i> <span>Edit</span>";
              echo "</a>";
              echo "<a href='deletePsychTest.php?test_id=" . urlencode($row['test_id']) . "' class='test-action delete-action' onclick=\"return confirm('Are you sure you want to delete this test?');\">";
              echo "<i class='fas fa-trash-alt'></i> <span>Delete</span>";
              echo "</a>";
              echo "</div>";
              echo "</div>";
            }
          } else {
            showEmptyState();
          }
          $stmt->close();

          function showEmptyState()
          {
            echo "<div class='empty-state'>";
            echo "<i class='fas fa-clipboard-list empty-icon'></i>";
            echo "<h3 class='empty-title'>No Tests Found</h3>";
            echo "<p class='empty-message'>Create your first psychological test by clicking the 'Create New Test' button.</p>";
            echo "</div>";
          }
          ?>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Script to make tabs and filter functionality work
    document.addEventListener('DOMContentLoaded', function() {
      const tabs = document.querySelectorAll('.category-tab');
      const filterSelect = document.getElementById('categoryFilter');
      const testCards = document.querySelectorAll('.test-card');
      const testsContainer = document.getElementById('testsContainer');

      // Function to filter tests
      function filterTests(category) {
        let visibleCount = 0;

        testCards.forEach(card => {
          const cardCategory = card.getAttribute('data-category');

          if (category === 'all' || cardCategory === category) {
            card.style.display = '';
            visibleCount++;
          } else {
            card.style.display = 'none';
          }
        });

        // Show empty state if no tests are visible
        const existingEmptyState = document.querySelector('.empty-state');
        if (visibleCount === 0 && !existingEmptyState) {
          const emptyState = document.createElement('div');
          emptyState.className = 'empty-state';
          emptyState.innerHTML = `
            <i class="fas fa-filter empty-icon"></i>
            <h3 class="empty-title">No Tests Found</h3>
            <p class="empty-message">No tests match the selected filter criteria.</p>
          `;
          testsContainer.appendChild(emptyState);
        } else if (visibleCount > 0 && existingEmptyState) {
          existingEmptyState.remove();
        }
      }

      // Tab click handler
      tabs.forEach(tab => {
        tab.addEventListener('click', function() {
          // Remove active class from all tabs
          tabs.forEach(t => t.classList.remove('active'));

          // Add active class to clicked tab
          this.classList.add('active');

          // Get category from data attribute
          const category = this.getAttribute('data-category');

          // Update dropdown to match selected tab
          filterSelect.value = category;

          // Filter tests
          filterTests(category);
        });
      });

      // Dropdown change handler
      filterSelect.addEventListener('change', function() {
        const category = this.value;

        // Update tabs to match selected dropdown
        tabs.forEach(tab => {
          if (tab.getAttribute('data-category') === category) {
            tab.classList.add('active');
          } else {
            tab.classList.remove('active');
          }
        });

        // Filter tests
        filterTests(category);
      });
    });
  </script>
</body>

</html>