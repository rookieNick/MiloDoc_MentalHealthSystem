<?php
session_start();
require_once(__DIR__ . '/../includes/database/communityDA.php');
require_once(__DIR__ . '/../includes/database/postDA.php');
require_once(__DIR__ . '/../includes/database/communityMemberDA.php');
require_once(__DIR__ . '/../includes/database/userDA.php');
$communityDA = new CommunityDA();
$userDA = new UserDA();
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
$userData = $userDA->getUserByEmail($useremail);
$userId = $userData['user_id'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Help - MiloDoc Mental Health Community</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="../assets/css/findHelp.css" rel="stylesheet">
    <!-- Removed communityMain.css link -->
</head>

<body>
    <div class="container">
        <?php include 'rightForumNav.php'; ?>

        <!-- Header -->
        <header>
            <h1>FIND HELP</h1>
            <p>Access professional support and resources for your mental health needs</p>
        </header>

        <!-- Navigation -->
        <nav class="navbar">
            <div class="nav-links">
                <a href="communityMain.php">All Forums</a>
                <a href="myForum.php">My Forums</a>
                <a href="findHelp.php" class="active">Find Help</a>
            </div>
        </nav>

        <!-- Emergency Support Banner -->
        <div class="emergency-support">
            <i class="fas fa-phone-alt"></i>
            <div class="emergency-content">
                <h3>Need Immediate Support?</h3>
                <p>If you're in crisis or need urgent help, don't hesitate to reach out to these professional services in your region.</p>
            </div>
        </div>

        <!-- Main Content -->
        <section class="helplines-section">
            <h2><i class="fas fa-hands-helping"></i> Crisis Helplines</h2>
            <div class="helplines-grid">
                <!-- Malaysia -->
                <div class="helpline-card">
                    <i class="fas fa-phone-alt"></i>
                    <h3>Befrienders Malaysia</h3>
                    <p>24/7 emotional support for those in distress, despair, or with suicidal thoughts.</p>
                    <a href="tel:+60376272929" class="helpline-contact">Call: +603-7627-2929</a>
                    <a href="mailto:sam@befrienders.org.my" class="helpline-contact">Email: sam@befrienders.org.my</a>
                    <a href="https://www.befrienders.org.my" target="_blank" class="helpline-link">Visit Website</a>
                </div>
                <div class="helpline-card">
                    <i class="fas fa-comment-medical"></i>
                    <h3>Mental Illness Awareness and Support Association (MIASA)</h3>
                    <p>24/7 crisis support and counseling for mental health issues.</p>
                    <a href="tel:1800180066" class="helpline-contact">Call: 1800-180-066</a>
                    <a href="https://wa.me/+60397656088" class="helpline-contact">WhatsApp: +603-9765-6088</a>
                    <a href="https://www.miasa.org.my" target="_blank" class="helpline-link">Visit Website</a>
                </div>
                <!-- Singapore -->
                <div class="helpline-card">
                    <i class="fas fa-heartbeat"></i>
                    <h3>Samaritans of Singapore (SOS)</h3>
                    <p>24/7 confidential support for those in crisis or contemplating suicide.</p>
                    <a href="tel:1767" class="helpline-contact">Call: 1767</a>
                    <a href="https://wa.me/+6591511767" class="helpline-contact">WhatsApp: +65 9151-1767</a>
                    <a href="https://www.sos.org.sg" target="_blank" class="helpline-link">Visit Website</a>
                </div>
                <div class="helpline-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Institute of Mental Health (IMH) Helpline</h3>
                    <p>24/7 emergency support for mental health crises.</p>
                    <a href="tel:+6563892222" class="helpline-contact">Call: +65 6389-2222</a>
                    <a href="https://www.imh.com.sg" target="_blank" class="helpline-link">Visit Website</a>
                </div>
                <!-- Brunei -->
                <div class="helpline-card">
                    <i class="fas fa-phone-alt"></i>
                    <h3>Hope Line 145 (Talian Harapan)</h3>
                    <p>Confidential support for mental health and suicide prevention, available 8 AM to 11 PM daily.</p>
                    <a href="tel:145" class="helpline-contact">Call: 145</a>
                    <a href="https://www.moh.gov.bn" target="_blank" class="helpline-link">Visit Ministry of Health</a>
                </div>
                <!-- Thailand -->
                <div class="helpline-card">
                    <i class="fas fa-comment-medical"></i>
                    <h3>Thai Mental Health Hotline</h3>
                    <p>24/7 support for mental health issues and suicide prevention.</p>
                    <a href="tel:1323" class="helpline-contact">Call: 1323</a>
                    <a href="https://www.dmh.go.th" target="_blank" class="helpline-link">Visit Department of Mental Health</a>
                </div>
                <div class="helpline-card">
                    <i class="fas fa-heartbeat"></i>
                    <h3>Samaritans of Thailand</h3>
                    <p>24/7 emotional support for those in distress or at risk of suicide.</p>
                    <a href="tel:+6621136789" class="helpline-contact">Call: +66 2 113 6789</a>
                    <a href="https://www.samaritansthailand.org" target="_blank" class="helpline-link">Visit Website</a>
                </div>
            </div>
        </section>

        <section class="find-therapist">
            <h2><i class="fas fa-user-md"></i> Find a Therapist</h2>
            <p>Connecting with a licensed mental health professional can make a significant difference. Explore these trusted platforms to find a therapist that suits your needs:</p>
            <div class="therapist-resources">
                <div class="resource-card">
                    <h3>Psychology Test</h3>
                    <p>Search for therapists by completing the test prepared by professionals</p>
                    <a href="../../patient/psychologicalTest.php" target="_blank" class="resource-link"><i class="fas fa-external-link-alt"></i>Do a Psychology Test</a>
                </div>
                <div class="resource-card">
                    <h3>BetterHelp</h3>
                    <p>Online therapy with licensed professionals, accessible anywhere.</p>
                    <a href="../../patient/doctors.php" target="_blank" class="resource-link"><i class="fas fa-external-link-alt"></i> Get Started</a>
                </div>
                <div class="resource-card">
                    <h3>MindEase Mini-Games</h3>
                    <p>Play engaging mini-games designed to help manage stress, anxiety, and other mental health challenges.</p>
                    <a href="../../minigames/gameLists.php" target="_blank" class="resource-link"><i class="fas fa-external-link-alt"></i> Try Mini-Games</a>
                </div>
            </div>
        </section>

        <section class="additional-resources">
            <h2><i class="fas fa-book-medical"></i> Additional Resources</h2>
            <p>Explore these organizations for more mental health support and information:</p>
            <ul class="resource-list">
                <li><a href="https://www.samhsa.gov" target="_blank">SAMHSA</a> - Substance Abuse and Mental Health Services Administration.</li>
                <li><a href="https://www.mhanational.org" target="_blank">Mental Health America</a> - Advocacy and resources for mental wellness.</li>
            </ul>
        </section>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>About MiloDoc</h3>
                    <p>MiloDoc is dedicated to creating supportive spaces for people dealing with mental health challenges. Our mission is to connect, educate, and empower through shared experiences.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="#"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="#"><i class="fas fa-users"></i> Communities</a></li>
                        <li><a href="#"><i class="fas fa-book"></i> Resources</a></li>
                        <li><a href="#"><i class="fas fa-question-circle"></i> FAQ</a></li>
                        <li><a href="#"><i class="fas fa-shield-alt"></i> Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Support</h3>
                    <ul>
                        <li><a href="#"><i class="fas fa-phone"></i> Crisis Hotlines</a></li>
                        <li><a href="#"><i class="fas fa-user-md"></i> Find a Therapist</a></li>
                        <li><a href="#"><i class="fas fa-book-medical"></i> Mental Health Resources</a></li>
                        <li><a href="#"><i class="fas fa-hand-holding-heart"></i> Volunteer</a></li>
                        <li><a href="#"><i class="fas fa-donate"></i> Donate</a></li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>© 2025 MiloDoc Mental Health Community. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>

</html>