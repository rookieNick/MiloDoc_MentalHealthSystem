<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
        
    <title>Doctors</title>
    <style>
        .popup{
            animation: transitionIn-Y-bottom 0.5s;
        }
        .sub-table{
            animation: transitionIn-Y-bottom 0.5s;
        }
        .scroll-table-wrapper {
            max-height: 600px;
            overflow-y: auto;
            margin-top: 10px;
            border: 1px solid #ddd;
            width: 93%;
        }

        .sub-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sub-table thead th {
            position: sticky;
            top: 0;
            background-color: #f1f1f1;
            z-index: 1;
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ccc;
        }

        .sub-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .height670{
            width: 100%;
            height: 670px;
        }

        .sub-table.scrolldown.add-doc-form-container {
            max-height: 490px;
            overflow-y: auto;
        }
</style>
</head>
<body>
    <?php
    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
            header("location: ../login.php");
        }

    }else{
        header("location: ../login.php");
    }
    
    

    //import database
    include("../connection.php");
    include(__DIR__ . '/../minigames/gamedb.php');
    $quizList = getAllQuizQuestions();
    
    ?>
    <div class="container">
        <?php include(__DIR__ . '/adminMenu.php'); ?>
        <div class="dash-body">
        <table border="0" width="100%" style="border-spacing: 0; margin:0; padding:0; margin-top:25px;">
    <tr>
        <td colspan="4">
            <center>
                <div class="height670 scroll">
                <table border="0" width="100%" style="border-spacing: 0; margin:0; padding:0; margin-top:25px;">
    <tr>
        <td width="13%">
            <a href="manageGames.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                <font class="tn-in-text">Back</font></button></a>
        </td>
        <td>
            <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">All Quiz Questions (<?php echo count($quizList); ?>)</p>
        </td>
        <td colspan="2" style="padding-right: 45px;">
            <div style="display: flex; justify-content: flex-end;">
                <a href="?action=add&id=none&error=0" class="non-style-link">
                    <button class="login-btn btn-primary btn button-icon" style="background-image: url('../img/icons/add.svg');">
                        Add New
                    </button>
                </a>
            </div>
        </td>
    </tr>

    <tr>
        <td colspan="4">
            <center>
                <div class="height670 scroll">
                <div class="scroll-table-wrapper">
                    <table class="sub-table">
                        <thead style="transform: translateY(-1px);">
                            <tr>
                                <th>Question ID</th>
                                <th>Question Text</th>
                                <th>Correct Answer</th>
                                <th>Status</th>
                                <th>Events</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                if (empty($quizList)) {
                                    echo '<tr>
                                            <td colspan="5">
                                                <br><br><br>
                                                <center>
                                                    <img src="../img/notfound.svg" width="25%">
                                                    <br>
                                                    <p class="heading-main12" style="font-size:20px;color:rgb(49, 49, 49)">
                                                        No quiz questions found!
                                                    </p>
                                                </center>
                                                <br><br><br>
                                            </td>
                                        </tr>';
                                } else {
                                    foreach ($quizList as $quiz) {
                                        $status = htmlspecialchars($quiz["status"]);
                                        $badgeStyle = ($status === "enabled")
                                        ? 'background-color: #d4edda; color: #155724;'
                                        : 'background-color: #f8d7da; color: #721c24;';


                                        echo '<tr>
                                            <td>' . $quiz["questionId"] . '</td>
                                            <td>' . htmlspecialchars($quiz["questionText"]) . '</td>
                                            <td>' . htmlspecialchars($quiz["correctAnswerText"]) . '</td>
                                            <td>
                                                <span style="padding: 8px 12px; border-radius: 12px; font-weight: bold; font-size: 0.9em; ' . $badgeStyle . '">
                                                    ' . $status . '
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display:flex;justify-content: center;">
                                                    <a href="?action=edit&id=' . $quiz["questionId"] . '&error=0" class="non-style-link">
                                                        <button class="btn-primary-soft btn button-icon btn-edit">
                                                            <font class="tn-in-text">Edit</font>
                                                        </button>
                                                    </a>
                                                    &nbsp;&nbsp;&nbsp;<a href="?action=view&id=' . $quiz["questionId"] . '" class="non-style-link">
                                                        <button class="btn-primary-soft btn button-icon btn-view">
                                                            <font class="tn-in-text">View</font>
                                                        </button>
                                                    </a>
                                                    &nbsp;&nbsp;&nbsp;';
                                                if ($status === "enabled") {
                                                    echo '<a href="?action=drop&id=' . $quiz["questionId"] . '&name=' . $quiz["questionText"] . '" class="non-style-link">
                                                        <button class="btn-primary-soft btn button-icon btn-delete">
                                                            <font class="tn-in-text">Disable</font>
                                                        </button>
                                                    </a>';
                                                } else {
                                                    echo '<a href="?action=activate&id=' . $quiz["questionId"] . '&name=' . $quiz["questionText"] . '" class="non-style-link">
                                                        <button class="btn-primary-soft btn button-icon btn-enabled">
                                                            <font class="tn-in-text">Enable</font>
                                                        </button>
                                                    </a>';
                                                }

                                            echo '</div>
                                            </td>
                                        </tr>';
                                    }
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
                        </div>
                        </center>
                   </td> 
                </tr>
                       
                        
                        
            </table>
        </div>
    </div>
    <?php 
    if($_GET){
        
        $id=$_GET["id"];
        $action=$_GET["action"];
        if($action=='drop'){
            $nameget=$_GET["name"];
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                        <h2>Are you sure?</h2>
                        <a class="close" href="manageQuiz.php">&times;</a>
                        <div class="content">
                            You want to disabled this question?<br>('.substr($nameget,0,40).').
                            
                        </div>
                        <div style="display: flex;justify-content: center;">
                        <a href="disabled-question.php?id='.$id.'" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"<font class="tn-in-text">&nbsp;Yes&nbsp;</font></button></a>&nbsp;&nbsp;&nbsp;
                        <a href="manageQuiz.php" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;No&nbsp;&nbsp;</font></button></a>

                        </div>
                    </center>
            </div>
            </div>
            ';
        }elseif($action=='activate'){
            $nameget=$_GET["name"];
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                        <h2>Are you sure?</h2>
                        <a class="close" href="manageQuiz.php">&times;</a>
                        <div class="content">
                            You want to activate this question?<br>('.substr($nameget,0,40).').
                            
                        </div>
                        <div style="display: flex;justify-content: center;">
                        <a href="disabled-question.php?id='.$id.'&activate=true" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"<font class="tn-in-text">&nbsp;Yes&nbsp;</font></button></a>&nbsp;&nbsp;&nbsp;
                        <a href="manageQuiz.php" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;No&nbsp;&nbsp;</font></button></a>

                        </div>
                    </center>
            </div>
            </div>
            ';
        }elseif ($action == 'view') {
            if (!isset($_GET["id"])) {
                echo "Quiz ID is missing!";
                exit;
            }
        
            $id = $_GET["id"];
            $quiz = getQuizQuestionById($id);
        
            if (!$quiz) {
                echo "Quiz not found.";
                exit;
            }
        
            $questionText = htmlspecialchars($quiz["questionText"]);
            $optionA = htmlspecialchars($quiz["optionA"]);
            $optionB = htmlspecialchars($quiz["optionB"]);
            $optionC = htmlspecialchars($quiz["optionC"]);
            $optionD = htmlspecialchars($quiz["optionD"]);
            $correctAnswer = htmlspecialchars($quiz["correctAnswer"]);
            $status = htmlspecialchars($quiz["status"]);
        
            echo '
            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <h2></h2>
                        <a class="close" href="manageQuiz.php">&times;</a>
                        <div class="content">
                            mDoc Web App - Quiz Viewer
                        </div>
                        <div style="display: flex; justify-content: center;">
                            <div class="sub-table scrolldown add-doc-form-container" style="width: 80%;">
                            <table width="100%" border="0">
                                <tr>
                                    <td colspan="2">
                                        <p style="padding: 0; margin: 0; text-align: left; font-size: 25px; font-weight: 500;">View Quiz Question</p><br><br>
                                    </td>
                                </tr>
                                <tr><td class="label-td" colspan="2"><label>Question:</label></td></tr>
                                <tr><td class="label-td" colspan="2">'.$questionText.'<br><br></td></tr>
        
                                <tr><td class="label-td" colspan="2"><label>Option A:</label></td></tr>
                                <tr><td class="label-td" colspan="2">'.$optionA.'<br><br></td></tr>
        
                                <tr><td class="label-td" colspan="2"><label>Option B:</label></td></tr>
                                <tr><td class="label-td" colspan="2">'.$optionB.'<br><br></td></tr>
        
                                <tr><td class="label-td" colspan="2"><label>Option C:</label></td></tr>
                                <tr><td class="label-td" colspan="2">'.$optionC.'<br><br></td></tr>
        
                                <tr><td class="label-td" colspan="2"><label>Option D:</label></td></tr>
                                <tr><td class="label-td" colspan="2">'.$optionD.'<br><br></td></tr>
        
                                <tr><td class="label-td" colspan="2"><label>Correct Answer:</label></td></tr>
                                <tr><td class="label-td" colspan="2">'.$correctAnswer.'<br><br></td></tr>
        
                                <tr><td class="label-td" colspan="2"><label>Status:</label></td></tr>
                                <tr><td class="label-td" colspan="2">'.$status.'<br><br></td></tr>
        
                                <tr>
                                    <td colspan="2">
                                        <a href="manageQuiz.php"><input type="button" value="OK" class="login-btn btn-primary-soft btn"></a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </center>
                    <br><br>
                </div>
            </div>';
        }elseif ($action == 'add') {
            $error_1 = $_GET["error"] ?? '0';
            $errorlist = array(
                '1' => '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Invalid correct answer. Must be A, B, C, or D.</label>',
                '2' => '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">All fields are required.</label>',
                '3' => '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Database error while inserting the question.</label>',
                '4' => '', // Success
                '0' => '',
            );
        
            if ($error_1 != '4') {
                echo '
                <div id="popup1" class="overlay">
                    <div class="popup">
                        <center>
                            <a class="close" href="manageQuiz.php">&times;</a> 
                            <div style="display: flex;justify-content: center;">
                                <div class="abc">
                                    <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                                        <tr>
                                            <td class="label-td" colspan="2">' . $errorlist[$error_1] . '</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2">
                                                <p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Add New Quiz Question</p><br><br>
                                            </td>
                                        </tr>
                                        <form action="add-new-question.php" method="POST" class="add-new-form">
                                            <tr>
                                                <td class="label-td" colspan="2">
                                                    <label class="form-label">Question Text:</label>
                                                    <input type="text" name="questionText" class="input-text" placeholder="Enter the question" required>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="label-td" colspan="2">
                                                    <label class="form-label">Option A:</label>
                                                    <input type="text" name="optionA" class="input-text" placeholder="Option A" required>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="label-td" colspan="2">
                                                    <label class="form-label">Option B:</label>
                                                    <input type="text" name="optionB" class="input-text" placeholder="Option B" required>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="label-td" colspan="2">
                                                    <label class="form-label">Option C:</label>
                                                    <input type="text" name="optionC" class="input-text" placeholder="Option C" required>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="label-td" colspan="2">
                                                    <label class="form-label">Option D:</label>
                                                    <input type="text" name="optionD" class="input-text" placeholder="Option D" required>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="label-td" colspan="2">
                                                    <label class="form-label">Correct Answer:</label>
                                                    <select name="correctAnswer" class="box" required>
                                                        <option value="">--Select--</option>
                                                        <option value="A">A</option>
                                                        <option value="B">B</option>
                                                        <option value="C">C</option>
                                                        <option value="D">D</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2">
                                                    <input type="reset" value="Reset" class="login-btn btn-primary-soft btn">
                                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                    <input type="submit" value="Add" class="login-btn btn-primary btn">
                                                </td>
                                            </tr>
                                        </form>
                                    </table>
                                </div>
                            </div>
                        </center>
                        <br><br>
                    </div>
                </div>';
            } else {
                echo '
                <div id="popup1" class="overlay">
                    <div class="popup">
                        <center>
                        <br><br><br><br>
                            <h2>Quiz Question Added Successfully!</h2>
                            <a class="close" href="manageQuiz.php">&times;</a>
                            <div class="content"></div>
                            <div style="display: flex;justify-content: center;">
                                <a href="manageQuiz.php" class="non-style-link">
                                    <button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;">
                                        <font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font>
                                    </button>
                                </a>
                            </div>
                            <br><br>
                        </center>
                    </div>
                </div>';
            }
        }elseif($action == 'edit'){
            $question = getQuizQuestionById($id);
        
            $questionText = $question['questionText'];
            $optionA = $question['optionA'];
            $optionB = $question['optionB'];
            $optionC = $question['optionC'];
            $optionD = $question['optionD'];
            $correctAnswer = $question['correctAnswer'];
            $status = $question['status'];
        
            $error_1 = $_GET["error"] ?? '0';
        
            $errorlist = array(
                '1' => '<label class="form-label" style="color:red;text-align:center;">All fields are required.</label>',
                '2' => '<label class="form-label" style="color:red;text-align:center;">Invalid correct answer.</label>',
                '3' => '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Database error while inserting the question.</label>',
                '4' => '<label class="form-label" style="color:green;text-align:center;">Edit Successful.</label>', // Success
                '0' => '',
            );
        
            echo '
            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <a class="close" href="manageQuiz.php">&times;</a>
                        <div style="display: flex;justify-content: center;">
                            <div class="abc">
                                <form action="update-quiz-question.php" method="POST" class="add-new-form">
                                <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                                    <tr>
                                        <td colspan="2">' . $errorlist[$error_1] . '</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <p style="font-size: 25px; font-weight: 500; text-align: left;">Edit Quiz Question</p>
                                            Question ID: '.$id.'<br><br>
                                            <input type="hidden" name="questionId" value="'.$id.'">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label-td" colspan="2">
                                            <label>Question Text:</label>
                                            <textarea name="questionText" class="input-text" required>'.$questionText.'</textarea>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label-td"><label>Option A:</label></td>
                                        <td class="label-td"><input type="text" name="optionA" class="input-text" value="'.$optionA.'" required></td>
                                    </tr>
                                    <tr>
                                        <td class="label-td"><label>Option B:</label></td>
                                        <td class="label-td"><input type="text" name="optionB" class="input-text" value="'.$optionB.'" required></td>
                                    </tr>
                                    <tr>
                                        <td class="label-td"><label>Option C:</label></td>
                                        <td class="label-td"><input type="text" name="optionC" class="input-text" value="'.$optionC.'" required></td>
                                    </tr>
                                    <tr>
                                        <td class="label-td"><label>Option D:</label></td>
                                        <td class="label-td"><input type="text" name="optionD" class="input-text" value="'.$optionD.'" required></td>
                                    </tr>
                                    <tr>
                                        <td class="label-td" colspan="2">
                                            <label>Correct Answer:</label>
                                            <select name="correctAnswer" class="box" required>
                                                <option value="A"'.($correctAnswer == 'A' ? ' selected' : '').'>A</option>
                                                <option value="B"'.($correctAnswer == 'B' ? ' selected' : '').'>B</option>
                                                <option value="C"'.($correctAnswer == 'C' ? ' selected' : '').'>C</option>
                                                <option value="D"'.($correctAnswer == 'D' ? ' selected' : '').'>D</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label-td" colspan="2">
                                            <label>Status:</label>
                                            <select name="status" class="box" required>
                                                <option value="enabled"'.($status == 'enabled' ? ' selected' : '').'>Enabled</option>
                                                <option value="disabled"'.($status == 'disabled' ? ' selected' : '').'>Disabled</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <input type="reset" value="Reset" class="login-btn btn-primary-soft btn">
                                            &nbsp;&nbsp;&nbsp;
                                            <input type="submit" value="Save" class="login-btn btn-primary btn">
                                        </td>
                                    </tr>
                                </table>
                                </form>
                            </div>
                        </div>
                    </center>
                </div>
            </div>';
        };
    };

?>
</div>

</body>
</html>