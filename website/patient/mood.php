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
$reminderEnabled = 0;

// Fetch patient basic details (keep this as you want)
$userrow   = $database->query("SELECT * FROM patient WHERE pemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid    = $userfetch["pid"];
$username  = $userfetch["pname"];

// Always initialize reminderEnabled first
$reminderEnabled = 0;

// Fetch patient details
$query = "SELECT pname, moodReminder FROM patient WHERE pid = ?";
$stmt = $database->prepare($query); // FIXED from $con to $database
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();
$userfetch = $result->fetch_assoc();
$stmt->close();

$reminderEnabled = (int)$userfetch['moodReminder'];
// ---------------------------------------------------------------------
// 2a) Prepare today's date (in YYYY-MM-DD) for DB
// ---------------------------------------------------------------------
date_default_timezone_set('Asia/Kolkata');
$mysqlDate = date('Y-m-d');  // e.g. "2025-03-08"

// ---------------------------------------------------------------------
// 2b) Check if user has already checked in today
// ---------------------------------------------------------------------
$checkStmt = $database->prepare("SELECT mood FROM mood WHERE pid = ? AND dateCreated = ? LIMIT 1");
$checkStmt->bind_param("is", $userid, $mysqlDate);
$checkStmt->execute();
$existingResult = $checkStmt->get_result();
$todayMoodRow   = $existingResult->fetch_assoc();
$checkStmt->close();

$todayMood = $todayMoodRow ? $todayMoodRow['mood'] : null;

// ---------------------------------------------------------------------
// 2c) Process mood submission (insert or update)
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_mood'])) {
    $selectedMood = $_POST['selected_mood']; // e.g. "Happy", "Neutral", etc.

    if ($todayMood) {
        // Already has a mood for today => UPDATE
        $updateStmt = $database->prepare("UPDATE mood SET mood = ? WHERE pid = ? AND dateCreated = ?");
        $updateStmt->bind_param("sis", $selectedMood, $userid, $mysqlDate);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        // No mood yet for today => INSERT
        $insertStmt = $database->prepare("INSERT INTO mood (pid, mood, dateCreated) VALUES (?, ?, ?)");
        $insertStmt->bind_param("iss", $userid, $selectedMood, $mysqlDate);
        $insertStmt->execute();
        $insertStmt->close();
    }

    // Redirect to avoid form resubmission
    header("Location: ./mood.php?success=1");
    exit;
}

// ---------------------------------------------------------------------
// 3) Prepare calendar logic
// ---------------------------------------------------------------------
$months = [
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

// Get the current month/year from query params or default to today's
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n'); // 1-12
$currentYear  = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

if ($currentMonth < 1) {
    $currentMonth = 1;
} elseif ($currentMonth > 12) {
    $currentMonth = 12;
}

$prevMonth = $currentMonth - 1;
$prevYear  = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear -= 1;
}

$nextMonth = $currentMonth + 1;
$nextYear  = $currentYear;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear += 1;
}

$firstDayOfMonth = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$daysInMonth     = date('t', $firstDayOfMonth);
$startDay        = date('w', $firstDayOfMonth);

// ---------------------------------------------------------------------
// 4) Fetch this month's moods for the user
// ---------------------------------------------------------------------
$moodsForMonth = []; // day => mood
$moodQuery = $database->prepare("
    SELECT dateCreated, mood 
    FROM mood 
    WHERE pid = ? 
      AND YEAR(dateCreated) = ? 
      AND MONTH(dateCreated) = ?
");
$moodQuery->bind_param("iii", $userid, $currentYear, $currentMonth);
$moodQuery->execute();
$result = $moodQuery->get_result();
while ($row = $result->fetch_assoc()) {
    $dayNumber = (int) date('j', strtotime($row['dateCreated']));
    $moodsForMonth[$dayNumber] = $row['mood'];
}
$moodQuery->close();

// Map each mood label to an emoji
$moodEmojis = [
    'Happy'    => '😊',
    'Neutral'  => '😐',
    'Stress'   => '😵‍💫',
    'Sad'      => '😔',
    'Grateful' => '🤗'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_reminder'])) {
    $newFlag = isset($_POST['moodReminder']) ? 1 : 0;
    $stmt = $database->prepare("UPDATE patient SET moodReminder = ? WHERE pid = ?");
    $stmt->bind_param("ii", $newFlag, $userid);
    $stmt->execute();
    $stmt->close();

    header("Location: mood.php?reminderSaved=" . $newFlag);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Mood Calendar</title>
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/mood.css">

</head>

<body>
    <!-- ======================= SIDE MENU ======================= -->
    <div class="container">
        <?php include(__DIR__ . '/patientMenu.php'); ?>

        <!-- ======================= MAIN CONTENT ======================= -->
        <div class="dash-body">
            <?php if (isset($_GET['success'])) { ?>
                <div id="success-popup" style="
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: #d4edda;
                color: #155724;
                padding: 10px 20px;
                border: 1px solid #c3e6cb;
                border-radius: 8px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.15);
                z-index: 9999;
                font-family: Arial, sans-serif;
            ">
                    Your mood has been recorded/updated successfully!
                </div>
                <script>
                    // Hide the success popup after 2 seconds
                    setTimeout(() => {
                        const popup = document.getElementById("success-popup");
                        if (popup) {
                            popup.style.display = "none";
                        }
                    }, 2000);
                </script>
            <?php } ?>



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
                        <p style="font-size: 23px; font-weight: 600; margin: 0;">My Mood Calendar</p>
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

            <!-- Calendar Container -->
            <div class="calendar-container" id="calendar">
                <!-- Navigation Buttons -->
                <div class="nav-buttons">
                    <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>">&laquo; Prev</a>
                    <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>">Next &raquo;</a>
                </div>

                <!-- Month-Year Title -->
                <div class="month-title">
                    <?php echo $months[$currentMonth - 1] . " " . $currentYear; ?>
                </div>

                <!-- Month-Year Form to jump directly -->
                <form action="" method="GET" class="month-year-form">
                    <select name="month">
                        <?php
                        foreach ($months as $index => $m) {
                            $value = $index + 1;
                            $selected = ($value == $currentMonth) ? "selected" : "";
                            echo "<option value='$value' $selected>$m</option>";
                        }
                        ?>
                    </select>
                    <select name="year">
                        <?php
                        $startYear = date('Y') - 5;
                        $endYear   = date('Y') + 5;
                        for ($y = $startYear; $y <= $endYear; $y++) {
                            $selected = ($y == $currentYear) ? "selected" : "";
                            echo "<option value='$y' $selected>$y</option>";
                        }
                        ?>
                    </select>
                    <button type="submit">Go</button>
                </form>

                <!-- Calendar Table with class="calendar-table" -->
                <table class="calendar-table">
                    <thead>
                        <tr>
                            <th>Sun</th>
                            <th>Mon</th>
                            <th>Tue</th>
                            <th>Wed</th>
                            <th>Thu</th>
                            <th>Fri</th>
                            <th>Sat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $dayCount = 0;
                        echo "<tr>";

                        // Fill empty cells before the first day
                        for ($i = 0; $i < $startDay; $i++) {
                            echo "<td></td>";
                            $dayCount++;
                        }

                        // Now display the days in the month
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            if ($dayCount == 7) {
                                echo "</tr><tr>";
                                $dayCount = 0;
                            }

                            // Check if there's a mood for this day
                            $emojiToShow = '';
                            if (isset($moodsForMonth[$day])) {
                                $moodLabel = $moodsForMonth[$day];
                                $emoji = isset($moodEmojis[$moodLabel]) ? $moodEmojis[$moodLabel] : '';
                                $emojiToShow = "<div class='mood-emoji'>{$emoji}</div>";
                            }

                            echo "<td>
                                <div class='day-mood'>
                                    <strong>$day</strong>
                                    $emojiToShow
                                </div>
                              </td>";
                            $dayCount++;
                        }

                        // Fill remaining cells at the end of the month
                        while ($dayCount < 7) {
                            echo "<td></td>";
                            $dayCount++;
                        }
                        echo "</tr>";
                        ?>
                    </tbody>
                </table>

                <!-- Show today's mood + update form -->
                <div class="today-mood">
                    <?php if ($todayMood): ?>
                        <p>You have already checked in today. Current mood:
                            <strong><?php echo $todayMood; ?></strong>
                            <?php if (isset($moodEmojis[$todayMood])) {
                                echo $moodEmojis[$todayMood];
                            } ?>
                        </p>
                        <p>If you want to change it, just pick a new mood below:</p>
                    <?php else: ?>
                        <p>You haven't checked in today yet. Please select your mood:</p>
                    <?php endif; ?>
                </div>

                <!-- Mood Buttons (Form) -->
                <form action="" method="POST">
                    <div class="mood-buttons">
                        <button class="mood-button" type="submit" name="selected_mood" value="Happy">😊 Happy</button>
                        <button class="mood-button" type="submit" name="selected_mood" value="Neutral">😐 Neutral</button>
                        <button class="mood-button" type="submit" name="selected_mood" value="Stress">😵‍💫 Stress</button>
                        <button class="mood-button" type="submit" name="selected_mood" value="Sad">😔 Sad</button>
                        <button class="mood-button" type="submit" name="selected_mood" value="Grateful">🤗 Grateful</button>
                    </div>
                </form>

                <!-- Mood Analytics Report Button -->
                <div style="text-align:center; margin-top:20px;">
                    <a href="moodReport.php" class="mood-button" style="text-decoration:none; padding:10px 20px;">
                        Generate Mood Analytics Report
                    </a>
                </div>

                <!-- Daily Reminder Toggle -->
                <div style="background-color: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); padding: 20px; margin: 20px 0;">
                    <h3 style="color: #2b84ea; margin-top: 0; margin-bottom: 15px; font-size: 18px;">Daily Reminder</h3>
                    <form method="POST" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 15px;">
                        <label style="display: flex; align-items: center; font-size: 16px; cursor: pointer;">
                            <input
                                type="checkbox"
                                name="moodReminder"
                                value="1"
                                <?php echo $reminderEnabled ? 'checked' : ''; ?>
                                style="margin-right: 10px; width: 18px; height: 18px; cursor: pointer;">
                            <span>Send me a daily mood check reminder at 8 PM</span>
                        </label>
                        <button type="submit" name="toggle_reminder" style="background-color: #47c98d; color: white; border: none; border-radius: 6px; padding: 10px 16px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                            Save
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>