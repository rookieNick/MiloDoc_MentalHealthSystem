<?php
require_once(__DIR__ . '/../includes/database/communityDA.php');
require_once(__DIR__ . '/../includes/database/postDA.php');
require_once(__DIR__ . '/../includes/database/communityMemberDA.php');

$communityDA = new CommunityDA();
$postDA = new PostDA();
$communityMemberDA = new CommunityMemberDA();

$visibility = isset($_GET['visibility']) ? $_GET['visibility'] : null;
$createdAt = isset($_GET['created_at']) ? $_GET['created_at'] : null;
$category = isset($_GET['category']) ? $_GET['category'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;
$allCommunity = $communityDA->getFilteredCommunities($visibility, $createdAt, $category, $search); // Pass search to the method

// Start session and check user authentication
session_start();

if (isset($_SESSION["user"])) {
    if ($_SESSION["user"] == "" && $_SESSION['usertype'] != 'p'  && $_SESSION['usertype'] != 'a'  && $_SESSION['usertype'] != 'd') {
        header("location: ../../login.php");
        exit;
    } else {
        $useremail = $_SESSION["user"];
    }
} else {
    header("location: ../../login.php");
    exit;
}

// Import database connection
include("../../connection.php");

// Fetch user details and store user ID in session
if ($_SESSION['usertype'] == 'p') {
    $sqlmain = "SELECT * FROM patient WHERE pemail = ?";
    $stmt = $database->prepare($sqlmain);
    $stmt->bind_param("s", $useremail);
    $stmt->execute();
    $userrow = $stmt->get_result();
    $userfetch = $userrow->fetch_assoc();
    $userid = $userfetch["pid"];
    $username = $userfetch["pname"];
} elseif ($_SESSION['usertype'] == 'd') {
    $sqlmain = "SELECT * FROM doctor WHERE docemail = ?";
    $stmt = $database->prepare($sqlmain);
    $stmt->bind_param("s", $useremail);
    $stmt->execute();
    $userrow = $stmt->get_result();
    $userfetch = $userrow->fetch_assoc();
    $userid = $userfetch["docid"];
    $username = $userfetch["docname"];
} else {
    $errorMessage = "error";
}

// Fetch usertype from webuser table
$sqlWebUser = "SELECT usertype FROM webuser WHERE email = ?";
$stmtWebUser = $database->prepare($sqlWebUser);
$stmtWebUser->bind_param("s", $useremail);
$stmtWebUser->execute();
$webUserRow = $stmtWebUser->get_result();
$webUser = $webUserRow->fetch_assoc();
if ($webUser) {
    $webUserType = $webUser['usertype'];
} else {
    $webUserType = null;
    $errorMessage = "User type not found in webuser table.";
}
$stmtWebUser->close();

$sqlForumUser = "SELECT username FROM user WHERE email = ?";
$stmtForumUser = $database->prepare($sqlForumUser);
$stmtForumUser->bind_param("s", $useremail);
$stmtForumUser->execute();
$forumUserRow = $stmtForumUser->get_result();
$forumUser = $forumUserRow->fetch_assoc();
$isFirstTime = !isset($forumUser['username']) || is_null($forumUser['username']);

// Handle username submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_username'])) {
    $newUsername = trim($_POST['new_username']);
    if (!empty($newUsername)) {
        $sqlUpdateUsername = "UPDATE user SET username = ? WHERE email = ?";
        $stmtUpdate = $database->prepare($sqlUpdateUsername);
        $stmtUpdate->bind_param("ss", $newUsername, $useremail);
        if ($stmtUpdate->execute()) {
            header("Location: communityMain.php");
            exit;
        } else {
            $errorMessage = "Error updating username. Please try again.";
        }
        $stmtUpdate->close();
    } else {
        $errorMessage = "Username cannot be empty.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiloDoc Mental Health Community</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="../assets/css/communityMain.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/animations.css">
    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/admin.css">
    <style>
        /* Pop-up styles */
        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .popup-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .popup-content h2 {
            margin-top: 0;
            font-size: 1.5em;
            color: #333;
        }

        .popup-content form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .popup-content input[type="text"] {
            padding: 10px;
            font-size: 1em;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
        }

        .popup-content button {
            padding: 10px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }

        .popup-content button:hover {
            background: #0056b3;
        }

        .popup-content .error {
            color: red;
            font-size: 0.9em;
        }

        .popup-content .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: transparent;
            border: none;
            font-size: 1.2em;
            cursor: pointer;
        }
    </style>
</head>

<body class="no-style">


    <!-- Main content wrapper -->
    <div class="main-wrapper">
        <div>
            <?php if ($webUser['usertype'] == 'p') {
                include '../../patient/patientMenu.php';
            } elseif ($webUser['usertype'] == 'd') {
                include '../../doctor/doctorMenu.php';
            } elseif ($webUser['usertype'] == 'a') {
                include '../../admin/adminMenu.php';
            } else {
                $errorMessage = "Error displaying the left nav.";
            }
            ?>
        </div>

        <?php include 'rightForumNav.php'; ?>
        <!-- Include the left navigation bar at the top level -->

        <!-- Main content -->
        <div class="communitycontainer">
            <?php
            // Success Message for Forum Deletion
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div class="success-message">
                        <p><i class="fas fa-check-circle"></i> Forum deleted successfully!</p>
                      </div>
                      <script>
                        setTimeout(function() {
                            document.querySelector(\'.success-message\').style.display = \'none\';
                        }, 3000);
                      </script>';
            }
            ?>
            <!-- Header -->
            <header>
                <div class="header-content">
                    <h1>MILODOC MENTAL HEALTH COMMUNITY</h1>
                    <p>A safe space to connect, share experiences, and support each other on your mental health journey</p>
                </div>
            </header>

            <!-- Navigation -->
            <nav class="navbar">
                <div class="nav-links">
                    <a href="communityMain.php" class="active">All Forums</a>
                    <a href="myForum.php">My Forums</a>
                    <a href="findHelp.php">Find Help</a>
                </div>
            </nav>

            <!-- Emergency Support Banner -->
            <div class="emergency-support">
                <i class="fas fa-phone-alt"></i>
                <div class="emergency-content">
                    <h3>Need Immediate Support?</h3>
                    <p>If you're in crisis or need urgent help, don't hesitate to reach out to these professional services:</p>
                    <a href="findHelp.php" class="hotline-btn"><i class="fas fa-phone"></i>Suicide & Crisis Lifeline</a>
                </div>
            </div>

           <!-- Main Content -->
        <section class="filter-section">
            <form method="GET" action="" class="filter-form">
                <div>
                    <label for="search"><i class="fas fa-search"></i> Search:</label>
                    <input type="text" name="search" id="search" placeholder="Search forums..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                </div>
                <div>
                    <label for="visibility"><i class="fas fa-eye"></i> Visibility:</label>
                    <select name="visibility">
                        <option value="">All</option>
                        <option value="Public" <?= (isset($_GET['visibility']) && $_GET['visibility'] == 'Public') ? 'selected' : ''; ?>>Public</option>
                        <option value="Private" <?= (isset($_GET['visibility']) && $_GET['visibility'] == 'Private') ? 'selected' : ''; ?>>Private</option>
                        <option value="Anonymous" <?= (isset($_GET['visibility']) && $_GET['visibility'] == 'Anonymous') ? 'selected' : ''; ?>>Anonymous</option>
                    </select>
                </div>
                <div>
                    <label for="created_at"><i class="fas fa-calendar"></i> Date:</label>
                    <input type="date" name="created_at" value="<?= isset($_GET['created_at']) ? $_GET['created_at'] : '' ?>">
                </div>
                <div>
                    <label for="category"><i class="fas fa-tag"></i> Category:</label>
                    <select name="category">
                        <option value="">All Categories</option>
                        <option value="anxiety" <?= (isset($_GET['category']) && $_GET['category'] === 'anxiety') ? 'selected' : ''; ?>>Anxiety</option>
                        <option value="stress" <?= (isset($_GET['category']) && $_GET['category'] === 'stress') ? 'selected' : ''; ?>>Stress</option>
                        <option value="depression" <?= (isset($_GET['category']) && $_GET['category'] === 'depression') ? 'selected' : ''; ?>>Depression</option>
                        <option value="trauma support" <?= (isset($_GET['category']) && $_GET['category'] === 'trauma support') ? 'selected' : ''; ?>>Trauma Support</option>
                        <option value="mindfulness and meditation" <?= (isset($_GET['category']) && $_GET['category'] === 'mindfulness and meditation') ? 'selected' : ''; ?>>Mindfulness &amp; Meditation</option>
                    </select>
                </div>
                <button type="submit"><i class="fas fa-filter"></i> Apply Filters</button>
            </form>
        </section>

            <section class="community-list">
                <h2><i class="fas fa-users"></i> Support Communities</h2>
                <div class="forum-grid">
                    <!-- Create New Forum Card -->
                    <a href="createForum.php" class="create-forum-card">
                        <i class="fas fa-plus-circle"></i>
                        <h3>Create a New Forum</h3>
                        <p>Start a supportive community around a mental health topic that matters to you</p>
                        <div class="btn btn-primary">Get Started</div>
                    </a>

                    <!-- Forum Cards -->
                    <?php foreach ($allCommunity as $community): ?>
                        <?php
                        // Fetch the actual number of members
                        $members = $communityMemberDA->getCommunityMembersById($community['community_id']);
                        $memberCount = $members ? count($members) : 0;

                        // Fetch the actual number of posts
                        $posts = $postDA->getPostByCommunityID($community['community_id'], null);
                        $postCount = $posts ? count($posts) : 0;
                        ?>
                        <div class="forum-card" onclick="window.location.href='forumDetail.php?id=<?= htmlspecialchars($community['community_id']); ?>'">
                            <div class="visibility-badge">
                                <?php if ($community['visibility'] == 'Public'): ?>
                                    <i class="fas fa-globe"></i> Public
                                <?php elseif ($community['visibility'] == 'Private'): ?>
                                    <i class="fas fa-lock"></i> Private
                                <?php else: ?>
                                    <i class="fas fa-user-secret"></i> Anonymous
                                <?php endif; ?>
                            </div>
                            <div class="forum-img-container">
                                <?php
                                $image_url = (!empty($community['picture_url']) && file_exists(__DIR__ . "/../" . $community['picture_url']))
                                    ? "../" . htmlspecialchars($community['picture_url'])
                                    : "../assets/image/default_image.jpg";
                                ?>
                                <img src="<?= $image_url; ?>" alt="Forum Image" class="forum-img">
                            </div>
                            <div class="forum-content">
                                <h3 class="forum-title"><?= htmlspecialchars($community['name']); ?></h3>
                                <p class="forum-description"><?= htmlspecialchars($community['description']); ?></p>
                                <div class="forum-meta">
                                    <div class="forum-stats">
                                        <div class="forum-stat">
                                            <i class="fas fa-comment"></i> <?= $postCount; ?>
                                        </div>
                                        <div class="forum-stat">
                                            <i class="fas fa-users"></i> <?= $memberCount; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <i class="fas fa-clock"></i> Active
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Community Guidelines -->
            <section class="community-guidelines">
                <h3 class="guidelines-title"><i class="fas fa-heart"></i> Community Guidelines</h3>
                <ul class="guidelines-list">
                    <li><strong>Be Kind & Respectful:</strong> Treat others with compassion and respect, even when opinions differ.</li>
                    <li><strong>Maintain Privacy:</strong> Protect your own and others' personal information.</li>
                    <li><strong>No Medical Advice:</strong> Share experiences but avoid giving medical or professional advice.</li>
                    <li><strong>Content Warning:</strong> Use content warnings for potentially triggering topics.</li>
                    <li><strong>Report Concerns:</strong> If you see something concerning, please report it to moderators.</li>
                </ul>
            </section>

            <!-- Footer -->
            <footer>
                <div class="footer-content">
                    <div class="footer-column">
                        <h3>About MiloDoc</h3>
                        <p>MiloDoc is dedicated to creating supportive spaces for people dealing with mental health challenges. Our mission is to connect, educate, and empower through shared experiences.</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                    <div class="footer-column">
                        <h3>Quick Links</h3>
                        <ul>
                            <li><a href="#"><i class="fas fa-home"></i> Home</a></li>
                            <li><a href="#"><i class="fas fa-users"></i> Communities</a></li>
                            <li><a href="#"><i class="fas fa-book"></i> Resources</a></li>
                            <li><a href="#"><i class="fas fa-question-circle"></i> FAQ</a></li>
                            <li><a href="#"><i class="fas fa-shield-alt"></i> Privacy Policy</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h3>Support</h3>
                        <ul>
                            <li><a href="#"><i class="fas fa-phone"></i> Crisis Hotlines</a></li>
                            <li><a href="#"><i class="fas fa-user-md"></i> Find a Therapist</a></li>
                            <li><a href="#"><i class="fas fa-book-medical"></i> Mental Health Resources</a></li>
                            <li><a href="#"><i class="fas fa-hand-holding-heart"></i> Volunteer</a></li>
                            <li><a href="#"><i class="fas fa-donate"></i> Donate</a></li>
                        </ul>
                    </div>
                </div>
                <div class="copyright">
                    <p>© 2025 MiloDoc Mental Health Community. All rights reserved.</p>
                </div>
            </footer>
        </div>
    </div>

    <!-- Pop-up for first-time username entry -->
    <?php if ($isFirstTime): ?>
        <div class="popup-overlay" id="usernamePopup" style="display: flex;">
            <div class="popup-content">
                <button class="close-btn" onclick="closePopup()">×</button>
                <h2>Welcome to MiloDoc Community!</h2>
                <p>Please choose a username to participate in the forums.</p>
                <?php if (isset($errorMessage)): ?>
                    <p class="error"><?= htmlspecialchars($errorMessage) ?></p>
                <?php endif; ?>
                <form method="POST" action="">
                    <input type="text" name="new_username" placeholder="Enter your username" required>
                    <button type="submit">Save Username</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Close pop-up function
        function closePopup() {
            document.getElementById('usernamePopup').style.display = 'none';
        }

        // Existing scripts
        document.addEventListener("DOMContentLoaded", function() {
            // Ensure all forum cards are clickable
            document.querySelectorAll(".forum-card").forEach(card => {
                card.addEventListener("click", function() {
                    const href = this.getAttribute("data-href") || this.getAttribute("onclick").split("'")[1];
                    window.location.href = href;
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            const sidebar = document.querySelector('.sidebar-nav');

            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }

            // Handle "Show More" buttons
            const showMoreButtons = document.querySelectorAll('.show-more-btn');
            showMoreButtons.forEach(button => {
                button.addEventListener('click', function() {
                    if (this.textContent.includes('Show all')) {
                        this.innerHTML = 'Show less <i class="fas fa-chevron-up"></i>';
                    } else {
                        this.innerHTML = 'Show all ' + this.parentElement.querySelector('.sidebar-title span').textContent.toLowerCase() + ' <i class="fas fa-chevron-right"></i>';
                    }
                });
            });
        });
    </script>
</body>

</html>