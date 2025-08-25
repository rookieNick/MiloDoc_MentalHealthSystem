<?php
session_start();

// Check if the user is logged in and is an admin
if (isset($_SESSION["user"])) {
    if (($_SESSION["user"]) == "" || $_SESSION['usertype'] != 'a') {
        header("location: ../login.php");
        exit();
    }
} else {
    header("location: ../login.php");
    exit();
}

// Import database connection
include("../connection.php");

// Get the record to edit
if (!isset($_GET['id'])) {
    header("Location: testDescription.php");
    exit();
}

$id = (int)$_GET['id'];

// Fetch existing data
$result = $database->query("SELECT * FROM test_result_description WHERE id = $id");
if ($result->num_rows == 0) {
    header("Location: testDescription.php");
    exit();
}
$data = $result->fetch_assoc();

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cat = $database->real_escape_string($_POST['category']);
    $min = (int)$_POST['min_score'];
    $max = (int)$_POST['max_score'];
    $desc = $database->real_escape_string($_POST['description']);

    $database->query("UPDATE test_result_description 
                      SET category='$cat', min_score=$min, max_score=$max, description='$desc' 
                      WHERE id=$id");

    header("Location: testDescription.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Result Description</title>
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
        
        .score-inputs {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .score-field {
            flex: 1;
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
            border-left: 4px solid #f39c12;
        }
        
        .form-info p {
            margin: 0;
            color: #2c3e50;
        }
        
        .edit-id {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: -20px;
            margin-bottom: 20px;
        }
        
        @media screen and (max-width: 768px) {
            .page-container {
                padding: 15px;
                margin: 10px;
            }
            
            .score-inputs {
                flex-direction: column;
                gap: 10px;
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
                        <a href="testDescription.php">
                            <button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                                <font class="tn-in-text">Back</font>
                            </button>
                        </a>
                    </td>
                    <td>
                        <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Edit Result Description</p>
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
                        <button class="btn-label" style="display: flex;justify-content: center;align-items: center;">
                            <img src="../img/calendar.svg" width="100%">
                        </button>
                    </td>
                </tr>
            </table>

            <div class="page-container">
                <h1 class="page-title">Edit Result Description</h1>
                <p class="edit-id">ID: <?php echo $id; ?></p>
                
                <div class="form-info">
                    <p>You are editing an existing psychological test result description. Changes will be applied immediately after updating.</p>
                </div>
                
                <form method="post">
                    <div class="form-group">
                        <label class="form-label">Category:</label>
                        <select name="category" class="form-control" required>
                            <option value="Stress" <?php if($data['category']=='Stress') echo 'selected'; ?>>Stress</option>
                            <option value="Anxiety" <?php if($data['category']=='Anxiety') echo 'selected'; ?>>Anxiety</option>
                            <option value="Depression" <?php if($data['category']=='Depression') echo 'selected'; ?>>Depression</option>
                            <option value="Aggression" <?php if($data['category']=='Aggression') echo 'selected'; ?>>Aggression</option>
                            <option value="Self-Esteem" <?php if($data['category']=='Self-Esteem') echo 'selected'; ?>>Self-Esteem</option>
                        </select>
                    </div>

                    <div class="score-inputs">
                        <div class="score-field">
                            <label class="form-label">Min Score:</label>
                            <input type="number" name="min_score" class="form-control" value="<?php echo $data['min_score']; ?>" min="0" max="100" required>
                        </div>
                        
                        <div class="score-field">
                            <label class="form-label">Max Score:</label>
                            <input type="number" name="max_score" class="form-control" value="<?php echo $data['max_score']; ?>" min="0" max="100" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description:</label>
                        <textarea name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($data['description']); ?></textarea>
                    </div>

                    <div class="btn-container">
                        <button type="submit" class="btn btn-primary">Update Description</button>
                        <a href="testDescription.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
