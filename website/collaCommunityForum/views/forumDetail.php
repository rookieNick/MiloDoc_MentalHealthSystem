<?php
session_start();
require_once(__DIR__ . '/../includes/database/communityDA.php');
require_once(__DIR__ . '/../includes/database/postDA.php');
require_once(__DIR__ . '/../includes/database/postMediaDA.php');
require_once(__DIR__ . '/../includes/database/communityMemberDA.php');
require_once(__DIR__ . '/../includes/database/userDA.php');
$communityDA = new CommunityDA();
$postDA = new PostDA();
$postMediaDA = new PostMediaDA();
$communityMemberDA = new CommunityMemberDA();
$userDA = new UserDA();
$communityId = isset($_GET['id']) ? $_GET['id'] : null;
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
$userData =  $userDA->getUserByEmail($useremail);
$userId = $userData['user_id'];
$community = $communityDA->getCommunityById($communityId);
// Pass the user ID to retrieve posts with user's previous votes
$posts = $postDA->getPostByCommunityID($communityId, $userId);
$posts = $postDA->getPostByCommunityID($communityId, $userId);
if (!is_array($posts)) {
    $posts = []; // Initialize as empty array to prevent errors
}
// Fetch usernames for all posts
$authorUsernames = [];
foreach ($posts as $post) {
    $authorId = $post['author_id'];
    if (!isset($authorUsernames[$authorId])) {
        $user = $userDA->getUserById($authorId);
        $authorUsernames[$authorId] = $user ? $user['username'] : 'Unknown User';
    }
}
if (!$community) {
    echo "<h1>Forum not found</h1>";
    exit;
}
$isMember = false;
$isMemberAdmin = false;
$communityMembers = $communityMemberDA->getAllCommunityMembers();
if ($communityMembers) {
    foreach ($communityMembers as $member) {
        if ($member['community_id'] === $communityId && $member['user_id'] === $userId) {
            $isMember = true;
            if ($member['role'] === "Admin") {
                $isMemberAdmin = true;
            }
            break; // Exit loop once the user is found
        }
    }
}
// Get the forum visibility from your existing database column
$forumVisibility = $community['visibility'];
$isPublicForum = ($forumVisibility === 'Public');
$isAnonymousForum = ($forumVisibility === 'Anonymous');
$isPrivateForum = ($forumVisibility === 'Private');
// Modify the image path handling
$backgroundImage = !empty($community['picture_url'])
    ? '../assets/image/forumImg/' . htmlspecialchars(basename($community['picture_url']))
    : null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title><?= htmlspecialchars($community['name']); ?> - Forum</title>
    <link href="../assets/css/forumDetail.css" rel="stylesheet">
    <link href="../assets/css/forumImgBg.css" rel="stylesheet">
    <link href="../assets/css/forumVisibility.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        <?php if ($backgroundImage): ?>.main-content header {
            background-image: url('<?= $backgroundImage; ?>');
        }

        <?php endif; ?>

        /* Styles for the post menu */
        .post-menu-container {
            position: relative;
            display: inline-block;
        }

        .post-menu-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            color: #666;
            padding: 5px;
            line-height: 1;
        }

        .post-menu {
            display: none;
            position: absolute;
            top: 25px;
            right: 0;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            min-width: 120px;
        }

        .post-menu a {
            display: block;
            padding: 8px 12px;
            color: #333;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .post-menu a:hover {
            background-color: #f0f0f0;
        }

        .post-menu a.delete-post {
            color: #d33;
        }

        .post-menu a.delete-post:hover {
            background-color: #ffe6e6;
        }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Settings menu toggle
            const settingsBtn = document.getElementById("settings-btn");
            const settingsMenu = document.getElementById("settings-menu");

            if (settingsBtn && settingsMenu) {
                settingsBtn.addEventListener("click", function(e) {
                    e.stopPropagation(); // Prevent window click from closing immediately
                    settingsMenu.style.display = settingsMenu.style.display === "block" ? "none" : "block";
                    console.log("Settings button clicked, menu display:", settingsMenu.style.display);
                });

                // Prevent menu clicks from closing the menu
                settingsMenu.addEventListener("click", function(e) {
                    e.stopPropagation();
                });

                // Close menu when clicking outside
                window.addEventListener("click", function() {
                    settingsMenu.style.display = "none";
                });
            } else {
                console.warn("Settings button or menu not found in DOM");
            }

            // Post menu toggle
            document.querySelectorAll('.post-menu-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const menu = this.nextElementSibling;
                    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
                });
            });

            // Close post menus when clicking outside
            window.addEventListener('click', function() {
                document.querySelectorAll('.post-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
            });

            // Prevent post menu from closing when clicking inside
            document.querySelectorAll('.post-menu').forEach(menu => {
                menu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            });
        });
    </script>
</head>

<body>
    <div class="main-content">
        <?php include 'rightForumNav.php'; ?>
        <header>
            <h1><?= htmlspecialchars($community['name']); ?></h1>
            <p><?= htmlspecialchars($community['description']); ?></p>
        </header>

        <nav class="navbar">
            <a href="communityMain.php">All Forums</a>
            <a href="myForum.php">My Forum</a>
            <?php if ($isMember): ?>
                <a href="#" id="new-post-btn">+ New Post</a>
                <?php if ($community['creator_id'] === $userId && $isMemberAdmin): ?>
                    <div class="settings-container">
                        <button id="settings-btn" class="settings-button">⚙ Settings</button>
                        <div id="settings-menu" class="settings-menu">
                            <a href="forumInfo.php?id=<?= $communityId; ?>">✏ Forum Details</a>
                            <a href="updateForum.php?id=<?= $communityId; ?>">✏ Update Forum Details</a>
                            <a href="#" class="delete-btn" data-forum-id="<?= $communityId; ?>">🗑 Delete Forum</a>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="settings-container">
                        <button id="settings-btn" class="settings-button">⚙ Settings</button>
                        <div id="settings-menu" class="settings-menu">
                            <a href="#" id="quit-community-btn" data-community-id="<?= htmlspecialchars($communityId); ?>" data-user-id="<?= htmlspecialchars($userId); ?>">🗑 Quit Community</a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <button id="join-btn" class="join-btn" data-community-id="<?= htmlspecialchars($communityId); ?>" data-user-id="<?= htmlspecialchars($userId) ?>" data-visibility="<?= htmlspecialchars($forumVisibility); ?>">
                    Join Forum
                </button>
            <?php endif; ?>
        </nav>
        <section class="forum-posts <?php
                                    if (!$isMember) {
                                        echo $isPublicForum ? 'public-forum-nonmember blur-container' : 'private-forum-nonmember';
                                    }
                                    ?>">
            <?php if (!$isMember && !$isPublicForum): ?>
                <div class="private-forum-message">
                    <div class="lock-icon">🔒</div>
                    <h3>This forum is <?= strtolower($forumVisibility) ?></h3>
                    <p>You need to be a member to view posts in this forum. Join to see the content shared by this community.</p>
                </div>
            <?php else: ?>
                <ul>
                    <?php if (empty($posts)): ?>
                        <p>No posts yet. Be the first to share your thoughts!</p>
                    <?php else: ?>
                        <?php foreach ($posts as $index => $post): ?>
                            <li class="post-item" data-post-id="<?= htmlspecialchars($post['post_id']); ?>">
                                <div class="post-content">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <p>
                                            <strong><?= $isAnonymousForum ? 'Anonymous User' : htmlspecialchars($authorUsernames[$post['author_id']]); ?></strong> on <?= htmlspecialchars($post['created_at']); ?>
                                        </p>
                                        <?php if ($post['author_id'] === $userId): ?>
                                            <div class="post-menu-container">
                                                <button class="post-menu-btn" title="More options">...</button>
                                                <div class="post-menu">
                                                    <a href="#" class="delete-post" data-post-id="<?= htmlspecialchars($post['post_id']); ?>">Delete Post</a>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <p><?= nl2br(htmlspecialchars($post['content'])); ?></p>
                                </div>
                                <div class="post-media">
                                    <?php
                                    $mediaFiles = $postMediaDA->getMediaByPostId($post['post_id']);
                                    if (!empty($mediaFiles)): ?>
                                        <div class="media-container">
                                            <?php foreach ($mediaFiles as $media): ?>
                                                <?php
                                                /// Get the media_url from the database
                                                $mediaPath = htmlspecialchars($media['media_url'], ENT_QUOTES, 'UTF-8'); // e.g., assets/image/postImg/1742782471_Screenshot 2024-07-24 153003.png

                                                // Construct absolute file path using __DIR__ (views directory) and move up to project root
                                                $projectRoot = realpath(__DIR__ . '/..'); // Resolves to C:\Users\user\Documents\GitHub\MiloDoc\website\colla_communityForum
                                                $fullMediaPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $mediaPath);

                                                // For browser URLs, use absolute path starting with /
                                                $browserMediaPath = '../assets/image/postImg/' . rawurlencode(basename($media['media_url'])); // e.g., /assets/image/postImg/1742782471_Screenshot%202024-07-24%20153003.png

                                                if (file_exists($fullMediaPath)) {
                                                    $mediaType = $media['media_type'];
                                                    $isImage = strpos($mediaType, 'image') !== false;
                                                    $isVideo = strpos($mediaType, 'video') !== false;
                                                    $dataAttributes = sprintf(
                                                        'data-media-type="%s" data-media-src="%s"',
                                                        $isImage ? 'image' : 'video',
                                                        $browserMediaPath
                                                    );
                                                ?>
                                                    <?php if ($isImage): ?>
                                                        <img src="<?php echo $browserMediaPath; ?>" alt="Post Image" class="post-image" <?php echo $dataAttributes; ?>>
                                                    <?php elseif ($isVideo): ?>
                                                        <video class="post-video" <?php echo $dataAttributes; ?>>
                                                            <source src="<?php echo $browserMediaPath; ?>" type="<?php echo htmlspecialchars($mediaType, ENT_QUOTES, 'UTF-8'); ?>">
                                                            Your browser does not support the video tag.
                                                        </video>
                                                    <?php endif; ?>
                                                <?php } else {
                                                    echo "<div class='media-error'>⚠️ Media file not found: " . htmlspecialchars($mediaPath, ENT_QUOTES, 'UTF-8') . " (Full path: $fullMediaPath)</div>";
                                                }
                                                ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="action-section">
                                    <div class="vote-section">
                                        <button class="vote-button upvote <?= (!empty($post['user_vote']) && $post['user_vote'] === 'up') ? 'active' : '' ?> <?= (!$isMember) ? 'non-member-btn' : '' ?>"
                                            data-post-id="<?= htmlspecialchars($post['post_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-vote-type="up" <?= (!$isMember) ? 'disabled' : '' ?>>
                                            <img src="../assets/image/icons/upvote-removebg.png" alt="Upvote" width="20" height="20">
                                        </button>
                                        <span id="upvotes-<?= htmlspecialchars($post['post_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?= htmlspecialchars($post['upvotes'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>

                                        <button class="vote-button downvote <?= (!empty($post['user_vote']) && $post['user_vote'] === 'down') ? 'active' : '' ?> <?= (!$isMember) ? 'non-member-btn' : '' ?>"
                                            data-post-id="<?= htmlspecialchars($post['post_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-vote-type="down" <?= (!$isMember) ? 'disabled' : '' ?>>
                                            <img src="../assets/image/icons/downvote-removebg.png" alt="Downvote" width="20" height="20">
                                        </button>
                                        <span id="downvotes-<?= htmlspecialchars($post['post_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?= htmlspecialchars($post['downvotes'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>

                                        <?php if ($isMember): ?>
                                            <a href="postDetail.php?id=<?= htmlspecialchars($post['post_id'], ENT_QUOTES, 'UTF-8'); ?>" class="comment-btn">
                                                💬 Comment
                                            </a>
                                        <?php else: ?>
                                            <button class="comment-btn non-member-btn" disabled>
                                                💬 Comment
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>

                <?php if (!$isMember && $isPublicForum && count($posts) > 3): ?>
                    <div class="join-message">
                        <h3>Want to see more?</h3>
                        <p>Join this forum to view all posts and participate in discussions</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
    <?php if ($isMember): ?>
        <form id="create-post-form" method="post" action="../controllers/createPostController.php" enctype="multipart/form-data">
            <input type="hidden" name="community_id" value="<?= htmlspecialchars($communityId); ?>">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId); ?>">

            <div id="create-post-modal" class="modal">
                <div class="modal-content">
                    <span class="close-btn">&times;</span>

                    <div class="create-post-header">
                        <h2>Create New Post</h2>
                    </div>

                    <div class="create-post-body">
                        <div class="textarea-container">
                            <textarea id="post-text" name="content" class="post-textarea" placeholder="What's on your mind?" maxlength="2000" required></textarea>
                            <div class="char-counter"><span id="char-count">0</span>/2000</div>
                        </div>

                        <div id="media-container" class="media-container empty">
                            <div id="media-placeholder" class="media-placeholder">
                                <div class="placeholder-icon">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#bdc3c7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                </div>
                                <p>Add photos/videos<br>or drag and drop</p>
                            </div>
                        </div>

                        <div class="media-actions" id="media-actions">
                            <button type="button" id="add-media-btn" class="media-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 5px;">
                                    <path d="M12 5v14M5 12h14"></path>
                                </svg>
                                Add photos/videos
                            </button>
                            <span class="media-info">Up to 5 files (max 10MB each)</span>
                        </div>

                        <input type="file" id="hidden-media-input" name="media[]" accept="image/*, video/*" multiple style="width:1px; height:1px; opacity:0; position:absolute;">
                    </div>

                    <div class="create-post-footer">
                        <div class="post-guidelines">
                            <p>Be respectful and follow community guidelines</p>
                        </div>
                        <button type="submit" id="post-btn" class="post-button">Post</button>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>

    <div id="join-request-modal" class="modal">
        <div class="modal-content">
            <span class="join-modal-close">&times;</span>

            <div class="join-request-header">
                <h2>Request to Join Forum</h2>
            </div>

            <div class="join-request-body">
                <p>Please tell us why you'd like to join this forum:</p>
                <textarea id="join-reason" name="reason" class="join-textarea" placeholder="I'd like to join because..." maxlength="500" required></textarea>
                <div class="char-counter"><span id="reason-char-count">0</span>/500</div>
            </div>

            <div class="join-request-footer">
                <button type="button" id="cancel-join-btn" class="cancel-button">Cancel</button>
                <button type="button" id="submit-join-btn" class="submit-button">Submit Request</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // === General Elements ===
            const modal = document.getElementById("create-post-modal");
            const newPostBtn = document.getElementById("new-post-btn");
            const closeBtn = document.querySelector(".close-btn");
            const postForm = document.getElementById("create-post-form");
            const postText = document.getElementById("post-text");
            const postBtn = document.getElementById("post-btn");
            const charCount = document.getElementById("char-count");
            const addMediaBtn = document.getElementById("add-media-btn");
            const hiddenMediaInput = document.getElementById("hidden-media-input");
            const mediaContainer = document.getElementById("media-container");
            const mediaPlaceholder = document.getElementById("media-placeholder");
            const mediaActions = document.getElementById("media-actions");
            const deleteBtn = document.querySelector(".delete-btn");

            // === Constants ===
            const MAX_FILES = 5;
            const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
            let fileCounter = 0;

            // === New Post Modal ===
            if (newPostBtn) {
                newPostBtn.addEventListener("click", function(e) {
                    e.preventDefault();
                    modal.style.display = "flex";
                    resetForm();
                });
            }

            if (closeBtn) {
                closeBtn.addEventListener("click", closeModal);
            }

            window.addEventListener("click", function(event) {
                if (event.target === modal) closeModal();
            });

            window.addEventListener("keydown", function(e) {
                if (e.key === "Escape" && modal.style.display === "flex") closeModal();
            });

            // === Character Counter ===
            if (postText) {
                postText.addEventListener("input", function() {
                    const length = this.value.length;
                    charCount.textContent = length;
                    charCount.style.color = length > 1800 ? "#e74c3c" : length > 1500 ? "#f39c12" : "#95a5a6";
                    validateForm();
                });
            }

            // === Media Handling ===
            if (addMediaBtn) {
                addMediaBtn.addEventListener("click", () => hiddenMediaInput.click());
            }

            if (mediaPlaceholder) {
                mediaPlaceholder.addEventListener("click", () => {
                    if (fileCounter < MAX_FILES) hiddenMediaInput.click();
                    else showMessage("You can only add up to 5 files", "error");
                });
            }

            if (hiddenMediaInput) {
                hiddenMediaInput.addEventListener("change", handleFiles);
            }

            mediaContainer.addEventListener("dragover", e => {
                e.preventDefault();
                mediaContainer.classList.add("dragover");
            });

            mediaContainer.addEventListener("dragleave", e => {
                e.preventDefault();
                mediaContainer.classList.remove("dragover");
            });

            mediaContainer.addEventListener("drop", e => {
                e.preventDefault();
                mediaContainer.classList.remove("dragover");
                const files = Array.from(e.dataTransfer.files);
                if (files.length > 0) {
                    processFiles(files.slice(0, MAX_FILES - fileCounter));
                }
            });

            // === Form Submission ===
            if (postForm) {
                postForm.addEventListener("submit", function(e) {
                    if (!validateForm()) {
                        e.preventDefault();
                        return;
                    }
                    postBtn.classList.add("loading");
                    postBtn.disabled = true;
                });
            }

            // === Delete Forum ===
            if (deleteBtn) {
                deleteBtn.addEventListener("click", function(e) {
                    e.preventDefault();
                    const forumId = this.dataset.forumId;
                    Swal.fire({
                        title: "Delete Forum",
                        text: "Are you sure? This cannot be undone.",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#d33",
                        cancelButtonColor: "#3085d6",
                        confirmButtonText: "Yes, delete it!"
                    }).then(result => {
                        if (result.isConfirmed) {
                            // Show loading state
                            Swal.fire({
                                title: "Deleting...",
                                text: "Please wait while the forum is being deleted.",
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            fetch(`../controllers/deleteForumController.php?id=${forumId}`)
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            title: "Deleted!",
                                            text: data.message,
                                            icon: "success",
                                            timer: 1500,
                                            showConfirmButton: false
                                        }).then(() => {
                                            window.location.href = "communityMain.php";
                                        });
                                    } else {
                                        Swal.fire("Error!", data.message, "error");
                                    }
                                })
                                .catch(error => {
                                    Swal.fire("Error!", "An error occurred: " + error, "error");
                                    console.error("Error:", error);
                                });
                        }
                    });
                });
            }

            // === Delete Post ===
            document.querySelectorAll('.delete-post').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const postId = this.dataset.postId;
                    Swal.fire({
                        title: "Delete Post",
                        text: "Are you sure you want to delete this post? This action cannot be undone.",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#d33",
                        cancelButtonColor: "#3085d6",
                        confirmButtonText: "Yes, delete it!"
                    }).then(result => {
                        if (result.isConfirmed) {
                            fetch(`../controllers/deletePostController.php?post_id=${postId}`, {
                                    method: 'POST'
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        // Remove the post from the UI
                                        const postElement = document.querySelector(`.post-item[data-post-id="${postId}"]`);
                                        if (postElement) {
                                            postElement.remove();
                                        }
                                        Swal.fire({
                                            title: "Deleted!",
                                            text: "Post deleted successfully!",
                                            icon: "success",
                                            timer: 1500,
                                            showConfirmButton: false
                                        }).then(() => {
                                            // Check if there are any posts left
                                            const remainingPosts = document.querySelectorAll('.post-item');
                                            if (remainingPosts.length === 0) {
                                                window.location.href = "forumDetail.php?id=<?php echo htmlspecialchars($communityId); ?>";
                                            }
                                        });
                                    } else {
                                        Swal.fire("Error!", data.message, "error");
                                    }
                                })
                                .catch(error => {
                                    Swal.fire("Error!", "An error occurred: " + error, "error");
                                    console.error("Error:", error);
                                });
                        }
                    });
                });
            });

            // === Lightbox Setup ===
            const lightboxContainer = document.createElement("div");
            lightboxContainer.className = "lightbox-container";
            lightboxContainer.innerHTML = `
                <div class="lightbox-overlay"></div>
                <div class="lightbox-content">
                    <button class="lightbox-close">&times;</button>
                    <div class="lightbox-media-container"></div>
                    <div class="lightbox-controls">
                        <button class="lightbox-prev">&lt;</button>
                        <button class="lightbox-next">&gt;</button>
                    </div>
                </div>
            `;
            document.body.appendChild(lightboxContainer);

            const overlay = document.querySelector(".lightbox-overlay");
            const mediaContainerLightbox = document.querySelector(".lightbox-media-container");
            const closeLightboxBtn = document.querySelector(".lightbox-close");
            const prevBtn = document.querySelector(".lightbox-prev");
            const nextBtn = document.querySelector(".lightbox-next");

            let currentIndex = 0;
            let mediaItems = [];

            document.querySelectorAll(".post-image, .post-video").forEach((mediaElement, index) => {
                mediaElement.style.cursor = "pointer";
                mediaElement.addEventListener("click", e => {
                    e.preventDefault();
                    const postItem = mediaElement.closest(".post-item");
                    mediaItems = Array.from(postItem.querySelectorAll(".post-image, .post-video"));
                    currentIndex = mediaItems.indexOf(mediaElement);
                    openLightbox(mediaElement);
                });
            });

            // === Helper Functions ===
            function closeModal() {
                if (postText.value.trim() || fileCounter > 0) {
                    if (confirm("Discard your post?")) {
                        resetForm();
                        modal.style.display = "none";
                    }
                } else {
                    modal.style.display = "none";
                }
            }

            function handleFiles(e) {
                const files = Array.from(e.target.files);
                if (fileCounter + files.length > MAX_FILES) {
                    showMessage(`You can only add up to ${MAX_FILES} files total`, "error");
                    processFiles(files.slice(0, MAX_FILES - fileCounter));
                } else {
                    processFiles(files);
                }
            }

            function processFiles(files) {
                const validFiles = files.filter(file => {
                    if (file.size > MAX_FILE_SIZE) {
                        showMessage(`File "${file.name}" exceeds 10MB limit`, "error");
                        return false;
                    }
                    return true;
                });

                if (validFiles.length > 0) {
                    mediaContainer.classList.remove("empty");
                    mediaPlaceholder.style.display = "none";
                    mediaActions.style.display = "flex";

                    validFiles.forEach(file => {
                        const reader = new FileReader();
                        reader.onload = evt => {
                            const mediaItem = document.createElement("div");
                            mediaItem.className = "media-item";

                            let element;
                            if (file.type.startsWith("image/")) {
                                element = document.createElement("img");
                                element.src = evt.target.result;
                            } else if (file.type.startsWith("video/")) {
                                element = document.createElement("video");
                                element.src = evt.target.result;
                                element.controls = true;
                            }

                            const removeBtn = document.createElement("button");
                            removeBtn.className = "remove-media-btn";
                            removeBtn.innerHTML = "&times;";
                            removeBtn.type = "button";
                            removeBtn.addEventListener("click", () => {
                                mediaItem.remove();
                                fileCounter--;
                                checkMediaContainer();
                                validateForm();
                            });

                            if (element) {
                                mediaItem.appendChild(element);
                                mediaItem.appendChild(removeBtn);
                                mediaContainer.appendChild(mediaItem);
                                fileCounter++;
                                validateForm();
                            }
                        };
                        reader.readAsDataURL(file);
                    });
                }
            }

            function checkMediaContainer() {
                if (!mediaContainer.querySelector(".media-item")) {
                    mediaContainer.classList.add("empty");
                    mediaPlaceholder.style.display = "flex";
                    mediaActions.style.display = "none";
                    fileCounter = 0;
                }
            }

            function validateForm() {
                const isValid = postText.value.trim().length > 0 || fileCounter > 0;
                postBtn.disabled = !isValid;
                return isValid;
            }

            function resetForm() {
                postText.value = "";
                charCount.textContent = "0";
                charCount.style.color = "#95a5a6";
                mediaContainer.querySelectorAll(".media-item").forEach(item => item.remove());
                checkMediaContainer();
                validateForm();
            }

            function showMessage(message, type = "info") {
                Swal.fire({
                    text: message,
                    icon: type,
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }

            function openLightbox(mediaElement) {
                let lightboxMedia;
                if (mediaElement.tagName === "IMG") {
                    lightboxMedia = document.createElement("img");
                    lightboxMedia.src = mediaElement.src;
                    lightboxMedia.className = "lightbox-image";
                } else if (mediaElement.tagName === "VIDEO") {
                    lightboxMedia = document.createElement("video");
                    lightboxMedia.src = mediaElement.querySelector("source").src;
                    lightboxMedia.className = "lightbox-video";
                    lightboxMedia.controls = true;
                    lightboxMedia.autoplay = true;
                }

                mediaContainerLightbox.innerHTML = "";
                if (lightboxMedia) mediaContainerLightbox.appendChild(lightboxMedia);

                prevBtn.style.display = mediaItems.length <= 1 ? "none" : "block";
                nextBtn.style.display = mediaItems.length <= 1 ? "none" : "block";

                lightboxContainer.style.display = "flex";
                setTimeout(() => lightboxContainer.classList.add("active"), 10);
                document.body.style.overflow = "hidden";
            }

            function closeLightbox() {
                lightboxContainer.classList.remove("active");
                setTimeout(() => {
                    lightboxContainer.style.display = "none";
                    mediaContainerLightbox.innerHTML = "";
                    document.body.style.overflow = "";
                    const video = mediaContainerLightbox.querySelector("video");
                    if (video) video.pause();
                }, 300);
            }

            function showPrevMedia() {
                currentIndex = (currentIndex - 1 + mediaItems.length) % mediaItems.length;
                openLightbox(mediaItems[currentIndex]);
            }

            function showNextMedia() {
                currentIndex = (currentIndex + 1) % mediaItems.length;
                openLightbox(mediaItems[currentIndex]);
            }

            closeLightboxBtn.addEventListener("click", closeLightbox);
            overlay.addEventListener("click", closeLightbox);
            prevBtn.addEventListener("click", showPrevMedia);
            nextBtn.addEventListener("click", showNextMedia);

            document.addEventListener("keydown", e => {
                if (lightboxContainer.style.display !== "none") {
                    if (e.key === "Escape") closeLightbox();
                    else if (e.key === "ArrowLeft") showPrevMedia();
                    else if (e.key === "ArrowRight") showNextMedia();
                }
            });
        });

        // === Voting System ===
        document.addEventListener("DOMContentLoaded", function() {
            const voteButtons = document.querySelectorAll('.vote-button');

            console.log(`Found ${voteButtons.length} vote buttons`);

            voteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    if (this.classList.contains('non-member-btn')) {
                        showMessage('You need to join the forum to vote', 'warning');
                        return;
                    }

                    const postId = this.getAttribute('data-post-id');
                    const voteType = this.getAttribute('data-vote-type');
                    const userId = "<?= htmlspecialchars($userId); ?>";

                    console.log(`Vote clicked - Post: ${postId}, Type: ${voteType}, User: ${userId}`);

                    const formData = new FormData();
                    formData.append('post_id', postId);
                    formData.append('user_id', userId);
                    formData.append('vote_type', voteType);

                    this.disabled = true;

                    fetch('../controllers/postVoteController.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            console.log('Response status:', response.status);
                            return response.text();
                        })
                        .then(text => {
                            try {
                                console.log('Raw response:', text);
                                const data = JSON.parse(text);
                                console.log('Parsed data:', data);

                                this.disabled = false;

                                if (data.success) {
                                    document.getElementById(`upvotes-${postId}`).textContent = data.upvotes;
                                    document.getElementById(`downvotes-${postId}`).textContent = data.downvotes;

                                    const upvoteBtn = document.querySelector(`.upvote[data-post-id="${postId}"]`);
                                    const downvoteBtn = document.querySelector(`.downvote[data-post-id="${postId}"]`);

                                    upvoteBtn.classList.remove('active');
                                    downvoteBtn.classList.remove('active');

                                    if (data.currentVote === 'up') {
                                        upvoteBtn.classList.add('active');
                                    } else if (data.currentVote === 'down') {
                                        downvoteBtn.classList.add('active');
                                    }

                                    showMessage(`Vote ${data.message}`, 'success');
                                } else {
                                    showMessage(data.message, 'error');
                                }
                            } catch (e) {
                                console.error('Failed to parse JSON response:', text);
                                showMessage('Invalid response from server', 'error');
                                this.disabled = false;
                            }
                        })
                        .catch(error => {
                            this.disabled = false;
                            console.error('Error:', error);
                            showMessage('Failed to process vote: ' + error.message, 'error');
                        });
                });
            });

            function showMessage(message, type = 'info') {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        text: message,
                        icon: type,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                } else {
                    alert(message);
                }
                console.log(`${type.toUpperCase()}: ${message}`);
            }
        });

        document.addEventListener("DOMContentLoaded", function() {
            const joinForumBtn = document.getElementById("join-forum-btn");
            if (joinForumBtn) {
                joinForumBtn.addEventListener("click", joinForum);
            }

            const joinPrivateBtn = document.getElementById("join-private-btn");
            if (joinPrivateBtn) {
                joinPrivateBtn.addEventListener("click", joinForum);
            }

            function joinForum() {
                const communityId = this.getAttribute("data-community-id");
                const userId = this.getAttribute("data-user-id");

                const formData = new FormData();
                formData.append("community_id", communityId);
                formData.append("user_id", userId);

                this.disabled = true;
                this.textContent = "Joining...";

                fetch("../controllers/joinForumController.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: "Success!",
                                text: data.message || "You've joined this forum!",
                                icon: "success"
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: "Error",
                                text: data.message || "Failed to join this forum.",
                                icon: "error"
                            });
                            this.disabled = false;
                            this.textContent = "Join Forum";
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        Swal.fire({
                            title: "Error",
                            text: "An unexpected error occurred. Please try again.",
                            icon: "error"
                        });
                        this.disabled = false;
                        this.textContent = "Join Forum";
                    });
            }
        });

        document.addEventListener("DOMContentLoaded", function() {
    const quitCommunityBtn = document.getElementById("quit-community-btn");

    if (quitCommunityBtn) {
        quitCommunityBtn.addEventListener("click", function(e) {
            e.preventDefault();
            const communityId = this.getAttribute("data-community-id");
            const userId = this.getAttribute("data-user-id");

            Swal.fire({
                title: "Quit Community",
                text: "Are you sure you want to quit this community? This action cannot be undone.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, quit!"
            }).then(result => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "Quitting...",
                        text: "Please wait while you are removed from the community.",
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    const formData = new FormData();
                    formData.append("community_id", communityId);
                    formData.append("user_id", userId);

                    fetch("../controllers/quitCommunityController.php", {
                        method: "POST",
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: "Success!",
                                    text: data.message || "You have successfully quit the community.",
                                    icon: "success",
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = "communityMain.php";
                                });
                            } else {
                                Swal.fire({
                                    title: "Error",
                                    text: data.message || "Failed to quit the community.",
                                    icon: "error"
                                });
                            }
                        })
                        .catch(error => {
                            console.error("Fetch Error:", error);
                            Swal.fire({
                                title: "Error",
                                text: "A network error occurred. Please check your connection and try again.",
                                icon: "error"
                            });
                        });
                }
            });
        });
    }
});
        // === Join Request Modal Functionality ===
        document.addEventListener("DOMContentLoaded", function() {
            const joinForumBtn = document.getElementById("join-forum-btn");
            const joinBtn = document.getElementById("join-btn");
            const joinPrivateBtn = document.getElementById("join-private-btn");
            const joinRequestModal = document.getElementById("join-request-modal");
            const joinReasonInput = document.getElementById("join-reason");
            const reasonCharCount = document.getElementById("reason-char-count");
            const submitJoinBtn = document.getElementById("submit-join-btn");
            const cancelJoinBtn = document.getElementById("cancel-join-btn");
            const joinModalClose = document.querySelector(".join-modal-close");
            let currentCommunityId = null;
            let currentUserId = null;

            function handleJoinClick(e) {
                const visibility = this.getAttribute("data-visibility");
                currentCommunityId = this.getAttribute("data-community-id");
                currentUserId = this.getAttribute("data-user-id");
                if (visibility === "Public") {
                    joinForum(currentCommunityId, currentUserId, this);
                } else {
                    showJoinModal();
                }
            }

            if (joinForumBtn) {
                joinForumBtn.addEventListener("click", handleJoinClick);
            }
            if (joinBtn) {
                joinBtn.addEventListener("click", handleJoinClick);
            }
            if (joinPrivateBtn) {
                joinPrivateBtn.addEventListener("click", handleJoinClick);
            }

            if (joinModalClose) {
                joinModalClose.addEventListener("click", closeJoinModal);
            }
            if (cancelJoinBtn) {
                cancelJoinBtn.addEventListener("click", closeJoinModal);
            }

            if (joinReasonInput) {
                joinReasonInput.addEventListener("input", function() {
                    const length = this.value.length;
                    reasonCharCount.textContent = length;
                    reasonCharCount.style.color = length > 400 ? "#e74c3c" : length > 300 ? "#f39c12" : "#95a5a6";
                    submitJoinBtn.disabled = length < 1;
                });
            }

            if (submitJoinBtn) {
                submitJoinBtn.addEventListener("click", submitJoinRequest);
            }

            window.addEventListener("click", function(event) {
                if (event.target === joinRequestModal) {
                    closeJoinModal();
                }
            });

            window.addEventListener("keydown", function(e) {
                if (e.key === "Escape" && joinRequestModal.style.display === "flex") {
                    closeJoinModal();
                }
            });

            function showJoinModal() {
                joinRequestModal.style.display = "flex";
                joinReasonInput.value = "";
                reasonCharCount.textContent = "0";
                submitJoinBtn.disabled = true;
                joinReasonInput.focus();
            }

            function closeJoinModal() {
                joinRequestModal.style.display = "none";
                joinReasonInput.value = "";
                reasonCharCount.textContent = "0";
                submitJoinBtn.disabled = true;
            }

            function joinForum(communityId, userId, button) {
                const formData = new FormData();
                formData.append("community_id", communityId);
                formData.append("user_id", userId);
                button.disabled = true;
                button.textContent = "Joining...";
                fetch("../controllers/joinForumController.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: "Success!",
                                text: data.message || "You've joined this forum!",
                                icon: "success"
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: "Error",
                                text: data.message || "Failed to join this forum.",
                                icon: "error"
                            });
                            button.disabled = false;
                            button.textContent = "Join Forum";
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        Swal.fire({
                            title: "Error",
                            text: "An unexpected error occurred. Please try again.",
                            icon: "error"
                        });
                        button.disabled = false;
                        button.textContent = "Join Forum";
                    });
            }

            function submitJoinRequest() {
                const reason = joinReasonInput.value.trim();
                if (!reason) {
                    showMessage("Please provide a reason for joining the forum", "warning");
                    return;
                }
                submitJoinBtn.disabled = true;
                submitJoinBtn.textContent = "Submitting...";
                const formData = new FormData();
                formData.append("community_id", currentCommunityId);
                formData.append("user_id", currentUserId);
                formData.append("reason", reason);
                fetch("../controllers/joinForumController.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        closeJoinModal();
                        if (data.success) {
                            Swal.fire({
                                title: "Request Submitted!",
                                text: data.message || "Your request to join this forum has been submitted.",
                                icon: "success"
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: "Error",
                                text: data.message || "Failed to submit join request.",
                                icon: "error"
                            });
                            submitJoinBtn.disabled = false;
                            submitJoinBtn.textContent = "Submit Request";
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        closeJoinModal();
                        Swal.fire({
                            title: "Error",
                            text: "An unexpected error occurred. Please try again.",
                            icon: "error"
                        });
                        submitJoinBtn.disabled = false;
                        submitJoinBtn.textContent = "Submit Request";
                    });
            }

            function showMessage(message, type = "info") {
                Swal.fire({
                    text: message,
                    icon: type,
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }
        });




    document.addEventListener("DOMContentLoaded", function() {
    const postForm = document.getElementById("create-post-form");
    const postText = document.getElementById("post-text");
    const postBtn = document.getElementById("post-btn");

    if (postForm) {
        postForm.addEventListener("submit", function(e) {
            e.preventDefault(); // Prevent default form submission temporarily

            const postContent = postText.value.trim();

            // Check if the post content is empty
            if (!postContent) {
                Swal.fire({
                    text: "Post content cannot be empty.",
                    icon: "warning",
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                return;
            }

            // Disable the post button to prevent multiple submissions
            postBtn.disabled = true;
            postBtn.classList.add("loading");
            postBtn.textContent = "Checking...";

            // Send the post content to the /detect_suicidal endpoint
            fetch("http://127.0.0.1:8001/detect_suicidal", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    message: postContent,
                    memory: "" // Empty string for memory
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                postBtn.disabled = false;
                postBtn.classList.remove("loading");
                postBtn.textContent = "Post";

                if (data.is_suicidal && data.confidence > 0.5) { // Adjust threshold as needed
                    Swal.fire({
                        title: "We're Here to Help",
                        html: `Your message appears to express concerning thoughts. Please consider booking a consultation with our professionals: <a href='http://localhost:8000/patient/schedule.php'>Book Consultation</a>`,
                        icon: "warning",
                        confirmButtonText: "OK"
                    }).then(() => {
                        // Submit the form regardless of the warning
                        postForm.submit();
                    });
                } else {
                    // No suicidal content detected, submit the form
                    postForm.submit();
                }
            })
            .catch(error => {
                postBtn.disabled = false;
                postBtn.classList.remove("loading");
                postBtn.textContent = "Post";
                console.error("Error connecting to suicidal detection API:", error);
                Swal.fire({
                    text: "Error connecting to the server. Your post will still be created.",
                    icon: "warning",
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                // Submit the form even if the API fails
                postForm.submit();
            });
        });
    }
});
        // Generate a new post 
    </script>
</body>

</html>