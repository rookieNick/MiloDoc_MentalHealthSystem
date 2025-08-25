<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Bingo Missions</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/bingo.css">

</head>
<?php 
session_start();
include(__DIR__ . '../../connection.php');
include(__DIR__ . '/gamedb.php');

$gameId = 1;
$gameStatus = getGameStatusById($gameId);
if ($gameStatus == 0) {
    $gameName = getGameNameById($gameId);
    $gameNameUrl = urlencode($gameName);
    header("Location: /minigames/underMaintenance.php?game=" . $gameNameUrl);
    exit;
}

  $userEmail = $_SESSION["user"];
  $playId = getOrCreateBingoPlayId($userEmail); // get playId is set in db, if not create it

  // Fetch bingo missions from the database
  $bingoMissions = getBingoMissionsByPlayId($playId);

// Ensure session is set
if (!isset($_SESSION['completed_missions'])) {
    $_SESSION['completed_missions'] = [];
}


$missionStatuses = [];

foreach ($bingoMissions as $mission) {
  $i = $mission['missionNumber']; // This is the missionNumber from DB
  $target = $mission['target'];
  $completed = false;

  switch ($i) {
      case 1:
          $completed = hasPlayedDifferentGamesThisWeek($userEmail, $target+1);
          break;
      case 2:
          $completed = hasReachedTotalFromWeeklyHighScores($userEmail, $target);
          break;
      case 3:
          $completed = hasSurvivedXSecondsInGame5ThisWeek($userEmail, $target);
          break;
      case 4:
          $completed = hasScoredAtLeastXPercentInGame3ThisWeek($userEmail, $target);
          break;
      case 5:
          $completed = hasDestroyedXEnemiesInGame5ThisWeek($userEmail, $target);
          break;
      case 6:
          $completed = hasScoredXInGame4ThisWeek($userEmail, $target);
          break;
      case 7:
          $completed = hasPlayedMindfulCountingWithHighDifficulty($userEmail, $target);
          break;
      case 8:
          $completed = hasUserConversatedThisWeek($userEmail, $target);
          break;
      case 9:
          $completed = hasPostedInForum($userEmail, $target);
          break;
      case 10:
          $completed = hasUserScheduledAppointmentsAtLeast($userEmail, $target);
          break;
      case 11:
          $completed = hasCheckedInAtLeast($userEmail, $target);
          break;
      case 12:
          $completed = hasUserVotedAtLeast($userEmail, $target);
          break;
  }

  if ($completed) {
      $claimed = hasClaimedMission($i, $playId); // rewardStatus = 2
      $missionStatuses[$i] = $claimed ? 2 : 1;
  } else {
      $missionStatuses[$i] = 0;
  }
}
?>
<body>
<?php if (isset($_GET['claimed']) && is_numeric($_GET['claimed']) && $_GET['claimed'] > 0): ?>
  <div class="alert alert-success alert-dismissible fade show alert-fixed-top" role="alert">
    You've claimed <?= htmlspecialchars($_GET['claimed']) ?> bonus points!
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

  <div class="container">
    <div class="bingo-board">
      <div id="bingoGrid" class="grid"></div>
    </div>
    <div class="progress-section">
      <button onclick="goBack()" class="back-btn">Back to gamelist</button>
      <div>
        <h2>Wellness Quest Bingo</h2>
        <p>Complete rows, columns, or diagonals to unlock increasing bonus rewards with each line.</p>
      </div>
      
      <div class="progress-info">
      <h2>Progress</h2>
    <div class="reward-labels">
        <span id="reward48" style="left: 28%;">100 pts</span>
        <span id="reward49" style="left: 60%;">50 pts</span>
        <span id="reward50" style="left: 88%;">50 pts</span>
    </div>
    <div class="progress-wrapper">
        <div class="progress-bar" id="progressBar">
        <div class="progress-fill" id="progressFill"></div>
        </div>
        <span id="progressPercent" class="progress-percent">0%</span>
    </div>
    <button id="claimBonusBtn" class="claim-bonus" disabled>Claim Bonus</button>
    </div>
      <p class="reset-note">Progress resets every Monday 12AM.</p>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<script>
    const missions = <?php echo json_encode($bingoMissions); ?>;
    const missionStatuses = <?php echo json_encode($missionStatuses); ?>;

    let bonusClaimed = false; // Flag to track if bonus has been claimed
    let alreadyClaimedMissions = []; // Will be filled with claimed missionNumbers
    let playId = <?php echo (int)$playId; ?>; // Your actual playId from PHP


    // On page load, check if anything is already claimed
    missions.forEach(m => {
        if (m.rewardStatus === 'claimed') {
            alreadyClaimedMissions.push(m.missionNumber);
        }
    });

    let completedLines = 0;

missions.forEach((mission) => {
  mission.missionNumber = mission.missionNumber;
  mission.missionType = mission.type;
  mission.missionName = mission.name;
  mission.missionDescription = mission.desc;

  // Use PHP-provided status from missionStatuses
  mission.completed = missionStatuses[mission.missionNumber] ?? 0;  // Default to 0 if missing
});

missions.forEach(mission => {
        const span = document.getElementById('reward' + mission.missionNumber);
        console.log('reward' + mission.missionNumber, mission.rewardStatus);
        if (span) {
            if (mission.rewardStatus === 'claimed') {
                span.innerHTML = '✔';
            }
        }
    });

    function loadBingoBoard() {
  const bingoGrid = document.getElementById("bingoGrid");
  bingoGrid.innerHTML = "";

  console.log(missions);

  missions.forEach((m, index) => {
  if ([48, 49, 50].includes(m.missionNumber)) return; // Skip extra missions

  const div = document.createElement("div");
  div.className = "mission-box";
  if (m.completed) div.classList.add("completed");

  let targetLabel = "";
  switch (m.missionType) {
    case "game":
      targetLabel = `Target: Play ${m.target} game(s)`;
      break;
    case "scoreTarget":
      targetLabel = `Target: Score ${m.target}`;
      break;
    case "requiredTime":
      targetLabel = `Target: ${m.target} second(s)`;
      break;
    case "percentage":
      targetLabel = `Target: Score ${m.target}% or higher`;
      break;
    case "level":
      let levelLabel = '';
      switch (m.target) {
        case 1:
          levelLabel = 'Target: Reach any level (Easy or higher)';
          break;
        case 2:
          levelLabel = 'Target: Reach Medium level or higher';
          break;
        case 3:
          levelLabel = 'Target: Reach Hard level or higher';
          break;
        case 4:
          levelLabel = 'Target: Reach Extreme level or higher';
          break;
        default:
          levelLabel = 'Target: Reach a high level';
          break;
      }
      targetLabel = levelLabel;
      break;
    case "requiredDay":
      targetLabel = `Target: Check in for ${m.target} day(s)`;
      break;
    case "target":
      targetLabel = `Target: ${m.target}`;
      break;
    case "other":
    default:
      targetLabel = `Target: ${m.target}`;
      break;
  }

  div.innerHTML = `
    <div class="mission-name">${m.missionName}</div>
    <div class="mission-desc">${m.missionDescription}</div>
    <div class="mission-target">${targetLabel}</div>
    <button class="claim-btn">Completed</button>
  `;
  bingoGrid.appendChild(div);
});

}


// Function to determine which missionNumbers should be claimed based on progress
function getClaimableMissions(percentage) {
    const claimMap = {
        33: [48],
        67: [48, 49],
        100: [48, 49, 50]
    };

    const target = claimMap[percentage] || [];
    return target.filter(m => !alreadyClaimedMissions.includes(m));
}

// Update claim button style and logic based on progress
function updateClaimButton(percentage) {
    const claimBtn = document.getElementById("claimBonusBtn");

    const toClaim = getClaimableMissions(percentage);
    console.log("Claimable missions:", toClaim);
    if (percentage === 0) {
        const messages = ["Keep Going!", "Almost There!", "More Progress Needed"];
        const randomMessage = messages[Math.floor(Math.random() * messages.length)];

        claimBtn.disabled = true;
        claimBtn.classList.add("claimed");
        claimBtn.innerText = randomMessage;
        return;
    } else if (alreadyClaimedMissions.length >= 3 || toClaim.length === 0) {
        claimBtn.disabled = true;
        claimBtn.classList.add("claimed");
        claimBtn.innerText = "Claimed";
        bonusClaimed = true;
    } else {
        claimBtn.disabled = false;
        claimBtn.classList.remove("claimed");
        claimBtn.innerText = "Claim Bonus";
    }

    // Save for click handler
    claimBtn.dataset.claimTargets = JSON.stringify(toClaim);
}

function claimReward(missionNumber) {
  // Simulate API call
  fetch("bingo-claim.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `missionNumber=${missionNumber}&updateType=3`
  })
    .then(res => res.json())
    .then(data => {
      console.log("Reward claimed:", data);
      missions[index].completed = true;
      checkLines();
      loadBingoBoard();
    });
}

function checkLines() {
  const winPatterns = [
    [0,1,2],[3,4,5],[6,7,8], // rows
    [0,3,6],[1,4,7],[2,5,8], // cols
    [0,4,8],[2,4,6]          // diagonals
  ];

  completedLines = winPatterns.filter(pat =>
    pat.every(i => missions[i].completed)
  ).length;

  const progressFill = document.getElementById("progressFill");
  const progressPercent = document.getElementById("progressPercent");
  const claimBtn = document.getElementById("claimBonusBtn");

  const displayLines = Math.min(completedLines, 3);
  const percentage = Math.round((displayLines / 3) * 100);

  progressFill.style.width = `${percentage}%`;
  progressPercent.innerText = `${percentage}%`;

  // Update progress bar color based on stage
  if (percentage <= 33) {
  progressFill.style.background = "#ff9800"; // Orange
} else if (percentage <= 67) {
  progressFill.style.background = "#ffeb3b"; // Yellow
} else {
  progressFill.style.background = "#4caf50"; // Green
}

updateClaimButton(percentage);
}

document.getElementById("claimBonusBtn").addEventListener("click", () => {
    if (bonusClaimed) return;

    const btn = document.getElementById("claimBonusBtn");
    const claimTargets = JSON.parse(btn.dataset.claimTargets || "[]");

    if (claimTargets.length === 0) return;

    claimBonusMissions(playId, claimTargets);

});

function claimBonusMissions(playId, claimTargets) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "bingo-claim.php";

    const playIdInput = document.createElement("input");
    playIdInput.type = "hidden";
    playIdInput.name = "playId";
    playIdInput.value = playId;
    form.appendChild(playIdInput);

    const missionsInput = document.createElement("input");
    missionsInput.type = "hidden";
    missionsInput.name = "missions";
    missionsInput.value = claimTargets.join(",");
    form.appendChild(missionsInput);

    document.body.appendChild(form);
    form.submit();
}

function goBack() {
  window.location.href = "/minigames/gameLists.php";
}

loadBingoBoard();
checkLines();

</script>