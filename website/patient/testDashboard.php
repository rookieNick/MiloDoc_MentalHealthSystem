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
$userrow = $database->query("SELECT * FROM patient WHERE pemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["pid"];
$username = $userfetch["pname"];

// ---------------------------------------------------------------------
// 3) Fetch all completed tests for this patient across categories
// ---------------------------------------------------------------------
$testHistorySql = "
    SELECT 
        pt.patient_test_id,
        pt.test_id,
        pt.completed_at,
        t.test_name,
        t.test_category
    FROM 
        patient_test pt
    JOIN 
        test t ON pt.test_id = t.test_id
    WHERE 
        pt.pid = ? AND
        pt.completed_at IS NOT NULL
    ORDER BY 
        pt.completed_at DESC
";

$testHistoryStmt = $database->prepare($testHistorySql);
$testHistoryStmt->bind_param("i", $userid);
$testHistoryStmt->execute();
$testHistoryResult = $testHistoryStmt->get_result();

$testHistory = [];
$categories = [];
$categoryTests = [];

while ($row = $testHistoryResult->fetch_assoc()) {
    $testHistory[] = $row;

    $category = $row['test_category'];
    if (!in_array($category, $categories)) {
        $categories[] = $category;
    }

    if (!isset($categoryTests[$category])) {
        $categoryTests[$category] = [];
    }
    $categoryTests[$category][] = $row;
}

// ---------------------------------------------------------------------
// 4) Get level stats for each category
// ---------------------------------------------------------------------
$categoryData = [];

foreach ($categories as $category) {
    $levelCountsSql = "
        SELECT 
            level, 
            COUNT(*) as count
        FROM (
            SELECT 
                pt.patient_test_id,
                CASE 
                    WHEN (SUM(pa.answer_value) / (COUNT(pa.answer_value) * 5)) * 100 <= 25 THEN 'Level 1'
                    WHEN (SUM(pa.answer_value) / (COUNT(pa.answer_value) * 5)) * 100 <= 50 THEN 'Level 2'
                    WHEN (SUM(pa.answer_value) / (COUNT(pa.answer_value) * 5)) * 100 <= 75 THEN 'Level 3'
                    ELSE 'Level 4'
                END as level
            FROM 
                patient_test pt
            JOIN 
                test t ON pt.test_id = t.test_id
            JOIN 
                patient_answer pa ON pt.patient_test_id = pa.patient_test_id
            WHERE 
                pt.pid = ? AND
                t.test_category = ? AND
                pt.completed_at IS NOT NULL
            GROUP BY 
                pt.patient_test_id
        ) as levels
        GROUP BY 
            level
        ORDER BY 
            level
    ";

    $levelCountsStmt = $database->prepare($levelCountsSql);
    $levelCountsStmt->bind_param("is", $userid, $category);
    $levelCountsStmt->execute();
    $levelCountsResult = $levelCountsStmt->get_result();

    $levelCounts = [
        'Level 1' => 0,
        'Level 2' => 0,
        'Level 3' => 0,
        'Level 4' => 0
    ];

    while ($row = $levelCountsResult->fetch_assoc()) {
        $levelCounts[$row['level']] = (int)$row['count'];
    }

    $categoryData[$category] = $levelCounts;
}

// ---------------------------------------------------------------------
// 5) Get trend data for each category (last 5 tests)
// ---------------------------------------------------------------------
$trendData = [];

foreach ($categories as $category) {
    $trendSql = "
        SELECT 
            pt.patient_test_id,
            pt.completed_at,
            t.test_name,
            (SUM(pa.answer_value) / (COUNT(pa.answer_value) * 5)) * 100 as score_percentage
        FROM 
            patient_test pt
        JOIN 
            test t ON pt.test_id = t.test_id
        JOIN 
            patient_answer pa ON pt.patient_test_id = pa.patient_test_id
        WHERE 
            pt.pid = ? AND
            t.test_category = ? AND
            pt.completed_at IS NOT NULL
        GROUP BY 
            pt.patient_test_id
        ORDER BY 
            pt.completed_at DESC
        LIMIT 5
    ";

    $trendStmt = $database->prepare($trendSql);
    $trendStmt->bind_param("is", $userid, $category);
    $trendStmt->execute();
    $trendResult = $trendStmt->get_result();

    $trend = [];

    while ($row = $trendResult->fetch_assoc()) {
        $dateObj = new DateTime($row['completed_at']);
        $trend[] = [
            'date' => $dateObj->format('M d'),
            'test_name' => $row['test_name'],
            'score' => round($row['score_percentage'], 1)
        ];
    }

    // Reverse to get chronological order
    $trend = array_reverse($trend);

    if (count($trend) > 0) {
        $trendData[$category] = $trend;
    }
}

// ---------------------------------------------------------------------
// 6) Get most recent test result for each category
// ---------------------------------------------------------------------
$recentResults = [];

foreach ($categories as $category) {
    $recentSql = "
        SELECT 
            pt.patient_test_id,
            pt.completed_at,
            t.test_name,
            t.test_id,
            (SUM(pa.answer_value) / (COUNT(pa.answer_value) * 5)) * 100 as score_percentage,
            CASE 
                WHEN (SUM(pa.answer_value) / (COUNT(pa.answer_value) * 5)) * 100 <= 25 THEN 'Minimal'
                WHEN (SUM(pa.answer_value) / (COUNT(pa.answer_value) * 5)) * 100 <= 50 THEN 'Mild'
                WHEN (SUM(pa.answer_value) / (COUNT(pa.answer_value) * 5)) * 100 <= 75 THEN 'Moderate'
                ELSE 'Significant'
            END as severity_text,
            CASE 
                WHEN (SUM(pa.answer_value) / (COUNT(pa.answer_value) * 5)) * 100 <= 25 THEN '#4caf50'
                WHEN (SUM(pa.answer_value) / (COUNT(pa.answer_value) * 5)) * 100 <= 50 THEN '#8bc34a'
                WHEN (SUM(pa.answer_value) / (COUNT(pa.answer_value) * 5)) * 100 <= 75 THEN '#ff9800'
                ELSE '#f44336'
            END as severity_color
        FROM 
            patient_test pt
        JOIN 
            test t ON pt.test_id = t.test_id
        JOIN 
            patient_answer pa ON pt.patient_test_id = pa.patient_test_id
        WHERE 
            pt.pid = ? AND
            t.test_category = ? AND
            pt.completed_at IS NOT NULL
        GROUP BY 
            pt.patient_test_id
        ORDER BY 
            pt.completed_at DESC
        LIMIT 1
    ";

    $recentStmt = $database->prepare($recentSql);
    $recentStmt->bind_param("is", $userid, $category);
    $recentStmt->execute();
    $recentResult = $recentStmt->get_result();

    if ($row = $recentResult->fetch_assoc()) {
        $dateObj = new DateTime($row['completed_at']);
        $row['formatted_date'] = $dateObj->format('M d, Y');
        $recentResults[$category] = $row;
    }
}

// ---------------------------------------------------------------------
// 7) Get total tests count and prepare data for other calculations
// ---------------------------------------------------------------------
// Fetch all completed tests with their dates in one query
$historyQuery = $database->prepare("
    SELECT patient_test_id, completed_at
    FROM patient_test 
    WHERE pid = ? AND completed_at IS NOT NULL
");
$historyQuery->bind_param("i", $userid);
$historyQuery->execute();
$historyResult = $historyQuery->get_result();
$totalTests = $historyResult->num_rows;

// ---------------------------------------------------------------------
// 8) Get tests completed this month
// ---------------------------------------------------------------------
$thisMonth = date('Y-m');
$monthlyTests = 0;
$historyResult->data_seek(0); // Reset result pointer

while ($row = $historyResult->fetch_assoc()) {
    $testDate = date('Y-m', strtotime($row['completed_at']));
    if ($testDate == $thisMonth) {
        $monthlyTests++;
    }
}

// ---------------------------------------------------------------------
// 9) Calculate tests in the last 30 days
// ---------------------------------------------------------------------
$testsLastMonth = 0;
$oneMonthAgo = date('Y-m-d', strtotime('-30 days'));
$historyResult->data_seek(0); // Reset result pointer again

while ($row = $historyResult->fetch_assoc()) {
    if (strtotime($row['completed_at']) >= strtotime($oneMonthAgo)) {
        $testsLastMonth++;
    }
}
// ---------------------------------------------------------------------
// 10) Get unique categories count
// ---------------------------------------------------------------------
$uniqueCategoriesCount = count($categories);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Psychological Dashboard</title>
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --light-bg: #f8f9fa;
            --border-color: #e9ecef;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --mild-color: #8bc34a;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }

        .dashboard-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .dashboard-header h1 {
            color: var(--secondary-color);
            margin-bottom: 10px;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card h3 {
            font-size: 14px;
            color: #6c757d;
            margin: 0 0 10px 0;
            text-transform: uppercase;
        }

        .stat-card .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0;
        }

        .cat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .category-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .category-header {
            padding: 15px 20px;
            background: var(--secondary-color);
            color: white;
        }

        .category-header h2 {
            margin: 0;
            font-size: 18px;
        }

        .category-body {
            padding: 20px;
        }

        .category-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .recent-test {
            background: var(--light-bg);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .recent-test h4 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }

        .level-indicator {
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .test-date {
            font-size: 12px;
            color: #6c757d;
            display: block;
            margin-top: 5px;
        }

        .chart-container {
            position: relative;
            height: 200px;
            margin-top: 20px;
        }

        .level-legend {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding: 0 10px;
        }

        .level-item {
            display: flex;
            align-items: center;
            font-size: 12px;
        }

        .level-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .view-all {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        .no-tests {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .cat-grid {
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
                        <a href="patientTestHistory.php">
                            <button class="login-btn btn-primary-soft btn btn-icon-back"
                                style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                                <font class="tn-in-text">Back</font>
                            </button>
                        </a>
                    </td>
                    <td style="text-align: center;">
                        <p style="font-size: 23px; font-weight: 600; margin: 0;">Your Psychological Test Dashboard</p>
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
            <div class="dashboard-container">
                <div class="dashboard-header">
                    <p>Track your progress and view your test results across different categories</p>
                </div>
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <h3>Total Tests Completed</h3>
                        <p class="stat-value"><?php echo $totalTests; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Tests This Month</h3>
                        <p class="stat-value"><?php echo $monthlyTests; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Categories</h3>
                        <p class="stat-value"><?php echo $uniqueCategoriesCount; ?></p>
                    </div>
                </div>

                <?php if (count($categories) > 0): ?>
                    <div class="cat-grid">
                        <?php foreach ($categories as $category): ?>
                            <div class="category-card">
                                <div class="category-header">
                                    <h2><?php echo htmlspecialchars($category); ?></h2>
                                </div>
                                <div class="category-body">
                                    <?php if (isset($recentResults[$category])):
                                        $recent = $recentResults[$category];
                                    ?>
                                        <div class="recent-test">
                                            <h4>Most Recent: <?php echo htmlspecialchars($recent['test_name']); ?></h4>
                                            <span class="level-indicator" style="background-color: <?php echo $recent['severity_color']; ?>">
                                                <?php echo $recent['severity_text']; ?>
                                            </span>
                                            <span class="test-date">Completed on <?php echo $recent['formatted_date']; ?></span>
                                            <a href="testResult.php?patient_test_id=<?php echo $recent['patient_test_id']; ?>" class="view-all">View Details</a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($trendData[$category])): ?>
                                        <div class="trend-title">
                                            <h4>Progress Trends</h4>
                                            <span class="trend-info">Lower scores are better</span>
                                        </div>
                                        <div class="chart-container">
                                            <canvas id="trendChart_<?php echo str_replace(' ', '_', $category); ?>"></canvas>
                                        </div>
                                    <?php endif; ?>

                                    <h4>Distribution by Level</h4>
                                    <div class="chart-container">
                                        <canvas id="levelChart_<?php echo str_replace(' ', '_', $category); ?>"></canvas>
                                    </div>

                                    <div class="level-legend">
                                        <div class="level-item">
                                            <div class="level-color" style="background-color: #4caf50;"></div>
                                            <span>Minimal</span>
                                        </div>
                                        <div class="level-item">
                                            <div class="level-color" style="background-color: #8bc34a;"></div>
                                            <span>Mild</span>
                                        </div>
                                        <div class="level-item">
                                            <div class="level-color" style="background-color: #ff9800;"></div>
                                            <span>Moderate</span>
                                        </div>
                                        <div class="level-item">
                                            <div class="level-color" style="background-color: #f44336;"></div>
                                            <span>Significant</span>
                                        </div>
                                    </div>

                                    <a href="patientTestHistory.php?category=<?php echo urlencode($category); ?>" class="view-all">View All <?php echo htmlspecialchars($category); ?> Tests</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-tests">
                        <h2>No tests completed yet</h2>
                        <p>Complete your first assessment to see your dashboard</p>
                        <a href="patientTests.php" class="back-link">Take a Test</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Charts configuration
            <?php foreach ($categories as $category):
                $chartId = str_replace(' ', '_', $category);
                $levelData = $categoryData[$category];
            ?>
                // Level distribution charts
                new Chart(document.getElementById('levelChart_<?php echo $chartId; ?>'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Minimal', 'Mild', 'Moderate', 'Significant'],
                        datasets: [{
                            data: [
                                <?php echo $levelData['Level 1']; ?>,
                                <?php echo $levelData['Level 2']; ?>,
                                <?php echo $levelData['Level 3']; ?>,
                                <?php echo $levelData['Level 4']; ?>
                            ],
                            backgroundColor: [
                                '#4caf50',
                                '#8bc34a',
                                '#ff9800',
                                '#f44336'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false,
                            }
                        }
                    }
                });

                <?php
                // Trend charts
                if (isset($trendData[$category])):
                    $trend = $trendData[$category];
                    $labels = [];
                    $data = [];

                    foreach ($trend as $point) {
                        $labels[] = $point['date'];
                        $data[] = $point['score'];
                    }
                ?>
                    // Trend charts
                    new Chart(document.getElementById('trendChart_<?php echo $chartId; ?>'), {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode($labels); ?>,
                            datasets: [{
                                label: 'Score',
                                data: <?php echo json_encode($data); ?>,
                                borderColor: '#3498db',
                                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                                tension: 0.3,
                                fill: true,
                                spanGaps: true // This will connect points with null values between
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    title: {
                                        display: true,
                                        text: 'Score Percentage'
                                    }
                                },
                                x: {
                                    ticks: {
                                        maxRotation: 45,
                                        minRotation: 45
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            if (context.raw !== null) {
                                                label += context.raw + '% (Lower is better)';
                                            } else {
                                                label += 'No data for this month';
                                            }
                                            return label;
                                        }
                                    }
                                }
                            }
                        }
                    });
                <?php endif; ?>

            <?php endforeach; ?>
        });
    </script>
</body>

</html>