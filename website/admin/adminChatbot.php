<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/adminChatbot.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Chatbot</title>
    <style>
        .popup {
            animation: transitionIn-Y-bottom 0.5s;
        }

        .sub-table {
            animation: transitionIn-Y-bottom 0.5s;
        }
    </style>
</head>

<body>
    <?php

    //learn from w3schools.com

    session_start();

    if (isset($_SESSION["user"])) {
        if (($_SESSION["user"]) == "" or $_SESSION['usertype'] != 'a') {
            header("location: ../login.php");
        }
    } else {
        header("location: ../login.php");
    }



    //import database
    include("../connection.php");


    ?>
    <div class="container">
        <?php include(__DIR__ . '/adminMenu.php'); ?>
        <div class="dash-body">
            <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;padding-bottom:50px">
                <tr>
                    <td width="13%">
                        <a href="index.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                                <font class="tn-in-text">Back</font>
                            </button></a>
                    </td>
                    <td width="5%">
                        <a href="adminIngest.php">
                            <button class="login-btn btn-primary-soft btn" style="padding-top:11px;padding-bottom:11px;margin-right:20px;width:125px">
                                <font class="tn-in-text">Ingest</font>
                            </button>
                        </a>
                    </td>
                    <td width="67%">
                        <form action="" method="post" class="header-search">
                            <input type="search" name="search" class="input-text header-searchbar" placeholder="Search keyword, flag, etc.">&nbsp;&nbsp;
                            <input type="Submit" value="Search" class="login-btn btn-primary btn" style="padding: 10px 25px;">
                        </form>
                    </td>
                    <td width="15%">
                        <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">Today's Date</p>
                        <p class="heading-sub12" style="padding: 0;margin: 0;">
                            <?php
                            date_default_timezone_set('Asia/Kuala_Lumpur');
                            echo date('Y-m-d');
                            ?>
                        </p>
                    </td>
                    <td width="10%">
                        <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="padding-top:30px;">
                        <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Manage Sensitive Keywords</p>
                    </td>
                    <td colspan="2">
                        <a href="?action=add" class="non-style-link"><button class="login-btn btn-primary btn button-icon" style="margin-left:75px;background-image: url('../img/icons/add.svg'); float:right;">Add New</button></a>
                    </td>
                </tr>

                <tr>
                    <td colspan="4" style="padding-top:10px;">
                        <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)">All Keywords</p>
                    </td>
                </tr>

                <?php
                if ($_POST) {
                    $keyword = $_POST["search"];
                    $sqlmain = "SELECT * FROM sensitive_keywords WHERE keyword LIKE '%$keyword%' OR flag LIKE '%$keyword%' ORDER BY sensitive_keyword_id DESC";
                } else {
                    $sqlmain = "SELECT * FROM sensitive_keywords ORDER BY sensitive_keyword_id DESC";
                }
                $result = $database->query($sqlmain);
                ?>

                <tr>
                    <td colspan="4">
                        <center>
                            <div class="abc scroll">
                                <table width="93%" class="sub-table scrolldown" border="0">
                                    <thead>
                                        <tr>
                                            <th class="table-headin">ID</th>
                                            <th class="table-headin">Keyword</th>
                                            <th class="table-headin">Status</th>
                                            <th class="table-headin">Flag</th>
                                            <th class="table-headin">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows == 0): ?>
                                            <tr>
                                                <td colspan="5">
                                                    <center>
                                                        <img src="../img/notfound.svg" width="25%">
                                                        <p class="heading-main12" style="font-size:20px;color:rgb(49, 49, 49)">No results found!</p>
                                                        <a class="non-style-link" href="adminChatbot.php">
                                                            <button class="login-btn btn-primary-soft btn">Show all</button>
                                                        </a>
                                                    </center>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row["sensitive_keyword_id"]) ?></td>
                                                    <td><?= htmlspecialchars($row["keyword"]) ?></td>
                                                    <td><?= htmlspecialchars($row["status"]) ?></td>
                                                    <td><?= htmlspecialchars($row["flag"]) ?></td>
                                                    <td>
                                                        <div style="display:flex;justify-content: center;">
                                                            <a href="?action=edit&id=<?= urlencode($row["sensitive_keyword_id"]) ?>" class="non-style-link">
                                                                <button class="btn-primary-soft btn button-icon btn-edit" style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;">

                                                                    <font class="tn-in-text">Edit</font>

                                                                </button>
                                                            </a>
                                                            &nbsp;&nbsp;&nbsp;
                                                            <a href="?action=view&id=<?= urlencode($row["sensitive_keyword_id"]) ?>" class="non-style-link">
                                                                <button class="btn-primary-soft btn button-icon btn-view" style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;">
                                                                    <font class="tn-in-text">View</font>
                                                                </button>
                                                            </a>
                                                            &nbsp;&nbsp;&nbsp;
                                                            <a href="?action=delete&id=<?= urlencode($row["sensitive_keyword_id"]) ?>" class="non-style-link">
                                                                <button class="btn-primary-soft btn button-icon btn-delete" style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;">

                                                                    <font class="tn-in-text">Delete</font>

                                                                </button>
                                                            </a>

                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </center>
                    </td>
                </tr>
            </table>







        </div>








    </div>

    <?php
    if ($_GET) {
        $id = $_GET["id"];
        $action = $_GET["action"];

        $sqlmain = "SELECT * FROM sensitive_keywords WHERE sensitive_keyword_id ='$id'";
        $result = $database->query($sqlmain);
        $row = $result->fetch_assoc();
        $keyword = $row["keyword"];

        if ($action == 'delete') {

    ?>
            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <h2>Are you sure?</h2>
                        <a class="close" href="adminChatbot.php">&times;</a>
                        <div class="content">
                            You want to delete this sensitive keyword<br>('<?= htmlspecialchars(substr($keyword, 0, 40)) ?>').
                        </div>
                        <div style="display: flex; justify-content: center;">
                            <a href="delete-sensitive-keyword.php?id=<?= urlencode($id) ?>" class="non-style-link">
                                <button class="btn-primary btn" style="display: flex; justify-content: center; align-items: center; margin:10px; padding:10px;">
                                    <font class="tn-in-text">&nbsp;Yes&nbsp;</font>
                                </button>
                            </a>
                            &nbsp;&nbsp;&nbsp;
                            <a href="adminChatbot.php" class="non-style-link">
                                <button class="btn-primary btn" style="display: flex; justify-content: center; align-items: center; margin:10px; padding:10px;">
                                    <font class="tn-in-text">&nbsp;&nbsp;No&nbsp;&nbsp;</font>
                                </button>
                            </a>
                        </div>
                    </center>
                </div>
            </div>

        <?php
        }
        ?>
        <?php if ($action == 'view') {
            // SQL query to fetch details from sensitive_keywords table
            $sqlmain = "SELECT * FROM sensitive_keywords WHERE sensitive_keyword_id='$id'";
            $result = $database->query($sqlmain);
            $row = $result->fetch_assoc();

            // Retrieve data from the sensitive_keywords table
            $keyword = $row["keyword"];
            $status = $row["status"];
            $created_date = $row["created_date"];
            $updated_date = $row["updated_date"];
            $created_by = $row["created_by"];
            $updated_by = $row["updated_by"];
            $flag = $row["flag"];
            $description = $row["description"];
        ?>

            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <h2>View Sensitive Keyword Details</h2>
                        <a class="close" href="adminChatbot.php">&times;</a>
                        <div class="content">
                            eDoc Web App<br>
                        </div>
                        <div style="display: flex; justify-content: center;">
                            <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                                <tr>
                                    <td>
                                        <p style="padding: 0; margin: 0; text-align: left; font-size: 25px; font-weight: 500;">View Details</p><br><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="keyword" class="form-label">Keyword: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <?= $keyword ?><br><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="status" class="form-label">Status: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <?= $status ?><br><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="created_date" class="form-label">Created Date: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <?= $created_date ?><br><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="updated_date" class="form-label">Updated Date: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <?= $updated_date ?><br><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="created_by" class="form-label">Created By: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <?= $created_by ?><br><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="updated_by" class="form-label">Updated By: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <?= $updated_by ?><br><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="flag" class="form-label">Flag: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <?= $flag ?><br><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="description" class="form-label">Description: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <?= $description ?><br><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <a href="adminChatbot.php">
                                            <input type="button" value="OK" class="login-btn btn-primary-soft btn">
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </center>
                    <br><br>
                </div>
            </div>

        <?php } ?>


        <?php
        if ($action == 'add' && !isset($_GET['success'])) {

        ?>
            <!-- ❌ Error Message Popup -->
            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <a class="close" href="adminChatbot.php">&times;</a>
                        <div style="display: flex; justify-content: center;">
                            <div class="abc">
                                <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                                    <tr>
                                        <td class="label-td" colspan="2">
                                            <label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">
                                                <?php if (isset($_GET['message']) && (!isset($_GET['success']) || $_GET['success'] != 1)) { ?>
                                                    <?= htmlspecialchars($_GET['message']) ?>
                                                <?php } ?>
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <p style="padding: 0; margin: 0; text-align: left; font-size: 25px; font-weight: 500;">
                                                Add New Sensitive Keyword.
                                            </p><br><br>
                                        </td>
                                    </tr>

                                    <form action="add-sensitive-keyword.php" method="POST" class="add-new-form">
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <label for="keyword" class="form-label">Keyword: </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <input type="text" name="keyword" class="input-text" placeholder="Sensitive Keyword" required><br>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <label for="description" class="form-label">Description: </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <textarea name="description" class="input-text" placeholder="Description of the keyword" required></textarea><br>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <label for="flag" class="form-label">Flag: </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <select name="flag" class="box">
                                                    <option value="suicidal">Suicidal</option>
                                                    <option value="self-harm">Self-Harm</option>
                                                    <option value="abuse">Abuse</option>
                                                    <option value="depression">Depression</option>
                                                </select><br>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <label for="status" class="form-label">Status: </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <select name="status" class="box">
                                                    <option value="1">Active</option>
                                                    <option value="0">Inactive</option>
                                                </select><br>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td colspan="2">
                                                <input type="reset" value="Reset" class="login-btn btn-primary-soft btn">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
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
            </div>
        <?php
        } else if ($action == 'add' && isset($_GET['success'])) {
        ?>
            <!-- ✅ Success Message Popup -->
            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <br><br><br><br>
                        <h2><?= htmlspecialchars($_GET['message']) ?></h2>
                        <a class="close" href="adminChatbot.php">&times;</a>
                        <div class="content"></div>
                        <div style="display: flex; justify-content: center;">
                            <a href="adminChatbot.php" class="non-style-link">
                                <button class="btn-primary btn" style="display: flex; justify-content: center; align-items: center; margin: 10px; padding: 10px;">
                                    <font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font>
                                </button>
                            </a>
                        </div>
                        <br><br>
                    </center>
                </div>
            </div>
        <?php
        }
        ?>





        <?php if ($action == 'edit' && !isset($_GET['success'])) {
            $sqlmain = "SELECT * FROM sensitive_keywords WHERE sensitive_keyword_id ='$id'";
            $result = $database->query($sqlmain);
            $row = $result->fetch_assoc();
            $keyword = $row["keyword"];
            $description = $row["description"];
            $flag = $row["flag"];
            $status = $row["status"];
        ?>


            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <a class="close" href="adminChatbot.php">&times;</a>
                        <div style="display: flex;justify-content: center;">
                            <div class="abc">
                                <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                                    <tr>
                                        <td class="label-td" colspan="2">
                                            <?php if (isset($_GET['message']) && (!isset($_GET['success']) || $_GET['success'] != 1)) { ?>
                                                <?= htmlspecialchars($_GET['message']) ?>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Edit Sensitive Keyword</p>
                                            ID : <?= $id ?><br><br>
                                        </td>
                                    </tr>
                                    <form action="edit-sensitive-keyword.php" method="POST" class="add-new-form">
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <label for="keyword" class="form-label">Keyword: </label>
                                                <input type="hidden" name="keyword_id" value="<?= $id ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <input type="text" name="keyword" class="input-text" placeholder="Enter keyword" value="<?= htmlspecialchars($keyword) ?>" required><br>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <label for="description" class="form-label">Description: </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <textarea name="description" class="input-text" placeholder="Enter description" required><?= htmlspecialchars($description) ?></textarea><br>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <label for="flag" class="form-label">Category (Flag): </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <select name="flag" class="input-text" required>
                                                    <?php
                                                    $flags = ['suicidal', 'self-harm', 'abuse', 'depression', 'anxiety', 'violence'];
                                                    foreach ($flags as $f) {
                                                        $selected = ($flag == $f) ? "selected" : "";
                                                        echo "<option value=\"$f\" $selected>$f</option>";
                                                    }
                                                    ?>
                                                </select><br>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <label for="status" class="form-label">Status: </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <select name="status" class="input-text" required>
                                                    <option value="1" <?= ($status == 1) ? "selected" : "" ?>>Active</option>
                                                    <option value="0" <?= ($status == 0) ? "selected" : "" ?>>Inactive</option>
                                                </select><br>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2">
                                                <input type="reset" value="Reset" class="login-btn btn-primary-soft btn">
                                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <input type="submit" value="Save" class="login-btn btn-primary btn">
                                            </td>
                                        </tr>
                                    </form>
                                </table>
                            </div>
                        </div>
                    </center>
                    <br><br>
                </div>
            </div>

        <?php } else if ($action == 'edit' && isset($_GET['success'])) { ?>

            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <br><br><br><br>
                        <h2><?= htmlspecialchars($_GET['message']) ?></h2>
                        <a class="close" href="adminChatbot.php">&times;</a>
                        <div class="content"></div>
                        <div style="display: flex;justify-content: center;">
                            <a href="adminChatbot.php" class="non-style-link">
                                <button class="btn-primary btn" style="margin:10px;padding:10px;">
                                    <font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font>
                                </button>
                            </a>
                        </div>
                        <br><br>
                    </center>
                </div>
            </div>

        <?php
        }
        ?>
    <?php
    }
    ?>





  
</body>

</html>