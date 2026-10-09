
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Career Advisor | TechPath AI</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #172554;
        }

        .container {
            max-width: 850px;
            margin: 30px auto;
            padding: 15px;
        }

        .header, .chat-box {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            margin-bottom: 18px;
        }

        h1 { color: #4338ca; margin-top: 0; }

        .chat-box { min-height: 350px; }

        .message {
            padding: 13px 16px;
            margin: 12px 0;
            border-radius: 12px;
            line-height: 1.5;
            max-width: 85%;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .bot { background: #e0e7ff; margin-right: auto; }
        .user { background: #4338ca; color: white; margin-left: auto; }

        .input-area {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        input {
            flex: 1;
            min-width: 0;
            padding: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            font-size: 15px;
        }

        button {
            padding: 13px 20px;
            border: none;
            border-radius: 9px;
            background: #4338ca;
            color: white;
            cursor: pointer;
            font-size: 15px;
        }

        button:hover { background: #3730a3; }
        button:disabled { opacity: 0.6; cursor: wait; }

        .suggestions button {
            margin: 5px 5px 5px 0;
            background: #eef2ff;
            color: #3730a3;
        }

        .back {
            display: inline-block;
            margin-bottom: 15px;
            color: #4338ca;
            text-decoration: none;
        }

        @media (max-width: 500px) {
            .input-area { flex-direction: column; }
            .message { max-width: 95%; }
        }
    </style>
</head>

<body>
<div class="container">

    <a class="back" href="dashboard.php">← Back to Dashboard</a>

    <div class="header">
        <h1>🤖 TechPath AI Career Advisor</h1>
        <p>Ask questions about careers, skills, internships, projects, and learning paths.</p>
    </div>

    <div class="chat-box">
        <div id="messages">
            <div class="message bot">
                Hi! 👋 I'm your TechPath Career Advisor.

                Ask me about technology careers, programming, projects, internships, resumes, or study plans!
            </div>
        </div>

        <div class="suggestions">
            <p><strong>Try asking:</strong></p>

            <button type="button"
                onclick="suggestQuestion('How do I become a web developer?')">
                Web Developer
            </button>

            <button type="button"
                onclick="suggestQuestion('What skills do I need for AI and Machine Learning?')">
                AI and ML Skills
            </button>

            <button type="button"
                onclick="suggestQuestion('How can I prepare for an internship?')">
                Internship Preparation
            </button>
        </div>

        <form class="input-area" id="chatForm">
            <input
                type="text"
                id="question"
                placeholder="Ask any career-related question..."
                autocomplete="off"
                required
            >
            <button type="submit" id="sendButton">Send</button>
        </form>
    </div>
</div>

<script>
var messages = document.getElementById("messages");
var questionInput = document.getElementById("question");
var chatForm = document.getElementById("chatForm");
var sendButton = document.getElementById("sendButton");

function addMessage(text, type) {
    var message = document.createElement("div");
    message.className = "message " + type;
    message.textContent = text;
    messages.appendChild(message);
    message.scrollIntoView({ behavior: "smooth", block: "nearest" });
}

function suggestQuestion(question) {
    questionInput.value = question;
    questionInput.focus();
}

chatForm.addEventListener("submit", async function(event) {
    event.preventDefault();

    var question = questionInput.value.trim();

    if (question === "") {
        return;
    }

    addMessage(question, "user");
    questionInput.value = "";
    questionInput.disabled = true;
    sendButton.disabled = true;
    sendButton.textContent = "Thinking...";

    var loadingMessage = document.createElement("div");
    loadingMessage.className = "message bot";
    loadingMessage.textContent = "Thinking...";
    messages.appendChild(loadingMessage);

    try {
        var response = await fetch("ai_chat.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify({
                message: question
            })
        });

        var responseText = await response.text();
        var data;

        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error("Unexpected server response:", responseText);
            throw new Error(
                "The server returned HTML instead of JSON. Check ai_chat.php for PHP errors."
            );
        }

        if (!response.ok) {
            console.error("AI server error:", data);
            throw new Error(
                data.error ||
                "The AI request failed. HTTP status: " + response.status
            );
        }

        var answer = data.reply || data.answer;

        if (!answer) {
            throw new Error("The server returned no answer. Check ai_chat.php.");
        }

        loadingMessage.textContent = answer;

    } catch (error) {
        console.error("AI Advisor error:", error);
        loadingMessage.textContent =
            "Sorry, I couldn't get an AI response. " + error.message;
    } finally {
        questionInput.disabled = false;
        sendButton.disabled = false;
        sendButton.textContent = "Send";
        questionInput.focus();
    }
});
</script>

</body>
</html>
