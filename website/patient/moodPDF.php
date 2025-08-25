<?php
session_start();

// 1) Check if user is logged in as patient
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION['usertype'] != 'p') {
    header("location: ../login.php");
    exit;
}

$useremail = $_SESSION["user"];

// Include database connection
include("../connection.php");
require '../vendor/fpdf186/fpdf.php';

// Fetch user details
$userrow   = $database->query("SELECT * FROM patient WHERE pemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid    = $userfetch["pid"];
$username  = $userfetch["pname"];

// Define mood -> numeric score mapping
$moodScores = [
    "Grateful" => 4,
    "Happy"    => 3,
    "Neutral"  => 2,
    "Sad"      => 1,
    "Stress"   => 0
];

// Get month/year from request, default to current if not specified
date_default_timezone_set('Asia/Kolkata');
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear  = isset($_GET['year'])  ? (int)$_GET['year']  : date('Y');

if ($currentMonth < 1) {
    $currentMonth = 1;
}
if ($currentMonth > 12) {
    $currentMonth = 12;
}

$startOfMonth = date("Y-m-d", mktime(0, 0, 0, $currentMonth, 1, $currentYear));
$endOfMonth   = date("Y-m-d", mktime(0, 0, 0, $currentMonth + 1, 0, $currentYear));

// Calculate number of days in the month
$numberOfDays = (int)date('t', strtotime($startOfMonth)); // This will give you the correct number of days in the current month.


// Query mood data for that month
$stmt = $database->prepare("
    SELECT dateCreated, mood
    FROM mood
    WHERE pid = ?
      AND dateCreated BETWEEN ? AND ?
    ORDER BY dateCreated ASC
");
$stmt->bind_param("iss", $userid, $startOfMonth, $endOfMonth);
$stmt->execute();
$res = $stmt->get_result();

$moodData = [];
while ($row = $res->fetch_assoc()) {
    $moodData[$row['dateCreated']] = $row['mood'];
}
$stmt->close();

// Build arrays for data processing
$chartLabels = [];
$chartData   = [];


for ($day = 1; $day <= $numberOfDays; $day++) {
    $thisDate = sprintf("%04d-%02d-%02d", $currentYear, $currentMonth, $day);
    $chartLabels[] = $day; // Just use day number for cleaner display

    if (isset($moodData[$thisDate])) {
        $theMood = $moodData[$thisDate];
        $score   = isset($moodScores[$theMood]) ? $moodScores[$theMood] : 0;
        $chartData[] = $score;
    } else {
        $chartData[] = null; // No mood logged that day
    }
}

// Check if enough data
$validEntries = array_filter($chartData, function ($val) {
    return $val !== null;
});

$hasEnoughData = count($validEntries) >= 10;

// Calculate weekly averages / overall score
$weeklyAverages = [];
$totalScore = 0;

if ($hasEnoughData) {
    $tempSum   = 0;
    $tempCount = 0;
    $allSum    = 0;
    $allCount  = 0;

    for ($i = 0; $i < count($chartData); $i++) {
        $score = $chartData[$i];

        if ($score !== null) {
            $tempSum   += $score;
            $tempCount += 1;

            $allSum   += $score;
            $allCount += 1;
        }

        // If 7 days passed or end of month
        if ((($i + 1) % 7 === 0) || $i === count($chartData) - 1) {
            $avg = ($tempCount > 0) ? $tempSum / $tempCount : null;
            $weeklyAverages[] = $avg !== null ? round($avg, 2) : "N/A";
            $tempSum   = 0;
            $tempCount = 0;
        }
    }

    if ($allCount > 0) {
        $avgAll = $allSum / $allCount;
        $totalScore = round(($avgAll / 4) * 100);
    }
}

// Determine Grade and Message
$gradeMessage = "";
$gradeTitle = "";

if ($hasEnoughData) {
    if ($totalScore >= 0 && $totalScore <= 20) {
        // Grade E
        $gradeTitle = "Grade E";
        $gradeMessage = "Your overall mood score is quite low this month. We strongly recommend scheduling a consultation with a doctor to discuss how you're feeling. Remember, you are not alone. We believe in you!";
    } elseif ($totalScore >= 21 && $totalScore <= 40) {
        // Grade D
        $gradeTitle = "Grade D";
        $gradeMessage = "Your mood trends are on the lower side. Consider exploring new activities or talking with a friend or counselor. Every step counts!";
    } elseif ($totalScore >= 41 && $totalScore <= 60) {
        // Grade C
        $gradeTitle = "Grade C";
        $gradeMessage = "Your overall mood is moderate. Keep focusing on positive habits and self-care strategies to move toward higher moods!";
    } elseif ($totalScore >= 61 && $totalScore <= 80) {
        // Grade B
        $gradeTitle = "Grade B";
        $gradeMessage = "Great job maintaining a relatively positive mood! Continue doing what works, and don't hesitate to reach out for support if needed.";
    } elseif ($totalScore >= 81 && $totalScore <= 100) {
        // Grade A
        $gradeTitle = "Grade A";
        $gradeMessage = "Fantastic! You've maintained a consistently positive mood this month. Keep up the wonderful work and share your positivity with others!";
    } else {
        // Out of range
        $gradeTitle = "Score Error";
        $gradeMessage = "Score out of expected range. Please contact support.";
    }
}

// Get month name for display
$monthNames = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December"
];
$monthName = $monthNames[$currentMonth - 1];

// Create PDF file
class MoodPDF extends FPDF
{
    function Header()
    {
        // Arial bold 15
        $this->SetFont('Arial', 'B', 20);
        // Move to the right
        $this->Cell(30);
        // Title
        $this->Cell(140, 10, 'MoodTracker', 0, 0, 'L');
        $this->Ln(20);
    }

    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function ChapterTitle($title)
    {
        $this->SetFont('Arial', 'B', 16);
        $this->SetFillColor(0, 128, 128); // Teal color
        $this->SetTextColor(255);
        $this->Cell(0, 10, $title, 0, 1, 'L', true);
        $this->Ln(5);
        $this->SetTextColor(0);
    }

    function GradeBox($grade, $message, $score)
    {
        // Determine color based on grade
        switch ($grade) {
            case 'Grade A':
                $this->SetFillColor(178, 235, 242); // Light Teal
                $this->SetDrawColor(128, 222, 234);
                $this->SetTextColor(0, 105, 92);
                break;
            case 'Grade B':
                $this->SetFillColor(200, 230, 201); // Light Green
                $this->SetDrawColor(165, 214, 167);
                $this->SetTextColor(46, 112, 49);
                break;
            case 'Grade C':
                $this->SetFillColor(255, 243, 205); // Light Yellow
                $this->SetDrawColor(255, 238, 186);
                $this->SetTextColor(133, 100, 4);
                break;
            case 'Grade D':
                $this->SetFillColor(255, 232, 178); // Light Orange
                $this->SetDrawColor(255, 217, 139);
                $this->SetTextColor(128, 88, 32);
                break;
            case 'Grade E':
                $this->SetFillColor(255, 189, 189); // Light Red
                $this->SetDrawColor(255, 156, 156);
                $this->SetTextColor(128, 32, 32);
                break;
            default:
                $this->SetFillColor(240, 240, 240);
                $this->SetDrawColor(200, 200, 200);
                $this->SetTextColor(102, 102, 102);
        }

        // Draw grade box
        $this->SetLineWidth(0.5);
        $this->Rect(10, $this->GetY(), 190, 40, 'DF');

        // Grade title and score
        $this->SetFont('Arial', 'B', 14);
        $this->SetXY(15, $this->GetY() + 5);
        $this->Cell(40, 10, $grade, 0, 0);
        $this->SetXY(160, $this->GetY());
        $this->Cell(30, 10, $score . '/100', 0, 1, 'R');

        // Grade message
        $this->SetFont('Arial', '', 11);
        $this->SetXY(15, $this->GetY() + 5);
        $this->MultiCell(180, 6, $message);

        $this->Ln(10);
    }

    function MoodScaleLegend()
    {
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 10, 'Mood Scale Legend:', 0, 1);

        $this->SetFont('Arial', '', 10);

        // Stress - Teal
        $this->SetFillColor(0, 191, 165);
        $this->Rect(15, $this->GetY(), 5, 5, 'F');
        $this->SetXY(25, $this->GetY());
        $this->Cell(40, 5, '0: Stress', 0, 0);

        // Sad - Orange
        $this->SetFillColor(255, 145, 0);
        $this->Rect(70, $this->GetY(), 5, 5, 'F');
        $this->SetXY(80, $this->GetY());
        $this->Cell(40, 5, '1: Sad', 0, 0);

        // Neutral - Yellow
        $this->SetFillColor(214, 209, 0);
        $this->Rect(125, $this->GetY(), 5, 5, 'F');
        $this->SetXY(135, $this->GetY());
        $this->Cell(40, 5, '2: Neutral', 0, 1);

        $this->Ln(5);

        // Happy - Blue
        $this->SetFillColor(27, 94, 213);
        $this->Rect(15, $this->GetY(), 5, 5, 'F');
        $this->SetXY(25, $this->GetY());
        $this->Cell(40, 5, '3: Happy', 0, 0);

        // Grateful - Purple
        $this->SetFillColor(106, 27, 154);
        $this->Rect(70, $this->GetY(), 5, 5, 'F');
        $this->SetXY(80, $this->GetY());
        $this->Cell(40, 5, '4: Grateful', 0, 1);

        $this->Ln(10);
    }

    function DailyMoodTable($chartLabels, $chartData, $moodScores)
    {
        $this->ChapterTitle('Daily Mood Records');

        // Table header
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(30, 10, 'Day', 1, 0, 'C', true);
        $this->Cell(80, 10, 'Mood', 1, 0, 'C', true);
        $this->Cell(30, 10, 'Score', 1, 1, 'C', true);

        // Reverse the mood scores array to look up mood names
        $scoreToMood = array_flip($moodScores);

        // Table content
        $this->SetFont('Arial', '', 10);
        $fill = false;

        // Group by weeks for cleaner presentation
        $entriesPerPage = 20;
        $entryCount = 0;

        for ($i = 0; $i < count($chartLabels); $i++) {
            if ($entryCount >= $entriesPerPage) {
                $this->AddPage();

                // Reprint header on new page
                $this->SetFont('Arial', 'B', 11);
                $this->SetFillColor(240, 240, 240);
                $this->Cell(30, 10, 'Day', 1, 0, 'C', true);
                $this->Cell(80, 10, 'Mood', 1, 0, 'C', true);
                $this->Cell(30, 10, 'Score', 1, 1, 'C', true);

                $this->SetFont('Arial', '', 10);
                $entryCount = 0;
            }

            if ($chartData[$i] !== null) {
                $this->SetFillColor(245, 245, 245);
                $this->Cell(30, 8, $chartLabels[$i], 1, 0, 'C', $fill);

                // Get mood name from score
                $moodName = isset($scoreToMood[$chartData[$i]]) ? $scoreToMood[$chartData[$i]] : 'Unknown';
                $this->Cell(80, 8, $moodName, 1, 0, 'C', $fill);

                $this->Cell(30, 8, $chartData[$i] . '/4', 1, 1, 'C', $fill);
                $fill = !$fill;
                $entryCount++;
            }
        }

        $this->Ln(10);
    }

    function WeeklyAveragesTable($weeklyAverages)
    {
        $this->ChapterTitle('Weekly Mood Averages');

        // Table header
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(50, 10, 'Week', 1, 0, 'C', true);
        $this->Cell(50, 10, 'Average Score', 1, 1, 'C', true);

        // Table content
        $this->SetFont('Arial', '', 10);
        $fill = false;

        $weekStart = 0;
        foreach ($weeklyAverages as $i => $avg) {
            // Get month/year from request, default to current if not specified
            date_default_timezone_set('Asia/Kolkata');
            $currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
            $currentYear  = isset($_GET['year'])  ? (int)$_GET['year']  : date('Y');

            if ($currentMonth < 1) {
                $currentMonth = 1;
            }
            if ($currentMonth > 12) {
                $currentMonth = 12;
            }
            $weekNumber = $i + 1;
            $startOfMonth = date("Y-m-d", mktime(0, 0, 0, $currentMonth, 1, $currentYear));
            $endOfMonth   = date("Y-m-d", mktime(0, 0, 0, $currentMonth + 1, 0, $currentYear));
            $numberOfDays = (int)date('t', strtotime($startOfMonth));
            // Ensure the week end doesn't exceed the total days in the month
            $weekEnd = ($weekStart + 6) < ($numberOfDays - 1) ? ($weekStart + 6) : ($numberOfDays - 1);

            $this->SetFillColor(245, 245, 245);
            $this->Cell(50, 8, "Week $weekNumber (Days " . ($weekStart + 1) . "-" . ($weekEnd + 1) . ")", 1, 0, 'C', $fill);
            $this->Cell(50, 8, $avg, 1, 1, 'C', $fill);
            $fill = !$fill;

            $weekStart += 7;
        }

        $this->Ln(10);
    }

    
}

// Create new PDF document
$pdf = new MoodPDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Patient and report information
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Mood Analytics Report', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, $monthName . ' ' . $currentYear, 0, 1, 'C');
$pdf->Ln(5);

// Patient info
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(40, 8, 'Patient Name:', 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 8, $username, 0, 1);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(40, 8, 'Generated On:', 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 8, date('Y-m-d H:i:s'), 0, 1);
$pdf->Ln(5);

if ($hasEnoughData) {
    // Overall Statistics
    $pdf->ChapterTitle('Overall Statistics');

    // Create a 2x2 grid for stats
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(95, 10, 'Overall Score:', 0, 0);
    $pdf->Cell(95, 10, 'Days Logged:', 0, 1);

    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(95, 10, $totalScore . '/100', 0, 0);
    $pdf->Cell(95, 10, count($validEntries) . '/' . $numberOfDays, 0, 1);

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(95, 10, 'Average Mood Level:', 0, 0);
    $pdf->Cell(95, 10, 'Grade:', 0, 1);

    $pdf->SetFont('Arial', '', 11);
    $avgMood = $allCount > 0 ? number_format($allSum / $allCount, 1) . '/4.0' : 'N/A';
    $pdf->Cell(95, 10, $avgMood, 0, 0);
    $pdf->Cell(95, 10, substr($gradeTitle, -1), 0, 1);

    $pdf->Ln(10);

    // Grade message box
    $pdf->GradeBox($gradeTitle, $gradeMessage, $totalScore);

    // Add mood scale legend
    $pdf->MoodScaleLegend();

    // Weekly averages table
    $pdf->WeeklyAveragesTable($weeklyAverages);

    // Daily mood records table
    $pdf->DailyMoodTable($chartLabels, $chartData, $moodScores);

    
} else {
    // Not enough data message
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Not Enough Data', 0, 1, 'C');

    $pdf->SetFont('Arial', '', 11);
    $pdf->MultiCell(0, 8, 'We need at least 10 mood entries to generate a meaningful report for the selected month. Continue tracking your mood daily to unlock valuable insights about your emotional wellbeing!');
}

// Set headers to open in a new tab
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="mood_report_' . $monthName . '_' . $currentYear . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Output PDF
$pdf->Output('I', 'mood_report_' . $monthName . '_' . $currentYear . '.pdf');
