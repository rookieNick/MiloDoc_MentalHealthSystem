<?php
session_start();

if (isset($_SESSION["user"])) {
    if ($_SESSION["user"] == "" || $_SESSION['usertype'] != 'a') {
        header("location: ../login.php");
        exit();
    }
} else {
    header("location: ../login.php");
    exit();
}

if($_GET){
    //import database
    require("../connection.php");
    require_once '../config.php'; 
    require_once '../GoogleCalendarApi.php'; 
    $id=$_GET["id"];
    $calendarId = 'primary';
    $googleCalendar = new GoogleCalendarApi();

    // Get patient's token and patient calendar ID
    $sql = "SELECT a.googleCalendarId, p.google_access_token
            FROM appointment a
            INNER JOIN patient p ON a.pid = p.pid
            WHERE a.appoid = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $calendarEventIdPatient = $row['googleCalendarId'] ?? null;
    $patientAccessToken = $row['google_access_token'] ?? null;

    // Get doctor's token and doctor calendar ID
    $sqlDoc = "SELECT a.googleCalendarId_doctor, d.google_access_token
               FROM appointment a
               INNER JOIN schedule s ON a.scheduleid = s.scheduleid
               INNER JOIN doctor d ON s.docid = d.docid
               WHERE a.appoid = ?";
    $stmtDoc = $database->prepare($sqlDoc);
    $stmtDoc->bind_param("i", $id);
    $stmtDoc->execute();
    $resultDoc = $stmtDoc->get_result();
    $rowDoc = $resultDoc->fetch_assoc();
    $calendarEventIdDoctor = $rowDoc['googleCalendarId_doctor'] ?? null;
    $doctorAccessToken = $rowDoc['google_access_token'] ?? null;

    // Delete patient's calendar event
    if (!empty($calendarEventIdPatient) && !empty($patientAccessToken)) {
        try {
            $googleCalendar->DeleteCalendarEvent($patientAccessToken, $calendarId, $calendarEventIdPatient);
        } catch (Exception $e) {
            error_log('Patient Calendar Deletion Error: ' . $e->getMessage());
            header("Location: appointment.php?error=calendar_delete");
        }
    }

    // Delete doctor's calendar event
    if (!empty($calendarEventIdDoctor) && !empty($doctorAccessToken)) {
        try {
            $googleCalendar->DeleteCalendarEvent($doctorAccessToken, $calendarId, $calendarEventIdDoctor);
        } catch (Exception $e) {
            error_log('Doctor Calendar Deletion Error: ' . $e->getMessage());
            header("Location: appointment.php?error=calendar_delete_doctor");
            exit();
        }
    }

    // Update appointment status to 'Completed'
    $sqlmain = "UPDATE appointment SET status = 'Completed', googleCalendarId = NULL, googleCalendarId_doctor = NULL WHERE appoid=?";
    $stmt = $database->prepare($sqlmain);
    $stmt->bind_param("i", $id);
    $stmt->execute();        
    header("location: index.php");
}
?>
