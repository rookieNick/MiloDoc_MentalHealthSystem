<?php
require_once(__DIR__ . '/../includes/database/communityDA.php');
require_once(__DIR__ . '/../includes/database/userDA.php');
// Hardcode the user ID as per communityMain.php
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

// Initialize CommunityDA to fetch forums
$communityDA = new CommunityDA();
$userDA = new UserDA();
$userData = $userDA->getUserByEmail($useremail);
$userID = $userData['user_id'];
// Fetch all communities created by the user
$createdForums = array_filter($communityDA->getAllCommunities(), function ($community) use ($userID) {
    return $community['creator_id'] === $userID;
});
?>

<div class="right-nav-bar">
    <div class="nav-section">
        <div class="nav-title">
            <span>Your Created Forums</span>
        </div>
        <ul class="forum-list">
            <?php if (empty($createdForums)): ?>
                <li class="no-forums">
                    <span>No created forums yet.</span>
                </li>
            <?php else: ?>
                <?php foreach ($createdForums as $forum): ?>
                    <li class="forum-item">
                        <a href="forumDetail.php?id=<?= htmlspecialchars($forum['community_id']); ?>&userID=<?= htmlspecialchars($userID); ?>" class="forum-link">
                            <div class="forum-pic">
                                <img src="<?= !empty($forum['picture_url']) && file_exists(__DIR__ . "/../" . $forum['picture_url']) ? "../" . htmlspecialchars($forum['picture_url']) : "../assets/image/default_image.jpg"; ?>" alt="<?= htmlspecialchars($forum['name']); ?>">
                            </div>
                            <span class="forum-name"><?= htmlspecialchars($forum['name']); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>

<style>
:root {
    --primary-color: #5E81AC;
    --secondary-color: #81A1C1;
    --light-bg: #ECEFF4;
    --text-color: #2E3440;
    --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    --border-radius: 8px;
}

.right-nav-bar {
    position: fixed;
    right: 0;
    top: 0;
    width: 60px;
    height: 100vh;
    background-color: white;
    border-radius: 8px 0 0 8px;
    box-shadow: -2px 0 6px rgba(0, 0, 0, 0.1);
    padding: 15px 0;
    z-index: 2000; /* Increased z-index to ensure it appears above other elements */
    overflow: visible;
    align-items: center;
}

.nav-section {
    display: flex;
    flex-direction: column;
    height: 100%;
    width: 100%;
    align-items: center; /* Center content */
}

.nav-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 15px;
    text-align: center;
    padding: 5px;
    position: sticky;
    top: 0;
    background-color: white;
    z-index: 1;
    line-height: 1.2;
    white-space: normal;
}

.nav-title i {
    display: none;
}

.forum-list {
    list-style: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-grow: 1;
    overflow-y: auto;
    padding-bottom: 15px;
    width: 100%;
    padding-left: 0; /* Remove default padding that might affect centering */
}

/* Customize scrollbar for better visibility */
.forum-list::-webkit-scrollbar {
    width: 6px;
}

.forum-list::-webkit-scrollbar-track {
    background: var(--light-bg);
    border-radius: 3px;
}

.forum-list::-webkit-scrollbar-thumb {
    background: var(--secondary-color);
    border-radius: 3px;
}

.forum-list::-webkit-scrollbar-thumb:hover {
    background: var(--primary-color);
}

.forum-item {
    margin-bottom: 15px; /* Increased from 10px */
    transition: all 0.3s ease;
    width: 100%;
    display: flex;
    justify-content: center;
}

.forum-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    position: relative;
    width: 100%;
    justify-content: center; /* Ensure the image is centered */
}

.forum-pic {
    width: 48px; /* Reduced size for better fit */
    height: 48px; /* Made square for consistency */
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    transition: all 0.3s ease;
    border: 2px solid var(--light-bg);
    position: relative;
    z-index: 1;
}

.forum-pic img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.forum-name {
    position: absolute;
    right: 70px; /* Position to the left of the nav bar */
    top: 50%;
    transform: translateY(-50%);
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 0.9rem;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease 0.1s, visibility 0.3s ease 0.1s;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    z-index: 10000; /* Increased z-index to ensure it appears above everything */
    min-width: 150px;
    text-align: center;
    pointer-events: none; /* Prevents the tooltip from interfering with clicks */
}

.forum-pic:hover + .forum-name, .forum-name:hover {
    opacity: 1;
    visibility: visible;
}

.forum-pic:hover {
    transform: scale(1.05);
    border-color: var(--primary-color);
}

.no-forums {
    font-size: 0.85rem;
    color: #666;
    text-align: center;
    padding: 5px;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .right-nav-bar {
        display: none;
    }

    .right-nav-bar.active {
        display: block;
        width: 60px;
        right: 0;
        top: 0;
        height: 100vh;
        border-radius: 0;
        z-index: 2000;
    }

    .forum-list {
        list-style: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-grow: 1;
    overflow-y: auto;
    padding-bottom: 15px;
    width: 100%; /* Ensure it uses full width */
    }

    .forum-name {
        right: auto;
        left: 70px; /* On mobile, position to the right if toggled */
    }
}
</style>