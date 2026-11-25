
<?php

$GROQ_API_KEY = require 'D:/xampp/private/groq_secret.php';

$url = "https://api.groq.com/openai/v1/chat/completions";

$data = [
    "model" => "openai/gpt-oss-20b",
    "messages" => [
        [
            "role" => "user",
            "content" => "Reply with: Groq connection successful"
        ]
    ],
    "temperature" => 0.2
];

$options = [
    "http" => [
        "header" => [
            "Content-Type: application/json",
            "Authorization: Bearer " . $GROQ_API_KEY
        ],
        "method" => "POST",
        "content" => json_encode($data),
        "ignore_errors" => true,
        "timeout" => 30
    ]
];

$context = stream_context_create($options);
$response = file_get_contents($url, false, $context);

if ($response === false) {
    exit("Connection failed. Check PHP network settings.");
}

$result = json_decode($response, true);

if (isset($result["error"])) {
    echo "Groq Error: " . htmlspecialchars(
        $result["error"]["message"] ?? "Unknown error"
    );
} else {
    echo "<h2>Groq Connected Successfully!</h2>";
    echo htmlspecialchars(
        $result["choices"][0]["message"]["content"] ?? "No response"
    );
}
