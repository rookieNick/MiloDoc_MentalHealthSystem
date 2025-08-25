<?php



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
include(__DIR__ . "/../connection.php");

$sqlmain = "select * from patient where pemail=?";
$stmt = $database->prepare($sqlmain);
$stmt->bind_param("s", $useremail);
$stmt->execute();
$userrow = $stmt->get_result();
$userfetch = $userrow->fetch_assoc();

$userid = $userfetch["pid"];
$username = $userfetch["pname"];
$parent_email = $userfetch["parentemail"];

date_default_timezone_set('Asia/Kuala_Lumpur');

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <!-- FontAwesome for the Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/chatbot/css/chatbot.css">


    <!-- jQuery & JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="notification_container">

    </div>

    <!-- Chatbot Icon -->
    <div class="chatbot-icon" id="chatbotIcon">
        <i class="fas fa-comment-alt"></i>
    </div>

    <!-- Chatbot Window -->
    <div class="chatbot-container" id="chatbotContainer">
        <div class="chatbot-header">
            Chat with MiloDoc
            <span class="close-btn" id="closeChatbot">&times;</span>
        </div>
        <div class="chatbot-messages" id="chatbotMessages">
            <!-- <div class="bot-message message">Hi! This is MiloDoc AI Chatbot. How can I help you today?</div> -->
        </div>
        <div class="chatbot-footer">
            <input type="text" id="userInput" placeholder="Type a message..." />
            <button id="sendMessage"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>



</body>

</html>


<script>
    $(document).ready(function() {




        var user_id = parseInt(<?php echo json_encode($userid); ?>, 10);
        var parent_email = <?php echo json_encode($parent_email); ?>;
        // console.log("User ID:", user_id); // Debugging step

        var msgCount = 0;
        let botFollowUpMessage = null;

        // Open chatbot
        $("#chatbotIcon").click(function() {
            $("#chatbotIcon").fadeOut();

            $("#chatbotContainer").fadeIn();
            loadPreviousMessages(); // Load previous messages when chatbot opens



            //loading icon
            let loadingMessage = $(`
                <div class="bot-message message typing-indicator">
                    <span></span><span></span><span></span>
                </div>
            `);
            setTimeout(() => {
                $("#chatbotMessages").append(loadingMessage);
                scrollToBottom();
            }, 2000); // Delay ensures messages don't get cleared


            //load last 7 journal and today conversation
            //display follow up message
            // Call AJAX to retrieve memory (journals + today's conversation)
            $.ajax({
                url: "http://localhost:8000/chatbot/retrieve_memory.php?user_id=" + user_id,
                type: "GET",
                dataType: "json",
                success: function(data) {

                    userMessage = '';


                    // Send message to chatbot API
                    $.ajax({
                        url: "http://127.0.0.1:8001/chat",
                        type: "POST",
                        data: JSON.stringify({
                            message: userMessage,
                            memory: data.memory

                        }),
                        contentType: "application/json",
                        success: function(response) {

                            setTimeout(() => {
                                $("#chatbotMessages").append(`<div class="bot-message message">${response.response}</div>`);
                                scrollToBottom();

                                // Remove loading message
                                $(".typing-indicator").remove();

                            }, 2000); // Delay ensures messages don't get cleared


                            botFollowUpMessage = response.response;

                        },
                        error: function() {
                            $("#chatbotMessages").append(`<div class="bot-message message">Error connecting to chatbot.</div>`);
                            scrollToBottom();
                        }
                    });
                },
                error: function(xhr, status, error) {
                    console.error("Error retrieving memory:", error);
                    console.error("XHR Response:", xhr.responseText);
                }
            });




        });

        // Close chatbot
        $("#closeChatbot").click(function() {
            $("#chatbotIcon").fadeIn();



            $("#chatbotContainer").fadeOut();

            if (msgCount >= 1) {
                // Before closing, call ajax to summarise_conversation.php
                $.ajax({
                    url: "http://localhost:8000/chatbot/get_conversation.php?user_id=" + user_id, // Adjust path as needed
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        if (data.conversation && data.conversation.trim() !== "") {
                            // Call the model API (FastAPI) to summarize the conversation
                            $.ajax({
                                url: "http://127.0.0.1:8001/summarise", // Your model API endpoint
                                type: "POST",
                                data: JSON.stringify({
                                    message: data.conversation,
                                    memory: ''
                                }),
                                contentType: "application/json",
                                dataType: "json",
                                success: function(modelResponse) {
                                    if (modelResponse.summary) {
                                        // Now send the summary to PHP to store in the journal table
                                        $.ajax({
                                            url: "http://localhost:8000/chatbot/store_journal.php", // Adjust path as needed
                                            type: "POST",
                                            data: {
                                                user_id: user_id,
                                                summary: modelResponse.summary
                                            },
                                            success: function(storeResponse) {
                                                console.log("Journal summary saved:", storeResponse);
                                                showNotification("Journal updated! Please refresh to get latest journal", "#80ff63");
                                            },
                                            error: function() {
                                                console.error("Error storing summary.");
                                            }
                                        });
                                    } else {
                                        console.error("No summary returned from model API.");
                                        $("#chatbotContainer").fadeOut();
                                    }
                                },
                                error: function() {
                                    console.error("Error summarizing conversation.");
                                    $("#chatbotContainer").fadeOut();
                                }
                            });


                            // Call the model API (FastAPI) to analyze the conversation
                            $.ajax({
                                url: "http://127.0.0.1:8001/analyze_chat_report",
                                type: "POST",
                                contentType: "application/json",
                                data: JSON.stringify({
                                    message: data.conversation,
                                    memory: ''
                                }),
                                success: function(response) {
                                    console.log("Raw Model Response:", response); // Debugging

                                    // If API returns an error, stop execution
                                    if (response.error) {
                                        console.error("API Error:", response.error);
                                        return;
                                    }

                                    // Extract raw_response as a string
                                    let rawText = response.report.trim();

                                    // Function to extract a field value using regex
                                    function extractField(fieldName, text) {
                                        let regex = new RegExp(`"${fieldName}"\\s*:\\s*(?:"([^"]+)"|(\\d+))`);
                                        let match = text.match(regex);
                                        return match ? (match[2] !== undefined ? parseInt(match[2], 10) : match[1]) : null;
                                    }

                                    // Extract values manually
                                    let extractedData = {
                                        overall_score: extractField("overall_score", rawText),
                                        sentiment_score: extractField("sentiment_score", rawText),
                                        stress_level: extractField("stress_level", rawText),
                                        anxiety_level: extractField("anxiety_level", rawText),
                                        depression_risk: extractField("depression_risk", rawText),
                                        overall_score_reason: extractField("overall_score_reason", rawText),
                                        sentiment_score_reason: extractField("sentiment_score_reason", rawText),
                                        stress_level_reason: extractField("stress_level_reason", rawText),
                                        anxiety_level_reason: extractField("anxiety_level_reason", rawText),
                                        depression_risk_reason: extractField("depression_risk_reason", rawText),
                                    };

                                    console.log("Extracted Data:", extractedData); // Debugging output

                                    // Ensure values exist before sending to backend
                                    if (extractedData.overall_score !== null) {
                                        $.ajax({
                                            url: "http://localhost:8000/chatbot/store_mental_health_status_report.php",
                                            type: "POST",
                                            data: {
                                                user_id: user_id,
                                                overall_score: extractedData.overall_score,
                                                sentiment_score: extractedData.sentiment_score,
                                                stress_level: extractedData.stress_level,
                                                anxiety_level: extractedData.anxiety_level,
                                                depression_risk: extractedData.depression_risk,
                                                overall_score_reason: extractedData.overall_score_reason,
                                                sentiment_score_reason: extractedData.sentiment_score_reason,
                                                stress_level_reason: extractedData.stress_level_reason,
                                                anxiety_level_reason: extractedData.anxiety_level_reason,
                                                depression_risk_reason: extractedData.depression_risk_reason
                                            },
                                            success: function() {
                                                console.log("Mental health report saved successfully.");
                                                showNotification("Mental health report updated! Please refresh to see the latest report.", "#80ff63");
                                            },
                                            error: function() {
                                                console.error("Error storing mental health report.");
                                            }
                                        });
                                    } else {
                                        console.error("Invalid response: Could not extract required values.");
                                    }
                                },
                                error: function() {
                                    console.error("Error analyzing conversation.");
                                }
                            });




                        } else {
                            console.log("No conversation found for today.");
                            $("#chatbotContainer").fadeOut();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error:", error);
                        console.error("Status:", status);
                        console.error("XHR Response:", xhr.responseText); // Print full error response
                        $("#chatbotContainer").fadeOut();

                    }

                });
            }

            msgCount = 0;
        });




        // Send message
        $("#sendMessage").click(function() {
            sendMessage();
        });

        // Allow pressing "Enter" to send message
        $("#userInput").keypress(function(e) {
            if (e.which == 13) {
                sendMessage();
            }
        });


        function loadPreviousMessages() {
            $.ajax({
                url: "http://localhost:8000/chatbot/get_previous_conversation.php?user_id=" + user_id,
                type: "GET",
                dataType: "json",
                success: function(data) {
                    if (!data || typeof data.conversation !== "string") {
                        console.error("Invalid conversation data:", data);
                        return;
                    }

                    // $("#chatbotMessages").empty(); // Clear previous messages

                    let messages = data.conversation.split("\n");
                    let lastDate = ""; // Track the last date to prevent duplicate headers

                    messages.forEach(msg => {
                        msg = msg.trim(); // Remove extra spaces

                        let match = msg.match(/^\[(\d{2} \w{3} \d{4}) (\d{2}:\d{2})\] (User|Bot): (.*)$/);
                        if (match) {
                            let date = match[1]; // Extract date (e.g., 19 Mar 2025)
                            let time = match[2]; // Extract time (e.g., 14:30)
                            let sender = match[3]; // Extract User/Bot
                            let text = match[4]; // Extract message

                            // Insert date header only if it's a new date
                            if (date !== lastDate) {
                                $("#chatbotMessages").append(`<div class="date-header">${date}</div>`);
                                lastDate = date; // Update lastDate to prevent duplicate headers
                            }

                            // Append message after the date
                            if (sender === "User") {
                                $("#chatbotMessages").append(`<div class="user-message message">${text} <span class="user-time message-time">${time}</span> </div>`);
                            } else if (sender === "Bot") {
                                $("#chatbotMessages").append(`<div class="bot-message message">${text} <span class="bot-time message-time">${time}</span> </div>`);
                            }
                        }
                    });


                    scrollToBottom();
                },
                error: function(xhr, status, error) {
                    console.error("Error loading conversation:", error);
                    console.error("XHR Response:", xhr.responseText);
                }
            });
        }






        function sendMessage() {
            let userMessage = $("#userInput").val().trim();
            if (userMessage === "") return;

            // Display user message
            $("#chatbotMessages").append(`<div class="user-message message">${userMessage}</div>`);
            $("#userInput").val(""); // Clear input
            scrollToBottom();

            var user_id = <?php echo $userid; ?>;

            // Show animated typing indicator
            let loadingMessage = $(`
                <div class="bot-message message typing-indicator">
                    <span></span><span></span><span></span>
                </div>
            `);
            $("#chatbotMessages").append(loadingMessage);
            scrollToBottom();



            //load last 7 journal and today conversation
            // Call AJAX to retrieve memory (journals + today's conversation)
            $.ajax({
                url: "http://localhost:8000/chatbot/retrieve_memory.php?user_id=" + user_id,
                type: "GET",
                dataType: "json",
                success: function(data) {

                    // Send message to chatbot API
                    $.ajax({
                        url: "http://127.0.0.1:8001/chat",
                        type: "POST",
                        data: JSON.stringify({
                            message: userMessage,
                            user_id: user_id,
                            memory: data.memory

                        }),
                        contentType: "application/json",
                        success: function(response) {
                            let botReply = response.response || "Sorry, I didn't understand that.";
                            let isSuicidal = response.is_suicidal || false;
                            let confidence = response.confidence || 0.0;



                            $("#chatbotMessages").append(`<div class="bot-message message">${botReply}</div>`);
                            scrollToBottom();

                            // Remove loading message
                            $(".typing-indicator").remove();


                            //if user initiate conversation, only save the bot follow up message (use msgCount)
                            // only save the bot follow up message on the first message user sent
                            if (botFollowUpMessage != null) {
                                $.ajax({
                                    url: "http://localhost:8000/chatbot/store_chat.php", // The PHP endpoint to save chat
                                    type: "POST",
                                    data: {
                                        user_id: user_id, // match your session user_id
                                        user_message: null,
                                        bot_message: botFollowUpMessage,
                                        is_suicidal: null, // Convert boolean to integer
                                        confidence: null
                                    },
                                    success: function(dbResponse) {
                                        console.log("Conversation saved to DB:", dbResponse);

                                    },
                                    error: function() {
                                        console.error("Error saving conversation to DB.");
                                    }
                                });

                                botFollowUpMessage = null;
                            }



                            // Step 2: Send userMessage + botReply to PHP for DB saving
                            $.ajax({
                                url: "http://localhost:8000/chatbot/store_chat.php", // The PHP endpoint to save chat
                                type: "POST",
                                data: {
                                    user_id: user_id, // match your session user_id
                                    user_message: userMessage,
                                    bot_message: botReply,
                                    is_suicidal: isSuicidal ? 1 : 0, // Convert boolean to integer
                                    confidence: confidence
                                },
                                success: function(dbResponse) {
                                    console.log("Conversation saved to DB:", dbResponse);

                                },
                                error: function() {
                                    console.error("Error saving conversation to DB.");
                                }
                            });



                            // Step 3: Send email (suicidal) to guardian 
                            if (isSuicidal == true) {


                                $.ajax({
                                    url: "http://localhost:8000/chatbot/inform_suicidal.php",
                                    type: "POST",
                                    dataType: "json",
                                    data: {
                                        user_id: user_id, // Replace with actual user ID dynamically
                                        parent_email: parent_email // Replace with actual user email dynamically
                                    },
                                    success: function(response) {
                                        if (response.success) {
                                            console.log("Email success:", response.message);
                                            showNotification("Suicidal intention detected, informing guardian... Please seek professional help immediately", "#ff5040")

                                        } else {
                                            console.error("Email failed:", response.message);
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.error("Error:", error);
                                        console.error("XHR Response:", xhr.responseText);
                                    }
                                });



                            }





                        },
                        error: function(xhr, status, error) {
                            $("#chatbotMessages").append(`<div class="bot-message message">Error connecting to chatbot.</div>`);
                            scrollToBottom();

                            console.error("Chatbot API call failed:", error);
                            console.error("XHR Response:", xhr.responseText);
                        }
                    });
                },
                error: function(xhr, status, error) {
                    console.error("Error retrieving memory:", error);
                    console.error("XHR Response:", xhr.responseText);
                }
            });



            msgCount = msgCount + 1;
        }

        function scrollToBottom() {
            let chatMessages = $("#chatbotMessages");
            chatMessages.scrollTop(chatMessages[0].scrollHeight);
        }




    });







    // Function to display a notification pop-up
    function showNotification(message, color) {
        // Create a unique notification element
        let notificationId = "notificationPopup_" + new Date().getTime(); // Unique ID
        let notificationDiv = $(`
            <div id="${notificationId}" style="
                position: relative;
                background: ${color};
                color: #fff;
                padding: 15px 20px;
                border-radius: 5px;
                z-index: 10000;
                box-shadow: 0 2px 5px rgba(0,0,0,0.3);
                font-family: Arial, sans-serif;
                opacity: 0; /* Start hidden */
                transition: opacity 0.5s ease-in-out;
            ">
                <span>${message}</span>
                <button style="
                    background: transparent;
                    border: none;
                    color: #fff;
                    font-size: 20px;
                    float: right;
                    cursor: pointer;
                " onclick="$(this).parent().fadeOut();">&times;</button>
            </div>
        `);

        $(".notification_container").append(notificationDiv);

        // Fade in effect
        setTimeout(() => {
            $(`#${notificationId}`).css("opacity", "2");
        }, 50);

        // Auto-hide after 30 seconds
        setTimeout(() => {
            $(`#${notificationId}`).fadeOut(500, function() {
                $(this).remove(); // Remove the element after fading out
            });
        }, 30000);
    }
</script>