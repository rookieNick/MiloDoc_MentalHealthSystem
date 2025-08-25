<?php
session_start();

if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION["usertype"] != "a") {
    header("location: ../login.php");
    exit();
}

include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // read & escape
    $cat = $database->real_escape_string($_POST['category']);

    // get min/max from selected range
    $range = $_POST['score_range'];
    switch ($range) {
        case "0-25":
            $min = 0;
            $max = 25;
            $level = "Level 1";
            break;
        case "26-50":
            $min = 26;
            $max = 50;
            $level = "Level 2";
            break;
        case "51-75":
            $min = 51;
            $max = 75;
            $level = "Level 3";
            break;
        case "76-100":
            $min = 76;
            $max = 100;
            $level = "Level 4";
            break;
        default:
            // invalid value
            header("Location: addDescription.php");
            exit();
    }

    $desc = $database->real_escape_string($_POST['description']);

    $sql = "INSERT INTO test_result_description 
            (category, min_score, max_score, level, description)
            VALUES
            ('$cat', $min, $max, '$level', '$desc')";
    $database->query($sql);

    header("Location: testDescription.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add New Result Description</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- External CSS files -->
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .page-container {
            padding: 25px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin: 20px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .page-title {
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 26px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
        }
        
        select.form-control {
            cursor: pointer;
            background-color: #f8f9fa;
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .btn-container {
            margin-top: 30px;
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-secondary {
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-secondary:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .form-info {
            background-color: #f2f6fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        
        .form-info p {
            margin: 0;
            color: #2c3e50;
        }
        
        @media screen and (max-width: 768px) {
            .page-container {
                padding: 15px;
                margin: 10px;
            }
            
            .btn-container {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include(__DIR__ . '/adminMenu.php'); ?>
        <div class="dash-body">
            <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
                <tr>
                    <td width="13%">
                        <a href="testDescription.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                                <font class="tn-in-text">Back</font>
                            </button></a>
                    </td>
                    <td>
                        <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Psychological Test Manager</p>
                    </td>
                    <td width="15%">
                        <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
                            Today's Date
                        </p>
                        <p class="heading-sub12" style="padding: 0;margin: 0;">
                            <?php
                            date_default_timezone_set('Asia/Kolkata');
                            echo date('Y-m-d');
                            ?>
                        </p>
                    </td>
                    <td width="10%">
                        <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                    </td>
                </tr>
            </table>

            <div class="page-container">
                <h1 class="page-title">Add New Result Description</h1>
                
                <div class="form-info">
                    <p>Create a new psychological test result description. This will be shown to users based on their test scores.</p>
                </div>
                
                <form method="post">
                    <div class="form-group">
                        <label class="form-label">Category:</label>
                        <select name="category" class="form-control" required>
                            <option value="">-- Select Category --</option>
                            <option value="Stress">Stress</option>
                            <option value="Anxiety">Anxiety</option>
                            <option value="Depression">Depression</option>
                            <option value="Aggression">Aggression</option>
                            <option value="Self-Esteem">Self-Esteem</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Score Range:</label>
                        <select name="score_range" class="form-control" required>
                            <option value="">-- Select Score Range --</option>
                            <option value="0-25">0 - 25 (Level 1)</option>
                            <option value="26-50">26 - 50 (Level 2)</option>
                            <option value="51-75">51 - 75 (Level 3)</option>
                            <option value="76-100">76 - 100 (Level 4)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description:</label>
                        <textarea name="description" class="form-control" rows="5" placeholder="Enter the description that will be shown to users who score in this range..." required></textarea>
                    </div>

                    <div class="btn-container">
                        <button type="submit" class="btn btn-primary">Add Description</button>
                        <a href="testDescription.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
    </div>
</body>

</html>