<?php
// Get the current request URI for active link highlighting
$currentUri = $_SERVER['REQUEST_URI'];

$baseFileName = (parse_url($currentUri, PHP_URL_PATH));
// xxx/meow.php?success=1   -> PHP_URL_PATH xxx/meow.php
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
                            <img src="../../img/user.png" alt="" width="100%" style="border-radius:50%">
                        </td>
                        <td style="padding:0px;margin:0px;">
                            <p class="profile-title">Administrator</p>
                            <p class="profile-subtitle">admin@mdoc.com</p>
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
                <a href="/admin/index.php" class="non-style-link-menu <?= strpos($currentUri, 'index.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Dashboard</p></a></div></a>
            </td>
        </tr>
        <tr class="menu-row">
            <td class="menu-btn menu-icon-doctor <?= strpos($currentUri, 'doctors.php') !== false ? 'menu-active  menu-icon-doctor-active' : '' ?>">
                <a href="/admin/doctors.php" class="non-style-link-menu <?= strpos($currentUri, 'doctors.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Doctors</p></a></div>
            </td>
        </tr>
        <tr class="menu-row" >
            <td class="menu-btn menu-icon-schedule <?= strpos($currentUri, 'schedule.php') !== false ? 'menu-active  menu-icon-schedule-active' : '' ?>">
                <a href="/admin/schedule.php" class="non-style-link-menu <?= strpos($currentUri, 'schedule.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Schedule</p></div></a>
            </td>
        </tr>
        <tr class="menu-row">
            <td class="menu-btn menu-icon-appoinment <?= strpos($currentUri, 'appointment.php') !== false ? 'menu-active  menu-icon-appoinment-active' : '' ?>">
                <a href="/admin/appointment.php" class="non-style-link-menu <?= strpos($currentUri, 'appointment.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Appointment</p></a></div>
            </td>
        </tr>
        <tr class="menu-row" >
            <td class="menu-btn menu-icon-patient <?= strpos($currentUri, 'patient.php') !== false ? 'menu-active  menu-icon-patient-active' : '' ?>">
                <a href="/admin/patient.php" class="non-style-link-menu <?= strpos($currentUri, 'patient.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Patients</p></a></div>
            </td>
        </tr>
        </tr>        
        <tr class="menu-row" >
            <td class="menu-btn menu-icon-test <?= strpos($currentUri, 'tests.php') !== false ? 'menu-active  menu-icon-test-active' : '' ?>">
                <a href="/admin/tests.php" class="non-style-link-menu <?= strpos($currentUri, 'tests.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Psychological Assessment Tests</p></a></div>
            </td>
        </tr>
        <tr class="menu-row" >
            <td class="menu-btn menu-icon-desc <?= strpos($currentUri, 'testDescription.php') !== false ? 'menu-active  menu-icon-desc-active' : '' ?>">
                <a href="/admin/testDescription.php" class="non-style-link-menu <?= strpos($currentUri, 'testDescription.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Psychological Description</p></a></div>
            </td>
        </tr>
        <tr class="menu-row" >
            <td class="menu-btn menu-icon-games <?= (strpos($currentUri, 'manageGames.php') !== false || strpos($currentUri, 'manageQuiz.php') !== false || strpos($currentUri, 'manageCardMatching.php') !== false || strpos($currentUri, 'manageMindfulCounting.php') !== false || strpos($currentUri, 'manageBingo.php') !== false || strpos($currentUri, 'manageSpaceshipShooter.php') !== false) ? 'menu-active  menu-icon-games-active' : '' ?>">
                <a href="/admin/manageGames.php" class="non-style-link-menu <?= strpos($currentUri, 'manageGames.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Manage Games</p></a></div>
            </td>
        </tr>
        <tr class="menu-row" >
            <td class="menu-btn menu-icon-chatbot <?= (strpos($currentUri, 'adminChatbot.php') !== false || strpos($currentUri, 'adminIngest.php') !== false ) ? 'menu-active  menu-icon-chatbot-active' : '' ?>">
                <a href="/admin/adminChatbot.php" class="non-style-link-menu <?= strpos($currentUri, 'adminChatbot.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Chatbot</p></a></div>
            </td>
        </tr>
        <tr class="menu-row" >
            <td class="menu-btn menu-icon-forums <?= (strpos($currentUri, 'adminForumReport.php') !== false || strpos($currentUri, 'createForum.php') !== false || strpos($currentUri, 'myForum.php') !== false || strpos($currentUri, 'findHelp.php') !== false || strpos($currentUri, 'forumDetail.php') !== false) ? 'menu-active  menu-icon-forums-active' : '' ?>">
                <a href="/collaCommunityForum/views/adminForumReport.php" class="non-style-link-menu <?= strpos($currentUri, 'adminForumReport.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Forum</p></a></div>
            </td>
        </tr>
    </table>
</div>