<?php
// Start the session
session_start();

// Check if the user is logged in and has the correct usertype
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION['usertype'] != 'a') {
    header("location: ../login.php");
    exit();
}

$useremail = $_SESSION["user"];

// Import database connection
include("../connection.php");

// Directory to store profile images
$uploadDir = __DIR__ . "/../patient/profileImage/";;
// Ensure the directory exists and is writable
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Initialize variables for messages
$error = "";
$success = "";

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_image"])) {
    $file = $_FILES["profile_image"];
    $email = $_POST["email"] ?? $useremail;

    // Validate that the email matches the session email
    if ($_SESSION['usertype'] != 'a') {
        $error = "Unauthorized email address.";
        header("location: patient.php?action=edit&id=" . $_SESSION["user"] . "&error=0&upload_error=" . urlencode($error));
        exit();
    }

    // Validate file upload
    if ($file["error"] == UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        $fileType = mime_content_type($file["tmp_name"]);
        $fileSize = $file["size"];
        $fileExt = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $fileName = uniqid() . "_" . time() . "." . $fileExt; // Unique filename to avoid conflicts
        $destination = $uploadDir . $fileName;

        // Validate file type
        if (!in_array($fileType, $allowedTypes)) {
            $error = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        }
        // Validate file size
        elseif ($fileSize > $maxFileSize) {
            $error = "File size exceeds 5MB limit.";
        }
        // Move the uploaded file
        elseif (!move_uploaded_file($file["tmp_name"], $destination)) {
            $error = "Failed to upload the file.";
        } else {
            // File uploaded successfully, update the database
            try {
                // Get the current profile image to delete it
                $sql = "SELECT profile_image FROM webuser WHERE email=?";
                $stmt = $database->prepare($sql);
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $currentImage = $result->fetch_assoc()['profile_image'];

                // Delete the old image if it exists
                if ($currentImage && file_exists($uploadDir . $currentImage)) {
                    unlink($uploadDir . $currentImage);
                }

                // Update the profile_image field in the webuser table
                $sql = "UPDATE webuser SET profile_image=? WHERE email=?";
                $stmt = $database->prepare($sql);
                $stmt->bind_param("ss", $fileName, $email);
                if ($stmt->execute()) {
                    $success = "Profile image updated successfully.";
                } else {
                    $error = "Failed to update profile image in the database.";
                    // Delete the uploaded file if database update fails
                    if (file_exists($destination)) {
                        unlink($destination);
                    }
                }
                $stmt->close();
            } catch (Exception $e) {
                $error = "Database error: " . $e->getMessage();
                // Delete the uploaded file if an error occurs
                if (file_exists($destination)) {
                    unlink($destination);
                }
            }
        }
    } else {
        $error = "No file uploaded or an upload error occurred.";
    }

    $query = "action=edit&id=" . $_SESSION["user"] . "&error=0";
    if ($error) {
        $query .= "&upload_error=" . urlencode($error);
    } elseif ($success) {
        $query .= "&upload_success=" . urlencode($success);
    }
    header("location: patient.php?" . $query);
    exit();
} else {
    // Invalid request
    header("location: patient.php");
    exit();
}
?>