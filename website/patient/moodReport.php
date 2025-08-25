<?php
session_start();

// 1) Check if user is logged in as patient
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION['usertype'] != 'p') {
    header("location: ../login.php");
    exit;
}

$useremail = $_SESSION["user"];

// Include database connection
include("../connection.php");

// Fetch user details
$userrow   = $database->query("SELECT * FROM patient WHERE pemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid    = $userfetch["pid"];
$username  = $userfetch["pname"];

// Define mood -> numeric score mapping (teal theme doesn't affect logic)
$moodScores = [
    "Grateful" => 4,
    "Happy"    => 3,
    "Neutral"  => 2,
    "Sad"      => 1,
    "Stress"   => 0
];

// 2) Let the user pick a month/year. If none selected, default to current
date_default_timezone_set('Asia/Kolkata');
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear  = isset($_GET['year'])  ? (int)$_GET['year']  : date('Y');

if ($currentMonth < 1) {
    $currentMonth = 1;
}
if ($currentMonth > 12) {
    $currentMonth = 12;
}

// For previous/next month navigation
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$startOfMonth = date("Y-m-d", mktime(0, 0, 0, $currentMonth, 1, $currentYear));
$endOfMonth   = date("Y-m-d", mktime(0, 0, 0, $currentMonth + 1, 0, $currentYear));

// 3) Query mood data for that month
$stmt = $database->prepare("
    SELECT dateCreated, mood
    FROM mood
    WHERE pid = ?
      AND dateCreated BETWEEN ? AND ?
    ORDER BY dateCreated ASC
");
$stmt->bind_param("iss", $userid, $startOfMonth, $endOfMonth);
$stmt->execute();
$res = $stmt->get_result();

$moodData = [];
while ($row = $res->fetch_assoc()) {
    $moodData[$row['dateCreated']] = $row['mood'];
}
$stmt->close();

// 4) Build arrays for Chart.js
$chartLabels = [];
$chartData   = [];

$numberOfDays = (int)date('t', strtotime($startOfMonth));
for ($day = 1; $day <= $numberOfDays; $day++) {
    $thisDate = sprintf("%04d-%02d-%02d", $currentYear, $currentMonth, $day);
    $chartLabels[] = $day; // Just use day number for cleaner display

    if (isset($moodData[$thisDate])) {
        $theMood = $moodData[$thisDate];
        $score   = isset($moodScores[$theMood]) ? $moodScores[$theMood] : 0;
        $chartData[] = $score;
    } else {
        $chartData[] = null; // No mood logged that day
    }
}

//Check if enough data
$validEntries = array_filter($chartData, function ($val) {
    return $val !== null;
});

$hasEnoughData = count($validEntries) >= 10;

// 5) If no data, skip weekly averages / overall score
$weeklyAverages = [];
$totalScore = 0;

if ($hasEnoughData) {
    $tempSum   = 0;
    $tempCount = 0;
    $allSum    = 0;
    $allCount  = 0;

    for ($i = 0; $i < count($chartData); $i++) {
        $score = $chartData[$i];

        if ($score !== null) {
            $tempSum   += $score;
            $tempCount += 1;

            $allSum   += $score;
            $allCount += 1;
        }

        // If 7 days passed or end of month
        if ((($i + 1) % 7 === 0) || $i === count($chartData) - 1) {
            $avg = ($tempCount > 0) ? $tempSum / $tempCount : null;
            $weeklyAverages[] = $avg !== null ? round($avg, 2) : "N/A";
            $tempSum   = 0;
            $tempCount = 0;
        }
    }

    if ($allCount > 0) {
        $avgAll = $allSum / $allCount;
        $totalScore = round(($avgAll / 4) * 100);
    }
}

// 6) Determine Grade and Show a Custom Message (only if we have data)
$gradeMessage = "";
$gradeTitle = "";
$gradeBgColor = "#fff";
$gradeBorderColor = "#ccc";
$gradeTextColor = "#000";

if ($hasEnoughData) {
    if ($totalScore >= 0 && $totalScore <= 20) {
        // Grade E (pastel red)
        $gradeTitle = "Grade E";
        $gradeBgColor = "#ffbdbd";
        $gradeBorderColor = "#ff9c9c";
        $gradeTextColor = "#802020";
        $gradeMessage = "
            <p>Your overall mood score is quite low this month. We strongly recommend scheduling 
            a consultation with a doctor to discuss how you're feeling. 
            <a href='schedule.php'>Click here</a> to book a session.</p>
            <p>Remember, you are not alone. We believe in you!</p>
        ";
    } elseif ($totalScore >= 21 && $totalScore <= 40) {
        // Grade D (pastel orange)
        $gradeTitle = "Grade D";
        $gradeBgColor = "#ffe8b2";
        $gradeBorderColor = "#ffd98b";
        $gradeTextColor = "#805820";
        $gradeMessage = "
            <p>Your mood trends are on the lower side. Consider exploring new activities or 
            talking with a friend or counselor. Every step counts!</p>
        ";
    } elseif ($totalScore >= 41 && $totalScore <= 60) {
        // Grade C (pastel yellow)
        $gradeTitle = "Grade C";
        $gradeBgColor = "#fff3cd";
        $gradeBorderColor = "#ffeeba";
        $gradeTextColor = "#856404";
        $gradeMessage = "
            <p>Your overall mood is moderate. Keep focusing on positive habits and 
            self-care strategies to move toward higher moods!</p>
        ";
    } elseif ($totalScore >= 61 && $totalScore <= 80) {
        // Grade B (pastel green)
        $gradeTitle = "Grade B";
        $gradeBgColor = "#c8e6c9";
        $gradeBorderColor = "#a5d6a7";
        $gradeTextColor = "#2e7031";
        $gradeMessage = "
            <p>Great job maintaining a relatively positive mood! Continue doing what works, 
            and don't hesitate to reach out for support if needed.</p>
        ";
    } elseif ($totalScore >= 81 && $totalScore <= 100) {
        // Grade A (pastel teal/blue)
        $gradeTitle = "Grade A";
        $gradeBgColor = "#b2ebf2";
        $gradeBorderColor = "#80deea";
        $gradeTextColor = "#00695c";
        $gradeMessage = "
            <p>Fantastic! You've maintained a consistently positive mood this month. 
            Keep up the wonderful work and share your positivity with others!</p>
        ";
    } else {
        // Out of range
        $gradeTitle = "Score Error";
        $gradeBgColor = "#f0f0f0";
        $gradeBorderColor = "#ccc";
        $gradeTextColor = "#666";
        $gradeMessage = "
            <p>Score out of expected range. Please contact support.</p>
        ";
    }
}

// Get month name for display
$monthNames = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December"
];
$monthName = $monthNames[$currentMonth - 1];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mood Analytics Report</title>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Base Styles and Variables */
        :root {
            --primary: #00897b;
            --primary-dark: #00695c;
            --primary-light: #4db6ac;
            --primary-bg: #e0f2f1;
            --text-dark: #004d40;
            --text-light: #ffffff;
            --accent: #26a69a;
            --gray-light: #f5f5f5;
            --gray: #e0e0e0;
            --shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            --border-radius: 10px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--primary-bg);
            color: var(--text-dark);
            line-height: 1.6;
            padding-bottom: 40px;
        }

        /* Layout */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header & Navigation */
        header {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 15px 0;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .site-title {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .user-welcome {
            font-size: 0.9rem;
        }

        nav {
            background-color: white;
            padding: 10px 0;
            margin-bottom: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .breadcrumbs {
            list-style: none;
            display: flex;
            align-items: center;
        }

        .breadcrumbs li {
            display: flex;
            align-items: center;
        }

        .breadcrumbs li:not(:last-child)::after {
            content: '/';
            margin: 0 10px;
            color: #aaa;
        }

        .breadcrumbs a {
            color: var(--primary);
            text-decoration: none;
        }

        .breadcrumbs a:hover {
            text-decoration: underline;
        }

        /* Month Selector */
        .report-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 20px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .month-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
        }

        .month-form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .month-nav {
            display: flex;
            gap: 10px;
        }

        .month-nav a {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .month-nav a:hover {
            background-color: var(--primary-dark);
            transform: scale(1.05);
        }

        select,
        button {
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid var(--gray);
            font-size: 1rem;
        }

        button {
            background-color: var(--primary);
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        button:hover {
            background-color: var(--primary-dark);
        }

        /* Main Content Cards */
        .card {
            margin-top: 20px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card-header {
            padding: 15px 20px;
            background-color: var(--primary-light);
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
        }

        .card-header i {
            margin-right: 10px;
        }

        .card-body {
            padding: 20px;
        }

        /* Chart Section */
        .chart-container {
            height: 400px;
            padding: 10px;
            position: relative;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .stat-card {
            padding: 20px;
            border-radius: var(--border-radius);
            background-color: white;
            box-shadow: var(--shadow);
            text-align: center;
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-title {
            font-size: 1rem;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        /* Weekly Averages Table */
        .weekly-stats {
            width: 100%;
            border-collapse: collapse;
        }

        .weekly-stats th,
        .weekly-stats td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--gray);
        }

        .weekly-stats th {
            background-color: var(--gray-light);
            font-weight: 600;
            color: var(--primary-dark);
        }

        .weekly-stats tr:hover {
            background-color: var(--gray-light);
        }

        /* Grade Card */
        .grade-card {
            padding: 25px;
            border-radius: var(--border-radius);
            margin-top: 20px;
            box-shadow: var(--shadow);
            position: relative;
            border-left: 10px solid;
            /* Will be set dynamically */
            transition: transform 0.3s ease;
        }

        .grade-card:hover {
            transform: translateY(-5px);
        }

        .grade-badge {
            position: absolute;
            top: -20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2rem;
            font-weight: 700;
            box-shadow: var(--shadow);
        }

        .grade-title {
            font-size: 1.5rem;
            margin-bottom: 15px;
            padding-right: 70px;
            /* Make room for the badge */
        }

        .grade-card p {
            margin-bottom: 15px;
            line-height: 1.7;
        }

        .grade-card a {
            color: var(--primary);
            text-decoration: underline;
            font-weight: 600;
        }

        .grade-card a:hover {
            color: var(--primary-dark);
        }

        /* No Data Message */
        .no-data-container {
            text-align: center;
            padding: 40px 20px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            max-width: 600px;
            margin: 40px auto;
            animation: fadeIn 0.5s ease-in-out;
        }

        .no-data-container img {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.8;
        }

        .no-data-title {
            font-size: 1.8rem;
            color: var(--primary-dark);
            margin-bottom: 15px;
        }

        .no-data-text {
            margin-bottom: 20px;
            font-size: 1.1rem;
            color: var(--text-dark);
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn i {
            margin-right: 5px;
        }

        /* Mood Scale Legend */
        .mood-legend {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .mood-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }

        .mood-color {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            margin-right: 5px;
        }

        /* Progress Bar for Overall Score */
        .progress-container {
            margin-top: 10px;
            background-color: var(--gray-light);
            border-radius: 20px;
            height: 20px;
            width: 100%;
            overflow: hidden;
            position: relative;
        }

        .progress-bar {
            height: 100%;
            border-radius: 20px;
            transition: width 1s ease-in-out;
            background: linear-gradient(to right, #ff9a9e, #fecfef);
        }

        .progress-bar.score-a {
            background: linear-gradient(to right, #43cea2, #185a9d);
        }

        .progress-bar.score-b {
            background: linear-gradient(to right, #9be15d, #00e3ae);
        }

        .progress-bar.score-c {
            background: linear-gradient(to right, #ffdb3a, #ffb347);
        }

        .progress-bar.score-d {
            background: linear-gradient(to right, #f8af5a, #ff8272);
        }

        .progress-bar.score-e {
            background: linear-gradient(to right, #ff8585, #ff5858);
        }

        /* Animations */
        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: translateY(-10px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Footer */
        footer {
            background-color: var(--primary-dark);
            color: white;
            text-align: center;
            padding: 15px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        .footer-content {
            font-size: 0.9rem;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .report-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .month-title {
                margin-bottom: 15px;
            }

            .month-form {
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
            }

            .chart-container {
                height: 300px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .grade-badge {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header>
        <div class="container header-content">
            <div class="site-title">MoodTracker</div>
            <div class="user-welcome">Welcome, <?php echo htmlspecialchars($username); ?></div>
        </div>
    </header>

    <div class="container">
        <!-- Breadcrumbs -->
        <nav>
            <ul class="breadcrumbs">
                <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="mood.php">Mood Calendar</a></li>
                <li>Analytics Report</li>
            </ul>
        </nav>

        <!-- Report Header with Month Selection -->
        <div class="report-header">
            <h1 class="month-title">Mood Report: <?php echo $monthName . ' ' . $currentYear; ?></h1>

            <div class="month-form">
                <div class="month-nav">
                    <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" title="Previous Month">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" title="Next Month">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>

                <form method="GET" action="">
                    <select name="month" id="month">
                        <?php
                        for ($m = 1; $m <= 12; $m++) {
                            $selected = ($m == $currentMonth) ? "selected" : "";
                            echo "<option value='$m' $selected>{$monthNames[$m - 1]}</option>";
                        }
                        ?>
                    </select>

                    <select name="year" id="year">
                        <?php
                        $currentYearTemp = date('Y');
                        for ($y = $currentYearTemp - 5; $y <= $currentYearTemp + 5; $y++) {
                            $selected = ($y == $currentYear) ? "selected" : "";
                            echo "<option value='$y' $selected>$y</option>";
                        }
                        ?>
                    </select>

                    <button type="submit"><i class="fas fa-search"></i> View Report</button>

                </form>
            </div>
        </div>

        <?php if ($hasEnoughData): ?>
            <!-- Chart Section -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i> Mood Trends
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="moodChartCanvas"></canvas>
                    </div>

                    <div class="mood-legend">
                        <div class="mood-item">
                            <div class="mood-color" style="background-color: rgba(0,191,165,1)"></div>
                            <span>0: Stress</span>
                        </div>
                        <div class="mood-item">
                            <div class="mood-color" style="background-color: rgba(255,145,0,1)"></div>
                            <span>1: Sad</span>
                        </div>
                        <div class="mood-item">
                            <div class="mood-color" style="background-color: rgba(214,209,0,1)"></div>
                            <span>2: Neutral</span>
                        </div>
                        <div class="mood-item">
                            <div class="mood-color" style="background-color: rgba(27,94,213,1)"></div>
                            <span>3: Happy</span>
                        </div>
                        <div class="mood-item">
                            <div class="mood-color" style="background-color: rgba(106,27,154,1)"></div>
                            <span>4: Grateful</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Overview -->
            <div class="stats-grid">
                <!-- Overall Score Card -->
                <div class="stat-card">
                    <div class="stat-title">Overall Score</div>
                    <div class="stat-value"><?php echo $totalScore; ?>/100</div>
                    <div class="progress-container">
                        <div class="progress-bar <?php
                                                    if ($totalScore >= 81) echo 'score-a';
                                                    elseif ($totalScore >= 61) echo 'score-b';
                                                    elseif ($totalScore >= 41) echo 'score-c';
                                                    elseif ($totalScore >= 21) echo 'score-d';
                                                    else echo 'score-e';
                                                    ?>" style="width: <?php echo $totalScore; ?>%"></div>
                    </div>
                </div>

                <!-- Number of Entries -->
                <div class="stat-card">
                    <div class="stat-title">Days Logged</div>
                    <div class="stat-value"><?php echo count($validEntries); ?>/<?php echo $numberOfDays; ?></div>
                </div>

                <!-- Average Mood -->
                <div class="stat-card">
                    <div class="stat-title">Average Mood Level</div>
                    <div class="stat-value">
                        <?php
                        if ($allCount > 0) {
                            $avg = $allSum / $allCount;
                            echo number_format($avg, 1) . '/4.0';
                        } else {
                            echo "N/A";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Weekly Averages -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-calendar-week"></i> Weekly Breakdown
                </div>
                <div class="card-body">
                    <table class="weekly-stats">
                        <thead>
                            <tr>
                                <th>Week</th>
                                <th>Average Score</th>
                                <th>Days Tracked</th>
                                <th>Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $weekStart = 0;
                            foreach ($weeklyAverages as $i => $avg) {
                                $weekNumber = $i + 1;
                                $weekEnd = min($weekStart + 6, $numberOfDays - 1);

                                // Count days with data in this week
                                $daysWithData = 0;
                                for ($j = $weekStart; $j <= $weekEnd; $j++) {
                                    if ($chartData[$j] !== null) {
                                        $daysWithData++;
                                    }
                                }

                                // Determine trend compared to previous week
                                $trendIcon = "";
                                $trendColor = "";
                                if ($i > 0 && $avg !== "N/A" && $weeklyAverages[$i - 1] !== "N/A") {
                                    if ($avg > $weeklyAverages[$i - 1]) {
                                        $trendIcon = '<i class="fas fa-arrow-up"></i>';
                                        $trendColor = "color: #43a047";
                                    } elseif ($avg < $weeklyAverages[$i - 1]) {
                                        $trendIcon = '<i class="fas fa-arrow-down"></i>';
                                        $trendColor = "color: #e53935";
                                    } else {
                                        $trendIcon = '<i class="fas fa-equals"></i>';
                                        $trendColor = "color: #7e57c2";
                                    }
                                }

                                echo "<tr>";
                                echo "<td>Week $weekNumber (" . ($weekStart + 1) . "-" . ($weekEnd + 1) . ")</td>";
                                echo "<td>$avg</td>";
                                echo "<td>$daysWithData</td>";
                                echo "<td style='$trendColor'>$trendIcon</td>";
                                echo "</tr>";

                                $weekStart += 7;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Grade -->
            <div class="grade-card" style="background-color: <?php echo $gradeBgColor; ?>; border-left-color: <?php echo $gradeBorderColor; ?>; color: <?php echo $gradeTextColor; ?>">
                <div class="grade-badge" style="background-color: <?php echo $gradeBorderColor; ?>; color: <?php echo $gradeTextColor; ?>">
                    <?php echo substr($gradeTitle, -1); ?>
                </div>
                <h2 class="grade-title"><?php echo $gradeTitle; ?></h2>
                <?php echo $gradeMessage; ?>
            </div>

            <!-- Action Buttons -->
            <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: center">
                <a href="mood.php" class="btn"><i class="fas fa-calendar"></i> Back to Mood Calendar</a>
                <a href="#" id="printReport" style="
                        display: inline-block;
                        background-color: #007BFF;
                        color: white;
                        padding: 10px 20px;
                        font-size: 14px;
                        text-decoration: none;
                        border-radius: 5px;
                        border: none;
                        font-family: Arial, sans-serif;
                        cursor: pointer;
                        transition: background-color 0.3s ease;
                    "
                    onmouseover="this.style.backgroundColor='#0056b3'"
                    onmouseout="this.style.backgroundColor='#007BFF'">
                    Download Report
                </a>

            </div>

        <?php else: ?>
            <!-- No Data Section -->
            <div class="no-data-container">
                <img src="https://cdn-icons-png.flaticon.com/512/4387/4387781.png" alt="No Data Icon">
                <h2 class="no-data-title">Not Enough Data</h2>
                <p class="no-data-text">
                    We need at least 10 mood entries to generate a meaningful report for the selected month.
                </p>
                <p class="no-data-text">
                    Continue tracking your mood daily to unlock valuable insights about your emotional wellbeing!
                </p>
                <div style="margin-top: 25px; display: flex; justify-content: center; gap: 15px;">
                    <a href="mood.php" class="btn"><i class="fas fa-calendar"></i> Back to Mood Calendar</a>
                </div>
            </div>
        <?php endif; ?>
    </div>


    <script>
        // Chart.js implementation
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($hasEnoughData): ?>
                const ctx = document.getElementById('moodChartCanvas').getContext('2d');

                // Convert PHP arrays to JavaScript
                const labels = <?php echo json_encode($chartLabels); ?>;
                const data = <?php echo json_encode($chartData); ?>;

                // Create color array for multi-color points
                const pointColors = data.map(score => {
                    if (score === 0) return 'rgba(0,191,165,1)'; // Stress - Teal
                    if (score === 1) return 'rgba(255,145,0,1)'; // Sad - Orange
                    if (score === 2) return 'rgba(214,209,0,1)'; // Neutral - Yellow
                    if (score === 3) return 'rgba(27,94,213,1)'; // Happy - Blue
                    if (score === 4) return 'rgba(106,27,154,1)'; // Grateful - Purple
                    return 'rgba(200,200,200,1)'; // None
                });

                // Create gradient fill for the chart
                let gradient = ctx.createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, 'rgba(0,191,165,0.3)'); // Teal with 0.3 alpha
                gradient.addColorStop(1, 'rgba(0,191,165,0)'); // Transparent at bottom

                // Create the chart
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Daily Mood Score',
                            data: data,
                            fill: true,
                            backgroundColor: gradient,
                            borderColor: 'rgba(0,191,165,0.8)',
                            borderWidth: 2,
                            tension: 0.4,
                            pointRadius: 6,
                            pointBackgroundColor: pointColors,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 8,
                            pointHoverBackgroundColor: pointColors,
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                min: 0,
                                max: 4,
                                title: {
                                    display: true,
                                    text: 'Mood Score (0-4)',
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                },
                                grid: {
                                    color: 'rgba(0,0,0,0.05)'
                                },
                                ticks: {
                                    stepSize: 1,
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Day of Month',
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                },
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 12
                                    },
                                    maxRotation: 0,
                                    autoSkip: true,
                                    maxTicksLimit: 15
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,0.7)',
                                titleFont: {
                                    size: 14
                                },
                                bodyFont: {
                                    size: 13
                                },
                                padding: 12,
                                callbacks: {
                                    title: function(tooltipItems) {
                                        const day = tooltipItems[0].label;
                                        return `Day ${day} - <?php echo $monthName . ' ' . $currentYear; ?>`;
                                    },
                                    label: function(context) {
                                        const score = context.raw;
                                        if (score === null) return 'No mood logged';

                                        let moodText;
                                        switch (score) {
                                            case 0:
                                                moodText = 'Stress';
                                                break;
                                            case 1:
                                                moodText = 'Sad';
                                                break;
                                            case 2:
                                                moodText = 'Neutral';
                                                break;
                                            case 3:
                                                moodText = 'Happy';
                                                break;
                                            case 4:
                                                moodText = 'Grateful';
                                                break;
                                            default:
                                                moodText = 'Unknown';
                                        }

                                        return `Mood: ${moodText} (${score}/4)`;
                                    }
                                }
                            }
                        }
                    }
                });

                // Animate progress bar on load
                const progressBar = document.querySelector('.progress-bar');
                progressBar.style.width = '0%';
                setTimeout(() => {
                    progressBar.style.width = '<?php echo $totalScore; ?>%';
                }, 300);
            <?php endif; ?>
        });
    </script>
    <script>
        document.getElementById('printReport').addEventListener('click', function(e) {
            e.preventDefault();

            const month = "<?php echo urlencode($currentMonth); ?>";
            const year = "<?php echo urlencode($currentYear); ?>";

            const reportUrl = `/patient/moodPDF.php?month=${month}&year=${year}`;

            window.open(
                reportUrl,
                "MoodPDFReport",
                "width=800,height=600,resizable=yes,scrollbars=yes,status=no"
            );
        });
    </script>


</body>

</html>