
<?php
session_start();

ini_set("display_errors", "0");
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

// Send a JSON response and stop execution
function sendJson($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Handle unexpected PHP exceptions
set_exception_handler(function ($exception) {
    error_log("TechPath AI error: " . $exception->getMessage());

    sendJson(500, array(
        "error" => "A server error occurred. Check the Apache error log."
    ));
});

// Check login
if (!isset($_SESSION["user_id"])) {
    sendJson(401, array(
        "error" => "Please log in again."
    ));
}

// Accept POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendJson(405, array(
        "error" => "POST request required."
    ));
}

// Read the JSON request
$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);

if (!is_array($input)) {
    sendJson(400, array(
        "error" => "Invalid request. Please refresh the page and try again."
    ));
}

// Accept either "message" or "question"
$question = "";

if (isset($input["message"]) && is_string($input["message"])) {
    $question = trim($input["message"]);
} elseif (isset($input["question"]) && is_string($input["question"])) {
    $question = trim($input["question"]);
}

// Validate question
if ($question === "") {
    sendJson(400, array(
        "error" => "Please enter a question."
    ));
}

if (strlen($question) > 2000) {
    sendJson(400, array(
        "error" => "Please enter a question up to 2000 characters."
    ));
}

// Load Gemini configuration
$keyFile = __DIR__ . "/gemini_config.php";

if (!file_exists($keyFile)) {
    sendJson(500, array(
        "error" => "gemini_config.php was not found in your project folder."
    ));
}

require_once $keyFile;

// Check API key configuration
if (
    !defined("GEMINI_API_KEY") ||
    trim(GEMINI_API_KEY) === "" ||
    GEMINI_API_KEY === "REAL_KEY_HERE" ||
    GEMINI_API_KEY === "YOUR_ACTUAL_GEMINI_KEY" ||
    GEMINI_API_KEY === "PASTE_YOUR_REAL_GEMINI_API_KEY_HERE"
) {
    sendJson(500, array(
        "error" => "Please configure your Gemini API key in gemini_config.php."
    ));
}

// Check cURL availability
if (!function_exists("curl_init")) {
    sendJson(500, array(
        "error" => "PHP cURL is disabled. Enable extension=curl in php.ini and restart Apache."
    ));
}

// Create the career-advisor prompt
$prompt = "You are TechPath AI, a friendly career advisor for college students.

Answer the student's actual question, not just a fixed list of topics.
Help with technology careers, programming, college projects, internships,
interviews, resumes, skill development, and study plans.
For unrelated questions, respond helpfully when appropriate and explain
your career-advisor focus when needed.
Use simple English, practical steps, and examples.
Never guarantee jobs or placements.

Student question: " . $question;

// Prepare Gemini request
$payload = array(
    "contents" => array(
        array(
            "parts" => array(
                array(
                    "text" => $prompt
                )
            )
        )
    )
);

// Gemini API endpoint
$model = "gemini-3.8-flash";

$url = "https://generativelanguage.googleapis.com/v1beta/models/"
     . $model
     . ":generateContent";

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Content-Type: application/json",
    "x-goog-api-key: " . GEMINI_API_KEY
));
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
curl_setopt($ch, CURLOPT_TIMEOUT, 45);

// Call Gemini
$response = curl_exec($ch);
$curlError = curl_error($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

// Handle network or SSL errors
if ($response === false) {
    error_log("Gemini cURL error: " . $curlError);

    sendJson(502, array(
        "error" => "Could not connect to Gemini. Check your internet and PHP SSL configuration."
    ));
}

// Decode Gemini response
$result = json_decode($response, true);

// Handle Gemini API errors
if ($status < 200 || $status >= 300) {
    $apiMessage = "Gemini API returned HTTP " . $status . ".";

    if (
        is_array($result) &&
        isset($result["error"]["message"])
    ) {
        $apiMessage = $result["error"]["message"];
    }

    error_log("Gemini API error HTTP " . $status . ": " . $apiMessage);

    sendJson(502, array(
        "error" => $apiMessage,
        "http_status" => $status
    ));
}

// Check response format
if (!is_array($result)) {
    sendJson(502, array(
        "error" => "Gemini returned an invalid response."
    ));
}

// Extract the answer
$answer = "";

if (
    isset($result["candidates"][0]["content"]["parts"]) &&
    is_array($result["candidates"][0]["content"]["parts"])
) {
    foreach ($result["candidates"][0]["content"]["parts"] as $part) {
        if (isset($part["text"])) {
            $answer .= $part["text"];
        }
    }
}

// Return the AI answer
if (trim($answer) !== "") {
    sendJson(200, array(
        "answer" => trim($answer),
        "reply" => trim($answer)
    ));
}

// Handle empty responses
sendJson(502, array(
    "error" => "Gemini returned no text answer. Please try another question."
));
?>
