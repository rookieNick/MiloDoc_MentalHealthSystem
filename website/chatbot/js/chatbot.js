$(document).ready(function () {
    $("#chatbotIcon, #closeChatbot").on("click", function () {
        let chatbot = $("#chatbotContainer");

        if (chatbot.hasClass("active")) {
            chatbot.css({ "opacity": "0", "transform": "translateY(20px)" });
            setTimeout(() => {
                chatbot.removeClass("active").hide();
            }, 300);
        } else {
            chatbot.show();
            setTimeout(() => {
                chatbot.addClass("active").css({ "opacity": "1", "transform": "translateY(0)" });
            }, 10);
        }
    });
});
