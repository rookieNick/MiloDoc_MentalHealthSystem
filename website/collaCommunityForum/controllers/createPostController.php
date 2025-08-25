<?php
session_start();
require_once(__DIR__ . '/../includes/database/postDA.php');
require_once(__DIR__ . '/../includes/database/postMediaDA.php');
require_once(__DIR__ . '/../includes/utilities/generatePostID.php');
require_once(__DIR__ . '/../includes/utilities/generatePostMediaID.php');
require_once(__DIR__ . '/../includes/database/userDA.php');
require_once(__DIR__ . '/../../connection.php'); // Include database connection
require_once(__DIR__ . '/../../utils/emailSender.php'); // Include EmailSender 
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug log
error_log("POST request received: " . print_r($_POST, true));
error_log("FILES request received: " . print_r($_FILES, true));

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/forumDetail.php?id=" . urlencode($_POST['community_id'] ?? ''));
    exit;
}

if (isset($_SESSION["user"])) {
    if ($_SESSION["user"] == "" || !in_array($_SESSION['usertype'], ['p', 'a', 'd'])) {
        header("location: ../../login.php");
        exit;
    } else {
        $useremail = $_SESSION["user"];
    }
} else {
    header("location: ../../login.php");
    exit;
}

$userDA = new UserDA();
$userData = $userDA->getUserByEmail($useremail);

// Retrieve basic form fields from POST
$communityId = $_POST['community_id'] ?? null;
$userId      = $userData['user_id'];
$content     = $_POST['content'] ?? "";

// Validate required fields
if (!$communityId || !$userId || !$content) {
    header("Location: ../views/forumDetail.php?id=" . urlencode($communityId) . "&error=Missing+required+fields");
    exit;
}

//detect suicidal message
// Send message to chatbot API
// Detect suicidal message
// Detect suicidal message
$suicidalDetected = false;
$confidence = 0;
try {
    $response = file_get_contents("http://127.0.0.1:8001/detect_suicidal", false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode([
                'message' => $content,
                'memory' => ''
            ])
        ]
    ]));
    $data = json_decode($response, true);
    if (isset($data['is_suicidal']) && $data['is_suicidal'] && isset($data['confidence'])) {
        $suicidalDetected = $data['confidence'] > 0.5; // Threshold for suicidal detection
        $confidence = $data['confidence'];
    }
    error_log("Suicidal detection response: " . print_r($data, true));
} catch (Exception $e) {
    error_log("Suicidal detection API error: " . $e->getMessage());
}

// If suicidal message detected and user is a patient, notify parent
if ($suicidalDetected && $_SESSION['usertype'] === 'p') {
    // Fetch parent email from patient table
    $sql = "SELECT parentemail FROM patient WHERE pemail = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("s", $useremail);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();

    if ($patient && !empty($patient['parentemail'])) {
        $emailSender = new EmailSender();
        if (!$emailSender->sendPostEmail($patient['parentemail'], $content, $confidence)) {
            error_log("Failed to send email to parent: " . $patient['parentemail']);
        } else {
            error_log("Email sent to parent: " . $patient['parentemail']);
        }
    } else {
        error_log("No parent email found for user: " . $useremail);
    }
}
$postId = generatePostId();

// Create the new post record immediately
$postDA = new PostDA();
$postData = [
    "post_id"      => $postId,
    "community_id" => $communityId,
    "author_id"    => $userId,
    "content"      => $content,
];

if (!$postDA->addPost($postData)) {
    header("Location: ../views/forumDetail.php?id=" . urlencode($communityId) . "&error=Failed+to+create+post");
    exit;
}

// Process file uploads (if any)
$uploadDir = __DIR__ . "/../assets/image/postImg/";
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        header("Location: ../views/forumDetail.php?id=" . urlencode($communityId) . "&error=Server+error:+Unable+to+create+upload+directory");
        exit;
    }
}

if (!empty($_FILES['media']['name']) && is_array($_FILES['media']['name'])) {
    $fileCount = count($_FILES['media']['name']);
    $postMediaDA = new PostMediaDA();

    for ($i = 0; $i < $fileCount; $i++) {
        $tmpName  = $_FILES['media']['tmp_name'][$i];
        $fileName = $_FILES['media']['name'][$i];
        $fileType = $_FILES['media']['type'][$i];

        if (!$tmpName) {
            continue;
        }

        $uniqueName = time() . "_" . basename($fileName);
        $targetPath = $uploadDir . $uniqueName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            error_log("File Upload Error: Could not move file $fileName to $targetPath");
            header("Location: ../views/forumDetail.php?id=" . urlencode($communityId) . "&error=File+upload+failed");
            exit;
        }

        $mediaType = (strpos($fileType, 'image/') === 0) ? 'image' : 'video';
        $mediaId = generatePostMediaId();
        $mediaUrl = "assets/image/postImg/" . $uniqueName;

        $postMediaData = [
            "media_id"   => $mediaId,
            "post_id"    => $postId,
            "media_url"  => $mediaUrl,
            "media_type" => $mediaType
        ];

        if (!$postMediaDA->addPostMedia($postMediaData)) {
            error_log("Media Insertion Error: Failed to insert media record for file $uniqueName");
        }
    }
}

// Redirect to forum details page on success
header("Location: ../views/forumDetail.php?id=" . urlencode($communityId) . "&success=Post+created+successfully");
exit;