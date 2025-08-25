<?php
session_start();
include(__DIR__ . '../../connection.php');

$userEmail = $_SESSION['user'];
$query = "SELECT g.playId, p.playDate, g.percentageScore 
          FROM gameplay p 
          JOIN quizgame g ON g.playId = p.playId 
          WHERE p.userEmail = ? 
          ORDER BY p.playDate DESC";

$stmt = $database->prepare($query);
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quiz History</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-image: url('/img/quizHistoryBackground.jpeg');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .container {
            max-width: 900px;
            margin: auto;
            background: rgba(255, 255, 255, 0.8);
            padding: 20px;
            border-radius: 10px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }

        th {
            background: #f0f0f0;
        }

        button {
            padding: 5px 10px;
            cursor: pointer;
            background-color: green;
            color: white;
            border: none;
            border-radius: 5px;
        }

        button:hover {
            background-color: darkgreen;
        }

        .top-left-btns {
            position: fixed;
            top: 10px;
            left: 10px;
        }

        #backBtn {
            margin: 5px;
            padding: 10px 15px;
            font-size: 16px;
            cursor: pointer;
            background-color: white;
            color: black;
            border: 1px solid black;
        }
    </style>
</head>
<body>
<div class="top-left-btns">
    <button id="backBtn" onclick="goBack()">Back</button>
</div>
<div class="container">
    <h2>Your Quiz History</h2>
    <table>
        <tr>
            <th>Play Date</th>
            <th>Score (%)</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['playDate'] ?></td>
            <td><?= round($row['percentageScore'], 2) ?>%</td>
            <td><a href="quizHistoryDetail.php?playId=<?= $row['playId'] ?>"><button>View</button></a></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
<script>
    function goBack() {
        window.location.href = "/minigames/positivityQuiz.php";
    }        
</script>
</body>
</html>
