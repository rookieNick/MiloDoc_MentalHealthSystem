<?php
session_start();
require_once(__DIR__ . '/../includes/database/postDA.php');
require_once(__DIR__ . '/../includes/database/commentDA.php');
require_once(__DIR__ . '/../includes/database/postMediaDA.php');
require_once(__DIR__ . '/../includes/database/communityDA.php');
require_once(__DIR__ . '/../includes/database/userDA.php');
$postDA = new PostDA();
$commentDA = new CommentDA();
$postMediaDA = new PostMediaDA();
$communityDA = new CommunityDA();
$userDA = new UserDA();
// Get the post ID from the URL
$postId = isset($_GET['id']) ? $_GET['id'] : null;
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

$post = $postDA->getPostWithUserVote($postId, $userId);

$user = $userDA->getUserById($userId);
$authorUsernames = $user ? $user['username'] : 'Unknown User';

if (!$post) {
    echo "<h1>Post not found</h1>";
    exit;
}

// Fetch the community details to determine visibility
$community = $communityDA->getCommunityById($post['community_id']);
if (!$community) {
    echo "<h1>Community not found</h1>";
    exit;
}
$forumVisibility = $community['visibility'];
$isAnonymousForum = ($forumVisibility === 'Anonymous');

// Fetch post media
$mediaFiles = $postMediaDA->getMediaByPostId($postId);

// Fetch comments for this post
$comments = $commentDA->getCommentsByPostId($postId);
$commentUsernames = [];
foreach ($comments as $comment) {
    $authorId = $comment['author_id'];
    if (!isset($commentUsernames[$authorId])) {
        $user = $userDA->getUserById($authorId);
        $commentUsernames[$authorId] = $user ? $user['username'] : 'Unknown User';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Details</title>
    <link href="../assets/css/postDetail.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Styles for the comment menu */
        .comment-menu-container {
            position: relative;
            display: inline-block;
        }

        .comment-menu-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            color: #666;
            padding: 5px;
            line-height: 1;
        }

        .comment-menu {
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

        .comment-menu a {
            display: block;
            padding: 8px 12px;
            color: #333;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .comment-menu a:hover {
            background-color: #f0f0f0;
        }

        .comment-menu a.delete-comment {
            color: #d33;
        }

        .comment-menu a.delete-comment:hover {
            background-color: #ffe6e6;
        }
    </style>
</head>

<body>
    <div class="main-content">
        <div class="post-detail-container">
            <!-- Main Post Section -->
            <section class="main-post">
                <div class="post-header">
                    <a href="forumDetail.php?id=<?= htmlspecialchars($post['community_id']); ?>" class="back-button">
                        <img src="../assets/image/icons/back-icon.png" alt="Back">
                    </a>
                    <div class="post-author-info">
                        <span class="author-name"><?= $isAnonymousForum ? 'Anonymous User' : htmlspecialchars($authorUsernames); ?></span>
                        <span class="post-timestamp"><?= htmlspecialchars($post['created_at']); ?></span>
                    </div>
                    <div class="post-actions">
                        <!-- Add post actions like edit/delete if needed -->
                    </div>
                </div>

                <div class="post-content">
                    <p><?= nl2br(htmlspecialchars($post['content'])); ?></p>
                </div>

                <!-- Media Display -->
                <?php if (!empty($mediaFiles)): ?>
                    <div class="post-media-container">
                        <div class="post-media">
                            <?php foreach ($mediaFiles as $index => $media): ?>
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

                    <!-- Vote Section -->
                    <div class="vote-section">
                        <button class="vote-button upvote <?= (!empty($post['user_vote']) && $post['user_vote'] === 'up') ? 'active' : '' ?>"
                            data-post-id="<?= htmlspecialchars($post['post_id'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-vote-type="up">
                            <img src="../assets/image/icons/upvote-removebg.png" alt="Upvote" width="20" height="20">
                        </button>
                        <span id="upvotes-<?= htmlspecialchars($post['post_id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?= htmlspecialchars($post['upvotes'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>

                        <button class="vote-button downvote <?= (!empty($post['user_vote']) && $post['user_vote'] === 'down') ? 'active' : '' ?>"
                            data-post-id="<?= htmlspecialchars($post['post_id'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-vote-type="down">
                            <img src="../assets/image/icons/downvote-removebg.png" alt="Downvote" width="20" height="20">
                        </button>
                        <span id="downvotes-<?= htmlspecialchars($post['post_id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?= htmlspecialchars($post['downvotes'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
            </section>

            <!-- Comments Section -->
            <section class="comments-section">
                <h2>Comments</h2>

                <!-- Create Comment Form -->
                <form id="create-comment-form" method="post" action="../controllers/createCommentController.php">
                    <input type="hidden" name="post_id" value="<?= htmlspecialchars($postId); ?>">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId); ?>">

                    <div class="comment-input-container">
                        <textarea
                            name="comment_content"
                            id="comment-text"
                            placeholder="Add a comment..."
                            maxlength="1000"
                            required></textarea>
                        <div class="comment-actions">
                            <span class="char-counter"><span id="char-count">0</span>/1000</span>
                            <button type="submit" id="submit-comment-btn">Post Comment</button>
                        </div>
                    </div>
                </form>

                <!-- Comments List -->
                <div class="comments-list">
                    <?php if (empty($comments)): ?>
                        <p class="no-comments">No comments yet. Be the first to comment!</p>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment" data-comment-id="<?= htmlspecialchars($comment['comment_id']); ?>">
                                <div class="comment-header">
                                    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                        <div>
                                            <span class="comment-author"><?= $isAnonymousForum ? 'Anonymous User' : htmlspecialchars($commentUsernames[$comment['author_id']]); ?></span>
                                            <span class="comment-timestamp"><?= htmlspecialchars($comment['created_at']); ?></span>
                                        </div>
                                        <?php if ($comment['author_id'] === $userId): ?>
                                            <div class="comment-menu-container">
                                                <button class="comment-menu-btn" title="More options">...</button>
                                                <div class="comment-menu">
                                                    <a href="#" class="delete-comment" data-comment-id="<?= htmlspecialchars($comment['comment_id']); ?>">Delete Comment</a>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="comment-content">
                                    <p><?= nl2br(htmlspecialchars($comment['content'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <!-- Media Modal -->
    <div id="media-modal" class="media-modal">
        <div class="media-modal-content">
            <span class="media-modal-close">×</span>
            <div id="modal-media-container">
                <!-- Dynamically populated media will go here -->
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Media Modal Logic
            const mediaItems = document.querySelectorAll('.media-item');
            const mediaModal = document.getElementById('media-modal');
            const mediaModalClose = document.querySelector('.media-modal-close');
            const modalMediaContainer = document.getElementById('modal-media-container');

            mediaItems.forEach(item => {
                item.addEventListener('click', function() {
                    const mediaType = this.getAttribute('data-type');
                    const mediaSrc = this.getAttribute('data-src');

                    // Clear previous content
                    modalMediaContainer.innerHTML = '';

                    if (mediaType === 'image') {
                        const img = document.createElement('img');
                        img.src = mediaSrc;
                        img.classList.add('modal-image');
                        modalMediaContainer.appendChild(img);
                    } else if (mediaType === 'video') {
                        const video = document.createElement('video');
                        video.src = mediaSrc;
                        video.type = this.getAttribute('data-mime');
                        video.controls = true;
                        video.autoplay = true;
                        video.classList.add('modal-video');
                        modalMediaContainer.appendChild(video);
                    }

                    mediaModal.style.display = 'block';
                });
            });

            // Close modal when clicking on close button
            mediaModalClose.addEventListener('click', function() {
                mediaModal.style.display = 'none';

                // Pause any playing videos
                const modalVideo = modalMediaContainer.querySelector('video');
                if (modalVideo) {
                    modalVideo.pause();
                }
            });

            // Close modal when clicking outside of the modal content
            mediaModal.addEventListener('click', function(event) {
                if (event.target === mediaModal) {
                    mediaModal.style.display = 'none';

                    // Pause any playing videos
                    const modalVideo = modalMediaContainer.querySelector('video');
                    if (modalVideo) {
                        modalVideo.pause();
                    }
                }
            });

            // Comment Menu Toggle
            document.querySelectorAll('.comment-menu-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const menu = this.nextElementSibling;
                    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
                });
            });

            // Close comment menus when clicking outside
            window.addEventListener('click', function() {
                document.querySelectorAll('.comment-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
            });

            // Prevent comment menu from closing when clicking inside
            document.querySelectorAll('.comment-menu').forEach(menu => {
                menu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            });

            // Delete Comment Functionality
            document.querySelectorAll('.delete-comment').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const commentId = this.dataset.commentId;
                    Swal.fire({
                        title: "Delete Comment",
                        text: "Are you sure you want to delete this comment? This action cannot be undone.",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#d33",
                        cancelButtonColor: "#3085d6",
                        confirmButtonText: "Yes, delete it!"
                    }).then(result => {
                        if (result.isConfirmed) {
                            fetch(`../controllers/deleteCommentController.php?comment_id=${commentId}`, {
                                    method: 'POST'
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        // Remove the comment from the UI
                                        const commentElement = document.querySelector(`.comment[data-comment-id="${commentId}"]`);
                                        if (commentElement) {
                                            commentElement.remove();
                                        }
                                        Swal.fire({
                                            title: "Deleted!",
                                            text: "Comment deleted successfully!",
                                            icon: "success",
                                            timer: 1500,
                                            showConfirmButton: false
                                        });
                                        // Check if there are any comments left
                                        const remainingComments = document.querySelectorAll('.comment');
                                        if (remainingComments.length === 0) {
                                            const noCommentsMessage = document.createElement('p');
                                            noCommentsMessage.className = 'no-comments';
                                            noCommentsMessage.textContent = 'No comments yet. Be the first to comment!';
                                            document.querySelector('.comments-list').appendChild(noCommentsMessage);
                                        }
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

            // Character Counter for Comment
            const commentText = document.getElementById('comment-text');
            const charCount = document.getElementById('char-count');
            const submitCommentBtn = document.getElementById('submit-comment-btn');

            commentText.addEventListener('input', function() {
                const length = this.value.length;
                charCount.textContent = length;
                charCount.style.color = length > 900 ? '#e74c3c' : length > 700 ? '#f39c12' : '#95a5a6';
                submitCommentBtn.disabled = length === 0;
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
            // Voting System
            const voteButtons = document.querySelectorAll('.vote-button');

            console.log(`Found ${voteButtons.length} vote buttons`);

            voteButtons.forEach(button => {
                button.addEventListener('click', function() {
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
                            return response.json();
                        })
                        .then(data => {
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
                        })
                        .catch(error => {
                            this.disabled = false;
                            console.error('Error:', error);
                            showMessage('Failed to process vote: ' + error.message, 'error');
                        });
                });
            });

            // Function to show message
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

        document.addEventListener('DOMContentLoaded', function() {
            // Check for comment success message
            <?php if (isset($_SESSION['comment_success'])): ?>
                Swal.fire({
                    text: '<?= $_SESSION['comment_success'] ?>',
                    icon: 'success',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            <?php
                unset($_SESSION['comment_success']);
            endif;
            ?>

            // Check for comment error message
            <?php if (isset($_SESSION['comment_error'])): ?>
                Swal.fire({
                    text: '<?= $_SESSION['comment_error'] ?>',
                    icon: 'error',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            <?php
                unset($_SESSION['comment_error']);
            endif;
            ?>
        });
    </script>
</body>

</html>