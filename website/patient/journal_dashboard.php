<?php

//learn from w3schools.com

session_start();

if (isset($_SESSION["user"])) {
    if (($_SESSION["user"]) == "" or $_SESSION['usertype'] != 'p') {
        header("location: ../login.php");
    } else {
        $useremail = $_SESSION["user"];
    }
} else {
    header("location: ../login.php");
}


//import database
require_once("../connection.php");

$sqlmain = "select * from patient where pemail=?";
$stmt = $database->prepare($sqlmain);
$stmt->bind_param("s", $useremail);
$stmt->execute();
$userrow = $stmt->get_result();
$userfetch = $userrow->fetch_assoc();

$userid = $userfetch["pid"];
$username = $userfetch["pname"];


//echo $userid;
//echo $username;

date_default_timezone_set('Asia/Kuala_Lumpur');

$today = date('Y-m-d');


// card div-------------------------------------------------------

// Calculate Highest and Lowest Overall Score
$sqlMaxMinScore = "
    SELECT 
        (SELECT overall_score FROM mental_health_status_score WHERE user_id=? ORDER BY overall_score DESC, report_date DESC LIMIT 1) AS highest_score,
        (SELECT report_date FROM mental_health_status_score WHERE user_id=? ORDER BY overall_score DESC, report_date DESC LIMIT 1) AS highest_score_date,
        (SELECT overall_score FROM mental_health_status_score WHERE user_id=? ORDER BY overall_score ASC, report_date ASC LIMIT 1) AS lowest_score,
        (SELECT report_date FROM mental_health_status_score WHERE user_id=? ORDER BY overall_score ASC, report_date ASC LIMIT 1) AS lowest_score_date
";

$stmtMaxMin = $database->prepare($sqlMaxMinScore);
$stmtMaxMin->bind_param("iiii", $userid, $userid, $userid, $userid);
$stmtMaxMin->execute();
$resultMaxMin = $stmtMaxMin->get_result()->fetch_assoc();

$highestScore = $resultMaxMin['highest_score'] ?? 0;
$highestScoreDate = $resultMaxMin['highest_score_date'] ?? 'N/A';
$lowestScore = $resultMaxMin['lowest_score'] ?? 0;
$lowestScoreDate = $resultMaxMin['lowest_score_date'] ?? 'N/A';


// Calculate Active Rate: Count unique dates with conversations
$sqlActiveDays = "SELECT COUNT(DISTINCT DATE(datetime)) AS active_days 
                  FROM chatbot_conversation WHERE user_id=?";
$stmtActiveDays = $database->prepare($sqlActiveDays);
$stmtActiveDays->bind_param("i", $userid);
$stmtActiveDays->execute();
$activeDays = $stmtActiveDays->get_result()->fetch_assoc()['active_days'] ?? 0;



// Calculate total available days from first report to today
$sqlFirstReport = "SELECT MIN(DATE(datetime)) AS first_conversation FROM chatbot_conversation WHERE user_id=?";
$stmtFirstReport = $database->prepare($sqlFirstReport);
$stmtFirstReport->bind_param("i", $userid);
$stmtFirstReport->execute();
$firstReportDate = $stmtFirstReport->get_result()->fetch_assoc()['first_conversation'] ?? $today;

// Calculate the number of days between first report and today
$firstReportDateTime = new DateTime($firstReportDate);
$todayDateTime = new DateTime($today);
$totalDays = $firstReportDateTime->diff($todayDateTime)->days + 1; // Include first day



$activeRate = ($totalDays > 0) ? round(($activeDays / $totalDays) * 100, 2) : 0;

// Calculate Mental Status
$sqlMentalStatus = "SELECT overall_score, sentiment_score, stress_level, anxiety_level, depression_risk 
                    FROM mental_health_status_score WHERE user_id=? ORDER BY report_date DESC LIMIT 1";
$stmtMentalStatus = $database->prepare($sqlMentalStatus);
$stmtMentalStatus->bind_param("i", $userid);
$stmtMentalStatus->execute();
$resultMentalStatus = $stmtMentalStatus->get_result()->fetch_assoc();

$mentalStatus = 0;
if ($resultMentalStatus) {
    $mentalStatus = ($resultMentalStatus['overall_score'] +
        $resultMentalStatus['sentiment_score'] +
        (100 - $resultMentalStatus['stress_level']) +
        (100 - $resultMentalStatus['anxiety_level']) +
        (100 - $resultMentalStatus['depression_risk'])) / 5;
}
// Define mental status categories
if ($mentalStatus < 1) {
    $mentalStatusText = "No data yet";
} elseif ($mentalStatus >= 1 && $mentalStatus < 20) {
    $mentalStatusText = "Needed to seek professional help immediately";
} elseif ($mentalStatus >= 20 && $mentalStatus < 40) {
    $mentalStatusText = "High risk, consider professional support";
} elseif ($mentalStatus >= 40 && $mentalStatus < 60) {
    $mentalStatusText = "Moderate risk, recommended to monitor closely";
} elseif ($mentalStatus >= 60 && $mentalStatus < 80) {
    $mentalStatusText = "Mild risk, maintain healthy coping strategies";
} else {
    $mentalStatusText = "Good mental health status";
}


// card div----------------------END---------------------------------




// Fetch journal scores for the logged-in user
$sqlScores = "SELECT * FROM mental_health_status_score WHERE user_id=? ORDER BY report_date desc limit 10";
$stmtScores = $database->prepare($sqlScores);
$stmtScores->bind_param("i", $userid);
$stmtScores->execute();
$resultScores = $stmtScores->get_result();

$scoreData = [];
$latestUpdatedDate = null;


while ($row = $resultScores->fetch_assoc()) {
    $scoreData[] = $row; // Store data in an array

    // Check and store the latest date
    if ($latestUpdatedDate === null || strtotime($row['updated_date']) > strtotime($latestUpdatedDate)) {
        $latestUpdatedDate = $row['updated_date'];
    }
}

// Reverse the array to display in ascending order
$scoreData = array_reverse($scoreData);
// Convert PHP array to JSON
$scoreData = json_encode($scoreData);


// Format the latest updated date to dd-mm-yyyy hh:mm:ss
$latestUpdatedDateFormatted = date('d-m-Y H:i:s', strtotime($latestUpdatedDate));




// Fetch journal scores for the logged-in user
$sqlConversationMessages = "SELECT DATE(datetime) AS message_date, COUNT(*) AS message_count
FROM chatbot_conversation
WHERE user_id = ? AND ResponseByUser = 1
GROUP BY DATE(datetime) desc limit 10;
";
$stmtConversationMessages = $database->prepare($sqlConversationMessages);
$stmtConversationMessages->bind_param("i", $userid);
$stmtConversationMessages->execute();
$resultMessages = $stmtConversationMessages->get_result();

$messagesData = [];
while ($row = $resultMessages->fetch_assoc()) {
    $messagesData[] = $row;
}
// Reverse the array to display in ascending order
$messagesData = array_reverse($messagesData);
$messagesData = json_encode($messagesData);




// Fetch total messages and count suicidal ones
$sql = "SELECT COUNT(*) AS total, SUM(is_suicidal) AS suicidal FROM chatbot_conversation WHERE user_id=?";
$stmt = $database->prepare($sql);
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$totalMessages = $result['total'] ?? 0;
$suicidalMessages = $result['suicidal'] ?? 0;
$nonSuicidalMessages = $totalMessages - $suicidalMessages;


// Prepare data for JavaScript
$suicidalData = json_encode([
    'total' => $totalMessages,
    'suicidal' => $suicidalMessages,
    'nonSuicidal' => $nonSuicidalMessages
]);



?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="../css/journal_dashboard_template.css" rel="stylesheet" />
    <link href="../css/journal_dashboard.css" rel="stylesheet" />

    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/journal.css">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet" />

    <!-- FullCalendar JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>

    <title>Journal</title>
    <style>
        .dashbord-tables {
            animation: transitionIn-Y-over 0.5s;
        }

        .filter-container {
            animation: transitionIn-Y-bottom 0.5s;
        }

        .sub-table,
        .anime {
            animation: transitionIn-Y-bottom 0.5s;
        }
    </style>





</head>

<body>
    <script>
        // Get JSON data from PHP
        var scoreData = <?php echo $scoreData; ?>;
        console.log(scoreData); // Debugging: check data in browser console

        var messageData = <?php echo $messagesData; ?>;
        console.log(messageData);

        var suicidalData = <?php echo $suicidalData; ?>;
        console.log(suicidalData);
    </script>


    <div class="container">
        <?php include(__DIR__ . '/patientMenu.php'); ?>
        <div class="dash-body" style="margin-top: 15px">
            <table width="100%">
                <tr class="header">
                    <td width="13%">
                        <a href="journal.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                                <font class="tn-in-text">Back</font>
                            </button></a>
                    </td>
                    <td>
                        <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Journal Dashboard</p>

                    </td>
                    <td>
                        <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
                            Today's Date
                        </p>
                        <p class="heading-sub12" style="padding: 0;margin: 0;">
                            <?php


                            echo $today;



                            ?> </p>
                    </td>
                    <td width="10%">
                        <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                    </td>




                </tr>
            </table>


            <div class="dashboard-above">
                <div class="card blue">
                    <div class="card-content">
                        <p>HIGHEST OVERALL SCORE</p>
                        <h2> <?php echo $highestScore; ?> <span class="overall-score-value">/ 100</span></h2>
                        <div class="overall-score-date"><?= $highestScoreDate ?></div>

                    </div>
                </div>

                <div class="card red">
                    <div class="card-content">
                        <p>LOWEST OVERALL SCORE</p>
                        <h2> <?php echo $lowestScore; ?> <span class="overall-score-value">/ 100</span></h2>
                        <div class="overall-score-date"><?= $lowestScoreDate ?></div>

                    </div>
                </div>

                <div class="card orange">
                    <div class="card-content">
                        <p>ACTIVE RATE</p>
                        <h2> <?php echo $activeRate; ?>%</h2>
                    </div>
                </div>

                <div class="card green">
                    <div class="card-content">
                        <p>MENTAL STATUS</p>
                        <h2 id="mentalstatusText"> <?php echo $mentalStatusText; ?></h2>
                    </div>
                </div>
            </div>


            <div class="dashboard">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-chart-area me-1"></i>
                        Overall Score
                    </div>
                    <div class="card-body"><canvas id="overall_score_chart" width="100%" height="30"></canvas></div>
                    <div class="card-footer small text-muted">Updated at <?php echo $latestUpdatedDateFormatted; ?></div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-chart-area me-1"></i>
                                Sentiment Score
                            </div>
                            <div class="card-body"><canvas id="sentiment_score_chart" width="100%" height="50"></canvas></div>
                            <div class="card-footer small text-muted">Updated at <?php echo $latestUpdatedDateFormatted; ?></div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-chart-area me-1"></i>
                                Stress Level
                            </div>
                            <div class="card-body"><canvas id="stress_level_chart" width="100%" height="50"></canvas></div>
                            <div class="card-footer small text-muted">Updated at <?php echo $latestUpdatedDateFormatted; ?></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-chart-area me-1"></i>
                                Anxiety Level
                            </div>
                            <div class="card-body"><canvas id="anxiety_level_chart" width="100%" height="50"></canvas></div>
                            <div class="card-footer small text-muted">Updated at <?php echo $latestUpdatedDateFormatted; ?></div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-chart-area me-1"></i>
                                Depression Risk
                            </div>
                            <div class="card-body"><canvas id="depression_risk_chart" width="100%" height="50"></canvas></div>
                            <div class="card-footer small text-muted">Updated at <?php echo $latestUpdatedDateFormatted; ?></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-chart-bar me-1"></i>
                                Number of Conversation Message
                            </div>
                            <div class="card-body"><canvas id="myBarChart" width="100%" height="50"></canvas></div>
                            <div class="card-footer small text-muted">Updated at <?php echo $latestUpdatedDateFormatted; ?></div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-chart-pie me-1"></i>
                                Suicidal Rate
                            </div>
                            <div class="card-body"><canvas id="myPieChart" width="100%" height="50"></canvas></div>
                            <div class="card-footer small text-muted">Updated at <?php echo $latestUpdatedDateFormatted; ?></div>
                        </div>
                    </div>
                </div>


            </div>


        </div>





    </div>


    <?php include __DIR__ . '/../chatbot/chatbot_window.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>

    <script src="../js/journal_dashboard/overall_score_chart.js"></script>
    <script src="../js/journal_dashboard/sentiment_score_chart.js"></script>
    <script src="../js/journal_dashboard/stress_level_chart.js"></script>
    <script src="../js/journal_dashboard/anxiety_level_chart.js"></script>
    <script src="../js/journal_dashboard/depression_risk_chart.js"></script>
    <script src="../js/journal_dashboard/chart-bar.js"></script>
    <script src="../js/journal_dashboard/chart-pie.js"></script>
    

</body>

</html>