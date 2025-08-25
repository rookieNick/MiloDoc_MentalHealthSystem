<?php 
session_start();

include_once 'GoogleCalendarApi.php'; 
require_once 'connection.php'; 
require_once 'config.php'; 

$statusMsg = ''; 
$status = 'danger'; 

if (isset($_GET['code'])) {
    $GoogleCalendarApi = new GoogleCalendarApi(); 

    // Get access token from Google
    $data = $GoogleCalendarApi->GetAccessToken(GOOGLE_CLIENT_ID, REDIRECT_URI, GOOGLE_CLIENT_SECRET, $_GET['code']); 
    $access_token = $data['access_token'] ?? '';

    if (!empty($access_token) && isset($_SESSION['user']) && isset($_SESSION['usertype'])) {
        $useremail = $_SESSION['user'];
        $usertype = $_SESSION['usertype'];

        // Save token to correct table
        if ($usertype === 'p') {
            $stmt = $database->prepare("UPDATE patient SET google_access_token = ? WHERE pemail = ?");
        } elseif ($usertype === 'd') {
            $stmt = $database->prepare("UPDATE doctor SET google_access_token = ? WHERE docemail = ?");
        }

        if (isset($stmt)) {
            $stmt->bind_param("ss", $access_token, $useremail);
            $stmt->execute();
        }
    }

    // After saving token, check if it's patient + booking appointment
    $appoid = $_SESSION['last_appoid'] ?? null;

    if (!empty($appoid) && isset($access_token)) {
        // Only for patient making appointment
        $sqlQ = "SELECT 
                    a.appodate, 
                    a.appoid, 
                    s.title, 
                    s.scheduledate, 
                    s.scheduletime, 
                    d.docname, 
                    d.docemail
                FROM appointment a
                JOIN schedule s ON a.scheduleid = s.scheduleid
                JOIN doctor d ON s.docid = d.docid
                WHERE a.appoid = ?";

        $stmt = $database->prepare($sqlQ);
        $stmt->bind_param("i", $appoid);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment = $result->fetch_assoc();

        if (!empty($appointment)) {
            try {
                $calendar_event = array(
                    'summary'     => 'Appointment with Dr. ' . $appointment['docname'],
                    'location'    => 'Clinic / Hospital',
                    'description' => 'Session Title: ' . $appointment['title']
                );

                $event_datetime = array(
                    'event_date'  => $appointment['scheduledate'],
                    'start_time'  => $appointment['scheduletime'],
                    'end_time'    => date("H:i:s", strtotime($appointment['scheduletime'] . ' +30 minutes'))
                );

                $user_timezone = $GoogleCalendarApi->GetUserCalendarTimezone($access_token);

                // Create event in patient's calendar
                $google_event_id_patient = $GoogleCalendarApi->CreateCalendarEvent(
                    $access_token,
                    'primary',
                    $calendar_event,
                    0,
                    $event_datetime,
                    $user_timezone
                );

                // Save patient's event ID
                $stmt = $database->prepare("UPDATE appointment SET googleCalendarId = ? WHERE appoid = ?");
                $stmt->bind_param("si", $google_event_id_patient, $appoid);
                $stmt->execute();

                // Create event in doctor's calendar
                $doctor_email = $appointment['docemail'];
                $stmtDoctor = $database->prepare("SELECT google_access_token FROM doctor WHERE docemail = ?");
                $stmtDoctor->bind_param("s", $doctor_email);
                $stmtDoctor->execute();
                $resultDoctor = $stmtDoctor->get_result();
                $doctorData = $resultDoctor->fetch_assoc();
                $doctor_access_token = $doctorData['google_access_token'] ?? '';

                if (!empty($doctor_access_token)) {
                    $google_event_id_doctor = $GoogleCalendarApi->CreateCalendarEvent(
                        $doctor_access_token,
                        'primary',
                        $calendar_event,
                        0,
                        $event_datetime,
                        $user_timezone
                    );

                    $stmt = $database->prepare("UPDATE appointment SET googleCalendarId_doctor = ? WHERE appoid = ?");
                    $stmt->bind_param("si", $google_event_id_doctor, $appoid);
                    $stmt->execute();
                }

                unset($_SESSION['last_appoid']);
                unset($_SESSION['google_access_token']);

                $status = 'success';
                $statusMsg = '<p>Appointment #' . $appoid . ' has been added to Google Calendar!</p><p><a href="https://calendar.google.com/calendar/" target="_blank">Open Calendar</a></p>';
            } catch (Exception $e) {
                $statusMsg = 'Google API Error: ' . $e->getMessage();
            }
        } else {
            $statusMsg = 'Appointment data not found.';
        }
    } else {
        $statusMsg = 'Access Token updated successfully.';
    }

    $_SESSION['status_response'] = array(
        'status' => $status,
        'status_msg' => $statusMsg
    );

    // Redirect based on user type
    if (isset($_SESSION['usertype'])) {
        if ($_SESSION['usertype'] == 'p') {
            header("Location: patient/index.php");
        } elseif ($_SESSION['usertype'] == 'd') {
            header("Location: doctor/index.php");
        } else {
            header("Location: ../login.php"); 
        }
    } else {
        header("Location: ../login.php"); 
    }
}
?>
