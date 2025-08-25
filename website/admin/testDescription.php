<?php
session_start();

if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION["usertype"] != "a") {
    header("location: ../login.php");
    exit();
}

include("../connection.php");

$result = $database->query("SELECT * FROM test_result_description ORDER BY category, min_score");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Result Descriptions</title>
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
            margin: 30px;
        }

        .page-title {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 26px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .add-new-btn {
            background-color: #27ae60;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .add-new-btn:hover {
            background-color: #2ecc71;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .category-section {
            margin-bottom: 30px;
        }

        .category-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            font-size: 18px;
        }

        .category-Anxiety {
            background-color: #e74c3c;
        }

        .category-Depression {
            background-color: #9b59b6;
        }

        .category-Stress {
            background-color: #f39c12;
        }

        .category-Aggression {
            background-color: #e67e22;
        }

        .category-Self-Esteem {
            background-color: rgb(23, 105, 89);
        }

        .descriptions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            border: 1px solid #e0e0e0;
        }

        .descriptions-table th {
            background-color: #f2f6fa;
            color: #2c3e50;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #3498db;
        }

        .descriptions-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: top;
        }

        .descriptions-table tr:hover {
            background-color: #f5f9ff;
        }

        .action-links a {
            display: inline-block;
            margin-right: 8px;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-size: 14px;
        }

        .edit-link {
            background-color: #3498db;
        }

        .edit-link:hover {
            background-color: #2980b9;
        }

        .delete-link {
            background-color: #e74c3c;
        }

        .delete-link:hover {
            background-color: #c0392b;
        }

        .description-text {
            width: 700px;
            max-height: 100px;
            overflow: auto;
            line-height: 1.5;
        }

        .category-count {
            background-color: rgba(255, 255, 255, 0.3);
            padding: 2px 8px;
            border-radius: 12px;
            margin-left: 10px;
            font-size: 14px;
        }

        .level-indicator {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            background-color: #f8f9fa;
            color: #2c3e50;
            border: 1px solid #ddd;
        }

        .empty-category {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            text-align: center;
            color: #7f8c8d;
        }

        /* Simple responsive design */
        @media screen and (max-width: 768px) {
            .descriptions-table {
                display: block;
                overflow-x: auto;
            }

            .action-links a {
                margin-bottom: 5px;
                display: block;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include(__DIR__ . '/adminMenu.php'); ?>
        <div class="dash-body">
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
                        <p style="font-size: 23px; font-weight: 600; margin: 0;"></p>
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
            <div class="page-container">
                <h1 class="page-title">Result Descriptions</h1>
                <a href="addDescription.php" class="add-new-btn">
                    <i class="fas fa-plus"></i> Add New Description
                </a>

                <?php
                // Categories we want to display, in order
                $categories = ['Stress', 'Anxiety', 'Depression', 'Aggression', 'Self-Esteem'];

                // Store results by category
                $resultsByCategory = [];

                // Initialize categories with empty arrays
                foreach ($categories as $category) {
                    $resultsByCategory[$category] = [];
                }

                // Group results by category
                if ($result && $result->num_rows > 0) {
                    // Reset pointer to beginning
                    $result->data_seek(0);

                    // Group all results by category
                    while ($row = $result->fetch_assoc()) {
                        if (isset($resultsByCategory[$row['category']])) {
                            $resultsByCategory[$row['category']][] = $row;
                        }
                    }
                }

                // Display each category section
                foreach ($categories as $category) {
                    //$categoryClass = 'category-' . strtolower(str_replace(' ', '-', $category));
                    // create a CSS class name from category
                    $categoryClass = 'category-' . str_replace(' ', '-', $category);

                    $count = count($resultsByCategory[$category]);
                ?>
                    <div class="category-section">
                        <div class="category-header <?php echo $categoryClass; ?>">
                            <?php echo $category; ?>
                            <span class="category-count"><?php echo $count; ?></span>
                        </div>

                        <?php if ($count > 0): ?>
                            <table class="descriptions-table">
                                <thead>
                                    <tr>
                                        <th>Level</th>
                                        <th>Score Range</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resultsByCategory[$category] as $row): ?>
                                        <tr>
                                            <td>
                                                <span class="level-indicator">
                                                    <?= isset($row['level']) ? htmlspecialchars($row['level']) : 'Level ' . ceil($row['max_score'] / 25) ?>
                                                </span>
                                            </td>
                                            <td><?= $row['min_score'] ?> - <?= $row['max_score'] ?></td>
                                            <td>
                                                <div class="description-text">
                                                    <?= nl2br(htmlspecialchars($row['description'])) ?>
                                                </div>
                                            </td>
                                            <td class="action-links">
                                                <a href="editDescription.php?id=<?= $row['id'] ?>" class="edit-link">Edit</a>
                                                <a href="deleteDescription.php?id=<?= $row['id'] ?>"
                                                    class="delete-link"
                                                    onclick="return confirm('Are you sure you want to delete this description?')">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-category">
                                <p>No descriptions for <?php echo $category; ?> category. <a href="addDescription.php">Add one now</a>.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php } ?>

                <?php if ($result->num_rows == 0): ?>
                    <div style="text-align: center; padding: 30px; color: #777;">
                        <p>No result descriptions found in any category. Click "Add New Description" to create one.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>