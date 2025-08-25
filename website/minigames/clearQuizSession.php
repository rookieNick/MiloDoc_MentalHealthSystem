<?php
session_start();
unset($_SESSION['quiz_questions']);
unset($_SESSION['user_answers']);
unset($_SESSION['question_records']);
unset($_SESSION['quiz_start_time']);
unset($_SESSION['quizBackground']); // Remove background session if stored
unset($_SESSION['playId']); // Remove playId session if stored

?>