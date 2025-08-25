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
// 3) Get filter parameters
// ---------------------------------------------------------------------
$timeFilter = isset($_GET['time']) ? $_GET['time'] : 'all';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : 'all';

// ---------------------------------------------------------------------
// 4) Prepare filter conditions
// ---------------------------------------------------------------------
$whereConditions = ["pt.pid = ?"];
$params = [$userid];
$paramTypes = "i";

// Time filter
if ($timeFilter == '1week') {
    $whereConditions[] = "pt.completed_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
} elseif ($timeFilter == '1month') {
    $whereConditions[] = "pt.completed_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
}

// Category filter - using test_category instead of category
if ($categoryFilter != 'all') {
    $whereConditions[] = "t.test_category = ?";
    $params[] = $categoryFilter;
    $paramTypes .= "s";
}

// ---------------------------------------------------------------------
// 5) Fetch Test History with filters - correct field names
// ---------------------------------------------------------------------
$query = "SELECT pt.patient_test_id, t.test_name, t.test_category, pt.completed_at 
          FROM patient_test pt 
          JOIN test t ON pt.test_id = t.test_id
          WHERE " . implode(" AND ", $whereConditions) . " 
          ORDER BY pt.completed_at DESC";

$historyStmt = $database->prepare($query);
$historyStmt->bind_param($paramTypes, ...$params);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();
$historyStmt->close();

// ---------------------------------------------------------------------
// 6) Fetch distinct categories for filter dropdown - correct field name
// ---------------------------------------------------------------------
$categoriesStmt = $database->prepare("SELECT DISTINCT t.test_category 
                                     FROM test t 
                                     JOIN patient_test pt ON t.test_id = pt.test_id
                                     WHERE pt.pid = ?");
$categoriesStmt->bind_param("i", $userid);
$categoriesStmt->execute();
$categoriesResult = $categoriesStmt->get_result();
$categoriesStmt->close();

// Store categories in array
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row['test_category'];
}

// ---------------------------------------------------------------------
// 7) Group tests by category - using test_category instead of category
// ---------------------------------------------------------------------
$groupedTests = [];
$historyResult->data_seek(0);
while ($row = $historyResult->fetch_assoc()) {
    $category = $row['test_category'];
    if (!isset($groupedTests[$category])) {
        $groupedTests[$category] = [];
    }
    $groupedTests[$category][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test History - <?php echo htmlspecialchars($username); ?></title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #00695c;
            --secondary-color: #4db6ac;
            --hover-color: #e0f2f1;
            --light-color: #f5f5f5;
            --text-dark: #333333;
            --text-light: #666666;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --border-radius: 12px;

            /* Category colors */
            --category-Blood-color: #e57373;
            --category-Cardiac-color: #f06292;
            --category-Imaging-color: #64b5f6;
            --category-Physical-color: #81c784;
            --category-General-color: #9575cd;
        }

        body {
            background-color: #f9fafb;
            color: var(--text-dark);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .history-container {
            max-width: 900px;
            margin: 30px auto;
            background: #ffffff;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--light-color);
            padding-bottom: 15px;
        }

        .history-header h1 {
            color: var(--primary-color);
            font-size: 28px;
            margin: 0;
        }

        .filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 14px;
            color: var(--text-light);
        }

        .filter-select {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
            background-color: white;
            font-size: 14px;
            color: var(--text-dark);
            min-width: 150px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            padding: 20px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
            margin: 10px 0 5px;
        }

        .stat-card .label {
            color: var(--text-light);
            font-size: 14px;
        }

        .category-section {
            margin-bottom: 40px;
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }

        .category-indicator {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background-color: var(--category-General-color);
        }

        .category-name {
            font-size: 20px;
            color: var(--text-dark);
            margin: 0;
        }

        .category-count {
            margin-left: auto;
            background-color: #f1f1f1;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 14px;
            color: var(--text-light);
        }

        .history-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .test-item {
            padding: 20px;
            background-color: white;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--secondary-color);
            box-shadow: var(--shadow);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .test-item:hover {
            background-color: var(--hover-color);
            transform: translateX(5px);
        }

        .test-info {
            flex: 1;
        }

        .test-info h3 {
            margin: 0 0 10px 0;
            color: var(--primary-color);
        }

        .test-meta {
            color: var(--text-light);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .test-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .test-action {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .test-action:hover {
            background-color: var(--secondary-color);
            transform: rotate(45deg);
        }

        .empty-state {
            text-align: center;
            padding: 40px 0;
        }

        .empty-state i {
            font-size: 60px;
            color: var(--secondary-color);
            margin-bottom: 20px;
            opacity: 0.7;
        }

        .empty-state p {
            color: var(--text-light);
            font-size: 18px;
            margin-bottom: 20px;
        }

        .btn-take-test {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-take-test:hover {
            background-color: var(--secondary-color);
            transform: scale(1.05);
        }

        /* Category-specific styling */
        .category-Blood .category-indicator,
        .category-Blood .test-item {
            border-left-color: var(--category-Blood-color);
        }

        .category-Cardiac .category-indicator,
        .category-Cardiac .test-item {
            border-left-color: var(--category-Cardiac-color);
        }

        .category-Imaging .category-indicator,
        .category-Imaging .test-item {
            border-left-color: var(--category-Imaging-color);
        }

        .category-Physical .category-indicator,
        .category-Physical .test-item {
            border-left-color: var(--category-Physical-color);
        }

        .category-General .category-indicator,
        .category-General .test-item {
            border-left-color: var(--category-General-color);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .history-container {
                padding: 20px;
                margin: 20px 10px;
            }

            .filter-container {
                flex-direction: column;
                gap: 10px;
            }

            .stats-container {
                grid-template-columns: 1fr 1fr;
            }

            .test-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .test-action {
                margin-top: 15px;
                align-self: flex-end;
            }
        }

        @media (max-width: 480px) {
            .stats-container {
                grid-template-columns: 1fr;
            }

            .history-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .test-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
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
                    <td width="5%">
                        <a href="testDashboard.php">
                            <button class="login-btn btn-primary-soft btn" style="padding-top:11px;padding-bottom:11px;margin-right:20px;width:125px">
                                <font class="tn-in-text">Dashboard</font>
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
            <div class="history-container">
                <div class="history-header">
                    <h1>Test History</h1>
                </div>

                <!-- Filter options -->
                <form class="filter-container" method="get" action="patientTestHistory.php">
                    <div class="filter-group">
                        <label for="time-filter">Time Period</label>
                        <select id="time-filter" name="time" class="filter-select" onchange="this.form.submit()">
                            <option value="all" <?php echo $timeFilter == 'all' ? 'selected' : ''; ?>>All Time</option>
                            <option value="1week" <?php echo $timeFilter == '1week' ? 'selected' : ''; ?>>Within 1 Week</option>
                            <option value="1month" <?php echo $timeFilter == '1month' ? 'selected' : ''; ?>>Within 1 Month</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="category-filter">Test Category</label>
                        <select id="category-filter" name="category" class="filter-select" onchange="this.form.submit()">
                            <option value="all" <?php echo $categoryFilter == 'all' ? 'selected' : ''; ?>>All Categories</option>
                            <?php foreach ($categories as $category) { ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $categoryFilter == $category ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </form>

                <div class="stats-container">
                    <div class="stat-card">
                        <i class="fas fa-clipboard-check"></i>
                        <div class="value"><?php echo $historyResult->num_rows; ?></div>
                        <div class="label">Total Tests</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-calendar-week"></i>
                        <div class="value">
                            <?php
                            // Calculate tests in the last week
                            $lastWeekCount = 0;
                            $oneWeekAgo = date('Y-m-d', strtotime('-7 days'));
                            $historyResult->data_seek(0);
                            while ($row = $historyResult->fetch_assoc()) {
                                if (strtotime($row['completed_at']) >= strtotime($oneWeekAgo)) {
                                    $lastWeekCount++;
                                }
                            }
                            echo $lastWeekCount;
                            ?>
                        </div>
                        <div class="label">Last 7 Days</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-calendar-alt"></i>
                        <div class="value">
                            <?php
                            // Calculate tests in the last month
                            $lastMonthCount = 0;
                            $oneMonthAgo = date('Y-m-d', strtotime('-30 days'));
                            $historyResult->data_seek(0);
                            while ($row = $historyResult->fetch_assoc()) {
                                if (strtotime($row['completed_at']) >= strtotime($oneMonthAgo)) {
                                    $lastMonthCount++;
                                }
                            }
                            echo $lastMonthCount;
                            ?>
                        </div>
                        <div class="label">Last 30 Days</div>
                    </div>
                </div>

                <?php if ($historyResult->num_rows > 0) { ?>
                    <?php
                    // If no categories found in filter, show message
                    if (empty($groupedTests)) {
                        echo "<p>No tests found matching your filters.</p>";
                    } else {
                        // Display each category
                        foreach ($groupedTests as $category => $tests) {
                    ?>
                            <div class="category-section category-<?php echo htmlspecialchars($category); ?>">
                                <div class="category-header">
                                    <div class="category-indicator"></div>
                                    <h2 class="category-name"><?php echo htmlspecialchars($category); ?></h2>
                                    <span class="category-count"><?php echo count($tests); ?> tests</span>
                                </div>

                                <div class="history-list">
                                    <?php foreach ($tests as $test) {
                                        $testDate = date('M d, Y', strtotime($test['completed_at']));
                                        $testTime = date('h:i A', strtotime($test['completed_at']));
                                    ?>
                                        <div class="test-item" onclick="window.location.href='testResult.php?patient_test_id=<?php echo $test['patient_test_id']; ?>'">
                                            <div class="test-info">
                                                <h3><?php echo htmlspecialchars($test['test_name']); ?></h3>
                                                <div class="test-meta">
                                                    <span><i class="far fa-calendar"></i> <?php echo $testDate; ?></span>
                                                    <span><i class="far fa-clock"></i> <?php echo $testTime; ?></span>
                                                </div>
                                            </div>
                                            <div class="test-action">
                                                <i class="fas fa-arrow-right"></i>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                    <?php
                        }
                    }
                    ?>
                <?php } else { ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard"></i>
                        <p>No test history available with current filters.</p>
                        <?php if ($timeFilter != 'all' || $categoryFilter != 'all') { ?>
                            <a href="patientTestHistory.php" class="btn-take-test">Show All Tests</a>
                        <?php } else { ?>
                            <a href="psychologicalTest.php" class="btn-take-test">Take Your First Test</a>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</body>

</html>