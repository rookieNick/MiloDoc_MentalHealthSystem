<?php
session_start();
require_once(__DIR__ . '/../includes/database/communityDA.php');
require_once(__DIR__ . '/../includes/database/communityMemberDA.php');
require_once(__DIR__ . '/../includes/utilities/generateCommunityID.php');
require_once(__DIR__ . '/../includes/database/userDA.php');
$communityDA = new CommunityDA();
$communityMemberDA = new CommunityMemberDA();
$userDA = new UserDA();
$errors = [];
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Generate and sanitize inputs
    $community_id = generateCommunityId();
    $name         = trim($_POST['name']);
    $description  = trim($_POST['description']);
    $visibility   = $_POST['visibility'] ?? '';
    $userData =  $userDA->getUserByEmail($useremail);
    $creator_id   = $userData['user_id']; // ideally from session

    // 2) Handle category / other_category
    $allowedCategories = [
        'anxiety',
        'stress',
        'depression',
        'trauma support',
        'mindfulness and meditation'
    ];

    $rawCategory = $_POST['category'] ?? '';
    if ($rawCategory === 'other') {
        $other = trim($_POST['other_category'] ?? '');
        if (empty($other)) {
            $errors[] = 'Please specify your custom category.';
        } else {
            // optionally: normalize to lower-case and strip disallowed chars
            $category = mb_strtolower($other);
        }
    } elseif (in_array($rawCategory, $allowedCategories, true)) {
        $category = $rawCategory;
    } else {
        $errors[] = 'Invalid category selection.';
    }

    // 3) Validate name & visibility
    if (empty($name)) {
        $errors[] = 'Community name is required.';
    }
    if (!in_array($visibility, ['Public', 'Private', 'Anonymous'], true)) {
        $errors[] = 'Invalid visibility selection.';
    }

    // 4) Handle picture upload (unchanged)
    $default_image = "assets/image/default_image.png";
    $target_file   = $default_image;
    $allowedTypes  = ['jpg','jpeg','png','gif'];

    if (!empty($_FILES['picture']['name'])) {
        $base_path = realpath(__DIR__ . '/../assets/image/forumImg');
        if ($base_path === false) {
            $base_path = __DIR__ . '/../assets/image/forumImg';
            if (!mkdir($base_path, 0777, true)) {
                $errors[] = 'Failed to create upload directory.';
            }
        }

        $original_filename = basename($_FILES['picture']['name']);
        $safe_filename     = preg_replace("/[^a-zA-Z0-9.]/", "", $original_filename);
        $file_name         = time() . "_" . $safe_filename;
        $target_path       = $base_path . DIRECTORY_SEPARATOR . $file_name;
        $fileType          = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($fileType, $allowedTypes, true)) {
            $errors[] = 'Invalid file type. Only JPG, JPEG, PNG, and GIF allowed.';
        } elseif ($_FILES['picture']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'File size exceeds 2MB.';
        } else {
            $check = getimagesize($_FILES['picture']['tmp_name']);
            if ($check === false) {
                $errors[] = 'File is not a valid image.';
            } elseif (move_uploaded_file($_FILES['picture']['tmp_name'], $target_path)) {
                $target_file = "assets/image/forumImg/" . $file_name;
            } else {
                $errors[]     = 'Error uploading the image. Using default.';
                $target_file = $default_image;
            }
        }
    }

    // 5) If any errors, redirect back
    if (!empty($errors)) {
        header("Location: ../views/createForum.php?error=" . urlencode(implode(' ', $errors)));
        exit();
    }

    // 6) Prepare data array (including category)
    $communityData = [
        'community_id' => $community_id,
        'name'         => $name,
        'description'  => $description,
        'picture_url'  => $target_file,
        'creator_id'   => $creator_id,
        'visibility'   => $visibility,
        'category'     => $category,
    ];

    // 7) Insert into DB
    $result = $communityDA->addCommunity($communityData);
    if ($result === true) {
        // Generate membership_id based on the latest ID
        $latestMembershipId = $communityMemberDA->getLatestCommunityMemberID();
        if ($latestMembershipId) {
            $numericPart = (int)substr($latestMembershipId, 8);
            $newNumericPart = $numericPart + 1;
            $membership_id = sprintf("MEM%d%02d%04d", date('Y'), date('m'), $newNumericPart);
        } else {
            $membership_id = sprintf("MEM%d%02d0001", date('Y'), date('m'));
        }

        // Add the creator as the first community member with the role 'Admin'
        $memberResult = $communityMemberDA->addCommunityMember($membership_id, $community_id, $creator_id, 'Admin');
        if ($memberResult === true) {
            $_SESSION['success_message'] = 'Community created successfully.';
            header("Location: ../views/createForum.php?success=1");
            exit();
        } else {
            $errors[] = 'Failed to add creator as admin member.';
            header("Location: ../views/createForum.php?error=" . urlencode(implode(' ', $errors)));
            exit();
        }
    } else {
        $errors[] = 'Database error: ' . $result;
        header("Location: ../views/createForum.php?error=" . urlencode(implode(' ', $errors)));
        exit();
    }
}