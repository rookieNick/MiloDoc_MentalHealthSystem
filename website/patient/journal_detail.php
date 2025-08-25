<?php

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



// Get date from URL
$date_happened = $_GET['date'];

// Fetch Journal Data
$sql = "SELECT * FROM journal WHERE user_id = ? AND date_happened = ?";
$stmt2 = $database->prepare($sql);
$stmt2->bind_param("is", $userid, $date_happened);
$stmt2->execute();
$result = $stmt2->get_result();

if ($result->num_rows == 0) {
    header("Location: journal_detail_error.php");
    exit();
}

$journal = $result->fetch_assoc();


// Fetch mental health status score for the selected date
$sql_score = "SELECT * FROM mental_health_status_score WHERE user_id = ? AND report_date = ?";
$stmt_score = $database->prepare($sql_score);
$stmt_score->bind_param("is", $userid, $date_happened);
$stmt_score->execute();
$score_result = $stmt_score->get_result();
$score_data = $score_result->fetch_assoc();

// Fetch previous day's mental health status score
$sql_prev_score = "SELECT * FROM mental_health_status_score WHERE user_id = ? ORDER BY report_date DESC LIMIT 1 OFFSET 1";
$stmt_prev_score = $database->prepare($sql_prev_score);
$stmt_prev_score->bind_param("i", $userid);
$stmt_prev_score->execute();
$prev_score_result = $stmt_prev_score->get_result();
$prev_score_data = $prev_score_result->fetch_assoc();

// Function to calculate percentage change
function calculate_percentage_change($current, $previous)
{
    if ($previous == 0 || $previous === null) return "N/A";
    $change = (($current - $previous) / abs($previous)) * 100;
    return round($change, 2);
}

// Extract score values
$metrics = ['overall_score', 'sentiment_score', 'stress_level', 'anxiety_level', 'depression_risk'];
$score_display = [];

foreach ($metrics as $metric) {
    $current_score = $score_data[$metric] ?? 0;
    $previous_score = $prev_score_data[$metric] ?? 0;
    $percentage_change = calculate_percentage_change($current_score, $previous_score);
    $reason_key = $metric . "_reason";
    $reason = $score_data[$reason_key] ?? "No reason provided.";

    $score_display[$metric] = [
        'current' => $current_score,
        'change' => $percentage_change,
        'reason' => $reason
    ];
}






// Fetch Chat Conversations for the Selected Date
$sql_chat = "SELECT * FROM chatbot_conversation WHERE user_id = ? AND DATE(datetime) = ? ORDER BY datetime ASC";
$stmt_chat = $database->prepare($sql_chat);
$stmt_chat->bind_param("is", $userid, $date_happened);
$stmt_chat->execute();
$chat_result = $stmt_chat->get_result();
$chats = [];

while ($chat_row = $chat_result->fetch_assoc()) {
    $chats[] = $chat_row;
}












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
    <link rel="stylesheet" href="../css/journal_detail.css">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet" />

    <!-- FullCalendar JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- jQuery & JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


    <!-- SweetAlert2 CSS & JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <title>Journal Details</title>






</head>

<body>



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
                        <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Journal Details</p>

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



            <!-- Slide-Out History Panel -->
            <div id="journal-history-panel">
                <div class="resize-handle"></div> <!-- New: handle for resizing -->
                <div class="panel-header">
                    <h3>Journal History</h3>
                    <button id="close-history" title="Close Panel">&times;</button>
                </div>
                <div id="history-content">
                    <!-- AJAX content goes here -->

                </div>
            </div>



            <div class="container_journal_detail">
                <div class="journal_detail_left">
                    <div class="journal_detail_header">
                        <h2><?php echo date('d F Y', strtotime($journal['date_happened'])); ?></h2>

                        <div class="journal_detail_header_right">
                            <!-- Delete Button with Hover Animation -->
                            <div class="delete-container">
                                <button class="delete-button" data-journal-id="<?= $journal['journal_id'] ?>" data-report-date="<?= $journal['date_happened'] ?>" data-user-id="<?= $userid?>">
                                    <i class="fa fa-trash"></i> <!-- You can use any delete icon you want here -->
                                    <span class="delete-text">Delete</span>
                                </button>
                            </div>
                            <!-- Date Picker for Selecting Journal Date -->
                            <div class="button-wrapper">
                                <input type="date" id="datePicker" value="<?php echo $date_happened; ?>">
                                <div class="tooltip" id="dateTooltip">No journal found for selected date.</div>
                            </div>

                        </div>

                    </div>

                    <div class="card journal-entry">

                        <div class="journal-title-container">
                            <h3 class="journal-title">Journal Entry</h3>

                            <div class="journal-icon-container">
                                <!-- Comment icon -->
                                <div class="journal-icon toggle-comment">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                    </svg>
                                </div>


                                <!-- History icon -->
                                <div class="journal-icon toggle-history" title="History">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 3v5h5"></path>
                                        <path d="M3.05 13a9 9 0 1 0 .5-5.5L3 8"></path>
                                        <path d="M12 7v5l3 3"></path>
                                    </svg>
                                </div>



                            </div>

                        </div>

                        <p id="journal-content"><?php echo nl2br(htmlspecialchars($journal['content'])); ?></p>
                        <div class="journal-dates">
                            Updated: <?php echo date('d F Y', strtotime($journal['updated_date'])); ?>
                        </div>



                        <!-- Comment box (hidden by default) -->
                        <div class="comment-box" style="display: none;">
                            <textarea placeholder="Give feedback to chatbot summarisation to make your journal more accurate!" rows="3"></textarea>
                            <div class="comment-actions">
                                <button class="btn-cancel cancel-comment">Cancel</button>
                                <button class="btn-submit submit-comment">Submit</button>
                            </div>
                        </div>

                    </div>






                    <!-- Chat History Section -->
                    <div class="card chat-history">
                        <h3>Chat Conversations - <?php echo date('d F Y', strtotime($date_happened)); ?></h3>
                        <div class="chat-container">
                            <?php foreach ($chats as $chat): ?>
                                <div class="chat-message <?php echo ($chat['ResponseByUser'] == 1) ? 'user-message' : 'bot-message'; ?>">
                                    <p>

                                        <?php echo htmlspecialchars($chat['message']); ?>

                                    </p>
                                    <?php if ($chat['is_suicidal'] == 1): ?>
                                        <span class="suicidal-flag">🚩 ( <?= $chat['confidence'] ?> <b>%</b> )</span> <!-- More visible flag -->
                                    <?php endif; ?><span class="chat-time"><?php echo date('h:i A', strtotime($chat['datetime'])); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="flag-note"><span class="suicidal-flag">🚩</span> Might be Suicidal Message</p>
                    </div>


                </div>


                <div class="journal_detail_right">
                    <div class="stats-grid">
                        <?php foreach ($score_display as $metric => $data): ?>
                            <div class="card stat-card">
                                <h4><?php echo ucwords(str_replace('_', ' ', $metric)); ?></h4>
                                <p class="score">
                                    <?php echo $data['current']; ?> <span class="score_denomiator">/ 100</span>
                                    <?php if ($data['change'] != "N/A" && $data['change'] != "0" ): ?>
                                        <span class="<?php echo ($data['change'] >= 0) ? 'positive' : 'negative'; ?>">
                                            <?php echo ($data['change'] >= 0) ? '↑' : '↓'; ?> <?php echo abs($data['change']); ?>%
                                        </span>
                                    <?php endif; ?>
                                </p>
                                <p class="description"><?php echo htmlspecialchars($data['reason']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>


            </div>
        </div>


    </div>




    </div>


    <?php include __DIR__ . '/../chatbot/chatbot_window.php'; ?>

</body>

</html>

<script>
    $(document).ready(function() {

        var user_id = parseInt(<?php echo json_encode($userid); ?>, 10);
        var journal_content = <?php echo json_encode($journal['content']); ?>;
        var journal_id = <?php echo json_encode($journal['journal_id']); ?>;
        let oldDate = $('#datePicker').val(); // Store initial date

        $('#datePicker').on('change', function () {
            const selectedDate = $(this).val();
            if (!selectedDate) return;

            fetch("checkJournalDateExist.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `user_id=${user_id}&date=${selectedDate}`
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "FOUND") {
                    window.location.href = "journal_detail.php?date=" + selectedDate;
                } else {
                    // Reset to old date
                    $('#datePicker').val(oldDate);

                    // Show tooltip
                    const tooltip = document.getElementById('dateTooltip');
                    tooltip.style.visibility = 'visible';
                    tooltip.style.opacity = '1';

                    // Hide tooltip after 3 seconds
                    setTimeout(() => {
                        tooltip.style.opacity = '0';
                        tooltip.style.visibility = 'hidden';
                    }, 3000);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                $('#datePicker').val(oldDate);
            });
        });

        $('.toggle-comment').on('click', function() {
            const $journalEntry = $(this).closest('.journal-entry');
            const $commentBox = $journalEntry.find('.comment-box');

            if ($commentBox.is(':visible')) {
                $commentBox.hide();
            } else {
                $commentBox.show().find('textarea').focus();
            }
        });

        $('.cancel-comment').on('click', function() {
            const $commentBox = $(this).closest('.comment-box');
            $commentBox.hide();
            $commentBox.find('textarea').val('');
        });

        $('.submit-comment').on('click', function() {
            const $commentBox = $(this).closest('.comment-box');
            const userFeedback = $commentBox.find('textarea').val().trim();

            if (userFeedback) {
                // TODO: AJAX to send the comment
                // Send message to chatbot API
                $.ajax({
                    url: "http://127.0.0.1:8001/journal_feedback",
                    type: "POST",
                    data: JSON.stringify({
                        memory: journal_content,
                        message: userFeedback,
                    }),
                    contentType: "application/json",
                    success: function(response) {

                        console.log("journal content: " + journal_content + "\nuser feedback: " + userFeedback);

                        console.log("Raw response from API:", response);
                        console.log("refined_journal:", response.refined_journal);

                        let extracted_refined_journal = null;

                        // const match = rawResponse.match(/refined_journal\s*:\s*['"]([^'"]+)['"]/);

                        // if (match && match[1]) {
                        //     extracted_refined_journal = match[1];
                        //     console.log("Extracted with regex:", response);
                        // }

                        extracted_refined_journal = response.refined_journal;

                        if (extracted_refined_journal) {
                            // Now send the summary to PHP to store in the journal table
                            $.ajax({
                                url: "http://localhost:8000/chatbot/refine_journal.php", // Adjust path as needed
                                type: "POST",
                                data: {
                                    journal_id: journal_id,
                                    journal_content: journal_content,
                                    user_feedback: userFeedback,
                                    refined_journal: extracted_refined_journal
                                },
                                success: function(extracted_refined_journal) {
                                    console.log("Journal summary updated:", extracted_refined_journal);
                                    showNotification("Journal updated! Please refresh to get latest journal", "#80ff63");
                                },
                                error: function() {
                                    console.error("Error storing summary.");
                                }
                            });
                        } else {
                            console.error(refinedJournal);

                            //show error notification
                        }




                    },
                    error: function(xhr, status, error) {

                        console.error("Error: ", error);
                        console.error("XHR Response:", xhr.responseText);
                    }
                });

                // Clear the textarea
                $commentBox.find('textarea').val('');

                // Show notification (replace with your own logic)

            } else {
                alert('Please write a comment before submitting.');
            }
        });






        // Toggle the history panel when the history icon is clicked
        $('.toggle-history').on('click', function() {
            // Check if the panel is open or closed and toggle it
            $('#journal-history-panel').toggleClass('open');

            // Optional: You can load the history via AJAX when opening the panel
            if ($('#journal-history-panel').hasClass('open')) {
                // Load history via AJAX only when opening the panel (optional)
                $.ajax({
                    url: 'http://localhost:8000/chatbot/get_journal_history.php',
                    type: 'POST',
                    data: {
                        journal_id: journal_id // Use the dynamic journal ID if available
                    },
                    success: function(data) {
                        $('#history-content').html(data);
                    },
                    error: function() {
                        $('#history-content').html('<p>Error loading history.</p>');
                    }
                });
            }
        });

        $('#close-history').on('click', function() {
            $('#journal-history-panel').removeClass('open');
        });





        // Store the original journal content once when the page loads
        const originalContent = $('#journal-content').html();

        // Delegate hover event to dynamically loaded entries
        $('#history-content').on('mouseenter', '.history-entry', function() {
            const previousContent = $(this).find('.entry-section.original p').text();

            $('#journal-content').stop(true, true).fadeOut(100, function() {
                $(this).html(previousContent).fadeIn(200)
                    .css('color', '#3396ff') // highlight color
                    .animate({
                        color: 'black'
                    }, 300); // fade back to black
            });
        });

        // On hover out: revert content and style
        $('#history-content').on('mouseleave', '.history-entry', function() {
            $('#journal-content').stop(true, true).fadeOut(100, function() {
                $(this).html(originalContent).fadeIn(200)
                    .css('color', '#3396ff') // highlight color again briefly
                    .animate({
                        color: 'black'
                    }, 300); // fade back to black
            });
        });













        let isResizing = false;
        let lastDownX = 0;

        $('.resize-handle').on('mousedown', function(e) {
            isResizing = true;
            lastDownX = e.clientX;
            e.preventDefault();
        });

        $(document).on('mousemove', function(e) {
            if (!isResizing) return;

            const dx = lastDownX - e.clientX;
            const panel = $('#journal-history-panel');
            const currentWidth = panel.width();
            const newWidth = Math.min(Math.max(currentWidth + dx, 250), 1000); // clamp between 250-600

            panel.css('width', newWidth + 'px');
            lastDownX = e.clientX;
        });

        $(document).on('mouseup', function() {
            isResizing = false;
        });








        // Delegate the click to dynamically added restore buttons
        $('#history-content').on('click', '.restore-btn', function() {
            const journalId = $(this).data('journal-id');
            const feedbackId = $(this).data('feedback-id');

            $.ajax({
                url: 'http://localhost:8000/chatbot/restore_journal.php',
                type: 'POST',
                data: {
                    journal_id: journalId,
                    journal_feedback_id: feedbackId
                },
                success: function(response) {
                    alert("Journal has been restored.");
                    location.reload(); // This reloads the page
                },
                error: function() {
                    alert("Error restoring journal. Please try again.");
                }
            });
        });










        $('.delete-button').on('click', function(e) {
            e.preventDefault();
            const journalId = $(this).data('journal-id');
            const report_date = $(this).data('report-date');
            const user_id = $(this).data('user-id');
            const button = $(this);

            Swal.fire({
                title: 'Are you sure?',
                text: "This journal entry will be deleted! Process is irreversible",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Yes, delete it!',
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'http://localhost:8000/chatbot/delete_journal.php',
                        type: 'POST',
                        data: {
                            journal_id: journalId,
                            report_date: report_date,
                            user_id: user_id,
                             
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'The journal has been deleted.',
                                'success'
                            ).then(() => {
                                window.location.href = 'journal.php'; // Redirect after confirmation
                            });
                        },
                        error: function(xhr, status, error) {
                            Swal.fire(
                                'Error!',
                                'Something went wrong while deleting.',
                                'error'
                            );
                        }
                    });
                }
            });
        });

    });
</script>

<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>