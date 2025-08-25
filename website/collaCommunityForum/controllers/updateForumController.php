<?php
session_start();
require_once(__DIR__ . '/../includes/database/communityDA.php');

// Start output buffering to capture stray output
ob_start();

$communityDA = new CommunityDA();
$error = "";

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $forumId = isset($_POST['forum_id']) ? trim($_POST['forum_id']) : null;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $visibility = isset($_POST['visibility']) ? $_POST['visibility'] : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $otherCategory = isset($_POST['other_category']) ? trim($_POST['other_category']) : '';
    $defaultImage = "assets/image/default_image.png";

    // Handle custom category
    $finalCategory = ($category === 'other' && !empty($otherCategory)) ? $otherCategory : $category;

    // Validate inputs
    if (empty($forumId) || empty($name) || empty($visibility) || empty($finalCategory)) {
        $error = "Community name, visibility, and category are required.";
        sendRedirect("../views/updateForum.php?id=$forumId&error=" . urlencode($error));
        exit();
    }

    // Fetch the current forum details
    $forum = $communityDA->getCommunityById($forumId);
    if (!$forum) {
        $error = "Forum not found.";
        sendRedirect("../views/updateForum.php?id=$forumId&error=" . urlencode($error));
        exit();
    }

    // Handle Image Upload
    $targetFile = $forum['picture_url'];
    $oldImage = $forum['picture_url'];

    if (!empty($_FILES['picture']['name']) && $_FILES['picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadDir = __DIR__ . '/../assets/image/';
        $webRelativeDir = 'assets/image/';

        // Ensure upload directory exists and is writable
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                error_log("Failed to create upload directory: $uploadDir");
                $error = "Failed to create image directory.";
                sendRedirect("../views/updateForum.php?id=$forumId&error=" . urlencode($error));
                exit();
            }
        }
        if (!is_writable($uploadDir)) {
            error_log("Upload directory is not writable: $uploadDir");
            $error = "Upload directory is not writable.";
            sendRedirect("../views/updateForum.php?id=$forumId&error=" . urlencode($error));
            exit();
        }

        $fileName = time() . "_" . basename($_FILES['picture']['name']);
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($fileType, $allowedTypes)) {
            $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
            sendRedirect("../views/updateForum.php?id=$forumId&error=" . urlencode($error));
            exit();
        } elseif ($_FILES['picture']['size'] > 2 * 1024 * 1024) {
            $error = "File size exceeds the maximum limit of 2MB.";
            sendRedirect("../views/updateForum.php?id=$forumId&error=" . urlencode($error));
            exit();
        }

        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['picture']['tmp_name'], $targetPath)) {
            $targetFile = $webRelativeDir . $fileName;
            // Delete old image if it exists and is not default
            if ($oldImage !== $defaultImage && file_exists(__DIR__ . '/../' . $oldImage)) {
                if (!unlink(__DIR__ . '/../' . $oldImage)) {
                    error_log("Failed to delete old image: $oldImage");
                }
            }
        } else {
            error_log("Failed to move uploaded file: {$_FILES['picture']['name']}, error: {$_FILES['picture']['error']}");
            $error = "Error uploading the image. Please try again.";
            sendRedirect("../views/updateForum.php?id=$forumId&error=" . urlencode($error));
            exit();
        }
    }

    // Update Forum in Database
    $updatedData = [
        'community_id' => $forumId,
        'name' => $name,
        'description' => $description,
        'picture_url' => $targetFile,
        'visibility' => $visibility,
        'category' => $finalCategory
    ];

    try {
        $result = $communityDA->updateCommunity($updatedData);
        if ($result === true) {
            $_SESSION['success_message'] = "Forum updated successfully!";
            sendRedirect("../views/updateForum.php?id=$forumId&success_message=1");
            exit();
        } else {
            $error = "Database error: " . $result;
            sendRedirect("../views/updateForum.php?id=$forumId&error=" . urlencode($error));
            exit();
        }
    } catch (Exception $e) {
        error_log("Exception in updateCommunity: " . $e->getMessage());
        $error = "Failed to update forum: " . $e->getMessage();
        sendRedirect("../views/updateForum.php?id=$forumId&error=" . urlencode($error));
        exit();
    }
} else {
    $error = "Invalid request method.";
    sendRedirect("../views/updateForum.php?id=" . urlencode($_POST['forum_id'] ?? '') . "&error=" . urlencode($error));
    exit();
}

// Helper function to send redirect
function sendRedirect($url) {
    ob_end_clean(); // Clear any stray output
    header("Location: $url");
    exit();
}
?>