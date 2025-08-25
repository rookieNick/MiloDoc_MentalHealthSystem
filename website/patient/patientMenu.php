<?php
// Get the current request URI for active link highlighting
$currentUri = $_SERVER['REQUEST_URI'];
// Import database

if(strpos($currentUri, 'collaCommunityForum') !== false){
    include("../../connection.php");
}
else{
    include("../connection.php");
}

// Check session and redirect if necessary
if (isset($_SESSION["user"])) {
    if (($_SESSION["user"]) == "" || $_SESSION['usertype'] != 'p') {
        header("location: ../login.php");
        exit(); // Ensure no further code is executed after redirect
    } else {
        $useremail = $_SESSION["user"];
    }
} else {
    header("location: ../login.php");
    exit();
}
$sqlProfile = "SELECT profile_image FROM webuser WHERE email=?";
$stmtProfile = $database->prepare($sqlProfile);
$stmtProfile->bind_param("s", $useremail);
$stmtProfile->execute();
$resultProfile = $stmtProfile->get_result();
$profileData = $resultProfile->fetch_assoc();
$profileImage = $profileData ? $profileData['profile_image'] : null;
$profileImagePath = $profileImage ? "/patient/profileImage/".$profileImage : "/img/user.png";
$baseFileName = (parse_url($currentUri, PHP_URL_PATH));
// xxx/meow.php?success=1   -> PHP_URL_PATH xxx/meow.php
?>
<script>
    function confirmLogout() {
        return confirm("Are you sure you want to log out?");
    }
</script>

        <div class="rightNavMenu"  id="rightNavMenu">
            <table id="right-menu-container" border="0">
                <tr>
                    <td style="padding:10px" colspan="2">
                        <table border="0" class="profile-container">
                            <tr>
                                <td width="30%" style="padding-left:20px" >
                                    <img src="<?php echo htmlspecialchars($profileImagePath); ?>" alt="Profile Image" style="border-radius:50%; width: 75px; height: 75px; object-fit: cover;" >
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
                    <td class="menu-btn menu-icon-home <?= strpos($currentUri, 'index.php') !== false ? 'menu-active  menu-icon-home-active' : '' ?>" >
                        <a href="/patient/index.php" class="non-style-link-menu <?= strpos($currentUri, 'index.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Home</p></a></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-doctor <?= strpos($currentUri, 'doctors.php') !== false ? 'menu-active  menu-icon-doctor-active' : '' ?>">
                        <a href="/patient/doctors.php" class="non-style-link-menu <?= strpos($currentUri, 'doctors.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">All Doctors</p></a></div>
                    </td>
                </tr>
                
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-session <?= strpos($currentUri, 'schedule.php') !== false ? 'menu-active  menu-icon-session-active' : '' ?>">
                        <a href="/patient/schedule.php" class="non-style-link-menu <?= strpos($currentUri, 'schedule.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Scheduled Sessions</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-appoinment <?= strpos($currentUri, 'appointment.php') !== false ? 'menu-active  menu-icon-appoinment-active' : '' ?>">
                        <a href="/patient/appointment.php" class="non-style-link-menu <?= strpos($currentUri, 'appointment.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">My Bookings</p></a></div>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-mood <?= strpos($currentUri, 'mood.php') !== false ? 'menu-active  menu-icon-mood-active' : '' ?>">
                        <a href="/patient/mood.php" class="non-style-link-menu <?= strpos($currentUri, 'mood.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Daily Mood Check-In</p></a></div>
                    </td>
                </tr>
                <tr class="menu-row">                                             
                    <td class="menu-btn menu-icon-test <?= (strpos($currentUri, 'psychologicalTest.php') !== false || strpos($currentUri, 'viewCategoryTests.php') !== false || strpos($currentUri, 'doTestDetails.php') !== false)? 'menu-active  menu-icon-test-active' : '' ?>">
                        <a href="/patient/psychologicalTest.php" class="non-style-link-menu <?= strpos($currentUri, 'psychologicalTest.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Psychological Test</p></a></div>
                    </td>
                </tr>
                <tr class="menu-row">                                             
                    <td class="menu-btn menu-icon-history <?= (strpos($currentUri, 'patientTestHistory.php') !== false || strpos($currentUri, 'testResult.php') !== false || strpos($currentUri, 'testDashboard.php') !== false ) ? 'menu-active  menu-icon-history-active' : '' ?>">
                        <a href="/patient/patientTestHistory.php" class="non-style-link-menu <?= strpos($currentUri, 'patientTestHistory.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Psychological Test History</p></a></div>
                    </td>
                </tr>
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-games <?= (strpos($currentUri, 'gameLists.php') !== false || strpos($currentUri, 'bingo.php') !== false || strpos($currentUri, 'cardMatching.php') !== false || strpos($currentUri, 'mindfulCounting.php') !== false || strpos($currentUri, 'positivityQuiz.php') !== false) ? 'menu-active  menu-icon-games-active' : '' ?>">
                        <a href="/minigames/gameLists.php" class="non-style-link-menu <?= strpos($currentUri, 'gameLists.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Game Lists</p></a></div>
                    </td>
                </tr>
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-journal <?= (strpos($currentUri, 'journal.php') !== false || strpos($currentUri, 'journal_dashboard.php') !== false || strpos($currentUri, 'journal_detail.php') !== false) ? 'menu-active  menu-icon-journal-active' : '' ?>">
                        <a href="/patient/journal.php" class="non-style-link-menu <?= strpos($currentUri, 'journal.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Journal</p></a></div>
                    </td>
                </tr>
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-forums <?= (strpos($currentUri, 'communityMain.php') !== false || strpos($currentUri, 'createForum.php') !== false || strpos($currentUri, 'myForum.php') !== false || strpos($currentUri, 'findHelp.php') !== false || strpos($currentUri, 'forumDetail.php') !== false) ? 'menu-active  menu-icon-forums-active' : '' ?>">
                        <a href="/collaCommunityForum/views/communityMain.php" class="non-style-link-menu <?= strpos($currentUri, 'communityMain.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Forums</p></a></div>
                    </td>
                </tr>
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-settings <?= strpos($currentUri, 'settings.php') !== false ? 'menu-active  menu-icon-settings-active' : '' ?>">
                        <a href="/patient/settings.php" class="non-style-link-menu <?= strpos($currentUri, 'settings.php') !== false ? 'non-style-link-menu-active' : '' ?>"><div><p class="menu-text">Settings</p></a></div>
                    </td>
                </tr>
                
            </table>
        </div>