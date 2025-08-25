<?php
session_start();
require_once(__DIR__ . '/../includes/database/communityDA.php');
require_once(__DIR__ . '/../includes/database/communityMemberDA.php');
require_once(__DIR__ . '/../includes/database/userDA.php');
$communityDA = new CommunityDA();
$communityMemberDA = new CommunityMemberDA();
$userDA = new UserDA();
// Get all communities where the user is a member
$userCommunities = [];
$allMembers = $communityMemberDA->getAllCommunityMembers();
// Start session and check user authentication

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
$userData = $userDA->getUserByEmail($useremail);
$userId = $userData['user_id'];
foreach ($allMembers as $member) {
    if ($member['user_id'] == $userId) {
        $community = $communityDA->getCommunityById($member['community_id']);
        if ($community) {
            $community['role'] = $member['role'];
            $userCommunities[] = $community;
        }
    }
}

// Separate created and joined communities
$createdCommunities = [];
$joinedCommunities = [];

foreach ($userCommunities as $community) {
    if ($community['creator_id'] == $userId) {
        $createdCommunities[] = $community;
    } else {
        $joinedCommunities[] = $community;
    }
}

// Filter options
$visibility = isset($_GET['visibility']) ? $_GET['visibility'] : null;
$category = isset($_GET['category']) ? $_GET['category'] : null;

// Filter the communities based on filter parameters
if ($visibility || $category) {
    $filteredCreated = [];
    $filteredJoined = [];

    foreach ($createdCommunities as $community) {
        if ((!$visibility || $community['visibility'] == $visibility) &&
            (!$category || $community['category'] == $category)
        ) {
            $filteredCreated[] = $community;
        }
    }

    foreach ($joinedCommunities as $community) {
        if ((!$visibility || $community['visibility'] == $visibility) &&
            (!$category || $community['category'] == $category)
        ) {
            $filteredJoined[] = $community;
        }
    }

    $createdCommunities = $filteredCreated;
    $joinedCommunities = $filteredJoined;
}

// Page title
$pageTitle = "My Forums";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | MiloDoc Mental Health Support</title>
    <link rel="stylesheet" href="../assets/css/myForum.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <!-- Success Message for Forum Actions -->
    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="success-message">
            <p><i class="fas fa-check-circle"></i> Action completed successfully!</p>
        </div>
        <script>
            setTimeout(function() {
                document.querySelector('.success-message').style.display = 'none';
            }, 3000);
        </script>
    <?php endif; ?>

    <div class="container">
    <?php include 'rightForumNav.php'; ?>
        <!-- Header -->
        <header>
            <h1>MILODOC MY FORUMS</h1>
            <p>Manage and participate in the mental health communities you've created and joined</p>
        </header>

        <!-- Navigation -->
        <nav class="navbar">
            <div class="nav-links">
                <a href="communityMain.php">All Forums</a>
                <a href="myForum.php" class="active">My Forums</a>
                <a href="findHelp.php">Find Help</a>
            </div>
        </nav>

        <!-- My Forums Overview -->
        <div class="my-forums-overview">
            <div class="overview-card">
                <div class="overview-icon created">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="overview-content">
                    <h3><?php echo count($createdCommunities); ?></h3>
                    <p>Forums Created</p>
                </div>
            </div>
            <div class="overview-card">
                <div class="overview-icon joined">
                    <i class="fas fa-users"></i>
                </div>
                <div class="overview-content">
                    <h3><?php echo count($joinedCommunities); ?></h3>
                    <p>Forums Joined</p>
                </div>
            </div>
            <div class="overview-card">
                <div class="overview-icon new">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="overview-content" onclick="window.location.href='createForum.php'">
                    <a href="createForum.php" class="create-forum-link" onclick="event.stopPropagation();">Create New Forum</a>
                    <p>Start a support group</p>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <section class="filter-section">
            <form method="GET" action="" class="filter-form">
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
                    <label for="category"><i class="fas fa-tag"></i> Category:</label>
                    <select name="category">
                        <option value="">All Categories</option>
                        <option value="anxiety"
                            <?= (isset($_GET['category']) && $_GET['category'] === 'anxiety') ? 'selected' : ''; ?>>
                            Anxiety
                        </option>
                        <option value="stress"
                            <?= (isset($_GET['category']) && $_GET['category'] === 'stress') ? 'selected' : ''; ?>>
                            Stress
                        </option>
                        <option value="depression"
                            <?= (isset($_GET['category']) && $_GET['category'] === 'depression') ? 'selected' : ''; ?>>
                            Depression
                        </option>
                        <option value="trauma support"
                            <?= (isset($_GET['category']) && $_GET['category'] === 'trauma support') ? 'selected' : ''; ?>>
                            Trauma Support
                        </option>
                        <option value="mindfulness and meditation"
                            <?= (isset($_GET['category']) && $_GET['category'] === 'mindfulness and meditation') ? 'selected' : ''; ?>>
                            Mindfulness &amp; Meditation
                        </option>
                    </select>
                </div>
                <button type="submit"><i class="fas fa-filter"></i> Apply Filters</button>
            </form>
        </section>

        <!-- Forums I Created -->
        <section class="my-forums-section">
            <h2><i class="fas fa-crown"></i> Forums I Created</h2>
            <?php if (empty($createdCommunities)): ?>
                <div class="empty-state">
                    <h3>You haven't created any forums yet</h3>
                    <p>Start your own support community to connect with others facing similar challenges</p>
                    <a href="createForum.php" class="btn btn-primary">Create Your First Forum</a>
                </div>
            <?php else: ?>
                <div class="forum-grid">
                    <?php foreach ($createdCommunities as $community): ?>
                        <div class="forum-card">
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
                                <div class="admin-badge"><i class="fas fa-crown"></i> Creator/Admin</div>
                            </div>
                            <div class="forum-content">
                                <h3 class="forum-title"><?= htmlspecialchars($community['name']); ?></h3>
                                <p class="forum-description"><?= htmlspecialchars($community['description']); ?></p>
                                <div class="forum-meta">
                                    <div class="forum-stats">
                                        <div class="forum-stat">
                                            <i class="fas fa-users"></i>
                                            <?php
                                            $members = $communityMemberDA->getCommunityMembersById($community['community_id']);
                                            echo count($members);
                                            ?>
                                        </div>
                                        <div class="forum-stat">
                                            <i class="fas fa-tag"></i>
                                            <?= ucfirst(htmlspecialchars($community['category'] ?? 'General')); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="forum-actions">
                                    <a href="forumDetail.php?id=<?php echo $community['community_id']; ?>" class="btn btn-view">View</a>
                                    <a href="forumInfo.php?id=<?php echo $community['community_id']; ?>" class="btn btn-manage"><i class="fas fa-cog"></i> Manage</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Forums I Joined -->
        <section class="my-forums-section">
            <h2><i class="fas fa-users"></i> Forums I Joined</h2>
            <?php if (empty($joinedCommunities)): ?>
                <div class="empty-state">
                    <h3>You haven't joined any forums yet</h3>
                    <p>Explore our communities to find support and connect with others</p>
                    <a href="communityMain.php" class="btn btn-primary">Find

System: Find Support Communities</a>
                </div>
            <?php else: ?>
                <div class="forum-grid">
                    <?php foreach ($joinedCommunities as $community): ?>
                        <div class="forum-card">
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
                                <?php if ($community['role'] == 'Moderator'): ?>
                                    <div class="moderator-badge"><i class="fas fa-shield-alt"></i> Moderator</div>
                                <?php else: ?>
                                    <div class="member-badge"><i class="fas fa-user"></i> Member</div>
                                <?php endif; ?>
                            </div>
                            <div class="forum-content">
                                <h3 class="forum-title"><?= htmlspecialchars($community['name']); ?></h3>
                                <p class="forum-description"><?= htmlspecialchars($community['description']); ?></p>
                                <div class="forum-meta">
                                    <div class="forum-stats">
                                        <div class="forum-stat">
                                            <i class="fas fa-users"></i>
                                            <?php
                                            $members = $communityMemberDA->getCommunityMembersById($community['community_id']);
                                            echo count($members);
                                            ?>
                                        </div>
                                        <div class="forum-stat">
                                            <i class="fas fa-tag"></i>
                                            <?= ucfirst(htmlspecialchars($community['category'] ?? 'General')); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="forum-actions">
                                    <a href="forumDetail.php?id=<?= $community['community_id']; ?>" class="btn btn-view">View</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Encouragement Section -->
        <section class="encouragement-section">
            <div class="encouragement-content">
                <h3><i class="fas fa-heart"></i> Your Journey Matters</h3>
                <p>Connecting with others who understand your experiences can make a significant difference in your mental health journey. Whether you're sharing your own story or supporting others, remember that every step you take is valuable.</p>
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
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>About MiloDoc</h3>
                <p>We're dedicated to providing safe spaces for mental health support and community building.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="communityMain.php"><i class="fas fa-users"></i> Forums</a></li>
                    <li><a href="resources.php"><i class="fas fa-book"></i> Resources</a></li>
                    <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact Us</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Emergency Support</h3>
                <p>If you're in crisis, please reach out for immediate help:</p>
                <a href="tel:988" class="hotline-btn"><i class="fas fa-phone"></i> 988 Suicide & Crisis Lifeline</a>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 MiloDoc Mental Health Support Community. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Self-care tips array
        const selfCareTips = [
            "Take a moment to breathe deeply and check in with yourself. How are you feeling today?",
            "Remember that it's okay to set boundaries and prioritize your mental health.",
            "Small steps forward are still progress. Celebrate your victories, no matter how small.",
            "Stay hydrated and try to get enough rest tonight. Your body and mind need it.",
            "Consider taking a short walk outside today - nature can help clear your mind.",
            "You don't have to face everything alone. Reaching out for support shows strength.",
            "Practice gratitude by noting three things you appreciate about today.",
            "It's okay to take breaks from social media and the news when feeling overwhelmed.",
            "Your feelings are valid, whatever they may be. Allow yourself to experience them.",
            "Remember that healing isn't linear - ups and downs are a normal part of the journey."
        ];

        // Function to display random self-care tip
        document.addEventListener('DOMContentLoaded', function() {
            // Add mobile sidebar toggle if needed for responsive design
            if (window.innerWidth <= 1200) {
                const toggleButton = document.createElement('div');
                toggleButton.className = 'sidebar-toggle';
                toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
                document.body.appendChild(toggleButton);
            }

            // Leave community button hover effect
            const leaveButtons = document.querySelectorAll('.btn-leave');
            leaveButtons.forEach(button => {
                button.addEventListener('mouseover', function() {
                    this.textContent = 'Leave Forum';
                    this.classList.add('btn-danger');
                });

                button.addEventListener('mouseout', function() {
                    this.textContent = 'Leave';
                    this.classList.remove('btn-danger');
                });
            });
        });
    </script>
</body>

</html>