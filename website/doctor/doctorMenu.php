<?php
// Get the current request URI for active link highlighting
$currentUri = $_SERVER['REQUEST_URI'];

$baseFileName = (parse_url($currentUri, PHP_URL_PATH));
// xxx/meow.php?success=1   -> PHP_URL_PATH xxx/meow.php

$useremail = isset($_SESSION['user']) ? $_SESSION['user'] : 'noemail@example.com';
$username = 'Doctor';

// If user is logged in, fetch doctor name from DB
if ($useremail !== 'noemail@example.com') {
    $stmt = $database->prepare("SELECT docname FROM doctor WHERE docemail = ?");
    $stmt->bind_param("s", $useremail);
    $stmt->execute();
    $stmt->bind_result($nameResult);
    if ($stmt->fetch()) {
        $username = $nameResult;
    }
    $stmt->close();
}
?>

<script>
    function confirmLogout() {
        return confirm("Are you sure you want to log out?");
    }
</script>

<div class="menu">
    <table class="menu-container" border="0">
        <tr>
            <td style="padding:10px" colspan="2">
                <table border="0" class="profile-container">
                    <tr>
                        <td width="30%" style="padding-left:20px" >
                            <img src="<?= strpos($currentUri, 'communityMain.php') !== false ? '../../img/user.png' : '../img/user.png' ?>" alt="" width="100%" style="border-radius:50%">
                        </td>
                        <td style="padding:0px;margin:0px;">
                            <p class="profile-title"><?php echo substr($username,0,13)  ?>..</p>
                            <p class="profile-subtitle"><?php echo substr($useremail,0,22)  ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <a href="/logout.php" onclick="return confirmLogout();"><input type="button" value="Log out" class="logout-btn btn-primary-soft btn"></a>
                        </td>
                    </tr>
            </table>
            </td>
        </tr>
        <tr class="menu-row" >
            <td class="menu-btn menu-icon-dashbord <?= strpos($currentUri, 'index.php') !== false ? 'menu-active  menu-icon-dashbord-active' : '' ?>" >
                <a href="/doctor/index.php" class="non-style-link-menu <?= strpos($currentUri, 'index.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Dashboard</p></a></div></a>
            </td>
        </tr>
        <tr class="menu-row">
            <td class="menu-btn menu-icon-appoinment <?= strpos($currentUri, 'appointment.php') !== false ? 'menu-active  menu-icon-appoinment-active' : '' ?>">
                <a href="/doctor/appointment.php" class="non-style-link-menu <?= strpos($currentUri, 'appointment.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">My Appointments</p></a></div>
            </td>
        </tr>
        
        <tr class="menu-row" >
            <td class="menu-btn menu-icon-session <?= strpos($currentUri, 'schedule.php') !== false ? 'menu-active  menu-icon-session-active' : '' ?>">
                <a href="/doctor/schedule.php" class="non-style-link-menu <?= strpos($currentUri, 'schedule.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">My Sessions</p></div></a>
            </td>
        </tr>
        <tr class="menu-row" >
            <td class="menu-btn menu-icon-patient <?= strpos($currentUri, 'patient.php') !== false ? 'menu-active  menu-icon-patient-active' : '' ?>">
                <a href="/doctor/patient.php" class="non-style-link-menu <?= strpos($currentUri, 'patient.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">My Patients</p></a></div>
            </td>
        </tr>
        <tr class="menu-row" >
            <td class="menu-btn menu-icon-forums <?= strpos($currentUri, 'communityMain.php') !== false ? 'menu-active  menu-icon-forums-active' : '' ?>">
                <a href="/collaCommunityForum/views/communityMain.php" class="non-style-link-menu <?= strpos($currentUri, 'communityMain.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Forums</p></a></div>
            </td>
        </tr>
        <tr class="menu-row" >
            <td class="menu-btn menu-icon-settings <?= strpos($currentUri, 'settings.php') !== false ? 'menu-active  menu-icon-settings-active' : '' ?>">
                <a href="/doctor/settings.php" class="non-style-link-menu <?= strpos($currentUri, 'settings.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Settings</p></a></div>
            </td>
        </tr>
        
    </table>
</div>