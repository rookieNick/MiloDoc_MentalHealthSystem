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



// ---------------------
// 2. Fetch Journal Data
// ---------------------
$sql = "SELECT date_happened, content, journal_id
    FROM journal
    WHERE user_id = ?";  // Adjust column names if needed
$stmt2 = $database->prepare($sql);
$stmt2->bind_param("s", $userid);
$stmt2->execute();
$result = $stmt2->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = [
        'journal_id' => $row['journal_id'],
        'title' => $row['content'], // Show a short summary
        'start' => $row['date_happened'],
        'extendedProps' => [
        'journal_id' => $row['journal_id'] // Ensure journal ID is available in JS
    ]
    ];
}

$eventsJSON = json_encode($events);


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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



    <div class="container">
        <?php include(__DIR__ . '/patientMenu.php'); ?>
        <div class="dash-body" style="margin-top: 15px">
            <table width="100%">
                <tr class="header">
                    <td width="13%">
                        <a href="index.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                                <font class="tn-in-text">Back</font>
                            </button></a>
                    </td>
                    <td width="5%">
                        <a href="journal_dashboard.php">
                            <button class="login-btn btn-primary-soft btn" style="padding-top:11px;padding-bottom:11px;margin-right:20px;width:125px">
                                <font class="tn-in-text">Dashboard</font>
                            </button>
                        </a>
                    </td>
                    <td>
                        <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Journal</p>

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




            <div id="calendar"></div>


        </div>




    </div>


    <?php include __DIR__ . '/../chatbot/chatbot_window.php'; ?>

</body>

</html>


<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Convert PHP's $eventsJSON into a JS array/object
        var journalEvents = <?php echo $eventsJSON; ?>;

        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            // You can choose any initial view you prefer, e.g., dayGridMonth
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: ''
            },
            eventClick: function(info) {
                // Redirect to the journal detail page
                // Here we use the event's start date as a parameter. 
                // You can customize this to include additional parameters if needed.
                window.location.href = 'journal_detail.php?date=' + info.event.startStr;
            },
            eventMouseEnter: function(info) {
                var eventEl = info.el;

                // // Store original text (for reverting later)
                // eventEl.dataset.originalText = eventEl.innerText;

                // // Show full content
                // eventEl.innerText = info.event.extendedProps.fullContent;
                eventEl.classList.add('event-hover');

                // // Adjust positioning to keep the calendar fixed
                // eventEl.style.position = "absolute";
                // eventEl.style.zIndex = "100"; // Ensure it appears on top
            },

            eventMouseLeave: function(info) {
                var eventEl = info.el;

                // // Restore original short title when hover ends
                // eventEl.innerText = eventEl.dataset.originalText;
                eventEl.classList.remove('event-hover');

                // // Reset position to normal
                // eventEl.style.position = "relative";
                // eventEl.style.zIndex = "";
            },




            events: journalEvents // Add events as needed
        });
        calendar.render();
    });
</script>