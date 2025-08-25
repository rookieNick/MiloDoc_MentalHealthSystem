<?php
include("../connection.php");

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $database->prepare("UPDATE patient SET ban_until = NULL WHERE pid = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: patient.php?unbanned=true");
    } else {
        header("Location: patient.php?error=unban_fail");
    }
    $stmt->close();
} else {
    header("Location: patient.php");
}
exit;
?>
