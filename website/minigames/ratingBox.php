<?php
date_default_timezone_set('Asia/Kuala_Lumpur');

include(__DIR__ . '../../connection.php');

$userEmail = $_SESSION["user"];
$gameId = $_SESSION["currentGame"];
$ratingValue = getUserGameRating($userEmail, $gameId);
$currentUri = $_SERVER['REQUEST_URI'];

$ratingSuccess = isset($_GET['successRating']) && $_GET['successRating'] == 1;
    
?>
<style>
    .rating-box {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #e3f2fd; /* Light blue */
        padding: 20px 50px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        text-align: center;
        display: none;
        z-index: 10;
    }
    #submitButton, #closeButton, #okBtn {
            margin: 5px;
            padding: 10px;
            font-size: 16px;
            cursor: pointer;
        }
    .star {
    font-size: 30px;
    cursor: pointer;
    color: #ddd; /* Default gray */
    transition: color 0.3s;
}

.star.selected, .star.hovered {
    color: gold; /* Filled stars */
}

#rate-success {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px 70px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    text-align: center;
    display: none;
    z-index: 10;
}
</style>

<!-- Rating Box (Hidden Initially) -->
<form id="ratingForm" action="submitRating.php" method="POST">
    <input type="hidden" name="ratingValue" id="ratingValue" value="<?php echo $ratingValue !== null ? $ratingValue : '1'; ?>">
    <input type="hidden" name="gameId" id="gameId">
    <div id="rating-box" class="rating-box">
        <h2>Rate This Game</h2>
        <p>How would you rate your experience?</p>
        <div class="star-rating">
            <span class="star" data-value="1">★</span>
            <span class="star" data-value="2">★</span>
            <span class="star" data-value="3">★</span>
            <span class="star" data-value="4">★</span>
            <span class="star" data-value="5">★</span>
        </div>
        <br>
        <button type="submit" id="submitButton">Submit</button>
        <button type="button" id="closeButton" onclick="closeRating()">Close</button>
    </div>
</form>

<!-- Success Popup -->
<div id="rate-success" class="popup" style="display: none;">
    <h2>Rating Submitted!</h2>
    <p>Thank you for your feedback.</p>
    <button id="okBtn" onclick="closeSuccessPopup()">OK</button>
</div>

<script>
    let currentGameId = null;
    let selectedRating = 1; // Default 1 star selected
    var currentUri = "<?php echo $currentUri; ?>";

    document.addEventListener("DOMContentLoaded", function () {
        const stars = document.querySelectorAll(".star");
        const ratingValue = document.getElementById("ratingValue");

        // Pre-fill stars if a rating exists
        if (ratingValue.value) {
            highlightStars(ratingValue.value);
        }

        stars.forEach(star => {
            star.addEventListener("mouseover", function () {
                highlightStars(this.getAttribute("data-value"));
            });

            star.addEventListener("mouseout", function () {
                highlightStars(ratingValue.value); // Reset to selected stars
            });

            star.addEventListener("click", function () {
                ratingValue.value = this.getAttribute("data-value"); // Update hidden input
                highlightStars(ratingValue.value);
            });
        });

        function highlightStars(value) {
            stars.forEach(star => {
                star.classList.toggle("selected", star.getAttribute("data-value") <= value);
            });
        }


        let ratingSuccess = <?php echo $ratingSuccess ? 'true' : 'false'; ?>;
        if (ratingSuccess) {        
            if(currentUri.includes("cardMatching.php")){
                document.getElementById("changeCardImgPanel").style.display = "none";
                document.getElementById("gameInfo").style.display = "none";
                document.getElementById("bottomBtns").style.display = "none";
                document.getElementById("ratingSuccessBox").style.display = "grid";
                document.getElementById("changeCardBtn").style.display = "none";
                disableAllCards();
            }
            else if(currentUri.includes("mindfulCounting.php")){
                document.getElementById("rate-success").style.display = "block";
                document.getElementById("level-popup").style.display = "none";
            }
            else if (currentUri.includes("SubmitQuiz.php")) {
                document.getElementById("rate-success").style.display = "block";
                document.getElementById("quiz-result-box").style.display = "none";
            }
            else if(currentUri.includes("spaceshipShooter.php")){
                document.getElementById("rate-success").style.display = "block";
                document.getElementById("instruction-box").style.display = "none";
                document.getElementById("backBtn").style.display = "none";
                document.getElementById("powerShopBtn").style.display = "none";
            }
            
        }
    });

    function resetStars() {
        document.querySelectorAll(".star").forEach(star => star.classList.remove("selected"));
    }

    function rateGame(gameId) {
        currentGameId = gameId; // Store the game ID for submission
        document.getElementById("rating-box").style.display = "block";

        if(currentGameId == 1){
            document.getElementById("gameId").value = currentGameId; // Set hidden input value
        }
        else if(currentGameId == 2){
            document.getElementById("gameId").value = currentGameId; // Set hidden input value
            document.getElementById("game-over-box").style.display = "none";
        }
        else if(currentGameId == 3){
            document.getElementById("gameId").value = currentGameId; // Set hidden input value
            document.getElementById("quiz-result-box").style.display = "none";
        }
        else if(currentGameId == 5){
            document.getElementById("gameId").value = currentGameId; // Set hidden input value
            document.getElementById("game-over-box").style.display = "none";
        }
    }

    function closeRating() {
        document.getElementById("rating-box").style.display = "none";

        if(currentGameId == 1){
            
        }
        else if(currentGameId == 2){
            document.getElementById("game-over-box").style.display = "block";
        }
        else if(currentGameId == 3){
            document.getElementById("quiz-result-box").style.display = "block";
        }
        else if(currentGameId == 5){
            document.getElementById("game-over-box").style.display = "block";
        }
    }

    function submitRating() {
        const rating = document.querySelector('input[name="rating"]:checked');
        if (!rating) {
            alert("Please select a rating!");
            return;
        }

        console.log(`Game ID: ${currentGameId}, Rating: ${rating.value}`);

        // Send rating to backend (example)
        fetch("/submitRating", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ gameId: currentGameId, rating: rating.value }),
        })
        .then(response => response.json())
        .then(data => {
            alert("Rating submitted successfully!");
            closeRating();
        })
        .catch(error => console.error("Error:", error));
    }

    function closeSuccessPopup() {
        if(currentUri.includes("cardMatching.php")){
            window.location.href = "/minigames/cardMatching.php";
        }
        else if(currentUri.includes("mindfulCounting.php")){
            window.location.href = "/minigames/mindfulCounting.php";
        }
        else if (currentUri.includes("SubmitQuiz.php")) {
            document.getElementById("rate-success").style.display = "none";
            document.getElementById("quiz-result-box").style.display = "block";
        }
        else if(currentUri.includes("spaceshipShooter.php")){
            window.location.href = "/minigames/spaceshipShooter.php";
        }
    }
</script>